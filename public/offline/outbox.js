/*
 * ISSOBS offline outbox (docs/offline-sync.md).
 *
 * Saves attendance sign-ins / kiosk marks on the phone (IndexedDB) and uploads them when the
 * phone is online. Used by the sign-in page, the supervisor kiosk and the service worker (sw.js),
 * so it touches no DOM.
 *
 *   IssobsOutbox.add(queue, payload, label)   save one item (payload must carry a uuid)
 *   IssobsOutbox.flush(queue)                 upload what is waiting; resolves to a summary
 *   IssobsOutbox.flushAll()                   every queue (used by the service worker)
 *   IssobsOutbox.setQueue(queue, config)      { url, auth: 'none' | 'kiosk', tokenKey, body: {...} }
 *   IssobsOutbox.pending(queue) / results(queue, n) / get(key) / set(key, value)
 *
 * The server records each uuid once, so an item sent twice (connection dropped after the server
 * saved it) is never saved twice. An item is removed from the phone only when the server has
 * answered for it; "retry" answers, network errors, an expired kiosk token or a read-only
 * subscription leave it waiting.
 */
(function (root) {
    'use strict';

    var DB_NAME = 'issobs-outbox', DB_VERSION = 1, BATCH = 20;
    var dbPromise = null, running = {};

    function open() {
        if (dbPromise) return dbPromise;
        dbPromise = new Promise(function (resolve, reject) {
            var req = indexedDB.open(DB_NAME, DB_VERSION);
            req.onupgradeneeded = function () {
                var db = req.result;
                if (!db.objectStoreNames.contains('items')) db.createObjectStore('items', { keyPath: 'uuid' }).createIndex('queue', 'queue');
                if (!db.objectStoreNames.contains('results')) db.createObjectStore('results', { keyPath: 'uuid' }).createIndex('queue', 'queue');
                if (!db.objectStoreNames.contains('meta')) db.createObjectStore('meta');
            };
            req.onsuccess = function () { resolve(req.result); };
            req.onerror = function () { dbPromise = null; reject(req.error); };
        });
        return dbPromise;
    }

    function tx(store, mode, fn) {
        return open().then(function (db) {
            return new Promise(function (resolve, reject) {
                var t = db.transaction(store, mode), out;
                var s = t.objectStore(store);
                Promise.resolve(fn(s)).then(function (v) { out = v; });
                t.oncomplete = function () { resolve(out); };
                t.onerror = t.onabort = function () { reject(t.error); };
            });
        });
    }

    function req2p(r) {
        return new Promise(function (resolve, reject) { r.onsuccess = function () { resolve(r.result); }; r.onerror = function () { reject(r.error); }; });
    }

    function byQueue(store, queue) {
        return tx(store, 'readonly', function (s) { return req2p(s.index('queue').getAll(queue)); })
            .then(function (rows) { return (rows || []).sort(function (a, b) { return a.created - b.created; }); });
    }

    var api = {
        uuid: function () {
            if (root.crypto && crypto.randomUUID) return crypto.randomUUID();
            var b = new Uint8Array(16); crypto.getRandomValues(b);
            b[6] = (b[6] & 15) | 64; b[8] = (b[8] & 63) | 128;
            var h = Array.prototype.map.call(b, function (x) { return (x + 256).toString(16).slice(1); }).join('');
            return h.slice(0, 8) + '-' + h.slice(8, 12) + '-' + h.slice(12, 16) + '-' + h.slice(16, 20) + '-' + h.slice(20);
        },

        get: function (key) { return tx('meta', 'readonly', function (s) { return req2p(s.get(key)); }); },
        set: function (key, value) { return tx('meta', 'readwrite', function (s) { s.put(value, key); }); },

        setQueue: function (queue, config) { return api.set('queue:' + queue, config); },

        add: function (queue, payload, label) {
            return tx('items', 'readwrite', function (s) {
                s.put({ uuid: payload.uuid, queue: queue, payload: payload, label: label || '', created: Date.now() });
            }).then(function () { return payload.uuid; });
        },

        pending: function (queue) { return byQueue('items', queue); },

        /** Last n answers for this queue, newest first. */
        results: function (queue, n) {
            return byQueue('results', queue).then(function (rows) { return rows.reverse().slice(0, n || 10); });
        },

        /**
         * Upload everything waiting in this queue.
         * Resolves to { results: [...], offline, auth, locked, error, left } — never rejects.
         */
        flush: function (queue) {
            if (running[queue]) return running[queue];
            var summary = { results: [], offline: false, auth: false, locked: false, error: null, left: 0 };
            var p = api.get('queue:' + queue).then(function (config) {
                if (!config || !config.url) { summary.error = 'not set up'; return summary; }
                var tokenGet = config.auth === 'kiosk' ? api.get(config.tokenKey || 'kioskToken') : Promise.resolve(null);
                return tokenGet.then(function (kt) { return loop(queue, config, kt, summary); });
            }).catch(function (e) { summary.error = String(e && e.message || e); return summary; })
              .then(function (s) { return api.pending(queue).then(function (rows) { s.left = rows.length; return s; }); })
              .finally(function () { delete running[queue]; });
            running[queue] = p;
            return p;
        },

        flushAll: function () {
            return tx('meta', 'readonly', function (s) { return req2p(s.getAllKeys()); }).then(function (keys) {
                var queues = (keys || []).filter(function (k) { return String(k).indexOf('queue:') === 0; }).map(function (k) { return String(k).slice(6); });
                return Promise.all(queues.map(api.flush));
            });
        },
    };

    function loop(queue, config, kioskToken, summary) {
        return api.pending(queue).then(function (rows) {
            if (!rows.length) return summary;
            var batch = rows.slice(0, BATCH);
            var headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
            if (config.auth === 'kiosk') {
                if (!kioskToken || !kioskToken.token) { summary.auth = true; return summary; }
                headers.Authorization = 'Bearer ' + kioskToken.token;
            }
            var body = Object.assign({}, config.body || {}, { sent_at: Date.now(), items: batch.map(function (r) { return r.payload; }) });

            return fetch(config.url, { method: 'POST', headers: headers, body: JSON.stringify(body), credentials: 'omit', cache: 'no-store' })
                .then(function (res) {
                    return res.json().catch(function () { return {}; }).then(function (j) { return { res: res, j: j }; });
                }, function () { summary.offline = true; return null; })
                .then(function (r) {
                    if (!r) return summary;
                    var res = r.res, j = r.j;
                    if (res.status === 200 && Array.isArray(j.results)) {
                        return settle(queue, batch, j.results, summary).then(function (progress) {
                            return progress ? loop(queue, config, kioskToken, summary) : summary;
                        });
                    }
                    if (res.status === 410 || res.status === 422) {
                        // The poster was replaced, or the server cannot read these items: tell the user and stop sending them.
                        var msg = j.message || 'This could not be uploaded. Please do it again.';
                        return settle(queue, batch, batch.map(function (b) { return { uuid: b.uuid, result: 'rejected', message: msg }; }), summary)
                            .then(function () { return loop(queue, config, kioskToken, summary); });
                    }
                    if (res.status === 401) summary.auth = true;
                    else if (res.status === 402) summary.locked = true;
                    else summary.error = j.message || ('Server answered ' + res.status);
                    return summary;
                });
        });
    }

    /** Save the server's answers and drop answered items. Resolves true if anything left the queue. */
    function settle(queue, batch, results, summary) {
        var byUuid = {};
        batch.forEach(function (b) { byUuid[b.uuid] = b; });
        var done = results.filter(function (r) { return r && byUuid[r.uuid] && r.result !== 'retry'; });
        summary.results = summary.results.concat(results);
        if (!done.length) return Promise.resolve(false);

        return open().then(function (db) {
            return new Promise(function (resolve, reject) {
                var t = db.transaction(['items', 'results'], 'readwrite');
                done.forEach(function (r) {
                    var item = byUuid[r.uuid];
                    t.objectStore('items').delete(r.uuid);
                    t.objectStore('results').put({ uuid: r.uuid, queue: queue, label: item.label, result: r.result, status: r.status || null,
                        message: r.message || '', employee_id: r.employee_id || null, made: item.payload.captured_at || item.created, created: Date.now() });
                });
                t.oncomplete = function () { resolve(true); };
                t.onerror = t.onabort = function () { reject(t.error); };
            });
        }).then(function (ok) { return trimResults(queue).then(function () { return ok; }); });
    }

    function trimResults(queue) {
        return byQueue('results', queue).then(function (rows) {
            if (rows.length <= 50) return;
            var old = rows.slice(0, rows.length - 50);
            return tx('results', 'readwrite', function (s) { old.forEach(function (r) { s.delete(r.uuid); }); });
        });
    }

    root.IssobsOutbox = api;
})(typeof self !== 'undefined' ? self : this);

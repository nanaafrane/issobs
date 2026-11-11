/*
 * ISSOBS drafts on this device (docs/offline-sync.md, "Drafts").
 *
 * A form marked <form data-draft="invoice:12" data-draft-label="Invoice for DVLA" data-draft-add="#add">
 * is saved on this device (IndexedDB) as you type. If the connection drops, nothing typed is lost:
 *  - press Save while offline and the entry is kept as a draft "waiting to send";
 *  - open the form again (even with no signal: the page is kept on the device) and the draft is
 *    put back, to check and save once the connection is back.
 * A draft is removed once the server has really saved the form (the next page shows a success message).
 *
 * Drafts never go to the server on their own: invoices, receipts and the rest are always checked
 * and saved by a person, online, with all the usual checks (balances, numbers, approvals).
 * Not kept: the one-time save code, the security token, passwords and attached files.
 */
(function (root) {
    'use strict';

    var DB = 'issobs-drafts', SKIP = { _token: 1, _method: 1, submission_key: 1 };
    var MAX_AGE = 14 * 864e5, SENT_WINDOW = 15 * 60e3;
    var dbp = null;

    function open() {
        if (dbp) return dbp;
        dbp = new Promise(function (resolve, reject) {
            var r = indexedDB.open(DB, 1);
            r.onupgradeneeded = function () { r.result.createObjectStore('drafts', { keyPath: 'key' }).createIndex('user', 'user'); };
            r.onsuccess = function () { resolve(r.result); };
            r.onerror = function () { dbp = null; reject(r.error); };
        });
        return dbp;
    }

    function run(mode, fn) {
        return open().then(function (db) {
            return new Promise(function (resolve, reject) {
                var t = db.transaction('drafts', mode), out;
                var r = fn(t.objectStore('drafts'));
                if (r) r.onsuccess = function () { out = r.result; };
                t.oncomplete = function () { resolve(out); };
                t.onerror = t.onabort = function () { reject(t.error); };
            });
        });
    }

    var api = {
        get: function (key) { return run('readonly', function (s) { return s.get(key); }); },
        put: function (rec) { return run('readwrite', function (s) { s.put(rec); }); },
        remove: function (key) { return run('readwrite', function (s) { s.delete(key); }); },
        list: function (user) {
            return run('readonly', function (s) { return s.index('user').getAll(String(user)); })
                .then(function (rows) { return (rows || []).sort(function (a, b) { return b.savedAt - a.savedAt; }); });
        },

        /**
         * On every page: forget drafts the server has saved, and old ones. A draft knows it was saved
         * when the server confirms its form's one-time save code (savedCheck), or, for forms without
         * one, when the page after Save shows a success message (savedNow).
         */
        tidy: function (user, savedNow, savedCheck) {
            if (!user) return Promise.resolve();
            return api.list(user).then(function (rows) {
                var gone = rows.filter(function (r) {
                    return Date.now() - r.savedAt > MAX_AGE || (savedNow && r.sentAt && !r.sentKey && Date.now() - r.sentAt < SENT_WINDOW);
                });
                var ask = rows.filter(function (r) { return r.sentKey && gone.indexOf(r) < 0; });
                var check = ask.length && savedCheck && navigator.onLine
                    ? fetch(savedCheck + '?' + ask.map(function (r) { return 'keys[]=' + encodeURIComponent(r.sentKey); }).join('&'), { credentials: 'same-origin', headers: { Accept: 'application/json' } })
                        .then(function (res) { return res.ok ? res.json() : { saved: [] }; })
                        .then(function (j) { return ask.filter(function (r) { return (j.saved || []).indexOf(r.sentKey) >= 0; }); })
                        .catch(function () { return []; })
                    : Promise.resolve([]);
                return check.then(function (saved) {
                    return Promise.all(gone.concat(saved).map(function (r) { return api.remove(r.key); }));
                });
            });
        },

        serialize: serialize,
        restore: restore,
        attach: attach,
    };

    function fields(form) {
        return Array.prototype.filter.call(form.elements, function (el) {
            if (!el.name || SKIP[el.name] || el.disabled) return false;
            var t = (el.type || '').toLowerCase();
            return ['file', 'submit', 'button', 'reset', 'password', 'image'].indexOf(t) < 0;
        });
    }

    function serialize(form) {
        return fields(form).map(function (el) {
            var t = (el.type || '').toLowerCase();
            if (t === 'checkbox' || t === 'radio') return [el.name, el.value, el.checked];
            if (t === 'select-multiple') return [el.name, Array.prototype.filter.call(el.options, function (o) { return o.selected; }).map(function (o) { return o.value; })];
            return [el.name, el.value];
        });
    }

    function fire(el) {
        ['input', 'change'].forEach(function (type) { el.dispatchEvent(new Event(type, { bubbles: true })); });
        if (root.jQuery && root.jQuery(el).data('select2')) root.jQuery(el).trigger('change.select2');
    }

    /** Put saved values back. Repeating rows (name[]) are added with the form's "+" button first. */
    function restore(form, saved) {
        var want = {}, have = {};
        saved.forEach(function (f) { if (/\[\]$/.test(f[0]) && f.length === 2) want[f[0]] = (want[f[0]] || 0) + 1; });
        fields(form).forEach(function (el) { if (/\[\]$/.test(el.name) && el.type !== 'checkbox' && el.type !== 'radio') have[el.name] = (have[el.name] || 0) + 1; });
        var add = form.dataset.draftAdd && document.querySelector(form.dataset.draftAdd);
        if (add) {
            var missing = 0;
            Object.keys(want).forEach(function (n) { missing = Math.max(missing, want[n] - (have[n] || 0)); });
            for (var i = 0; i < missing; i++) add.click();
        }

        var els = fields(form), used = {}, byName = {};
        els.forEach(function (el) { (byName[el.name] = byName[el.name] || []).push(el); });
        saved.forEach(function (f) {
            var list = byName[f[0]] || [];
            if (f.length === 3) {                       // checkbox / radio: by name and value
                list.forEach(function (el) { if (el.value === f[1] && el.checked !== f[2]) { el.checked = f[2]; fire(el); } });
                return;
            }
            var i = used[f[0]] || 0, el = list[i];
            used[f[0]] = i + 1;
            if (!el) return;
            if (Array.isArray(f[1])) Array.prototype.forEach.call(el.options, function (o) { o.selected = f[1].indexOf(o.value) >= 0; });
            else if (el.value !== f[1]) el.value = f[1];
            fire(el);
        });
    }

    function when(ms) {
        var d = new Date(ms);
        return d.toLocaleDateString([], { weekday: 'short', day: 'numeric', month: 'short' }) + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function bar(form, cls, html, buttons) {
        var old = form.previousElementSibling;
        if (old && old.classList.contains('issobs-draft-bar')) old.remove();
        var div = document.createElement('div');
        div.className = 'alert ' + cls + ' issobs-draft-bar d-flex flex-wrap align-items-center gap-2';
        div.innerHTML = '<div class="me-auto">' + html + '</div>';
        (buttons || []).forEach(function (b) {
            var btn = document.createElement('button');
            btn.type = 'button'; btn.className = 'btn btn-sm ' + b[1]; btn.textContent = b[0];
            btn.addEventListener('click', function () { b[2](); div.remove(); });
            div.appendChild(btn);
        });
        form.parentNode.insertBefore(div, form);
        return div;
    }

    /**
     * cfg: { user, saved (the page shows a success message), errors (the page shows form errors) }
     */
    function attach(form, cfg) {
        if (!cfg.user || !root.indexedDB) return;
        var key = cfg.user + ':' + form.dataset.draft;
        var rec = null, dirty = false, timer = null;

        function save(extra) {
            var now = Date.now();
            rec = Object.assign(rec || { key: key, user: String(cfg.user), formKey: form.dataset.draft }, {
                label: form.dataset.draftLabel || document.title, url: location.pathname + location.search,
                fields: serialize(form), savedAt: now,
            }, extra || {});
            return api.put(rec).catch(function () {});
        }

        form.issobsDraft = {
            /** Called by connection.js when Save is pressed with no connection. */
            keepForLater: function () { return save({ pending: true, sentAt: null }); },
        };

        form.addEventListener('input', function (e) { if (e.isTrusted) { dirty = true; clearTimeout(timer); timer = setTimeout(save, 600); } });
        form.addEventListener('change', function (e) { if (e.isTrusted) { dirty = true; clearTimeout(timer); timer = setTimeout(save, 600); } });
        form.addEventListener('submit', function (e) {
            // Runs after connection.js: if the save was stopped (offline), the draft is already kept.
            var code = form.querySelector('[name="submission_key"]');
            setTimeout(function () { if (!e.defaultPrevented && dirty) save({ pending: false, sentAt: Date.now(), sentKey: code ? code.value : null }); }, 0);
        });

        api.get(key).then(function (found) {
            if (!found || !found.fields || !found.fields.length) return;
            rec = found;
            if (cfg.saved && found.sentAt && !found.sentKey && Date.now() - found.sentAt < SENT_WINDOW) { api.remove(key); rec = null; return; }
            if (cfg.errors && found.sentAt) {
                // The server sent the form back with errors and refilled it: keep the draft, just say so.
                rec.sentAt = null; api.put(rec);
                return;
            }
            if (found.pending) {
                restore(form, found.fields); dirty = true;
                bar(form, 'alert-warning', '<strong>Saved while offline</strong> on ' + when(found.savedAt) + ' and not sent yet. Check the entries below, then press Save.',
                    [['Discard this draft', 'btn-outline-secondary', function () { api.remove(key); rec = null; location.reload(); }]]);
                return;
            }
            bar(form, 'alert-info', 'You have an <strong>unsent draft</strong> of this form on this device from ' + when(found.savedAt) + '.', [
                ['Restore it', 'btn-primary', function () { restore(form, found.fields); dirty = true; }],
                ['Discard', 'btn-outline-secondary', function () { api.remove(key); rec = null; }],
            ]);
        }).catch(function () {});
    }

    root.IssobsDrafts = api;
})(typeof self !== 'undefined' ? self : this);

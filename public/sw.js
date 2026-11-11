/*
 * ISSOBS service worker (docs/offline-sync.md).
 *
 * Only the guard sign-in page (attend/{token}) and the supervisor kiosk (attendance/{id}/kiosk)
 * work offline. Every other page goes straight to the network as before: this worker does not
 * touch it.
 *
 *  - Those two pages: network first; if there is no answer within a few seconds, the copy saved
 *    the last time the page opened online.
 *  - Their scripts (libs/, offline/): saved copy first.
 *  - Background Sync (Android Chrome) and messages from the pages: upload the outbox.
 */
importScripts('offline/outbox.js');

var VERSION = 'issobs-offline-v1';
var SCOPE = new URL(self.registration.scope).pathname;            // e.g. "/" or "/issobs/public/"
var PRECACHE = ['offline/outbox.js', 'libs/html5-qrcode/2.3.8/html5-qrcode.min.js'];
var OFFLINE_PAGES = [/^attend\/[A-Za-z0-9]{32}$/, /^attendance\/\d+\/kiosk$/];
var STATIC = [/^libs\//, /^offline\//];
var NETWORK_WAIT_MS = 5000;

self.addEventListener('install', function (event) {
    event.waitUntil(caches.open(VERSION).then(function (c) { return c.addAll(PRECACHE); }).then(function () { return self.skipWaiting(); }));
});

self.addEventListener('activate', function (event) {
    event.waitUntil(caches.keys().then(function (keys) {
        return Promise.all(keys.filter(function (k) { return k.indexOf('issobs-offline-') === 0 && k !== VERSION; }).map(function (k) { return caches.delete(k); }));
    }).then(function () { return self.clients.claim(); }));
});

function relPath(url) {
    var p = url.pathname;
    return p.indexOf(SCOPE) === 0 ? p.slice(SCOPE.length) : null;
}

self.addEventListener('fetch', function (event) {
    var req = event.request;
    if (req.method !== 'GET') return;
    var url = new URL(req.url);
    if (url.origin !== self.location.origin) return;
    var path = relPath(url);
    if (path === null) return;

    if (req.mode === 'navigate' && OFFLINE_PAGES.some(function (r) { return r.test(path); })) {
        event.respondWith(networkFirst(req));
    } else if (STATIC.some(function (r) { return r.test(path); })) {
        event.respondWith(cacheFirst(req));
    }
});

function networkFirst(req) {
    var key = req.url.split('?')[0] + (new URL(req.url).search.indexOf('shift=') >= 0 ? new URL(req.url).search : '');
    return caches.open(VERSION).then(function (cache) {
        var network = fetch(req).then(function (res) {
            // Keep only real pages: not the login page a logged-out kiosk is sent to, not errors.
            if (res.ok && !res.redirected && res.type === 'basic') cache.put(key, res.clone());
            return res;
        });
        var timeout = new Promise(function (resolve) { setTimeout(resolve, NETWORK_WAIT_MS); });
        return Promise.race([network.catch(function () { return null; }), timeout]).then(function (res) {
            if (res) return res;
            return cache.match(key).then(function (saved) { return saved || network; });
        });
    });
}

function cacheFirst(req) {
    return caches.open(VERSION).then(function (cache) {
        return cache.match(req).then(function (saved) {
            return saved || fetch(req).then(function (res) { if (res.ok) cache.put(req, res.clone()); return res; });
        });
    });
}

function flushAndTell() {
    return IssobsOutbox.flushAll().then(function (summaries) {
        return self.clients.matchAll({ type: 'window' }).then(function (clients) {
            clients.forEach(function (c) { c.postMessage({ type: 'issobs-outbox-flushed', summaries: summaries }); });
        });
    });
}

self.addEventListener('sync', function (event) {
    if (event.tag === 'issobs-outbox') event.waitUntil(flushAndTell());
});

self.addEventListener('message', function (event) {
    if (event.data && event.data.type === 'issobs-outbox-flush') event.waitUntil(flushAndTell());
});

/*
 * ISSOBS service worker (docs/offline-sync.md).
 *
 *  - Guard sign-in page (attend/{token}) and supervisor kiosk (attendance/{id}/kiosk), and the forms
 *    that keep drafts (invoice, proforma, receipt, fuel, maintenance): network first; with no answer
 *    within a few seconds, the copy kept from the last time the page opened online.
 *  - Any other page that can't be reached: the "You're offline" page (offline/offline.html) instead of
 *    the browser's error. Pages that load normally are not touched or kept.
 *  - Libraries in libs/: kept copy first. Our scripts in offline/: kept copy at once, refreshed in the background.
 *  - Background Sync (Android Chrome) and messages from the pages: upload the attendance outbox.
 *  - Logging out removes the kept form pages.
 */
importScripts('offline/outbox.js');

var VERSION = 'issobs-offline-v3';          // scripts, offline page, attendance pages
var PAGES = 'issobs-pages-v1';             // draft-capable form pages (cleared on logout)
var SCOPE = new URL(self.registration.scope).pathname;            // e.g. "/" or "/issobs/public/"
var PRECACHE = ['offline/outbox.js', 'offline/drafts.js', 'offline/connection.js', 'offline/offline.html',
    'libs/html5-qrcode/2.3.8/html5-qrcode.min.js'];
var ATTENDANCE_PAGES = [/^attend\/[A-Za-z0-9]{32}$/, /^attendance\/\d+\/kiosk$/];
var DRAFT_PAGES = [/^generate\/\d+$/, /^proforma\/create$/, /^receipt-multi\/create$/, /^fleet\/fuel$/, /^fleet\/maintenance$/];
var VERSIONED = [/^libs\//];               // file names carry the library version: kept copy is always right
var OWN_SCRIPTS = [/^offline\//];         // our own scripts: kept copy at once, refreshed in the background
var THEME = [/^(vendor|assets|js|css|fonts|img|images|build)\//];   // what the kept pages need: network first, kept copy offline
var ASSETS = 'issobs-assets-v1';
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

function matches(list, path) { return list.some(function (r) { return r.test(path); }); }

self.addEventListener('fetch', function (event) {
    var req = event.request;
    var url = new URL(req.url);
    if (url.origin !== self.location.origin) return;
    var path = relPath(url);
    if (path === null) return;

    if (req.method === 'POST' && path === 'logout') {
        event.waitUntil(caches.delete(PAGES));
        return;
    }
    if (req.method !== 'GET') return;

    if (req.mode === 'navigate') {
        if (matches(ATTENDANCE_PAGES, path)) event.respondWith(networkFirst(req, VERSION, attendanceKey(req)));
        else if (matches(DRAFT_PAGES, path)) event.respondWith(networkFirst(req, PAGES, req.url));
        else event.respondWith(fetch(req).catch(offlinePage));
    } else if (matches(VERSIONED, path)) {
        event.respondWith(cacheFirst(req));
    } else if (matches(OWN_SCRIPTS, path)) {
        event.respondWith(staleWhileRevalidate(req, event));
    } else if (matches(THEME, path)) {
        event.respondWith(fetch(req).then(function (res) {
            if (res.ok && res.type === 'basic') { var copy = res.clone(); caches.open(ASSETS).then(function (c) { c.put(req, copy); }); }
            return res;
        }).catch(function () {
            return caches.open(ASSETS).then(function (c) { return c.match(req); }).then(function (saved) { return saved || Response.error(); });
        }));
    }
});

function attendanceKey(req) {
    var u = new URL(req.url);
    return u.origin + u.pathname + (u.search.indexOf('shift=') >= 0 ? u.search : '');
}

function offlinePage() {
    return caches.open(VERSION).then(function (c) { return c.match('offline/offline.html'); }).then(function (res) {
        if (!res) return Response.error();
        return res.text().then(function (html) {
            return new Response(html.split('__SCOPE__').join(SCOPE), {
                status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store' },
            });
        });
    });
}

function networkFirst(req, cacheName, key) {
    return caches.open(cacheName).then(function (cache) {
        var network = fetch(req).then(function (res) {
            // Keep only real pages: not the login page a logged-out user is sent to, not errors.
            if (res.ok && !res.redirected && res.type === 'basic') cache.put(key, res.clone());
            return res;
        });
        var timeout = new Promise(function (resolve) { setTimeout(resolve, NETWORK_WAIT_MS); });
        return Promise.race([network.catch(function () { return null; }), timeout]).then(function (res) {
            if (res) return res;
            return cache.match(key).then(function (saved) {
                return saved ? markKept(saved) : network.catch(offlinePage);
            });
        });
    });
}

/** Tell the page it is the copy kept on this device (connection.js reads the meta tag). */
function markKept(res) {
    var when = res.headers.get('Date') ? new Date(res.headers.get('Date')).toISOString() : '';
    return res.text().then(function (html) {
        var tag = '<meta name="issobs-kept-copy" content="' + when + '">';
        html = html.indexOf('</head>') >= 0 ? html.replace('</head>', tag + '</head>') : tag + html;
        var headers = new Headers(res.headers);
        headers.delete('Content-Length');
        return new Response(html, { status: 200, headers: headers });
    });
}

function cacheFirst(req) {
    return caches.open(VERSION).then(function (cache) {
        return cache.match(req).then(function (saved) {
            return saved || fetch(req).then(function (res) { if (res.ok) cache.put(req, res.clone()); return res; });
        });
    });
}

function staleWhileRevalidate(req, event) {
    var key = req.url.split('?')[0];
    return caches.open(VERSION).then(function (cache) {
        return cache.match(key).then(function (saved) {
            var fresh = fetch(req).then(function (res) { if (res.ok) cache.put(key, res.clone()); return res; });
            if (saved) { event.waitUntil(fresh.catch(function () {})); return saved; }
            return fresh;
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

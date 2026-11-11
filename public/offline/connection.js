/*
 * ISSOBS connection helper, on every page (docs/offline-sync.md, "When the connection drops").
 *
 *  - Registers the service worker, which shows a helpful "You're offline" page instead of the
 *    browser's error when a page can't be reached, and keeps the draft-capable forms and the
 *    attendance pages on the device.
 *  - When the connection drops while a page is open: a full "You're offline" panel (instead of the
 *    old red banner) saying what is safe, what still works and what to check. It can be shrunk to
 *    a small note to keep reading the page. It closes by itself when the server answers again.
 *  - Save pressed while offline: the form is NOT sent (which would end on a browser error and lose
 *    what was typed). On forms that keep drafts, the entry is kept on this device to send later.
 *  - Logging out clears the pages kept on this device.
 *
 * Config: window.ISSOBS_CONN = { sw, up, user, userName, rendered, saved, errors }
 */
(function () {
    'use strict';
    var cfg = window.ISSOBS_CONN || {};
    var offlineSince = null, probeTimer = null, panel = null, pill = null, stale = false;

    try { if (cfg.user) localStorage.setItem('issobs_user', JSON.stringify({ id: cfg.user, name: cfg.userName || '' })); } catch (e) {}

    // Opened from the copy kept on this device (the service worker marks it)?
    var kept = document.querySelector('meta[name="issobs-kept-copy"]');
    stale = !!kept;
    var keptAt = kept && kept.content ? new Date(kept.content) : null;

    if ('serviceWorker' in navigator && cfg.sw) {
        navigator.serviceWorker.register(cfg.sw).catch(function () {});
    }

    function hhmm(d) { return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

    var css = '' +
        '.issobs-off{position:fixed;inset:0;z-index:2050;background:rgba(245,246,250,.97);overflow:auto;display:flex;align-items:flex-start;justify-content:center;padding:6vh 16px 24px;font-size:15px;line-height:1.5;color:#384551;font-family:"Public Sans",-apple-system,"Segoe UI",Roboto,Arial,sans-serif}' +
        '.issobs-off .box{max-width:640px;width:100%;background:#fff;border-radius:14px;box-shadow:0 10px 40px rgba(34,48,62,.12);padding:28px 28px 22px}' +
        '.issobs-off h2{margin:0 0 4px;font-size:24px;color:#22303e;display:flex;align-items:center;gap:10px}' +
        '.issobs-off .dot{width:12px;height:12px;border-radius:50%;background:#ff9f43;box-shadow:0 0 0 4px rgba(255,159,67,.2)}' +
        '.issobs-off .lead{color:#646e78;margin:0 0 18px}' +
        '.issobs-off h3{font-size:13px;letter-spacing:.06em;text-transform:uppercase;color:#8592a3;margin:18px 0 6px}' +
        '.issobs-off ul{margin:0;padding-left:20px}.issobs-off li{margin:4px 0;line-height:1.45}' +
        '.issobs-off .note{background:#fff2d6;color:#8a5a00;border-radius:8px;padding:10px 12px;margin:0 0 14px;font-weight:600}' +
        '.issobs-off .drafts a{font-weight:600}' +
        '.issobs-off .foot{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:22px;border-top:1px solid #eceef1;padding-top:16px}' +
        '.issobs-off .status{color:#8592a3;font-size:13px;margin-right:auto}' +
        '.issobs-off a{color:#696cff;font-weight:600}' +
        '.issobs-btn{font:inherit;font-weight:600;font-size:14px;border-radius:8px;padding:8px 14px;border:1px solid #696cff;background:#696cff;color:#fff;cursor:pointer}' +
        '.issobs-btn.ghost{background:#fff;color:#696cff}' +
        '.issobs-pill{font-family:"Public Sans",-apple-system,"Segoe UI",Roboto,Arial,sans-serif;position:fixed;left:16px;bottom:16px;z-index:2050;background:#22303e;color:#fff;border-radius:999px;padding:8px 14px;font-size:14px;box-shadow:0 6px 20px rgba(0,0,0,.2);cursor:pointer;display:flex;gap:8px;align-items:center}' +
        '.issobs-pill .dot{width:9px;height:9px;border-radius:50%;background:#ff9f43}' +
        '.issobs-toast{font-family:"Public Sans",-apple-system,"Segoe UI",Roboto,Arial,sans-serif;position:fixed;left:50%;transform:translateX(-50%);bottom:18px;z-index:2060;background:#28c76f;color:#fff;border-radius:10px;padding:10px 16px;font-weight:600;box-shadow:0 6px 20px rgba(0,0,0,.2);display:flex;gap:10px;align-items:center}' +
        '.issobs-toast button{font:inherit;background:#fff;color:#22303e;border:0;border-radius:6px;padding:4px 10px;font-weight:600;cursor:pointer}';

    function style() {
        if (document.getElementById('issobs-off-css')) return;
        var s = document.createElement('style'); s.id = 'issobs-off-css'; s.textContent = css; document.head.appendChild(s);
    }

    function draftsHtml() {
        if (!window.IssobsDrafts || !cfg.user) return Promise.resolve('');
        return IssobsDrafts.list(cfg.user).then(function (rows) {
            if (!rows.length) return '';
            return '<h3>Drafts on this device</h3><ul class="drafts">' + rows.slice(0, 8).map(function (r) {
                return '<li><a href="' + esc(r.url) + '">' + esc(r.label) + '</a> · ' + (r.pending ? 'waiting to send' : 'unsent draft') + ', ' + esc(new Date(r.savedAt).toLocaleString([], { weekday: 'short', hour: '2-digit', minute: '2-digit' })) + '</li>';
            }).join('') + '</ul>';
        }).catch(function () { return ''; });
    }

    function showPanel(note) {
        style();
        if (pill) { pill.remove(); pill = null; }
        if (!panel) {
            panel = document.createElement('div');
            panel.className = 'issobs-off'; panel.setAttribute('role', 'alertdialog'); panel.setAttribute('aria-live', 'assertive');
            document.body.appendChild(panel);
        }
        draftsHtml().then(function (drafts) {
            if (!panel) return;
            panel.innerHTML = '<div class="box">' +
                '<h2><span class="dot"></span>You\'re offline</h2>' +
                '<p class="lead">No connection to the ISSOBS server' + (offlineSince ? ' since ' + hhmm(offlineSince) : '') + '. Stay on this page: it reconnects by itself.' +
                (stale ? ' You are looking at the copy of this page kept on this device' + (keptAt && !isNaN(keptAt) ? ' (from ' + keptAt.toLocaleString([], { weekday: 'short', hour: '2-digit', minute: '2-digit' }) + ')' : '') + '.' : '') + '</p>' +
                (note ? '<div class="note">' + note + '</div>' : '') +
                '<h3>Your work</h3><ul>' +
                '<li>Everything saved before the connection dropped is safe on the server.</li>' +
                '<li>What you have typed on this page is still here. Saving is paused until the connection is back.</li>' +
                '<li>Invoices, proformas, receipts, fuel and maintenance entries are kept as drafts on this device as you type. Press Save while offline and the entry waits on this device for you to send it.</li>' +
                '</ul>' + drafts +
                '<h3>Still works without internet</h3><ul>' +
                '<li>Guard sign-in (QR poster) and the supervisor kiosk: sign-ins are saved on the phone and sent automatically.</li>' +
                '<li>The forms above, if you opened them on this device before.</li>' +
                '</ul>' +
                '<h3>Check</h3><ul>' +
                '<li>Is Wi-Fi or mobile data on? Try opening another website.</li>' +
                '<li>Other websites work but ISSOBS doesn\'t: the server may be down. Tell your administrator.</li>' +
                '<li>Nothing opens: restart the router or switch to mobile data.</li>' +
                '</ul>' +
                '<div class="foot"><span class="status" id="issobs-off-status">Checking the connection every 10 seconds…</span>' +
                '<button type="button" class="issobs-btn ghost" id="issobs-off-hide">Keep reading this page</button>' +
                '<button type="button" class="issobs-btn" id="issobs-off-retry">Try again now</button></div>' +
                '</div>';
            document.getElementById('issobs-off-hide').onclick = shrink;
            document.getElementById('issobs-off-retry').onclick = function () { probe(true); };
        });
    }

    function shrink() {
        if (panel) { panel.remove(); panel = null; }
        if (pill) return;
        pill = document.createElement('div');
        pill.className = 'issobs-pill'; pill.setAttribute('role', 'status');
        pill.innerHTML = '<span class="dot"></span><span>Offline' + (offlineSince ? ' since ' + hhmm(offlineSince) : '') + ' · saving paused</span><u>Details</u>';
        pill.onclick = function () { showPanel(); };
        document.body.appendChild(pill);
    }

    function toast(text, button) {
        style();
        var t = document.createElement('div');
        t.className = 'issobs-toast'; t.setAttribute('role', 'status');
        t.innerHTML = '<span>' + esc(text) + '</span>';
        if (button) {
            var b = document.createElement('button'); b.textContent = button[0]; b.onclick = button[1]; t.appendChild(b);
        }
        document.body.appendChild(t);
        if (!button) setTimeout(function () { t.remove(); }, 3500);
    }

    function goOffline(note) {
        if (!offlineSince) offlineSince = new Date();
        if (note || !pill) showPanel(note); else shrink();
        clearInterval(probeTimer);
        probeTimer = setInterval(probe, 10000);
    }

    function backOnline() {
        if (!offlineSince) return;
        offlineSince = null;
        clearInterval(probeTimer);
        if (panel) { panel.remove(); panel = null; }
        if (pill) { pill.remove(); pill = null; }
        if (stale) {
            toast('Back online. This page was opened from the copy on this device: reload it before saving (your entries are kept as a draft).',
                ['Reload', function () { location.reload(); }]);
        } else {
            toast('Back online. You can save again.');
        }
    }

    /** Ask the server, not just the network card: Wi-Fi without internet also counts as offline. */
    function probe(manual) {
        var s = document.getElementById('issobs-off-status');
        if (s && manual) s.textContent = 'Checking…';
        return fetch(cfg.up + (cfg.up.indexOf('?') < 0 ? '?' : '&') + 't=' + Date.now(), { method: 'GET', cache: 'no-store', credentials: 'same-origin' })
            .then(function (r) { if (r.ok) { backOnline(); return true; } throw new Error(r.status); })
            .catch(function () {
                var s2 = document.getElementById('issobs-off-status');
                if (s2) s2.textContent = 'Still offline · last checked ' + hhmm(new Date()) + ' · checking every 10 seconds';
                return false;
            });
    }

    window.addEventListener('offline', function () { goOffline(); });
    window.addEventListener('online', function () { probe(); });
    document.addEventListener('DOMContentLoaded', function () {
        if (!navigator.onLine) goOffline();
        else if (stale) toast('This is the copy of the page kept on this device (the connection was slow).', ['Reload', function () { location.reload(); }]);
    });

    // Save pressed with no connection: don't send (the browser would show an error and drop the typing).
    document.addEventListener('submit', function (e) {
        var form = e.target;
        var method = (form.getAttribute('method') || 'get').toLowerCase();
        if (method !== 'post') return;
        if (navigator.onLine && !offlineSince) {
            // A copy kept on this device carries an old one-time save code and security token:
            // keep the entries as a draft, get a fresh page, and the draft comes back on it.
            if (!stale || !form.issobsDraft) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            form.issobsDraft.keepForLater().then(function () { location.reload(); });
            return;
        }
        e.preventDefault();
        e.stopImmediatePropagation();
        if (form.issobsDraft) {
            form.issobsDraft.keepForLater().then(function () {
                goOffline('Not sent: there is no connection. Your entries are saved on this device as a draft, waiting to send. When the connection is back, open this form again (or stay here) and press Save.');
            });
        } else {
            goOffline('Not sent: there is no connection. What you typed is still on the page. Press the button again when the connection is back.');
        }
    }, true);

    // Drafts: forms marked data-draft keep what is typed on this device.
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.IssobsDrafts || !cfg.user) return;
        IssobsDrafts.tidy(cfg.user, !!cfg.saved, cfg.savedCheck).catch(function () {}).then(function () {
            // After the page's own scripts (e.g. the "+" row button) are ready.
            var go = function () { document.querySelectorAll('form[data-draft]').forEach(function (f) { IssobsDrafts.attach(f, cfg); }); };
            if (document.readyState === 'complete') go(); else window.addEventListener('load', go);
        });
    });

    // Logging out: remove the pages kept on this device for offline use.
    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[href$="/logout"], [data-logout]');
        if (!a || !window.caches) return;
        caches.keys().then(function (keys) {
            keys.filter(function (k) { return k.indexOf('issobs-pages') === 0; }).forEach(function (k) { caches.delete(k); });
        });
    }, true);
})();

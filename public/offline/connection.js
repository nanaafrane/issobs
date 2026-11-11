/*
 * ISSOBS connection helper, on every page (docs/offline-sync.md, "When the connection drops").
 *
 *  - A thin bar under the top bar for as long as there is no connection:
 *      "You're offline. Keep working: everything is saved on this device. Save when you're back online."
 *    or, when the internet works but the ISSOBS server doesn't answer, "Can't reach ISSOBS right now…".
 *    "What works offline?" opens a short note. Nothing covers the page.
 *  - "Back online" only when the browser has a connection AND the server answers twice in a row,
 *    so a server on the same machine or office network (which answers with the internet down) or
 *    a flickering signal never shows it too early.
 *  - Save pressed with no connection: not sent; the entry is kept as a draft and a short note by the
 *    button says so ("Saved as a draft · 14:32"). Other forms: "Not sent: you're offline…".
 *  - Drafts icon in the top bar (partials/drafts_tray): every draft on this device, ready-to-save first.
 *  - Registers the service worker (offline page, form pages kept on the device). Logging out removes
 *    the kept form pages.
 *
 * Config: window.ISSOBS_CONN = { sw, up, savedCheck, user, userName, rendered, saved, errors }
 */
(function () {
    'use strict';
    var cfg = window.ISSOBS_CONN || {};
    var state = 'online', since = null, okStreak = 0, timer = null, bar = null;

    try { if (cfg.user) localStorage.setItem('issobs_user', JSON.stringify({ id: cfg.user, name: cfg.userName || '' })); } catch (e) {}

    // Opened from the copy kept on this device (the service worker marks it)?
    var kept = !!document.querySelector('meta[name="issobs-kept-copy"]');

    if ('serviceWorker' in navigator && cfg.sw) navigator.serviceWorker.register(cfg.sw).catch(function () {});

    function hhmm(d) { return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

    var FONT = 'font-family:"Public Sans",-apple-system,"Segoe UI",Roboto,Arial,sans-serif;';
    var css = '' +
        '.issobs-bar{' + FONT + 'background:#fff2d6;color:#7a4b00;border-radius:8px;margin:8px auto 0;padding:9px 14px;font-size:14px;line-height:1.45;display:flex;flex-wrap:wrap;gap:4px 12px;align-items:center}' +
        '.issobs-bar.top{position:sticky;top:0;z-index:1080;border-radius:0;margin:0}' +
        '.issobs-bar .ic{width:9px;height:9px;border-radius:50%;background:#ff9f43;flex:none}' +
        '.issobs-bar .msg{flex:1 1 300px}' +
        '.issobs-bar .since{color:#a0700f;font-size:13px}' +
        '.issobs-bar button{' + FONT + 'background:none;border:0;padding:0;color:#7a4b00;font-size:13px;font-weight:600;text-decoration:underline;cursor:pointer}' +
        '.issobs-bar .help{flex:1 1 100%;margin:4px 0 2px;padding:8px 0 0 21px;border-top:1px solid rgba(122,75,0,.15);font-size:13px}' +
        '.issobs-bar .help ul{margin:0;padding-left:16px}.issobs-bar .help li{margin:2px 0}' +
        '.issobs-toast{' + FONT + 'position:fixed;left:50%;transform:translateX(-50%);bottom:18px;z-index:2060;background:#e8f8ef;color:#1b6b3f;border:1px solid #b8e6cb;border-radius:10px;padding:10px 16px;font-size:14px;box-shadow:0 6px 20px rgba(0,0,0,.12);display:flex;gap:12px;align-items:center;max-width:calc(100vw - 24px)}' +
        '.issobs-toast.info{background:#fff;color:#384551;border-color:#e4e6e8}' +
        '.issobs-toast button{' + FONT + 'background:none;border:0;padding:0;color:inherit;font-weight:600;text-decoration:underline;cursor:pointer;font-size:14px;white-space:nowrap}' +
        '.issobs-note{' + FONT + 'display:inline-flex;align-items:center;gap:6px;margin:6px 0 0 10px;font-size:13px;font-weight:600;vertical-align:middle}' +
        '.issobs-note.ok{color:#1b6b3f}.issobs-note.warn{color:#8a5a00}' +
        '#issobs-drafts-tray .issobs-draft{display:flex;gap:10px;align-items:flex-start;padding:9px 14px;border-bottom:1px solid #eceef1}' +
        '#issobs-drafts-tray .issobs-draft a{flex:1;color:inherit;text-decoration:none;min-width:0}' +
        '#issobs-drafts-tray .issobs-draft a:hover .t{text-decoration:underline}' +
        '#issobs-drafts-tray .issobs-draft .t{display:block;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}' +
        '#issobs-drafts-tray .issobs-draft .s{font-size:12px;color:#8592a3}#issobs-drafts-tray .issobs-draft .s.ready{color:#a0700f;font-weight:600}' +
        '#issobs-drafts-tray .issobs-draft button{background:none;border:0;color:#8592a3;font-size:12px;padding:2px 0;cursor:pointer}' +
        '#issobs-drafts-tray .issobs-draft button:hover{color:#ff3e1d}';

    function style() {
        if (document.getElementById('issobs-conn-css')) return;
        var s = document.createElement('style'); s.id = 'issobs-conn-css'; s.textContent = css; document.head.appendChild(s);
    }

    // ---------------------------------------------------------------- the bar

    function renderBar() {
        style();
        if (!bar) {
            bar = document.createElement('div');
            bar.className = 'issobs-bar'; bar.setAttribute('role', 'status'); bar.setAttribute('aria-live', 'polite');
            var nav = document.getElementById('layout-navbar');
            if (nav) nav.insertAdjacentElement('afterend', bar);
            else { bar.classList.add('top'); document.body.insertBefore(bar, document.body.firstChild); }
            if (nav) bar.style.maxWidth = getComputedStyle(nav).width;
        }
        var open = !!bar.querySelector('.help');
        var lead = state === 'unreachable'
            ? '<strong>Can\'t reach ISSOBS right now.</strong> Keep working: everything is saved on this device. Save when it\'s back.'
            : '<strong>You\'re offline.</strong> Keep working: everything is saved on this device. Save when you\'re back online.';
        bar.innerHTML = '<span class="ic" aria-hidden="true"></span><span class="msg">' + lead + '</span>' +
            '<span class="since">' + (since ? 'since ' + hhmm(since) : '') + '</span>' +
            '<button type="button" data-help>' + (open ? 'Hide' : 'What works offline?') + '</button>' +
            (open ? helpHtml() : '');
        bar.querySelector('[data-help]').onclick = function () {
            bar.querySelector('.help') ? bar.querySelector('.help').remove() : bar.insertAdjacentHTML('beforeend', helpHtml());
            this.textContent = bar.querySelector('.help') ? 'Hide' : 'What works offline?';
        };
    }

    function helpHtml() {
        return '<div class="help"><ul>' +
            '<li>Invoices, proformas, receipts, fuel and maintenance: what you type is kept. Press Save and it waits under Drafts in the top bar.</li>' +
            '<li>Guard sign-in and the supervisor kiosk keep working; sign-ins are sent by themselves.</li>' +
            '<li>Pages you haven\'t opened on this device before open once you\'re back.</li>' +
            '<li>Still offline after a while? Check Wi-Fi or mobile data. If other websites work, tell your administrator.</li>' +
            '</ul></div>';
    }

    function removeBar() { if (bar) { bar.remove(); bar = null; } }

    function toast(html, button, tone, ms) {
        style();
        var old = document.querySelector('.issobs-toast'); if (old) old.remove();
        var t = document.createElement('div');
        t.className = 'issobs-toast' + (tone === 'info' ? ' info' : ''); t.setAttribute('role', 'status');
        t.innerHTML = '<span>' + html + '</span>';
        if (button) {
            var b = document.createElement('button'); b.type = 'button'; b.textContent = button[0];
            b.onclick = function () { t.remove(); button[1](); };
            t.appendChild(b);
        }
        document.body.appendChild(t);
        if (ms !== 0) setTimeout(function () { t.remove(); }, ms || 6000);
    }

    // ---------------------------------------------------------------- connection state

    function setState(next) {
        if (next === state) { if (next !== 'online') renderBar(); return; }
        var was = state;
        state = next;
        if (next === 'online') {
            since = null; okStreak = 0; clearTimeout(timer); removeBar();
            if (was !== 'online') backOnline();
        } else {
            if (!since) since = new Date();
            renderBar();
            schedule(10000);
        }
    }

    function schedule(ms) { clearTimeout(timer); timer = setTimeout(check, ms); }

    /** Online = the browser has a connection AND the server answers twice in a row. */
    function check() {
        if (!navigator.onLine) { okStreak = 0; setState('offline'); return; }
        fetch(cfg.up + (cfg.up.indexOf('?') < 0 ? '?' : '&') + 't=' + Date.now(), { cache: 'no-store', credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error(r.status); })
            .then(function () {
                if (!navigator.onLine) { okStreak = 0; setState('offline'); return; }
                okStreak++;
                if (okStreak >= 2) setState('online'); else schedule(2000);
            })
            .catch(function () {
                okStreak = 0;
                setState(navigator.onLine ? 'unreachable' : 'offline');
            });
    }

    function backOnline() {
        if (kept) {
            toast('<strong>Back online.</strong> This page was opened offline: reload it before saving. What you typed is kept as a draft.',
                ['Reload', function () { location.reload(); }], 'ok', 0);
            return;
        }
        draftsList().then(function (rows) {
            var ready = rows.filter(function (r) { return r.pending; }).length;
            // Opened after this click has finished, or the menu would close on the same click.
            if (ready) toast('<strong>Back online.</strong> ' + ready + (ready === 1 ? ' draft is' : ' drafts are') + ' ready to save.', ['Review drafts', function () { setTimeout(openTray, 0); }]);
            else toast('<strong>Back online.</strong> You can save again.');
        });
    }

    window.addEventListener('offline', function () { okStreak = 0; setState('offline'); });
    window.addEventListener('online', function () { okStreak = 0; check(); });
    document.addEventListener('DOMContentLoaded', function () {
        if (!navigator.onLine) setState('offline');
        else if (kept) toast('This page is the copy kept on this device (the connection was slow).', ['Reload', function () { location.reload(); }], 'info', 0);
    });

    // ---------------------------------------------------------------- saving with no connection

    function note(form, submitter, text, tone) {
        style();
        var old = form.querySelector('.issobs-note'); if (old) old.remove();
        var n = document.createElement('span');
        n.className = 'issobs-note ' + tone; n.setAttribute('role', 'status');
        n.innerHTML = (tone === 'ok' ? '&#10003; ' : '') + text;
        var anchor = submitter && form.contains(submitter) ? submitter : null;
        if (anchor) anchor.insertAdjacentElement('afterend', n); else form.appendChild(n);
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if ((form.getAttribute('method') || 'get').toLowerCase() !== 'post') return;
        if (state === 'online' && navigator.onLine) {
            // A copy kept on this device carries an old security token and one-time save code:
            // keep the entries as a draft, get a fresh page, and the draft comes back on it.
            if (!kept || !form.issobsDraft) return;
            e.preventDefault(); e.stopImmediatePropagation();
            form.issobsDraft.keepForLater().then(function () { location.reload(); });
            return;
        }
        e.preventDefault(); e.stopImmediatePropagation();
        if (state === 'online') setState('offline');
        if (form.issobsDraft) {
            form.issobsDraft.keepForLater().then(function () {
                note(form, e.submitter, 'Saved as a draft · ' + hhmm(new Date()) + '. Save it when you\'re back online.', 'ok');
            });
        } else {
            note(form, e.submitter, 'Not sent: you\'re offline. Press it again when you\'re back online.', 'warn');
        }
    }, true);

    // ---------------------------------------------------------------- drafts icon in the top bar

    function draftsList() {
        if (!window.IssobsDrafts || !cfg.user) return Promise.resolve([]);
        return IssobsDrafts.list(cfg.user).then(function (rows) {
            return rows.sort(function (a, b) { return (b.pending ? 1 : 0) - (a.pending ? 1 : 0) || b.savedAt - a.savedAt; });
        }).catch(function () { return []; });
    }

    function renderTray() {
        var tray = document.getElementById('issobs-drafts-tray');
        if (!tray) return;
        style();
        draftsList().then(function (rows) {
            tray.hidden = !rows.length;
            var ready = rows.filter(function (r) { return r.pending; }).length;
            var badge = tray.querySelector('[data-drafts-count]');
            badge.textContent = rows.length > 99 ? '99+' : rows.length;
            badge.hidden = !rows.length;
            badge.classList.toggle('bg-warning', !!ready);
            badge.classList.toggle('bg-secondary', !ready);
            tray.querySelector('[data-drafts-total]').textContent = ready ? ready + ' ready to save' : rows.length;
            tray.querySelector('a.dropdown-toggle').setAttribute('aria-label', rows.length + ' draft(s) on this device' + (ready ? ', ' + ready + ' ready to save' : ''));
            var list = tray.querySelector('[data-drafts-list]');
            list.innerHTML = rows.map(function (r) {
                var sub = (r.pending ? '<span class="s ready">Ready to save</span>' : '<span class="s">Not finished</span>') +
                    '<span class="s">' + (r.lines > 1 ? ' · ' + r.lines + ' lines' : '') + ' · ' + esc(IssobsDrafts.when(r.savedAt)) + '</span>';
                return '<div class="issobs-draft"><a href="' + esc(r.url) + '#issobs-draft"><span class="t">' + esc(r.label) + '</span>' + sub + '</a>' +
                    '<button type="button" data-discard="' + esc(r.key) + '" aria-label="Discard draft: ' + esc(r.label) + '">Discard</button></div>';
            }).join('');
            list.querySelectorAll('[data-discard]').forEach(function (b) {
                b.onclick = function (ev) {
                    ev.preventDefault(); ev.stopPropagation();
                    if (b.dataset.sure) { IssobsDrafts.remove(b.dataset.discard); return; }
                    b.dataset.sure = '1'; b.textContent = 'Discard?'; b.style.color = '#ff3e1d';
                    setTimeout(function () { if (b.isConnected) { delete b.dataset.sure; b.textContent = 'Discard'; b.style.color = ''; } }, 4000);
                };
            });
        });
    }

    function openTray() {
        var toggle = document.querySelector('#issobs-drafts-tray a.dropdown-toggle');
        if (!toggle) return;
        if (window.bootstrap && bootstrap.Dropdown) bootstrap.Dropdown.getOrCreateInstance(toggle).show();
        else toggle.click();
        toggle.scrollIntoView({ block: 'nearest' });
    }

    window.addEventListener('issobs-drafts-changed', renderTray);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) renderTray(); });

    // Drafts: forms marked data-draft keep what is typed on this device.
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.IssobsDrafts || !cfg.user) return;
        IssobsDrafts.tidy(cfg.user, !!cfg.saved, cfg.savedCheck).catch(function () {}).then(function () {
            renderTray();
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

    window.IssobsConnection = { state: function () { return state; }, openTray: openTray };
})();

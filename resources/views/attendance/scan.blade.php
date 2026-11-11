<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#1f2a44">
    <title>Guard Sign-in</title>
    <style>
        :root { --ink: #111827; --muted: #6b7280; --line: #d1d5db; --brand: #1f2a44; --ok: #15803d; --okbg: #e8f6ed; --hold: #92400e; --holdbg: #fff4d6; --bad: #b91c1c; --badbg: #fdecec; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color: var(--ink); background: #f3f4f6; }
        header { background: var(--brand); color: #fff; padding: 18px 16px 16px; }
        header .co { font-size: 12px; letter-spacing: .08em; opacity: .8; }
        header h1 { margin: 4px 0 0; font-size: 21px; line-height: 1.25; }
        header .br { font-size: 14px; opacity: .85; margin-top: 2px; }
        main { padding: 16px; max-width: 460px; margin: 0 auto; }
        .clock { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 14px; }
        .clock .t { font-size: 30px; font-weight: 700; font-variant-numeric: tabular-nums; }
        .clock .d { color: var(--muted); font-size: 14px; }
        label { display: block; font-weight: 600; font-size: 15px; margin: 14px 0 6px; }
        input { width: 100%; font-size: 22px; padding: 12px 14px; border: 2px solid var(--line); border-radius: 10px; background: #fff; }
        input:focus { outline: none; border-color: var(--brand); }
        #pin { letter-spacing: .5em; font-variant-numeric: tabular-nums; }
        button { width: 100%; margin-top: 20px; font-size: 20px; font-weight: 700; padding: 15px; border: 0; border-radius: 10px; background: var(--brand); color: #fff; }
        button:disabled { opacity: .55; }
        .loc { margin-top: 14px; font-size: 14px; padding: 10px 12px; border-radius: 8px; background: #fff; border: 1px solid var(--line); color: var(--muted); }
        .loc.ok { color: var(--ok); border-color: #b7e2c4; }
        .loc.bad { color: var(--bad); border-color: #f5c2c2; }
        .result { display: none; margin-top: 18px; padding: 18px 16px; border-radius: 12px; font-size: 17px; line-height: 1.45; }
        .result .big { font-size: 26px; font-weight: 800; margin-bottom: 6px; }
        .result.ok { display: block; background: var(--okbg); color: var(--ok); }
        .result.hold { display: block; background: var(--holdbg); color: var(--hold); }
        .result.bad { display: block; background: var(--badbg); color: var(--bad); }
        .foot { color: var(--muted); font-size: 12.5px; margin-top: 22px; line-height: 1.5; }
        .net { display: none; margin: 0 0 12px; padding: 10px 12px; border-radius: 8px; font-size: 14px; font-weight: 600; background: var(--holdbg); color: var(--hold); }
        .net.on { display: block; }
        .outbox { margin-top: 20px; font-size: 14px; }
        .outbox h2 { font-size: 13px; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); margin: 0 0 6px; }
        .outbox .row { background: #fff; border: 1px solid var(--line); border-radius: 8px; padding: 8px 10px; margin-bottom: 6px; line-height: 1.4; }
        .outbox .row b { display: block; }
        .outbox .accepted b { color: var(--ok); } .outbox .held b, .outbox .waiting b { color: var(--hold); }
        .outbox .rejected b, .outbox .skipped b { color: var(--bad); }
    </style>
</head>
<body>
<header>
    <div class="co">FIRST WATCH SECURITY SERVICES</div>
    <h1>{{ $client->business_name ?: $client->name }}</h1>
    @if($client->branch)<div class="br">{{ $client->branch }}</div>@endif
</header>
<main>
    <div class="net" id="net"></div>
    <div class="clock"><span class="t" id="clock">--:--</span><span class="d" id="today"></span></div>

    <form id="f" autocomplete="off" novalidate>
        <label for="emp">Employee number</label>
        <input id="emp" name="employee_number" inputmode="text" autocapitalize="characters" placeholder="FWSS 245" required>

        <label for="pin">PIN</label>
        <input id="pin" name="pin" type="password" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="••••" required>

        <div class="loc" id="loc">Finding your location… allow location when the phone asks.</div>

        <button id="go" type="submit">Sign in</button>
    </form>

    <div class="result" id="result" role="status" aria-live="polite"></div>

    <div class="outbox" id="outbox" hidden>
        <h2>Sign-ins on this phone</h2>
        <div id="outbox-rows"></div>
    </div>

    <div class="foot">Sign in at the start of every shift, at this site, with your own phone and PIN.
        Forgot your PIN or changed your phone? Tell your supervisor.
        @if($offline) No signal? Sign in anyway: it is saved on this phone and sent when the phone is back online.@endif</div>
</main>
@if($offline)<script src="{{ asset('offline/outbox.js') }}"></script>@endif
<script>
(function () {
    const $ = id => document.getElementById(id);
    let pos = null;

    // Clock
    function tick() {
        const n = new Date();
        $('clock').textContent = n.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        $('today').textContent = n.toLocaleDateString([], { weekday: 'long', day: 'numeric', month: 'short' });
    }
    tick(); setInterval(tick, 10000);

    // This phone's id: made once, kept in the browser. Operations see it when a guard uses another phone.
    function deviceId() {
        let id = null;
        try { id = localStorage.getItem('issobs_device'); } catch (e) {}
        if (!id) {
            id = (window.crypto && crypto.randomUUID) ? crypto.randomUUID()
                : 'd' + Date.now().toString(36) + Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2);
            try { localStorage.setItem('issobs_device', id); } catch (e) {}
        }
        return id;
    }

    // Remember the employee number on this phone (never the PIN).
    try { const last = localStorage.getItem('issobs_emp'); if (last) { $('emp').value = last; } } catch (e) {}
    ($('emp').value ? $('pin') : $('emp')).focus();

    // Location
    function locMsg(text, cls) { const el = $('loc'); el.textContent = text; el.className = 'loc ' + (cls || ''); }
    if (!navigator.geolocation) {
        locMsg('This phone cannot share its location. Your sign-in will be checked by operations.', 'bad');
    } else {
        navigator.geolocation.watchPosition(p => {
            if (!pos || p.coords.accuracy <= pos.coords.accuracy) pos = p;
            locMsg('Location found (within about ' + Math.round(pos.coords.accuracy) + ' m).', 'ok');
        }, e => {
            locMsg(e.code === 1 ? 'Location is blocked. Allow location for this page in your browser settings, then reload.'
                : 'Could not get your location yet. Move near a window or outside and wait a moment.', 'bad');
        }, { enableHighAccuracy: true, maximumAge: 30000, timeout: 30000 });
    }

    const esc = s => { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; };
    function show(cls, title, text) {
        const r = $('result'); r.className = 'result ' + cls; r.innerHTML = '<div class="big">' + esc(title) + '</div>' + esc(text);
        r.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    function showAnswer(j) {
        if (j.result === 'accepted' && j.status === 'present') show('ok', '✓ Signed in', j.message);
        else if (j.result === 'accepted' || j.result === 'held') show('hold', 'Received', j.message);
        else show('bad', 'Not signed in', j.message || 'Check your details and try again.');
    }
    const hhmm = ms => new Date(ms).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    // ---- Offline outbox (docs/offline-sync.md). Needs IndexedDB + WebCrypto (HTTPS); otherwise the page works online only, as before.
    const OFFLINE = @json((bool) $offline) && !!(window.indexedDB && window.crypto && crypto.subtle && window.IssobsOutbox);
    const QUEUE = 'scan:' + @json($token);
    const PIN_KEY = @json($pinKey);
    let keyPromise = null;

    function sealPin(uuid, emp, pin) {
        if (!keyPromise) {
            const der = Uint8Array.from(atob(PIN_KEY), c => c.charCodeAt(0));
            keyPromise = crypto.subtle.importKey('spki', der, { name: 'RSA-OAEP', hash: 'SHA-1' }, false, ['encrypt']);
        }
        return keyPromise.then(k => crypto.subtle.encrypt({ name: 'RSA-OAEP' }, k, new TextEncoder().encode(uuid + '|' + emp + '|' + pin)))
            .then(buf => btoa(String.fromCharCode.apply(null, new Uint8Array(buf))));
    }

    function netBanner(text) { const n = $('net'); n.textContent = text || ''; n.className = 'net' + (text ? ' on' : ''); }

    function renderOutbox() {
        if (!OFFLINE) return Promise.resolve();
        return Promise.all([IssobsOutbox.pending(QUEUE), IssobsOutbox.results(QUEUE, 5)]).then(([waiting, done]) => {
            const rows = waiting.map(w => ({ cls: 'waiting', title: 'Waiting to send · ' + w.label, text: 'Saved on this phone at ' + hhmm(w.created) + '. It is sent automatically when the phone is online.' }))
                .concat(done.map(d => ({ cls: d.result, title: ({ accepted: 'Sent', held: 'Sent · operations will check', rejected: 'Not accepted', skipped: 'Not needed' }[d.result] || d.result) + ' · ' + d.label, text: d.message })));
            $('outbox').hidden = !rows.length;
            $('outbox-rows').innerHTML = rows.map(r => '<div class="row ' + esc(r.cls) + '"><b>' + esc(r.title) + '</b>' + esc(r.text) + '</div>').join('');
            if (waiting.length) netBanner(waiting.length + ' sign-in(s) waiting on this phone. They are sent when the phone is online.');
            else netBanner(navigator.onLine ? '' : 'No signal. You can still sign in: it is saved on this phone.');
        });
    }

    // The last sign-in saved while offline: when it is finally sent, the box above shows the real answer.
    let lastSaved = null;
    function answerLastSaved(results) {
        const mine = lastSaved && (results || []).find(r => r.uuid === lastSaved && r.result !== 'retry');
        if (mine) { lastSaved = null; showAnswer(mine); }
    }

    function flush() {
        if (!OFFLINE) return Promise.resolve({ results: [] });
        return IssobsOutbox.flush(QUEUE).then(s => { answerLastSaved(s.results); return renderOutbox().then(() => s); });
    }

    function askBackgroundSync() {
        if (navigator.serviceWorker && navigator.serviceWorker.ready) {
            navigator.serviceWorker.ready.then(reg => reg.sync && reg.sync.register('issobs-outbox')).catch(() => {});
        }
    }

    if (OFFLINE) {
        IssobsOutbox.setQueue(QUEUE, { url: @json(route('attend.sync', $token)), auth: 'none' }).then(flush);
        window.addEventListener('online', flush);
        window.addEventListener('offline', renderOutbox);
        if (navigator.serviceWorker) {
            navigator.serviceWorker.register(@json(asset('sw.js'))).catch(() => {});
            navigator.serviceWorker.addEventListener('message', e => {
                if (!e.data || e.data.type !== 'issobs-outbox-flushed') return;
                (e.data.summaries || []).forEach(s => answerLastSaved(s.results));
                renderOutbox();
            });
        }
    }

    $('f').addEventListener('submit', function (ev) {
        ev.preventDefault();
        const emp = $('emp').value.trim(), pin = $('pin').value.trim();
        if (!emp || !pin) { show('bad', 'Not signed in', 'Enter your employee number and PIN.'); return; }
        $('go').disabled = true; $('go').textContent = 'Signing in…';
        const where = pos ? { latitude: pos.coords.latitude, longitude: pos.coords.longitude, accuracy: pos.coords.accuracy } : {};
        const done = () => { $('go').disabled = false; $('go').textContent = 'Sign in'; };
        const remember = () => { try { localStorage.setItem('issobs_emp', emp); } catch (e) {} $('pin').value = ''; };

        if (!OFFLINE) {
            // Online only (old browser / no HTTPS): straight to the server, as before.
            fetch(@json(route('attend.store', $token)), { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(Object.assign({ employee_number: emp, pin, device_id: deviceId() }, where)) })
                .then(res => res.json().catch(() => ({ ok: false, message: res.status === 429 ? 'Too many tries. Wait a minute and try again.' : 'Something went wrong (' + res.status + '). Try again.' })))
                .then(j => { showAnswer({ result: j.ok ? (j.status === 'pending' ? 'held' : 'accepted') : 'rejected', status: j.status, message: j.message }); if (j.ok) remember(); })
                .catch(() => show('bad', 'No connection', 'Check your data or Wi-Fi and press Sign in again.'))
                .finally(done);
            return;
        }

        // Save on the phone first (PIN sealed with the server's key), then send. Same path with or without signal.
        const uuid = IssobsOutbox.uuid(), madeAt = Date.now();
        sealPin(uuid, emp, pin)
            .then(sealed => IssobsOutbox.add(QUEUE, Object.assign({ uuid, captured_at: madeAt, offline: !navigator.onLine, employee_number: emp, pin_sealed: sealed, device_id: deviceId() }, where),
                emp.toUpperCase() + ' · ' + hhmm(madeAt)))
            .then(() => { remember(); return flush(); })
            .then(s => {
                const mine = (s.results || []).find(r => r.uuid === uuid);
                if (mine && mine.result !== 'retry') { showAnswer(mine); return; }
                askBackgroundSync();
                lastSaved = uuid;
                if (mine) show('hold', 'Saved on this phone', mine.message + ' It will be sent again automatically.');
                else if (s.locked) show('hold', 'Saved on this phone', 'The system is read-only at the moment. Your sign-in at ' + hhmm(madeAt) + ' is kept and sent once it is open again.');
                else show('hold', 'Saved on this phone', 'No signal right now. Your sign-in at ' + hhmm(madeAt) + ' is saved and will be sent automatically when the phone is online. You can close this page.');
            })
            .catch(err => show('bad', 'Not saved', 'This phone could not save the sign-in (' + (err && err.message || err) + '). Try again, or ask your supervisor to mark you on the kiosk.'))
            .finally(done);
    });
})();
</script>
</body>
</html>

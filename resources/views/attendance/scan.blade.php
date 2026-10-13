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
    </style>
</head>
<body>
<header>
    <div class="co">FIRST WATCH SECURITY SERVICES</div>
    <h1>{{ $client->business_name ?: $client->name }}</h1>
    @if($client->branch)<div class="br">{{ $client->branch }}</div>@endif
</header>
<main>
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

    <div class="foot">Sign in at the start of every shift, at this site, with your own phone and PIN.
        Forgot your PIN or changed your phone? Tell your supervisor.</div>
</main>
<script>
(function () {
    const url = @json(route('attend.store', $token));
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

    $('f').addEventListener('submit', function (ev) {
        ev.preventDefault();
        const emp = $('emp').value.trim(), pin = $('pin').value.trim();
        const r = $('result');
        if (!emp || !pin) { r.className = 'result bad'; r.innerHTML = 'Enter your employee number and PIN.'; return; }
        $('go').disabled = true; $('go').textContent = 'Signing in…';
        const body = { employee_number: emp, pin, device_id: deviceId() };
        if (pos) { body.latitude = pos.coords.latitude; body.longitude = pos.coords.longitude; body.accuracy = pos.coords.accuracy; }

        fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(body) })
            .then(res => res.json().catch(() => ({ ok: false, message: res.status === 429 ? 'Too many tries. Wait a minute and try again.' : 'Something went wrong (' + res.status + '). Try again.' })))
            .then(j => {
                const esc = s => { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; };
                if (j.ok && j.status === 'present') {
                    r.className = 'result ok'; r.innerHTML = '<div class="big">✓ Signed in</div>' + esc(j.message);
                } else if (j.ok) {
                    r.className = 'result hold'; r.innerHTML = '<div class="big">Received</div>' + esc(j.message);
                } else {
                    r.className = 'result bad'; r.innerHTML = '<div class="big">Not signed in</div>' + esc(j.message || 'Check your details and try again.');
                }
                if (j.ok) { try { localStorage.setItem('issobs_emp', emp); } catch (e) {} $('pin').value = ''; }
            })
            .catch(() => { r.className = 'result bad'; r.innerHTML = '<div class="big">No connection</div>Check your data or Wi-Fi and press Sign in again.'; })
            .finally(() => { $('go').disabled = false; $('go').textContent = 'Sign in'; r.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); });
    });
})();
</script>
</body>
</html>

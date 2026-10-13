<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#1f2a44">
    <title>Supervisor Kiosk</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
    <style>
        :root { --ink: #111827; --muted: #6b7280; --line: #e5e7eb; --brand: #1f2a44; --ok: #15803d; --okbg: #e8f6ed; --hold: #92400e; --holdbg: #fff4d6; --bad: #b91c1c; --badbg: #fdecec; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color: var(--ink); background: #f3f4f6; padding-bottom: 80px; }
        header { background: var(--brand); color: #fff; padding: 14px 16px; position: sticky; top: 0; z-index: 5; }
        header .top { display: flex; justify-content: space-between; align-items: center; font-size: 12px; opacity: .85; }
        header .top a { color: #fff; }
        header h1 { margin: 4px 0 2px; font-size: 19px; }
        header .slot { font-size: 14px; }
        .tabs { display: flex; gap: 6px; margin-top: 10px; }
        .tabs a { flex: 1; text-align: center; padding: 7px; border-radius: 8px; background: rgba(255,255,255,.12); color: #fff; text-decoration: none; font-weight: 600; font-size: 14px; }
        .tabs a.on { background: #fff; color: var(--brand); }
        main { max-width: 520px; margin: 0 auto; padding: 12px; }
        .bar { display: flex; gap: 8px; margin-bottom: 10px; }
        .bar input { flex: 1; font-size: 17px; padding: 10px 12px; border: 2px solid #d1d5db; border-radius: 10px; }
        .btn { border: 0; border-radius: 10px; font-weight: 700; font-size: 15px; padding: 10px 14px; cursor: pointer; }
        .btn-brand { background: var(--brand); color: #fff; }
        .btn-p { background: var(--ok); color: #fff; }
        .btn-a { background: #fff; color: var(--bad); border: 2px solid #f5c2c2; }
        #reader { display: none; border-radius: 12px; overflow: hidden; margin-bottom: 10px; background: #000; }
        .loc { font-size: 13px; color: var(--muted); margin: 0 2px 10px; }
        .loc.ok { color: var(--ok); } .loc.bad { color: var(--bad); }
        h2 { font-size: 13px; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); margin: 16px 4px 6px; }
        .g { background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 10px 12px; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; }
        .g .who { flex: 1; min-width: 0; }
        .g .n { font-weight: 700; font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .g .m { font-size: 12.5px; color: var(--muted); }
        .badge { font-size: 12px; font-weight: 700; border-radius: 6px; padding: 2px 7px; }
        .s-present { background: var(--okbg); color: var(--ok); } .s-absent { background: var(--badbg); color: var(--bad); }
        .s-pending { background: var(--holdbg); color: var(--hold); } .s-other { background: #eef0ff; color: #3b3ea6; }
        #toast { position: fixed; left: 12px; right: 12px; bottom: 14px; max-width: 496px; margin: 0 auto; padding: 14px; border-radius: 12px; font-size: 15px; font-weight: 600; display: none; box-shadow: 0 6px 20px rgba(0,0,0,.18); }
        #toast.ok { display: block; background: var(--okbg); color: var(--ok); }
        #toast.hold { display: block; background: var(--holdbg); color: var(--hold); }
        #toast.bad { display: block; background: var(--badbg); color: var(--bad); }
        .count { font-size: 13px; margin-top: 6px; }
    </style>
</head>
<body>
@php
    $statusOf = fn ($g) => $records[$g->id]->status ?? null;
    $presentNow = $records->where('status', 'present')->count();
@endphp
<header>
    <div class="top"><span>SUPERVISOR KIOSK · {{ Auth::user()->name }}</span><a href="{{ route('attendance.show', $client->id) }}">Time sheet</a></div>
    <h1>{{ $client->business_name ?: $client->name }}</h1>
    <div class="slot">{{ ucfirst($shift) }} shift · {{ $date->format('D j M') }}
        <div class="count"><span id="present-count">{{ $presentNow }}</span> present @if($required) of {{ $required }} required @endif</div></div>
    <div class="tabs">
        <a href="?shift=day" class="{{ $shift === 'day' ? 'on' : '' }}">Day</a>
        <a href="?shift=night" class="{{ $shift === 'night' ? 'on' : '' }}">Night</a>
    </div>
</header>
<main>
    <div class="loc" id="loc">Finding your location… your location proves the marks were made at the site.</div>
    <div id="reader"></div>
    <div class="bar">
        <input id="manual" placeholder="FWSS number" inputmode="text" autocapitalize="characters">
        <button class="btn btn-brand" id="manual-go">Present</button>
        <button class="btn btn-brand" id="scan-btn" title="Scan a guard's ID card">Scan ID</button>
    </div>

    @foreach(['On this shift' => $onShift, 'Other guards at this client' => $others] as $title => $list)
        @if($list->isNotEmpty())
            <h2>{{ $title }}</h2>
            @foreach($list as $g)
                @php($st = $statusOf($g))
                <div class="g" id="g-{{ $g->id }}">
                    <div class="who">
                        <div class="n">{{ $g->name }}</div>
                        <div class="m">FWSS {{ $g->id }} · {{ ucfirst($g->attendance_shift ?: 'no shift set') }}
                            <span class="badge st {{ $st ? 's-' . (in_array($st, ['present','absent','pending']) ? $st : 'other') : '' }}">{{ $st ? \App\Models\Attendance::LABELS[$st] : '' }}</span></div>
                    </div>
                    <button class="btn btn-p" data-id="{{ $g->id }}" data-status="present">P</button>
                    <button class="btn btn-a" data-id="{{ $g->id }}" data-status="absent">A</button>
                </div>
            @endforeach
        @endif
    @endforeach
    @if($onShift->isEmpty() && $others->isEmpty())
        <p>No guards are posted to this client.</p>
    @endif
</main>
<div id="toast" role="status" aria-live="polite"></div>
<script>
(function () {
    const url = @json(route('attendance.kioskMark', $client->id));
    const csrf = @json(csrf_token());
    const date = @json($date->toDateString());
    const shift = @json($shift);
    const LABELS = @json(\App\Models\Attendance::LABELS);
    const $ = id => document.getElementById(id);
    let pos = null, toastTimer = null;

    function deviceId() {
        let id = null;
        try { id = localStorage.getItem('issobs_device'); } catch (e) {}
        if (!id) { id = (crypto.randomUUID ? crypto.randomUUID() : 'd' + Date.now().toString(36) + Math.random().toString(36).slice(2, 20)); try { localStorage.setItem('issobs_device', id); } catch (e) {} }
        return id;
    }
    function toast(text, cls) {
        const t = $('toast'); t.textContent = text; t.className = cls; clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { t.className = ''; }, 4000);
    }
    if (navigator.geolocation) {
        navigator.geolocation.watchPosition(p => {
            if (!pos || p.coords.accuracy <= pos.coords.accuracy) pos = p;
            $('loc').textContent = 'Location found (about ' + Math.round(pos.coords.accuracy) + ' m).'; $('loc').className = 'loc ok';
        }, e => { $('loc').textContent = 'Location unavailable: present marks will be held for review. Allow location and reload.'; $('loc').className = 'loc bad'; },
        { enableHighAccuracy: true, maximumAge: 30000, timeout: 30000 });
    }

    function mark(employee, status) {
        const body = { employee: String(employee), status, date, shift, device_id: deviceId() };
        if (pos) { body.latitude = pos.coords.latitude; body.longitude = pos.coords.longitude; body.accuracy = pos.coords.accuracy; }
        return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(body) })
            .then(r => r.json().catch(() => ({ ok: false, message: r.status === 419 ? 'Session expired: reload the page.' : 'Error ' + r.status })))
            .then(j => {
                toast(j.message || 'Saved', !j.ok ? 'bad' : (j.status === 'pending' ? 'hold' : 'ok'));
                if (j.ok && j.employee_id) {
                    const row = $('g-' + j.employee_id);
                    if (row) {
                        const b = row.querySelector('.st');
                        const was = b.classList.contains('s-present');
                        b.textContent = LABELS[j.status] || j.status;
                        b.className = 'badge st s-' + (['present', 'absent', 'pending'].includes(j.status) ? j.status : 'other');
                        const c = $('present-count'); const now = j.status === 'present';
                        if (now !== was) c.textContent = (+c.textContent) + (now ? 1 : -1);
                    }
                }
                return j;
            })
            .catch(() => toast('No connection. Try again.', 'bad'));
    }

    document.querySelectorAll('[data-status]').forEach(b => b.addEventListener('click', () => mark(b.dataset.id, b.dataset.status)));
    $('manual-go').onclick = () => { const v = $('manual').value.trim(); if (v) mark(v, 'present').then(() => { $('manual').value = ''; }); };

    // Scan a guard's ID card (QR "ISSOBS-GUARD:<id>") with the phone camera.
    let scanner = null, busy = false;
    $('scan-btn').onclick = function () {
        if (typeof Html5Qrcode === 'undefined') { toast('Camera scanner did not load. Type the FWSS number instead.', 'bad'); return; }
        const r = $('reader');
        if (scanner) { scanner.stop().catch(() => {}); scanner = null; r.style.display = 'none'; this.textContent = 'Scan ID'; return; }
        r.style.display = 'block'; this.textContent = 'Stop';
        scanner = new Html5Qrcode('reader');
        scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: 220 }, text => {
            if (busy) return; busy = true;
            mark(text, 'present').finally(() => setTimeout(() => { busy = false; }, 2000));
        }).catch(err => { toast('Camera not available: ' + err, 'bad'); r.style.display = 'none'; scanner = null; $('scan-btn').textContent = 'Scan ID'; });
    };
})();
</script>
</body>
</html>

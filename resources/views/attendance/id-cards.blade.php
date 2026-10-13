<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Guard ID Cards</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        body { margin: 0; font-family: Arial, sans-serif; color: #111827; background: #eef0f4; }
        .bar { max-width: 190mm; margin: 14px auto 0; display: flex; gap: 8px; justify-content: space-between; align-items: center; font-size: 14px; }
        .bar button, .bar a { font: inherit; padding: 8px 14px; border-radius: 6px; border: 1px solid #696cff; background: #696cff; color: #fff; cursor: pointer; text-decoration: none; }
        .bar a { background: #fff; color: #696cff; }
        .page { width: 190mm; margin: 10px auto 24px; display: grid; grid-template-columns: repeat(2, 85.6mm); gap: 6mm 8mm; justify-content: center; }
        /* Credit-card size, so it fits a standard card holder. */
        .card { width: 85.6mm; height: 54mm; background: #fff; border: 1px solid #111827; border-radius: 3mm; padding: 4mm; display: flex; gap: 4mm; break-inside: avoid; }
        .card .info { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .co { font-size: 7.5pt; font-weight: 700; letter-spacing: .05em; color: #1f2a44; }
        .name { font-size: 11pt; font-weight: 700; margin-top: 3mm; line-height: 1.2; }
        .id { font-size: 14pt; font-weight: 700; margin-top: 2mm; font-family: monospace; }
        .site { font-size: 7.5pt; color: #4b5563; margin-top: auto; }
        .qr { width: 32mm; height: 32mm; align-self: center; }
        .qr img, .qr canvas { width: 32mm !important; height: 32mm !important; }
        @media print { body { background: #fff; } .bar { display: none; } .page { margin: 0 auto; } }
    </style>
</head>
<body>
    <div class="bar">
        <span>{{ $guards->count() }} card(s) · {{ $client->business_name ?: $client->name }}. Supervisors scan these on the kiosk.</span>
        <span><a href="{{ route('attendance.show', $client->id) }}">Back</a> <button type="button" onclick="window.print()">Print</button></span>
    </div>
    <div class="page">
        @foreach($guards as $g)
            <div class="card">
                <div class="info">
                    <div class="co">FIRST WATCH SECURITY SERVICES</div>
                    <div class="name">{{ $g->name }}</div>
                    <div class="id">FWSS {{ $g->id }}</div>
                    <div class="site">{{ $client->business_name ?: $client->name }}</div>
                </div>
                <div class="qr" data-text="ISSOBS-GUARD:{{ $g->id }}"></div>
            </div>
        @endforeach
    </div>
    <script>
        document.querySelectorAll('.qr').forEach(el => new QRCode(el, { text: el.dataset.text, width: 256, height: 256, correctLevel: QRCode.CorrectLevel.M }));
    </script>
</body>
</html>

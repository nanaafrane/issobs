<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Guard PIN Slips</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        body { margin: 0; font-family: Arial, sans-serif; color: #111827; background: #eef0f4; }
        .bar { max-width: 190mm; margin: 14px auto 0; font-size: 14px; line-height: 1.5; }
        .bar .warn { background: #fff4d6; color: #8a5a00; border-radius: 6px; padding: 10px 12px; margin-bottom: 8px; }
        .bar button, .bar a { font: inherit; padding: 8px 14px; border-radius: 6px; border: 1px solid #696cff; background: #696cff; color: #fff; cursor: pointer; text-decoration: none; }
        .bar a { background: #fff; color: #696cff; }
        .page { width: 190mm; margin: 10px auto 24px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 0; }
        .slip { background: #fff; border: 1px dashed #6b7280; padding: 5mm 6mm; break-inside: avoid; }
        .co { font-size: 8pt; font-weight: 700; letter-spacing: .05em; color: #1f2a44; }
        .name { font-size: 12pt; font-weight: 700; margin-top: 2mm; }
        .row { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 3mm; }
        .lbl { font-size: 8pt; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }
        .val { font-size: 15pt; font-weight: 700; font-family: monospace; }
        .pin { font-size: 22pt; letter-spacing: .15em; }
        .how { font-size: 8pt; color: #374151; margin-top: 3mm; line-height: 1.35; }
        .empty { background: #fff; padding: 20px; border-radius: 6px; }
        @media print { body { background: #fff; } .bar { display: none; } .page { margin: 0 auto; } }
    </style>
</head>
<body>
    <div class="bar">
        <div class="warn"><strong>Print or note these PINs now.</strong> They are not stored anywhere readable and won't be shown again.
            Cut along the dashed lines and hand each guard their own slip. A lost PIN can be replaced from the attendance page.</div>
        <span><a href="{{ route('attendance.show', $client->id) }}">Back to attendance</a>
        @if($slips)<button type="button" onclick="window.print()">Print slips</button>@endif</span>
    </div>
    @if($slips)
    <div class="page">
        @foreach($slips as $s)
            <div class="slip">
                <div class="co">FIRST WATCH SECURITY SERVICES · ATTENDANCE PIN</div>
                <div class="name">{{ $s['name'] }}</div>
                <div class="row">
                    <div><div class="lbl">Employee number</div><div class="val">FWSS {{ $s['id'] }}</div></div>
                    <div style="text-align:right"><div class="lbl">PIN</div><div class="val pin">{{ $s['pin'] }}</div></div>
                </div>
                <div class="how">{{ $client->business_name ?: $client->name }}{{ $s['shift'] ? ' · ' . ucfirst($s['shift']) . ' shift' : '' }}.
                    At the start of every shift scan the QR poster at the post and enter your employee number and PIN. Keep your PIN secret.</div>
            </div>
        @endforeach
    </div>
    @else
        <div class="bar"><div class="empty">Every guard at {{ $client->business_name ?: $client->name }} already has a PIN. Choose "All guards" to replace them, or reset one guard from the grid.</div></div>
    @endif
</body>
</html>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Guard Sign-in Poster</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Public Sans", Arial, sans-serif; color: #1f2937; background: #eef0f4; }
        .bar { max-width: 190mm; margin: 16px auto 0; display: flex; gap: 8px; justify-content: flex-end; }
        .bar button, .bar a { font: inherit; font-size: 14px; padding: 8px 14px; border-radius: 6px; border: 1px solid #696cff; background: #696cff; color: #fff; cursor: pointer; text-decoration: none; }
        .bar a { background: #fff; color: #696cff; }
        .sheet { width: 190mm; min-height: 270mm; margin: 12px auto 24px; background: #fff; padding: 14mm 14mm 10mm; border: 3px solid #111827; display: flex; flex-direction: column; align-items: center; text-align: center; }
        .company { display: flex; align-items: center; gap: 10px; font-weight: 700; letter-spacing: .04em; font-size: 15px; }
        .company img { height: 44px; }
        h1 { font-size: 40px; margin: 16px 0 4px; letter-spacing: .02em; }
        .client { font-size: 24px; font-weight: 700; margin: 6px 0 2px; }
        .branch { font-size: 16px; color: #4b5563; }
        #qr { margin: 22px 0 10px; padding: 14px; border: 2px solid #111827; border-radius: 10px; }
        #qr img, #qr canvas { width: 92mm !important; height: 92mm !important; }
        .steps { text-align: left; font-size: 17px; line-height: 1.5; margin: 14px 0 6px; max-width: 150mm; }
        .steps li { margin-bottom: 4px; }
        .shifts { font-size: 14px; color: #374151; margin-top: 8px; }
        .warn { margin-top: auto; font-size: 12.5px; color: #4b5563; border-top: 1px dashed #9ca3af; padding-top: 8px; width: 100%; }
        .url { font-family: monospace; font-size: 10px; color: #9ca3af; word-break: break-all; margin-top: 4px; }
        @media print { body { background: #fff; } .bar { display: none; } .sheet { margin: 0 auto; border-width: 3px; } }
    </style>
</head>
<body>
    <div class="bar">
        <a href="{{ route('attendance.show', $client->id) }}">Back</a>
        <button type="button" onclick="window.print()">Print poster</button>
    </div>
    <div class="sheet">
        <div class="company"><img src="{{ asset('img/icons/brands/issobs.png') }}" alt="" onerror="this.remove()"> FIRST WATCH SECURITY SERVICES</div>
        <h1>GUARD SIGN-IN</h1>
        <div class="client">{{ $client->business_name ?: $client->name }}</div>
        @if($client->branch || $client->address)<div class="branch">{{ trim($client->branch . ' ' . ($client->address ? '· ' . $client->address : ''), ' ·') }}</div>@endif
        <div id="qr"></div>
        <ol class="steps">
            <li>Scan this code with your phone camera when you <strong>start every shift</strong>.</li>
            <li>Enter your <strong>employee number</strong> (e.g. FWSS 245) and your <strong>4-digit PIN</strong>.</li>
            <li>Allow the phone to share its <strong>location</strong>, then press <strong>Sign in</strong>.</li>
        </ol>
        @if($plan)
            <div class="shifts">
                @if($plan->day_guards)Day shift {{ $plan->day_start }}–{{ $plan->day_end }}@endif
                @if($plan->day_guards && $plan->night_guards) &nbsp;·&nbsp; @endif
                @if($plan->night_guards)Night shift {{ $plan->night_start }}–{{ $plan->night_end }}@endif
            </div>
        @endif
        <div class="warn">Use your own phone and your own PIN. Never sign in for another guard. Sign-ins away from this site are checked.
            No PIN yet? Ask your supervisor.<div class="url">{{ $url }}</div></div>
    </div>
    <script>
        new QRCode(document.getElementById('qr'), { text: @json($url), width: 512, height: 512, correctLevel: QRCode.CorrectLevel.M });
    </script>
</body>
</html>

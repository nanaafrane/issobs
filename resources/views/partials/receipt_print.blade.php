
@php
    $fmt = fn ($v) => 'GH&#8373; ' . number_format((float) $v, 2);

    // Only the payment methods actually used on this receipt, in a fixed order.
    $payments = collect([
        ['key' => 'cash',     'label' => 'Cash',          'amount' => $receipt->cash_amount,
         'detail' => null],
        ['key' => 'momo',     'label' => 'Mobile Money',  'amount' => $receipt->momo_amount,
         'detail' => $receipt->momo_transactin_id ? 'Transaction ID: ' . $receipt->momo_transactin_id : null],
        ['key' => 'transfer', 'label' => 'Bank transfer', 'amount' => $receipt->transfer_amount,
         'detail' => trim(($receipt->transfer_reference ? 'Ref: ' . $receipt->transfer_reference : '') . ($receipt->transfer_bank ? ' · ' . $receipt->transfer_bank : '') . ($receipt->transferToBank ? ' · into ' . $receipt->transferToBank->name : ''), ' ·')],
        ['key' => 'cheque',   'label' => 'Cheque',        'amount' => $receipt->cheque_amount,
         'detail' => trim(($receipt->cheque_reference ? 'Ref: ' . $receipt->cheque_reference : '') . ($receipt->cheque_bank ? ' · ' . $receipt->cheque_bank : '') . ($receipt->chequeToBank ? ' · to ' . $receipt->chequeToBank->name : ''), ' ·')],
        ['key' => 'other',    'label' => 'Other payment', 'amount' => $receipt->other_payment_amnt,
         'detail' => $receipt->other_payment_descri],
    ])->filter(fn ($p) => (float) $p['amount'] > 0)->values();

    $whtRate = rtrim(rtrim(number_format(($wht->wht_rate ?? 0) * 100, 2), '0'), '.');
    $client  = $receipt->client;
    $invoice = $receipt->invoice;
    $allocs  = $receipt->allocations()->with('invoice')->get();
    $isMulti = $allocs->unique('invoice_id')->count() > 1 || ! $receipt->invoice_id;
    $invoiceRef = $isMulti
        ? ($allocs->isEmpty() ? 'Advance' : $allocs->pluck('invoice_id')->unique()->map(fn ($id) => 'FWSSi' . $id)->implode(', '))
        : 'FWSSi' . $receipt->invoice_id;
@endphp

<style>
    /* ---------- On screen: the print sheet does not exist ---------- */
    .rp-sheet { display: none; }

    @media print {
        @page { size: A4 portrait; margin: 12mm; }

        /* 1. Remove the app chrome (sidebar, navbar, footer, floating buttons). */
        .layout-menu,
        .layout-navbar,
        .layout-overlay,
        .menu-inner-shadow,
        .content-footer,
        .content-backdrop,
        .buy-now,
        .content-wrapper,
        .alert,
        footer,
        .receipt-show-page { display: none !important; }

        /* 2. Un-constrain the layout so the sheet flows across pages normally. */
        html, body {
            background: #fff !important;
            height: auto !important;
            overflow: visible !important;
        }
        .layout-wrapper,
        .layout-container,
        .layout-page,
        .content-wrapper {
            display: block !important;
            height: auto !important;
            min-height: 0 !important;
            overflow: visible !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* 3. The sheet itself. */
        .rp-sheet {
            display: block;
            width: 100%;
            color: #1c1c1c;
            font-family: 'Public Sans', -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
            font-size: 9.5pt;
            line-height: 1.45;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .rp-sheet *, .rp-sheet *::before, .rp-sheet *::after { box-sizing: border-box; }

        .rp-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        .rp-muted { color: #666; }

        /* Masthead */
        .rp-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10mm;
            padding-bottom: 1mm;
            margin-left: 30mm;
            border-bottom: 2px solid #d61c1c;
        }
        .rp-brand { display: flex; gap: 4mm; align-items: flex-start; }
        .rp-brand img { width: 22mm; height: auto; }
        .rp-brand-name { font-size: 8pt; font-weight: 700; letter-spacing: .01em; }
        .rp-brand address { margin: 1mm 0 0; font-style: normal; font-size: 8pt; color: #555; line-height: 1.4; }

        .rp-title { text-align: right; }
        .rp-title h1 { margin: 0; font-size: 20pt; font-weight: 700; color: #d61c1c; letter-spacing: .02em; line-height: 1; }
        .rp-title .rp-id { margin-top: 2mm; font-size: 11pt; font-weight: 700; }
        .rp-title .rp-date { font-size: 8.5pt; color: #555; }
        .rp-status {
            display: inline-block;
            margin-top: 2mm;
            padding: .6mm 3mm;
            border: 1.2px solid currentColor;
            border-radius: 1mm;
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .rp-status.is-completed { color: #1a7f45; }
        .rp-status.is-other     { color: #b3261e; }

        /* Three information blocks */
        .rp-info {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 6mm;
            margin: 0mm 0mm 0mm 50mm;
        }
        .rp-block { break-inside: avoid; }
        .rp-block h2 {
            margin: 0 0 1.5mm;
            padding-bottom: 1mm;
            border-bottom: 1px solid #cfcfcf;
            font-size: 8.5pt;
            font-weight: 700;
            color: #444;
        }
        .rp-block dl { margin: 0; }
        .rp-block dl > div { display: flex; justify-content: space-between; gap: 3mm; padding: .5mm 0; }
        .rp-block dt { font-weight: 400; color: #666; }
        .rp-block dd { margin: 0; font-weight: 600; text-align: right; }
        .rp-block .rp-name { font-size: 10.5pt; font-weight: 700; margin-bottom: .5mm; }
        .rp-block p { margin: 0; }

        /* Section headings */
        .rp-section { margin-top: 5mm; margin-left: 50mm; break-inside: avoid; }
        .rp-section > h2 { margin: 0 0 2mm; font-size: 10pt; font-weight: 700; }

        /* Tables */
        .rp-table { width: 100%; border-collapse: collapse; }
        .rp-table th {
            padding: 1.6mm 3mm;
            text-align: left;
            font-size: 8pt;
            font-weight: 600;
            color: #555;
            background: #f1f1f1;
            border-top: 1px solid #cfcfcf;
            border-bottom: 1px solid #cfcfcf;
        }
        .rp-table th.rp-num { text-align: right; }
        .rp-table td { padding: 2.4mm 3mm; border-bottom: 1px solid #e2e2e2; vertical-align: top; }
        .rp-table tr { break-inside: avoid; }

        /* Payment-method marker: same colour language as the on-screen cards */
        .rp-method { font-weight: 700; border-left: 3mm solid #999; }
        .rp-method.m-cash     { border-left-color: #1e3c72; }
        .rp-method.m-momo     { border-left-color: #e0a526; }
        .rp-method.m-transfer { border-left-color: #4b5563; }
        .rp-method.m-cheque   { border-left-color: #0f8a94; }
        .rp-method.m-other    { border-left-color: #6a11cb; }

        .rp-table tfoot td {
            font-weight: 700;
            border-top: 1.5px solid #1c1c1c;
            border-bottom: none;
        }

        /* Lower area: notes/signatures left, deductions summary right */
        .rp-lower {
            display: grid;
            grid-template-columns: 1fr 88mm;
            gap: 8mm;
            align-items: start;
            margin-top: 5mm;
            margin-left: 50mm;
            break-inside: avoid;
        }
        .rp-summary td:first-child { color: #555; }
        .rp-summary tr.rp-grand td {
            font-size: 11pt;
            font-weight: 700;
            color: #1c1c1c;
            background: #f1f1f1;
            border-top: 1.5px solid #1c1c1c;
            border-bottom: 1.5px solid #1c1c1c;
        }
        .rp-summary small { display: block; color: #666; font-size: 8pt; }

        .rp-cheque-img { margin-top: 3mm; break-inside: avoid; }
        .rp-cheque-img img { max-height: 48mm; max-width: 100%; border: 1px solid #cfcfcf; }
        .rp-cheque-img figcaption { font-size: 8pt; color: #666; margin-top: 1mm; }

        .rp-sign { display: grid; grid-template-columns: 1fr 1fr; gap: 20mm; margin-top: 85mm; margin-left: -100mm; }
        .rp-sign > div { border-top: 1px solid #1c1c1c; padding-top: 1.5mm; font-size: 8.5pt; color: #444; }
        .rp-sign strong { display: grid; color: #1c1c1c; }

        .rp-foot {
            margin-top: 8mm;
            padding-top: 2mm;
            border-top: 1px solid #cfcfcf;
            display: flex;
            justify-content: space-between;
            font-size: 7.5pt;
            color: #777;
        }
    }
</style>

<div class="rp-sheet">

    {{-- Masthead --}}
    <header class="rp-head">
        <div class="rp-brand">
            <img src="{{ asset('img/icons/brands/issobs.png') }}" alt="ISSOBS">
            <div>
                <div class="rp-brand-name">FIRST WATCH SECURITY SERVICE LIMITED</div>
                <address>
                    P.O.BOX AN 18529, GPS : GA-105-4850, Boundary Road, Accra North<br>
                    Tel: {{ $client->field->number ?? '' }}, +233(0) 560 027 411<br>
                    info@firstwatchsecgh.com.
                </address>
            </div>
        </div>

        <div class="rp-title">
            <h1>RECEIPT</h1>
            <div class="rp-id">FWSSR{{ $receipt->id }}</div>
            <div class="rp-date">{{ $receipt->receipt_month?->format('F d, Y') }}</div>
            <span class="rp-status {{ $receipt->status === 'completed' ? 'is-completed' : 'is-other' }}">{{ $receipt->status }}</span>
        </div>
    </header>

    {{-- Who / what / which invoice --}}
    <section class="rp-info">
        <div class="rp-block">
            <h2>Client From</h2>
            <p class="rp-name">{{ $client->name }}</p>
            @if($client->business_name && $client->business_name !== $client->name)
                <p>{{ $client->business_name }}</p>
            @endif
            <p class="rp-muted">{{ $client->phone_number }}@if($client->phone_number1) | {{ $client->phone_number1 }}@endif</p>
            <p class="rp-muted">{{ $client->field->name ?? '' }}</p>
            <p class="rp-muted">{{ $client->address }}</p>
        </div>
        @if($isMulti)
        <div class="rp-block">
            <h2>Invoices paid</h2>
            <dl>
                @forelse($allocs as $a)
                    <div><dt>FWSSi{{ $a->invoice_id }} · {{ $a->invoice?->invoice_month?->format('M Y') }}</dt><dd class="rp-num">{!! $fmt($a->settled) !!}</dd></div>
                @empty
                    <div><dt>Advance payment</dt><dd>not yet applied</dd></div>
                @endforelse
                @if($receipt->hasUnappliedCredit())
                    <div><dt>Client credit</dt><dd class="rp-num">{!! $fmt($receipt->unapplied_amount) !!}</dd></div>
                @endif
            </dl>
        </div>
        @else
        <div class="rp-block">
            <h2>Invoice</h2>
            <dl>
                <div><dt>Invoice no.</dt><dd>FWSSi{{ $receipt->invoice_id }}</dd></div>
                <div><dt>Invoice month</dt><dd>{{ $invoice?->invoice_month?->format('F Y') }}</dd></div>
                <div><dt>Amount</dt><dd class="rp-num">{!! $fmt($invoice?->total) !!}</dd></div>
                <div><dt>Balance</dt><dd class="rp-num">{!! $fmt($invoice?->balance) !!}</dd></div>
            </dl>
        </div>
        @endif
    </section>

    {{-- Payment received --}}
    <section class="rp-section">
        <table class="rp-table">
            <thead>
                <tr>
                    <th style="width:34%">Method</th>
                    <th>Details</th>
                    <th class="rp-num" style="width:30%">Amount</th>
                    @if($receipt->advance_payment)
                    <th>Advance payment</th>
                     @endif
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $p)
                    <tr>
                        <td class="rp-method m-{{ $p['key'] }}">{{ $p['label'] }}</td>
                        <td class="rp-muted">{{ $p['detail'] ?: '—' }}</td>
                        <td class="rp-num">{!! $fmt($p['amount']) !!}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="rp-muted">No payment amount recorded on this receipt.</td></tr>
                @endforelse
            </tbody>
            @if($payments->count() > 1)
                <tfoot>
                    <tr>
                        <td colspan="2">Total paid</td>
                        <td class="rp-num">{!! $fmt($payments->sum(fn ($p) => (float) $p['amount'])) !!}</td>
                    </tr>
                </tfoot>
            @endif
        </table>

        @if($receipt->cheque_amount > 0 && $receipt->image)
            <figure class="rp-cheque-img">
                <img src="{{ asset('storage/' . $receipt->image) }}" alt="Cheque copy">
                <figcaption>Cheque copy</figcaption>
            </figure>
        @endif
    </section>

    {{-- Deductions summary (same lines as the on-screen table) + sign-off --}}
    <section class="rp-lower">
        <table class="rp-table rp-summary">
            <tbody>
                <tr><td>Withholding ({{ $whtRate }}%)</td><td class="rp-num">{!! $fmt($receipt->wht_amount) !!}</td></tr>
                <tr><td>After WHT</td><td class="rp-num">{!! $fmt($receipt->amount_received) !!}</td></tr>
                <tr><td>7% VAT</td><td class="rp-num">{!! $fmt($receipt->vat7_value) !!}</td></tr>
                <tr><td>After VAT</td><td class="rp-num">{!! $fmt($receipt->vat7_amount) !!}</td></tr>
                <tr>
                    <td>Other deductions
                        @if($receipt->description)<small>{{ strtoupper($receipt->description) }}</small>@endif
                    </td>
                    <td class="rp-num">{!! $fmt($receipt->dAmount) !!}</td>
                </tr>
                <tr class="rp-grand">
                    <td>Total</td>
                    <td class="rp-num">{!! $fmt($receipt->total + $receipt->dAmount) !!}</td>
                </tr>
            </tbody>
        </table>

                <div>
            <div class="rp-sign">
                <div><strong>{{ $receipt->user?->name }}</strong>Received by</div>
                <div><strong>&nbsp;</strong>Authorised signature</div>
            </div>
        </div>
        
    </section>


    <footer class="rp-foot">
        <span>FWSSR{{ $receipt->id }} · {{ $invoiceRef }}</span>
        <span>Printed {{ now()->format('F d, Y, H:i A') }} by {{ Auth::user()->name }}</span>
    </footer>
</div>

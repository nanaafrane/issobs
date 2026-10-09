{{--
    The statement as a plain document (inline styles only) — used for the email
    body and the PDF, so both always show the same thing as the page.
--}}
@php
    $st = $statement;
    $c = $st['client'];
    $money = fn ($v) => \App\Services\Receipts\ClientStatement::money((float) $v);
    $label = ['invoice' => 'Invoice', 'receipt' => 'Payment', 'wht' => 'WHT', 'vat' => 'VAT 7%', 'deduction' => 'Deduction', 'credit_applied' => 'Credit applied', 'reversal' => 'Correction'];
    $cell = 'padding:5px 6px;border-bottom:1px solid #e5e7eb;vertical-align:top;';
    $num = $cell . 'text-align:right;white-space:nowrap;';
@endphp
<div style="font-family:'DejaVu Sans',Arial,Helvetica,sans-serif;font-size:11px;color:#1f2937;">
    <table style="width:100%;border-collapse:collapse;margin-bottom:12px;">
        <tr>
            <td style="vertical-align:top;">
                <div style="font-size:18px;font-weight:bold;">Statement of Account</div>
                <div style="margin-top:4px;font-weight:bold;">{{ $c->business_name ?: $c->name }}</div>
                @if($c->business_name && $c->name !== $c->business_name)<div>{{ $c->name }}</div>@endif
                <div style="color:#6b7280;">{{ $c->address }} {{ $c->phone_number ? '· ' . $c->phone_number : '' }}</div>
            </td>
            <td style="vertical-align:top;text-align:right;color:#374151;">
                <div>{{ config('app.name') }}</div>
                <div>Period: {{ $st['from'] ? $st['from']->format('d M Y') : 'start' }} – {{ ($st['to'] ?? now())->format('d M Y') }}</div>
                <div>Issued: {{ now()->format('d M Y') }}</div>
            </td>
        </tr>
    </table>

    <table style="width:100%;border-collapse:collapse;margin-bottom:14px;">
        <tr>
            <td style="{{ $cell }}">Opening balance</td><td style="{{ $num }}">{{ $money($st['opening']) }}</td>
            <td style="{{ $cell }}">Invoiced</td><td style="{{ $num }}">{{ number_format($st['invoiced'], 2) }}</td>
            <td style="{{ $cell }}">Received</td><td style="{{ $num }}">{{ number_format($st['received'], 2) }}</td>
            <td style="{{ $cell }}">WHT / VAT / deductions</td><td style="{{ $num }}">{{ number_format($st['withheld'], 2) }}</td>
        </tr>
        <tr>
            <td style="{{ $cell }}font-weight:bold;font-size:13px;" colspan="6">Balance {{ $st['closing'] < 0 ? '(in credit)' : 'due' }}</td>
            <td style="{{ $num }}font-weight:bold;font-size:13px;" colspan="2">GH₵ {{ $money($st['closing']) }}</td>
        </tr>
    </table>

    <table style="width:100%;border-collapse:collapse;">
        <thead>
            <tr style="background:#f3f4f6;">
                <th style="{{ $cell }}text-align:left;">Date</th>
                <th style="{{ $cell }}text-align:left;">Type</th>
                <th style="{{ $cell }}text-align:left;">Description</th>
                <th style="{{ $num }}">Debit</th>
                <th style="{{ $num }}">Credit</th>
                <th style="{{ $num }}">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="{{ $cell }}color:#6b7280;">{{ $st['from'] ? $st['from']->format('d M Y') : '' }}</td>
                <td style="{{ $cell }}"></td>
                <td style="{{ $cell }}color:#6b7280;">Opening balance</td>
                <td style="{{ $num }}"></td><td style="{{ $num }}"></td>
                <td style="{{ $num }}">{{ $money($st['opening']) }}</td>
            </tr>
            @foreach($st['lines'] as $line)
            <tr>
                <td style="{{ $cell }}">{{ $line['date']->format('d M Y') }}</td>
                <td style="{{ $cell }}">{{ $label[$line['kind']] ?? $line['kind'] }}</td>
                <td style="{{ $cell }}">{{ $line['description'] }}@if($line['details'])<div style="color:#6b7280;font-size:10px;">{{ $line['details'] }}</div>@endif</td>
                <td style="{{ $num }}">{{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '' }}</td>
                <td style="{{ $num }}">{{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '' }}</td>
                <td style="{{ $num }}">{{ $line['kind'] === 'credit_applied' ? '' : $money($line['balance']) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($st['open_invoices']->isNotEmpty())
    <div style="margin-top:16px;font-weight:bold;">Invoices still open</div>
    <table style="width:100%;border-collapse:collapse;margin-top:4px;">
        @foreach($st['open_invoices'] as $inv)
        <tr>
            <td style="{{ $cell }}">FWSSi{{ $inv->id }} — {{ $inv->invoice_month?->format('F Y') }}</td>
            <td style="{{ $cell }}color:#6b7280;">{{ $inv->due_date ? 'due ' . \Carbon\Carbon::parse($inv->due_date)->format('d M Y') : '' }}{{ $inv->days_overdue ? ' · ' . $inv->days_overdue . ' days overdue' : '' }}</td>
            <td style="{{ $num }}">{{ number_format($inv->outstanding, 2) }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    @if($st['credit_receipts']->isNotEmpty())
    <div style="margin-top:12px;color:#374151;">Unused credit on your account: <strong>GH₵ {{ number_format($st['credit_receipts']->sum('unapplied_amount'), 2) }}</strong> — it will be used on your next invoice.</div>
    @endif

    <div style="margin-top:16px;color:#6b7280;font-size:10px;">"CR" means you have paid ahead and are in credit. Please contact us if anything on this statement does not match your records.</div>
</div>

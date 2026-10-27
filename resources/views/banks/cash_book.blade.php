<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <style>
        .cb-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        .cb-band { display: grid; grid-template-columns: repeat(4, 1fr); background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; }
        .cb-band > div { padding: .9rem 1.1rem; border-right: 1px solid #e5e7eb; }
        .cb-band > div:last-child { border-right: 0; }
        .cb-band .label { font-size: .78rem; color: #6b7280; }
        .cb-band .value { font-size: 1.25rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        @media (max-width: 767px) { .cb-band { grid-template-columns: 1fr 1fr; } .cb-band > div { border-bottom: 1px solid #e5e7eb; } }
        table.cb-table td { font-size: .8rem; vertical-align: top; }
        table.cb-table th { font-size: .72rem; color: #6b7280; white-space: nowrap; }
        .cb-item { display: inline-block; background: #f1f3f9; border-radius: 4px; padding: 0 .35rem; margin: 0 .2rem .15rem 0; font-size: .74rem; }
        .cb-bar { height: 6px; background: #696cff; border-radius: 3px; }
        tr.cb-edge td { background: #f8f9fb; font-weight: 600; }
        @media print { #layout-menu, .layout-navbar, .no-print, footer { display: none !important; } .layout-page { padding: 0 !important; } }
    </style>
    @endsection

    @section('content')
    @php
        $m = fn ($v) => number_format((float) $v, 2);
        $blank = fn ($v) => abs((float) $v) > 0.004 ? number_format((float) $v, 2) : '';
        $title = $a['bank']?->name ?? 'All accounts';
        $max = max(1, (float) $a['items']->max());
        $query = ['bank' => $a['bank']?->id ?? 'all', 'from' => $a['from']->toDateString(), 'to' => $a['to']->toDateString()];
        $colTotals = $a['offices']->mapWithKeys(fn ($o) => [$o => round(collect($a['matrix'])->sum(fn ($r) => (float) ($r[$o] ?? 0)), 2)]);
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div>
                <h3 class="text-primary mb-1"><i class="bx bx-book-content me-1"></i> Cash book — {{ $title }}</h3>
                <div class="text-muted small">{{ $a['from']->format('d M Y') }} – {{ $a['to']->format('d M Y') }} · every payment spread over its items (expense type and office), and the total spent on each item. Built from the ledger, so nothing is typed twice.</div>
            </div>
            <div class="d-flex gap-2 no-print">
                <a href="{{ route('money.cashbook.export', $query) }}" class="btn btn-secondary btn-sm"><i class="bx bx-download"></i> Excel (Main Cash layout)</a>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print</button>
            </div>
        </div>

        <form method="GET" class="d-flex flex-wrap align-items-end gap-2 mb-3 no-print">
            <div><label class="form-label small mb-0">Account</label>
                <select name="bank" class="form-select form-select-sm">
                    @foreach($accounts as $acc)<option value="{{ $acc->id }}" @selected($a['bank']?->id === $acc->id)>{{ $acc->name }}{{ $acc->isCash() ? ' (cash)' : '' }}</option>@endforeach
                    <option value="all" @selected(! $a['bank'])>All accounts (transfers between them left out)</option>
                </select></div>
            <div><label class="form-label small mb-0">Month</label><input type="month" name="month" class="form-control form-control-sm" value="{{ $a['from']->format('Y-m') }}" onchange="this.form.from.value='';this.form.to.value='';"></div>
            <div><label class="form-label small mb-0">or from</label><input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}"></div>
            <div><label class="form-label small mb-0">to</label><input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}"></div>
            <button class="btn btn-sm btn-primary">Show</button>
        </form>

        <div class="cb-band mb-3">
            <div><div class="label">Opening balance</div><div class="value">{{ $a['opening'] !== null ? $m($a['opening']) : '—' }}</div></div>
            <div><div class="label">Receipts (money in)</div><div class="value text-success">{{ $m($a['receipts']) }}</div></div>
            <div><div class="label">Payments (money out)</div><div class="value text-danger">{{ $m($a['payments']) }}</div></div>
            <div><div class="label">Closing balance</div><div class="value">{{ $a['closing'] !== null ? $m($a['closing']) : '—' }}</div></div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-5">
                <div class="card h-100"><div class="card-body">
                    <h6 class="mb-2">Total spent per item</h6>
                    @forelse($a['items'] as $item => $total)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small"><span>{{ $item }}</span><span class="cb-num fw-semibold">{{ $m($total) }}</span></div>
                            <div class="cb-bar" style="width: {{ max(1, round(max(0, $total) / $max * 100)) }}%"></div>
                        </div>
                    @empty
                        <div class="text-muted small">Nothing was paid out in this period.</div>
                    @endforelse
                    @if($a['items']->isNotEmpty())
                    <hr class="my-2">
                    <div class="d-flex justify-content-between fw-bold"><span>Total</span><span class="cb-num">{{ $m($a['analysed']) }}</span></div>
                    <div class="small text-muted mt-1">Money returned on a payment, or a cancelled payment, is taken off the item it belongs to.</div>
                    @endif
                </div></div>
            </div>
            <div class="col-xl-7">
                <div class="card h-100"><div class="card-body">
                    <h6 class="mb-2">Per item and office</h6>
                    <div class="table-responsive"><table class="table table-sm mb-0">
                        <thead><tr><th>Item</th>@foreach($a['offices'] as $o)<th class="cb-num">{{ $o }}</th>@endforeach<th class="cb-num">Total</th></tr></thead>
                        <tbody>
                        @foreach($a['items'] as $item => $total)
                            <tr><td class="small">{{ $item }}</td>@foreach($a['offices'] as $o)<td class="cb-num small">{{ $blank($a['matrix'][$item][$o] ?? 0) }}</td>@endforeach<td class="cb-num small fw-semibold">{{ $m($total) }}</td></tr>
                        @endforeach
                        </tbody>
                        @if($a['items']->isNotEmpty())
                        <tfoot><tr class="fw-bold"><td>Total</td>@foreach($a['offices'] as $o)<td class="cb-num">{{ $m($colTotals[$o]) }}</td>@endforeach<td class="cb-num">{{ $m($a['analysed']) }}</td></tr></tfoot>
                        @endif
                    </table></div>
                </div></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header py-2"><h6 class="mb-0">Cash book lines</h6></div>
            <div class="table-responsive">
                <table class="table table-sm cb-table mb-0">
                    <thead><tr><th>Date</th><th>Payment details</th><th>Cheq / ref</th>@if(! $a['bank'])<th>Account</th>@endif<th class="cb-num">Payment</th><th class="cb-num">Receipt</th>@if($a['bank'])<th class="cb-num">Balance</th>@endif<th>Item(s)</th></tr></thead>
                    <tbody>
                    @if($a['opening'] !== null)<tr class="cb-edge"><td>{{ $a['from']->format('d.m.y') }}</td><td>Opening balance</td><td></td><td></td><td></td><td class="cb-num">{{ $m($a['opening']) }}</td><td></td></tr>@endif
                    @forelse($a['lines'] as $l)
                        <tr>
                            <td class="text-nowrap">{{ $l['date']?->format('d.m.y') }}</td>
                            <td>{{ $l['details'] }}<div>@include('banks.partials.ledger_source', ['t' => $l['line']])</div></td>
                            <td>{{ $l['reference'] }}</td>
                            @if(! $a['bank'])<td class="small">{{ $l['account'] }}</td>@endif
                            <td class="cb-num text-danger">{{ $blank($l['payment']) }}</td>
                            <td class="cb-num text-success">{{ $blank($l['receipt']) }}</td>
                            @if($a['bank'])<td class="cb-num">{{ $m($l['balance']) }}</td>@endif
                            <td>@foreach($l['splits'] as $s)<span class="cb-item">{{ $s['item'] }}{{ $s['office'] ? ' · ' . $s['office'] : '' }} <strong>{{ $m($s['amount']) }}</strong></span>@endforeach</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-3">No money moved in this period.</td></tr>
                    @endforelse
                    <tr class="cb-edge"><td></td><td>TOTAL</td><td></td>@if(! $a['bank'])<td></td>@endif<td class="cb-num">{{ $m($a['payments']) }}</td><td class="cb-num">{{ $m($a['receipts']) }}</td>@if($a['bank'])<td class="cb-num">{{ $a['closing'] !== null ? $m($a['closing']) : '' }}</td>@endif<td class="cb-num">{{ $m($a['analysed']) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endsection
</x-sales-dashboard>

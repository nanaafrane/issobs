<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">
    <style>
        .st-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        .st-in { color: #1a7f45; } .st-out { color: #b3261e; } .st-muted { color: #6b7280; }
        .st-band { display: grid; grid-template-columns: repeat(4, 1fr); background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; }
        .st-band > div { padding: 1rem 1.25rem; border-right: 1px solid #e5e7eb; }
        .st-band > div:last-child { border-right: 0; }
        .st-band .label { font-size: .8rem; color: #6b7280; }
        .st-band .value { font-size: 1.35rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        @media (max-width: 767px) { .st-band { grid-template-columns: 1fr 1fr; } .st-band > div { border-bottom: 1px solid #e5e7eb; } }
        table.st-table td { font-size: .85rem; vertical-align: middle; }
        table.st-table th { font-size: .78rem; color: #6b7280; white-space: nowrap; }
        tr.st-edge td { background: #f8f9fb; font-weight: 600; }
        .st-src { font-size: .78rem; }
        @media print { #layout-menu, .layout-navbar, .no-print, footer, .dt-buttons, .dt-search { display: none !important; } .layout-page { padding: 0 !important; } }
    </style>
    @endsection

    @section('content')
    @php
        $m = fn ($v) => number_format((float) $v, 2);
        $signed = fn ($v) => ((float) $v < 0 ? '−' : '') . number_format(abs((float) $v), 2);
        $preset = fn (Carbon\Carbon $a, Carbon\Carbon $b) => route('banks.show', ['bank' => $bank->id, 'from' => $a->format('Y-m-d'), 'to' => $b->format('Y-m-d')]);
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <a href="{{ route('banks.index') }}" class="small no-print"><i class="bx bx-arrow-back"></i> Bank &amp; cash</a>
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mt-1 mb-3">
            <div>
                <h3 class="mb-1">
                    {{ $bank->name }}
                    <span class="badge {{ $bank->isCash() ? 'bg-label-success' : 'bg-label-primary' }} align-middle fs-6">{{ \App\Models\Bank::KINDS[$bank->kind] ?? 'Bank account' }}</span>
                    @if($bank->is_active === false)<span class="badge bg-label-secondary align-middle fs-6">closed</span>@endif
                </h3>
                <div class="st-muted small">
                    @if($bank->isCash()) Held by {{ $bank->custodian ?: '—' }}
                    @else {{ $bank->branch }}@if($bank->acc_number) · Account {{ $bank->acc_number }}@endif @endif
                    · Statement {{ $st['from']->format('d M Y') }} – {{ $st['to']->format('d M Y') }}
                </div>
            </div>
            @if($bank->is_active !== false)
            <div class="d-flex gap-2 no-print">
                <a href="{{ route('money.cashbook', ['bank' => $bank->id, 'from' => $st['from']->format('Y-m-d'), 'to' => $st['to']->format('Y-m-d')]) }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-book-content"></i> Cash book by item</a>
                <a href="{{ route('money.transfers.create', ['to' => $bank->id]) }}" class="btn btn-outline-primary btn-sm"><i class="bx bx-transfer"></i> Fund this account</a>
                <a href="{{ route('money.payments.create', ['bank' => $bank->id]) }}" class="btn btn-primary btn-sm"><i class="bx bx-money-withdraw"></i> Pay from it</a>
            </div>
            @endif
        </div>

        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        @if($check !== null)
            <div class="alert alert-warning">The stored balance ({{ $m($bank->total) }}) does not equal the opening balance plus the ledger ({{ $m($check) }}). Something changed the balance without a ledger line — post an adjustment with the reason once found.</div>
        @endif

        <form method="GET" class="d-flex flex-wrap align-items-end gap-2 mb-3 no-print">
            <div><label class="form-label small mb-0" for="from">From</label><input type="date" id="from" name="from" class="form-control form-control-sm" value="{{ $st['from']->format('Y-m-d') }}"></div>
            <div><label class="form-label small mb-0" for="to">To</label><input type="date" id="to" name="to" class="form-control form-control-sm" value="{{ $st['to']->format('Y-m-d') }}"></div>
            <button class="btn btn-sm btn-primary">Show</button>
            <div class="btn-group btn-group-sm ms-md-auto">
                <a class="btn btn-outline-secondary" href="{{ $preset(now()->startOfMonth(), now()) }}">This month</a>
                <a class="btn btn-outline-secondary" href="{{ $preset(now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()) }}">Last month</a>
                <a class="btn btn-outline-secondary" href="{{ $preset(now()->startOfYear(), now()) }}">This year</a>
                <a class="btn btn-outline-secondary" href="{{ $preset($bank->opening_date ?? now()->subYear(), now()) }}">Since opening</a>
            </div>
        </form>

        <div class="st-band mb-3">
            <div><div class="label">Opening balance {{ $st['from']->format('d M Y') }}</div><div class="value">{{ $signed($st['opening']) }}</div></div>
            <div><div class="label">Money in</div><div class="value st-in">{{ $m($st['in']) }}</div></div>
            <div><div class="label">Money out</div><div class="value st-out">{{ $m($st['out']) }}</div></div>
            <div><div class="label">Closing balance {{ $st['to']->format('d M Y') }}</div><div class="value">{{ $signed($st['closing']) }}</div></div>
        </div>

        <div class="card mb-3">
            <div class="table-responsive p-2">
                <table class="table table-sm st-table mb-0 w-100" id="stTable">
                    <thead><tr><th>Date</th><th>Details</th><th>Source</th><th class="st-num">Money in</th><th class="st-num">Money out</th><th class="st-num">Balance</th></tr></thead>
                    <tbody>
                        <tr class="st-edge"><td>{{ $st['from']->format('d M Y') }}</td><td>Opening balance</td><td></td><td></td><td></td><td class="st-num">{{ $signed($st['opening']) }}</td></tr>
                        @foreach($st['lines'] as $t)
                        <tr>
                            <td class="text-nowrap">{{ $t->entry_date?->format('d M Y') }}</td>
                            <td>@include('banks.partials.channel_badge', ['t' => $t]) {{ $t->narration }}
                                @if($t->payment?->status === 'issued')<span class="badge bg-label-warning ms-1">not cleared</span>@endif</td>
                            <td class="st-src">@include('banks.partials.ledger_source', ['t' => $t])</td>
                            <td class="st-num st-in">{{ $t->credit > 0 ? $m($t->credit) : '' }}</td>
                            <td class="st-num st-out">{{ $t->debit > 0 ? $m($t->debit) : '' }}</td>
                            <td class="st-num">{{ $signed($t->running) }}</td>
                        </tr>
                        @endforeach
                        <tr class="st-edge"><td>{{ $st['to']->format('d M Y') }}</td><td>Closing balance</td><td></td><td class="st-num st-in">{{ $m($st['in']) }}</td><td class="st-num st-out">{{ $m($st['out']) }}</td><td class="st-num">{{ $signed($st['closing']) }}</td></tr>
                    </tbody>
                </table>
            </div>
            @if($st['lines']->isEmpty())<div class="card-body pt-0 text-muted small">No money moved in this period.</div>@endif
        </div>

        <div class="row g-3">
            @if(! $bank->isCash())
            <div class="col-lg-7">
                <div class="card h-100"><div class="card-body">
                    <h6>Reconcile with the bank statement</h6>
                    @php $unclearedTotal = round($st['uncleared']->sum('amount'), 2); @endphp
                    <div class="d-flex justify-content-between"><span>Balance in the books today</span><span class="st-num">{{ $signed($bank->total) }}</span></div>
                    <div class="d-flex justify-content-between"><span>+ cheques issued, not yet presented ({{ $st['uncleared']->count() }})</span><span class="st-num">{{ $m($unclearedTotal) }}</span></div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between fw-bold"><span>The bank statement should show</span><span class="st-num">{{ $signed((float) $bank->total + $unclearedTotal) }}</span></div>
                    <div class="small st-muted mt-1">If the bank shows something else, look for bank charges, interest or deposits not yet entered, and enter them (charges: <em>Pay from it</em> › Bank charges).</div>
                    @if($st['uncleared']->isNotEmpty())
                    <table class="table table-sm st-table mt-3 mb-0">
                        <thead><tr><th>Issued</th><th>Cheque</th><th>Payee</th><th class="st-num">Amount</th><th class="no-print"></th></tr></thead>
                        <tbody>
                        @foreach($st['uncleared'] as $p)
                            <tr>
                                <td>{{ $p->payment_date->format('d M Y') }}</td>
                                <td><a href="{{ route('money.payments.show', $p) }}">{{ $p->reference }}</a></td>
                                <td>{{ $p->payee }}</td>
                                <td class="st-num">{{ $m($p->amount) }}</td>
                                <td class="no-print">
                                    <form method="POST" action="{{ route('money.payments.clear', $p) }}" class="d-flex gap-1">
                                        @csrf
                                        <input type="date" name="cleared_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" style="width: 9.5rem">
                                        <button class="btn btn-sm btn-outline-success text-nowrap">Cleared</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    @endif
                </div></div>
            </div>
            @else
            <div class="col-lg-7">
                <div class="card h-100"><div class="card-body">
                    <h6>Cash count</h6>
                    <p class="small st-muted">Count the cash in the box and enter it. Any difference should be explained; a Finance Manager posts it as an adjustment.</p>
                    <div class="d-flex flex-wrap gap-3 align-items-end">
                        <div><label class="form-label small mb-0" for="counted">Cash counted (GH₵)</label><input type="number" step="0.01" min="0" id="counted" class="form-control form-control-sm" style="width: 11rem"></div>
                        <div class="small">Books: <strong class="st-num">{{ $m($bank->total) }}</strong></div>
                        <div class="small">Difference: <strong id="countDiff" class="st-num">—</strong></div>
                    </div>
                </div></div>
            </div>
            @endif

            <div class="col-lg-5 no-print">
                <div class="card h-100"><div class="card-body">
                    <h6>Account details</h6>
                    <form method="POST" action="{{ route('banks.update', $bank) }}" class="row g-2">
                        @csrf @method('PUT')
                        <div class="col-12"><label class="form-label small mb-0">Name</label><input name="name" class="form-control form-control-sm" value="{{ old('name', $bank->name) }}" required></div>
                        @if($bank->isCash())
                            <div class="col-12"><label class="form-label small mb-0">Held by</label><input name="custodian" class="form-control form-control-sm" value="{{ old('custodian', $bank->custodian) }}"></div>
                        @else
                            <div class="col-6"><label class="form-label small mb-0">Account number</label><input name="acc_number" class="form-control form-control-sm" value="{{ old('acc_number', $bank->acc_number) }}" required></div>
                            <div class="col-6"><label class="form-label small mb-0">Branch</label><input name="branch" class="form-control form-control-sm" value="{{ old('branch', $bank->branch) }}"></div>
                        @endif
                        <div class="col-12 small st-muted">Opening balance {{ $signed($bank->opening_balance) }} on {{ $bank->opening_date?->format('d M Y') ?? '—' }}. The balance is never typed in; it changes only through the ledger.</div>
                        <div class="col-12"><button class="btn btn-sm btn-outline-dark">Save details</button></div>
                    </form>

                    @if($canCorrect)
                    <hr>
                    <h6 class="mb-1">Adjust the balance</h6>
                    <p class="small st-muted mb-2">For a difference found on a cash count or bank statement (e.g. interest, a charge). Use + to add, − to take off. The reason is kept on the ledger line.</p>
                    <form method="POST" action="{{ route('banks.adjust', $bank) }}" class="row g-2" onsubmit="return confirm('Post this adjustment to {{ addslashes($bank->name) }}?');">
                        @csrf
                        {!! \App\Support\SubmissionGuard::field() !!}
                        <div class="col-5"><input type="number" step="0.01" name="amount" id="adjustAmount" class="form-control form-control-sm" placeholder="e.g. -20.00" required></div>
                        <div class="col-7"><input type="date" name="adjust_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}"></div>
                        <div class="col-12"><input name="reason" class="form-control form-control-sm" maxlength="300" placeholder="Reason, e.g. cash count 20 Oct short by 20.00" required></div>
                        <div class="col-12"><button class="btn btn-sm btn-warning" data-loading>Post adjustment</button></div>
                    </form>

                    <hr>
                    <div class="d-flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('banks.toggle', $bank) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary" @disabled($bank->is_active !== false && abs((float) $bank->total) > 0.004)
                                title="{{ $bank->is_active !== false && abs((float) $bank->total) > 0.004 ? 'Only an account at 0.00 can be closed' : '' }}">
                                {{ $bank->is_active === false ? 'Reopen account' : 'Close account' }}
                            </button>
                        </form>
                        @if(! $hasHistory && abs((float) $bank->total) < 0.005)
                        <form method="POST" action="{{ route('banks.destroy', $bank) }}" onsubmit="return confirm('Delete {{ addslashes($bank->name) }}? It has never been used.');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete (never used)</button>
                        </form>
                        @endif
                    </div>
                    @endif
                </div></div>
            </div>
        </div>
    </div>
    @endsection

    @section('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.dataTables.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.print.min.js"></script>
    <script>
        if (window.DataTable) try {   // the cash count below must work even if the export buttons fail to load
            new DataTable('#stTable', {
                ordering: false, paging: false, info: false,
                layout: { topStart: { buttons: [
                    { extend: 'excelHtml5', text: 'Export to Excel', className: 'btn btn-secondary btn-sm', title: @json($bank->name . ' statement ' . $st['from']->format('Y-m-d') . ' to ' . $st['to']->format('Y-m-d')) },
                    { text: 'Print', className: 'btn btn-outline-secondary btn-sm', action: function () { window.print(); } }
                ] } }
            });
        } catch (e) { console.warn(e); }
        (function () {
            var c = document.getElementById('counted');
            if (!c) return;
            var books = {{ json_encode(round((float) $bank->total, 2)) }};
            c.addEventListener('input', function () {
                var v = parseFloat(c.value), out = document.getElementById('countDiff');
                if (isNaN(v)) { out.textContent = '—'; return; }
                var d = Math.round((v - books) * 100) / 100;
                out.textContent = (d > 0 ? '+' : d < 0 ? '−' : '') + Math.abs(d).toFixed(2) + (d === 0 ? ' (agrees)' : d < 0 ? ' short' : ' over');
                out.className = 'st-num ' + (d < 0 ? 'st-out' : d > 0 ? 'st-in' : '');
                var adj = document.getElementById('adjustAmount');
                if (adj && d !== 0) adj.value = d.toFixed(2);
            });
        })();
    </script>
    @endsection
</x-sales-dashboard>

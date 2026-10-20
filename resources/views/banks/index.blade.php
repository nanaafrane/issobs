<x-sales-dashboard>

    @php
        $user = Auth::user();
        $canSeeAll = $user?->hasRole(['Invoice', 'Finance Manager']) ?? false;
        $canDelete = $user?->hasRole(['Finance Manager']) ?? false;
        $money = fn ($v) => 'GH₵ ' . number_format((float) $v, 2);
        $groupName = ['day' => 'Day', 'week' => 'Week starting', 'month' => 'Month', 'year' => 'Year'][$group] ?? 'Period';

        // Period presets: each one also picks the grouping that reads best for that span.
        $preset = fn (Carbon\Carbon $a, Carbon\Carbon $b, string $g) => url('banks') . '?' . http_build_query([
            'from' => $a->format('Y-m-d'), 'to' => $b->format('Y-m-d'), 'group' => $g,
        ]);
        $presets = [
            'Today'          => $preset(now(), now(), 'day'),
            'This week'      => $preset(now()->startOfWeek(), now()->endOfWeek(), 'day'),
            'This month'     => $preset(now()->startOfMonth(), now()->endOfMonth(), 'day'),
            'This year'      => $preset(now()->startOfYear(), now()->endOfYear(), 'month'),
            'Last 12 months' => $preset(now()->subMonths(11)->startOfMonth(), now(), 'month'),
        ];
    @endphp

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">
    <style>
        .bk-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        .bk-in  { color: #1a7f45; }
        .bk-out { color: #b3261e; }
        .bk-muted { color: #6b7280; }

        /* One summary band instead of four separate cards: figures read left to right as a sum. */
        .bk-band { display: grid; grid-template-columns: 1.4fr 1fr 1fr 1fr; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; }
        .bk-band > div { padding: 1.1rem 1.4rem; border-right: 1px solid #e5e7eb; }
        .bk-band > div:last-child { border-right: 0; }
        .bk-band .label { font-size: .8rem; color: #6b7280; margin-bottom: .25rem; }
        .bk-band .value { font-size: 1.45rem; font-weight: 700; line-height: 1.2; font-variant-numeric: tabular-nums; }
        .bk-band .lead-value { font-size: 1.9rem; }
        .bk-band .note { font-size: .78rem; color: #6b7280; margin-top: .35rem; }
        @media (max-width: 991px) {
            .bk-band { grid-template-columns: 1fr 1fr; }
            .bk-band > div { border-bottom: 1px solid #e5e7eb; }
        }

        .bk-toolbar { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: .75rem 1rem; }
        .bk-toolbar label { font-size: .75rem; color: #6b7280; margin-bottom: .15rem; }

        .bk-panel { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; }
        .bk-panel > .head { padding: .9rem 1.25rem; border-bottom: 1px solid #e5e7eb; font-weight: 600; display: flex; justify-content: space-between; align-items: center; gap: .5rem; }
        .bk-panel > .head small { font-weight: 400; color: #6b7280; }

        /* Where the money sits: each bank is a row with a bar showing its share of the total. */
        .bk-bank { padding: .85rem 1.25rem; border-bottom: 1px solid #f0f1f3; }
        .bk-bank:last-child { border-bottom: 0; }
        .bk-bank .name { font-weight: 600; }
        .bk-bank .meta { font-size: .78rem; color: #6b7280; }
        .bk-bar { height: 6px; border-radius: 3px; background: #eef0f3; margin: .5rem 0 .35rem; overflow: hidden; }
        .bk-bar > span { display: block; height: 100%; background: #d61c1c; }
        .bk-flow { font-size: .78rem; display: flex; gap: 1rem; }

        .bk-tabs .nav-link { padding: .55rem 1.1rem; }
        table.bk-table th { font-size: .78rem; font-weight: 600; color: #6b7280; white-space: nowrap; }
        table.bk-table td { vertical-align: middle; font-size: .85rem; }
        table.bk-table tfoot td { font-weight: 700; border-top: 2px solid #1e293b; }
        .bk-src { font-size: .78rem; }
    </style>
    @endsection

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

        {{-- Title and the one primary action --}}
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
            <div>
                <h3 class="mb-1"><i class="bx bxs-bank me-1"></i> Banks</h3>
                <div class="bk-muted small">Balances, money in and out, and every ledger entry for the period you choose.</div>
            </div>
            @if($canSeeAll)
            <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addBankModal">
                <i class="icon-base bx bx-plus"></i> Add bank
            </button>
            @endif
        </div>

        <div class="card-header ml-2 d-none d-lg-block px-0">
            @include('flash-messages')
        </div>

        @if(! $canSeeAll)
            <div class="alert alert-info">Bank balances and ledger entries are visible to Finance only. Your branch deposits are under <strong>Accounts › Bank Deposit</strong>.</div>
        @else

        {{-- Period --}}
        <form method="GET" action="{{ url('banks') }}" class="bk-toolbar d-flex flex-wrap align-items-end gap-3 mb-4">
            <div>
                <label class="d-block" for="bkFrom">From</label>
                <input type="date" id="bkFrom" name="from" class="form-control form-control-sm" value="{{ $from->format('Y-m-d') }}">
            </div>
            <div>
                <label class="d-block" for="bkTo">To</label>
                <input type="date" id="bkTo" name="to" class="form-control form-control-sm" value="{{ $to->format('Y-m-d') }}">
            </div>
            <div>
                <label class="d-block" for="bkGroup">Group by</label>
                <select id="bkGroup" name="group" class="form-select form-select-sm">
                    @foreach(['day' => 'Day', 'week' => 'Week', 'month' => 'Month', 'year' => 'Year'] as $v => $l)
                        <option value="{{ $v }}" @selected($group === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Show</button>
            <div class="btn-group btn-group-sm ms-lg-auto" role="group" aria-label="Quick periods">
                @foreach($presets as $label => $href)
                    <a href="{{ $href }}" class="btn btn-outline-secondary">{{ $label }}</a>
                @endforeach
            </div>
        </form>

        {{-- Summary band: balance now, then what moved in the chosen period --}}
        <div class="bk-band mb-4">
            <div>
                <div class="label">Total across {{ $banks->count() }} {{ Str::plural('bank', $banks->count()) }}</div>
                <div class="value lead-value">{{ $money($totalBalance) }}</div>
                <div class="note">
                    @if($projection['runway_days'] !== null)
                        At the last 30 days' pace this lasts about <strong>{{ $projection['runway_days'] }} days</strong>.
                    @else
                        In 30 days at the same pace: <strong>{{ $money($projection['balance']) }}</strong>.
                    @endif
                </div>
            </div>
            <div>
                <div class="label">Money in</div>
                <div class="value bk-in">{{ $money($inTotal) }}</div>
                <div class="note">{{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}</div>
            </div>
            <div>
                <div class="label">Money out</div>
                <div class="value bk-out">{{ $money($outTotal) }}</div>
                <div class="note">Expenses, salaries and withdrawals</div>
            </div>
            <div>
                <div class="label">Net movement</div>
                <div class="value {{ $net >= 0 ? 'bk-in' : 'bk-out' }}">{{ $net >= 0 ? '+' : '−' }}{{ $money(abs($net)) }}</div>
                <div class="note">{{ $net >= 0 ? 'Balances grew' : 'Balances shrank' }} in this period</div>
            </div>
        </div>

        {{-- Chart and bank positions --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="bk-panel h-100">
                    <div class="head">Money in and out <small>by {{ strtolower($groupName === 'Week starting' ? 'week' : $groupName) }}</small></div>
                    <div class="p-3"><div id="bkTrend"></div></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="bk-panel h-100">
                    <div class="head">Where the money sits <small>share of total</small></div>
                    <div style="max-height: 24rem; overflow-y: auto;">
                        @forelse($banks as $bank)
                            @php
                                $p = $perBank[$bank->id] ?? null;
                                $share = $totalBalance > 0 ? max(0, $bank->total) / $totalBalance * 100 : 0;
                                $off = isset($lastBalances[$bank->id]) && abs($lastBalances[$bank->id] - $bank->total) > 0.005;
                            @endphp
                            <div class="bk-bank">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="name">{{ $bank->name }}</div>
                                        <div class="meta">
                                            {{ $bank->branch }}@if($bank->acc_number) · •••• {{ substr($bank->acc_number, -4) }}@endif
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold bk-num">{{ $money($bank->total) }}</div>
                                        @if($off)
                                            <span class="badge bg-label-warning" title="Balance on this bank differs from its last ledger entry ({{ $money($lastBalances[$bank->id]) }}). Review its statement.">Check balance</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="bk-bar"><span style="width: {{ number_format($share, 1, '.', '') }}%"></span></div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="bk-flow">
                                        <span class="bk-in">+ {{ number_format($p->in_total ?? 0, 2) }}</span>
                                        <span class="bk-out">− {{ number_format($p->out_total ?? 0, 2) }}</span>
                                    </div>
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-label="Actions for {{ $bank->name }}">
                                            <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="{{ url('banks', $bank->id) }}"><i class="icon-base bx bx-detail me-1"></i> Statement and details</a>
                                            @if($canDelete)
                                            <form action="{{ url('banks', $bank->id) }}" method="POST"
                                                  onsubmit="return confirm('Delete {{ addslashes($bank->name) }}? This cannot be undone.');">
                                                @csrf @method('DELETE')
                                                <button class="dropdown-item text-danger" type="submit" @disabled(abs($bank->total) > 0.004)
                                                    title="{{ abs($bank->total) > 0.004 ? 'Only a bank with a zero balance can be deleted.' : '' }}">
                                                    <i class="icon-base bx bx-trash me-1"></i> Delete
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-center bk-muted">No bank accounts yet. Use <strong>Add bank</strong> to create the first one.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Ledger and period summary share one panel --}}
        <div class="bk-panel mb-4">
            <div class="px-3 pt-2">
                <ul class="nav nav-tabs bk-tabs border-0" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#bkLedger" type="button" role="tab">Ledger <span class="badge bg-label-dark ms-1">{{ $transactions->count() }}</span></button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#bkSummary" type="button" role="tab">Summary by {{ strtolower($group) }}</button></li>
                </ul>
            </div>

            <div class="tab-content p-3 border-top">

                <div class="tab-pane fade show active" id="bkLedger" role="tabpanel">
                    @if($truncated)
                        <div class="alert alert-warning py-2 small">Showing the latest {{ number_format($transactions->count()) }} entries. Narrow the dates to see the rest.</div>
                    @endif

                    <div class="d-flex flex-wrap gap-2 align-items-end mb-3">
                        <div>
                            <label class="form-label small mb-0" for="bkBankFilter">Bank</label>
                            <select id="bkBankFilter" class="form-select form-select-sm">
                                <option value="">All banks</option>
                                @foreach($banks as $bank)<option value="{{ $bank->name }}">{{ $bank->name }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label small mb-0" for="bkDirFilter">Direction</label>
                            <select id="bkDirFilter" class="form-select form-select-sm">
                                <option value="">In and out</option>
                                <option value="in">Money in only</option>
                                <option value="out">Money out only</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="bkLedgerTable" class="display bk-table w-100">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Bank</th>
                                    <th>Details</th>
                                    <th>Source</th>
                                    <th class="bk-num">Money in</th>
                                    <th class="bk-num">Money out</th>
                                    <th class="bk-num">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $t)
                                <tr>
                                    <td data-order="{{ $t->created_at->timestamp }}">
                                        {{ $t->created_at->format('d M Y') }}
                                        <div class="bk-muted bk-src">{{ $t->created_at->format('H:i') }}</div>
                                    </td>
                                    <td>{{ $t->bank?->name }}</td>
                                    <td>
                                        @if($t->channel === 'momo_charge')<span class="badge bg-label-danger me-1">MoMo charge</span>@elseif($t->channel)<span class="badge bg-label-primary me-1">{{ $t->channel }}</span>@endif
                                        {{ $t->narration }}
                                    </td>
                                    <td class="bk-src">
                                        @if($t->receipt_id)<a href="{{ url('receipt', $t->receipt_id) }}">Receipt FWSSR{{ $t->receipt_id }}</a>
                                        @elseif($t->deposit_id)<a href="{{ route('deposit.show', $t->deposit_id) }}">Deposit #{{ $t->deposit_id }}</a>
                                        @elseif($t->expense_id)Expense #{{ $t->expense_id }}
                                        @else <span class="bk-muted">Manual</span>@endif
                                    </td>
                                    <td class="bk-num bk-in" data-order="{{ (float) $t->credit }}" data-dir="in">{{ $t->credit > 0 ? number_format($t->credit, 2) : '' }}</td>
                                    <td class="bk-num bk-out" data-order="{{ (float) $t->debit }}" data-dir="out">{{ $t->debit > 0 ? number_format($t->debit, 2) : '' }}</td>
                                    <td class="bk-num fw-semibold" data-order="{{ (float) $t->balance }}">{{ number_format($t->balance, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4">Totals for the rows shown</td>
                                    <td class="bk-num bk-in" id="bkFootIn"></td>
                                    <td class="bk-num bk-out" id="bkFootOut"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="bkSummary" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-sm bk-table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ $groupName }}</th>
                                    <th class="bk-num">Entries</th>
                                    <th class="bk-num">Money in</th>
                                    <th class="bk-num">Money out</th>
                                    <th class="bk-num">Net</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($series as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="bk-num">{{ $row['count'] }}</td>
                                    <td class="bk-num bk-in">{{ number_format($row['in'], 2) }}</td>
                                    <td class="bk-num bk-out">{{ number_format($row['out'], 2) }}</td>
                                    <td class="bk-num fw-semibold {{ $row['net'] >= 0 ? 'bk-in' : 'bk-out' }}">{{ number_format($row['net'], 2) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center bk-muted py-4">No ledger entries between these dates.</td></tr>
                                @endforelse
                            </tbody>
                            @if(count($series))
                            <tfoot>
                                <tr>
                                    <td>Total</td>
                                    <td class="bk-num">{{ collect($series)->sum('count') }}</td>
                                    <td class="bk-num bk-in">{{ number_format($inTotal, 2) }}</td>
                                    <td class="bk-num bk-out">{{ number_format($outTotal, 2) }}</td>
                                    <td class="bk-num {{ $net >= 0 ? 'bk-in' : 'bk-out' }}">{{ number_format($net, 2) }}</td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>

            </div>
        </div>
        @endif
    </div>

    {{-- Add bank --}}
    @if($canSeeAll)
    <div class="modal fade" id="addBankModal" tabindex="-1" aria-labelledby="addBankTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form class="modal-content" method="POST" action="{{ url('banks') }}">
                @csrf
                <input type="hidden" name="user_id" value="{{ auth()->id() }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBankTitle">Add bank</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Bank name</label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="acc_number" class="form-label">Account number</label>
                        <input type="text" name="acc_number" id="acc_number" class="form-control @error('acc_number') is-invalid @enderror" value="{{ old('acc_number') }}" required>
                        @error('acc_number')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="mb-0">
                        <label for="branch" class="form-label">Branch</label>
                        <input type="text" name="branch" id="branch" class="form-control @error('branch') is-invalid @enderror" value="{{ old('branch') }}" required>
                        @error('branch')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark">Add bank</button>
                </div>
            </form>
        </div>
    </div>
    @endif
    @endsection

    @section('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.dataTables.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.html5.min.js"></script>

    @if($canSeeAll)
    <script>
        // ---- Trend chart (ApexCharts is already loaded by the dashboard layout) ----
        const bkSeries = @json($series);
        const bkMoney = v => 'GH₵ ' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        new ApexCharts(document.querySelector('#bkTrend'), {
            chart: { type: 'line', height: 320, toolbar: { show: false }, fontFamily: 'inherit' },
            series: [
                { name: 'Money in',  type: 'column', data: bkSeries.map(r => r.in) },
                { name: 'Money out', type: 'column', data: bkSeries.map(r => r.out) },
                { name: 'Net',       type: 'line',   data: bkSeries.map(r => r.net) },
            ],
            colors: ['#1a7f45', '#b3261e', '#1e293b'],
            stroke: { width: [0, 0, 3], curve: 'straight' },
            plotOptions: { bar: { columnWidth: '55%', borderRadius: 2 } },
            dataLabels: { enabled: false },
            xaxis: { categories: bkSeries.map(r => r.label), labels: { rotate: -45, hideOverlappingLabels: true } },
            yaxis: { labels: { formatter: v => Number(v).toLocaleString() } },
            tooltip: { shared: true, y: { formatter: bkMoney } },
            legend: { position: 'top', horizontalAlign: 'left' },
            noData: { text: 'No ledger entries between these dates.' },
        }).render();

        // ---- Ledger table ----
        const bkTable = new DataTable('#bkLedgerTable', {
            responsive: true,
            order: [[0, 'desc']],
            pageLength: 25,
            layout: {
                topStart: {
                    buttons: [
                        { extend: 'pageLength', text: 'Show', className: 'btn btn-secondary btn-sm' },
                        { extend: 'excelHtml5', text: 'Export Excel', title: 'Bank ledger {{ $from->format("d M Y") }} to {{ $to->format("d M Y") }}', className: 'btn btn-secondary btn-sm', exportOptions: { columns: ':visible' } },
                        { extend: 'pdfHtml5', text: 'Export PDF', title: 'Bank ledger {{ $from->format("d M Y") }} to {{ $to->format("d M Y") }}', orientation: 'landscape', className: 'btn btn-secondary btn-sm', exportOptions: { columns: ':visible' } },
                    ]
                }
            },
            footerCallback: function () {
                const api = this.api();
                const sum = idx => api.column(idx, { search: 'applied' }).nodes().toArray()
                    .reduce((s, td) => s + Number(td.dataset.order || 0), 0);
                document.getElementById('bkFootIn').textContent  = sum(4).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('bkFootOut').textContent = sum(5).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
        });

        // Bank filter (exact match on the Bank column)
        const escapeRe = s => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        document.getElementById('bkBankFilter').addEventListener('change', function () {
            bkTable.column(1).search(this.value ? '^' + escapeRe(this.value) + '$' : '', true, false).draw();
        });

        // Direction filter: keep only rows with an amount in the chosen column
        DataTable.ext.search.push(function (settings, data, i) {
            if (settings.nTable.id !== 'bkLedgerTable') return true;
            const dir = document.getElementById('bkDirFilter').value;
            if (!dir) return true;
            const row = bkTable.row(i).node();
            const cell = row.querySelector('[data-dir="' + dir + '"]');
            return cell && Number(cell.dataset.order) > 0;
        });
        document.getElementById('bkDirFilter').addEventListener('change', () => bkTable.draw());
    </script>
    @endif
    @endsection

</x-sales-dashboard>
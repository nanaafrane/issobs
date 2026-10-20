<x-sales-dashboard>

    @php
        $user = Auth::user();
        $canSeeAll = $user?->hasRole(\App\Http\Controllers\BankController::VIEW_ROLES) ?? false;
        $canCorrect = $user?->hasRole(\App\Http\Controllers\BankController::CORRECT_ROLES) ?? false;
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
                <h3 class="mb-1"><i class="bx bxs-bank me-1"></i> Bank &amp; cash</h3>
                <div class="bk-muted small">Balances, where money came from and where it went, and every ledger entry for the period you choose.</div>
            </div>
            @if($canSeeAll)
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('money.transfers.create') }}" class="btn btn-outline-primary"><i class="icon-base bx bx-transfer"></i> Fund / transfer</a>
                <a href="{{ route('money.payments.create') }}" class="btn btn-primary"><i class="icon-base bx bx-money-withdraw"></i> Make a payment</a>
                <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addBankModal">
                    <i class="icon-base bx bx-plus"></i> Add account
                </button>
            </div>
            @endif
        </div>

        <div class="card-header ml-2 d-none d-lg-block px-0">
            @include('flash-messages')
        </div>

        @if(! $canSeeAll)
            <div class="alert alert-info">Bank and cash balances and ledger entries are visible to Finance only. Your branch deposits are under <strong>Accounts › Bank Deposit</strong>.</div>
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
                <div class="label">Total across {{ $live->count() }} {{ Str::plural('account', $live->count()) }}</div>
                <div class="value lead-value">{{ $money($totalBalance) }}</div>
                <div class="note">In banks <strong>{{ $money($bankBalance) }}</strong> · Cash on hand <strong>{{ $money($cashBalance) }}</strong></div>
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
                <div class="note">From clients and deposits, {{ $from->format('d M') }} – {{ $to->format('d M Y') }}</div>
            </div>
            <div>
                <div class="label">Money out</div>
                <div class="value bk-out">{{ $money($outTotal) }}</div>
                <div class="note">Payments, expenses and charges</div>
            </div>
            <div>
                <div class="label">Net movement</div>
                <div class="value {{ $net >= 0 ? 'bk-in' : 'bk-out' }}">{{ $net >= 0 ? '+' : '−' }}{{ $money(abs($net)) }}</div>
                <div class="note">{{ $net >= 0 ? 'Balances grew' : 'Balances shrank' }} in this period.
                    @if($flow['internal'] > 0) Moves between our own accounts ({{ $money($flow['internal']) }}) are not counted as in or out. @endif</div>
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
                        @forelse($live as $bank)
                            @php
                                $p = $perBank[$bank->id] ?? null;
                                $share = $totalBalance > 0 ? max(0, $bank->total) / $totalBalance * 100 : 0;
                                $off = isset($checks[$bank->id]);
                            @endphp
                            <div class="bk-bank">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="name"><a href="{{ route('banks.show', $bank) }}" class="text-body">{{ $bank->name }}</a>
                                            @if($bank->isCash())<span class="badge bg-label-success ms-1">Cash</span>@endif
                                            @if($bank->is_active === false)<span class="badge bg-label-secondary ms-1">closed</span>@endif
                                        </div>
                                        <div class="meta">
                                            @if($bank->isCash())Held by {{ $bank->custodian ?: '—' }}
                                            @else{{ $bank->branch }}@if($bank->acc_number) · •••• {{ substr($bank->acc_number, -4) }}@endif @endif
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold bk-num">{{ $money($bank->total) }}</div>
                                        @if($off)
                                            <span class="badge bg-label-warning" title="Balance differs from opening balance + ledger ({{ $money($checks[$bank->id]) }}). Review its statement.">Check balance</span>
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
                                            <a class="dropdown-item" href="{{ route('banks.show', $bank) }}"><i class="icon-base bx bx-detail me-1"></i> Statement and details</a>
                                            @if($bank->is_active !== false)
                                            <a class="dropdown-item" href="{{ route('money.transfers.create', ['to' => $bank->id]) }}"><i class="icon-base bx bx-transfer me-1"></i> Fund this account</a>
                                            <a class="dropdown-item" href="{{ route('money.payments.create', ['bank' => $bank->id]) }}"><i class="icon-base bx bx-money-withdraw me-1"></i> Pay from this account</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-center bk-muted">No accounts yet. Use <strong>Add account</strong> to create the first one.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Where money came from and where it went (business money only) --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="bk-panel h-100">
                    <div class="head">Where money came from and where it went <small>{{ $from->format('d M') }} – {{ $to->format('d M Y') }}</small></div>
                    <div class="row g-0">
                        <div class="col-md-6 border-end">
                            <table class="table table-sm bk-table mb-0">
                                <thead><tr><th>Money in</th><th class="bk-num">GH₵</th></tr></thead>
                                <tbody>
                                @forelse($flow['in'] as $r)
                                    <tr><td>{{ $r['label'] }} <span class="bk-muted bk-src">· {{ $r['count'] }}</span></td><td class="bk-num bk-in">{{ number_format($r['amount'], 2) }}</td></tr>
                                @empty
                                    <tr><td colspan="2" class="bk-muted">Nothing came in.</td></tr>
                                @endforelse
                                </tbody>
                                <tfoot><tr><td>Total in</td><td class="bk-num bk-in">{{ number_format($flow['total_in'], 2) }}</td></tr></tfoot>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm bk-table mb-0">
                                <thead><tr><th>Money out</th><th class="bk-num">GH₵</th></tr></thead>
                                <tbody>
                                @forelse($flow['out'] as $r)
                                    <tr><td>{{ $r['label'] }} <span class="bk-muted bk-src">· {{ $r['count'] }}</span></td><td class="bk-num bk-out">{{ number_format($r['amount'], 2) }}</td></tr>
                                @empty
                                    <tr><td colspan="2" class="bk-muted">Nothing went out.</td></tr>
                                @endforelse
                                </tbody>
                                <tfoot><tr><td>Total out</td><td class="bk-num bk-out">{{ number_format($flow['total_out'], 2) }}</td></tr></tfoot>
                            </table>
                        </div>
                    </div>
                    @if($flow['internal'] > 0)
                    <div class="px-3 py-2 border-top small bk-muted">
                        <i class="bx bx-transfer"></i> {{ $flow['internal_count'] }} {{ Str::plural('move', $flow['internal_count']) }} between our own accounts ({{ $money($flow['internal']) }}), e.g. funding Main Cash — not money in or out of the business.
                        <a href="{{ route('money.transfers') }}">See transfers</a>
                    </div>
                    @endif
                </div>
            </div>
            <div class="col-lg-4">
                <div class="bk-panel h-100">
                    <div class="head">Needs attention</div>
                    <div class="p-3 small">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Approved expenses not yet paid <span class="bk-muted">({{ $awaiting->count() }})</span></span>
                            <span class="bk-num fw-semibold">{{ number_format($awaiting->sum('amount'), 2) }}</span>
                        </div>
                        @if($awaiting->isNotEmpty())
                            <a href="{{ route('money.payments.create', ['for' => 'expenses']) }}" class="btn btn-sm btn-outline-primary mb-3">Pay approved expenses</a>
                        @endif
                        <div class="d-flex justify-content-between mb-1">
                            <span>Cheques issued, not yet cleared <span class="bk-muted">({{ $uncleared->count() }})</span></span>
                            <span class="bk-num fw-semibold">{{ number_format($uncleared->sum('amount'), 2) }}</span>
                        </div>
                        <div class="bk-muted mb-3">Your bank statement shows this much more than the books until these cheques are presented.
                            @if($uncleared->isNotEmpty())<a href="{{ route('money.payments', ['status' => 'issued']) }}">List</a>@endif</div>
                        @if($checks)
                            <div class="alert alert-warning py-2 mb-0">{{ count($checks) }} {{ Str::plural('account', count($checks)) }} with a balance that does not match its ledger — open its statement.</div>
                        @else
                            <div class="bk-muted"><i class="bx bx-check-circle text-success"></i> Every balance matches its opening balance plus its ledger.</div>
                        @endif
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
                            <label class="form-label small mb-0" for="bkBankFilter">Account</label>
                            <select id="bkBankFilter" class="form-select form-select-sm">
                                <option value="">All accounts</option>
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
                                    <th>Account</th>
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
                                    <td data-order="{{ $t->entry_date?->format('Ymd') }}{{ str_pad($t->id, 9, '0', STR_PAD_LEFT) }}">
                                        {{ $t->entry_date?->format('d M Y') }}
                                        <div class="bk-muted bk-src">recorded {{ $t->created_at?->format('d M H:i') }}</div>
                                    </td>
                                    <td>{{ $t->bank?->name }}</td>
                                    <td>
                                        @include('banks.partials.channel_badge', ['t' => $t])
                                        {{ $t->narration }}
                                    </td>
                                    <td class="bk-src">@include('banks.partials.ledger_source', ['t' => $t])</td>
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
                                <tr><td colspan="5" class="text-center bk-muted py-4">No money in or out between these dates (moves between our own accounts are left out here).</td></tr>
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

    {{-- Add account --}}
    @if($canSeeAll)
    <div class="modal fade" id="addBankModal" tabindex="-1" aria-labelledby="addBankTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form class="modal-content" method="POST" action="{{ route('banks.store') }}">
                @csrf
                {!! \App\Support\SubmissionGuard::field() !!}
                <div class="modal-header">
                    <h5 class="modal-title" id="addBankTitle">Add account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @php $kindOld = old('kind', 'bank'); @endphp
                    <div class="btn-group w-100 mb-3" role="group" aria-label="Kind of account">
                        <input type="radio" class="btn-check" name="kind" id="kindBank" value="bank" @checked($kindOld === 'bank')>
                        <label class="btn btn-outline-primary" for="kindBank"><i class="bx bxs-bank"></i> Bank account</label>
                        <input type="radio" class="btn-check" name="kind" id="kindCash" value="cash" @checked($kindOld === 'cash')>
                        <label class="btn btn-outline-success" for="kindCash"><i class="bx bx-wallet"></i> Cash on hand</label>
                    </div>
                    <p class="small bk-muted" data-kind="cash">Cash held at the office (e.g. <strong>Main Cash</strong>), funded by cheques or withdrawals from the banks and used to pay approved expenses. It has its own balance and statement like a bank.</p>
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. GCB Operations / Main Cash" required>
                        @error('name')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="row g-2 mb-3" data-kind="bank">
                        <div class="col-6">
                            <label for="acc_number" class="form-label">Account number</label>
                            <input type="text" name="acc_number" id="acc_number" class="form-control @error('acc_number') is-invalid @enderror" value="{{ old('acc_number') }}">
                            @error('acc_number')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                        </div>
                        <div class="col-6">
                            <label for="branch" class="form-label">Branch</label>
                            <input type="text" name="branch" id="branch" class="form-control @error('branch') is-invalid @enderror" value="{{ old('branch') }}">
                            @error('branch')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                        </div>
                    </div>
                    <div class="mb-3" data-kind="cash">
                        <label for="custodian" class="form-label">Held by (custodian)</label>
                        <input type="text" name="custodian" id="custodian" class="form-control @error('custodian') is-invalid @enderror" value="{{ old('custodian') }}" placeholder="who keeps the cash">
                        @error('custodian')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label for="opening_balance" class="form-label">Opening balance (GH₵)</label>
                            <input type="number" step="0.01" name="opening_balance" id="opening_balance" class="form-control @error('opening_balance') is-invalid @enderror" value="{{ old('opening_balance', '0.00') }}">
                            @error('opening_balance')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                        </div>
                        <div class="col-6">
                            <label for="opening_date" class="form-label">As at</label>
                            <input type="date" name="opening_date" id="opening_date" class="form-control" value="{{ old('opening_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}">
                        </div>
                    </div>
                    <div class="form-text">The balance on the bank statement (or cash counted) on that date. After this, the balance only changes through deposits, receipts, transfers, payments and adjustments.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark" data-loading>Add account</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    (function () {
        function show() {
            var kind = (document.querySelector('input[name="kind"]:checked') || {}).value || 'bank';
            document.querySelectorAll('#addBankModal [data-kind]').forEach(function (el) { el.style.display = el.dataset.kind === kind ? '' : 'none'; });
        }
        document.querySelectorAll('input[name="kind"]').forEach(function (r) { r.addEventListener('change', show); });
        show();
    })();
    </script>
    @if($errors->any() && old('kind'))
    <script>document.addEventListener('DOMContentLoaded', function () { if (window.bootstrap) new bootstrap.Modal(document.getElementById('addBankModal')).show(); });</script>
    @endif
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
<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">
    <style>
        .cr-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        .cr-band { display: grid; grid-template-columns: repeat(5, 1fr); background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; }
        .cr-band > div { padding: .9rem 1.1rem; border-right: 1px solid #e5e7eb; }
        .cr-band > div:last-child { border-right: 0; }
        .cr-band .label { font-size: .78rem; color: #6b7280; }
        .cr-band .value { font-size: 1.25rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        @media (max-width: 991px) { .cr-band { grid-template-columns: 1fr 1fr; } .cr-band > div { border-bottom: 1px solid #e5e7eb; } }
        tr.cr-edge td { background: #f8f9fb; font-weight: 600; }
        tr.cr-rev td { color: #8592a3; }
        :target td { background: rgba(105, 108, 255, .1) !important; }
        /* All three tabs stay visible on a phone (they wrap instead of running off the screen). */
        .cr-tabs { flex-wrap: wrap; }
        .cr-tabs .nav-link { white-space: nowrap; }
        @media (max-width: 575px) { .cr-tabs .nav-link { padding: .5rem .75rem; } }
        @media print { #layout-menu, .layout-navbar, .no-print, footer, .dt-buttons { display: none !important; } }
    </style>
    @endsection

    @section('content')
    @php
        $m = fn ($v) => number_format((float) $v, 2);
        $kindBadge = ['received' => 'bg-label-success', 'on_behalf' => 'bg-label-info', 'repaid' => 'bg-label-warning', 'reversal' => 'bg-label-secondary'];
        $tab = old('_tab', $errors->has('expense_ids') || $errors->has('payee') ? 'behalf' : 'receive');
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <a href="{{ route('creditors.index') }}" class="small no-print"><i class="bx bx-arrow-back"></i> Creditors</a>
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mt-1 mb-3">
            <div>
                <h3 class="mb-1">{{ $creditor->name }} <span class="badge bg-label-dark align-middle fs-6">{{ $creditor->kind_label }}</span></h3>
                <div class="text-muted small">@if($creditor->phone){{ $creditor->phone }} · @endif @if($creditor->email){{ $creditor->email }} · @endif Statement {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}@if($creditor->notes) · {{ $creditor->notes }}@endif</div>
            </div>
            <div class="text-end">
                <div class="small text-muted">The company owes now</div>
                <div class="fs-3 fw-bold">GH₵ {{ $m($creditor->balance) }}</div>
            </div>
        </div>

        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

        <form method="GET" class="d-flex flex-wrap align-items-end gap-2 mb-3 no-print">
            <div><label class="form-label small mb-0">From</label><input type="date" name="from" class="form-control form-control-sm" value="{{ $from->toDateString() }}"></div>
            <div><label class="form-label small mb-0">To</label><input type="date" name="to" class="form-control form-control-sm" value="{{ $to->toDateString() }}"></div>
            <button class="btn btn-sm btn-primary">Show</button>
        </form>

        <div class="cr-band mb-3">
            <div><div class="label">Owed {{ $from->format('d M Y') }}</div><div class="value">{{ $m($st['opening']) }}</div></div>
            <div><div class="label">Received from them</div><div class="value">{{ $m($st['received']) }}</div></div>
            <div><div class="label">Paid on our behalf</div><div class="value">{{ $m($st['on_behalf']) }}</div></div>
            <div><div class="label">Repaid</div><div class="value">{{ $m($st['repaid']) }}</div></div>
            <div><div class="label">Owed {{ $to->format('d M Y') }}</div><div class="value">{{ $m($st['closing']) }}</div></div>
        </div>

        @if($creditor->is_active !== false)
        {{-- Sneat tabs: nav-align-top wraps the tab list and the panes (card-header-tabs does not fit this template). --}}
        <div class="nav-align-top mb-3 no-print">
            <ul class="nav nav-tabs cr-tabs" role="tablist">
                <li class="nav-item"><button class="nav-link {{ $tab === 'receive' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tReceive" type="button" role="tab"><i class="bx bx-download me-1"></i> Money received</button></li>
                <li class="nav-item"><button class="nav-link {{ $tab === 'behalf' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tBehalf" type="button" role="tab"><i class="bx bx-receipt me-1"></i> Paid on our behalf</button></li>
                <li class="nav-item"><button class="nav-link {{ $tab === 'repay' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tRepay" type="button" role="tab"><i class="bx bx-upload me-1"></i> Repay</button></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade {{ $tab === 'receive' ? 'show active' : '' }}" id="tReceive">
                    <p class="small text-muted">{{ $creditor->name }} put money into a company account or Main Cash. The account gets money in (marked <em>Funding, to repay</em>) and the company owes them more.</p>
                    <form method="POST" action="{{ route('creditors.receive', $creditor) }}" class="row g-2" onsubmit="return confirm('Record money received from {{ addslashes($creditor->name) }}?');">
                        @csrf {!! \App\Support\SubmissionGuard::field() !!}
                        <input type="hidden" name="_tab" value="receive">
                        @include('creditors.partials.money_fields', ['prefix' => 'rc', 'label' => 'Into'])
                        <div class="col-12"><button class="btn btn-success" data-loading>Record money received</button></div>
                    </form>
                </div>

                <div class="tab-pane fade {{ $tab === 'behalf' ? 'show active' : '' }}" id="tBehalf">
                    <p class="small text-muted">{{ $creditor->name }} paid something for the company from their own money. No company account moves; the company owes them more. Tick approved expenses they paid (they show as paid), or enter who they paid.</p>
                    <form method="POST" action="{{ route('creditors.onBehalf', $creditor) }}" class="row g-2" onsubmit="return confirm('Record a payment made on the company\'s behalf?');">
                        @csrf {!! \App\Support\SubmissionGuard::field() !!}
                        <input type="hidden" name="_tab" value="behalf">
                        @if($awaiting->isNotEmpty())
                        <div class="col-12">
                            <div class="table-responsive border rounded" style="max-height: 14rem; overflow-y: auto;">
                                <table class="table table-sm mb-0">
                                    <thead><tr><th></th><th>#</th><th>Date</th><th>Office</th><th>Type</th><th>Description</th><th class="cr-num">Amount</th></tr></thead>
                                    <tbody>
                                    @foreach($awaiting as $e)
                                        <tr><td><input type="checkbox" class="form-check-input" name="expense_ids[]" value="{{ $e->id }}" @checked(in_array($e->id, (array) old('expense_ids', [])))></td>
                                            <td>{{ $e->id }}</td><td>{{ $e->expense_date?->format('d M Y') }}</td><td>{{ $e->field?->name }}</td><td>{{ $e->type?->name }}</td><td class="small">{{ \Illuminate\Support\Str::limit($e->description, 50) }}</td><td class="cr-num">{{ $m($e->amount) }}</td></tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="small text-muted mt-1">With expenses ticked, the amount is their total.</div>
                        </div>
                        @endif
                        <div class="col-md-4"><label class="form-label small mb-0">Paid to</label><input name="payee" class="form-control" maxlength="150" value="{{ old('payee') }}" placeholder="supplier / who was paid"></div>
                        <div class="col-md-2"><label class="form-label small mb-0">Amount (GH₵)</label><input type="number" step="0.01" min="0" name="amount" class="form-control text-end" value="{{ old('amount') }}"></div>
                        <div class="col-md-2"><label class="form-label small mb-0">Date</label><input type="date" name="entry_date" class="form-control" value="{{ old('entry_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required></div>
                        <div class="col-md-2"><label class="form-label small mb-0">Ref / receipt no.</label><input name="reference" class="form-control" maxlength="60" value="{{ old('reference') }}"></div>
                        <div class="col-md-2"><label class="form-label small mb-0">For</label><input name="description" class="form-control" maxlength="300" value="{{ old('description') }}"></div>
                        <div class="col-12"><button class="btn btn-info" data-loading>Record payment on our behalf</button></div>
                    </form>
                </div>

                <div class="tab-pane fade {{ $tab === 'repay' ? 'show active' : '' }}" id="tRepay">
                    <p class="small text-muted">Pay {{ $creditor->name }} back from a bank or Main Cash (at most GH₵ {{ $m($creditor->balance) }}). Interest, if any, goes in its own box: it is recorded as a payment (<em>Interest on loans</em>) because interest is a cost, the repayment is not.</p>
                    <form method="POST" action="{{ route('creditors.repay', $creditor) }}" class="row g-2" onsubmit="return confirm('Record repayment to {{ addslashes($creditor->name) }}?');">
                        @csrf {!! \App\Support\SubmissionGuard::field() !!}
                        <input type="hidden" name="_tab" value="repay">
                        @include('creditors.partials.money_fields', ['prefix' => 'rp', 'label' => 'From', 'max' => (float) $creditor->balance])
                        <div class="col-md-2"><label class="form-label small mb-0">+ interest (GH₵)</label><input type="number" step="0.01" min="0" name="interest" class="form-control text-end" value="{{ old('interest') }}" placeholder="0.00"></div>
                        <div class="col-12"><button class="btn btn-warning" data-loading @disabled((float) $creditor->balance <= 0)>Record repayment</button></div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        <div class="card mb-3">
            <div class="table-responsive p-2">
                <table class="table table-sm mb-0 w-100" id="crTable">
                    <thead><tr><th>Date</th><th>What</th><th>Account</th><th>Details</th><th class="cr-num">Company owes more</th><th class="cr-num">Owes less</th><th class="cr-num">Owed</th><th class="no-print"></th></tr></thead>
                    <tbody>
                        <tr class="cr-edge"><td>{{ $from->format('d M Y') }}</td><td>Balance owed</td><td></td><td></td><td></td><td></td><td class="cr-num">{{ $m($st['opening']) }}</td><td class="no-print"></td></tr>
                        @foreach($st['lines'] as $e)
                        <tr id="entry-{{ $e->id }}" class="{{ $e->reversed_at || $e->kind === 'reversal' ? 'cr-rev' : '' }}">
                            <td class="text-nowrap">{{ $e->entry_date->format('d M Y') }}</td>
                            <td><span class="badge {{ $kindBadge[$e->kind] ?? 'bg-label-secondary' }}">{{ \App\Models\CreditorEntry::KINDS[$e->kind] ?? $e->kind }}</span>@if($e->reversed_at) <span class="badge bg-label-secondary">reversed</span>@endif</td>
                            <td>@if($e->bank)<a href="{{ route('banks.show', $e->bank_id) }}">{{ $e->bank->name }}</a>@endif</td>
                            <td class="small">{{ collect([$e->description, $e->reference ? ($e->method === 'cheque' ? 'cheque ' : 'ref ') . $e->reference : null])->filter()->implode(' · ') }}
                                @if($e->interest) · interest {{ $m($e->interest) }} (<a href="{{ route('money.payments.show', $e->interest_payment_id) }}">payment</a>)@endif
                                @if($e->reversal_reason)<div class="text-muted">Reversed: {{ $e->reversal_reason }}</div>@endif</td>
                            <td class="cr-num">{{ $e->owed_change > 0 ? $m($e->owed_change) : '' }}</td>
                            <td class="cr-num">{{ $e->owed_change < 0 ? $m(-$e->owed_change) : '' }}</td>
                            <td class="cr-num fw-semibold">{{ $m($e->running) }}</td>
                            <td class="no-print text-end">
                                @if($canCorrect && ! $e->reversed_at && $e->kind !== 'reversal')
                                <form method="POST" action="{{ route('creditors.reverse', $e) }}" class="d-inline-flex gap-1" onsubmit="this.reversal_reason.value = prompt('Reason for reversing this entry?') || ''; return this.reversal_reason.value.length >= 5;">
                                    @csrf {!! \App\Support\SubmissionGuard::field() !!}
                                    <input type="hidden" name="reversal_reason">
                                    <button class="btn btn-sm btn-outline-danger py-0">Reverse</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        <tr class="cr-edge"><td>{{ $to->format('d M Y') }}</td><td>Balance owed</td><td></td><td></td><td class="cr-num">{{ $m($st['received'] + $st['on_behalf'] + max(0, $st['reversals'])) }}</td><td class="cr-num">{{ $m($st['repaid'] + max(0, -$st['reversals'])) }}</td><td class="cr-num">{{ $m($st['closing']) }}</td><td class="no-print"></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <details class="card no-print"><summary class="card-body py-2">Creditor details</summary>
            <div class="card-body pt-0">
                <form method="POST" action="{{ route('creditors.update', $creditor) }}" class="row g-2">
                    @csrf @method('PUT')
                    <div class="col-md-4"><label class="form-label small mb-0">Name</label><input name="name" class="form-control form-control-sm" value="{{ $creditor->name }}" required></div>
                    <div class="col-md-2"><label class="form-label small mb-0">Type</label><select name="kind" class="form-select form-select-sm">@foreach(\App\Models\Creditor::KINDS as $k => $v)<option value="{{ $k }}" @selected($creditor->kind === $k)>{{ $v }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label class="form-label small mb-0">Phone</label><input name="phone" class="form-control form-control-sm" value="{{ $creditor->phone }}"></div>
                    <div class="col-md-4"><label class="form-label small mb-0">Email</label><input type="email" name="email" class="form-control form-control-sm" value="{{ $creditor->email }}"></div>
                    <div class="col-md-10"><label class="form-label small mb-0">Notes / terms</label><input name="notes" class="form-control form-control-sm" value="{{ $creditor->notes }}"></div>
                    <div class="col-md-2 d-grid align-items-end"><button class="btn btn-sm btn-outline-dark mt-3">Save</button></div>
                    <div class="col-12 small text-muted">Opening balance owed: GH₵ {{ $m($creditor->opening_balance) }} on {{ $creditor->opening_date?->format('d M Y') }}. The balance only changes through the entries above.</div>
                </form>
            </div>
        </details>
    </div>
    @endsection

    @section('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.dataTables.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.html5.min.js"></script>
    <script>
        if (window.DataTable) try {
            new DataTable('#crTable', { ordering: false, paging: false, info: false, searching: false,
                layout: { topStart: { buttons: [
                    { extend: 'excelHtml5', text: 'Export to Excel', className: 'btn btn-secondary btn-sm', title: @json($creditor->name . ' statement ' . $from->format('Y-m-d') . ' to ' . $to->format('Y-m-d')), exportOptions: { columns: [0,1,2,3,4,5,6] } },
                    { text: 'Print', className: 'btn btn-outline-secondary btn-sm', action: function () { window.print(); } }
                ] } } });
        } catch (e) { console.warn(e); }
        document.querySelectorAll('[data-money-form]').forEach(function (box) {
            var bank = box.querySelector('select[name="bank_id"]'), method = box.querySelector('select[name="method"]');
            function sync() {
                var opt = bank.options[bank.selectedIndex], cash = opt && opt.dataset.cash === '1';
                method.closest('div').style.display = cash ? 'none' : '';
            }
            bank.addEventListener('change', sync); sync();
        });
    </script>
    @endsection
</x-sales-dashboard>

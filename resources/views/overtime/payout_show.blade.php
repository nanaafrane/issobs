<x-sales-dashboard>

    @section('side_nav')
        @include('partials.payroll_side_nav')
    @endsection

    @section('css')
    <style>
        .op-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        .op-band { display: grid; grid-template-columns: repeat(4, 1fr); background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; }
        .op-band > div { padding: .9rem 1.2rem; border-right: 1px solid #e5e7eb; }
        .op-band > div:last-child { border-right: 0; }
        .op-band .label { font-size: .8rem; color: #6b7280; }
        .op-band .value { font-size: 1.3rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        @media (max-width: 767px) { .op-band { grid-template-columns: 1fr 1fr; } }
        tr.op-paid td { background: rgba(113, 221, 55, .07); }
    </style>
    @endsection

    @section('content')
    @php
        $p = $payout;
        $paid = $entries->whereNotNull('disbursed_at');
        $unpaid = $entries->whereNull('disbursed_at');
        $open = $p->status === 'open';
        $isMomo = $p->method === 'momo';
        $badge = ['open' => 'bg-label-warning', 'closed' => 'bg-label-success', 'cancelled' => 'bg-label-secondary'][$p->status] ?? 'bg-label-secondary';
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <a href="{{ route('overtime.payouts') }}" class="small"><i class="bx bx-arrow-back"></i> Overtime pay-outs</a>
        <h3 class="text-primary mt-1 mb-1">{{ $p->label() }} <span class="badge {{ $badge }} align-middle fs-6">{{ $p->status }}</span></h3>
        <div class="text-muted small mb-3">
            {{ \App\Models\OvertimePayout::METHODS[$p->method] ?? $p->method }}
            @if($p->method === 'payroll') · {{ $p->payroll_month?->format('F Y') }} salaries @endif
            @if($p->held_by) · cash given to <strong>{{ $p->held_by }}</strong> @endif
            @if($p->payment) · paid from <a href="{{ route('banks.show', $p->bank_id) }}">{{ $p->bank?->name }}</a> (<a href="{{ route('money.payments.show', $p->payment_id) }}">payment #{{ $p->payment_id }}</a>) @endif
            · raised by {{ $p->user?->name }} {{ $p->created_at?->format('d M Y H:i') }}
        </div>

        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        @if($p->status === 'cancelled')<div class="alert alert-secondary">Cancelled {{ $p->cancelled_at?->format('d M Y H:i') }}: {{ $p->cancel_reason }}</div>@endif

        <div class="op-band mb-3">
            <div><div class="label">Pay-out total</div><div class="value">{{ number_format($p->total, 2) }}</div></div>
            <div><div class="label">Paid to guards ({{ $paid->count() }})</div><div class="value text-success">{{ number_format($paid->sum('amount'), 2) }}</div></div>
            <div><div class="label">{{ $p->method === 'cash' ? 'Still held by the officer' : 'Still to pay' }} ({{ $unpaid->count() }})</div><div class="value {{ $unpaid->isNotEmpty() && $open ? 'text-danger' : '' }}">{{ number_format($unpaid->sum('amount'), 2) }}</div></div>
            <div><div class="label">Returned (not paid out)</div><div class="value">{{ number_format($p->returned, 2) }}</div></div>
        </div>

        <form method="POST" action="{{ route('overtime.payouts.disburse', $p) }}" id="disForm">
            @csrf
            {!! \App\Support\SubmissionGuard::field() !!}
            <div class="card mb-3">
                <div class="card-header py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 class="mb-0">Guards on this pay-out</h6>
                    @if($open && $unpaid->isNotEmpty() && $p->method !== 'payroll')
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <label class="small mb-0"><input type="checkbox" class="form-check-input me-1" id="disAll"> tick all unpaid</label>
                        <input type="date" name="disbursed_at" class="form-control form-control-sm" style="width: 10rem" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" title="Date paid">
                        <button class="btn btn-sm btn-success" id="disBtn" data-loading disabled>Mark ticked as paid</button>
                    </div>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th></th><th>Date</th><th>Shift</th><th>Guard</th><th>Phone</th><th>Client</th><th class="op-num">Amount</th><th>{{ $isMomo ? 'MoMo transaction ID' : 'Paid' }}</th></tr></thead>
                        <tbody>
                        @forelse($entries as $o)
                            <tr class="{{ $o->disbursed_at ? 'op-paid' : '' }}">
                                <td>@if(! $o->disbursed_at && $open)<input type="checkbox" class="form-check-input dis-pick" name="overtime_ids[]" value="{{ $o->id }}" data-amount="{{ (float) $o->amount }}">@endif</td>
                                <td class="text-nowrap">{{ $o->entry_date->format('D d M') }}</td>
                                <td>{{ ucfirst($o->shift) }}</td>
                                <td>{{ $o->otEmployee?->name ?? '—' }}</td>
                                <td class="small">{{ $o->phone_number }}</td>
                                <td class="small">{{ $o->clientLabel() }}</td>
                                <td class="op-num">{{ number_format($o->amount, 2) }}</td>
                                <td class="small">
                                    @if($o->disbursed_at)
                                        <i class="bx bx-check text-success"></i> {{ $o->disbursed_at->format('d M Y') }} · {{ $o->disbursed_ref }}
                                    @elseif($open && $isMomo)
                                        <input name="refs[{{ $o->id }}]" class="form-control form-control-sm" maxlength="80" placeholder="transaction ID" style="min-width: 10rem">
                                    @elseif($open)
                                        <span class="text-muted">waiting</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-muted text-center py-3">No entries left on this pay-out.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </form>

        @if($p->method === 'payroll' && $p->status !== 'cancelled')
        <div class="card mb-3"><div class="card-body">
            <h6 class="mb-1">Payroll check · {{ $p->payroll_month?->format('F Y') }} · {{ $p->field?->name }}</h6>
            <p class="small text-muted">The "overtime" on each guard's {{ $p->payroll_month?->format('F') }} salary should equal the overtime sent to that payroll. Differences:</p>
            @if($check->isEmpty())
                <div class="small"><i class="bx bx-check-circle text-success"></i> Every guard's salary overtime matches.</div>
            @else
                <table class="table table-sm mb-0">
                    <thead><tr><th>Guard</th><th class="op-num">Sent to payroll</th><th class="op-num">On the salary</th><th class="op-num">Difference</th></tr></thead>
                    <tbody>@foreach($check as $r)<tr><td>{{ $r['name'] }}</td><td class="op-num">{{ number_format($r['daily'], 2) }}</td><td class="op-num">{{ number_format($r['salary'], 2) }}</td><td class="op-num {{ $r['difference'] > 0 ? 'text-danger' : 'text-warning' }}">{{ ($r['difference'] > 0 ? '+' : '') . number_format($r['difference'], 2) }}</td></tr>@endforeach</tbody>
                </table>
                <div class="small text-muted mt-1">+ means the salary pays more overtime than was approved (possible double pay); − means approved overtime is missing from the salary.</div>
            @endif
        </div></div>
        @endif

        @if($open && $canFund && $unpaid->isNotEmpty() && $p->method !== 'payroll')
        <div class="card mb-3 border-warning"><div class="card-body">
            <h6 class="mb-1">Return money not paid out</h6>
            <p class="small text-muted mb-2">For guards who did not collect, or a MoMo that failed: tick them in the list above, choose where the money goes back, and give the reason. The entries become owed again and can go on the next pay-out.</p>
            <form method="POST" action="{{ route('overtime.payouts.giveBack', $p) }}" class="row g-2" id="backForm" onsubmit="return confirm('Return the money for the ticked entries?');">
                @csrf
                {!! \App\Support\SubmissionGuard::field() !!}
                <div id="backIds"></div>
                <div class="col-md-4"><select name="bank_id" class="form-select form-select-sm" required>
                    @foreach($accounts as $a)<option value="{{ $a->id }}" @selected($a->id === $p->bank_id)>{{ $a->name }}</option>@endforeach
                </select></div>
                <div class="col-md-5"><input name="reason" class="form-control form-control-sm" maxlength="300" placeholder="Reason, e.g. guard absent on pay day" required></div>
                <div class="col-md-3"><button class="btn btn-sm btn-warning w-100" id="backBtn" data-loading disabled>Return ticked</button></div>
            </form>
        </div></div>
        @endif

        @if($canCorrect && $p->status !== 'cancelled' && ($p->method === 'payroll' || ($paid->isEmpty() && (float) $p->returned == 0)))
        <div class="card border-danger"><div class="card-body">
            <h6 class="text-danger mb-1">Cancel this pay-out</h6>
            <p class="small text-muted mb-2">Entered in error. {{ $p->payment_id ? 'The payment is reversed (the money goes back on ' . $p->bank?->name . '), the expense removed, and' : 'The' }} entries become owed again.</p>
            <form method="POST" action="{{ route('overtime.payouts.cancel', $p) }}" class="d-flex flex-wrap gap-2" onsubmit="return confirm('Cancel this pay-out?');">
                @csrf
                {!! \App\Support\SubmissionGuard::field() !!}
                <input name="cancel_reason" class="form-control form-control-sm" style="max-width: 28rem" maxlength="300" placeholder="Reason" required>
                <button class="btn btn-sm btn-danger" data-loading>Cancel pay-out</button>
            </form>
        </div></div>
        @endif
    </div>
    @endsection

    @section('scripts')
    <script>
    (function () {
        var form = document.getElementById('disForm');
        if (!form) return;
        function update() {
            var picked = Array.prototype.filter.call(form.querySelectorAll('.dis-pick'), function (c) { return c.checked; });
            var total = picked.reduce(function (s, c) { return s + parseFloat(c.dataset.amount); }, 0);
            var label = picked.length ? ' (' + picked.length + ' · GH₵ ' + total.toFixed(2) + ')' : '';
            var dis = document.getElementById('disBtn');
            if (dis && form.dataset.submitting !== '1') { dis.disabled = !picked.length; dis.textContent = 'Mark ticked as paid' + label; }
            var back = document.getElementById('backBtn');
            if (back) {
                back.disabled = !picked.length;
                back.textContent = 'Return ticked' + label;
                document.getElementById('backIds').innerHTML = picked.map(function (c) { return '<input type="hidden" name="overtime_ids[]" value="' + c.value + '">'; }).join('');
            }
        }
        var all = document.getElementById('disAll');
        if (all) all.addEventListener('change', function () { form.querySelectorAll('.dis-pick').forEach(function (c) { c.checked = all.checked; }); update(); });
        form.addEventListener('change', update);
        update();
    })();
    </script>
    @endsection
</x-sales-dashboard>

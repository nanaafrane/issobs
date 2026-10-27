<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <style>
        .op-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        .op-total { font-size: 1.4rem; font-weight: 700; }
        tr.op-off td:not(:first-child) { opacity: .45; }
        .op-method { border: 1px solid #e5e7eb; border-radius: .5rem; padding: .75rem 1rem; }
    </style>
    @endsection

    @section('content')
    @php
        $methodSel = old('method', 'cash');
        $oldIds = old('_token') ? array_map('intval', (array) old('overtime_ids', [])) : $entries->pluck('id')->all();
        $field = $fields->firstWhere('id', $fieldId);
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <a href="{{ route('overtime.payouts') }}" class="small"><i class="bx bx-arrow-back"></i> Overtime pay-outs</a>
        <h3 class="text-primary mt-1 mb-1"><i class="bx bx-money-withdraw me-1"></i> New overtime pay-out</h3>
        <p class="text-muted small mb-3">Choose the office and dates, tick the approved overtime to pay, and how it is paid. The bank or cash account shows <strong>one</strong> payment for the whole pay-out.</p>

        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

        <form method="GET" class="card mb-3"><div class="card-body row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label small mb-0" for="field">Office</label>
                <select name="field" id="field" class="form-select" required>
                    <option value="">Choose…</option>
                    @foreach($fields as $f)<option value="{{ $f->id }}" @selected($fieldId === $f->id)>{{ $f->name }}</option>@endforeach
                </select></div>
            <div class="col-md-3"><label class="form-label small mb-0" for="from">From</label><input type="date" name="from" id="from" class="form-control" value="{{ $from->toDateString() }}"></div>
            <div class="col-md-3"><label class="form-label small mb-0" for="to">To</label><input type="date" name="to" id="to" class="form-control" value="{{ $to->toDateString() }}" max="{{ now()->toDateString() }}"></div>
            <div class="col-md-2 d-grid"><button class="btn btn-outline-primary">Show overtime</button></div>
        </div></form>

        @if($fieldId)
        <form method="POST" action="{{ route('overtime.payouts.store') }}" id="poForm" autocomplete="off">
            @csrf
            {!! \App\Support\SubmissionGuard::field() !!}
            <input type="hidden" name="field_id" value="{{ $fieldId }}">
            <input type="hidden" name="period_from" value="{{ $from->toDateString() }}">
            <input type="hidden" name="period_to" value="{{ $to->toDateString() }}">
            <div class="row g-3">
                <div class="col-xl-8">
                    <div class="card mb-3">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Approved overtime owed · {{ $field?->name }} · {{ $from->format('d M') }} – {{ $to->format('d M Y') }}</h6>
                            @if($entries->isNotEmpty())<label class="small mb-0"><input type="checkbox" class="form-check-input me-1" id="poAll" checked> all</label>@endif
                        </div>
                        <div class="table-responsive" style="max-height: 30rem; overflow-y: auto;">
                            <table class="table table-sm mb-0">
                                <thead><tr><th></th><th>Date</th><th>Shift</th><th>Guard</th><th>Phone</th><th>Covering</th><th>Client</th><th class="op-num">Amount</th></tr></thead>
                                <tbody>
                                @forelse($entries as $o)
                                    <tr class="{{ in_array($o->id, $oldIds, true) ? '' : 'op-off' }}">
                                        <td><input type="checkbox" class="form-check-input po-pick" name="overtime_ids[]" value="{{ $o->id }}" data-amount="{{ (float) $o->amount }}" data-guard="{{ $o->ot_employee_id }}" @checked(in_array($o->id, $oldIds, true))></td>
                                        <td class="text-nowrap">{{ $o->entry_date->format('D d M') }}</td>
                                        <td>{{ ucfirst($o->shift) }}</td>
                                        <td>{{ $o->otEmployee?->name ?? '—' }}</td>
                                        <td class="small">{{ $o->phone_number }}</td>
                                        <td class="small">{{ $o->absentLabel() }}</td>
                                        <td class="small">{{ $o->clientLabel() }}</td>
                                        <td class="op-num">{{ number_format($o->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-muted text-center py-3">No approved overtime is owed for {{ $field?->name }} between these dates.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($entries->isNotEmpty())
                    <div class="card"><div class="card-body">
                        <h6>How is it paid?</h6>
                        @foreach(\App\Models\OvertimePayout::METHODS as $k => $label)
                            <div class="form-check"><input class="form-check-input po-method" type="radio" name="method" id="m_{{ $k }}" value="{{ $k }}" @checked($methodSel === $k)><label class="form-check-label" for="m_{{ $k }}">{{ $label }}</label></div>
                        @endforeach

                        <div class="op-method mt-3" data-for="cash momo">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small mb-0" for="bank_id">Paid from <span class="text-danger">*</span></label>
                                    <select name="bank_id" id="bank_id" class="form-select">
                                        <option value="">Choose…</option>
                                        @foreach($accounts as $a)
                                            <option value="{{ $a->id }}" data-cash="{{ $a->isCash() ? 1 : 0 }}" data-balance="{{ (float) $a->total }}" @selected((string) old('bank_id') === (string) $a->id)>{{ $a->label }} · {{ number_format($a->total, 2) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3" id="payMethodBox">
                                    <label class="form-label small mb-0" for="pay_method">By</label>
                                    <select name="pay_method" id="pay_method" class="form-select">
                                        <option value="cheque" @selected(old('pay_method') === 'cheque')>Cheque</option>
                                        <option value="transfer" @selected(old('pay_method') === 'transfer')>Transfer</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-0" for="reference">Cheque / ref no.</label>
                                    <input name="reference" id="reference" class="form-control" maxlength="60" value="{{ old('reference') }}">
                                </div>
                                <div class="col-md-6" data-for="cash">
                                    <label class="form-label small mb-0" for="held_by">Cash given to (officer) <span class="text-danger">*</span></label>
                                    <input name="held_by" id="held_by" class="form-control" maxlength="120" value="{{ old('held_by') }}" placeholder="who pays the guards">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-0" for="payment_date">Date</label>
                                    <input type="date" name="payment_date" id="payment_date" class="form-control" value="{{ old('payment_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}">
                                </div>
                            </div>
                            <div class="small text-muted mt-2" data-for="momo">Send each guard's MoMo, then tick them paid on the pay-out page with each transaction ID.</div>
                        </div>

                        <div class="op-method mt-3" data-for="payroll">
                            <label class="form-label small mb-0" for="payroll_month">Paid with the salaries of <span class="text-danger">*</span></label>
                            <input type="month" name="payroll_month" id="payroll_month" class="form-control" style="max-width: 14rem" value="{{ old('payroll_month', now()->format('Y-m')) }}">
                            <div class="small text-muted mt-2">No money moves now. Each guard's total goes on that month's payroll; the payroll check shows any guard whose salary overtime does not match.</div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label small mb-0" for="notes">Notes</label>
                            <input name="notes" id="notes" class="form-control" maxlength="500" value="{{ old('notes') }}">
                        </div>
                    </div></div>
                    @endif
                </div>

                @if($entries->isNotEmpty())
                <div class="col-xl-4">
                    <div class="card"><div class="card-body">
                        <div class="text-muted small">Pay-out total</div>
                        <div class="op-total">GH₵ <span id="poTotal">0.00</span></div>
                        <div class="small text-muted" id="poCount"></div>
                        <div class="small mt-2" id="poBalance"></div>
                        <div class="small text-danger mt-1" id="poWarn"></div>
                        <button class="btn btn-primary w-100 mt-3" id="poBtn" data-loading>Create pay-out</button>
                    </div></div>
                </div>
                @endif
            </div>
        </form>
        @endif
    </div>
    @endsection

    @section('scripts')
    <script>
    (function () {
        var form = document.getElementById('poForm');
        if (!form || !document.getElementById('poBtn')) return;
        var bank = document.getElementById('bank_id');
        function money(v) { return (Math.round(v * 100) / 100).toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
        function method() { return (form.querySelector('.po-method:checked') || {}).value || 'cash'; }
        function update() {
            var m = method();
            form.querySelectorAll('[data-for]').forEach(function (el) { el.style.display = el.dataset.for.split(' ').indexOf(m) >= 0 ? '' : 'none'; });
            var opt = bank.options[bank.selectedIndex], isCash = opt && opt.dataset.cash === '1';
            document.getElementById('payMethodBox').style.display = (m === 'cash' && opt && opt.value && !isCash) ? '' : 'none';
            var total = 0, n = 0, guards = {};
            form.querySelectorAll('.po-pick').forEach(function (c) {
                c.closest('tr').classList.toggle('op-off', !c.checked);
                if (c.checked) { total += parseFloat(c.dataset.amount) || 0; n++; guards[c.dataset.guard] = 1; }
            });
            document.getElementById('poTotal').textContent = money(total);
            document.getElementById('poCount').textContent = n + ' entries · ' + Object.keys(guards).length + ' guards';
            var warn = '';
            if (m !== 'payroll' && opt && opt.value) {
                var bal = parseFloat(opt.dataset.balance);
                document.getElementById('poBalance').textContent = opt.text.split(' · ')[0] + ' after: GH₵ ' + money(bal - total);
                if (isCash && total > bal) warn = 'Not enough cash in ' + opt.text.split(' (')[0] + ' — fund it first.';
                if (m === 'momo' && isCash) warn = 'MoMo is sent from a bank account.';
            } else document.getElementById('poBalance').textContent = '';
            document.getElementById('poWarn').textContent = warn;
            var btn = document.getElementById('poBtn');
            if (form.dataset.submitting !== '1') {
                btn.disabled = n === 0 || !!warn || (m !== 'payroll' && !bank.value);
                btn.textContent = n ? (m === 'payroll' ? 'Send GH₵ ' + money(total) + ' to payroll' : 'Pay out GH₵ ' + money(total)) : 'Create pay-out';
            }
        }
        var all = document.getElementById('poAll');
        if (all) all.addEventListener('change', function () { form.querySelectorAll('.po-pick').forEach(function (c) { c.checked = all.checked; }); update(); });
        form.addEventListener('change', update);
        form.addEventListener('submit', function (e) { if (!confirm(document.getElementById('poBtn').textContent + '?')) e.preventDefault(); });
        update();
    })();
    </script>
    @endsection
</x-sales-dashboard>

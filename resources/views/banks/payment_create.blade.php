<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <style>
        .mv-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        .mv-total { font-size: 1.4rem; font-weight: 700; }
        tr.mv-on td { background: rgba(105, 108, 255, .08); }
    </style>
    @endsection

    @section('content')
    @php
        $bankSel = (string) old('bank_id', request('bank'));
        $modeSel = old('mode', $mode);
        $oldExp = array_map('intval', (array) old('expense_ids', $ticked));
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <a href="{{ route('money.payments') }}" class="small"><i class="bx bx-arrow-back"></i> Payments</a>
        <h3 class="text-primary mt-1 mb-1"><i class="bx bx-money-withdraw me-1"></i> Make a payment</h3>
        <p class="text-muted small mb-3">Money paid out to someone else: by cheque, transfer or mobile money from a bank, or in cash from a cash account such as Main Cash.</p>

        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

        <form method="POST" action="{{ route('money.payments.store') }}" id="payForm" autocomplete="off">
            @csrf
            {!! \App\Support\SubmissionGuard::field() !!}
            <div class="btn-group mb-3" role="group">
                <input type="radio" class="btn-check" name="mode" id="modeExp" value="expenses" @checked($modeSel === 'expenses')>
                <label class="btn btn-outline-primary" for="modeExp">Pay approved expenses <span class="badge bg-primary ms-1">{{ $awaiting->count() }}</span></label>
                <input type="radio" class="btn-check" name="mode" id="modeOther" value="other" @checked($modeSel !== 'expenses')>
                <label class="btn btn-outline-primary" for="modeOther">Other payment (supplier, charges, …)</label>
            </div>

            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card mb-3" data-mode="expenses">
                        <div class="card-header d-flex justify-content-between align-items-center py-2">
                            <h6 class="mb-0">Approved expenses waiting to be paid</h6>
                            @if($awaiting->isNotEmpty())<button type="button" class="btn btn-sm btn-outline-secondary" id="expAll">Tick all</button>@endif
                        </div>
                        <div class="table-responsive" style="max-height: 26rem; overflow-y: auto;">
                            <table class="table table-sm mb-0">
                                <thead><tr><th></th><th>#</th><th>Date</th><th>Office</th><th>Type</th><th>Description</th><th class="mv-num">Amount</th></tr></thead>
                                <tbody>
                                @forelse($awaiting as $e)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input exp-check" name="expense_ids[]" value="{{ $e->id }}" data-amount="{{ (float) $e->amount }}" @checked(in_array($e->id, $oldExp, true))></td>
                                        <td>{{ $e->id }}</td>
                                        <td class="text-nowrap">{{ $e->expense_date?->format('d M Y') }}</td>
                                        <td>{{ $e->field?->name }}</td>
                                        <td>{{ $e->type?->name }}</td>
                                        <td class="small">{{ \Illuminate\Support\Str::limit($e->description, 60) }}</td>
                                        <td class="mv-num">{{ number_format($e->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-muted text-center py-3">No approved expenses are waiting to be paid.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card"><div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small mb-0" for="bank_id">Paid from <span class="text-danger">*</span></label>
                                <select name="bank_id" id="bank_id" class="form-select" required>
                                    <option value="">Choose…</option>
                                    @foreach($accounts as $a)
                                        <option value="{{ $a->id }}" data-cash="{{ $a->isCash() ? 1 : 0 }}" data-balance="{{ (float) $a->total }}" @selected($bankSel === (string) $a->id)>{{ $a->label }} · {{ number_format($a->total, 2) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-0" for="method">How <span class="text-danger">*</span></label>
                                <select name="method" id="method" class="form-select" data-old="{{ old('method') }}" required>
                                    @foreach(\App\Models\AccountPayment::METHODS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-0" for="reference">Cheque / ref no.</label>
                                <input name="reference" id="reference" class="form-control" maxlength="60" value="{{ old('reference') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-0" for="payee">Paid to <span class="text-danger">*</span></label>
                                <input name="payee" id="payee" class="form-control" maxlength="150" value="{{ old('payee') }}" placeholder="supplier, person or institution" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-0" for="payment_date">Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" id="payment_date" class="form-control" value="{{ old('payment_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-3" data-mode="other">
                                <label class="form-label small mb-0" for="amount">Amount (GH₵) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="amount" id="amount" class="form-control text-end" value="{{ old('amount') }}">
                            </div>
                            <div class="col-md-6" data-mode="other">
                                <label class="form-label small mb-0" for="category">What for <span class="text-danger">*</span></label>
                                <select name="category" id="category" class="form-select">
                                    @foreach(\App\Models\AccountPayment::CATEGORIES as $k => $v)
                                        @continue($k === 'expenses')
                                        <option value="{{ $k }}" @selected(old('category') === $k)>{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-0" for="description">Description</label>
                                <input name="description" id="description" class="form-control" maxlength="500" value="{{ old('description') }}" placeholder="invoice no., what was bought / paid for">
                            </div>
                        </div>
                    </div></div>
                </div>

                <div class="col-lg-4">
                    <div class="card"><div class="card-body">
                        <div class="text-muted small">Amount paid</div>
                        <div class="mv-total">GH₵ <span id="payTotal">0.00</span></div>
                        <div class="small text-muted" id="payWhat"></div>
                        <hr>
                        <div class="d-flex justify-content-between small"><span>Account now</span><span class="mv-num" id="payNow">—</span></div>
                        <div class="d-flex justify-content-between small fw-semibold"><span>After this payment</span><span class="mv-num" id="payAfter">—</span></div>
                        <div class="small text-muted mt-2" id="payNote"></div>
                        <div class="small text-danger mt-1" id="payWarn"></div>
                        <button type="submit" class="btn btn-primary w-100 mt-3" id="payBtn" data-loading>Record payment</button>
                    </div></div>
                </div>
            </div>
        </form>
    </div>
    @endsection

    @section('scripts')
    <script>
    (function () {
        var form = document.getElementById('payForm');
        var bank = document.getElementById('bank_id'), method = document.getElementById('method');
        var labels = @json(\App\Models\AccountPayment::METHODS);
        function money(v) { return (Math.round(v * 100) / 100).toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
        function mode() { return (form.querySelector('input[name="mode"]:checked') || {}).value || 'other'; }

        function fitMethods() {
            var opt = bank.options[bank.selectedIndex];
            var cash = opt && opt.dataset.cash === '1';
            var allowed = !bank.value ? Object.keys(labels) : (cash ? ['cash'] : ['cheque', 'transfer', 'momo']);
            var keep = method.value || method.dataset.old;
            method.innerHTML = allowed.map(function (k) { return '<option value="' + k + '">' + labels[k] + '</option>'; }).join('');
            if (allowed.indexOf(keep) >= 0) method.value = keep;
            method.dataset.old = '';
            var ref = document.getElementById('reference');
            ref.required = method.value === 'cheque';
            ref.placeholder = method.value === 'cheque' ? 'cheque no. (required)' : 'optional';
        }

        function update() {
            var m = mode();
            form.querySelectorAll('[data-mode]').forEach(function (el) { el.style.display = el.dataset.mode === m ? '' : 'none'; });
            form.querySelectorAll('.exp-check').forEach(function (c) { c.disabled = m !== 'expenses'; c.closest('tr').classList.toggle('mv-on', c.checked && m === 'expenses'); });
            document.getElementById('amount').disabled = m === 'expenses';
            document.getElementById('category').disabled = m === 'expenses';
            fitMethods();

            var total = 0, n = 0;
            if (m === 'expenses') {
                form.querySelectorAll('.exp-check:checked').forEach(function (c) { total += parseFloat(c.dataset.amount) || 0; n++; });
                document.getElementById('payWhat').textContent = n ? n + (n === 1 ? ' expense' : ' expenses') + ' — marked paid when saved' : 'Tick the expenses being paid';
            } else {
                total = parseFloat(document.getElementById('amount').value) || 0;
                var cat = document.getElementById('category');
                document.getElementById('payWhat').textContent = cat.options[cat.selectedIndex] ? cat.options[cat.selectedIndex].text : '';
            }
            document.getElementById('payTotal').textContent = money(total);

            var opt = bank.options[bank.selectedIndex], warn = '';
            var now = opt && opt.value ? parseFloat(opt.dataset.balance) : null;
            document.getElementById('payNow').textContent = now === null ? '—' : money(now);
            document.getElementById('payAfter').textContent = now === null ? '—' : money(now - total);
            if (now !== null && opt.dataset.cash === '1' && total > now) warn = 'Not enough cash — fund it from a bank first.';
            document.getElementById('payNote').textContent = method.value === 'cheque' ? 'The cheque shows as issued until you mark it cleared on the bank statement.' : '';
            document.getElementById('payWarn').textContent = warn;
            var btn = document.getElementById('payBtn');
            if (form.dataset.submitting !== '1') {
                btn.disabled = !bank.value || total <= 0 || !!warn;
                btn.textContent = total > 0 ? 'Pay GH₵ ' + money(total) : 'Record payment';
            }
        }

        var all = document.getElementById('expAll');
        if (all) all.addEventListener('click', function () { form.querySelectorAll('.exp-check').forEach(function (c) { c.checked = true; }); update(); });
        form.addEventListener('input', update);
        form.addEventListener('change', update);
        form.addEventListener('submit', function (e) {
            if (!confirm('Record a payment of GH₵ ' + document.getElementById('payTotal').textContent + ' to ' + (document.getElementById('payee').value || '…') + '?')) e.preventDefault();
        });
        update();
    })();
    </script>
    @endsection
</x-sales-dashboard>

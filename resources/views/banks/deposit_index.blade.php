<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <style>
        .dp-num { font-variant-numeric: tabular-nums; white-space: nowrap; text-align: right; }
        .dp-tile { border-left: 3px solid var(--bs-primary, #696cff); }
        .dp-tile.momo { border-left-color: var(--bs-warning, #ffab00); }
        .dp-tile.old { border-left-color: var(--bs-danger, #ff3e1d); }
        .dp-pick { display: flex; align-items: center; gap: .45rem; white-space: nowrap; cursor: pointer; margin: 0; }
        .dp-pick input { margin: 0; }
        tr.dp-on-cash td.dp-cash, tr.dp-on-momo td.dp-momo { background: rgba(105, 108, 255, .08); }
        tr.dp-on-momo td.dp-momo { background: rgba(255, 171, 0, .12); }
        .dp-ref { font-size: .75rem; color: var(--bs-secondary-color, #8592a3); }
        .dp-old { color: var(--bs-danger, #ff3e1d); font-weight: 600; }
        @media (min-width: 992px) and (min-height: 860px) { .dp-slip { position: sticky; top: 5.5rem; } }
        .dp-slip .dp-line { display: flex; justify-content: space-between; gap: 1rem; }
        .dp-slip .dp-total { font-size: 1.5rem; font-weight: 700; }
        .dp-ledger { font-size: .8rem; background: var(--bs-gray-100, #f5f5f9); border-radius: .375rem; padding: .5rem .75rem; }
        .dp-ledger .in { color: var(--bs-success, #71dd37); }
        .dp-ledger .out { color: var(--bs-danger, #ff3e1d); }
    </style>
    @endsection

    @section('content')
    @php
        $cashWaiting = $waiting->where('cash_due', '>', 0);
        $momoWaiting = $waiting->where('momo_due', '>', 0);
        $oldest = $waiting->max('days_waiting');
        $gh = fn ($v) => number_format((float) $v, 2);
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
            <div>
                <h3 class="text-primary mb-1"><i class="bx bx-arrow-from-left me-1"></i> Bank Deposit</h3>
                <div class="text-muted small">Cash and MoMo received on receipts, waiting to go to the bank. One deposit = one bank slip.</div>
            </div>
            <div class="d-flex gap-2 align-items-end">
                @if($fields->count() > 1)
                <form method="GET" class="d-flex gap-2 align-items-end">
                    <div>
                        <label class="form-label small mb-0" for="field">Office</label>
                        <select class="form-select form-select-sm" name="field" id="field" onchange="this.form.submit()">
                            <option value="">All offices</option>
                            @foreach($fields as $f)
                                <option value="{{ $f->id }}" {{ (int) $fieldId === $f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
                @endif
                @if($canDeposit)
                <a href="{{ route('deposit.index') }}" class="btn btn-outline-secondary btn-sm">Deposits made</a>
                @endif
            </div>
        </div>

        @include('flash-messages')
        @if($errors->any())
            <div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3"><div class="card dp-tile"><div class="card-body py-3">
                <div class="small text-muted">Cash waiting</div>
                <div class="fs-5 fw-bold">GH&#8373; {{ $gh($cashWaiting->sum('cash_due')) }}</div>
                <div class="small text-muted">{{ $cashWaiting->count() }} {{ \Illuminate\Support\Str::plural('receipt', $cashWaiting->count()) }}</div>
            </div></div></div>
            <div class="col-6 col-lg-3"><div class="card dp-tile momo"><div class="card-body py-3">
                <div class="small text-muted">MoMo waiting (to withdraw)</div>
                <div class="fs-5 fw-bold">GH&#8373; {{ $gh($momoWaiting->sum('momo_due')) }}</div>
                <div class="small text-muted">{{ $momoWaiting->count() }} {{ \Illuminate\Support\Str::plural('receipt', $momoWaiting->count()) }}</div>
            </div></div></div>
            <div class="col-6 col-lg-3"><div class="card dp-tile old"><div class="card-body py-3">
                <div class="small text-muted">Oldest waiting</div>
                <div class="fs-5 fw-bold {{ $oldest > 7 ? 'dp-old' : '' }}">{{ $waiting->isEmpty() ? '—' : $oldest . ' ' . \Illuminate\Support\Str::plural('day', $oldest) }}</div>
                <div class="small text-muted">since the receipt was written</div>
            </div></div></div>
            <div class="col-6 col-lg-3"><div class="card dp-tile"><div class="card-body py-3">
                <div class="small text-muted">Total waiting</div>
                <div class="fs-5 fw-bold">GH&#8373; {{ $gh($waiting->sum('cash_due') + $waiting->sum('momo_due')) }}</div>
                <div class="small text-muted">before MoMo charges</div>
            </div></div></div>
        </div>

        <form method="POST" action="{{ route('deposit.store') }}" id="depositForm" autocomplete="off">
            @csrf
            {!! \App\Support\SubmissionGuard::field() !!}
            <div id="offPageTicks"></div>
            <div class="row g-3">
                <div class="{{ $canDeposit ? 'col-lg-8' : 'col-12' }}">
                    <div class="card">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                            <h6 class="mb-0">Waiting to be deposited <span class="small text-primary fw-normal ms-1" id="pickedNote"></span></h6>
                            @if($canDeposit && $waiting->isNotEmpty())
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-tick="cash">Tick all cash</button>
                                <button type="button" class="btn btn-sm btn-outline-warning" data-tick="momo">Tick all MoMo</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-tick="none">Clear</button>
                            </div>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0" id="waitingTable">
                                <thead>
                                    <tr>
                                        <th class="text-end">Cash</th>
                                        <th class="text-end">MoMo</th>
                                        <th>Receipt</th>
                                        <th>Client</th>
                                        <th>Office</th>
                                        <th>Received</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($waiting as $c)
                                    @php
                                        $cashOld = old('cash') ? in_array($c->id, (array) old('cash')) : false;
                                        $momoOld = old('momo') ? in_array($c->id, (array) old('momo')) : false;
                                    @endphp
                                    <tr data-id="{{ $c->id }}">
                                        <td class="dp-cash dp-num" data-order="{{ $c->cash_due }}">
                                            @if($c->cash_due > 0)
                                                @if($canDeposit)
                                                <label class="dp-pick justify-content-end"><input type="checkbox" class="form-check-input dp-check" name="cash[]" value="{{ $c->id }}" data-kind="cash" data-amount="{{ $c->cash_due }}" {{ $cashOld ? 'checked' : '' }}> {{ $gh($c->cash_due) }}</label>
                                                @else {{ $gh($c->cash_due) }} @endif
                                                @if((float) $c->cheque_amount > 0 && $c->cash_due > (float) $c->cash_amount)<div class="dp-ref">incl. old cheque</div>@endif
                                            @elseif($c->cash_deposit_id)
                                                <span class="dp-ref">deposited</span>
                                            @endif
                                        </td>
                                        <td class="dp-momo dp-num" data-order="{{ $c->momo_due }}">
                                            @if($c->momo_due > 0)
                                                @if($canDeposit)
                                                <label class="dp-pick justify-content-end"><input type="checkbox" class="form-check-input dp-check" name="momo[]" value="{{ $c->id }}" data-kind="momo" data-amount="{{ $c->momo_due }}" {{ $momoOld ? 'checked' : '' }}> {{ $gh($c->momo_due) }}</label>
                                                @else {{ $gh($c->momo_due) }} @endif
                                                @if($c->momo_ref)<div class="dp-ref">ID {{ $c->momo_ref }}</div>@endif
                                            @elseif($c->momo_deposit_id)
                                                <span class="dp-ref">deposited</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($c->receipt_id)<a href="{{ url('receipt', $c->receipt_id) }}">FWSSR{{ $c->receipt_id }}</a>@else #{{ $c->id }} @endif
                                            @if(strcasecmp((string) $c->status, 'Part deposited') === 0)<span class="badge bg-label-info ms-1">part deposited</span>@endif
                                        </td>
                                        <td>{{ $c->client_name ?? $c->paid_by }}</td>
                                        <td>{{ $c->office }}</td>
                                        <td data-order="{{ $c->created_at?->timestamp }}">
                                            {{ $c->created_at?->format('d M Y') }}
                                            <div class="dp-ref {{ $c->days_waiting > 7 ? 'dp-old' : '' }}">{{ $c->days_waiting === 0 ? 'today' : $c->days_waiting . ' ' . \Illuminate\Support\Str::plural('day', $c->days_waiting) . ' waiting' }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($waiting->isEmpty())
                            <div class="card-body text-center text-muted">Nothing waiting — all cash and MoMo has been deposited.</div>
                        @endif
                    </div>
                    <p class="small text-muted mt-2 mb-0">
                        Cheques and transfers are not here: they go straight into the bank chosen on the receipt.
                        Cash and MoMo from the same receipt can go on different slips.
                    </p>
                    @if($canCorrect && $waiting->isNotEmpty())
                    <p class="small mt-1">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#bankedModal">Some of this was already banked before this screen existed?</a>
                    </p>
                    @endif
                </div>

                @if($canDeposit)
                <div class="col-lg-4">
                    <div class="card dp-slip">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="bx bx-receipt me-1"></i> Deposit slip</h6>

                            <div class="mb-2">
                                <label class="form-label small mb-0" for="bank_id">Bank account <span class="text-danger">*</span></label>
                                <select name="bank_id" id="bank_id" class="form-select form-select-sm" required>
                                    <option value="">Choose…</option>
                                    @foreach($banks as $bank)
                                        <option value="{{ $bank->id }}" {{ (string) old('bank_id') === (string) $bank->id ? 'selected' : '' }}>{{ $bank->name }}@if($bank->acc_number) — {{ $bank->acc_number }}@endif</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small mb-0" for="deposit_date">Date on slip <span class="text-danger">*</span></label>
                                    <input type="date" name="deposit_date" id="deposit_date" class="form-control form-control-sm" value="{{ old('deposit_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-0" for="slip_number">Slip / ref no.</label>
                                    <input type="text" name="slip_number" id="slip_number" class="form-control form-control-sm" maxlength="60" value="{{ old('slip_number') }}" placeholder="from the slip">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small mb-0" for="depositor">Deposited by</label>
                                <input type="text" name="depositor" id="depositor" class="form-control form-control-sm" maxlength="120" value="{{ old('depositor', Auth::user()?->name) }}">
                            </div>

                            <div class="dp-line"><span>Cash <span class="text-muted small" id="cashCount"></span></span><span class="dp-num" id="cashSum">0.00</span></div>
                            <div class="dp-line"><span>MoMo withdrawn <span class="text-muted small" id="momoCount"></span></span><span class="dp-num" id="momoSum">0.00</span></div>

                            <div id="chargeBox" class="mt-2" style="display:none">
                                <label class="form-label small mb-0" for="momo_charge">MoMo withdrawal charge (GH&#8373;) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="momo_charge" id="momo_charge" class="form-control form-control-sm text-end" value="{{ old('momo_charge') }}" placeholder="from the withdrawal SMS">
                                <div class="form-text">
                                    What the MoMo vendor/agent took to cash it out. Enter 0 if nothing.
                                    @if($chargeRate) Suggested at {{ rtrim(rtrim(number_format($chargeRate, 2), '0'), '.') }}% — change it to the real charge. @endif
                                </div>
                                <div class="dp-line text-danger mt-1"><span>less charge</span><span class="dp-num" id="chargeShow">(0.00)</span></div>
                            </div>

                            <hr class="my-2">
                            <div class="dp-line align-items-baseline"><span class="fw-semibold">Amount on slip</span><span class="dp-num dp-total">GH&#8373; <span id="slipSum">0.00</span></span></div>

                            <div class="dp-ledger mt-2" id="ledgerPreview" style="display:none">
                                <div class="text-muted mb-1">On the bank account this shows as:</div>
                                <div class="dp-line"><span>Deposit (cash + MoMo)</span><span class="dp-num in">+ <span id="pvIn">0.00</span></span></div>
                                <div class="dp-line" id="pvChargeRow"><span>MoMo withdrawal charge</span><span class="dp-num out">− <span id="pvOut">0.00</span></span></div>
                            </div>

                            <div class="mt-3">
                                <label class="form-label small mb-0" for="notes">Notes</label>
                                <input type="text" name="notes" id="notes" class="form-control form-control-sm" maxlength="500" value="{{ old('notes') }}" placeholder="optional">
                            </div>

                            <button type="submit" id="depositBtn" class="btn btn-primary w-100 mt-3" data-loading disabled>Tick what you are depositing</button>
                            <div class="small text-muted mt-2" id="whyDisabled"></div>
                        </div>
                    </div>
                </div>
                @else
                <div class="col-12">
                    <div class="alert alert-secondary small mb-0">Deposits are recorded by head office accounts (Invoice / Finance Manager).</div>
                </div>
                @endif
            </div>
        </form>

        @if($canCorrect && $waiting->isNotEmpty())
        <div class="modal fade" id="bankedModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('deposit.markBanked') }}" class="modal-content" id="bankedForm">
                    @csrf
                    {!! \App\Support\SubmissionGuard::field() !!}
                    <div class="modal-header"><h5 class="modal-title">Mark as already banked</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                        <p class="small">For old receipts whose cash or MoMo was taken to the bank by hand before this screen existed.
                            The ticked rows (<strong id="bankedCount">0</strong>) are closed and <strong>nothing is posted to any bank</strong>.
                            Use a normal deposit for anything going to the bank now.</p>
                        <div id="bankedIds"></div>
                        <label class="form-label small" for="banked_reason">How was it banked? <span class="text-danger">*</span></label>
                        <input type="text" name="banked_reason" id="banked_reason" class="form-control" maxlength="300" placeholder="e.g. Deposited at GCB Accra, Aug 2026, slip 5521" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" id="bankedBtn" data-loading disabled>Mark as already banked</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>
    @endsection

    @section('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.js"></script>
    <script>
    (function () {
        // Paged list. Ticks on other pages still count: totals read every row, and on
        // saving, ticks on pages not shown are sent as hidden fields.
        var dt = null;
        if (window.DataTable && document.querySelector('#waitingTable tbody tr')) {   // table works without it too
            dt = new DataTable('#waitingTable', {
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                order: [[5, 'asc']],
                language: { info: 'Showing _START_–_END_ of _TOTAL_ waiting', infoFiltered: '(filtered from _MAX_)' }
            });
        }

        var rate = {{ $chargeRate !== null ? json_encode($chargeRate) : 'null' }};
        var form = document.getElementById('depositForm');
        var charge = document.getElementById('momo_charge');
        if (!charge) return;
        var chargeTouched = charge.value !== '';
        charge.addEventListener('input', function () { chargeTouched = true; update(); });

        function money(v) { return (Math.round(v * 100) / 100).toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
        // Every row, including rows on pages not shown (DataTables keeps them out of the page).
        function rows(onlyMatchingSearch) {
            if (!dt) return Array.prototype.slice.call(document.querySelectorAll('#waitingTable tbody tr'));
            return dt.rows(onlyMatchingSearch ? { search: 'applied' } : {}).nodes().toArray();
        }
        function checks(kind, onlyMatchingSearch) {
            var out = [];
            rows(onlyMatchingSearch).forEach(function (tr) {
                tr.querySelectorAll('.dp-check' + (kind ? '[data-kind="' + kind + '"]' : '')).forEach(function (c) { out.push(c); });
            });
            return out;
        }
        function picked(kind) {
            return checks(kind).filter(function (c) { return c.checked; });
        }
        function sum(list) { return list.reduce(function (s, c) { return s + parseFloat(c.dataset.amount || 0); }, 0); }
        function count(n) { return n ? '(' + n + ')' : ''; }

        function update() {
            var cash = picked('cash'), momo = picked('momo');
            var cashSum = sum(cash), momoSum = sum(momo);
            document.getElementById('cashSum').textContent = money(cashSum);
            document.getElementById('momoSum').textContent = money(momoSum);
            document.getElementById('cashCount').textContent = count(cash.length);
            document.getElementById('momoCount').textContent = count(momo.length);

            document.getElementById('chargeBox').style.display = momoSum > 0 ? '' : 'none';
            if (momoSum > 0 && rate && !chargeTouched) charge.value = (Math.round(momoSum * rate) / 100).toFixed(2);
            if (momoSum <= 0 && !chargeTouched) charge.value = '';
            var chg = momoSum > 0 ? (parseFloat(charge.value) || 0) : 0;
            document.getElementById('chargeShow').textContent = '(' + money(chg) + ')';

            var gross = cashSum + momoSum, slip = gross - chg;
            document.getElementById('slipSum').textContent = money(slip);
            document.getElementById('ledgerPreview').style.display = gross > 0 ? '' : 'none';
            document.getElementById('pvIn').textContent = money(gross);
            document.getElementById('pvOut').textContent = money(chg);
            document.getElementById('pvChargeRow').style.display = chg > 0 ? '' : 'none';

            var offPage = cash.concat(momo).filter(function (c) { return !document.body.contains(c); }).length;
            var note = document.getElementById('pickedNote');
            if (note) note.textContent = cash.length + momo.length
                ? (cash.length + momo.length) + ' ticked' + (offPage ? ' (' + offPage + ' on other pages)' : '')
                : '';

            rows().forEach(function (tr) {
                var c = tr.querySelector('.dp-check[data-kind="cash"]'), m = tr.querySelector('.dp-check[data-kind="momo"]');
                tr.classList.toggle('dp-on-cash', !!(c && c.checked));
                tr.classList.toggle('dp-on-momo', !!(m && m.checked));
            });

            var why = '';
            if (gross <= 0) why = 'Tick the cash and/or MoMo you are depositing.';
            else if (!document.getElementById('bank_id').value) why = 'Choose the bank account.';
            else if (momoSum > 0 && charge.value === '') why = 'Enter the MoMo withdrawal charge (0 if none).';
            else if (momoSum > 0 && chg >= momoSum) why = 'The charge must be less than the MoMo withdrawn.';
            var btn = document.getElementById('depositBtn');
            if (form.dataset.submitting !== '1') {
                btn.disabled = !!why;
                btn.innerHTML = gross > 0 ? 'Deposit GH&#8373; ' + money(slip) : 'Tick what you are depositing';
            }
            document.getElementById('whyDisabled').textContent = gross > 0 ? why : '';

            var bankedCount = document.getElementById('bankedCount');
            if (bankedCount) {
                var ids = {};
                cash.concat(momo).forEach(function (c) { ids[c.value] = true; });
                var keys = Object.keys(ids);
                bankedCount.textContent = keys.length;
                document.getElementById('bankedIds').innerHTML = keys.map(function (id) { return '<input type="hidden" name="collections[]" value="' + id + '">'; }).join('');
                document.getElementById('bankedBtn').disabled = keys.length === 0;
            }
        }

        form.addEventListener('change', update);
        document.getElementById('bank_id').addEventListener('change', update);
        document.querySelectorAll('[data-tick]').forEach(function (b) {
            b.addEventListener('click', function () {
                var kind = b.dataset.tick;
                // "Tick all" ticks every row that matches the search box, on every page.
                (kind === 'none' ? checks(null) : checks(kind, true)).forEach(function (c) { c.checked = kind !== 'none'; });
                update();
            });
        });
        form.addEventListener('submit', function (e) {
            var slip = document.getElementById('slipSum').textContent;
            var bank = document.getElementById('bank_id');
            if (!confirm('Deposit GH₵ ' + slip + ' to ' + bank.options[bank.selectedIndex].text + '?')) { e.preventDefault(); return; }

            // Ticks on pages not shown are not in the form: send them as hidden fields.
            var carry = document.getElementById('offPageTicks');
            carry.innerHTML = '';
            picked('cash').concat(picked('momo')).forEach(function (c) {
                if (document.body.contains(c)) return;
                var h = document.createElement('input');
                h.type = 'hidden'; h.name = c.name; h.value = c.value;
                carry.appendChild(h);
            });
        });
        update();
    })();
    </script>
    @endsection
</x-sales-dashboard>

<x-sales-dashboard>

    @section('side_nav')
        @include('partials.receipt_side_nav')
    @endsection

    @section('css')
    <style>
        .mi-num { font-variant-numeric: tabular-nums; white-space: nowrap; }
        .mi-table td, .mi-table th { vertical-align: middle; }
        .mi-table input[type=number] { min-width: 105px; text-align: right; }
        .mi-table tr.mi-off td:not(:first-child) { opacity: .45; }
        .mi-table tr.mi-paid .mi-remaining { color: var(--bs-success, #71dd37); font-weight: 600; }
        .mi-summary { position: sticky; top: 90px; }
        .mi-summary dl { display: grid; grid-template-columns: 1fr auto; gap: .35rem .75rem; margin: 0; }
        .mi-summary dt { font-weight: 400; color: var(--bs-secondary-color, #8592a3); }
        .mi-summary dd { margin: 0; text-align: right; }
        .mi-summary .mi-big { font-size: 1.35rem; font-weight: 600; }
        .mi-credit-box { border: 1px dashed var(--bs-warning, #ffab00); border-radius: .5rem; padding: .75rem; }
        .mi-mode-row { border-left: 3px solid var(--bs-primary, #696cff); padding-left: .75rem; margin-bottom: 1rem; }
    </style>
    @endsection

    @section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h3 class="mb-0 text-primary">
                <i class="icon-base bx bx-receipt text-primary me-2"></i> Receipt
                <i class="icon-base bx bx-right-arrow-alt mx-1 text-muted"></i> Pay Multiple Invoices
            </h3>
            <a href="{{ url('receipt/create') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bx bx-list-ul me-1"></i> Pay a single invoice instead
            </a>
        </div>

        @include('flash-messages')

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Please fix the following:</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Step 1: choose the client --}}
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('receipt.multi.create') }}" id="clientForm" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label" for="clientFilter">Find client</label>
                        <input type="search" id="clientFilter" class="form-control" placeholder="Type name, business or office…">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="client_id">Client <small class="text-muted">({{ $clients->count() }} with unpaid invoices)</small></label>
                        <select name="client_id" id="client_id" class="form-select" required>
                            <option value="" disabled {{ $client ? '' : 'selected' }}>Choose the client who is paying…</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}" {{ $client?->id === $c->id ? 'selected' : '' }}>
                                    {{ $c->business_name ?: $c->name }}@if($c->business_name && $c->name !== $c->business_name) — {{ $c->name }}@endif · {{ $c->field?->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary" type="submit">Load invoices</button>
                    </div>
                </form>
            </div>
        </div>

        @if($client)
        <form method="POST" action="{{ route('receipt.multi.store') }}" enctype="multipart/form-data" id="multiForm">
            @csrf
            <input type="hidden" name="client_id" value="{{ $client->id }}">

            <div class="row g-4">
                <div class="col-xl-8">

                    {{-- Step 2: which invoices this payment covers --}}
                    <div class="card mb-4">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h5 class="mb-0">Invoices to pay</h5>
                                <small class="text-muted">Tick the invoices this payment covers. Money is applied oldest first; type an amount to override.</small>
                            </div>
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="applyWht">
                                    <label class="form-check-label" for="applyWht">{{ $wht_rate->wht_rate * 100 }}% WHT</label>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="applyVat">
                                    <label class="form-check-label" for="applyVat">7% VAT</label>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="autoAllocate">
                                    <i class="bx bx-shuffle me-1"></i> Auto-allocate
                                </button>
                            </div>
                        </div>

                        @if($invoices->isEmpty())
                            <div class="card-body">
                                <p class="mb-0 text-muted">This client has no unpaid invoices. You can still record the money as an <strong>advance payment</strong>: fill in the payment below and tick “Keep the extra as client credit”.</p>
                            </div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-sm mi-table mb-0">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" class="form-check-input" id="selectAll" title="Select all"></th>
                                        <th>Invoice</th>
                                        <th>Month</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-end">Outstanding</th>
                                        <th class="text-end">WHT</th>
                                        <th class="text-end">7% VAT</th>
                                        <th class="text-end">Deduction</th>
                                        <th class="text-end">Amount applied</th>
                                        <th class="text-end">Left to pay</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoices as $inv)
                                        @php
                                            $outstanding = max(0, round((float) $inv->total - (float) $inv->settled_sum, 2));
                                            $whtDefault = round((float) $inv->sub_amount * $wht_rate->wht_rate, 2);
                                            $vatDefault = round(((float) $inv->sub_total > 0 ? (float) $inv->sub_total : (float) $inv->sub_amount) * 0.07, 2);
                                            $key = 'invoices.' . $inv->id;
                                            $checked = old($key . '.selected') === '1';
                                        @endphp
                                        <tr class="mi-row {{ $checked ? '' : 'mi-off' }}"
                                            data-id="{{ $inv->id }}"
                                            data-outstanding="{{ $outstanding }}"
                                            data-wht="{{ $whtDefault }}"
                                            data-vat="{{ $vatDefault }}">
                                            <td><input type="checkbox" class="form-check-input mi-pick" name="invoices[{{ $inv->id }}][selected]" value="1" {{ $checked ? 'checked' : '' }}></td>
                                            <td><strong>FWSSi{{ $inv->id }}</strong><br><span class="badge bg-label-{{ $inv->status === 'uncompleted' ? 'warning' : 'danger' }}">{{ $inv->status === 'uncompleted' ? 'part paid' : 'unpaid' }}</span></td>
                                            <td>{{ $inv->invoice_month?->format('M Y') }}</td>
                                            <td class="text-end mi-num">{{ number_format($inv->total, 2) }}</td>
                                            <td class="text-end mi-num"><strong>{{ number_format($outstanding, 2) }}</strong></td>
                                            <td><input type="number" step="0.01" min="0" class="form-control form-control-sm mi-wht" name="invoices[{{ $inv->id }}][wht]" value="{{ old($key . '.wht') }}" placeholder="0.00"></td>
                                            <td><input type="number" step="0.01" min="0" class="form-control form-control-sm mi-vat" name="invoices[{{ $inv->id }}][vat7]" value="{{ old($key . '.vat7') }}" placeholder="0.00"></td>
                                            <td><input type="number" step="0.01" min="0" class="form-control form-control-sm mi-ded" name="invoices[{{ $inv->id }}][deduction]" value="{{ old($key . '.deduction') }}" placeholder="0.00"></td>
                                            <td><input type="number" step="0.01" min="0" class="form-control form-control-sm mi-applied" name="invoices[{{ $inv->id }}][applied]" value="{{ old($key . '.applied') }}" placeholder="auto"></td>
                                            <td class="text-end mi-num mi-remaining">{{ number_format($outstanding, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-body pt-3">
                            <label class="form-label" for="description">Deduction description <small class="text-muted">(only if you entered deductions)</small></label>
                            <input type="text" class="form-control" name="description" id="description" value="{{ old('description') }}" placeholder="e.g. Penalty for late guard posting">
                        </div>
                        @endif
                    </div>

                    {{-- Step 3: the money received, once --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Payment received</h5>
                            <small class="text-muted">Enter the money once — it is shared across the ticked invoices.</small>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="from" class="form-label">FROM</label>
                                    <input type="text" name="from" id="from" class="form-control" value="{{ old('from', $client->name) }}" placeholder="Full name of payer" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="receipt_month" class="form-label">RECEIPT DATE</label>
                                    <input type="date" name="receipt_month" id="receipt_month" class="form-control" value="{{ old('receipt_month', now()->toDateString()) }}" required>
                                </div>
                            </div>

                            <label class="form-label d-block">MODE <small class="text-muted">(tick one or more)</small></label>
                            @php
                                $modeInputId = fn ($name) => 'mode_' . str_replace(' ', '_', strtolower($name));
                                $selectedModes = old('mode', []);
                            @endphp
                            <div class="d-flex flex-wrap gap-4 mb-3">
                                @foreach ($mode as $m)
                                    <div class="form-check">
                                        <input type="checkbox" name="mode[]" id="{{ $modeInputId($m->name) }}" class="form-check-input mi-mode" value="{{ $m->name }}" {{ in_array($m->name, $selectedModes) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="{{ $modeInputId($m->name) }}">{{ $m->name }}</label>
                                    </div>
                                @endforeach
                            </div>

                            <div id="cashrow" class="mi-mode-row" style="display:none">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="cash_amount" class="form-label">CASH AMOUNT</label>
                                        <input type="number" step="0.01" min="0" name="cash_amount" id="cash_amount" class="form-control mi-money" value="{{ old('cash_amount') }}" placeholder="GH&#8373;">
                                    </div>
                                </div>
                            </div>

                            <div id="momorow" class="mi-mode-row" style="display:none">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="momo_transactin_id" class="form-label">MOMO TRANSACTION ID</label>
                                        <input type="text" name="momo_transactin_id" id="momo_transactin_id" class="form-control" value="{{ old('momo_transactin_id') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="momo_amount" class="form-label">MOMO AMOUNT</label>
                                        <input type="number" step="0.01" min="0" name="momo_amount" id="momo_amount" class="form-control mi-money" value="{{ old('momo_amount') }}" placeholder="GH&#8373;">
                                    </div>
                                </div>
                            </div>

                            <div id="chequerow" class="mi-mode-row" style="display:none">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label for="cheque_reference" class="form-label">CHEQUE NO. / REF</label>
                                        <input type="text" name="cheque_reference" id="cheque_reference" class="form-control" value="{{ old('cheque_reference') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="cheque_amount" class="form-label">CHEQUE AMOUNT</label>
                                        <input type="number" step="0.01" min="0" name="cheque_amount" id="cheque_amount" class="form-control mi-money" value="{{ old('cheque_amount') }}" placeholder="GH&#8373;">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="cheque_bank" class="form-label">DRAWN ON (PAYER'S BANK)</label>
                                        <input type="text" name="cheque_bank" id="cheque_bank" class="form-control" value="{{ old('cheque_bank') }}" placeholder="e.g. GCB">
                                    </div>
                                    <div class="col-md-3">
                                        @include('partials.receipt_bank_select', ['name' => 'cheque_to_bank_id', 'label' => 'DEPOSIT INTO (OUR BANK)', 'banks' => $banks, 'selected' => null])
                                    </div>
                                </div>
                            </div>

                            <div id="transferrow" class="mi-mode-row" style="display:none">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label for="transfer_reference" class="form-label">TRANSFER REF</label>
                                        <input type="text" name="transfer_reference" id="transfer_reference" class="form-control" value="{{ old('transfer_reference') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="transfer_amount" class="form-label">TRANSFER AMOUNT</label>
                                        <input type="number" step="0.01" min="0" name="transfer_amount" id="transfer_amount" class="form-control mi-money" value="{{ old('transfer_amount') }}" placeholder="GH&#8373;">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="transfer_bank" class="form-label">FROM (PAYER'S BANK)</label>
                                        <input type="text" name="transfer_bank" id="transfer_bank" class="form-control" value="{{ old('transfer_bank') }}" placeholder="e.g. Ecobank">
                                    </div>
                                    <div class="col-md-3">
                                        @include('partials.receipt_bank_select', ['name' => 'transfer_to_bank_id', 'label' => 'RECEIVED INTO (OUR BANK)', 'banks' => $banks, 'selected' => null])
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1"><i class="bx bx-info-circle"></i> The transfer is credited to this bank account straight away.</small>
                            </div>

                            <div id="otherpayrow" class="mi-mode-row" style="display:none">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="other_payment_descri" class="form-label">OTHER PAYMENT DESCRIPTION</label>
                                        <input type="text" name="other_payment_descri" id="other_payment_descri" class="form-control" value="{{ old('other_payment_descri') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="other_payment_amnt" class="form-label">AMOUNT</label>
                                        <input type="number" step="0.01" min="0" name="other_payment_amnt" id="other_payment_amnt" class="form-control mi-money" value="{{ old('other_payment_amnt') }}" placeholder="GH&#8373;">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label for="image" class="form-label">Attach cheque / slip <small class="text-muted">(jpg/png, max 2MB)</small></label>
                                    <input type="file" name="image" id="image" class="form-control" accept="image/png, image/jpeg">
                                </div>
                                @if($assign_staff->isNotEmpty())
                                <div class="col-md-6">
                                    <label for="staff" class="form-label">ASSIGN TO (for approval)</label>
                                    <select name="staff" id="staff" class="form-select" required>
                                        <option value="" disabled {{ old('staff') ? '' : 'selected' }}>Choose…</option>
                                        @foreach($assign_staff as $u)
                                            <option value="{{ $u->id }}" {{ old('staff') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Live summary --}}
                <div class="col-xl-4">
                    <div class="mi-summary">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="mb-1">{{ $client->business_name ?: $client->name }}</h5>
                                <p class="text-muted small mb-3">{{ $client->name }} · {{ $client->phone_number }} · {{ $client->field?->name }}</p>
                                <dl>
                                    <dt>Money received</dt><dd class="mi-num mi-big" id="sumMoney">0.00</dd>
                                    <dt>Applied to invoices</dt><dd class="mi-num" id="sumApplied">0.00</dd>
                                    <dt>WHT + VAT + deductions</dt><dd class="mi-num" id="sumCredits">0.00</dd>
                                    <dt>Total settled on invoices</dt><dd class="mi-num"><strong id="sumSettled">0.00</strong></dd>
                                    <dt>Invoices ticked / fully paid</dt><dd class="mi-num" id="sumCount">0 / 0</dd>
                                </dl>
                                <div class="mi-credit-box mt-3" id="creditBox" style="display:none">
                                    <div class="d-flex justify-content-between"><span>Not applied to any invoice</span><strong class="mi-num" id="sumUnapplied">0.00</strong></div>
                                    <div class="form-check mt-2 mb-0">
                                        <input class="form-check-input" type="checkbox" name="keep_credit" value="1" id="keep_credit" {{ old('keep_credit') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="keep_credit">Keep the extra as client credit (advance)</label>
                                    </div>
                                    <small class="text-muted d-block mt-1">It stays on this receipt and can be applied to the client's next invoices from the receipt page.</small>
                                </div>
                                <div class="alert alert-danger mt-3 mb-0 py-2" id="overBox" style="display:none">
                                    More is applied than was received. Lower an amount or add the missing payment.
                                </div>
                            </div>
                        </div>

                        @if($creditReceipts->isNotEmpty())
                        <div class="card mb-3 border border-warning">
                            <div class="card-body">
                                <h6 class="mb-2"><i class="bx bx-wallet text-warning me-1"></i> Client already has credit</h6>
                                <p class="small text-muted mb-2">Use it before taking new money — open the receipt and click “Apply credit”.</p>
                                <ul class="list-unstyled mb-0 small">
                                    @foreach($creditReceipts as $cr)
                                    <li class="d-flex justify-content-between">
                                        <a href="{{ route('receipt.show', $cr->id) }}">FWSSR{{ $cr->id }}</a>
                                        <span class="mi-num">GH&#8373; {{ number_format($cr->unapplied_amount, 2) }}</span>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        @endif

                        <button type="submit" class="btn btn-primary w-100" id="submitBtn" data-loading>
                            <i class="bx bx-check-circle me-1"></i> Generate one receipt
                        </button>
                    </div>
                </div>
            </div>
        </form>
        @endif
    </div>
    @endsection

    @section('scripts')
    <script>
    $(function () {
        // ---- client picker: type to filter, choose to load ----
        const $clientSelect = $('#client_id');
        const allOptions = $clientSelect.find('option').clone();
        $('#clientFilter').on('input', function () {
            const q = this.value.toLowerCase().trim();
            const current = $clientSelect.val();
            $clientSelect.empty().append(allOptions.filter(function () {
                return !this.value || !q || this.text.toLowerCase().includes(q);
            }).clone());
            $clientSelect.val(current);
        });
        $clientSelect.on('change', function () { if (this.value) $('#clientForm').trigger('submit'); });

        if (!$('#multiForm').length) return;

        // ---- payment modes: show the row of each ticked mode ----
        const modes = {
            'cash': { row: '#cashrow', required: ['#cash_amount'] },
            'momo': { row: '#momorow', required: ['#momo_transactin_id', '#momo_amount'] },
            'cheque': { row: '#chequerow', required: ['#cheque_reference', '#cheque_amount', '#cheque_bank', '#cheque_to_bank_id'] },
            'transfer': { row: '#transferrow', required: ['#transfer_reference', '#transfer_amount', '#transfer_bank', '#transfer_to_bank_id'] },
            'other payments': { row: '#otherpayrow', required: ['#other_payment_descri', '#other_payment_amnt'] },
        };
        function syncModes(clear) {
            $('.mi-mode').each(function () {
                const cfg = modes[this.value];
                if (!cfg) return;
                const on = this.checked;
                $(cfg.row).toggle(on);
                cfg.required.forEach(sel => {
                    $(sel).prop('required', on);
                    if (!on && clear) $(sel).val('');
                });
            });
        }
        $('.mi-mode').on('change', function () { syncModes(true); recalc(); });
        syncModes(false);

        // ---- allocation maths (mirrors App\Services\Receipts\ReceiptAllocator) ----
        const r2 = n => Math.round((parseFloat(n) || 0) * 100) / 100;
        const fmt = n => r2(n).toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        function moneyReceived() {
            let total = 0;
            $('.mi-mode:checked').each(function () {
                const cfg = modes[this.value];
                if (cfg) $(cfg.row).find('.mi-money').each(function () { total += r2(this.value); });
            });
            return r2(total);
        }

        function recalc() {
            const money = moneyReceived();
            const rows = $('.mi-row').toArray();

            // money already committed to amounts the user typed
            let explicit = 0;
            rows.forEach(tr => {
                const $tr = $(tr);
                if ($tr.find('.mi-pick').is(':checked') && $tr.find('.mi-applied').data('typed')) {
                    explicit += r2($tr.find('.mi-applied').val());
                }
            });
            let pool = Math.max(0, r2(money - explicit));

            let applied = 0, credits = 0, settled = 0, picked = 0, paid = 0;
            rows.forEach(tr => {
                const $tr = $(tr);
                const on = $tr.find('.mi-pick').is(':checked');
                const outstanding = r2($tr.data('outstanding'));
                $tr.toggleClass('mi-off', !on);
                $tr.find('input[type=number]').prop('disabled', !on);

                if (!on) {
                    $tr.find('.mi-remaining').text(fmt(outstanding));
                    $tr.removeClass('mi-paid');
                    return;
                }
                picked++;
                const c = r2(r2($tr.find('.mi-wht').val()) + r2($tr.find('.mi-vat').val()) + r2($tr.find('.mi-ded').val()));
                const $applied = $tr.find('.mi-applied');
                let a;
                if ($applied.data('typed')) {
                    a = r2($applied.val());
                } else {
                    a = Math.min(Math.max(0, r2(outstanding - c)), pool);
                    pool = r2(pool - a);
                    $applied.val(a ? a.toFixed(2) : '');
                }
                const s = r2(a + c);
                const left = r2(outstanding - s);
                $tr.find('.mi-remaining').text(left < 0 ? 'over by ' + fmt(-left) : fmt(left));
                $tr.toggleClass('mi-paid', left <= 0.01 && left >= -0.01);
                $tr.find('.mi-remaining').toggleClass('text-danger', left < -0.01);
                if (left <= 0.01) paid++;
                applied += a; credits += c; settled += s;
            });

            const unapplied = r2(money - applied);
            $('#sumMoney').text(fmt(money));
            $('#sumApplied').text(fmt(applied));
            $('#sumCredits').text(fmt(credits));
            $('#sumSettled').text(fmt(settled));
            $('#sumCount').text(picked + ' / ' + paid);
            $('#sumUnapplied').text(fmt(Math.max(0, unapplied)));
            $('#creditBox').toggle(unapplied > 0.009 || ($('.mi-row').length === 0 && money > 0));
            $('#overBox').toggle(unapplied < -0.009);
        }

        // typing an amount pins it; clearing it hands the row back to auto
        $('.mi-applied').on('input', function () { $(this).data('typed', this.value !== ''); recalc(); });
        $('.mi-applied').each(function () { if (this.value !== '') $(this).data('typed', true); });
        $(document).on('input', '.mi-money, .mi-wht, .mi-vat, .mi-ded', recalc);
        $('.mi-pick').on('change', function () {
            const $tr = $(this).closest('tr');
            if (this.checked) {
                if ($('#applyWht').is(':checked') && !$tr.find('.mi-wht').val()) $tr.find('.mi-wht').val(r2($tr.data('wht')).toFixed(2));
                if ($('#applyVat').is(':checked') && !$tr.find('.mi-vat').val()) $tr.find('.mi-vat').val(r2($tr.data('vat')).toFixed(2));
            } else {
                $tr.find('input[type=number]').val('').data('typed', false);
            }
            recalc();
        });
        $('#selectAll').on('change', function () {
            $('.mi-pick').prop('checked', this.checked).trigger('change');
        });
        $('#autoAllocate').on('click', function () {
            $('.mi-applied').data('typed', false);
            recalc();
        });
        // WHT / VAT switches fill each ticked invoice with the standard amount (editable)
        $('#applyWht, #applyVat').on('change', function () {
            const isWht = this.id === 'applyWht';
            const on = this.checked;
            $('.mi-row').each(function () {
                const $tr = $(this);
                if (!$tr.find('.mi-pick').is(':checked')) return;
                const $in = $tr.find(isWht ? '.mi-wht' : '.mi-vat');
                $in.val(on ? r2($tr.data(isWht ? 'wht' : 'vat')).toFixed(2) : '');
            });
            recalc();
        });

        $('#multiForm').on('submit', function (e) {
            if (!$('.mi-mode:checked').length) {
                e.preventDefault();
                alert('Tick at least one payment mode.');
                return;
            }
            const picked = $('.mi-pick:checked').length;
            const msg = picked
                ? 'Generate ONE receipt for GH₵ ' + $('#sumMoney').text() + ' covering ' + picked + ' invoice(s)?'
                : 'Record GH₵ ' + $('#sumMoney').text() + ' as an advance (client credit) with no invoice?';
            if (!confirm(msg)) e.preventDefault();
        });

        recalc();
    });
    </script>
    @endsection
</x-sales-dashboard>

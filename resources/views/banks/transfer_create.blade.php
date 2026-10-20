<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <style>
        .mv-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        .mv-line td { vertical-align: top; }
        .mv-total { font-size: 1.4rem; font-weight: 700; }
    </style>
    @endsection

    @section('content')
    @php
        $oldLines = old('lines', [['from_bank_id' => '', 'method' => '', 'reference' => '', 'amount' => '']]);
        $toSel = (string) old('to_bank_id', $toId);
        $methods = \App\Models\AccountTransfer::METHODS;
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <a href="{{ route('money.transfers') }}" class="small"><i class="bx bx-arrow-back"></i> Transfers</a>
        <h3 class="text-primary mt-1 mb-1"><i class="bx bx-transfer me-1"></i> Fund an account / move money</h3>
        <p class="text-muted small mb-3">Money moving between our own accounts — e.g. cheques written from one or more banks to fund <strong>Main Cash</strong>, or unused cash paid back into a bank.
            Each line is debited from its account and credited to the receiving account. It is not income or spending.</p>

        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

        <form method="POST" action="{{ route('money.transfers.store') }}" id="trForm" autocomplete="off">
            @csrf
            {!! \App\Support\SubmissionGuard::field() !!}
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card"><div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small mb-0" for="to_bank_id">Money goes into <span class="text-danger">*</span></label>
                                <select name="to_bank_id" id="to_bank_id" class="form-select" required>
                                    <option value="">Choose…</option>
                                    @foreach($accounts as $a)
                                        <option value="{{ $a->id }}" data-cash="{{ $a->isCash() ? 1 : 0 }}" data-balance="{{ (float) $a->total }}" @selected($toSel === (string) $a->id)>{{ $a->label }} · {{ number_format($a->total, 2) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-0" for="transfer_date">Date <span class="text-danger">*</span></label>
                                <input type="date" name="transfer_date" id="transfer_date" class="form-control" value="{{ old('transfer_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                            </div>
                        </div>

                        <table class="table table-sm mb-2">
                            <thead><tr><th>From account</th><th style="width: 11rem">How</th><th style="width: 9rem">Cheque / ref no.</th><th class="mv-num" style="width: 9rem">Amount (GH₵)</th><th></th></tr></thead>
                            <tbody id="trLines">
                            @foreach($oldLines as $i => $l)
                                <tr class="mv-line">
                                    <td>
                                        <select name="lines[{{ $i }}][from_bank_id]" class="form-select form-select-sm tr-from">
                                            <option value="">Choose…</option>
                                            @foreach($accounts as $a)
                                                <option value="{{ $a->id }}" data-balance="{{ (float) $a->total }}" data-cash="{{ $a->isCash() ? 1 : 0 }}" @selected((string) ($l['from_bank_id'] ?? '') === (string) $a->id)>{{ $a->name }} · {{ number_format($a->total, 2) }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="lines[{{ $i }}][method]" class="form-select form-select-sm tr-method" data-old="{{ $l['method'] ?? '' }}">
                                            @foreach($methods as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                                        </select>
                                    </td>
                                    <td><input name="lines[{{ $i }}][reference]" class="form-control form-control-sm tr-ref" maxlength="60" value="{{ $l['reference'] ?? '' }}"></td>
                                    <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][amount]" class="form-control form-control-sm text-end tr-amount" value="{{ $l['amount'] ?? '' }}"></td>
                                    <td><button type="button" class="btn btn-sm btn-link text-danger p-0 tr-remove" title="Remove line">✕</button></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="trAdd"><i class="bx bx-plus"></i> Add another cheque / account</button>

                        <div class="mt-3">
                            <label class="form-label small mb-0" for="notes">Purpose / notes</label>
                            <input name="notes" id="notes" class="form-control" maxlength="500" value="{{ old('notes') }}" placeholder="e.g. Imprest for October field expenses">
                        </div>
                    </div></div>
                </div>

                <div class="col-lg-4">
                    <div class="card"><div class="card-body">
                        <div class="text-muted small">Total moving in</div>
                        <div class="mv-total">GH₵ <span id="trTotal">0.00</span></div>
                        <div class="small text-muted" id="trCount"></div>
                        <hr>
                        <div class="d-flex justify-content-between small"><span>Receiving account now</span><span class="mv-num" id="trToNow">—</span></div>
                        <div class="d-flex justify-content-between small fw-semibold"><span>After this</span><span class="mv-num" id="trToAfter">—</span></div>
                        <div class="small text-danger mt-2" id="trWarn"></div>
                        <button type="submit" class="btn btn-primary w-100 mt-3" id="trBtn" data-loading>Save</button>
                    </div></div>
                </div>
            </div>
        </form>
    </div>
    @endsection

    @section('scripts')
    <script>
    (function () {
        var pairs = @json($pairs);
        var labels = @json($methods);
        var body = document.getElementById('trLines');
        var to = document.getElementById('to_bank_id');

        function money(v) { return (Math.round(v * 100) / 100).toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

        function fitMethods(tr) {
            var from = tr.querySelector('.tr-from').value, sel = tr.querySelector('.tr-method');
            var keep = sel.value || sel.dataset.old;
            var allowed = (from && to.value && pairs[from + '-' + to.value]) || Object.keys(labels);
            sel.innerHTML = allowed.map(function (k) { return '<option value="' + k + '">' + labels[k] + '</option>'; }).join('');
            if (allowed.indexOf(keep) >= 0) sel.value = keep;
            sel.dataset.old = '';
            var ref = tr.querySelector('.tr-ref');
            ref.placeholder = sel.value === 'cheque' ? 'cheque no. (required)' : 'optional';
            ref.required = sel.value === 'cheque';
        }

        function update() {
            var total = 0, n = 0, warn = '';
            body.querySelectorAll('tr').forEach(function (tr) {
                fitMethods(tr);
                var amt = parseFloat(tr.querySelector('.tr-amount').value) || 0;
                var from = tr.querySelector('.tr-from');
                if (amt > 0) { total += amt; n++; }
                var opt = from.options[from.selectedIndex];
                if (amt > 0 && opt && opt.dataset.cash === '1' && amt > parseFloat(opt.dataset.balance)) warn = opt.text.split(' · ')[0] + ' only holds ' + money(parseFloat(opt.dataset.balance)) + '.';
                if (from.value && from.value === to.value) warn = 'A line comes from the same account it goes into.';
            });
            document.getElementById('trTotal').textContent = money(total);
            document.getElementById('trCount').textContent = n ? n + (n === 1 ? ' line' : ' lines') : '';
            var t = to.options[to.selectedIndex];
            var now = t && t.value ? parseFloat(t.dataset.balance) : null;
            document.getElementById('trToNow').textContent = now === null ? '—' : money(now);
            document.getElementById('trToAfter').textContent = now === null ? '—' : money(now + total);
            document.getElementById('trWarn').textContent = warn;
            document.getElementById('trBtn').disabled = !to.value || total <= 0 || !!warn;
            if (document.getElementById('trForm').dataset.submitting !== '1') document.getElementById('trBtn').textContent = total > 0 ? 'Move GH₵ ' + money(total) : 'Save';
        }

        document.getElementById('trAdd').addEventListener('click', function () {
            var rows = body.querySelectorAll('tr'), last = rows[rows.length - 1], i = rows.length;
            var tr = last.cloneNode(true);
            tr.querySelectorAll('select, input').forEach(function (el) {
                el.name = el.name.replace(/lines\[\d+\]/, 'lines[' + i + ']');
                if (el.tagName === 'INPUT') el.value = ''; else if (el.classList.contains('tr-from')) el.value = '';
            });
            body.appendChild(tr);
            update();
        });
        body.addEventListener('click', function (e) {
            if (!e.target.classList.contains('tr-remove')) return;
            if (body.querySelectorAll('tr').length > 1) e.target.closest('tr').remove();
            update();
        });
        document.getElementById('trForm').addEventListener('input', update);
        document.getElementById('trForm').addEventListener('change', update);
        update();
    })();
    </script>
    @endsection
</x-sales-dashboard>

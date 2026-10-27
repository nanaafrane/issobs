<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <style>.fl-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; } tr.fl-off td:not(:first-child) { opacity: .45; }</style>
    @endsection

    @section('content')
    @php
        $oldIds = old('_token') ? array_map('intval', (array) old('fuel_ids', [])) : $entries->pluck('id')->all();
        $field = $fields->firstWhere('id', $fieldId);
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <a href="{{ route('fleet.fuel.payouts') }}" class="small"><i class="bx bx-arrow-back"></i> Fuel pay-outs</a>
        <h3 class="text-primary mt-1 mb-1"><i class="bx bx-money-withdraw me-1"></i> New fuel pay-out</h3>
        <p class="text-muted small mb-3">Choose the office, dates and (for a station's bill) the station; tick the approved fuel to pay. The account shows <strong>one</strong> payment.</p>
        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

        <form method="GET" class="card mb-3"><div class="card-body row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label small mb-0">Office</label>
                <select name="field" class="form-select" required><option value="">Choose…</option>@foreach($fields as $f)<option value="{{ $f->id }}" @selected($fieldId === $f->id)>{{ $f->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label small mb-0">Station (optional)</label>
                <select name="station" class="form-select"><option value="">All stations</option>@foreach($stations as $s)<option value="{{ $s }}" @selected($station === $s)>{{ $s }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label small mb-0">From</label><input type="date" name="from" class="form-control" value="{{ $from->toDateString() }}"></div>
            <div class="col-md-2"><label class="form-label small mb-0">To</label><input type="date" name="to" class="form-control" value="{{ $to->toDateString() }}" max="{{ now()->toDateString() }}"></div>
            <div class="col-md-2 d-grid"><button class="btn btn-outline-primary">Show fuel</button></div>
        </div></form>

        @if($fieldId)
        <form method="POST" action="{{ route('fleet.fuel.payouts.store') }}" autocomplete="off">
            @csrf
            {!! \App\Support\SubmissionGuard::field() !!}
            <input type="hidden" name="field_id" value="{{ $fieldId }}">
            <input type="hidden" name="station" value="{{ $station }}">
            <input type="hidden" name="period_from" value="{{ $from->toDateString() }}">
            <input type="hidden" name="period_to" value="{{ $to->toDateString() }}">
            <div class="row g-3">
                <div class="col-xl-8">
                    <div class="card">
                        <div class="card-header py-2 d-flex justify-content-between"><h6 class="mb-0">Approved fuel owed · {{ $field?->name }}{{ $station ? ' · ' . $station : '' }} · {{ $from->format('d M') }} – {{ $to->format('d M Y') }}</h6>
                            @if($entries->isNotEmpty())<label class="small mb-0"><input type="checkbox" class="form-check-input me-1" id="fpAll" checked> all</label>@endif</div>
                        <div class="table-responsive" style="max-height: 32rem; overflow-y: auto;"><table class="table table-sm mb-0">
                            <thead><tr><th></th><th>Date</th><th>Vehicle</th><th>Driver</th><th>Station</th><th class="fl-num">Litres</th><th class="fl-num">Amount</th></tr></thead>
                            <tbody>
                            @forelse($entries as $f)
                                <tr class="{{ in_array($f->id, $oldIds, true) ? '' : 'fl-off' }}">
                                    <td><input type="checkbox" class="form-check-input fp-pick" name="fuel_ids[]" value="{{ $f->id }}" data-amount="{{ (float) $f->amount }}" data-litres="{{ (float) $f->litres }}" @checked(in_array($f->id, $oldIds, true))></td>
                                    <td class="text-nowrap">{{ $f->entry_date->format('D d M') }}</td>
                                    <td>{{ $f->vehicle?->reg_no }}</td>
                                    <td class="small">{{ $f->driverLabel() }}</td>
                                    <td class="small">{{ $f->station }}</td>
                                    <td class="fl-num">{{ number_format($f->litres, 2) }}</td>
                                    <td class="fl-num">{{ number_format($f->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-muted text-center py-3">No approved fuel is owed for {{ $field?->name }} between these dates.</td></tr>
                            @endforelse
                            </tbody>
                        </table></div>
                    </div>
                </div>
                @if($entries->isNotEmpty())
                <div class="col-xl-4">
                    <div class="card"><div class="card-body">
                        <div class="small text-muted">To pay</div>
                        <div class="fs-4 fw-bold">GH₵ <span id="fpTotal">0.00</span></div>
                        <div class="small text-muted mb-3"><span id="fpCount">0</span> fill-ups · <span id="fpLitres">0.00</span> L</div>
                        <div class="mb-2"><label class="form-label small mb-0">Paid to</label><input name="payee" class="form-control form-control-sm" value="{{ old('payee', $station ?: '') }}" placeholder="Fuel station, or who bought the fuel" required maxlength="150"></div>
                        <div class="mb-2"><label class="form-label small mb-0">From account</label>
                            <select name="bank_id" class="form-select form-select-sm" id="fpBank" required>
                                @foreach($accounts as $a)<option value="{{ $a->id }}" data-cash="{{ $a->isCash() ? 1 : 0 }}" @selected((int) old('bank_id') === $a->id)>{{ $a->name }} ({{ number_format((float) $a->total, 2) }})</option>@endforeach
                            </select></div>
                        <div class="mb-2" id="fpMethodRow"><label class="form-label small mb-0">How</label>
                            <select name="pay_method" class="form-select form-select-sm">@foreach(['cheque' => 'Cheque', 'transfer' => 'Bank transfer', 'momo' => 'Mobile money'] as $k => $l)<option value="{{ $k }}" @selected(old('pay_method') === $k)>{{ $l }}</option>@endforeach</select></div>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><label class="form-label small mb-0">Reference / cheque no.</label><input name="reference" class="form-control form-control-sm" value="{{ old('reference') }}" maxlength="60"></div>
                            <div class="col-6"><label class="form-label small mb-0">Payment date</label><input type="date" name="payment_date" class="form-control form-control-sm" value="{{ old('payment_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}"></div>
                        </div>
                        <div class="mb-3"><label class="form-label small mb-0">Notes</label><input name="notes" class="form-control form-control-sm" value="{{ old('notes') }}" maxlength="500"></div>
                        <button class="btn btn-primary w-100" data-loading>Pay fuel</button>
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
            const picks = () => Array.from(document.querySelectorAll('.fp-pick'));
            function sum() {
                let t = 0, l = 0, n = 0;
                picks().forEach(c => { c.closest('tr').classList.toggle('fl-off', !c.checked); if (c.checked) { t += +c.dataset.amount; l += +c.dataset.litres; n++; } });
                const set = (id, v) => { const e = document.getElementById(id); if (e) e.textContent = v; };
                set('fpTotal', t.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })); set('fpLitres', l.toFixed(2)); set('fpCount', n);
            }
            document.addEventListener('change', e => { if (e.target.classList.contains('fp-pick')) sum(); });
            document.getElementById('fpAll')?.addEventListener('change', function () { picks().forEach(c => c.checked = this.checked); sum(); });
            const bank = document.getElementById('fpBank'), row = document.getElementById('fpMethodRow');
            function method() { if (bank && row) row.style.display = bank.selectedOptions[0]?.dataset.cash === '1' ? 'none' : ''; }
            bank?.addEventListener('change', method); method(); sum();
        })();
    </script>
    @endsection
</x-sales-dashboard>

<x-sales-dashboard>

    @section('side_nav')
        @include('partials.receipt_side_nav')
    @endsection

    @section('content')
    @php
        $money = fn ($v) => number_format((float) $v, 2);
        $date = fn ($v, $f = 'd M Y') => $v ? \Carbon\Carbon::parse($v)->format($f) : '';
        $bank = fn ($id) => $id ? ($banks[$id] ?? 'Bank #' . $id) : '';
        $methods = array_values(array_filter([
            ['Cash', $receipt['cash_amount'] ?? 0, null, null, null],
            ['MoMo', $receipt['momo_amount'] ?? 0, $receipt['momo_transactin_id'] ?? null, null, $receipt['momo_to_bank_id'] ?? null],
            ['Cheque', $receipt['cheque_amount'] ?? 0, $receipt['cheque_reference'] ?? null, $receipt['cheque_bank'] ?? null, $receipt['cheque_to_bank_id'] ?? null],
            ['Bank transfer', $receipt['transfer_amount'] ?? 0, $receipt['transfer_reference'] ?? null, $receipt['transfer_bank'] ?? null, $receipt['transfer_to_bank_id'] ?? null],
            ['Other', $receipt['other_payment_amnt'] ?? 0, $receipt['other_payment_descri'] ?? null, null, null],
        ], fn ($m) => (float) $m[1] > 0));
        $deductions = array_values(array_filter([
            ['Withholding tax (WHT)', $receipt['wht_amount'] ?? 0],
            ['VAT withheld (7%)', $receipt['vat7_amount'] ?? 0],
        ], fn ($m) => (float) $m[1] > 0));
        $collection = $void->snapshot['collection'] ?? null;
        $before = collect($void->snapshot['bank_lines'] ?? []);
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <a href="{{ route('receipt.voided') }}" class="small"><i class="bx bx-chevron-left"></i> Voided receipts</a>
        <h3 class="text-primary mb-1 mt-2">
            Receipt FWSSR{{ $void->receipt_id }} <span class="badge bg-label-danger align-middle ms-1">Voided</span>
        </h3>
        <p class="text-muted mb-3">
            Voided on {{ $void->created_at->format('d M Y H:i') }}{{ $void->voidedBy ? ' by ' . $void->voidedBy->name : '' }}.
            The receipt no longer counts anywhere; below is what it recorded and what voiding it reversed.
        </p>

        @include('flash-messages')

        <div class="alert alert-warning"><strong>Reason:</strong> {{ $void->reason }}</div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">What the receipt recorded</h5></div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5 text-muted fw-normal">Client</dt>
                            <dd class="col-sm-7">{{ $void->client?->business_name ?: $void->client?->name ?: '—' }}</dd>
                            <dt class="col-sm-5 text-muted fw-normal">Receipt date</dt>
                            <dd class="col-sm-7">{{ $date($receipt['receipt_month'] ?? null) ?: '—' }}</dd>
                            @if(!empty($receipt['from']))
                                <dt class="col-sm-5 text-muted fw-normal">Received from</dt>
                                <dd class="col-sm-7">{{ $receipt['from'] }}</dd>
                            @endif
                            @if(!empty($receipt['description']))
                                <dt class="col-sm-5 text-muted fw-normal">Description</dt>
                                <dd class="col-sm-7">{{ $receipt['description'] }}</dd>
                            @endif
                            <dt class="col-sm-5 text-muted fw-normal">Money received</dt>
                            <dd class="col-sm-7 fw-semibold">GH₵ {{ $money($void->amount) }}</dd>
                            @if((float) ($receipt['unapplied_amount'] ?? 0) > 0)
                                <dt class="col-sm-5 text-muted fw-normal">Unapplied credit</dt>
                                <dd class="col-sm-7">GH₵ {{ $money($receipt['unapplied_amount']) }}</dd>
                            @endif
                            <dt class="col-sm-5 text-muted fw-normal">Recorded by</dt>
                            <dd class="col-sm-7">{{ $users[$receipt['user_id'] ?? 0] ?? '—' }}{{ !empty($receipt['created_at']) ? ', ' . $date($receipt['created_at'], 'd M Y H:i') : '' }}</dd>
                            @if(!empty($receipt['user_id1']) || !empty($receipt['coll_date']))
                                <dt class="col-sm-5 text-muted fw-normal">Collection approved</dt>
                                <dd class="col-sm-7">{{ $users[$receipt['user_id1'] ?? 0] ?? '' }} {{ $date($receipt['coll_date'] ?? null) }}</dd>
                            @endif
                            @if(!empty($receipt['user_id2']) || !empty($receipt['bran_date']))
                                <dt class="col-sm-5 text-muted fw-normal">Branch approved</dt>
                                <dd class="col-sm-7">{{ $users[$receipt['user_id2'] ?? 0] ?? '' }} {{ $date($receipt['bran_date'] ?? null) }}</dd>
                            @endif
                            @if(!empty($receipt['ho_status']))
                                <dt class="col-sm-5 text-muted fw-normal">Head office</dt>
                                <dd class="col-sm-7">{{ $receipt['ho_status'] }} {{ $date($receipt['ho_date'] ?? null) }}</dd>
                            @endif
                            @if(!empty($receipt['image']))
                                <dt class="col-sm-5 text-muted fw-normal">Attachment</dt>
                                <dd class="col-sm-7"><a href="{{ asset('storage/' . $receipt['image']) }}" target="_blank" rel="noopener">Open the scanned receipt</a></dd>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">How it was paid</h5></div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Method</th><th class="text-end">GH₵</th><th>Reference</th><th>Into</th></tr></thead>
                            <tbody>
                            @forelse($methods as [$name, $amount, $ref, $fromBank, $toBank])
                                <tr>
                                    <td>{{ $name }}</td>
                                    <td class="text-end">{{ $money($amount) }}</td>
                                    <td class="small">{{ $ref ?: '—' }}{{ $fromBank ? ' · ' . $fromBank : '' }}</td>
                                    <td class="small">{{ $bank($toBank) ?: ($name === 'Cash' ? 'Cash collection' : '—') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No money recorded.</td></tr>
                            @endforelse
                            @foreach($deductions as [$name, $amount])
                                <tr class="text-muted"><td>{{ $name }}</td><td class="text-end">{{ $money($amount) }}</td><td colspan="2" class="small">deducted by the client</td></tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($collection)
                        <div class="card-body small text-muted border-top">
                            Cash collection #{{ $collection['id'] ?? '' }} ({{ $collection['status'] ?? 'pending' }}):
                            GH₵ {{ $money($collection['total_amount'] ?? 0) }}{{ !empty($collection['user_id']) && isset($users[$collection['user_id']]) ? ', collected by ' . $users[$collection['user_id']] : '' }}.
                            It was removed when the receipt was voided (nothing of it had been deposited).
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Invoices it paid</h5></div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Invoice</th><th>Invoice date</th><th class="text-end">Settled by this receipt</th><th class="text-end">WHT</th><th>Invoice now</th></tr></thead>
                            <tbody>
                            @forelse($allocations as $a)
                                @php $inv = $invoices[$a['invoice_id']] ?? null; @endphp
                                <tr>
                                    <td>
                                        @if($inv)
                                            <a href="{{ route('invoice.show', $inv->id) }}">FWSSi{{ $a['invoice_id'] }}</a>
                                        @else
                                            FWSSi{{ $a['invoice_id'] }} <span class="text-muted small">(deleted)</span>
                                        @endif
                                    </td>
                                    <td>{{ $inv ? $date($inv->invoice_month) : '' }}</td>
                                    <td class="text-end">{{ $money($a['settled'] ?? 0) }}</td>
                                    <td class="text-end">{{ (float) ($a['wht_amount'] ?? 0) > 0 ? $money($a['wht_amount']) : '' }}</td>
                                    <td>
                                        @if($inv)
                                            <span class="badge {{ $inv->status === 'completed' ? 'bg-label-success' : 'bg-label-warning' }}">{{ $inv->status === 'completed' ? 'paid' : $inv->status }}</span>
                                            @if($inv->status !== 'completed') <span class="small text-muted">balance {{ $money($inv->balance) }}</span>@endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                @if(!empty($receipt['invoice_id']) && ($inv = $invoices[$receipt['invoice_id']] ?? null))
                                    <tr><td><a href="{{ route('invoice.show', $inv->id) }}">FWSSi{{ $inv->id }}</a></td><td>{{ $date($inv->invoice_month) }}</td><td class="text-end">—</td><td></td><td>{{ $inv->status }}</td></tr>
                                @else
                                    <tr><td colspan="5" class="text-muted">This receipt was not applied to any invoice (an advance or credit).</td></tr>
                                @endif
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-body small text-muted border-top py-2">Each invoice's balance was worked out again from the receipts that remain.</div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Bank lines</h5></div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Date</th><th>Account</th><th>Narration</th><th class="text-end">In</th><th class="text-end">Out</th></tr></thead>
                            <tbody>
                            @forelse($bankLines as $line)
                                <tr class="{{ $line->debit ? 'table-danger' : '' }}">
                                    <td class="text-nowrap">{{ $date($line->entry_date ?: $line->created_at) }}</td>
                                    <td>{{ $bank($line->bank_id) }}</td>
                                    <td class="small">{{ $line->narration }}</td>
                                    <td class="text-end">{{ $line->credit ? $money($line->credit) : '' }}</td>
                                    <td class="text-end">{{ $line->debit ? $money($line->debit) : '' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">{{ $before->isEmpty() ? 'Nothing from this receipt went straight into a bank account (cash goes through Bank Deposit).' : 'No bank lines are left for this receipt.' }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Client ledger</h5>
                        @if($void->client_id)
                            <a href="{{ url('clientStatement', $void->client_id) }}" class="small">Full statement</a>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Date</th><th>Entry</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
                            <tbody>
                            @forelse($ledger as $e)
                                <tr class="{{ $e->reverses_id ? 'table-danger' : '' }}">
                                    <td class="text-nowrap">{{ $e->entry_date?->format('d M Y') }}</td>
                                    <td class="small">{{ $e->description }}@if($e->details)<br><span class="text-muted">{{ $e->details }}</span>@endif</td>
                                    <td class="text-end">{{ $e->debit ? $money($e->debit) : '' }}</td>
                                    <td class="text-end">{{ $e->credit ? $money($e->credit) : '' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No ledger entries for this receipt.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-body small text-muted border-top py-2">Red rows are the reversals posted on the day it was voided; earlier statements stay as they were sent.</div>
                </div>
            </div>
        </div>
    </div>
    @endsection
</x-sales-dashboard>

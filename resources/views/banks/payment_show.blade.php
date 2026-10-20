<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <style>.mv-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }</style>
    @endsection

    @section('content')
    @php $p = $payment; @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <a href="{{ route('money.payments', ['month' => $p->payment_date->format('Y-m')]) }}" class="small"><i class="bx bx-arrow-back"></i> Payments</a>
        <h3 class="text-primary mt-1 mb-1">GH₵ {{ number_format($p->amount, 2) }} to {{ $p->payee }}
            <span class="badge {{ ['paid' => 'bg-label-success', 'issued' => 'bg-label-warning', 'cleared' => 'bg-label-success', 'cancelled' => 'bg-label-secondary'][$p->status] ?? 'bg-label-secondary' }} align-middle fs-6">{{ \App\Models\AccountPayment::STATUSES[$p->status] ?? $p->status }}</span></h3>
        <div class="text-muted small mb-3">{{ $p->payment_date->format('l d M Y') }} · {{ $p->category_label }} · recorded by {{ $p->user?->name }} {{ $p->created_at?->format('d M Y H:i') }}</div>

        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        @if($p->status === 'cancelled')<div class="alert alert-secondary">Cancelled {{ $p->cancelled_at?->format('d M Y H:i') }} by {{ $p->cancelledBy?->name }}: {{ $p->cancel_reason }}</div>@endif

        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card h-100"><div class="card-body">
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted fw-normal">Paid from</dt><dd class="col-7"><a href="{{ route('banks.show', $p->bank_id) }}">{{ $p->bank?->name }}</a></dd>
                        <dt class="col-5 text-muted fw-normal">How</dt><dd class="col-7">{{ \App\Models\AccountPayment::METHODS[$p->method] ?? $p->method }}@if($p->reference) {{ $p->reference }}@endif</dd>
                        <dt class="col-5 text-muted fw-normal">Paid to</dt><dd class="col-7">{{ $p->payee }}</dd>
                        <dt class="col-5 text-muted fw-normal">What for</dt><dd class="col-7">{{ $p->category_label }}</dd>
                        @if($p->description)<dt class="col-5 text-muted fw-normal">Description</dt><dd class="col-7">{{ $p->description }}</dd>@endif
                        @if($p->cleared_date)<dt class="col-5 text-muted fw-normal">Cleared</dt><dd class="col-7">{{ $p->cleared_date->format('d M Y') }}</dd>@endif
                    </dl>
                    @if($p->status === 'issued')
                    <form method="POST" action="{{ route('money.payments.clear', $p) }}" class="d-flex gap-2 mt-3">
                        @csrf
                        <input type="date" name="cleared_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" style="max-width: 11rem">
                        <button class="btn btn-sm btn-outline-success">Mark cheque cleared</button>
                    </form>
                    @endif
                </div></div>
            </div>
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-body pb-0"><h6>On the account</h6></div>
                    <div class="table-responsive"><table class="table table-sm mb-0">
                        <thead><tr><th>Date</th><th>Details</th><th class="mv-num">In</th><th class="mv-num">Out</th><th class="mv-num">Balance after</th></tr></thead>
                        <tbody>
                        @foreach($p->bankLines as $l)
                            <tr><td class="text-nowrap">{{ $l->entry_date?->format('d M Y') }}</td><td class="small">{{ $l->narration }}</td>
                                <td class="mv-num text-success">{{ $l->credit > 0 ? number_format($l->credit, 2) : '' }}</td><td class="mv-num text-danger">{{ $l->debit > 0 ? number_format($l->debit, 2) : '' }}</td><td class="mv-num">{{ number_format($l->balance, 2) }}</td></tr>
                        @endforeach
                        </tbody>
                    </table></div>
                </div>
            </div>
        </div>

        @if($p->expenses->isNotEmpty())
        <div class="card mt-3">
            <div class="card-body pb-0"><h6>Expenses paid</h6></div>
            <div class="table-responsive"><table class="table table-sm mb-0">
                <thead><tr><th>#</th><th>Date</th><th>Office</th><th>Type</th><th>Description</th><th class="mv-num">Amount</th></tr></thead>
                <tbody>
                @foreach($p->expenses as $e)
                    <tr><td>{{ $e->id }}</td><td>{{ $e->expense_date?->format('d M Y') }}</td><td>{{ $e->field?->name }}</td><td>{{ $e->type?->name }}</td><td class="small">{{ $e->description }}</td><td class="mv-num">{{ number_format($e->amount, 2) }}</td></tr>
                @endforeach
                </tbody>
            </table></div>
        </div>
        @endif

        @if($canCorrect && $p->status !== 'cancelled')
        <div class="card border-danger mt-3"><div class="card-body">
            <h6 class="text-danger mb-1">Cancel this payment</h6>
            <p class="small text-muted mb-2">Entered in error, or the cheque was stopped / never presented. The amount goes back on {{ $p->bank?->name }} (a reversal line; nothing is deleted)@if($p->expenses->isNotEmpty()) and its expenses go back to "awaiting payment"@endif.</p>
            <form method="POST" action="{{ route('money.payments.cancel', $p) }}" class="d-flex flex-wrap gap-2" onsubmit="return confirm('Cancel this payment?');">
                @csrf
                {!! \App\Support\SubmissionGuard::field() !!}
                <input name="cancel_reason" class="form-control form-control-sm" style="max-width: 28rem" maxlength="300" placeholder="Reason" required>
                <button class="btn btn-sm btn-danger" data-loading>Cancel payment</button>
            </form>
        </div></div>
        @endif
    </div>
    @endsection
</x-sales-dashboard>

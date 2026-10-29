<x-sales-dashboard>

    @section('content')
    @php
        $status = $subscription->status();
        $badge = [
            'active' => ['success', 'Paid up'],
            'due_soon' => ['info', 'Due soon'],
            'overdue' => ['warning', 'Overdue'],
            'locked' => ['danger', 'Read-only (overdue)'],
        ][$status];
        $fee = (float) config('subscription.monthly_fee');
        $currency = config('subscription.currency', 'GHS');
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <h3 class="text-primary mb-1"><i class="bx bx-credit-card me-1"></i> Subscription</h3>
        <div class="text-muted small mb-3">
            Monthly fee for using the system. If a payment is more than {{ config('subscription.grace_days') }} days late,
            the system becomes read-only (you can view and print, but not save) until it is paid.
        </div>

        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

        <div class="row g-3 mb-3">
            <div class="col-md-4"><div class="card h-100"><div class="card-body py-3">
                <div class="small text-muted">Status</div>
                <div class="fs-5 fw-bold"><span class="badge bg-label-{{ $badge[0] }}">{{ $badge[1] }}</span></div>
                @unless($subscription->enforced())<div class="small text-muted mt-1">Not enforced (switched off in settings).</div>@endunless
            </div></div></div>
            <div class="col-md-4"><div class="card h-100"><div class="card-body py-3">
                <div class="small text-muted">Next payment due</div>
                <div class="fs-5 fw-bold">{{ $subscription->dueDate()->format('j M Y') }}</div>
                <div class="small text-muted">
                    @if($subscription->daysUntilDue() > 0) in {{ $subscription->daysUntilDue() }} {{ \Illuminate\Support\Str::plural('day', $subscription->daysUntilDue()) }}
                    @elseif($subscription->daysUntilDue() === 0) today
                    @else {{ abs($subscription->daysUntilDue()) }} {{ \Illuminate\Support\Str::plural('day', abs($subscription->daysUntilDue())) }} ago
                    @endif
                </div>
            </div></div></div>
            <div class="col-md-4"><div class="card h-100"><div class="card-body py-3">
                <div class="small text-muted">Read-only from (if unpaid)</div>
                <div class="fs-5 fw-bold">{{ $subscription->lockDate()->format('j M Y') }}</div>
                <div class="small text-muted">@if($fee > 0) Monthly fee {{ $currency }} {{ number_format($fee, 2) }} @endif</div>
            </div></div></div>
        </div>

        <div class="card mb-3"><div class="card-body">
            <h5 class="mb-2">Pay</h5>
            @if($onlinePayment)
                <form method="POST" action="{{ url('billing/pay') }}" class="row g-2 align-items-end" data-readonly-allow>
                    @csrf
                    <div class="col-auto">
                        <label class="form-label small mb-1" for="months">Months</label>
                        <select name="months" id="months" class="form-select form-select-sm">
                            @foreach([1, 2, 3, 6, 12] as $m)
                                <option value="{{ $m }}">{{ $m }} {{ \Illuminate\Support\Str::plural('month', $m) }} - {{ $currency }} {{ number_format($fee * $m, 2) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary btn-sm" data-loading><i class="bx bx-mobile-alt me-1"></i> Pay with Hubtel (MoMo / card)</button>
                    </div>
                </form>
                <div class="small text-muted mt-2">You will be taken to Hubtel's secure page. The system opens again as soon as Hubtel confirms the payment.</div>
            @else
                <div class="text-muted">
                    Online payment is coming soon. For now, please pay the system provider directly;
                    your renewal is then recorded for you and the due date moves forward.
                </div>
            @endif
        </div></div>

        <div class="card"><div class="card-body">
            <h5 class="mb-2">Payment history</h5>
            @if($subscription->history()->isEmpty())
                <div class="text-muted">No payments recorded yet.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Paid on</th><th class="text-end">Months</th><th>How</th><th>Reference</th></tr></thead>
                        <tbody>
                        @foreach($subscription->history()->reverse() as $row)
                            <tr>
                                <td>{{ $row['paid_on']?->format('j M Y') ?? '-' }}</td>
                                <td class="text-end">{{ $row['months'] }}</td>
                                <td>{{ $row['source'] === 'manual' ? 'Recorded by provider' : 'Hubtel' }}</td>
                                <td>{{ $row['reference'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div></div>
    </div>
    @endsection

</x-sales-dashboard>

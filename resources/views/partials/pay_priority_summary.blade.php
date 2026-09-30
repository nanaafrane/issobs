{{--
    Payment priority summary for a payroll screen.
    $rows    : salaries shown in the table (pending + approved)
    $held    : salaries on hold / rejected for the same screen
    $holdUrl : link to this screen's hold view (optional)
    Chips filter the table's PAY column (see partials.pay_priority_script).
--}}
@php
    use App\Support\PayPriority;
    $pp = [];
    foreach ([PayPriority::URGENT => 'urgent', PayPriority::PRIORITY => 'priority'] as $lvl => $key) {
        $inLevel = $rows->where('pay_priority', $lvl);
        $pp[$key] = [
            'level' => $lvl,
            'total' => $inLevel->count(),
            'unpaid' => $inLevel->where('payment_status', '!=', 'approved')->count(),
            'unpaid_net' => $inLevel->where('payment_status', '!=', 'approved')->sum('net_salary'),
            'held' => $held->where('pay_priority', $lvl)->count(),
        ];
    }
@endphp
@if($pp['urgent']['total'] || $pp['priority']['total'] || $pp['urgent']['held'] || $pp['priority']['held'])
<div class="card mb-3">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="fw-semibold me-1">Pay in this order:</span>
            @if($pp['urgent']['total'])
                <button type="button" class="btn btn-sm btn-outline-danger js-pay-chip" data-pay="pay-urgent" aria-pressed="false">
                    <i class="bx bxs-bolt"></i> Pay first: <strong>{{ $pp['urgent']['unpaid'] }}</strong> unpaid of {{ $pp['urgent']['total'] }}
                    @if($pp['urgent']['unpaid']) (GH&#x20B5; {{ number_format($pp['urgent']['unpaid_net'], 2) }}) @endif
                </button>
            @endif
            @if($pp['priority']['total'])
                <button type="button" class="btn btn-sm btn-outline-warning js-pay-chip" data-pay="pay-priority" aria-pressed="false">
                    <i class="bx bx-time-five"></i> Pay early: <strong>{{ $pp['priority']['unpaid'] }}</strong> unpaid of {{ $pp['priority']['total'] }}
                    @if($pp['priority']['unpaid']) (GH&#x20B5; {{ number_format($pp['priority']['unpaid_net'], 2) }}) @endif
                </button>
            @endif
            <button type="button" class="btn btn-sm btn-link js-pay-chip-clear d-none">Show everyone</button>
        </div>

        @foreach(['urgent' => ['danger', 'pay-first'], 'priority' => ['warning', 'pay-early']] as $key => [$color, $word])
            @if($pp[$key]['held'])
                <div class="alert alert-{{ $color }} py-2 mb-0 mt-2 small">
                    <i class="bx bx-error"></i>
                    {{ $pp[$key]['held'] }} {{ $word }} {{ \Illuminate\Support\Str::plural('salary', $pp[$key]['held']) }}
                    {{ $pp[$key]['held'] === 1 ? 'is' : 'are' }} on hold or rejected and will not be paid.
                    @if(!empty($holdUrl)) <a href="{{ $holdUrl }}" class="alert-link">Review held salaries</a> @endif
                </div>
            @endif
        @endforeach
    </div>
</div>
@endif

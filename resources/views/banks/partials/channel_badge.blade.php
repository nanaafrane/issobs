{{-- What moved this ledger line, as a small coloured label. @include('banks.partials.channel_badge', ['t' => $line]) --}}
@php
    $badge = [
        'cheque' => ['Cheque received', 'bg-label-success'],
        'transfer' => ['Transfer received', 'bg-label-success'],
        'deposit' => ['Deposit', 'bg-label-primary'],
        'momo_charge' => ['MoMo charge', 'bg-label-danger'],
        'transfer_in' => ['Moved in', 'bg-label-info'],
        'transfer_out' => ['Moved out', 'bg-label-info'],
        'payment' => ['Payment', 'bg-label-warning'],
        'adjustment' => ['Adjustment', 'bg-label-secondary'],
        'loan_in' => ['Funding (to repay)', 'bg-label-dark'],
        'loan_out' => ['Loan repayment', 'bg-label-dark'],
        'expense' => ['Expense', 'bg-label-warning'],
    ][$t->channel] ?? [$t->channel ?: 'Other', 'bg-label-secondary'];
@endphp
<span class="badge {{ $badge[1] }} me-1">{{ $badge[0] }}</span>

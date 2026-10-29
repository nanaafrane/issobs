{{--
    Subscription banner on every page.
    Finance Manager / Director (config subscription.billing_roles): due soon, overdue
    and read-only messages with dates and the Pay button.
    Everyone else: nothing until the system is locked, then "SYSTEM INACTIVE - READ ONLY".
--}}
@php
    $sub = app(\App\Support\Subscription::class);
    $subStatus = $sub->status();
    $subBypass = $sub->bypasses(auth()->user());
    $subManager = $sub->canManage(auth()->user());
@endphp

@if (! $subManager)
    @if ($subStatus === \App\Support\Subscription::LOCKED)
        <div class="alert alert-danger text-center fw-bold mx-3 mt-3 mb-0" role="alert" id="subscription-banner">
            <i class="bx bx-lock-alt me-1"></i> SYSTEM INACTIVE &ndash; READ ONLY
            @if ($subBypass) <div class="small fw-normal">(Your login is exempt, so you can still make changes.)</div> @endif
        </div>
    @endif
@elseif ($subStatus === \App\Support\Subscription::LOCKED)
    <div class="alert alert-danger d-flex flex-wrap align-items-center justify-content-between gap-2 mx-3 mt-3 mb-0" role="alert" id="subscription-banner">
        <div>
            <i class="bx bx-lock-alt me-1"></i>
            <strong>Read-only:</strong> the subscription was due on {{ $sub->dueDate()->format('j M Y') }} and is more than {{ config('subscription.grace_days') }} days overdue.
            You can view and print, but saving, creating, editing and deleting are switched off until payment is made.
            @if ($subBypass) <em>(Your login is exempt, so you can still make changes.)</em> @endif
        </div>
        <a href="{{ url('billing') }}" class="btn btn-sm btn-light text-nowrap">Pay / renew</a>
    </div>
@elseif ($subStatus === \App\Support\Subscription::OVERDUE)
    @php $d = $sub->daysUntilLock(); @endphp
    <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 mx-3 mt-3 mb-0" role="alert" id="subscription-banner">
        <div>
            <i class="bx bx-error me-1"></i>
            <strong>Subscription overdue</strong> since {{ $sub->dueDate()->format('j M Y') }}.
            The system becomes read-only on {{ $sub->lockDate()->format('j M Y') }}
            ({{ $d }} {{ \Illuminate\Support\Str::plural('day', $d) }} left) unless payment is made.
        </div>
        <a href="{{ url('billing') }}" class="btn btn-sm btn-warning text-nowrap">Pay now</a>
    </div>
@elseif ($subStatus === \App\Support\Subscription::DUE_SOON)
    @php $d = $sub->daysUntilDue(); @endphp
    <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2 mx-3 mt-3 mb-0" role="alert" id="subscription-banner">
        <div>
            <i class="bx bx-calendar me-1"></i>
            Subscription due {{ $d === 0 ? 'today' : 'in '.$d.' '.\Illuminate\Support\Str::plural('day', $d) }} ({{ $sub->dueDate()->format('j M Y') }}).
        </div>
        <a href="{{ url('billing') }}" class="btn btn-sm btn-outline-info text-nowrap">View</a>
    </div>
@endif

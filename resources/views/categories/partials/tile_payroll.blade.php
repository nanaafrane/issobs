{{--
    Salary figures for one category tile ($t = CategoryPayroll::summary()['tiles'][key]).
    Net = pending + approved (same as the payroll master); held shown separately.
--}}
@php $money = fn ($v) => 'GH&#x20B5; ' . number_format((float) $v, 2); @endphp
<div class="tile-pay small mt-2 pt-2 border-top">
    @if(! $hasPayroll)
        <div class="text-muted fst-italic">No salaries generated</div>
    @elseif($canViewSalary)
        <div><span class="text-muted">Net</span> <strong>{!! $money($t['net']) !!}</strong> <span class="text-muted">&middot; {{ $t['staff'] }} staff</span></div>
        @if($t['priority_staff'])
            <div title="Pay first {{ strip_tags($money($t['urgent_net'])) }} ({{ $t['urgent_staff'] }}) &middot; Pay early {{ strip_tags($money($t['early_net'])) }} ({{ $t['early_staff'] }})" data-bs-toggle="tooltip">
                <i class="bx bxs-bolt text-danger"></i><span class="text-muted">Priority</span>
                <strong>{!! $money($t['priority_net']) !!}</strong> <span class="text-muted">&middot; {{ $t['priority_staff'] }}</span>
            </div>
        @else
            <div class="text-muted">Priority: none</div>
        @endif
        @if($t['held_staff'])
            <div class="text-danger">+ {!! $money($t['held_net']) !!} on hold ({{ $t['held_staff'] }})</div>
        @endif
    @else
        {{-- No salary amounts for this user: staff and priority counts only. --}}
        <div><strong>{{ $t['staff'] }}</strong> <span class="text-muted">staff</span></div>
        <div><i class="bx bxs-bolt text-danger"></i><strong>{{ $t['priority_staff'] }}</strong> <span class="text-muted">priority</span></div>
        @if($t['held_staff'])<div class="text-danger">{{ $t['held_staff'] }} on hold</div>@endif
    @endif
</div>

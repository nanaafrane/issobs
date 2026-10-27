{{-- Approval state + approve / reject buttons for a fuel entry or maintenance record.
     @include('fleet._approval', ['r' => $row, 'approve' => route(...), 'reject' => route(...)]) --}}
@php
    $stage = $r->currentStage();
    $canAct = $stage && $r->canActOnStage(Auth::user(), $stage) && ! $r->isLockedForPay();
    $cls = $r->isRejected() ? 'bg-label-danger' : ($r->isFullyApproved() ? 'bg-label-success' : 'bg-label-warning');
@endphp
<span class="badge {{ $cls }}" title="@foreach($r->approvalTimeline() as $s){{ $s['label'] }}: {{ $s['status'] }}{{ $s['date'] ? ' ' . $s['date']->format('d M H:i') : '' }}&#10;@endforeach">{{ $r->stageLabel() }}</span>
@if($canAct)
    <div class="d-inline-flex gap-1 ms-1">
        <form method="POST" action="{{ $approve }}">@csrf<button class="btn btn-sm btn-outline-success py-0 px-2" title="Approve ({{ \App\Models\FuelEntry::$STAGE_LABELS[$stage] }})"><i class="bx bx-check"></i></button></form>
        <form method="POST" action="{{ $reject }}" onsubmit="return confirm('Reject this entry?');">@csrf<button class="btn btn-sm btn-outline-danger py-0 px-2" title="Reject"><i class="bx bx-x"></i></button></form>
    </div>
@endif

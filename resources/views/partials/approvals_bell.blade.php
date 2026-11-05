{{-- Top-bar bell: what is waiting for the signed-in user's approval. Data: AppServiceProvider view composer. --}}
@use('App\Support\Approvals\ApprovalInbox')
@if(($approvalSummary ?? null) && $approvalSummary['can'])
  @php
    $apTotal = $approvalSummary['total'];
    $apItems = array_filter($approvalSummary['items'], fn ($i) => $i['count'] > 0);
  @endphp
  <li class="nav-item dropdown me-3 me-xl-2">
    <a class="nav-link dropdown-toggle hide-arrow position-relative px-2" href="javascript:void(0);" data-bs-toggle="dropdown"
       data-bs-auto-close="outside" aria-expanded="false"
       aria-label="{{ $apTotal ? $apTotal . ' ' . \Illuminate\Support\Str::plural('approval', $apTotal) . ' waiting for you' : 'No approvals waiting' }}">
      <i class="icon-base bx bx-bell icon-md"></i>
      @if($apTotal)
        <span class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle"
              style="font-size:.65rem; padding:.25em .45em;">{{ $apTotal > 99 ? '99+' : $apTotal }}</span>
      @endif
    </a>
    <div class="dropdown-menu dropdown-menu-end p-0" style="width: 22rem; max-width: calc(100vw - 2rem);">
      <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
        <h6 class="mb-0">Waiting for you</h6>
        @if($apTotal)<span class="badge bg-label-danger rounded-pill">{{ $apTotal }}</span>@endif
      </div>
      @forelse($apItems as $item)
        @php($days = ApprovalInbox::ageDays($item['oldest']))
        @php($tone = ApprovalInbox::ageTone($days))
        <a class="dropdown-item d-flex justify-content-between align-items-center gap-3 py-2 border-bottom" href="{{ $item['url'] }}" style="white-space: normal;">
          <span>
            <span class="fw-semibold d-block">{{ $item['label'] }}</span>
            <small class="text-body-secondary">{{ $item['stage'] }}@if($days !== null) · oldest <span class="{{ $tone === 'bad' ? 'text-danger fw-semibold' : ($tone === 'warn' ? 'text-warning fw-semibold' : '') }}">{{ ApprovalInbox::ageLabel($days) }}</span>@endif</small>
          </span>
          <span class="badge bg-label-primary rounded-pill">{{ $item['count'] }}</span>
        </a>
      @empty
        <div class="px-3 py-4 text-center text-body-secondary small">Nothing is waiting for you.</div>
      @endforelse
      <a class="dropdown-item text-center fw-semibold py-2" href="{{ route('approvals.index') }}">Open all approvals</a>
    </div>
  </li>
@endif

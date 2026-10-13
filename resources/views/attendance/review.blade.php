<x-hr-dashboard>

  @section('css')
    @include('attendance._styles')
  @endsection

  @section('side_nav')
    @include('partials.payroll_side_nav')
  @endsection

  @section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
      @include('attendance._flash')

      <a href="{{ route('attendance.index') }}" class="small"><i class="bx bx-chevron-left"></i> All clients</a>
      <h4 class="mb-1 mt-1">Sign-ins to review</h4>
      <p class="text-muted small mb-3">These QR sign-ins failed a check and do not count yet. <strong>Approve</strong> makes the shift present; <strong>Reject</strong> marks it absent.
          Tick <em>Trust this phone</em> when a guard has changed phones, so their next sign-ins go straight through.</p>

      <div class="card">
          <div class="table-responsive">
              <table class="table table-sm mb-0">
                  <thead class="table-light">
                      <tr><th>Guard</th><th>Client</th><th>Shift</th><th>Signed in</th><th>Why it's held</th><th class="text-end">Decision</th></tr>
                  </thead>
                  <tbody>
                  @forelse($items as $a)
                      <tr>
                          <td><div class="fw-semibold">{{ $a->employee->name ?? '—' }}</div>
                              <div class="small text-muted">FWSS {{ $a->employee_id }} @if($a->employee?->phone_number)· {{ $a->employee->phone_number }}@endif</div></td>
                          <td><a href="{{ route('attendance.show', ['client' => $a->client_id, 'month' => $a->work_date->format('Y-m')]) }}">{{ $a->client->business_name ?? $a->client->name ?? '' }}</a>
                              @if($a->client?->branch)<div class="small text-muted">{{ $a->client->branch }}</div>@endif</td>
                          <td>{{ ucfirst($a->shift) }}<div class="small text-muted">{{ $a->work_date->format('D j M Y') }}</div></td>
                          <td>{{ $a->checked_in_at?->format('D j M, H:i') }}
                              @if($a->latitude !== null)<div class="small"><a target="_blank" rel="noopener" href="https://www.google.com/maps?q={{ $a->latitude }},{{ $a->longitude }}">Where{{ $a->distance_m !== null ? ' (' . $a->distance_m . ' m from site)' : '' }}</a></div>@endif</td>
                          <td class="small">
                              @foreach((array) $a->flags as $f)<div class="text-warning"><i class="bx bx-error"></i> {{ $f }}</div>@endforeach
                              @if($a->note)<div class="text-muted">{{ $a->note }}</div>@endif
                          </td>
                          <td class="text-end" style="min-width: 15rem">
                              <form method="post" action="{{ route('attendance.decide', $a->id) }}" class="d-inline-flex flex-wrap gap-1 justify-content-end align-items-center">
                                  @csrf
                                  @if($a->device_id)
                                  <label class="small me-1"><input type="checkbox" name="trust_phone" value="1" class="form-check-input"> Trust this phone</label>
                                  @endif
                                  <button name="decision" value="approve" class="btn btn-sm btn-success">Approve</button>
                                  <button name="decision" value="reject" class="btn btn-sm btn-outline-danger" onclick="return confirm('Mark this shift absent?')">Reject</button>
                              </form>
                          </td>
                      </tr>
                  @empty
                      <tr><td colspan="6" class="text-center text-muted py-4">Nothing to review.</td></tr>
                  @endforelse
                  </tbody>
              </table>
          </div>
      </div>
      <div class="mt-3">{{ $items->links() }}</div>
  </div>
  @endsection
</x-hr-dashboard>

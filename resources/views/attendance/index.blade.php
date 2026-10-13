<x-hr-dashboard>

  @section('css')
    @include('attendance._styles')
  @endsection

  @section('side_nav')
    @include('partials.payroll_side_nav')
  @endsection

  @section('content')
  @php
      $tot = [
          'required' => $rows->sum('required'), 'posted' => $rows->sum('posted'), 'shortage' => $rows->sum('shortage'),
          'uncovered' => $rows->sum(fn ($r) => (int) $r['uncovered_guard_days']), 'absent' => $rows->sum('absent'),
          'noPlan' => $rows->whereNull('plan')->count(),
      ];
  @endphp
  <div class="container-xxl flex-grow-1 container-p-y">
      @include('attendance._flash')

      <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
          <div>
              <h4 class="mb-1">Guard attendance</h4>
              <div class="text-muted small">Each client's shift plan, the guards posted there, and how {{ $month->format('F Y') }} is going.</div>
          </div>
          <div class="d-flex gap-2 align-items-end">
              <form method="get" class="d-flex gap-2 align-items-end">
                  <div>
                      <label class="form-label small mb-1" for="month">Month</label>
                      <input type="month" id="month" name="month" class="form-control form-control-sm" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()">
                  </div>
              </form>
              <a href="{{ route('attendance.review') }}" class="btn btn-sm {{ $pending ? 'btn-warning' : 'btn-outline-secondary' }}">
                  <i class="bx bx-error-circle"></i> Review sign-ins @if($pending)<span class="badge bg-white text-warning ms-1">{{ $pending }}</span>@endif
              </a>
          </div>
      </div>

      <div class="row g-3 mb-4">
          <div class="col-6 col-md-3"><div class="att-metric"><div class="k">Guards required</div><div class="v">{{ $tot['required'] }}</div></div></div>
          <div class="col-6 col-md-3"><div class="att-metric"><div class="k">Guards posted</div><div class="v">{{ $tot['posted'] }}</div></div></div>
          <div class="col-6 col-md-3"><div class="att-metric {{ $tot['shortage'] ? 'bad' : '' }}"><div class="k">Total shortages</div><div class="v">{{ $tot['shortage'] ?: 'Nil' }}</div></div></div>
          <div class="col-6 col-md-3"><div class="att-metric {{ $tot['uncovered'] ? 'warn' : '' }}"><div class="k">Uncovered guard-shifts</div><div class="v">{{ number_format($tot['uncovered']) }}</div></div></div>
      </div>

      @if($tot['noPlan'])
          <div class="alert alert-info py-2 small">{{ $tot['noPlan'] }} client(s) have no shift plan yet, so shortages can't be worked out for them. Open the client and set how many day and night guards they need.</div>
      @endif

      <div class="card">
          <div class="table-responsive">
              <table class="table table-sm table-hover mb-0 att-overview">
                  <thead class="table-light">
                      <tr>
                          <th>Client</th>
                          <th>Field</th>
                          <th class="text-center">Plan</th>
                          <th class="text-end">Required<br><span class="fw-normal text-muted">day/night</span></th>
                          <th class="text-end">Posted<br><span class="fw-normal text-muted">day/night/rot.</span></th>
                          <th class="text-end">Shortage</th>
                          <th class="text-end" title="Present guard-shifts this month">Worked</th>
                          <th class="text-end">Absent</th>
                          <th class="text-end" title="Shift slots in the plan nobody was present for, up to today">Uncovered</th>
                          <th class="text-center">QR</th>
                      </tr>
                  </thead>
                  <tbody>
                  @forelse($rows as $r)
                      @php($c = $r['client'])
                      <tr>
                          <td>
                              <a href="{{ route('attendance.show', ['client' => $c->id, 'month' => $month->format('Y-m')]) }}" class="fw-semibold">{{ $c->business_name ?: $c->name }}</a>
                              @if($c->branch)<div class="small text-muted">{{ $c->branch }}</div>@endif
                          </td>
                          <td class="small">{{ $c->field->name ?? '' }}</td>
                          <td class="text-center small">{{ $r['plan'] ? $r['plan']->patternLabel() : '' }}@unless($r['plan'])<span class="badge bg-label-secondary">Not set</span>@endunless</td>
                          <td class="text-end">{{ $r['plan'] ? $r['required_day'] . ' / ' . $r['required_night'] : '' }}</td>
                          <td class="text-end">
                              {{ $r['posted_by_shift']['day'] }} / {{ $r['posted_by_shift']['night'] }} / {{ $r['posted_by_shift']['rotate'] }}
                              @if($r['posted_by_shift']['unset'])<span class="badge bg-label-warning ms-1" title="Guards posted here with no shift set">+{{ $r['posted_by_shift']['unset'] }} no shift</span>@endif
                          </td>
                          <td class="text-end">@if($r['plan'])<span class="{{ $r['shortage'] ? 'text-danger fw-semibold' : 'text-success' }}">{{ $r['shortage'] ?: 'Nil' }}</span>@endif</td>
                          <td class="text-end">{{ $r['worked'] }}</td>
                          <td class="text-end">{{ $r['absent'] ?: '' }}</td>
                          <td class="text-end">
                              @if($r['uncovered_guard_days'] !== null)
                                  <span class="{{ $r['uncovered_guard_days'] ? 'text-warning fw-semibold' : 'text-success' }}">{{ $r['uncovered_guard_days'] }}</span>
                              @endif
                              @if($r['pending'])<span class="badge bg-warning ms-1" title="Sign-ins waiting for review">{{ $r['pending'] }} ?</span>@endif
                          </td>
                          <td class="text-center">
                              @if($r['has_qr'])<i class="bx bx-qr text-success" title="QR poster issued"></i>@else<span class="text-muted small">none</span>@endif
                              @unless($r['has_site'])<i class="bx bx-map text-muted" title="Site location not set, distance not checked"></i>@endunless
                          </td>
                      </tr>
                  @empty
                      <tr><td colspan="10" class="text-center text-muted py-4">No active clients for your field.</td></tr>
                  @endforelse
                  </tbody>
              </table>
          </div>
      </div>
  </div>
  @endsection
</x-hr-dashboard>

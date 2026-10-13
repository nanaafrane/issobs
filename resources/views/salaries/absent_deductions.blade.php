<x-hr-dashboard>

  @section('css')
    <style>
        .ad-metric { background: #fff; border: 1px solid #e7e7ef; border-radius: .5rem; padding: .7rem .9rem; height: 100%; }
        .ad-metric .k { font-size: .75rem; text-transform: uppercase; letter-spacing: .03em; color: #8592a3; }
        .ad-metric .v { font-size: 1.35rem; font-weight: 600; color: #384551; }
        .ad-table td, .ad-table th { vertical-align: middle; white-space: nowrap; }
        .ad-table tr.paid td { opacity: .55; }
        .ad-table td.client { max-width: 170px; overflow: hidden; text-overflow: ellipsis; }
        .ad-rule { background: #f5f5f9; border-radius: .5rem; padding: .6rem .9rem; font-size: .9rem; }
    </style>
  @endsection

  @section('side_nav')
    @include('partials.payroll_side_nav')
  @endsection

  @section('content')
  @php
      $m = $month->format('Y-m');
      $toApply = $lines->filter(fn ($l) => $l['editable'] && $l['differs']);
      $money = fn ($v) => $v === null ? '—' : number_format((float) $v, 2);
      $pendingGuards = $lines->filter(fn ($l) => $l['pending_days'] > 0)->count();
  @endphp
  <div class="container-xxl flex-grow-1 container-p-y">
      @include('attendance._flash')

      <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
          <div>
              <h4 class="mb-1">Absent deductions from attendance</h4>
              <div class="text-muted small">Days marked <strong>Absent</strong> on the guard attendance sheet × the daily rate, for {{ $month->format('F Y') }} salaries.</div>
          </div>
          <form method="get" class="d-flex gap-2 align-items-end">
              <div><label class="form-label small mb-1">Salary month</label>
                  <input type="month" name="month" class="form-control form-control-sm" value="{{ $m }}" onchange="this.form.submit()"></div>
              <div><label class="form-label small mb-1">Field</label>
                  <select name="field_id" class="form-select form-select-sm" onchange="this.form.submit()">
                      <option value="">All fields</option>
                      @foreach($fields as $id => $name)<option value="{{ $id }}" @selected($fieldId == $id)>{{ $name }}</option>@endforeach
                  </select></div>
          </form>
      </div>

      <div class="card mb-4">
          <div class="card-body">
              <form method="post" action="{{ route('absent-deductions.settings') }}" class="row g-3 align-items-end">
                  @csrf
                  <div class="col-12">
                      <div class="ad-rule">
                          <strong>Daily rate</strong> = {{ \App\Support\Attendance\AbsentDeduction::BASES[$settings['basis']] }}
                          ÷ {{ \App\Support\Attendance\AbsentDeduction::DIVISORS[$settings['divisor']] }}.
                          Deduction = absent days × daily rate, never more than that pay.
                          @if($settingsBy)<span class="text-muted">Set by {{ $settingsBy }}, {{ \Carbon\Carbon::parse($settings['updated_at'])->format('j M Y') }}.</span>@else<span class="text-muted">(default)</span>@endif
                      </div>
                  </div>
                  <div class="col-md-3">
                      <label class="form-label small mb-1">Pay a day is worked out from</label>
                      <select name="basis" class="form-select form-select-sm">
                          @foreach(\App\Support\Attendance\AbsentDeduction::BASES as $k => $label)<option value="{{ $k }}" @selected($settings['basis'] === $k)>{{ $label }}</option>@endforeach
                      </select>
                  </div>
                  <div class="col-md-3">
                      <label class="form-label small mb-1">Divided by</label>
                      <select name="divisor" class="form-select form-select-sm">
                          @foreach(\App\Support\Attendance\AbsentDeduction::DIVISORS as $k => $label)<option value="{{ $k }}" @selected($settings['divisor'] === (string) $k)>{{ $label }}</option>@endforeach
                      </select>
                  </div>
                  <div class="col-md-4">
                      <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="auto_on_create" value="1" id="auto" @checked($settings['auto_on_create'])>
                          <label class="form-check-label small" for="auto">Fill the absent deduction automatically when a month's salaries are added</label>
                      </div>
                  </div>
                  <div class="col-md-2 text-md-end"><button class="btn btn-sm btn-outline-primary">Save rule</button></div>
              </form>
          </div>
      </div>

      <div class="row g-3 mb-3">
          <div class="col-6 col-md-3"><div class="ad-metric"><div class="k">Guards on attendance</div><div class="v">{{ $lines->count() }}</div></div></div>
          <div class="col-6 col-md-3"><div class="ad-metric"><div class="k">Absent days</div><div class="v">{{ $lines->sum('absent_days') }}</div></div></div>
          <div class="col-6 col-md-3"><div class="ad-metric"><div class="k">Deductions from attendance</div><div class="v">GH₵ {{ number_format($lines->sum('new'), 2) }}</div></div></div>
          <div class="col-6 col-md-3"><div class="ad-metric"><div class="k">Salaries to update</div><div class="v">{{ $toApply->count() }}</div></div></div>
      </div>

      @if($pendingGuards)
          <div class="alert alert-warning py-2 small">{{ $pendingGuards }} guard(s) still have sign-ins awaiting review this month. A rejected sign-in becomes an absent day, so
              <a href="{{ route('attendance.review') }}">review them</a> before applying.</div>
      @endif

      <form method="post" action="{{ route('absent-deductions.apply') }}" onsubmit="return confirm('Update the absent deduction (and net pay) on the ticked salaries?');">
          @csrf
          <input type="hidden" name="month" value="{{ $m }}">
          <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center py-2">
                  <div class="small text-muted">Only guards with attendance recorded in {{ $month->format('F Y') }} are listed; everyone else keeps the absent amount typed in. Paid salaries can't change.</div>
                  <button class="btn btn-sm btn-primary" @disabled($toApply->isEmpty())>Apply ticked</button>
              </div>
              <div class="table-responsive">
                  <table class="table table-sm table-hover mb-0 ad-table">
                      <thead class="table-light">
                          <tr>
                              <th><input type="checkbox" class="form-check-input" id="tick-all" @disabled($toApply->isEmpty()) checked></th>
                              <th>Guard</th><th>Client</th><th>Status</th>
                              <th class="text-end">Absent days</th><th class="text-end">Daily rate</th>
                              <th class="text-end">Absent now</th><th class="text-end">From attendance</th><th class="text-end">Net pay</th>
                          </tr>
                      </thead>
                      <tbody>
                      @forelse($lines as $l)
                          @php($s = $l['salary'])
                          <tr class="{{ $l['editable'] ? '' : 'paid' }}">
                              <td>@if($l['editable'] && $l['differs'])<input type="checkbox" class="form-check-input tick" name="salaries[]" value="{{ $s->id }}" checked>@endif</td>
                              <td><div class="fw-semibold">{{ $s->employee_name }}</div><div class="small text-muted">FWSS {{ $s->employee_id }}</div></td>
                              <td class="small client" title="{{ $s->client_name }}">{{ $s->client_name }}</td>
                              <td><span class="badge bg-label-{{ $l['editable'] ? 'secondary' : 'success' }}">{{ $s->payment_status === 'approved' ? 'paid' : $s->payment_status }}</span></td>
                              <td class="text-end">{{ $l['absent_days'] }}
                                  @if($l['pending_days'])<span class="badge bg-warning ms-1" title="Sign-ins awaiting review">{{ $l['pending_days'] }} ?</span>@endif</td>
                              <td class="text-end">{{ $money($l['daily_rate']) }}</td>
                              <td class="text-end">{{ $money($l['current']) }}</td>
                              <td class="text-end fw-semibold {{ $l['differs'] ? 'text-primary' : '' }}">{{ $money($l['new']) }}</td>
                              <td class="text-end">
                                  @if($l['differs'])
                                      @php($delta = (float) $l['current'] - $l['new'])
                                      <span class="{{ $delta < 0 ? 'text-danger' : 'text-success' }}">{{ $delta < 0 ? '−' : '+' }}{{ number_format(abs($delta), 2) }}</span>
                                  @else<span class="text-muted">no change</span>@endif
                              </td>
                          </tr>
                      @empty
                          <tr><td colspan="9" class="text-center text-muted py-4">No {{ $month->format('F Y') }} salaries for guards with attendance{{ $fieldId ? ' in this field' : '' }}. Add the month's salaries first, or check the month.</td></tr>
                      @endforelse
                      </tbody>
                  </table>
              </div>
          </div>
      </form>

      @if($history->isNotEmpty())
          <div class="card mt-4">
              <div class="card-header py-2"><h6 class="mb-0">Changes for {{ $month->format('F Y') }}</h6></div>
              <div class="table-responsive">
                  <table class="table table-sm small mb-0">
                      <thead class="table-light"><tr><th>When</th><th>Guard</th><th class="text-end">Days</th><th class="text-end">Rate</th><th class="text-end">Was</th><th class="text-end">Now</th><th>How</th><th>By</th></tr></thead>
                      @foreach($history as $h)
                          <tr>
                              <td>{{ \Carbon\Carbon::parse($h->created_at)->format('j M H:i') }}</td>
                              <td>{{ $h->employee_name }} <span class="text-muted">FWSS {{ $h->employee_id }}</span></td>
                              <td class="text-end">{{ $h->absent_days }}</td>
                              <td class="text-end">{{ number_format($h->daily_rate, 2) }}</td>
                              <td class="text-end">{{ $money($h->old_amount) }}</td>
                              <td class="text-end">{{ number_format($h->new_amount, 2) }}</td>
                              <td>{{ $h->trigger === 'salaries_added' ? 'When salaries were added' : 'Applied here' }}</td>
                              <td>{{ $h->user_name }}</td>
                          </tr>
                      @endforeach
                  </table>
              </div>
          </div>
      @endif
  </div>
  @endsection

  @section('scripts')
  <script>
      (function () {
          const all = document.getElementById('tick-all');
          all && all.addEventListener('change', () => document.querySelectorAll('.tick').forEach(c => { c.checked = all.checked; }));
      })();
  </script>
  @endsection
</x-hr-dashboard>

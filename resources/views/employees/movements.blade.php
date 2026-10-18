<x-hr-dashboard>

  @section('css')
  <style>
      .mv-card { border-radius: 12px; border: 1px solid #e5e7eb; }
      .mv-card .card-header { background: #f9fafb; border-bottom: 1px solid #e5e7eb; font-weight: 600; }
      .mv-flow { display: flex; flex-wrap: wrap; align-items: stretch; gap: .5rem; }
      .mv-step { flex: 1 1 8rem; border: 1px solid #e5e7eb; border-radius: 10px; padding: .7rem .9rem; background: #fff; }
      .mv-step .label { font-size: .78rem; color: #6b7280; }
      .mv-step .v { font-size: 1.45rem; font-weight: 700; line-height: 1.2; }
      .mv-step.total { background: #f3f4f6; }
      .mv-op { align-self: center; font-size: 1.3rem; color: #9ca3af; }
      .mv-plus { color: #047857; }
      .mv-minus { color: #b91c1c; }
      .mv-table td, .mv-table th { font-size: .84rem; white-space: nowrap; }
      .mv-table td.num, .mv-table th.num { text-align: right; font-variant-numeric: tabular-nums; }
      .mv-table tfoot td { font-weight: 700; background: #f9fafb; }
      .mv-est { color: #b45309; font-size: .72rem; }
  </style>
  @endsection

  @section('side_nav')
      @include('employees.partials.hr_side_nav')
  @endsection

  @section('content')
  @php
      $r = $report;
      $t = $r['totals'];
      $L = \App\Support\EmployeeMovements::LABELS;
      $single = $mode === 'month';
      $period = $single ? $r['from']->format('F Y') : $r['from']->format('d M Y') . ' – ' . $r['to']->format('d M Y');
      $officeName = $field ? optional($offices->firstWhere('id', $field))->name : ($all ? 'all offices' : $offices->pluck('name')->implode(' + '));
      $transfersNet = $t['transfers_in'] - $t['transfers_out'];
      $dateCell = fn ($row) => \Carbon\Carbon::parse($row['date'])->format('d M Y') . ($row['estimated'] ? ' <span class="mv-est" title="Date estimated from old records">est.</span>' : '');
      $tenure = fn ($d) => $d === null ? '–' : ($d < 60 ? $d . ' days' : ($d < 730 ? round($d / 30.4) . ' months' : round($d / 365, 1) . ' years'));
      $attention = count($r['undated']) + count($r['mismatches']);
  @endphp
  <div class="container-xxl flex-grow-1 container-p-y">

      @foreach(['success' => 'success', 'error' => 'danger'] as $key => $color)
          @if(session($key))
              <div class="alert alert-{{ $color }} alert-dismissible" role="alert">{{ session($key) }}
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
          @endif
      @endforeach
      @if($errors->any())
          <div class="alert alert-danger" role="alert">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
      @endif

      <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
          <div>
              <h3 class="mb-1"><i class="bx bx-transfer-alt"></i> HR movements · {{ $period }}</h3>
              <div class="text-muted small">New recruits, re-instatements, terminations and office transfers for {{ $officeName }}. Only approved movements count; requests waiting for head office are listed separately.</div>
          </div>
      </div>

      <form method="GET" action="{{ route('employees.movements') }}" class="card mv-card mb-3" id="mvFilter">
          <div class="card-body d-flex flex-wrap gap-3 align-items-end">
              <div>
                  <label class="form-label small mb-0">Period</label>
                  <select name="mode" class="form-select form-select-sm" id="mvMode">
                      <option value="month" @selected($single)>Month</option>
                      <option value="range" @selected(! $single)>Date range</option>
                  </select>
              </div>
              <div class="mv-month {{ $single ? '' : 'd-none' }}">
                  <label class="form-label small mb-0" for="mvMonth">Month</label>
                  <input type="month" name="month" id="mvMonth" class="form-control form-control-sm" value="{{ $month }}">
              </div>
              <div class="mv-range {{ $single ? 'd-none' : '' }}">
                  <label class="form-label small mb-0" for="mvFrom">From</label>
                  <input type="date" name="from" id="mvFrom" class="form-control form-control-sm" value="{{ $r['from']->toDateString() }}">
              </div>
              <div class="mv-range {{ $single ? 'd-none' : '' }}">
                  <label class="form-label small mb-0" for="mvTo">To</label>
                  <input type="date" name="to" id="mvTo" class="form-control form-control-sm" value="{{ $r['to']->toDateString() }}">
              </div>
              <div>
                  <label class="form-label small mb-0" for="mvField">Office</label>
                  <select name="field" id="mvField" class="form-select form-select-sm">
                      <option value="">{{ $all ? 'All offices' : 'All my offices' }}</option>
                      @foreach($offices as $o)
                          <option value="{{ $o->id }}" @selected($field === (int) $o->id)>{{ $o->name }}{{ $o->parent_field_id ? '' : ($offices->where('parent_field_id', $o->id)->isNotEmpty() ? ' (with sub-offices)' : '') }}</option>
                      @endforeach
                  </select>
              </div>
              <button class="btn btn-sm btn-primary" type="submit">Show</button>
              <a class="btn btn-sm btn-outline-success" id="mvExport" href="{{ route('employees.movements.export', request()->query()) }}"><i class="bx bx-spreadsheet"></i> Excel</a>
          </div>
      </form>

      {{-- The reconciliation: start + in - out = end --}}
      <div class="card mv-card mb-3">
          <div class="card-body">
              <div class="mv-flow" id="mvFlow">
                  <div class="mv-step total"><div class="label">Headcount at start</div><div class="v">{{ number_format($t['opening']) }}</div><div class="small text-muted">end of {{ $r['from']->copy()->subDay()->format('d M Y') }}</div></div>
                  <div class="mv-op">+</div>
                  <div class="mv-step"><div class="label">New recruits</div><div class="v mv-plus">{{ number_format($t['hires']) }}</div></div>
                  <div class="mv-op">+</div>
                  <div class="mv-step"><div class="label">Re-instated</div><div class="v mv-plus">{{ number_format($t['reinstated']) }}</div></div>
                  @if($t['transfers_in'] || $t['transfers_out'])
                  <div class="mv-op">±</div>
                  <div class="mv-step"><div class="label">Transfers (in − out)</div><div class="v">{{ $transfersNet > 0 ? '+' : '' }}{{ $transfersNet }}</div><div class="small text-muted">{{ $t['transfers_in'] }} in · {{ $t['transfers_out'] }} out</div></div>
                  @endif
                  <div class="mv-op">−</div>
                  <div class="mv-step"><div class="label">Terminated</div><div class="v mv-minus">{{ number_format($t['terminated']) }}</div></div>
                  <div class="mv-op">=</div>
                  <div class="mv-step total"><div class="label">Headcount at end</div><div class="v">{{ number_format($t['closing']) }}</div><div class="small text-muted">end of {{ $r['to']->format('d M Y') }}</div></div>
              </div>
              <div class="d-flex flex-wrap gap-4 mt-3 small">
                  <span><strong>Net change:</strong> {{ $t['net'] > 0 ? '+' : '' }}{{ $t['net'] }}</span>
                  <span><strong>Turnover:</strong> {{ $t['turnover'] === null ? '–' : $t['turnover'] . '%' }} <span class="text-muted">(terminated ÷ average headcount)</span></span>
                  <span><strong>Early leavers:</strong> {{ $r['early_leavers'] }} <span class="text-muted">(left within 90 days)</span></span>
                  @if(! $t['reconciles'])<span class="text-danger"><strong>Does not add up</strong> – please report this.</span>@endif
              </div>
          </div>
      </div>

      @if(count($r['pending']) || $attention)
      <div class="alert alert-warning py-2 small">
          @if(count($r['pending']))<strong>{{ count($r['pending']) }}</strong> request{{ count($r['pending']) === 1 ? '' : 's' }} waiting for head office (not counted).@endif
          @if($attention) <strong>{{ $attention }}</strong> record{{ $attention === 1 ? '' : 's' }} need{{ $attention === 1 ? 's' : '' }} attention (no date, or status disagrees with the movements).@endif
          See the tabs below.
      </div>
      @endif

      <div class="card mv-card mb-3">
          <div class="card-header">By office</div>
          <div class="table-responsive">
              <table class="table table-sm mv-table mb-0" id="mvOffices">
                  <thead><tr><th>Office</th><th class="num">Start</th><th class="num">New recruits</th><th class="num">Re-instated</th><th class="num">Transfers in</th><th class="num">Terminated</th><th class="num">Transfers out</th><th class="num">End</th><th class="num">Net</th><th class="num">Turnover</th></tr></thead>
                  <tbody>
                      @forelse($r['offices'] as $o)
                          <tr>
                              <td>{{ $o['name'] }}</td><td class="num">{{ $o['opening'] }}</td><td class="num">{{ $o['hires'] }}</td><td class="num">{{ $o['reinstated'] }}</td>
                              <td class="num">{{ $o['transfers_in'] }}</td><td class="num">{{ $o['terminated'] }}</td><td class="num">{{ $o['transfers_out'] }}</td>
                              <td class="num">{{ $o['closing'] }}</td><td class="num">{{ $o['net'] > 0 ? '+' : '' }}{{ $o['net'] }}</td>
                              <td class="num">{{ $o['turnover'] === null ? '–' : $o['turnover'] . '%' }}</td>
                          </tr>
                      @empty
                          <tr><td colspan="10" class="text-muted">No staff or movements for this period.</td></tr>
                      @endforelse
                  </tbody>
                  <tfoot><tr><td>Total</td><td class="num">{{ $t['opening'] }}</td><td class="num">{{ $t['hires'] }}</td><td class="num">{{ $t['reinstated'] }}</td><td class="num">{{ $t['transfers_in'] }}</td><td class="num">{{ $t['terminated'] }}</td><td class="num">{{ $t['transfers_out'] }}</td><td class="num">{{ $t['closing'] }}</td><td class="num">{{ $t['net'] > 0 ? '+' : '' }}{{ $t['net'] }}</td><td class="num">{{ $t['turnover'] === null ? '–' : $t['turnover'] . '%' }}</td></tr></tfoot>
              </table>
          </div>
      </div>

      <div class="row g-3 mb-3">
          @if($r['payroll'])
          <div class="col-lg-4">
              <div class="card mv-card h-100" id="mvPayroll">
                  <div class="card-header">Payroll check · {{ $r['from']->format('F Y') }}</div>
                  <div class="card-body small">
                      <div class="d-flex justify-content-between"><span>Employed at some point in the month</span><strong>{{ $r['payroll']['employed'] }}</strong></div>
                      <div class="d-flex justify-content-between"><span>Salaries generated</span><strong>{{ $r['payroll']['salaries'] }}</strong></div>
                      <hr class="my-2">
                      <div class="{{ count($r['payroll']['no_salary']) ? 'text-danger' : 'text-success' }}">Employed but no salary: <strong>{{ count($r['payroll']['no_salary']) }}</strong></div>
                      <div class="{{ count($r['payroll']['not_employed']) ? 'text-danger' : 'text-success' }}">Salary but not employed: <strong>{{ count($r['payroll']['not_employed']) }}</strong></div>
                      @if(count($r['payroll']['no_salary']) || count($r['payroll']['not_employed']))<div class="text-muted mt-1">Names under "Needs attention".</div>@endif
                  </div>
              </div>
          </div>
          @endif
          <div class="col-lg-4">
              <div class="card mv-card h-100">
                  <div class="card-header">Why staff left</div>
                  <table class="table table-sm mv-table mb-0">
                      @forelse($r['by_reason'] as $reason => $n)
                          <tr><td>{{ $reason }}</td><td class="num">{{ $n }}</td></tr>
                      @empty
                          <tr><td class="text-muted">No terminations.</td></tr>
                      @endforelse
                  </table>
              </div>
          </div>
          <div class="col-lg-4">
              <div class="card mv-card h-100">
                  <div class="card-header">How long leavers had worked</div>
                  <table class="table table-sm mv-table mb-0">
                      @foreach($r['tenure'] as $band => $n)
                          @if($n || $band !== 'Unknown')<tr><td>{{ $band }}</td><td class="num">{{ $n }}</td></tr>@endif
                      @endforeach
                  </table>
              </div>
          </div>
          @if(! $r['payroll'])
          <div class="col-lg-4">
              <div class="card mv-card h-100">
                  <div class="card-header">Clients with most leavers</div>
                  <table class="table table-sm mv-table mb-0">
                      @forelse($r['by_client'] as $client => $n)<tr><td>{{ $client }}</td><td class="num">{{ $n }}</td></tr>@empty<tr><td class="text-muted">No terminations.</td></tr>@endforelse
                  </table>
              </div>
          </div>
          @endif
      </div>

      @php
          $tabs = [
              'hire' => [$L['hire'], count($r['lists']['hire'])],
              'reinstatement' => [$L['reinstatement'], count($r['lists']['reinstatement'])],
              'termination' => [$L['termination'], count($r['lists']['termination'])],
              'transfer' => ['Transfers', count($r['lists']['transfer'])],
              'pending' => ['Waiting for approval', count($r['pending'])],
              'attention' => ['Needs attention', $attention + ($r['payroll'] ? count($r['payroll']['no_salary']) + count($r['payroll']['not_employed']) : 0)],
          ];
      @endphp
      <div class="card mv-card mb-3">
          <ul class="nav nav-tabs px-2 pt-2" role="tablist">
              @foreach($tabs as $key => [$label, $n])
                  <li class="nav-item"><button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#mv-{{ $key }}" type="button" role="tab">{{ $label }} <span class="badge bg-label-secondary">{{ $n }}</span></button></li>
              @endforeach
          </ul>
          <div class="tab-content p-0">
              @foreach(['hire', 'reinstatement', 'termination', 'transfer'] as $type)
                  <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="mv-{{ $type }}" role="tabpanel">
                      <div class="table-responsive">
                          <table class="table table-sm mv-table mb-0" id="mvList-{{ $type }}">
                              <thead><tr>
                                  <th>Date</th><th>Employee</th><th>Office</th>
                                  @if($type === 'transfer')<th>From</th>@endif
                                  <th>Client</th><th>Role</th>
                                  @if($type === 'termination')<th>Reason</th><th>Worked for</th>@endif
                                  <th>Note</th><th>Requested by</th><th>Approved by</th>
                              </tr></thead>
                              <tbody>
                                  @forelse($r['lists'][$type] as $row)
                                      <tr>
                                          <td>{!! $dateCell($row) !!}
                                              @if($canCorrect && $row['estimated'])
                                                  <button type="button" class="btn btn-link btn-sm p-0 ms-1 mv-fix" data-id="{{ $row['id'] }}" data-type="{{ $type }}"
                                                          data-name="{{ $row['name'] }}" data-date="{{ $row['date'] }}" data-reason="{{ $row['reason_key'] }}" data-note="{{ $row['note'] }}">correct</button>
                                              @endif
                                          </td>
                                          <td><a href="{{ url('employees', $row['employee_id']) }}">{{ $row['name'] }}</a> <span class="text-muted">FWSS {{ $row['employee_id'] }}</span></td>
                                          <td>{{ $row['office'] }}</td>
                                          @if($type === 'transfer')<td>{{ $row['from_office'] }}</td>@endif
                                          <td>{{ $row['client'] }}</td><td>{{ $row['role'] }}</td>
                                          @if($type === 'termination')<td>{{ $row['reason'] }}</td><td>{{ $tenure($row['tenure_days']) }}</td>@endif
                                          <td class="text-wrap" style="max-width: 18rem">{{ $row['note'] }}</td>
                                          <td>{{ $row['requested_by'] }}</td><td>{{ $row['approved_by'] }}</td>
                                      </tr>
                                  @empty
                                      <tr><td colspan="11" class="text-muted">None in this period.</td></tr>
                                  @endforelse
                              </tbody>
                          </table>
                      </div>
                  </div>
              @endforeach
              <div class="tab-pane fade" id="mv-pending" role="tabpanel">
                  <div class="table-responsive">
                      <table class="table table-sm mv-table mb-0">
                          <thead><tr><th>Request</th><th>Employee</th><th>Office</th><th>Effective</th><th>Reason</th><th class="num">Waiting</th></tr></thead>
                          <tbody>
                              @forelse($r['pending'] as $p)
                                  <tr><td>{{ $p['type'] }}</td><td><a href="{{ url('employees', $p['employee_id']) }}">{{ $p['name'] }}</a> <span class="text-muted">FWSS {{ $p['employee_id'] }}</span></td>
                                      <td>{{ $p['office'] }}</td><td>{{ $p['date'] ? \Carbon\Carbon::parse($p['date'])->format('d M Y') : '' }}</td><td>{{ $p['reason'] }}</td>
                                      <td class="num">{{ $p['waiting_days'] === null ? '' : $p['waiting_days'] . ' days' }}</td></tr>
                              @empty
                                  <tr><td colspan="6" class="text-muted">Nothing waiting.</td></tr>
                              @endforelse
                          </tbody>
                      </table>
                  </div>
                  <div class="small text-muted p-2">Approve or decline on the <a href="{{ url('employeesPending') }}">pending employees</a> page.</div>
              </div>
              <div class="tab-pane fade" id="mv-attention" role="tabpanel">
                  <div class="p-3 small">
                      @if($r['payroll'] && count($r['payroll']['no_salary']))
                          <p class="mb-1"><strong>Employed in {{ $r['from']->format('F') }} but no salary</strong></p>
                          <p>@foreach($r['payroll']['no_salary'] as $p)<a href="{{ url('employees', $p['employee_id']) }}">{{ $p['name'] }}</a>{{ $loop->last ? '' : ', ' }}@endforeach</p>
                      @endif
                      @if($r['payroll'] && count($r['payroll']['not_employed']))
                          <p class="mb-1"><strong>Salary in {{ $r['from']->format('F') }} but not employed</strong></p>
                          <p>@foreach($r['payroll']['not_employed'] as $p)<a href="{{ url('employees', $p['employee_id']) }}">{{ $p['name'] }}</a>{{ $loop->last ? '' : ', ' }}@endforeach</p>
                      @endif
                      @if(count($r['undated']))
                          <p class="mb-1"><strong>No date recorded</strong> (left out of every count until a date is set)</p>
                          <p>
                              @foreach($r['undated'] as $p)
                                  <a href="{{ url('employees', $p['employee_id']) }}">{{ $p['name'] }}</a> ({{ $p['type'] }})
                                  @if($canCorrect)
                                      <button type="button" class="btn btn-link btn-sm p-0 mv-fix" data-id="{{ $p['id'] }}" data-type="{{ $p['type_key'] }}"
                                              data-name="{{ $p['name'] }}" data-date="" data-reason="{{ $p['reason_key'] }}" data-note="">set date</button>
                                  @endif
                                  {{ $loop->last ? '' : ' · ' }}
                              @endforeach
                          </p>
                      @endif
                      @if(count($r['mismatches']))
                          <p class="mb-1"><strong>Status disagrees with the movements</strong> (status changed outside Terminate / Re-instate)</p>
                          <p>@foreach($r['mismatches'] as $p)<a href="{{ url('employees', $p['employee_id']) }}">{{ $p['name'] }}</a> – status {{ $p['status'] }}, movements say {{ strtolower($p['movements_say']) }}{{ $loop->last ? '' : '; ' }}@endforeach</p>
                      @endif
                      @if(! $tabs['attention'][1])<p class="text-muted mb-0">Nothing needs attention.</p>@endif
                  </div>
              </div>
          </div>
      </div>
  </div>

  @if($canCorrect)
  {{-- Correct the date (and a termination's reason) of an old record. --}}
  <div class="modal fade" id="mvFixModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
          <form method="POST" class="modal-content" id="mvFixForm" data-action="{{ route('employees.movements.update', 0) }}">
              @csrf @method('PUT')
              <div class="modal-header"><h5 class="modal-title" id="mvFixTitle">Correct</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
              <div class="modal-body">
                  <div class="mb-3">
                      <label class="form-label" for="mvFixDate" id="mvFixDateLabel">Date *</label>
                      <input type="date" class="form-control" name="effective_date" id="mvFixDate" required max="{{ now()->addDays(90)->toDateString() }}">
                  </div>
                  <div class="mb-3" id="mvFixReasonWrap">
                      <label class="form-label" for="mvFixReason">Reason *</label>
                      <select class="form-select" name="reason" id="mvFixReason">
                          <option value="">Choose…</option>
                          @foreach(\App\Support\EmployeeMovements::REASONS as $value => $label)
                              <option value="{{ $value }}">{{ $label }}</option>
                          @endforeach
                      </select>
                  </div>
                  <div class="mb-0">
                      <label class="form-label" for="mvFixNote">Note</label>
                      <input type="text" class="form-control" name="note" id="mvFixNote" maxlength="255">
                  </div>
              </div>
              <div class="modal-footer"><button class="btn btn-primary" type="submit">Save</button></div>
          </form>
      </div>
  </div>
  @endif
  @endsection

  @section('scripts')
  <script>
  (function () {
      // "correct" / "set date": fill the one modal form for that movement.
      var fixForm = document.getElementById('mvFixForm');
      document.querySelectorAll('.mv-fix').forEach(function (b) {
          b.addEventListener('click', function () {
              if (!fixForm) { return; }
              var termination = b.dataset.type === 'termination';
              fixForm.action = fixForm.dataset.action.replace(/\/0$/, '/' + b.dataset.id);
              document.getElementById('mvFixTitle').textContent = 'Correct · ' + b.dataset.name;
              document.getElementById('mvFixDateLabel').textContent = (termination ? 'Last day employed' : b.dataset.type === 'reinstatement' ? 'Back at work from' : b.dataset.type === 'hire' ? 'Date of joining' : 'Date') + ' *';
              fixForm.elements.effective_date.value = b.dataset.date || '';
              document.getElementById('mvFixReasonWrap').classList.toggle('d-none', !termination);
              fixForm.elements.reason.required = termination;
              fixForm.elements.reason.value = termination ? (b.dataset.reason || '') : '';
              fixForm.elements.note.value = b.dataset.note || '';
              var el = document.getElementById('mvFixModal');
              if (window.bootstrap && bootstrap.Modal) { bootstrap.Modal.getOrCreateInstance(el).show(); }
          });
      });

      var mode = document.getElementById('mvMode');
      if (!mode) { return; }
      mode.addEventListener('change', function () {
          var range = mode.value === 'range';
          document.querySelectorAll('.mv-range').forEach(function (el) { el.classList.toggle('d-none', !range); });
          document.querySelectorAll('.mv-month').forEach(function (el) { el.classList.toggle('d-none', range); });
      });
  })();
  </script>
  @endsection
</x-hr-dashboard>

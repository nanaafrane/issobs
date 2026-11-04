<x-hr-dashboard>

  @section('css')
    <style>
        .su-step { display:flex; align-items:center; gap:.5rem; font-weight:600; margin:0 0 .75rem; }
        .su-step .n { width:1.6rem; height:1.6rem; border-radius:50%; background:rgba(105,108,255,.15); color:#696cff; display:inline-flex; align-items:center; justify-content:center; font-size:.85rem; }
        .su-metric { background:#f5f5f9; border-radius:.5rem; padding:.7rem .9rem; height:100%; }
        .su-metric .k { font-size:.75rem; text-transform:uppercase; letter-spacing:.03em; color:#8592a3; }
        .su-metric .v { font-size:1.4rem; font-weight:600; color:#384551; }
        .su-metric.err .v { color:#ff3e1d; } .su-metric.warn .v { color:#d48a00; } .su-metric.ok .v { color:#56a02e; }
        .su-cols label { font-weight:normal; }
        .su-colgroup { border:1px solid #e7e7ef; border-radius:.5rem; padding:.6rem .8rem; }
        #suRows td { vertical-align:top; font-size:.875rem; }
        .chg { display:block; white-space:nowrap; }
        .chg .old { color:#a1acb8; text-decoration:line-through; }
        .msg { display:block; font-size:.8rem; }
        .msg.e { color:#ff3e1d; } .msg.w { color:#b07400; }
        .su-filter.active { background:#696cff; color:#fff; border-color:#696cff; }
        tr.st-error td:first-child { border-left:3px solid #ff3e1d; }
        tr.st-change td:first-child { border-left:3px solid #696cff; }
        tr.st-totals td:first-child { border-left:3px solid #8592a3; }
        .num { text-align:right; white-space:nowrap; }
        #suRows td.fix { min-width:280px; }
    </style>
  @endsection

  @section('side_nav')
    @include('partials.payroll_side_nav')
  @endsection

  @section('content')
  @php
      $money = fn ($v) => $v === null ? '—' : number_format((float) $v, 2);
      $ym = $month->format('Y-m');
      $upload = $upload ?? null;
      $statusBadge = ['checked' => 'bg-label-info', 'applied' => 'bg-label-success', 'undone' => 'bg-label-secondary', 'unreadable' => 'bg-label-danger'];
      $allCols = $earnings + $deductions;
  @endphp
  <div class="container-xxl flex-grow-1 container-p-y">

      <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
          <div>
              <h4 class="mb-1">Upload salaries</h4>
              <div class="text-muted small">Download the month's salaries, change the amounts in Excel (it checks each row as you type), upload, review every change, then apply.</div>
          </div>
          <div class="d-flex gap-2">
              @if($upload)
                  <a href="{{ route('salary-upload.index', ['month' => $ym]) }}" class="btn btn-sm btn-outline-primary"><i class="bx bx-upload me-1"></i>Another upload</a>
              @endif
              <a id="salaryMonthBackLink" data-base-url="{{ route('salaries.salariesMonth') }}" href="{{ route('salaries.salariesMonth', ['month' => $ym]) }}" class="btn btn-sm btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i>{{ $month->format('F Y') }} salaries</a>
          </div>
      </div>

      @include('flash-messages')
      @foreach (['file', 'excelFile', 'columns', 'month'] as $f)
          @error($f)<div class="alert alert-danger">{{ $message }}</div>@enderror
      @endforeach

  @if(! $upload)
      {{-- ============================== 1. Download, 2. Upload ============================== --}}
      <div class="row g-4">
          <div class="col-lg-7">
              <div class="card h-100"><div class="card-body">
                  <p class="su-step"><span class="n">1</span>Download the template</p>
                  <form method="get" action="{{ route('salary-upload.template') }}" id="templateForm">
                      <div class="row g-3 mb-3">
                          <div class="col-sm-5">
                              <label class="form-label small mb-1">Salary month</label>
                              <input type="month" id="salaryUploadMonth" name="month" class="form-control form-control-sm" value="{{ old('month', $ym) }}" required>
                          </div>
                          <div class="col-sm-7">
                              <label class="form-label small mb-1">Office</label>
                              <select name="field_id" id="salaryUploadField" class="form-select form-select-sm">
                                  <option value="">All offices</option>
                                  @foreach($fields as $f)<option value="{{ $f->id }}" @selected($fieldId == $f->id)>{{ $f->name }}</option>@endforeach
                              </select>
                          </div>
                      </div>

                      <div id="templateMonthMessage" class="alert alert-warning small py-2 mb-2 d-none"></div>

                      <label class="form-label small mb-1">Columns to put in the file <span class="text-muted">(only these can be changed by this file)</span></label>
                      <div class="d-flex flex-wrap gap-1 mb-2">
                          <button type="button" class="btn btn-xs btn-sm btn-outline-secondary py-0" data-pick="all">All</button>
                          <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-pick="untick">Untick all</button>
                          <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-pick="earnings">Earnings</button>
                          <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-pick="deductions">Deductions</button>
                          <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-pick="absent">Absent only</button>
                          <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-pick="loans">Loan, IOU &amp; other deductions</button>
                          <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-pick="overtime">Overtime &amp; allowances</button>
                      </div>
                      <div class="row g-2 su-cols">
                          <div class="col-md-5"><div class="su-colgroup">
                              <div class="small fw-semibold mb-1">Earnings</div>
                              @foreach($earnings as $k => $label)
                                  <div class="form-check"><input class="form-check-input" type="checkbox" name="columns[]" value="{{ $k }}" id="c_{{ $k }}" data-group="earnings" checked>
                                      <label class="form-check-label small" for="c_{{ $k }}">{{ $label }}</label></div>
                              @endforeach
                          </div></div>
                          <div class="col-md-7"><div class="su-colgroup">
                              <div class="small fw-semibold mb-1">Deductions</div>
                              <div class="row">
                              @foreach($deductions as $k => $label)
                                  <div class="col-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="columns[]" value="{{ $k }}" id="c_{{ $k }}" data-group="deductions" checked>
                                      <label class="form-check-label small" for="c_{{ $k }}">{{ $label }}</label></div></div>
                              @endforeach
                              </div>
                          </div></div>
                      </div>
                      <div class="small text-muted mt-2">The file has one row per unpaid salary (pending, rejected or held) with today's amounts, and a <strong>Row check</strong> column that says OK, CHECK or ERROR as you type. Approved salaries are left out.</div>
                      <button id="downloadTemplateBtn" class="btn btn-primary btn-sm mt-3" type="submit" @disabled(! ($canDownloadTemplate ?? true))><i class="bx bx-download me-1"></i>Download template</button>
                  </form>
              </div></div>
          </div>

          <div class="col-lg-5">
              <div class="card h-100"><div class="card-body">
                  <p class="su-step"><span class="n">2</span>Upload it back</p>
                  <form method="post" action="{{ route('salary-upload.check') }}" enctype="multipart/form-data">
                      @csrf
                      <input type="hidden" name="month" value="{{ $ym }}">
                      <input type="file" name="file" class="form-control form-control-sm mb-2" accept=".xlsx,.xls,.csv" required>
                      <button class="btn btn-dark btn-sm" type="submit"><i class="bx bx-check-shield me-1"></i>Check file</button>
                  </form>
                  <ul class="small text-muted mt-3 mb-0 ps-3">
                      <li>Nothing is saved yet: you see every error (and how to fix it), every change and net pay per office before and after.</li>
                      <li>You then tick which columns to apply. Rows with errors are left out and can be downloaded, fixed and uploaded again.</li>
                      <li>The older Excel export from the salaries table still works (its <em>id</em> column, or Employee ID + Salary Month).</li>
                      <li>@if($canApply) You can apply checked files. @else A Finance Manager or Accounts Manager applies the file after you check it - it shows in their upload history. @endif</li>
                  </ul>
              </div></div>
          </div>
      </div>

      {{-- ============================== History ============================== --}}
      <div class="card mt-4">
          <div class="card-header py-3"><h6 class="mb-0">Recent uploads</h6></div>
          <div class="table-responsive">
              <table class="table table-sm mb-0">
                  <thead><tr><th>When</th><th>File</th><th>Month</th><th>By</th><th>Status</th><th class="num">Rows</th><th class="num">Errors</th><th class="num">Saved</th><th class="num">Net change</th><th></th></tr></thead>
                  <tbody>
                  @forelse($recent as $u)
                      <tr>
                          <td class="text-nowrap">{{ $u->created_at->format('d M H:i') }}</td>
                          <td>{{ \Illuminate\Support\Str::limit($u->original_name, 34) }}</td>
                          <td>{{ $u->months ?? '—' }}</td>
                          <td>{{ $u->user?->name }}{!! $u->appliedBy && $u->applied_by != $u->user_id ? '<br><span class="small text-muted">applied by ' . e($u->appliedBy->name) . '</span>' : '' !!}</td>
                          <td><span class="badge {{ $statusBadge[$u->status] ?? 'bg-label-secondary' }}">{{ $u->status }}</span></td>
                          <td class="num">{{ $u->total_rows }}</td>
                          <td class="num">{{ $u->error_rows ?: '' }}</td>
                          <td class="num">{{ $u->status === 'checked' ? '' : $u->updated_count }}</td>
                          <td class="num {{ $u->status === 'undone' ? 'text-muted text-decoration-line-through' : '' }}">{{ $u->net_after !== null ? number_format($u->net_after - $u->net_before, 2) : '' }}</td>
                          <td class="text-end">@if($u->status !== 'unreadable')<a href="{{ route('salary-upload.show', $u) }}" class="btn btn-sm btn-outline-primary py-0">Open</a>@endif</td>
                      </tr>
                  @empty
                      <tr><td colspan="10" class="text-muted text-center py-3">No uploads yet.</td></tr>
                  @endforelse
                  </tbody>
              </table>
          </div>
      </div>

  @elseif(! empty($done))
      {{-- ============================== Applied / undone upload ============================== --}}
      @php $changes = $upload->changes ?? []; @endphp
      <div class="card mb-4"><div class="card-body">
          <div class="d-flex flex-wrap justify-content-between gap-3">
              <div>
                  <h5 class="mb-1">{{ $upload->original_name }} <span class="badge {{ $statusBadge[$upload->status] ?? '' }}">{{ $upload->status }}</span></h5>
                  <div class="small text-muted">
                      Uploaded by {{ $upload->user?->name }} {{ $upload->created_at->format('d M Y H:i') }}.
                      @if($upload->applied_at) Applied by {{ $upload->appliedBy?->name }} {{ $upload->applied_at->format('d M Y H:i') }}. @endif
                      @if($upload->undone_at) Undone by {{ $upload->undoneBy?->name }} {{ $upload->undone_at->format('d M Y H:i') }}. @endif
                  </div>
                  <div class="small mt-1">Columns applied: <strong>{{ implode(', ', array_map(fn ($k) => $allCols[$k] ?? $k, $upload->applied_columns ?? [])) }}</strong></div>
              </div>
              <div class="d-flex gap-2 align-items-start">
                  @if($upload->error_rows)
                      <a href="{{ route('salary-upload.errors', $upload) }}" class="btn btn-sm btn-outline-danger"><i class="bx bx-download me-1"></i>Rows not saved ({{ $upload->error_rows }})</a>
                  @endif
                  @if($upload->status === 'applied' && $canApply)
                      <form method="post" action="{{ route('salary-upload.undo', $upload) }}" onsubmit="return confirm('Put these {{ count($changes) }} salaries back to what they were before this upload? Salaries paid or changed since are left as they are.')">
                          @csrf <button class="btn btn-sm btn-outline-warning"><i class="bx bx-undo me-1"></i>Undo this upload</button>
                      </form>
                  @endif
              </div>
          </div>
          <div class="row g-3 mt-1">
              <div class="col-6 col-md-3"><div class="su-metric ok"><div class="k">Salaries saved</div><div class="v">{{ $upload->updated_count }}</div></div></div>
              <div class="col-6 col-md-3"><div class="su-metric err"><div class="k">Rows not saved</div><div class="v">{{ $upload->skipped_count }}</div></div></div>
              <div class="col-6 col-md-3"><div class="su-metric"><div class="k">Net pay before</div><div class="v">{{ $money($upload->net_before) }}</div></div></div>
              <div class="col-6 col-md-3"><div class="su-metric"><div class="k">Net pay after</div><div class="v">{{ $money($upload->net_after) }}</div></div></div>
          </div>
          @if($upload->undo_kept)
              <div class="alert alert-warning small mt-3 mb-0"><strong>Left as they were when undone:</strong> {{ implode('; ', $upload->undo_kept) }}</div>
          @endif
      </div></div>

      <div class="card">
          <div class="table-responsive">
              <table class="table table-sm mb-0" id="suRows">
                  <thead><tr><th>Sheet row</th><th>Salary ID</th><th>Employee</th><th>Changes</th><th class="num">Net before</th><th class="num">Net after</th></tr></thead>
                  <tbody>
                  @foreach($changes as $sid => $a)
                      <tr>
                          <td>{{ $a['row'] }}</td>
                          <td><a href="{{ url('salaries/' . $sid . '/edit') }}">{{ $sid }}</a></td>
                          <td>FWSS {{ $a['employee_id'] }} {{ $names[$a['employee_id']] ?? '' }}</td>
                          <td>
                              @forelse($a['changes'] as $k => $c)
                                  <span class="chg">{{ $allCols[$k] ?? $k }}: <span class="old">{{ $money($c['old']) }}</span> → <strong>{{ $money($c['new']) }}</strong></span>
                              @empty
                                  <span class="text-muted small">totals worked out</span>
                              @endforelse
                          </td>
                          <td class="num">{{ $money($a['before']['net_salary'] ?? null) }}</td>
                          <td class="num">{{ $money($a['after']['net_salary'] ?? null) }}</td>
                      </tr>
                  @endforeach
                  </tbody>
              </table>
          </div>
      </div>

  @else
      {{-- ============================== 3. Check: review and apply ============================== --}}
      @php
          $s = $summary;
          $diff = $s['net_after'] - $s['net_before'];
      @endphp
      <div class="card mb-4"><div class="card-body">
          <p class="su-step"><span class="n">3</span>Review <span class="fw-normal text-muted ms-1">{{ $upload->original_name }} · {{ implode(', ', $s['months']) ?: 'no month' }} · {{ $read['layout'] }} · nothing saved yet</span></p>
          @if(count($s['months']) > 1)
              <div class="alert alert-warning small py-2">This file has salaries of more than one month: {{ implode(', ', $s['months']) }}.</div>
          @endif
          @if($read['ignored'])
              <div class="alert alert-secondary small py-2">Columns not recognised, so ignored: {{ implode(', ', $read['ignored']) }}.</div>
          @endif

          <div class="row g-3">
              <div class="col-6 col-md-2"><div class="su-metric"><div class="k">Rows in file</div><div class="v">{{ $s['total'] }}</div></div></div>
              <div class="col-6 col-md-2"><div class="su-metric ok"><div class="k">Ready to save</div><div class="v">{{ $s['ready'] }}</div><div class="small text-muted">{{ $s['change'] }} changed · {{ $s['totals'] }} totals only</div></div></div>
              <div class="col-6 col-md-2"><div class="su-metric err"><div class="k">Errors</div><div class="v">{{ $s['errors'] }}</div><div class="small text-muted">not saved</div></div></div>
              <div class="col-6 col-md-2"><div class="su-metric warn"><div class="k">To double check</div><div class="v">{{ $s['warnings'] }}</div></div></div>
              <div class="col-6 col-md-2"><div class="su-metric"><div class="k">Unchanged</div><div class="v">{{ $s['same'] }}</div></div></div>
              <div class="col-6 col-md-2"><div class="su-metric"><div class="k">Net pay change</div><div class="v {{ $diff < 0 ? 'text-danger' : '' }}">{{ ($diff > 0 ? '+' : '') . number_format($diff, 2) }}</div><div class="small text-muted">{{ number_format($s['net_before'], 2) }} → {{ number_format($s['net_after'], 2) }}</div></div></div>
          </div>
      </div></div>

      <div class="row g-4 mb-4">
          <div class="col-lg-7">
              <div class="card h-100">
                  <div class="card-header py-3"><h6 class="mb-0">Columns: tick the ones to apply</h6></div>
                  <form method="get" action="{{ route('salary-upload.show', $upload) }}" id="colsForm">
                      <input type="hidden" name="apply_sent" value="1">
                      <div class="table-responsive">
                          <table class="table table-sm mb-0">
                              <thead><tr><th style="width:2rem"></th><th>Column</th><th class="num">Salaries changed</th><th class="num">Total before</th><th class="num">Total after</th></tr></thead>
                              <tbody>
                              @foreach($s['columns'] as $k => $c)
                                  <tr class="{{ $c['applied'] ? '' : 'text-muted' }}">
                                      <td><input class="form-check-input su-col" type="checkbox" name="columns[]" value="{{ $k }}" @checked($c['applied'])></td>
                                      <td>{{ $c['label'] }}</td>
                                      <td class="num">{{ $c['applied'] ? $c['changed'] : '—' }}</td>
                                      <td class="num">{{ $c['applied'] && $c['changed'] ? number_format($c['before'], 2) : '' }}</td>
                                      <td class="num">{{ $c['applied'] && $c['changed'] ? number_format($c['after'], 2) : '' }}</td>
                                  </tr>
                              @endforeach
                              </tbody>
                          </table>
                      </div>
                      <div class="card-body py-2 d-flex align-items-center gap-2">
                          <button class="btn btn-sm btn-outline-primary" type="submit" id="refreshBtn"><i class="bx bx-refresh me-1"></i>Update preview</button>
                          <span class="small text-muted" id="refreshHint">Columns not ticked keep their current values. Tax, SSNIT and net are worked out again for every row saved.</span>
                      </div>
                  </form>
              </div>
          </div>
          <div class="col-lg-5">
              <div class="card h-100">
                  <div class="card-header py-3"><h6 class="mb-0">Net pay per office (rows to save)</h6></div>
                  <div class="table-responsive">
                      <table class="table table-sm mb-0">
                          <thead><tr><th>Office</th><th class="num">Salaries</th><th class="num">Before</th><th class="num">After</th><th class="num">Change</th></tr></thead>
                          <tbody>
                          @forelse($s['offices'] as $name => $o)
                              <tr><td>{{ $name }}</td><td class="num">{{ $o['count'] }}</td><td class="num">{{ number_format($o['before'], 2) }}</td><td class="num">{{ number_format($o['after'], 2) }}</td>
                                  <td class="num {{ $o['after'] - $o['before'] < 0 ? 'text-danger' : '' }}">{{ number_format($o['after'] - $o['before'], 2) }}</td></tr>
                          @empty
                              <tr><td colspan="5" class="text-muted text-center py-3">Nothing to save.</td></tr>
                          @endforelse
                          </tbody>
                      </table>
                  </div>
              </div>
          </div>
      </div>

      <div class="card mb-4"><div class="card-body d-flex flex-wrap align-items-center gap-3">
          <p class="su-step mb-0"><span class="n">4</span>Apply</p>
          @if($s['errors'])
              <a href="{{ route('salary-upload.errors', $upload) }}" class="btn btn-sm btn-outline-danger"><i class="bx bx-download me-1"></i>Download the {{ $s['errors'] }} rows with errors</a>
          @endif
          @if($canApply)
              @if($s['ready'])
                  <form method="post" action="{{ route('salary-upload.apply', $upload) }}" id="applyForm" class="d-flex align-items-center gap-2"
                        onsubmit="return confirm('Save {{ $s['ready'] }} salaries ({{ implode(', ', array_map(fn ($k) => $labels[$k], $s['applied'])) }})?{{ $s['errors'] ? ' ' . $s['errors'] . ' rows with errors are left as they are.' : '' }}')">
                      @csrf
                      @foreach($s['applied'] as $k)<input type="hidden" name="columns[]" value="{{ $k }}">@endforeach
                      <button class="btn btn-sm btn-success" type="submit" id="applyBtn"><i class="bx bx-check me-1"></i>Apply {{ $s['ready'] }} salaries</button>
                  </form>
                  <span class="small text-muted">{{ $s['errors'] ? 'Rows with errors are not saved.' : '' }} An applied upload can be undone.</span>
              @else
                  <span class="text-muted small">Nothing to save: every row either has an error or is unchanged.</span>
              @endif
          @else
              <span class="small text-muted">Checked and saved in the upload history. A Finance Manager or Accounts Manager applies it from there.</span>
          @endif
      </div></div>

      <div class="card">
          <div class="card-header py-3 d-flex flex-wrap gap-2 align-items-center">
              <h6 class="mb-0 me-2">Rows</h6>
              @php
                  $c = collect($results);
                  $filters = ['all' => ['All', $c->count()], 'error' => ['Errors', $s['errors']], 'warn' => ['To double check', $s['warnings']],
                              'change' => ['Changed', $s['change']], 'totals' => ['Totals only', $s['totals']], 'same' => ['Unchanged', $s['same']]];
                  $start = $s['errors'] ? 'error' : ($s['warnings'] ? 'warn' : 'all');
              @endphp
              @foreach($filters as $key => [$label, $n])
                  <button type="button" class="btn btn-sm btn-outline-secondary su-filter {{ $key === $start ? 'active' : '' }}" data-f="{{ $key }}">{{ $label }} <span class="badge bg-white text-dark ms-1">{{ $n }}</span></button>
              @endforeach
          </div>
          <div class="table-responsive">
              <table class="table table-sm mb-0" id="suRows">
                  <thead><tr><th>Row</th><th>Salary ID</th><th>Employee</th><th>Office</th><th>Result</th><th>Changes</th><th class="num">Net now</th><th class="num">Net after</th><th>What to fix / check</th></tr></thead>
                  <tbody>
                  @foreach($results as $r)
                      @php
                          $sal = $r['salary'];
                          $warnRow = $r['warnings'] && $r['state'] !== 'error';
                          $badge = ['error' => ['bg-label-danger', 'Error - not saved'], 'change' => ['bg-label-primary', 'Changed'], 'totals' => ['bg-label-secondary', 'Totals worked out'], 'same' => ['bg-label-light text-muted', 'Unchanged']][$r['state']];
                      @endphp
                      <tr class="st-{{ $r['state'] }}" data-state="{{ $r['state'] }}" data-warn="{{ $warnRow ? 1 : 0 }}">
                          <td>{{ $r['row'] }}</td>
                          <td>{!! $sal ? '<a href="' . url('salaries/' . $sal->id . '/edit') . '" target="_blank">' . $sal->id . '</a>' : e($read['rows'][$r['row']]['salary_id'] ?? '—') !!}</td>
                          <td class="text-nowrap">{{ $sal ? 'FWSS ' . $sal->employee_id . ' ' . ($sal->employee?->name ?? '') : ($read['rows'][$r['row']]['name'] ?? '') }}</td>
                          <td>{{ $sal?->field?->name }}</td>
                          <td><span class="badge {{ $badge[0] }}">{{ $badge[1] }}</span>{!! $sal && $sal->payment_status !== 'pending' ? '<br><span class="small text-muted">' . e($sal->payment_status) . '</span>' : '' !!}</td>
                          <td>
                              @foreach($r['changes'] as $k => [$old, $new])
                                  <span class="chg">{{ $labels[$k] }}: <span class="old">{{ $money($old) }}</span> → <strong>{{ $money($new) }}</strong></span>
                              @endforeach
                              @if($r['state'] === 'totals' && $r['before'])
                                  @foreach(['tax' => 'Tax', 'ssnit_tier2_5' => 'SSNIT 5%', 'gross_salary' => 'Gross', 'total_deductions' => 'Deductions'] as $k => $label)
                                      @if(($r['before'][$k] ?? null) === null ? $r['after'][$k] != 0 : abs($r['before'][$k] - $r['after'][$k]) > 0.005)
                                          <span class="chg text-muted">{{ $label }}: <span class="old">{{ $money($r['before'][$k]) }}</span> → {{ $money($r['after'][$k]) }}</span>
                                      @endif
                                  @endforeach
                              @endif
                          </td>
                          <td class="num">{{ $money($r['before']['net_salary'] ?? null) }}</td>
                          <td class="num">{!! ! $r['after'] || $r['state'] === 'same' ? '' : ($r['state'] === 'error' ? '<span class="text-muted" title="Not saved">' . number_format($r['after']['net_salary'], 2) . '</span>' : '<strong>' . number_format($r['after']['net_salary'], 2) . '</strong>') !!}</td>
                          <td class="fix">
                              @foreach($r['errors'] as $e)<span class="msg e"><i class="bx bx-x-circle"></i> {{ $e['msg'] }}</span>@endforeach
                              @foreach($r['warnings'] as $w)<span class="msg w"><i class="bx bx-error"></i> {{ $w['msg'] }}</span>@endforeach
                          </td>
                      </tr>
                  @endforeach
                  </tbody>
              </table>
          </div>
      </div>
  @endif
  </div>
  @endsection

  @section('scripts')
  <script>
  (function () {
      var monthInput = document.getElementById('salaryUploadMonth');
      var fieldSelect = document.getElementById('salaryUploadField');
      var backLink = document.getElementById('salaryMonthBackLink');
      var downloadBtn = document.getElementById('downloadTemplateBtn');
      var messageBox = document.getElementById('templateMonthMessage');

      if (monthInput && backLink) {
          var syncBackLink = function () {
              var value = monthInput.value;
              if (!value) return;
              var url = new URL(backLink.dataset.baseUrl || '{{ route('salaries.salariesMonth') }}', window.location.origin);
              url.searchParams.set('month', value);
              backLink.href = url.pathname + url.search;
          };
          monthInput.addEventListener('input', syncBackLink);
          syncBackLink();
      }

      function setTemplateAvailability(hasRows, msg) {
          if (!downloadBtn) return;
          downloadBtn.disabled = !hasRows;
          if (messageBox) {
              if (!hasRows && msg) {
                  messageBox.textContent = msg;
                  messageBox.classList.remove('d-none');
              } else {
                  messageBox.textContent = '';
                  messageBox.classList.add('d-none');
              }
          }
      }

      function checkTemplateAvailability() {
          if (!monthInput || !monthInput.value || !downloadBtn) return;
          var params = new URLSearchParams({ month: monthInput.value });
          if (fieldSelect && fieldSelect.value) {
              params.set('field_id', fieldSelect.value);
          }
          fetch('{{ route('salary-upload.availability') }}?' + params.toString(), {
              headers: { 'X-Requested-With': 'XMLHttpRequest' }
          })
          .then(function (response) { return response.ok ? response.json() : null; })
          .then(function (json) {
              if (!json) return;
              setTemplateAvailability(Boolean(json.has_rows), json.message || '');
          })
          .catch(function () {
              setTemplateAvailability(true, '');
          });
      }

      if (monthInput) {
          monthInput.addEventListener('change', checkTemplateAvailability);
          monthInput.addEventListener('input', checkTemplateAvailability);
      }
      if (fieldSelect) {
          fieldSelect.addEventListener('change', checkTemplateAvailability);
      }
      if (downloadBtn) {
          checkTemplateAvailability();
      }

      // Template column presets.
      var presets = {
          all: null, untick: 'untick', earnings: 'earnings', deductions: 'deductions',
          absent: ['absent'], loans: ['loan', 'iou', 'other_deductions'],
          overtime: ['overtime', 'allowances', 'airtime_allowance', 'transport_allowance', 'reimbursements']
      };
      document.querySelectorAll('[data-pick]').forEach(function (b) {
          b.addEventListener('click', function () {
              var p = presets[b.dataset.pick];
              document.querySelectorAll('#templateForm input[name="columns[]"]').forEach(function (cb) {
                  if (p === null) {
                      cb.checked = true;
                  } else if (p === 'untick') {
                      cb.checked = false;
                  } else if (typeof p === 'string') {
                      cb.checked = cb.dataset.group === p;
                  } else {
                      cb.checked = p.indexOf(cb.value) !== -1;
                  }
              });
          });
      });

      // Row filters.
      var rows = document.querySelectorAll('#suRows tbody tr[data-state]');
      function show(f) {
          rows.forEach(function (tr) {
              var ok = f === 'all' || (f === 'warn' ? tr.dataset.warn === '1' : tr.dataset.state === f);
              tr.style.display = ok ? '' : 'none';
          });
          document.querySelectorAll('.su-filter').forEach(function (b) { b.classList.toggle('active', b.dataset.f === f); });
      }
      document.querySelectorAll('.su-filter').forEach(function (b) { b.addEventListener('click', function () { show(b.dataset.f); }); });
      var active = document.querySelector('.su-filter.active');
      if (active) { show(active.dataset.f); }

      // Changing the ticks: the numbers shown are for the old ticks until refreshed.
      document.querySelectorAll('.su-col').forEach(function (cb) {
          cb.addEventListener('change', function () {
              var btn = document.getElementById('applyBtn');
              if (btn) { btn.disabled = true; btn.title = 'Press Update preview first'; }
              var hint = document.getElementById('refreshHint');
              if (hint) { hint.innerHTML = '<strong class="text-warning">Press Update preview to see these columns\' effect before applying.</strong>'; }
          });
      });
  })();
  </script>
  @endsection
</x-hr-dashboard>

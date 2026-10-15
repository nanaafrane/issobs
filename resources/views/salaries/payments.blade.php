<x-hr-dashboard>

  @section('css')
  <style>
      .sp-metric { border-radius: 10px; padding: .9rem 1rem; background: #fff; border: 1px solid #e5e7eb; height: 100%; }
      .sp-metric .label { font-size: .8rem; color: #6b7280; }
      .sp-metric .v { font-size: 1.3rem; font-weight: 700; color: #111827; line-height: 1.3; }
      .sp-card { border-radius: 12px; border: 1px solid #e5e7eb; }
      .sp-card .card-header { background: #f9fafb; border-bottom: 1px solid #e5e7eb; font-weight: 600; }
      .sp-run { border: 1px solid #e5e7eb; border-radius: 10px; padding: .7rem .85rem; min-width: 13rem; background: #fff; }
      .sp-run.open { border-color: #16a34a; box-shadow: 0 0 0 2px rgba(22,163,74,.15); }
      .sp-table td, .sp-table th { white-space: nowrap; font-size: .84rem; }
      .sp-table td.num, .sp-table th.num { text-align: right; font-variant-numeric: tabular-nums; }
      .sp-table th.paycol { background: #eef2ff; }
      .sp-table td.bal, .sp-table th.bal { color: #6b7280; }
      .sp-table tr.r-hold td:first-child { padding-left: 1.4rem; color: #b45309; }
      .sp-table tr.r-topup td:first-child { padding-left: 1.4rem; color: #1d4ed8; }
      .sp-table tr.r-main td:first-child { font-weight: 600; }
      .sp-table tfoot td { font-weight: 700; background: #f9fafb; }
      .sp-table a.cell { color: inherit; text-decoration: underline dotted; cursor: pointer; }
      .neg { color: #b91c1c; }
      .pos-owed { color: #b45309; }
      .sticky-col { position: sticky; left: 0; background: #fff; z-index: 1; }
      tfoot .sticky-col { background: #f9fafb; }
  </style>
  @endsection

  @section('side_nav')
      @include('partials.payroll_side_nav')
  @endsection

  @section('content')
  @php
      $money = fn ($v) => number_format((float) $v, 2);
      $r = $report;
      $cols = $r['columns'];
      $t = $r['totals'];
      $b = $r['breakdown'];
      $open = $r['open'];
      $nextNumber = ((int) $r['runs']->max('number')) + 1;
      $ym = $month->format('Y-m');
      $rowName = fn ($row) => $row['row'] === 'hold' ? 'Hold' : ($row['row'] === 'topup' ? 'Top-ups' : $row['office']);
      $owedChanges = $r['changed']->where('payment_status', 'approved')->where('difference', '>', 0);
  @endphp
  <div class="container-xxl flex-grow-1 container-p-y">

      @foreach(['success' => 'success', 'primary' => 'primary', 'error' => 'danger'] as $key => $color)
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
              <h3 class="mb-1"><i class="bx bx-list-check"></i> Salary payments · {{ $month->format('F Y') }}</h3>
              <div class="text-muted small" style="max-width:56rem">
                  What each payment paid, per office, and what is still owed. Paid amounts are fixed when paid;
                  the pay table is always the <strong>current</strong> net, so a salary changed after it was paid shows below as a difference.
              </div>
          </div>
          <form method="GET" action="{{ route('salary-payments.index') }}" class="d-flex gap-2 align-items-end">
              <div>
                  <label class="form-label small mb-0" for="spMonth">Payroll month</label>
                  <select name="month" id="spMonth" class="form-select form-select-sm" onchange="this.form.submit()">
                      @if(! $months->contains($ym))
                          <option value="{{ $ym }}" selected>{{ $month->format('F Y') }}</option>
                      @endif
                      @foreach($months as $m)
                          <option value="{{ $m }}" @selected($m === $ym)>{{ \Carbon\Carbon::parse($m . '-01')->format('F Y') }}</option>
                      @endforeach
                  </select>
              </div>
              <a class="btn btn-sm btn-outline-success" href="{{ route('salary-payments.export', ['month' => $ym]) }}"><i class="bx bx-spreadsheet"></i> Excel</a>
          </form>
      </div>

      {{-- Where approvals go right now --}}
      <div class="alert {{ $open ? 'alert-success' : 'alert-secondary' }} py-2 small mb-3" id="spOpenBanner">
          @if($open)
              <i class="bx bx-radio-circle-marked"></i> Approvals for {{ $month->format('F') }} are going into
              <strong>Payment {{ $open->number }}</strong> ({{ \Carbon\Carbon::parse($open->run_date)->format('d M Y') }}).
              Close it when this payment is done; the next approval then starts Payment {{ $nextNumber }}.
          @else
              <i class="bx bx-radio-circle"></i> No payment is open for {{ $month->format('F') }}. The next approval starts
              <strong>Payment {{ $nextNumber }}</strong> dated that day{{ $canManage ? ', or start it now to set its date and funds released' : '' }}.
          @endif
      </div>

      <div class="row g-3 mb-3">
          <div class="col-md-3 col-6"><div class="sp-metric"><div class="label">Pay table (current nets)</div><div class="v">{{ $money($t['owed']) }}</div><div class="small text-muted">{{ number_format(collect($r['rows'])->sum('staff')) }} salaries and top-ups</div></div></div>
          <div class="col-md-3 col-6"><div class="sp-metric"><div class="label">Paid</div><div class="v">{{ $money($t['paid_total']) }}</div><div class="small text-muted">in {{ $r['runs']->count() }} {{ $r['runs']->count() === 1 ? 'payment' : 'payments' }}{{ collect($cols)->firstWhere('key', 'before') ? ' + before runs' : '' }}</div></div></div>
          <div class="col-md-3 col-6"><div class="sp-metric"><div class="label">Outstanding</div><div class="v {{ $t['outstanding'] < 0 ? 'neg' : '' }}">{{ $money($t['outstanding']) }}</div><div class="small text-muted">breakdown below</div></div></div>
          <div class="col-md-3 col-6"><div class="sp-metric"><div class="label">Changed after payment</div><div class="v">{{ $r['changed']->count() }}</div><div class="small text-muted">{{ $money($b['owed_after_change']) }} owed · {{ $money(abs($b['overpaid_after_change'])) }} overpaid</div></div></div>
      </div>

      {{-- Payments of the month --}}
      <div class="card sp-card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
              <span>Payments</span>
              @if($canManage)
                  <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#spStart" aria-expanded="false">
                      <i class="bx bx-plus"></i> Start Payment {{ $nextNumber }}
                  </button>
              @endif
          </div>
          <div class="card-body">
              @if($canManage)
                  <form method="POST" action="{{ route('salary-payments.start') }}" class="collapse border rounded p-3 mb-3 bg-light" id="spStart"
                        data-confirm="{{ $open ? 'This closes Payment ' . $open->number . ' and starts Payment ' . $nextNumber . '. Continue?' : '' }}">
                      @csrf
                      <input type="hidden" name="month" value="{{ $month->toDateString() }}">
                      <div class="row g-2 align-items-end">
                          <div class="col-md-2"><label class="form-label small mb-0">Date</label><input type="date" name="run_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required></div>
                          <div class="col-md-2"><label class="form-label small mb-0">Funds released (optional)</label><input type="number" step="0.01" min="0" name="funds_released" class="form-control form-control-sm"></div>
                          <div class="col-md-3"><label class="form-label small mb-0">Reference (optional)</label><input type="text" name="reference" maxlength="120" class="form-control form-control-sm" placeholder="Bank / transfer reference"></div>
                          <div class="col-md-3"><label class="form-label small mb-0">Notes (optional)</label><input type="text" name="notes" maxlength="255" class="form-control form-control-sm"></div>
                          <div class="col-md-2"><button class="btn btn-sm btn-primary w-100" type="submit">Start Payment {{ $nextNumber }}</button></div>
                      </div>
                      @if($open)
                          <div class="small text-muted mt-2">Payment {{ $open->number }} is open; starting this one closes it.</div>
                      @endif
                  </form>
              @endif

              @if($r['runs']->isEmpty())
                  <div class="text-muted small">No payments yet for {{ $month->format('F Y') }}.{{ collect($cols)->firstWhere('key', 'before') ? ' Salaries approved before payments were tracked are shown as "Before payment runs".' : '' }}</div>
              @else
                  <div class="d-flex flex-wrap gap-2">
                      @foreach($r['runs'] as $run)
                          @php $paid = $t['paid'][(string) $run->id] ?? 0; $f = $r['funds'][(string) $run->id] ?? null; @endphp
                          <div class="sp-run {{ $run->status === 'open' ? 'open' : '' }}" data-run="{{ $run->id }}">
                              <div class="d-flex justify-content-between gap-2">
                                  <strong>Payment {{ $run->number }}</strong>
                                  <span class="badge {{ $run->status === 'open' ? 'bg-success' : 'bg-secondary' }}">{{ $run->status === 'open' ? 'Open' : 'Closed' }}</span>
                              </div>
                              <div class="small text-muted">{{ \Carbon\Carbon::parse($run->run_date)->format('d M Y') }}{{ $run->auto_opened ? ' · started by an approval' : '' }}</div>
                              <div class="small">Paid <strong>{{ $money($paid) }}</strong></div>
                              @if($f)
                                  <div class="small">Released {{ $money($f['released']) }} · <span class="{{ $f['difference'] < 0 ? 'neg' : '' }}">variance {{ $money($f['difference']) }}</span></div>
                              @endif
                              @if($run->reference)
                                  <div class="small text-muted">Ref: {{ $run->reference }}</div>
                              @endif
                              @if($run->notes)
                                  <div class="small text-muted">{{ $run->notes }}</div>
                              @endif
                              @if($canManage)
                                  <div class="d-flex gap-1 mt-2">
                                      <button type="button" class="btn btn-xs btn-sm btn-outline-secondary py-0" data-bs-toggle="collapse" data-bs-target="#spEdit{{ $run->id }}">Edit</button>
                                      @if($run->status === 'open')
                                          <form method="POST" action="{{ route('salary-payments.close', $run->id) }}" onsubmit="return confirm('Close Payment {{ $run->number }}? What it paid can no longer be unapproved.')">
                                              @csrf
                                              <button class="btn btn-sm btn-outline-danger py-0" type="submit">Close</button>
                                          </form>
                                      @endif
                                  </div>
                                  <form method="POST" action="{{ route('salary-payments.update', $run->id) }}" class="collapse mt-2" id="spEdit{{ $run->id }}">
                                      @csrf @method('PUT')
                                      <input type="date" name="run_date" class="form-control form-control-sm mb-1" value="{{ \Carbon\Carbon::parse($run->run_date)->toDateString() }}" required>
                                      <input type="number" step="0.01" min="0" name="funds_released" class="form-control form-control-sm mb-1" value="{{ $run->funds_released }}" placeholder="Funds released">
                                      <input type="text" name="reference" maxlength="120" class="form-control form-control-sm mb-1" value="{{ $run->reference }}" placeholder="Reference">
                                      <input type="text" name="notes" maxlength="255" class="form-control form-control-sm mb-1" value="{{ $run->notes }}" placeholder="Notes">
                                      <button class="btn btn-sm btn-primary w-100" type="submit">Save</button>
                                  </form>
                              @endif
                          </div>
                      @endforeach
                  </div>
              @endif
          </div>
      </div>

      {{-- The report, in the layout of the old sheet --}}
      <div class="card sp-card mb-3">
          <div class="card-header">Payment report <span class="small text-muted fw-normal">· click a paid amount to see who it was</span></div>
          <div class="table-responsive">
              <table class="table table-sm table-bordered sp-table mb-0" id="spReport">
                  <thead>
                      <tr>
                          <th class="sticky-col">Office</th>
                          <th class="num">Staff</th>
                          <th class="num">Pay table</th>
                          @foreach($cols as $c)
                              <th class="num paycol">{{ $c['label'] }}<div class="small fw-normal">{{ $c['date'] ?? '' }}</div></th>
                              <th class="num bal">Balance</th>
                          @endforeach
                          <th class="num">Total paid</th>
                          <th class="num">Outstanding</th>
                      </tr>
                  </thead>
                  <tbody>
                      @forelse($r['rows'] as $row)
                          <tr class="r-{{ $row['row'] }}">
                              <td class="sticky-col">{{ $rowName($row) }}</td>
                              <td class="num">{{ number_format($row['staff']) }}</td>
                              <td class="num">{{ $money($row['owed']) }}</td>
                              @foreach($cols as $c)
                                  @php $p = $row['paid'][$c['key']] ?? 0; @endphp
                                  <td class="num">
                                      @if(abs($p) >= 0.005)
                                          <a class="cell" data-run="{{ $c['key'] }}" data-field="{{ $row['field_id'] }}" data-row="{{ $row['row'] }}"
                                             data-title="{{ $row['office'] }} · {{ $rowName($row) === $row['office'] ? 'Main' : $rowName($row) }} · {{ $c['label'] }}">{{ $money($p) }}</a>
                                      @else
                                          <span class="text-muted">–</span>
                                      @endif
                                  </td>
                                  <td class="num bal {{ $row['balance'][$c['key']] < 0 ? 'neg' : '' }}">{{ $money($row['balance'][$c['key']]) }}</td>
                              @endforeach
                              <td class="num">{{ $money($row['paid_total']) }}</td>
                              <td class="num {{ $row['outstanding'] < 0 ? 'neg' : ($row['outstanding'] > 0 ? 'pos-owed' : '') }}">{{ $money($row['outstanding']) }}</td>
                          </tr>
                      @empty
                          <tr><td colspan="{{ 5 + 2 * count($cols) }}" class="text-muted">No salaries for {{ $month->format('F Y') }}.</td></tr>
                      @endforelse
                  </tbody>
                  <tfoot>
                      <tr>
                          <td class="sticky-col">Total</td>
                          <td class="num">{{ number_format(collect($r['rows'])->sum('staff')) }}</td>
                          <td class="num">{{ $money($t['owed']) }}</td>
                          @foreach($cols as $c)
                              <td class="num">{{ $money($t['paid'][$c['key']] ?? 0) }}</td>
                              <td class="num bal">{{ $money($t['balance'][$c['key']] ?? 0) }}</td>
                          @endforeach
                          <td class="num">{{ $money($t['paid_total']) }}</td>
                          <td class="num">{{ $money($t['outstanding']) }}</td>
                      </tr>
                      @if($r['funds'])
                          <tr>
                              <td class="sticky-col">Funds released</td><td></td><td></td>
                              @foreach($cols as $c)
                                  <td class="num">{{ isset($r['funds'][$c['key']]) ? $money($r['funds'][$c['key']]['released']) : '' }}</td><td></td>
                              @endforeach
                              <td></td><td></td>
                          </tr>
                          <tr>
                              <td class="sticky-col">Variance (released − paid)</td><td></td><td></td>
                              @foreach($cols as $c)
                                  @php $f = $r['funds'][$c['key']] ?? null; @endphp
                                  <td class="num {{ $f && $f['difference'] < 0 ? 'neg' : '' }}">{{ $f ? $money($f['difference']) : '' }}</td><td></td>
                              @endforeach
                              <td></td><td></td>
                          </tr>
                      @endif
                  </tfoot>
              </table>
          </div>
      </div>

      <div class="row g-3 mb-3">
          <div class="col-lg-4">
              <div class="card sp-card h-100">
                  <div class="card-header">Where the outstanding is</div>
                  <table class="table table-sm mb-0 sp-table" id="spBreakdown">
                      <tr><td>Not yet paid (pending)</td><td class="num">{{ $money($b['not_paid']) }}</td></tr>
                      <tr><td>On hold</td><td class="num">{{ $money($b['held']) }}</td></tr>
                      <tr><td>Top-ups not yet paid</td><td class="num">{{ $money($b['topups']) }}</td></tr>
                      <tr><td>Owed: net went up after payment</td><td class="num">{{ $money($b['owed_after_change']) }}</td></tr>
                      <tr><td>Overpaid: net went down after payment</td><td class="num {{ $b['overpaid_after_change'] < 0 ? 'neg' : '' }}">{{ $money($b['overpaid_after_change']) }}</td></tr>
                      @if(abs($b['other']) >= 0.01)
                          <tr><td>Other</td><td class="num">{{ $money($b['other']) }}</td></tr>
                      @endif
                      <tr class="fw-bold"><td>Outstanding</td><td class="num">{{ $money($t['outstanding']) }}</td></tr>
                  </table>
              </div>
          </div>
          <div class="col-lg-8">
              <div class="card sp-card h-100">
                  <div class="card-header d-flex justify-content-between align-items-center">
                      <span>Changed after payment</span>
                      <span class="small text-muted fw-normal">net now vs what was paid</span>
                  </div>
                  @if($r['changed']->isEmpty())
                      <div class="card-body text-muted small">No salary changed after it was paid.</div>
                  @else
                      <form method="POST" action="{{ route('salary-payments.differences') }}" id="spDiffForm"
                            data-confirm="Record the selected differences as paid in {{ $open ? 'Payment ' . $open->number : 'a new Payment ' . $nextNumber }}?">
                          @csrf
                          <input type="hidden" name="month" value="{{ $month->toDateString() }}">
                          <div class="table-responsive" style="max-height: 22rem">
                              <table class="table table-sm sp-table mb-0">
                                  <thead><tr>
                                      <th class="{{ $canManage ? '' : 'd-none' }}"><input type="checkbox" id="spDiffAll" aria-label="Select all owed"></th>
                                      <th>Employee</th><th>Office</th><th>Status</th><th class="num">Paid</th><th class="num">Net now</th><th class="num">Difference</th>
                                  </tr></thead>
                                  <tbody>
                                      @foreach($r['changed'] as $c)
                                          @php $payable = $c->difference > 0 && $c->payment_status === 'approved'; @endphp
                                          <tr>
                                              <td class="{{ $canManage ? '' : 'd-none' }}">
                                                  @if($payable && $canManage)
                                                      <input type="checkbox" class="sp-diff" name="salary[]" value="{{ $c->id }}">
                                                  @endif
                                              </td>
                                              <td>{{ $c->employee_name ?? 'Unknown' }} <span class="text-muted small">#{{ $c->id }}</span></td>
                                              <td>{{ $c->field_name }}</td>
                                              <td>{{ ucfirst($c->payment_status) }}</td>
                                              <td class="num">{{ $money($c->paid) }}</td>
                                              <td class="num">{{ $money($c->net_salary) }}</td>
                                              <td class="num {{ $c->difference < 0 ? 'neg' : 'pos-owed' }}">{{ $c->difference > 0 ? '+' : '' }}{{ $money($c->difference) }}
                                                  <div class="small text-muted">{{ $c->difference > 0 ? 'owed to staff' : 'overpaid' }}</div></td>
                                          </tr>
                                      @endforeach
                                  </tbody>
                              </table>
                          </div>
                          @if($canManage && $owedChanges->isNotEmpty())
                              <div class="card-body py-2 d-flex justify-content-between align-items-center">
                                  <span class="small text-muted">Overpaid amounts are not taken back here; recover them through a deduction.</span>
                                  <button class="btn btn-sm btn-warning" type="submit" id="spDiffPay">Pay selected differences into {{ $open ? 'Payment ' . $open->number : 'Payment ' . $nextNumber }}</button>
                              </div>
                          @endif
                      </form>
                  @endif
              </div>
          </div>
      </div>
  </div>

  <div class="modal fade" id="spCellModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-scrollable">
          <div class="modal-content">
              <div class="modal-header"><h5 class="modal-title" id="spCellTitle">Paid</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
              <div class="modal-body" id="spCellBody">Loading…</div>
          </div>
      </div>
  </div>
  @endsection

  @section('scripts')
  <script>
  (function () {
      var cellUrl = {!! json_encode(route('salary-payments.cell')) !!};
      var month = {!! json_encode($ym) !!};
      var modalEl = document.getElementById('spCellModal');
      var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; };
      var fmt = function (n) { return Number(n).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}); };
      var kinds = {salary: '', topup: 'top-up', difference: 'difference'};

      document.querySelectorAll('#spReport a.cell').forEach(function (a) {
          a.addEventListener('click', function () {
              document.getElementById('spCellTitle').textContent = a.dataset.title;
              var body = document.getElementById('spCellBody');
              body.textContent = 'Loading…';
              if (window.bootstrap && bootstrap.Modal) { bootstrap.Modal.getOrCreateInstance(modalEl).show(); }
              var qs = new URLSearchParams({month: month, run: a.dataset.run, field: a.dataset.field || '', row: a.dataset.row});
              fetch(cellUrl + '?' + qs.toString(), {headers: {'Accept': 'application/json'}, credentials: 'same-origin'})
                  .then(function (r) { if (!r.ok) { throw new Error(r.status); } return r.json(); })
                  .then(function (d) {
                      var html = '<div class="small text-muted mb-2">' + d.count + ' paid · ' + fmt(d.total) + '</div><table class="table table-sm"><tbody>';
                      d.people.forEach(function (p) {
                          html += '<tr><td>' + esc(p.name) + (kinds[p.kind] ? ' <span class="badge bg-label-warning">' + esc(kinds[p.kind]) + '</span>' : '')
                              + '</td><td class="text-end">' + fmt(p.amount) + '</td></tr>';
                      });
                      body.innerHTML = html + '</tbody></table>';
                  })
                  .catch(function () { body.textContent = 'Could not load the list.'; });
          });
      });

      var start = document.getElementById('spStart');
      if (start) {
          start.addEventListener('submit', function (e) {
              if (start.dataset.confirm && !confirm(start.dataset.confirm)) { e.preventDefault(); }
          });
      }

      var all = document.getElementById('spDiffAll');
      if (all) {
          all.addEventListener('change', function () {
              document.querySelectorAll('.sp-diff').forEach(function (c) { c.checked = all.checked; });
          });
      }
      var form = document.getElementById('spDiffForm');
      if (form) {
          form.addEventListener('submit', function (e) {
              if (!form.querySelector('.sp-diff:checked')) { e.preventDefault(); alert('Select the salaries whose difference you are paying.'); return; }
              if (!confirm(form.dataset.confirm)) { e.preventDefault(); }
          });
      }
  })();
  </script>
  @endsection
</x-hr-dashboard>

@use('App\Support\Dashboards\Fmt')
@php
  $k = $d['kpi'];
  $w = $d['workforce'];
  $reportLink = route('employee.report', $ctx->reportQuery(['weekly', 'monthly', 'quarterly', 'semiannual', 'yearly']));
  $turnoverStatus = $k['turnover'] === null ? null : ($k['turnover'] <= 2 ? 'good' : ($k['turnover'] <= 4 ? 'warn' : 'bad'));
@endphp
<x-sales-dashboard>

  @section('side_nav')
    @include('dashboards.nav.' . $nav)
  @endsection

  @section('css')
    @include('dashboards._styles')
  @endsection

  @section('content')
  <div class="container-xxl flex-grow-1 container-p-y dash">
    @include('dashboards._header', ['title' => 'HR dashboard'])

    <div class="row g-3 mb-3">
      <div class="col-xl-2 col-md-4 col-6">
        @include('dashboards._kpi', [
          'label' => 'Headcount', 'value' => Fmt::int($k['closing']),
          'delta' => ['text' => ($k['net'] > 0 ? '+' : '') . $k['net'] . ' since ' . $ctx->from->format('j M') . ' (' . Fmt::int($k['opening']) . ')', 'dir' => $k['net'] > 0 ? 'up' : ($k['net'] < 0 ? 'down' : 'flat')],
          'neutral' => true, 'note' => 'Employed at ' . ($ctx->isRunning() ? 'the end of the period' : $ctx->to->format('j M Y')), 'href' => $reportLink,
        ])
      </div>
      <div class="col-xl-2 col-md-4 col-6">
        @include('dashboards._kpi', [
          'label' => 'Joined', 'value' => Fmt::int($k['hires'] + $k['reinstated']),
          'note' => Fmt::int($k['hires']) . ' new · ' . Fmt::int($k['reinstated']) . ' re-instated',
        ])
      </div>
      <div class="col-xl-2 col-md-4 col-6">
        @include('dashboards._kpi', [
          'label' => 'Left', 'value' => Fmt::int($k['terminated']),
          'status' => $turnoverStatus, 'statusText' => $k['turnover'] === null ? null : Fmt::pct($k['turnover']) . ' turnover',
          'note' => Fmt::int($k['early_leavers']) . ' within 90 days of joining',
        ])
      </div>
      <div class="col-xl-2 col-md-4 col-6">
        @include('dashboards._kpi', [
          'label' => 'Transfers', 'value' => Fmt::int($k['transfers_in']) . ' in / ' . Fmt::int($k['transfers_out']) . ' out',
          'note' => 'Between field offices',
        ])
      </div>
      <div class="col-xl-2 col-md-4 col-6">
        @include('dashboards._kpi', [
          'label' => 'Awaiting approval', 'value' => Fmt::int($k['pending']),
          'status' => $k['pending'] ? 'warn' : 'good', 'statusText' => $k['pending'] ? 'Requests at head office' : 'Nothing waiting',
          'note' => 'Movements not yet counted', 'href' => route('employees.movements'),
        ])
      </div>
      <div class="col-xl-2 col-md-4 col-6">
        @include('dashboards._kpi', [
          'label' => 'Complete records', 'value' => Fmt::pct($w['complete_pct'], 0),
          'status' => $w['complete_pct'] === null ? null : ($w['complete_pct'] >= 95 ? 'good' : ($w['complete_pct'] >= 80 ? 'warn' : 'bad')),
          'statusText' => $w['complete_pct'] === null ? null : Fmt::int(count($w['missing'])) . ' kinds of detail missing',
          'note' => 'Of ' . Fmt::int($w['active']) . ' active employees',
        ])
      </div>
    </div>

    @if(! $k['reconciles'] || $d['issues'])
      <div class="alert alert-warning py-2 small">
        @if(! $k['reconciles'])Headcount does not reconcile for this month. @endif
        @if($d['issues']){{ Fmt::int($d['issues']) }} movement records need correcting (missing dates, order conflicts or office mismatches) — see the
          <a href="{{ $reportLink }}">Employees report</a>.@endif
      </div>
    @endif

    <div class="row g-3 mb-3">
      <div class="col-xl-8">
        <div class="panel">
          <div class="panel-h"><h6>Headcount and movements</h6><span class="hint">{{ $d['series']['caption'] }} · approved movements</span></div>
          <div class="legend">
            <span><i style="background:var(--s1)"></i>Joined</span>
            <span><i style="background:var(--s2)"></i>Left</span>
          </div>
          <div id="ch-moves" class="chart" style="min-height:200px"></div>
          <div class="legend mt-1"><span><i style="background:var(--s3)"></i>Headcount at the end of each {{ $ctx->period === 'custom' ? 'bar' : 'period' }}</span></div>
          <div id="ch-head" class="chart" style="min-height:170px"></div>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="panel">
          <div class="panel-h"><h6>Why people left</h6><span class="hint">{{ $d['year']['label'] }} · {{ Fmt::int($d['year']['terminated']) }} leavers · {{ Fmt::pct($d['year']['turnover']) }} turnover</span></div>
          <div class="panel-b">
            @php($maxR = max(array_column($d['by_reason'], 'value') ?: [0]))
            @forelse($d['by_reason'] as $r)
              <ul class="bar-list"><li>
                <span>{{ $r['label'] }}</span><span class="v">{{ $r['value'] }}</span>
                <span class="track"><span class="fill d-block" style="width: {{ $maxR ? round($r['value'] / $maxR * 100, 1) : 0 }}%; background: var(--s2)"></span></span>
              </li></ul>
            @empty
              <div class="empty">No terminations in {{ $d['year']['label'] }}.</div>
            @endforelse
            <div class="small mt-3" style="color:var(--ink-2)"><strong style="color:var(--ink)">How long leavers stayed</strong></div>
            <table class="dt mt-1">
              @foreach($d['tenure'] as $band => $n)
                @continue($band === 'Unknown' && ! $n)
                <tr><td>{{ $band }}</td><td class="num">{{ $n }}</td></tr>
              @endforeach
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-xl-8">
        <div class="panel">
          <div class="panel-h"><h6>Offices — {{ $ctx->label() }}</h6><span class="hint">Start + joined + transfers in − left − transfers out = end</span></div>
          <div class="panel-b table-responsive">
            <table class="dt">
              <thead><tr><th>Office</th><th class="num">Start</th><th class="num">New</th><th class="num">Re-instated</th><th class="num">In</th>
                <th class="num">Left</th><th class="num">Out</th><th class="num">End</th><th class="num">Turnover</th></tr></thead>
              <tbody>
              @forelse($d['offices'] as $o)
                <tr>
                  <td>@if($o['field_id'] ?? null)<a href="{{ route('dashboard.show', ['board' => 'hr'] + $ctx->query(['field' => $o['field_id']])) }}">{{ $o['name'] }}</a>@else{{ $o['name'] }}@endif</td>
                  <td class="num">{{ $o['opening'] }}</td><td class="num">{{ $o['hires'] }}</td><td class="num">{{ $o['reinstated'] }}</td>
                  <td class="num">{{ $o['transfers_in'] }}</td><td class="num {{ $o['terminated'] ? 'neg' : '' }}">{{ $o['terminated'] }}</td>
                  <td class="num">{{ $o['transfers_out'] }}</td><td class="num"><strong>{{ $o['closing'] }}</strong></td>
                  <td class="num muted">{{ Fmt::pct($o['turnover'] ?? null) }}</td>
                </tr>
              @empty
                <tr><td colspan="9" class="empty">No employees in view.</td></tr>
              @endforelse
              </tbody>
              <tfoot><tr><td>Total</td><td class="num">{{ $k['opening'] }}</td><td class="num">{{ $k['hires'] }}</td><td class="num">{{ $k['reinstated'] }}</td>
                <td class="num">{{ $k['transfers_in'] }}</td><td class="num">{{ $k['terminated'] }}</td><td class="num">{{ $k['transfers_out'] }}</td>
                <td class="num">{{ $k['closing'] }}</td><td class="num">{{ Fmt::pct($k['turnover']) }}</td></tr></tfoot>
            </table>
          </div>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="panel">
          <div class="panel-h"><h6>Waiting for head office</h6><a class="hint" href="{{ route('employees.movements') }}">All requests →</a></div>
          <div class="panel-b">
            <ul class="alert-list">
            @forelse($d['pending'] as $p)
              <li>
                <span>{{ $p['name'] }}<br><span class="muted small">{{ $p['type'] }}{{ $p['reason'] ? ' · ' . $p['reason'] : '' }} · {{ $p['office'] }}</span></span>
                <span class="text-end small">
                  @if($p['waiting_days'] !== null)
                    <span class="chip {{ $p['waiting_days'] > 7 ? 'bad' : 'warn' }}">{{ $p['waiting_days'] > 7 ? '■' : '▲' }} {{ $p['waiting_days'] }}d</span>
                  @endif
                </span>
              </li>
            @empty
              <li class="empty d-block">No requests waiting.</li>
            @endforelse
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-xl-4 col-md-6">
        <div class="panel">
          <div class="panel-h"><h6>Workforce today</h6><span class="hint">Active employees</span></div>
          <div class="panel-b">
            <table class="dt">
              <tr><td>Guards</td><td class="num">{{ Fmt::int($w['guards']) }}</td></tr>
              <tr><td class="muted">&nbsp;&nbsp;not posted to a client</td><td class="num {{ $w['unposted_guards'] ? 'neg' : '' }}">{{ Fmt::int($w['unposted_guards']) }}</td></tr>
              <tr><td>Office &amp; supervisory staff</td><td class="num">{{ Fmt::int($w['staff']) }}</td></tr>
              @foreach($w['gender'] as $g => $n)
                <tr><td>{{ $g }}</td><td class="num">{{ Fmt::int($n) }} <span class="muted">({{ $w['active'] ? Fmt::pct($n / $w['active'] * 100, 0) : '—' }})</span></td></tr>
              @endforeach
            </table>
            @php($st = $d['by_status']['total'])
            <div class="small mt-3 muted">
              On record: {{ Fmt::int($st['total']) }} ·
              terminated {{ Fmt::int($st['terminated']) }} ·
              pending recruits {{ Fmt::int($st['recruit_pending']) }} ·
              pending edits {{ Fmt::int($st['edit_pending'] + $st['termination_pending'] + $st['reinstatement_pending']) }}
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-md-6">
        <div class="panel">
          <div class="panel-h"><h6>Records missing details</h6><span class="hint">Active employees</span></div>
          <div class="panel-b">
            @php($maxM = max(array_column($w['missing'], 'value') ?: [0]))
            @forelse($w['missing'] as $m)
              <ul class="bar-list"><li>
                <span>{{ $m['label'] }}</span><span class="v">{{ Fmt::int($m['value']) }} <span class="muted">({{ Fmt::pct($m['pct'], 0) }})</span></span>
                <span class="track"><span class="fill d-block" style="width: {{ $maxM ? round($m['value'] / $maxM * 100, 1) : 0 }}%; background: var(--s3)"></span></span>
              </li></ul>
            @empty
              <div class="empty">Every active record is complete.</div>
            @endforelse
            <a class="small" href="{{ url('employees') }}">Employee list →</a>
          </div>
        </div>
      </div>
      <div class="col-xl-4">
        @php($pc = $d['payroll_check'])
        @php($py = $d['payroll'])
        <div class="panel">
          <div class="panel-h"><h6>Payroll — {{ $py['months'] }}</h6>
            @if($py['visible'])<a class="hint" href="{{ route('salaries.report', $ctx->reportQuery(['monthly', 'quarterly', 'semiannual', 'yearly'], 'month')) }}">Salaries report →</a>@endif</div>
          <div class="panel-b">
            @if(! $pc)
              <div class="small muted mb-2">The employed-vs-salaries check runs for a single month — pick <em>Monthly</em> to see it.</div>
            @else
              <table class="dt">
                <tr><td>Employed during the month</td><td class="num">{{ Fmt::int($pc['employed']) }}</td></tr>
                <tr><td>Salaries raised</td><td class="num">{{ Fmt::int($pc['salaries']) }}</td></tr>
                <tr><td>Employed, no salary</td><td class="num {{ count($pc['no_salary']) ? 'neg' : '' }}">{{ Fmt::int(count($pc['no_salary'])) }}</td></tr>
                <tr><td>Salary, but not employed</td><td class="num {{ count($pc['not_employed']) ? 'neg' : '' }}">{{ Fmt::int(count($pc['not_employed'])) }}</td></tr>
              </table>
            @endif
            @if($py['visible'] && $py['heads'])
              <table class="dt mt-2">
                <tr><td>Net pay ({{ Fmt::int($py['heads']) }} people)</td><td class="num">{{ Fmt::money($py['net'], 0) }}</td></tr>
                <tr><td>Average net pay</td><td class="num">{{ Fmt::money($py['avg_net'], 0) }}</td></tr>
                <tr><td>Absence deductions</td><td class="num">{{ Fmt::money($py['absent'], 0) }}</td></tr>
                <tr><td>Overtime paid</td><td class="num">{{ Fmt::money($py['overtime'], 0) }}</td></tr>
              </table>
            @elseif($py['visible'])
              <div class="empty">No payroll uploaded for {{ $py['months'] }} yet.</div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
  @endsection

  @section('scripts')
    @include('dashboards._charts')
    <script>
      document.addEventListener('dash:charts', function () {
        var s = @json($d['series']);
        Dash.bars('#ch-moves', s.labels, [{ name: 'Joined', data: s.hires }, { name: 'Left', data: s.terminated }], { height: 200 });
        Dash.lines('#ch-head', s.labels, [{ name: 'Headcount', data: s.headcount }], { height: 170, colors: [Dash.colors.s3] });
      });
    </script>
  @endsection
</x-sales-dashboard>

@use('App\Support\Dashboards\Fmt')
@php
  $k = $d['kpi'];
  $covStatus = fn ($c) => $c === null ? null : ($c >= 95 ? 'good' : ($c >= 85 ? 'warn' : 'bad'));
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
    @include('dashboards._header', ['title' => 'Operations dashboard'])

    <div class="row g-3 mb-3">
      <div class="col-xl-2 col-md-4 col-6">
        @include('dashboards._kpi', [
          'label' => 'Post coverage', 'value' => Fmt::pct($k['coverage']),
          'status' => $covStatus($k['coverage']),
          'statusText' => $k['coverage'] === null ? null : Fmt::int($k['uncovered_days']) . ' guard-shifts not covered',
          'note' => 'Signed-in guards vs shift plans', 'href' => route('attendance.index', ['month' => $ctx->month->format('Y-m')]),
        ])
      </div>
      <div class="col-xl-2 col-md-4 col-6">
        @include('dashboards._kpi', [
          'label' => 'Shortage', 'value' => Fmt::int($k['shortage']) . ' guards',
          'status' => $k['shortage'] ? 'bad' : 'good', 'statusText' => $k['shortage'] ? 'at ' . Fmt::int($k['clients_short']) . ' clients' : 'Every post filled',
          'note' => Fmt::int($k['posted']) . ' posted of ' . Fmt::int($k['required']) . ' required',
        ])
      </div>
      <div class="col-xl-2 col-md-4 col-6">
        @include('dashboards._kpi', [
          'label' => 'Active guards', 'value' => Fmt::int($k['active_guards']),
          'status' => $k['unposted'] ? 'warn' : null, 'statusText' => Fmt::int($k['unposted']) . ' not posted',
          'note' => 'Across ' . Fmt::int($k['clients']) . ' active clients',
        ])
      </div>
      <div class="col-xl-2 col-md-4 col-6">
        @include('dashboards._kpi', [
          'label' => 'Absence rate', 'value' => Fmt::pct($k['absence_rate']),
          'status' => $k['absence_rate'] === null ? null : ($k['absence_rate'] <= 3 ? 'good' : ($k['absence_rate'] <= 7 ? 'warn' : 'bad')),
          'statusText' => $k['absence_rate'] === null ? null : Fmt::int($k['absent']) . ' absent days',
          'note' => Fmt::int($k['worked']) . ' guard-days worked',
        ])
      </div>
      <div class="col-xl-2 col-md-4 col-6">
        @include('dashboards._kpi', [
          'label' => 'Sign-ins to review', 'value' => Fmt::int($k['pending_scans']),
          'status' => $k['pending_scans'] ? 'warn' : 'good', 'statusText' => $k['pending_scans'] ? 'Not counted until approved' : 'All reviewed',
          'note' => 'Flagged scans', 'href' => route('attendance.review'),
        ])
      </div>
      <div class="col-xl-2 col-md-4 col-6">
        @include('dashboards._kpi', [
          'label' => 'Overtime', 'value' => Fmt::int($k['overtime_count']) . ' shifts',
          'note' => Fmt::money($k['overtime_amount'], 0) . ' this month', 'href' => route('overtime.report', ['period' => 'monthly', 'date' => $ctx->month->toDateString()]),
        ])
      </div>
    </div>

    @if($k['no_plan'] || $k['no_qr'] || $k['no_site'])
      <div class="panel h-auto mb-3">
        <div class="panel-b pt-3 d-flex flex-wrap gap-4 small">
          <span class="fw-bold" style="color:var(--ink)">Set-up gaps</span>
          @if($k['no_plan'])<span><span class="chip bad">■ {{ $k['no_plan'] }}</span> clients have no shift plan — no shortage or coverage can be worked out for them</span>@endif
          @if($k['no_qr'])<span><span class="chip warn">▲ {{ $k['no_qr'] }}</span> clients have no QR poster</span>@endif
          @if($k['no_site'])<span><span class="chip warn">▲ {{ $k['no_site'] }}</span> clients have no site location (sign-ins can't be checked by GPS)</span>@endif
          <a href="{{ route('attendance.index') }}">Attendance →</a>
        </div>
      </div>
    @endif

    <div class="row g-3 mb-3">
      <div class="col-xl-8">
        <div class="panel">
          <div class="panel-h"><h6>Guards on duty each day</h6><span class="hint">Approved sign-ins vs guards the shift plans require</span></div>
          <div class="legend">
            <span><i style="background:var(--s1)"></i>Present</span>
            <span><i style="background:var(--ink-3)"></i>Required (dashed)</span>
          </div>
          <div id="ch-daily" class="chart"></div>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="panel">
          <div class="panel-h"><h6>Overtime reasons</h6><span class="hint">{{ $ctx->month->format('M Y') }}</span></div>
          <div class="panel-b">
            @php($maxO = max(array_column($d['overtime_reasons'], 'value') ?: [0]))
            @forelse($d['overtime_reasons'] as $o)
              <ul class="bar-list"><li>
                <span>{{ $o['label'] }}</span><span class="v">{{ $o['value'] }} · {{ Fmt::money($o['amount'], 0) }}</span>
                <span class="track"><span class="fill d-block" style="width: {{ $maxO ? round($o['value'] / $maxO * 100, 1) : 0 }}%; background: var(--s2)"></span></span>
              </li></ul>
            @empty
              <div class="empty">No overtime recorded this month.</div>
            @endforelse
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-12">
        <div class="panel">
          <div class="panel-h"><h6>Offices</h6><span class="hint">{{ $ctx->month->format('F Y') }}</span></div>
          <div class="panel-b table-responsive">
            <table class="dt">
              <thead><tr><th>Office</th><th class="num">Clients</th><th class="num">Guards</th><th class="num">Required</th><th class="num">Posted</th>
                <th class="num">Short</th><th class="num">Coverage</th><th class="num">Absence</th><th class="num">Overtime</th></tr></thead>
              <tbody>
              @forelse($d['offices'] as $o)
                <tr>
                  <td><a href="{{ route('dashboard.show', ['board' => 'operations', 'month' => $ctx->month->format('Y-m'), 'field' => $o['id']]) }}">{{ $o['name'] }}</a></td>
                  <td class="num">{{ $o['clients'] }}</td>
                  <td class="num">{{ $o['guards'] }}@if($o['unposted'])<span class="muted"> ({{ $o['unposted'] }} free)</span>@endif</td>
                  <td class="num">{{ $o['required'] }}</td><td class="num">{{ $o['posted'] }}</td>
                  <td class="num {{ $o['shortage'] ? 'neg' : '' }}">{{ $o['shortage'] }}</td>
                  <td class="num">@if($o['coverage'] !== null)<span class="chip {{ $covStatus($o['coverage']) }}">{{ Fmt::pct($o['coverage'], 0) }}</span>@else<span class="muted">—</span>@endif</td>
                  <td class="num">{{ Fmt::pct($o['absence_rate']) }}</td>
                  <td class="num">{{ $o['overtime'] }}</td>
                </tr>
              @empty
                <tr><td colspan="9" class="empty">No offices in view.</td></tr>
              @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-xl-7">
        <div class="panel">
          <div class="panel-h"><h6>Clients needing attention</h6><span class="hint">Biggest shortages first</span></div>
          <div class="panel-b table-responsive">
            <table class="dt">
              <thead><tr><th>Client</th><th class="num">Posted / req.</th><th class="num">Short</th><th class="num">Coverage</th></tr></thead>
              <tbody>
              @forelse($d['attention'] as $c)
                <tr>
                  <td><a href="{{ route('attendance.show', ['client' => $c['id'], 'month' => $ctx->month->format('Y-m')]) }}">{{ $c['name'] }}</a><br><span class="muted small">{{ $c['office'] }}</span></td>
                  <td class="num">@if($c['no_plan'])<span class="chip bad">■ No plan</span>@else{{ $c['posted'] }} / {{ $c['required'] }}@endif</td>
                  <td class="num {{ $c['shortage'] ? 'neg' : '' }}">{{ $c['shortage'] ?: '—' }}</td>
                  <td class="num">{{ Fmt::pct($c['coverage'], 0) }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="empty">Every client is fully posted and covered.</td></tr>
              @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="col-xl-5">
        <div class="panel">
          <div class="panel-h"><h6>Guards on record by office</h6><span class="hint">Today, all guard records</span></div>
          <div class="legend">
            <span><i style="background:var(--s1)"></i>Active</span>
            <span><i style="background:var(--s2)"></i>Terminated</span>
          </div>
          <div id="ch-guards" class="chart" style="min-height:300px"></div>
        </div>
      </div>
    </div>
  </div>
  @endsection

  @section('scripts')
    @include('dashboards._charts')
    <script>
      document.addEventListener('dash:charts', function () {
        var dl = @json($d['daily']);
        Dash.lines('#ch-daily', dl.labels, [
          { name: 'Present', type: 'column', data: dl.present },
          { name: 'Required', type: 'line', data: dl.required, dashed: true },
        ], { height: 280, min: 0, colors: [Dash.colors.s1, Dash.colors.ink3] });

        var g = @json($d['guard_status']);
        Dash.bars('#ch-guards', g.map(function (r) { return r.office; }),
          [{ name: 'Active', data: g.map(function (r) { return r.active; }) }, { name: 'Terminated', data: g.map(function (r) { return r.terminated; }) }],
          { height: 300, width: '60%', horizontal: true });
      });
    </script>
  @endsection
</x-sales-dashboard>

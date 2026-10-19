@use('App\Support\Dashboards\Fmt')
@php
  $k = $d['kpi'];
  $prevLabel = $ctx->previousLabel();
  $mtd = $ctx->isRunning();   // a period still running is not compared with a complete one
  $inPeriod = $ctx->label();
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
    @include('dashboards._header', ['title' => 'Accounts dashboard'])

    {{-- Headline figures --}}
    <div class="row g-3 mb-3">
      <div class="col-xl-2 col-md-4 col-6">
        @include('dashboards._kpi', [
          'label' => 'Invoiced', 'value' => Fmt::short($k['invoiced']), 'title' => Fmt::money($k['invoiced']),
          'delta' => $mtd ? null : Fmt::delta($k['invoiced'], $k['invoiced_prev'], $prevLabel),
          'note' => Fmt::int($k['invoiced_count']) . ' invoices for ' . $k['invoiced_months'],
          'href' => url('invoice-report'),
        ])
      </div>
      <div class="col-xl-2 col-md-4 col-6">
        @include('dashboards._kpi', [
          'label' => 'Collected', 'value' => Fmt::short($k['collected']), 'title' => Fmt::money($k['collected']),
          'note' => Fmt::int($k['collected_count']) . ' approved receipts', 'href' => url('receipt-report?' . http_build_query(['period' => 'custom', 'from' => $ctx->span()[0], 'to' => $ctx->span()[1]])),
          'delta' => $mtd ? null : Fmt::delta($k['collected'], $k['collected_prev'], $prevLabel),
        ])
      </div>
      <div class="col-xl-2 col-md-4 col-6">
        @include('dashboards._kpi', [
          'label' => 'Collection rate', 'value' => Fmt::pct($k['collection_rate']),
          'status' => $k['collection_rate'] === null ? null : ($k['collection_rate'] >= 90 ? 'good' : ($k['collection_rate'] >= 70 ? 'warn' : 'bad')),
          'statusText' => $k['collection_rate'] === null ? null : ($k['collection_rate'] >= 90 ? 'On track' : ($k['collection_rate'] >= 70 ? 'Below 90%' : 'Below 70%')),
          'note' => $k['collection_rate'] === null ? 'Shown for whole months only' : 'Collected ÷ invoiced, same months',
        ])
      </div>
      <div class="col-xl-2 col-md-4 col-6">
        @include('dashboards._kpi', [
          'label' => 'Receivables', 'value' => Fmt::short($k['receivable']), 'title' => Fmt::money($k['receivable']) . ' (owed ' . Fmt::money($k['receivable_owed']) . ' less credit ' . Fmt::money($k['credit']) . ')',
          'status' => $k['debtor_days'] === null ? null : ($k['debtor_days'] <= 45 ? 'good' : ($k['debtor_days'] <= 75 ? 'warn' : 'bad')),
          'statusText' => $k['debtor_days'] === null ? null : $k['debtor_days'] . ' debtor days',
          'note' => 'Owed today, net of credit', 'href' => route('receivables.aged', array_filter(['field' => $ctx->fieldId])),
        ])
      </div>
      <div class="col-xl-2 col-md-4 col-6">
        @include('dashboards._kpi', [
          'label' => 'Over 90 days', 'value' => Fmt::short($k['overdue_90']), 'title' => Fmt::money($k['overdue_90']),
          'status' => $k['overdue_90'] > 0 ? 'bad' : 'good',
          'statusText' => $k['receivable_owed'] > 0 ? Fmt::pct($k['overdue_90'] / $k['receivable_owed'] * 100) . ' of what is owed' : 'Nothing overdue',
          'note' => 'Oldest debts: chase first',
        ])
      </div>
      <div class="col-xl-2 col-md-4 col-6">
        @include('dashboards._kpi', [
          'label' => 'Expenses', 'value' => Fmt::short($k['expenses']), 'title' => Fmt::money($k['expenses']),
          'delta' => $mtd ? null : Fmt::delta($k['expenses'], $k['expenses_prev'], $prevLabel), 'upIsGood' => false,
          'note' => $k['expenses_pending_count'] ? Fmt::int($k['expenses_pending_count']) . ' awaiting approval (' . Fmt::short($k['expenses_pending']) . ')' : 'Approved at head office',
          'href' => route('expense.list'),
        ])
      </div>
    </div>

    {{-- Things waiting for someone --}}
    @if($k['awaiting']->cnt || $k['expenses_pending_count'] || $k['unpaid_invoices'])
    <div class="row g-3 mb-3">
      <div class="col-12">
        <div class="panel h-auto">
          <div class="panel-b pt-3 d-flex flex-wrap gap-4 small">
            <span class="fw-bold" style="color:var(--ink)">Needs action</span>
            @if($k['awaiting']->cnt)
              <a href="{{ route('receipt.pending') }}"><span class="chip warn">▲ {{ Fmt::int($k['awaiting']->cnt) }}</span> receipts ({{ Fmt::money($k['awaiting']->received) }}) waiting for head-office approval — not counted as collected yet</a>
            @endif
            @if($k['expenses_pending_count'])
              <a href="{{ route('expense.list') }}"><span class="chip warn">▲ {{ Fmt::int($k['expenses_pending_count']) }}</span> expenses waiting for a decision</a>
            @endif
            @if($k['unpaid_invoices'])
              <a href="{{ url('invoiceOutstanding') }}"><span class="chip flat">■ {{ Fmt::int($k['unpaid_invoices']) }}</span> of the invoices for {{ $k['invoiced_months'] }} have nothing paid yet</a>
            @endif
          </div>
        </div>
      </div>
    </div>
    @endif

    <div class="row g-3 mb-3">
      {{-- 12-month trend --}}
      <div class="col-xl-8">
        <div class="panel">
          <div class="panel-h"><h6>Billing, collections and spend</h6><span class="hint">{{ $d['trend']['caption'] }} · GH₵</span></div>
          <div class="legend">
            @if($d['trend']['invoiced'])<span><i style="background:var(--s1)"></i>Invoiced (by invoice month)</span>@endif
            <span><i style="background:var(--s2)"></i>Collected (approved receipts)</span>
            <span><i style="background:var(--s3)"></i>Expenses (approved)</span>
          </div>
          <div id="ch-trend" class="chart"></div>
          @unless($d['trend']['invoiced'])<div class="panel-b small muted pt-0">Invoices are raised per month, so they are plotted only when each bar is a month or longer.</div>@endunless
        </div>
      </div>
      {{-- Aging --}}
      <div class="col-xl-4">
        <div class="panel">
          <div class="panel-h"><h6>Receivables by age</h6><span class="hint">As of today · {{ Fmt::int($d['aging']['clients_owing']) }} clients owing</span></div>
          <div id="ch-aging" class="chart"></div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-3">
      {{-- Offices --}}
      <div class="col-xl-8">
        <div class="panel">
          <div class="panel-h"><h6>Offices</h6><span class="hint">Collected in {{ $inPeriod }}; invoices, payroll &amp; expenses for {{ $k['invoiced_months'] }}. Contribution = service revenue (before levies &amp; VAT) − payroll cost − expenses</span></div>
          <div class="panel-b table-responsive">
            @if(count($d['offices']))
            <table class="dt">
              <thead><tr>
                <th>Office</th><th class="num">Invoiced</th><th class="num">Collected</th><th class="num">Rate</th>
                <th class="num">Receivable</th><th class="num">Payroll</th><th class="num">Expenses</th><th class="num">Contribution</th>
              </tr></thead>
              <tbody>
              @foreach($d['offices'] as $o)
                <tr>
                  <td><a href="{{ route('dashboard.show', ['board' => 'accounts'] + $ctx->query(['field' => $o['id']])) }}">{{ $o['name'] }}</a></td>
                  <td class="num">{{ number_format($o['invoiced'], 0) }}</td>
                  <td class="num">{{ number_format($o['collected'], 0) }}</td>
                  <td class="num">{{ Fmt::pct($o['rate'], 0) }}</td>
                  <td class="num">{{ number_format($o['receivable'], 0) }}</td>
                  <td class="num">{{ number_format($o['payroll'], 0) }}</td>
                  <td class="num">{{ number_format($o['expenses'], 0) }}</td>
                  <td class="num {{ $o['contribution'] < 0 ? 'neg' : '' }}">{{ number_format($o['contribution'], 0) }}
                    <span class="muted">{{ $o['margin'] !== null ? '(' . Fmt::pct($o['margin'], 0) . ')' : '' }}</span></td>
                </tr>
              @endforeach
              </tbody>
              @php($sum = fn ($key) => array_sum(array_column($d['offices'], $key)))
              <tfoot><tr>
                <td>Total</td><td class="num">{{ number_format($sum('invoiced'), 0) }}</td><td class="num">{{ number_format($sum('collected'), 0) }}</td>
                <td class="num">{{ $sum('invoiced') > 0 ? Fmt::pct($sum('collected') / $sum('invoiced') * 100, 0) : '—' }}</td>
                <td class="num">{{ number_format($sum('receivable'), 0) }}</td><td class="num">{{ number_format($sum('payroll'), 0) }}</td>
                <td class="num">{{ number_format($sum('expenses'), 0) }}</td><td class="num {{ $sum('contribution') < 0 ? 'neg' : '' }}">{{ number_format($sum('contribution'), 0) }}</td>
              </tr></tfoot>
            </table>
            @else
              <div class="empty">No field offices in view.</div>
            @endif
          </div>
        </div>
      </div>
      {{-- Top debtors --}}
      <div class="col-xl-4">
        <div class="panel">
          <div class="panel-h"><h6>Largest balances</h6><a class="hint" href="{{ route('receivables.aged', array_filter(['field' => $ctx->fieldId])) }}">Aged receivables →</a></div>
          <div class="panel-b">
            @forelse($d['aging']['top'] as $r)
              <ul class="alert-list"><li>
                <span>
                  <a href="{{ route('receivables.aged.client', $r['client_id']) }}">{{ $r['client'] }}</a><br>
                  <span class="muted small">{{ $r['field'] }} · {{ $r['invoices'] }} {{ \Illuminate\Support\Str::plural('invoice', $r['invoices']) }} · oldest {{ $r['oldest_days'] }} days</span>
                </span>
                <span class="text-end">
                  <strong style="font-variant-numeric:tabular-nums">{{ number_format($r['net'], 0) }}</strong><br>
                  @if($r['d90plus'] > 0)<span class="chip bad">■ {{ number_format($r['d90plus'], 0) }} 90+</span>@endif
                </span>
              </li></ul>
            @empty
              <div class="empty">No client owes anything.</div>
            @endforelse
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-xl-4 col-md-6">
        <div class="panel">
          <div class="panel-h"><h6>How clients paid</h6><span class="hint">{{ $inPeriod }}</span></div>
          <div id="ch-modes" class="chart"></div>
          <div class="panel-b small muted">
            Also withheld by clients: WHT {{ Fmt::money($k['wht']) }}@if($k['vat_withheld'] > 0), VAT {{ Fmt::money($k['vat_withheld']) }}@endif — check the certificates are filed.
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-md-6">
        <div class="panel">
          <div class="panel-h"><h6>Expenses by type</h6><span class="hint">Approved, {{ $inPeriod }}</span></div>
          <div class="panel-b">
            @php($maxExp = max(array_column($d['expense_types'], 'value') ?: [0]))
            @forelse($d['expense_types'] as $e)
              <ul class="bar-list"><li>
                <span>{{ $e['label'] }} <span class="muted">· {{ $e['count'] }}</span></span><span class="v">{{ Fmt::money($e['value'], 0) }}</span>
                <span class="track"><span class="fill d-block" style="width: {{ $maxExp ? round($e['value'] / $maxExp * 100, 1) : 0 }}%"></span></span>
              </li></ul>
            @empty
              <div class="empty">No approved expenses in this period.</div>
            @endforelse
          </div>
        </div>
      </div>
      <div class="col-xl-4">
        @php($p = $d['payroll'])
        <div class="panel">
          <div class="panel-h"><h6>Payroll — {{ $p['months'] }}</h6>
            @if($p['visible'])<a class="hint" href="{{ route('salaries.report', $ctx->reportQuery(['monthly', 'quarterly', 'semiannual', 'yearly'], 'month')) }}">Salaries report →</a>@endif
          </div>
          <div class="panel-b">
            @if(! $p['visible'])
              <div class="empty">Payroll figures are for finance and payroll roles.</div>
            @elseif(! $p['headcount'] && ! $p['held_count'])
              <div class="empty">No payroll uploaded for {{ $p['months'] }} yet.</div>
            @else
              <table class="dt">
                <tr><td>Net pay ({{ Fmt::int($p['headcount']) }} people)</td><td class="num">{{ Fmt::money($p['net'], 0) }}</td></tr>
                <tr><td>Cost to company</td><td class="num">{{ Fmt::money($p['ctc'], 0) }}</td></tr>
                <tr><td>Paid</td><td class="num">{{ Fmt::money($p['paid'], 0) }} <span class="muted">{{ $p['paid_pct'] !== null ? '(' . Fmt::pct($p['paid_pct'], 0) . ')' : '' }}</span></td></tr>
                <tr><td>Still to pay <span class="muted">· {{ Fmt::int($p['outstanding_count']) }}</span></td><td class="num">{{ Fmt::money($p['outstanding'], 0) }}</td></tr>
                <tr><td>On hold / rejected <span class="muted">· {{ Fmt::int($p['held_count']) }}</span></td><td class="num {{ $p['held_count'] ? 'neg' : '' }}">{{ Fmt::money($p['held'], 0) }}</td></tr>
              </table>
              <div class="progress mt-2" style="height:6px" role="progressbar" aria-label="Share of net pay paid" aria-valuenow="{{ (int) $p['paid_pct'] }}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: {{ (float) $p['paid_pct'] }}%; background: var(--s1)"></div>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    @if($d['banks']->count())
    <div class="row g-3 mb-3">
      <div class="col-12">
        <div class="panel">
          <div class="panel-h"><h6>Bank balances</h6><span class="hint">Running balance on each account in the system · total {{ Fmt::money($d['banks']->sum('total')) }}</span></div>
          <div class="panel-b table-responsive">
            <table class="dt">
              <thead><tr><th>Bank</th><th>Branch</th><th>Account</th><th class="num">Balance</th></tr></thead>
              <tbody>
              @foreach($d['banks'] as $b)
                <tr><td>{{ $b->name }}</td><td class="muted">{{ $b->branch }}</td><td class="muted">{{ $b->acc_number }}</td><td class="num">{{ Fmt::money($b->total) }}</td></tr>
              @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    @endif
  </div>
  @endsection

  @section('scripts')
    @include('dashboards._charts')
    <script>
      document.addEventListener('dash:charts', function () {
        var t = @json($d['trend']);
        var series = [];
        if (t.invoiced) series.push({ name: 'Invoiced', type: 'column', data: t.invoiced });
        series.push({ name: 'Collected', type: 'column', data: t.collected });
        series.push({ name: 'Expenses', type: 'line', data: t.expenses });
        Dash.lines('#ch-trend', t.labels, series, { money: true, height: 290,
          colors: t.invoiced ? [Dash.colors.s1, Dash.colors.s2, Dash.colors.s3] : [Dash.colors.s2, Dash.colors.s3] });

        var a = @json($d['aging']['buckets']);
        Dash.bars('#ch-aging', ['Not due', '1–30 d', '31–60 d', '61–90 d', '90+ d'], [{ name: 'Owed', data: a.map(function (b) { return b.value; }) }],
          { money: true, distributed: true, height: 290, width: '62%',
            // One hue, light to dark: the older the debt, the darker the bar.
            colors: ['#c9daf4', '#93b7ea', '#5b93df', '#2a78d6', '#174e94'] });

        var m = @json($d['modes']).filter(function (x) { return x.value > 0; });
        if (m.length) {
          Dash.donut('#ch-modes', m.map(function (x) { return x.label; }), m.map(function (x) { return x.value; }), { money: true, height: 270, totalLabel: 'Collected' });
        } else {
          document.querySelector('#ch-modes').innerHTML = '<div class="empty">No approved receipts in this period.</div>';
        }
      });
    </script>
  @endsection
</x-sales-dashboard>

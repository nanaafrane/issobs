<x-hr-dashboard>

    @section('css')
    <style>
        .rep-kpi { border-radius: 10px; padding: 1rem 1.1rem; background: #fff; border: 1px solid #e5e7eb; height: 100%; }
        .rep-kpi .label { font-size: .8rem; color: #6b7280; }
        .rep-kpi .value { font-size: 1.35rem; font-weight: 700; color: #111827; line-height: 1.3; }
        .rep-kpi .sub { font-size: .8rem; color: #6b7280; }
        .rep-card { border-radius: 12px; border: 1px solid #e5e7eb; }
        .rep-card .card-header { background: #f9fafb; border-bottom: 1px solid #e5e7eb; font-weight: 600; }
        .delta-up { color: #b91c1c; }      /* payroll cost going up */
        .delta-down { color: #047857; }
        .delta-flat { color: #6b7280; }
        .rep-table td, .rep-table th { white-space: nowrap; }
        .rep-table td.num, .rep-table th.num { text-align: right; font-variant-numeric: tabular-nums; }
        .margin-neg { color: #b91c1c; font-weight: 600; }
    </style>
    @endsection

    @section('side_nav')
        @include('partials.payroll_side_nav')
    @endsection

    @section('content')
    @php
        $ghs = fn ($v) => 'GH₵ ' . number_format((float) $v, 2);
        $int = fn ($v) => number_format((float) $v, 0);
        $pct = fn ($v) => $v === null ? '–' : number_format((float) $v, 1) . '%';
        // Change vs the previous comparable period. $costLike: an increase is shown in red.
        $delta = function ($cur, $prev, $costLike = true) {
            $cur = (float) $cur; $prev = (float) $prev;
            if ($prev == 0.0) {
                return '<span class="delta-flat">no data for previous period</span>';
            }
            $change = ($cur - $prev) / $prev * 100;
            $cls = abs($change) < 0.05 ? 'delta-flat' : (($change > 0) === $costLike ? 'delta-up' : 'delta-down');
            return '<span class="' . $cls . '">' . ($change > 0 ? '+' : '') . number_format($change, 1) . '% vs previous period</span>';
        };
        $periodLabel = $from->eq($to) ? $from->format('F Y') : $from->format('M Y') . ' – ' . $to->format('M Y');
        $prevLabel = $prevFrom->eq($prevTo) ? $prevFrom->format('F Y') : $prevFrom->format('M Y') . ' – ' . $prevTo->format('M Y');
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div>
                <h3 class="mb-0"><i class="bx bx-line-chart"></i> Salaries report</h3>
                <div class="text-muted small">{{ $periodLabel }}, compared with {{ $prevLabel }}. Payroll means pending + approved salaries.</div>
            </div>
            <form method="GET" action="{{ route('salaries.report') }}" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label small mb-0" for="period">Period</label>
                    <select name="period" id="period" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach(['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'semiannual' => 'Half-year', 'yearly' => 'Yearly'] as $val => $label)
                            <option value="{{ $val }}" @selected($period === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label small mb-0" for="month">Month</label>
                    <input type="month" name="month" id="month" value="{{ $anchor->format('Y-m') }}" class="form-control form-control-sm" onchange="this.form.submit()">
                </div>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('salaries.salariesMonth', ['month' => $to->format('Y-m')]) }}">Open {{ $to->format('F Y') }} salaries</a>
            </form>
        </div>

        @if($kpi->payslips == 0 && $kpi->held_count == 0)
            <div class="alert alert-secondary">No salaries were recorded for {{ $periodLabel }}. Pick another month or period above.</div>
        @endif

        {{-- Key figures --}}
        <div class="row g-3 mb-4">
            <div class="col-xl-2 col-md-4 col-6"><div class="rep-kpi">
                <div class="label">Net payroll</div>
                <div class="value">{{ $ghs($kpi->net) }}</div>
                <div class="sub">{!! $delta($kpi->net, $prev->net) !!}</div>
            </div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="rep-kpi">
                <div class="label">Cost to company</div>
                <div class="value">{{ $ghs($kpi->ctc) }}</div>
                <div class="sub">{!! $delta($kpi->ctc, $prev->ctc) !!}</div>
            </div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="rep-kpi">
                <div class="label">Employees paid{{ $kpi->months > 1 ? ' (avg per month)' : '' }}</div>
                <div class="value">{{ $int($kpi->avg_monthly_headcount) }}</div>
                <div class="sub">{!! $delta($kpi->avg_monthly_headcount, $prev->avg_monthly_headcount) !!}</div>
            </div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="rep-kpi">
                <div class="label">Average net pay</div>
                <div class="value">{{ $ghs($kpi->avg_net) }}</div>
                <div class="sub">{!! $delta($kpi->avg_net, $prev->avg_net) !!}</div>
            </div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="rep-kpi">
                <div class="label">Paid so far</div>
                <div class="value">{{ $pct($kpi->paid_pct) }}</div>
                <div class="sub">{{ $ghs($kpi->outstanding) }} pending ({{ $int($kpi->outstanding_count) }})</div>
            </div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="rep-kpi">
                <div class="label">Held or rejected</div>
                <div class="value">{{ $ghs($kpi->held) }}</div>
                <div class="sub">{{ $int($kpi->held_count) }} salaries, not in payroll totals</div>
            </div></div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6"><div class="rep-kpi"><div class="label">Gross pay</div><div class="value">{{ $ghs($kpi->gross) }}</div></div></div>
            <div class="col-md-3 col-6"><div class="rep-kpi"><div class="label">Total deductions</div><div class="value">{{ $ghs($kpi->deductions) }}</div></div></div>
            <div class="col-md-3 col-6"><div class="rep-kpi"><div class="label">PAYE tax to remit</div><div class="value">{{ $ghs($kpi->tax) }}</div></div></div>
            <div class="col-md-3 col-6"><div class="rep-kpi"><div class="label">SSNIT to remit (13.5%)</div><div class="value">{{ $ghs($kpi->ssnit_remit) }}</div></div></div>
        </div>

        {{-- Trend + movement --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card rep-card h-100">
                    <div class="card-header">Payroll trend, last {{ count($trend['labels']) }} {{ ['monthly' => 'months', 'quarterly' => 'quarters', 'semiannual' => 'half-years', 'yearly' => 'years'][$period] }}</div>
                    <div class="card-body"><div id="chart-trend" style="min-height:320px"></div></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card rep-card h-100">
                    <div class="card-header">Joiners and leavers on payroll</div>
                    <div class="card-body">
                        <div class="small text-muted mb-2">
                            {{ $to->format('F Y') }}: {{ $movement['last_joiners'] }} joined, {{ $movement['last_leavers'] }} left, compared with the month before.
                        </div>
                        <div id="chart-movement" style="min-height:260px"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Field offices + deductions --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card rep-card h-100">
                    <div class="card-header">Field offices</div>
                    <div class="table-responsive">
                        <table class="table table-sm rep-table mb-0">
                            <thead><tr>
                                <th>Field</th><th class="num">Employees</th><th class="num">Net payroll</th><th class="num">Cost to company</th>
                                <th class="num">Bank</th><th class="num">MoMo</th><th class="num">Paid</th><th class="num">Pending</th><th class="num">Held</th>
                            </tr></thead>
                            <tbody>
                            @forelse($byField as $f)
                                <tr>
                                    <td>{{ $f->name }}</td>
                                    <td class="num">{{ $int($f->headcount) }}</td>
                                    <td class="num">{{ number_format($f->net, 2) }}</td>
                                    <td class="num">{{ number_format($f->ctc, 2) }}</td>
                                    <td class="num">{{ number_format($f->bank, 2) }}</td>
                                    <td class="num">{{ number_format($f->momo, 2) }}</td>
                                    <td class="num">{{ number_format($f->paid, 2) }}</td>
                                    <td class="num">{{ number_format($f->outstanding, 2) }}</td>
                                    <td class="num">{{ number_format($f->held, 2) }} @if($f->held_count)<span class="text-muted">({{ $f->held_count }})</span>@endif</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-muted text-center py-3">No salaries in this period.</td></tr>
                            @endforelse
                            </tbody>
                            @if($byField->isNotEmpty())
                            <tfoot><tr class="fw-semibold">
                                <td>Total</td>
                                <td class="num">{{ $int($byField->sum('headcount')) }}</td>
                                <td class="num">{{ number_format($byField->sum('net'), 2) }}</td>
                                <td class="num">{{ number_format($byField->sum('ctc'), 2) }}</td>
                                <td class="num">{{ number_format($byField->sum('bank'), 2) }}</td>
                                <td class="num">{{ number_format($byField->sum('momo'), 2) }}</td>
                                <td class="num">{{ number_format($byField->sum('paid'), 2) }}</td>
                                <td class="num">{{ number_format($byField->sum('outstanding'), 2) }}</td>
                                <td class="num">{{ number_format($byField->sum('held'), 2) }}</td>
                            </tr></tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card rep-card h-100">
                    <div class="card-header">Where deductions go</div>
                    <div class="card-body"><div id="chart-deductions" style="min-height:300px"></div></div>
                </div>
            </div>
        </div>

        {{-- Category economics --}}
        <div class="card rep-card mb-4">
            <div class="card-header">Client categories: payroll cost against billing</div>
            <div class="table-responsive">
                <table class="table table-sm rep-table mb-0">
                    <thead><tr>
                        <th>Category</th><th class="num">Clients</th><th class="num">Guards paid</th><th class="num">Guards invoiced</th>
                        <th class="num">Net Salary</th><th class="num">Invoiced</th><th class="num">Received</th>
                        <th class="num">Margin</th><th class="num">Margin %</th><th class="num">Collected %</th>
                    </tr></thead>
                    <tbody>
                    @foreach($categories as $c)
                        <tr>
                            <td>{{ $c->name }}</td>
                            <td class="num">{{ $int($c->clients) }}</td>
                            <td class="num @if($c->guards_invoiced > 0 && $c->guard_payslips > $c->guards_invoiced) margin-neg @endif">{{ $int($c->guard_payslips) }}</td>
                            <td class="num">{{ $int($c->guards_invoiced) }}</td>
                            <td class="num">{{ number_format($c->ctc, 2) }}</td>
                            <td class="num">{{ number_format($c->invoiced, 2) }}</td>
                            <td class="num">{{ number_format($c->received, 2) }}</td>
                            <td class="num @if($c->margin < 0) margin-neg @endif">{{ number_format($c->margin, 2) }}</td>
                            <td class="num @if($c->margin < 0) margin-neg @endif">{{ $pct($c->margin_pct) }}</td>
                            <td class="num">{{ $pct($c->collection_pct) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body py-2 small text-muted">
                Margin is invoiced amount minus payroll cost to company. Guards paid in red means more guard salaries than guards billed, which is worth checking.
            </div>
        </div>

        {{-- Top clients + top-ups + data quality --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-7">
                <div class="card rep-card h-100">
                    <div class="card-header">Top 10 clients by payroll cost</div>
                    <div class="table-responsive">
                        <table class="table table-sm rep-table mb-0">
                            <thead><tr><th>Client</th><th class="num">Employees</th><th class="num">Net Salary</th><th class="num">Invoiced</th><th class="num">Margin %</th></tr></thead>
                            <tbody>
                            @forelse($topClients as $c)
                                <tr>
                                    <td>{{ trim($c->name . ' ' . $c->business_name) }}</td>
                                    <td class="num">{{ $int($c->headcount) }}</td>
                                    <td class="num">{{ number_format($c->net, 2) }}</td>
                                    <td class="num">{{ $c->invoiced > 0 ? number_format($c->invoiced, 2) : 'not invoiced' }}</td>
                                    <td class="num @if($c->margin < 0) margin-neg @endif">{{ $pct($c->margin_pct) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center py-3">No client payroll in this period.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card rep-card mb-3">
                    <div class="card-header">MoMo top ups</div>
                    <div class="card-body py-2">
                        @forelse($topUps as $t)
                            <div class="d-flex justify-content-between small py-1">
                                <span class="text-capitalize">{{ $t->status }} ({{ $t->cnt }})</span><span>{{ $ghs($t->amount) }}</span>
                            </div>
                        @empty
                            <div class="small text-muted">No top ups in this period.</div>
                        @endforelse
                    </div>
                </div>
                <div class="card rep-card">
                    <div class="card-header">Data checks</div>
                    <ul class="list-group list-group-flush small">
                        @foreach($quality as $q)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $q['label'] }}</span>
                                <span class="{{ $q['count'] > 0 ? 'text-danger fw-semibold' : 'text-success' }}">{{ $q['count'] > 0 ? number_format($q['count']) : 'none' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endsection

    @section('scripts')
    <script>
    (function () {
        const trend = @json($trend);
        const movement = @json($movement);
        const deductions = @json($deductions);
        const money = v => 'GH₵ ' + Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const compact = v => 'GH₵ ' + Intl.NumberFormat(undefined, { notation: 'compact', maximumFractionDigits: 1 }).format(v || 0);

        function render() {
            new ApexCharts(document.querySelector('#chart-trend'), {
                chart: { type: 'line', height: 320, toolbar: { show: false } },
                series: [
                    { name: 'Cost to company', type: 'area', data: trend.ctc },
                    { name: 'Net payroll', type: 'line', data: trend.net },
                    { name: 'Employees paid', type: 'column', data: trend.headcount },
                ],
                colors: ['#94a3b8', '#1d4ed8', '#f59e0b'],
                stroke: { curve: 'smooth', width: [1, 3, 0] },
                fill: { opacity: [0.25, 1, 0.35] },
                xaxis: { categories: trend.labels },
                yaxis: [
                    { seriesName: 'Cost to company', labels: { formatter: compact } },
                    { seriesName: 'Cost to company', show: false },
                    { seriesName: 'Employees paid', opposite: true, labels: { formatter: v => Math.round(v) } },
                ],
                tooltip: { shared: true, y: { formatter: (v, o) => o.seriesIndex === 2 ? Math.round(v) + ' employees' : money(v) } },
                dataLabels: { enabled: false },
                legend: { position: 'top' },
            }).render();

            new ApexCharts(document.querySelector('#chart-movement'), {
                chart: { type: 'bar', height: 260, toolbar: { show: false } },
                series: [{ name: 'Joined', data: movement.joiners }, { name: 'Left', data: movement.leavers.map(v => -v) }],
                colors: ['#047857', '#b91c1c'],
                plotOptions: { bar: { columnWidth: '70%' } },
                xaxis: { categories: movement.labels, labels: { rotate: -45 } },
                yaxis: { labels: { formatter: v => Math.abs(Math.round(v)) } },
                tooltip: { y: { formatter: v => Math.abs(v) + ' employees' } },
                dataLabels: { enabled: false },
                legend: { position: 'top' },
            }).render();

            const el = document.querySelector('#chart-deductions');
            if (!deductions.length) {
                el.innerHTML = '<div class="text-muted small">No deductions in this period.</div>';
                return;
            }
            new ApexCharts(el, {
                chart: { type: 'donut', height: 300 },
                series: deductions.map(d => d.amount),
                labels: deductions.map(d => d.label),
                legend: { position: 'bottom' },
                tooltip: { y: { formatter: money } },
                dataLabels: { enabled: false },
            }).render();
        }

        // The layout loads ApexCharts from /vendor; fall back to the CDN if that asset is missing.
        if (window.ApexCharts) {
            render();
        } else {
            const s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.54.1/apexcharts.min.js';
            s.onload = render;
            document.head.appendChild(s);
        }
    })();
    </script>
    @endsection
</x-hr-dashboard>

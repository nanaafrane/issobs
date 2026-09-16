<x-sales-dashboard>

    @php
      $user = Auth::user();
      $canViewInvoices = $user?->hasRole(['Invoice','Finance Manager', 'Director']) ?? false;
      $canViewReceipts = $user?->hasRole(['Finance Manager', 'Manager','Admin Assistant']) ?? false;
      $canManageFinance = $user?->hasRole(['Finance Manager']) ?? false;
    @endphp

    @section('css')
    <style>
        .rep-kpi { border-radius: 10px; padding: 1rem 1.2rem; background:#fff; border:1px solid #e5e7eb; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; gap:.25rem; }
        .rep-kpi .value { font-size: 1.5rem; font-weight: 700; }
        .rep-kpi .pct { font-size: 1.25rem; font-weight: 800; color: #111827; }
        .rep-kpi .sub { font-size: .85rem; color: #6b7280; }
        .rep-card { border-radius: 12px; border: 1px solid #e5e7eb; }
        .rep-card .card-header { background: #f9fafb; border-bottom: 1px solid #e5e7eb; font-weight: 600; }
    </style>
    @endsection

    @section('side_nav')
    @endsection

    @section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row mb-4">
            <div class="col-12"><h3 class="mb-0"><i class="bx bx-bar-chart-alt-2"></i> Receipts Reports</h3></div>
        </div>

        <form method="GET" action="{{ url('receipt-report') }}" class="row g-2 align-items-end mb-4">
            <div class="col-auto">
                <label class="form-label small mb-0">Period</label>
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach(['daily'=>'Daily','weekly'=>'Weekly','monthly'=>'Monthly','yearly'=>'Yearly'] as $val=>$label)
                        <option value="{{ $val }}" @selected($period == $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Anchor Date</label>
                <input type="date" name="date" value="{{ $anchor->format('Y-m-d') }}" class="form-control form-control-sm" onchange="this.form.submit()">
            </div>
            <div class="col-auto text-muted small pb-1">Showing: {{ $from->format('d M Y') }} &ndash; {{ $to->format('d M Y') }}</div>
        </form>

        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-6"><div class="rep-kpi"><small class="text-muted">TOTAL RECEIPTS (NET)</small><div class="value">GH&#x20B5; {{ number_format($total,2) }}</div></div></div>
            <div class="col-lg-3 col-6"><div class="rep-kpi"><small class="text-muted">RECEIPTS</small><div class="value">{{ $count }}</div></div></div>
            <div class="col-lg-3 col-6"><div class="rep-kpi"><small class="text-muted">AVG PER RECEIPT</small><div class="value">GH&#x20B5; {{ $count ? number_format($total / $count, 2) : '0.00' }}</div></div></div>
            <div class="col-lg-3 col-6"><div class="rep-kpi"><small class="text-muted">PROJECTED NEXT {{ strtoupper($period) }}</small><div class="value text-primary">GH&#x20B5; {{ number_format($projection,2) }}</div>
                <small class="text-muted">avg of last 4 {{ $period }} periods</small>
            </div></div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card rep-card h-100">
                    <div class="card-header">Receipts Trend</div>
                    <div class="card-body"><div id="rcpt-trend-chart"></div></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card rep-card h-100">
                    <div class="card-header">By Payment Method</div>
                    <div class="card-body">
                        @php
                            $statusTotals = ($byStatus->sum('total') ?? 0) ?: 0;
                            $statusMap = ($byStatus->keyBy('status') ?? collect());
                            $ordered = ['completed','uncompleted','unpaid'];
                        @endphp

                        <div class="row text-center mb-3">
                            @foreach($ordered as $st)
                                @php
                                    $r = $statusMap[$st] ?? (object)['cnt'=>0,'total'=>0];
                                    $pct = $statusTotals ? round(($r->total / $statusTotals) * 100, 1) : 0;
                                @endphp
                                <div class="col-12 col-md-4 mb-2">
                                    <div class="rep-kpi">
                                        <div class="small text-muted">{{ ucfirst($st) }}</div>
                                        <div class="pct">{{ $pct }}%</div>
                                        <div class="sub">{{ $r->cnt }} · GH&#x20B5; {{ number_format($r->total,2) }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div id="rcpt-method-chart" style="height:300px"></div>
                        <hr />
                        <ul class="list-unstyled mb-0">
                            <li>Cash: GH&#x20B5; {{ number_format($modes->cash ?? 0, 2) }}</li>
                            <li>MoMo: GH&#x20B5; {{ number_format($modes->momo ?? 0, 2) }}</li>
                            <li>Cheque: GH&#x20B5; {{ number_format($modes->cheque ?? 0, 2) }}</li>
                            <li>Transfer: GH&#x20B5; {{ number_format($modes->transfer ?? 0, 2) }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card rep-card h-100">
                    <div class="card-header">By Field Office</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Office</th><th>Receipts</th><th>Total</th></tr></thead>
                            <tbody>
                                @forelse($byField as $row)
                                <tr><td>{{ $row->field_name }}</td><td>{{ $row->cnt }}</td><td>GH&#x20B5; {{ number_format($row->total,2) }}</td></tr>
                                @empty
                                <tr><td colspan="3" class="text-muted text-center py-3">No data for this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card rep-card h-100">
                    <div class="card-header">Top 10 Clients (by receipts)</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Client</th><th>Receipts</th><th>Total</th></tr></thead>
                            <tbody>
                                @forelse($topClients as $row)
                                <tr><td>{{ $row->business_name ?: $row->name }}</td><td>{{ $row->cnt }}</td><td>GH&#x20B5; {{ number_format($row->total,2) }}</td></tr>
                                @empty
                                <tr><td colspan="3" class="text-muted text-center py-3">No data for this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card rep-card h-100">
                    <div class="card-header">Top 10 Collectors (by amount)</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Collector</th><th>Receipts</th><th>Total</th></tr></thead>
                            <tbody>
                                @forelse($topCollectors as $row)
                                <tr><td>{{ $row->name }}</td><td>{{ $row->cnt }}</td><td>GH&#x20B5; {{ number_format($row->total,2) }}</td></tr>
                                @empty
                                <tr><td colspan="3" class="text-muted text-center py-3">No data for this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="alert alert-info">
                    <i class="bx bx-info-circle"></i> This report uses `receipt_month` and only includes receipts with <strong>ho_status = approved</strong>.
                </div>
            </div>
        </div>

    </div>
    @endsection

    @section('scripts')
    <script src="{{asset('vendor/libs/apex-charts/apexcharts.js')}}"></script>
    <script>
        const rawTrendLabels = @json($trend->pluck('receipt_month')) || [];
        const rawTrendValues = @json($trend->pluck('total')) || [];
        const reportPeriod = @json($period);

        const trendLabels = rawTrendLabels.map(l => {
            if (!l) return '-';
            const d = new Date(l);
            // choose friendly format based on period
            if (reportPeriod === 'daily') return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
            if (reportPeriod === 'weekly') return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
            if (reportPeriod === 'yearly') return d.getFullYear().toString();
            // monthly
            return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short' });
        });

        const trendValues = rawTrendValues.map(v => (typeof v === 'number' ? v : Number(v) || 0));

        new ApexCharts(document.querySelector('#rcpt-trend-chart'), {
            chart: { type: 'area', height: 300, toolbar: { show: false } },
            series: [{ name: 'Receipts (GHS)', data: trendValues }],
            xaxis: { categories: trendLabels, labels: { rotate: -45 } },
            dataLabels: { enabled: false },
            colors: ['#0ea5a4'],
            stroke: { curve: 'smooth', width: 2 },
            tooltip: {
                y: { formatter: val => val ? Number(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00' }
            }
        }).render();

        // Payment method donut
        const methodLabels = ['Cash','MoMo','Cheque','Transfer'];
        const methodValues = [@json((float)($modes->cash ?? 0)), @json((float)($modes->momo ?? 0)), @json((float)($modes->cheque ?? 0)), @json((float)($modes->transfer ?? 0))];

        if (methodValues.some(v => v > 0)) {
            new ApexCharts(document.querySelector('#rcpt-method-chart'), {
                chart: { type: 'donut', height: 300 },
                series: methodValues,
                labels: methodLabels,
                colors: ['#10b981', '#06b6d4', '#f59e0b', '#6366f1'],
            }).render();
        } else {
            document.querySelector('#rcpt-method-chart').innerHTML = '<div class="text-muted p-4">No payment method data for this period.</div>';
        }

        // (status donut removed) status summary is shown as KPI cards above
    </script>
    @endsection

</x-sales-dashboard>

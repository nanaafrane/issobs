<x-sales-dashboard>

    @section('css')
    <style>
        .rep-kpi { border-radius: 10px; padding: 1rem 1.2rem; background:#fff; border:1px solid #e5e7eb; }
        .rep-kpi .value { font-size: 1.5rem; font-weight: 700; }
        .rep-card { border-radius: 12px; border: 1px solid #e5e7eb; }
        .rep-card .card-header { background: #f9fafb; border-bottom: 1px solid #e5e7eb; font-weight: 600; }
    </style>
    @endsection

    @section('side_nav')
    <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
        <div class="app-brand demo">
            <a href="#" class="app-brand-link">
                <span class="app-brand-logo demo"><img width="70px" src="{{asset('img/icons/brands/issobs.png')}}" alt=""></span>
                <span class="app-brand-text demo menu-text fw-bold ms-2">ISSOBS</span>
            </a>
        </div>
        <div class="menu-divider mt-0"></div>
        <ul class="menu-inner py-1">
            <li class="menu-item"><a href="{{url('home')}}" class="menu-link"><i class="menu-icon tf-icons bx bx-home-smile"></i><div>Dashboard</div></a></li>
            <li class="menu-item active open">
                <a href="javascript:void(0);" class="menu-link menu-toggle"><i class="menu-icon tf-icons bx bx-time-five bg-danger"></i><div>Overtime</div></a>
                <ul class="menu-sub">
                    <li class="menu-item"><a href="{{url('overtime')}}" class="menu-link"><div>Daily Entry</div></a></li>
                    <li class="menu-item active"><a href="{{url('overtime-report')}}" class="menu-link"><div>Reports</div></a></li>
                </ul>
            </li>
            @if(Auth::user()->hasRole(['Manager','Officer','Finance Manager','Director']))
            <li class="menu-item"><a href="{{url('expense')}}" class="menu-link"><i class="menu-icon tf-icons bx bx-bxs-credit-card bg-secondary"></i><div>Expenses</div></a></li>
            @endif
        </ul>
    </aside>
    @endsection

    @section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row mb-4">
            <div class="col-12"><h3 class="mb-0"><i class="bx bx-bar-chart-alt-2"></i> Overtime Reports</h3></div>
        </div>

        <form method="GET" action="{{ url('overtime-report') }}" class="row g-2 align-items-end mb-4">
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
            <div class="col-lg-3 col-6"><div class="rep-kpi"><small class="text-muted">TOTAL OVERTIME</small><div class="value">GH&#x20B5; {{ number_format($total,2) }}</div></div></div>
            <div class="col-lg-3 col-6"><div class="rep-kpi"><small class="text-muted">ENTRIES</small><div class="value">{{ $count }}</div></div></div>
            <div class="col-lg-3 col-6"><div class="rep-kpi"><small class="text-muted">AVG PER ENTRY</small><div class="value">GH&#x20B5; {{ $count ? number_format($total / $count, 2) : '0.00' }}</div></div></div>
            <div class="col-lg-3 col-6"><div class="rep-kpi"><small class="text-muted">PROJECTED NEXT {{ strtoupper($period) }}</small><div class="value text-primary">GH&#x20B5; {{ number_format($projection,2) }}</div>
                <small class="text-muted">avg of last 4 {{ $period }} periods</small>
            </div></div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card rep-card h-100">
                    <div class="card-header">Overtime Trend</div>
                    <div class="card-body"><div id="ot-trend-chart"></div></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card rep-card h-100">
                    <div class="card-header">By Shift</div>
                    <div class="card-body"><div id="ot-shift-chart"></div></div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card rep-card h-100">
                    <div class="card-header">By Field Office</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Office</th><th>Entries</th><th>Total</th></tr></thead>
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
                    <div class="card-header">By Reason</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Reason</th><th>Entries</th><th>Total</th></tr></thead>
                            <tbody>
                                @forelse($byReason as $row)
                                <tr><td>{{ $row->reason ?: '-' }}</td><td>{{ $row->cnt }}</td><td>GH&#x20B5; {{ number_format($row->total,2) }}</td></tr>
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
                    <div class="card-header">Top 10 Clients / Sites (by overtime cost)</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Client</th><th>Entries</th><th>Total</th></tr></thead>
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
            <div class="col-lg-6">
                <div class="card rep-card h-100">
                    <div class="card-header">Top 10 Guards (by overtime earned)</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Employee</th><th>Entries</th><th>Total</th></tr></thead>
                            <tbody>
                                @forelse($topEmployees as $row)
                                <tr><td>{{ $row->name }}</td><td>{{ $row->cnt }}</td><td>GH&#x20B5; {{ number_format($row->total,2) }}</td></tr>
                                @empty
                                <tr><td colspan="3" class="text-muted text-center py-3">No data for this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-info">
            <i class="bx bx-info-circle"></i> Fully-approved overtime entries in this window are already reflected under
            <strong>Expenses &rarr; Overtime</strong> for both field offices and Head Office totals — no separate posting is needed.
        </div>

    </div>
    @endsection

    @section('scripts')
    <script src="{{asset('vendor/libs/apex-charts/apexcharts.js')}}"></script>
    <script>
        const trendLabels = @json($trend->pluck('entry_date'));
        const trendValues = @json($trend->pluck('total'));
        new ApexCharts(document.querySelector('#ot-trend-chart'), {
            chart: { type: 'area', height: 300, toolbar: { show: false } },
            series: [{ name: 'Overtime (GHS)', data: trendValues }],
            xaxis: { categories: trendLabels },
            dataLabels: { enabled: false },
            colors: ['#7c3aed'],
            stroke: { curve: 'smooth', width: 2 },
        }).render();

        const shiftLabels = @json($byShift->pluck('shift'));
        const shiftValues = @json($byShift->pluck('total'));
        new ApexCharts(document.querySelector('#ot-shift-chart'), {
            chart: { type: 'donut', height: 300 },
            series: shiftValues,
            labels: shiftLabels.map(s => s ? s.charAt(0).toUpperCase() + s.slice(1) : s),
            colors: ['#f59e0b', '#1f2937'],
        }).render();
    </script>
    @endsection

</x-sales-dashboard>

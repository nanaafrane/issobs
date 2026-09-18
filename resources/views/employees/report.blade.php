<x-sales-dashboard>

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
      <!-- Menu -->
    <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
        <div class="app-brand demo">
            <a href="#" class="app-brand-link">
                <span class="app-brand-logo demo">
                    <img width="70px" src="{{asset('img/icons/brands/issobs.png')}}" alt="">
                    <!-- Logo -->
                </span>
                <span class="app-brand-text demo menu-text fw-bold ms-2">ISSOBS</span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                <i class="bx bx-chevron-left d-block d-xl-none align-middle"></i>
            </a>
        </div>

        <div class="menu-divider mt-0"></div>

        <div class="menu-inner-shadow"></div>

        <ul class="menu-inner py-1">
            <!-- Dashboards -->
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-home-smile"></i>
                    <div class="text-truncate" data-i18n="Dashboards"><strong>Dashboard</strong></div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item ">
                        <a href="{{url('home')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="Dashboard">Dashboard</div>
                        </a>
                    </li>
                </ul>
            </li>

        @if(Auth::user()->hasPermission('Accounts') || Auth::user()->hasPermission('Administration'))
            <!-- Apps & Pages -->
            <li class="menu-header small text-uppercase ">
                <span class="menu-header-text text-primary">Transactions</span>
            </li>
            <!-- Pages -->
            <li class="menu-item">
                <a href="{{url('transaction')}}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-transfer-alt bg-primary"></i>
                    <div class="text-truncate" data-i18n="Transaction">Transactions</div>
                </a>
            </li>

                @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))
                <li class="menu-item">
                    <a href="{{ url('invoice') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-bxs-receipt bg-primary"></i>
                        <div class="text-truncate" data-i18n="Invoices">Invoices</div>
                    </a>
                </li>
                @endif

                @if(Auth::user()->hasRole(['Finance Manager' ,'Manager', 'Admin Assistant']))
                <li class="menu-item ">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-money-withdraw bg-primary"></i>
                    <div class="text-truncate" data-i18n="Receipts">Receipts</div>
                    </a>
                    <ul class="menu-sub">

                        <li class="menu-item ">
                            <a href="{{url('receipt')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="RList">List</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="{{url('receiptPending')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="RPending">Pending </div>
                            </a>
                        </li>

                    </ul>
                </li>

                    <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
            <li class="menu-item ">
                <a class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-bxs-user-detail"></i>
                    <div class="text-truncate" data-i18n="Clients"><strong>Clients</strong></div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item ">
                        <a href="{{url('client/create')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="CRegister">Register</div>
                        </a>
                    </li>
                    <li class="menu-item ">
                        <a href="{{url('client')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="CList">List</div>
                        </a>
                    </li>
                    <li class="menu-item ">
                        <a href="{{url('clientTerminated')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="CList">Terminated</div>
                        </a>
                    </li>
                    <li class="menu-item ">
                        <a href="{{url('clientPending')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="CList">Pending</div>
                        </a>
                    </li>
                </ul>
            </li>
                    <li class="menu-item active open">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons bx bxs-user-account"></i>
                        <div class="text-truncate" data-i18n="Staffs">Employees</div>
                        </a>
                        <ul class="menu-sub">
                        <li class="menu-item ">
                            <a href="{{url('employees/create')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="SRegister">Register</div>
                            </a>
                        </li>
                        <li class="menu-item ">
                            <a href="{{url('employees')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="SList">List</div>
                            </a>
                        </li>
                        <li class="menu-item ">
                            <a href="{{url('employeesPending')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="SList">Pending</div>
                            </a>
                        </li>
                        <li class="menu-item active">
                            <a href="{{url('employee-report')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="SList"> Report </div>
                            </a>
                        </li>
                        <li class="menu-item ">
                            <a href="{{url('employeesnrrit')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="SList">Terminate / Recruit</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="{{url('employeesBank')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="SList">Employee Banks</div>
                            </a>
                        </li>
                            <li class="menu-item">
                            <a href="{{url('employeesCash')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="SList">Employee Cash</div>
                            </a>
                        </li>
                        </ul>
                    </li>
                @endif
        @endif

        @if(Auth::user()->hasPermission('HR') || Auth::user()->hasRole(['Invoice']))
            <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
            @if(Auth::user()->hasRole(['Manager']))
            <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons bx bx-bxs-group"></i>
                        <div class="text-truncate" data-i18n="Staffs">System Users</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="{{url('staffAdd')}}" class="menu-link">
                                <div class="text-truncate" data-i18n="SRegister">Register</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="{{url('staff')}}" class="menu-link">
                                <div class="text-truncate" data-i18n="SList">List</div>
                            </a>
                        </li>
                    </ul>
            </li>
            @endif
            <li class="menu-item active open ">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bxs-user-account"></i>
                <div class="text-truncate" data-i18n="Staffs">Employees</div>
                </a>
                <ul class="menu-sub">
                <li class="menu-item ">
                    <a href="{{url('employees/create')}}" class="menu-link">
                    <div class="text-truncate" data-i18n="SRegister">Register</div>
                    </a>
                </li>
                <li class="menu-item ">
                    <a href="{{url('employees')}}" class="menu-link">
                    <div class="text-truncate" data-i18n="SList">List</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{url('employeesPending')}}" class="menu-link">
                    <div class="text-truncate" data-i18n="SList">Pending</div>
                    </a>
                </li>
                @if(Auth::user()->hasRole(['Manager', 'Invoice']))
                <li class="menu-item active">
                    <a href="{{url('employee-report')}}" class="menu-link">
                    <div class="text-truncate" data-i18n="SList"> Report </div>
                    </a>
                </li>

                <li class="menu-item ">
                    <a href="{{url('employeesnrrit')}}" class="menu-link">
                    <div class="text-truncate" data-i18n="SList">Terminate / Recruit</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{url('employeesBank')}}" class="menu-link">
                    <div class="text-truncate" data-i18n="SList">Employee Banks</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{url('employeesCash')}}" class="menu-link">
                    <div class="text-truncate" data-i18n="SList">Employee Cash</div>
                    </a>
                </li>
                @endif
                </ul>
            </li>
        
        @if(Auth::user()->hasRole(['Manager', 'Invoice']))
            <li class="menu-item ">
                <a class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-bxs-user-detail"></i>
                    <div class="text-truncate" data-i18n="Clients"><strong>Clients</strong></div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item ">
                        <a href="{{url('client/create')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="CRegister">Register</div>
                        </a>
                    </li>
                    <li class="menu-item ">
                        <a href="{{url('client')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="CList">List</div>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="menu-item">
                <a href="{{url('departments')}}" class="menu-link">
                <i class="menu-icon tf-icons bx bxs-buildings"></i>
                <div class="text-truncate" data-i18n="depnroles">Department & Roles </div>
                </a>
            </li>

            <li class="menu-item">
                <a href="{{url('field')}}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-bxs-location-plus"></i>
                <div class="text-truncate" data-i18n="fOffices">Field Offices</div>
                </a>
            </li>
            @endif

            @endif

            @if((Auth::user()->hasRole(['Finance Manager']) && Auth::user()->hasPermission('Accounts')) )

            <li class="menu-header small text-uppercase"> <span class="menu-header-text text-danger">Accounts</span></li>

            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bxs-analyse bg-danger"></i>
                    <div class="text-truncate" data-i18n="Accounts"> Accounts</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{url('collections')}}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-add-to-queue bg-danger"></i>
                            <div class="text-truncate" data-i18n="ARegister">Collections</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-arrow-from-left bg-danger"></i>
                            <div class="text-truncate" data-i18n="AList">Bank Deposit</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="" class="menu-link">
                            <i class="menu-icon tf-icons bx bxs-bank bg-danger"></i>
                            <div class="text-truncate" data-i18n="AList">Banks</div>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="menu-item">
                <a href="{{url('expense')}}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-bxs-credit-card bg-secondary"></i>
                <div class="text-truncate" data-i18n="Expense"> Expense </div>
                </a>
            </li>

            @endif

        @if(Auth::user()->hasPermission('Accounts') && Auth::user()->hasRole(['Invoice', 'Officer', 'Director', 'Finance Manager']) )

            <li class="menu-header small text-uppercase"><span class="menu-header-text">PAYROLL</span></li>
            <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                    <div class="text-truncate" data-i18n="Payroll">Payroll</div>
                    </a>
                    <ul class="menu-sub">
                @if(Auth::user()->hasRole([ 'Finance Manager']))

                    <li class="menu-item">
                        <a href="{{ url('salaries') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bxs-user-account"></i>
                        <div class="text-truncate" data-i18n="Employees">Add to Salaries</div>
                        </a>
                    </li>

                    <li class="menu-item">
                        <a href="{{ url('salaries/create') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                        <div class="text-truncate" data-i18n="Salaries">Salaries</div>
                        </a>
                    </li>
                     @endif

                    <li class="menu-item">
                        <a href="{{ url('salariesTransaction') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-transfer-alt"></i>
                        <div class="text-truncate" data-i18n="Transaction">Transactions</div>
                        </a>
                    </li>


                    </ul>
                </li>
            @endif

        </ul>
    </aside>
  <!-- / Menu -->
   @endsection

    @section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row mb-4">
            <div class="col-12"><h3 class="mb-0"><i class="bx bx-user"></i> Employees Report</h3></div>
        </div>

        <form method="GET" action="{{ url('employee-report') }}" class="row g-2 align-items-end mb-4">
            <div class="col-auto">
                <label class="form-label small mb-0">Period</label>
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach(['daily'=>'Daily','weekly'=>'Weekly','monthly'=>'Monthly','quarterly'=>'Quarterly','semiannual'=>'Semiannual','yearly'=>'Yearly'] as $val=>$label)
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
            <div class="col-lg-2 col-6"><div class="rep-kpi"><small class="text-muted">TOTAL EMPLOYEES</small><div class="value">{{ number_format($total) }}</div></div></div>
            <div class="col-lg-2 col-6"><div class="rep-kpi"><small class="text-muted">ADDED TO SALARY</small><div class="value">{{ $active }}</div></div></div>
            <div class="col-lg-2 col-6"><div class="rep-kpi"><small class="text-muted">TERMINATED</small><div class="value">{{ $terminated }}</div></div></div>
            <div class="col-lg-2 col-6"><div class="rep-kpi"><small class="text-muted">PENDING</small><div class="value">{{ $pending }}</div></div></div>
            <div class="col-lg-2 col-6"><div class="rep-kpi"><small class="text-muted">AVG BASIC SALARY</small><div class="value">GH&#x20B5; {{ number_format($avgSalary,2) }}</div></div></div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card rep-card h-100">
                    <div class="card-header">Hires Trend</div>
                    <div class="card-body"><div id="emp-trend-chart"></div></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card rep-card h-100">
                    <div class="card-header">By Status</div>
                    <div class="card-body">
                        @php
                            $statusTotals = ($byStatus->sum('cnt') ?? 0) ?: 0;
                            $statusMap = ($byStatus->keyBy('status') ?? collect());
                            $ordered = ['Active','Terminated','On Leave'];
                        @endphp

                        <div class="row text-center mb-3">
                            @foreach($ordered as $st)
                                @php
                                    $r = $statusMap[$st] ?? (object)['cnt'=>0];
                                    $pct = $statusTotals ? round(($r->cnt / $statusTotals) * 100, 1) : 0;
                                @endphp
                                <div class="col-12 col-md-4 mb-2">
                                    <div class="rep-kpi">
                                        <div class="small text-muted">{{ $st }}</div>
                                        <div class="pct">{{ $pct }}%</div>
                                        <div class="sub">{{ $r->cnt }} employees</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div id="emp-status-chart" style="height:220px"></div>

                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card rep-card h-100">
                    <div class="card-header">Top Roles</div>
                    <div class="card-body">
                        <div id="chart-top-roles" style="height:240px"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card rep-card h-100">
                    <div class="card-header">Roles Summary</div>
                    <div class="card-body">
                        <div id="chart-role-summary" style="height:240px"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card rep-card h-100">
                    <div class="card-header">Field Office Summary</div>
                    <div class="card-body">
                        <div id="chart-field-summary" style="height:260px"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card rep-card h-100">
                    <div class="card-header">Top Departments (hires)</div>
                    <div class="card-body">
                        <div id="chart-top-depts" style="height:240px"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card rep-card h-100">
                    <div class="card-header">Terminations (selected period)</div>
                    <div class="card-body">
                        <div id="emp-terminations-trend" style="height:180px"></div>
                        <div class="mt-3"><div class="rep-kpi"><small class="text-muted">Terminations in period</small><div class="value">{{ $terminationsInPeriod }}</div></div></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card rep-card h-100">
                    <div class="card-header">Terminations by Month</div>
                    <div class="table-responsive">
                        <div id="chart-terminations-month" style="height:200px"></div>
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Month</th><th>Terminations</th></tr></thead>
                            <tbody>
                                @forelse($terminationsTrend as $t)
                                    <tr><td>{{ 
                                        
                                        \Carbon\Carbon::parse($t->month)->format('M Y')
                                    }}</td><td>{{ $t->total }}</td></tr>
                                @empty
                                    <tr><td colspan="2" class="text-muted text-center py-3">No terminations in this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
    @endsection

    @section('scripts')
    <script src="{{asset('vendor/libs/apex-charts/apexcharts.js')}}"></script>
    <script>
        // Use period-aligned overlay arrays (hires vs terminations)
        const rawTrendLabels = @json($overlayLabels) || [];
        const rawTrendValues = @json($overlayHires) || [];
        const rawTermValues = @json($overlayTerms) || [];
        const reportPeriod = @json($period);

        const trendLabels = rawTrendLabels.map(l => {
            if (!l) return '-';
            const d = new Date(l);
            if (reportPeriod === 'daily') return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
            if (reportPeriod === 'weekly') return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
            if (reportPeriod === 'yearly') return d.getFullYear().toString();
            // monthly: short month + year
            return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short' });
        });

        const trendValues = rawTrendValues.map(v => (typeof v === 'number' ? v : Number(v) || 0));
        const termValuesOverlay = rawTermValues.map(v => (typeof v === 'number' ? v : Number(v) || 0));

        const hiresTickAmount = Math.min(8, trendLabels.length || 8);
        new ApexCharts(document.querySelector('#emp-trend-chart'), {
            chart: { type: 'line', height: 300, toolbar: { show: false } },
            series: [
                { name: 'Hires', type: 'area', data: trendValues },
                { name: 'Terminations', type: 'line', data: termValuesOverlay }
            ],
            xaxis: { categories: trendLabels, labels: { rotate: -45, trim: true }, tickAmount: hiresTickAmount },
            dataLabels: { enabled: false },
            colors: ['#7c3aed', '#ef4444'],
            stroke: { curve: 'smooth', width: [2, 2] },
            tooltip: { x: { formatter: (val) => val }, y: { formatter: val => val ? Number(val).toLocaleString() : '0' } },
            responsive: [{ breakpoint: 768, options: { xaxis: { labels: { rotate: -30 }, tickAmount: Math.min(4, trendLabels.length || 4) } } }]
        }).render();

        const statusLabels = @json($byStatus->pluck('status')) || [];
        const statusValues = @json($byStatus->pluck('cnt')) || [];
        const statusSeries = statusValues.map(v => (typeof v === 'number' ? v : Number(v) || 0));

        if (statusSeries.some(v => v > 0)) {
            new ApexCharts(document.querySelector('#emp-status-chart'), {
                chart: { type: 'donut', height: 220 },
                series: statusSeries,
                labels: statusLabels.map(s => s ? (s.charAt(0).toUpperCase() + s.slice(1)) : '-'),
                colors: ['#10b981', '#ef4444', '#f59e0b'],
                legend: { position: 'bottom' }
            }).render();
        } else {
            document.querySelector('#emp-status-chart').innerHTML = '<div class="text-muted p-3">No status data for this period.</div>';
        }

        // Terminations trend chart (by month)
        const termLabelsRaw = @json($terminationsTrend->pluck('month')) || [];
        const termValuesRaw = @json($terminationsTrend->pluck('total')) || [];
        const termLabels = termLabelsRaw.map(l => {
            if (!l) return '-';
            const d = new Date(l);
            return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short' });
        });
        const termValues = termValuesRaw.map(v => (typeof v === 'number' ? v : Number(v) || 0));
        const termTick = Math.min(8, termLabels.length || 8);

        if (termValues.some(v => v > 0)) {
            new ApexCharts(document.querySelector('#emp-terminations-trend'), {
                chart: { type: 'area', height: 180, toolbar: { show: false } },
                series: [{ name: 'Terminations', data: termValues }],
                xaxis: { categories: termLabels, labels: { rotate: -30, trim: true }, tickAmount: termTick },
                dataLabels: { enabled: false },
                colors: ['#ef4444'],
                stroke: { curve: 'smooth', width: 2 },
                tooltip: { x: { formatter: (val) => val }, y: { formatter: val => val ? Number(val).toLocaleString() : '0' } },
                responsive: [{ breakpoint: 768, options: { xaxis: { labels: { rotate: 0 }, tickAmount: Math.min(4, termLabels.length || 4) } } }]
            }).render();
        } else {
            document.querySelector('#emp-terminations-trend').innerHTML = '<div class="text-muted p-3">No terminations data for this period.</div>';
        }

        // Top Roles bar
        const topRoleLabels = @json($topRoleLabels) || [];
        const topRoleCounts = @json($topRoleCounts) || [];
        if ((topRoleCounts || []).some(v => v > 0)) {
            new ApexCharts(document.querySelector('#chart-top-roles'), {
                chart: { type: 'bar', height: 240 },
                series: [{ name: 'Employees', data: topRoleCounts }],
                xaxis: { categories: topRoleLabels, labels: { rotate: -45, trim: true } },
                colors: ['#2563eb'],
                tooltip: { y: { formatter: v => v ? Number(v).toLocaleString() : '0' } }
            }).render();
        } else {
            document.querySelector('#chart-top-roles').innerHTML = '<div class="text-muted p-3">No role data.</div>';
        }

        // Roles summary donut
        const roleLabels = @json($roleLabels) || [];
        const roleCounts = @json($roleCounts) || [];
        if ((roleCounts || []).some(v => v > 0)) {
            new ApexCharts(document.querySelector('#chart-role-summary'), {
                chart: { type: 'donut', height: 240 },
                series: roleCounts,
                labels: roleLabels,
                legend: { position: 'bottom' }
            }).render();
        } else {
            document.querySelector('#chart-role-summary').innerHTML = '<div class="text-muted p-3">No role data.</div>';
        }

        // Field summary stacked bar
        const fieldLabels = @json($fieldLabels) || [];
        const fieldActive = @json($fieldActiveArr) || [];
        const fieldHires = @json($fieldHiresArr) || [];
        const fieldTerms = @json($fieldTermsArr) || [];
        if ((fieldActive.concat(fieldHires, fieldTerms)).some(v => v > 0)) {
            new ApexCharts(document.querySelector('#chart-field-summary'), {
                chart: { type: 'bar', height: 260, stacked: true },
                series: [
                    { name: 'Active', data: fieldActive },
                    { name: 'Hires', data: fieldHires },
                    { name: 'Terminations', data: fieldTerms }
                ],
                xaxis: { categories: fieldLabels, labels: { rotate: -45, trim: true } },
                colors: ['#10b981', '#7c3aed', '#ef4444'],
                legend: { position: 'bottom' },
                tooltip: { y: { formatter: v => v ? Number(v).toLocaleString() : '0' } }
            }).render();
        } else {
            document.querySelector('#chart-field-summary').innerHTML = '<div class="text-muted p-3">No field data.</div>';
        }

        // Top Departments bar
        const deptLabels = @json($deptLabels) || [];
        const deptCounts = @json($deptCounts) || [];
        if ((deptCounts || []).some(v => v > 0)) {
            new ApexCharts(document.querySelector('#chart-top-depts'), {
                chart: { type: 'bar', height: 240 },
                series: [{ name: 'Hires', data: deptCounts }],
                xaxis: { categories: deptLabels, labels: { rotate: -45, trim: true } },
                colors: ['#06b6d4'],
                tooltip: { y: { formatter: v => v ? Number(v).toLocaleString() : '0' } }
            }).render();
        } else {
            document.querySelector('#chart-top-depts').innerHTML = '<div class="text-muted p-3">No department data.</div>';
        }

        // Terminations by month bar
        const tMonthRaw = @json($terminationMonths) || [];
        const tMonthVals = @json($terminationCounts) || [];
        const tMonthLabels = tMonthRaw.map(l => {
            if (!l) return '-';
            const d = new Date(l);
            return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short' });
        });
        if ((tMonthVals || []).some(v => v > 0)) {
            new ApexCharts(document.querySelector('#chart-terminations-month'), {
                chart: { type: 'bar', height: 200 },
                series: [{ name: 'Terminations', data: tMonthVals }],
                xaxis: { categories: tMonthLabels, labels: { rotate: -45, trim: true } },
                colors: ['#ef4444'],
                tooltip: { y: { formatter: v => v ? Number(v).toLocaleString() : '0' } }
            }).render();
        } else {
            document.querySelector('#chart-terminations-month').innerHTML = '<div class="text-muted p-3">No terminations data.</div>';
        }
    </script>
    @endsection

</x-sales-dashboard>

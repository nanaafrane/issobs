<x-sales-dashboard>

    @php
      $user = Auth::user();
      $canViewInvoices = $user?->hasRole(['Invoice','Finance Manager', 'Director']) ?? false;
      $canViewReceipts = $user?->hasRole(['Finance Manager', 'Manager','Admin Assistant']) ?? false;
      $canManageFinance = $user?->hasRole(['Finance Manager']) ?? false;
      $canManageOperations = $user?->hasRole(['Invoice' ,'Director', 'Manager', 'Admin Assistant']) ?? false;
      $canViewAccounts = $user?->hasRole(['Finance Manager', 'Director']) ?? false;
      $canViewPayroll = ($user?->hasPermission('Accounts') ?? false) && ($user?->hasRole(['Invoice', 'Officer', 'Director', 'Finance Manager']) ?? false);
    @endphp

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
        <li class="menu-item ">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-home-smile text-primary me-2"></i>
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

        @if($canViewInvoices)
        <li class="menu-item">
            <a href="{{ url('invoice') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-receipt text-primary me-2"></i>
            <div class="text-truncate" data-i18n="Invoices">Invoices</div>
            </a>
        </li>
        <li class="menu-item ">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-receipt text-primary me-2"></i>
            <div class="text-truncate" data-i18n="Staffs">Pro Forma</div>
            </a>
            <ul class="menu-sub">
            <li class="menu-item ">
                <a href="{{url('proforma/create')}}" class="menu-link">
                <div class="text-truncate" data-i18n="SRegister">Generate</div>
                </a>
            </li>
            <li class="menu-item">
                <a href="{{url('proforma')}}" class="menu-link">
                <div class="text-truncate" data-i18n="SList">List</div>
                </a>
            </li>
            <li class="menu-item">
                <a href="{{url('proformaClient')}}" class="menu-link">
                <div class="text-truncate" data-i18n="SList">ProForma Clients</div>
                </a>
            </li>
            </ul>
        </li>
        @endif


        @if($canViewReceipts)

        <li class="menu-item ">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-money-withdraw bg-primary"></i>
            <div class="text-truncate" data-i18n="Receipts">Receipts</div>
            </a>
            <ul class="menu-sub">

                <li class="menu-item">
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
        @endif

        @if($canManageFinance)
        <!-- Components -->
        <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
        <li class="menu-item">
            <a href="{{url('client')}}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-user-detail text-info me-2"></i>
            <div class="text-truncate" data-i18n="Clients">Clients</div>
            </a>
        </li>
        <li class="menu-item ">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-user-circle text-info me-2"></i>
            <div class="text-truncate" data-i18n="Staffs">Employees</div>
            </a>
            <ul class="menu-sub">
            <li class="menu-item ">
                <a href="{{url('employees/create')}}" class="menu-link">
                <div class="text-truncate" data-i18n="SRegister">Register</div>
                </a>
            </li>
            <li class="menu-item">
                <a href="{{url('employees')}}" class="menu-link">
                <div class="text-truncate" data-i18n="SList">List</div>
                </a>
            </li>
            <li class="menu-item">
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

        <li class="menu-item">
            <a href="{{url('category')}}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-category text-info me-2"></i>
            <div class="text-truncate" data-i18n="Categories">Categories</div>
            </a>
        </li>
        @elseif($canManageOperations)

        <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
            <li class="menu-item ">
                <a class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-user-detail text-info me-2"></i>
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
            <li class="menu-item ">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-category text-info me-2"></i>
            <div class="text-truncate" data-i18n="Staffs">Employees</div>
            </a>
            <ul class="menu-sub">
            <li class="menu-item ">
                <a href="{{url('employees/create')}}" class="menu-link">
                <div class="text-truncate" data-i18n="SRegister">Register</div>
                </a>
            </li>
            <li class="menu-item">
                <a href="{{url('employees')}}" class="menu-link">
                <div class="text-truncate" data-i18n="SList">List</div>
                </a>
            </li>
            <li class="menu-item">
                    <a href="{{url('employeesPending')}}" class="menu-link">
                    <div class="text-truncate" data-i18n="SList">Pending</div>
                    </a>
                </li>
            <li class="menu-item">
                <a href="{{url('employeesnrrit')}}" class="menu-link">
                <div class="text-truncate" data-i18n="SList">Terminate / Recruit </div>
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

        @if($canViewAccounts)

        <li class="menu-header small text-uppercase"> <span class="menu-header-text text-danger">Accounts</span></li>

        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-chart text-danger me-2"></i>
            <div class="text-truncate" data-i18n="Accounts"> Accounts </div>
            </a>
            <ul class="menu-sub">
            <li class="menu-item">
                <a href="{{url('collections')}}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-add-to-queue bg-danger"></i>
                <div class="text-truncate" data-i18n="ARegister">Collections</div>
                </a>
            </li>
            <li class="menu-item">
                <a href="{{url('deposit')}}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-arrow-from-left bg-danger"></i>
                <div class="text-truncate" data-i18n="AList">Bank Deposit</div>
                </a>
            </li>
            <li class="menu-item">
                <a href="{{url('banks')}}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-bank text-danger me-2"></i>
                <div class="text-truncate" data-i18n="AList">Banks</div>
                </a>
            </li>
            </ul>
        </li>
        <li class="menu-item">
            <a href="{{url('expense')}}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-credit-card text-secondary me-2"></i>
            <div class="text-truncate" data-i18n="Expense"> Expense </div>
            </a>
        </li>

        <li class="menu-item active open">
            <a href="javascript:void(0);" class="menu-link menu-toggle"><i class="menu-icon tf-icons bx bx-time-five bg-danger"></i><div>Overtime</div></a>
            <ul class="menu-sub">
                <li class="menu-item "><a href="{{url('overtime')}}" class="menu-link"><div>Daily Entry</div></a></li>
                <li class="menu-item active"><a href="{{url('overtime-report')}}" class="menu-link"><div>Reports</div></a></li>
            </ul>
        </li>
        @endif

        @if($canViewPayroll)
        <li class="menu-header small text-uppercase"><span class="menu-header-text">PAYROLL</span></li>
            <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-money-withdraw text-primary me-2"></i>
                    <div class="text-truncate" data-i18n="Payroll">Payroll</div>
                    </a>
                    <ul class="menu-sub">
                    @if(Auth::user()->hasRole([ 'Finance Manager']))

                    <li class="menu-item">
                        <a href="{{ url('salaries') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-user-circle text-info me-2"></i>
                        <div class="text-truncate" data-i18n="Employees">Add to Salaries</div>
                        </a>
                    </li>

                    <li class="menu-item">
                        <a href="{{ url('salaries/create') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-money-withdraw text-primary me-2"></i>
                        <div class="text-truncate" data-i18n="Salaries">Salaries</div>
                        </a>
                    </li>
                    @endif


                    <li class="menu-item">
                        <a href="{{ url('salariesTransaction') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-transfer-alt text-primary me-2"></i>
                        <div class="text-truncate" data-i18n="Transaction">Transactions</div>
                        </a>
                    </li>

                    </ul>
                </li>
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
                    @foreach(['daily'=>'Daily','weekly'=>'Weekly','monthly'=>'Monthly','quarterly'=>'Quarterly','semiannual'=>'Semi-Annual','yearly'=>'Yearly'] as $val=>$label)
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
                                <tr class="ot-drill-row" data-type="field" data-field-id="{{ $row->field_id }}"><td>{{ $row->field_name }}</td><td>{{ $row->cnt }}</td><td>GH&#x20B5; {{ number_format($row->total,2) }}</td></tr>
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
                                <tr class="ot-drill-row" data-type="reason" data-key="{{ $row->reason }}"><td>{{ $row->reason ?: '-' }}</td><td>{{ $row->cnt }}</td><td>GH&#x20B5; {{ number_format($row->total,2) }}</td></tr>
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
                                <tr class="ot-drill-row" data-type="client" data-client-id="{{ $row->id }}" data-client-name="{{ $row->business_name ?: $row->name }}"><td>{{ $row->business_name ?: $row->name }}</td><td>{{ $row->cnt }}</td><td>GH&#x20B5; {{ number_format($row->total,2) }}</td></tr>
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
                                <tr class="ot-drill-row" data-type="employee" data-employee-id="{{ $row->id }}" data-employee-name="{{ $row->name }}"><td>{{ $row->name }}</td><td>{{ $row->cnt }}</td><td>GH&#x20B5; {{ number_format($row->total,2) }}</td></tr>
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
        const reportPeriod = @json($period);
        const reportAnchor = @json($anchor->format('Y-m-d'));
        new ApexCharts(document.querySelector('#ot-trend-chart'), {
            chart: { type: 'area', height: 300, toolbar: { show: false } },
            series: [{ name: 'Overtime (GHS)', data: trendValues }],
            xaxis: { categories: trendLabels },
            dataLabels: { enabled: false },
            colors: ['#7c3aed'],
            stroke: { curve: 'smooth', width: 2 },
        }).render();

        const shiftLabelsRaw = @json($byShift->pluck('shift'));
        const shiftValuesRaw = @json($byShift->pluck('total'));

        const shiftLabels = Array.isArray(shiftLabelsRaw)
            ? shiftLabelsRaw.map(s => s ? (s.charAt(0).toUpperCase() + s.slice(1)) : '-')
            : [];

        const shiftValues = Array.isArray(shiftValuesRaw)
            ? shiftValuesRaw.map(v => (typeof v === 'number' ? v : Number(v) || 0))
            : [];

        if (shiftValues.length && shiftValues.some(v => v > 0)) {
            new ApexCharts(document.querySelector('#ot-shift-chart'), {
                chart: { type: 'donut', height: 300 },
                series: shiftValues,
                labels: shiftLabels,
                colors: ['#f59e0b', '#1f2937'],
            }).render();
        } else {
            document.querySelector('#ot-shift-chart').innerHTML = '<div class="text-muted p-4">No shift data for this period.</div>';
        }
            // Make drill rows clickable and fetch details
            document.querySelectorAll('.ot-drill-row').forEach(r => r.style.cursor = 'pointer');
            const modalEl = document.createElement('div');
                        modalEl.innerHTML = `
                        <div class="modal fade" id="ot-details-modal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="ot-details-title">Details</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div id="ot-details-loading" class="text-center py-3">Loading…</div>
                                        <div id="ot-details-body" style="display:none">
                                            <h6>Clients</h6>
                                            <ul id="ot-details-clients" class="list-unstyled"></ul>
                                            <hr>
                                            <h6>Employees</h6>
                                            <ul id="ot-details-employees" class="list-unstyled"></ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>`;
            document.body.appendChild(modalEl);

            function showDetails(type, key, displayName) {
                const title = type === 'field' ? 'Field Office Details'
                    : type === 'reason' ? 'Reason Details'
                    : type === 'client' ? 'Client Details'
                    : type === 'employee' ? 'Employee Details'
                    : 'Details';
                const modalNode = document.getElementById('ot-details-modal');
                const titleNode = document.getElementById('ot-details-title');
                const loadingNode = document.getElementById('ot-details-loading');
                const bodyNode = document.getElementById('ot-details-body');
                const clientsNode = document.getElementById('ot-details-clients');
                const employeesNode = document.getElementById('ot-details-employees');

                titleNode.textContent = title + (displayName ? ' — ' + displayName : (type === 'field' ? ' — ' + key : ' — ' + key));
                loadingNode.style.display = '';
                bodyNode.style.display = 'none';
                clientsNode.innerHTML = '';
                employeesNode.innerHTML = '';

                // toggle visibility: for 'reason' hide clients, for 'field' hide employees
                const clientsHeader = clientsNode.previousElementSibling;
                const clientsHr = clientsNode.nextElementSibling;
                const employeesHeader = employeesNode.previousElementSibling;
                const employeesHr = employeesNode.previousElementSibling ? employeesNode.previousElementSibling.previousElementSibling : null;

                if (type === 'reason') {
                    // show employees only, hide clients
                    if (clientsHeader) clientsHeader.style.display = 'none';
                    clientsNode.style.display = 'none';
                    if (clientsHr) clientsHr.style.display = 'none';

                    if (employeesHeader) employeesHeader.style.display = '';
                    if (employeesHr) employeesHr.style.display = '';
                    employeesNode.style.display = '';
                } else if (type === 'client') {
                    // show both: absent employees in clientsNode and replacements in employeesNode
                    if (clientsHeader) clientsHeader.style.display = '';
                    clientsNode.style.display = '';
                    if (clientsHr) clientsHr.style.display = '';

                    if (employeesHeader) employeesHeader.style.display = '';
                    if (employeesHr) employeesHr.style.display = '';
                    employeesNode.style.display = '';
                } else {
                    // field or employee: show clients, hide employees
                    if (clientsHeader) clientsHeader.style.display = '';
                    clientsNode.style.display = '';
                    if (clientsHr) clientsHr.style.display = '';

                    if (employeesHeader) employeesHeader.style.display = 'none';
                    if (employeesHr) employeesHr.style.display = 'none';
                    employeesNode.style.display = 'none';
                }

                let url;
                if (type === 'field') url = `/overtime/field/${encodeURIComponent(key)}/details`;
                else if (type === 'reason') url = `/overtime/reason/${encodeURIComponent(key)}/details`;
                else if (type === 'client') url = `/overtime/client/${encodeURIComponent(key)}/details`;
                else if (type === 'employee') url = `/overtime/employee/${encodeURIComponent(key)}/details`;
                else url = `/overtime/reason/${encodeURIComponent(key)}/details`;

                const params = `?period=${encodeURIComponent(reportPeriod)}&date=${encodeURIComponent(reportAnchor)}`;
                url = url + params;

                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => {
                        loadingNode.style.display = 'none';
                        bodyNode.style.display = '';

                        // handle responses by type
                        if ((type === 'field') || (type === 'employee')) {
                            // both 'field' and 'employee' return clients array
                            if (Array.isArray(data.clients) && data.clients.length) {
                                data.clients.forEach(c => {
                                    const li = document.createElement('li');
                                    const label = (c.business_name || c.name || '–');
                                    const entries = c.entries || 0;
                                    const total = (typeof c.total === 'number') ? c.total : Number(c.total) || 0;
                                    li.textContent = `${label} — ${entries} entries — GH₵ ${total.toFixed(2)}`;
                                    clientsNode.appendChild(li);
                                });
                            } else {
                                clientsNode.innerHTML = '<li class="text-muted">No clients found.</li>';
                            }
                        } else if (type === 'client') {
                            // client returns absent + replacements
                            if (Array.isArray(data.absent) && data.absent.length) {
                                data.absent.forEach(a => {
                                    const li = document.createElement('li');
                                    li.textContent = a.name;
                                    clientsNode.appendChild(li);
                                });
                            } else {
                                clientsNode.innerHTML = '<li class="text-muted">No absent employees found.</li>';
                            }

                            if (Array.isArray(data.replacements) && data.replacements.length) {
                                data.replacements.forEach(r => {
                                    const li = document.createElement('li');
                                    li.textContent = `${r.name} — GH₵ ${Number(r.total).toFixed(2)}`;
                                    employeesNode.appendChild(li);
                                });
                            } else {
                                employeesNode.innerHTML = '<li class="text-muted">No replacements found.</li>';
                            }
                        } else if (type === 'reason') {
                            // reason returns employees only
                            if (Array.isArray(data.employees) && data.employees.length) {
                                data.employees.forEach(e => {
                                    const li = document.createElement('li');
                                    li.textContent = e.name;
                                    employeesNode.appendChild(li);
                                });
                            } else {
                                employeesNode.innerHTML = '<li class="text-muted">No employees found.</li>';
                            }
                        }
                    })
                    .catch(() => {
                        loadingNode.style.display = 'none';
                        bodyNode.style.display = '';
                        clientsNode.innerHTML = '<li class="text-muted">Error loading data.</li>';
                        employeesNode.innerHTML = '<li class="text-muted">Error loading data.</li>';
                    });

                // show modal using bootstrap if available, else toggle simple visibility
                if (window.bootstrap && typeof bootstrap.Modal === 'function') {
                    const m = new bootstrap.Modal(document.getElementById('ot-details-modal'));
                    m.show();
                } else {
                    const el = document.getElementById('ot-details-modal');
                    el.classList.add('show');
                    el.style.display = 'block';
                }
            }

            document.addEventListener('click', function (ev) {
                const row = ev.target.closest('.ot-drill-row');
                if (!row) return;
                const type = row.dataset.type || (row.dataset.fieldId ? 'field' : 'reason');
                let key;
                if (type === 'field') key = row.dataset.fieldId;
                else if (type === 'reason') key = row.dataset.key;
                else if (type === 'client') key = row.dataset.clientId;
                else if (type === 'employee') key = row.dataset.employeeId;
                else key = row.dataset.key;
                const displayName = row.querySelector('td') ? row.querySelector('td').textContent.trim() : null;
                showDetails(type, key, displayName);
            });
    </script>
    @endsection

</x-sales-dashboard>

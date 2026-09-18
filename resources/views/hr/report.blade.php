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
          <i class="menu-icon tf-icons bx bx-home-smile"></i>
          <div class="text-truncate" data-i18n="Dashboards">Dashboard</div>
        </a>
        <ul class="menu-sub">
          <li class="menu-item">
            <a href="{{url('home')}}" class="menu-link">
              <div class="text-truncate" data-i18n="Dashboard">Dashboard</div>
            </a>
          </li>
        </ul>
      </li>
      <!-- Components -->
      <li class="menu-header small text-uppercase"><span class="menu-header-text">Management</span></li>
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
      <li class="menu-item ">
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
        </ul>
      </li>

      <li class="menu-item">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-bxs-user-detail"></i>
          <div class="text-truncate" data-i18n="Clients">Clients</div>
        </a>
        <ul class="menu-sub">
          <li class="menu-item">
            <a href="{{url('client/create')}}" class="menu-link">
              <div class="text-truncate" data-i18n="CRegister">Register</div>
            </a>
          </li>
          <li class="menu-item">
            <a href="{{url('client')}}" class="menu-link">
              <div class="text-truncate" data-i18n="CList">List</div>
            </a>
          </li>
        </ul>
      </li>
      @if(Auth::user()->hasRole(['Manager']))

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

      <li class="menu-header small text-uppercase"> <span class="menu-header-text text-danger">Accounts</span></li>
      <li class="menu-item active open">
          <a href="javascript:void(0);" class="menu-link menu-toggle"><i class="menu-icon tf-icons bx bx-time-five bg-danger"></i><div>Overtime</div></a>
          <ul class="menu-sub">
              <li class="menu-item"><a href="{{url('overtime')}}" class="menu-link"><div>Daily Entry</div></a></li>
              <li class="menu-item active"><a href="{{url('overtime-report')}}" class="menu-link"><div>Reports</div></a></li>
          </ul>
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
        const trendLabels = @json($trendLabels);
        const trendValues = @json($trend->pluck('total'));
        const reportPeriod = @json($period);
        const reportAnchor = @json($anchor->format('Y-m-d'));
        new ApexCharts(document.querySelector('#ot-trend-chart'), {
            chart: { type: 'area', height: 300, toolbar: { show: false } },
            series: [{ name: 'Overtime (GHS)', data: trendValues }],
            xaxis: {
                categories: trendLabels,
                labels: { rotate: -45, hideOverlappingLabels: true }
            },
            tooltip: { x: { show: true } },
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
                                        <div id="ot-field-details" class="d-none">
                                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                                <div class="small text-muted" id="ot-field-summary"></div>
                                                <button type="button" class="btn btn-success btn-sm" id="ot-field-export" disabled>
                                                    <i class="bx bx-spreadsheet me-1"></i> Export Excel
                                                </button>
                                            </div>
                                            <div class="table-responsive border rounded">
                                                <table class="table table-sm table-hover mb-0" id="ot-field-table">
                                                    <thead class="table-light"><tr id="ot-field-table-head"><th>Client</th><th class="text-end">Entries</th><th class="text-end">Overtime Amount (GH₵)</th></tr></thead>
                                                    <tbody id="ot-field-rows"></tbody>
                                                </table>
                                            </div>
                                        </div>
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
            let fieldExportName = 'Field Office';

            document.getElementById('ot-field-export').addEventListener('click', function () {
                const table = document.getElementById('ot-field-table');
                const workbook = '<html><head><meta charset="utf-8"></head><body><h3>'
                    + fieldExportName.replace(/[&<>"']/g, '') + ' Overtime Clients</h3>'
                    + table.outerHTML + '</body></html>';
                const file = new Blob(['\ufeff', workbook], { type: 'application/vnd.ms-excel' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(file);
                link.download = fieldExportName.replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '') + '-overtime-clients.xls';
                link.click();
                URL.revokeObjectURL(link.href);
            });

            function renderModalTable(headers, rows) {
                const head = document.getElementById('ot-field-table-head');
                const body = document.getElementById('ot-field-rows');
                head.innerHTML = '';
                headers.forEach((header, index) => {
                    const cell = document.createElement('th');
                    cell.textContent = header;
                    if (index > 0) cell.className = 'text-end';
                    head.appendChild(cell);
                });
                body.innerHTML = '';
                if (!rows.length) {
                    body.innerHTML = `<tr><td colspan="${headers.length}" class="text-center text-muted py-3">No overtime records for this period.</td></tr>`;
                    return false;
                }
                rows.forEach(values => {
                    const row = document.createElement('tr');
                    values.forEach((value, index) => {
                        const cell = document.createElement('td');
                        cell.textContent = value;
                        if (index > 0) cell.className = 'text-end';
                        row.appendChild(cell);
                    });
                    body.appendChild(row);
                });
                return true;
            }

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
                const fieldDetailsNode = document.getElementById('ot-field-details');
                const fieldRowsNode = document.getElementById('ot-field-rows');
                const fieldSummaryNode = document.getElementById('ot-field-summary');
                const fieldExportNode = document.getElementById('ot-field-export');

                titleNode.textContent = title + (displayName ? ' — ' + displayName : (type === 'field' ? ' — ' + key : ' — ' + key));
                loadingNode.style.display = '';
                bodyNode.style.display = 'none';
                fieldDetailsNode.classList.remove('d-none');
                fieldRowsNode.innerHTML = '';
                fieldSummaryNode.textContent = '';
                fieldExportNode.disabled = true;
                clientsNode.innerHTML = '';
                employeesNode.innerHTML = '';

                // toggle visibility: for 'reason' hide clients, for 'field' hide employees
                const clientsHeader = clientsNode.previousElementSibling;
                const clientsHr = clientsNode.nextElementSibling;
                const employeesHeader = employeesNode.previousElementSibling;
                const employeesHr = employeesNode.previousElementSibling ? employeesNode.previousElementSibling.previousElementSibling : null;

                if (type === 'field') {
                    fieldExportName = displayName || 'Field Office';
                } else if (type === 'reason') {
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
                        // handle responses by type
                        if (type === 'field') {
                            const clients = Array.isArray(data.clients) ? data.clients : [];
                            const total = clients.reduce((sum, client) => sum + (Number(client.total) || 0), 0);
                            fieldSummaryNode.textContent = `${clients.length} client${clients.length === 1 ? '' : 's'} · GH₵ ${total.toFixed(2)} total`;
                            fieldExportNode.disabled = !renderModalTable(['Client', 'Entries', 'Overtime Amount (GH₵)'], clients.map(client => [client.business_name || client.name || '–', Number(client.entries) || 0, (Number(client.total) || 0).toFixed(2)]));
                        } else if (type === 'employee') {
                            const clients = Array.isArray(data.clients) ? data.clients : [];
                            const total = clients.reduce((sum, client) => sum + (Number(client.total) || 0), 0);
                            fieldExportName = displayName || 'Guard';
                            fieldSummaryNode.textContent = `${clients.length} client${clients.length === 1 ? '' : 's'} · GH₵ ${total.toFixed(2)} total`;
                            fieldExportNode.disabled = !renderModalTable(['Client / Site', 'Entries', 'Overtime Amount (GH₵)'], clients.map(client => [client.business_name || client.name || '–', Number(client.entries) || 0, (Number(client.total) || 0).toFixed(2)]));
                        } else if (type === 'reason') {
                            const employees = Array.isArray(data.employees) ? data.employees : [];
                            fieldExportName = displayName || 'Reason';
                            fieldSummaryNode.textContent = `${employees.length} guard${employees.length === 1 ? '' : 's'} affected`;
                            fieldExportNode.disabled = !renderModalTable(['Guard', 'Occurrences'], employees.map(employee => [employee.name || '–', Number(employee.occurrences) || 0]));
                        } else if (type === 'client') {
                            const absent = Array.isArray(data.absent) ? data.absent : [];
                            const replacements = Array.isArray(data.replacements) ? data.replacements : [];
                            const total = replacements.reduce((sum, employee) => sum + (Number(employee.total) || 0), 0);
                            fieldExportName = displayName || 'Client';
                            fieldSummaryNode.textContent = `${absent.length} absent · ${replacements.length} replacement${replacements.length === 1 ? '' : 's'} · GH₵ ${total.toFixed(2)} earned`;
                            const rows = [
                                ...absent.map(employee => ['Absent', employee.name || '–', '', '']),
                                ...replacements.map(employee => ['Replacement', employee.name || '–', Number(employee.entries) || 0, (Number(employee.total) || 0).toFixed(2)]),
                            ];
                            fieldExportNode.disabled = !renderModalTable(['Type', 'Guard', 'Entries', 'Overtime Amount (GH₵)'], rows);
                        }
                        /* Legacy list rendering remains below for compatibility with non-table drilldowns. */
                        if (false && ((type === 'field') || (type === 'employee'))) {
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
                        fieldSummaryNode.textContent = 'Unable to load drill-down data.';
                        renderModalTable(['Details'], []);
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

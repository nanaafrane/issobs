<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">

    <link href="https://cdn.datatables.net/columncontrol/1.1.1/css/columnControl.dataTables.min.css" rel="stylesheet">
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

        @if(Auth::user()->hasPermission('Accounts'))
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

                @if(Auth::user()->hasRole(['Finance Manager']))
                    <li class="menu-item">
                        <a href="{{url('receipt')}}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-money-withdraw bg-primary"></i>
                            <div class="text-truncate" data-i18n="Receipts">Receipts</div>
                        </a>
                    </li>

            <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
            <li class="menu-item">
                <a href="{{url('client')}}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-bxs-user-detail bg-info"></i>
                <div class="text-truncate" data-i18n="Clients">Clients</div>
                </a>
            </li>
        <li class="menu-item">
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
            @if(Auth::user()->hasPermission('HR'))
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
                    <li class="menu-item">
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

            @if((Auth::user()->hasRole(['Manager', 'Officer', 'Finance Manager']) && Auth::user()->hasPermission('Accounts')) )

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

            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle"><i class="menu-icon tf-icons bx bx-time-five bg-danger"></i><div>Overtime</div></a>
                <ul class="menu-sub">
                    <li class="menu-item"><a href="{{url('overtime')}}" class="menu-link"><div>Daily Entry</div></a></li>
                    <li class="menu-item"><a href="{{url('overtime-report')}}" class="menu-link"><div>Reports</div></a></li>
                </ul>
            </li>

            @endif

            <li class="menu-header small text-uppercase"><span class="menu-header-text">PAYROLL</span></li>
            <li class="menu-item active open">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                    <div class="text-truncate" data-i18n="Payroll">Payroll</div>
                    </a>
                    <ul class="menu-sub">
                    @if(Auth::user()->hasPermission('HR') || Auth::user()->hasRole(['Invoice' , 'Finance Manager']))
                    <li class="menu-item active">
                        <a href="{{ url('salaries') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bxs-user-account"></i>
                        <div class="text-truncate" data-i18n="Employees">Add to Salaries</div>
                        </a>
                    </li>
                    @endif

                    <li class="menu-item">
                        <a href="{{ url('salaries/create') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                        <div class="text-truncate" data-i18n="Salaries">Salaries</div>
                        </a>
                    </li>

                    <li class="menu-item">
                        <a href="{{ url('salariesTransaction') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-transfer-alt"></i>
                        <div class="text-truncate" data-i18n="Transaction">Transactions</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('salaries.report') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-line-chart"></i>
                        <div class="text-truncate" data-i18n="SalariesReport">Salaries Report</div>
                        </a>
                    </li>
                    </ul>
                </li>
        </ul>
    </aside>
  <!-- / Menu -->
    
  @endsection


  @section('content')

  <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bx-bxs-user-detail"></i> Payroll / Add to Salaries  </h3>
            </div>
        </div><br>

        @if(Auth::user()->hasRole(['Invoice','Manager' , 'Finance Manager' ]))
        <div class="row">
            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> ACCRA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ $employeeAccra }}  </strong> </h4>
                        <small class="fw-medium"> TOTAL EMPLOYEES  </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> BOTWE </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ $employeeBotwe }} </strong> </h4>
                        <small class="fw-medium"> TOTAL EMPLOYEES </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> SHAIHILLS </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ $employeeShyhills }} </strong> </h4>
                        <small class="fw-medium"> TOTAL EMPLOYEES </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> TEMA </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ $employeeTema }} </strong> </h4>
                        <small class="fw-medium"> TOTAL EMPLOYEES </small>
                    </div>
                </div>
            </div>



            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1">TAKORADI</p>
                        <h4 class="card-title mb-3 text-white"> {{ $employeeTakoradi }} </h4>
                        <small class="fw-medium"> TOTAL EMPLOYEES   </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"> <strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3 text-white">{{ $employeeKoforidua }}</h4>
                        <small class="fw-medium"> TOTAL EMPLOYEES  </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2 m-3">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3 text-white"> {{ $employeeKumasi }} </h4>
                        <small class="fw-medium"> TOTAL EMPLOYEES  </small>
                    </div>
                </div>
            </div>

        </div> <br> <br>
        @endif
        <br>
        <div class="card-header  ml-2  d-none d-lg-block">
            @include('flash-messages')
        </div> <br>

        
            <div class="row">
                <form action="{{ route('salaries.store') }}" method="POST" id="addToSalariesForm">
                    @csrf
                    <div class="col">
                        {{-- Toolbar: target month + filters. Changing the month refreshes the "Payroll" column. --}}
                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-auto">
                                <label for="salary_month" class="form-label small mb-0">Salary month</label>
                                <input type="month" id="salary_month" name="salary_month" class="form-control form-control-sm"
                                       value="{{ old('salary_month', now()->format('Y-m')) }}" required />
                            </div>
                            <div class="col-auto">
                                <label for="filter_field" class="form-label small mb-0">Field office</label>
                                <select id="filter_field" class="form-select form-select-sm">
                                    <option value="">All fields</option>
                                    @foreach($fields as $field)
                                        <option value="{{ $field->id }}">{{ $field->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                <label for="filter_payroll" class="form-label small mb-0">Payroll status</label>
                                <select id="filter_payroll" class="form-select form-select-sm">
                                    <option value="">Everyone</option>
                                    <option value="not_added">Not yet added this month</option>
                                    <option value="added">Already added this month</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-dark btn-sm" type="submit" id="addToSalariesBtn" disabled>
                                    <i class="icon-base bx bx-arrow-from-left"></i> Add to salaries
                                </button>
                            </div>
                        </div>

                        {{-- Selection survives paging, sorting and searching (kept in JS, posted on submit). --}}
                        <div class="alert alert-secondary py-2 small d-flex flex-wrap gap-3 align-items-center" id="selectionBar">
                            <span id="selectionText">No employee selected. Tick employees on any page, or select every matching employee.</span>
                            <a href="#" id="selectAllMatching" class="fw-semibold"></a>
                            <a href="#" id="clearSelection" class="text-danger d-none">Clear selection</a>
                        </div>

                        <div id="selectionInputs"></div>

                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive text-normal-dark">
                                    <table id="myTable" class="display" data-source="{{ route('salaries.employeesData') }}">
                                        <thead>
                                            <tr>
                                            <th><input class="form-check-input" type="checkbox" id="options" title="Select all on this page" /></th>
                                            <th>#</th>
                                            <th> Employee ID </th>
                                            <th>Name</th>
                                            <th>Gender</th>
                                            <th>Number</th>
                                            <th> Employment Date </th>
                                            <th> Department </th>
                                            <th>Role</th>
                                            <th>Field Office</th>
                                            <th>Client </th>
                                            <th> Location </th>
                                            <th>Payment Type</th>
                                            <th>Bank Name </th>
                                            <th>TAX</th>
                                            <th>SSNIT</th>
                                            <th>Basic</th>
                                            <th>Allowa</th>
                                            <th>Payroll</th>
                                            </tr>
                                        </thead>
                                        <tbody>{{-- Rows are loaded by the server-side DataTable. --}}</tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
       
    </div>
  <!-- / Content -->

  @endsection


    @section('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.dataTables.js"></script>
    <script src="https://cdn.datatables.net/columncontrol/1.1.1/js/dataTables.columnControl.min.js"></script>

    <script>
    $(function () {
        const $table = $('#myTable');
        const monthEl = document.getElementById('salary_month');
        const fieldEl = document.getElementById('filter_field');
        const payrollEl = document.getElementById('filter_payroll');

        // Selection model: either an explicit set of ids, or "all matching the filters" minus exclusions.
        const selection = { ids: new Set(), all: false, excluded: new Set(), selectable: 0, filterKey: '' };
        // Fingerprint of the text searches, so "select all" is dropped only when the search really changes.
        const filterKey = p => JSON.stringify([(p.search && p.search.value) || '', (p.columns || []).map(c =>
            (c.columnControl && c.columnControl.search && c.columnControl.search.value) || (c.search && c.search.value) || '')]);
        let lastParams = {};

        const table = $table.DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: 25,
            searchDelay: 400,
            ajax: {
                url: $table.data('source'),
                type: 'GET',
                data: function (d) {
                    d.salary_month = monthEl.value;
                    d.field_id = fieldEl.value;
                    d.payroll_filter = payrollEl.value;
                    lastParams = d;
                    return d;
                },
                dataSrc: function (json) {
                    selection.selectable = json.selectable || 0;
                    if (selection.all && filterKey(lastParams) !== selection.filterKey) {
                        selection.all = false; selection.excluded.clear();
                    }
                    return json.data;
                },
            },
            order: [[2, 'asc']],
            columnControl: [{ target: 1, content: ['search'] }],
            columns: [
                { data: 'id', orderable: false, searchable: false, render: function (id, type, row) {
                    if (row.in_payroll) {
                        return '<input class="form-check-input" type="checkbox" disabled title="Already in this month\'s salaries" />';
                    }
                    const checked = selection.all ? !selection.excluded.has(id) : selection.ids.has(id);
                    return '<input class="checkBoxes form-check-input" type="checkbox" value="' + id + '"' + (checked ? ' checked' : '') + ' />';
                } },
                { data: 'row_number', orderable: false, searchable: false, render: (d, t, r, meta) => meta.row + 1 + meta.settings._iDisplayStart },
                { data: 'employee_id' }, { data: 'name' }, { data: 'gender' }, { data: 'phone_number' },
                { data: 'date_of_joining' }, { data: 'department' }, { data: 'role' }, { data: 'field' },
                { data: 'client' }, { data: 'location' }, { data: 'payment_type' }, { data: 'bank' },
                { data: 'tax', orderable: false }, { data: 'ssnit', orderable: false },
                { data: 'basic_salary' }, { data: 'allowances' },
                { data: 'payroll', orderable: false, searchable: false },
            ],
            layout: {
                topStart: { buttons: [{ extend: 'pageLength', className: 'btn btn-secondary' }] },
            },
            lengthMenu: [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]],
        });

        function selectedCount() {
            return selection.all ? Math.max(0, selection.selectable - selection.excluded.size) : selection.ids.size;
        }

        function refreshBar() {
            const n = selectedCount();
            const month = monthEl.value ? new Date(monthEl.value + '-01T00:00:00').toLocaleDateString(undefined, { month: 'long', year: 'numeric' }) : 'the selected month';
            $('#selectionText').text(n === 0
                ? 'No employee selected. Tick employees on any page, or select every matching employee.'
                : (selection.all
                    ? (selection.excluded.size
                        ? n + ' of ' + selection.selectable + ' matching employee(s) not yet in ' + month + ' are selected.'
                        : 'All ' + n + ' matching employee(s) not yet in ' + month + ' are selected.')
                    : n + ' employee(s) selected for ' + month + '.'));
            $('#selectAllMatching').toggle(!selection.all && selection.selectable > 0)
                .text('Select all ' + selection.selectable + ' matching employee(s) not yet added');
            $('#clearSelection').toggleClass('d-none', n === 0);
            $('#addToSalariesBtn').prop('disabled', n === 0 || !monthEl.value);
            const $boxes = $table.find('tbody .checkBoxes');
            $('#options').prop('checked', $boxes.length > 0 && $boxes.filter(':checked').length === $boxes.length);
        }

        function clearSelection() {
            selection.ids.clear(); selection.excluded.clear(); selection.all = false;
            table.rows().invalidate('data').draw(false);
        }

        $table.on('change', 'tbody .checkBoxes', function () {
            const id = Number(this.value);
            if (selection.all) {
                this.checked ? selection.excluded.delete(id) : selection.excluded.add(id);
            } else {
                this.checked ? selection.ids.add(id) : selection.ids.delete(id);
            }
            refreshBar();
        });

        // Header checkbox: select / unselect the rows on THIS page (it used to invert them).
        $('#options').on('change', function () {
            const on = this.checked;
            $table.find('tbody .checkBoxes').each(function () {
                if (this.checked !== on) { this.checked = on; $(this).trigger('change'); }
            });
        });

        $('#selectAllMatching').on('click', function (e) {
            e.preventDefault();
            selection.all = true; selection.ids.clear(); selection.excluded.clear();
            selection.filterKey = filterKey(lastParams);
            table.rows().invalidate('data').draw(false);
        });
        $('#clearSelection').on('click', function (e) { e.preventDefault(); clearSelection(); });

        // Filters and month change what "matching" means, so they reset the selection.
        $(fieldEl).add(payrollEl).add(monthEl).on('change', clearSelection);
        table.on('draw.dt', refreshBar);

        $('#addToSalariesForm').on('submit', function (e) {
            const n = selectedCount();
            if (n === 0) { e.preventDefault(); return; }
            if (!confirm('Add ' + n + ' employee(s) to the ' + monthEl.value + ' salaries?')) { e.preventDefault(); return; }

            const $box = $('#selectionInputs').empty();
            const add = (name, value) => $box.append($('<input type="hidden">').attr('name', name).val(value));

            if (selection.all) {
                // The server re-applies these exact filters, so it adds the same people the user saw.
                add('select_all', '1');
                add('filter_search', (lastParams.search && lastParams.search.value) || '');
                add('filter_field_id', fieldEl.value);
                (lastParams.columns || []).forEach(function (c, i) {
                    const v = (c.columnControl && c.columnControl.search && c.columnControl.search.value) || (c.search && c.search.value) || '';
                    if (v) add('filter_columns[' + i + ']', v);
                });
                selection.excluded.forEach(id => add('excluded[]', id));
            } else {
                selection.ids.forEach(id => add('employees[]', id));
            }
            $('#addToSalariesBtn').prop('disabled', true).text('Adding…');
        });
    });
    </script>

    @endsection
</x-hr-dashboard>
  @php
      $user = Auth::user();
      $canViewInvoices = $user?->hasRole(['Invoice','Finance Manager', 'Director']) ?? false;
      $canViewReceipts = $user?->hasRole(['Finance Manager', 'Manager','Admin Assistant']) ?? false;
      $canManageFinance = $user?->hasRole(['Finance Manager']) ?? false;
      $canManageOperations = $user?->hasRole(['Invoice' ,'Director', 'Manager', 'Admin Assistant']) ?? false;
      $canViewAccounts = $user?->hasRole(['Finance Manager', 'Director']) ?? false;
      $canViewPayroll = ($user?->hasPermission('Accounts') ?? false) && ($user?->hasRole(['Invoice', 'Officer', 'Director', 'Finance Manager']) ?? false);
  @endphp
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
      <li class="menu-item active open">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-home-smile text-primary me-2"></i>
          <div class="text-truncate" data-i18n="Dashboards"><strong>Dashboard</strong></div>
        </a>
        <ul class="menu-sub">
          <li class="menu-item active">
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
      @if($canViewInvoices)
      <li class="menu-item">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
           <i class="menu-icon tf-icons bx bx-receipt text-primary me-2"></i>
                <div class="text-truncate" data-i18n="Invoices">Invoices</div>
          </a>
          <ul class="menu-sub"> 
              <li class="menu-item">
                  <a href="{{url('invoice')}}" class="menu-link">
                  <div class="text-truncate" data-i18n="SList">Invoices</div>
                  </a>
              </li>

              <li class="menu-item">
                  <a href="{{url('invoice-report')}}" class="menu-link">
                  <div class="text-truncate" data-i18n="SList">Reports</div>
                  </a>
              </li>
          </ul>
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
            <li class="menu-item">
                <a href="{{url('receipt-report')}}" class="menu-link">
                <div class="text-truncate" data-i18n="RPending">Reports </div>
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
           <i class="menu-icon tf-icons bx bx-bxs-user-detail text-info me-2"></i>
          <div class="text-truncate" data-i18n="Clients">Clients</div>
        </a>
      </li>
        <li class="menu-item {{ request()->is('attendance*') ? 'active' : '' }}">
            <a href="{{ route('attendance.index') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-calendar-check text-info me-2"></i>
            <div class="text-truncate" data-i18n="Attendance">Guard Attendance</div>
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
              <div class="text-truncate" data-i18n="SRegister">New Recruit</div>
              </a>
          </li>
          <li class="menu-item">
              <a href="{{url('employees')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">List</div>
              </a>
          </li>
          <li class="menu-item">
              <a href="{{url('employee-report')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">Reports</div>
              </a>
          </li>
          <li class="menu-item">
              <a href="{{ route('employees.movements') }}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">HR movements</div>
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
              <i class="menu-icon tf-icons bx bx-bxs-user-detail text-info me-2"></i>
                <div class="text-truncate" data-i18n="Clients"><strong>Clients</strong></div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item ">
                    <a href="{{url('client/create')}}" class="menu-link">
                        <div class="text-truncate" data-i18n="CRegister">New Contract</div>
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
              <div class="text-truncate" data-i18n="SRegister">New Recruit</div>
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
              <a href="{{ route('employees.movements') }}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">HR movements</div>
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
                <div class="text-truncate"><strong>Accounts</strong></div>
            </a>
            <ul class="menu-sub">

                <li @class(['menu-item', 'active' => request()->is('deposit*')])>
                    <a href="{{ url('deposit') }}" class="menu-link"><div class="text-truncate">Bank Deposit</div></a>
                </li>
                <li @class(['menu-item', 'active' => request()->is('banks*')])>
                    <a href="{{ url('banks') }}" class="menu-link"><div class="text-truncate">Bank &amp; cash</div></a>
                </li>
                <li @class(['menu-item', 'active' => request()->is('money/transfers*')])>
                    <a href="{{ route('money.transfers') }}" class="menu-link"><div class="text-truncate">Transfers / fund cash</div></a>
                </li>
                <li @class(['menu-item', 'active' => request()->is('money/payments*')])>
                    <a href="{{ route('money.payments') }}" class="menu-link"><div class="text-truncate">Payments</div></a>
                </li>
                <li @class(['menu-item', 'active' => request()->is('money/cash-book*')])>
                    <a href="{{ route('money.cashbook') }}" class="menu-link"><div class="text-truncate">Cash book (by item)</div></a>
                </li>
                <li @class(['menu-item', 'active' => request()->is('creditors*')])>
                    <a href="{{ route('creditors.index') }}" class="menu-link"><div class="text-truncate">Creditors (loans)</div></a>
                </li>
            </ul>
        </li>

      <li class="menu-item">
        <a href="{{url('expense')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-credit-card text-secondary me-2"></i>
          <div class="text-truncate" data-i18n="Expense"> <strong>Expense</strong> </div>
        </a>
       </li>

      <li class="menu-item">
          <a href="javascript:void(0);" class="menu-link menu-toggle"><i class="menu-icon tf-icons bx bx-time-five bg-danger"></i><div> <strong>Overtime</strong></div></a>
          <ul class="menu-sub">
              <li class="menu-item"><a href="{{url('overtime')}}" class="menu-link"><div>Daily Entry</div></a></li>
              <li class="menu-item"><a href="{{url('overtime-report')}}" class="menu-link"><div>Reports</div></a></li>
                <li class="menu-item"><a href="{{ route('overtime.payouts') }}" class="menu-link"><div>Pay-outs</div></a></li>
          </ul>
      </li>

      {{-- Fleet: open to every office (each sees its own vehicles); approvals and pay-outs are role-checked. --}}
        <li @class(['menu-item', 'active open' => request()->is('fleet*')])>
            <a href="javascript:void(0);" class="menu-link menu-toggle"><i class="menu-icon tf-icons bx bx-car text-info me-2"></i><div> <strong>Fleet</strong></div></a>
            <ul class="menu-sub">
                <li @class(['menu-item', 'active' => request()->is('fleet/vehicles*')])><a href="{{ route('fleet.vehicles') }}" class="menu-link"><div>Vehicles</div></a></li>
                <li @class(['menu-item', 'active' => request()->is('fleet/fuel') || request()->is('fleet/fuel/*')])><a href="{{ route('fleet.fuel') }}" class="menu-link"><div>Fuel</div></a></li>
                <li @class(['menu-item', 'active' => request()->is('fleet/fuel-payouts*')])><a href="{{ route('fleet.fuel.payouts') }}" class="menu-link"><div>Fuel pay-outs</div></a></li>
                <li @class(['menu-item', 'active' => request()->is('fleet/maintenance*')])><a href="{{ route('fleet.maintenance') }}" class="menu-link"><div>Maintenance</div></a></li>
                <li @class(['menu-item', 'active' => request()->is('fleet/report*')])><a href="{{ route('fleet.report') }}" class="menu-link"><div>Monthly cost</div></a></li>
            </ul>
        </li>
      @endif

      @if($canViewPayroll)
      <li class="menu-header small text-uppercase"><span class="menu-header-text">PAYROLL</span></li>
            <li class="menu-item ">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                <div class="text-truncate" data-i18n="Payroll">Payroll</div>
                </a>
                <ul class="menu-sub">
                @if(Auth::user()->hasRole(['Finance Manager']) )
                <li @class(['menu-item', 'active' => request()->is('salaries/index')])>
                    <a href="{{ url('salaries') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bxs-user-account"></i>
                    <div class="text-truncate" data-i18n="Employees">Add to Salaries</div>
                    </a>
                </li>

                <li @class(['menu-item', 'active' => request()->is('salaries/create', 'salariesCreate*', 'salaries')])>
                    <a href="{{ url('salaries/create') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                    <div class="text-truncate" data-i18n="Salaries">Salaries</div>
                    </a>
                </li>
                @endif

                <li @class(['menu-item', 'active' => request()->is('salariesTransaction', 'salariesMonth*', 'salaries')])>
                    <a href="{{ url('salariesTransaction') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-transfer-alt"></i>
                    <div class="text-truncate" data-i18n="Transaction">Transactions</div>
                    </a>
                </li>

               @if(Auth::user()->hasRole(['Finance Manager']) )
                <li @class(['menu-item', 'active' => request()->is('salaries-report')])>
                    <a href="{{ route('salaries.report') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-line-chart"></i>
                    <div class="text-truncate" data-i18n="SalariesReport">Salaries Report</div>
                    </a>
                </li>
                <li @class(['menu-item', 'active' => request()->is('salary-payments')])>
                    <a href="{{ route('salary-payments.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-list-check"></i>
                    <div class="text-truncate" data-i18n="SalaryPayments">Salary payments</div>
                    </a>
                </li>
                <li @class(['menu-item', 'active' => request()->is('absent-deductions')])>
                    <a href="{{ route('absent-deductions.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-calendar-x"></i>
                    <div class="text-truncate" data-i18n="AbsentDeductions">Absent deductions</div>
                    </a>
                </li>
                <li @class(['menu-item', 'active' => request()->is('pay-priority')])>
                    <a href="{{ route('pay-priority.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bxs-bolt"></i>
                    <div class="text-truncate" data-i18n="PayPriority">Pay priority rules</div>
                    </a>
                </li>
                @endif

                </ul>
            </li>
      @endif

    </ul>
  </aside>
  <!-- / Menu -->

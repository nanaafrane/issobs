@php
    $stamp = match ($salary->payment_status) {
        'approved' => ['text' => 'PAID',     'color' => '#198754'],
        'hold'     => ['text' => 'ON HOLD',  'color' => '#ffab00'],
        'rejected' => ['text' => 'REJECTED', 'color' => '#dc3545'],
        default    => ['text' => 'PENDING',  'color' => '#ff3e1d'],
    };

          $user = Auth::user();
      $canViewInvoices = $user?->hasRole(['Invoice','Finance Manager', 'Director']) ?? false;
      $canViewReceipts = $user?->hasRole(['Finance Manager', 'Manager','Admin Assistant']) ?? false;
      $canManageFinance = $user?->hasRole(['Finance Manager']) ?? false;
      $canManageOperations = $user?->hasRole(['Invoice' ,'Director', 'Manager', 'Admin Assistant']) ?? false;
      $canViewAccounts = $user?->hasRole(['Finance Manager', 'Director']) ?? false;
      $canViewPayroll = ($user?->hasPermission('Accounts') ?? false) && ($user?->hasRole(['Invoice', 'Officer', 'Director', 'Finance Manager']) ?? false);
@endphp


<!doctype html>

<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title></title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{asset('img/favicon/favicon.ico')}}" />

    <!-- Fonts -->
    <link rel="stylesheet" href="{{ asset('libs/fonts/public-sans/public-sans.css') }}" />

    <link rel="stylesheet" href="{{asset('vendor/fonts/iconify-icons.css')}}" />

    <!-- Core CSS -->
    <!-- build:css assets/vendor/css/theme.css  -->

    <link rel="stylesheet" href="{{asset('vendor/css/core.css')}}" />
    <link rel="stylesheet" href="{{asset('css/demo.css')}}" />

    <!-- Vendors CSS -->

    <link rel="stylesheet" href="{{asset('vendor/libs/perfect-scrollbar/perfect-scrollbar.css')}}" />

    <!-- endbuild -->

    <link rel="stylesheet" href="{{asset('vendor/libs/apex-charts/apex-charts.css')}}" />

    <!-- Page CSS -->
    <style>
        #printContent { position: relative; }

        .status-stamp {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            z-index: 0;
            pointer-events: none;
            text-align: center;
            color: var(--stamp-color);
            border: 10px double var(--stamp-color);
            border-radius: 18px;
            padding: 10px 60px;
            opacity: 0.18;
            font-weight: 900;
            line-height: 1;
            text-transform: uppercase;
            white-space: nowrap;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .status-stamp .stamp-text {
            display: block;
            font-size: 120px;
            letter-spacing: 12px;
        }

        .status-stamp .stamp-date {
            display: block;
            font-size: 28px;
            letter-spacing: 4px;
            margin-top: 6px;
        }

        /* keep payslip content above the stamp */
        .watermarked { position: relative; z-index: 1; }

        @media print {
            .status-stamp { position: fixed; opacity: 0.15; }
        }
    </style>
    <!-- Helpers -->
    <script src="{{asset('vendor/js/helpers.js')}}"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->

    <script src="{{asset('js/config.js')}}"></script>

</head>

<body >
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">

        <div style="background: #fbfbfbff; " class="layout-container">


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
      <li @class(['menu-item', 'active open' => request()->is('home') || request()->is('home/*')])>
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-home-smile text-primary me-2"></i>
          <div class="text-truncate" data-i18n="Dashboards"><strong>Dashboard</strong></div>
        </a>
        <ul class="menu-sub">
          <li @class(['menu-item', 'active' => request()->is('home') || request()->is('home/*')])>
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
      <li @class(['menu-item', 'active open' => request()->is('invoice*')])>
        <a href="javascript:void(0);" class="menu-link menu-toggle">
           <i class="menu-icon tf-icons bx bx-receipt text-primary me-2"></i>
                <div class="text-truncate" data-i18n="Invoices">Invoices</div>
          </a>
          <ul class="menu-sub"> 
              <li @class(['menu-item', 'active' => request()->is('invoice') || request()->is('invoice/*')])>
                  <a href="{{url('invoice')}}" class="menu-link">
                  <div class="text-truncate" data-i18n="SList">Invoices</div>
                  </a>
              </li>

              <li  @class(['menu-item', 'active' => request()->is('invoice-report')])>
                  <a href="{{url('invoice-report')}}" class="menu-link">
                  <div class="text-truncate" data-i18n="SList">Reports</div>
                  </a>
              </li>
          </ul>
      </li>
      <li @class(['menu-item', 'active open' => request()->is('proforma*')])>
          <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-receipt text-primary me-2"></i>
          <div class="text-truncate" data-i18n="Staffs">Pro Forma</div>
          </a>
          <ul class="menu-sub">
          <li  @class(['menu-item', 'active' => request()->is('proforma/create')])>
              <a href="{{url('proforma/create')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SRegister">Generate</div>
              </a>
          </li>
          <li  @class(['menu-item', 'active' => request()->is('proforma')])>
              <a href="{{url('proforma')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">List</div>
              </a>
          </li>
          <li  @class(['menu-item', 'active' => request()->is('proformaClient')])>
              <a href="{{url('proformaClient')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">ProForma Clients</div>
              </a>
          </li>
          </ul>
      </li>
      @endif


      @if($canViewReceipts)
      <li @class(['menu-item', 'active open' => request()->is('receipt*')])>
          <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-money-withdraw bg-primary"></i>
          <div class="text-truncate" data-i18n="Receipts">Receipts</div>
          </a>
          <ul class="menu-sub">

            <li @class(['menu-item', 'active' => request()->is('receipt') || request()->is('receipt/*')] )>
                <a href="{{url('receipt')}}" class="menu-link">
                <div class="text-truncate" data-i18n="RList">List</div>
                </a>
            </li>
            <li @class(['menu-item', 'active' => request()->is('receipt-multi/create')])>
                <a href="{{url('receipt-multi/create')}}" class="menu-link">
                <div class="text-truncate" data-i18n="RList">New Receipt</div>
                </a>
            </li>
            <li @class(['menu-item', 'active' => request()->is('receiptPending')])>
                <a href="{{url('receiptPending')}}" class="menu-link">
                <div class="text-truncate" data-i18n="RPending">Pending </div>
                </a>
            </li>
            <li  @class(['menu-item', 'active' => request()->is('receipt-report')])>
                <a href="{{url('receipt-report')}}" class="menu-link">
                <div class="text-truncate" data-i18n="RPending">Reports </div>
                </a>
            </li>
              <li  @class(['menu-item', 'active' => request()->is('receipt-voided')])>
                <a href="{{url('receipt-voided')}}" class="menu-link">
                <div class="text-truncate" data-i18n="voided">Voided </div>
                </a>
            </li>
              <li  @class(['menu-item', 'active' => request()->is('receivables/aged')])>
                <a href="{{ url('receivables/aged') }}" class="menu-link">
                <div class="text-truncate" data-i18n="receivables">Aged Recievables </div>
                </a>
            </li>

          </ul>
      </li>
       @endif

       @if($canManageFinance)
      <!-- Components -->
      <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
      <li @class(['menu-item', 'active' => request()->is('client') || request()->is('client/*') || request()->is('clientStatement*') || request()->is('clientPending') || request()->is('clientTerminated')])>
        <a href="{{url('client')}}" class="menu-link">
           <i class="menu-icon tf-icons bx bx-bxs-user-detail text-info me-2"></i>
          <div class="text-truncate" data-i18n="Clients">Clients</div>
        </a>
      </li>
        <li @class(['menu-item', 'active' => request()->is('attendance*')])>
            <a href="{{ route('attendance.index') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-calendar-check text-info me-2"></i>
            <div class="text-truncate" data-i18n="Attendance">Guard Attendance</div>
            </a>
        </li>
      <li @class(['menu-item', 'active open' => request()->is('employees*') || request()->is('employee-report') || request()->is('employeesBank') || request()->is('employeesCash') || request()->is('employeesPending')])>
          <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-user-circle text-info me-2"></i>
          <div class="text-truncate" data-i18n="Staffs">Employees</div>
          </a>
          <ul class="menu-sub">
          <li @class(['menu-item', 'active' => request()->is('employees/create')])>
              <a href="{{url('employees/create')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SRegister">New Recruit</div>
              </a>
          </li>
          <li @class(['menu-item', 'active' => request()->is('employees') || (request()->is('employees/*') && !request()->is('employees/create')) || request()->is('employeesSalary/*') || request()->is('employeesPayInfo/*') || request()->routeIs('employees.PayInfo') || request()->routeIs('employees.ViewPayInfo')])>
              <a href="{{url('employees')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">List</div>
              </a>
          </li>
          <li @class(['menu-item', 'active' => request()->is('employee-report')])>
              <a href="{{url('employee-report')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">Reports</div>
              </a>
          </li>
          <li @class(['menu-item', 'active' => request()->is('employees/movements*') || request()->routeIs('employees.movements')])>
              <a href="{{ route('employees.movements') }}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">HR movements</div>
              </a>
          </li>
          <li @class(['menu-item', 'active' => request()->is('employeesBank')])>
              <a href="{{url('employeesBank')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">Employee Banks</div>
              </a>
          </li>
          <li @class(['menu-item', 'active' => request()->is('employeesCash')])>
              <a href="{{url('employeesCash')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">Employee Cash</div>
              </a>
          </li>

          </ul>
      </li>

      <li @class(['menu-item', 'active' => request()->is('category*')])>
        <a href="{{url('category')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-category text-info me-2"></i>
          <div class="text-truncate" data-i18n="Categories">Categories</div>
        </a>
      </li>
      @elseif($canManageOperations)

      <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
        <li @class(['menu-item', 'active open' => request()->is('client') || request()->is('client/*') || request()->is('clientStatement*') || request()->is('clientPending') || request()->is('clientTerminated')])>
            <a class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bxs-user-detail text-info me-2"></i>
                <div class="text-truncate" data-i18n="Clients"><strong>Clients</strong></div>
            </a>
            <ul class="menu-sub">
                <li @class(['menu-item', 'active' => request()->is('client/create')])>
                    <a href="{{url('client/create')}}" class="menu-link">
                        <div class="text-truncate" data-i18n="CRegister">New Contract</div>
                    </a>
                </li>
                <li @class(['menu-item', 'active' => request()->is('client') || (request()->is('client/*') && !request()->is('client/create')) || request()->is('clientStatement*')])>
                    <a href="{{url('client')}}" class="menu-link">
                        <div class="text-truncate" data-i18n="CList">List</div>
                    </a>
                </li>

                <li @class(['menu-item', 'active' => request()->is('clientTerminated')])>
                    <a href="{{url('clientTerminated')}}" class="menu-link">
                        <div class="text-truncate" data-i18n="CList">Terminated</div>
                    </a>
                </li>

                <li @class(['menu-item', 'active' => request()->is('clientPending')])>
                    <a href="{{url('clientPending')}}" class="menu-link">
                        <div class="text-truncate" data-i18n="CList">Pending</div>
                    </a>
                </li>
            </ul>
        </li>
        <li @class(['menu-item', 'active open' => request()->is('employees*') || request()->is('employeesPending') || request()->is('employeesBank') || request()->is('employeesCash') || request()->routeIs('employees.movements')])>
          <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-category text-info me-2"></i>
          <div class="text-truncate" data-i18n="Staffs">Employees</div>
          </a>
          <ul class="menu-sub">
          <li @class(['menu-item', 'active' => request()->is('employees/create')])>
              <a href="{{url('employees/create')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SRegister">New Recruit</div>
              </a>
          </li>
          <li @class(['menu-item', 'active' => request()->is('employees') || (request()->is('employees/*') && !request()->is('employees/create')) || request()->is('employeesPending') || request()->is('employeesSalary/*') || request()->is('employeesPayInfo/*') || request()->routeIs('employees.PayInfo') || request()->routeIs('employees.ViewPayInfo')])>
              <a href="{{url('employees')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">List</div>
              </a>
          </li>
          <li @class(['menu-item', 'active' => request()->is('employeesPending')])>
                <a href="{{url('employeesPending')}}" class="menu-link">
                <div class="text-truncate" data-i18n="SList">Pending</div>
                </a>
            </li>
          <li @class(['menu-item', 'active' => request()->routeIs('employees.movements')])>
              <a href="{{ route('employees.movements') }}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">HR movements</div>
              </a>
          </li>
          <li @class(['menu-item', 'active' => request()->is('employeesBank')])>
              <a href="{{url('employeesBank')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">Employee Banks</div>
              </a>
          </li>

          <li @class(['menu-item', 'active' => request()->is('employeesCash')])>
              <a href="{{url('employeesCash')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">Employee Cash</div>
              </a>
          </li>

          </ul>
      </li>
      @endif

      @if($canViewAccounts)

      <li class="menu-header small text-uppercase"> <span class="menu-header-text text-danger">Accounts</span></li>

        <li @class(['menu-item', 'active open' => request()->is('deposit*') || request()->is('banks*') || request()->is('money/*') || request()->is('creditors*')])>
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
        
      <li @class(['menu-item', 'active' => request()->is('expense*')])>
        <a href="{{url('expense')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-credit-card text-secondary me-2"></i>
          <div class="text-truncate" data-i18n="Expense"> <strong>Expense</strong> </div>
        </a>
       </li>

      <li @class(['menu-item', 'active open' => request()->is('overtime*')])>
          <a href="javascript:void(0);" class="menu-link menu-toggle"><i class="menu-icon tf-icons bx bx-time-five bg-danger"></i><div> <strong>Overtime</strong> </div></a>
          <ul class="menu-sub">
              <li @class(['menu-item', 'active' => request()->is('overtime') || request()->is('overtime/*') && !request()->is('overtime-report') && !request()->is('overtime/payouts*')])><a href="{{url('overtime')}}" class="menu-link"><div>Daily Entry</div></a></li>
              <li @class(['menu-item', 'active' => request()->is('overtime-report')])><a href="{{url('overtime-report')}}" class="menu-link"><div>Reports</div></a></li>
                <li @class(['menu-item', 'active' => request()->is('overtime/payouts*') || request()->routeIs('overtime.payouts')])><a href="{{ route('overtime.payouts') }}" class="menu-link"><div>Pay-outs</div></a></li>
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
            <li @class(['menu-item', 'active open' => request()->is('salaries*') || request()->is('salariesTransaction') || request()->is('salary-upload') || request()->is('salary-payments*') || request()->is('absent-deductions*') || request()->is('pay-priority*') || request()->is('salaries-report')])>
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                <div class="text-truncate" data-i18n="Payroll">Payroll</div>
                </a>
                <ul class="menu-sub">
                @if(Auth::user()->hasRole(['Finance Manager']) )
                <li @class(['menu-item', 'active' => request()->is('salaries') || request()->routeIs('salaries.index') || (request()->is('salaries/*') && !request()->is('salaries/*/edit') && !request()->is('salariesTransaction') && !request()->is('salariesMonth*') && !request()->is('salaries/*/show') && !request()->routeIs('salaries.show'))])>
                    <a href="{{ url('salaries') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bxs-user-account"></i>
                    <div class="text-truncate" data-i18n="Employees">Add to Salaries</div>
                    </a>
                </li>

                <li @class(['menu-item', 'active' => request()->is('salary-upload')])>
                    <a href="{{ url('salary-upload') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                    <div class="text-truncate" data-i18n="Salaries">Salaries</div>
                    </a>
                </li>
                @endif

                <li @class(['menu-item', 'active' => request()->is('salariesTransaction') || request()->is('salariesMonth*') || request()->is('salaries/*/edit') || (request()->is('salaries/*') && !request()->is('salaries/create') && !request()->is('salaries') && !request()->routeIs('salaries.index')) || request()->routeIs('salaries.salariesMonth')])>
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



            <!-- Layout container -->
                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <div id="printContent" class="content-wrapper">
                                                    <div class="status-stamp" style="--stamp-color: {{ $stamp['color'] }};">
                                <span class="stamp-text">{{ $stamp['text'] }}</span>
                                @if($salary->payment_status === 'approved' && $salary->approval_date)
                                    <span class="stamp-date">{{ $salary->approval_date->format('d M Y') }}</span>
                                @endif
                            </div>
                        <div class="watermarked"> 
                            <div class="container-xxl flex-grow-1 container-p-y">
                                <!-- Invoice 1 - Bootstrap Brain Component -->
                                <section class="py-3 py-md-5">
                                    <div class="row justify-content-center">
                                        <div style="margin-top: -40px;" class="col-12 col-lg-9 col-xl-8 col-xxl-7">
                                            <div class="row gy-3 mb-3">
                                    <div class="col-8">
                                        <h5 class="text-uppercase text-endx m-0 text-danger"><strong>FIRST WATCH SECURITY SERVICE LIMITED.</strong></h5> <br>
                                        <h4><strong>PAYSLIP FOR : {{ strtoupper($salary->salary_month?->format('F, Y')) }}</strong></h4>
                                    </div>
                                                <div class="col-4">
                                                    <a class="d-block text-end">
                                                        <img width="100px" src="{{asset('img/icons/brands/issobs.png')}}" class="img-fluid" alt="BootstrapBrain Logo" width="135" height="44">
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-12 col-sm-6 col-md-6">
                                                    <h5 class="text-danger"> <strong> BASIC INFO </strong>  </h5>
                                                    <address>
                                                        <h7> EMPLOYEE ID : <strong> FWSS {{ $salary->employee_id }} </strong> </h7> <br>
                                                        <h7>   EMPLOYEE NAME :<strong> {{ strtoupper($salary->employee?->name)  }} </strong>  </h7> <br>
                                                        <h7>  WORKER TYPE  :<strong> {{ strtoupper($salary->employee?->worker_type) }} </strong> </h7> <br>
                                                        <h7>  DEPARTMENT  : <strong>{{ strtoupper($salary->employee?->department?->name) }} </strong> </h7>  <br>
                                                        <h7>  ROLE  : <strong> {{ strtoupper($salary->employee?->role?->name) }} </strong> </h7>  
                                                    </address>
                                                </div>
                                                <div class="col-12 col-sm-6 col-md-6">
                                                <h5 class="text-danger text-end"> <strong> PAYMENT INFO </strong>  </h5>
                                                    <address>
                                                        <h7> PAYMENT TYPE : <strong> {{ strtoupper($salary->payment_type ) }} </strong> </h7> <br>
                                                        @if($salary->payment_type == 'Bank')
                                                        <h7>  BANK NAME : <strong> {{ strtoupper($salary->bank?->name ) }} </strong>  </h7> <br>
                                                        <h7> BANK ACCOUNT   : <strong> {{ strtoupper($salary?->account_number ) }} </strong> </h7> <br>
                                                        <h7> BANK BRANCH  : <strong> {{ strtoupper($salary?->branch ) }} </strong> </h7>  <br>
                                                        @endif
                                                        <br>
                                                        @if($salary->employee->tax_button == 'on')
                                                        <h5 > <span class="text-danger">TAX</span>   : <strong> GH&#8373; {{ strtoupper($salary?->tax ) }} </strong> </h5>  
                                                        @endif
                                                    </address>
                                                </div>
                                            </div>
                                            <hr style="height: 5px; background-color : black; margin-top: -16px;"/>
                                            @if($salary->employee->ssnit_button == 'on')
                                            <div class="row mb-3">
                                                <div class="col 8">
                                                    <h5 class="text-danger"> <strong> EARNINGS </strong>  </h5>
                                                    <div class="row">
                                                    <div class="col"> 
                                                    <address>
                                                        <h7> BASIC SALARY </h7> <br>
                                                        <h7 > ALLOWANCE  </h7>  <br>
                                                        <h7 > SSNIT 5% ADD UP </h7>   <br>
                                                        <h7> OVERTIME </h7>   <br>
                                                        <h7> AIRTIME ALLOWANCE </h7> <br>
                                                        <h7> REIMBURSEMENT </h7> <br>
                                                        <h7> T&T ALLOWANCE </h7> <br>
                                                        <h5> <strong>GROSS SALARY </strong>  </h5> 
                                                    </address>
                                                    </div>

                                                    <div class="col text-end">
                                                        <address>
                                                            <h7> <strong>   GH&#8373; {{ number_format($salary->basic_salary, 2)  }} </strong>  </h7> <br> 
                                                            <h7 > <strong>  GH&#8373; {{ number_format($salary->allowances, 2)  }} </strong> </h7>  <br> 
                                                            <h7 > <strong>   {{ $salary->ssnit_tier2_5 }} </strong> </h7>  <br>
                                                            <h7> <strong>    {{ $salary->overtime }} </strong> </h7>   <br>
                                                            <h7> <strong>    {{ $salary->airtime_allowance }} </strong>  </h7> <br>
                                                            <h7>  <strong>   {{ $salary->reimbursements }} </strong>  </h7> <br>
                                                            <h7>  <strong>   {{ $salary->transport_allowance }} </strong>  </h7> <br>
                                                            <h5> <strong>   GH&#8373; {{ number_format($salary->gross_salary, 2) }} </strong>  </h5> 
                                                        </address>
                                                    </div>
                                                    </div>
                                                </div>
                                                <div class="col 4">
                                                <h5 class="text-danger text-end"> <strong> PENSIONS </strong>  </h5>
                                                    <address class="text-end">
                                                        <h7> TIER 1 : <strong> {{ number_format($salary->ssnit_tier1_0_5, 2) }}  </strong>  </h7> <br>
                                                        <h7> TIER 2 : <strong>  {{ number_format($salary->ssnit_tier2_5, 2) }}  </strong> </h7> <br>
                                                        <h5> SUMMARY  : <strong>  GH&#8373; {{ number_format($salary->ssnit_tier1_0_5 + $salary->ssnit_tier2_5, 2) }}  </strong> </h5>  
                                                    </address>
                                                </div>
                                            </div>
                                            @else

                                            <div class="row mb-3">
                                                <div class="col">
                                                    <h5 class="text-danger"> <strong> EARNINGS </strong>  </h5>
                                                    <div class="row">
                                                    <div class="col"> 
                                                    <address>
                                                        <h7> BASIC SALARY   </h7> <br> 
                                                        <h7 > ALLOWANCE </h7>  <br> 
                                                        <h7 > SSNIT 5% ADD UP  </h7>  <br>
                                                        <h7> OVERTIME   <br>
                                                        <h7> AIRTIME ALLOWANCE  <br>
                                                        <h7> REIMBURSEMENT  <br>
                                                        <h7> T&T ALLOWANCE  <br>
                                                        <h5> <strong>GROSS SALARY </strong>  </h5> 
                                                    </address>

                                                    </div>

                                                    <div class="col text-end">
                                                        <address>
                                                            <h7> <strong>   GH&#8373; {{ number_format($salary->basic_salary, 2)  }} </strong>  </h7> <br> 
                                                            <h7 > <strong>  GH&#8373; {{ number_format($salary->allowances, 2)  }} </strong> </h7>  <br> 
                                                            <h7 ><strong>  {{ $salary->ssnit_tier2_5 }} </strong> </h7>  <br>
                                                            <h7> <strong> {{ $salary->overtime }} </strong> </h7>   <br>
                                                            <h7> <strong>  {{ $salary->airtime_allowance }} </strong>  </h7> <br>
                                                            <h7>  <strong>  {{ $salary->reimbursements }} </strong>  </h7> <br>
                                                            <h7>  <strong>  {{ $salary->transport_allowance }} </strong>  </h7> <br>
                                                            <h5> <strong>  GH&#8373; {{ number_format($salary->gross_salary, 2) }} </strong>  </h5> 
                                                        </address>
                                                    </div>


                                                    </div>
                                                </div>
                                                @if($salary->employee->ssnit_button == 'on')
                                                <div class="col">
                                                <h5 class="text-danger text-end"> <strong> PENSIONS </strong>  </h5>
                                                    <address>
                                                        <h7> TIER 1 : <strong> {{ number_format($salary->ssnit_tier1_0_5, 2) }}  </strong>  </h7> <br>
                                                        <h7> TIER 2   : <strong>  {{ number_format($salary->ssnit_tier2_5, 2) }}  </strong> </h7> <br>
                                                        <h5> SUMMARY  : <strong> {{ number_format($salary->ssnit_tier1_0_5 + $salary->ssnit_tier2_5, 2) }}  </strong> </h5>  
                                                    </address>
                                                </div>
                                                @endif
                                            </div>
                                            @endif
        

                                            <hr style="height: 5px; background-color : black; margin-top: -20px;"/>
                                            <div class="row mb-3">
                                                <div class="col-12 col-sm-12 col-md-12">
                                                    <h5 class="text-danger"> <strong> DEDUCTIONS  </strong>  </h5>
                                                <div class="row"> 

                                                <div class="col">
                                                    <address>
                                                        <h7> SSNIT 5% DEDUCTED  </h7> <br> 
                                                        <h7> TAX  </h7> <br> 
                                                        <h7> WELFARE  </h7> <br> 
                                                        <h7 > MAINTENANCE </h7>  <br> 
                                                        <h7 >ABSENT </h7>  <br>
                                                        <h7> AMOUNT DEDUCTED COS OF START DATE </h7>   <br>
                                                        <h7> BOOT   </h7> <br>
                                                        <h7> IOU    </h7> <br>
                                                        <h7> HOSTEL   </h7> <br>
                                                        <h7> INSURANCE  </h7> <br>
                                                        <h7> OTHER   </h7> <br>
                                                        <h7> REPRIMAND  </h7> <br>
                                                        <h7> RAINCOAT </h7> <br>
                                                        <h7> MEAL   </h7> <br>
                                                        <h7> LOAN  </h7> <br>
                                                        <h7> WALK IN  </h7> <br>
                                                        <h7> TOTAL DEDUCTIONS </h7> <br>
                                                        
                                                        <h5><strong> NET SALARY  </strong>  </h5> 
                                                    </address>

                                                </div>

                                                <div class="col text-end">
                                                    <address>
                                                        <h7> <strong>    {{ $salary->ssnit_tier2_5  }} </strong>  </h7> <br> 
                                                        <h7> <strong>    {{ $salary->tax  }} </strong>  </h7> <br> 
                                                        <h7> <strong>    {{ $salary->welfare  }} </strong>  </h7> <br> 
                                                        <h7 > <strong>   {{ $salary->maintenance  }} </strong> </h7>  <br> 
                                                        <h7 > <strong>   {{ $salary->absent }} </strong> </h7>  <br>
                                                        <h7> <strong>  {{ $salary->amnt_ded_cof_start_date }} </strong> </h7>   <br>
                                                        <h7> <strong>   {{ $salary->boot }} </strong>  </h7> <br>
                                                        <h7> <strong>   {{ $salary->iou }} </strong>  </h7> <br>
                                                        <h7>  <strong>   {{ $salary->hostel }} </strong>  </h7> <br>
                                                        <h7> <strong>   {{ $salary->insurance }} </strong>  </h7> <br>
                                                        <h7> <strong>   {{ $salary->other_deductions }} </strong>  </h7> <br>
                                                        <h7> <strong>   {{ $salary->reprimand }} </strong>  </h7> <br>
                                                        <h7> <strong>   {{ $salary->raincoat }} </strong>  </h7> <br>
                                                        <h7> <strong>   {{ $salary->meal }} </strong>  </h7> <br>
                                                        <h7>  <strong>   {{ $salary->loan }} </strong>  </h7> <br>
                                                        <h7>  <strong>   {{ $salary->walkin }} </strong>  </h7> <br>
                                                        <h7> <strong>  GH&#8373; {{ number_format($salary->total_deductions, 2) }} </strong>  </h7> <br>
                                                        
                                                        <h5> <strong>  GH&#8373; {{ number_format($salary->net_salary, 2) }} </strong>  </h5> 
                                                    </address>

                                                </div>
                                                </div>
                                                </div>
                                            </div>

                                            <hr style="height: 5px; background-color : black; margin-top: -20px;"/>
                                            <div class="row mb-3">
                                            <div class="col-12 col-sm-12 col-md-12">
                                                    <h5 class="text-danger"> <strong> EMPLOYER CONTRIBUTION  </strong>  </h5>
                                                <div class="row"> 
                                                <div class="col">
                                                    <address>
                                                        <h5> SOCIAL SECURITY TIER 1  </h5> <br> 
                                                    </address>
                                                </div>

                                                <div class="col text-end">
                                                    <address>
                                                        <h5> <strong>   GH&#8373; {{ number_format($salary->ssnit_tobe_paid13_5, 2)  }} </strong>  </h5> <br> 
                                                    </address>
                                                </div>

                                                </div>
                                                </div>
                                            </div> 

                                            <hr style="height: 5px; background-color : black ; margin-top: -40px;"/>
                                            <div class="row mb-3">
                                                <div class="col-12 col-sm-12 col-md-12">
                                                <div class="row"> 
                                                <div class="col">
                                                    <address>
                                                        <h5> TOTAL  </h5> <br> 
                                                    </address>
                                                </div>

                                                <div class="col text-end">
                                                    <address>
                                                        <h5> <strong>   GH&#8373; {{ number_format($salary->ssnit_tobe_paid13_5, 2)  }} </strong>  </h5> <br> 
                                                    </address>
                                                </div>

                                                </div>
                                                </div>
                                            </div> 


                                        </div>
                                </section>
                            </div>
                        </div>
                    </div>

                    <div class="content-backdrop fade"></div>

                    <!-- Content wrapper -->

                    <!-- / Layout page -->
                </div>
            <!-- Overlay -->
            <div class="layout-overlay layout-menu-toggle"></div>
        </div>
        <!-- / Layout wrapper -->
        <div class="buy-now">
            <button id="print"
                class="btn btn-danger btn-buy-now"> <i class="icon-base bx bxs-printer"></i> Print Payslip</button>
        </div>

        <script src="{{asset('vendor/libs/jquery/jquery.js')}}"></script>

        <script src="{{asset('vendor/libs/popper/popper.js')}}"></script>
        <script src="{{asset('vendor/js/bootstrap.js')}}"></script>

        <script src="{{asset('vendor/libs/perfect-scrollbar/perfect-scrollbar.js')}}"></script>

        <script src="{{asset('vendor/js/menu.js')}}"></script>
        <!-- Main JS -->
        <script src="{{asset('js/main.js')}}"></script>

        <script>
            $(document).ready(function() {
                $('#print').on('click', function() {
                    $.ajax({
                        url: '/printPayslip/{{$salary->id}}', // The route to your dedicated print view
                        method: 'GET',
                        success: function(response) {
                            var printWindow = window.open('url', '_parent');
                            var originalContents = $('body').html();
                            printWindow.document.write(response);
                            printWindow.print();
                            $('body').html(originalContents);
                        },
                        error: function(xhr, status, error) {
                            console.error("Error fetching print content:", error);
                        }
                    });
                });
            });
        </script>

</body>

</html>
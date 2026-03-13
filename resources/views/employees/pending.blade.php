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
          <li class="menu-item active">
              <a href="{{url('employees')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">List</div>
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
                <li class="menu-item active">
                    <a href="{{url('employeesPending')}}" class="menu-link">
                    <div class="text-truncate" data-i18n="SList">Pending</div>
                    </a>
                </li>
            @if(Auth::user()->hasRole(['Manager', 'Invoice']))
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
            @endif

            @if(Auth::user()->hasRole(['Invoice', 'Finance Manager', 'Manager']))
            <li class="menu-header small text-uppercase"><span class="menu-header-text">PAYROLL</span></li>
            <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                    <div class="text-truncate" data-i18n="Payroll">Payroll</div>
                    </a>
                    <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{ url('salaries') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bxs-user-account"></i>
                        <div class="text-truncate" data-i18n="Employees">Add to Salaries</div>
                        </a>
                    </li>
                    @if(Auth::user()->hasPermission('Accounts'))
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
                    <a href="{{ url('salariesBulkCash') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bxs-group"></i>
                    <div class="text-truncate" data-i18n="Transaction">Bulk Cash Salaries</div>
                    </a>
                </li>
                
                    <li class="menu-item">
                        <a href="{{ url('salariesInvPayroll') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-git-compare"></i>
                        <div class="text-truncate" data-i18n="InvtoPayroll">Invoice to Payroll</div>
                        </a>
                    </li>
                    @endif

                    </ul>
                </li>
            @endif
        </ul>
    </aside>
  <!-- / Menu -->
  @endsection


  @section('content')

  <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bxs-user-account"></i> Pending Employees </h3>
            </div>
        </div><br>



        @if(Auth::user()->hasRole(['Invoice','Manager', 'Finance Manager']))
        <div class="row mb-4">
            <div class="col-lg-12 col-md-6 mb-4 mb-md-0">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                            <p class="mb-1"><strong> PENDING EMPLOYEES </strong> </p>
                            <h4 class="card-title mb-3 text-white"><strong> {{ $pendingEmployees->count() }}  </strong> </h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> ACCRA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ $employeeAccra->count() }}  </strong> </h4>

                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> BOTWE </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ $employeeBotwe->count() }} </strong> </h4>

                    </div>
                </div>
            </div>


            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> SHAIHILLS </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ $employeeShyhills }} </strong> </h4>
                    </div>
                </div>
            </div>
            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> TEMA </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ $employeeTema }} </strong> </h4>
                    </div>
                </div>
            </div>



            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1">TAKORADI</p>
                        <h4 class="card-title mb-3 text-white"> {{ $employeeTakoradi->count() }} </h4>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"> <strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3 text-white"> {{ $employeeKoforidua->count() }} </h4>
                    </div>
                </div>
            </div>


            <div class="col-lg-2 m-3">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3 text-white"> {{ $employeeKumasi->count() }} </h4>
                    </div>
                </div>
            </div>

        </div> <br>
        @elseif(Auth::user()->field?->name == 'Accra')
      
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div class="card h-100 bg-dark text-white">
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
                        <h4 class="card-title mb-3 text-white"><strong> {{ $employeeAccra->count() }}  </strong> </h4>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(Auth::user()->field?->name == 'Botwe')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div class="card h-100">
                    <div class="card-body bg-dark text-white">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> BOTWE </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ $employeeBotwe->count() }} </strong> </h4>
                    </div>
                </div>
            </div>
        </div>
        @endif

          @if(Auth::user()->field?->name == 'Tema')
        <div class="row">
            <div class="col-xxl-6 mb-6">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> SHAIHILLS </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ $employeeShyhills }} </strong> </h4>
                    </div>
                </div>
            </div>

            <div class="col-xxl-6 mb-6 order-0">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> TEMA </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ $employeeTema }} </strong> </h4>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Takoradi')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1">TAKORADI</p>
                        <h4 class="card-title mb-3 text-white"> {{ $employeeTakoradi->count() }} </h4>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Koforidua')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"> <strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3 text-white"> {{ $employeeKoforidua->count() }} </h4>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Kumasi')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3 text-white"> {{ $employeeKumasi->count() }} </h4>
                    </div>
                </div>
            </div>
        </div>
        @endif
        <br><br>



        <div class="card-header  ml-2  d-none d-lg-block">
            @include('flash-messages')
        </div>

        <div class="row">
        <form action="/employeesAproval" method="POST">
            @csrf
            <div class="col">
                         @if(Auth::user()->hasNotRole(['Admin Assistant']))
                        <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />

                        <div class="form-check form-check-inline">                            
                            <button class="btn btn-success" type="submit" onclick="return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('Approve') }}</button>
                        </div>
                        @endif
                <div class="card"> 
                    <div class="card-body"> 
                    <div class="table-responsive text-normal-dark"> 
                    <table id="myTable" class="display">
                        <thead>
                            <tr>
                                <th> </th>
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
                                <th> Bank </th>
                                <th>Account No.</th>
                                <th>Status</th>
                                <th>Status Date</th>
                                <th>TAX</th>
                                <th>TIN</th>
                                <th>SSNIT</th>
                                <th>SSNIT #</th>
                                <th>Basic</th>
                                <th>Allowance</th>
                                <th> Created </th>
                                <th> Period </th>
                                <th> Updated</th>
                                <th> Period </th>
                                <th>Staff</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                         @if(Auth::user()->hasRole(['Invoice','Manager', 'Finance Manager']))
                            @foreach ($pendingEmployees as $key => $employee )
                            <tr>
                                <td> <input class="checkBoxes form-check-input" type="checkbox" name="employees[]" value="{{ $employee->id }}" /></td>
                                <td> {{ $key + 1 }} </td>
                                <td> FWSS {{ $employee->id }}  </td>
                                <td>{{$employee->name}}  </td>
                                <td>{{ $employee->gender }}  </td>
                                <td>{{ $employee->phone_number }}  </td>
                                <td>{{ $employee->date_of_joining?->format('F, Y') }} </td>
                                <td> {{ $employee->department?->name }} </td>
                                <td> {{ $employee->role?->name }}  </td>
                                <td> {{ $employee->field?->name }}   </td>
                                <td>{{ $employee->client?->name }} {{ $employee->client?->business_name }} </td>
                                <td> {{ $employee->location }} </td>
                                <td> {{ $employee->payment_type }}  </td>
                                <td> {{  $employee->paymentInfo?->bank?->name  }} </td>
                                <td> {{  $employee->paymentInfo?->acc_number  }} </td>
                                @if($employee->status == 'Active')
                                <td><span class="badge bg-label-success">{{$employee->status}}</span></td>
                                @else
                                <td><span class="badge bg-label-danger">{{$employee->status}}</span></td>
                                @endif
                                <td> {{ $employee->status_date?->format('F, Y') }} </td>
                                 @if($employee->tax_button == 'on')
                                <td> <span class="badge bg-label-dark"> {{  $employee->tax_button }} </span> </td>
                                @else
                                <td> <span class="badge bg-label-danger"> OFF </span> </td>
                                @endif
                                <td> {{  $employee->paymentInfo?->tin_number  }} </td>

                                @if($employee->ssnit_button == 'on')
                                <td> <span class="badge bg-label-dark"> {{  $employee->ssnit_button }} </span> </td>
                                @else
                                <td> <span class="badge bg-label-danger"> OFF </span> </td>
                                @endif
                                 <td> {{  $employee->paymentInfo?->ssnit_number  }} </td>
                                <td> {{$employee->basic_salary}} </td>
                                <td> {{$employee->allowances}} </td>
                                <td> {{ $employee->created_at?->format('F, Y') }} </td>
                                <td> {{ $employee->created_at?->diffForHumans() }} </td>
                                <td>{{ $employee->updated_at?->format('F, Y') }} </td>
                                <td>{{ $employee->updated_at?->diffForHumans() }} </td>
                                <td>{{  $employee->user1?->name }}</td>
                               <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{url('employees', $employee->id)}}"><i class="icon-base bx bxs-bullseye"></i> view</a>
                                        <a class="dropdown-item" href="{{url('employees', $employee->id)}}/edit"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>
                                      @if(Auth::user()->hasNotRole(['Manager']))
                                        <hr>
                                        <a class="dropdown-item" href="{{url('employeesSalary', $employee->id)}}"><i class="icon-base bx bx-money-withdraw"></i> Salaries</a>
                                       @endif
                                        <!-- <hr>
                                        @if ($employee->status == 'Active')
                                            <a class="dropdown-item" href="{{url('terminateEmployee', $employee->id )}}"><i class="icon-base bx bx-user-x me-1" onclick="return confirm('Kindly Confirm?')"></i> Terminate</a>

                                       
                                            @else
                                        <a class="dropdown-item" href="{{url('employeeReinstate', $employee->id )}}" onclick="return confirm('Kindly Confirm?')"><i class="icon-base bx bx-edit-alt me-1"></i>Re-Instate </a>
                                            @endif -->

                                        <!-- <form action="employees/{{$employee->id}}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button class="dropdown-item" type="submit"><i class="icon-base bx bx-trash me-1"></i>Delete</button>
                                        </form> -->
                                    </div>
                                </div>
                            </td>
                            </tr>
                            @endforeach
                        @elseif(Auth::user()->field?->name == 'Accra')
                            @foreach ($employeeAccra as $key => $employee )
                            <tr>
                                <td></td>
                                <td> {{ $key + 1 }} </td>
                                <td> FWSS {{ $employee->id }}  </td>
                                <td>{{$employee->name}}  </td>
                                <td>{{ $employee->gender }}  </td>
                                <td>{{ $employee->phone_number }}  </td>
                                <td>{{ $employee->date_of_joining?->format('F, Y') }} </td>
                                <td> {{ $employee->department?->name }} </td>
                                <td> {{ $employee->role?->name }}  </td>
                                <td> {{ $employee->field?->name }}   </td>
                                <td>{{ $employee->client?->name }} {{ $employee->client?->business_name }} </td>
                                <td> {{ $employee->location }} </td>
                                <td> {{ $employee->payment_type }}  </td>
                                <td> {{  $employee->paymentInfo?->bank?->name  }} </td>
                                <td> {{  $employee->paymentInfo?->acc_number  }} </td>
                                @if($employee->status == 'Active')
                                <td><span class="badge bg-label-success">{{$employee->status}}</span></td>
                                @else
                                <td><span class="badge bg-label-danger">{{$employee->status}}</span></td>
                                @endif
                                <td> {{ $employee->status_date?->format('F, Y') }} </td>
                                 @if($employee->tax_button == 'on')
                                <td> <span class="badge bg-label-dark"> {{  $employee->tax_button }} </span> </td>
                                @else
                                <td> <span class="badge bg-label-danger"> OFF </span> </td>
                                @endif
                                <td> {{  $employee->paymentInfo?->tin_number  }} </td>

                                @if($employee->ssnit_button == 'on')
                                <td> <span class="badge bg-label-dark"> {{  $employee->ssnit_button }} </span> </td>
                                @else
                                <td> <span class="badge bg-label-danger"> OFF </span> </td>
                                @endif
                                 <td> {{  $employee->paymentInfo?->ssnit_number  }} </td>
                                <td> {{$employee->basic_salary}} </td>
                                <td> {{$employee->allowances}} </td>
                                <td> {{ $employee->created_at?->format('F, Y') }} </td>
                                <td> {{ $employee->created_at?->diffForHumans() }} </td>
                                <td>{{ $employee->updated_at?->format('F, Y') }} </td>
                                <td>{{ $employee->updated_at?->diffForHumans() }} </td>
                                <td>{{  $employee->user1?->name }}</td>
                               <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{url('employees', $employee->id)}}"><i class="icon-base bx bxs-bullseye"></i> view</a>
                                        <a class="dropdown-item" href="{{url('employees', $employee->id)}}/edit"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>
                                      @if(Auth::user()->hasNotRole(['Manager']))
                                        <hr>
                                        <a class="dropdown-item" href="{{url('employeesSalary', $employee->id)}}"><i class="icon-base bx bx-money-withdraw"></i> Salaries</a>
                                       @endif
                                    </div>
                                </div>
                            </td>
                            </tr>
                            @endforeach
                       @endif


                       @if(Auth::user()->field?->name == 'Botwe')
                            @foreach ($employeeBotwe as $key => $employee )
                            <tr>
                                <td></td>
                                <td> {{ $key + 1 }} </td>
                                <td> FWSS {{ $employee->id }}  </td>
                                <td>{{$employee->name}}  </td>
                                <td>{{ $employee->gender }}  </td>
                                <td>{{ $employee->phone_number }}  </td>
                                <td>{{ $employee->date_of_joining?->format('F, Y') }} </td>
                                <td> {{ $employee->department?->name }} </td>
                                <td> {{ $employee->role?->name }}  </td>
                                <td> {{ $employee->field?->name }}   </td>
                                <td>{{ $employee->client?->name }} {{ $employee->client?->business_name }} </td>
                                <td> {{ $employee->location }} </td>
                                <td> {{ $employee->payment_type }}  </td>
                                <td> {{  $employee->paymentInfo?->bank?->name  }} </td>
                                <td> {{  $employee->paymentInfo?->acc_number  }} </td>
                                @if($employee->status == 'Active')
                                <td><span class="badge bg-label-success">{{$employee->status}}</span></td>
                                @else
                                <td><span class="badge bg-label-danger">{{$employee->status}}</span></td>
                                @endif
                                <td> {{ $employee->status_date?->format('F, Y') }} </td>
                                 @if($employee->tax_button == 'on')
                                <td> <span class="badge bg-label-dark"> {{  $employee->tax_button }} </span> </td>
                                @else
                                <td> <span class="badge bg-label-danger"> OFF </span> </td>
                                @endif
                                <td> {{  $employee->paymentInfo?->tin_number  }} </td>

                                @if($employee->ssnit_button == 'on')
                                <td> <span class="badge bg-label-dark"> {{  $employee->ssnit_button }} </span> </td>
                                @else
                                <td> <span class="badge bg-label-danger"> OFF </span> </td>
                                @endif
                                 <td> {{  $employee->paymentInfo?->ssnit_number  }} </td>
                                <td> {{$employee->basic_salary}} </td>
                                <td> {{$employee->allowances}} </td>
                                <td> {{ $employee->created_at?->format('F, Y') }} </td>
                                <td> {{ $employee->created_at?->diffForHumans() }} </td>
                                <td>{{ $employee->updated_at?->format('F, Y') }} </td>
                                <td>{{ $employee->updated_at?->diffForHumans() }} </td>
                                <td>{{  $employee->user1?->name }}</td>
                               <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{url('employees', $employee->id)}}"><i class="icon-base bx bxs-bullseye"></i> view</a>
                                        <a class="dropdown-item" href="{{url('employees', $employee->id)}}/edit"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>
                                      @if(Auth::user()->hasNotRole(['Manager']))
                                        <hr>
                                        <a class="dropdown-item" href="{{url('employeesSalary', $employee->id)}}"><i class="icon-base bx bx-money-withdraw"></i> Salaries</a>
                                       @endif
                                    </div>
                                </div>
                            </td>
                            </tr>
                            @endforeach 
                       @endif


                       @if(Auth::user()->field?->name == 'Tema')
                        @foreach ($Tema as $key => $employee )
                            <tr>
                                <td></td>
                                <td> {{ $key + 1 }} </td>
                                <td> FWSS {{ $employee->id }}  </td>
                                <td>{{$employee->name}}  </td>
                                <td>{{ $employee->gender }}  </td>
                                <td>{{ $employee->phone_number }}  </td>
                                <td>{{ $employee->date_of_joining?->format('F, Y') }} </td>
                                <td> {{ $employee->department?->name }} </td>
                                <td> {{ $employee->role?->name }}  </td>
                                <td> {{ $employee->field?->name }}   </td>
                                <td>{{ $employee->client?->name }} {{ $employee->client?->business_name }} </td>
                                <td> {{ $employee->location }} </td>
                                <td> {{ $employee->payment_type }}  </td>
                                <td> {{  $employee->paymentInfo?->bank?->name  }} </td>
                                <td> {{  $employee->paymentInfo?->acc_number  }} </td>
                                @if($employee->status == 'Active')
                                <td><span class="badge bg-label-success">{{$employee->status}}</span></td>
                                @else
                                <td><span class="badge bg-label-danger">{{$employee->status}}</span></td>
                                @endif
                                <td> {{ $employee->status_date?->format('F, Y') }} </td>
                                 @if($employee->tax_button == 'on')
                                <td> <span class="badge bg-label-dark"> {{  $employee->tax_button }} </span> </td>
                                @else
                                <td> <span class="badge bg-label-danger"> OFF </span> </td>
                                @endif
                                <td> {{  $employee->paymentInfo?->tin_number  }} </td>

                                @if($employee->ssnit_button == 'on')
                                <td> <span class="badge bg-label-dark"> {{  $employee->ssnit_button }} </span> </td>
                                @else
                                <td> <span class="badge bg-label-danger"> OFF </span> </td>
                                @endif
                                 <td> {{  $employee->paymentInfo?->ssnit_number  }} </td>
                                <td> {{$employee->basic_salary}} </td>
                                <td> {{$employee->allowances}} </td>
                                <td> {{ $employee->created_at?->format('F, Y') }} </td>
                                <td> {{ $employee->created_at?->diffForHumans() }} </td>
                                <td>{{ $employee->updated_at?->format('F, Y') }} </td>
                                <td>{{ $employee->updated_at?->diffForHumans() }} </td>
                                <td>{{  $employee->user1?->name }}</td>
                               <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{url('employees', $employee->id)}}"><i class="icon-base bx bxs-bullseye"></i> view</a>
                                        <a class="dropdown-item" href="{{url('employees', $employee->id)}}/edit"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>
                                      @if(Auth::user()->hasNotRole(['Manager']))
                                        <hr>
                                        <a class="dropdown-item" href="{{url('employeesSalary', $employee->id)}}"><i class="icon-base bx bx-money-withdraw"></i> Salaries</a>
                                       @endif
                                    </div>
                                </div>
                            </td>
                            </tr>
                            @endforeach 
                       @endif
                       
                       
                      @if(Auth::user()->field?->name == 'Takoradi')
                        @foreach ($employeeTakoradi as $key => $employee )
                            <tr>
                                <td></td>
                                <td> {{ $key + 1 }} </td>
                                <td> FWSS {{ $employee->id }}  </td>
                                <td>{{$employee->name}}  </td>
                                <td>{{ $employee->gender }}  </td>
                                <td>{{ $employee->phone_number }}  </td>
                                <td>{{ $employee->date_of_joining?->format('F, Y') }} </td>
                                <td> {{ $employee->department?->name }} </td>
                                <td> {{ $employee->role?->name }}  </td>
                                <td> {{ $employee->field?->name }}   </td>
                                <td>{{ $employee->client?->name }} {{ $employee->client?->business_name }} </td>
                                <td> {{ $employee->location }} </td>
                                <td> {{ $employee->payment_type }}  </td>
                                <td> {{  $employee->paymentInfo?->bank?->name  }} </td>
                                <td> {{  $employee->paymentInfo?->acc_number  }} </td>
                                @if($employee->status == 'Active')
                                <td><span class="badge bg-label-success">{{$employee->status}}</span></td>
                                @else
                                <td><span class="badge bg-label-danger">{{$employee->status}}</span></td>
                                @endif
                                <td> {{ $employee->status_date?->format('F, Y') }} </td>
                                 @if($employee->tax_button == 'on')
                                <td> <span class="badge bg-label-dark"> {{  $employee->tax_button }} </span> </td>
                                @else
                                <td> <span class="badge bg-label-danger"> OFF </span> </td>
                                @endif
                                <td> {{  $employee->paymentInfo?->tin_number  }} </td>

                                @if($employee->ssnit_button == 'on')
                                <td> <span class="badge bg-label-dark"> {{  $employee->ssnit_button }} </span> </td>
                                @else
                                <td> <span class="badge bg-label-danger"> OFF </span> </td>
                                @endif
                                 <td> {{  $employee->paymentInfo?->ssnit_number  }} </td>
                                <td> {{$employee->basic_salary}} </td>
                                <td> {{$employee->allowances}} </td>
                                <td> {{ $employee->created_at?->format('F, Y') }} </td>
                                <td> {{ $employee->created_at?->diffForHumans() }} </td>
                                <td>{{ $employee->updated_at?->format('F, Y') }} </td>
                                <td>{{ $employee->updated_at?->diffForHumans() }} </td>
                                <td>{{  $employee->user1?->name }}</td>
                               <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{url('employees', $employee->id)}}"><i class="icon-base bx bxs-bullseye"></i> view</a>
                                        <a class="dropdown-item" href="{{url('employees', $employee->id)}}/edit"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>
                                      @if(Auth::user()->hasNotRole(['Manager']))
                                        <hr>
                                        <a class="dropdown-item" href="{{url('employeesSalary', $employee->id)}}"><i class="icon-base bx bx-money-withdraw"></i> Salaries</a>
                                       @endif
                                    </div>
                                </div>
                            </td>
                            </tr>
                            @endforeach 
                       @endif



                      @if(Auth::user()->field?->name == 'Koforidua')
                        @foreach ($employeeKoforidua as $key => $employee )
                            <tr>
                                <td></td>
                                <td> {{ $key + 1 }} </td>
                                <td> FWSS {{ $employee->id }}  </td>
                                <td>{{$employee->name}}  </td>
                                <td>{{ $employee->gender }}  </td>
                                <td>{{ $employee->phone_number }}  </td>
                                <td>{{ $employee->date_of_joining?->format('F, Y') }} </td>
                                <td> {{ $employee->department?->name }} </td>
                                <td> {{ $employee->role?->name }}  </td>
                                <td> {{ $employee->field?->name }}   </td>
                                <td>{{ $employee->client?->name }} {{ $employee->client?->business_name }} </td>
                                <td> {{ $employee->location }} </td>
                                <td> {{ $employee->payment_type }}  </td>
                                <td> {{  $employee->paymentInfo?->bank?->name  }} </td>
                                <td> {{  $employee->paymentInfo?->acc_number  }} </td>
                                @if($employee->status == 'Active')
                                <td><span class="badge bg-label-success">{{$employee->status}}</span></td>
                                @else
                                <td><span class="badge bg-label-danger">{{$employee->status}}</span></td>
                                @endif
                                <td> {{ $employee->status_date?->format('F, Y') }} </td>
                                 @if($employee->tax_button == 'on')
                                <td> <span class="badge bg-label-dark"> {{  $employee->tax_button }} </span> </td>
                                @else
                                <td> <span class="badge bg-label-danger"> OFF </span> </td>
                                @endif
                                <td> {{  $employee->paymentInfo?->tin_number  }} </td>

                                @if($employee->ssnit_button == 'on')
                                <td> <span class="badge bg-label-dark"> {{  $employee->ssnit_button }} </span> </td>
                                @else
                                <td> <span class="badge bg-label-danger"> OFF </span> </td>
                                @endif
                                 <td> {{  $employee->paymentInfo?->ssnit_number  }} </td>
                                <td> {{$employee->basic_salary}} </td>
                                <td> {{$employee->allowances}} </td>
                                <td> {{ $employee->created_at?->format('F, Y') }} </td>
                                <td> {{ $employee->created_at?->diffForHumans() }} </td>
                                <td>{{ $employee->updated_at?->format('F, Y') }} </td>
                                <td>{{ $employee->updated_at?->diffForHumans() }} </td>
                                <td>{{  $employee->user1?->name }}</td>
                               <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{url('employees', $employee->id)}}"><i class="icon-base bx bxs-bullseye"></i> view</a>
                                        <a class="dropdown-item" href="{{url('employees', $employee->id)}}/edit"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>
                                      @if(Auth::user()->hasNotRole(['Manager']))
                                        <hr>
                                        <a class="dropdown-item" href="{{url('employeesSalary', $employee->id)}}"><i class="icon-base bx bx-money-withdraw"></i> Salaries</a>
                                       @endif
                                    </div>
                                </div>
                            </td>
                            </tr>
                            @endforeach 
                       @endif


                      @if(Auth::user()->field?->name == 'Kumasi')
                        @foreach ($employeeKumasi as $key => $employee )
                            <tr>
                                <td></td>
                                <td> {{ $key + 1 }} </td>
                                <td> FWSS {{ $employee->id }}  </td>
                                <td>{{$employee->name}}  </td>
                                <td>{{ $employee->gender }}  </td>
                                <td>{{ $employee->phone_number }}  </td>
                                <td>{{ $employee->date_of_joining?->format('F, Y') }} </td>
                                <td> {{ $employee->department?->name }} </td>
                                <td> {{ $employee->role?->name }}  </td>
                                <td> {{ $employee->field?->name }}   </td>
                                <td>{{ $employee->client?->name }} {{ $employee->client?->business_name }} </td>
                                <td> {{ $employee->location }} </td>
                                <td> {{ $employee->payment_type }}  </td>
                                <td> {{  $employee->paymentInfo?->bank?->name  }} </td>
                                <td> {{  $employee->paymentInfo?->acc_number  }} </td>
                                @if($employee->status == 'Active')
                                <td><span class="badge bg-label-success">{{$employee->status}}</span></td>
                                @else
                                <td><span class="badge bg-label-danger">{{$employee->status}}</span></td>
                                @endif
                                <td> {{ $employee->status_date?->format('F, Y') }} </td>
                                 @if($employee->tax_button == 'on')
                                <td> <span class="badge bg-label-dark"> {{  $employee->tax_button }} </span> </td>
                                @else
                                <td> <span class="badge bg-label-danger"> OFF </span> </td>
                                @endif
                                <td> {{  $employee->paymentInfo?->tin_number  }} </td>

                                @if($employee->ssnit_button == 'on')
                                <td> <span class="badge bg-label-dark"> {{  $employee->ssnit_button }} </span> </td>
                                @else
                                <td> <span class="badge bg-label-danger"> OFF </span> </td>
                                @endif
                                 <td> {{  $employee->paymentInfo?->ssnit_number  }} </td>
                                <td> {{$employee->basic_salary}} </td>
                                <td> {{$employee->allowances}} </td>
                                <td> {{ $employee->created_at?->format('F, Y') }} </td>
                                <td> {{ $employee->created_at?->diffForHumans() }} </td>
                                <td>{{ $employee->updated_at?->format('F, Y') }} </td>
                                <td>{{ $employee->updated_at?->diffForHumans() }} </td>
                                <td>{{  $employee->user1?->name }}</td>
                               <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{url('employees', $employee->id)}}"><i class="icon-base bx bxs-bullseye"></i> view</a>
                                        <a class="dropdown-item" href="{{url('employees', $employee->id)}}/edit"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>
                                      @if(Auth::user()->hasNotRole(['Manager']))
                                        <hr>
                                        <a class="dropdown-item" href="{{url('employeesSalary', $employee->id)}}"><i class="icon-base bx bx-money-withdraw"></i> Salaries</a>
                                       @endif
                                    </div>
                                </div>
                            </td>
                            </tr>
                            @endforeach 
                       @endif

                       
                       
                        </tbody>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.html5.min.js"></script>

    <script src="https://cdn.datatables.net/columncontrol/1.1.1/js/dataTables.columnControl.min.js"></script>

    <script>
        new DataTable('#myTable', {
            responsive: true,
            columnControl: [ ['search'] ],
            layout: {
                topStart: {
                    buttons: [ 
                    {
                        extend: 'pageLength',
                        text: 'Show',
                        className: 'btn btn-secondary',
                        Options: [10, 25, 50, 100, 250, 500, 1000, 2000], 
                    },
                        {
                            extend: 'excelHtml5',
                            title: 'Employees',
                            className: 'btn btn-secondary',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                    ]
                }
            },
        });
    </script>

    <script>
        $(document).ready(function() {
            $('#options').change(function() {
                $('.checkBoxes').prop('checked', function(i, val) {
                    return !val;
                });
            });
        });
    </script>

    @endsection
</x-hr-dashboard>
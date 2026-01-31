<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.5/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.5/css/buttons.dataTables.css">   
     <link rel="stylesheet" href="{{asset('vendor/css/datatables.css')}}" /> 
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

            @endif

            <li class="menu-header small text-uppercase"><span class="menu-header-text">PAYROLL</span></li>
            <li class="menu-item active open">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                    <div class="text-truncate" data-i18n="Payroll">Payroll</div>
                    </a>
                    <ul class="menu-sub">
                    @if(Auth::user()->hasPermission('HR') || Auth::user()->hasRole(['Invoice']))
                    <li class="menu-item ">
                        <a href="{{ url('salaries') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bxs-user-account"></i>
                        <div class="text-truncate" data-i18n="Employees">Add to Salaries</div>
                        </a>
                    </li>
                    @endif

                    <li class="menu-item ">
                        <a href="{{ url('salaries/create') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                        <div class="text-truncate" data-i18n="Salaries">Salaries</div>
                        </a>
                    </li>

                    <li class="menu-item active">
                        <a href="{{ url('salariesTransaction') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-transfer-alt"></i>
                        <div class="text-truncate" data-i18n="Transaction">Transactions</div>
                        </a>
                    </li>

                    <li class="menu-item">
                        <a href="{{ url('salariesInvPayroll') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-git-compare"></i>
                        <div class="text-truncate" data-i18n="InvtoPayroll">Invoice to Payroll</div>
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
                <h3 class="card-header"> <i class="icon-base bx bx-transfer-alt"></i> Salaries Transaction   @if (isset($date)) <strong> / For Month: {{  \Carbon\Carbon::parse($date)->format('F Y') }}</strong> @endif </h3>

            </div>
        </div><br>

         @if(Auth::user()->hasRole(['Invoice','Manager', 'Finance Manager' ]))
        <div class="row">
            <div class="col-lg-3">
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
                        <p class="mb-1"><strong> BANKS </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5;  {{ number_format($groupedBankSalaries->sum('paid'), 2) }} </strong> </h4> <br>
                        <small class="fw-medium">TOTAL DEDUCTIONS : </small> <br>
                        <small class="fw-medium"> GH&#x20B5;  {{ number_format($groupedBankSalaries->sum('deductions'), 2) }}  </small> <br>
                        <small class="fw-medium"> EMPLOYEES : {{ $groupedBankSalaries->sum('total_employees') }}  </small>

                    </div>
                </div>
            </div>

            <div class="col-lg-3">
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
                        <p class="mb-1"><strong> CASH </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($groupedCashkSalaries->sum('paid'), 2) }} </strong> </h4> <br>
                        <small class="fw-medium">TOTAL DEDUCTIONS : </small> <br>
                        <small class="fw-medium"> GH&#x20B5;  {{ number_format($groupedCashkSalaries->sum('deductions'), 2) }}  </small> <br>
                        <small class="fw-medium"> EMPLOYEES : {{ $groupedCashkSalaries->sum('total_employees') }}  </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-3">
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
                        <p class="mb-1"><strong> TAX </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($salariesTaxes->sum('tax'), 2) }} </strong> </h4> <br>
                        <small class="fw-medium"> TOTAL :  GH&#x20B5; {{ number_format($salariesTaxes->sum('paid'), 2) }} </small> <br>
                        <small class="fw-medium"> EMPLOYEES : {{ $salariesTaxes->sum('total_employees') }} </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
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
                        <p class="mb-1"><strong> PENSIONS </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($salariesPensions->sum('cont13_5'), 2) }} </strong> </h4> <br>
                        <small class="fw-medium"> TOTAL :  GH&#x20B5; {{ number_format($salariesPensions->sum('paid') , 2) }} </small> <br>
                        <small class="fw-medium"> EMPLOYEES : {{ $salariesPensions->sum('total_employees') }} </small>
                    </div>
                </div>
            </div>


        </div> <br> <br>
                     <div class="card-header  ml-2  d-none d-lg-block">
                  @include('flash-messages')
        </div> <br>
        @endif

        <!-- Table -->  
        <hr> <br> <br>
        <div class="nav-align-top">
            <ul class="nav nav-pills mb-4 nav-fill" role="tablist">

                @if(Auth::user()->hasRole(['Finance Manager', 'Invoice']) )
                <li class="nav-item mb-1 mb-sm-0">
                    <button
                        type="button"
                        class="nav-link active"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-accra"
                        aria-controls="navs-pills-justified-accra"
                        aria-selected="true">
                        <span class="d-none d-sm-inline-flex align-items-center">
                            <i class="icon-base bx bx-home icon-sm me-1_5"></i>Banks
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $groupedBankSalaries->count() }} </span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>

                <li class="nav-item">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-botwe"
                        aria-controls="navs-pills-justified-botwe"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>Cash
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $groupedCashkSalaries->count() }} </span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>

                <li class="nav-item mb-1 mb-sm-0">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-shyhills"
                        aria-controls="navs-pills-justified-shyhills"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>Tax
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesTaxes->count() }} </span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>

                <li class="nav-item mb-1 mb-sm-0">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-tema"
                        aria-controls="navs-pills-justified-tema"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>Pensions
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesPensions->count() }}</span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>

                <li class="nav-item mb-1 mb-sm-0">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-master"
                        aria-controls="navs-pills-justified-master"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i> Master
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesMaster->count() }} </span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>

                @endif

            </ul>


            <div class="tab-content">
                <div class="tab-pane fade show active" id="navs-pills-justified-accra" role="tabpanel">
                    <div class="table-responsive">
                        <table id="myTableiAccra" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Bank Name</th>
                                    <th>Gross Salary</th>
                                    <th>Total Deductions</th>
                                    <th>Cost to Company</th>
                                    <th>Employees</th>
                                    <th> View Employees</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                @foreach($groupedBankSalaries as $key => $banks)
                                <tr>
                                    <td> {{ $key + 1 }} </td>
                                    <td> {{ $banks->bank->name }} </td>
                                    <td> GH&#x20B5; {{ number_format($banks->gross, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($banks->deductions, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($banks->paid, 2) }} </td>
                                    <td> {{ $banks->total_employees }} </td>
                                    <td> 
                                        <a href="/salariesBankMonth/{{ $banks->bank->id }}/{{ $date }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a>    
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>


                <div class="tab-pane fade" id="navs-pills-justified-botwe" role="tabpanel">

                    <div class="table-responsive text-nowrap">
                        <table id="myTableiBotwe" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Field</th>
                                    <th>Gross Salary</th>
                                    <th>Total Deductions</th>
                                    <th>Cost to Company</th>
                                    <th>Employees</th>
                                    <th> View Employees</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                @foreach($groupedCashkSalaries as $key => $cash)
                                <tr>
                                    <td> {{ $key + 1 }} </td>
                                    <td>{{ $cash->field?->name }}</td>
                                    <td> GH&#x20B5; {{ number_format($cash->gross, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($cash->deductions, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($cash->paid, 2) }} </td>
                                    <td> {{ $cash->total_employees }} </td>
                                    <td> 
                                        <a href="/salariesCashMonth/{{ $cash->field?->id }}/{{ $date }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a>    
                                    </td>
                                   
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="navs-pills-justified-shyhills" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                        <table id="myTableiShyhills" class="display">
                            <thead>
                                <tr>
                                   
                                    <th>#</th>
                                    <th>Field</th>
                                    <th>Total Paid</th>
                                    <th>Total Tax</th>
                                    <th>Employees</th>
                                    <th>View Employees</th>
                                   
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                               @foreach ($salariesTaxes as $key => $taxes)
                                   <tr>
                                    <td> {{ $key + 1 }} </td>
                                    <td>{{ $taxes->field?->name }}</td>
                                    <td> GH&#x20B5; {{ number_format($taxes->paid, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($taxes->tax, 2) }} </td>
                                    <td> {{ $taxes->total_employees }} </td>
                                    <td> 
                                        <a href="/salariesTaxMonth/{{ $taxes->field?->id }}/{{ $date }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a>    
                                    </td>   
                                </tr>
                                 @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>

                <div class="tab-pane fade" id="navs-pills-justified-tema" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                        <table id="myTableiTema" class="display">
                            <thead>
                                <tr>
                                   
                                    <th>#</th>
                                    <th>Field</th>
                                    <th>Tier 1</th>
                                    <th>Tier 2</th>
                                    <th>Cont 13</th>
                                    <th>Cont 13.5</th>
                                    <th>Employees</th>
                                    <th> View Employees</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                              @foreach ($salariesPensions as $key => $pensions)
                                   <tr>
                                    <td> {{ $key + 1 }} </td>
                                    <td>{{ $pensions->field?->name }}</td>
                                    <td> GH&#x20B5; {{ number_format($pensions->tier1, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($pensions->tier2, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($pensions->cont13, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($pensions->cont13_5, 2) }} </td>
                                    <td> {{ $pensions->total_employees }} </td>
                                    <td> 
                                        <a href="/salariesPensionMonth/{{ $pensions->field?->id }}/{{ $date }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a>    
                                    </td>   
                                </tr>
                                    @endforeach 
                            </tbody>
                        </table>
                    </div>

                </div>


                 <div class="tab-pane fade" id="navs-pills-justified-master" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                        <table id="myTableimaster" class="display">
                            <thead>
                                <tr>
                                                <th> View </th>
                                                <th> id</th>
                                                <th> Salary Month </th>
                                                <th> employee_id </th>
                                                <th> Name</th>
                                                <th> Department </th>
                                                <th> Role</th>
                                                <th> Field </th>
                                                <th> Emp. Type </th>
                                                <th> Client </th>
                                                <th> Location </th>
                                                <th> SSNIT No.</th>
                                                <th> TIN No.</th>
                                                <th> Payment Type</th>
                                                <th> Bank Name </th>
                                                <th> Branch </th>
                                                <th> Account No.</th>
                                                <th> Basic Salary</th>
                                                <th> Allowances</th>
                                                <th> airtime_allowance</th>
                                                <th> overtime</th>
                                                <th> reimbursements </th>
                                                <th> transport_allowance</th>
                                                <th> ssnit_tier2_5</th>
                                                <th> ssnit_tier2_5d</th>
                                                <th> tax</th>
                                                <th> ssnit_tier1_0_5</th>
                                                <th> welfare </th>
                                                <th> maintenance</th>
                                                <th> absent</th>
                                                <th> boot</th>
                                                <th> iou</th>
                                                <th> hostel</th>
                                                <th> insurance</th>
                                                <th> reprimand</th>
                                                <th> scouter </th>
                                                <th> raincoat </th>
                                                <th> meal</th>
                                                <th> loan</th>
                                                <th> walkin</th>
                                                <th> amnt_ded_cof_start_date</th>
                                                <th> other_deductions</th>
                                                <th> gross_salary </th>
                                                <th> total_deductions</th>
                                                <th> net_salary </th>
                                                <th> ssnit_comp_cont_13 </th>
                                                <th> ssnit_tobe_paid13_5</th>
                                                <th> cost_to_company </th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                              @foreach ($salariesMaster as $key => $salary)

                                   <tr>
                                                <td><a class="dropdown-item" href="/salaries/{{$salary->id}}"><i class="icon-base bx bxs-bullseye"></i></a> </td>
                                                <td> {{ $salary->id }} </td>
                                                <td> {{$salary->salary_month?->format('F, Y')}} </td>
                                                <td> {{ $salary?->employee_id }} </td>
                                                <td> {{ strtoupper($salary->employee?->name) }} </td>
                                                <td> {{ $salary->department?->name }} </td>
                                                <td> {{ $salary->role?->name }} </td>
                                                <td> {{ $salary->field?->name }} </td>
                                                <td> {{ $salary->employee?->worker_type }} </td>
                                                <td> {{ $salary->client?->name }} {{ $salary->client?->business_name }}</td>
                                                <td> {{ $salary->location }} </td>
                                                <td> {{$salary->paymentInfo?->ssnit_number}}</td>
                                                <td> {{$salary->paymentInfo?->tin_number}}</td>
                                                <td> {{$salary->payment_type}}</td>
                                                <td> {{$salary->bank?->name}}</td>
                                                <td> {{$salary->branch}}</td>
                                                <td> {{$salary->account_number}}</td>
                                                <td> {{$salary->basic_salary}}</td>
                                                <td> {{$salary->allowances}}</td>
                                                <td> {{$salary->airtime_allowance}}</td>
                                                <td> {{$salary->overtime}}</td>
                                                <td> {{$salary->reimbursements}}</td>
                                                <td> {{$salary->transport_allowance}}</td>
                                                <td> {{$salary->ssnit_tier2_5}}</td>
                                                <td> {{$salary->ssnit_tier2_5d}}</td>   
                                                <td> {{$salary->tax}} </td>                            
                                                <td> {{$salary->ssnit_tier1_0_5}} </td>                            
                                                <td> {{$salary->welfare}} </td>                            
                                                <td> {{$salary->maintenance}} </td>                            
                                                <td> {{$salary->absent}} </td>                            
                                                <td> {{$salary->boot}} </td>                            
                                                <td> {{$salary->iou}} </td>                            
                                                <td> {{$salary->hostel}} </td>                            
                                                <td> {{$salary->insurance}} </td>                            
                                                <td> {{$salary->reprimand}} </td>                            
                                                <td> {{$salary->scouter}} </td>                            
                                                <td> {{$salary->raincoat}} </td>                            
                                                <td> {{$salary->meal}} </td>                            
                                                <td> {{$salary->loan}} </td>                            
                                                <td> {{$salary->walkin}} </td>                            
                                                <td> {{$salary->amnt_ded_cof_start_date}} </td>                            
                                                <td> {{$salary->other_deductions}} </td>                            
                                                <td> {{$salary->gross_salary}} </td>                            
                                                <td> {{$salary->total_deductions}} </td>                            
                                                <td> {{$salary->net_salary}} </td>                            
                                                <td> {{$salary->ssnit_comp_cont_13}} </td>                            
                                                <td> {{$salary->ssnit_tobe_paid13_5}} </td>                            
                                                <td> {{$salary->cost_to_company }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>
        </div>


    </div>
  <!-- / Content -->

  @endsection


    @section('scripts')

     <script src="{{asset('vendor/js/datatables.js')}}"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.dataTables.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.html5.min.js"></script>


    <script>
        let myTableiAccra = new DataTable('#myTableiAccra');
        let myTableiBotwe = new DataTable('#myTableiBotwe');
        let myTableiShyhills = new DataTable('#myTableiShyhills');
        let myTableiTema = new DataTable('#myTableiTema');
      new DataTable('#myTableimaster', {
        //  dom: 'Blfrtip',
        //  stateSave: false,
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
                        title: 'Salaries',
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

    @endsection
</x-hr-dashboard>
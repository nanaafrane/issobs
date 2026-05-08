<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.5/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.5/css/buttons.dataTables.css">   
     <link rel="stylesheet" href="{{asset('vendor/css/datatables.css')}}" /> 


    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/4.0.5/css/fixedHeader.dataTables.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/5.0.5/css/fixedColumns.dataTables.css">
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
                <h3 class="card-header"> <i class="icon-base bx bx-transfer-alt"></i> Salaries Transaction   @if (isset($month)) <strong> / For Month: {{  \Carbon\Carbon::parse($month)->format('F Y') }}  </strong>  @endif </h3>

            </div>
        </div><br>

         @if(Auth::user()->hasRole(['Invoice','Manager', 'Finance Manager' ]))
        <div class="row">
            <div class="col-lg-2 h-100" >
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
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5;  {{ number_format($groupedBankSalaries->sum('paid'), 2) }} </strong> </h4> 
                        <!-- <small class="fw-medium">TOTAL DEDUCTIONS : </small> <br> -->
                        <!-- <small class="fw-medium"> GH&#x20B5;  {{ number_format($groupedBankSalaries->sum('deductions'), 2) }}  </small> <br> -->
                        <small class="fw-medium"> EMPLOYEES : {{ $groupedBankSalaries->sum('total_employees') }}  </small>

                    </div>
                </div>
            </div>

            <div class="col-lg-2 h-100">
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
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($groupedCashkSalaries->sum('paid'), 2) }} </strong> </h4> 
                        <!-- <small class="fw-medium">TOTAL DEDUCTIONS : </small> <br> -->
                        <!-- <small class="fw-medium"> GH&#x20B5;  {{ number_format($groupedCashkSalaries->sum('deductions'), 2) }}  </small> <br> -->
                        <small class="fw-medium"> EMPLOYEES : {{ $groupedCashkSalaries->sum('total_employees') }}  </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2">
                <div  class="card h-100 bg-secondary text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart dark"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> CATEGORY A  </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($clientA->sum('net_salary'), 2) }} </strong> </h4> 
                        <small class="fw-medium"> <strong>  INVOICES :  &#x20B5; {{ number_format($clientAInvoices->sum('total'), 2) }}  </strong> </small> <br>
                        <small class="fw-medium"> <strong>  RECEIPTS :  &#x20B5; {{ number_format( collect($clientAReceipts['transfer'])->flatten()->sum() + collect($clientAReceipts['cheque'])->flatten()->sum() + collect($clientAReceipts['cash'])->flatten()->sum() + collect($clientAReceipts['momo'])->flatten()->sum(), 2) }}  </strong> </small> <br>
                        <small class="fw-medium"> CLIENTS :   {{ $clientA->count()  }} </small> <br>
                        <small class="fw-medium"> EMPLOYEES : {{ $clientA->sum('total_employees') }} </small> <br>
                        <small class="fw-medium text-danger"> <strong> HOLD : {{ $clientAHold->sum('total_employees') }} </strong> </small> <br>
                        <small class="fw-medium">  INVOICE GUARDS : {{ $clientAInvoicesGuards }}</small>

                        <hr>
                        <small class="fw-medium"> <strong>  CASH : &#x20B5; {{ number_format($clientACash->sum('net_salary'), 2) }} | {{ $clientACash->count() }} </strong> </small> <br>
                        <small class="fw-medium"> <strong>  BANK : &#x20B5; {{ number_format($clientABank->sum('net_salary'), 2) }} | {{ $clientABank->count() }} </strong> </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div  class="card h-100 bg-secondary text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart dark"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> CATEGORY B </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($clientB->sum('net_salary'), 2) }} </strong> </h4> 
                        <small class="fw-medium"> <strong>  INVOICES :  &#x20B5; {{ number_format($clientBInvoices->sum('total'), 2) }} </strong> </small> <br>
                        <small class="fw-medium"> <strong>  RECEIPTS :  &#x20B5; {{ number_format( collect($clientBReceipts['transfer'])->flatten()->sum() + collect($clientBReceipts['cheque'])->flatten()->sum() + collect($clientBReceipts['cash'])->flatten()->sum() + collect($clientBReceipts['momo'])->flatten()->sum(), 2) }} </strong> </small> <br>
                        <small class="fw-medium"> CLIENTS :  {{ $clientB->count() }} </small> <br>
                        <small class="fw-medium"> EMPLOYEES : {{ $clientB->sum('total_employees') }} </small>  <br>
                        <small class="fw-medium text-danger"> <strong> HOLD : {{ $clientBHold->sum('total_employees') }} </strong> </small>  <br>
                         <small class="fw-medium">  INVOICE GUARDS : {{ $clientBInvoicesGuards }}</small>
                        <hr>
                        <small class="fw-medium"> <strong>  CASH : &#x20B5; {{ number_format($clientBCash->sum('net_salary'), 2) }} | {{ $clientBCash->count() }} </strong> </small> <br>
                        <small class="fw-medium"> <strong>  BANK : &#x20B5; {{ number_format($clientBBank->sum('net_salary'), 2) }} | {{ $clientBBank->count() }} </strong> </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div  class="card h-100 bg-secondary text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> CATEGORY C  </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($clientC->sum('net_salary'), 2) }} </strong> </h4> 
                        <small class="fw-medium"> <strong>  INVOICES :  &#x20B5; {{ number_format($clientCInvoices->sum('total'), 2) }} </strong> </small> <br>
                        <small class="fw-medium"> <strong>  RECEIPTS :  &#x20B5; {{ number_format( collect($clientCReceipts['transfer'])->flatten()->sum() + collect($clientCReceipts['cheque'])->flatten()->sum() + collect($clientCReceipts['cash'])->flatten()->sum() + collect($clientCReceipts['momo'])->flatten()->sum(), 2) }} </strong> </small> <br>
                        <small class="fw-medium"> CLIENTS : {{ $clientC->count() }} </small> <br>
                        <small class="fw-medium"> EMPLOYEES : {{ $clientC->sum('total_employees') }} </small> <br>
                        <small class="fw-medium text-danger"> <strong> HOLD : {{ $clientCHold->sum('total_employees') }} </strong> </small> <br>
                         <small class="fw-medium">  INVOICE GUARDS : {{ $clientCInvoicesGuards }}</small>
                        <hr>
                        <small class="fw-medium"> <strong>  CASH : &#x20B5; {{ number_format($clientCCash->sum('net_salary'), 2)  }} |  {{  $clientCCash->count() }} </strong> </small> <br>
                        <small class="fw-medium"> <strong>  BANK : &#x20B5; {{ number_format($clientCBank->sum('net_salary'), 2) }} | {{ $clientCBank->count() }} </strong> </small> 
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div  class="card h-100 bg-secondary text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> CATEGORY D  </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($clientD->sum('net_salary'), 2) }} </strong> </h4> 
                        <small class="fw-medium"> <strong>  INVOICES :  &#x20B5; {{ number_format($clientDInvoices->sum('total'), 2) }} </strong> </small> <br>
                        <small class="fw-medium"> <strong>  RECEIPTS :  &#x20B5; {{ number_format( collect($clientDReceipts['transfer'])->flatten()->sum() + collect($clientDReceipts['cheque'])->flatten()->sum() + collect($clientDReceipts['cash'])->flatten()->sum() + collect($clientDReceipts['momo'])->flatten()->sum(), 2) }} </strong> </small> <br>
                        <small class="fw-medium"> CLIENTS :  {{ $clientD->count() }} </small> <br>
                        <small class="fw-medium"> EMPLOYEES : {{ $clientD->sum('total_employees') }} </small> <br>
                        <small class="fw-medium text-danger"> <strong> HOLD : {{ $clientDHold->sum('total_employees') }} </strong> </small> <br>
                         <small class="fw-medium">  INVOICE GUARDS : {{ $clientDInvoicesGuards }}</small>
                        <hr>
                        <small class="fw-medium"> <strong>  CASH : &#x20B5; {{ number_format($clientDCash->sum('net_salary'), 2) }} | {{ $clientDCash->count() }} </strong> </small> <br>
                        <small class="fw-medium"> <strong>  BANK : &#x20B5; {{ number_format($clientDBank->sum('net_salary'), 2) }} | {{ $clientDBank->count() }} </strong> </small>  
                    </div>
                </div>
            </div>            

        </div> <br>

        <div class="row">

            <div class="col-lg-2" style="transform: translateY(-150px);">
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
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($salariesTaxes->sum('tax'), 2) }} </strong> </h4> 
                        <!-- <small class="fw-medium"> TOTAL :  GH&#x20B5; {{ number_format($salariesTaxes->sum('paid'), 2) }} </small> <br> -->
                        <small class="fw-medium"> EMPLOYEES : {{ $salariesTaxes->sum('total_employees') }} </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2" style="transform: translateY(-150px);">
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
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($salariesPensions->sum('cont13_5'), 2) }} </strong> </h4> 
                        <!-- <small class="fw-medium"> TOTAL :  GH&#x20B5; {{ number_format($salariesPensions->sum('paid') , 2) }} </small> <br> -->
                        <small class="fw-medium"> EMPLOYEES : {{ $salariesPensions->sum('total_employees') }} </small>
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
                        <p class="mb-1"><strong> BOOTS </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($salariesBoots->sum('boot'), 2) }} </strong> </h4> 
                        <!-- <small class="fw-medium"> TOTAL :  GH&#x20B5; {{ number_format($salariesBoots->sum('paid') , 2) }} </small> <br> -->
                        <small class="fw-medium"> EMPLOYEES : {{ $salariesBoots->sum('total_employees') }} </small>
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
                        <p class="mb-1"><strong> OVERTIME </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($salariesOvertime->sum('overtime'), 2) }} </strong> </h4> 
                        <!-- <small class="fw-medium"> TOTAL :  GH&#x20B5; {{ number_format($salariesOvertime->sum('paid') , 2) }} </small> <br> -->
                        <small class="fw-medium"> EMPLOYEES : {{ $salariesOvertime->sum('total_employees') }} </small>
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
                        <p class="mb-1"><strong> IOU </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($salariesIOU->sum('iou'), 2) }} </strong> </h4> 
                        <!-- <small class="fw-medium"> TOTAL :  GH&#x20B5; {{ number_format($salariesIOU->sum('paid') , 2) }} </small> <br> -->
                        <small class="fw-medium"> EMPLOYEES : {{ $salariesIOU->sum('total_employees') }} </small>
                    </div>
                </div>
            </div>  

        </div>
        
        
        <br> <br>
                     <div class="card-header  ml-2  d-none d-lg-block">
                  @include('flash-messages')
        </div> <br>
        @endif

        <!-- Table -->  
        <hr> <br> <br>
        <div class="nav-align-top">
            <ul class="nav nav-pills mb-4 nav-fill" role="tablist">

                @if(Auth::user()->hasRole(['Finance Manager', 'Invoice', 'Manager']) )
                <li class="nav-item mb-1 mb-sm-0">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-clients"
                        aria-controls="navs-pills-justified-clients"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center">
                            <i class="icon-base bx bx-home icon-sm me-1_5"></i>Clients
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesClients->count() }} </span>
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
                        data-bs-target="#navs-pills-justified-accra"
                        aria-controls="navs-pills-justified-accra"
                        aria-selected="false">
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
                        data-bs-target="#navs-pills-justified-overtime"
                        aria-controls="navs-pills-justified-overtime"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>Overtime
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesOvertime->count() }}</span>
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
                        data-bs-target="#navs-pills-justified-iou"
                        aria-controls="navs-pills-justified-iou"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>IOU
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesIOU->count() }}</span>
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
                        data-bs-target="#navs-pills-justified-boot"
                        aria-controls="navs-pills-justified-boot"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>BOOTS
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesBoots->count() }}</span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>


                <li class="nav-item mb-1 mb-sm-0">
                    <button
                        type="button"
                        class="nav-link active"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-master"
                        aria-controls="navs-pills-justified-master"
                        aria-selected="true">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i> Master
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesMaster->count() }} </span>
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
                        data-bs-target="#navs-pills-justified-hold"
                        aria-controls="navs-pills-justified-hold"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i> HOLD
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>
                @endif

            </ul>


            <div class="tab-content">
                <div class="tab-pane fade " id="navs-pills-justified-clients" role="tabpanel">

                             <div class="nav-align-top">
                                <ul class="nav nav-pills mb-4 nav-fill" role="tablist">
                                    
                                    <li class="nav-item mb-1 mb-sm-0">
                                        <button
                                            type="button"
                                            class="nav-link"
                                            role="tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#navs-pills-justified-categorya"
                                            aria-controls="navs-pills-justified-categorya"
                                            aria-selected="true">
                                            <span class="d-none d-sm-inline-flex align-items-center">
                                                <i class="icon-base bx bx-home icon-sm me-1_5"></i>CATEGORY A
                                                <span class="badge rounded-pill bg-danger ms-1_5"> {{ $clientA->count() }} </span>
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
                                            data-bs-target="#navs-pills-justified-categoryb"
                                            aria-controls="navs-pills-justified-categoryb"
                                            aria-selected="true">
                                            <span class="d-none d-sm-inline-flex align-items-center">
                                                <i class="icon-base bx bx-home icon-sm me-1_5"></i>CATEGORY B
                                                <span class="badge rounded-pill bg-danger ms-1_5"> {{ $clientB->count() }} </span>
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
                                            data-bs-target="#navs-pills-justified-categoryc"
                                            aria-controls="navs-pills-justified-categoryc"
                                            aria-selected="true">
                                            <span class="d-none d-sm-inline-flex align-items-center">
                                                <i class="icon-base bx bx-home icon-sm me-1_5"></i>CATEGORY C
                                                <span class="badge rounded-pill bg-danger ms-1_5"> {{ $clientC->count() }} </span>
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
                                            data-bs-target="#navs-pills-justified-categoryd"
                                            aria-controls="navs-pills-justified-categoryd"
                                            aria-selected="true">
                                            <span class="d-none d-sm-inline-flex align-items-center">
                                                <i class="icon-base bx bx-home icon-sm me-1_5"></i>CATEGORY D
                                                <span class="badge rounded-pill bg-danger ms-1_5"> {{ $clientD->count() }} </span>
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
                                            data-bs-target="#navs-pills-justified-clientmaster"
                                            aria-controls="navs-pills-justified-clientmaster"
                                            aria-selected="true">
                                            <span class="d-none d-sm-inline-flex align-items-center">
                                                <i class="icon-base bx bx-home icon-sm me-1_5"></i>CLIENT MASTER
                                                <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesClients->count() }} </span>
                                            </span>
                                            <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                                        </button>
                                    </li>

                                </ul>
                             </div>

                             <div class="tab-content">
                                
                                <div class="tab-pane fade " id="navs-pills-justified-categorya" role="tabpanel">
                                    <form action="/categoryReAssign" method="POST">
                                        @csrf
                                        <input type="text" name="month" value="{{ $month }}" hidden>
                                        <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />
                                    
                                        <div class="form-check form-check-inline">
                                            <button class="btn btn-dark" name="submit" value="" onclick="return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-recycle"> </i> {{ __('ReAssign Category') }}</button>                   
                                       
                                            <a href="/exportCategory/{{ $month }}/Category A" class="btn btn-success m-4" > <i class="icon-base bx bx-bxs-file-plus"> </i> {{ __(' Excel Download Category') }}</a>                   

                                        </div>
                                    <div class="table-responsive">
                                        <table id="myTablecategorya" class="display">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>#</th>
                                                    <th>Client Name</th>
                                                    <th>Category_Name</th>
                                                    <th>Field</th>
                                                    <th>Inv Value</th>
                                                    <th>Inv Guards</th>
                                                    <th>Inv Status</th>
                                                    <th>Net Salary</th>
                                                    <th> Difference </th>
                                                    <th>Status</th>
                                                    <th>Employees</th>
                                                    <th> View Employees</th>

                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">
                                                @foreach($clientA as $key => $catA)
                                                <tr>
                                                    <td> <input class="checkBoxes form-check-input" type="checkbox" name="client[{{ $key + 1 }}]" value="{{ $catA->client?->id }}" /> </td>
                                                    <td> {{ $key + 1 }} </td>
                                                    <td> {{ $catA->client?->name }} {{ $catA->client?->business_name }} </td>
                                                    <td> 
                                                                <select name="name[{{ $key + 1 }}]" class="form-select @error('name') is-invalid @enderror" id="name" value="{{ old('name')}}" required>
                                                                    <option >Choose...</option>
                                                                    <option selected value="Category A">Category A </option>
                                                                    <option value="Category B">Category B </option>
                                                                    <option value="Category C">Category C </option>
                                                                    <option value="Category D">Category D </option>
                                                                </select>
                                                    </td>
                                                    <td> {{ $catA->client?->field?->name }} </td>
                                                    <td> @php  $catAinv = $catA->client?->invoices()->whereMonth('invoice_month', $month->month)->get(); @endphp GH&#x20B5; {{  number_format($catAinv->sum('total'), 2)  }} </td>
                                                  
                                                    <td>  
                                                        @php
                                                            $guardsa = [];
                                                            foreach ($catAinv as $invGuard) {
                                                                $guardsa[] = $invGuard->invoice_data()->sum('quantity');
                                                            }
                                                        @endphp
                                                        {{ collect($guardsa)->sum() }}
                                                    </td>

                                                    <td>  {{ $catAinv->pluck('status') }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catA->net_salary, 2) }} </td>
                                                    <td> GH&#x20B5; {{ number_format( $catAinv->sum('total') - $catA->net_salary, 2) }} </td>
                                                
                                                    @if( $catAinv->sum('total') <= $catA->net_salary )
                                                        <td><span class="badge bg-label-danger"> Loss </span></td>
                                                    @else
                                                        <td><span class="badge bg-label-success"> Profit </span></td>
                                                    @endif
                                                
                                                    <td> {{ $catA->total_employees }} </td>
                                                    <td> 
                                                        <a href="/salariesClientMonth/{{$catA->client_id}} / {{$month}} " class="btn btn-dark btn-sm">
                                                            <i class="bx bx-show"></i> 
                                                        </a>    
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    </form>
                                </div>

                                <div class="tab-pane fade " id="navs-pills-justified-categoryb" role="tabpanel">
                                    <form action="/categoryReAssign" method="POST">
                                        @csrf
                                        <input type="text" name="month" value="{{ $month }}" hidden>
                                        <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />
                                    
                                        <div class="form-check form-check-inline">
                                            <button class="btn btn-dark" name="submit" value="" onclick="return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-recycle"> </i> {{ __('ReAssign Category') }}</button>                   
                                            <a href="/exportCategory/{{ $month }}/Category B" class="btn btn-success m-4" > <i class="icon-base bx bx-bxs-file-plus"> </i> {{ __(' Excel Download Category') }}</a>                   
                                       
                                        </div>
                                    <div class="table-responsive">
                                        <table id="myTablecategoryb" class="display">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>#</th>
                                                    <th>Client Name</th>
                                                    <th>Category_Name</th>
                                                    <th>Field</th>
                                                    <th>Inv Value</th>
                                                    <th>Inv Guards</th>
                                                    <th>Inv Status</th>
                                                    <th>Net Salary</th>
                                                    <th> Difference </th>
                                                    <th>Status</th>
                                                    <th>Employees</th>
                                                    <th> View Employees</th>
                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">
                                                @foreach($clientB as $keyb => $catB)
                                                <tr>
                                                    <td> <input class="checkBoxes form-check-input" type="checkbox" name="client[{{ $keyb + 1 }}]" value="{{ $catB->client?->id }}" /> </td>
                                                   <td> {{ $keyb + 1 }} </td>
                                                    <td> {{ $catB->client?->name }} {{ $catB->client?->business_name }} </td>
                                                    <td> 
                                                                <select name="name[{{ $keyb + 1 }}]" class="form-select @error('name') is-invalid @enderror" id="name" value="{{ old('name')}}" required>
                                                                    <option value="">Choose...</option>
                                                                    <option value="Category A">Category A </option>
                                                                    <option selected value="Category B">Category B </option>
                                                                    <option value="Category C">Category C </option>
                                                                    <option value="Category D">Category D </option>
                                                                </select>    
                                                    </td>
                                                    <td> {{ $catB->client?->field?->name }} </td>
                                                    <td> @php $catBinv = $catB->client?->invoices()->whereMonth('invoice_month', $month->month)->get() @endphp GH&#x20B5; {{ number_format( $catBinv->sum('total'),2)  }} </td>
                                                    <td> 
                                                        @php
                                                            $guardsb = [];
                                                            foreach ($catBinv as $invGuard) {
                                                                $guardsb[] = $invGuard->invoice_data()->sum('quantity');
                                                            }
                                                        @endphp
                                                        {{ collect($guardsb)->sum() }}
                                                    </td>
                                                    <td>  {{ $catBinv->pluck('status') }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catB->paid, 2) }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catBinv->sum('total') - $catB->paid, 2) }} </td>
                                                
                                                    @if( $catBinv->sum('total') <= $catB->paid )
                                                        <td><span class="badge bg-label-danger"> Loss </span></td>
                                                    @else
                                                        <td><span class="badge bg-label-success"> Profit </span></td>
                                                    @endif
                                                
                                                    <td> {{ $catB->total_employees }} </td>
                                                    <td> 
                                                        <a href="/salariesClientMonth/{{$catB->client_id}} / {{$month}} " class="btn btn-dark btn-sm">
                                                            <i class="bx bx-show"></i> 
                                                        </a>    
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    </form>
                                </div>


                                <div class="tab-pane fade " id="navs-pills-justified-categoryc" role="tabpanel">
                                    <form action="/categoryReAssign" method="POST">
                                        @csrf
                                        <input type="text" name="month" value="{{ $month }}" hidden>
                                        <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />
                                    
                                        <div class="form-check form-check-inline">
                                            <button class="btn btn-dark" name="submit" value="" onclick="return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-recycle"> </i> {{ __('ReAssign Category') }}</button>                   
                                            <a href="/exportCategory/{{ $month }}/Category C" class="btn btn-success m-4" > <i class="icon-base bx bx-bxs-file-plus"> </i> {{ __(' Excel Download Category') }}</a>                   
                                       
                                        </div>

                                    <div class="table-responsive">
                                        <table id="myTablecategoryc" class="display">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>#</th>
                                                    <th>Client Name</th>
                                                    <th>Category_Name</th>
                                                    <th>Field</th>
                                                    <th>Inv Value</th>
                                                    <th>Inv Guards</th>
                                                    <th>Inv Status</th>
                                                    <th>Net Salary</th>
                                                    <th> Difference </th>
                                                    <th>Status</th>
                                                    <th>Employees</th>
                                                    <th> View Employees</th>
                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">
                                                @foreach($clientC as $keyc => $catC)
                                                <tr>
                                                    <td> <input class="checkBoxes form-check-input" type="checkbox" name="client[{{ $keyc + 1 }}]" value="{{ $catC->client?->id }}" /> </td>
                                                   <td> {{ $keyc + 1 }} </td>
                                                    <td> {{ $catC->client?->name }} {{ $catC->client?->business_name }} </td>
                                                    <td> 
                                                                <select name="name[{{ $keyc + 1 }}]" class="form-select @error('name') is-invalid @enderror" id="name" value="{{ old('name')}}" required>
                                                                    <option value="">Choose...</option>
                                                                    <option value="Category A">Category A </option>
                                                                    <option value="Category B">Category B </option>
                                                                    <option selected value="Category C">Category C </option>
                                                                    <option value="Category D">Category D </option>
                                                                </select>
                                                    </td>
                                                    <td> {{ $catC->client?->field?->name }} </td>
                                                    <td>  @php $catCinv = $catC->client?->invoices()->whereMonth('invoice_month', $month->month)->get() @endphp GH&#x20B5; {{ number_format( $catCinv->sum('total'),2)  }} </td>
                                                    <td>
                                                        @php
                                                            $guardsc = [];
                                                            foreach ($catCinv as $invGuard) {
                                                                $guardsc[] = $invGuard->invoice_data()->sum('quantity');
                                                            }
                                                        @endphp
                                                        {{ collect($guardsc)->sum() }}
                                                    </td>
                                                    <td>  {{ $catCinv->pluck('status') }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catC->paid, 2) }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catCinv->sum('total') - $catC->paid, 2) }} </td>
                                                
                                                    @if( $catCinv->sum('total') <= $catC->paid )
                                                        <td><span class="badge bg-label-danger"> Loss </span></td>
                                                    @else
                                                        <td><span class="badge bg-label-success"> Profit </span></td>
                                                    @endif
                                                
                                                    <td> {{ $catC->total_employees }} </td>
                                                    <td> 
                                                        <a href="/salariesClientMonth/{{$catC->client_id}} / {{$month}} " class="btn btn-dark btn-sm">
                                                            <i class="bx bx-show"></i> 
                                                        </a>    
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    </form>
                                </div>


                                <div class="tab-pane fade " id="navs-pills-justified-categoryd" role="tabpanel">
                                    <form action="/categoryReAssign" method="POST">
                                        @csrf
                                        <input type="text" name="month" value="{{ $month }}" hidden>
                                        <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />
                                    
                                        <div class="form-check form-check-inline">
                                            <button class="btn btn-dark" name="submit" value="" onclick="return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-recycle"> </i> {{ __('ReAssign Category') }}</button>                   
                                            <a href="/exportCategory/{{ $month }}/Category D" class="btn btn-success m-4" > <i class="icon-base bx bx-bxs-file-plus"> </i> {{ __(' Excel Download Category') }}</a>                   
                                       
                                        </div>

                                    <div class="table-responsive">
                                        <table id="myTablecategoryd" class="display">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>#</th>
                                                    <th>Client Name</th>
                                                    <th>Category_Name</th>
                                                    <th>Field</th>
                                                    <th>Inv Value</th>
                                                    <th>Inv Guards</th>
                                                    <th>Inv Status</th>
                                                    <th>Net Salary</th>
                                                    <th> Difference </th>
                                                    <th>Status</th>
                                                    <th>Employees</th>
                                                    <th> View Employees</th>
                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">
                                                @foreach($clientD as $keyd => $catD)
                                                <tr>
                                                    <td> <input class="checkBoxes form-check-input" type="checkbox" name="client[{{ $keyd + 1 }}]" value="{{ $catD->client?->id }}" /> </td>
                                                   <td> {{ $keyd + 1 }} </td>
                                                    <td> {{ $catD->client?->name }} {{ $catD->client?->business_name }} </td>
                                                   <td> 
                                                        <select name="name[{{ $keyd + 1 }}]" class="form-select @error('name') is-invalid @enderror" id="name" value="{{ old('name')}}" required>
                                                            <option value="">Choose...</option>
                                                            <option value="Category A">Category A </option>
                                                            <option value="Category B">Category B </option>
                                                            <option value="Category C">Category C </option>
                                                            <option selected value="Category D">Category D </option>
                                                        </select>
                                                   </td>
                                                    <td> {{ $catD->client?->field?->name }} </td>
                                                    <td> @php $catDinv = $catD->client?->invoices()->whereMonth('invoice_month', $month->month)->get() @endphp GH&#x20B5; {{ number_format( $catDinv->sum('total'),2)  }} </td>
                                                    <td> 
                                                        @php
                                                            $guardsd = [];
                                                            foreach ($catDinv as $invGuard) {
                                                                $guardsd[] = $invGuard->invoice_data()->sum('quantity');
                                                            }
                                                        @endphp
                                                        {{ collect($guardsd)->sum() }}
                                                    </td>
                                                    <td>  {{ $catDinv->pluck('status') }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catD->paid, 2) }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catDinv->sum('total') - $catD->paid, 2) }} </td>
                                                
                                                    @if( $catDinv->sum('total') <= $catD->paid )
                                                        <td><span class="badge bg-label-danger"> Loss </span></td>
                                                    @else
                                                        <td><span class="badge bg-label-success"> Profit </span></td>
                                                    @endif
                                                
                                                    <td> {{ $catD->total_employees }} </td>
                                                    <td> 
                                                        <a href="/salariesClientMonth/{{$catD->client_id}} / {{$month}} " class="btn btn-dark btn-sm">
                                                            <i class="bx bx-show"></i> 
                                                        </a>    
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    </form>
                                </div>

                            
                                <div class="tab-pane fade " id="navs-pills-justified-clientmaster" role="tabpanel">
                                    <form action="/categoryAssign" method="POST">
                                        @csrf
                                        <input type="text" name="month" value="{{ $month }}" hidden>
                                        <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />
                                    
                                        <div class="form-check form-check-inline">
                                            <button class="btn btn-dark" name="submit" value="" onclick="return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-recycle"> </i> {{ __('Assign Category') }}</button>                   
                                        </div>
                                        
                                        <div class="table-responsive">
                                            <table id="myTableiclientmaster" class="display">
                                                <thead>
                                                    <tr>
                                                        <th></th>
                                                        <th>#</th>
                                                        <th>Client Name</th>
                                                        <th>Category</th>
                                                        <th> Assign New_Category</th>
                                                        <th>Field</th>
                                                        <th>Inv Value</th>
                                                        <th>Inv Guards</th>
                                                        <th>Inv Status</th>
                                                        <th>Net Salary</th>
                                                        <th> Difference </th>
                                                        <th>Status</th>
                                                        <th>Employees</th>
                                                        <th> View Employees</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    @foreach($salariesClients as $cm => $clientMaster)
                                                    <tr>
                                                        <td> <input class="checkBoxes form-check-input" type="checkbox" name="client[]" value="{{ $clientMaster->client?->id }}" /> </td>
                                                        <td> {{ $cm + 1 }} </td>
                                                        <td> {{ $clientMaster->client?->name }} {{ $clientMaster->client?->business_name }} </td>
                                                        <td> @if ( $clientMaster->client?->category_month == \Carbon\Carbon::parse($month)->format('Y-m-d') ) {{ $clientMaster->client?->category_name }} @endif </td>
                                                        <td>
                                                                <select name="name[]" class="form-select @error('name') is-invalid @enderror" id="name" value="{{ old('name')}}" required>
                                                                    <option selected disabled>Choose...</option>
                                                                    <option value="Category A">Category A </option>
                                                                    <option value="Category B">Category B </option>
                                                                    <option value="Category C">Category C </option>
                                                                    <option value="Category D">Category D </option>
                                                                </select>
                                                        </td>
                                                        <td> {{ $clientMaster->client?->field?->name }} </td>
                                                        <td> @php $clientInv = $clientMaster->client?->invoices()->whereMonth('invoice_month', $month->month)->get() @endphp GH&#x20B5; {{ number_format($clientInv->sum('total'), 2) }} </td>
                                                        <td> 
                                                            @php
                                                                $guards = [];
                                                                foreach ($clientInv as $invGuard) {
                                                                    $guards[] = $invGuard->invoice_data()->sum('quantity');
                                                                }
                                                            @endphp
                                                            {{ collect($guards)->sum() }}
                                                        </td>
                                                        <td>  {{ $clientInv->pluck('status') }} </td>
                                                        <td> GH&#x20B5; {{ number_format($clientMaster->paid, 2) }} </td>
                                                        <td> GH&#x20B5; {{ number_format($clientInv->sum('total') - $clientMaster->paid, 2) }} </td>

                                                        @if( $clientInv->sum('total') <= $clientMaster->paid )
                                                            <td><span class="badge bg-label-danger"> Loss </span></td>
                                                        @else
                                                            <td><span class="badge bg-label-success"> Profit </span></td>
                                                        @endif
                                                    
                                                        <td> {{ $clientMaster->total_employees }} </td>
                                                        <td> 
                                                            <a href="/salariesClientMonth/{{$clientMaster->client_id}} / {{$month}} " class="btn btn-dark btn-sm">
                                                                <i class="bx bx-show"></i> 
                                                            </a>    
                                                        </td>

                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                    </form>
                                </div>

                             </div>
               
                </div>

                <div class="tab-pane fade " id="navs-pills-justified-accra" role="tabpanel">
                    <div class="table-responsive">
                        <table id="myTableiAccra" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Bank Name</th>
                                    <th>Gross Salary</th>
                                    <th>Total Deductions</th>
                                    <th>Net Salary</th>
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
                                        <a href="/salariesBankMonth/{{ $banks->bank->id }}/{{ $month }}" class="btn btn-dark btn-sm">
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
                                    <th>Net Salary </th>
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
                                        <a href="/salariesCashMonth/{{ $cash->field?->id }}/{{ $month->format('F Y') }}" class="btn btn-dark btn-sm">
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
                                    <th>Net Salary</th>
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
                                        <a href="/salariesTaxMonth/{{ $taxes->field?->id }}/{{ $month->format('F Y') }}" class="btn btn-dark btn-sm">
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
                                    <th>Net Salary</th>
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
                                    <td> GH&#x20B5; {{ number_format($pensions->paid, 2) }}</td>
                                    <td> GH&#x20B5; {{ number_format($pensions->cont13, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($pensions->cont13_5, 2) }} </td>
                                    <td> {{ $pensions->total_employees }} </td>
                                    <td> 
                                        <a href="/salariesPensionMonth/{{ $pensions->field?->id }}/{{ $month->format('F Y') }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a>    
                                    </td>   
                                </tr>
                                    @endforeach 
                            </tbody>
                        </table>
                    </div>
                </div>


                <div class="tab-pane fade" id="navs-pills-justified-overtime" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                        <table id="myTableiOvertime" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Field</th>
                                    <th>Overtime</th>
                                    <th>Net Salary</th>
                                    <th>Employees</th>
                                    <th> View Employees</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                              @foreach ($salariesOvertime as $key => $overtime)
                                   <tr>
                                    <td> {{ $key + 1 }} </td>
                                    <td>{{ $overtime->field?->name }}</td>
                                    <td> GH&#x20B5; {{ number_format($overtime->overtime, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($overtime->paid, 2) }}</td>
                                    <td> {{ $overtime->total_employees }} </td>
                                    <td> 
                                        <a href="/salariesOvertimeMonth/{{ $overtime->field?->id }}/{{ $month->format('F Y') }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a>    
                                    </td>   
                                </tr>
                                    @endforeach 
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="navs-pills-justified-iou" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                        <table id="myTableiIou" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Field</th>
                                    <th>IOU</th>
                                    <th>Net Salary</th>
                                    <th>Employees</th>
                                    <th> View Employees</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                              @foreach ($salariesIOU as $key => $iou)
                                   <tr>
                                    <td> {{ $key + 1 }} </td>
                                    <td>{{ $iou->field?->name }}</td>
                                    <td> GH&#x20B5; {{ number_format($iou->iou, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($iou->paid, 2) }}</td>
                                    <td> {{ $iou->total_employees }} </td>
                                    <td> 
                                        <a href="/salariesIouMonth/{{ $iou->field?->id }}/{{ $month->format('F Y') }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a>    
                                    </td>   
                                </tr>
                                    @endforeach 
                            </tbody>
                        </table>
                    </div>
                </div>


                <div class="tab-pane fade" id="navs-pills-justified-boot" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                        <table id="myTableiboot" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Field</th>
                                    <th>Boots</th>
                                    <th>Net Salary</th>
                                    <th>Employees</th>
                                    <th> View Employees</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                              @foreach ($salariesBoots as $key => $boots)
                                   <tr>
                                    <td> {{ $key + 1 }} </td>
                                    <td>{{ $boots->field?->name }}</td>
                                    <td> GH&#x20B5; {{ number_format($boots->boot, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($boots->paid, 2) }}</td>
                                    <td> {{ $boots->total_employees }} </td>
                                    <td> 
                                        <a href="/salariesBootMonth/{{ $boots->field?->id }}/{{ $month->format('F Y') }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a>    
                                    </td>   
                                </tr>
                                    @endforeach 
                            </tbody>
                        </table>
                    </div>
                </div>


                 <div class="tab-pane fade show active" id="navs-pills-justified-master" role="tabpanel">
                    <form action="/salariesBulkCash" method="POST">
                        @csrf
                        <div class="table-responsive text-nowrap">

                            <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />

                            <div class="form-check form-check-inline">
                                <!-- <button class="btn btn-dark" name="submit" value="bulk" onclick="return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-bxs-file-plus"> </i> {{ __(' Add To Bulk Cash Salaries') }}</button>                    -->
                                
                                <button class="btn btn-danger m-4" name="submit" value="hold" onclick="return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-bxs-file-plus"> </i> {{ __(' Hold') }}</button>                   
                                
                                <a href="{{ url('/exportMaster', $salariesMaster[0]->salary_month?->format('F, Y'))}} " class="btn btn-success m-4" > <i class="icon-base bx bx-bxs-file-plus"> </i> {{ __(' Excel Download Master') }}</a>                   
                          
                          
                            </div>

                            <table id="myTableimaster" class="display">
                                <thead>
                                    <tr>
                                        <th> </th>
                                        <th> Edit </th>
                                        <th> View </th>
                                        <!-- <th> Bulk Cash</th> -->
                                        <th> Payment Status</th>
                                        <th> Category </th>
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
                                        <th> Inv. Status </th>
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
                                        <th> meal </th>
                                        <th> loan </th>
                                        <th> walkin </th>
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
                                                    <td> <input class="checkBoxes form-check-input" type="checkbox" name="salary[]" value="{{ $salary->id }}" /> </td>
                                                    <td><a class="dropdown-item" href="/salaries/{{$salary->id}}/edit"><i class="icon-base bx bx-edit-alt me-1"></i></a> </td>
                                                    <td><a class="dropdown-item" href="/salaries/{{$salary->id}}"><i class="icon-base bx bxs-bullseye"></i></a> </td>

                                                    @if ($salary->payment_status == 'pending')
                                                    <td>  <span class="badge bg-label-info"> {{ $salary->payment_status }} </span> </td>
                                                    @elseif($salary->payment_status == 'hold')
                                                    <td> <span class="badge bg-label-warning"> {{ $salary->payment_status }} </span> </td>
                                                    @elseif($salary->payment_status == 'rejected')
                                                    <td> <span class="badge bg-label-danger"> {{ $salary->payment_status }} </span> </td>
                                                    @else
                                                    <td> <span class="badge bg-label-success"> {{ $salary->payment_status }} </span> </td>
                                                    @endif

                                                    <td> @if ( $salary->client?->category_month == \Carbon\Carbon::parse($month)->format('Y-m-d') ) {{ $salary->client?->category_name }} @endif </td>
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
                                                    <td> {{ $salary->client?->invoices()->whereMonth('invoice_month', $month->month)->pluck('status') }} </td>
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
                    </form>
                </div>

                <div class="tab-pane fade" id="navs-pills-justified-hold" role="tabpanel">
                   
                             <div class="nav-align-top">
                                <ul class="nav nav-pills mb-4 nav-fill" role="tablist">
                                    
                                    <li class="nav-item mb-1 mb-sm-0">
                                        <button
                                            type="button"
                                            class="nav-link"
                                            role="tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#navs-pills-justified-categoryahold"
                                            aria-controls="navs-pills-justified-categoryahold"
                                            aria-selected="true">
                                            <span class="d-none d-sm-inline-flex align-items-center">
                                                <i class="icon-base bx bx-home icon-sm me-1_5"></i>CATEGORY A HOLD
                                                <span class="badge rounded-pill bg-danger ms-1_5"> {{ $clientAHold->count() }} </span>
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
                                            data-bs-target="#navs-pills-justified-categorybhold"
                                            aria-controls="navs-pills-justified-categorybhold"
                                            aria-selected="true">
                                            <span class="d-none d-sm-inline-flex align-items-center">
                                                <i class="icon-base bx bx-home icon-sm me-1_5"></i>CATEGORY B HOLD
                                                <span class="badge rounded-pill bg-danger ms-1_5"> {{ $clientBHold->count() }} </span>
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
                                            data-bs-target="#navs-pills-justified-categorychold"
                                            aria-controls="navs-pills-justified-categorychold"
                                            aria-selected="true">
                                            <span class="d-none d-sm-inline-flex align-items-center">
                                                <i class="icon-base bx bx-home icon-sm me-1_5"></i>CATEGORY C HOLD
                                                <span class="badge rounded-pill bg-danger ms-1_5"> {{ $clientCHold->count() }} </span>
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
                                            data-bs-target="#navs-pills-justified-categorydhold"
                                            aria-controls="navs-pills-justified-categorydhold"
                                            aria-selected="true">
                                            <span class="d-none d-sm-inline-flex align-items-center">
                                                <i class="icon-base bx bx-home icon-sm me-1_5"></i>CATEGORY D HOLD
                                                <span class="badge rounded-pill bg-danger ms-1_5"> {{ $clientDHold->count() }} </span>
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
                                            data-bs-target="#navs-pills-justified-clientmasterhold"
                                            aria-controls="navs-pills-justified-clientmasterhold"
                                            aria-selected="true">
                                            <span class="d-none d-sm-inline-flex align-items-center">
                                                <i class="icon-base bx bx-home icon-sm me-1_5"></i>CLIENT MASTER HOLD
                                                <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesClientsHold->count() }} </span>
                                            </span>
                                            <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                                        </button>
                                    </li>

                                </ul>
                             </div>

                             <div class="tab-content">
                                
                                <div class="tab-pane fade " id="navs-pills-justified-categoryahold" role="tabpanel">
                                    <div class="table-responsive">
                                        <table id="myTablecategoryahold" class="display">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Client Name</th>
                                                    <th>Field</th>
                                                    <th>Inv Value</th>
                                                    <th>Inv Status</th>
                                                    <th>Net Salary</th>
                                                    <th> Difference </th>
                                                    <th>Status</th>
                                                    <th>Employees</th>
                                                    <th> View Employees</th>

                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">
                                                @foreach($clientAHold as $keyah => $catAHold)
                                                <tr>
                                                    <td> {{ $keyah + 1 }} </td>
                                                    <td> {{ $catAHold->client?->name }} {{ $catAHold->client?->business_name }} </td>
                                                    <td> {{ $catAHold->client?->field?->name }} </td>

                                                    <td> GH&#x20B5; {{ number_format( $catAHold->client?->invoices()->whereMonth('invoice_month', $month->month)->sum('total'),2)  }} </td>
                                                    <td>  {{ $catAHold->client?->invoices()->whereMonth('invoice_month', $month->month)->pluck('status') }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catAHold->paid, 2) }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catAHold->client?->invoices()->whereMonth('invoice_month', $month->month)->sum('total') - $catAHold->paid, 2) }} </td>
                                                
                                                    @if( $catAHold->client?->invoices()->whereMonth('invoice_month', $month->month)->sum('total') <= $catAHold->paid )
                                                        <td><span class="badge bg-label-danger"> Loss </span></td>
                                                    @else
                                                        <td><span class="badge bg-label-success"> Profit </span></td>
                                                    @endif
                                                
                                                    <td> {{ $catAHold->total_employees }} </td>
                                                    <td> 
                                                        <a href="/salariesClientHoldMonth/{{$catAHold->client_id}} / {{$month}} " class="btn btn-dark btn-sm">
                                                            <i class="bx bx-show"></i> 
                                                        </a>    
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade " id="navs-pills-justified-categorybhold" role="tabpanel">
                                    <div class="table-responsive">
                                        <table id="myTablecategorybhold" class="display">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Client Name</th>
                                                    <th>Field</th>
                                                    <th>Inv Value</th>
                                                    <th>Inv Status</th>
                                                    <th>Net Salary</th>
                                                    <th> Difference </th>
                                                    <th>Status</th>
                                                    <th>Employees</th>
                                                    <th> View Employees</th>
                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">
                                                @foreach($clientBHold as $keybh => $catBHold)
                                                <tr>
                                                   <td> {{ $keybh + 1 }} </td>
                                                    <td> {{ $catBHold->client?->name }} {{ $catBHold->client?->business_name }} </td>
                                                    <td> {{ $catBHold->client?->field?->name }} </td>

                                                    <td> GH&#x20B5; {{ number_format( $catBHold->client?->invoices()->whereMonth('invoice_month', $month->month)->sum('total'),2)  }} </td>
                                                    <td>  {{ $catBHold->client?->invoices()->whereMonth('invoice_month', $month->month)->pluck('status') }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catBHold->paid, 2) }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catBHold->client?->invoices()->whereMonth('invoice_month', $month->month)->sum('total') - $catBHold->paid, 2) }} </td>
                                                
                                                    @if( $catBHold->client?->invoices()->whereMonth('invoice_month', $month->month)->sum('total') <= $catBHold->paid )
                                                        <td><span class="badge bg-label-danger"> Loss </span></td>
                                                    @else
                                                        <td><span class="badge bg-label-success"> Profit </span></td>
                                                    @endif
                                                
                                                    <td> {{ $catBHold->total_employees }} </td>
                                                    <td> 
                                                        <a href="/salariesClientHoldMonth/{{$catBHold->client_id}} / {{$month}} " class="btn btn-dark btn-sm">
                                                            <i class="bx bx-show"></i> 
                                                        </a>    
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>


                                <div class="tab-pane fade " id="navs-pills-justified-categorychold" role="tabpanel">
                                    <div class="table-responsive">
                                        <table id="myTablecategorychold" class="display">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Client Name</th>
                                                    <th>Field</th>
                                                    <th>Inv Value</th>
                                                    <th>Inv Status</th>
                                                    <th>Net Salary</th>
                                                    <th> Difference </th>
                                                    <th>Status</th>
                                                    <th>Employees</th>
                                                    <th> View Employees</th>
                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">
                                                @foreach($clientCHold as $keych => $catCHold)
                                                <tr>
                                                   <td> {{ $keych + 1 }} </td>
                                                    <td> {{ $catCHold->client?->name }} {{ $catCHold->client?->business_name }} </td>
                                                    <td> {{ $catCHold->client?->field?->name }} </td>

                                                    <td> GH&#x20B5; {{ number_format( $catCHold->client?->invoices()->whereMonth('invoice_month', $month->month)->sum('total'),2)  }} </td>
                                                    <td>  {{ $catCHold->client?->invoices()->whereMonth('invoice_month', $month->month)->pluck('status') }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catCHold->paid, 2) }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catCHold->client?->invoices()->whereMonth('invoice_month', $month->month)->sum('total') - $catCHold->paid, 2) }} </td>
                                                
                                                    @if( $catCHold->client?->invoices()->whereMonth('invoice_month', $month->month)->sum('total') <= $catCHold->paid )
                                                        <td><span class="badge bg-label-danger"> Loss </span></td>
                                                    @else
                                                        <td><span class="badge bg-label-success"> Profit </span></td>
                                                    @endif
                                                
                                                    <td> {{ $catCHold->total_employees }} </td>
                                                    <td> 
                                                        <a href="/salariesClientHoldMonth/{{$catCHold->client_id}} / {{$month}} " class="btn btn-dark btn-sm">
                                                            <i class="bx bx-show"></i> 
                                                        </a>    
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>


                                <div class="tab-pane fade " id="navs-pills-justified-categorydhold" role="tabpanel">
                                    <div class="table-responsive">
                                        <table id="myTablecategorydhold" class="display">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Client Name</th>
                                                    <th>Field</th>
                                                    <th>Inv Value</th>
                                                    <th>Inv Status</th>
                                                    <th>Net Salary</th>
                                                    <th> Difference </th>
                                                    <th>Status</th>
                                                    <th>Employees</th>
                                                    <th> View Employees</th>
                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">
                                                @foreach($clientDHold as $keydh => $catDHold)
                                                <tr>
                                                   <td> {{ $keydh + 1 }} </td>
                                                    <td> {{ $catDHold->client?->name }} {{ $catDHold->client?->business_name }} </td>
                                                    <td> {{ $catDHold->client?->field?->name }} </td>

                                                    <td> GH&#x20B5; {{ number_format( $catDHold->client?->invoices()->whereMonth('invoice_month', $month->month)->sum('total'),2)  }} </td>
                                                    <td>  {{ $catDHold->client?->invoices()->whereMonth('invoice_month', $month->month)->pluck('status') }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catDHold->paid, 2) }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catDHold->client?->invoices()->whereMonth('invoice_month', $month->month)->sum('total') - $catDHold->paid, 2) }} </td>
                                                
                                                    @if( $catDHold->client?->invoices()->whereMonth('invoice_month', $month->month)->sum('total') <= $catDHold->paid )
                                                        <td><span class="badge bg-label-danger"> Loss </span></td>
                                                    @else
                                                        <td><span class="badge bg-label-success"> Profit </span></td>
                                                    @endif
                                                
                                                    <td> {{ $catDHold->total_employees }} </td>
                                                    <td> 
                                                        <a href="/salariesClientHoldMonth/{{$catDHold->client_id}} / {{$month}} " class="btn btn-dark btn-sm">
                                                            <i class="bx bx-show"></i> 
                                                        </a>    
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            
                                <div class="tab-pane fade " id="navs-pills-justified-clientmasterhold" role="tabpanel">
                                    <div class="table-responsive">
                                        <table id="myTableiclientmasterhold" class="display">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Client Name</th>
                                                    <th>Field</th>
                                                    <th>Inv Value</th>
                                                    <th>Inv Status</th>
                                                    <th>Net Salary</th>
                                                    <th> Difference </th>
                                                    <th>Status</th>
                                                    <th>Employees</th>
                                                    <th> View Employees</th>
                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">
                                                @foreach($salariesClientsHold as $cm => $clientMasterHold)
                                                <tr>
                                                    <td> {{ $cm + 1 }} </td>
                                                    <td> {{ $clientMasterHold->client?->name }} {{ $clientMasterHold->client?->business_name }} </td>
                                                    <td> {{ $clientMasterHold->client?->field?->name }} </td>

                                                    <td> GH&#x20B5; {{ number_format( $clientMasterHold->client?->invoices()->whereMonth('invoice_month', $month->month)->sum('total'),2)  }} </td>
                                                    <td>  {{ $clientMasterHold->client?->invoices()->whereMonth('invoice_month', $month->month)->pluck('status') }} </td>
                                                    <td> GH&#x20B5; {{ number_format($clientMasterHold->paid, 2) }} </td>
                                                    <td> GH&#x20B5; {{ number_format($clientMasterHold->client?->invoices()->whereMonth('invoice_month', $month->month)->sum('total') - $clientMasterHold->paid, 2) }} </td>
                                                
                                                    @if( $clientMasterHold->client?->invoices()->whereMonth('invoice_month', $month->month)->sum('total') <= $clientMasterHold->paid )
                                                        <td><span class="badge bg-label-danger"> Loss </span></td>
                                                    @else
                                                        <td><span class="badge bg-label-success"> Profit </span></td>
                                                    @endif
                                                
                                                    <td> {{ $clientMasterHold->total_employees }} </td>
                                                    <td> 
                                                        <a href="/salariesClientHoldMonth/{{$clientMasterHold->client_id}} / {{$month}} " class="btn btn-dark btn-sm">
                                                            <i class="bx bx-show"></i> 
                                                        </a>    
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

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

    <script src="https://cdn.datatables.net/fixedcolumns/5.0.5/js/dataTables.fixedColumns.js"> </script>           
    <script src="https://cdn.datatables.net/fixedcolumns/5.0.5/js/fixedColumns.dataTables.js"></script>     
    <script src="https://cdn.datatables.net/fixedheader/4.0.5/js/dataTables.fixedHeader.js"></script>      
    <script src="https://cdn.datatables.net/fixedheader/4.0.5/js/fixedHeader.dataTables.js"></script>  
    <script src="https://cdn.datatables.net/columncontrol/1.1.1/js/dataTables.columnControl.min.js"></script>


    <script>
        let myTableiAccra = new DataTable('#myTableiAccra'); 
        let myTableiBotwe = new DataTable('#myTableiBotwe');
        let myTableiShyhills = new DataTable('#myTableiShyhills');
        let myTableiTema = new DataTable('#myTableiTema');
        let myTableiOvertime = new DataTable('#myTableiOvertime');
        let myTableiIou = new DataTable('#myTableiIou');
        let myTableiboot = new DataTable('#myTableiboot');
        let myTableiholdcash = new DataTable('#myTableiholdcash');
        let myTableiholdbank = new DataTable('#myTableiholdbank');

        let myTablecategorya = new DataTable('#myTablecategorya', {
                responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
        }); 
        let myTablecategoryb = new DataTable('#myTablecategoryb', {
                responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
        }); 
        let myTablecategoryc = new DataTable('#myTablecategoryc', {
                responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
        }); 
        let myTablecategoryd = new DataTable('#myTablecategoryd', {
                responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
        }); 

        let myTablecategoryahold = new DataTable('#myTablecategoryahold', {
                responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
        }); 
        let myTablecategorybhold = new DataTable('#myTablecategorybhold', {
                            responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
        }); 
        let myTablecategorychold = new DataTable('#myTablecategorychold', {
                            responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
        }); 
        let myTablecategorydhold = new DataTable('#myTablecategorydhold', {
                            responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
        }); 

        let myTableiclientmasterhold = new DataTable('#myTableiclientmasterhold', {
                responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
                lengthMenu: [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]],
                columnControl: [ ['search'] ]
        }); 

        let myTableiclientmaster = new DataTable('#myTableiclientmaster', {
                responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
                lengthMenu: [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]],
                columnControl: [ ['search'] ]
        }); 


        new DataTable('#myTableimaster', {
                responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
              columnControl: [ ['search'] ],

                        fixedColumns: {
                        start: 0,
                        end: 0
                    },
                    fixedHeader: {
                        header: true,
                        footer: true
                    },
                    paging: false,
                    scrollCollapse: true,
                    scrollX: true,
                    scrollY: 600
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
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
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

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



            <!-- Layout container -->
                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <div id="printContent" class="content-wrapper">
                        <div class="watermarked"> 
                            <div class="container-xxl flex-grow-1 container-p-y">
                                <!-- Invoice 1 - Bootstrap Brain Component -->
                                <section class="py-3 py-md-5">
                                    <div class="row justify-content-center">
                                        <div style="margin-top: -40px;" class="col-12 col-lg-9 col-xl-8 col-xxl-7">
                                            <div class="row gy-3 mb-3">
                                                <div class="col-8">
                                                    <h5 class="text-uppercase text-endx m-0 text-danger"><strong>FIRST WATCH SECURITY SERVICE LIMITED.</strong></h5> <br>
                                                    <h4>  <strong>PAYSLIP FOR : {{ strtoupper($salary->salary_month?->format('F, Y')) }}  </strong> </h4>
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
                                                            @if($salary->employee->tax_button == 'on')
                                                            <h7> TAX   : <strong> GH&#8373; {{ strtoupper($salary?->tax ) }} </strong> </h7>  
                                                            @endif
                                                        @endif
                                                    </address>
                                                </div>
                                            </div>
                                            <hr style="height: 5px; background-color : black"/>
                                            @if($salary->employee->ssnit_button == 'on')
                                            <div class="row mb-3">
                                                <div class="col 8">
                                                    <h5 class="text-danger"> <strong> EARNINGS </strong>  </h5>
                                                    <div class="row">
                                                    <div class="col"> 
                                                    <address>
                                                        <h7> BASIC SALARY </h7> 
                                                        <h7 > ALLOWANCE  </h7>  
                                                        <h7 > SSNIT 5% ADD UP </h7>  
                                                        <h7> OVERTIME </h7>   
                                                        <h7> AIRTIME ALLOWANCE </h7> 
                                                        <h7> REIMBURSEMENT </h7> 
                                                        <h7> TRANSPORTATION ALLOWANCE </h7>
                                                        <h5> <strong>GROSS SALARY </strong>  </h5> 
                                                    </address>
                                                    </div>

                                                    <div class="col text-end">
                                                        <address>
                                                            <h7> <strong>   GH&#8373; {{ number_format($salary->basic_salary, 2)  }} </strong>  </h7> <br> 
                                                            <h7 > <strong>  GH&#8373; {{ number_format($salary->allowances, 2)  }} </strong> </h7>  <br> 
                                                            <h7 > <strong>  GH&#8373; {{ number_format($salary->ssnit_tier2_5, 2) }} </strong> </h7>  <br>
                                                            <h7> <strong>   GH&#8373; {{ number_format($salary->overtime, 2) }} </strong> </h7>   <br>
                                                            <h7> <strong>   GH&#8373; {{ number_format($salary->airtime_allowance, 2) }} </strong>  </h7> <br>
                                                            <h7>  <strong>  GH&#8373; {{ number_format($salary->reimbursements, 2) }} </strong>  </h7> <br>
                                                            <h7>  <strong>  GH&#8373; {{ number_format($salary->transport_allowance, 2) }} </strong>  </h7> <br>
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
                                                        <h7 > ALLOWANCE   <br> 
                                                        <h7 > SSNIT 5% ADD UP  </h7>  <br>
                                                        <h7> OVERTIME   <br>
                                                        <h7> AIRTIME ALLOWANCE  <br>
                                                        <h7> REIMBURSEMENT  <br>
                                                        <h7> TRANSPORTATION ALLOWANCE  <br>
                                                        <h5> <strong>GROSS SALARY </strong>  </h5> 
                                                    </address>

                                                    </div>

                                                    <div class="col text-end">
                                                        <address>
                                                            <h7> <strong>   GH&#8373; {{ number_format($salary->basic_salary, 2)  }} </strong>  </h7> <br> 
                                                            <h7 > <strong>  GH&#8373; {{ number_format($salary->allowances, 2)  }} </strong> </h7>  <br> 
                                                            <h7 ><strong>  GH&#8373; {{ number_format($salary->ssnit_tier2_5, 2) }} </strong> </h7>  <br>
                                                            <h7> <strong> GH&#8373; {{ number_format($salary->overtime, 2) }} </strong> </h7>   <br>
                                                            <h7> <strong>  GH&#8373; {{ number_format($salary->airtime_allowance, 2) }} </strong>  </h7> <br>
                                                            <h7>  <strong>  GH&#8373; {{ number_format($salary->reimbursements, 2) }} </strong>  </h7> <br>
                                                            <h7>  <strong>  GH&#8373; {{ number_format($salary->transport_allowance, 2) }} </strong>  </h7> <br>
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
        

                                            <hr style="height: 5px; background-color : black"/>
                                            <div class="row mb-3">
                                                <div class="col-12 col-sm-12 col-md-12">
                                                    <h5 class="text-danger"> <strong> DEDUCTIONS  </strong>  </h5>
                                                <div class="row"> 

                                                <div class="col">
                                                    <address>
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
                                                        <h7> <strong>   GH&#8373; {{ number_format($salary->welfare, 2)  }} </strong>  </h7> <br> 
                                                        <h7 > <strong>  GH&#8373; {{ number_format($salary->maintenance, 2)  }} </strong> </h7>  <br> 
                                                        <h7 > <strong>  GH&#8373; {{ number_format($salary->absent, 2) }} </strong> </h7>  <br>
                                                        <h7> <strong> GH&#8373; {{ number_format($salary->amnt_ded_cof_start_date, 2) }} </strong> </h7>   <br>
                                                        <h7> <strong>  GH&#8373; {{ number_format($salary->boot, 2) }} </strong>  </h7> <br>
                                                        <h7> <strong>  GH&#8373; {{ number_format($salary->iou, 2) }} </strong>  </h7> <br>
                                                        <h7>  <strong>  GH&#8373; {{ number_format($salary->hostel, 2) }} </strong>  </h7> <br>
                                                        <h7> <strong>  GH&#8373; {{ number_format($salary->insurance, 2) }} </strong>  </h7> <br>
                                                        <h7> <strong>  GH&#8373; {{ number_format($salary->other_deductions, 2) }} </strong>  </h7> <br>
                                                        <h7> <strong>  GH&#8373; {{ number_format($salary->reprimand, 2) }} </strong>  </h7> <br>
                                                        <h7> <strong>  GH&#8373; {{ number_format($salary->raincoat, 2) }} </strong>  </h7> <br>
                                                        <h7> <strong>  GH&#8373; {{ number_format($salary->meal, 2) }} </strong>  </h7> <br>
                                                        <h7>  <strong>  GH&#8373; {{ number_format($salary->loan, 2) }} </strong>  </h7> <br>
                                                        <h7>  <strong>  GH&#8373; {{ number_format($salary->walkin, 2) }} </strong>  </h7> <br>
                                                        <h7> <strong>  GH&#8373; {{ number_format($salary->total_deductions, 2) }} </strong>  </h7> <br>
                                                        
                                                        <h5> <strong>  GH&#8373; {{ number_format($salary->net_salary, 2) }} </strong>  </h5> 
                                                    </address>

                                                </div>
                                                </div>
                                                </div>
                                            </div>

                                            <hr style="height: 5px; background-color : black"/>
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

                                            <hr style="height: 5px; background-color : black"/>
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
<x-sales-dashboard>

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
      <li class="menu-item active open">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-home-smile"></i>
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
      <li class="menu-item">
        <a href="{{url('transaction')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-transfer-alt bg-primary"></i>
          <div class="text-truncate" data-i18n="Transaction">Transactions</div>
        </a>
      </li>

      @if(Auth::user()->hasRole(['Invoice','Finance Manager', 'Director']))
      <li class="menu-item">
        <a href="{{ url('invoice') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-bxs-receipt bg-primary"></i>
          <div class="text-truncate" data-i18n="Invoices">Invoices</div>
        </a>
      </li>
      <li class="menu-item ">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-bxs-receipt bg-primary"></i>
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


      @if(Auth::user()->hasRole(['Finance Manager', 'Manager','Admin Assistant' ]))
            
     <!-- <li class="menu-item">
        <a href="{{url('receipt')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-money-withdraw bg-primary"></i>
          <div class="text-truncate" data-i18n="Receipts">Receipts</div>
        </a>
      </li> -->

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

       @if(Auth::user()->hasRole(['Finance Manager' ]))
      <!-- Components -->
      <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
      <li class="menu-item">
        <a href="{{url('client')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-bxs-user-detail bg-info"></i>
          <div class="text-truncate" data-i18n="Clients">Clients</div>
        </a>
      </li>
      <li class="menu-item ">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bxs-user-account bg-info"></i>
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
          <i class="menu-icon tf-icons bx bxs-category bg-info"></i>
          <div class="text-truncate" data-i18n="Categories">Categories</div>
        </a>
      </li>
      @elseif(Auth::user()->hasRole(['Invoice' ,'Director', 'Manager', 'Admin Assistant']))

      <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
        <li class="menu-item ">
            <a class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-bxs-user-detail bg-info"></i>
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
          <i class="menu-icon tf-icons bx bxs-category bg-info"></i>
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

      <!-- <li class="menu-item">
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
      </li> -->
      @endif

      @if(Auth::user()->hasRole(['Finance Manager', 'Director']) )

      <li class="menu-header small text-uppercase"> <span class="menu-header-text text-danger">Accounts</span></li>

      <li class="menu-item">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bxs-analyse bg-danger"></i>
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

        <div class="row">
            <div class="col-12">
                <h3 class="card-header text-primary"> <i class="icon-base bx bx-bxs-receipt"></i> Invoices Outstanding   @if (isset($month)) <strong> / For Month: {{  \Carbon\Carbon::parse($month)->format('F Y') }}</strong> @endif </h3>
            </div>
        </div><br>

        @if(isset($invoiceTotal) && isset($invoiceCount))
        <div class="row mb-4">
            <div class="col-lg-12 col-md-6 mb-4 mb-md-0">
                <div  class="card h-100 bg-danger text-white">
                    <div class="card-body">
                            <p class="mb-1"><strong> INVOICES </strong> </p>
                            <h4 class="card-title mb-3 text-white"><strong> GH&#x20B5; {{ number_format($invoiceTotal, 2) }}  </strong> </h4>
                            <small class="fw-medium"> TOTAL INVOICES GENERATED : {{ $invoiceCount }}  </small><br>
                            <small class="fw-medium"><strong>  PART PAY'T OUTSTANDING : &#x20B5;{{ number_format($reportPinvoices->sum('balance'),2) }} </strong> </small><br>
                            <small class="fw-medium"> <strong>  PART PAY'T INVOICES : {{ count($reportPinvoices) }} </strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(Auth::user()->hasRole(['Invoice','Finance Manager']))
        <div class="row">
            <div class="col-lg-3">
                <div style="background: #ffe0e0ff; " class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{number_format($accraTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> <strong>  TOTAL INVOICES : {{$accraCount}}</strong> </small> <br>
                        <small class="fw-medium"><strong>  PART PAY'T OUTSTANDING : &#x20B5;{{ number_format($accraPcount->sum('balance'),2) }} </strong> </small><br>
                        <small class="fw-medium"> <strong>  NUMBER OF INVOICES : {{ count($accraPcount) }} </strong> </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-3">
                <div style="background: #ffe0e0ff; " class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{number_format($botweTotal,2)}}</strong> </h4>
                        <small class="fw-medium">  <strong> TOTAL INVOICES : {{$botweCount}} </strong> </small> <br>
                        <small class="fw-medium"><strong>  PART PAY'T OUTSTANDING : &#x20B5;{{ number_format($botwePcount->sum('balance'),2) }} </strong> </small><br>
                        <small class="fw-medium"> <strong> NUMBER OF INVOICES : {{ count($botwePcount) }} </strong>  </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-3">
                <div style="background: #ffe0e0ff; " class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{number_format($shyhillsTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> <strong>TOTAL INVOICES :  {{$shyhillsCount}} </strong> </small><br>
                        <small class="fw-medium"> <strong> PART PAY'T OUTSTANDING : &#x20B5;{{ number_format($shyhillsPcount->sum('balance'),2) }}  </strong>  </small><br>
                        <small class="fw-medium"> <strong> NUMBER OF INVOICES : {{ count($shyhillsPcount) }} </strong>  </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div style="background: #ffe0e0ff; " class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{number_format($temaTotal,2)}} </strong> </h4>
                        <small class="fw-medium"><strong> TOTAL INVOICES :  {{$temaCount}} </strong> </small> <br>
                        <small class="fw-medium"> <strong> PART PAY'T OUTSTANDING : &#x20B5;{{ number_format($temaPcount->sum('balance'),2) }} </strong>  </small><br>
                        <small class="fw-medium"> <strong>  NUMBER OF INVOICES : {{ count($temaPcount) }} </strong> </small>
                    </div>
                </div>
            </div>



            <div class="col-lg-3 m-3">
                <div style="background: #ffe0e0ff; " class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1">TAKORADI</p>
                        <h4 class="card-title mb-3"> <strong>  &#x20B5; {{number_format($takoradiTotal,2)}} </strong></h4>
                        <small class="fw-medium"><strong> TOTAL INVOICES : {{$takoradiCount}} </strong> </small> <br>
                        <small class="fw-medium"> <strong>  PART PAY'T OUTSTANDING : &#x20B5;{{ number_format($takoradiPcount->sum('balance'),2) }}  </strong></small><br>
                        <small class="fw-medium"> <strong>  NUMBER OF INVOICES : {{ count($takoradiPcount) }} </strong> </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 m-3">
                <div style="background: #ffe0e0ff; " class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"> <strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3"><strong> &#x20B5;{{number_format($koforiduaTotal,2)}} </strong></h4>
                        <small class="fw-medium"> <strong>  TOTAL INVOICES : {{$koforiduaCount}} </strong> </small><br>
                        <small class="fw-medium"> <strong> PART PAY'T OUTSTANDING : &#x20B5;{{ number_format($koforiduaPcount->sum('balance'),2) }} </strong>  </small><br>
                        <small class="fw-medium"><strong>  NUMBER OF INVOICES : {{ count($koforiduaPcount) }} </strong> </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-3 m-3">
                <div style="background: #ffe0e0ff; " class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3"> <strong> &#x20B5;{{number_format($kumasiTotal,2)}}  </strong></h4>
                        <small class="fw-medium"> <strong> TOTAL INVOICES : {{$kumasiCount}} </strong> </small> <br>
                        <small class="fw-medium"> <strong>  PART PAY'T OUTSTANDING : &#x20B5;{{ number_format($kumasiPcount->sum('balance'),2) }} </strong> </small><br>
                        <small class="fw-medium"><strong>  NUMBER OF INVOICES : {{ count($kumasiPcount) }} </strong> </small>
                    </div>
                </div>
            </div>

        </div>
        @elseif(Auth::user()->field?->name == 'Accra')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: #ffe0e0ff; " class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{number_format($accraTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$accraCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(Auth::user()->field?->name == 'Botwe')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: #ffe0e0ff; " class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> BOTWE </strong> </p>
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{number_format($botweTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$botweCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(Auth::user()->field?->name == 'Tema')
        <div class="row">
            <div class="col-xxl-6 mb-6 order-0">
                <div style="background: #ffe0e0ff; " class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> SHAIHILLS </strong> </p>
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{number_format($shyhillsTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$shyhillsCount}}</strong> </small>
                    </div>
                </div>
            </div>
            <div class="col-xxl-6 mb-6 order-0">
                <div style="background: #ffe0e0ff; " class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> TEMA </strong> </p>
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{number_format($temaTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$temaCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Takoradi')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: #ffe0e0ff; " class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> TAKORADI </strong> </p>
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{number_format($takoradiTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$takoradiCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Koforidua')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: #ffe0e0ff; " class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{number_format($koforiduaTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$koforiduaCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Kumasi')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: #ffe0e0ff; " class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{number_format($kumasiTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$kumasiCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif
        <br><br>


        <div class="nav-align-top">
            <ul class="nav nav-pills mb-4 nav-fill" role="tablist">

                <li class="nav-item">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-0-30"
                        aria-controls="navs-pills-justified-0-30"
                        aria-selected="true">
                        <span class="d-none d-sm-inline-flex align-items-center">
                            <i class="icon-base bx bx-home icon-sm me-1_5"></i>0-30 Days
                            <span class="badge rounded-pill bg-danger ms-1_5"> 
                               
                                @if(Auth::user()->hasRole(['Invoice','Finance Manager']))
                                    {{ $reportInvoicesAging['0-30 days']->count() }} 
                                 @elseif(Auth::user()->field?->name == 'Accra')
                                    {{ $accraAging['0-30 days']->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Botwe')
                                 {{ $botweAging['0-30 days']->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Tema')
                                 {{ $temaAging['0-30 days']->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Takoradi')
                                 {{ $takoradiAging['0-30 days']->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Koforidua')
                                 {{ $koforiduaAging['0-30 days']->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Kumasi')
                                 {{ $kumasiAging['0-30 days']->count() }}
                                @endif
                            </span>
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
                        data-bs-target="#navs-pills-justified-31-60"
                        aria-controls="navs-pills-justified-31-60"
                        aria-selected="true">
                        <span class="d-none d-sm-inline-flex align-items-center">
                            <i class="icon-base bx bx-home icon-sm me-1_5"></i> 31-60 Days
                            <span class="badge rounded-pill bg-danger ms-1_5">
                                @if(Auth::user()->hasRole(['Invoice','Finance Manager']))
                                    {{ $reportInvoicesAging['31-60 days']->count() }}
                                 @elseif(Auth::user()->field?->name == 'Accra')
                                    {{ $accraAging['31-60 days']->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Botwe')
                                 {{ $botweAging['31-60 days']->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Tema')
                                 {{ $temaAging['31-60 days']->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Takoradi')
                                 {{ $takoradiAging['31-60 days']->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Koforidua')
                                 {{ $koforiduaAging['31-60 days']->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Kumasi')
                                 {{ $kumasiAging['31-60 days']->count() }}
                                @endif
                            </span>
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
                        data-bs-target="#navs-pills-justified-61-90"
                        aria-controls="navs-pills-justified-61-90"
                        aria-selected="true">
                        <span class="d-none d-sm-inline-flex align-items-center">
                            <i class="icon-base bx bx-home icon-sm me-1_5"></i> 61-90 Days
                            <span class="badge rounded-pill bg-danger ms-1_5"> 
                               
                                @if(Auth::user()->hasRole(['Invoice','Finance Manager']))
                                    {{ $reportInvoicesAging['61-90 days']->count() }} 
                                 @elseif(Auth::user()->field?->name == 'Accra')
                                    {{ $accraAging['61-90 days']->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Botwe')
                                 {{ $botweAging['61-90 days']->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Tema')
                                 {{ $temaAging['61-90 days']->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Takoradi')
                                 {{ $takoradiAging['61-90 days']->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Koforidua')
                                 {{ $koforiduaAging['61-90 days']->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Kumasi')
                                 {{ $kumasiAging['61-90 days']->count() }}
                                @endif
                            </span>
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
                        data-bs-target="#navs-pills-justified-90plus"
                        aria-controls="navs-pills-justified-90plus"
                        aria-selected="true">
                        <span class="d-none d-sm-inline-flex align-items-center">
                            <i class="icon-base bx bx-home icon-sm me-1_5"></i>90+ Days
                            <span class="badge rounded-pill bg-danger ms-1_5"> 
                                
                                @if(Auth::user()->hasRole(['Invoice','Finance Manager']))
                                   {{ $reportInvoicesAging['90+ days']->count() }}
                                 @elseif(Auth::user()->field?->name == 'Accra')
                                    {{ $accraAging['90+ days']->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Botwe')
                                 {{ $botweAging['90+ days']->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Tema')
                                 {{ $temaAging['90+ days']->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Takoradi')
                                 {{ $takoradiAging['90+ days']->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Koforidua')
                                 {{ $koforiduaAging['90+ days']->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Kumasi')
                                 {{ $kumasiAging['90+ days']->count() }}
                                @endif
                            </span>
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
                        data-bs-target="#navs-pills-justified-all"
                        aria-controls="navs-pills-justified-all"
                        aria-selected="true">
                        <span class="d-none d-sm-inline-flex align-items-center">
                            <i class="icon-base bx bx-home icon-sm me-1_5"></i>All
                            <span class="badge rounded-pill bg-danger ms-1_5"> 
                               
                                @if(Auth::user()->hasRole(['Invoice','Finance Manager']))
                                   {{ $reportInvoices->count() }} 
                                 @elseif(Auth::user()->field?->name == 'Accra')
                                    {{ $accra->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Botwe')
                                 {{ $botwe->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Tema')
                                 {{ $tema->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Takoradi')
                                 {{ $takoradi->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Koforidua')
                                 {{ $koforidua->count() }}
                                @endif

                                @if(Auth::user()->field?->name == 'Kumasi')
                                 {{ $kumasi->count() }}
                                @endif
                            </span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>


            </ul>
            <div class="tab-content">   

                <div class="tab-pane fade" id="navs-pills-justified-0-30" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                        <table id="myTable030" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Invoice No.</th>
                                    <th>Invoice Month</th>
                                    <th>Client Name</th>
                                    <th>Client Status</th>
                                    <th>Phone No.</th>
                                    <th> Field Office </th>
                                    <th> Staff </th>
                                    <th>Date Created</th>
                                    <th>Due Date</th>
                                    <th>Service </th>
                                    <th> Description</th>
                                    <th> Gurds </th>
                                    <th> Rate </th>
                                    <th> Invoice Value </th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>

                                @if(Auth::user()->hasRole(['Invoice','Finance Manager']))
                                @foreach($reportInvoicesAging['0-30 days'] as $key => $invoice)
                                <tr>
                                        <td>{{$key +1 }}</td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client->name === $invoice->client->business_name)
                                        <td> {{$invoice->client->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                        @endif
                                        <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client->phone_number}} </td>
                                        <td> {{$invoice->client->field->name}} </td>
                                        <td> {{$invoice->user->name}} </td>
                                        <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
                                        </td>
                                </tr>
                                @endforeach
                                @elseif(Auth::user()->field?->name == 'Accra')
                                @foreach($accraAging['0-30 days'] as $key => $invoice)
                                <tr>
                                        <td> {{$key +1 }} </td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client->name === $invoice->client->business_name)
                                        <td> {{$invoice->client->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                        @endif
                                        <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client->phone_number}} </td>
                                        <td> {{$invoice->client->field->name}} </td>
                                        <td> {{$invoice->user->name}} </td>
                                        <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
                                        </td>
                                </tr>
                                @endforeach
                                @endif


                                @if(Auth::user()->field?->name == 'Botwe')
                                @foreach($botweAging['0-30 days'] as $key => $invoice)
                                <tr>
                                        <td> {{$key +1 }} </td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client->name === $invoice->client->business_name)
                                        <td> {{$invoice->client->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                        @endif
                                        <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client->phone_number}} </td>
                                        <td> {{$invoice->client->field->name}} </td>
                                        <td> {{$invoice->user->name}} </td>
                                        <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
                                        </td>
                                </tr>
                                @endforeach
                                @endif


                                @if(Auth::user()->field?->name == 'Tema')
                                @foreach($temaAging['0-30 days'] as $key => $invoice)
                                <tr>
                                        <td>{{$key +1 }}</td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client->name === $invoice->client->business_name)
                                        <td> {{$invoice->client->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                        @endif
                                        <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client->phone_number}} </td>
                                        <td> {{$invoice->client->field->name}} </td>
                                        <td> {{$invoice->user->name}} </td>
                                        <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
                                        </td>
                                </tr>
                                @endforeach
                                @endif


                                @if(Auth::user()->field?->name == 'Takoradi')
                                @foreach($takoradiAging['0-30 days'] as $key => $invoice)
                                <tr>
                                        <td>{{$key +1 }}</td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client->name === $invoice->client->business_name)
                                        <td> {{$invoice->client->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                        @endif
                                        <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client->phone_number}} </td>
                                        <td> {{$invoice->client->field->name}} </td>
                                        <td> {{$invoice->user->name}} </td>
                                        <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
                                        </td>
                                </tr>
                                @endforeach
                                @endif


                                @if(Auth::user()->field?->name == 'Koforidua')
                                @foreach($koforiduaAging['0-30 days'] as $key => $invoice)
                                <tr>
                                        <td>{{$key +1 }}</td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client->name === $invoice->client->business_name)
                                        <td> {{$invoice->client->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                        @endif
                                        <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client->phone_number}} </td>
                                        <td> {{$invoice->client->field->name}} </td>
                                        <td> {{$invoice->user->name}} </td>
                                        <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
                                        </td>
                                </tr>
                                @endforeach
                                @endif


                                @if(Auth::user()->field?->name == 'Kumasi')
                                @foreach($kumasiAging['0-30 days'] as $key => $invoice)
                                <tr>
                                        <td>{{$key +1 }}</td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client->name === $invoice->client->business_name)
                                        <td> {{$invoice->client->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                        @endif
                                        <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client->phone_number}} </td>
                                        <td> {{$invoice->client->field->name}} </td>
                                        <td> {{$invoice->user->name}} </td>
                                        <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
                                        </td>
                                </tr>
                                @endforeach
                                @endif


                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="navs-pills-justified-31-60" role="tabpanel">
                        <div class="table-responsive text-nowrap">
                            <table id="myTable3160" class="display">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Invoice No.</th>
                                        <th>Invoice Month</th>
                                        <th>Client Name</th>
                                        <th>Client Status</th>
                                        <th>Phone No.</th>
                                        <th> Field Office </th>
                                        <th> Staff </th>
                                        <th>Date Created</th>
                                        <th>Due Date</th>
                                        <th>Service </th>
                                        <th> Description</th>
                                        <th> Gurds </th>
                                        <th> Rate </th>
                                        <th> Invoice Value </th>
                                        <th>Paid</th>
                                        <th>Balance</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @if(Auth::user()->hasRole(['Invoice','Finance Manager']))
                                    @foreach($reportInvoicesAging['31-60 days'] as $key => $invoice)
                                    <tr>
                                            <td>{{$key +1 }}</td>
                                            <td> FWSSi{{$invoice->id}} </td>
                                            <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                            @if ($invoice->client->name === $invoice->client->business_name)
                                            <td> {{$invoice->client->business_name}} </td>
                                            @else
                                            <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                            @endif
                                            <td>
                                                @if($invoice->client?->status == 'terminated')
                                                <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                                @else
                                                <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                                @endif
                                            </td>
                                            <td> {{$invoice->client->phone_number}} </td>
                                            <td> {{$invoice->client->field->name}} </td>
                                            <td> {{$invoice->user->name}} </td>
                                            <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                            <td> {{$invoice->due_date->diffForHumans()}} </td>
                                        
                                        
                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                    @foreach ( $invoice->invoice_data as $num => $service )
                                                        {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                    @endforeach
                                                @else
                                                    @foreach ( $invoice->invoice_data as $service )
                                                        {{ $service->service_name }} 
                                                    @endforeach
                                                @endif

                                            </td>
                                        
                                        
                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $des => $description )
                                                
                                                    {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                @else
                                                @foreach ( $invoice->invoice_data as $description )
                                                
                                                    {{ $description->description }}
                                                    
                                                @endforeach
                                            
                                                @endif
                                            </td>

                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $guad => $guards )
                                                
                                                    {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                </ol>
                                                @else
                                                @foreach ( $invoice->invoice_data as $guards )
                                                
                                                    {{ $guards->quantity }} 
                                                    
                                                @endforeach
                                            
                                                </ol>
                                                @endif


                                            </td>

                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $rat => $rate )
                                                
                                                    {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                @else
                                                @foreach ( $invoice->invoice_data as $rate )
                                                
                                                    GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                    
                                                @endforeach
                                            
                                                @endif

                                            </td>

                                            <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                            @if ($invoice->status == 'completed')
                                            <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                            @elseif($invoice->status == 'uncompleted')
                                            <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                            @else
                                            <td> GH&#x20B5; 0.00 </td>
                                            @endif

                                            @if ($invoice->status == 'unpaid')
                                            <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                            @else
                                            <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                            @endif


                                            @if($invoice->status == 'completed')
                                            <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                            @else
                                            <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                            @endif
                                            <td>
                                                <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                    <i class="icon-base bx bxs-bullseye"></i>
                                                </a>
                                            </td>
                                    </tr>
                                    @endforeach
                                    @elseif(Auth::user()->field?->name == 'Accra')
                                    @foreach($accraAging['31-60 days'] as $key => $invoice)
                                    <tr>
                                            <td>{{$key +1 }}</td>
                                            <td> FWSSi{{$invoice->id}} </td>
                                            <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                            @if ($invoice->client->name === $invoice->client->business_name)
                                            <td> {{$invoice->client->business_name}} </td>
                                            @else
                                            <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                            @endif
                                            <td>
                                                @if($invoice->client?->status == 'terminated')
                                                <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                                @else
                                                <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                                @endif
                                            </td>
                                            <td> {{$invoice->client->phone_number}} </td>
                                            <td> {{$invoice->client->field->name}} </td>
                                            <td> {{$invoice->user->name}} </td>
                                            <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                            <td> {{$invoice->due_date->diffForHumans()}} </td>
                                        
                                        
                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                    @foreach ( $invoice->invoice_data as $num => $service )
                                                        {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                    @endforeach
                                                @else
                                                    @foreach ( $invoice->invoice_data as $service )
                                                        {{ $service->service_name }} 
                                                    @endforeach
                                                @endif

                                            </td>
                                        
                                        
                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $des => $description )
                                                
                                                    {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                @else
                                                @foreach ( $invoice->invoice_data as $description )
                                                
                                                    {{ $description->description }}
                                                    
                                                @endforeach
                                            
                                                @endif
                                            </td>

                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $guad => $guards )
                                                
                                                    {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                </ol>
                                                @else
                                                @foreach ( $invoice->invoice_data as $guards )
                                                
                                                    {{ $guards->quantity }} 
                                                    
                                                @endforeach
                                            
                                                </ol>
                                                @endif


                                            </td>

                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $rat => $rate )
                                                
                                                    {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                @else
                                                @foreach ( $invoice->invoice_data as $rate )
                                                
                                                    GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                    
                                                @endforeach
                                            
                                                @endif

                                            </td>

                                            <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                            @if ($invoice->status == 'completed')
                                            <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                            @elseif($invoice->status == 'uncompleted')
                                            <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                            @else
                                            <td> GH&#x20B5; 0.00 </td>
                                            @endif

                                            @if ($invoice->status == 'unpaid')
                                            <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                            @else
                                            <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                            @endif


                                            @if($invoice->status == 'completed')
                                            <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                            @else
                                            <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                            @endif
                                            <td>
                                                <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                    <i class="icon-base bx bxs-bullseye"></i>
                                                </a>
                                            </td>
                                    </tr>
                                    @endforeach
                                    @endif


                                    @if(Auth::user()->field?->name == 'Botwe')
                                    @foreach($botweAging['31-60 days'] as $key => $invoice)
                                    <tr>
                                            <td>{{$key +1 }}</td>
                                            <td> FWSSi{{$invoice->id}} </td>
                                            <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                            @if ($invoice->client->name === $invoice->client->business_name)
                                            <td> {{$invoice->client->business_name}} </td>
                                            @else
                                            <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                            @endif
                                            <td>
                                                @if($invoice->client?->status == 'terminated')
                                                <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                                @else
                                                <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                                @endif
                                            </td>
                                            <td> {{$invoice->client->phone_number}} </td>
                                            <td> {{$invoice->client->field->name}} </td>
                                            <td> {{$invoice->user->name}} </td>
                                            <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                            <td> {{$invoice->due_date->diffForHumans()}} </td>
                                        
                                        
                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                    @foreach ( $invoice->invoice_data as $num => $service )
                                                        {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                    @endforeach
                                                @else
                                                    @foreach ( $invoice->invoice_data as $service )
                                                        {{ $service->service_name }} 
                                                    @endforeach
                                                @endif

                                            </td>
                                        
                                        
                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $des => $description )
                                                
                                                    {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                @else
                                                @foreach ( $invoice->invoice_data as $description )
                                                
                                                    {{ $description->description }}
                                                    
                                                @endforeach
                                            
                                                @endif
                                            </td>

                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $guad => $guards )
                                                
                                                    {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                </ol>
                                                @else
                                                @foreach ( $invoice->invoice_data as $guards )
                                                
                                                    {{ $guards->quantity }} 
                                                    
                                                @endforeach
                                            
                                                </ol>
                                                @endif


                                            </td>

                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $rat => $rate )
                                                
                                                    {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                @else
                                                @foreach ( $invoice->invoice_data as $rate )
                                                
                                                    GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                    
                                                @endforeach
                                            
                                                @endif

                                            </td>

                                            <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                            @if ($invoice->status == 'completed')
                                            <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                            @elseif($invoice->status == 'uncompleted')
                                            <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                            @else
                                            <td> GH&#x20B5; 0.00 </td>
                                            @endif

                                            @if ($invoice->status == 'unpaid')
                                            <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                            @else
                                            <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                            @endif


                                            @if($invoice->status == 'completed')
                                            <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                            @else
                                            <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                            @endif
                                            <td>
                                                <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                    <i class="icon-base bx bxs-bullseye"></i>
                                                </a>
                                            </td>
                                    </tr>
                                    @endforeach
                                    @endif


                                    @if(Auth::user()->field?->name == 'Tema')
                                    @foreach($temaAging['31-60 days'] as $key => $invoice)
                                    <tr>
                                            <td>{{$key +1 }}</td>
                                            <td> FWSSi{{$invoice->id}} </td>
                                            <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                            @if ($invoice->client->name === $invoice->client->business_name)
                                            <td> {{$invoice->client->business_name}} </td>
                                            @else
                                            <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                            @endif
                                            <td>
                                                @if($invoice->client?->status == 'terminated')
                                                <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                                @else
                                                <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                                @endif
                                            </td>
                                            <td> {{$invoice->client->phone_number}} </td>
                                            <td> {{$invoice->client->field->name}} </td>
                                            <td> {{$invoice->user->name}} </td>
                                            <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                            <td> {{$invoice->due_date->diffForHumans()}} </td>
                                        
                                        
                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                    @foreach ( $invoice->invoice_data as $num => $service )
                                                        {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                    @endforeach
                                                @else
                                                    @foreach ( $invoice->invoice_data as $service )
                                                        {{ $service->service_name }} 
                                                    @endforeach
                                                @endif

                                            </td>
                                        
                                        
                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $des => $description )
                                                
                                                    {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                @else
                                                @foreach ( $invoice->invoice_data as $description )
                                                
                                                    {{ $description->description }}
                                                    
                                                @endforeach
                                            
                                                @endif
                                            </td>

                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $guad => $guards )
                                                
                                                    {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                </ol>
                                                @else
                                                @foreach ( $invoice->invoice_data as $guards )
                                                
                                                    {{ $guards->quantity }} 
                                                    
                                                @endforeach
                                            
                                                </ol>
                                                @endif


                                            </td>

                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $rat => $rate )
                                                
                                                    {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                @else
                                                @foreach ( $invoice->invoice_data as $rate )
                                                
                                                    GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                    
                                                @endforeach
                                            
                                                @endif

                                            </td>

                                            <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                            @if ($invoice->status == 'completed')
                                            <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                            @elseif($invoice->status == 'uncompleted')
                                            <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                            @else
                                            <td> GH&#x20B5; 0.00 </td>
                                            @endif

                                            @if ($invoice->status == 'unpaid')
                                            <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                            @else
                                            <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                            @endif


                                            @if($invoice->status == 'completed')
                                            <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                            @else
                                            <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                            @endif
                                            <td>
                                                <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                    <i class="icon-base bx bxs-bullseye"></i>
                                                </a>
                                            </td>
                                    </tr>
                                    @endforeach
                                    @endif


                                    @if(Auth::user()->field?->name == 'Takoradi')
                                    @foreach($takoradiAging['31-60 days'] as $key => $invoice)
                                    <tr>
                                            <td>{{$key +1 }}</td>
                                            <td> FWSSi{{$invoice->id}} </td>
                                            <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                            @if ($invoice->client->name === $invoice->client->business_name)
                                            <td> {{$invoice->client->business_name}} </td>
                                            @else
                                            <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                            @endif
                                            <td>
                                                @if($invoice->client?->status == 'terminated')
                                                <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                                @else
                                                <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                                @endif
                                            </td>
                                            <td> {{$invoice->client->phone_number}} </td>
                                            <td> {{$invoice->client->field->name}} </td>
                                            <td> {{$invoice->user->name}} </td>
                                            <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                            <td> {{$invoice->due_date->diffForHumans()}} </td>
                                        
                                        
                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                    @foreach ( $invoice->invoice_data as $num => $service )
                                                        {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                    @endforeach
                                                @else
                                                    @foreach ( $invoice->invoice_data as $service )
                                                        {{ $service->service_name }} 
                                                    @endforeach
                                                @endif

                                            </td>
                                        
                                        
                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $des => $description )
                                                
                                                    {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                @else
                                                @foreach ( $invoice->invoice_data as $description )
                                                
                                                    {{ $description->description }}
                                                    
                                                @endforeach
                                            
                                                @endif
                                            </td>

                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $guad => $guards )
                                                
                                                    {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                </ol>
                                                @else
                                                @foreach ( $invoice->invoice_data as $guards )
                                                
                                                    {{ $guards->quantity }} 
                                                    
                                                @endforeach
                                            
                                                </ol>
                                                @endif


                                            </td>

                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $rat => $rate )
                                                
                                                    {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                @else
                                                @foreach ( $invoice->invoice_data as $rate )
                                                
                                                    GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                    
                                                @endforeach
                                            
                                                @endif

                                            </td>

                                            <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                            @if ($invoice->status == 'completed')
                                            <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                            @elseif($invoice->status == 'uncompleted')
                                            <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                            @else
                                            <td> GH&#x20B5; 0.00 </td>
                                            @endif

                                            @if ($invoice->status == 'unpaid')
                                            <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                            @else
                                            <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                            @endif


                                            @if($invoice->status == 'completed')
                                            <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                            @else
                                            <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                            @endif
                                            <td>
                                                <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                    <i class="icon-base bx bxs-bullseye"></i>
                                                </a>
                                            </td>
                                    </tr>
                                    @endforeach
                                    @endif


                                    @if(Auth::user()->field?->name == 'Koforidua')
                                    @foreach($koforiduaAging['31-60 days'] as $key => $invoice)
                                    <tr>
                                            <td>{{$key +1 }}</td>
                                            <td> FWSSi{{$invoice->id}} </td>
                                            <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                            @if ($invoice->client->name === $invoice->client->business_name)
                                            <td> {{$invoice->client->business_name}} </td>
                                            @else
                                            <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                            @endif
                                            <td>
                                                @if($invoice->client?->status == 'terminated')
                                                <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                                @else
                                                <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                                @endif
                                            </td>
                                            <td> {{$invoice->client->phone_number}} </td>
                                            <td> {{$invoice->client->field->name}} </td>
                                            <td> {{$invoice->user->name}} </td>
                                            <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                            <td> {{$invoice->due_date->diffForHumans()}} </td>
                                        
                                        
                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                    @foreach ( $invoice->invoice_data as $num => $service )
                                                        {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                    @endforeach
                                                @else
                                                    @foreach ( $invoice->invoice_data as $service )
                                                        {{ $service->service_name }} 
                                                    @endforeach
                                                @endif

                                            </td>
                                        
                                        
                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $des => $description )
                                                
                                                    {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                @else
                                                @foreach ( $invoice->invoice_data as $description )
                                                
                                                    {{ $description->description }}
                                                    
                                                @endforeach
                                            
                                                @endif
                                            </td>

                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $guad => $guards )
                                                
                                                    {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                </ol>
                                                @else
                                                @foreach ( $invoice->invoice_data as $guards )
                                                
                                                    {{ $guards->quantity }} 
                                                    
                                                @endforeach
                                            
                                                </ol>
                                                @endif


                                            </td>

                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $rat => $rate )
                                                
                                                    {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                @else
                                                @foreach ( $invoice->invoice_data as $rate )
                                                
                                                    GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                    
                                                @endforeach
                                            
                                                @endif

                                            </td>

                                            <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                            @if ($invoice->status == 'completed')
                                            <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                            @elseif($invoice->status == 'uncompleted')
                                            <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                            @else
                                            <td> GH&#x20B5; 0.00 </td>
                                            @endif

                                            @if ($invoice->status == 'unpaid')
                                            <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                            @else
                                            <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                            @endif


                                            @if($invoice->status == 'completed')
                                            <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                            @else
                                            <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                            @endif
                                            <td>
                                                <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                    <i class="icon-base bx bxs-bullseye"></i>
                                                </a>
                                            </td>
                                    </tr>
                                    @endforeach
                                    @endif


                                    @if(Auth::user()->field?->name == 'Kumasi')
                                    @foreach($kumasiAging['31-60 days'] as $key => $invoice)
                                    <tr>
                                            <td>{{$key +1 }}</td>
                                            <td> FWSSi{{$invoice->id}} </td>
                                            <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                            @if ($invoice->client->name === $invoice->client->business_name)
                                            <td> {{$invoice->client->business_name}} </td>
                                            @else
                                            <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                            @endif
                                            <td>
                                                @if($invoice->client?->status == 'terminated')
                                                <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                                @else
                                                <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                                @endif
                                            </td>
                                            <td> {{$invoice->client->phone_number}} </td>
                                            <td> {{$invoice->client->field->name}} </td>
                                            <td> {{$invoice->user->name}} </td>
                                            <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                            <td> {{$invoice->due_date->diffForHumans()}} </td>
                                        
                                        
                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                    @foreach ( $invoice->invoice_data as $num => $service )
                                                        {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                    @endforeach
                                                @else
                                                    @foreach ( $invoice->invoice_data as $service )
                                                        {{ $service->service_name }} 
                                                    @endforeach
                                                @endif

                                            </td>
                                        
                                        
                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $des => $description )
                                                
                                                    {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                @else
                                                @foreach ( $invoice->invoice_data as $description )
                                                
                                                    {{ $description->description }}
                                                    
                                                @endforeach
                                            
                                                @endif
                                            </td>

                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $guad => $guards )
                                                
                                                    {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                </ol>
                                                @else
                                                @foreach ( $invoice->invoice_data as $guards )
                                                
                                                    {{ $guards->quantity }} 
                                                    
                                                @endforeach
                                            
                                                </ol>
                                                @endif


                                            </td>

                                            <td>
                                                @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $rat => $rate )
                                                
                                                    {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                    
                                                @endforeach
                                            
                                                @else
                                                @foreach ( $invoice->invoice_data as $rate )
                                                
                                                    GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                    
                                                @endforeach
                                            
                                                @endif

                                            </td>

                                            <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                            @if ($invoice->status == 'completed')
                                            <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                            @elseif($invoice->status == 'uncompleted')
                                            <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                            @else
                                            <td> GH&#x20B5; 0.00 </td>
                                            @endif

                                            @if ($invoice->status == 'unpaid')
                                            <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                            @else
                                            <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                            @endif


                                            @if($invoice->status == 'completed')
                                            <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                            @else
                                            <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                            @endif
                                            <td>
                                                <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                    <i class="icon-base bx bxs-bullseye"></i>
                                                </a>
                                            </td>
                                    </tr>
                                    @endforeach
                                    @endif


                                </tbody>
                            </table>
                        </div>
                </div>


                <div class="tab-pane fade" id="navs-pills-justified-61-90" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                        <table id="myTable6190" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Invoice No.</th>
                                    <th>Invoice Month</th>
                                    <th>Client Name</th>
                                    <th>Client Status</th>
                                    <th>Phone No.</th>
                                    <th> Field Office </th>
                                    <th> Staff </th>
                                    <th>Date Created</th>
                                    <th>Due Date</th>
                                    <th>Service </th>
                                    <th> Description</th>
                                    <th> Gurds </th>
                                    <th> Rate </th>
                                    <th> Invoice Value </th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>

                                @if(Auth::user()->hasRole(['Invoice','Finance Manager']))
                                @foreach($reportInvoicesAging['61-90 days'] as $key => $invoice)
                                <tr>
                                        <td>{{$key +1 }}</td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client->name === $invoice->client->business_name)
                                        <td> {{$invoice->client->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                        @endif
                                        <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client->phone_number}} </td>
                                        <td> {{$invoice->client->field->name}} </td>
                                        <td> {{$invoice->user->name}} </td>
                                        <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
                                        </td>
                                </tr>
                                @endforeach
                                @elseif(Auth::user()->field?->name == 'Accra')
                                @foreach($accraAging['61-90 days'] as $key => $invoice)
                                <tr>
                                        <td>{{$key +1 }}</td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client->name === $invoice->client->business_name)
                                        <td> {{$invoice->client->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                        @endif
                                        <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client->phone_number}} </td>
                                        <td> {{$invoice->client->field->name}} </td>
                                        <td> {{$invoice->user->name}} </td>
                                        <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
                                        </td>
                                </tr>
                                @endforeach
                                @endif


                                @if(Auth::user()->field?->name == 'Botwe')
                                @foreach($botweAging['61-90 days'] as $key => $invoice)
                                <tr>
                                        <td>{{$key +1 }}</td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client->name === $invoice->client->business_name)
                                        <td> {{$invoice->client->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                        @endif
                                        <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client->phone_number}} </td>
                                        <td> {{$invoice->client->field->name}} </td>
                                        <td> {{$invoice->user->name}} </td>
                                        <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
                                        </td>
                                </tr>
                                @endforeach
                                @endif


                                @if(Auth::user()->field?->name == 'Tema')
                                @foreach($temaAging['61-90 days'] as $key => $invoice)
                                <tr>
                                        <td>{{$key +1 }}</td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client->name === $invoice->client->business_name)
                                        <td> {{$invoice->client->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                        @endif
                                        <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client->phone_number}} </td>
                                        <td> {{$invoice->client->field->name}} </td>
                                        <td> {{$invoice->user->name}} </td>
                                        <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
                                        </td>
                                </tr>
                                @endforeach
                                @endif


                                @if(Auth::user()->field?->name == 'Takoradi')
                                @foreach($takoradiAging['61-90 days'] as $key => $invoice)
                                <tr>
                                        <td>{{$key +1 }}</td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client->name === $invoice->client->business_name)
                                        <td> {{$invoice->client->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                        @endif
                                        <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client->phone_number}} </td>
                                        <td> {{$invoice->client->field->name}} </td>
                                        <td> {{$invoice->user->name}} </td>
                                        <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
                                        </td>
                                </tr>
                                @endforeach
                                @endif


                                @if(Auth::user()->field?->name == 'Koforidua')
                                @foreach($koforiduaAging['61-90 days'] as $key => $invoice)
                                <tr>
                                        <td>{{$key +1 }}</td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client->name === $invoice->client->business_name)
                                        <td> {{$invoice->client->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                        @endif
                                        <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client->phone_number}} </td>
                                        <td> {{$invoice->client->field->name}} </td>
                                        <td> {{$invoice->user->name}} </td>
                                        <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
                                        </td>
                                </tr>
                                @endforeach
                                @endif


                                @if(Auth::user()->field?->name == 'Kumasi')
                                @foreach($kumasiAging['61-90 days'] as $key => $invoice)
                                <tr>
                                        <td>{{$key +1 }}</td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client->name === $invoice->client->business_name)
                                        <td> {{$invoice->client->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                        @endif
                                        <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client->phone_number}} </td>
                                        <td> {{$invoice->client->field->name}} </td>
                                        <td> {{$invoice->user->name}} </td>
                                        <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
                                        </td>
                                </tr>
                                @endforeach
                                @endif


                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="navs-pills-justified-90plus" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                    <table id="myTable90" class="display">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Invoice No.</th>
                                <th>Invoice Month</th>
                                <th>Client Name</th>
                                <th>Client Status</th>
                                <th>Phone No.</th>
                                <th> Field Office </th>
                                <th> Staff </th>
                                <th>Date Created</th>
                                <th>Due Date</th>
                                <th>Service </th>
                                <th> Description</th>
                                <th> Gurds </th>
                                <th> Rate </th>
                                <th> Invoice Value </th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                            @if(Auth::user()->hasRole(['Invoice','Finance Manager']))
                            @foreach($reportInvoicesAging['90+ days'] as $key => $invoice)
                            <tr>
                                    <td>{{$key +1 }}</td>
                                    <td> FWSSi{{$invoice->id}} </td>
                                    <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                    @if ($invoice->client->name === $invoice->client->business_name)
                                    <td> {{$invoice->client->business_name}} </td>
                                    @else
                                    <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                    @endif

                                    <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                    </td>
                                    <td> {{$invoice->client->phone_number}} </td>
                                    <td> {{$invoice->client->field->name}} </td>
                                    <td> {{$invoice->user->name}} </td>
                                    <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                    <td> {{$invoice->due_date->diffForHumans()}} </td>
                                
                                
                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $num => $service )
                                                {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                            @endforeach
                                        @else
                                            @foreach ( $invoice->invoice_data as $service )
                                                {{ $service->service_name }} 
                                            @endforeach
                                        @endif

                                    </td>
                                
                                
                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $des => $description )
                                        
                                            {{  $des +1  }}. {{ $description->description }} <br> <br>
                                            
                                        @endforeach
                                    
                                        @else
                                        @foreach ( $invoice->invoice_data as $description )
                                        
                                            {{ $description->description }}
                                            
                                        @endforeach
                                    
                                        @endif
                                    </td>

                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $guad => $guards )
                                        
                                            {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                            
                                        @endforeach
                                    
                                        </ol>
                                        @else
                                        @foreach ( $invoice->invoice_data as $guards )
                                        
                                            {{ $guards->quantity }} 
                                            
                                        @endforeach
                                    
                                        </ol>
                                        @endif


                                    </td>

                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $rat => $rate )
                                        
                                            {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                            
                                        @endforeach
                                    
                                        @else
                                        @foreach ( $invoice->invoice_data as $rate )
                                        
                                            GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                            
                                        @endforeach
                                    
                                        @endif

                                    </td>

                                    <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                    @if ($invoice->status == 'completed')
                                    <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                    @elseif($invoice->status == 'uncompleted')
                                    <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                    @else
                                    <td> GH&#x20B5; 0.00 </td>
                                    @endif

                                    @if ($invoice->status == 'unpaid')
                                    <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                    @else
                                    <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                    @endif


                                    @if($invoice->status == 'completed')
                                    <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                    @else
                                    <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                    @endif
                                    <td>
                                        <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                            <i class="icon-base bx bxs-bullseye"></i>
                                        </a>
                                    </td>
                            </tr>
                            @endforeach
                            @elseif(Auth::user()->field?->name == 'Accra')
                            @foreach($accraAging['90+ days'] as $key => $invoice)
                            <tr>
                                    <td>{{$key +1 }}</td>
                                    <td> FWSSi{{$invoice->id}} </td>
                                    <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                    @if ($invoice->client->name === $invoice->client->business_name)
                                    <td> {{$invoice->client->business_name}} </td>
                                    @else
                                    <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                    @endif

                                    <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                    </td>
                                    <td> {{$invoice->client->phone_number}} </td>
                                    <td> {{$invoice->client->field->name}} </td>
                                    <td> {{$invoice->user->name}} </td>
                                    <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                    <td> {{$invoice->due_date->diffForHumans()}} </td>
                                
                                
                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $num => $service )
                                                {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                            @endforeach
                                        @else
                                            @foreach ( $invoice->invoice_data as $service )
                                                {{ $service->service_name }} 
                                            @endforeach
                                        @endif

                                    </td>
                                
                                
                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $des => $description )
                                        
                                            {{  $des +1  }}. {{ $description->description }} <br> <br>
                                            
                                        @endforeach
                                    
                                        @else
                                        @foreach ( $invoice->invoice_data as $description )
                                        
                                            {{ $description->description }}
                                            
                                        @endforeach
                                    
                                        @endif
                                    </td>

                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $guad => $guards )
                                        
                                            {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                            
                                        @endforeach
                                    
                                        </ol>
                                        @else
                                        @foreach ( $invoice->invoice_data as $guards )
                                        
                                            {{ $guards->quantity }} 
                                            
                                        @endforeach
                                    
                                        </ol>
                                        @endif


                                    </td>

                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $rat => $rate )
                                        
                                            {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                            
                                        @endforeach
                                    
                                        @else
                                        @foreach ( $invoice->invoice_data as $rate )
                                        
                                            GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                            
                                        @endforeach
                                    
                                        @endif

                                    </td>

                                    <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                    @if ($invoice->status == 'completed')
                                    <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                    @elseif($invoice->status == 'uncompleted')
                                    <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                    @else
                                    <td> GH&#x20B5; 0.00 </td>
                                    @endif

                                    @if ($invoice->status == 'unpaid')
                                    <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                    @else
                                    <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                    @endif


                                    @if($invoice->status == 'completed')
                                    <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                    @else
                                    <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                    @endif
                                    <td>
                                        <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                            <i class="icon-base bx bxs-bullseye"></i>
                                        </a>
                                    </td>
                            </tr>
                            @endforeach
                            @endif


                            @if(Auth::user()->field?->name == 'Botwe')
                            @foreach($botweAging['90+ days'] as $key => $invoice)
                            <tr>
                                    <td>{{$key +1 }}</td>
                                    <td> FWSSi{{$invoice->id}} </td>
                                    <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                    @if ($invoice->client->name === $invoice->client->business_name)
                                    <td> {{$invoice->client->business_name}} </td>
                                    @else
                                    <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                    @endif

                                    <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                    </td>
                                    <td> {{$invoice->client->phone_number}} </td>
                                    <td> {{$invoice->client->field->name}} </td>
                                    <td> {{$invoice->user->name}} </td>
                                    <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                    <td> {{$invoice->due_date->diffForHumans()}} </td>
                                
                                
                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $num => $service )
                                                {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                            @endforeach
                                        @else
                                            @foreach ( $invoice->invoice_data as $service )
                                                {{ $service->service_name }} 
                                            @endforeach
                                        @endif

                                    </td>
                                
                                
                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $des => $description )
                                        
                                            {{  $des +1  }}. {{ $description->description }} <br> <br>
                                            
                                        @endforeach
                                    
                                        @else
                                        @foreach ( $invoice->invoice_data as $description )
                                        
                                            {{ $description->description }}
                                            
                                        @endforeach
                                    
                                        @endif
                                    </td>

                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $guad => $guards )
                                        
                                            {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                            
                                        @endforeach
                                    
                                        </ol>
                                        @else
                                        @foreach ( $invoice->invoice_data as $guards )
                                        
                                            {{ $guards->quantity }} 
                                            
                                        @endforeach
                                    
                                        </ol>
                                        @endif


                                    </td>

                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $rat => $rate )
                                        
                                            {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                            
                                        @endforeach
                                    
                                        @else
                                        @foreach ( $invoice->invoice_data as $rate )
                                        
                                            GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                            
                                        @endforeach
                                    
                                        @endif

                                    </td>

                                    <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                    @if ($invoice->status == 'completed')
                                    <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                    @elseif($invoice->status == 'uncompleted')
                                    <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                    @else
                                    <td> GH&#x20B5; 0.00 </td>
                                    @endif

                                    @if ($invoice->status == 'unpaid')
                                    <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                    @else
                                    <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                    @endif


                                    @if($invoice->status == 'completed')
                                    <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                    @else
                                    <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                    @endif
                                    <td>
                                        <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                            <i class="icon-base bx bxs-bullseye"></i>
                                        </a>
                                    </td>
                            </tr>
                            @endforeach
                            @endif


                            @if(Auth::user()->field?->name == 'Tema')
                            @foreach($temaAging['90+ days'] as $key => $invoice)
                            <tr>
                                    <td>{{$key +1 }}</td>
                                    <td> FWSSi{{$invoice->id}} </td>
                                    <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                    @if ($invoice->client->name === $invoice->client->business_name)
                                    <td> {{$invoice->client->business_name}} </td>
                                    @else
                                    <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                    @endif

                                    <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                    </td>
                                    <td> {{$invoice->client->phone_number}} </td>
                                    <td> {{$invoice->client->field->name}} </td>
                                    <td> {{$invoice->user->name}} </td>
                                    <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                    <td> {{$invoice->due_date->diffForHumans()}} </td>
                                
                                
                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $num => $service )
                                                {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                            @endforeach
                                        @else
                                            @foreach ( $invoice->invoice_data as $service )
                                                {{ $service->service_name }} 
                                            @endforeach
                                        @endif

                                    </td>
                                
                                
                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $des => $description )
                                        
                                            {{  $des +1  }}. {{ $description->description }} <br> <br>
                                            
                                        @endforeach
                                    
                                        @else
                                        @foreach ( $invoice->invoice_data as $description )
                                        
                                            {{ $description->description }}
                                            
                                        @endforeach
                                    
                                        @endif
                                    </td>

                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $guad => $guards )
                                        
                                            {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                            
                                        @endforeach
                                    
                                        </ol>
                                        @else
                                        @foreach ( $invoice->invoice_data as $guards )
                                        
                                            {{ $guards->quantity }} 
                                            
                                        @endforeach
                                    
                                        </ol>
                                        @endif


                                    </td>

                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $rat => $rate )
                                        
                                            {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                            
                                        @endforeach
                                    
                                        @else
                                        @foreach ( $invoice->invoice_data as $rate )
                                        
                                            GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                            
                                        @endforeach
                                    
                                        @endif

                                    </td>

                                    <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                    @if ($invoice->status == 'completed')
                                    <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                    @elseif($invoice->status == 'uncompleted')
                                    <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                    @else
                                    <td> GH&#x20B5; 0.00 </td>
                                    @endif

                                    @if ($invoice->status == 'unpaid')
                                    <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                    @else
                                    <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                    @endif


                                    @if($invoice->status == 'completed')
                                    <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                    @else
                                    <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                    @endif
                                    <td>
                                        <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                            <i class="icon-base bx bxs-bullseye"></i>
                                        </a>
                                    </td>
                            </tr>
                            @endforeach
                            @endif


                            @if(Auth::user()->field?->name == 'Takoradi')
                            @foreach($takoradiAging['90+ days'] as $key => $invoice)
                            <tr>
                                    <td>{{$key +1 }}</td>
                                    <td> FWSSi{{$invoice->id}} </td>
                                    <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                    @if ($invoice->client->name === $invoice->client->business_name)
                                    <td> {{$invoice->client->business_name}} </td>
                                    @else
                                    <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                    @endif

                                    <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                    </td>
                                    <td> {{$invoice->client->phone_number}} </td>
                                    <td> {{$invoice->client->field->name}} </td>
                                    <td> {{$invoice->user->name}} </td>
                                    <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                    <td> {{$invoice->due_date->diffForHumans()}} </td>
                                
                                
                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $num => $service )
                                                {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                            @endforeach
                                        @else
                                            @foreach ( $invoice->invoice_data as $service )
                                                {{ $service->service_name }} 
                                            @endforeach
                                        @endif

                                    </td>
                                
                                
                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $des => $description )
                                        
                                            {{  $des +1  }}. {{ $description->description }} <br> <br>
                                            
                                        @endforeach
                                    
                                        @else
                                        @foreach ( $invoice->invoice_data as $description )
                                        
                                            {{ $description->description }}
                                            
                                        @endforeach
                                    
                                        @endif
                                    </td>

                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $guad => $guards )
                                        
                                            {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                            
                                        @endforeach
                                    
                                        </ol>
                                        @else
                                        @foreach ( $invoice->invoice_data as $guards )
                                        
                                            {{ $guards->quantity }} 
                                            
                                        @endforeach
                                    
                                        </ol>
                                        @endif


                                    </td>

                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $rat => $rate )
                                        
                                            {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                            
                                        @endforeach
                                    
                                        @else
                                        @foreach ( $invoice->invoice_data as $rate )
                                        
                                            GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                            
                                        @endforeach
                                    
                                        @endif

                                    </td>

                                    <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                    @if ($invoice->status == 'completed')
                                    <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                    @elseif($invoice->status == 'uncompleted')
                                    <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                    @else
                                    <td> GH&#x20B5; 0.00 </td>
                                    @endif

                                    @if ($invoice->status == 'unpaid')
                                    <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                    @else
                                    <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                    @endif


                                    @if($invoice->status == 'completed')
                                    <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                    @else
                                    <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                    @endif
                                    <td>
                                        <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                            <i class="icon-base bx bxs-bullseye"></i>
                                        </a>
                                    </td>
                            </tr>
                            @endforeach
                            @endif


                            @if(Auth::user()->field?->name == 'Koforidua')
                            @foreach($koforiduaAging['90+ days'] as $key => $invoice)
                            <tr>
                                    <td>{{$key +1 }}</td>
                                    <td> FWSSi{{$invoice->id}} </td>
                                    <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                    @if ($invoice->client->name === $invoice->client->business_name)
                                    <td> {{$invoice->client->business_name}} </td>
                                    @else
                                    <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                    @endif

                                    <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                    </td>
                                    <td> {{$invoice->client->phone_number}} </td>
                                    <td> {{$invoice->client->field->name}} </td>
                                    <td> {{$invoice->user->name}} </td>
                                    <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                    <td> {{$invoice->due_date->diffForHumans()}} </td>
                                
                                
                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $num => $service )
                                                {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                            @endforeach
                                        @else
                                            @foreach ( $invoice->invoice_data as $service )
                                                {{ $service->service_name }} 
                                            @endforeach
                                        @endif

                                    </td>
                                
                                
                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $des => $description )
                                        
                                            {{  $des +1  }}. {{ $description->description }} <br> <br>
                                            
                                        @endforeach
                                    
                                        @else
                                        @foreach ( $invoice->invoice_data as $description )
                                        
                                            {{ $description->description }}
                                            
                                        @endforeach
                                    
                                        @endif
                                    </td>

                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $guad => $guards )
                                        
                                            {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                            
                                        @endforeach
                                    
                                        </ol>
                                        @else
                                        @foreach ( $invoice->invoice_data as $guards )
                                        
                                            {{ $guards->quantity }} 
                                            
                                        @endforeach
                                    
                                        </ol>
                                        @endif


                                    </td>

                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $rat => $rate )
                                        
                                            {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                            
                                        @endforeach
                                    
                                        @else
                                        @foreach ( $invoice->invoice_data as $rate )
                                        
                                            GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                            
                                        @endforeach
                                    
                                        @endif

                                    </td>

                                    <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                    @if ($invoice->status == 'completed')
                                    <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                    @elseif($invoice->status == 'uncompleted')
                                    <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                    @else
                                    <td> GH&#x20B5; 0.00 </td>
                                    @endif

                                    @if ($invoice->status == 'unpaid')
                                    <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                    @else
                                    <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                    @endif


                                    @if($invoice->status == 'completed')
                                    <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                    @else
                                    <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                    @endif
                                    <td>
                                        <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                            <i class="icon-base bx bxs-bullseye"></i>
                                        </a>
                                    </td>
                            </tr>
                            @endforeach
                            @endif


                            @if(Auth::user()->field?->name == 'Kumasi')
                            @foreach($kumasiAging['90+ days'] as $key => $invoice)
                            <tr>
                                    <td>{{$key +1 }}</td>
                                    <td> FWSSi{{$invoice->id}} </td>
                                    <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                    @if ($invoice->client->name === $invoice->client->business_name)
                                    <td> {{$invoice->client->business_name}} </td>
                                    @else
                                    <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                    @endif

                                    <td>
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                    </td>
                                    <td> {{$invoice->client->phone_number}} </td>
                                    <td> {{$invoice->client->field->name}} </td>
                                    <td> {{$invoice->user->name}} </td>
                                    <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                    <td> {{$invoice->due_date->diffForHumans()}} </td>
                                
                                
                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $num => $service )
                                                {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                            @endforeach
                                        @else
                                            @foreach ( $invoice->invoice_data as $service )
                                                {{ $service->service_name }} 
                                            @endforeach
                                        @endif

                                    </td>
                                
                                
                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $des => $description )
                                        
                                            {{  $des +1  }}. {{ $description->description }} <br> <br>
                                            
                                        @endforeach
                                    
                                        @else
                                        @foreach ( $invoice->invoice_data as $description )
                                        
                                            {{ $description->description }}
                                            
                                        @endforeach
                                    
                                        @endif
                                    </td>

                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $guad => $guards )
                                        
                                            {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                            
                                        @endforeach
                                    
                                        </ol>
                                        @else
                                        @foreach ( $invoice->invoice_data as $guards )
                                        
                                            {{ $guards->quantity }} 
                                            
                                        @endforeach
                                    
                                        </ol>
                                        @endif


                                    </td>

                                    <td>
                                        @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $rat => $rate )
                                        
                                            {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                            
                                        @endforeach
                                    
                                        @else
                                        @foreach ( $invoice->invoice_data as $rate )
                                        
                                            GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                            
                                        @endforeach
                                    
                                        @endif

                                    </td>

                                    <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                    @if ($invoice->status == 'completed')
                                    <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                    @elseif($invoice->status == 'uncompleted')
                                    <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                    @else
                                    <td> GH&#x20B5; 0.00 </td>
                                    @endif

                                    @if ($invoice->status == 'unpaid')
                                    <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                    @else
                                    <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                    @endif


                                    @if($invoice->status == 'completed')
                                    <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                    @else
                                    <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                    @endif
                                    <td>
                                        <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                            <i class="icon-base bx bxs-bullseye"></i>
                                        </a>
                                    </td>
                            </tr>
                            @endforeach
                            @endif


                        </tbody>
                    </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="navs-pills-justified-all" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                    <table id="myTableall" class="display">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Invoice No.</th>
                                <th>Invoice Month</th>
                                <th>Client Name</th>
                                <th>Client Status</th>
                                <th>Phone No.</th>
                                <th> Field Office </th>
                                <th> Staff </th>
                                <th>Date Created</th>
                                <th>Due Date</th>
                                <th>Service </th>
                                <th> Description</th>
                                <th> Gurds </th>
                                <th> Rate </th>
                                <th> Invoice Value </th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                            @if(Auth::user()->hasRole(['Invoice','Finance Manager']))
                                @foreach($reportInvoices as $key => $invoice)
                                <tr>
                                        <td>{{$key +1 }}</td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client?->name === $invoice->client?->business_name)
                                        <td> {{$invoice->client?->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client?->name}} {{$invoice->client?->business_name}} </td>
                                        @endif
                                        <td> 
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client?->phone_number}} </td>
                                        <td> {{$invoice->client?->field?->name}} </td>
                                        <td> {{$invoice->user?->name}} </td>
                                        <td> {{$invoice->created_at?->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date?->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
                                        </td>
                                </tr>
                                @endforeach
                            @elseif(Auth::user()->field?->name == 'Accra')
                                @foreach($accra as $key => $invoice)
                                <tr>
                                        <td>{{$key +1 }}</td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client?->name === $invoice->client?->business_name)
                                        <td> {{$invoice->client?->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client?->name}} {{$invoice->client?->business_name}} </td>
                                        @endif
                                        <td> 
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client?->phone_number}} </td>
                                        <td> {{$invoice->client?->field?->name}} </td>
                                        <td> {{$invoice->user?->name}} </td>
                                        <td> {{$invoice->created_at?->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date?->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
                                        </td>
                                </tr>
                                @endforeach
                            @endif


                            @if(Auth::user()->field?->name == 'Botwe')
                                @foreach($botwe as $key => $invoice)
                                <tr>
                                        <td>{{$key +1 }}</td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client?->name === $invoice->client?->business_name)
                                        <td> {{$invoice->client?->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client?->name}} {{$invoice->client?->business_name}} </td>
                                        @endif
                                        <td> 
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client?->phone_number}} </td>
                                        <td> {{$invoice->client?->field?->name}} </td>
                                        <td> {{$invoice->user?->name}} </td>
                                        <td> {{$invoice->created_at?->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date?->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
                                        </td>
                                </tr>
                                @endforeach
                            @endif


                            @if(Auth::user()->field?->name == 'Tema')
                                @foreach($tema as $key => $invoice)
                                <tr>
                                        <td>{{$key +1 }}</td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client?->name === $invoice->client?->business_name)
                                        <td> {{$invoice->client?->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client?->name}} {{$invoice->client?->business_name}} </td>
                                        @endif
                                        <td> 
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client?->phone_number}} </td>
                                        <td> {{$invoice->client?->field?->name}} </td>
                                        <td> {{$invoice->user?->name}} </td>
                                        <td> {{$invoice->created_at?->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date?->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
                                        </td>
                                </tr>
                                @endforeach
                            @endif


                            @if(Auth::user()->field?->name == 'Takoradi')
                                @foreach($takoradi as $key => $invoice)
                                <tr>
                                        <td>{{$key +1 }}</td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client?->name === $invoice->client?->business_name)
                                        <td> {{$invoice->client?->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client?->name}} {{$invoice->client?->business_name}} </td>
                                        @endif
                                        <td> 
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client?->phone_number}} </td>
                                        <td> {{$invoice->client?->field?->name}} </td>
                                        <td> {{$invoice->user?->name}} </td>
                                        <td> {{$invoice->created_at?->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date?->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
                                        </td>
                                </tr>
                                @endforeach
                            @endif


                            @if(Auth::user()->field?->name == 'Koforidua')
                                @foreach($koforidua as $key => $invoice)
                                <tr>
                                        <td>{{$key +1 }}</td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client?->name === $invoice->client?->business_name)
                                        <td> {{$invoice->client?->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client?->name}} {{$invoice->client?->business_name}} </td>
                                        @endif
                                        <td> 
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client?->phone_number}} </td>
                                        <td> {{$invoice->client?->field?->name}} </td>
                                        <td> {{$invoice->user?->name}} </td>
                                        <td> {{$invoice->created_at?->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date?->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
                                        </td>
                                </tr>   
                                @endforeach
                            @endif


                            @if(Auth::user()->field?->name == 'Kumasi')
                                @foreach($kumasi as $key => $invoice)
                                <tr>
                                        <td>{{$key +1 }}</td>
                                        <td> FWSSi{{$invoice->id}} </td>
                                        <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                        @if ($invoice->client?->name === $invoice->client?->business_name)
                                        <td> {{$invoice->client?->business_name}} </td>
                                        @else
                                        <td> {{$invoice->client?->name}} {{$invoice->client?->business_name}} </td>
                                        @endif
                                        <td> 
                                            @if($invoice->client?->status == 'terminated')
                                            <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                            @else
                                            <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                            @endif
                                        </td>
                                        <td> {{$invoice->client?->phone_number}} </td>
                                        <td> {{$invoice->client?->field?->name}} </td>
                                        <td> {{$invoice->user?->name}} </td>
                                        <td> {{$invoice->created_at?->format('F l d, Y, H:i A')}} </td>
                                        <td> {{$invoice->due_date?->diffForHumans()}} </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                                @foreach ( $invoice->invoice_data as $num => $service )
                                                    {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                                @endforeach
                                            @else
                                                @foreach ( $invoice->invoice_data as $service )
                                                    {{ $service->service_name }} 
                                                @endforeach
                                            @endif

                                        </td>
                                    
                                    
                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $des => $description )
                                            
                                                {{  $des +1  }}. {{ $description->description }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $description )
                                            
                                                {{ $description->description }}
                                                
                                            @endforeach
                                        
                                            @endif
                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $guad => $guards )
                                            
                                                {{  $guad +1  }}.  {{ $guards->quantity }} <br> <br>
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @else
                                            @foreach ( $invoice->invoice_data as $guards )
                                            
                                                {{ $guards->quantity }} 
                                                
                                            @endforeach
                                        
                                            </ol>
                                            @endif


                                        </td>

                                        <td>
                                            @if (count($invoice->invoice_data) > 1)
                                            @foreach ( $invoice->invoice_data as $rat => $rate )
                                            
                                                {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                                
                                            @endforeach
                                        
                                            @else
                                            @foreach ( $invoice->invoice_data as $rate )
                                            
                                                GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                                
                                            @endforeach
                                        
                                            @endif

                                        </td>

                                        <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                        @if ($invoice->status == 'completed')
                                        <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                        @elseif($invoice->status == 'uncompleted')
                                        <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                        @else
                                        <td> GH&#x20B5; 0.00 </td>
                                        @endif

                                        @if ($invoice->status == 'unpaid')
                                        <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                        @else
                                        <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                        @endif


                                        @if($invoice->status == 'completed')
                                        <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                        @else
                                        <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                        @endif
                                        <td>
                                            <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                                <i class="icon-base bx bxs-bullseye"></i>
                                            </a>
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

    <br><br><br><br>
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


        new DataTable('#myTableall', {
            responsive: true,

            layout: {
              topStart: {
                buttons: [ 
                {
                     extend: 'pageLength',
                    text: 'Show',
                    className: 'btn btn-secondary',
                    Options: [10, 25, 50, 100, 500], 
                },
                    {
                        extend: 'excelHtml5',
                        title: 'Receipts',
                        className: 'btn btn-secondary',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    'colvis'
                ]
            }

            },
            columnControl: [
                ['search']
            ]
        });


        new DataTable('#myTable030', {
            responsive: true,

            layout: {
              topStart: {
                buttons: [ 
                {
                     extend: 'pageLength',
                    text: 'Show',
                    className: 'btn btn-secondary',
                    Options: [10, 25, 50, 100, 500], 
                },
                    {
                        extend: 'excelHtml5',
                        title: 'Receipts',
                        className: 'btn btn-secondary',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    'colvis'
                ]
            }

            },
            columnControl: [
                ['search']
            ]
        });


        new DataTable('#myTable3160', {
            responsive: true,

            layout: {
              topStart: {
                buttons: [ 
                {
                     extend: 'pageLength',
                    text: 'Show',
                    className: 'btn btn-secondary',
                    Options: [10, 25, 50, 100, 500], 
                },
                    {
                        extend: 'excelHtml5',
                        title: 'Receipts',
                        className: 'btn btn-secondary',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    'colvis'
                ]
            }

            },
            columnControl: [
                ['search']
            ]
        });

        new DataTable('#myTable6190', {
            responsive: true,

            layout: {
              topStart: {
                buttons: [ 
                {
                     extend: 'pageLength',
                    text: 'Show',
                    className: 'btn btn-secondary',
                    Options: [10, 25, 50, 100, 500], 
                },
                    {
                        extend: 'excelHtml5',
                        title: 'Receipts',
                        className: 'btn btn-secondary',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    'colvis'
                ]
            }

            },
            columnControl: [
                ['search']
            ]
        });


        new DataTable('#myTable90', {
            responsive: true,

            layout: {
              topStart: {
                buttons: [ 
                {
                     extend: 'pageLength',
                    text: 'Show',
                    className: 'btn btn-secondary',
                    Options: [10, 25, 50, 100, 500], 
                },
                    {
                        extend: 'excelHtml5',
                        title: 'Receipts',
                        className: 'btn btn-secondary',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    'colvis'
                ]
            }

            },
            columnControl: [
                ['search']
            ]
        });

    </script>


    @endsection

</x-sales-dashboard>
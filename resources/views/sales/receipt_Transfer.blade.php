<x-sales-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">
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
      <!-- Components -->
      <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
      <li class="menu-item">
        <a href="{{url('client')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-bxs-user-detail bg-info"></i>
          <div class="text-truncate" data-i18n="Clients">Clients</div>
        </a>
      </li>
      @elseif(Auth::user()->hasRole(['Invoice']))

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
            </ul>
        </li>
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

      @if(Auth::user()->hasRole(['Manager','Officer','Finance Manager']) )

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

      @if(Auth::user()->hasPermission('Accounts') )
      <li class="menu-header small text-uppercase"><span class="menu-header-text">PAYROLL</span></li>
        <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                <div class="text-truncate" data-i18n="Payroll">Payroll</div>
                </a>
                <ul class="menu-sub">
                @if(Auth::user()->hasRole(['Invoice']))
                <li class="menu-item">
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
                    <a href="{{ url('salariesInvPayroll') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-git-compare"></i>
                    <div class="text-truncate" data-i18n="InvtoPayroll">Invoice to Payroll</div>
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
                <h3 class="card-header text-primary"> <i class="icon-base bx bx-bxs-receipt"></i> Transfer Payment </h3>
            </div>
        </div><br>

        @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))
        <div class="row">
            <div class="col-lg-2">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> ACCRA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($accraTotal, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$accraCount}}</strong> </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> BOTWE </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($botweTotal,2)}}</strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$botweCount}} </strong> </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> SHAIHILLS </strong></p>
                        <h4 class="card-title mb-3 text-white"> <strong>&#x20B5;{{number_format($shyhillsTotal, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$shyhillsCount}} </strong> </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> TEMA </strong></p>
                        <h4 class="card-title mb-3 text-white"> <strong>&#x20B5;{{number_format($temaTotal, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$temaCount}} </strong> </small>
                    </div>
                </div>
            </div>



            <div class="col-lg-2">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1">TAKORADI</p>
                        <h4 class="card-title mb-3 text-white"> <strong> &#x20B5; {{number_format($takoradiTotal,2)}} </strong></h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : {{$takoradiCount}} </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"> <strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($koforiduaTotal,2)}}</strong></h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$koforiduaCount}} </strong> </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2 m-3">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($kumasiTotal,2)}}</strong></h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$kumasiCount}} </strong> </small>
                    </div>
                </div>
            </div>

        </div>
        @elseif(Auth::user()->field?->name == 'Accra')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> ACCRA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($accraTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$accraCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(Auth::user()->field?->name == 'Botwe')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> BOTWE </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($botweTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$botweCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(Auth::user()->field?->name == 'Tema')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> TEMA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($temaTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$temaCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Takoradi')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> TAKORADI </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($takoradiTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$takoradiCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Koforidua')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($koforiduaTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$koforiduaCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Kumasi')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($kumasiTotal, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$kumasiCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif
        <br><br>

        <div class="row">
            <div class="col">
                <table id="myTable" class="display">
                    <thead>
                        <tr>
                            <th>id</th>
                            <th>Invoice No.</th>
                            <th>Client Name</th>
                            <th>Phone No.</th>
                            <th>Business Name </th>
                            <th> Field Office </th>
                            <th> Staff </th>
                            <th>Date Created</th>
                            <th>Paid</th>
                            <th>Status</th>
                            <th>View</th>
                        </tr>
                    </thead>

                    <tbody>

                        @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))
                        @foreach($transferReceipt as $receipt)
                        <tr>
                            <td>FWSSR{{$receipt->id}}</td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> {{$receipt->client->name}}</td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->business_name}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>
                            <!-- <td>GH&#x20B5; {{$receipt->invoice->total}} </td> -->

                            <td> GH&#x20B5; {{$receipt->total}} </td>


                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

                            <td>
                                <a href="{{url('receipt', $receipt->id)}}" class="btn btn-info">
                                    <i class="icon-base bx bxs-bullseye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @elseif(Auth::user()->field?->name == 'Accra')
                        @foreach($accra as $receipt)
                        <tr>
                            <td>FWSSR{{$receipt->id}}</td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> {{$receipt->client->name}}</td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->business_name}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>
                            <!-- <td>GH&#x20B5; {{$receipt->invoice->total}} </td> -->

                            <td> GH&#x20B5; {{$receipt->total}} </td>


                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

                            <td>
                                <a href="{{url('receipt', $receipt->id)}}" class="btn btn-info">
                                    <i class="icon-base bx bxs-bullseye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @endif


                        @if(Auth::user()->field?->name == 'Botwe')
                        @foreach($botwe as $receipt)
                        <tr>
                            <td>FWSSR{{$receipt->id}}</td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> {{$receipt->client->name}}</td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->business_name}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>
                            <!-- <td>GH&#x20B5; {{$receipt->invoice->total}} </td> -->

                            <td> GH&#x20B5; {{$receipt->total}} </td>


                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

                            <td>
                                <a href="{{url('receipt', $receipt->id)}}" class="btn btn-info">
                                    <i class="icon-base bx bxs-bullseye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @endif


                        @if(Auth::user()->field?->name == 'Tema')
                        @foreach($tema as $receipt)
                        <tr>
                            <td>FWSSR{{$receipt->id}}</td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> {{$receipt->client->name}}</td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->business_name}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>
                            <!-- <td>GH&#x20B5; {{$receipt->invoice->total}} </td> -->

                            <td> GH&#x20B5; {{$receipt->total}} </td>


                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

                            <td>
                                <a href="{{url('receipt', $receipt->id)}}" class="btn btn-info">
                                    <i class="icon-base bx bxs-bullseye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @endif


                        @if(Auth::user()->field?->name == 'Takoradi')
                        @foreach($takoradi as $receipt)
                        <tr>
                            <td>FWSSR{{$receipt->id}}</td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> {{$receipt->client->name}}</td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->business_name}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>
                            <!-- <td>GH&#x20B5; {{$receipt->invoice->total}} </td> -->

                            <td> GH&#x20B5; {{$receipt->total}} </td>


                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

                            <td>
                                <a href="{{url('receipt', $receipt->id)}}" class="btn btn-info">
                                    <i class="icon-base bx bxs-bullseye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @endif


                        @if(Auth::user()->field?->name == 'Koforidua')
                        @foreach($koforidua as $receipt)
                        <tr>
                            <td>FWSSR{{$receipt->id}}</td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> {{$receipt->client->name}}</td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->business_name}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>
                            <!-- <td>GH&#x20B5; {{$receipt->invoice->total}} </td> -->

                            <td> GH&#x20B5; {{$receipt->total}} </td>


                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

                            <td>
                                <a href="{{url('receipt', $receipt->id)}}" class="btn btn-info">
                                    <i class="icon-base bx bxs-bullseye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @endif


                        @if(Auth::user()->field?->name == 'Kumasi')
                        @foreach($kumasi as $receipt)
                        <tr>
                            <td>FWSSR{{$receipt->id}}</td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> {{$receipt->client->name}}</td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->business_name}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>
                            <!-- <td>GH&#x20B5; {{$receipt->invoice->total}} </td> -->

                            <td> GH&#x20B5; {{$receipt->total}} </td>


                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

                            <td>
                                <a href="{{url('receipt', $receipt->id)}}" class="btn btn-info">
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


    <script>
        new DataTable('#myTable', {
            responsive: true,

            layout: {
                topStart: {
                    buttons: ['excelHtml5', 'pdfHtml5']
                }
            }
        });
    </script>


    @endsection

</x-sales-dashboard>
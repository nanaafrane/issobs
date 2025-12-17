<x-sales-dashboard>

    @section('css')
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
                    <div class="text-truncate" data-i18n="Dashboards"><strong>Dashboard</strong> </div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item">
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
            <li class="menu-item active">
                <a href="{{url('transaction')}}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-transfer-alt"></i>
                    <div class="text-truncate" data-i18n="Transaction"><strong>Transactions</strong> </div>
                </a>
            </li>
            @if(Auth::user()->hasRole(['Invoice','Finance Manager']))
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
            <div class="col-6">
                <h3 class="card-header text-info"> <i class="icon-base bx bx-bxs-receipt"></i> Transactions {{ Auth::user()->field->name }} </h3>
            </div>
        </div><br>
        <br>
        <!-- end of cards -->


        <div class="row g-6">
            <div class="col-xl-12">
                <div class="nav-align-top">
                    <ul class="nav nav-pills mb-4 nav-fill" role="tablist">

                        @if(Auth::user()->field?->name == 'Accra' || Auth::user()->hasRole(['Finance Manager', 'Invoice']) )
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
                                    <i class="icon-base bx bx-home icon-sm me-1_5"></i>Accra
                                    <span class="badge rounded-pill bg-danger ms-1_5">{{$count_accraTransactions}}</span>
                                </span>
                                <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                            </button>
                        </li>
                        @endif

                        @if(Auth::user()->field?->name == 'Botwe' || Auth::user()->hasRole(['Finance Manager', 'Invoice']) )
                        <li class="nav-item">
                            <button
                                type="button"
                                class="nav-link"
                                role="tab"
                                data-bs-toggle="tab"
                                data-bs-target="#navs-pills-justified-botwe"
                                aria-controls="navs-pills-justified-botwe"
                                aria-selected="false">
                                <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>Botwe
                                    <span class="badge rounded-pill bg-danger ms-1_5">{{$count_botweTransactions}}</span>
                                </span>
                                <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                            </button>
                        </li>
                        @endif

                        @if(Auth::user()->field?->name == 'Tema' || Auth::user()->hasRole(['Finance Manager', 'Invoice']) )
                        <li class="nav-item mb-1 mb-sm-0">
                            <button
                                type="button"
                                class="nav-link"
                                role="tab"
                                data-bs-toggle="tab"
                                data-bs-target="#navs-pills-justified-shyhills"
                                aria-controls="navs-pills-justified-shyhills"
                                aria-selected="false">
                                <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>Shy Hills
                                    <span class="badge rounded-pill bg-danger ms-1_5">{{$count_shyhillsTransactions}}</span>
                                </span>
                                <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                            </button>
                        </li>
                        @endif

                        @if(Auth::user()->field?->name == 'Tema' || Auth::user()->hasRole(['Finance Manager', 'Invoice']) )
                        <li class="nav-item mb-1 mb-sm-0">
                            <button
                                type="button"
                                class="nav-link"
                                role="tab"
                                data-bs-toggle="tab"
                                data-bs-target="#navs-pills-justified-tema"
                                aria-controls="navs-pills-justified-tema"
                                aria-selected="false">
                                <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>Tema
                                    <span class="badge rounded-pill bg-danger ms-1_5">{{$count_temaTransactions}}</span>
                                </span>
                                <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                            </button>
                        </li>
                        @endif

                        @if(Auth::user()->field?->name == 'Takoradi' || Auth::user()->hasRole(['Finance Manager','Invoice' ]) )
                        <li class="nav-item">
                            <button
                                type="button"
                                class="nav-link"
                                role="tab"
                                data-bs-toggle="tab"
                                data-bs-target="#navs-pills-justified-takoradi"
                                aria-controls="navs-pills-justified-takoradi"
                                aria-selected="false">
                                <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>Takoradi
                                    <span class="badge rounded-pill bg-danger ms-1_5">{{$count_takoradiTransactions}}</span>
                                </span>
                                <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                            </button>
                        </li>
                        @endif

                        @if(Auth::user()->field?->name == 'Koforidua' || Auth::user()->hasRole(['Finance Manager','Invoice']) )
                        <li class="nav-item">
                            <button
                                type="button"
                                class="nav-link"
                                role="tab"
                                data-bs-toggle="tab"
                                data-bs-target="#navs-pills-justified-koforidua"
                                aria-controls="navs-pills-justified-koforidua"
                                aria-selected="false">
                                <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>Koforidua
                                    <span class="badge rounded-pill bg-danger ms-1_5">{{$count_koforiduaTransactions}}</span>
                                </span>
                                <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                            </button>
                        </li>
                        @endif

                        @if(Auth::user()->field?->name == 'Kumasi' || Auth::user()->hasRole(['Finance Manager', 'Invoice']))
                        <li class="nav-item">
                            <button
                                type="button"
                                class="nav-link"
                                role="tab"
                                data-bs-toggle="tab"
                                data-bs-target="#navs-pills-justified-kumasi"
                                aria-controls="navs-pills-justified-kumasi"
                                aria-selected="false">
                                <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>Kumasi
                                    <span class="badge rounded-pill bg-danger ms-1_5">{{$count_kumasiTransactions}}</span>
                                </span>
                                <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                            </button>
                        </li>
                        @endif

                    </ul>


                    <div class="tab-content">
                        @if(Auth::user()->field?->name == 'Accra')
                        <div class="tab-pane fade show active" id="navs-pills-justified-accra" role="tabpanel">
                            <div class="table-responsive">
                                <table id="myTableiAccra" class="display">
                                    <thead>
                                        <tr>
                                            <!-- <th>#</th> -->
                                            <th>Invoice No.</th>
                                            <th>Receipt No.</th>
                                            <th>Client Name</th>
                                            <th>Business Name </th>
                                            <th>Invoice Amount </th>
                                            <th>Receipt Amount</th>
                                            <th> Date </th>
                                            <th>Balance</th>
                                            <th> Status </th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        @foreach($accraTransactions as $accra)

                                        <tr>
                                            <td> FWSSi{{$accra->invoice_id}} </td>
                                            <td> FWSSR{{$accra->receipt_id}} </td>
                                            <td> {{$accra->client->name}} </td>
                                            <td> {{$accra->client->business_name}} </td>
                                            <td> GH&#8373; {{$accra->invoice_amount}} </td>
                                            <td> GH&#8373;{{$accra->receipt_amount}} </td>
                                            <td> {{$accra->created_at->diffForHumans()}} </td>
                                            <td> {{$accra->balance}} </td>
                                            @if($accra->status == 'completed')
                                            <td> <span class="badge bg-label-success"> {{$accra->status}} </span> </td>
                                            @else
                                            <td> <span class="badge bg-label-danger"> {{$accra->status}} </span> </td>
                                            @endif
                                        </tr>

                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif


                        <div class="tab-pane fade" id="navs-pills-justified-botwe" role="tabpanel">

                            <div class="table-responsive text-nowrap">
                                <table id="myTableiBotwe" class="display">
                                    <thead>
                                        <tr>
                                            <!-- <th>#</th> -->
                                            <th>Invoice No.</th>
                                            <th>Receipt No.</th>
                                            <th>Client Name</th>
                                            <th>Business Name </th>
                                            <th>Invoice Amount </th>
                                            <th>Receipt Amount</th>
                                            <th>Date</th>
                                            <th>Balance</th>
                                            <th> Status </th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">

                                        @foreach($botweTransactions as $botwe)
                                        <tr>
                                            <td> FWSSi{{$botwe->invoice_id}} </td>
                                            <td> FWSSR{{$botwe->receipt_id}} </td>
                                            <td> {{$botwe->client->name}} </td>
                                            <td> {{$botwe->client->business_name}} </td>
                                            <td> GH&#8373; {{$botwe->invoice_amount}} </td>
                                            <td> GH&#8373;{{$botwe->receipt_amount}} </td>
                                            <td> {{$botwe->created_at->diffForHumans()}} </td>
                                            <td> {{$botwe->balance}} </td>
                                            @if($botwe->status == 'completed')
                                            <td> <span class="badge bg-label-success"> {{$botwe->status}} </span> </td>
                                            @else
                                            <td> <span class="badge bg-label-danger"> {{$botwe->status}} </span> </td>
                                            @endif
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
                                            <!-- <th>#</th> -->
                                            <th>Invoice No.</th>
                                            <th>Receipt No.</th>
                                            <th>Client Name</th>
                                            <th>Business Name </th>
                                            <th>Invoice Amount </th>
                                            <th>Receipt Amount</th>
                                            <th> Date </th>
                                            <th>Balance</th>
                                            <th> Status </th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        @foreach($shyhillsTransactions as $shyhills)
                                        <tr>
                                            <td> FWSSi{{$shyhills->invoice_id}} </td>
                                            <td> FWSSR{{$shyhills->receipt_id}} </td>
                                            <td> {{$shyhills->client->name}} </td>
                                            <td> {{$shyhills->client->business_name}} </td>
                                            <td> GH&#8373; {{$shyhills->invoice_amount}} </td>
                                            <td> GH&#8373;{{$shyhills->receipt_amount}} </td>
                                            <td> {{$shyhills->created_at->diffForHumans()}} </td>
                                            <td> {{$shyhills->balance}} </td>
                                            @if($shyhills->status == 'completed')
                                            <td> <span class="badge bg-label-success"> {{$shyhills->status}} </span> </td>
                                            @else
                                            <td> <span class="badge bg-label-danger"> {{$shyhills->status}} </span> </td>
                                            @endif
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
                                            <!-- <th>#</th> -->
                                            <th>Invoice No.</th>
                                            <th>Receipt No.</th>
                                            <th>Client Name</th>
                                            <th>Business Name </th>
                                            <th>Invoice Amount </th>
                                            <th>Receipt Amount</th>
                                            <th> Date </th>
                                            <th>Balance</th>
                                            <th> Status </th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        @foreach($temaTransactions as $tema)
                                        <tr>
                                            <td> FWSSi{{$tema->invoice_id}} </td>
                                            <td> FWSSR{{$tema->receipt_id}} </td>
                                            <td> {{$tema->client->name}} </td>
                                            <td> {{$tema->client->business_name}} </td>
                                            <td> GH&#8373; {{$tema->invoice_amount}} </td>
                                            <td> GH&#8373;{{$tema->receipt_amount}} </td>
                                            <td> {{$tema->created_at->diffForHumans()}} </td>
                                            <td> {{$tema->balance}} </td>
                                            @if($tema->status == 'completed')
                                            <td> <span class="badge bg-label-success"> {{$tema->status}} </span> </td>
                                            @else
                                            <td> <span class="badge bg-label-danger"> {{$tema->status}} </span> </td>
                                            @endif
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                        </div>

                        <div class="tab-pane fade" id="navs-pills-justified-takoradi" role="tabpanel">
                            <div class="table-responsive text-nowrap">
                                <table id="myTableiTakoradi" class="display">
                                    <thead>
                                        <tr>
                                            <!-- <th>#</th> -->
                                            <th>Invoice No.</th>
                                            <th>Receipt No.</th>
                                            <th>Client Name</th>
                                            <th>Business Name </th>
                                            <th>Invoice Amount </th>
                                            <th>Receipt Amount</th>
                                            <th>Date</th>
                                            <th>Balance</th>
                                            <th> Status </th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">

                                        @foreach($takoradiTransactions as $takoradi)
                                        <tr>
                                            <td> FWSSi{{$takoradi->invoice_id}} </td>
                                            <td> FWSSR{{$takoradi->receipt_id}} </td>
                                            <td> {{$takoradi->client->name}} </td>
                                            <td> {{$takoradi->client->business_name}} </td>
                                            <td> GH&#8373; {{$takoradi->invoice_amount}} </td>
                                            <td> GH&#8373;{{$takoradi->receipt_amount}} </td>
                                            <td> {{$takoradi->created_at->diffForHumans()}} </td>
                                            <td> {{$takoradi->balance}} </td>
                                            @if($takoradi->status == 'completed')
                                            <td> <span class="badge bg-label-success"> {{$takoradi->status}} </span> </td>
                                            @else
                                            <td> <span class="badge bg-label-danger"> {{$takoradi->status}} </span> </td>
                                            @endif
                                        </tr>
                                        @endforeach

                                    </tbody>
                                </table>
                            </div>

                        </div>

                        <div class="tab-pane fade" id="navs-pills-justified-koforidua" role="tabpanel">

                            <div class="table-responsive text-nowrap">
                                <table id="myTableiKoforidua" class="display">
                                    <thead>
                                        <tr>
                                            <!-- <th>#</th> -->
                                            <th>Invoice No.</th>
                                            <th>Receipt No.</th>
                                            <th>Client Name</th>
                                            <th>Business Name </th>
                                            <th>Invoice Amount </th>
                                            <th>Receipt Amount</th>
                                            <th>Date</th>
                                            <th>Balance</th>
                                            <th> Status </th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">

                                        @foreach($koforiduaTransactions as $koforidua)
                                        <tr>
                                            <td> FWSSi{{$koforidua->invoice_id}} </td>
                                            <td> FWSSR{{$koforidua->receipt_id}} </td>
                                            <td> {{$koforidua->client->name}} </td>
                                            <td> {{$koforidua->client->business_name}} </td>
                                            <td> GH&#8373; {{$koforidua->invoice_amount}} </td>
                                            <td> GH&#8373;{{$koforidua->receipt_amount}} </td>
                                            <td> {{$koforidua->created_at->diffForHumans()}} </td>
                                            <td> {{$koforidua->balance}} </td>
                                            @if($koforidua->status == 'completed')
                                            <td> <span class="badge bg-label-success"> {{$koforidua->status}} </span> </td>
                                            @else
                                            <td> <span class="badge bg-label-danger"> {{$koforidua->status}} </span> </td>
                                            @endif
                                        </tr>
                                        @endforeach

                                    </tbody>
                                </table>
                            </div>

                        </div>

                        <div class="tab-pane fade" id="navs-pills-justified-kumasi" role="tabpanel">

                            <div class="table-responsive text-nowrap">
                                <table id="myTableiKumasi" class="display">
                                    <thead>
                                        <tr>
                                            <!-- <th>#</th> -->
                                            <th>Invoice No.</th>
                                            <th>Receipt No.</th>
                                            <th>Client Name</th>
                                            <th>Business Name </th>
                                            <th>Invoice Amount </th>
                                            <th>Receipt Amount</th>
                                            <th>Date</th>
                                            <th>Balance</th>
                                            <th> Status </th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">

                                        @foreach($kumasiTransactions as $kumasi)
                                        <tr>
                                            <td> FWSSi{{$kumasi->invoice_id}} </td>
                                            <td> FWSSR{{$kumasi->receipt_id}} </td>
                                            <td> {{$kumasi->client->name}} </td>
                                            <td> {{$kumasi->client->business_name}} </td>
                                            <td> GH&#8373; {{$kumasi->invoice_amount}} </td>
                                            <td> GH&#8373;{{$kumasi->receipt_amount}} </td>
                                            <td> {{$kumasi->created_at->diffForHumans()}} </td>
                                            <td> {{$kumasi->balance}} </td>
                                            @if($kumasi->status == 'completed')
                                            <td> <span class="badge bg-label-success"> {{$kumasi->status}} </span> </td>
                                            @else
                                            <td> <span class="badge bg-label-danger"> {{$kumasi->status}} </span> </td>
                                            @endif
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
    @endsection

    @section('scripts')

    <script src="{{asset('vendor/js/datatables.js')}}"></script>
    <script>
        let myTableiAccra = new DataTable('#myTableiAccra');
        let myTableiBotwe = new DataTable('#myTableiBotwe');
        let myTableiShyhills = new DataTable('#myTableiShyhills');
        let myTableiTema = new DataTable('#myTableiTema');
        let myTableiTakoradi = new DataTable('#myTableiTakoradi');
        let myTableiKoforidua = new DataTable('#myTableiKoforidua');
        let myTableiKumasi = new DataTable('#myTableiKumasi');
    </script>
    @endsection
</x-sales-dashboard>
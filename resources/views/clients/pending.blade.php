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

        @if( Auth::user()->hasPermission('Accounts') || Auth::user()->hasPermission('Administration'))
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

            @if(Auth::user()->hasRole(['Finance Manager', 'Manager', 'Admin Assistant']))
            <li class="menu-item ">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-money-withdraw bg-primary"></i>
                <div class="text-truncate" data-i18n="Receipts">Receipts</div>
                </a>
                <ul class="menu-sub">

                    <li class="menu-item ">
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

            <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
            <li class="menu-item active open">
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
                    <li class="menu-item active">
                        <a href="{{url('clientPending')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="CList">Pending</div>
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
                    <li class="menu-item ">
                        <a href="{{url('employees')}}" class="menu-link">
                        <div class="text-truncate" data-i18n="SList">List</div>
                        </a>
                    </li>
                    <li class="menu-item">
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
                    <li class="menu-item ">
                        <a href="{{url('employees')}}" class="menu-link">
                        <div class="text-truncate" data-i18n="SList">List</div>
                        </a>
                    </li>
                    <li class="menu-item">
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
        
            <li class="menu-item active open">
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
                    <li class="menu-item active">
                        <a href="{{url('clientPending')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="CList">Pending</div>
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

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="bx bx-bxs-user-detail me-2"></i>Pending Clients</h3>
                </div>
            </div>
        </div>

        <!-- Summary Statistics Cards -->
        @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']) || (Auth::user()->department?->name == 'HR' && Auth::user()->role?->name == 'Manager') )
        <div class="row mb-4">
                <div class="col-12">
                     <div class="card border-left-dark bg-warning text-white h-100 py-2">
                    <div class="card-body">
                        <div class="text-uppercase mb-1"><small class="fw-bold">Total Clients / Guards</small></div>
                        <div class="h3 text-white mb-0 fw-bold">{{  $clients->count() }} <span class="text-muted fs-6">/ {{ 0 }} Guards</span></div>
                        <small class="text-muted">Clients & Operations</small>
                    </div>
                </div>
                </div>
        </div>

        <div class="row mb-4">

            <div class="col-12">
                <div class="row">
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-dark shadow-sm">
                            <div class="card-body text-center p-8">
                                <h6 class="mb-2 text-white fw-bold">ACCRA</h6>
                                <div class="mb-2">
                                    <span class="badge bg-white text-dark">{{ $clients->where('field_id', '1')->count() }}</span>
                                    <span class="badge bg-white text-dark">{{ 0 }}</span>
                                </div>
                                <small class="text-white text-muted d-block">CLIENTS - GUARDS</small>
                            </div> 
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-dark shadow-sm ">
                            <div class="card-body text-center p-8">
                                <h6 class="mb-2 text-white fw-bold">BOTWE</h6>
                                <div class="mb-2">
                                    <span class="badge bg-white text-dark">{{ $clients->where('field_id', '2')->count() }}</span>
                                    <span class="badge bg-white text-dark">{{ 0 }}</span>
                                </div>
                                <small class="text-white text-muted d-block">CLIENTS - GUARDS</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-dark shadow-sm ">
                            <div class="card-body text-center p-8">
                                <h6 class="mb-2 text-white fw-bold">TEMA</h6>
                                <div class="mb-2">
                                    <span class="badge bg-white text-dark">{{ $clients->where('field_id', '3')->count() }}</span>
                                    <span class="badge bg-white text-dark">{{ 0 }}</span>
                                </div>
                                <small class="text-white text-muted d-block">CLIENTS - GUARDS</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-dark shadow-sm ">
                            <div class="card-body text-center p-8">
                                <h6 class="mb-2 text-white fw-bold">SHAIHILLS</h6>
                                <div class="mb-2">
                                    <span class="badge bg-white text-dark">{{ $clients->where('field_id', '7')->count() }}</span>
                                    <span class="badge bg-white text-dark">{{ 0 }}</span>
                                </div>
                                <small class="text-white text-muted d-block">CLIENTS - GUARDS </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-dark shadow-sm ">
                            <div class="card-body text-center p-8">
                                <h6 class="mb-2 text-white fw-bold">TAKORADI</h6>
                                <div class="mb-2">
                                    <span class="badge bg-white text-dark">{{ $clients->where('field_id', '4')->count() }}</span>
                                    <span class="badge bg-white text-dark">{{ 0 }}</span>
                                </div>
                                <small class="text-white text-muted d-block">CLIENTS - GUARDS</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-dark shadow-sm ">
                            <div class="card-body text-center p-8">
                                <h6 class="mb-2 text-white fw-bold">KOFORIDUA</h6>
                                <div class="mb-2">
                                    <span class="badge bg-white text-dark">{{ $clients->where('field_id', '5')->count() }}</span>
                                    <span class="badge bg-white text-dark">{{ 0 }}</span>
                                </div>
                                <small class="text-white text-muted d-block">CLIENTS - GUARDS</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-dark shadow-sm ">
                            <div class="card-body text-center p-8">
                                <h6 class="mb-2 text-white fw-bold">KUMASI</h6>
                                <div class="mb-2">
                                    <span class="badge bg-white text-dark">{{ $clients->where('field_id', '6')->count() }}</span>
                                    <span class="badge bg-white text-dark">{{ 0 }}</span>
                                </div>
                                <small class="text-white text-muted d-block">CLIENTS - GUARDS</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>

        @else

        <div class="row mb-4">
                <div class="col-12">
                     <div class="card border-left-dark bg-warning text-white h-100 py-2">
                    <div class="card-body">
                        <div class="text-uppercase mb-1"><small class="fw-bold"> {{strtoupper(Auth::user()->field?->name)}} PENDING CLIENTS / GUARDS</small></div>
                        <div class="h3 text-white mb-0 fw-bold">{{  $clients->count() }} <span class="text-muted fs-6">/ {{ 0 }} Guards</span></div>
                        <small class="text-muted">Clients & Operations</small>
                    </div>
                </div>
                </div>
                <!-- <div class="col-lg-6 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-dark shadow-sm">
                            <div class="card-body text-center p-8">
                                <h6 class="mb-2 text-white fw-bold"> {{strtoupper(Auth::user()->field?->name)}} </h6>
                                <div class="mb-2">
                                    <span class="badge bg-white text-dark">{{ $clients->where('field_id', '1')->count() }}</span>
                                    <span class="badge bg-white text-dark">{{ 0 }}</span>
                                </div>
                                <small class="text-white text-muted d-block">CLIENTS - GUARDS</small>
                            </div> 
                        </div>
                </div> -->
        </div>

        @endif

        <div class="card-header ml-2 d-none d-lg-block">
            @include('flash-messages')
        </div>



        <!-- Tab Content -->
            <!-- Standard Clients Tab -->
            <form action="/clientAproval" method="POST">
                @csrf  
                <input type="hidden" name="action_type" id="client_action_type" value="" />
                <div class="card">


                    <div class="card-header pb-4">
                    <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />
                  
                    @if(Auth::user()->hasRole(['Manager']) && Auth::user()->hasPermission('Administration'))
                    <div class="form-check form-check-inline">                            
                        <button class="btn btn-dark" type="submit" onclick="document.getElementById('client_action_type').value='branch'; return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('Approve') }}</button>
                    </div>    

                    @elseif(Auth::user()->hasRole(['Invoice']))
                    <div class="form-check form-check-inline">                            
                        <button class="btn btn-success" type="submit" onclick="document.getElementById('client_action_type').value='headOffice'; return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('Approve') }}</button>
                    </div>
                    <div class="form-check form-check-inline">                            
                        <button class="btn btn-danger" type="submit" onclick="document.getElementById('client_action_type').value='decline'; return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('Decline') }}</button>
                    </div>
                    @endif

                    </div>

                    <div class="table-responsive ">
                        <table id="standardClientsTable" class="display">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th style="width: 50px;"> # </th>
                                    <th style="width: 60px;"> ID </th>
                                    <th>Status </th>

                                    <th>Admin Assit.</th>
                                    <th>Branch</th>
                                    <th>H/O</th>

                                    <th> Re-Instate Date </th>
                                    <th> Contract Date </th>
                                    <th> Type  </th>
                                    <th> Business Name </th>
                                    <th> Contact </th>
                                    <th> Field Office </th>
                                    <th> Address </th>
                                    <th> Branch </th>
                                    <th style="width: 80px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clients as $key => $client)
                                <tr class="table-light">
                                    <td> <input class="checkBoxes form-check-input" type="checkbox" name="clients[{{$client->id}}]" value="{{ $client->id }}" /></td>

                                    <td>  {{ $key + 1 }} </td>

                                    <td><strong>{{ $client->id }}</strong></td>
                                    <td>
                                        <span class="badge bg-label-warning">{{ $client->status }}</span>
                                    </td>
                                    <td> 
                                        @if($client->coll_status == 'approved')
                                        {{$client->user?->name}}
                                        {{ $client->coll_date?->format('l, F j, Y') }}
                                        <span class="badge bg-label-success">{{$client->coll_status}}</span>
                                        @else
                                        {{$client->user?->name}}
                                        {{ $client->coll_date?->format('l, F j, Y') }}
                                        <span class="badge bg-label-danger">{{$client->coll_status}}</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($client->bran_status == 'approved')
                                        {{$client->user1?->name}}
                                        {{ $client->bran_date?->format('l, F j, Y') }}
                                        <span class="badge bg-label-success">{{$client->bran_status}}</span>
                                        @else
                                        {{$client->user1?->name}}
                                        {{ $client->bran_date?->format('l, F j, Y') }}
                                        <span class="badge bg-label-danger">{{$client->bran_status}}</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{$client->user2?->name}}
                                        <span class="badge bg-label-danger">{{$client->ho_status}}</span>
                                    </td>
                                    <td>  {{ $client->status_date?->format('F l d, Y') }} </td>
                                    <td>  {{ $client->start_date?->format('F l d, Y') }} </td>
                                    <td>
                                        @if($client->state_institution == '1' )
                                            <i class="bx bx-building text-danger me-2"></i>State
                                        @else
                                            <i class="bx bx-bxs-group text-danger me-2"></i> Standard
                                        @endif
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $client->business_name }}</strong>
                                            <br><small class="text-muted">{{ $client->name }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong> <small>{{ $client->phone_number }}</small> </strong> 
                                            @if($client->phone_number1)
                                            <br><small class="text-muted">{{ $client->phone_number1 }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $fieldColors = [
                                                'Accra' => 'danger',
                                                'Botwe' => 'secondary',
                                                'Tema' => 'info',
                                                'ShaiHills' => 'info',
                                                'Takoradi' => 'warning',
                                                'Koforidua' => 'success',
                                                'Kumasi' => 'primary'
                                            ];
                                            $color = $fieldColors[$client->field->name] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-label-{{ $color }}">{{ $client->field->name }}</span>
                                    </td>
                                    <td><small>{{ $client->address }}</small></td>
                                    <td><small>{{ $client->branch }}</small></td>
                                    <td>
                                                <a class="btn btn-warning btn-sm" href="client/{{ $client->id }}/edit"><i class="bx bx-edit-alt me-1"></i> Edit</a>

                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($clients->isEmpty())
                        <div class="text-center py-5">
                            <p class="text-muted">No standard clients found</p>
                        </div>
                        @endif
                    </div>
                </div>
            </form>

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
    <script src="https://cdn.datatables.net/columncontrol/1.1.1/js/dataTables.columnControl.min.js"></script>

    <script>
        // Initialize Standard Clients Table
        new DataTable('#standardClientsTable', {
            responsive: true,
            columnControl: [['search']],
            layout: {
                topStart: {
                    buttons: [
                        {
                            extend: 'pageLength',
                            text: 'Show',
                            className: 'btn btn-secondary btn-sm',
                            Options: [10, 25, 50, 100, 250, 500, 1000],
                        },
                        {
                            extend: 'excelHtml5',
                            title: 'Standard Clients',
                            className: 'btn btn-secondary btn-sm',
                            exportOptions: {
                                columns: ':visible'
                            }
                        }
                    ]
                }
            },
        });

        // Initialize State Institution Clients Table
        new DataTable('#stateClientsTable', {
            responsive: true,
            columnControl: [['search']],
            layout: {
                topStart: {
                    buttons: [
                        {
                            extend: 'pageLength',
                            text: 'Show',
                            className: 'btn btn-secondary btn-sm',
                            Options: [10, 25, 50, 100, 250, 500, 1000],
                        },
                        {
                            extend: 'excelHtml5',
                            title: 'State Institution Clients',
                            className: 'btn btn-secondary btn-sm',
                            exportOptions: {
                                columns: ':visible'
                            }
                        }
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
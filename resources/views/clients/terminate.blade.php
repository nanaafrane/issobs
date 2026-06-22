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
                    <li class="menu-item active">
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

    <div class="container-xxl flex-grow-1 container-p-y">

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="bx bx-bxs-user-detail me-2"></i>Terminated Clients</h3>
                </div>
            </div>
        </div>

        <!-- Summary Statistics Cards -->
        @if(Auth::user()->hasRole(['Invoice', 'Finance Manager', 'Manager']))
        <div class="row mb-4">
                <div class="col-12">
                     <div class="card border-left-dark bg-danger text-white h-100 py-2">
                    <div class="card-body">
                        <div class="text-uppercase mb-1"><small class="fw-bold">Total Clients / Guards</small></div>
                        <div class="h3 text-white mb-0 fw-bold">{{ $clientsCount }} <span class="text-muted fs-6">/ {{ 0 }} Guards</span></div>
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
                                    <span class="badge bg-white text-dark">{{ $accraCount }}</span>
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
                                    <span class="badge bg-white text-dark">{{ $botweCount }}</span>
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
                                    <span class="badge bg-white text-dark">{{ $temaCount }}</span>
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
                                    <span class="badge bg-white text-dark">{{ $shyhillsCount }}</span>
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
                                    <span class="badge bg-white text-dark">{{ $takoradiCount }}</span>
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
                                    <span class="badge bg-white text-dark">{{ $koforiduaCount }}</span>
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
                                    <span class="badge bg-white text-dark">{{ $kumasiCount }}</span>
                                    <span class="badge bg-white text-dark">{{ 0 }}</span>
                                </div>
                                <small class="text-white text-muted d-block">CLIENTS - GUARDS</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>

        @endif

        <div class="card-header ml-2 d-none d-lg-block">
            @include('flash-messages')
        </div>



        <!-- Tab Content -->
            <!-- Standard Clients Tab -->
                
                <div class="card">
                    <div class="card-header pb-3">
                        <h6 class="mb-4"><i class="bx bx-bxs-group text-dark me-2"></i>Standard Clients List</h6>
                    </div>

                    @if(Auth::user()->hasRole(['Manager', 'Invoice']))
                        <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />

                        <div class="form-check form-check-inline">                            
                            <button class="btn btn-success" type="submit" onclick="return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('Re-Instate') }}</button>
                        </div>
                    @endif

                    <div class="table-responsive ">
                        <table id="standardClientsTable" class="display">
                            <thead>
                                <tr>
                                    <th> </th>
                                    <th style="width: 50px;"> # </th>
                                    <th style="width: 60px;"> ID </th>
                                    <th>Type</th>
                                    <th> Status </th>
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
                                    <td> <input class="checkBoxes form-check-input" type="checkbox" name="clients[]" value="{{ $client->id }}" /></td>

                                    <td>  {{ $key + 1 }} </td>

                                    <td><strong>{{ $client->id }}</strong></td>
                                    <td>
                                        @if($client->status == 'terminated')
                                        <span class="badge bg-label-danger">{{ $client->status }}</span>
                                        @else
                                        <span class="badge bg-label-success">{{ $client->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($client->state_institution == '1' )
                                            <i class="bx bx-building text-danger me-2"></i>
                                        @else
                                            <i class="bx-bxs-group text-danger me-2"></i> 
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
                                                <a class="btn btn-danger btn-sm" href="{{ url('client', $client->id) }}"><i class="bx bxs-bullseye me-1"></i> View</a>

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
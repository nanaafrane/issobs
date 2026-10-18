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
                            <div class="text-truncate" data-i18n="CRegister">New Contract</div>
                        </a>
                    </li>
                    <li class="menu-item active">
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
                        <a href="{{ route('employees.movements') }}" class="menu-link">
                        <div class="text-truncate" data-i18n="SList">HR movements</div>
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
            <li class="menu-item  ">
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
                        <a href="{{ route('employees.movements') }}" class="menu-link">
                        <div class="text-truncate" data-i18n="SList">HR movements</div>
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
                            <div class="text-truncate" data-i18n="CRegister">New Contract</div>
                        </a>
                    </li>
                    <li class="menu-item active">
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
        <div class="row">
            <div class="col-6 mb-8">
                <h3 class="card-header text-info"> <i class="icon-base bx bx-bxs-user-detail"></i> Client </h3>
            </div>
        </div>

        <div class="row">


            <div class="col-md-4 col-xl-4">
                <div class="card text-bg-dark">
                    <div class="card-body">
                        <h5 class="card-title text-white"> Client Details <span class="badge bg-label-dark"> </span></h5>
                        <hr>
                        <p class="card-text">Client ID # : <strong> {{$client->id}}</strong> </p>
                        <p class="card-text">Client Name : <strong>{{$client->name}}</strong> </p>
                        <p class="card-text">Phone Number : <strong>{{$client->phone_number}}, {{ $client->phone_number1}}</strong> </p>
                        <p class="card-text">Business Name : <strong>{{$client->business_name}}</strong> </p>
                        <p class="card-text">Address : <strong>{{$client->address}}</strong> </p>
                        <p class="card-text">Location : <strong>{{$client->field->name}}</strong> </p>

                    </div>
                </div>
            </div>

            @if($total_wth_amount_received > 0.00)
            <div class="col-md-4 col-xl-4">
                <div class="card text-bg-secondary">
                    <div class="card-body">
                        <h5 class="card-title text-white"> Client With Holdings </h5>
                        <hr>
                        <p class="card-text">Total Amount Received : <strong> GH&#x20B5; {{number_format($total_wth_amount_received, 2) }} </strong> </p>
                        <p class="card-text">Total WTH Amount : <strong> GH&#x20B5; {{number_format($total_wth_amount, 2) }} </strong> </p>
                    </div>
                </div>
            </div>
            @endif

            <div class="col-md-4 col-xl-4">
                <div class="card text-bg-danger">
                    <div class="card-body">
                        <h5 class="card-title text-white"> Client Account </h5>
                        <hr>
                        <p class="card-text">Total Invoiced : <strong> GH&#x20B5; {{ number_format($account['invoiced'], 2) }} </strong> </p>
                        <p class="card-text">Money Received : <strong> GH&#x20B5; {{ number_format($account['received'], 2) }} </strong> </p>
                        <p class="card-text">WHT / VAT / Deductions : <strong> GH&#x20B5; {{ number_format($account['withheld'], 2) }} </strong> </p>
                        <p class="card-text">Owed on Invoices : <strong> GH&#x20B5; {{ number_format($account['owed_on_invoices'], 2) }} </strong> </p>
                        @if($account['credit'] > 0)
                        <p class="card-text">Unused Credit : <strong> GH&#x20B5; {{ number_format($account['credit'], 2) }} </strong> </p>
                        @endif
                        <h4 class="card-text text-white"> Balance : <strong> GH&#x20B5; {{ \App\Services\Receipts\ClientStatement::money($account['balance']) }} </strong> </h4>
                        @unless($account['check']['ok'])
                        <small class="text-white-50">Statement and invoice balances differ by GH&#x20B5;{{ number_format($account['check']['difference'], 2) }} — old data, see the statement.</small>
                        @endunless
                    </div>
                </div>
            </div>

        </div><br>

        <a href="{{ url('clientStatement', $client->id )}}" class="btn btn-secondary"  type=" button"> Get Statement of Account </a>
        <a href="{{ route('attendance.show', $client->id) }}" class="btn btn-primary ms-2"><i class="bx bx-qr-scan me-1"></i> Attendance, QR poster &amp; guard PINs</a>


        <div class="row mt-12">

            <div class="col-xl-12">
                <!-- <h6 class="text-body-secondary">Filled Tabs</h6> -->
                <div class="nav-align-top nav-tabs-shadow">
                    <ul class="nav nav-tabs nav-fill" role="tablist">
                        <li class="nav-item">
                            <button
                                type="button"
                                class="nav-link active"
                                role="tab"
                                data-bs-toggle="tab"
                                data-bs-target="#navs-justified-home"
                                aria-controls="navs-justified-home"
                                aria-selected="true">
                                <span class="d-none d-sm-inline-flex align-items-center">
                                    <i class="icon-base bx bx-bxs-receipt icon-sm me-1_5"></i>Account
                                    <span class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-danger ms-1_5">{{ count($account['lines']) }}</span>
                                </span>
                                <i class="icon-base bx bx-bxs-receipt icon-sm d-sm-none"></i>
                            </button>
                        </li>

                    </ul>

                    <div class="tab-content">

                        <div class="tab-pane fade show active" id="navs-justified-home" role="tabpanel">
                            <div class="table-responsive text-normal-dark"> 
                                {{-- Account lines from the client ledger (the old transactions table is no longer read here). --}}
                                <table id="myTabletrans" class="display">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th>Details</th>
                                            <th>Debit</th>
                                            <th>Credit</th>
                                            <th>Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($account['lines'] as $line)
                                        <tr>
                                            <td data-order="{{ $line['date']->format('Y-m-d') }}">{{ $line['date']->format('d M Y') }}</td>
                                            <td>{{ ['invoice' => 'Invoice', 'receipt' => 'Payment', 'wht' => 'WHT', 'vat' => 'VAT 7%', 'deduction' => 'Deduction', 'credit_applied' => 'Credit applied', 'reversal' => 'Correction'][$line['kind']] ?? $line['kind'] }}</td>
                                            <td>@if($line['link'])<a href="{{ $line['link'] }}">{{ $line['description'] }}</a>@else{{ $line['description'] }}@endif</td>
                                            <td class="small">{{ $line['details'] }}</td>
                                            <td class="text-end">{{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '' }}</td>
                                            <td class="text-end">{{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '' }}</td>
                                            <td class="text-end">{{ $line['kind'] === 'credit_applied' ? '' : \App\Services\Receipts\ClientStatement::money($line['balance']) }}</td>
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
        new DataTable('#myTabletrans', {
            responsive: true,
            ordering: false,   // keep date order so the running balance reads correctly

            layout: {
                topStart: {
                    buttons: ['excelHtml5', 'pdfHtml5']
                }
            },
            columnControl: [
                ['search']
            ]
        });
    </script>

    <!-- <script>
        new DataTable('#myTableinv', {
            responsive: true,

            layout: {
                topStart: {
                    buttons: ['excelHtml5', 'pdfHtml5']
                }
            }
        });
    </script>


    <script>
        new DataTable('#myTablerec', {
            responsive: true,

            layout: {
                topStart: {
                    buttons: ['excelHtml5', 'pdfHtml5']
                }
            }
        });
    </script> -->


    @endsection



</x-hr-dashboard>
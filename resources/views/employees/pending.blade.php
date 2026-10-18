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

        @if(Auth::user()->hasPermission('Accounts') || Auth::user()->hasPermission('Administration'))
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

                @if(Auth::user()->hasRole(['Finance Manager' ,'Manager', 'Admin Assistant']))
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
            <li class="menu-item ">
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
                    <li class="menu-item active open">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons bx bxs-user-account"></i>
                        <div class="text-truncate" data-i18n="Staffs">Employees</div>
                        </a>
                        <ul class="menu-sub">
                        <li class="menu-item ">
                            <a href="{{url('employees/create')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="SRegister">New Recruit</div>
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
                    <div class="text-truncate" data-i18n="SRegister">New Recruit</div>
                    </a>
                </li>
                <li class="menu-item">
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
        
        @if(Auth::user()->hasRole(['Manager', 'Invoice']))
            <li class="menu-item ">
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
                    <li class="menu-item ">
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

            @if((Auth::user()->hasRole(['Finance Manager']) && Auth::user()->hasPermission('Accounts')) )

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

  <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bxs-user-account"></i> Pending Employees </h3>
            </div>
        </div><br>



        @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']) || (Auth::user()->hasRole(['Manager']) && Auth::user()->hasPermission('HR')))
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

            @if($employeeNoOffice->isNotEmpty())
            {{-- Pending staff without a field office: part of the total, so they get a card too. --}}
            <div class="col-lg-2">
                <div class="card h-100 bg-warning text-dark" title="{{ $employeeNoOffice->map(fn ($e) => 'FWSS ' . $e->id . ' ' . $e->name)->implode(', ') }}">
                    <div class="card-body">
                        <p class="mb-1"><strong> NO OFFICE </strong> </p>
                        <h4 class="card-title mb-3 text-dark"> {{ $employeeNoOffice->count() }} </h4>
                        <small>Set their field office</small>
                    </div>
                </div>
            </div>
            @endif

        </div> <br>
        @else
        {{-- A field office sees its own office and its sub-offices (Tema also sees Shai Hills). --}}
        <div class="row">
            @foreach ($officeCards as $card)
            <div class="col-lg-3 col-md-4 mb-3">
                <div class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong> {{ strtoupper($card['name']) }} </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ $card['count'] }} </strong> </h4>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
        <br><br>



        <div class="card-header  ml-2  d-none d-lg-block">
            @include('flash-messages')
        </div>

        <div class="row">
        <form action="/employeesAproval" method="POST">
            @csrf
            <input type="hidden" name="action_type" id="employee_action_type" value="" />
            <div class="col">

                    <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />

                    @if(Auth::user()->hasRole(['Manager']) && Auth::user()->hasPermission('Administration') )
                    <div class="form-check form-check-inline">                            
                        <button class="btn btn-dark" type="submit" onclick="document.getElementById('employee_action_type').value='branch'; return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('Approve') }}</button>
                    </div>    

                    @elseif(Auth::user()->hasRole(['Invoice']) || (Auth::user()->hasRole(['Manager']) && Auth::user()->hasPermission('HR')) )
                    <div class="form-check form-check-inline">                            
                        <button class="btn btn-success" type="submit" onclick="document.getElementById('employee_action_type').value='headOffice'; return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('Approve') }}</button>
                    </div>
                    <div class="form-check form-check-inline">                            
                        <button class="btn btn-danger" type="submit" onclick="document.getElementById('employee_action_type').value='decline'; return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('Decline') }}</button>
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
                                <th>Status</th>

                                <th>Admin Assit.</th>
                                <th>Branch</th>
                                <th>H/O</th>

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
                                <!-- <th>Status Date</th> -->
                                <th>TAX</th>
                                <th>TIN</th>
                                <th>SSNIT</th>
                                <th>SSNIT #</th>
                                <th>Basic</th>
                                <th>Allowance</th>
                                <th> Created </th>
                                <!-- <th> Period </th> -->
                                <!-- <th> Updated</th> -->
                                <!-- <th> Period </th> -->
                                <!-- <th>Staff</th> -->
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                         @foreach ($visiblePending as $key => $employee)
                            @include('employees.partials.pending_row')
                         @endforeach

                       
                       
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
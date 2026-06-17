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

        <div class="row ">
            <div class="col-12">
                <h3 class="card-header text-info"> <i class="icon-base bx bx-bxs-receipt"></i> Client <i class="icon-base bx bx-bxs-right-arrow-alt"></i> Edit </h3>
            </div>
        </div><br>

        <div class="card-header  ml-2  d-none d-lg-block">
            @include('flash-messages')
        </div>

        <div class="row gy-3 mt-3">

            <div class="col-2"></div>
            <div class="col-8">
                <form method="POST" action="/client/{{$client->id}}">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col mb-0">
                            <label for="name" class="form-label"> {{ __('Full Name') }}</label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{$client->name}}"
                                placeholder="Full Name"

                                autocomplete="name"
                                autofocus>

                            @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror


                        </div>
                        <div class="col mb-0">
                            <label for="phone_number" class="form-label"> {{ __('Phone Number 1') }} </label>
                            <input
                                type="number"
                                id="phone_number"
                                name="phone_number"
                                class="form-control @error('phone_number') is-invalid @enderror"
                                value="{{$client->phone_number}}"
                                placeholder="Phone Number 1"
                                autocomplete="phone_number"
                                autofocus>

                            @error('phone_number')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col mb-0">
                            <label for="phone_number1" class="form-label"> {{ __('Phone Number 2') }} </label>
                            <input
                                type="number"
                                id="phone_number1"
                                name="phone_number1"
                                class="form-control @error('phone_number') is-invalid @enderror"
                                value="{{$client->phone_number1}}"
                                placeholder="Phone Number 2"
                                autocomplete="phone_number1"
                                autofocus>

                            @error('phone_number1')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>
                    <div class="row g-6">
                        <div class="col mb-0">
                            <label for="business_name" class="form-label"> {{ __('Business Name') }} </label>
                            <input
                                type="text"
                                id="business_name"
                                name="business_name"
                                class="form-control @error('business_name') is-invalid @enderror"
                                value="{{$client->business_name}}"
                                placeholder="Business Name"

                                autocomplete="business_name"
                                autofocus>

                            @error('business_name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col mb-0">
                            <label for="address" class="form-label"> {{ __('Address') }} </label>
                            <input
                                type="text"
                                id="address"
                                name="address"
                                class="form-control @error('address') is-invalid @enderror"
                                value="{{$client->address}}"
                                placeholder="Address"

                                autocomplete="address"
                                autofocus>

                            @error('address')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror

                        </div>
                    </div>

                    <br>
                    <div class="row g-6">
                        <div class="col mb-0">
                            <div class="input-group">
                                <label class="input-group-text" for="inputGroupSelect01">{{ __('Location') }}</label>
                                <select name="field_id" class="form-select @error('inputGroupSelect01') is-invalid @enderror" id="inputGroupSelect01" value="{{ old('field_id')}}" required>
                                    @foreach($fields as $field)
                                    <option @if($field->name == $client->field->name) selected @endif value="{{$field->id}}">{{$field->name}}</option>
                                    @endforeach
                                </select>
                            </div>

                            @error('field_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col mb-0">
                            <div class="input-group">
                                <input
                                    type="text"
                                    id="branch"
                                    name="branch"
                                    class="form-control @error('branch') is-invalid @enderror"
                                    value="{{$client->branch}}"
                                    placeholder="branch"
                                    autocomplete="branch"
                                    autofocus>

                            </div>

                            @error('branch')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                    </div>

                                        <br>
                    <div class="row g-6">
                        <div class="col mb-0">
                            <label for="rate" class="form-label"> {{ __('Rate') }} </label>
                            <input
                                type="number"
                                id="rate"
                                name="rate"
                                class="form-control @error('rate') is-invalid @enderror"
                                value="{{ old('rate') ?? $client->rate }}"
                                placeholder="GH&#x20B5;"

                                autocomplete="rate"
                                step="any"
                                autofocus>

                            @error('rate')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col mb-0">
                            <label for="guards" class="form-label"> {{ __('Guards') }} </label>
                            <input
                                type="text"
                                id="guards"
                                name="guards"
                                class="form-control @error('guards') is-invalid @enderror"
                                value="{{ old('guards') ?? $client->guards }}"
                                placeholder="Number of guards"

                                autocomplete="guards"
                                autofocus>

                            @error('guards')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror

                        </div>
                    </div>

                    <br>
                    <div class="row g-6">
                        <div class="col mb-0">
                            <label for="start_date" class="form-label"> {{ __('Start Date') }} </label>
                            <input
                                type="date"
                                id="start_date"
                                name="start_date"
                                class="form-control @error('start_date') is-invalid @enderror"
                                value=" {{old('start_date') ?? $client->start_date?->format('d/m/Y')}} "
                                required
                                autocomplete="start_date"
                                autofocus>

                            @error('start_date')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col mb-0">
                            <label for="scope_of_work" class="form-label"> {{ __('Scope of Work') }} </label>
                           <textarea name="scope_of_work" id="scope_of_work" class="form-control @error('scope_of_work') is-invalid @enderror">{{ old('scope_of_work') ?? $client->scope_of_work }}</textarea>

                            @error('scope_of_work')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror

                        </div>
                    </div>

                    <br>
                    <div class="row g-6">

                        <div class="col mb-0">

                                <input name="state_institution" class="form-check-input" type="checkbox" value="1" id="state_institution" @if(old('state_institution') || $client->state_institution) checked @endif />
                                <label class="form-check-label" for="state_institution"> Tick For State Institution </label>

                            @error('state_institution')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror

                        </div>
                    </div>


                    <br>
                    <hr>
                    <br>
                    <div class="row g-6">
                        <div class="col mb-0">
                            <div class="input-group">
                                <label class="input-group-text" for="inputGroupSelect01">{{ __('Categories') }}</label>
                                <select name="category_name" class="form-select @error('inputGroupSelect01') is-invalid @enderror" id="inputGroupSelect01" value="{{ old('category_name')}}" required>
                                    @foreach($categories as $category)
                                    <option @if($category == $client?->category?->name) selected @endif value="{{ $category }}">{{ $category }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @error('category_name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col mb-0">
                            <input type="text" name="user_id" id="user_id"  value="{{ Auth::id() }}" hidden>
                            @error('user_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                    </div>


                    <br>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-info d-grid w-100">{{ __('Update') }}</button>
                    </div>
                </form>

            </div>
            <div class="col-2">
                                    <button
                        type="button"
                        class="btn btn-danger btn-buy-now"
                        data-bs-toggle="modal"
                        data-bs-target="#basicModal">
                        <i class="icon-base bx bx-bxs-user-plus"> </i>Terminate
                    </button>

                    <div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel1">Choose Month To Terminate</h5>
                                    <button
                                        type="button"
                                        class="btn-close"
                                        data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>

                                <form method="GET" action="/terminateClient/{{ $client->id }}">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col mb-0">
                                                <label for="name" class="form-label"> {{ __(' MONTH ') }}</label>
                                                <input
                                                    type="month"
                                                    name="status_date"
                                                    id="status_date"
                                                    class="form-control @error('status_date') is-invalid @enderror"
                                                    value="{{ old('status_date')}}"
                                                    autocomplete="status_date"
                                                    autofocus
                                                    required>

                                                @error('status_date')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <br>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-info d-grid w-100">{{ __('Terminate') }}</button>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>
            </div>




        </div>
        <br> <br>

        <hr class="m-5" >

        <!-- Add a ui to attach and detach employees -->
        <div class="row mt-5">
            <div class="col-6">
                <div class="card">
                    <div class="card-header">Attached Employees</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('client.detachEmployees', ['client' => $client->id]) }}">
                            @csrf
                            @if(isset($attachedEmployees) && $attachedEmployees->isNotEmpty())
                            <div class="table-responsive">
                                <table id="myTable1" class="display">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Name</th>
                                            <th>Phone</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($attachedEmployees as $emp)
                                        <tr>
                                            <td><input type="checkbox" name="employees[]" value="{{ $emp->id }}"></td>
                                            <td>{{ $emp->name }}</td>
                                            <td>{{ $emp->phone_number }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" class="btn btn-danger">Detach selected</button>
                            @else
                            <div class="alert alert-info">No employees attached to this client.</div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card">
                    <div class="card-header">Available Employees</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('client.attachEmployees', ['client' => $client->id]) }}">
                            @csrf
                            @if(isset($availableEmployees) && $availableEmployees->isNotEmpty())
                            <div class="table-responsive">
                                <table id="myTable" class="display">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Name</th>
                                            <th>Client</th>
                                            <th>Phone</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($availableEmployees as $emp)
                                        <tr>
                                            <td><input type="checkbox" name="employees[]" value="{{ $emp->id }}"></td>
                                            <td>{{ $emp->name }}</td>
                                            <td>{{ $emp->client?->business_name ?? 'N/A' }} {{ $emp->client?->name ?? 'N/A' }}</td>
                                            <td>{{ $emp->phone_number }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" class="btn btn-success">Attach selected</button>
                            @else
                            <div class="alert alert-info">No available employees to attach.</div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>

        
        <br><br><br><br><br><br><br><br>
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
        new DataTable('#myTable1', {
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


    @endsection


        </x-sales-dashboard>
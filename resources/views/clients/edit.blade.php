<x-hr-dashboard>

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
            @if(Auth::user()->hasRole('Invoice') || Auth::user()->hasRole('Finance Manager'))
            <li class="menu-item">
                <a href="{{ url('invoice') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bxs-receipt bg-primary"></i>
                    <div class="text-truncate" data-i18n="Invoices">Invoices</div>
                </a>
            </li>
            @endif
            <li class="menu-item">
                <a href="{{url('receipt')}}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-money-withdraw bg-primary"></i>
                    <div class="text-truncate" data-i18n="Receipts">Receipts</div>
                </a>
            </li>
            @endif
            <!-- Components -->
            <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>

            @if(Auth::user()->hasPermission('HR'))
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-bxs-group"></i>
                    <div class="text-truncate" data-i18n="Staffs">Staffs</div>
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

            @if(Auth::user()->hasPermission('HR'))
            <li class="menu-item active open">
                <a class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-bxs-user-detail"></i>
                    <div class="text-truncate" data-i18n="Clients"><strong>Clients</strong></div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{url('client/create')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="CRegister">Register</div>
                        </a>
                    </li>
                    <li class="menu-item active">
                        <a href="{{url('client')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="CList">List</div>
                        </a>
                    </li>
                </ul>
            </li>
            @else
            <li class="menu-item active">
                <a href="{{url('client')}}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bxs-user-detail"></i>
                    <div class="text-truncate" data-i18n="Clients"><strong>Clients</strong></div>
                </a>
            </li>

            @endif

            @if(Auth::user()->hasPermission('HR'))
            <li class="menu-item">
                <a href="{{url('field')}}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bxs-location-plus"></i>
                    <div class="text-truncate" data-i18n="Locations">Locations</div>
                </a>
            </li>

            <li class="menu-header small text-uppercase"><span class="menu-header-text">PAYROLL</span></li>
            <li class="menu-item">
                <a href="" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                    <div class="text-truncate" data-i18n="Locations">Payroll</div>
                </a>
            </li>
            @endif

            @if((Auth::user()->hasRole('Manager') && Auth::user()->hasPermission('Accounts')) || Auth::user()->hasRole('Officer') || Auth::user()->hasRole('Finance Manager') )

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
                            <label for="phone_number" class="form-label"> {{ __('Phone Number') }} </label>
                            <input
                                type="number"
                                id="phone_number"
                                name="phone_number"
                                class="form-control @error('phone_number') is-invalid @enderror"
                                value="{{$client->phone_number}}"

                                autocomplete="phone_number"
                                autofocus>

                            @error('phone_number')
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
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-info d-grid w-100">{{ __('Update') }}</button>
                    </div>
                </form>

            </div>
            <div class="col-2"></div>

        </div>
        <br><br><br><br><br><br><br><br>
        @endsection



        </x-sales-dashboard>
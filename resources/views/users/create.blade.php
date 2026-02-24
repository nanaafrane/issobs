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
                    <div class="text-truncate" data-i18n="Dashboards">Dashboard</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{url('home')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="Dashboard">Dashboard</div>
                        </a>
                    </li>
                </ul>
            </li>
            <!-- Components -->
            <li class="menu-header small text-uppercase"><span class="menu-header-text">Management</span></li>
            <li class="menu-item active open">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-bxs-group"></i>
                    <div class="text-truncate" data-i18n="Staffs">Staffs</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item active">
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
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-bxs-user-detail"></i>
                    <div class="text-truncate" data-i18n="Clients">Clients</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item">
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


        </ul>
    </aside>
    <!-- / Menu -->
    @endsection



    @section('content')

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-12">
                <h3 class="card-header text-dark"> <i class="icon-base bx bx-bxs-group"></i> Create Staff </h3>
            </div>
        </div><br>

        <div class="content-wrapper">
            <!-- Content -->
            <div class="container-xxl flex-grow-1 container-p-y">
                <div class="row">
                    <div class="col-md-12">
                        <div class="nav-align-top">
                            <ul class="nav nav-pills flex-column flex-md-row mb-6 gap-md-0 gap-2">

                                <li class="nav-item">
                                    <a class="nav-link bg-danger active"><i class="icon-base bx bx-user icon-sm me-1_5"></i> Account</a>
                                </li>

                            </ul>
                        </div>
                        <div class="card mb-6">
                            <form method="post" action="/staff">
                                @csrf
                                <!-- Account -->
                                <div class="card-body pt-4">

                                    <div class="row g-6">
                                        <div class="col-md-6">
                                            <label for="name" class="form-label">Full Name</label>
                                            <input
                                                class="form-control @error('name') is-invalid @enderror"
                                                type="text"
                                                id="name"
                                                name="name"
                                                required
                                                autofocus />

                                            @error('name')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">E-mail</label>
                                            <input
                                                class="form-control @error('email') is-invalid @enderror"
                                                type="text"
                                                id="email"
                                                name="email"
                                                required />

                                            @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>

                                        <div class="col-md-4">
                                            <div class="input-group">
                                                <label class="input-group-text" for="department_id">{{ __('Department') }}</label>
                                                <select name="department_id" class="form-select @error('department_id') is-invalid @enderror" id="department_id" required>
                                                    <option selected disabled>Choose Department </option>
                                                    @foreach($Departments as $department)
                                                    <option value="{{$department->id}}">{{$department->name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            @error('department_id')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>


                                        <div class="col-md-4">
                                            <div class="input-group">
                                                <label class="input-group-text" for="role_id">{{ __('Role') }}</label>
                                                <select name="role_id" class="form-select @error('role_id') is-invalid @enderror" id="role_id" required>
                                                    <option selected disabled>Choose Role</option>
                                                    @foreach($Roles as $role)
                                                    <option value="{{$role->id}}">{{$role->name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            @error('role_id')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>

                                        <div class="col-md-4">
                                            <div class="input-group">
                                                <label class="input-group-text" for="field_id">{{ __('Branch') }}</label>
                                                <select name="field_id" class="form-select @error('field_id') is-invalid @enderror" id="field_id" required>
                                                    <option selected disabled>Choose Branch</option>
                                                    @foreach($fields as $field)
                                                    <option value="{{$field->id}}">{{$field->name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            @error('field_id')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror

                                        </div>



                                        <div class="col-md-6">
                                            <label class="form-label" for="phone_number">Phone Number</label>
                                            <div class="input-group input-group-merge">
                                                <!-- <span class="input-group-text"></span> -->
                                                <input
                                                    type="text"
                                                    id="phone_number"
                                                    name="phone_number"
                                                    class="form-control  @error('phone_number') is-invalid @enderror"
                                                    required />

                                                @error('phone_number')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="gender" class="form-label">Gender</label>
                                            <input type="text" class="form-control  @error('gender') is-invalid @enderror" id="gender" name="gender" required />

                                            @error('gender')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror

                                        </div>

                                        <div class="col-md-6 form-password-toggle">
                                            <label class="form-label " for="password">{{ __('Password') }}</label>
                                            <div class="input-group input-group-merge">
                                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                                                <span class="input-group-text cursor-pointer"><i class="icon-base bx bx-hide"></i></span>

                                                @error('password')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6 form-password-toggle">
                                            <label class="form-label " for="password-confirm">{{ __('Confirm Password') }}</label>
                                            <div class="input-group input-group-merge">
                                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                                                <span class="input-group-text cursor-pointer"><i class="icon-base bx bx-hide"></i></span>
                                            </div>
                                        </div>

                                    </div><br>
                                    <button type="submit" class="btn btn-dark"> Create </button>

                            </form>
                        </div>
                        <!-- /Account -->
                    </div>

                </div>
            </div>
        </div>
        <!-- / Content -->


        <div class="content-backdrop fade"></div>
    </div>

    </div>
    @endsection

</x-hr-dashboard>
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
                <h3 class="card-header text-dark"> <i class="icon-base bx bx-bxs-receipt"></i> Staff Edit </h3>
            </div>
        </div>


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
                            <!-- Account -->
                            <div class="card-body">
                                <div class="card-header  ml-2  d-none d-lg-block">
                                    @include('flash-messages')
                                </div>

                                <div class="d-flex align-items-start align-items-sm-center gap-6 pb-4 border-bottom">
                                    <img
                                        src="@if($user->path) {{asset('storage/'.$user->path)}} @else {{asset('img/user.png')}} @endif"
                                        alt="user-avatar"
                                        class="d-block w-px-100 h-px-100 rounded"
                                        id="uploadedAvatar" />

                                    <form action="/staffProfileImage/{{$user->id}}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        @method('PATCH')
                                        <div class="button-wrapper">
                                            <label for="image" class="btn btn-dark  me-3 mb-4" tabindex="0">
                                                <i class="icon-base bx bx-upload d-block"></i>
                                                <!-- <span class="d-none d-sm-block"> Upload photo</span> -->
                                                <input
                                                    type="file"
                                                    id="image"
                                                    name="image"
                                                    class="account-file-input  @error('image') is-invalid @enderror"
                                                    accept="image/png, image/jpeg" required />
                                                @error('image')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                                @enderror
                                            </label>
                                            <button type="submit" class="btn btn-outline-danger account-image-reset mb-4">
                                                <i class="icon-base bx bx-reset d-block"></i>
                                                <span class="d-none d-sm-block">Save</span>
                                            </button>
                                            <div>Allowed JPG or PNG. Max size of 2MB </div>

                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>

                        <form method="POST" action="/staff/{{$user->id}}">
                            @csrf
                            @method('PATCH')
                            <div class="card-body pt-4">
                                <div class="row g-6">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input
                                            class="form-control @error('name') is-invalid @enderror"
                                            type="text"
                                            id="name"
                                            name="name"
                                            value="{{$user->name}}"
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
                                            value="{{$user->email}}" />

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
                                                @foreach($Departments as $department)
                                                <option @if($department->name == $user->department->name) selected @endif value="{{$department->id}}">{{$department->name}}</option>
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
                                                @foreach($Roles as $role)
                                                <option @if($role->name == $user->role->name) selected @endif value="{{$role->id}}">{{$role->name}}</option>
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
                                            <label class="input-group-text" for="role_id">{{ __('Branch') }}</label>
                                            <select name="field_id" class="form-select @error('field_id') is-invalid @enderror" id="field_id" required>
                                                @foreach($fields as $field)
                                                <option @if($field?->name == $user->field?->name) selected @endif value="{{$field?->id}}">{{$field?->name}}</option>
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
                                            <input
                                                type="text"
                                                id="phone_number"
                                                name="phone_number"
                                                class="form-control @error('phone_number') is-invalid @enderror"
                                                value="{{$user->phone_number}}" />

                                            @error('phone_number')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="gender" class="form-label">Gender</label>
                                        <input type="text" class="form-control" id="gender" name="gender" value="{{$user->gender}}" />
                                    </div>

                                </div>
                                <div class="mt-6">
                                    <button type="submit" class="btn btn-info me-3"> Update </button>
                                </div>
                            </div>

                        </form>
                        <!-- /Account -->
                    </div>
                </div>
                <br>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card bg-secondary">
                            <h5 class="card-header ">Change Password</h5>
                            <div class="card-body">

                                <form method="POST" action="/staffPassword/{{$user->id}}">
                                    @csrf
                                    @method('PATCH')
                                    <div class="row ">
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

                                    </div> <br>
                                    <button type="submit" class="btn btn-danger deactivate-account"> Change </button>
                                </form>

                            </div>
                        </div>
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
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

        </ul>
    </aside>
    <!-- / Menu -->
    @endsection



    @section('content')

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-12">
                <h3 class="card-header text-dark"> <i class="icon-base bx bx-bxs-group"></i> Show System User </h3>
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
                            <form method="post" action="/staff/{{$user->id}}" enctype="multipart/form-data">
                                @csrf
                                @method('PATCH')
                                <!-- Account -->
                                <div class="card-body">
                                    <div class="d-flex align-items-start align-items-sm-center gap-6 pb-4 border-bottom">
<img
src="@if($user->path) {{asset('storage/'.$user->path)}} @else {{asset('img/user.png')}} @endif"
alt="user-avatar"
class="d-block w-px-100 h-px-100 rounded"
id="uploadedAvatar" />
                                    </div>
                                </div>
                                <div class="card-body pt-4">

                                    <div class="row g-6">
                                        <div class="col-md-6">
                                            <label for="name" class="form-label">Full Name</label>
                                            <input
                                                class="form-control"
                                                type="text"
                                                id="name"
                                                name="name"
                                                value="{{$user->name}}"
                                                autofocus
                                                disabled />
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">E-mail</label>
                                            <input
                                                class="form-control"
                                                type="text"
                                                id="email"
                                                name="email"
                                                value="{{$user->email}}"
                                                disabled />
                                        </div>

                                        <div class="col-md-4">
                                            <label for="department_id" class="form-label">Department</label>
                                            <input
                                                class="form-control"
                                                type="text"
                                                id="department_id"
                                                name="department_id"
                                                value="{{$user->department->name}}"
                                                disabled />
                                        </div>

                                        <div class="col-md-4">
                                            <label for="role_id" class="form-label">Role</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                id="role_id"
                                                name="role_id"
                                                value="{{$user->role->name}}"
                                                disabled />
                                        </div>

                                        <div class="col-md-4">
                                            <label for="field_id" class="form-label">Branch</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                id="field_id"
                                                name="field_id"
                                                value="{{$user->field?->name}}"
                                                disabled />
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label" for="phone_number">Phone Number</label>
                                            <div class="input-group input-group-merge">
                                                <!-- <span class="input-group-text"></span> -->
                                                <input
                                                    type="text"
                                                    id="phone_number"
                                                    name="phone_number"
                                                    class="form-control"
                                                    value="{{$user->phone_number}}"
                                                    disabled />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="gender" class="form-label">Gender</label>
                                            <input type="text" class="form-control" id="gender" value="{{$user->gender}}" name="gender" disabled />
                                        </div>

                                    </div><br>
                            </form>
                        </div>
                        <!-- /Account -->
                    </div>

                    <div class="card">
                        <h5 class="card-header">Delete Account</h5>
                        <div class="card-body">
                            <div class="mb-6 col-12 mb-0">
                                <div class="alert alert-warning">
                                    <h5 class="alert-heading mb-1">Are you sure you want to delete your account?</h5>
                                    <p class="mb-0">Once you delete your account, there is no going back. Please be certain.</p>
                                </div>
                            </div>
                            <form>
                                <div class="form-check my-8 ms-2">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="accountActivation"
                                        id="accountActivation" />
                                    <label class="form-check-label" for="accountActivation"> Confirm Account Deletion</label>
                                </div>
                                <button type="submit" class="btn btn-danger deactivate-account">Deletion Account</button>
                            </form>
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
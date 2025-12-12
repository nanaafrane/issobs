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

      <li class="menu-item active open">
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
            <li class="menu-item active">
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

  <!-- Content -->
            <!-- Content -->

            <div class="container-xxl flex-grow-1 container-p-y">
              <div class="card-header  ml-2  d-none d-lg-block">
                  @include('flash-messages')
              </div>
              <div class="row"> 
                <div class="col-md-6">
                <h5 class="fw-bold mb-4"><span class="text-muted fw-light"><i class="bx bxs-user-account"></i> Employee /</span> Show</h5>
                </div>

                  <div class="col-md-6 text-end">
                    <a href="{{url('employeesPayInfo/'.$employee_pay_info->employee_id)}}" class="btn btn-dark mb-3"><i class="bx bx-edit-alt"></i> Edit Payment Info</a>  
                </div>
              </div>

              <div class="row">
                <div class="col-md-12">
                  <ul class="nav nav-pills flex-column flex-md-row mb-3">
                    <li class="nav-item">
                      <a class="nav-link" href="{{url('employees', $employee_pay_info->employee_id)}}"><i class="bx bx-user me-1"></i> Employee Details</a>
                    </li>
                    <li class="nav-item ">
                      <a class="nav-link active" href=" javascript:void(0);" ><i class="bx bxs-comment-detail"></i> Payment Info </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" href="" ><i class="bx bx-money-withdraw"></i> Salaries </a>
                    </li>
                  </ul>
                  <div class="card mb-4">
                    <div class="card-body">

                            <div class="row" id="payment_field"> 
                                <h5 class="card-header"> <strong> Payment Infomation</strong> </h5> 
                                <hr class="mb-3" />
                            
                                <div class="mb-3 col-md-4">
                                  <label for="bank_id" class="form-label"> <strong> {{ __('Bank') }} * </strong>  </label>
                                    <h4> <strong> {{$employee_pay_info->bank?->name}} </strong> </h4> 
                                </div>

                                  <div class="mb-3 col-md-4">
                                  <label for="acc_number" class="form-label"> <strong>   Account Number * </strong> </label>
                                    <h4> <strong> {{$employee_pay_info->acc_number}} </strong> </h4> 
                                </div>


                                  <div class="mb-3 col-md-4">
                                  <label for="branch" class="form-label"> <strong>    Branch * </strong> </label>
                                    <h4> <strong> {{$employee_pay_info->branch}} </strong> </h4> 
                                </div>


                                  <div class="mb-3 col-md-4">
                                  <label for="branch" class="form-label"> <strong>    Branch Code * </strong> </label>
                                    <h4> <strong> {{$employee_pay_info->branch_code}} </strong> </h4> 
                                </div>

                                  <div class="mb-3 col-md-4">
                                  <label for="tin_number" class="form-label"> <strong>    TIN Number  </strong> </label>
                                    <h4> <strong> {{$employee_pay_info->tin_number}} </strong> </h4> 
                                  </div>

                                <div class="mb-3 col-md-4">
                                  <label for="ssnit_number" class="form-label"> <strong>  SSNIT Number </strong> </label>
                                    <h4> <strong> {{$employee_pay_info->ssnit_number}} </strong> </h4> 
                                </div>
                            </div>
                    </div>
                    <!-- /Account -->
                  </div>

                </div>
              </div>
            </div>
            <!-- / Content -->



  <!-- / Content -->

  @endsection


</x-hr-dashboard>
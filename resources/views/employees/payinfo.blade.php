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
        <a href="{{url('field')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-bxs-location-plus"></i>
          <div class="text-truncate" data-i18n="Locations">Locations</div>
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
              <div class="text-truncate" data-i18n="Locations">Employees</div>
            </a>
          </li>

          <li class="menu-item">
            <a href="{{ url('salaries/create') }}" class="menu-link">
              <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
              <div class="text-truncate" data-i18n="Locations">Salaries</div>
            </a>
          </li>

          <li class="menu-item">
            <a href="{{ url('salariesTransaction') }}" class="menu-link">
              <i class="menu-icon tf-icons bx bx-transfer-alt"></i>
              <div class="text-truncate" data-i18n="Locations">Transactions</div>
            </a>
          </li>


          <li class="menu-item">
            <a href="{{ url('salariesInvPayroll') }}" class="menu-link">
              <i class="menu-icon tf-icons bx bx-git-compare"></i>
              <div class="text-truncate" data-i18n="Locations">Invoice to Payroll</div>
            </a>
          </li>

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
              <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light"> <i class="bx bxs-user-account"></i> Employee /</span> Edit</h4>

              <div class="row">
                <div class="col-md-12">
                  <ul class="nav nav-pills flex-column flex-md-row mb-3">
                    <li class="nav-item">
                      <a class="nav-link" href="/employees/{{$employee_pay_info->employee_id}}/edit"><i class="bx bx-user me-1"></i> Employee Details</a>
                    </li>
                    <li class="nav-item ">
                      <a class="nav-link active" href=" javascript:void(0);" ><i class="bx bxs-comment-detail"></i> Payment Info </a>
                    </li>

                  </ul>
                  <div class="card mb-4">
                    <div class="card-body">
                        <form action="">
                            <div class="row" id="payment_field"> 
                                <h5 class="card-header"> <strong> Payment Infomation</strong> </h5> 
                                <hr class="mb-3" />
                            
                                <div class="mb-3 col-md-4">
                                  <label for="bank_id" class="form-label"> <strong> {{ __('Bank') }} * </strong>  </label>
                                    <select name="bank_id" class="form-select @error('bank_id') is-invalid @enderror" id="bank_id">
                                        @foreach($banks as $bank)
                                        <option  @if($bank->id == $employee_pay_info->bank_id) selected @endif  value="{{$bank->id}}">{{$bank->name}}</option>
                                        @endforeach
                                    </select>
                                    @error('bank_id')
                                      <span class="invalid-feedback" role="alert">
                                          <strong>{{ $message }}</strong>
                                      </span>
                                    @enderror
                                </div>

                                  <div class="mb-3 col-md-4">
                                  <label for="acc_number" class="form-label"> <strong>   Account Number * </strong> </label>
                                  <input  class="form-control @error('acc_number') is-invalid @enderror" type="number" id="acc_number" name="acc_number" placeholder="Your Account Number" value="{{$employee_pay_info->acc_number}}" autofocus />
                                    @error('acc_number')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>


                                  <div class="mb-3 col-md-4">
                                  <label for="branch" class="form-label"> <strong>    Branch * </strong> </label>
                                  <input  class="form-control @error('branch') is-invalid @enderror" type="text" id="branch" name="branch" placeholder=" Branch " value="{{$employee_pay_info->branch}}" autofocus />
                                    @error('branch')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                                  <div class="mb-3 col-md-4">
                                  <label for="tin_number" class="form-label"> <strong>    TIN Number  </strong> </label>
                                  <input  class="form-control @error('tin_number') is-invalid @enderror" type="text" id="tin_number" name="tin_number" placeholder="TIN Number" value="{{$employee_pay_info->tin_number}}" autofocus />
                                    @error('tin_number')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                  </div>

                                <div class="mb-3 col-md-4">
                                  <label for="ssnit_number" class="form-label"> <strong>  SSNIT Number </strong> </label>
                                  <input  class="form-control @error('ssnit_number') is-invalid @enderror" type="text" id="ssnit_number" name="ssnit_number" placeholder="SSNIT Number " value="{{$employee_pay_info->ssnit_number}}" autofocus />
                                    @error('ssnit_number')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                        
                          <div class="mt-2">
                            <button type="submit" class="btn btn-primary me-2">Update </button>
                          </div>
                        
                          </form>

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
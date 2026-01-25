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
                    <li class="menu-item active open">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bxs-user-account"></i>
          <div class="text-truncate" data-i18n="Staffs">Employees</div>
          </a>
          <ul class="menu-sub">
          <li class="menu-item active">
              <a href="{{url('employees/create')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SRegister">Register</div>
              </a>
          </li>
          <li class="menu-item">
              <a href="{{url('employees')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">List</div>
              </a>
          </li>
          <li class="menu-item">
              <a href="{{url('employeesBank')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">Employee Banks</div>
              </a>
          </li>
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
            <li class="menu-item active open ">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bxs-user-account"></i>
                <div class="text-truncate" data-i18n="Staffs">Employees</div>
                </a>
                <ul class="menu-sub">
                <li class="menu-item active">
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
        
            <li class="menu-item ">
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

  <!-- Content -->
            <div class="container-xxl flex-grow-1 container-p-y">
              <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light"><i class="bx bxs-user-account"></i> Employee /</span> Register</h4>

              <div class="row">
                <div class="col-md-12">
                  <ul class="nav nav-pills flex-column flex-md-row mb-3">
                    <li class="nav-item">
                      <a class="nav-link active" href="javascript:void(0);"><i class="bx bx-user me-1"></i> Employee Details</a>
                    </li>
                  </ul>

                  <form  action="{{ url('employees') }}" method="POST" enctype="multipart/form-data">
                  @csrf
                    <div class="card mb-4">
                      <h5 class="card-header"><strong> Basic Infomation</strong> </h5>
                      <!-- Account -->
                      <div class="card-body">
                        <div class="d-flex align-items-start align-items-sm-center gap-4">
                         
                            <div class="button-wrapper">
                                <label for="image" class="btn btn-dark  me-3 mb-4" tabindex="0">
                                    <!-- <span class="d-none d-sm-block"> Attach   </span> -->
                                    <input
                                        type="file"
                                        id="image"
                                        name="image"
                                        class="account-file-input  @error('image') is-invalid @enderror"
                                        accept="image/png, image/jpeg" />
                                    @error('image')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </label>
                                <div>Kindly attach Employee Image, Max size of 2MB </div>

                            </div>

                        </div>
                      </div>
                      <hr class="my-0" />
                      <div class="card-body">
                          <div class="row">
                            <div class="mb-3 col-md-4">
                              <label for="name" class="form-label"> <strong>Full Name * </strong> </label>
                              <input  class="form-control @error('name') is-invalid @enderror" type="text" id="name" placeholder="Full Name" name="name" value="{{old('name')}}" autofocus required/>
                                @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                           
                           
                            <div class="mb-3 col-md-4">
                              <label for="gender" class="form-label"> <strong>Gender * </strong> </label>
                             <select name="gender" class="form-select @error('gender') is-invalid @enderror" id="gender" value="{{ old('gender')}}" required>
                                    <option disabled selected>Choose...</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                                @error('gender')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                           
                           
                            <div class="mb-3 col-md-4">
                            <label for="phone_number" class="form-label"> <strong>{{ __('Phone Number *') }} </strong> </label>
                            <input
                                type="number"
                                id="phone_number"
                                name="phone_number"
                                class="form-control @error('phone_number') is-invalid @enderror"
                                value="{{ old('phone_number')}}"
                                placeholder="Phone Number "
                                autocomplete=" phone_number"
                                autofocus
                                required>

                            @error('phone_number')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                            </div>
                            
                            <div class="mb-3 col-md-4">
                              <label for="date_of_birth" class="form-label"> <strong>Date Of Birth </strong> </label>
                              <input  class="form-control @error('date_of_birth') is-invalid @enderror" type="date" id="date_of_birth" name="date_of_birth" placeholder="Male or Female" value="{{old('date_of_birth')}}" autofocus />
                                @error('date_of_birth')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="nia_number" class="form-label"> <strong>NIA Number  </strong> </label>
                              <input  class="form-control @error('nia_number') is-invalid @enderror" type="text" id="nia_number" name="nia_number" placeholder="GHAXXXXXXXXX-X" value="{{old('nia_number')}}" autofocus />
                                @error('nia_number')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="address" class="form-label"> <strong> Digital Address  </strong> </label>
                              <input  class="form-control @error('address') is-invalid @enderror" type="text" id="address" name="address" placeholder="GC-XXX-X" value="{{old('address')}}" autofocus />
                                @error('address')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="marital_status" class="form-label"><strong>Marital Status *</strong> </label>
                                <select name="marital_status" class="form-select @error('marital_status') is-invalid @enderror" id="marital_status" value="{{ old('marital_status')}}" >
                                    <option disabled selected>Choose...</option>
                                    <option value="single">Single</option>
                                    <option value="married">Married</option>
                                    <option value="divorced">Divorced</option>  
                                    <option value="widowed">Widowed</option>

                                </select>
                                @error('marital_status')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="worker_type" class="form-label"><strong>Worker Type *</strong> </label>
                                <select name="worker_type" class="form-select @error('worker_type') is-invalid @enderror" id="worker_type" value="{{ old('worker_type')}}" required>
                                    <option disabled selected>Choose...</option>
                                    <option value="employee">Employee</option>
                                    <option value="contractor">Contractor</option>

                                </select>
                                @error('worker_type')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="date_of_joining" class="form-label"> <strong>  Date Of Joining * </strong> </label>
                              <input  class="form-control @error('date_of_joining') is-invalid @enderror" type="date" id="date_of_joining" name="date_of_joining"  value="{{old('date_of_joining')}}" autofocus required/>
                                @error('date_of_joining')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="department_id" class="form-label"> <strong> {{ __('Department') }} * </strong>  </label>
                                <select name="department_id" class="form-select @error('department_id') is-invalid @enderror" id="department_id" required>
                                    <option selected disabled>Assign Department </option>
                                    @foreach($Departments as $department)
                                    <option value="{{$department->id}}">{{$department->name}}</option>
                                    @endforeach
                                </select>
                                @error('department_id')
                                  <span class="invalid-feedback" role="alert">
                                      <strong>{{ $message }}</strong>
                                  </span>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="role_id" class="form-label"> <strong> {{ __('Role') }} * </strong>  </label>
                                <select name="role_id" class="form-select @error('role_id') is-invalid @enderror" id="role_id" required>
                                    <option selected disabled>Assign Role </option>
                                    @foreach($Roles as $role)
                                    <option value="{{$role->id}}">{{$role->name}}</option>
                                    @endforeach
                                </select>
                                @error('role_id')
                                  <span class="invalid-feedback" role="alert">
                                      <strong>{{ $message }}</strong>
                                  </span>
                                @enderror
                            </div>


                            <div class="mb-3 col-md-4">
                              <label for="field_id" class="form-label"> <strong> {{ __('Field Office') }} * </strong>  </label>
                                <select name="field_id" class="form-select @error('field_id') is-invalid @enderror" id="field_id" required>
                                    <option selected disabled>Assign Field Office </option>
                                    @foreach($Fields as $field)
                                    <option value="{{$field->id}}">{{$field->name}}</option>
                                    @endforeach
                                </select>
                                @error('field_id')
                                  <span class="invalid-feedback" role="alert">
                                      <strong>{{ $message }}</strong>
                                  </span>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="client_id" class="form-label"> <strong> {{ __('Client') }} </strong>  </label>
                                <select name="client_id" class="form-select @error('client_id') is-invalid @enderror" id="client_id" required>
                                    <option selected disabled>Assign Field Office </option>
                                    @foreach($clients as $client)
                                    <option value="{{$client->id}}">{{$client->name}} {{$client->business_name}}</option>
                                    @endforeach
                                </select>
                                @error('client_id')
                                  <span class="invalid-feedback" role="alert">
                                      <strong>{{ $message }}</strong>
                                  </span>
                                @enderror
                            </div>


                            <div class="mb-3 col-md-4">
                              <label for="location" class="form-label"> <strong>  {{ __('Location') }}  </strong> </label>
                              <input  class="form-control @error('location') is-invalid @enderror" type="text" id="location" name="location"  placeholder="Address Name or Location Name" value="{{old('location')}}" autofocus required/>
                                @error('location')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-4">
                              <label for="payment_type" class="form-label"> <strong> {{ __('Payment Type') }} *</strong>  </label>
                                <select name="payment_type" class="form-select @error('payment_type') is-invalid @enderror" id="payment_type" required>
                                    <option selected disabled>Choose... </option>
                                    <option value="Bank">Bank</option>
                                    <option value="Cash">Cash</option>
                                </select>
                                @error('payment_type')
                                  <span class="invalid-feedback" role="alert">
                                      <strong>{{ $message }}</strong>
                                  </span>
                                @enderror
                            </div>                       

                              <div class="row"> 
                                <div class="col-md-2"></div>
                                <div class="mb-3 col-md-4">
                                  <label for="basic_salary" class="form-label"> <strong>  Basic Salary * </strong> </label>
                                  <input  class="form-control @error('basic_salary') is-invalid @enderror" type="number" id="basic_salary" name="basic_salary" placeholder="GH&#x20B5;" value="{{old('basic_salary')}}" autofocus required step="any"/>
                                    @error('basic_salary')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                                <div class="mb-3 col-md-4">
                                  <label for="allowances" class="form-label"> <strong>  Allawonce * </strong> </label>
                                  <input  class="form-control @error('allowances') is-invalid @enderror" type="number" id="allowances" name="allowances" placeholder="GH&#x20B5;" value="{{old('allowances')}}" autofocus required step="any"/>
                                    @error('allowances')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                                <div class="col-md-2"></div>
                              </div>

                          <div class="row" id="payment_field" style="display: none;"> 
                            <h5 class="card-header"> <strong> Payment Infomation</strong> </h5> 
                            <hr class="mb-3" />
                         
                            <div class="mb-3 col-md-4">
                              <label for="bank_id" class="form-label"> <strong> {{ __('Bank') }} * </strong>  </label>
                                <select name="bank_id" class="form-select @error('bank_id') is-invalid @enderror" id="bank_id">
                                    <option selected disabled> Choose... </option>
                                    @foreach($banks as $bank)
                                    <option value="{{$bank->id}}">{{$bank->name}}</option>
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
                              <input  class="form-control @error('acc_number') is-invalid @enderror" type="number" id="acc_number" name="acc_number" placeholder="Your Account Number" value="{{old('acc_number')}}" autofocus />
                                @error('acc_number')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>


                              <div class="mb-3 col-md-4">
                              <label for="branch" class="form-label"> <strong>    Branch * </strong> </label>
                              <input  class="form-control @error('branch') is-invalid @enderror" type="text" id="branch" name="branch" placeholder=" Branch " value="{{old('branch')}}" autofocus />
                                @error('branch')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="branch_code" class="form-label"> <strong>    Branch Code </strong> </label>
                              <input  class="form-control @error('branch_code') is-invalid @enderror" type="text" id="branch_code" name="branch_code" placeholder=" branch_code " value="{{old('branch_code')}}" autofocus />
                                @error('branch_code')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                              <div class="mb-3 col-md-4">
                              <label for="tin_number" class="form-label"> <strong>    TIN Number  </strong> </label>
                              <input  class="form-control @error('tin_number') is-invalid @enderror" type="text" id="tin_number" name="tin_number" placeholder="TIN Number" value="{{old('tin_number')}}" autofocus />
                                @error('tin_number')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                              </div>

                            <div class="mb-3 col-md-4">
                              <label for="ssnit_number" class="form-label"> <strong>  SSNIT Number </strong> </label>
                              <input  class="form-control @error('ssnit_number') is-invalid @enderror" type="text" id="ssnit_number" name="ssnit_number" placeholder="SSNIT Number " value="{{old('ssnit_number')}}" autofocus />
                                @error('ssnit_number')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                            <h5 class="card-header"> <strong>Gurantor Infomation</strong> </h5> 
                            <hr class="mb-3" />

                            <div class="mb-3 col-md-4">
                              <label for="gurantor_name" class="form-label"> <strong>   Gurantor Name  </strong> </label>
                              <input  class="form-control @error('gurantor_name') is-invalid @enderror" type="text" id="gurantor_name" name="gurantor_name" placeholder="Gurantor Full Name" value="{{old('gurantor_name')}}" autofocus />
                                @error('gurantor_name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                              <div class="mb-3 col-md-4">
                              <label for="gurantor_number" class="form-label"> <strong>   Gurantor Number  </strong> </label>
                              <input  class="form-control @error('gurantor_number') is-invalid @enderror" type="number" id="gurantor_number" name="gurantor_number" placeholder="Gurantor Number" value="{{old('gurantor_number')}}" autofocus />
                                @error('gurantor_number')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>


                              <div class="mb-3 col-md-4">
                              <label for="gurantor_address" class="form-label"> <strong>   Gurantor Address  </strong> </label>
                              <input  class="form-control @error('gurantor_address') is-invalid @enderror" type="text" id="gurantor_address" name="gurantor_address" placeholder="Gurantor Address" value="{{old('gurantor_address')}}" autofocus />
                                @error('gurantor_address')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                              <div class="mb-3 col-md-4">
                              <label for="gurantor_nia_number" class="form-label"> <strong>   Gurantor NIA Number  </strong> </label>
                              <input  class="form-control @error('gurantor_nia_number') is-invalid @enderror" type="text" id="gurantor_nia_number" name="gurantor_nia_number" placeholder="Gurantor NIA Number" value="{{old('gurantor_nia_number')}}" autofocus />
                                @error('gurantor_nia_number')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                              </div>

                            <div class="mb-3 col-md-4">
                              <label for="relationship" class="form-label"> <strong>  Relationship with Gurantor  </strong> </label>
                              <input  class="form-control @error('relationship') is-invalid @enderror" type="text" id="relationship" name="relationship" placeholder="Mother or Father..." value="{{old('relationship')}}" autofocus />
                                @error('relationship')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>




                          </div>
                          <div class="mt-2">
                            <button type="submit" class="btn btn-primary me-2">Save </button>
                            <button type="reset" class="btn btn-outline-secondary">Cancel</button>
                          </div>
                      </div>
                      <!-- /Account -->
                    </div>
                  </form>
              
                  
                </div>
              </div>
            </div>
  <!-- / Content -->

  @endsection



  @section('scripts')
    
  <script>
        $(document).ready(function() {
            $('#payment_type').change(function() {
              // var value = $(this).val();     
              // console.log(" Value: " + value);

                if ($(this).val() == 'Bank') {
                    $('#payment_field').toggle();
                    // console.log(" Value: " + value);
                } else {
                    $('#payment_field').toggle();
                }
            });
        });
</script>


  @endsection

</x-hr-dashboard>
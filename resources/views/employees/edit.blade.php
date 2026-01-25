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
              <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light"><i class="bx bxs-user-account"></i> Employee /</span> Edit</h4>
       
              <div class="card-header  ml-2  d-none d-lg-block">
                  @include('flash-messages')
              </div>
              
              <div class="row">
                <div class="col-md-12">
                  <ul class="nav nav-pills flex-column flex-md-row mb-3">
                    <li class="nav-item">
                      <a class="nav-link active" href="javascript:void(0);"><i class="bx bx-user me-1"></i> Employee Details</a>
                    </li>
                    <li class="nav-item ">
                      <a class="nav-link" href=" {{url('employeesPayInfo', $employee->id)}}" ><i class="bx bxs-comment-detail"></i> Payment Info </a>
                    </li>
                  </ul>


                  <form  action="/employees/{{$employee->id}}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="card mb-4">
                      <h5 class="card-header"><strong> Basic Infomation</strong> </h5>

                      <!-- Account -->
                      <div class="card-body">
                    
                            <div class="col-md-6 form-check form-switch ">
                                <input name="tax_button" class="form-check-input" type="checkbox"  @if ($employee->tax_button == "on") checked @endif id="tax_button">
                                <label class="form-check-label" for="tax_button"> TAX EMPLOYEE </label>
                            </div> 
                            
                            <div class="col-md-6 form-check form-switch ">
                                <input name="ssnit_button" class="form-check-input" type="checkbox"  @if ($employee->ssnit_button == "on") checked @endif id="ssnit_button">
                                <label class="form-check-label" for="ssnit_button"> DEDUCT SSNIT FOR EMPLOYEE </label>
                            </div> 
                        <br>

                        <div class="d-flex align-items-start align-items-sm-center gap-4">
                           <img
                                        src="@if($employee->image) {{asset($employee->image)}} @else {{asset('img/user.png')}} @endif"
                                        alt="user-avatar"
                                        class="d-block w-px-100 h-px-100 rounded"
                                        id="uploadedAvatar" />
                                        
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
                              <input  class="form-control @error('name') is-invalid @enderror" type="text" id="name" placeholder="Full Name" name="name" value="{{$employee->name}}" autofocus />
                                @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                           
                           
                            <div class="mb-3 col-md-4">
                              <label for="gender" class="form-label"> <strong>Gender * </strong> </label>
                             <select name="gender" class="form-select @error('gender') is-invalid @enderror" id="gender"  >
                                   
                                    <option @if ($employee->gender == 'male') selected @endif  value="male">Male</option>

                                    <option @if ($employee->gender == 'female') selected @endif value="female">Female</option>
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
                                value="{{ $employee->phone_number}}"
                                placeholder="Phone Number "
                                autocomplete=" phone_number"
                                autofocus
                                >

                            @error('phone_number')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                            </div>
                            
                            <div class="mb-3 col-md-4">
                              <label for="date_of_birth" class="form-label"> <strong>Date Of Birth *</strong> </label>
                              <input  class="form-control @error('date_of_birth') is-invalid @enderror" type="date" id="date_of_birth" name="date_of_birth"  value="{{$employee->date_of_birth?->format('Y-m-d')}}" autofocus />
                                @error('date_of_birth')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="nia_number" class="form-label"> <strong>NIA Number * </strong> </label>
                              <input  class="form-control @error('nia_number') is-invalid @enderror" type="text" id="nia_number" name="nia_number" placeholder="GHAXXXXXXXXX-X" value="{{$employee->nia_number}}" autofocus />
                                @error('nia_number')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="address" class="form-label"> <strong> Digital Address * </strong> </label>
                              <input  class="form-control @error('address') is-invalid @enderror" type="text" id="address" name="address" placeholder="GC-XXX-X" value="{{$employee->address}}" autofocus />
                                @error('address')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="marital_status" class="form-label"><strong>Marital Status *</strong> </label>
                                <select name="marital_status" class="form-select @error('marital_status') is-invalid @enderror" id="marital_status" value="{{ old('marital_status')}}" >
                                    <option @if ($employee->gender == 'single') selected @endif value="single">Single</option>
                                    <option @if ($employee->gender == 'married') selected @endif value="married">Married</option>
                                    <option @if ($employee->gender == 'divorced') selected @endif value="divorced">Divorced</option>  
                                    <option @if ($employee->gender == 'widowed') selected @endif value="widowed">Widowed</option>

                                </select>
                                @error('marital_status')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="worker_type" class="form-label"><strong>Worker Type *</strong> </label>
                                <select name="worker_type" class="form-select @error('worker_type') is-invalid @enderror" id="worker_type" value="{{ old('worker_type')}}" >
                                    <option @if ($employee->worker_type == 'employee') selected @endif  value="employee">Employee</option>
                                    <option @if ($employee->worker_type == 'contractor') selected @endif value="contractor">Contractor</option>

                                </select>
                                @error('worker_type')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="date_of_joining" class="form-label"> <strong>  Date Of Joining * </strong> </label>
                              <input  class="form-control @error('date_of_joining') is-invalid @enderror" type="date" id="date_of_joining" name="date_of_joining"  value="{{$employee->date_of_joining?->format('Y-m-d') }}" autofocus />
                                @error('date_of_joining')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="department_id" class="form-label"> <strong> {{ __('Department') }} * </strong>  </label>
                                <select name="department_id" class="form-select @error('department_id') is-invalid @enderror" id="department_id" >
                                    @foreach($Departments as $department)
                                    <option @if($department->name == $employee->department?->name) selected @endif value="{{$department->id}}">{{$department->name}}</option>
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
                                <select name="role_id" class="form-select @error('role_id') is-invalid @enderror" id="role_id" >
                                  @foreach($Roles as $role)
                                  <option @if($role->name == $employee->role?->name) selected @endif value="{{$role->id}}">{{$role->name}}</option>
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
                                <select name="field_id" class="form-select @error('field_id') is-invalid @enderror" id="field_id" >
                                  @foreach($Fields as $field)
                                  <option @if($field?->name == $employee->field?->name) selected @endif value="{{$field?->id}}">{{$field?->name}}</option>
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
                                <select name="client_id" class="form-select @error('client_id') is-invalid @enderror" id="client_id">
                                  <option  value=""> Choose... </option>
                                    @foreach($clients as $client)
                                    <option  @if($client->id == $employee->client?->id) selected @endif  value="{{$client->id}}">{{$client->name}} {{$client->business_name}}</option>
                                    @endforeach
                                </select>
                                @error('client_id')
                                  <span class="invalid-feedback" role="alert">
                                      <strong>{{ $message }}</strong>
                                  </span>
                                @enderror
                            </div>


                            <div class="mb-3 col-md-4">
                              <label for="location" class="form-label"> <strong>  {{ __('Location') }} * </strong> </label>
                              <input  class="form-control @error('location') is-invalid @enderror" type="text" id="location" name="location"  placeholder="Address Name or Location Name" value="{{$employee->location}}" autofocus />
                                @error('location')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-4">
                              <label for="payment_type" class="form-label"> <strong> {{ __('Payment Type') }} *</strong>  </label>
                                <select name="payment_type" class="form-select @error('payment_type') is-invalid @enderror" id="payment_type" >
                                    <option @if ($employee->payment_type == 'Bank') selected @endif value="bank">Bank</option>
                                    <option  @if ($employee->payment_type == 'Cash') selected @endif value="cash">Cash</option>
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
                                <input  class="form-control @error('basic_salary') is-invalid @enderror" type="number" id="basic_salary" name="basic_salary" placeholder="GH&#x20B5;" value="{{$employee->basic_salary}}" autofocus  step="any"/>
                                  @error('basic_salary')
                                  <span class="invalid-feedback" role="alert">
                                      <strong>{{ $message }}</strong>
                                  </span>
                                  @enderror
                              </div>

                              <div class="mb-3 col-md-4">
                                <label for="allowances" class="form-label"> <strong>  Allowances * </strong> </label>
                                <input  class="form-control @error('allowances') is-invalid @enderror" type="number" id="allowances" name="allowances" placeholder="GH&#x20B5;" value="{{$employee->allowances}}" autofocus  step="any"/>
                                  @error('allowances')
                                  <span class="invalid-feedback" role="alert">
                                      <strong>{{ $message }}</strong>
                                  </span>
                                  @enderror
                              </div> 
                                <div class="col-md-2"></div>
                            </div>


                            <h5 class="card-header"> <strong>Gurantor Infomation</strong> </h5> 
                            <hr class="mb-3" />

                            <div class="mb-3 col-md-4">
                              <label for="gurantor_name" class="form-label"> <strong>   Gurantor Name * </strong> </label>
                              <input  class="form-control @error('gurantor_name') is-invalid @enderror" type="text" id="gurantor_name" name="gurantor_name" placeholder="Gurantor Full Name" value="{{$employee->gurantor_name}}" autofocus />
                                @error('gurantor_name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                              <div class="mb-3 col-md-4">
                              <label for="gurantor_number" class="form-label"> <strong>   Gurantor Number * </strong> </label>
                              <input  class="form-control @error('gurantor_number') is-invalid @enderror" type="number" id="gurantor_number" name="gurantor_number" placeholder="Gurantor Number" value="{{$employee->gurantor_number}}" autofocus />
                                @error('gurantor_number')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>


                              <div class="mb-3 col-md-4">
                              <label for="gurantor_address" class="form-label"> <strong>   Gurantor Address * </strong> </label>
                              <input  class="form-control @error('gurantor_address') is-invalid @enderror" type="text" id="gurantor_address" name="gurantor_address" placeholder="Gurantor Address" value="{{$employee->gurantor_address}}" autofocus />
                                @error('gurantor_address')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                              <div class="mb-3 col-md-4">
                              <label for="gurantor_nia_number" class="form-label"> <strong>   Gurantor NIA Number * </strong> </label>
                              <input  class="form-control @error('gurantor_nia_number') is-invalid @enderror" type="text" id="gurantor_nia_number" name="gurantor_nia_number" placeholder="Gurantor NIA Number" value="{{$employee->gurantor_nia_number}}" autofocus />
                                @error('gurantor_nia_number')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                              </div>

                            <div class="mb-3 col-md-4">
                              <label for="relationship" class="form-label"> <strong>  Relationship with Gurantor * </strong> </label>
                              <input  class="form-control @error('relationship') is-invalid @enderror" type="text" id="relationship" name="relationship" placeholder="Mother or Father..." value="{{$employee->relationship}}" autofocus />
                                @error('relationship')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>




                          </div>
                          <div class="mt-2">
                            <button type="submit" class="btn btn-primary me-2">Update </button>
                          </div>
                      </div>
                      <!-- /Account -->
                    </div>
                  </form>
              
                  
                </div>
              </div>
            </div>
  <!-- / Content -->

          <div class="buy-now">
            @if ($employee->status !== 'Active')
            <a style="margin-bottom: 70px;" href="{{url('employeeReinstate', $employee->id )}}" class="btn btn-danger btn-buy-now"> <i class="icon-base bx bx-edit-alt me-1"></i> Re-Instate </a>
              
            @else
            <a  href="{{url('terminateEmployee', $employee->id )}}" class="btn btn-danger btn-buy-now"> <i class="icon-base bx bx-trash me-1"></i> Terminate </a>  
              
            @endif
          </div>
  @endsection


</x-hr-dashboard>
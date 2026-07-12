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
                            <div class="text-truncate" data-i18n="CRegister">Register</div>
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
                            <div class="text-truncate" data-i18n="SRegister">Register</div>
                            </a>
                        </li>
                        <li class="menu-item active">
                            <a href="{{url('employees')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="SList">List</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="{{url('employeesPending')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="SList">Pending</div>
                            </a>
                        </li>
                        <li class="menu-item ">
                            <a href="{{url('employeesnrrit')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="SList">Terminate / Recruit</div>
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
                    <div class="text-truncate" data-i18n="SRegister">Register</div>
                    </a>
                </li>
                <li class="menu-item active">
                    <a href="{{url('employees')}}" class="menu-link">
                    <div class="text-truncate" data-i18n="SList">List</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{url('employeesPending')}}" class="menu-link">
                    <div class="text-truncate" data-i18n="SList">Pending</div>
                    </a>
                </li>
                @if(Auth::user()->hasRole(['Manager', 'Invoice']))
                <li class="menu-item ">
                    <a href="{{url('employeesnrrit')}}" class="menu-link">
                    <div class="text-truncate" data-i18n="SList">Terminate / Recruit</div>
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

            @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))
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

                                    <!-- <li class="menu-item">
                    <a href="{{ url('salariesBulkCash') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bxs-group"></i>
                    <div class="text-truncate" data-i18n="Transaction">Bulk Cash Salaries</div>
                    </a>
                </li> -->

                    <!-- <li class="menu-item">
                        <a href="{{ url('salariesInvPayroll') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-git-compare"></i>
                        <div class="text-truncate" data-i18n="InvtoPayroll">Invoice to Payroll</div>
                        </a>
                    </li> -->
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
    <div class="card mb-4">
      <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
          <h4 class="fw-bold mb-1">Edit employee</h4>
          <p class="text-muted mb-0">Update profile and payroll settings for {{ $employee->name }}.</p>
        </div>
        <div class="text-md-end">
          <a href="{{ url('employees', $employee->id) }}" class="btn btn-outline-secondary btn-sm mb-2">
            <i class="bx bx-user me-1"></i> Employee Details
          </a>
          <a href="{{ url('employeesPayInfo', $employee->id) }}" class="btn btn-outline-secondary btn-sm mb-2">
            <i class="bx bxs-comment-detail me-1"></i> Bank Details
          </a>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header"><strong>Summary</strong></div>
      <div class="card-body">
      <form action="/employees/{{ $employee->id }}" method="POST" enctype="multipart/form-data">
              @csrf
              @method('PUT')

        <div class="row g-4">

          <div class="col-lg-4">
            <div class="card bg-info text-white h-80">
              <div class="card-body text-center">
                <img src="@if($employee->image) {{ asset($employee->image) }} @else {{ asset('img/user.png') }} @endif" alt="user-avatar" class="rounded-circle mb-3 w-px-120 h-px-120 object-fit-cover" />
                <h4 class="card-title text-white mb-1"> <strong> {{ strtoupper($employee->name) }} </strong> </h4>
                <p class="text-muted mb-2"> <strong> {{ strtoupper($employee->role?->name) ?? 'Employee' }} </strong> </p>
                <span class="badge {{ $employee->status === 'Active' ? 'bg-success' : 'bg-danger' }} mb-3">{{ $employee->status }}</span>
               
                  <!-- <hr> -->
                <div class="row p-3">
                    <div class="col-md-3 form-check form-switch">
                        <input name="tin" class="form-check-input" type="checkbox" id="tin" @if ($employee->tax_button == 'on') checked @endif>
                        <label class="form-check-label text-white" for="tin">  TIN  </label>
                    </div>

                    <div class="mb-3 col-md-9" id="tin_number" @if ( isset($employee->tax_button)  ) style="display: flex;" @else style="display: none;" @endif >
                      <label for="tin_number" class="form-label text-white"> <strong>    TIN Number  </strong> </label>
                      <input  class="form-control @error('tin_number') is-invalid @enderror text-white" type="text"  name="tin_number" placeholder="TIN Number" value="{{ $employee->tin_number }}" autofocus />
                        @error('tin_number')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                      </div>
                </div>

                  <div class="row p-3">
                        <div class="col-md-3 form-check form-switch">
                            <input name="ssnit" class="form-check-input" type="checkbox" id="ssnit" @if ($employee->ssnit_button == 'on') checked @endif>
                            <label class="form-check-label text-white" for="ssnit">  SSNIT  </label>
                        </div>

                        <div class="mb-3 col-md-9" id="ssnit_number" @if ( isset($employee->ssnit_button)  ) style="display: flex;" @else style="display: none;" @endif >
                          <label for="ssnit_number" class="form-label text-white"> <strong>  SSNIT Number </strong> </label>
                          <input  class="form-control @error('ssnit_number') is-invalid @enderror text-white" type="text" id="ssnit_number" name="ssnit_number" placeholder="SSNIT Number " value="{{ $employee->ssnit_number }}" autofocus />
                            @error('ssnit_number')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                    </div>
               
                <div class="text-start text-white">
                  <small class="text-muted">Phone </small>
                  <h5 class="mb-1 text-white"><strong> {{ strtoupper( $employee->phone_number ?: 'Not provided' ) }}</strong></h5>

                  <small class="text-muted">Client Name</small>
                  <h5 class="mb-1 text-white"><strong>{{ strtoupper($employee->client->business_name) ?: '-' }}</strong></h5>
                  <small class="text-muted">Location</small>
                  <h5 class="mb-1 text-white"><strong>{{ strtoupper($employee->location) ?: '-' }}</strong></h5>
                </div>

              </div>
            </div>
          </div>

          <div class="col-lg-8">

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
              <hr>

              <div class="row g-3">
                <div class="col-md-6">
                  <label for="name" class="form-label"><strong>Full Name *</strong></label>
                  <input class="form-control @error('name') is-invalid @enderror" type="text" id="name" name="name" value="{{ $employee->name }}" autofocus />
                  @error('name')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-3">
                  <label for="gender" class="form-label"><strong>Gender *</strong></label>
                  <select name="gender" id="gender" class="form-select @error('gender') is-invalid @enderror">
                    <option @if ($employee->gender == 'male') selected @endif value="male">Male</option>
                    <option @if ($employee->gender == 'female') selected @endif value="female">Female</option>
                  </select>
                  @error('gender')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-3">
                  <label for="phone_number" class="form-label"><strong>Phone Number *</strong></label>
                  <input type="number" id="phone_number" name="phone_number" class="form-control @error('phone_number') is-invalid @enderror" value="{{ $employee->phone_number }}" />
                  @error('phone_number')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-3">
                  <label for="channel" class="form-label"><strong>Channel</strong></label>
                  <select name="channel" id="channel" class="form-select @error('channel') is-invalid @enderror">
                    <option disabled selected value="">Choose...</option>
                    @foreach($channels as $channel)
                      <option @if($employee?->channel == $channel->channel) selected @endif value="{{ $channel->channel }}">{{ $channel->name }}</option>
                    @endforeach
                  </select>
                  @error('channel')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-3">
                  <label for="date_of_birth" class="form-label"><strong>Date Of Birth *</strong></label>
                  <input class="form-control @error('date_of_birth') is-invalid @enderror" type="date" id="date_of_birth" name="date_of_birth" value="{{ $employee->date_of_birth?->format('Y-m-d') }}" />
                  @error('date_of_birth')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-3">
                  <label for="nia_number" class="form-label"><strong>NIA Number *</strong></label>
                  <input class="form-control @error('nia_number') is-invalid @enderror" type="text" id="nia_number" name="nia_number" value="{{ $employee->nia_number }}" />
                  @error('nia_number')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-3">
                  <label for="address" class="form-label"><strong>Digital Address / Residence</strong></label>
                  <input class="form-control @error('address') is-invalid @enderror" type="text" id="address" name="address" value="{{ $employee->address }}" />
                  @error('address')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-3">
                  <label for="marital_status" class="form-label"><strong>Marital Status *</strong></label>
                  <select name="marital_status" id="marital_status" class="form-select @error('marital_status') is-invalid @enderror">
                    <option @if ($employee->marital_status == 'single') selected @endif value="single">Single</option>
                    <option @if ($employee->marital_status == 'married') selected @endif value="married">Married</option>
                    <option @if ($employee->marital_status == 'divorced') selected @endif value="divorced">Divorced</option>
                    <option @if ($employee->marital_status == 'widowed') selected @endif value="widowed">Widowed</option>
                  </select>
                  @error('marital_status')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-3">
                  <label for="worker_type" class="form-label"><strong>Worker Type *</strong></label>
                  <select name="worker_type" id="worker_type" class="form-select @error('worker_type') is-invalid @enderror">
                    <option @if($employee->worker_type == 'employee') selected @endif value="employee">Employee</option>
                    <option @if($employee->worker_type == 'contractor') selected @endif value="contractor">Contractor</option>
                  </select>
                  @error('worker_type')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-3">
                  <label for="date_of_joining" class="form-label"><strong>Date Of Joining *</strong></label>
                  <input class="form-control @error('date_of_joining') is-invalid @enderror" type="date" id="date_of_joining" name="date_of_joining" value="{{ $employee->date_of_joining?->format('Y-m-d') }}" />
                  @error('date_of_joining')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-3">
                  <label for="department_id" class="form-label"><strong>Department *</strong></label>
                  <select name="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror">
                    @foreach($Departments as $department)
                      <option @if($department->name == $employee->department?->name) selected @endif value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                  </select>
                  @error('department_id')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-3">
                  <label for="role_id" class="form-label"><strong>Role *</strong></label>
                  <select name="role_id" id="role_id" class="form-select @error('role_id') is-invalid @enderror">
                    @foreach($Roles as $role)
                      <option @if($role->name == $employee->role?->name) selected @endif value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                  </select>
                  @error('role_id')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-3">
                  <label for="field_id" class="form-label"><strong>Field Office *</strong></label>
                  <select name="field_id" id="field_id" class="form-select @error('field_id') is-invalid @enderror">
                    @foreach($Fields as $field)
                      <option @if($field?->name == $employee->field?->name) selected @endif value="{{ $field?->id }}">{{ $field?->name }}</option>
                    @endforeach
                  </select>
                  @error('field_id')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-3">
                  <label for="client_id" class="form-label"><strong>Client</strong></label>
                  <select name="client_id" id="client_id" class="form-select @error('client_id') is-invalid @enderror">
                    <option value="">Choose...</option>
                    @foreach($clients as $client)
                      <option @if($client->id == $employee->client?->id) selected @endif value="{{ $client->id }}">{{ $client->name }} {{ $client->business_name }}</option>
                    @endforeach
                  </select>
                  @error('client_id')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-3">
                  <label for="location" class="form-label"><strong>Location *</strong></label>
                  <textarea id="location" name="location" class="form-control @error('location') is-invalid @enderror" placeholder="Address Name or Location Name">{{ $employee->location }}</textarea>
                  @error('location')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>

                <div class="col-md-4">
                  <label for="payment_type" class="form-label"><strong>Payment Type *</strong></label>
                  <select name="payment_type" id="payment_type" class="form-select @error('payment_type') is-invalid @enderror">
                    <option @if ($employee->payment_type == 'Bank') selected @endif value="Bank">Bank</option>
                    <option @if ($employee->payment_type == 'Cash') selected @endif value="Cash">Cash</option>
                  </select>
                  @error('payment_type')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>

                <div class="col-md-4" >
                  <label for="basic_salary" class="form-label"><strong>Basic Salary</strong></label>
                  <input type="number" step="any" id="basic_salary" name="basic_salary" class="form-control @error('basic_salary') is-invalid @enderror" value="{{ $employee->basic_salary }}" />
                  @error('basic_salary')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-4">
                  <label for="allowances" class="form-label"><strong>Allowances</strong></label>
                  <input type="number" step="any" id="allowances" name="allowances" class="form-control @error('allowances') is-invalid @enderror" value="{{ $employee->allowances }}" />
                  @error('allowances')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>

                <div class="col-12">
                  <h5 class="card-header"><strong>Guarantor information</strong></h5>
                </div>
                <hr>
                <div class="col-md-3">
                  <label for="gurantor_name" class="form-label"><strong>Guarantor Name</strong></label>
                  <input class="form-control @error('gurantor_name') is-invalid @enderror" type="text" id="gurantor_name" name="gurantor_name" value="{{ $employee->gurantor_name }}" />
                  @error('gurantor_name')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-3">
                  <label for="gurantor_number" class="form-label"><strong>Guarantor Number</strong></label>
                  <input class="form-control @error('gurantor_number') is-invalid @enderror" type="number" id="gurantor_number" name="gurantor_number" value="{{ $employee->gurantor_number }}" />
                  @error('gurantor_number')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-3">
                  <label for="gurantor_address" class="form-label"><strong>Guarantor Address</strong></label>
                  <input class="form-control @error('gurantor_address') is-invalid @enderror" type="text" id="gurantor_address" name="gurantor_address" value="{{ $employee->gurantor_address }}" />
                  @error('gurantor_address')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-3">
                  <label for="gurantor_nia_number" class="form-label"><strong>Guarantor NIA Number</strong></label>
                  <input class="form-control @error('gurantor_nia_number') is-invalid @enderror" type="text" id="gurantor_nia_number" name="gurantor_nia_number" value="{{ $employee->gurantor_nia_number }}" />
                  @error('gurantor_nia_number')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-3">
                  <label for="relationship" class="form-label"><strong>Relationship with Guarantor</strong></label>
                  <input class="form-control @error('relationship') is-invalid @enderror" type="text" id="relationship" name="relationship" value="{{ $employee->relationship }}" />
                  @error('relationship')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>

                                            <hr>
                            <div class="mb-3 col-md-4">
                                <div class="input-group">
                                    <label class="input-group-text" for="inputGroupSelect01">{{ __('ASSIGN TO') }}</label>
                                    <select name="staff" class="form-select @error('inputGroupSelect01') is-invalid @enderror" id="inputGroupSelect01" value="{{ old('staff')}}" >
                                        <option selected disabled>Choose...</option>
                                                @foreach($assign_staff as $user)
                                                    @if($user->field_id == Auth::user()->field_id)
                                                    <option value="{{$user->id}}"> {{$user->name}} </option>
                                                    @elseif(Auth::user()->hasRole(['Manager']))
                                                    <option value="{{$user->id}}"> {{$user->name}} </option>
                                                    @endif
                                                @endforeach
                                    </select>
                                </div>

                                @error('staff')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
              </div>

              <div class="mt-4 text-end">
                <button type="submit" class="btn btn-primary">Update</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  @endsection



@section('scripts')
    <script>
        $(document).ready(function() {

            $('#tin').change(function() {
                 if ($(this).is(':checked')) {
                    $('#tin_number').toggle();
                    // console.log("Checkbox checked! Value: " + value);
                } else {
                    $('#tin_number').toggle();
                }
            });
        });

            $(document).ready(function() {
            $('#ssnit').change(function() {
                 if ($(this).is(':checked')) {
                    $('#ssnit_number').toggle();
                    // console.log("Checkbox checked! Value: " + value);
                } else {
                    $('#ssnit_number').toggle();
                }
            });
        });

    </script>
  @endsection

</x-hr-dashboard>
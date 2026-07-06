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
          <h4 class="fw-bold mb-1">Edit payment info</h4>
          <p class="text-muted mb-0">Update payment details for {{ $employee_pay_info->employee->name }}.</p>
        </div>
        <div class="text-md-end">
          <a href="{{ url('employees', $employee_pay_info->employee_id) }}" class="btn btn-outline-secondary btn-sm mb-2">
            <i class="bx bx-user me-1"></i> Employee Details
          </a>
          @if(Auth::user()->hasRole(['Finance Manager', 'Manager']))
            <a href="{{ url('employeesSalary', $employee_pay_info->employee_id) }}" class="btn btn-outline-secondary btn-sm mb-2">
              <i class="bx bx-money-withdraw me-1"></i> Salaries
            </a>
          @endif
        </div>
      </div>
    </div>

    <!-- <div class="row mb-3">
      <div class="col-12">
        <ul class="nav nav-pills flex-column flex-md-row gap-2">
          <li class="nav-item">
            <a class="nav-link" href="{{ url('employees', $employee_pay_info?->employee_id) }}"><i class="bx bx-user me-1"></i> Employee Details</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="javascript:void(0);"><i class="bx bxs-comment-detail me-1"></i> Payment Info</a>
          </li>
        </ul>
      </div>
    </div> -->

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-body text-center">
            <h5 class="card-title mb-1">{{ $employee_pay_info->employee->name }}</h5>
            <p class="text-muted mb-3">{{ $employee_pay_info->employee->role?->name ?? 'Employee' }}</p>
            <span class="badge bg-primary mb-3">{{ $employee_pay_info->employee->payment_type ?? 'Payment type' }}</span>
            <dl class="row text-start">
              <dt class="col-sm-5 text-muted">Status</dt>
              <dd class="col-sm-7 mb-3">{{ $employee_pay_info->employee->status ?? 'Unknown' }}</dd>
              <dt class="col-sm-5 text-muted">Bank</dt>
              <dd class="col-sm-7 mb-3">{{ $employee_pay_info->bank?->name ?? '-' }}</dd>
              <dt class="col-sm-5 text-muted">Account</dt>
              <dd class="col-sm-7 mb-0">{{ $employee_pay_info->acc_number ?? '-' }}</dd>
            </dl>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card mb-4">
          <div class="card-header">
            <strong>Payment information</strong>
          </div>
          <div class="card-body">
            <form action="/employeesPayInfoUpdate/{{ $employee_pay_info->id }}" method="POST">
              @csrf
              @method('PUT')
              <div class="row g-3" id="payment_field">
                <div class="col-md-6">
                  <label for="payment_type" class="form-label"><strong>Payment Type *</strong></label>
                  <select name="payment_type" class="form-select @error('payment_type') is-invalid @enderror" id="payment_type">
                    <option @if ($employee_pay_info->employee->payment_type == 'Bank') selected @endif value="Bank">Bank</option>
                    <option @if ($employee_pay_info->employee->payment_type == 'Cash') selected @endif value="Cash">Cash</option>
                  </select>
                  @error('payment_type')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>

                <div class="col-md-6">
                  <label for="bank_id" class="form-label"><strong>Bank *</strong></label>
                  <select name="bank_id" class="form-select @error('bank_id') is-invalid @enderror" id="bank_id">
                    <option value="0">Select Bank</option>
                    @foreach($banks as $bank)
                      <option @if($bank->id == $employee_pay_info->bank_id) selected @endif value="{{ $bank->id }}">{{ $bank->name }}</option>
                    @endforeach
                  </select>
                  @error('bank_id')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>

                <div class="col-md-6">
                  <label for="acc_number" class="form-label"><strong>Account Number *</strong></label>
                  <input type="text" id="acc_number" name="acc_number" class="form-control @error('acc_number') is-invalid @enderror" placeholder="Your Account Number" value="{{ $employee_pay_info->acc_number }}" />
                  @error('acc_number')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>

                <div class="col-md-6">
                  <label for="branch" class="form-label"><strong>Branch *</strong></label>
                  <input type="text" id="branch" name="branch" class="form-control @error('branch') is-invalid @enderror" placeholder="Branch" value="{{ $employee_pay_info->branch }}" />
                  @error('branch')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>

                <div class="col-md-6">
                  <label for="branch_code" class="form-label"><strong>Branch Code</strong></label>
                  <input type="text" id="branch_code" name="branch_code" class="form-control @error('branch_code') is-invalid @enderror" placeholder="Branch Code" value="{{ $employee_pay_info->branch_code }}" />
                  @error('branch_code')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>

                <div class="col-md-6">
                  <label for="tin_number" class="form-label"><strong>TIN Number</strong></label>
                  <input type="text" id="tin_number" name="tin_number" class="form-control @error('tin_number') is-invalid @enderror" placeholder="TIN Number" value="{{ $employee_pay_info->tin_number }}" />
                  @error('tin_number')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>

                <div class="col-md-6">
                  <label for="ssnit_number" class="form-label"><strong>SSNIT Number</strong></label>
                  <input type="text" id="ssnit_number" name="ssnit_number" class="form-control @error('ssnit_number') is-invalid @enderror" placeholder="SSNIT Number" value="{{ $employee_pay_info->ssnit_number }}" />
                  @error('ssnit_number')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
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


</x-hr-dashboard>
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
      <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
          <div>
            <h4 class="fw-bold mb-1">Employee profile</h4>
            <p class="text-muted mb-0">Comprehensive profile for {{ $employee->name }}.</p>
          </div>

          <div class="text-md-end">
            @php
              $channelName = collect($channels)->firstWhere('channel', $employee->channel)?->name ?? '-';
              $statusBadgeClass = $employee->status === 'Active' ? 'bg-success' : 'bg-danger';
              $taxBadgeClass = $employee->tax_button === 'on' ? 'bg-success' : 'bg-secondary';
              $ssnitBadgeClass = $employee->ssnit_button === 'on' ? 'bg-success' : 'bg-secondary';
            @endphp

            <span class="badge {{ $statusBadgeClass }} mb-2">{{ ucfirst($employee->status) }}</span>
            <div class="btn-toolbar justify-content-end flex-wrap gap-2">
              @if ($employee->status == 'Active')
                <a href="{{ url('employees/'.$employee->id.'/edit') }}" class="btn btn-dark btn-sm">
                  <i class="bx bx-edit-alt me-1"></i> Edit
                </a>
              @endif
              <a href="{{ url('employeesViewPayInfo', $employee->id) }}" class="btn btn-outline-primary btn-sm">
                <i class="bx bxs-comment-detail me-1"></i> Payment Info
              </a>
              @if(Auth::user()->hasRole(['Finance Manager', 'Manager']))
                <a href="{{ url('employeesSalary', $employee->id) }}" class="btn btn-outline-secondary btn-sm">
                  <i class="bx bx-money-withdraw me-1"></i> Salaries
                </a>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">

      <div class="col-lg-4">

        <div class="card h-80">
          <div class="card-body text-center">
            <img
              src="@if($employee->image) {{ asset($employee->image) }} @else {{ asset('img/user.png') }} @endif"
              alt="Employee avatar"
              class="rounded-circle mb-3 w-px-120 h-px-120 object-fit-cover" />
            <h5 class="card-title mb-1"> <strong> {{ $employee->name }} </strong> </h5>
            <p class="text-muted mb-2"> <strong> {{ $employee->role?->name ?? 'No role assigned' }} </strong> </p>
            <div class="d-flex justify-content-center flex-wrap gap-2">
              <span class="badge {{ $taxBadgeClass }}">Tax Employee</span>
              <span class="badge {{ $ssnitBadgeClass }}">SSNIT Deduction</span>
            </div>
          </div>
          <div class="card-body border-top">
            <div class="mb-3">
              <small class="text-muted">Phone</small>
              <p class="mb-0"><strong>{{ $employee->phone_number ?: 'Not provided' }}</strong></p>
            </div>
            <div class="mb-3">
              <small class="text-muted">Location</small>
              <p class="mb-0"><strong>{{ $employee->location ?: 'Not provided' }}</strong></p>
            </div>
            <div class="mb-3">
              <small class="text-muted">Channel</small>
              <p class="mb-0"><strong>{{ $channelName }}</strong></p>
            </div>
          </div>
        </div>

        <div class="card h-20 mt-4">
          <div class="card-header">
            <strong>Compensation</strong>
          </div>
          <div class="card-body">
            <dl class="row mb-0">
              <dt class="col-sm-6 text-muted">Payment Type</dt>
              <dd class="col-sm-6 mb-3"><strong>{{ $employee->payment_type ?: '-' }}</strong></dd>

              <dt class="col-sm-6 text-muted">Basic Salary</dt>
              <dd class="col-sm-6 mb-3"><strong>{{ $employee->basic_salary ?: '-' }}</strong></dd>

              <dt class="col-sm-6 text-muted">Allowances</dt>
              <dd class="col-sm-6 mb-0"><strong>{{ $employee->allowances ?: '-' }}</strong></dd>
            </dl>
          </div>
        </div>

      </div>

      <div class="col-lg-8">
        <div class="card mb-4">
          <div class="card-header">
            <strong>Personal & employment information</strong>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Gender</label>
                <p class="fw-semibold mb-0"><strong>{{ $employee->gender ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Date of birth</label>
                <p class="fw-semibold mb-0"><strong>{{ $employee->date_of_birth?->format('l F d, Y') ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">NIA Number</label>
                <p class="fw-semibold mb-0"><strong>{{ $employee->nia_number ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Digital Address</label>
                <p class="fw-semibold mb-0"><strong>{{ $employee->address ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Marital Status</label>
                <p class="fw-semibold mb-0"><strong>{{ $employee->marital_status ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Worker Type</label>
                <p class="fw-semibold mb-0"><strong>{{ $employee->worker_type ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Date of joining</label>
                <p class="fw-semibold mb-0"><strong>{{ $employee->date_of_joining?->format('l F d, Y') ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Department</label>
                <p class="fw-semibold mb-0"><strong>{{ $employee->department?->name ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Role</label>
                <p class="fw-semibold mb-0"><strong>{{ $employee->role?->name ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Field Office</label>
                <p class="fw-semibold mb-0"><strong>{{ $employee->field?->name ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Client</label>
                <p class="fw-semibold mb-0"><strong>{{ $employee->client?->name ? $employee->client->name . ' / ' . $employee->client->business_name : '-' }}</strong></p>
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-header">
            <strong>Guarantor information</strong>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Guarantor name</label>
                <p class="fw-semibold mb-0"><strong>{{ $employee->gurantor_name ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Guarantor number</label>
                <p class="fw-semibold mb-0"><strong>{{ $employee->gurantor_number ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Guarantor address</label>
                <p class="fw-semibold mb-0"><strong>{{ $employee->gurantor_address ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Guarantor NIA number</label>
                <p class="fw-semibold mb-0"><strong>{{ $employee->gurantor_nia_number ?: '-' }}</strong></p>
              </div>
              <div class="col-md-12">
                <label class="form-label text-muted mb-1">Relationship</label>
                <p class="fw-semibold mb-0"><strong>{{ $employee->relationship ?: '-' }}</strong></p>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-body py-3">
            <div class="row g-3">
              <div class="col-md-4">
                <small class="text-muted">Created by</small>
                <p class="mb-0">{{ $employee->user?->name ?: 'Unknown' }}</p>
              </div>
              <div class="col-md-4">
                <small class="text-muted">Approved by</small>
                <p class="mb-0">{{ $employee->user2?->name ?: 'Not available' }}</p>
              </div>
              <div class="col-md-4">
                <small class="text-muted">Updated by</small>
                <p class="mb-0">{{ $employee->user1?->name ?: 'Not available' }}</p>
              </div>
              <div class="col-md-6">
                <small class="text-muted">Created at</small>
                <p class="mb-0">{{ $employee->created_at?->format('l F d, Y h:i A') ?: 'Unknown' }}</p>
              </div>
              <div class="col-md-6">
                <small class="text-muted">Last updated</small>
                <p class="mb-0">{{ $employee->updated_at?->format('l F d, Y h:i A') ?: 'Unknown' }}</p>
              </div>
            </div>
          </div>
        </div>

        @if(Auth::user()->hasRole(['Manager', 'Invoice']))
          <div class="card mt-4">
            <div class="card-body text-end">
              <button
                type="button"
                class="btn btn-danger btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#statusModal">
                <i class="icon-base bx bx-bxs-user-plus me-1"></i>
                {{ $employee->status !== 'Active' ? 'Re-Instate' : 'Terminate' }}
              </button>
            </div>
          </div>

          <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="statusModalLabel">
                    {{ $employee->status !== 'Active' ? 'Choose Month To Re-Instate' : 'Choose Month To Terminate' }}
                  </h5>
                  <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"></button>
                </div>

                <form method="GET" action="{{ $employee->status !== 'Active' ? url('employeeReinstate', $employee->id) : url('terminateEmployee', $employee->id) }}">
                  @csrf
                  <div class="modal-body">
                    <div class="row">
                      <div class="col mb-0">
                        <label for="status_date" class="form-label">{{ __('MONTH') }}</label>
                        <input
                          type="month"
                          name="status_date"
                          id="status_date"
                          class="form-control @error('status_date') is-invalid @enderror"
                          value="{{ old('status_date') }}"
                          autocomplete="status_date"
                          required>

                        @error('status_date')
                          <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                          </span>
                        @enderror
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="submit" class="btn btn-info d-grid w-100">
                      {{ $employee->status !== 'Active' ? __('Re-Instate') : __('Terminate') }}
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>

  @endsection



</x-hr-dashboard>
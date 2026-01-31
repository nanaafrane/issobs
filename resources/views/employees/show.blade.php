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
          <li class="menu-item">
              <a href="{{url('employeesBank')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">Employee Banks</div>
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
                    @if(Auth::user()->hasPermission('Accounts') )
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

              <div class="card-header  ml-2  d-none d-lg-block">
                  @include('flash-messages')
              </div>


              <div class="row"> 
                <div class="col-md-6">
                <h5 class="fw-bold mb-4"><span class="text-muted fw-light"><i class="bx bxs-user-account"></i> Employee /</span> Show</h5>

                </div>
                @if ($employee->status == 'Active')
                  <div class="col-md-6 text-end">
                    <a href="{{url('employees/'.$employee->id.'/edit')}}" class="btn btn-dark mb-3"><i class="bx bx-edit-alt"></i> Edit Employee</a>  
                </div>
                @endif


              </div>


              <div class="row">
                <div class="col-md-12">
                  <ul class="nav nav-pills flex-column flex-md-row mb-3">
                    <li class="nav-item">
                      <a class="nav-link active" href="javascript:void(0);"><i class="bx bx-user me-1"></i> Employee Details</a>
                    </li>
                    <li class="nav-item ">
                      <a class="nav-link" href=" {{url('employeesViewPayInfo', $employee->id)}}" ><i class="bx bxs-comment-detail"></i> Payment Info </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" href="{{url('employeesSalary', $employee->id)}}" ><i class="bx bx-money-withdraw"></i> Salaries </a>
                    </li>
                  </ul>


                    <div class="card mb-4">
                      <h5 class="card-header"><strong> Basic Infomation For {{ $employee->name }} </strong> </h5>

                       <hr class="my-0" />
                      <!-- Account -->
                      <div class="card-body">
                        <div class="d-flex align-items-start align-items-sm-center gap-4">
                           <img
                                src="@if($employee->image) {{asset($employee->image)}} @else {{asset('img/user.png')}} @endif"
                                alt="user-avatar"
                                class="d-block w-px-100 h-px-100 rounded"
                                id="uploadedAvatar" />
                        </div>
                      </div>
                      <hr class="my-0" />
                      <div class="card-body">
                            <div class="col-md-6 form-check form-switch ">
                                <input name="tax_button" class="form-check-input" type="checkbox"  @if ($employee->tax_button == "on") checked @endif  id="tax_button">
                                <label class="form-check-label" for="tax_button"> TAX EMPLOYEE </label>
                            </div> 
                            
                            <div class="col-md-6 form-check form-switch ">
                                <input name="ssnit_button" class="form-check-input" type="checkbox"  @if ($employee->ssnit_button == "on") checked @endif  id="ssnit_button">
                                <label class="form-check-label" for="ssnit_button"> DEDUCT SSNIT FOR EMPLOYEE </label>
                            </div> 
                        <br>

                          <div class="row">
                            <div class="mb-3 col-md-4">
                              <label for="name" class="form-label"> <strong>Full Name  </strong> </label>
                                <h4> <strong> {{$employee->name}} </strong> </h4> 
                            
                            </div>
                           
                           
                            <div class="mb-3 col-md-4">
                              <label for="gender" class="form-label"> <strong>Gender  </strong> </label>
                                    <h4> <strong> {{$employee->gender}} </strong> </h4> 
                            </div>
                           
                           
                            <div class="mb-3 col-md-4">
                                <label for="phone_number" class="form-label"> <strong>{{ __('Phone Number') }} </strong> </label>
                                <h4> <strong> {{$employee->phone_number}} </strong> </h4> 
                            </div>
                            
                            <div class="mb-3 col-md-4">
                              <label for="date_of_birth" class="form-label"> <strong>Date Of Birth </strong> </label>
                              <h4> <strong> {{$employee->date_of_birth?->format('l F d, Y')}} </strong> </h4> 

                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="nia_number" class="form-label"> <strong>NIA Number  </strong> </label>
                                <h4> <strong> {{$employee->nia_number}} </strong> </h4> 
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="address" class="form-label"> <strong> Digital Address  </strong> </label>
                                <h4> <strong> {{$employee->address}} </strong> </h4> 
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="marital_status" class="form-label"><strong>Marital Status </strong> </label>
                                <h4> <strong> {{$employee->marital_status}} </strong> </h4> 
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="worker_type" class="form-label"><strong>Worker Type </strong> </label>
                                <h4> <strong> {{$employee->worker_type}} </strong> </h4> 
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="date_of_joining" class="form-label"> <strong>  Date Of Joining  </strong> </label>
                                <h4> <strong> {{$employee->date_of_joining?->format('l F d, Y')}} </strong> </h4> 
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="department_id" class="form-label"> <strong> {{ __('Department') }}  </strong>  </label>
                                <h4> <strong> {{$employee->department?->name}} </strong> </h4> 
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="role_id" class="form-label"> <strong> {{ __('Role') }}  </strong>  </label>
                                <h4> <strong> {{$employee->role?->name}} </strong> </h4> 
                                
                            </div>


                            <div class="mb-3 col-md-4">
                              <label for="field_id" class="form-label"> <strong> {{ __('Field Office') }}  </strong>  </label>
                                <h4> <strong> {{$employee->field?->name}} </strong> </h4> 
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="client_id" class="form-label"> <strong> {{ __('Client') }} </strong>  </label>
                                <h4> <strong> {{$employee->client?->name}}  {{$employee->client?->business_name}}</strong> </h4> 
                            </div>


                            <div class="mb-3 col-md-4">
                              <label for="location" class="form-label"> <strong>  {{ __('Location') }}  </strong> </label>
                                <h4> <strong> {{$employee->location}} </strong> </h4> 
                            </div>
                           
                            <div class="mb-3 col-md-4">
                              <label for="payment_type" class="form-label"> <strong> {{ __('Payment Type') }} </strong>  </label>
                                <h4> <strong> {{$employee->payment_type}} </strong> </h4> 
                            </div>                       

                            <div class="row"> 
                              <div class="col-md-2"></div>
                            <div class="mb-3 col-md-4">
                              <label for="basic_salary" class="form-label"> <strong>  Basic Salary  </strong> </label>
                                <h4> <strong> {{$employee->basic_salary}} </strong> </h4> 
                            </div>

                            <div class="mb-3 col-md-4">
                              <label for="allowances" class="form-label"> <strong>  Allowances  </strong> </label>
                                <h4> <strong> {{$employee->allowances}} </strong> </h4> 
                            </div>
                            <div class="col-md-2"></div>
                            </div> 

                            <hr class="mb-3" />
                            <h5 class="card-header"> <strong>Gurantor Infomation</strong> </h5> 
                            <hr class="mb-3" />

                            <div class="mb-3 col-md-4">
                              <label for="gurantor_name" class="form-label"> <strong>   Gurantor Name  </strong> </label>
                                <h4> <strong> {{$employee->gurantor_name}} </strong> </h4> 
                            </div>

                              <div class="mb-3 col-md-4">
                              <label for="gurantor_number" class="form-label"> <strong>   Gurantor Number  </strong> </label>
                                <h4> <strong> {{$employee->gurantor_number}} </strong> </h4> 
                            </div>


                              <div class="mb-3 col-md-4">
                              <label for="gurantor_address" class="form-label"> <strong>   Gurantor Address  </strong> </label>
                                <h4> <strong> {{$employee->gurantor_address}} </strong> </h4> 
                            </div>

                              <div class="mb-3 col-md-4">
                              <label for="gurantor_nia_number" class="form-label"> <strong>   Gurantor NIA Number  </strong> </label>
                                <h4> <strong> {{$employee->gurantor_nia_number}} </strong> </h4> 
                              </div>

                            <div class="mb-3 col-md-4">
                              <label for="relationship" class="form-label"> <strong>  Relationship with Gurantor  </strong> </label>
                                <h4> <strong> {{$employee->relationship}} </strong> </h4> 
                            </div>




                          </div>

                      </div>
                      <!-- /Account -->
                    </div>
              
                  
                </div>
              </div>
              created by : {{ $employee->user?->name }} at {{ $employee->created_at?->format('l F d, Y h:i A')  }}

            </div>
  <!-- / Content -->

          <div class="buy-now">
            @if ($employee->status !== 'Active')
            <a style="margin-bottom: 70px;" href="{{url('employeeReinstate', $employee->id )}}" class="btn btn-danger btn-buy-now" onclick="return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-edit-alt me-1"></i> Re-Instate </a>
              
            @else
            <a  href="{{url('terminateEmployee', $employee->id )}}" class="btn btn-danger btn-buy-now" onclick="return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-trash me-1"></i> Terminate </a>  
              
            @endif
          </div>
  @endsection


</x-hr-dashboard>
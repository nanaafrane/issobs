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
        <li class="menu-item">
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
            <li class="menu-item  ">
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
                <li class="menu-item ">
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

            <li class="menu-header small text-uppercase"><span class="menu-header-text">PAYROLL</span></li>
            <li class="menu-item active open">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                    <div class="text-truncate" data-i18n="Payroll">Payroll</div>
                    </a>
                    <ul class="menu-sub">
                    @if(Auth::user()->hasPermission('HR') || Auth::user()->hasRole(['Invoice' , 'Finance Manager']))
                    <li class="menu-item">
                        <a href="{{ url('salaries') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bxs-user-account"></i>
                        <div class="text-truncate" data-i18n="Employees">Add to Salaries</div>
                        </a>
                    </li>
                    @endif

                    <li class="menu-item active">
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
              <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light"> <i class="bx bx-money-withdraw"></i> Salary /</span> Edit </h4>
        <div class="card-header  ml-2  d-none d-lg-block">
            @include('flash-messages')
        </div>

            <form  action="/salaries/{{$salary->id}}" method="POST" >
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col"> 
                            <div class="card mb-4">
                            <h5 class="card-header"><strong>  Employee Name: {{ $salary->employee->name }} </strong> </h5>
                            <div class="card-body">
                                <div class="row">

                                    <div class="mb-3 col-md-4">
                                    <label for="gross_salary" class="form-label"> <strong>  Gross Salary * </strong> </label>
                                    <input  class="form-control @error('gross_salary') is-invalid @enderror" type="number" id="gross_salary" name="gross_salary" placeholder="GH&#x20B5;" value="{{$salary->gross_salary}}" autofocus required step="any"/>
                                        @error('gross_salary')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 col-md-4">
                                    <label for="total_deductions" class="form-label"> <strong>  Total Deduction * </strong> </label>
                                    <input  class="form-control @error('total_deductions') is-invalid @enderror" type="number" id="total_deductions" name="total_deductions" placeholder="GH&#x20B5;" value="{{$salary->total_deductions}}" autofocus required step="any"/>
                                        @error('total_deductions')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 col-md-4">
                                    <label for="net_salary" class="form-label"> <strong>  Net Salary * </strong> </label>
                                    <input  class="form-control @error('net_salary') is-invalid @enderror" type="number" id="net_salary" name="net_salary" placeholder="GH&#x20B5;" value="{{$salary->net_salary}}" autofocus required step="any"/>
                                        @error('net_salary')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 col-md-4">
                                    <label for="ssnit_comp_cont_13" class="form-label"> <strong>  SSNIT Company Contribution 13% * </strong> </label>
                                    <input  class="form-control @error('ssnit_comp_cont_13') is-invalid @enderror" type="number" id="ssnit_comp_cont_13" name="ssnit_comp_cont_13" placeholder="GH&#x20B5;" value="{{$salary->ssnit_comp_cont_13}}" autofocus required step="any"/>
                                        @error('ssnit_comp_cont_13')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 col-md-4">
                                    <label for="ssnit_tobe_paid13_5" class="form-label"> <strong>  SSNIT To be Paid 13.5%* </strong> </label>
                                    <input  class="form-control @error('ssnit_tobe_paid13_5') is-invalid @enderror" type="number" id="ssnit_tobe_paid13_5" name="ssnit_tobe_paid13_5" placeholder="GH&#x20B5;" value="{{$salary->ssnit_tobe_paid13_5}}" autofocus required step="any"/>
                                        @error('ssnit_tobe_paid13_5')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 col-md-4">
                                    <label for="cost_to_company" class="form-label"> <strong>  Cost to Company * </strong> </label>
                                    <input  class="form-control @error('cost_to_company') is-invalid @enderror" type="number" id="cost_to_company" name="cost_to_company" placeholder="GH&#x20B5;" value="{{$salary->cost_to_company}}" autofocus required step="any"/>
                                        @error('cost_to_company')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 col-md-4">
                                    <label for="salary_month" class="form-label"> <strong>Salary Month  </strong> </label>
                                    <input  class="form-control @error('salary_month') is-invalid @enderror" type="date" id="salary_month" name="salary_month" value="{{$salary->salary_month?->format('Y-m-d')}}" autofocus required/>
                                        @error('salary_month')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                

                                    <div class="mb-3 col-md-4">
                                    <label for="client_id" class="form-label"> <strong> {{ __('Client') }} </strong>  </label>
                                        <select name="client_id" class="form-select @error('client_id') is-invalid @enderror" id="client_id" required>
                                            @foreach($clients as $client)
                                            <option  @if($client->id == $salary->client?->id) selected @endif  value="{{$client->id}}">{{$client->name}} {{$client->business_name}}</option>
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
                                    <input  class="form-control @error('location') is-invalid @enderror" type="text" id="location" name="location"  placeholder="Address Name or Location Name" value="{{$salary->location}}" autofocus required/>
                                        @error('location')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>


                                    <div class="mb-3 col-md-2">
                                    <label for="payment_type" class="form-label"> <strong>  {{ __('Payment Type') }}* </strong> </label>
                                    <input  class="form-control @error('payment_type') is-invalid @enderror" type="text" id="payment_type" name="payment_type"  value="{{$salary->payment_type}}" autofocus required/>
                                        @error('payment_type')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                    <!-- <div class="mb-3 col-md-2">
                                    <label for="payment_type" class="form-label"> <strong> {{ __('Payment Type') }} *</strong>  </label>
                                        <select name="payment_type" class="form-select @error('payment_type') is-invalid @enderror" id="payment_type" required>
                                            <option @if ($salary->payment_type == 'bank') selected @endif value="bank">Bank</option>
                                            <option  @if ($salary->payment_type == 'cash') selected @endif value="cash">Cash</option>
                                        </select>
                                        @error('payment_type')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>   -->
                               
                                    <div class="mb-3 col-md-2">
                                    <label for="bank_id" class="form-label"> <strong> {{ __('Bank') }}  </strong>  </label>
                                        <select name="bank_id" class="form-select @error('bank_id') is-invalid @enderror" id="bank_id">
                                            <option selected value="0"> Choose... </option>
                                           
                                             @foreach($banks as $bank)
                                            <option  @if($bank->id == $salary->bank_id) selected @endif  value="{{$bank->id}}">{{$bank->name}}</option>
                                            @endforeach
                                        </select>
                                        @error('bank_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>  

                                    <div class="mb-3 col-md-4">
                                    <label for="account_number" class="form-label"> <strong>  {{ __('Account Number') }}  </strong> </label>
                                    <input  class="form-control @error('account_number') is-invalid @enderror" type="text" id="account_number" name="account_number"  placeholder="Account Number" value="{{$salary->account_number}}" autofocus />
                                        @error('account_number')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 col-md-4">
                                    <label for="branch" class="form-label"> <strong>  {{ __('Branch') }} * </strong> </label>
                                    <input  class="form-control @error('branch') is-invalid @enderror" type="text" id="branch" name="branch"  placeholder="Branch" value="{{$salary->branch}}" autofocus />
                                        @error('branch')
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
                    </div>
                </div>

                <div class="row">
                    <div class="col-3">
                        <div class="card bg-dark text-white">
                                    <h5 class="card-header text-white"><strong>  Earnings </strong> </h5>
                                    <hr>
                            <div class="card-body"> 
                                    <div class="mb-3 ">
                                    <label for="basic_salary" class="form-label text-white"> <strong>  Basic Salary * </strong> </label>
                                    <input  class="form-control @error('basic_salary') is-invalid @enderror text-white" type="number" id="basic_salary" name="basic_salary" placeholder="GH&#x20B5;" value="{{$salary->basic_salary}}" autofocus required step="any"/>
                                        @error('basic_salary')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 ">
                                    <label for="allowances" class="form-label text-white"> <strong>  Allawonce * </strong> </label>
                                    <input  class="form-control @error('allowances') is-invalid @enderror text-white" type="number" id="allowances" name="allowances" placeholder="GH&#x20B5;" value="{{$salary->allowances}}" autofocus required step="any"/>
                                        @error('allowances')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 ">
                                    <label for="airtime_allowance" class="form-label text-white"> <strong>{{ __('Airtime Allowance *') }} </strong> </label>
                                    <input  class="form-control @error('airtime_allowance') is-invalid @enderror text-white" type="number" id="airtime_allowance" name="airtime_allowance" placeholder="GH&#x20B5;" value="{{$salary->airtime_allowance}}" autofocus required step="any"/>
                                    @error('airtime_allowance')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                    </div>

                                    <div class="mb-3 ">
                                    <label for="overtime" class="form-label text-white"> <strong>  Overtime * </strong> </label>
                                    <input  class="form-control @error('overtime') is-invalid @enderror text-white" type="number" id="overtime" name="overtime" placeholder="GH&#x20B5;" value="{{$salary->overtime}}" autofocus required step="any"/>
                                        @error('overtime')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 ">
                                    <label for="reimbursements" class="form-label text-white"> <strong>  Reimbursements * </strong> </label>
                                    <input  class="form-control @error('reimbursements') is-invalid @enderror text-white" type="number" id="reimbursements" name="reimbursements" placeholder="GH&#x20B5;" value="{{$salary->reimbursements}}" autofocus required step="any"/>
                                        @error('reimbursements')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>


                                    <div class="mb-3 ">
                                        <label for="transport_allowance" class="form-label text-white"> <strong>  Transport Allowance * </strong> </label>
                                        <input  class="form-control @error('transport_allowance') is-invalid @enderror text-white" type="number" id="transport_allowance" name="transport_allowance" placeholder="GH&#x20B5;" value="{{$salary->transport_allowance}}" autofocus required step="any"/>
                                            @error('transport_allowance')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                    </div>

                                    <div class="mb-3 ">
                                    <label for="ssnit_tier2_5" class="form-label text-white"> <strong>  SSNIT TIER 2.5% * </strong> </label>
                                    <input  class="form-control @error('ssnit_tier2_5') is-invalid @enderror text-white" type="number" id="ssnit_tier2_5" name="ssnit_tier2_5" placeholder="GH&#x20B5;" value="{{$salary->ssnit_tier2_5}}" autofocus required step="any"/>
                                        @error('ssnit_tier2_5')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                    </div>


                    <div class="col-9">
                        <div style="background: crimson;" class="card text white"> 
                                    <h5 class="card-header text-white"><strong>  Deductions </strong> </h5>
                                    <hr>
                        <div class="card-body"> 
                                    <div class="row">
                                    <div class="mb-3 col-md-4">
                                    <label for="tax" class="form-label text-white"> <strong>{{ __('TAX *') }} </strong> </label>
                                    <input  class="form-control @error('tax') is-invalid @enderror text-white" type="number" id="tax" name="tax" placeholder="GH&#x20B5;" value="{{$salary->tax}}" autofocus required step="any"/>
                                    @error('tax')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                    </div>

                                    <div class="mb-3 col-md-4">
                                    <label for="ssnit_tier2_5d" class="form-label text-white"> <strong>  SSNIT TIER 2.5% * </strong> </label>
                                    <input  class="form-control @error('ssnit_tier2_5d') is-invalid @enderror text-white" type="number" id="ssnit_tier2_5d" name="ssnit_tier2_5d" placeholder="GH&#x20B5;" value="{{$salary->ssnit_tier2_5d}}" autofocus required step="any"/>
                                        @error('ssnit_tier2_5d')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 col-md-4">
                                    <label for="ssnit_tier1_0_5" class="form-label text-white"> <strong>  SSNIT TIER 10.5% * </strong> </label>
                                    <input  class="form-control @error('ssnit_tier1_0_5') is-invalid @enderror text-white" type="number" id="ssnit_tier1_0_5" name="ssnit_tier1_0_5" placeholder="GH&#x20B5;" value="{{$salary->ssnit_tier1_0_5}}" autofocus required step="any"/>
                                        @error('ssnit_tier1_0_5')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                     </div>


                                    <div class="row"> 
                                    <div class="mb-3 col-md-4">
                                    <label for="welfare" class="form-label text-white"> <strong>  Welfare * </strong> </label>
                                    <input  class="form-control @error('welfare') is-invalid @enderror text-white" type="number" id="welfare" name="welfare" placeholder="GH&#x20B5;" value="{{$salary->welfare}}" autofocus required step="any"/>
                                        @error('welfare')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 col-md-4">
                                    <label for="maintenance" class="form-label text-white"> <strong>  Maintenance * </strong> </label>
                                    <input  class="form-control @error('maintenance') is-invalid @enderror text-white" type="number" id="maintenance" name="maintenance" placeholder="GH&#x20B5;" value="{{$salary->maintenance}}" autofocus required step="any"/>
                                        @error('maintenance')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 col-md-4">
                                    <label for="absent" class="form-label text-white"> <strong>  Absent * </strong> </label>
                                    <input  class="form-control @error('absent') is-invalid @enderror text-white" type="number" id="absent" name="absent" placeholder="GH&#x20B5;" value="{{$salary->absent}}" autofocus required step="any"/>
                                        @error('absent')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                    </div>


                                    <div class="row">
                                    <div class="mb-3 col-md-4">
                                    <label for="boot" class="form-label text-white"> <strong>  Boot * </strong> </label>
                                    <input  class="form-control @error('boot') is-invalid @enderror text-white" type="number" id="boot" name="boot" placeholder="GH&#x20B5;" value="{{$salary->boot}}" autofocus required step="any"/>
                                        @error('boot')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 col-md-4">
                                    <label for="iou" class="form-label text-white"> <strong>  IOU * </strong> </label>
                                    <input  class="form-control @error('iou') is-invalid @enderror text-white" type="number" id="iou" name="iou" placeholder="GH&#x20B5;" value="{{$salary->iou}}" autofocus required step="any"/>
                                        @error('iou')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 col-md-4">
                                    <label for="hostel" class="form-label text-white"> <strong>  Hostel * </strong> </label>
                                    <input  class="form-control @error('hostel') is-invalid @enderror text-white" type="number" id="hostel" name="hostel" placeholder="GH&#x20B5;" value="{{$salary->hostel}}" autofocus required step="any"/>
                                        @error('hostel')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                     </div>

                                    <div class="row"> 
                                    <div class="mb-3 col-md-4">
                                    <label for="insurance" class="form-label text-white"> <strong>  Insurance * </strong> </label>
                                    <input  class="form-control @error('insurance') is-invalid @enderror text-white" type="number" id="insurance" name="insurance" placeholder="GH&#x20B5;" value="{{$salary->insurance}}" autofocus required step="any"/>
                                        @error('insurance')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 col-md-4">
                                    <label for="reprimand" class="form-label text-white"> <strong>  Reprimand * </strong> </label>
                                    <input  class="form-control @error('reprimand') is-invalid @enderror text-white" type="number" id="reprimand" name="reprimand" placeholder="GH&#x20B5;" value="{{$salary->reprimand}}" autofocus required step="any"/>
                                        @error('reprimand')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 col-md-4">
                                    <label for="scouter" class="form-label text-white"> <strong>  Scouter * </strong> </label>
                                    <input  class="form-control @error('scouter') is-invalid @enderror text-white" type="number" id="scouter" name="scouter" placeholder="GH&#x20B5;" value="{{$salary->scouter}}" autofocus required step="any"/>
                                        @error('scouter')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                    </div>

                                    <div class="row">
                                    <div class="mb-3 col-md-4">
                                    <label for="raincoat" class="form-label text-white"> <strong>  raincoat * </strong> </label>
                                    <input  class="form-control @error('raincoat') is-invalid @enderror text-white" type="number" id="raincoat" name="raincoat" placeholder="GH&#x20B5;" value="{{$salary->raincoat}}" autofocus required step="any"/>
                                        @error('raincoat')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 col-md-4">
                                    <label for="meal" class="form-label text-white"> <strong>  meal * </strong> </label>
                                    <input  class="form-control @error('meal') is-invalid @enderror text-white" type="number" id="meal" name="meal" placeholder="GH&#x20B5;" value="{{$salary->meal}}" autofocus required step="any"/>
                                        @error('meal')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 col-md-4">
                                    <label for="loan" class="form-label text-white"> <strong>  loan * </strong> </label>
                                    <input  class="form-control @error('loan') is-invalid @enderror text-white" type="number" id="loan" name="loan" placeholder="GH&#x20B5;" value="{{$salary->loan}}" autofocus required step="any"/>
                                        @error('loan')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                     </div>

                                     <div class="row"> 
                                    <div class="mb-3 col-md-4">
                                    <label for="walkin" class="form-label text-white"> <strong>  walkin * </strong> </label>
                                    <input  class="form-control @error('walkin') is-invalid @enderror text-white" type="number" id="walkin" name="walkin" placeholder="GH&#x20B5;" value="{{$salary->walkin}}" autofocus required step="any"/>
                                        @error('walkin')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 col-md-4">
                                    <label for="amnt_ded_cof_start_date" class="form-label text-white"> <strong>  Amount Cos Of Start Date * </strong> </label>
                                    <input  class="form-control @error('amnt_ded_cof_start_date') is-invalid @enderror text-white" type="number" id="amnt_ded_cof_start_date" name="amnt_ded_cof_start_date" placeholder="GH&#x20B5;" value="{{$salary->amnt_ded_cof_start_date}}" autofocus required step="any"/>
                                        @error('amnt_ded_cof_start_date')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3 col-md-4">
                                    <label for="other_deductions" class="form-label text-white"> <strong>  Other Deductions * </strong> </label>
                                    <input  class="form-control @error('other_deductions') is-invalid @enderror text-white" type="number" id="other_deductions" name="other_deductions" placeholder="GH&#x20B5;" value="{{$salary->other_deductions}}" autofocus required step="any"/>
                                        @error('other_deductions')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                    </div>
                                </div>
                            </div>
                    </div>
                </div>
            </form>

            </div>
            <!-- / Content -->



  <!-- / Content -->

  @endsection




</x-hr-dashboard>
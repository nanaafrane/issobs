<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.5/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.5/css/buttons.dataTables.css">    
    <link href="https://cdn.datatables.net/columncontrol/1.1.1/css/columnControl.dataTables.min.css" rel="stylesheet"> 
    @endsection


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
                    <li class="menu-item ">
                        <a href="{{ url('salaries') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bxs-user-account"></i>
                        <div class="text-truncate" data-i18n="Employees">Add to Salaries</div>
                        </a>
                    </li>
                    @endif

                    <li class="menu-item ">
                        <a href="{{ url('salaries/create') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                        <div class="text-truncate" data-i18n="Salaries">Salaries</div>
                        </a>
                    </li>

                    <li class="menu-item active">
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
                </li>

                    <li class="menu-item">
                        <a href="{{ url('salariesInvPayroll') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-git-compare"></i>
                        <div class="text-truncate" data-i18n="InvtoPayroll">Invoice to Payroll</div>
                        </a>
                    </li> -->
                    </ul>
                </li>
        </ul>
    </aside>
  <!-- / Menu -->
  @endsection


  @section('content')

  <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bx-transfer-alt"></i> {{ $client->name }}{{ $client->business_name }}    @if (isset($month)) <strong> / For Month: {{  \Carbon\Carbon::parse($month)->format('F Y') }}</strong> @endif </h3>

            </div>
        </div><br>

         @if(Auth::user()->hasRole(['Invoice','Manager', 'Finance Manager' ]))
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> CLIENT HOLD NAME :  {{ strtoupper($client->name) }}   {{ strtoupper($client->business_name) }} </strong> </p> <br>
                        <h4 class="card-title mb-3 text-white"><strong> GH&#x20B5;  {{ number_format($ClientSalarieshold->sum('net_salary'), 2) }} </strong> </h4> <br>
                         <h6  class="card-title mb-3 text-white">TOTAL REJECTED : GH&#x20B5;  {{ number_format($ClientSalariesrejected->sum('net_salary'), 2) }}   |   REJECTED EMPLOYEES :  {{ $ClientSalariesrejected->count() }}  </h6> 
                        <h6  class="card-title mb-3 text-white"> TOTAL  : GH&#x20B5; {{ number_format($ClientSalariesHoldAll->sum('net_salary'), 2) }} |   EMPLOYEES :  {{ $ClientSalariesHoldAll->count() }} </h6> 

                    </div>
                </div>
            </div>

                        <div class="col-lg-6 mb-4">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body text-end">
                        <div class="card-title d-flex align-items-end justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>

                        <h4 class="card-title mb-3 text-white"><strong> CASH HOLD : GH&#x20B5; {{ number_format($ClientSalarieshold->where('payment_type', 'Cash')->sum('net_salary') ,2) }}  </strong> </h6> <br>
                        <h4 class="card-title mb-3 text-white"><strong> BANK HOLD : GH&#x20B5; {{ number_format($ClientSalarieshold->where('payment_type', 'Bank')->sum('net_salary') ,2) }}  </strong> </h6> <br>


                    </div>
                </div>
            </div>

        </div> <br>
        @endif
        
        <div class="card-header  ml-2  d-none d-lg-block">
                  @include('flash-messages')
        </div> <br>
        <!-- Table -->  
        <hr> <br>
        <div class="row">
            <form action="/salariesDeleteMultiple" method="POST">
                @csrf
                    <div class="col-lg-12 mb-4">
                        <input type="hidden" name="action_type" id="salary_action_type" value="" />
                        <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />
                         @if(Auth::user()->hasRole(['Finance Manager']))
                        <div class="form-check form-check-inline">
                            <button class="btn btn-success" data-action="main" onclick="document.getElementById('salary_action_type').value='main'; return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-recycle"> </i> {{ __('Move To Main') }}</button>                   
                        </div>
                        @endif
                        <div class="card">
                            <h5 class="card-header"> Client Employee Salaries  </h5>
                            <div class="card-body"> 
                            <div class="table-responsive text-nowrap">
                                <table class="display" id="myTable">
                                    <thead class="table-border-bottom-0">
                                        <tr>

                                            <th></th>
                                            <th>#</th>
                                            <th>Staff ID</th>
                                        <th>Status</th>
                                        <th>Hold Reason</th>
                                        <th>Month</th>
                                        <th>Name</th>
                                        <th> Department </th>
                                        <th> Role</th>
                                        <th> Field </th>
                                        <th> Emp. Type </th>
                                        <th> Client </th>
                                        <th> Location </th>
                                        <th> Inv. Status </th>
                                        <th> net_salary </th>
                                        <th> SSNIT No.</th>
                                        <th> TIN No.</th>
                                        <th> Payment Type</th>
                                        <th> Bank Name </th>
                                        <th> Branch </th>
                                        <th> Account No.</th>
                                        <th> Basic Salary</th>
                                        <th> Allowances</th>
                                        <th> airtime_allowance</th>
                                        <th> overtime</th>
                                        <th> reimbursements </th>
                                        <th> transport_allowance</th>
                                        <th> ssnit_tier2_5</th>
                                        <th> ssnit_tier2_5d</th>
                                        <th> tax</th>
                                        <th> ssnit_tier1_0_5</th>
                                        <th> welfare </th>
                                        <th> maintenance</th>
                                        <th> absent</th>
                                        <th> boot</th>
                                        <th> iou</th>
                                        <th> hostel</th>
                                        <th> insurance</th>
                                        <th> reprimand</th>
                                        <th> scouter </th>
                                        <th> raincoat </th>
                                        <th> meal </th>
                                        <th> loan </th>
                                        <th> walkin </th>
                                        <th> amnt_ded_cof_start_date</th>
                                        <th> other_deductions</th>
                                        <th> gross_salary </th>
                                        <th> total_deductions</th>
                                            <th>CREATED BY</th>
                                            <th>UPDATED</th>
                                            <th>UPDATED BY</th>
                                            <th>PERIOD</th>
                                            <th>ACTION</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        @foreach($ClientSalariesHoldAll as $key => $salary)
                                        <tr>
                                            <td> <input class="checkBoxes form-check-input" type="checkbox" name="salary[]" value="{{ $salary->id }}" /> </td>
                                            <td> {{ $key + 1 }} </td>
                                            <td> FWSS{{ $salary->employee?->id }} </td>

                                                    @if ($salary->payment_status == 'pending')
                                                    <td>  <span class="badge bg-label-info"> {{ $salary->payment_status }} </span> </td>
                                                    @elseif($salary->payment_status == 'hold')
                                                    <td> <span class="badge bg-label-warning"> {{ $salary->payment_status }} </span> </td>
                                                    @elseif($salary->payment_status == 'rejected')
                                                    <td> <span class="badge bg-label-danger"> {{ $salary->payment_status }} </span> </td>
                                                    @else
                                                    <td> <span class="badge bg-label-success"> {{ $salary->payment_status }} </span> </td>
                                                    @endif
                                                    <td>  {{ $salary?->hold_reason }}  </td>
                                                    <td> {{$salary->salary_month?->format('F, Y')}} </td>
                                                    <td> {{ strtoupper($salary->employee?->name) }} </td>
                                                    <td> {{ $salary->department?->name }} </td>
                                                    <td> {{ $salary->role?->name }} </td>
                                                    <td> {{ $salary->field?->name }} </td>
                                                    <td> {{ $salary->employee?->worker_type }} </td>
                                                    <td> {{ $salary->client?->name }} {{ $salary->client?->business_name }}</td>
                                                    <td> {{ $salary->location }} </td>
                                                    <td> {{ $salary->client?->invoices()->whereMonth('invoice_month', $month->month)->pluck('status') }} </td>
                                                    <td>  {{ number_format( $salary->net_salary,2) }} </td>  
                                                    <td> {{$salary->employee?->ssnit_number}}</td>
                                                    <td> {{$salary->employee?->tin_number}}</td>
                                                    <td> {{$salary->payment_type}}</td>
                                                    <td> {{$salary->bank?->name}}</td>
                                                    <td> {{$salary->branch}}</td>
                                                    <td> {{$salary->account_number}}</td>
                                                    <td> {{$salary->basic_salary}}</td>
                                                    <td> {{$salary->allowances}}</td>
                                                    <td> {{$salary->airtime_allowance}}</td>
                                                    <td> {{$salary->overtime}}</td>
                                                    <td> {{$salary->reimbursements}}</td>
                                                    <td> {{$salary->transport_allowance}}</td>
                                                    <td> {{$salary->ssnit_tier2_5}}</td>
                                                    <td> {{$salary->ssnit_tier2_5d}}</td>   
                                                    <td> {{$salary->tax}} </td>                            
                                                    <td> {{$salary->ssnit_tier1_0_5}} </td>                            
                                                    <td> {{$salary->welfare}} </td>                            
                                                    <td> {{$salary->maintenance}} </td>                            
                                                    <td> {{$salary->absent}} </td>                            
                                                    <td> {{$salary->boot}} </td>                            
                                                    <td> {{$salary->iou}} </td>                            
                                                    <td> {{$salary->hostel}} </td>                            
                                                    <td> {{$salary->insurance}} </td>                            
                                                    <td> {{$salary->reprimand}} </td>                            
                                                    <td> {{$salary->scouter}} </td>                            
                                                    <td> {{$salary->raincoat}} </td>                            
                                                    <td> {{$salary->meal}} </td>                            
                                                    <td> {{$salary->loan}} </td>                            
                                                    <td> {{$salary->walkin}} </td>                            
                                                    <td> {{$salary->amnt_ded_cof_start_date}} </td>                            
                                                    <td> {{$salary->other_deductions}} </td>                            
                                                    <td> {{$salary->gross_salary}} </td>                            
                                                    <td> {{$salary->total_deductions}} </td>                            
                                            <td>{{  $salary->user?->name }}</td>
                                            <td> {{$salary->updated_at->format('F l d, Y, H:i A')}} </td>
                                            <td> {{ $salary->user1?->name }} </td>
                                            <td> {{$salary->updated_at->diffForHumans()}} </td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                                <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                <a class="dropdown-item" href="{{url('salaries', $salary->id)}}"><i class="icon-base bx bxs-bullseye"></i> view</a>
                                                                <a class="dropdown-item" href="/salaries/{{$salary->id}}/edit"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>

                                                            </div>
                                                        </div>
                                                    </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            </div>
                        </div>
                    </div>
            </form>
        </div>



    </div>
  <!-- / Content -->

  @endsection


    @section('scripts')

    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.3.5/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.5/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.colVis.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <script src="https://cdn.datatables.net/columncontrol/1.1.1/js/dataTables.columnControl.min.js"></script>



    <script>
       
      new DataTable('#myTable', {
        //  dom: 'Blfrtip',
        //  stateSave: false,
        columnControl: [ ['search'] ],
        layout: {
            topStart: {
                buttons: [ 
                {
                     extend: 'pageLength',
                    text: 'Show',
                    className: 'btn btn-secondary',
                    Options: [10, 25, 50, 100, 250, 500, 1000, 2000], 
                },
                    {
                        extend: 'excelHtml5',
                        title:  "{{ $client->name.  $client->business_name . ' CASH SALARIES ' . \Carbon\Carbon::parse($month)->format('F Y')}}",
                        className: 'btn btn-secondary',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                ]
            }
        },
                
    });

    </script>

    <script>
        $(document).ready(function() {
            $('#options').change(function() {
                $('.checkBoxes').prop('checked', function(i, val) {
                    return !val;
                });
            });
        });
    </script>

    @endsection
</x-hr-dashboard>
<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">
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
                    @if(Auth::user()->hasPermission('HR') || Auth::user()->hasRole(['Invoice']))
                    <li class="menu-item ">
                        <a href="{{ url('salaries') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bxs-user-account"></i>
                        <div class="text-truncate" data-i18n="Employees">Add to Salaries</div>
                        </a>
                    </li>
                    @endif

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

                    <li class="menu-item active">
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
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bx-git-compare"></i> Invoices to Salary </h3>
            </div>
        </div><br>

        @if(Auth::user()->hasRole(['Invoice','Manager', 'Finance Manager' ]))
        <div class="row mb-4">
            <div class="col-lg-4">
                <div  class="card h-100 bg-danger text-white">
                    <div class="card-body">
                            <p class="mb-1"><strong> INVOICES  / For Month: {{  $month }}</strong> </p>
                            <h4 class="card-title mb-3 text-white"><strong> GH&#x20B5; {{ number_format($invoicesTotal, 2) }}  </strong> </h4>
                            <small class="fw-medium"> TOTAL INVOICES GENERATED </small>
                   </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div  class="card h-100 bg-success text-white">
                    <div class="card-body">
                            <p class="mb-1"><strong> SALARIES  / For Month: {{  $month }}</strong>  </p>
                            <h4 class="card-title mb-3 text-white"><strong> GH&#x20B5; {{ number_format($salariesTotal, 2) }}  </strong> </h4>
                            <small class="fw-medium"> TOTAL SALARIES PAID </small>      
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div  class="card h-100 bg-secondary text-white">
                    <div class="card-body">
                            <p class="mb-1"><strong> DIFFERENCE : {{  $month }}</strong>  </p>
                            <h4 class="card-title mb-3 text-white"><strong> GH&#x20B5; {{ number_format($invoicesTotal - $salariesTotal, 2) }}  </strong> </h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong> ACCRA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($AccrainvoicesTotal, 2) }} </strong> </h4>
                        INVOICES TOTAL: {{ $AccrainvoicesCount }}
                        <hr>
                        SALARIES TOTAL: {{ $AccrasalariesCount }}
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($AccrasalariesTotal, 2) }} </strong> </h4>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong> BOTWE </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($BotweinvoicesTotal, 2) }} </strong> </h4>
                        INVOICES TOTAL: {{ $BotweinvoicesCount }}
                        <hr>
                        SALARIES TOTAL: {{ $BotwesalariesCount }}
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($BotwesalariesTotal, 2) }} </strong> </h4>
                    </div>
                </div>
            </div>


            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong> TEMA </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($TemainvoicesTotal, 2) }} </strong> </h4>
                        INVOICES TOTAL: {{ $TemainvoicesCount }}
                        <hr>
                        SALARIES TOTAL: {{ $TemasalariesCount }}
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($TemasalariesTotal, 2) }} </strong> </h4>
                    </div>
                </div>
            </div>



            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1">TAKORADI</p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($TakoradinvocesTotal, 2) }} </strong> </h4>
                        INVOICES TOTAL: {{ $TakoradinvocesCount }}
                        <hr>
                        SALARIES TOTAL: {{ $TakoradisalariesCount }}
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($TakoradisalariesTotal, 2) }} </strong> </h4>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1"> <strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($KoforiduainvoicesTotal, 2) }} </strong> </h4>
                        INVOICES TOTAL: {{ $KoforiduainvoicesCount }}
                        <hr>
                        SALARIES TOTAL: {{ $KoforiduasalariesCount }}
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($KoforiduasalariesTotal, 2) }} </strong> </h4>
                    </div>
                </div>
            </div>


            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($KumasinvoicesTotal, 2) }} </strong> </h4>
                        INVOICES TOTAL: {{ $KumasinvoicesCount }}
                        <hr>
                        SALARIES TOTAL: {{ $KumasalariesCount }}
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($KumasalariesTotal, 2) }} </strong> </h4>  
                    </div>
                </div>
            </div>

        </div> <br> <br>
        @endif

        <hr />
        <br>
              <div class="card-header  ml-2  d-none d-lg-block">
                  @include('flash-messages')
              </div> <br>


        <div class="row">
            <form action="/salaries" method="POST">
                @csrf
                <div class="col">
                    <table id="myTable" class="display">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th> Month </th>
                                <th> Field </th>
                              <th> Client Invoice Amount </th>
                              <th>Client Name </th>
                              <th> Guards Salary </th>
                              <th> Difference </th>
                              <th>Status</th>  
                              <th>View Invoices </th>   
                              <th>View Guards</th> 
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ( $invoices as $key => $invoice )
                            <tr>
                                <td> {{ $key + 1 }} </td>
                                <td> {{ $month }} </td>
                                <td> {{ $invoice->client?->field?->name }} </td>
                                <td> GH&#x20B5;  {{number_format($invoice->total_invoice, 2) }} </td>
                                @foreach ($salaries as $salary)
                                    @if ($invoice->client_id == $salary->client_id)
                                        <td> {{$invoice->client?->name }} {{$invoice->client?->business_name }} </td>
                                        <td>GH&#x20B5; {{number_format($salary->total_salary, 2) }} </td>
                                        <td>GH&#x20B5;{{ number_format($invoice->total_invoice - $salary->total_salary, 2)}}</td>
                                            @if($invoice->total_invoice <= $salary->total_salary)
                                                    <td><span class="badge bg-label-danger"> Loss </span></td>
                                            @else
                                                    <td><span class="badge bg-label-success"> Profit </span></td>
                                            @endif
                                    @endif
                                @endforeach
                                <td> <a href="/invToPayrollInvoice/{{ $invoice->client_id }}/{{ $month }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a> 
                                </td>
                                <td> <a href="/invToPayrollGuards/{{ $invoice->client_id }}/{{ $month }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a> 
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </form>
        </div>

    </div>
  <!-- / Content -->
  @endsection




   @section('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.dataTables.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.html5.min.js"></script>


    <script>
        new DataTable('#myTable', {
            responsive: true,

            layout: {
              topStart: {
                // buttons: [] // No export/download buttons
              }
            }
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






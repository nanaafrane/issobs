<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.5/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.5/css/buttons.dataTables.css">    
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
      <li class="menu-item active open">
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

          <li class="menu-item active">
            <a href="{{ url('salaries/create') }}" class="menu-link">
              <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
              <div class="text-truncate" data-i18n="Locations">Salaries</div>
            </a>
          </li>

          <li class="menu-item">
            <a href="{{ url('salaries/transaction') }}" class="menu-link">
              <i class="menu-icon tf-icons bx bx-transfer-alt"></i>
              <div class="text-truncate" data-i18n="Locations">Transactions</div>
            </a>
          </li>


          <li class="menu-item">
            <a href="{{ url('salaries/invpayroll') }}" class="menu-link">
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
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bx-bxs-user-detail"></i> Active Employees </h3>
            </div>
        </div><br>

         @if(Auth::user()->hasRole(['Invoice','Manager' ]))
        <div class="row">
            <div class="col-lg-2">
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
                        <p class="mb-1"><strong> ACCRA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong> 0  </strong> </h4>
                        <small class="fw-medium"> TOTAL EMPLOYEES  </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
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
                        <p class="mb-1"><strong> BOTWE </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong>0 </strong> </h4>
                        <small class="fw-medium"> TOTAL EMPLOYEES </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2">
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
                        <p class="mb-1"><strong> TEMA </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong>0 </strong> </h4>
                        <small class="fw-medium"> TOTAL EMPLOYEES </small>
                    </div>
                </div>
            </div>



            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1">TAKORADI</p>
                        <h4 class="card-title mb-3 text-white">0 </h4>
                        <small class="fw-medium"> TOTAL EMPLOYEES   </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"> <strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3 text-white">0</h4>
                        <small class="fw-medium"> TOTAL EMPLOYEES  </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3 text-white">0 </h4>
                        <small class="fw-medium"> TOTAL EMPLOYEES  </small>
                    </div>
                </div>
            </div>

        </div> <br> <br>
                     <div class="card-header  ml-2  d-none d-lg-block">
                  @include('flash-messages')
        </div> <br>
        @endif

        <div class="row">
          <form action="" method="POST" enctype="multipart/form-data"> 
            <div class="col">
                <label for="acc_number" class="form-label"> <strong>   UPLOAD SALARIES: </strong> </label>
                <input
                      type="file"
                      id="excelFile"
                      name="excelFile"
                      class="account-file-input  @error('excelFile') is-invalid @enderror"
                      accept=".xls, .xlsx"/>

                <button class="btn btn-dark" onclick="return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-recycle"> </i> {{ __('Upload') }}</button>
            </div>
          </form>
        </div> <br> <br>
        <hr> <br> <br>
        <div class="row">
            <form action="/salariesDeleteMultiple" method="POST">
                @csrf
                <div class="col">
                    <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />

                    <div class="form-check form-check-inline">
                        <select name="salary" class="form-select">
                            <option value=""> Select All </option>
                        </select>
                    </div>

                    <div class="form-check form-check-inline">
                        <button class="btn btn-danger" onclick="return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-recycle"> </i> {{ __('Delete') }}</button>                   
                    </div>
                    <div class="form-check form-check-inline">
                        <button class="btn btn-success" onclick="return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-recycle"> </i> {{ __('Approve') }}</button>                   
                    </div>
                    <table id="myTable" class="display">
                        <thead>
                            <tr>
                                <th> </th>
                                <th> #</th>
                                <th> Salary Month</th>
                                <th> Emp. ID </th>
                                <th> Name</th>
                                <th> Department </th>
                                <th> Role</th>
                                <th> Field </th>
                                <th> Emp. Type </th>
                                <th> Client </th>
                                <th> Location </th>
                                <th> SSNIT No.</th>
                                <th> TIN #</th>
                                <th> Payment Type</th>
                                <th> Bank Name </th>
                                <th> Branch </th>
                                <th> Account No.</th>
                                <th> Basic Salary</th>
                                <th> Allowance</th>
                                <th> Airtime Allowance</th>
                                <th> Overtime</th>
                                <th> Reimbursements </th>
                                <th> Transport Allowance</th>
                                <th> ssnit_tier2_5</th>
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
                                <th> meal</th>
                                <th> loan</th>
                                <th> walkin</th>
                                <th> amnt_ded_cof_start_date</th>
                                <th> other_deductions</th>
                                <th> gross_salary </th>
                                <th> total_deductions</th>
                                <th> net_salary </th>
                                <th> ssnit_comp_cont_13 </th>
                                <th> ssnit_tobe_paid13_5</th>
                                <th>Total </th>

                            </tr>
                        </thead>
                        <tbody>

                        @foreach ($salaries as $key => $salary )
                            <tr>
                                <td> <input class="checkBoxes form-check-input" type="checkbox" name="salary[]" value="{{ $salary->id }}" /> </td>
                                <td> {{ $salary->id }} </td>
                                <td> {{$salary->salary_month?->format('F, Y')}} </td>
                                <td> {{ $salary->employee_id }} </td>
                                <td> {{ $salary->employee?->name }} </td>
                                <td> {{ $salary->department_id }} </td>
                                <td> {{ $salary->role_id }} </td>
                                <td> {{ $salary->field_id }} </td>
                                <td> {{ $salary->employee?->worker_type }} </td>
                                <td> {{ $salary->client_id }}</td>
                                <td> {{ $salary->location }} </td>
                                <td> {{$salary->ssnit_number}}</td>
                                <td> {{$salary->tin_number}}</td>
                                <td> {{$salary->payment_type}}</td>
                                <td> {{$salary->bank_id}}</td>
                                <td> {{$salary->branch}}</td>
                                <td> {{$salary->account_number}}</td>
                                <td> {{$salary->basic_salary}}</td>
                                <td> {{$salary->allowances}}</td>
                                <td> {{$salary->airtime_allowance}}</td>
                                <td> {{$salary->overtime}}</td>
                                <td> {{$salary->reimbursements}}</td>
                                <td> {{$salary->transport_allowance}}</td>
                                <td> {{$salary->ssnit_tier2_5}}</td>   
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
                                <td> {{$salary->net_salary}} </td>                            
                                <td> {{$salary->ssnit_comp_cont_13}} </td>                            
                                <td> {{$salary->ssnit_tobe_paid13_5}} </td>                            
                                <td> {{$salary->cost_to_company }}</td>
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
    <script src="https://cdn.datatables.net/2.3.5/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.5/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.colVis.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>


    <script>

      new DataTable('#myTable', {
        layout: {
            topStart: {
                buttons: [
                    {
                        extend: 'excelHtml5',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    'colvis'
                ]
            }


        },
                  columnDefs: [
              {
                  targets: [-2,-3,-7,-8,-9,-10,-11,-12,-13,-14,-15,-16,-17,-18,-19,-20,-21,-22,-23,-24,-25,-26,-27,-28],
                  visible: false
              }
          ]
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
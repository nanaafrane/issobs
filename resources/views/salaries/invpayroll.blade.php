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
          <div class="text-truncate" data-i18n="Staffs">Staffs</div>
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
          <li class="menu-item">
            <a href="{{ url('salaries') }}" class="menu-link">
              <!-- <i class="menu-icon tf-icons bx bxs-user-account"></i> -->
              <div class="text-truncate" data-i18n="Employees">Add to Salaries</div>
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

      <li class="menu-header small text-uppercase"><span class="menu-header-text">PAYROLL</span></li>
      <li class="menu-item">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
          <div class="text-truncate" data-i18n="Payroll">Payroll</div>
        </a>
        <ul class="menu-sub">
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

        </ul>
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
                <div style="background: crimson; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
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
                <div style="background: crimson; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
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
                <div style="background: crimson; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
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
                <div style="background: crimson; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
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
                <div style="background: crimson; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
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
                <div style="background: crimson; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3 text-white">0 </h4>
                        <small class="fw-medium"> TOTAL EMPLOYEES  </small>
                    </div>
                </div>
            </div>

        </div> <br>
       
       
        @endif

        <div class="row">
            <div class="col">
                <table id="myTable" class="display">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Period</th>
                            <th>Cash Amount</th>
                            <th>Momo Amount</th>
                            <th>Cheque Amount </th>
                            <th> Transfer Amount </th>
                            <th>Status</th>
                            <th>Exp id</th>
                            <th>Expense Amount</th>
                            <th> Total_Amount </th>
                            <th>Date Created</th>
                            <th>Branch</th>
                        </tr>
                    </thead>
                    <tbody>

                         @if(Auth::user()->hasRole(['Invoice','Manager' ]))
                       
                        <tr>
                            <td>  </td>
                            <td>  </td>
                            <td>GH&#x20B5;  </td>
                            <td>GH&#x20B5;  </td>
                            <td>GH&#x20B5;  </td>
                            <td>GH&#x20B5;  </td>

                            <td><span class="badge bg-label-success"> </span></td>
                       
                            <td>   </td>
                            <td>GH&#x20B5;   </td>
                            <td>GH&#x20B5;  </td>
                            <td>  </td>
                            <td>  </td>
                        </tr>
                        @endif








                    </tbody>
                </table>

            </div>
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
                    buttons: ['excelHtml5', 'pdfHtml5']
                }
            }
        });
    </script>
    @endsection
</x-hr-dashboard>
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
      <li class="menu-item active open">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-home-smile"></i>
          <div class="text-truncate" data-i18n="Dashboards">Dashboard</div>
        </a>
        <ul class="menu-sub">
          <li class="menu-item active">
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


    </ul>
  </aside>
  <!-- / Menu -->
  @endsection


  @section('content')

  <!-- Content -->
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
      <div class="col-xxl-8 mb-6 order-0">

        <div class="row">
          <div class="col-lg-3">
            <div class="card shadow-none bg-transparent border border-danger">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img
                      src="img/icons/unicons/paypal.png"
                      alt="chart success"
                      class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt3"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                      <a class="dropdown-item" href=" ">View</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1"><strong>ACCRA</strong></p>
                <h4 class="card-title mb-3"><strong> {{ $accraGuardsCount }} </strong> </h4>
                <small class="fw-medium"> TOTAL GUARDS </small>
              </div>
            </div>
          </div>

          <div class="col-lg-3">
            <div class="card shadow-none bg-transparent border border-secondary">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img
                      src="img/icons/unicons/paypal.png"
                      class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt3"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-white"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                      <a class="dropdown-item" href="">View</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1"><strong> BOTWE </strong></p>
                <h4 class="card-title mb-3 ">{{ $botweGuardsCount }}</h4>
                <small class="fw-medium"> TOTAL GUARDS </small>
              </div>
            </div>
          </div>

          <div class="col-lg-3">
            <div class="card shadow-none bg-transparent border border-info">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img
                      src="img/icons/unicons/paypal.png"
                      class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt3"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                      <a class="dropdown-item" href="">View</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1"><strong>SHY HILLS </strong> </p>
                <h4 class="card-title mb-3 ">{{ $shyhillsGuardsCount }}</h4>
                <small class="fw-medium"> TOTAL GUARDS </small>
              </div>
            </div>
          </div>


          <div class="col-lg-3">
            <div class="card shadow-none bg-transparent border border-warning">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img
                      src="img/icons/unicons/paypal.png"
                      class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt3"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                      <a class="dropdown-item" href="">View</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1"> <strong> TEMA </strong></p>
                <h4 class="card-title mb-3 ">{{ $temaGuardsCount }}</h4>
                <small class="fw-medium"> TOTAL GUARDS </small>
              </div>
            </div>
          </div>

        </div>


      </div>
      <div class="col-xxl-4 col-lg-12 col-md-4 order-1">
        <div class="row">
          <div class="col-lg-6 col-md-12 col-6 mb-6">
            <div class="card shadow-none bg-transparent border border-success">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img
                      src="img/icons/unicons/wallet-info.png"
                      class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt3"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                      <a class="dropdown-item" href="">View</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1"><strong> TAKORADI  </strong></p>
                <h4 class="card-title mb-3 "> {{ $takoradiGuardsCount }} </h4>
                <small class="fw-medium"> TOTAL GUARDS </small>
              </div>
            </div>
          </div>

          <div class="col-lg-6 col-md-12 col-6 mb-6">
            <div class="card shadow-none bg-transparent border border-primary">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img
                      src="img/icons/unicons/wallet-info.png"
                      alt="wallet info"
                      class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt6"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt6">
                      <a class="dropdown-item" href="">View</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1"><strong> KOFORIDUA </strong></p>
                <h4 class="card-title mb-3">{{ $koforiduaGuardsCount }} </h4>
                <small class="fw-medium"> TOTAL GUARDS </small>
              </div>
            </div>
          </div>

          <div class="col-lg-6 col-md-12 col-6 mb-6">
            <div class="card shadow-none bg-transparent border border-primary">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img
                      src="img/icons/unicons/wallet-info.png"
                      alt="wallet info"
                      class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt6"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt6">
                      <a class="dropdown-item" href="">View</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1"><strong> KUMASI </strong></p>
                <h4 class="card-title mb-3">{{ $shyhillsGuardsCount }} </h4>
                <small class="fw-medium"> TOTAL GUARDS </small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Total Revenue -->
      <div class="col-12 col-xxl-8 order-2 order-md-3 order-xxl-2 mb-6 total-revenue">
        <div class="card">
          <div class="row row-bordered g-0">
            <div class="col-lg-12">
              <div class="card-header d-flex align-items-center justify-content-between">
                <div class="card-title mb-0">
                  <h5 class="m-0 me-2">Salaries per year </h5>
                </div>

              </div>
              <div id="totalRevenueChart" class="px-3"></div>
            </div>

          </div>
        </div>
      </div>
      <!--/ Total Revenue -->

      <div class="col-12 col-md-8 col-lg-12 col-xxl-4 order-3 order-md-2 profile-report">
        <div class="row">

          <div class="col-6 mb-6 payments">
            <div class="card h-100 text-white bg-dark">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img src="img/icons/unicons/wallet-info.png" alt="paypal" class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt4"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt4">
                      <a class="dropdown-item" href="">View</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1"><strong>ACCOUNTS</strong></p>
                <h4 class="card-title mb-3 text-white"> {{ $AccountsCount }} </h4>
                <small class="text-default fw-medium"> TOTAL STAFFS </small>
              </div>
            </div>
          </div>

          <div class="col-6 mb-6 transactions">
            <div class="card h-100 text-white bg-dark">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img src="img/icons/unicons/wallet-info.png" alt="Credit Card" class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt1"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu" aria-labelledby="cardOpt1">
                      <a class="dropdown-item" href="">View</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1">HR</p>
                <h4 class="card-title mb-3 text-white">{{ $HRCount }}</h4>
                <small class="text-default fw-medium">TOTAL STAFFS</small>
              </div>
            </div>
          </div>

          <div class="col-6 mb-6 payments">
            <div class="card h-100 text-white bg-primary">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img src="img/icons/unicons/wallet-info.png" alt="paypal" class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button class="btn p-0" type="button" id="cardOpt4" data-bs-toggle="dropdown" aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt4">
                      <a class="dropdown-item" href="">View</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1">OPERATIONS</p>
                <h4 class="card-title mb-3 text-white"> {{ $OperationCount }} </h4>
                <small class="text-default fw-medium">TOTAL STAFFS</small>
              </div>
            </div>
          </div>

          <div class="col-6 mb-6 transactions">
            <div class="card h-100 text-white bg-danger">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img src="img/icons/unicons/wallet-info.png" alt="Credit Card" class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button class="btn p-0" type="button" id="cardOpt1" data-bs-toggle="dropdown" aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu" aria-labelledby="cardOpt1">
                      <a class="dropdown-item" href="">View</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1">GUARDS</p>
                <h4 class="card-title mb-3 text-white">{{ $GuardsCount }} </h4>
                <small class="text-default fw-medium">TOTAL STAFFS</small>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

  </div>
  <!-- / Content -->

  @endsection
</x-hr-dashboard>
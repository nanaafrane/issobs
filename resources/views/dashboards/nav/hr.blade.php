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
            <a href="{{url('employeesPending')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">Pending</div>
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
      @if(Auth::user()->hasRole(['Manager']))

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

      <li class="menu-header small text-uppercase"> <span class="menu-header-text text-danger">Accounts</span></li>
      <li class="menu-item">
          <a href="javascript:void(0);" class="menu-link menu-toggle"><i class="menu-icon tf-icons bx bx-time-five bg-danger"></i><div>Overtime</div></a>
          <ul class="menu-sub">
              <li class="menu-item"><a href="{{url('overtime')}}" class="menu-link"><div>Daily Entry</div></a></li>
              <li class="menu-item"><a href="{{url('overtime-report')}}" class="menu-link"><div>Reports</div></a></li>
          </ul>
      </li>
        </ul>
      </li>

      @endif

    </ul>
  </aside>
  <!-- / Menu -->

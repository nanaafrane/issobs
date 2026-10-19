  <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
      <a href="#" class="app-brand-link">
        <span class="app-brand-logo demo">
          <img width="70px" src="{{ asset('img/icons/brands/issobs.png') }}" alt="ISSOBS logo">
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
      <li class="menu-item active open">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-home-smile"></i>
          <div class="text-truncate" data-i18n="Dashboards">Dashboard</div>
        </a>
        <ul class="menu-sub">
          <li class="menu-item active">
            <a href="{{ url('home') }}" class="menu-link">
              <div class="text-truncate" data-i18n="Dashboard">Dashboard</div>
            </a>
          </li>
        </ul>
      </li>
    <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
      <li class="menu-item">
        <a href="{{ url('attendance') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-calendar-check text-info me-2"></i>
          <div class="text-truncate" data-i18n="Attendance">Guard Attendance</div>
        </a>
      </li>
    </ul>
  </aside>

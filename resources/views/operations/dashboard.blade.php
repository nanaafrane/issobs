<x-hr-dashboard>

  @section('side_nav')
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
  @endsection

  @section('css')
  <style>
    .ops-kpi { border-radius: 10px; padding: 1rem 1.2rem; background:#fff; border:1px solid #e5e7eb; }
    .ops-kpi .value { font-size: 1.5rem; font-weight: 700; }
  </style>
  @endsection

  @section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
      <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h4 class="mb-1 fw-bold">Operations Dashboard</h4>
            <p class="text-muted mb-0">Guard coverage and field operations overview</p>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-lg-3 col-6">
        <div class="ops-kpi">
          <small class="text-muted">TOTAL GUARDS</small>
          <div class="value">{{ $GuardsCount ?? 0 }}</div>
          <div class="small text-success">Active: {{ $activeGuardsCount ?? 0 }}</div>
          <div class="small text-danger">Terminated: {{ $terminatedGuardsCount ?? 0 }}</div>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="ops-kpi">
          <small class="text-muted">ACTIVE</small>
          <div class="value text-success">{{ $activeGuardsCount ?? 0 }}</div>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="ops-kpi">
          <small class="text-muted">TERMINATED</small>
          <div class="value text-danger">{{ $terminatedGuardsCount ?? 0 }}</div>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="ops-kpi">
          <small class="text-muted">OPERATIONS STAFF</small>
          <div class="value text-primary">{{ $OperationCount ?? 0 }}</div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm border-0 h-100 border-danger">
          <div class="card-body">
            <p class="mb-1 text-muted">Accra</p>
            <h3 class="mb-0 fw-bold">{{ $accraGuardsCount ?? 0 }}</h3>
            <small class="text-success">Active: {{ $accraActiveGuardsCount ?? 0 }}</small><br>
            <small class="text-danger">Terminated: {{ $accraTerminatedGuardsCount ?? 0 }}</small>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm border-0 h-100 border-secondary">
          <div class="card-body">
            <p class="mb-1 text-muted">Botwe</p>
            <h3 class="mb-0 fw-bold">{{ $botweGuardsCount ?? 0 }}</h3>
            <small class="text-success">Active: {{ $botweActiveGuardsCount ?? 0 }}</small><br>
            <small class="text-danger">Terminated: {{ $botweTerminatedGuardsCount ?? 0 }}</small>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm border-0 h-100 border-info">
          <div class="card-body">
            <p class="mb-1 text-muted">Tema</p>
            <h3 class="mb-0 fw-bold">{{ $temaGuardsCount ?? 0 }}</h3>
            <small class="text-success">Active: {{ $temaActiveGuardsCount ?? 0 }}</small><br>
            <small class="text-danger">Terminated: {{ $temaTerminatedGuardsCount ?? 0 }}</small>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm border-0 h-100 border-warning">
          <div class="card-body">
            <p class="mb-1 text-muted">Takoradi</p>
            <h3 class="mb-0 fw-bold">{{ $takoradiGuardsCount ?? 0 }}</h3>
            <small class="text-success">Active: {{ $takoradiActiveGuardsCount ?? 0 }}</small><br>
            <small class="text-danger">Terminated: {{ $takoradiTerminatedGuardsCount ?? 0 }}</small>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm border-0 h-100 border-success">
          <div class="card-body">
            <p class="mb-1 text-muted">Koforidua</p>
            <h3 class="mb-0 fw-bold">{{ $koforiduaGuardsCount ?? 0 }}</h3>
            <small class="text-success">Active: {{ $koforiduaActiveGuardsCount ?? 0 }}</small><br>
            <small class="text-danger">Terminated: {{ $koforiduaTerminatedGuardsCount ?? 0 }}</small>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm border-0 h-100 border-dark">
          <div class="card-body">
            <p class="mb-1 text-muted">Kumasi</p>
            <h3 class="mb-0 fw-bold">{{ $kumasiGuardsCount ?? 0 }}</h3>
            <small class="text-success">Active: {{ $kumasiActiveGuardsCount ?? 0 }}</small><br>
            <small class="text-danger">Terminated: {{ $kumasiTerminatedGuardsCount ?? 0 }}</small>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm border-0 h-100 border-secondary">
          <div class="card-body">
            <p class="mb-1 text-muted">Shy Hills</p>
            <h3 class="mb-0 fw-bold">{{ $shyhillsGuardsCount ?? 0 }}</h3>
            <small class="text-success">Active: {{ $shyhillsActiveGuardsCount ?? 0 }}</small><br>
            <small class="text-danger">Terminated: {{ $shyhillsTerminatedGuardsCount ?? 0 }}</small>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mt-2">
      <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-0 fw-semibold">Guard status by field</div>
          <div class="card-body"><div id="ops-field-chart"></div></div>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-0 fw-semibold">Guard status mix</div>
          <div class="card-body"><div id="ops-status-chart"></div></div>
        </div>
      </div>
    </div>
  </div>
  @endsection

  @section('scripts')
  <script src="{{ asset('vendor/libs/apex-charts/apexcharts.js') }}"></script>
  <script>
      const opsFieldLabels = ['Accra', 'Botwe', 'Tema', 'Takoradi', 'Koforidua', 'Kumasi', 'Shy Hills'];
      const opsFieldActive = [
          {{ $accraActiveGuardsCount ?? 0 }},
          {{ $botweActiveGuardsCount ?? 0 }},
          {{ $temaActiveGuardsCount ?? 0 }},
          {{ $takoradiActiveGuardsCount ?? 0 }},
          {{ $koforiduaActiveGuardsCount ?? 0 }},
          {{ $kumasiActiveGuardsCount ?? 0 }},
          {{ $shyhillsActiveGuardsCount ?? 0 }}
      ];
      const opsFieldTerminated = [
          {{ $accraTerminatedGuardsCount ?? 0 }},
          {{ $botweTerminatedGuardsCount ?? 0 }},
          {{ $temaTerminatedGuardsCount ?? 0 }},
          {{ $takoradiTerminatedGuardsCount ?? 0 }},
          {{ $koforiduaTerminatedGuardsCount ?? 0 }},
          {{ $kumasiTerminatedGuardsCount ?? 0 }},
          {{ $shyhillsTerminatedGuardsCount ?? 0 }}
      ];

      if ((opsFieldActive.concat(opsFieldTerminated)).some(v => v > 0)) {
          new ApexCharts(document.querySelector('#ops-field-chart'), {
              chart: { type: 'bar', height: 280, stacked: true, toolbar: { show: false } },
              series: [
                  { name: 'Active', data: opsFieldActive },
                  { name: 'Terminated', data: opsFieldTerminated }
              ],
              xaxis: { categories: opsFieldLabels, labels: { rotate: -35, trim: true } },
              colors: ['#10b981', '#ef4444'],
              legend: { position: 'bottom' },
              dataLabels: { enabled: false },
              tooltip: { y: { formatter: val => val ? Number(val).toLocaleString() : '0' } }
          }).render();
      } else {
          document.querySelector('#ops-field-chart').innerHTML = '<div class="text-muted p-3">No guard data available.</div>';
      }

      const opsStatusSeries = [
          {{ $activeGuardsCount ?? 0 }},
          {{ $terminatedGuardsCount ?? 0 }}
      ];

      if (opsStatusSeries.some(v => v > 0)) {
          new ApexCharts(document.querySelector('#ops-status-chart'), {
              chart: { type: 'donut', height: 260 },
              series: opsStatusSeries,
              labels: ['Active', 'Terminated'],
              colors: ['#10b981', '#ef4444'],
              legend: { position: 'bottom' }
          }).render();
      } else {
          document.querySelector('#ops-status-chart').innerHTML = '<div class="text-muted p-3">No status data available.</div>';
      }
  </script>
  @endsection

</x-hr-dashboard>

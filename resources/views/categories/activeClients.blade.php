<x-sales-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">
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
      <li class="menu-item ">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-home-smile"></i>
          <div class="text-truncate" data-i18n="Dashboards"><strong>Dashboard</strong></div>
        </a>
        <ul class="menu-sub">
          <li class="menu-item">
            <a href="{{url('home')}}" class="menu-link">
              <div class="text-truncate" data-i18n="Dashboard">Dashboard</div>
            </a>
          </li>
        </ul>
      </li>
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

      @if(Auth::user()->hasRole(['Invoice','Finance Manager', 'Director']))
      <li class="menu-item">
        <a href="{{ url('invoice') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-bxs-receipt bg-primary"></i>
          <div class="text-truncate" data-i18n="Invoices">Invoices</div>
        </a>
      </li>
      <li class="menu-item ">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-bxs-receipt bg-primary"></i>
          <div class="text-truncate" data-i18n="Staffs">Pro Forma</div>
          </a>
          <ul class="menu-sub">
          <li class="menu-item ">
              <a href="{{url('proforma/create')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SRegister">Generate</div>
              </a>
          </li>
          <li class="menu-item">
              <a href="{{url('proforma')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">List</div>
              </a>
          </li>
          <li class="menu-item">
              <a href="{{url('proformaClient')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">ProForma Clients</div>
              </a>
          </li>
          </ul>
      </li>
      @endif


      @if(Auth::user()->hasRole(['Finance Manager']))
            
     <li class="menu-item">
        <a href="{{url('receipt')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-money-withdraw bg-primary"></i>
          <div class="text-truncate" data-i18n="Receipts">Receipts</div>
        </a>
      </li>

      <!-- Components -->
      <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
      <li class="menu-item">
        <a href="{{url('client')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-bxs-user-detail bg-info"></i>
          <div class="text-truncate" data-i18n="Clients">Clients</div>
        </a>
      </li>
      <li class="menu-item ">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bxs-user-account bg-info"></i>
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
              <a href="{{url('employeesnrrit')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">Terminate / Recruit</div>
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

      <li class="menu-item active">
        <a href="{{url('category')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bxs-category bg-info"></i>
          <div class="text-truncate" data-i18n="Categories">Categories</div>
        </a>
      </li>
      @elseif(Auth::user()->hasRole(['Invoice' ,'Director']))

      <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
        <li class="menu-item ">
            <a class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-bxs-user-detail bg-info"></i>
                <div class="text-truncate" data-i18n="Clients"><strong>Clients</strong></div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item ">
                    <a href="{{url('client/create')}}" class="menu-link">
                        <div class="text-truncate" data-i18n="CRegister">Register</div>
                    </a>
                </li>
                <li class="menu-item ">
                    <a href="{{url('client')}}" class="menu-link">
                        <div class="text-truncate" data-i18n="CList">List</div>
                    </a>
                </li>
            </ul>
        </li>
        <li class="menu-item ">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bxs-category bg-info"></i>
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
          <li class="menu-item">
              <a href="{{url('employeesnrrit')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">Terminate / Recruit </div>
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

      @if(Auth::user()->hasRole(['Manager','Officer','Finance Manager', 'Director']) )

      <li class="menu-header small text-uppercase"> <span class="menu-header-text text-danger">Accounts</span></li>

      <li class="menu-item">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bxs-analyse bg-danger"></i>
          <div class="text-truncate" data-i18n="Accounts"> Accounts </div>
        </a>
        <ul class="menu-sub">
          <li class="menu-item">
            <a href="{{url('collections')}}" class="menu-link">
              <i class="menu-icon tf-icons bx bx-add-to-queue bg-danger"></i>
              <div class="text-truncate" data-i18n="ARegister">Collections</div>
            </a>
          </li>
          <li class="menu-item">
            <a href="{{url('deposit')}}" class="menu-link">
              <i class="menu-icon tf-icons bx bx-arrow-from-left bg-danger"></i>
              <div class="text-truncate" data-i18n="AList">Bank Deposit</div>
            </a>
          </li>
          <li class="menu-item">
            <a href="{{url('banks')}}" class="menu-link">
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

        @if(Auth::user()->hasPermission('Accounts') && Auth::user()->hasRole(['Invoice', 'Officer', 'Director', 'Finance Manager']) )
      <li class="menu-header small text-uppercase"><span class="menu-header-text">PAYROLL</span></li>
        <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                <div class="text-truncate" data-i18n="Payroll">Payroll</div>
                </a>
                <ul class="menu-sub">
                @if(Auth::user()->hasRole([ 'Finance Manager']))
                <li class="menu-item">
                    <a href="{{ url('salaries') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bxs-user-account"></i>
                    <div class="text-truncate" data-i18n="Employees">Add to Salaries</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="{{ url('salaries/create') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                    <div class="text-truncate" data-i18n="Salaries">Salaries</div>
                    </a>
                </li>
                @endif

                <li class="menu-item">
                    <a href="{{ url('salariesTransaction') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-transfer-alt"></i>
                    <div class="text-truncate" data-i18n="Transaction">Transactions</div>
                    </a>
                </li>


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

        @if(session('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                @foreach($errors->all() as $error) <div>{{ $error }}</div> @endforeach
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <h3 class="card-header text-info"> <i class="icon-base bx bx-bxs-category"></i> Active Clients &mdash; Assign To Category </h3>
            </div>
        </div><br>

        {{-- Month picker --}}
        <div class="row">
            <form action="{{ route('category.activeClientsByMonth') }}" method="GET">
                <div class="col">
                    <label for="month" class="form-label"> <strong> CHOOSE A MONTH </strong> </label> <br>
                    <div class="form-check form-check-inline">
                        <input type="month" class="form-control" name="month" id="month" value="{{ $month->format('Y-m') }}" required/> <br>
                        <button class="btn btn-dark" type="submit"> <i class="icon-base bx bx-search-alt"></i> {{ __('View') }}</button>
                    </div>
                </div>
            </form>
        </div> <br>

        @php
            // short label + theme colour per category (shared by tiles, chips and progress bars)
            $catMeta = [
                'Category A' => ['A', 'primary'],
                'Category B' => ['B', 'info'],
                'Category C' => ['C', 'success'],
                'Category D' => ['D', 'secondary'],
            ];
            $totals = $summary['totals'];
        @endphp

        <style>
            .cat-tile, .field-card { cursor: pointer; border: 2px solid transparent; transition: transform .12s, border-color .12s, box-shadow .12s; }
            .cat-tile:hover, .field-card:hover { transform: translateY(-1px); box-shadow: 0 .25rem .75rem rgba(0,0,0,.08); }
            .cat-tile.active, .field-card.active { border-color: var(--bs-primary, #696cff); }
            .field-grid { max-height: 26rem; overflow-y: auto; padding: .25rem; }
            .chip { display: inline-flex; align-items: center; gap: .3rem; padding: .15rem .55rem; border-radius: 50rem; font-size: .75rem; cursor: pointer; border: 1px solid transparent; }
            .chip.zero { opacity: .45; }
            .chip.active { border-color: currentColor; font-weight: 700; }
            .sticky-apply { position: fixed; bottom: 0; left: 0; right: 0; z-index: 1040; padding: .6rem 1.25rem;
                background: var(--bs-body-bg, #fff); border-top: 1px solid rgba(0,0,0,.1); box-shadow: 0 -4px 16px rgba(0,0,0,.08); }
        </style>

        {{-- Category tiles: click to filter the table by category. Salary figures are for the selected month. --}}
        <div class="d-flex align-items-center mb-2">
            <h6 class="mb-0 text-muted text-uppercase">Clients &amp; salaries &mdash; {{ $month->format('F Y') }}</h6>
            @unless($payroll['has_payroll'])
                <span class="badge bg-label-secondary ms-2">Payroll not generated for this month</span>
            @endunless
        </div>
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card cat-tile js-tile bg-label-dark h-100" data-state="" role="button" tabindex="0">
                    <div class="card-body py-3">
                        <div class="small fw-semibold">ACTIVE CLIENTS</div>
                        <div class="fs-3 fw-bold">{{ $totals['all'] }}</div>
                        @include('categories.partials.tile_payroll', ['t' => $payroll['tiles']['all'], 'hasPayroll' => $payroll['has_payroll']])
                    </div>
                </div>
            </div>
            @foreach($catMeta as $name => [$short, $color])
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card cat-tile js-tile bg-label-{{ $color }} h-100" data-state="{{ $name }}" role="button" tabindex="0">
                    <div class="card-body py-3">
                        <div class="small fw-semibold">{{ strtoupper($name) }}</div>
                        <div class="fs-3 fw-bold">{{ $totals[$name] }}</div>
                        @include('categories.partials.tile_payroll', ['t' => $payroll['tiles'][$name], 'hasPayroll' => $payroll['has_payroll']])
                    </div>
                </div>
            </div>
            @endforeach
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card cat-tile js-tile bg-label-warning h-100" data-state="outstanding" role="button" tabindex="0">
                    <div class="card-body py-3">
                        <div class="small fw-semibold">OUTSTANDING</div>
                        <div class="fs-3 fw-bold">{{ $totals['outstanding'] }}</div>
                        @include('categories.partials.tile_payroll', ['t' => $payroll['tiles']['outstanding'], 'hasPayroll' => $payroll['has_payroll']])
                    </div>
                </div>
            </div>
        </div>

        {{-- Field office cards: click the card to filter by office, a chip to filter office + category --}}
        <div class="d-flex align-items-center mb-2">
            <h6 class="mb-0 text-muted text-uppercase">Field offices &mdash; {{ $month->format('F Y') }}</h6>
        </div>
        <div class="field-grid mb-3">
            <div class="row g-2">
                @foreach($summary['fields'] as $f)
                <div class="col-sm-6 col-lg-4 col-xxl-3">
                    <div class="card field-card js-field-card h-100" data-field="{{ $f['id'] }}" data-name="{{ $f['name'] }}" role="button" tabindex="0">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="fw-semibold text-truncate" title="{{ $f['name'] }}">{{ $f['name'] }}</div>
                                <span class="badge bg-label-dark">{{ $f['total'] }}</span>
                            </div>

                            <div class="progress mb-2" style="height:6px">
                                @foreach($catMeta as $name => [$short, $color])
                                    <div class="progress-bar bg-{{ $color }}" style="width: {{ $f['total'] ? round($f['cats'][$name] / $f['total'] * 100, 1) : 0 }}%"></div>
                                @endforeach
                                <div class="progress-bar bg-warning" style="width: {{ $f['total'] ? round($f['outstanding'] / $f['total'] * 100, 1) : 0 }}%"></div>
                            </div>

                            <div class="d-flex flex-wrap gap-1">
                                @foreach($catMeta as $name => [$short, $color])
                                    <span class="chip js-chip bg-label-{{ $color }} {{ $f['cats'][$name] ? '' : 'zero' }}"
                                          data-field="{{ $f['id'] }}" data-state="{{ $name }}" title="{{ $name }} in {{ $f['name'] }}">
                                        {{ $short }} <strong>{{ $f['cats'][$name] }}</strong>
                                    </span>
                                @endforeach
                                <span class="chip js-chip bg-label-warning {{ $f['outstanding'] ? '' : 'zero' }}"
                                      data-field="{{ $f['id'] }}" data-state="outstanding" title="Outstanding in {{ $f['name'] }}">
                                    Outstanding <strong>{{ $f['outstanding'] }}</strong>
                                </span>
                            </div>

                            @if($f['outstanding'])
                                <div class="d-flex align-items-center gap-2 mt-2 js-card-tools">
                                    <button type="button" class="btn btn-xs btn-link p-0 text-nowrap js-tick-outstanding" data-field="{{ $f['id'] }}">
                                        Tick {{ $f['outstanding'] }}
                                    </button>
                                    @if(Auth::user()->hasRole(['Finance Manager']))
                                    <select class="form-select form-select-sm js-card-apply"
                                            data-field="{{ $f['id'] }}" data-name="{{ $f['name'] }}" data-outstanding="{{ $f['outstanding'] }}"
                                            title="Put all {{ $f['outstanding'] }} outstanding clients of {{ $f['name'] }} into a category">
                                        <option value="" selected disabled>Apply outstanding to&hellip;</option>
                                        @foreach($categories as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Active filters --}}
        <div id="activeFilters" class="d-flex flex-wrap align-items-center gap-2 mb-3 small"></div>

        {{-- Apply panel: ONE category for everything ticked --}}
        <div class="card mb-4" id="applyPanel">
            <div class="card-body">
                <form id="bulkForm" action="{{ route('category.bulkAssign') }}" method="POST" class="row g-3 align-items-end">
                    @csrf
                    <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                    <input type="hidden" name="client_ids" id="clientIds" value="">

                    <div class="col-auto">
                        <span class="d-block small text-muted">Ticked clients</span>
                        <span class="fs-4 fw-bold"><span id="selectedCount">0</span></span>
                    </div>

                    @if(Auth::user()->hasRole(['Finance Manager']))
                    <div class="col-md-3">
                        <label for="categorySelect" class="form-label"><strong>Category to apply</strong></label>
                        <select id="categorySelect" name="category" class="form-select" required>
                            <option value="" selected disabled>Choose...</option>
                            @foreach($categories as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button id="btnApply" class="btn btn-dark" type="submit" disabled>
                            <i class="icon-base bx bx-recycle"></i> Apply to ticked clients
                        </button>
                    </div>
                    @endif
                </form>

                <hr>

                {{-- Bulk selection tools --}}
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a type="button" id="btnSelectPage" class="btn btn-sm btn-outline-secondary">Tick this page</a>
                    <a type="button" id="btnSelectMatching" class="btn btn-sm btn-outline-secondary">Tick all matching search / filter</a>
                    <a type="button" id="btnSelectOutstanding" class="btn btn-sm btn-outline-warning">Tick all outstanding</a>
                    <a type="button" id="btnDefaults" class="btn btn-sm btn-outline-info" {{ empty($allDefaultIds) ? 'disabled' : '' }}>Reset to Default A ({{ count($allDefaultIds) }})</a>
                    <a type="button" id="btnClear" class="btn btn-sm btn-outline-danger">Untick all</a>

                    <div class="ms-auto d-flex align-items-center gap-2">
                        <label for="stateFilter" class="mb-0 small text-muted">Show</label>
                        <select id="stateFilter" class="form-select form-select-sm" style="width:auto">
                            <option value="">All active clients</option>
                            <option value="outstanding">Outstanding only</option>
                            <option value="categorized">Categorized only</option>
                            @foreach($categories as $option)
                                <option value="{{ $option }}">{{ $option }} only</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div id="selectionNote" class="small text-muted mt-2">
                    Ticks stay in place while you search, sort or change pages.
                    @if(count($defaultIds))
                        {{ count($defaultIds) }} default client(s) are already ticked for Category A &mdash; nothing is saved until you press Apply.
                    @endif
                </div>
            </div>
        </div>

        {{-- Table (rows come from the server) --}}
        <div class="row">
            <div class="col-12">
                <div class="table-responsive">
                    <table id="myTableActiveClients" class="display" data-source="{{ route('category.clientsData') }}" style="width:100%">
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="form-check-input" id="checkPage" title="Tick / untick this page"></th>
                                <th>#</th>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Phone</th>
                                <th>Field</th>
                                <th>Category ({{ $month->format('M Y') }})</th>
                                <th>Assigned On</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            {{-- Rows are loaded by the server-side DataTable. --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if(Auth::user()->hasRole(['Finance Manager']))
            {{-- Per-card apply (submitted by JS after a confirm) --}}
            <form id="fieldApplyForm" action="{{ route('category.fieldAssign') }}" method="POST" class="d-none">
                @csrf
                <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                <input type="hidden" name="field_id" id="fieldApplyField" value="">
                <input type="hidden" name="category" id="fieldApplyCategory" value="">
            </form>

            {{-- Sticky apply bar: appears when something is ticked and the main apply panel has scrolled out of view --}}
            <div id="stickyBar" class="sticky-apply d-none" role="region" aria-label="Apply category to ticked clients">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div><strong id="barCount">0</strong> ticked</div>
                    <select id="barCategory" class="form-select form-select-sm" style="width:12rem" aria-label="Category to apply">
                        <option value="" selected disabled>Choose category...</option>
                        @foreach($categories as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    <button type="button" id="barApply" class="btn btn-sm btn-dark" disabled>
                        <i class="icon-base bx bx-recycle"></i> Apply
                    </button>
                    <a type="button" id="barClear" class="btn btn-sm btn-outline-danger">Untick all</a>
                </div>
            </div>
            <div style="height:4.5rem"></div>{{-- room so the bar never covers the last rows --}}
        @endif

    </div>
  <!-- / Content -->

  {{-- Client drill-down: employees + salaries for the selected month (loaded on click). --}}
        <div class="offcanvas offcanvas-end" tabindex="-1" id="clientPanel" aria-labelledby="clientPanelTitle" style="width:min(720px,100vw)">
            <div class="offcanvas-header border-bottom">
                <div>
                    <h5 class="offcanvas-title mb-0" id="clientPanelTitle">Client</h5>
                    <div class="small text-muted" id="clientPanelMeta"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body" id="clientPanelBody" aria-live="polite"></div>
        </div>

  @endsection

    @section('scripts')

    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.dataTables.js"></script>
    <script src="https://cdn.datatables.net/columncontrol/1.1.1/js/dataTables.columnControl.min.js"></script>

    <script>
    $(function () {
        const MONTH        = @json($month->format('Y-m'));
        const MONTH_LABEL  = @json($month->format('F Y'));
        const IDS_URL      = @json(route('category.clientsIds'));
        const DEFAULT_IDS  = @json($defaultIds);

        // Ticked client ids. Lives outside the table so it survives paging, sorting and searching.
        let selected = new Set(DEFAULT_IDS);
        let panelVisible = true; // is the main apply panel on screen? (see sticky bar)

        // Filters driven by the tiles / field cards / dropdown.
        const filters = { state: '', field_id: '' };
        const FIELD_NAMES = {};
        $('.js-field-card').each(function () { FIELD_NAMES[$(this).data('field')] = $(this).data('name'); });

        const $table    = $('#myTableActiveClients');
        const $count    = $('#selectedCount');
        const $apply    = $('#btnApply');
        const $category = $('#categorySelect');
        const $barCategory = $('#barCategory');
        const CAN_APPLY = $category.length > 0;

        function refreshUi() {
            $count.text(selected.size);
            $apply.prop('disabled', selected.size === 0 || !$category.val());

            // sticky bar mirrors the main panel
            $('#barCount').text(selected.size);
            $('#barApply').prop('disabled', selected.size === 0 || !$category.val());
            updateBar();

            const $boxes = $table.find('tbody .row-check');
            const ticked = $boxes.filter(':checked').length;
            $('#checkPage')
                .prop('checked', $boxes.length > 0 && ticked === $boxes.length)
                .prop('indeterminate', ticked > 0 && ticked < $boxes.length);
        }

        function syncPageBoxes() {
            $table.find('tbody .row-check').each(function () {
                this.checked = selected.has(parseInt(this.value, 10));
            });
            refreshUi();
        }

        const table = $table.DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100, 250, 500],
            ordering: true,
            searching: true,
            searchDelay: 500,
            ajax: {
                url: $table.data('source'),
                type: 'GET',
                dataType: 'json',
                data: function (d) {
                    d.month = MONTH;
                    d.state = filters.state;
                    d.field_id = filters.field_id;
                },
                dataSrc: 'data'
            },
            order: [[3, 'asc']],
            columnControl: [{
                target: 1,
                content: ['search']
            }],
            columns: [
                { data: 'select', orderable: false, searchable: false, className: 'text-center',
                  render: function (data, type, row) {
                      return '<input type="checkbox" class="form-check-input row-check" value="' + row.client_id + '"'
                          + (selected.has(row.client_id) ? ' checked' : '') + '>';
                  } },
                { data: 'row_number', orderable: false, searchable: false, render: function (data, type, row, meta) {
                    return meta.row + 1 + meta.settings._iDisplayStart;
                } },
                { data: 'client_id', render: function (data) { return data; } },
                { data: 'client_name' },
                { data: 'phone_number' },
                { data: 'field_name' },
                { data: 'category' },
                { data: 'assigned_at' }
            ]
        });

        table.on('draw.dt', function () {
            $('select[name="myTableActiveClients_length"]').addClass('form-select form-select-sm');
            syncPageBoxes();
        });

        // Row checkbox
        $table.on('change', '.row-check', function () {
            const id = parseInt(this.value, 10);
            this.checked ? selected.add(id) : selected.delete(id);
            refreshUi();
        });

        // Header checkbox = this page
        $(document).on('change', '#checkPage', function () {
            const on = this.checked;
            $table.find('tbody .row-check').each(function () {
                this.checked = on;
                const id = parseInt(this.value, 10);
                on ? selected.add(id) : selected.delete(id);
            });
            refreshUi();
        });

        $('#btnSelectPage').on('click', function () {
            $table.find('tbody .row-check').each(function () {
                this.checked = true;
                selected.add(parseInt(this.value, 10));
            });
            refreshUi();
        });

        // Fetch ids for EVERY row matching the current search / column filters (all pages).
        function tickAllMatching(overrides, $btn) {
            const params = Object.assign({}, table.ajax.params() || {}, { month: MONTH, state: filters.state, field_id: filters.field_id }, overrides || {});
            const label = $btn.text();
            $btn.prop('disabled', true).text('Loading...');

            $.getJSON(IDS_URL, params)
                .done(function (res) {
                    (res.ids || []).forEach(function (id) { selected.add(id); });
                    syncPageBoxes();
                })
                .fail(function () { alert('Could not load the matching clients. Please try again.'); })
                .always(function () { $btn.prop('disabled', false).text(label); });
        }

        $('#btnSelectMatching').on('click', function () { tickAllMatching({}, $(this)); });
        $('#btnSelectOutstanding').on('click', function () { tickAllMatching({ state: 'outstanding' }, $(this)); });

        $('#btnDefaults').on('click', function () {
            selected = new Set(DEFAULT_IDS);
            syncPageBoxes();
        });

        $('#btnClear').on('click', function () {
            selected.clear();
            syncPageBoxes();
        });

        // ---- Tiles / field cards / chips drive the server-side table ----
        const STATE_LABELS = { outstanding: 'Outstanding', categorized: 'Categorized' };

        function stateLabel(state) { return STATE_LABELS[state] || state; }

        function paintFilters() {
            $('.js-tile').each(function () {
                $(this).toggleClass('active', String($(this).data('state')) === filters.state);
            });
            $('.js-field-card').each(function () {
                $(this).toggleClass('active', filters.field_id !== '' && String($(this).data('field')) === filters.field_id);
            });
            $('.js-chip').each(function () {
                $(this).toggleClass('active',
                    filters.field_id !== '' && String($(this).data('field')) === filters.field_id &&
                    String($(this).data('state')) === filters.state);
            });
            $('#stateFilter').val(filters.state);

            const $bar = $('#activeFilters').empty();
            if (filters.field_id !== '' || filters.state !== '') {
                $bar.append('<span class="text-muted">Showing:</span>');
                if (filters.field_id !== '') {
                    $bar.append($('<span class="badge bg-label-dark">').text('Field: ' + (FIELD_NAMES[filters.field_id] || '')).append(' <a href="#" class="js-clear" data-what="field">&times;</a>'));
                }
                if (filters.state !== '') {
                    $bar.append($('<span class="badge bg-label-dark">').text('Category: ' + stateLabel(filters.state)).append(' <a href="#" class="js-clear" data-what="state">&times;</a>'));
                }
                $bar.append('<a href="#" class="js-clear" data-what="all">Clear filters</a>');
            }
        }

        function setFilters(next) {
            Object.assign(filters, next);
            paintFilters();
            table.ajax.reload(); // back to page 1 with the new filters
        }

        // Tile: filter by category (click again to clear)
        $('.js-tile').on('click', function () {
            const state = String($(this).data('state'));
            setFilters({ state: filters.state === state ? '' : state });
        });

        // Field card: filter by office (click again to clear); keeps any category filter
        $('.js-field-card').on('click', function () {
            const id = String($(this).data('field'));
            setFilters({ field_id: filters.field_id === id ? '' : id });
        });

        // Chip: office + category together
        $('.js-chip').on('click', function (e) {
            e.stopPropagation();
            const id = String($(this).data('field')), state = String($(this).data('state'));
            const same = filters.field_id === id && filters.state === state;
            setFilters(same ? { field_id: '', state: '' } : { field_id: id, state: state });
        });

        // "Tick N outstanding" on a card: tick every outstanding client of that office
        $('.js-tick-outstanding').on('click', function (e) {
            e.stopPropagation();
            tickAllMatching({ field_id: String($(this).data('field')), state: 'outstanding' }, $(this));
        });

        // Keyboard: Enter / Space on a focused tile or card
        $('.js-tile, .js-field-card').on('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); }
        });

        $(document).on('click', '.js-clear', function (e) {
            e.preventDefault();
            const what = $(this).data('what');
            setFilters(what === 'field' ? { field_id: '' } : what === 'state' ? { state: '' } : { field_id: '', state: '' });
        });

        $('#stateFilter').on('change', function () { setFilters({ state: $(this).val() }); });

        // The two category dropdowns (main panel + sticky bar) stay in step.
        $category.on('change', function () { $barCategory.val($(this).val()); refreshUi(); });
        $barCategory.on('change', function () { $category.val($(this).val()); refreshUi(); });

        $('#bulkForm').on('submit', function (e) {
            if (selected.size === 0) {
                e.preventDefault();
                alert('Tick at least one client.');
                return;
            }
            if (!$category.val()) {
                e.preventDefault();
                alert('Choose a category to apply.');
                return;
            }
            if (!confirm('Apply ' + $category.val() + ' to ' + selected.size + ' client(s) for ' + MONTH_LABEL + '?')) {
                e.preventDefault();
                return;
            }
            // One comma-separated field (not client[] x N) so large selections never hit PHP max_input_vars.
            $('#clientIds').val(Array.from(selected).join(','));
        });

        // ---- Sticky apply bar ----
        const bar = document.getElementById('stickyBar');

        function placeBar() {
            // line the fixed bar up with the content area (clear of the left menu)
            const page = document.querySelector('.layout-page');
            if (bar && page) {
                const r = page.getBoundingClientRect();
                bar.style.left = r.left + 'px';
                bar.style.right = 'auto';
                bar.style.width = r.width + 'px';
            }
        }

        function updateBar() {
            if (!bar || !CAN_APPLY) { return; }
            const show = selected.size > 0 && !panelVisible;
            bar.classList.toggle('d-none', !show);
            if (show) { placeBar(); }
        }

        if (bar && 'IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                panelVisible = entries[0].isIntersecting;
                updateBar();
            }).observe(document.getElementById('applyPanel'));
        } else {
            panelVisible = false; // no observer support: show the bar whenever something is ticked
        }
        window.addEventListener('resize', placeBar);

        $('#barApply').on('click', function () {
            const form = document.getElementById('bulkForm');
            form.requestSubmit ? form.requestSubmit() : $(form).trigger('submit');
        });
        $('#barClear').on('click', function () { $('#btnClear').trigger('click'); });

        // ---- Per-card apply: every OUTSTANDING client of one office -> chosen category ----
        $('.js-card-apply').on('click keydown', function (e) { e.stopPropagation(); }); // don't toggle the card filter
        $('.js-card-apply').on('change', function () {
            const $sel = $(this);
            const category = $sel.val();
            const msg = 'Put all ' + $sel.data('outstanding') + ' outstanding client(s) of ' + $sel.data('name')
                + ' into ' + category + ' for ' + MONTH_LABEL + '?\n\nClients already in a category are not touched.';

            if (!confirm(msg)) {
                $sel.prop('selectedIndex', 0);
                return;
            }
            $('#fieldApplyField').val($sel.data('field'));
            $('#fieldApplyCategory').val(category);
            $('#fieldApplyForm').trigger('submit');
        });

        paintFilters();
        refreshUi();
    });
    </script>

    
    <script>
        // Client drill-down panel.
        (function () {
            const URL_TMPL = @json(route('category.clientEmployees', ['client' => '__ID__']));
            const panelEl = document.getElementById('clientPanel');
            const money = new Intl.NumberFormat('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const gh = v => 'GH\u20B5 ' + money.format(v || 0);
            const esc = v => $('<div>').text(v == null ? '' : String(v)).html();
            const STATUS = { approved: 'success', pending: 'warning', hold: 'danger', rejected: 'dark', 'not generated': 'secondary' };
            let request = null;

            function render(r) {
                const t = r.totals, pay = r.can_view_salary;
                $('#clientPanelTitle').html(r.client.name);
                $('#clientPanelMeta').html(esc(r.month) + ' &middot; ' + (r.client.category ? esc($('<div>').html(r.client.category).text()) : 'No category')
                    + (r.client.field ? ' &middot; ' + r.client.field : ''));

                let html = '';
                if (r.mode === 'roster') {
                    html += '<div class="alert alert-secondary py-2 small">'
                        + (r.month_has_payroll ? 'No salary was generated for this client in ' + esc(r.month) + '.'
                                               : 'Payroll has not been generated for ' + esc(r.month) + '.')
                        + ' Showing today\'s active employees' + (pay ? ' with contract pay (basic + allowances).' : '.') + '</div>';
                }

                html += '<div class="d-flex flex-wrap gap-3 mb-3 small">'
                    + '<div><div class="text-muted">Staff</div><div class="fs-5 fw-bold">' + t.staff + '</div></div>'
                    + (pay ? '<div><div class="text-muted">' + (r.mode === 'payroll' ? 'Net salaries' : 'Contract pay') + '</div><div class="fs-5 fw-bold">' + gh(t.net) + '</div></div>' : '')
                    + '<div><div class="text-muted">Priority</div><div class="fs-5 fw-bold text-danger">' + t.priority_staff
                    + (pay ? ' <span class="fs-6">&middot; ' + gh(t.priority_net) + '</span>' : '') + '</div></div>'
                    + (t.held_staff ? '<div><div class="text-muted">On hold / rejected</div><div class="fs-5 fw-bold text-danger">' + t.held_staff
                        + (pay ? ' <span class="fs-6">&middot; ' + gh(t.held_net) + '</span>' : '') + '</div></div>' : '')
                    + '</div>';

                if (!r.rows.length) {
                    html += '<p class="text-muted">No active employees for this client.</p>';
                } else {
                    html += '<div class="table-responsive"><table class="table table-sm align-middle"><thead><tr>'
                        + '<th>ID</th><th>Name</th><th>Location</th>'
                        + (pay ? '<th class="text-end">' + (r.mode === 'payroll' ? 'Net' : 'Contract') + '</th>' : '')
                        + '<th>Status</th></tr></thead><tbody>';
                    r.rows.forEach(e => {
                        html += '<tr>'
                            + '<td class="text-nowrap">FWSS ' + e.employee_id + '</td>'
                            + '<td>' + e.badge + '<a href="' + e.employee_url + '" target="_blank" rel="noopener">' + e.name + '</a></td>'
                            + '<td>' + e.location + '</td>'
                            + (pay ? '<td class="text-end text-nowrap">' + gh(e.amount) + '</td>' : '')
                            + '<td><span class="badge bg-label-' + (STATUS[e.status] || 'secondary') + '">' + esc(e.status) + '</span></td>'
                            + '</tr>';
                    });
                    html += '</tbody></table></div>';
                }

                if (r.mode === 'payroll') {
                    html += '<a class="btn btn-sm btn-outline-primary" href="' + r.payroll_url + '">Open client payroll for ' + esc(r.month) + '</a>';
                }
                $('#clientPanelBody').html(html);
                panelEl.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => bootstrap.Tooltip.getOrCreateInstance(el));
            }

            $(document).on('click', '.js-client-open', function (e) {
                e.preventDefault();
                const id = $(this).data('id');
                $('#clientPanelTitle').text($(this).data('name'));
                $('#clientPanelMeta').text(MONTH_LABEL);
                $('#clientPanelBody').html('<div class="text-muted py-4 text-center">Loading employees…</div>');
                bootstrap.Offcanvas.getOrCreateInstance(panelEl).show();

                if (request) request.abort();
                request = $.getJSON(URL_TMPL.replace('__ID__', id), { month: MONTH })
                    .done(render)
                    .fail((x, s) => { if (s !== 'abort') $('#clientPanelBody').html('<div class="alert alert-danger">Could not load employees. Please try again.</div>'); });
            });

            document.querySelectorAll('.tile-pay [data-bs-toggle="tooltip"]').forEach(el => bootstrap.Tooltip.getOrCreateInstance(el));
        })();
    </script>
@endsection
</x-sales-dashboard>
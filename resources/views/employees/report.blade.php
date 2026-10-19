<x-sales-dashboard>

    @section('css')
    <style>
        .rep-kpi { border-radius: 10px; padding: 1rem 1.2rem; background:#fff; border:1px solid #e5e7eb; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; gap:.25rem; }
        .rep-kpi .value { font-size: 1.5rem; font-weight: 700; }
        .rep-kpi .pct { font-size: 1.25rem; font-weight: 800; color: #111827; }
        .rep-kpi .sub { font-size: .85rem; color: #6b7280; }
        .rep-card { border-radius: 12px; border: 1px solid #e5e7eb; }
        .rep-card .card-header { background: #f9fafb; border-bottom: 1px solid #e5e7eb; font-weight: 600; }
        .rep-kpi .label { font-size: .75rem; color: #6b7280; text-transform: uppercase; letter-spacing: .02em; }
        .rep-kpi .value.plus { color: #047857; }
        .rep-kpi .value.minus { color: #b91c1c; }
        .rep-kpi.total { background: #f3f4f6; }
        .rep-table td, .rep-table th { white-space: nowrap; font-size: .85rem; }
        .rep-table td.num, .rep-table th.num { text-align: right; font-variant-numeric: tabular-nums; }
        .rep-table tfoot td { font-weight: 700; background: #f9fafb; }
    </style>
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

        @if(Auth::user()->hasPermission('Accounts') || Auth::user()->hasPermission('Administration'))
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

                @if(Auth::user()->hasRole(['Finance Manager' ,'Manager', 'Admin Assistant']))
                <li class="menu-item ">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-money-withdraw bg-primary"></i>
                    <div class="text-truncate" data-i18n="Receipts">Receipts</div>
                    </a>
                    <ul class="menu-sub">

                        <li class="menu-item ">
                            <a href="{{url('receipt')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="RList">List</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="{{url('receiptPending')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="RPending">Pending </div>
                            </a>
                        </li>

                    </ul>
                </li>

                    <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
            <li class="menu-item ">
                <a class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-bxs-user-detail"></i>
                    <div class="text-truncate" data-i18n="Clients"><strong>Clients</strong></div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item ">
                        <a href="{{url('client/create')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="CRegister">New Contract</div>
                        </a>
                    </li>
                    <li class="menu-item ">
                        <a href="{{url('client')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="CList">List</div>
                        </a>
                    </li>
                    <li class="menu-item ">
                        <a href="{{url('clientTerminated')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="CList">Terminated</div>
                        </a>
                    </li>
                    <li class="menu-item ">
                        <a href="{{url('clientPending')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="CList">Pending</div>
                        </a>
                    </li>
                </ul>
            </li>
                    <li class="menu-item active open">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons bx bxs-user-account"></i>
                        <div class="text-truncate" data-i18n="Staffs">Employees</div>
                        </a>
                        <ul class="menu-sub">
                        <li class="menu-item ">
                            <a href="{{url('employees/create')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="SRegister">New Recruit</div>
                            </a>
                        </li>
                        <li class="menu-item ">
                            <a href="{{url('employees')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="SList">List</div>
                            </a>
                        </li>
                        <li class="menu-item ">
                            <a href="{{url('employeesPending')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="SList">Pending</div>
                            </a>
                        </li>
                        <li class="menu-item active">
                            <a href="{{url('employee-report')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="SList"> Report </div>
                            </a>
                        </li>
                        <li class="menu-item ">
                            <a href="{{ route('employees.movements') }}" class="menu-link">
                            <div class="text-truncate" data-i18n="SList">HR movements</div>
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
            <li class="menu-item active open ">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bxs-user-account"></i>
                <div class="text-truncate" data-i18n="Staffs">Employees</div>
                </a>
                <ul class="menu-sub">
                <li class="menu-item ">
                    <a href="{{url('employees/create')}}" class="menu-link">
                    <div class="text-truncate" data-i18n="SRegister">New Recruit</div>
                    </a>
                </li>
                <li class="menu-item ">
                    <a href="{{url('employees')}}" class="menu-link">
                    <div class="text-truncate" data-i18n="SList">List</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{url('employeesPending')}}" class="menu-link">
                    <div class="text-truncate" data-i18n="SList">Pending</div>
                    </a>
                </li>
                @if(Auth::user()->hasRole(['Manager', 'Invoice']))
                <li class="menu-item active">
                    <a href="{{url('employee-report')}}" class="menu-link">
                    <div class="text-truncate" data-i18n="SList"> Report </div>
                    </a>
                </li>

                <li class="menu-item ">
                    <a href="{{ route('employees.movements') }}" class="menu-link">
                    <div class="text-truncate" data-i18n="SList">HR movements</div>
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
                @endif
                </ul>
            </li>
        
        @if(Auth::user()->hasRole(['Manager', 'Invoice']))
            <li class="menu-item ">
                <a class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-bxs-user-detail"></i>
                    <div class="text-truncate" data-i18n="Clients"><strong>Clients</strong></div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item ">
                        <a href="{{url('client/create')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="CRegister">New Contract</div>
                        </a>
                    </li>
                    <li class="menu-item ">
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

            @endif

            @if((Auth::user()->hasRole(['Finance Manager']) && Auth::user()->hasPermission('Accounts')) )

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
    @php
        $r = $report;
        $t = $r['totals'];
        $bs = $r['by_status'];
        $signed = fn ($n) => ($n > 0 ? '+' : '') . number_format($n);
        $officeName = $field ? optional($offices->firstWhere('id', $field))->name : ($all ? 'all offices' : $offices->pluck('name')->implode(' + '));
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div>
                <h3 class="mb-1"><i class="bx bx-user"></i> Employees report · {{ $periodLabel }}</h3>
                <div class="text-muted small">{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}, {{ $officeName }}. Same figures as
                    <a href="{{ $movementsUrl }}">HR movements</a>: only approved movements count; active at start + recruits + re-instated ± transfers − terminated = active at end.</div>
            </div>
            <a href="{{ $movementsUrl }}" class="btn btn-sm btn-outline-primary"><i class="bx bx-list-ul"></i> See who (HR movements)</a>
        </div>

        <form method="GET" action="{{ url('employee-report') }}" class="row g-2 align-items-end mb-4" id="repFilter">
            <div class="col-auto">
                <label class="form-label small mb-0">Period</label>
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($periods as $val => $lbl)
                        <option value="{{ $val }}" @selected($period == $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Date in the period</label>
                <input type="date" name="date" value="{{ $anchor->format('Y-m-d') }}" class="form-control form-control-sm" onchange="this.form.submit()">
            </div>
            @if($offices->count() > 1)
            <div class="col-auto">
                <label class="form-label small mb-0">Office</label>
                <select name="field" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">{{ $all ? 'All offices' : 'All my offices' }}</option>
                    @foreach($offices as $o)
                        <option value="{{ $o->id }}" @selected($field === (int) $o->id)>{{ $o->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </form>

        {{-- The period, as it adds up --}}
        <div class="row g-3 mb-2" id="repKpis">
            <div class="col-lg col-6"><div class="rep-kpi total"><span class="label">Active at start</span><div class="value">{{ number_format($t['opening']) }}</div><span class="sub">end of {{ $from->copy()->subDay()->format('d M Y') }}</span></div></div>
            <div class="col-lg col-6"><div class="rep-kpi"><span class="label">New recruits</span><div class="value {{ $t['hires'] ? 'plus' : '' }}">{{ $t['hires'] ? '+' . number_format($t['hires']) : '0' }}</div></div></div>
            <div class="col-lg col-6"><div class="rep-kpi"><span class="label">Re-instated</span><div class="value {{ $t['reinstated'] ? 'plus' : '' }}">{{ $t['reinstated'] ? '+' . number_format($t['reinstated']) : '0' }}</div></div></div>
            @if($t['transfers_in'] || $t['transfers_out'])
            <div class="col-lg col-6"><div class="rep-kpi"><span class="label">Transfers in − out</span><div class="value">{{ $signed($t['transfers_in'] - $t['transfers_out']) }}</div></div></div>
            @endif
            <div class="col-lg col-6"><div class="rep-kpi"><span class="label">Terminated</span><div class="value {{ $t['terminated'] ? 'minus' : '' }}">{{ $t['terminated'] ? '−' . number_format($t['terminated']) : '0' }}</div></div></div>
            <div class="col-lg col-6"><div class="rep-kpi total"><span class="label">Active at end</span><div class="value">{{ number_format($t['closing']) }}</div>
                <span class="sub">end of {{ $to->format('d M Y') }}{{ $r['awaiting_termination'] ? ' · incl. ' . $r['awaiting_termination'] . ' awaiting termination approval' : '' }}</span></div></div>
        </div>
        <div class="d-flex flex-wrap gap-4 small mb-4">
            <span><strong>Net change:</strong> {{ $signed($t['net']) }}</span>
            <span><strong>Turnover:</strong> {{ $t['turnover'] === null ? '–' : $t['turnover'] . '%' }} <span class="text-muted">(terminated ÷ average active)</span></span>
            <span><strong>Early leavers:</strong> {{ $r['early_leavers'] }} <span class="text-muted">(within 90 days)</span></span>
            @if(array_sum($r['pending_counts']))<span class="text-warning"><strong>Waiting for approval:</strong> {{ array_sum($r['pending_counts']) }} (not counted)</span>@endif
            @if(! $t['reconciles'])<span class="text-danger"><strong>Does not add up</strong> – please report this.</span>@endif
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card rep-card h-100">
                    <div class="card-header">Active staff and movements, {{ count($trend) }} {{ strtolower($periods[$period]) === 'weekly' ? 'weeks' : 'periods' }} to {{ $periodLabel }}</div>
                    <div class="card-body">
                        <div class="small text-muted">Active at the end of each period</div>
                        <div id="rep-trend-active" style="min-height:170px"></div>
                        <div class="small text-muted mt-2">Recruits + re-instated (up) and terminated (down)</div>
                        <div id="rep-trend-moves" style="min-height:190px"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card rep-card h-100">
                    <div class="card-header">Employees on record today</div>
                    <div class="card-body small">
                        @foreach(['active' => 'Active', 'edit_pending' => 'Active, edit awaiting approval', 'recruit_pending' => 'New recruits awaiting approval',
                                  'termination_pending' => 'Termination awaiting approval', 'reinstatement_pending' => 'Re-instatement awaiting approval', 'terminated' => 'Terminated'] as $k => $lbl)
                            <div class="d-flex justify-content-between border-bottom py-1"><span>{{ $lbl }}</span><strong>{{ number_format($bs['total'][$k]) }}</strong></div>
                        @endforeach
                        <div class="d-flex justify-content-between py-1"><span>Total on record</span><strong>{{ number_format($bs['total']['total']) }}</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card rep-card mb-4">
            <div class="card-header">By office · {{ $periodLabel }}</div>
            <div class="table-responsive">
                <table class="table table-sm rep-table mb-0" id="repOffices">
                    <thead><tr><th>Office</th><th class="num">Active at start</th><th class="num">New recruits</th><th class="num">Re-instated</th><th class="num">Transfers in</th>
                        <th class="num">Terminated</th><th class="num">Transfers out</th><th class="num">Active at end</th><th class="num">Net</th><th class="num">Turnover</th></tr></thead>
                    <tbody>
                        @forelse($r['offices'] as $o)
                            <tr><td>{{ $o['name'] }}</td><td class="num">{{ $o['opening'] }}</td><td class="num">{{ $o['hires'] }}</td><td class="num">{{ $o['reinstated'] }}</td>
                                <td class="num">{{ $o['transfers_in'] }}</td><td class="num">{{ $o['terminated'] }}</td><td class="num">{{ $o['transfers_out'] }}</td>
                                <td class="num">{{ $o['closing'] }}</td><td class="num">{{ $signed($o['net']) }}</td><td class="num">{{ $o['turnover'] === null ? '–' : $o['turnover'] . '%' }}</td></tr>
                        @empty
                            <tr><td colspan="10" class="text-muted">No staff or movements.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr><td>Total</td><td class="num">{{ $t['opening'] }}</td><td class="num">{{ $t['hires'] }}</td><td class="num">{{ $t['reinstated'] }}</td><td class="num">{{ $t['transfers_in'] }}</td>
                        <td class="num">{{ $t['terminated'] }}</td><td class="num">{{ $t['transfers_out'] }}</td><td class="num">{{ $t['closing'] }}</td><td class="num">{{ $signed($t['net']) }}</td>
                        <td class="num">{{ $t['turnover'] === null ? '–' : $t['turnover'] . '%' }}</td></tr></tfoot>
                </table>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-4">
                <div class="card rep-card h-100">
                    <div class="card-header">Why staff left · {{ $periodLabel }}</div>
                    <div class="card-body"><div id="rep-reasons"></div></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card rep-card h-100">
                    <div class="card-header">How long leavers had worked</div>
                    <div class="card-body"><div id="rep-tenure"></div></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card rep-card h-100">
                    <div class="card-header">New recruits by role</div>
                    <div class="card-body"><div id="rep-roles"></div></div>
                </div>
            </div>
        </div>

        <div class="card rep-card mb-4">
            <div class="card-header">Employees on record today, by office</div>
            <div class="table-responsive">
                <table class="table table-sm rep-table mb-0">
                    <thead><tr><th>Office</th><th class="num">Active</th><th class="num">Edit awaiting approval</th><th class="num">Recruits awaiting approval</th>
                        <th class="num">Terminations awaiting approval</th><th class="num">Re-instatements awaiting approval</th><th class="num">Terminated</th><th class="num">Total</th></tr></thead>
                    <tbody>
                        @foreach($bs['offices'] as $o)
                            <tr><td>{{ $o['name'] }}</td><td class="num">{{ $o['active'] }}</td><td class="num">{{ $o['edit_pending'] }}</td><td class="num">{{ $o['recruit_pending'] }}</td>
                                <td class="num">{{ $o['termination_pending'] }}</td><td class="num">{{ $o['reinstatement_pending'] }}</td><td class="num">{{ $o['terminated'] }}</td><td class="num">{{ $o['total'] }}</td></tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr><td>Total</td><td class="num">{{ $bs['total']['active'] }}</td><td class="num">{{ $bs['total']['edit_pending'] }}</td><td class="num">{{ $bs['total']['recruit_pending'] }}</td>
                        <td class="num">{{ $bs['total']['termination_pending'] }}</td><td class="num">{{ $bs['total']['reinstatement_pending'] }}</td><td class="num">{{ $bs['total']['terminated'] }}</td><td class="num">{{ $bs['total']['total'] }}</td></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
    @endsection

    @section('scripts')
    <script src="{{asset('vendor/libs/apex-charts/apexcharts.js')}}"></script>
    <script>
    (function () {
        var trend = {!! json_encode(array_map(fn ($b) => ['label' => $b['label'], 'in' => $b['hires'] + $b['reinstated'], 'out' => $b['terminated'], 'active' => $b['closing']], $trend)) !!};
        var reasons = {!! json_encode($r['by_reason']) !!};
        var tenure = {!! json_encode($r['tenure']) !!};
        var roles = {!! json_encode($r['hires_by_role']) !!};
        var none = function (id, text) { var el = document.getElementById(id); if (el) { el.innerHTML = '<div class="text-muted small p-2">' + text + '</div>'; } };
        var has = function (obj) { return Object.keys(obj).some(function (k) { return obj[k] > 0; }); };
        if (typeof ApexCharts === 'undefined') { return; }

        // Two charts on the same periods: the level (active staff) and what moved it.
        var labels = trend.map(function (b) { return b.label; });
        var actives = trend.map(function (b) { return b.active; });
        var lo = Math.min.apply(null, actives), hi = Math.max.apply(null, actives), pad = Math.max(1, Math.ceil((hi - lo) * 0.15));
        new ApexCharts(document.querySelector('#rep-trend-active'), {
            chart: { type: 'line', height: 170, toolbar: { show: false }, zoom: { enabled: false } },
            series: [{ name: 'Active at end', data: actives }],
            xaxis: { categories: labels, labels: { show: false }, tooltip: { enabled: false } },
            yaxis: { min: Math.max(0, lo - pad), max: hi + pad, tickAmount: 2, decimalsInFloat: 0, forceNiceScale: true },
            colors: ['#1f2937'], stroke: { width: 2.5, curve: 'straight' }, markers: { size: 3 },
            dataLabels: { enabled: false }, grid: { strokeDashArray: 3 }, legend: { show: false }
        }).render();
        var ins = trend.map(function (b) { return b.in; }), outs = trend.map(function (b) { return -b.out; });
        var top = Math.max(1, Math.max.apply(null, ins), Math.max.apply(null, outs.map(Math.abs)));
        if (top > 5) { top = Math.ceil(top / 2) * 2; }          // whole-number ticks either way
        new ApexCharts(document.querySelector('#rep-trend-moves'), {
            chart: { type: 'bar', height: 190, stacked: true, toolbar: { show: false } },
            series: [{ name: 'Recruits + re-instated', data: ins }, { name: 'Terminated', data: outs }],
            xaxis: { categories: labels },
            yaxis: { min: -top, max: top, tickAmount: top <= 5 ? top * 2 : 4, decimalsInFloat: 0,
                labels: { formatter: function (v) { return Math.abs(Math.round(v)); } } },
            plotOptions: { bar: { columnWidth: '55%' } },
            colors: ['#047857', '#b91c1c'], dataLabels: { enabled: false }, grid: { strokeDashArray: 3 },
            tooltip: { shared: true, intersect: false, y: { formatter: function (v) { return Math.abs(v).toLocaleString(); } } },
            legend: { position: 'top', horizontalAlign: 'left' }
        }).render();

        var bar = function (id, data, color, empty) {
            if (!has(data)) { none(id, empty); return; }
            var keys = Object.keys(data);
            new ApexCharts(document.querySelector('#' + id), {
                chart: { type: 'bar', height: Math.max(160, 34 * keys.length), toolbar: { show: false } },
                plotOptions: { bar: { horizontal: true, barHeight: '60%' } },
                series: [{ name: 'Staff', data: keys.map(function (k) { return data[k]; }) }],
                xaxis: { categories: keys, labels: { show: false }, axisTicks: { show: false } },
                yaxis: { labels: { maxWidth: 170 } },
                grid: { xaxis: { lines: { show: false } } },
                colors: [color], dataLabels: { enabled: true }, tooltip: { enabled: false }
            }).render();
        };
        bar('rep-reasons', reasons, '#b91c1c', 'No terminations in this period.');
        bar('rep-tenure', tenure, '#6b7280', 'No terminations in this period.');
        bar('rep-roles', roles, '#047857', 'No new recruits in this period.');
    })();
    </script>
    @endsection

</x-sales-dashboard>

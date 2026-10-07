<x-sales-dashboard>

  @php
      $user = Auth::user();
      $canViewInvoices = $user?->hasRole(['Invoice','Finance Manager', 'Director']) ?? false;
      $canViewReceipts = $user?->hasRole(['Finance Manager', 'Manager','Admin Assistant']) ?? false;
      $canManageFinance = $user?->hasRole(['Finance Manager']) ?? false;
      $canManageOperations = $user?->hasRole(['Invoice' ,'Director', 'Manager', 'Admin Assistant']) ?? false;
      $canViewAccounts = $user?->hasRole(['Finance Manager', 'Director']) ?? false;
      $canViewPayroll = ($user?->hasPermission('Accounts') ?? false) && ($user?->hasRole(['Invoice', 'Officer', 'Director', 'Finance Manager']) ?? false);
  @endphp

    @section('css')
    <style>
        .rep-kpi { border-radius: 10px; padding: 1rem 1.2rem; background:#fff; border:1px solid #e5e7eb; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; gap:.25rem; }
        .rep-kpi .value { font-size: 1.5rem; font-weight: 700; }
        .rep-kpi .pct { font-size: 1.25rem; font-weight: 800; color: #111827; }
        .rep-kpi .sub { font-size: .85rem; color: #6b7280; }
        .rep-card { border-radius: 12px; border: 1px solid #e5e7eb; }
        .rep-card .card-header { background: #f9fafb; border-bottom: 1px solid #e5e7eb; font-weight: 600; }
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
      <li class="menu-item ">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-home-smile text-primary me-2"></i>
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
      <!-- Apps & Pages -->
      <li class="menu-header small text-uppercase ">
        <span class="menu-header-text text-primary">Transactions</span>
      </li>
      <!-- Pages -->
      @if($canViewInvoices)
      <li class="menu-item ">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
           <i class="menu-icon tf-icons bx bx-receipt text-primary me-2"></i>
                <div class="text-truncate" data-i18n="Invoices">Invoices</div>
          </a>
          <ul class="menu-sub"> 
              <li class="menu-item">
                  <a href="{{url('invoice')}}" class="menu-link">
                  <div class="text-truncate" data-i18n="SList">Invoices</div>
                  </a>
              </li>

              <li class="menu-item">
                  <a href="{{url('invoice-report')}}" class="menu-link">
                  <div class="text-truncate" data-i18n="SList">Reports</div>
                  </a>
              </li>
          </ul>
      </li>
      <li class="menu-item ">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-receipt text-primary me-2"></i>
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


      @if($canViewReceipts)
      <li class="menu-item active open">
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
            <li class="menu-item active">
                <a href="{{url('receipt-report')}}" class="menu-link">
                <div class="text-truncate" data-i18n="RPending">Reports </div>
                </a>
            </li>

          </ul>
      </li>
       @endif

       @if($canManageFinance)
      <!-- Components -->
      <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
      <li class="menu-item">
        <a href="{{url('client')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bxs-user-detail text-info me-2"></i>
          <div class="text-truncate" data-i18n="Clients">Clients</div>
        </a>
      </li>
      <li class="menu-item ">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-user-circle text-info me-2"></i>
          <div class="text-truncate" data-i18n="Staffs">Employees</div>
          </a>
          <ul class="menu-sub">
          <li class="menu-item ">
              <a href="{{url('employees/create')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SRegister">New Recruit</div>
              </a>
          </li>
          <li class="menu-item">
              <a href="{{url('employees')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">List</div>
              </a>
          </li>
          <li class="menu-item">
              <a href="{{url('employee-report')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">Reports</div>
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

      <li class="menu-item">
        <a href="{{url('category')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-category text-info me-2"></i>
          <div class="text-truncate" data-i18n="Categories">Categories</div>
        </a>
      </li>
      @elseif($canManageOperations)

      <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
        <li class="menu-item ">
            <a class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bxs-user-detail text-info me-2"></i>
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
        <li class="menu-item ">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-category text-info me-2"></i>
          <div class="text-truncate" data-i18n="Staffs">Employees</div>
          </a>
          <ul class="menu-sub">
          <li class="menu-item ">
              <a href="{{url('employees/create')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SRegister">New Recruit</div>
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
      @endif

      @if($canViewAccounts)

      <li class="menu-header small text-uppercase"> <span class="menu-header-text text-danger">Accounts</span></li>

      <li class="menu-item">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-chart text-danger me-2"></i>
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
              <i class="menu-icon tf-icons bx bx-bank text-danger me-2"></i>
              <div class="text-truncate" data-i18n="AList">Banks</div>
            </a>
          </li>
        </ul>
      </li>
      <li class="menu-item">
        <a href="{{url('expense')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-credit-card text-secondary me-2"></i>
          <div class="text-truncate" data-i18n="Expense"> Expense </div>
        </a>
       </li>

      <li class="menu-item">
          <a href="javascript:void(0);" class="menu-link menu-toggle"><i class="menu-icon tf-icons bx bx-time-five bg-danger"></i><div>Overtime</div></a>
          <ul class="menu-sub">
              <li class="menu-item"><a href="{{url('overtime')}}" class="menu-link"><div>Daily Entry</div></a></li>
              <li class="menu-item"><a href="{{url('overtime-report')}}" class="menu-link"><div>Reports</div></a></li>
          </ul>
      </li>
      @endif

      @if($canViewPayroll)
      <li class="menu-header small text-uppercase"><span class="menu-header-text">PAYROLL</span></li>
        <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-money-withdraw text-primary me-2"></i>
                <div class="text-truncate" data-i18n="Payroll">Payroll</div>
                </a>
                <ul class="menu-sub">
                @if(Auth::user()->hasRole([ 'Finance Manager']))

                <li class="menu-item">
                    <a href="{{ url('salaries') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-user-circle text-info me-2"></i>
                    <div class="text-truncate" data-i18n="Employees">Add to Salaries</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="{{ url('salaries/create') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-money-withdraw text-primary me-2"></i>
                    <div class="text-truncate" data-i18n="Salaries">Salaries</div>
                    </a>
                </li>
                @endif


                <li class="menu-item">
                    <a href="{{ url('salariesTransaction') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-transfer-alt text-primary me-2"></i>
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
        $ghs = fn ($v) => 'GH₵ ' . number_format((float) $v, 2);
        // Change vs the previous window; receipts going UP is good (green).
        $delta = function ($cur, $prev) {
            $cur = (float) $cur; $prev = (float) $prev;
            if ($prev == 0.0) {
                return $cur > 0 ? '<span class="text-muted">nothing in previous period</span>' : '';
            }
            $c = ($cur - $prev) / $prev * 100;
            $cls = abs($c) < 0.05 ? 'text-muted' : ($c > 0 ? 'text-success' : 'text-danger');
            return '<span class="' . $cls . '">' . ($c > 0 ? '+' : '') . number_format($c, 1) . '% vs previous</span>';
        };
        $t = $totals;
        $methods = ['Cash' => $t->cash, 'MoMo' => $t->momo, 'Cheque' => $t->cheque, 'Transfer' => $t->transfer, 'Other' => $t->other];
        $periodWord = $period === 'custom' ? $report->days . '-day period' : strtolower(\App\Support\ReceiptReport::PERIODS[$period]) . ' period';
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row mb-3">
            <div class="col-12"><h3 class="mb-0"><i class="bx bx-bar-chart-alt-2"></i> Receipts Reports</h3></div>
        </div>

        {{-- Window: a named period around a date, or any range between two dates --}}
        <form method="GET" action="{{ url('receipt-report') }}" class="row g-2 align-items-end mb-2" id="rcpt-window">
            <div class="col-auto">
                <label class="form-label small mb-0" for="rcpt-period">Period</label>
                <select name="period" id="rcpt-period" class="form-select form-select-sm">
                    @foreach(\App\Support\ReceiptReport::PERIODS as $val => $label)
                        <option value="{{ $val }}" @selected($period === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto rcpt-named" @if($period === 'custom') hidden @endif>
                <label class="form-label small mb-0" for="rcpt-date">Date in period</label>
                <input type="date" name="date" id="rcpt-date" value="{{ $anchor->format('Y-m-d') }}" class="form-control form-control-sm" @disabled($period === 'custom')>
            </div>
            <div class="col-auto rcpt-range" @if($period !== 'custom') hidden @endif>
                <label class="form-label small mb-0" for="rcpt-from">From</label>
                <input type="date" name="from" id="rcpt-from" value="{{ $from->format('Y-m-d') }}" class="form-control form-control-sm" @disabled($period !== 'custom')>
            </div>
            <div class="col-auto rcpt-range" @if($period !== 'custom') hidden @endif>
                <label class="form-label small mb-0" for="rcpt-to">To</label>
                <input type="date" name="to" id="rcpt-to" value="{{ $to->format('Y-m-d') }}" class="form-control form-control-sm" @disabled($period !== 'custom')>
            </div>
            <div class="col-auto rcpt-range" @if($period !== 'custom') hidden @endif>
                <button type="submit" class="btn btn-sm btn-dark">Show</button>
            </div>
            <div class="col-auto rcpt-range d-flex flex-wrap gap-1 pb-1" @if($period !== 'custom') hidden @endif>
                @foreach(['last7' => 'Last 7 days', 'last30' => 'Last 30 days', 'month' => 'This month', 'lastmonth' => 'Last month', 'quarter' => 'This quarter', 'year' => 'This year'] as $key => $label)
                    <button type="button" class="btn btn-xs btn-sm btn-outline-secondary py-0 js-rcpt-preset" data-preset="{{ $key }}">{{ $label }}</button>
                @endforeach
            </div>
        </form>
        <div class="text-muted small mb-4">
            Showing <strong>{{ $report->label() }}</strong> ({{ $report->days }} {{ \Illuminate\Support\Str::plural('day', $report->days) }}),
            compared with {{ $previous->label() }}.
            @if($period === 'custom' && $report->days >= \App\Support\ReceiptReport::MAX_RANGE_DAYS)
                <span class="text-warning">Ranges are limited to {{ \App\Support\ReceiptReport::MAX_RANGE_DAYS }} days.</span>
            @endif
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-3 col-6"><div class="rep-kpi h-100"><small class="text-muted">MONEY RECEIVED</small><div class="value">{{ $ghs($t->received) }}</div><div class="sub">{!! $delta($t->received, $prevTotals->received) !!}</div></div></div>
            <div class="col-lg-3 col-6"><div class="rep-kpi h-100"><small class="text-muted">RECEIPTS</small><div class="value">{{ number_format($t->cnt) }}</div><div class="sub">{{ number_format($t->clients) }} {{ \Illuminate\Support\Str::plural('client', (int) $t->clients) }} &middot; {!! $delta($t->cnt, $prevTotals->cnt) !!}</div></div></div>
            <div class="col-lg-3 col-6"><div class="rep-kpi h-100"><small class="text-muted">AVG PER RECEIPT</small><div class="value">{{ $ghs($t->avg) }}</div><div class="sub">{!! $delta($t->avg, $prevTotals->avg) !!}</div></div></div>
            <div class="col-lg-3 col-6"><div class="rep-kpi h-100"><small class="text-muted">PROJECTED NEXT {{ strtoupper($periodWord) }}</small><div class="value text-primary">{{ $ghs($projection['value']) }}</div>
                <div class="sub">average of the previous 4 {{ $periodWord }}s</div>
            </div></div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-6"><div class="rep-kpi h-100"><small class="text-muted">TAX WITHHELD BY CLIENTS</small><div class="value">{{ $ghs($t->wht + $t->vat) }}</div><div class="sub">WHT {{ $ghs($t->wht) }} &middot; VAT {{ $ghs($t->vat) }}</div></div></div>
            <div class="col-lg-3 col-6"><div class="rep-kpi h-100"><small class="text-muted">DEDUCTIONS ALLOWED</small><div class="value">{{ $ghs($t->deductions) }}</div></div></div>
            <div class="col-lg-3 col-6"><div class="rep-kpi h-100"><small class="text-muted">CLEARED OFF INVOICES</small><div class="value">{{ $ghs($t->settled) }}</div><div class="sub">received + tax withheld + deductions</div></div></div>
            <div class="col-lg-3 col-6"><div class="rep-kpi h-100 @if($pending->cnt) border-warning @endif"><small class="text-muted">AWAITING HO APPROVAL</small><div class="value {{ $pending->cnt ? 'text-warning' : '' }}">{{ $ghs($pending->received) }}</div><div class="sub">{{ $pending->cnt }} {{ \Illuminate\Support\Str::plural('receipt', $pending->cnt) }}, not in the figures above</div></div></div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card rep-card h-100">
                    <div class="card-header">Receipts trend <span class="text-muted fw-normal small">by {{ $trend['bucket'] }}</span></div>
                    <div class="card-body"><div id="rcpt-trend-chart"></div></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card rep-card h-100">
                    <div class="card-header">By payment method</div>
                    <div class="card-body">
                        <div id="rcpt-method-chart" style="min-height:260px"></div>
                        <table class="table table-sm mb-3">
                            @foreach($methods as $label => $amount)
                                <tr><td>{{ $label }}</td><td class="text-end">{{ $ghs($amount) }}</td><td class="text-end text-muted small">{{ $t->received > 0 ? number_format($amount / $t->received * 100, 1) : '0.0' }}%</td></tr>
                            @endforeach
                            <tr class="fw-semibold"><td>Total</td><td class="text-end">{{ $ghs($t->received) }}</td><td></td></tr>
                        </table>
                        <div class="row text-center g-2">
                            <div class="col-6"><div class="rep-kpi"><div class="small text-muted">Full payments</div><div class="pct">{{ number_format($t->full_cnt) }}</div><div class="sub">{{ $ghs($t->full_amount) }}</div></div></div>
                            <div class="col-6"><div class="rep-kpi"><div class="small text-muted">Part payments</div><div class="pct">{{ number_format($t->part_cnt) }}</div><div class="sub">{{ $ghs($t->part_amount) }}</div></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card rep-card h-100">
                    <div class="card-header">By field office</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Office</th><th class="text-end">Receipts</th><th class="text-end">Received</th><th class="text-end">Share</th></tr></thead>
                            <tbody>
                                @forelse($byField as $row)
                                <tr @if($row->field_id) class="rcpt-drill-row" data-type="field" data-field-id="{{ $row->field_id }}" @endif>
                                    <td>{{ $row->field_name }}</td><td class="text-end">{{ $row->cnt }}</td><td class="text-end">{{ $ghs($row->total) }}</td>
                                    <td class="text-end text-muted">{{ $t->received > 0 ? number_format($row->total / $t->received * 100, 1) : '0.0' }}%</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-muted text-center py-3">No receipts in this period.</td></tr>
                                @endforelse
                            </tbody>
                            @if($byField->isNotEmpty())
                            <tfoot><tr class="fw-semibold"><td>Total</td><td class="text-end">{{ $byField->sum('cnt') }}</td><td class="text-end">{{ $ghs($byField->sum('total')) }}</td><td></td></tr></tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card rep-card h-100">
                    <div class="card-header">Top 10 clients <span class="text-muted fw-normal small">by money received</span></div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Client</th><th class="text-end">Receipts</th><th class="text-end">Received</th></tr></thead>
                            <tbody>
                                @forelse($topClients as $row)
                                <tr class="rcpt-drill-row" data-type="client" data-client-id="{{ $row->id }}"><td>{{ $row->business_name ?: $row->name }}</td><td class="text-end">{{ $row->cnt }}</td><td class="text-end">{{ $ghs($row->total) }}</td></tr>
                                @empty
                                <tr><td colspan="3" class="text-muted text-center py-3">No receipts in this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card rep-card h-100">
                    <div class="card-header">Top 10 collectors <span class="text-muted fw-normal small">by money received</span></div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Collector</th><th class="text-end">Receipts</th><th class="text-end">Received</th></tr></thead>
                            <tbody>
                                @forelse($topCollectors as $row)
                                <tr @if($row->id) class="rcpt-drill-row" data-type="collector" data-collector-id="{{ $row->id }}" @endif><td>{{ $row->name }}</td><td class="text-end">{{ $row->cnt }}</td><td class="text-end">{{ $ghs($row->total) }}</td></tr>
                                @empty
                                <tr><td colspan="3" class="text-muted text-center py-3">No receipts in this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="alert alert-info small mb-0">
                    <i class="bx bx-info-circle"></i>
                    Only receipts approved by head office are counted. <strong>Money received</strong> is cash + MoMo + cheque + transfer + other,
                    as entered on each receipt. Receipts are dated by their receipt date
                    @if($t->undated)
                        ; <strong>{{ (int) $t->undated }}</strong> {{ (int) $t->undated === 1 ? 'receipt has' : 'receipts have' }} no receipt date and {{ (int) $t->undated === 1 ? 'is' : 'are' }} dated by when {{ (int) $t->undated === 1 ? 'it was' : 'they were' }} entered.
                    @else
                        .
                    @endif
                    Click a row for its breakdown.
                </div>
            </div>
        </div>

    </div>
    @endsection

    @section('scripts')
    <script src="{{asset('vendor/libs/apex-charts/apexcharts.js')}}"></script>
    <script>
        const trend = @json($trend);
        const reportPeriod = @json($period);
        const reportQuery = @json($report->query());
        const methodLabels = ['Cash', 'MoMo', 'Cheque', 'Transfer', 'Other'];
        const methodValues = @json(array_map('floatval', array_values($methods)));
        const fmtMoney = v => Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        function renderReceiptCharts() {
            new ApexCharts(document.querySelector('#rcpt-trend-chart'), {
                chart: { type: trend.labels.length > 1 ? 'area' : 'bar', height: 300, toolbar: { show: false } },
                series: [{ name: 'Received (GH₵)', data: trend.values }],
                xaxis: { categories: trend.labels, labels: { rotate: -45, hideOverlappingLabels: true } },
                yaxis: { labels: { formatter: v => Intl.NumberFormat(undefined, { notation: 'compact' }).format(v) } },
                dataLabels: { enabled: false },
                colors: ['#0ea5a4'],
                stroke: { curve: 'smooth', width: 2 },
                tooltip: { y: { formatter: (v, o) => 'GH₵ ' + fmtMoney(v) + ' · ' + trend.counts[o.dataPointIndex] + ' receipt(s)' } },
            }).render();

            if (methodValues.some(v => v > 0)) {
                new ApexCharts(document.querySelector('#rcpt-method-chart'), {
                    chart: { type: 'donut', height: 260 },
                    series: methodValues,
                    labels: methodLabels,
                    colors: ['#10b981', '#06b6d4', '#f59e0b', '#6366f1', '#94a3b8'],
                    legend: { position: 'bottom' },
                    tooltip: { y: { formatter: v => 'GH₵ ' + fmtMoney(v) } },
                }).render();
            } else {
                document.querySelector('#rcpt-method-chart').innerHTML = '<div class="text-muted p-4">No receipts in this period.</div>';
            }
        }
        if (window.ApexCharts) { renderReceiptCharts(); }
        else { // vendor asset missing: fall back to the CDN
            const s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.54.1/apexcharts.min.js';
            s.onload = renderReceiptCharts;
            document.head.appendChild(s);
        }

        // Period selector: named periods submit at once; "Date range" shows From / To.
        (function () {
            const form = document.getElementById('rcpt-window');
            const sel = document.getElementById('rcpt-period');
            const fromEl = document.getElementById('rcpt-from'), toEl = document.getElementById('rcpt-to');
            const pad = n => String(n).padStart(2, '0');
            const ymd = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); // local date, never UTC

            function mode(custom) {
                form.querySelectorAll('.rcpt-range').forEach(el => { el.hidden = !custom; });
                form.querySelectorAll('.rcpt-named').forEach(el => { el.hidden = custom; });
                fromEl.disabled = toEl.disabled = !custom;
                document.getElementById('rcpt-date').disabled = custom;
            }
            sel.addEventListener('change', function () {
                const custom = sel.value === 'custom';
                mode(custom);
                if (!custom) form.submit(); // custom waits for From / To + Show
            });
            document.getElementById('rcpt-date').addEventListener('change', () => form.submit());
            toEl.addEventListener('change', () => { if (fromEl.value && toEl.value) form.submit(); });

            const PRESETS = {
                last7:     n => [new Date(n.getFullYear(), n.getMonth(), n.getDate() - 6), n],
                last30:    n => [new Date(n.getFullYear(), n.getMonth(), n.getDate() - 29), n],
                month:     n => [new Date(n.getFullYear(), n.getMonth(), 1), new Date(n.getFullYear(), n.getMonth() + 1, 0)],
                lastmonth: n => [new Date(n.getFullYear(), n.getMonth() - 1, 1), new Date(n.getFullYear(), n.getMonth(), 0)],
                quarter:   n => { const q = Math.floor(n.getMonth() / 3) * 3; return [new Date(n.getFullYear(), q, 1), new Date(n.getFullYear(), q + 3, 0)]; },
                year:      n => [new Date(n.getFullYear(), 0, 1), new Date(n.getFullYear(), 11, 31)],
            };
            form.querySelectorAll('.js-rcpt-preset').forEach(btn => btn.addEventListener('click', function () {
                const [a, b] = PRESETS[btn.dataset.preset](new Date());
                fromEl.value = ymd(a); toEl.value = ymd(b);
                form.submit();
            }));
        })();

        // (status donut removed) status summary is shown as KPI cards above

        // Drilldown modal: click a row in "By Field Office", "Top 10 Clients"
        // or "Top 10 Collectors" to fetch a grouped breakdown for that
        // entity, scoped to the same period/anchor as the report above.
        document.querySelectorAll('.rcpt-drill-row').forEach(r => r.style.cursor = 'pointer');
        const rcptModalEl = document.createElement('div');
        rcptModalEl.innerHTML = `
        <div class="modal fade" id="rcpt-details-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rcpt-details-title">Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="rcpt-details-loading" class="text-center py-3">Loading…</div>
                        <div id="rcpt-details-body" class="d-none">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                <div class="small text-muted" id="rcpt-details-summary"></div>
                                <button type="button" class="btn btn-success btn-sm" id="rcpt-details-export" disabled>
                                    <i class="bx bx-spreadsheet me-1"></i> Export Excel
                                </button>
                            </div>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-hover mb-0" id="rcpt-details-table">
                                    <thead class="table-light"><tr id="rcpt-details-table-head"></tr></thead>
                                    <tbody id="rcpt-details-rows"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
        document.body.appendChild(rcptModalEl);
        let rcptExportName = 'Details';

        document.getElementById('rcpt-details-export').addEventListener('click', function () {
            const table = document.getElementById('rcpt-details-table');
            const workbook = '<html><head><meta charset="utf-8"></head><body><h3>'
                + rcptExportName.replace(/[&<>"']/g, '') + '</h3>'
                + table.outerHTML + '</body></html>';
            const file = new Blob(['\ufeff', workbook], { type: 'application/vnd.ms-excel' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(file);
            link.download = rcptExportName.replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '') + '-receipts.xls';
            link.click();
            URL.revokeObjectURL(link.href);
        });

        function renderRcptModalTable(headers, rows) {
            const head = document.getElementById('rcpt-details-table-head');
            const body = document.getElementById('rcpt-details-rows');
            head.innerHTML = '';
            headers.forEach((header, index) => {
                const cell = document.createElement('th');
                cell.textContent = header;
                if (index > 0) cell.className = 'text-end';
                head.appendChild(cell);
            });
            body.innerHTML = '';
            if (!rows.length) {
                body.innerHTML = `<tr><td colspan="${headers.length}" class="text-center text-muted py-3">No receipts in this period.</td></tr>`;
                return false;
            }
            rows.forEach(values => {
                const row = document.createElement('tr');
                values.forEach((value, index) => {
                    const cell = document.createElement('td');
                    cell.textContent = value;
                    if (index > 0) cell.className = 'text-end';
                    row.appendChild(cell);
                });
                body.appendChild(row);
            });
            return true;
        }

        function showRcptDetails(type, key, displayName) {
            const title = type === 'field' ? 'Field Office Details'
                : type === 'client' ? 'Client Details'
                : type === 'collector' ? 'Collector Details'
                : 'Details';
            const titleNode = document.getElementById('rcpt-details-title');
            const loadingNode = document.getElementById('rcpt-details-loading');
            const bodyNode = document.getElementById('rcpt-details-body');
            const summaryNode = document.getElementById('rcpt-details-summary');
            const exportNode = document.getElementById('rcpt-details-export');

            titleNode.textContent = title + (displayName ? ' — ' + displayName : '');
            loadingNode.style.display = '';
            bodyNode.classList.add('d-none');
            summaryNode.textContent = '';
            exportNode.disabled = true;
            rcptExportName = displayName || title;

            let url;
            if (type === 'field') url = `/receipt/field/${encodeURIComponent(key)}/details`;
            else if (type === 'client') url = `/receipt/client/${encodeURIComponent(key)}/details`;
            else if (type === 'collector') url = `/receipt/collector/${encodeURIComponent(key)}/details`;
            else return;

            url += '?' + new URLSearchParams(reportQuery).toString(); // same window as the report (period+date, or from+to)

            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    loadingNode.style.display = 'none';
                    bodyNode.classList.remove('d-none');

                    if (type === 'field') {
                        const clients = Array.isArray(data.clients) ? data.clients : [];
                        const total = clients.reduce((sum, c) => sum + (Number(c.total) || 0), 0);
                        summaryNode.textContent = `${clients.length} client${clients.length === 1 ? '' : 's'} · GH₵ ${total.toFixed(2)} total`;
                        exportNode.disabled = !renderRcptModalTable(['Client', 'Receipts', 'Total (GH₵)'], clients.map(c => [c.business_name || c.name || '–', Number(c.entries) || 0, (Number(c.total) || 0).toFixed(2)]));
                    } else if (type === 'client') {
                        const collectors = Array.isArray(data.collectors) ? data.collectors : [];
                        const total = collectors.reduce((sum, c) => sum + (Number(c.total) || 0), 0);
                        summaryNode.textContent = `${collectors.length} collector${collectors.length === 1 ? '' : 's'} · GH₵ ${total.toFixed(2)} total`;
                        exportNode.disabled = !renderRcptModalTable(['Collector', 'Receipts', 'Total (GH₵)'], collectors.map(c => [c.name || '–', Number(c.entries) || 0, (Number(c.total) || 0).toFixed(2)]));
                    } else if (type === 'collector') {
                        const clients = Array.isArray(data.clients) ? data.clients : [];
                        const total = clients.reduce((sum, c) => sum + (Number(c.total) || 0), 0);
                        summaryNode.textContent = `${clients.length} client${clients.length === 1 ? '' : 's'} · GH₵ ${total.toFixed(2)} total`;
                        exportNode.disabled = !renderRcptModalTable(['Client', 'Receipts', 'Total (GH₵)'], clients.map(c => [c.business_name || c.name || '–', Number(c.entries) || 0, (Number(c.total) || 0).toFixed(2)]));
                    }
                })
                .catch(() => {
                    loadingNode.style.display = 'none';
                    bodyNode.classList.remove('d-none');
                    summaryNode.textContent = 'Unable to load drill-down data.';
                    renderRcptModalTable(['Details'], []);
                });

            if (window.bootstrap && typeof bootstrap.Modal === 'function') {
                const m = new bootstrap.Modal(document.getElementById('rcpt-details-modal'));
                m.show();
            } else {
                const el = document.getElementById('rcpt-details-modal');
                el.classList.add('show');
                el.style.display = 'block';
            }
        }

        document.addEventListener('click', function (ev) {
            const row = ev.target.closest('.rcpt-drill-row');
            if (!row) return;
            const type = row.dataset.type;
            let key;
            if (type === 'field') key = row.dataset.fieldId;
            else if (type === 'client') key = row.dataset.clientId;
            else if (type === 'collector') key = row.dataset.collectorId;
            else return;
            const displayName = row.querySelector('td') ? row.querySelector('td').textContent.trim() : null;
            showRcptDetails(type, key, displayName);
        });
    </script>
    @endsection

</x-sales-dashboard>
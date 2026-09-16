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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .ot-card { border-radius: 12px; border: 1px solid #e5e7eb; }
        .ot-card .card-header { background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
        .shift-total { font-size: 1.4rem; font-weight: 700; }
        .ot-kpi { border-radius: 10px; padding: .9rem 1.1rem; color: #fff; }
        .ot-kpi small { opacity: .85; }
        .ot-kpi .value { font-size: 1.35rem; font-weight: 700; }
        .quick-row td { vertical-align: middle; padding: .35rem; }
        .quick-row .form-control, .quick-row .select2-container { min-width: 130px; }
        table.ot-table td, table.ot-table th { white-space: nowrap; font-size: .85rem; }
        .badge-shift-day { background: #fef3c7; color: #92400e; }
        .badge-shift-night { background: #1f2937; color: #fff; }
        .stage-dot { width: 9px; height: 9px; border-radius: 50%; display:inline-block; }
        .stage-dot.approved { background:#16a34a; } .stage-dot.rejected{background:#dc2626;}
        .stage-dot.pending{background:#f59e0b;} .stage-dot.waiting{background:#d1d5db;}
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
        <li class="menu-item">
            <a href="{{url('transaction')}}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-transfer-alt bg-primary"></i>
            <div class="text-truncate" data-i18n="Transaction">Transactions</div>
            </a>
        </li>

        @if($canViewInvoices)
        <li class="menu-item">
            <a href="{{ url('invoice') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-receipt text-primary me-2"></i>
            <div class="text-truncate" data-i18n="Invoices">Invoices</div>
            </a>
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

        <li class="menu-item ">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-money-withdraw bg-primary"></i>
            <div class="text-truncate" data-i18n="Receipts">Receipts</div>
            </a>
            <ul class="menu-sub">

                <li class="menu-item">
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
        @endif

        @if($canManageFinance)
        <!-- Components -->
        <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
        <li class="menu-item">
            <a href="{{url('client')}}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-user-detail text-info me-2"></i>
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
                    <i class="menu-icon tf-icons bx bx-user-detail text-info me-2"></i>
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

        <li class="menu-item active open">
            <a href="javascript:void(0);" class="menu-link menu-toggle"><i class="menu-icon tf-icons bx bx-time-five bg-danger"></i><div>Overtime</div></a>
            <ul class="menu-sub">
                <li class="menu-item active"><a href="{{url('overtime')}}" class="menu-link"><div>Daily Entry</div></a></li>
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
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h3 class="mb-0"><i class="bx bx-time-five"></i> Overtime — Daily Entry</h3>
                <a href="{{ url('overtime-report') }}" class="btn btn-outline-dark btn-sm"><i class="bx bx-bar-chart-alt-2"></i> View Reports</a>
            </div>
        </div>

        <div class="card-header ml-2 d-none d-lg-block">
            @include('flash-messages')
        </div>

        <form method="GET" action="{{ url('overtime') }}" class="row g-2 align-items-end mb-4">
            <div class="col-auto">
                <label class="form-label small mb-0">Date</label>
                <input type="date" name="date" value="{{ $date->format('Y-m-d') }}" class="form-control form-control-sm" max="{{ now()->format('Y-m-d') }}" onchange="this.form.submit()">
            </div>
            @if($fields->count() > 1 || Auth::user()->hasRole(['Finance Manager','Director']))
            <div class="col-auto">
                <label class="form-label small mb-0">Field Office</label>
                <select name="field_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach(\App\Models\Field::all() as $f)
                        <option value="{{ $f->id }}" @selected($fieldId == $f->id)>{{ $f->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </form>

        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-6"><div class="ot-kpi" style="background:#111827;"><small>DAY TOTAL — {{ $date->format('d M') }}</small><div class="value">GH&#x20B5; {{ number_format($dayTotal,2) }}</div></div></div>
            <div class="col-lg-3 col-6"><div class="ot-kpi" style="background:#374151;"><small>NIGHT TOTAL — {{ $date->format('d M') }}</small><div class="value">GH&#x20B5; {{ number_format($nightTotal,2) }}</div></div></div>
            <div class="col-lg-3 col-6"><div class="ot-kpi" style="background:#b45309;"><small>THIS WEEK</small><div class="value">GH&#x20B5; {{ number_format($weekTotal,2) }}</div></div></div>
            <div class="col-lg-3 col-6"><div class="ot-kpi" style="background:#7c3aed;"><small>THIS MONTH</small><div class="value">GH&#x20B5; {{ number_format($monthTotal,2) }}</div></div></div>
        </div>

        @foreach(['day' => ['label' => 'Day Shift', 'rows' => $day, 'total' => $dayTotal, 'badge' => 'badge-shift-day'],
                  'night' => ['label' => 'Night Shift', 'rows' => $night, 'total' => $nightTotal, 'badge' => 'badge-shift-night']] as $shiftKey => $shiftData)
        <div class="card ot-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><span class="badge {{ $shiftData['badge'] }}">{{ $shiftData['label'] }}</span></strong>
                <span class="shift-total">GH&#x20B5; {{ number_format($shiftData['total'], 2) }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm ot-table mb-0" id="ot-table-{{ $shiftKey }}">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Absent / Post Vacant</th>
                            <th>Client / Site</th>
                            <th>O.T Employee</th>
                            <th>Officer On Duty</th>
                            <th>Reason</th>
                            <th>Amount</th>
                            <th>Phone</th>
                            <th>Approval</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shiftData['rows'] as $key => $row)
                        <tr data-id="{{ $row->id }}">
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $row->absentLabel() }}</td>
                            <td>{{ $row->clientLabel() }}</td>
                            <td><strong>{{ $row->otEmployee?->name }}</strong></td>
                            <td>{{ $row->officer?->name }}</td>
                            <td>{{ $row->reason }}</td>
                            <td>GH&#x20B5; {{ number_format($row->amount, 2) }}</td>
                            <td>{{ $row->phone_number }}</td>
                            <td>
                                @foreach($row->approvalTimeline() as $stage)
                                    <span class="stage-dot {{ $stage['status'] }}" title="{{ $stage['label'] }}: {{ $stage['status'] }}"></span>
                                @endforeach
                                @if($row->currentStage() && $row->canActOnStage(Auth::user(), $row->currentStage()))
                                    <form action="{{ url('overtime/'.$row->id.'/approve') }}" method="POST" class="d-inline">@csrf<button class="btn btn-link btn-sm p-0 text-success" title="Approve"><i class="bx bx-check"></i></button></form>
                                    <form action="{{ url('overtime/'.$row->id.'/reject') }}" method="POST" class="d-inline">@csrf<button class="btn btn-link btn-sm p-0 text-danger" title="Reject"><i class="bx bx-x"></i></button></form>
                                @endif
                            </td>
                            <td>
                                @if($row->isEditableBy(Auth::user()))
                                <form action="{{ url('overtime/'.$row->id) }}" method="POST" onsubmit="return confirm('Delete this entry?');" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-link btn-sm p-0 text-danger"><i class="bx bx-trash"></i></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="quick-row table-light">
                            <td colspan="10">
                                <form class="ot-quick-form d-flex flex-wrap gap-2 align-items-center" data-shift="{{ $shiftKey }}">
                                    @csrf
                                    <input type="hidden" name="entry_date" value="{{ $date->format('Y-m-d') }}">
                                    <input type="hidden" name="shift" value="{{ $shiftKey }}">
                                    <input type="hidden" name="field_id" value="{{ $fieldId }}">

                                    <select class="form-select form-select-sm ot-select-employee" name="absent_employee_id" data-placeholder="Absent guard (or type below)" style="width:170px"></select>
                                    <input type="text" class="form-control form-control-sm" name="absent_employee_note" placeholder="or note e.g. Shortage" style="width:140px">

                                    <select class="form-select form-select-sm ot-select-client" name="client_id" data-placeholder="Client" style="width:170px"></select>
                                    <input type="text" class="form-control form-control-sm" name="client_site_note" placeholder="or site name" style="width:130px">

                                    <select class="form-select form-select-sm ot-select-employee" name="ot_employee_id" data-placeholder="O.T Employee *" required style="width:170px"></select>

                                    <select class="form-select form-select-sm ot-select-employee" name="officer_id" data-placeholder="Officer on duty" style="width:170px"></select>

                                    <select class="form-select form-select-sm" name="reason" style="width:130px">
                                        <option value="">Reason...</option>
                                        @foreach($reasons as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
                                    </select>

                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="amount" placeholder="Amount" style="width:100px" value="30" required>
                                    <input type="text" class="form-control form-control-sm" name="phone_number" placeholder="Phone" style="width:120px">

                                    <button type="submit" class="btn btn-dark btn-sm"><i class="bx bx-plus"></i> Add</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm ot-duplicate-last"><i class="bx bx-copy"></i> Duplicate last</button>
                                </form>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endforeach

    </div>
    @endsection

    @section('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    $(function () {
        const employeeUrl = "{{ route('overtime.lookup.employees') }}";
        const clientUrl = "{{ route('overtime.lookup.clients') }}";
        const storeUrl = "{{ route('overtime.store') }}";
        const csrf = "{{ csrf_token() }}";

        function initSelects(scope) {
            scope.find('.ot-select-employee').each(function () {
                const $el = $(this);
                $el.select2({
                    width: '160px',
                    placeholder: function () { return $el.data('placeholder'); },
                    allowClear: true,
                    ajax: {
                        url: employeeUrl,
                        dataType: 'json',
                        delay: 200,
                        data: function (params) { return { q: params.term, select_name: $el.attr('name'), field_id: $('select[name=field_id]').val() }; },
                        processResults: data => ({ results: data })
                    }
                });
            });

            scope.find('.ot-select-client').each(function () {
                const $el = $(this);
                $el.select2({
                    width: '160px',
                    placeholder: function () { return $el.data('placeholder'); },
                    allowClear: true,
                    ajax: {
                        url: clientUrl,
                        dataType: 'json',
                        delay: 200,
                        data: function (params) { return { q: params.term, select_name: $el.attr('name'), field_id: $('select[name=field_id]').val() }; },
                        processResults: data => ({ results: data })
                    }
                });
            });
        }
        initSelects($('body'));

        // Fast repeated data-entry: submit row via AJAX, refresh totals + table without full reload.
        $('.ot-quick-form').on('submit', function (e) {
            e.preventDefault();
            const form = $(this);
            const shift = form.data('shift');

            $.ajax({
                url: storeUrl,
                method: 'POST',
                data: form.serialize(),
                headers: { 'X-CSRF-TOKEN': csrf },
                success: function () {
                    window.location.reload(); // simplest reliable refresh of totals/table/approval state
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Please check the required fields (O.T Employee and Amount).';
                    alert(msg);
                }
            });
        });

        // "Duplicate last": copies officer / client / reason from the last row added in this shift,
        // so a run of entries for the same site/officer doesn't need re-selecting every field.
        $('.ot-duplicate-last').on('click', function () {
            const form = $(this).closest('form');
            const table = form.closest('table');
            const lastRow = table.find('tbody tr').last();
            if (!lastRow.length) return;
            form.find('input[name=amount]').val(30);
            // Officer/client text is just copied as a visual cue; user re-confirms via the dropdowns.
            alert('Tip: re-select the officer/client from the dropdown — last entry was: '
                + lastRow.find('td').eq(4).text() + ' / ' + lastRow.find('td').eq(2).text());
        });
    });
    </script>
    @endsection

</x-sales-dashboard>

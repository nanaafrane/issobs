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
            <li class="menu-item">
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

            @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))
            <li class="menu-item">
                <a href="{{ url('invoice') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bxs-receipt bg-primary"></i>
                    <div class="text-truncate" data-i18n="Invoices">Invoices</div>
                </a>
            </li>
            @endif


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

            @if(Auth::user()->hasRole(['Manager', 'Officer', 'Finance Manager']) )

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

            <li class="menu-item active">
                <a href="{{url('expense')}}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bxs-credit-card bg-secondary"></i>
                    <div class="text-truncate" data-i18n="Expense"> Expenses </div>
                </a>
            </li>

            @endif

        </ul>
    </aside>
    <!-- / Menu -->
    @endsection




    @section('content')
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">

    <div class="container-fluid py-4">
    
        <style>
            .expense-toolbar { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 1rem 1.25rem; }
            .expense-toolbar label { font-size: .75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .03em; }
            .master-card { border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
            .master-card .card-header { background: #1f2937; color: #fff; border: none; padding: 1rem 1.25rem; }
            .type-chip { border-radius: 999px; font-size: .8125rem; padding: .35rem .85rem; }
            .type-chip .badge { font-weight: 600; }
            .office-card { border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,.04); transition: box-shadow .15s ease; height: 100%; }
            .office-card:hover { box-shadow: 0 4px 10px rgba(0,0,0,.08); }
            .office-card .card-header { background: #f9fafb; border-bottom: 1px solid #e5e7eb; padding: .9rem 1.1rem; }
            .office-card .sub-office-tag { font-weight: 400; font-size: .75rem; color: #6b7280; }
            .office-card .card-body { padding: 1.1rem; min-height: 88px; display: flex; flex-direction: column; justify-content: center; }
            .office-card .metric-total { font-size: 1.5rem; font-weight: 700; color: #111827; }
            .corporate-card { border-radius: 12px; border: 1px solid #e5e7eb; height: 100%; }
        </style>
    
        {{-- ===== SEARCH + NEW EXPENSE, one row: the only entry point for creating ===== --}}
        <form method="GET" action="{{ route('expense.index') }}" class="expense-toolbar d-flex flex-wrap align-items-end gap-3 mb-4">
            <div>
                <label class="d-block mb-1">From</label>
                <input type="month" name="from_month" class="form-control form-control-sm" value="{{ $from->format('Y-m') }}">
            </div>
            <div>
                <label class="d-block mb-1">To</label>
                <input type="month" name="to_month" class="form-control form-control-sm" value="{{ $to->format('Y-m') }}">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
    
            <a href="{{ route('expense.create') }}" class="btn btn-success btn-sm ms-auto">
                + New Expense
            </a>
        </form>
    
        <ul class="nav nav-pills mb-4" id="expenseScopeTabs">
            <li class="nav-item"><a class="nav-link active" data-scope="field" href="#">Field Offices</a></li>
            <li class="nav-item"><a class="nav-link" data-scope="corporate" href="#">Head Office (Accra)</a></li>
        </ul>
    
        {{-- ===================== FIELD OFFICES VIEW ===================== --}}
        <div id="fieldScopeView">
    
            <div class="card master-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-5">
                    <div>
                        <strong>Master Expense Types</strong>
                        <div class="small opacity-75">{{ $from->format('M Y') }} – {{ $to->format('M Y') }} · click a type to filter offices below</div>
                    </div>
                </div>
                <div class="card-body d-flex flex-wrap gap-2 bg-white" id="masterTypeChips">
                    @foreach($fieldTypes as $type)
                        <button type="button" class="btn btn-outline-primary type-chip" data-type-id="{{ $type->id }}">
                            {{ $type->name }}
                            <span class="badge bg-primary ms-1">
                                {{ number_format($masterTotals[$type->id]['total'] ?? 0, 2) }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>
    
            <div class="row g-3" id="fieldOfficeCards">
                @foreach($fieldGroups as $office)
                    <div class="col-md-4">
                        <div class="card office-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $office->name }}</strong>
                                    @if($office->children->isNotEmpty())
                                        <div class="sub-office-tag">+ {{ $office->children->pluck('name')->join(', ') }}</div>
                                    @endif
                                </div>
                                <a href="{{ route('expense.list', ['field_group_id' => $office->id, 'from_month' => $from->format('Y-m'), 'to_month' => $to->format('Y-m')]) }}"
                                class="btn btn-sm btn-outline-secondary">View entries</a>
                            </div>
                            <div class="card-body field-card-body" data-field-id="{{ $office->id }}">
                                <p class="text-muted small mb-0" data-placeholder>
                                    Select a type above to see this office's total.
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    
        {{-- ===================== HEAD OFFICE / CORPORATE VIEW ===================== --}}
        <div id="corporateScopeView" class="d-none">
            <div class="card corporate-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <strong>Head Office (Accra)</strong>
                        <div class="small text-muted">{{ $from->format('M Y') }} – {{ $to->format('M Y') }}</div>
                    </div>
                    @if($headOffice)
                        <div>
                            <a href="{{ route('expense.list', ['field_group_id' => $headOffice->id, 'from_month' => $from->format('Y-m'), 'to_month' => $to->format('Y-m')]) }}"
                            class="btn btn-sm btn-outline-secondary">View entries</a>
                            <a href="{{ route('expense.create', ['field_group_id' => $headOffice->id]) }}"
                            class="btn btn-sm btn-success">+ New Expense</a>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach($corporateTypes as $type)
                            @php $t = $masterTotals[$type->id] ?? ['total' => 0, 'cnt' => 0]; @endphp
                            <div class="col-md-3 col-sm-6">
                                <div class="border rounded p-2 h-100">
                                    <div class="small fw-semibold">{{ $type->name }}</div>
                                    <div class="text-muted small">
                                        {{ $t['cnt'] }} entr{{ $t['cnt'] === 1 ? 'y' : 'ies' }}
                                        &middot; {{ number_format($t['total'], 2) }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    
    </div>
 
    <script>
            const expenseMatrix = @json($matrix);
            
            document.querySelectorAll('#expenseScopeTabs .nav-link').forEach(tab => {
                tab.addEventListener('click', (e) => {
                    e.preventDefault();
                    document.querySelectorAll('#expenseScopeTabs .nav-link').forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    const scope = tab.dataset.scope;
                    document.getElementById('fieldScopeView').classList.toggle('d-none', scope !== 'field');
                    document.getElementById('corporateScopeView').classList.toggle('d-none', scope !== 'corporate');
                });
            });
            
            document.querySelectorAll('.type-chip').forEach(chip => {
                chip.addEventListener('click', () => {
                    document.querySelectorAll('.type-chip').forEach(c => {
                        c.classList.remove('active', 'btn-primary');
                        c.classList.add('btn-outline-primary');
                    });
                    chip.classList.add('active', 'btn-primary');
                    chip.classList.remove('btn-outline-primary');
            
                    const typeId = chip.dataset.typeId;
                    const typeName = chip.textContent.trim();
                    const perGroup = expenseMatrix[typeId] || {};
            
                    document.querySelectorAll('.field-card-body').forEach(body => {
                        const groupId = body.dataset.fieldId;
                        const data = perGroup[groupId];
            
                        body.innerHTML = data
                            ? `<div class="fw-semibold small text-muted mb-1">${typeName}</div>
                            <div class="metric-total">${Number(data.total).toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
                            <div class="text-muted small">${data.cnt} entr${data.cnt === 1 ? 'y' : 'ies'}</div>`
                            : `<div class="fw-semibold small text-muted mb-1">${typeName}</div>
                            <div class="metric-total text-muted">0.00</div>
                            <div class="text-muted small">Not used at this office in this period</div>`;
                    });
                });
            });
    </script>

        </div>
    </div>
    @endsection


</x-sales-dashboard>
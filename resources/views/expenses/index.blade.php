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
            
                <ul class="nav nav-tabs mb-4" id="expenseScopeTabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-scope="field" href="#">Field Offices</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-scope="corporate" href="#">Head Office (Corporate)</a>
                    </li>
                </ul>
            
                {{-- ===================== FIELD OFFICES VIEW ===================== --}}
                <div id="fieldScopeView">
            
                    <div class="card mb-4 border-primary">
                        <div class="card-header bg-primary text-white m-5">
                            <strong>Master Expense Types</strong>
                            <span class="small ms-2 opacity-75">— click a type to filter the field-office cards below</span>
                        </div>
                        <div class="card-body d-flex flex-wrap gap-6" id="masterTypeChips">
                            @foreach($fieldTypes as $type)
                                <a type="button"
                                        class="btn btn-outline-primary btn-md type-chip"
                                        data-type-id="{{ $type->id }}">
                                    {{ $type->name }}
                                    <span class="badge bg-primary ms-1">
                                        {{ number_format($masterTotals[$type->id]['total'] ?? 0, 2) }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
            
                    <div class="row" id="fieldOfficeCards">
                        @foreach($fieldOffices as $office)
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <strong>{{ $office->name }}</strong>
                                        <div>
                                            <a href="{{ route('expenses.list', ['field_id' => $office->id]) }}"
                                            class="btn btn-sm btn-outline-secondary">View entries</a>
                                            <a href="{{ route('expense.create', ['field_id' => $office->id]) }}"
                                            class="btn btn-sm btn-success">+ New Expense</a>
                                        </div>
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
                    <div class="card border-secondary">
                        <div class="card-header bg-secondary text-white">
                            <strong>Head Office (Accra) — Expense Types</strong>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach($corporateTypes as $type)
                                    @php $t = $masterTotals[$type->id] ?? ['total' => 0, 'cnt' => 0]; @endphp
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="border rounded p-2 h-100">
                                            <div class="small fw-bold">{{ $type->name }}</div>
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
            
            {{-- matrix[type_id][field_id] = {total, cnt} -- embedded once, filtered client-side on click --}}
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
                        document.querySelectorAll('.type-chip').forEach(c => c.classList.remove('active', 'btn-primary'));
                        document.querySelectorAll('.type-chip').forEach(c => c.classList.add('btn-outline-primary'));
                        chip.classList.add('active', 'btn-primary');
                        chip.classList.remove('btn-outline-primary');
                
                        const typeId = chip.dataset.typeId;
                        const typeName = chip.textContent.trim();
                        const perField = expenseMatrix[typeId] || {};
                
                        document.querySelectorAll('.field-card-body').forEach(body => {
                            const fieldId = body.dataset.fieldId;
                            const data = perField[fieldId];
                
                            if (data) {
                                body.innerHTML = `
                                    <div class="fw-bold">${typeName}</div>
                                    <div class="fs-4">${Number(data.total).toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
                                    <div class="text-muted small">${data.cnt} entr${data.cnt === 1 ? 'y' : 'ies'}</div>
                                `;
                            } else {
                                body.innerHTML = `
                                    <div class="fw-bold">${typeName}</div>
                                    <div class="fs-4 text-muted">0.00</div>
                                    <div class="text-muted small">Not used at this office yet</div>
                                `;
                            }
                        });
                    });
                });
            </script>

        </div>
    </div>
    @endsection


</x-sales-dashboard>
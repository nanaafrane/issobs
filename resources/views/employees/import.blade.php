<x-hr-dashboard>

  @section('css')
    <style>
        .imp-step { display:flex; align-items:center; gap:.5rem; font-weight:600; margin:0 0 .5rem; }
        .imp-step .n { width:1.6rem; height:1.6rem; border-radius:50%; background:rgba(105,108,255,.15); color:#696cff; display:inline-flex; align-items:center; justify-content:center; font-size:.85rem; }
        .imp-metric { background:#f5f5f9; border-radius:.5rem; padding:.75rem 1rem; }
        .imp-metric .v { font-size:1.5rem; font-weight:600; }
        #impRows td { vertical-align: top; }
        .imp-msg { display:block; font-size:.8rem; }
        .imp-filter.active { background:#696cff; color:#fff; border-color:#696cff; }
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
                    <li class="menu-item active open">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons bx bxs-user-account"></i>
                        <div class="text-truncate" data-i18n="Staffs">Employees</div>
                        </a>
                        <ul class="menu-sub">
                        <li class="menu-item active">
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
                                <li class="menu-item ">
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
                <li class="menu-item active">
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
                @if(Auth::user()->hasRole(['Manager', 'Invoice']))
                <li class="menu-item ">
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
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><i class="bx bxs-user-account"></i> Employee /</span> Bulk upload</h4>

        @include('flash-messages')
        @if(session('completed_import'))
            @php $done = \App\Models\EmployeeImport::find(session('completed_import')); @endphp
            @if($done && $done->skipped_count)
                <div class="alert alert-warning">
                    {{ $done->skipped_count }} rows were skipped.
                    <a href="{{ route('employees.import.errors', $done) }}" class="alert-link">Download the error report</a>, fix those rows and upload them again.
                </div>
            @endif
        @endif
        @if($errors->any())
            <div class="alert alert-danger mb-3">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
        @endif

        @unless($canCreate)
            <div class="alert alert-danger">Your role cannot create employees, so bulk upload is not available to you.</div>
        @else

        {{-- STEP 1: upload --}}
        <p class="imp-step"><span class="n">1</span> Upload</p>
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <h5 class="mb-1">Upload many employees at once</h5>
                        <p class="text-muted mb-0 small">Download the template: it lists only the field offices, clients and banks you can assign. One employee per row.</p>
                    </div>
                    <a href="{{ route('employees.import.template') }}" class="btn btn-outline-primary"><i class="bx bx-download me-1"></i> Download template</a>
                </div>
                <form action="{{ route('employees.import.preview') }}" method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label" for="file"><strong>Completed template (.xlsx) *</strong></label>
                        <input type="file" name="file" id="file" accept=".xlsx" class="form-control @error('file') is-invalid @enderror" required>
                    </div>
                    @if($needsApprover)
                    <div class="col-md-4">
                        <label class="form-label" for="approver_id"><strong>Assign to *</strong></label>
                        <select name="approver_id" id="approver_id" class="form-select @error('approver_id') is-invalid @enderror" required>
                            <option value="" disabled @selected(! old('approver_id', $import->approver_id ?? null))>Choose...</option>
                            @foreach($approvers as $approver)
                                <option value="{{ $approver->id }}" @selected(old('approver_id', $import->approver_id ?? null) == $approver->id)>{{ $approver->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100"><i class="bx bx-search-alt me-1"></i> Preview and check</button>
                    </div>
                    <div class="col-12"><small class="text-muted">Nothing is saved until you confirm in step 3. Up to {{ \App\Support\EmployeeBulkImport::MAX_ROWS }} employees per file.</small></div>
                </form>
            </div>
        </div>

        @isset($results)
        {{-- STEP 2: preview --}}
        <p class="imp-step"><span class="n">2</span> Preview <span class="text-muted fw-normal small">&middot; {{ $import->original_name }} &middot; nothing saved yet</span></p>
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3"><div class="imp-metric"><div class="text-muted small">Rows</div><div class="v">{{ $summary['total'] }}</div></div></div>
                    <div class="col-6 col-md-3"><div class="imp-metric"><div class="text-muted small">Ready</div><div class="v text-success">{{ $summary['valid'] }}</div></div></div>
                    <div class="col-6 col-md-3"><div class="imp-metric"><div class="text-muted small">Errors (skipped)</div><div class="v text-danger">{{ $summary['errors'] }}</div></div></div>
                    <div class="col-6 col-md-3"><div class="imp-metric"><div class="text-muted small">Ready with warnings</div><div class="v text-warning">{{ $summary['warnings'] }}</div></div></div>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary imp-filter active" data-filter="all">All rows</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary imp-filter" data-filter="error">Errors only</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary imp-filter" data-filter="warning">Warnings</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary imp-filter" data-filter="ok">Ready</button>
                    @if($summary['errors'])
                        <a href="{{ route('employees.import.errors', $import) }}" class="btn btn-sm btn-outline-danger ms-auto"><i class="bx bx-download me-1"></i> Error report</a>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-sm" id="impRows">
                        <thead><tr><th>Row</th><th>Name</th><th>Field office</th><th>Client</th><th>Result</th></tr></thead>
                        <tbody>
                        @foreach($results as $r)
                            @php $state = $r['errors'] ? 'error' : ($r['warnings'] ? 'warning' : 'ok'); @endphp
                            <tr data-state="{{ $state }}">
                                <td class="text-nowrap">{{ $r['row'] }}</td>
                                <td>{{ $r['name'] }}</td>
                                <td>{{ $r['field'] }}</td>
                                <td>{{ $r['client'] }}</td>
                                <td>
                                    @if($r['errors'])
                                        <span class="badge bg-label-danger mb-1">Will be skipped</span>
                                        @foreach($r['errors'] as $msg)<span class="imp-msg text-danger">{{ $msg }}</span>@endforeach
                                    @else
                                        <span class="badge bg-label-success mb-1">Ready</span>
                                        <x-pay-priority :level="$r['priority']" />
                                    @endif
                                    @foreach($r['warnings'] as $msg)<span class="imp-msg text-warning">{{ $msg }}</span>@endforeach
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- STEP 3: confirm --}}
        <p class="imp-step"><span class="n">3</span> Confirm</p>
        <div class="card mb-4">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    @if($summary['valid'])
                        <strong>{{ $summary['valid'] }} {{ \Illuminate\Support\Str::plural('employee', $summary['valid']) }} will be created{{ $summary['status'] ? ' as ' . $summary['status'] : '' }}.</strong><br>
                        <span class="text-muted small">
                            @if($summary['errors']) {{ $summary['errors'] }} rows with errors will be skipped; fix them using the error report and upload them again. @endif
                            Every row is checked again when you confirm.
                        </span>
                    @else
                        <strong class="text-danger">No rows are ready to import.</strong><br>
                        <span class="text-muted small">Download the error report, fix the rows and upload the file again.</span>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('employees.import') }}" class="btn btn-outline-secondary">Cancel</a>
                    @if($summary['valid'])
                    <form action="{{ route('employees.import.confirm', $import) }}" method="POST" onsubmit="this.querySelector('button').disabled = true;">
                        @csrf
                        <button type="submit" class="btn btn-primary"><i class="bx bx-check me-1"></i> Create {{ $summary['valid'] }} {{ \Illuminate\Support\Str::plural('employee', $summary['valid']) }}</button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @endisset

        @if($recent->count())
            <h6 class="text-muted mt-4">Your recent uploads</h6>
            <ul class="list-group mb-4">
                @foreach($recent as $past)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>{{ $past->original_name }} <span class="text-muted small">&middot; {{ $past->completed_at?->format('d M Y H:i') }}</span></span>
                        <span>
                            <span class="badge bg-label-success">{{ $past->created_count }} created</span>
                            @if($past->skipped_count)
                                <a href="{{ route('employees.import.errors', $past) }}" class="badge bg-label-danger">{{ $past->skipped_count }} skipped &middot; report</a>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
        @endunless
    </div>
  @endsection

  @section('scripts')
    <script>
        document.querySelectorAll('.imp-filter').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.imp-filter').forEach(b => b.classList.toggle('active', b === btn));
                const f = btn.dataset.filter;
                document.querySelectorAll('#impRows tbody tr').forEach(function (tr) {
                    tr.style.display = (f === 'all' || tr.dataset.state === f) ? '' : 'none';
                });
            });
        });
    </script>
  @endsection

</x-hr-dashboard>

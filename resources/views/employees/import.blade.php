<x-hr-dashboard>

  @section('side_nav')
    @include('partials.receipt_side_nav')
  @endsection
  
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

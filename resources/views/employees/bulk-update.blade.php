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
        #updRows td { vertical-align: top; }
        .imp-msg { display:block; font-size:.8rem; }
        .chg { display:block; font-size:.85rem; }
        .chg .old { color:#a1acb8; text-decoration: line-through; }
        .chg.pay { background: rgba(255,171,0,.12); border-radius:.25rem; padding:0 .25rem; }
        .imp-filter.active { background:#696cff; color:#fff; border-color:#696cff; }
    </style>
  @endsection


  @section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><i class="bx bxs-user-account"></i> Employee /</span> Bulk update</h4>

        @include('flash-messages')
        @if(session('completed_import'))
            @php $done = \App\Models\EmployeeImport::find(session('completed_import')); @endphp
            @if($done && $done->skipped_count)
                <div class="alert alert-warning">
                    {{ $done->skipped_count }} rows were skipped.
                    <a href="{{ route('employees.bulk-update.errors', $done) }}" class="alert-link">Download the error report</a>, fix those rows and upload them again.
                </div>
            @endif
        @endif
        @if($errors->any())
            <div class="alert alert-danger mb-3">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
        @endif

        @unless($canUpdate)
            <div class="alert alert-danger">Your role cannot edit employees, so bulk update is not available to you.</div>
        @else

        {{-- STEP 1: download + upload --}}
        <p class="imp-step"><span class="n">1</span> Download, edit, upload</p>
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <h5 class="mb-1">Change many employees at once</h5>
                        <p class="text-muted mb-0 small">
                            Download your employees already filled in, change only what you need, and upload the file back.
                            To download a <strong>filtered</strong> list (an office, a client, a status...), filter the
                            <a href="{{ route('employees.index') }}">employee list</a> and use its <em>Download for bulk update</em> button.
                            A blank cell means "leave unchanged".
                        </p>
                    </div>
                    <a href="{{ route('employees.bulk-update.template') }}" class="btn btn-outline-primary text-nowrap"><i class="bx bx-download me-1"></i> Download all my employees</a>
                </div>
                <form action="{{ route('employees.bulk-update.preview') }}" method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label" for="file"><strong>Edited file (.xlsx) *</strong></label>
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
                        <button type="submit" class="btn btn-primary w-100"><i class="bx bx-search-alt me-1"></i> Preview changes</button>
                    </div>
                    <div class="col-12"><small class="text-muted">Nothing is saved until you confirm in step 3. Up to {{ \App\Support\EmployeeBulkImport::MAX_UPDATE_ROWS }} employees per file.</small></div>
                </form>
            </div>
        </div>

        @isset($results)
        {{-- STEP 2: preview every change --}}
        <p class="imp-step"><span class="n">2</span> Preview <span class="text-muted fw-normal small">&middot; {{ $import->original_name }} &middot; nothing saved yet</span></p>
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md"><div class="imp-metric"><div class="text-muted small">Rows</div><div class="v">{{ $summary['total'] }}</div></div></div>
                    <div class="col-6 col-md"><div class="imp-metric"><div class="text-muted small">Will change</div><div class="v text-primary">{{ $summary['changed'] }}</div></div></div>
                    <div class="col-6 col-md"><div class="imp-metric"><div class="text-muted small">No change</div><div class="v text-muted">{{ $summary['unchanged'] }}</div></div></div>
                    <div class="col-6 col-md"><div class="imp-metric"><div class="text-muted small">Errors (skipped)</div><div class="v text-danger">{{ $summary['errors'] }}</div></div></div>
                    <div class="col-6 col-md"><div class="imp-metric"><div class="text-muted small">Bank / payment changes</div><div class="v text-warning">{{ $summary['payment_changes'] }}</div></div></div>
                    <div class="col-6 col-md"><div class="imp-metric"><div class="text-muted small">Changes to non-active staff</div><div class="v text-secondary">{{ $summary['inactive_changes'] }}</div></div></div>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary imp-filter active" data-filter="changed">Changes</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary imp-filter" data-filter="payment">Bank / payment changes</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary imp-filter" data-filter="inactive">Non-active staff</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary imp-filter" data-filter="error">Errors</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary imp-filter" data-filter="unchanged">No change</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary imp-filter" data-filter="all">All rows</button>
                    @if($summary['errors'])
                        <a href="{{ route('employees.bulk-update.errors', $import) }}" class="btn btn-sm btn-outline-danger ms-auto"><i class="bx bx-download me-1"></i> Error report</a>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-sm" id="updRows">
                        <thead><tr><th>Row</th><th>Employee</th><th>Changes</th></tr></thead>
                        <tbody>
                        @foreach($results as $r)
                            @php
                                $state = $r['errors'] ? 'error' : ($r['changes'] ? 'changed' : 'unchanged');
                                $payKeys = \App\Support\EmployeeBulkImport::PAYMENT_FIELDS;
                            @endphp
                            <tr data-state="{{ $state }}" data-payment="{{ $r['payment_change'] ? 1 : 0 }}" data-inactive="{{ $r['status'] && $r['status'] !== 'Active' ? 1 : 0 }}">
                                <td class="text-nowrap">{{ $r['row'] }}</td>
                                <td class="text-nowrap">
                                    @if($r['employee_id'])<span class="text-muted">FWSS {{ $r['employee_id'] }}</span><br>@endif
                                    {{ $r['name'] }}
                                    @if($r['status'])
                                        <br><span class="badge {{ $r['status'] === 'Active' ? 'bg-label-success' : 'bg-label-secondary' }}">{{ $r['status'] }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($r['errors'])
                                        <span class="badge bg-label-danger mb-1">Will be skipped</span>
                                        @foreach($r['errors'] as $msg)<span class="imp-msg text-danger">{{ $msg }}</span>@endforeach
                                    @elseif(! $r['changes'])
                                        <span class="text-muted small">No change</span>
                                    @else
                                        @foreach($r['changes'] as $attr => [$label, $old, $new])
                                            <span class="chg {{ in_array($attr, $payKeys, true) ? 'pay' : '' }}">
                                                <strong>{{ $label }}:</strong> <span class="old">{{ $old }}</span> &rarr; {{ $new }}
                                            </span>
                                        @endforeach
                                        @if($r['priority_before'] !== $r['priority_after'])
                                            <span class="chg"><strong>Pay priority:</strong>
                                                <span class="old">{{ \App\Support\PayPriority::LABELS[$r['priority_before']] }}</span> &rarr;
                                                {{ \App\Support\PayPriority::LABELS[$r['priority_after']] }}</span>
                                        @endif
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
            <div class="card-body">
                @if($summary['changed'])
                <form action="{{ route('employees.bulk-update.confirm', $import) }}" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                    @csrf
                    <p class="mb-2"><strong>{{ $summary['changed'] }} {{ \Illuminate\Support\Str::plural('employee', $summary['changed']) }} will be updated.</strong>
                        @if($summary['errors']) <span class="text-muted">{{ $summary['errors'] }} rows with errors will be skipped.</span> @endif
                    </p>
                    @if($summary['profile_changes'] && $summary['status'] === 'Pending')
                        <div class="alert alert-info py-2 small mb-2">
                            As when editing one employee, the {{ $summary['profile_changes'] }} employees whose details change will go back to
                            <strong>Pending</strong> for approval{{ $import->approver ? ' by ' . $import->approver->name : '' }}, and will not appear in the employee list until approved.
                            Bank-only changes do not need approval.
                        </div>
                    @endif
                    @if($summary['inactive_changes'])
                        <div class="alert alert-secondary py-2 small mb-2">
                            {{ $summary['inactive_changes'] }} of these employees are not Active (e.g. Terminated). Their details will be updated,
                            but their status stays the same - use Terminate / Re-instate on the employee page to change status.
                        </div>
                    @endif
                    @if($summary['payment_changes'])
                        <div class="form-check mb-3">
                            <input class="form-check-input @error('confirm_payment_changes') is-invalid @enderror" type="checkbox" value="1" id="confirm_payment_changes" name="confirm_payment_changes" required>
                            <label class="form-check-label" for="confirm_payment_changes">
                                I have checked the <strong>{{ $summary['payment_changes'] }}</strong> bank / payment changes highlighted above
                                (payment type, bank, account number, branch).
                            </label>
                        </div>
                    @endif
                    <div class="d-flex gap-2">
                        <a href="{{ route('employees.bulk-update') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="bx bx-check me-1"></i> Update {{ $summary['changed'] }} {{ \Illuminate\Support\Str::plural('employee', $summary['changed']) }}</button>
                    </div>
                    <small class="text-muted d-block mt-2">Every row is checked again when you confirm. All changes are applied together, or none are.</small>
                </form>
                @else
                    <strong class="text-danger">There are no changes to apply.</strong>
                    <div class="text-muted small">Edit the file (or fix the rows in the error report) and upload it again.</div>
                @endif
            </div>
        </div>
        @endisset

        @if($recent->count())
            <h6 class="text-muted mt-4">Your recent bulk updates</h6>
            <ul class="list-group mb-4">
                @foreach($recent as $past)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>{{ $past->original_name }} <span class="text-muted small">&middot; {{ $past->completed_at?->format('d M Y H:i') }}</span></span>
                        <span>
                            <span class="badge bg-label-primary">{{ $past->created_count }} updated</span>
                            @if($past->skipped_count)
                                <a href="{{ route('employees.bulk-update.errors', $past) }}" class="badge bg-label-danger">{{ $past->skipped_count }} skipped &middot; report</a>
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
        (function () {
            function apply(f) {
                document.querySelectorAll('.imp-filter').forEach(b => b.classList.toggle('active', b.dataset.filter === f));
                document.querySelectorAll('#updRows tbody tr').forEach(function (tr) {
                    const show = f === 'all'
                        || (f === 'payment' ? tr.dataset.payment === '1'
                        : f === 'inactive' ? tr.dataset.inactive === '1'
                        : tr.dataset.state === f);
                    tr.style.display = show ? '' : 'none';
                });
            }
            document.querySelectorAll('.imp-filter').forEach(btn => btn.addEventListener('click', () => apply(btn.dataset.filter)));
            if (document.getElementById('updRows')) apply('changed');
        })();
    </script>
  @endsection

</x-hr-dashboard>

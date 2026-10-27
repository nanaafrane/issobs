<x-hr-dashboard>


  @section('side_nav')
    @include('partials.receipt_side_nav')
  @endsection
  


    @section('content')

  <div class="container-xxl flex-grow-1 container-p-y">
    @foreach(['success' => 'success', 'error' => 'danger'] as $key => $color)
      @if(session($key))
        <div class="alert alert-{{ $color }} alert-dismissible" role="alert">{{ session($key) }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
      @endif
    @endforeach
    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
          <div>
            <h4 class="fw-bold mb-1">Employee profile</h4>
            <p class="text-muted mb-0">Comprehensive profile for {{ $employee->name }}.</p>
          </div>

          <div class="text-md-end">
            @php
              $channelName = collect($channels)->firstWhere('channel', $employee->channel)?->name ?? '-';
              $statusBadgeClass = $employee->status === 'Active' ? 'bg-success' : 'bg-danger';
              $taxBadgeClass = $employee->tax_button === 'on' ? 'bg-success' : 'bg-dark';
              $ssnitBadgeClass = $employee->ssnit_button === 'on' ? 'bg-success' : 'bg-dark';
            @endphp

            <span class="badge {{ $statusBadgeClass }} mb-2">{{ ucfirst($employee->status) }}</span>
            <div class="btn-toolbar justify-content-end flex-wrap gap-2">
              @if ($employee->ho_status !== 'approved' || Auth::user()->hasRole(['Finance Manager', 'Invoice']) || (Auth::user()->hasRole(['Manager']) && Auth::user()->hasPermission('HR')))
                <a href="{{ url('employees/'.$employee->id.'/edit') }}" class="btn btn-dark btn-sm">
                  <i class="bx bx-edit-alt me-1"></i> Edit
                </a>
              @endif

              @if ($employee->payment_type == 'Bank')
              <a href="{{ url('employeesViewPayInfo', $employee->id) }}" class="btn btn-outline-primary btn-sm">
                <i class="bx bxs-comment-detail me-1"></i> Bank Details
              </a>
              @endif

              @if(Auth::user()->hasRole(['Finance Manager', 'Manager']))
                <a href="{{ url('employeesSalary', $employee->id) }}" class="btn btn-outline-secondary btn-sm">
                  <i class="bx bx-money-withdraw me-1"></i> Salaries
                </a>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">

      <div class="col-lg-4">

        <div class="card h-80 bg-info text-white">
          <div class="card-body text-center">
            <img
              src="@if($employee->image) {{ asset($employee->image) }} @else {{ asset('img/user.png') }} @endif"
              alt="Employee avatar"
              class="rounded-circle mb-3 w-px-120 h-px-120 object-fit-cover" />
            <h4 class="card-title text-white mb-1"> <strong> {{ strtoupper($employee->name) }} </strong> </h4>
            <p class="text-muted mb-2"> <strong> {{ strtoupper($employee->role?->name)  ?? 'No role assigned' }} </strong> </p>
            <div class="d-flex justify-content-center flex-wrap gap-2">
              <span class="badge {{ $taxBadgeClass }}">TAX Deduction</span>
              <span class="badge {{ $ssnitBadgeClass }}">SSNIT Deduction</span>
            </div>
          </div>
          <div class="card-body border-top">
            <div class="mb-2">
              <small class="text-muted">Phone</small>
              <h5 class="mb-0 text-white"><strong>{{ $employee->phone_number ?: 'Not provided' }}</strong></h5>
            </div>
            <div class="mb-2">
              <small class="text-muted">Client Name</small>
              <h5 class="mb-0 text-white"><strong>{{ strtoupper($employee->client?->name) }} {{ strtoupper($employee->client?->business_name) ?: 'Not provided' }}</strong></h5>
            </div>
            <div class="mb-2">
              <small class="text-muted">Location</small>
              <h5 class="mb-0 text-white"><strong>{{ strtoupper($employee->location) ?: 'Not provided' }}</strong></h5>
            </div>
            <div class="mb-2">
              <small class="text-muted">Field</small>
              <h5 class="mb-0 text-white"><strong>{{ strtoupper($employee->field?->name) ?: 'Not provided' }}</strong></h5>
            </div>
          </div>
        </div>

        <div class="card h-20 mt-4">
          <div class="card-header">
            <strong>Compensation</strong>
          </div>
          <div class="card-body">
            <dl class="row mb-0">
              <dt class="col-sm-6 text-muted">Payment Type</dt>
              <dd class="col-sm-6 mb-3"><strong>{{ strtoupper($employee->payment_type) ?: '-' }}</strong></dd>

              <dt class="col-sm-6 text-muted">Basic Salary</dt>
              <dd class="col-sm-6 mb-3"><strong> GH&#x20B5; {{ $employee->basic_salary ?: '-' }}</strong></dd>

              <dt class="col-sm-6 text-muted">Allowances</dt>
              <dd class="col-sm-6 mb-0"><strong>GH&#x20B5; {{ $employee->allowances ?: '-' }}</strong></dd>
            </dl>
          </div>
        </div>

      </div>

      <div class="col-lg-8">
        <div class="card mb-4">
          <div class="card-header">
            <strong>Personal & employment information</strong>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Gender</label>
                <p class="fw-semibold mb-0"><strong>{{ strtoupper($employee->gender)  ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Date of birth</label>
                <p class="fw-semibold mb-0"><strong>{{ strtoupper($employee->date_of_birth?->format('l F d, Y'))  ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">NIA Number</label>
                <p class="fw-semibold mb-0"><strong>{{ strtoupper($employee->nia_number ) ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Digital Address</label>
                <p class="fw-semibold mb-0"><strong>{{ strtoupper($employee->address) ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Marital Status</label>
                <p class="fw-semibold mb-0"><strong>{{ strtoupper($employee->marital_status)  ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Worker Type</label>
                <p class="fw-semibold mb-0"><strong>{{ strtoupper($employee->worker_type)  ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Date of joining</label>
                <p class="fw-semibold mb-0"><strong>{{ strtoupper($employee->date_of_joining?->format('l F d, Y'))  ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Department</label>
                <p class="fw-semibold mb-0"><strong>{{ strtoupper($employee->department?->name) ?: '-' }}</strong></p>
              </div>



            </div>
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-header">
            <strong>Guarantor information</strong>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Guarantor name</label>
                <p class="fw-semibold mb-0"><strong>{{ strtoupper( $employee->gurantor_name) ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Guarantor number</label>
                <p class="fw-semibold mb-0"><strong>{{ strtoupper($employee->gurantor_number)  ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Guarantor address</label>
                <p class="fw-semibold mb-0"><strong>{{ strtoupper($employee->gurantor_address)  ?: '-' }}</strong></p>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted mb-1">Guarantor NIA number</label>
                <p class="fw-semibold mb-0"><strong>{{ strtoupper($employee->gurantor_nia_number)  ?: '-' }}</strong></p>
              </div>
              <div class="col-md-12">
                <label class="form-label text-muted mb-1">Relationship</label>
                <p class="fw-semibold mb-0"><strong>{{ strtoupper($employee->relationship ) ?: '-' }}</strong></p>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-body py-3">
            <div class="row g-3">
              <div class="col-md-4">
                <small class="text-muted">Created by</small>
                <p class="mb-0">{{ $employee->user?->name ?: 'Unknown' }}</p>
              </div>
              <div class="col-md-4">
                <small class="text-muted">Approved by</small>
                <p class="mb-0">{{ $employee->user2?->name ?: 'Not available' }}</p>
              </div>
              <div class="col-md-4">
                <small class="text-muted">Updated by</small>
                <p class="mb-0">{{ $employee->user1?->name ?: 'Not available' }}</p>
              </div>
              <div class="col-md-6">
                <small class="text-muted">Created at</small>
                <p class="mb-0">{{ $employee->created_at?->format('l F d, Y h:i A') ?: 'Unknown' }}</p>
              </div>
              <div class="col-md-6">
                <small class="text-muted">Last updated</small>
                <p class="mb-0">{{ $employee->updated_at?->format('l F d, Y h:i A') ?: 'Unknown' }}</p>
              </div>
            </div>
          </div>
        </div>

        @php
            $pendingMove = \App\Support\EmployeeMovements::pending($employee->id);
            $canTerminate = $employee->status === 'Active';
            $canReinstate = $employee->status === 'Terminated';
        @endphp
        @if($pendingMove)
          <div class="alert alert-warning mt-4 mb-0">
            <strong>{{ \App\Support\EmployeeMovements::LABELS[$pendingMove->type] }}</strong> waiting for head office approval{{ in_array($pendingMove->source, ['attendance', 'overtime'], true) ? ' (from the attendance sheet)' : '' }}
            @if($pendingMove->effective_date) · effective {{ $pendingMove->effective_date->format('d M Y') }} @endif
            @if($pendingMove->reason) · {{ \App\Support\EmployeeMovements::reasonLabel($pendingMove->reason) }} @endif
          </div>
        @endif

        @php
            $M = \App\Support\EmployeeMovements::class;
            $history = \App\Models\EmployeeMovement::with(['requester:id,name', 'decider:id,name', 'field:id,name', 'fromField:id,name'])
                ->where('employee_id', $employee->id)->orderByDesc('effective_date')->orderByDesc('id')->get();
            $lastChange = $history->whereIn('type', [$M::TERMINATION, $M::REINSTATEMENT])->whereIn('status', [$M::APPROVED, $M::PENDING])->sortByDesc('id')->first();
            $decides = $M::canDecide(Auth::user());
            $explain = \App\Support\EmployeeMovementsReport::explain($employee->id);
            $badge = ['approved' => 'bg-label-success', 'pending' => 'bg-label-warning', 'declined' => 'bg-label-secondary', 'cancelled' => 'bg-label-secondary'];
        @endphp
        @if($history->isNotEmpty())
          <div class="card mt-4" id="employmentHistory">
            <h6 class="card-header">Employment history</h6>
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead><tr><th>Date</th><th>What</th><th>Office</th><th>Status</th><th>Reason / note</th><th>By</th><th></th></tr></thead>
                <tbody>
                  @foreach($history as $h)
                    @php
                        $canWithdraw = $h->status === $M::PENDING && $h->id === $lastChange?->id && ($decides || (int) $h->requested_by === (int) Auth::id());
                        $canUndo = $h->status === $M::APPROVED && $decides && $h->id === $lastChange?->id
                            && $employee->status === ($h->type === $M::TERMINATION ? 'Terminated' : 'Active');
                    @endphp
                    <tr>
                      <td>{{ $h->effective_date?->format('d M Y') ?? 'date unknown' }}{{ $h->date_estimated ? ' (est.)' : '' }}</td>
                      <td>{{ $M::LABELS[$h->type] ?? $h->type }}{{ $h->type === $M::TRANSFER ? ' from ' . ($h->fromField?->name ?? '?') : '' }}</td>
                      <td>{{ $h->field?->name }}</td>
                      <td><span class="badge {{ $badge[$h->status] ?? 'bg-label-secondary' }}">{{ $h->status === 'cancelled' ? 'undone / withdrawn' : $h->status }}</span>
                        @if($h->status === 'pending')
                          <div class="small text-muted">counts in HR movements once approved</div>
                        @elseif($h->status === 'approved' && ! empty($explain[$h->id]))
                          <div class="small {{ str_starts_with($explain[$h->id], 'Not counted') ? 'text-danger' : 'text-muted' }}">{{ $explain[$h->id] }}</div>
                        @endif
                      </td>
                      <td class="text-wrap" style="max-width: 22rem">{{ $h->type === $M::TERMINATION ? $M::reasonLabel($h->reason) : '' }}{{ $h->note ? ($h->type === $M::TERMINATION ? ' · ' : '') . $h->note : '' }}</td>
                      <td class="small">{{ $h->requester?->name }}{{ $h->decider && $h->decider->id !== $h->requester?->id ? ' → ' . $h->decider->name : '' }}</td>
                      <td class="text-end">
                        @if($canWithdraw || $canUndo)
                          <button class="btn btn-sm btn-outline-danger py-0" type="button" data-bs-toggle="collapse" data-bs-target="#undo{{ $h->id }}">{{ $canWithdraw ? 'Withdraw' : 'Undo' }}</button>
                        @endif
                      </td>
                    </tr>
                    @if($canWithdraw || $canUndo)
                      <tr class="collapse" id="undo{{ $h->id }}"><td colspan="7">
                        <form method="POST" action="{{ route('employees.movements.undo', $h->id) }}" class="d-flex flex-wrap gap-2 align-items-center"
                              onsubmit="return confirm('{{ $canWithdraw ? 'Withdraw this request?' : 'Undo this ' . strtolower($M::LABELS[$h->type]) . '? It will no longer count anywhere.' }}')">
                          @csrf
                          <span class="small">{{ $canWithdraw ? 'Withdraw the request' : ($h->type === $M::TERMINATION ? 'Terminated by mistake? Undo it (not a re-instatement):' : 'Re-instated by mistake? Undo it:') }}</span>
                          <input type="text" name="why" class="form-control form-control-sm" style="max-width: 20rem" maxlength="150" minlength="5" required placeholder="Why (e.g. wrong guard selected)">
                          <button class="btn btn-sm btn-danger" type="submit">{{ $canWithdraw ? 'Withdraw' : 'Undo' }}</button>
                        </form>
                      </td></tr>
                    @endif
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @endif
        @if(Auth::user()->hasRole(['Manager', 'Invoice']) && $employee->ho_status === 'approved' && ($canTerminate || $canReinstate))
          <div class="card bg-info mt-4">
            <div class="card-body text-end">
              <button
                type="button"
                class="btn btn-danger btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#statusModal">
                <i class="icon-base bx bx-bxs-user-plus me-1"></i>
                {{ $canReinstate ? 'Re-Instate' : 'Terminate' }}
              </button>
            </div>
          </div>

          <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="statusModalLabel">{{ $canReinstate ? 'Re-instate' : 'Terminate' }} {{ $employee->name }}</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form method="POST" action="{{ $canReinstate ? route('employees.reinstate', $employee->id) : route('employees.terminateEmployee', $employee->id) }}" id="statusForm">
                  @csrf
                  <div class="modal-body">
                    <div class="mb-3">
                      <label for="effective_date" class="form-label">{{ $canReinstate ? 'Back at work from' : 'Last day employed' }} *</label>
                      <input type="date" name="effective_date" id="effective_date" max="{{ now()->addDays(90)->toDateString() }}"
                             class="form-control @error('effective_date') is-invalid @enderror" value="{{ old('effective_date', now()->toDateString()) }}" required>
                      <small class="text-muted">Decides which month the {{ $canReinstate ? 're-instatement' : 'termination' }} is reported in.</small>
                      @error('effective_date')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    @if($canTerminate)
                    <div class="mb-3">
                      <label for="reason" class="form-label">Reason *</label>
                      <select name="reason" id="reason" class="form-select @error('reason') is-invalid @enderror" required>
                        <option value="" disabled @selected(! old('reason'))>Choose…</option>
                        @foreach(\App\Support\EmployeeMovements::REASONS as $value => $label)
                          <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                        @endforeach
                      </select>
                      @error('reason')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    @endif
                    <div class="mb-3">
                      <label for="note" class="form-label">Note{{ $canTerminate ? ' (required for "Other")' : '' }}</label>
                      <input type="text" name="note" id="note" maxlength="255" class="form-control @error('note') is-invalid @enderror" value="{{ old('note') }}">
                      @error('note')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    @if(collect($assign_staff)->isNotEmpty())
                    <div class="input-group">
                      <label class="input-group-text" for="staff">{{ __('ASSIGN TO') }}</label>
                      <select name="staff" class="form-select @error('staff') is-invalid @enderror" id="staff">
                        <option value="" selected>Choose…</option>
                        @foreach($assign_staff as $user)
                          @if($user->field_id == Auth::user()->field_id || Auth::user()->hasRole(['Manager']))
                            <option value="{{ $user->id }}" @selected(old('staff') == $user->id)>{{ $user->name }}</option>
                          @endif
                        @endforeach
                      </select>
                    </div>
                    @endif
                  </div>
                  <div class="modal-footer">
                    <button type="submit" class="btn btn-info d-grid w-100" onclick="return confirm('Kindly Confirm?')">
                      {{ $canReinstate ? __('Re-Instate') : __('Terminate') }}
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>

  @endsection

  @section('scripts')
  @if($errors->hasAny(['effective_date', 'reason', 'note', 'staff']))
  <script>
    // The terminate / re-instate form came back with an error: show it again.
    document.addEventListener('DOMContentLoaded', function () {
      var el = document.getElementById('statusModal');
      if (el && window.bootstrap && bootstrap.Modal) { bootstrap.Modal.getOrCreateInstance(el).show(); }
    });
  </script>
  @endif
  @endsection

</x-hr-dashboard>
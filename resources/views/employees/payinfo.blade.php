<x-hr-dashboard>

  @section('side_nav')
    @include('partials.receipt_side_nav')
  @endsection


  @section('content')

  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
      <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
          <h4 class="fw-bold mb-1">Edit Bank Details</h4>
          <p class="text-muted mb-0">Update bank details for {{ $employee_pay_info->employee->name }}.</p>
        </div>
        <div class="text-md-end">
          <a href="{{ url('employees', $employee_pay_info->employee_id) }}" class="btn btn-outline-secondary btn-sm mb-2">
            <i class="bx bx-user me-1"></i> Employee Details
          </a>
          @if(Auth::user()->hasRole(['Finance Manager', 'Manager']))
            <a href="{{ url('employeesSalary', $employee_pay_info->employee_id) }}" class="btn btn-outline-secondary btn-sm mb-2">
              <i class="bx bx-money-withdraw me-1"></i> Salaries
            </a>
          @endif
        </div>
      </div>
    </div>

    <!-- <div class="row mb-3">
      <div class="col-12">
        <ul class="nav nav-pills flex-column flex-md-row gap-2">
          <li class="nav-item">
            <a class="nav-link" href="{{ url('employees', $employee_pay_info?->employee_id) }}"><i class="bx bx-user me-1"></i> Employee Details</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="javascript:void(0);"><i class="bx bxs-comment-detail me-1"></i> Payment Info</a>
          </li>
        </ul>
      </div>
    </div> -->

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card h-80 bg-info text-white">
          <div class="card-body text-center">
            <h5 class="card-title mb-1 text-white">{{ $employee_pay_info->employee->name }}</h5>
            <p class="text-muted mb-3">{{ $employee_pay_info->employee->role?->name ?? 'Employee' }}</p>
            <span class="badge bg-primary mb-3">{{ $employee_pay_info->employee->payment_type ?? 'Payment type' }}</span>
            <dl class="row text-start">
              <dt class="col-sm-5 text-muted">Status</dt>
              <dd class="col-sm-7 mb-3">{{ $employee_pay_info->employee->status ?? 'Unknown' }}</dd>
              <dt class="col-sm-5 text-muted">Bank</dt>
              <dd class="col-sm-7 mb-3">{{ $employee_pay_info->bank?->name ?? '-' }}</dd>
              <dt class="col-sm-5 text-muted">Account</dt>
              <dd class="col-sm-7 mb-0">{{ $employee_pay_info->acc_number ?? '-' }}</dd>
            </dl>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card mb-4">
          <div class="card-header">
            <strong>Bank  Details</strong>
          </div>
          <div class="card-body">
            @php($payInfoTerminated = strtolower($employee_pay_info->employee->status ?? '') === 'terminated')
            @if ($payInfoTerminated)
              <div class="alert alert-warning border-0 shadow-sm mb-3">
                This employee is <strong>{{ $employee_pay_info->employee->status }}</strong>. Data entry is disabled for terminated employees; use <strong>Re-instate</strong> to bring them back.
              </div>
            @endif
            <form action="/employeesPayInfoUpdate/{{ $employee_pay_info->id }}" method="POST">
              @csrf
              @method('PUT')
              <fieldset {{ $payInfoTerminated ? 'disabled' : '' }}>
              <div class="row g-3" id="payment_field">
                <div class="col-md-6">
                  <label for="payment_type" class="form-label"><strong>Payment Type *</strong></label>
                  <select name="payment_type" class="form-select @error('payment_type') is-invalid @enderror" id="payment_type">
                    <option @if ($employee_pay_info->employee->payment_type == 'Bank') selected @endif value="Bank">Bank</option>
                    <option @if ($employee_pay_info->employee->payment_type == 'Cash') selected @endif value="Cash">Cash</option>
                  </select>
                  @error('payment_type')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>

                <div class="col-md-6">
                  <label for="bank_id" class="form-label"><strong>Bank *</strong></label>
                  <select name="bank_id" class="form-select @error('bank_id') is-invalid @enderror" id="bank_id">
                    <option value="0">Select Bank</option>
                    @foreach($banks as $bank)
                      <option @if($bank->id == $employee_pay_info->bank_id) selected @endif value="{{ $bank->id }}">{{ $bank->name }}</option>
                    @endforeach
                  </select>
                  @error('bank_id')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>

                <div class="col-md-6">
                  <label for="acc_number" class="form-label"><strong>Account Number *</strong></label>
                  <input type="text" id="acc_number" name="acc_number" class="form-control @error('acc_number') is-invalid @enderror" placeholder="Your Account Number" value="{{ $employee_pay_info->acc_number }}" />
                  @error('acc_number')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>

                <div class="col-md-6">
                  <label for="branch" class="form-label"><strong>Branch *</strong></label>
                  <input type="text" id="branch" name="branch" class="form-control @error('branch') is-invalid @enderror" placeholder="Branch" value="{{ $employee_pay_info->branch }}" />
                  @error('branch')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>

                <div class="col-md-6">
                  <label for="branch_code" class="form-label"><strong>Branch Code</strong></label>
                  <input type="text" id="branch_code" name="branch_code" class="form-control @error('branch_code') is-invalid @enderror" placeholder="Branch Code" value="{{ $employee_pay_info->branch_code }}" />
                  @error('branch_code')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>

                <!-- <div class="col-md-6">
                  <label for="tin_number" class="form-label"><strong>TIN Number</strong></label>
                  <input type="text" id="tin_number" name="tin_number" class="form-control @error('tin_number') is-invalid @enderror" placeholder="TIN Number" value="{{ $employee_pay_info->tin_number }}" />
                  @error('tin_number')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div> -->

                <!-- <div class="col-md-6">
                  <label for="ssnit_number" class="form-label"><strong>SSNIT Number</strong></label>
                  <input type="text" id="ssnit_number" name="ssnit_number" class="form-control @error('ssnit_number') is-invalid @enderror" placeholder="SSNIT Number" value="{{ $employee_pay_info->ssnit_number }}" />
                  @error('ssnit_number')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div> -->
              </div>

              <div class="mt-4 text-end">
                <button type="submit" class="btn btn-primary" {{ $payInfoTerminated ? 'disabled' : '' }}>Update</button>
              </div>
              </fieldset>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  @endsection


</x-hr-dashboard>
<x-hr-dashboard>


  @section('side_nav')
    @include('partials.receipt_side_nav')
  @endsection


  @section('content')

  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
      <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
          <h4 class="fw-bold mb-1">Payment info</h4>
          <p class="text-muted mb-0">View payroll payment details for {{ $employee_pay_info->employee?->name }}.</p>
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
           @if($employee_pay_info->employee?->ho_status !== 'approved'  || (Auth::user()->department?->name == 'HR' && Auth::user()->role?->name == 'Manager') || Auth::user()->hasRole(['Finance Manager']) )
            <a href="{{ url('employeesPayInfo/'.$employee_pay_info->employee_id) }}" class="btn btn-dark btn-sm mb-2">
              <i class="bx bx-edit-alt me-1"></i> Edit Bank Details
            </a>
          @endif
        </div>
      </div>
    </div>

    <div class="row g-4">


        @if($employee_pay_info->employee?->payment_type == 'Bank')
      <div class="col-lg-12">
        <div class="card mb-4 bg-info text-white">
          <div class="card-header text-white">
            <strong>Bank details</strong>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <small class="text-muted">Bank</small>
                <p class="fw-semibold mb-0">{{ $employee_pay_info->bank?->name ?? '-' }}</p>
              </div>
              <div class="col-md-6">
                <small class="text-muted">Account number</small>
                <p class="fw-semibold mb-0">{{ $employee_pay_info->acc_number ?? '-' }}</p>
              </div>
              <div class="col-md-6">
                <small class="text-muted">Branch</small>
                <p class="fw-semibold mb-0">{{ $employee_pay_info->branch ?? '-' }}</p>
              </div>
              <div class="col-md-6">
                <small class="text-muted">Branch code</small>
                <p class="fw-semibold mb-0">{{ $employee_pay_info->branch_code ?? '-' }}</p>
              </div>
              <div class="col-md-6">
                <small class="text-muted">TIN number</small>
                <p class="fw-semibold mb-0">{{ $employee_pay_info->tin_number ?? '-' }}</p>
              </div>
              <div class="col-md-6">
                <small class="text-muted">SSNIT number</small>
                <p class="fw-semibold mb-0">{{ $employee_pay_info->ssnit_number ?? '-' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      @else

        <div class="row p-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-danger"> <i class="icon-base bx bx-error-alt"></i> Payment Type is Cash  </h5>
                    </div>      
                </div>
            </div>
        </div>
      @endif

    </div>
  </div>

  @endsection


</x-hr-dashboard>
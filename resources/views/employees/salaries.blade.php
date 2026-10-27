<x-hr-dashboard>

  @section('side_nav')
    @include('partials.receipt_side_nav')
  @endsection
  
    @php
        $totalGross      = $employee->salaries->sum('gross_salary');
        $totalDeductions = $employee->salaries->sum('total_deductions');
        $totalNet        = $employee->salaries->sum('net_salary');
        $salaryCount     = $employee->salaries->count();
        $annualTax       = $employee->salaries->sum('tax');
        $annualSsnit     = $employee->salaries->sum(function ($salary) {
            return (float) ($salary->ssnit_tier1_0_5 ?? 0) + (float) ($salary->ssnit_tier2_5 ?? 0);
        });
    @endphp

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">
    @endsection

  @section('content')

  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
      <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
          <h4 class="fw-bold mb-1">Employee salaries</h4>
          <p class="text-muted mb-0">All salary records for {{ $employee->name }}.</p>
        </div>
        <div class="text-md-end">
          <a href="{{ url('employees', $employee->id) }}" class="btn btn-outline-secondary btn-sm mb-2">
            <i class="bx bx-user me-1"></i> Employee Details
          </a>
          <a href="{{ url('employeesViewPayInfo', $employee->id) }}" class="btn btn-outline-secondary btn-sm mb-2">
            <i class="bx bxs-comment-detail me-1"></i> Bank Details
          </a>
        </div>
      </div>
    </div>


    <div class="row g-4">

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="mb-0 fw-bold">Tax & SSNIT</h5>
                        <span class="badge {{ $employee->tax_button == 'on' || $employee->ssnit_button == 'on' ? 'bg-success' : 'bg-secondary' }}">
                            {{ $employee->tax_button == 'on' || $employee->ssnit_button == 'on' ? 'Active' : 'None' }}
                        </span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded bg-light">
                                <small class="text-muted d-block mb-1">Tax</small>
                                <strong>{{ $employee->tax_button == 'on' ? 'Enabled' : 'Not enabled' }}</strong>
                                @if($employee->tax_button == 'on')
                                    <div class="mt-2 small text-muted">TIN: {{ $employee->paymentInfo?->tin_number ?: 'Not available' }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded bg-light">
                                <small class="text-muted d-block mb-1">SSNIT</small>
                                <strong>{{ $employee->ssnit_button == 'on' ? 'Enabled' : 'Not enabled' }}</strong>
                                @if($employee->ssnit_button == 'on')
                                    <div class="mt-2 small text-muted">SSNIT #: {{ $employee->paymentInfo?->ssnit_number ?: 'Not available' }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-dark text-white h-100">
            <div class="card-body">
                <p class="mb-1"><strong>TOTAL GROSS SALARY</strong></p>
                <h4 class="card-title mb-2 text-white"><strong>GH&#x20B5; {{ number_format($totalGross, 2) }}</strong></h4>
                <small class="fw-medium">PAYSLIPS : {{ $salaryCount }}</small>
            </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-danger text-white h-100">
            <div class="card-body">
                <p class="mb-1"><strong>TOTAL DEDUCTIONS</strong></p>
                <h4 class="card-title mb-2 text-white"><strong>GH&#x20B5; {{ number_format($totalDeductions, 2) }}</strong></h4>
                <small class="fw-medium">PAYSLIPS : {{ $salaryCount }}</small>
            </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
            <div class="card-body">
                <p class="mb-1"><strong>TOTAL NET SALARY</strong></p>
                <h4 class="card-title mb-2 text-white"><strong>GH&#x20B5; {{ number_format($totalNet, 2) }}</strong></h4>
                <small class="fw-medium">PAYSLIPS : {{ $salaryCount }}</small>
            </div>
            </div>
        </div>
    </div>

      <div class="col-lg-12">
        <div class="card mb-4">
          <div class="card-header">
            <strong>Employee salaries</strong>
          </div>
          <div class="card-body">
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <div class="p-3 rounded bg-warning text-dark h-100">
                  <small class="text-dark-50 d-block mb-1">Total Tax YTD</small>
                  <strong class="fs-5">GH₵ {{ number_format((float) $annualTax, 2) }}</strong>
                </div>
              </div>
              <div class="col-md-6">
                <div class="p-3 rounded bg-info text-dark h-100">
                  <small class="text-dark-50 d-block mb-1">Total SSNIT YTD</small>
                  <strong class="fs-5">GH₵ {{ number_format((float) $annualSsnit, 2) }}</strong>
                </div>
              </div>
            </div>
            <table id="myTable" class="display w-100">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Salary Month</th>
                  <th>Client</th>
                  <th>Location</th>
                  <th>Tax Deduction</th>
                  <th>SSNIT Deduction</th>
                  <th>Gross Salary</th>
                  <th>Deductions</th>
                  <th>Total</th>
                  <th>Show</th>
                  <th>Edit</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @foreach ($employee->salaries as $salary)
                  <tr>
                    <td>{{ $salary->id }}</td>
                    <td>{{ $salary->salary_month?->format('F, Y') }}</td>
                    <td>{{ $salary->client?->name }}{{ $salary->client?->business_name }}</td>
                    <td>{{ $salary->location }}</td>
                    <td>GH&#x20B5; {{ number_format((float) ($salary->tax ?? 0), 2) }}</td>
                    <td>GH&#x20B5; {{ number_format((float) (($salary->ssnit_tier1_0_5 ?? 0) + ($salary->ssnit_tier2_5 ?? 0)), 2) }}</td>
                    <td>GH&#x20B5; {{ number_format((float) ($salary->gross_salary ?? 0), 2) }}</td>
                    <td>GH&#x20B5; {{ number_format((float) ($salary->total_deductions ?? 0), 2) }}</td>
                    <td>GH&#x20B5; {{ number_format((float) ($salary->cost_to_company ?? 0), 2) }}</td>
                    <td><a class="btn btn-dark btn-sm" href="/salaries/{{ $salary->id }}"><i class="icon-base bx bxs-bullseye"></i></a></td>
                    @if ($employee->status == 'Active')
                      <td><a class="btn btn-dark btn-sm" href="/salaries/{{ $salary->id }}/edit"><i class="icon-base bx bx-edit-alt"></i></a></td>
                    @else
                      <td></td>
                    @endif
                    <td>
                        <a class="btn btn-dark btn-sm" href="{{ url('printPayslip', $salary->id) }}" target="_blank">
                            <i class="icon-base bx bxs-printer"></i>
                        </a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  @endsection
@section('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.dataTables.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.html5.min.js"></script>


    <script>
        new DataTable('#myTable', {
            responsive: true,

            layout: {
                topStart: {
                    buttons: ['excelHtml5', 'pdfHtml5']
                }
            }
        });
    </script>
    @endsection


</x-hr-dashboard>
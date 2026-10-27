<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">

    <link href="https://cdn.datatables.net/columncontrol/1.1.1/css/columnControl.dataTables.min.css" rel="stylesheet">
    <style>
        .emp-card { cursor: pointer; border: 2px solid transparent; transition: transform .12s, box-shadow .12s, border-color .12s; }
        .emp-card.border-danger { border-color: rgba(var(--bs-danger-rgb), .35) !important; }
        .emp-card.border-warning { border-color: rgba(var(--bs-warning-rgb), .45) !important; }
        .emp-card:hover { transform: translateY(-1px); box-shadow: 0 .25rem .75rem rgba(0,0,0,.12); }
        .emp-card.active { border-color: var(--bs-primary, #696cff) !important; box-shadow: 0 0 0 .2rem rgba(105,108,255,.35); }
        .emp-card:focus-visible, .emp-chip:focus-visible { outline: 2px solid var(--bs-primary, #696cff); outline-offset: 2px; }
        .emp-chip { cursor: pointer; border-radius: .25rem; padding: 0 .25rem; }
        .emp-chip:hover { text-decoration: underline; }
        .emp-chip.active { background: rgba(255,255,255,.2); text-decoration: underline; }
        .pay-flag { font-size: .7rem; vertical-align: middle; }
    </style>
    @endsection


  @section('side_nav')
    @include('partials.receipt_side_nav')
  @endsection


  @section('content')

  <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h3 class="card-header mb-0"> <i class="icon-base bx bxs-user-account"></i> All Employees </h3>
                    <div class="d-flex flex-wrap gap-2">
                        @if(Auth::user()->hasRole(['Finance Manager']))
                            <a href="{{ route('pay-priority.index') }}" class="btn btn-outline-danger"><i class="bx bxs-bolt me-1"></i> Pay priority rules</a>
                        @endif
                        <a href="{{ route('employees.import') }}" class="btn btn-outline-primary"><i class="bx bx-upload me-1"></i> Bulk upload</a>
                        <a href="{{ route('employees.bulk-update') }}" class="btn btn-outline-primary"><i class="bx bx-edit me-1"></i> Bulk update</a>
                        <a href="{{ route('employees.bulk-update.template') }}" id="bulkUpdateDownload" class="btn btn-primary"
                           title="Downloads the employees currently shown (filters, cards, search), ready to edit and upload on Bulk update">
                            <i class="bx bx-download me-1"></i> Download for bulk update
                        </a>
                    </div>
                </div>
            </div>
        </div><br>



        @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']) || (Auth::user()->department?->name == 'HR' && Auth::user()->role?->name == 'Manager'))
        <div class="row mb-4">
            <div class="col-lg-12 col-md-6 mb-4 mb-md-0">
                <div class="card h-100 bg-dark text-white emp-card js-emp-card" data-field="" role="button" tabindex="0" title="Show employees in all field offices">
                    <div class="card-body">
                            <p class="mb-1"><strong> ACTIVE EMPLOYEES </strong> </p>
                            <h4 class="card-title mb-3 text-white emp-chip js-emp-chip" data-field="" data-status="Active" role="button" tabindex="0" title="Active in all field offices"><strong> {{ $activeEmployees }}  </strong> </h4>
                            <small class="fw-medium emp-chip js-emp-chip" data-field="" data-status="Terminated" role="button" tabindex="0" title="Terminated in all field offices"> TERMINATED EMPLOYEES : {{ $terminatedEmployees }}  </small> <br> <hr>
                            <small class="fw-medium"> TOTAL EMPLOYEES : {{ $activeEmployees + $terminatedEmployees }}  </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-2">
                <div class="card h-100 bg-dark text-white emp-card js-emp-card" data-field="1" role="button" tabindex="0" title="Show employees in Accra">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> ACCRA </strong> </p>
                        <h4 class="card-title mb-3 text-white emp-chip js-emp-chip" data-field="1" data-status="Active" role="button" tabindex="0" title="Active in Accra"><strong> {{ $employeeAccraActive }}  </strong> </h4>
                        <small class="fw-medium emp-chip js-emp-chip" data-field="1" data-status="Terminated" role="button" tabindex="0" title="Terminated in Accra"> TERMINATED : {{ $employeeAccraTerminated }} </small>  <br> <hr>
                        <small class="fw-medium"> TOTAL EMPLOYEES : {{ $employeeAccraActive + $employeeAccraTerminated }} </small> 

                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div class="card h-100 bg-dark text-white emp-card js-emp-card" data-field="2" role="button" tabindex="0" title="Show employees in Botwe">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> BOTWE </strong></p>
                        <h4 class="card-title mb-3 text-white emp-chip js-emp-chip" data-field="2" data-status="Active" role="button" tabindex="0" title="Active in Botwe"><strong> {{ $employeeBotweActive }} </strong> </h4>
                        <small class="fw-medium emp-chip js-emp-chip" data-field="2" data-status="Terminated" role="button" tabindex="0" title="Terminated in Botwe"> TERMINATED : {{ $employeeBotweTerminated }} </small>  <br> <hr>
                        <small class="fw-medium"> TOTAL EMPLOYEES : {{ $employeeBotweActive + $employeeBotweTerminated }} </small>

                    </div>
                </div>
            </div>


            <div class="col-lg-2">
                <div class="card h-100 bg-dark text-white emp-card js-emp-card" data-field="7" role="button" tabindex="0" title="Show employees in Shaihills">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> SHAIHILLS </strong></p>
                        <h4 class="card-title mb-3 text-white emp-chip js-emp-chip" data-field="7" data-status="Active" role="button" tabindex="0" title="Active in Shaihills"><strong> {{ $employeeShyhillsActive }} </strong> </h4>
                        <small class="fw-medium emp-chip js-emp-chip" data-field="7" data-status="Terminated" role="button" tabindex="0" title="Terminated in Shaihills"> TERMINATED : {{ $employeeShyhillsTerminated }} </small> <br> <hr>
                        <small class="fw-medium"> TOTAL EMPLOYEES : {{ $employeeShyhillsActive + $employeeShyhillsTerminated }} </small> 
                    </div>
                </div>
            </div>
            <div class="col-lg-2">
                <div class="card h-100 bg-dark text-white emp-card js-emp-card" data-field="3" role="button" tabindex="0" title="Show employees in Tema">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> TEMA </strong></p>
                        <h4 class="card-title mb-3 text-white emp-chip js-emp-chip" data-field="3" data-status="Active" role="button" tabindex="0" title="Active in Tema"><strong> {{ $employeeTemaActive }} </strong> </h4>
                        <small class="fw-medium emp-chip js-emp-chip" data-field="3" data-status="Terminated" role="button" tabindex="0" title="Terminated in Tema"> TERMINATED : {{ $employeeTemaTerminated }} </small> <br> <hr>
                        <small class="fw-medium"> TOTAL EMPLOYEES : {{ $employeeTemaActive + $employeeTemaTerminated }} </small> 
                    </div>
                </div>
            </div>



            <div class="col-lg-2">
                <div class="card h-100 bg-dark text-white emp-card js-emp-card" data-field="4" role="button" tabindex="0" title="Show employees in Takoradi">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1">TAKORADI</p>
                        <h4 class="card-title mb-3 text-white emp-chip js-emp-chip" data-field="4" data-status="Active" role="button" tabindex="0" title="Active in Takoradi"> {{ $employeeTakoradiActive }} </h4>
                        <small class="fw-medium emp-chip js-emp-chip" data-field="4" data-status="Terminated" role="button" tabindex="0" title="Terminated in Takoradi"> TERMINATED : {{ $employeeTakoradiTerminated }} </small> <br> <hr>
                        <small class="fw-medium"> TOTAL EMPLOYEES : {{ $employeeTakoradiActive + $employeeTakoradiTerminated }} </small> 
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div class="card h-100 bg-dark text-white emp-card js-emp-card" data-field="5" role="button" tabindex="0" title="Show employees in Koforidua">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"> <strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3 text-white emp-chip js-emp-chip" data-field="5" data-status="Active" role="button" tabindex="0" title="Active in Koforidua"> {{ $employeeKoforiduaActive }} </h4>
                        <small class="fw-medium emp-chip js-emp-chip" data-field="5" data-status="Terminated" role="button" tabindex="0" title="Terminated in Koforidua"> TERMINATED : {{ $employeeKoforiduaTerminated }} </small> <br> <hr>
                        <small class="fw-medium"> TOTAL EMPLOYEES : {{ $employeeKoforiduaActive + $employeeKoforiduaTerminated }} </small> 
                    </div>
                </div>
            </div>


            <div class="col-lg-2 m-3">
                <div class="card h-100 bg-dark text-white emp-card js-emp-card" data-field="6" role="button" tabindex="0" title="Show employees in Kumasi">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3 text-white emp-chip js-emp-chip" data-field="6" data-status="Active" role="button" tabindex="0" title="Active in Kumasi"> {{ $employeeKumasiActive }} </h4>
                        <small class="fw-medium emp-chip js-emp-chip" data-field="6" data-status="Terminated" role="button" tabindex="0" title="Terminated in Kumasi"> TERMINATED : {{ $employeeKumasiTerminated }} </small> <br> <hr>
                        <small class="fw-medium"> TOTAL EMPLOYEES : {{ $employeeKumasiActive + $employeeKumasiTerminated }} </small> 
                    </div>
                </div>
            </div>

        </div> <br>
        @elseif(Auth::user()->field?->name == 'Accra')
      
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div class="card h-100 bg-dark text-white emp-card js-emp-card" data-field="1" role="button" tabindex="0" title="Show employees in Accra">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> ACCRA </strong> </p>
                        <h4 class="card-title mb-3 text-white emp-chip js-emp-chip" data-field="1" data-status="Active" role="button" tabindex="0" title="Active in Accra"><strong> {{ $employeeAccraActive }}  </strong> </h4>
                        <small class="fw-medium emp-chip js-emp-chip" data-field="1" data-status="Terminated" role="button" tabindex="0" title="Terminated in Accra"> TERMINATED : {{ $employeeAccraTerminated }} </small>  <br> <hr>
                        <small class="fw-medium"> TOTAL EMPLOYEES : {{ $employeeAccraActive + $employeeAccraTerminated }} </small> 
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(Auth::user()->field?->name == 'Botwe')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div class="card h-100">
                    <div class="card-body bg-dark text-white">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> BOTWE </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ $employeeBotweActive }} </strong> </h4>
                        <small class="fw-medium"> TERMINATED : {{ $employeeBotweTerminated }} </small>  <br> <hr>
                        <small class="fw-medium"> TOTAL EMPLOYEES : {{ $employeeBotweActive + $employeeBotweTerminated }} </small>
                    </div>
                </div>
            </div>
        </div>
        @endif

          @if(Auth::user()->field?->name == 'Tema')
        <div class="row">
            <div class="col-xxl-6 mb-6">
                <div class="card h-100 bg-dark text-white emp-card js-emp-card" data-field="7" role="button" tabindex="0" title="Show employees in Shaihills">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> SHAIHILLS </strong></p>
                        <h4 class="card-title mb-3 text-white emp-chip js-emp-chip" data-field="7" data-status="Active" role="button" tabindex="0" title="Active in Shaihills"><strong> {{ $employeeShyhillsActive }} </strong> </h4>
                        <small class="fw-medium emp-chip js-emp-chip" data-field="7" data-status="Terminated" role="button" tabindex="0" title="Terminated in Shaihills"> TERMINATED : {{ $employeeShyhillsTerminated }} </small> <br> <hr>
                        <small class="fw-medium"> TOTAL EMPLOYEES : {{ $employeeShyhillsActive + $employeeShyhillsTerminated }} </small> 
                    </div>
                </div>
            </div>

            <div class="col-xxl-6 mb-6 order-0">
                <div class="card h-100 bg-dark text-white emp-card js-emp-card" data-field="3" role="button" tabindex="0" title="Show employees in Tema">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> TEMA </strong></p>
                        <h4 class="card-title mb-3 text-white emp-chip js-emp-chip" data-field="3" data-status="Active" role="button" tabindex="0" title="Active in Tema"><strong> {{ $employeeTemaActive }} </strong> </h4>
                        <small class="fw-medium emp-chip js-emp-chip" data-field="3" data-status="Terminated" role="button" tabindex="0" title="Terminated in Tema"> TERMINATED : {{ $employeeTemaTerminated }} </small> <br> <hr>
                        <small class="fw-medium"> TOTAL EMPLOYEES : {{ $employeeTemaActive + $employeeTemaTerminated }} </small> 
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Takoradi')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div class="card h-100 bg-dark text-white emp-card js-emp-card" data-field="4" role="button" tabindex="0" title="Show employees in Takoradi">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1">TAKORADI</p>
                        <h4 class="card-title mb-3 text-white emp-chip js-emp-chip" data-field="4" data-status="Active" role="button" tabindex="0" title="Active in Takoradi"> {{ $employeeTakoradiActive }} </h4>
                        <small class="fw-medium emp-chip js-emp-chip" data-field="4" data-status="Terminated" role="button" tabindex="0" title="Terminated in Takoradi"> TERMINATED : {{ $employeeTakoradiTerminated }} </small> <br> <hr>
                        <small class="fw-medium"> TOTAL EMPLOYEES : {{ $employeeTakoradiActive + $employeeTakoradiTerminated }} </small> 
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Koforidua')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div class="card h-100 bg-dark text-white emp-card js-emp-card" data-field="5" role="button" tabindex="0" title="Show employees in Koforidua">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"> <strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3 text-white emp-chip js-emp-chip" data-field="5" data-status="Active" role="button" tabindex="0" title="Active in Koforidua"> {{ $employeeKoforiduaActive }} </h4>
                        <small class="fw-medium emp-chip js-emp-chip" data-field="5" data-status="Terminated" role="button" tabindex="0" title="Terminated in Koforidua"> TERMINATED : {{ $employeeKoforiduaTerminated }} </small> <br> <hr>
                        <small class="fw-medium"> TOTAL EMPLOYEES : {{ $employeeKoforiduaActive + $employeeKoforiduaTerminated }} </small> 
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Kumasi')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div class="card h-100 bg-dark text-white emp-card js-emp-card" data-field="6" role="button" tabindex="0" title="Show employees in Kumasi">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3 text-white emp-chip js-emp-chip" data-field="6" data-status="Active" role="button" tabindex="0" title="Active in Kumasi"> {{ $employeeKumasiActive }} </h4>
                        <small class="fw-medium emp-chip js-emp-chip" data-field="6" data-status="Terminated" role="button" tabindex="0" title="Terminated in Kumasi"> TERMINATED : {{ $employeeKumasiTerminated }} </small> <br> <hr>
                        <small class="fw-medium"> TOTAL EMPLOYEES : {{ $employeeKumasiActive + $employeeKumasiTerminated }} </small> 
                    </div>
                </div>
            </div>
        </div>
        @endif
        <br><br>



        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card h-100 border-danger emp-card js-emp-card" data-priority="urgent" role="button" tabindex="0" title="Active employees to pay first">
                    <div class="card-body py-3">
                        <p class="mb-1 text-danger fw-semibold"><i class="bx bxs-bolt"></i> Pay first</p>
                        <h4 class="mb-0">{{ $payFirstCount }}</h4>
                        <small class="text-muted">active employees</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card h-100 border-warning emp-card js-emp-card" data-priority="priority" role="button" tabindex="0" title="Active employees to pay early">
                    <div class="card-body py-3">
                        <p class="mb-1 text-warning fw-semibold"><i class="bx bx-time-five"></i> Pay early</p>
                        <h4 class="mb-0">{{ $payEarlyCount }}</h4>
                        <small class="text-muted">active employees</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- What the cards are filtering right now. --}}
        <div id="empFilterBar" class="alert alert-primary py-2 d-none d-flex align-items-center gap-2" aria-live="polite">
            <span>Showing: <strong id="empFilterText"></strong></span>
            <button type="button" class="btn btn-sm btn-link p-0 ms-auto" id="empFilterClear">Clear card filters</button>
        </div>

        <div class="card-header  ml-2  d-none d-lg-block">
            @include('flash-messages')
        </div>

        <div class="row">
            <div class="col">
                <div class="card"> 
                    <div class="card-body"> 
                    <div class="table-responsive text-normal-dark"> 
                    <table id="myTable" class="display" data-source="{{ route('employees.data') }}">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th> Employee ID </th>
                                <th>Name</th>
                                <th>Gender</th>
                                <th>Number</th>
                                <th> Employment Date </th>
                                <th> Department </th>
                                <th>Role</th>
                                <th>Field Office</th>
                                <th>Client </th>
                                <th> Location </th>
                                <th>Payment Type</th>
                                <th> Bank </th>
                                <th>Account No.</th>
                                <th>Status</th>
                                <th>Status Date</th>
                                <th>TAX</th>
                                <th>TIN</th>
                                <th>SSNIT</th>
                                <th>SSNIT #</th>
                                <th>Basic</th>
                                <th>Allowance</th>
                                <th> Created </th>
                                <th> Period </th>
                                <th> Updated</th>
                                <th> Period </th>
                                <th>Staff</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                         {{-- Rows are loaded by the server-side DataTable (employees.data). --}}
                        </tbody>
                    </table>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
  <!-- / Content -->

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

    <script src="https://cdn.datatables.net/columncontrol/1.1.1/js/dataTables.columnControl.min.js"></script>

     
    @include('partials.dt_range')
    @include('partials.dt_compact')

    <script>
        $(function () {
            // 1. Mount BEFORE creating the table: it inserts the bar above the table.
            //    range_by is whitelisted server-side (EMPLOYEE_RANGE_COLUMNS).
            const range = DtRange.mount('#myTable', {
                label: 'Date',
                type: 'date',
                presets: ['today', 'week', 'month', 'lastmonth', 'year'],
                exportUrl: '{{ route('employees.export') }}',
                extraParams: () => filters,   // "Export all (filtered)" follows the cards too
                rangeBy: [
                    { value: 'date_of_joining', label: 'Employment date' },
                    { value: 'status_date',     label: 'Status date (terminated / reinstated)' },
                    { value: 'created_at',      label: 'Date created' },
                ],
            });

            const canViewSalary = @json($canViewSalary);

            // Summary-card filters (same interaction as the categoryClients cards).
            const FIELD_NAMES = @json(array_flip($offices));
            const PRIORITY_NAMES = { urgent: 'Pay first', priority: 'Pay early' };
            const filters = { field_id: '', status: '', priority: '' };

            // 2. Send the range with every request.
            const table = new DataTable('#myTable', {
                processing: true,
                serverSide: true,
                pageLength: 25,
                searchDelay: 500,   // date search uses DATE_FORMAT() LIKE (no index): debounce typing
                // DtCompact.ajax: compacts AFTER ColumnControl adds its column searches (see partial).
                ajax: DtCompact.ajax({
                    url: $('#myTable').data('source'),
                    data: function (d) {
                        range.append(d);
                        d.field_id = filters.field_id;
                        d.status = filters.status;
                        d.priority = filters.priority;
                    },
                }),
                order: [[22, 'desc']],
                columnControl: [{
                    target: 1,
                    content: ['search']
                }],
                columns: [
                    { data: 'row_number', orderable: false, searchable: false, render: function (data, type, row, meta) { return meta.row + 1 + meta.settings._iDisplayStart; } },
                    { data: 'employee_id' }, { data: 'name' }, { data: 'gender' }, { data: 'phone_number' },
                    { data: 'date_of_joining' }, { data: 'department' }, { data: 'role' }, { data: 'field' },
                    { data: 'client', orderable: true }, { data: 'location' }, { data: 'payment_type' }, { data: 'bank' },
                    { data: 'account_number' }, { data: 'status' }, { data: 'status_date' },
                    { data: 'tax', orderable: false }, { data: 'tin', orderable: false },
                    { data: 'ssnit', orderable: false }, { data: 'ssnit_number', orderable: false },
                    { data: 'basic_salary', visible: canViewSalary, searchable: canViewSalary, orderable: canViewSalary },
                    { data: 'allowances',   visible: canViewSalary, searchable: canViewSalary, orderable: canViewSalary },
                    { data: 'created_at' }, { data: 'created_period', orderable: false },
                    { data: 'updated_at' }, { data: 'updated_period', orderable: false },
                    { data: 'staff' }, { data: 'action', orderable: false, searchable: false }
                ],
                layout: {
                    topStart: {
                        buttons: [
                            {
                                extend: 'pageLength',
                                text: 'Show',
                                className: 'btn btn-secondary',
                                Options: [10, 25, 50, 100, 250, 500, 1000, 2000],
                            },
                            // The built-in Excel button is intentionally gone: in server-side mode it
                            // only exports the visible page. Use "Export all (filtered)" in the bar above.
                        ]
                    }
                },
            });

            // 3. Wire pickers / presets / "filter by" / export button to the table.
            range.bind(table);

            // 4. Clickable summary cards.
            function paint() {
                $('.js-emp-card[data-field]').each(function () {
                    const f = String($(this).data('field'));
                    $(this).toggleClass('active', f !== '' && f === filters.field_id && !filters.status);
                });
                $('.js-emp-card[data-priority]').each(function () {
                    $(this).toggleClass('active', $(this).data('priority') === filters.priority);
                });
                $('.js-emp-chip').each(function () {
                    $(this).toggleClass('active', String($(this).data('field')) === filters.field_id
                        && $(this).data('status') === filters.status);
                });
                $('.js-emp-card, .js-emp-chip').each(function () {
                    $(this).attr('aria-pressed', $(this).hasClass('active') ? 'true' : 'false');
                });

                const bits = [];
                if (filters.field_id) bits.push(FIELD_NAMES[filters.field_id] || ('Field ' + filters.field_id));
                if (filters.status) bits.push(filters.status);
                if (filters.priority) bits.push(PRIORITY_NAMES[filters.priority]);
                $('#empFilterText').text(bits.join(' · '));
                $('#empFilterBar').toggleClass('d-none', bits.length === 0);
            }

            function setFilters(next) {
                Object.assign(filters, next);
                paint();
                table.draw();
            }

            // Field card: toggle that office (the overall card clears office + status).
            $('.js-emp-card[data-field]').on('click', function () {
                const id = String($(this).data('field'));
                if (id === '') return setFilters({ field_id: '', status: '' });
                setFilters(filters.field_id === id && !filters.status ? { field_id: '', status: '' } : { field_id: id, status: '' });
            });
            // Active / Terminated figure inside a card: office + status.
            $('.js-emp-chip').on('click', function (e) {
                e.stopPropagation();
                const id = String($(this).data('field')), st = $(this).data('status');
                const same = filters.field_id === id && filters.status === st;
                setFilters(same ? { field_id: '', status: '' } : { field_id: id, status: st });
            });
            // Pay first / Pay early cards.
            $('.js-emp-card[data-priority]').on('click', function () {
                const v = $(this).data('priority');
                setFilters({ priority: filters.priority === v ? '' : v });
            });
            $('.js-emp-card, .js-emp-chip').on('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); }
            });
            $('#empFilterClear').on('click', () => setFilters({ field_id: '', status: '', priority: '' }));

            // 5. Bulk update download: the same employees the table shows (all current filters).
            $('#bulkUpdateDownload').on('click', function (e) {
                e.preventDefault();
                const p = table.ajax.params() || {};
                const q = new URLSearchParams();
                ['from', 'to', 'range_by', 'field_id', 'status', 'priority'].forEach(k => { if (p[k]) q.set(k, p[k]); });
                if (p.search && p.search.value) q.set('search[value]', p.search.value);
                DtCompact.eachSearch(p.columns, (i, v) => q.set('columns[' + i + '][search][value]', v));
                window.location = this.href + (q.toString() ? '?' + q.toString() : '');
            });
        });
    </script>

    @endsection
</x-hr-dashboard>
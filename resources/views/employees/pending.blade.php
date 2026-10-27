<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">

    <link href="https://cdn.datatables.net/columncontrol/1.1.1/css/columnControl.dataTables.min.css" rel="stylesheet">
    @endsection


  @section('side_nav')
    @include('partials.receipt_side_nav')
  @endsection


  @section('content')

  <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bxs-user-account"></i> Pending Employees </h3>
            </div>
        </div><br>



        @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']) || (Auth::user()->hasRole(['Manager']) && Auth::user()->hasPermission('HR')))
        <div class="row mb-4">
            <div class="col-lg-12 col-md-6 mb-4 mb-md-0">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                            <p class="mb-1"><strong> PENDING EMPLOYEES </strong> </p>
                            <h4 class="card-title mb-3 text-white"><strong> {{ $pendingEmployees->count() }}  </strong> </h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
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
                        <h4 class="card-title mb-3 text-white"><strong> {{ $employeeAccra->count() }}  </strong> </h4>

                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
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
                        <h4 class="card-title mb-3 text-white"><strong> {{ $employeeBotwe->count() }} </strong> </h4>

                    </div>
                </div>
            </div>


            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
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
                        <h4 class="card-title mb-3 text-white"><strong> {{ $employeeShyhills }} </strong> </h4>
                    </div>
                </div>
            </div>
            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
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
                        <h4 class="card-title mb-3 text-white"><strong> {{ $employeeTema }} </strong> </h4>
                    </div>
                </div>
            </div>



            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1">TAKORADI</p>
                        <h4 class="card-title mb-3 text-white"> {{ $employeeTakoradi->count() }} </h4>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"> <strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3 text-white"> {{ $employeeKoforidua->count() }} </h4>
                    </div>
                </div>
            </div>


            <div class="col-lg-2 m-3">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3 text-white"> {{ $employeeKumasi->count() }} </h4>
                    </div>
                </div>
            </div>

            @if($employeeNoOffice->isNotEmpty())
            {{-- Pending staff without a field office: part of the total, so they get a card too. --}}
            <div class="col-lg-2">
                <div class="card h-100 bg-warning text-dark" title="{{ $employeeNoOffice->map(fn ($e) => 'FWSS ' . $e->id . ' ' . $e->name)->implode(', ') }}">
                    <div class="card-body">
                        <p class="mb-1"><strong> NO OFFICE </strong> </p>
                        <h4 class="card-title mb-3 text-dark"> {{ $employeeNoOffice->count() }} </h4>
                        <small>Set their field office</small>
                    </div>
                </div>
            </div>
            @endif

        </div> <br>
        @else
        {{-- A field office sees its own office and its sub-offices (Tema also sees Shai Hills). --}}
        <div class="row">
            @foreach ($officeCards as $card)
            <div class="col-lg-3 col-md-4 mb-3">
                <div class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong> {{ strtoupper($card['name']) }} </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ $card['count'] }} </strong> </h4>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
        <br><br>



        <div class="card-header  ml-2  d-none d-lg-block">
            @include('flash-messages')
        </div>

        <div class="row">
        <form action="/employeesAproval" method="POST">
            @csrf
            <input type="hidden" name="action_type" id="employee_action_type" value="" />
            <div class="col">

                    <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />

                    @if(Auth::user()->hasRole(['Manager']) && Auth::user()->hasPermission('Administration') )
                    <div class="form-check form-check-inline">                            
                        <button class="btn btn-dark" type="submit" onclick="document.getElementById('employee_action_type').value='branch'; return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('Approve') }}</button>
                    </div>    

                    @elseif(Auth::user()->hasRole(['Invoice']) || (Auth::user()->hasRole(['Manager']) && Auth::user()->hasPermission('HR')) )
                    <div class="form-check form-check-inline">                            
                        <button class="btn btn-success" type="submit" onclick="document.getElementById('employee_action_type').value='headOffice'; return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('Approve') }}</button>
                    </div>
                    <div class="form-check form-check-inline">                            
                        <button class="btn btn-danger" type="submit" onclick="document.getElementById('employee_action_type').value='decline'; return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('Decline') }}</button>
                    </div>
                    @endif

                <div class="card"> 
                    <div class="card-body"> 
                    <div class="table-responsive text-normal-dark"> 
                    <table id="myTable" class="display">
                        <thead>
                            <tr>
                                <th> </th>
                                <th>#</th>
                                <th>Status</th>

                                <th>Admin Assit.</th>
                                <th>Branch</th>
                                <th>H/O</th>

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
                                <!-- <th>Status Date</th> -->
                                <th>TAX</th>
                                <th>TIN</th>
                                <th>SSNIT</th>
                                <th>SSNIT #</th>
                                <th>Basic</th>
                                <th>Allowance</th>
                                <th> Created </th>
                                <!-- <th> Period </th> -->
                                <!-- <th> Updated</th> -->
                                <!-- <th> Period </th> -->
                                <!-- <th>Staff</th> -->
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                         @foreach ($visiblePending as $key => $employee)
                            @include('employees.partials.pending_row')
                         @endforeach

                       
                       
                        </tbody>
                    </table>
                    </div>
                    </div>
                </div>
            </div>
        </form>
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

    <script>
        new DataTable('#myTable', {
            responsive: true,
            columnControl: [ ['search'] ],
            layout: {
                topStart: {
                    buttons: [ 
                    {
                        extend: 'pageLength',
                        text: 'Show',
                        className: 'btn btn-secondary',
                        Options: [10, 25, 50, 100, 250, 500, 1000, 2000], 
                    },
                        {
                            extend: 'excelHtml5',
                            title: 'Employees',
                            className: 'btn btn-secondary',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                    ]
                }
            },
        });
    </script>

    <script>
        $(document).ready(function() {
            $('#options').change(function() {
                $('.checkBoxes').prop('checked', function(i, val) {
                    return !val;
                });
            });
        });
    </script>

    @endsection
</x-hr-dashboard>
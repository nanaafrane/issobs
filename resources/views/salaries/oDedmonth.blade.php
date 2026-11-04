<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.5/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.5/css/buttons.dataTables.css">    
    @endsection


   @section('side_nav')
    @include('partials.payroll_side_nav')
  @endsection


  @section('content')

  <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bx-transfer-alt"></i> {{ $field->name }}   @if (isset($month)) <strong> / For Month: {{  \Carbon\Carbon::parse($month)->format('F Y') }}</strong> @endif </h3>

            </div>
        </div><br>

         @if(Auth::user()->hasRole(['Invoice','Manager', 'Finance Manager' ]))
        <div class="row">
            <div class="col-lg-12 mb-4">
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
                        <p class="mb-1"><strong> FIELD OFFICE :  {{ strtoupper($field->name) }}  </strong> </p> <br>
                        <h4 class="card-title mb-3 text-white"><strong> GH&#x20B5;  {{ number_format($salariesoDed->sum('other_deductions'), 2) }} </strong> </h4> <br>
                        <small class="fw-medium"> NET SALARY : GH&#x20B5; {{ number_format($salariesoDed->sum('net_salary'), 2) }}  </small> <br>

                    </div>
                </div>
            </div>
        </div> <br>
        @endif

        <!-- Table -->  
        <hr> <br>
        <div class="row">
            <div class="col-lg-12 mb-4">
                <div class="card">
                    <h5 class="card-header"> Salaries Other Deductions  </h5>
                    <div class="card-body"> 
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover" id="myTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th> Month </th>
                                    <th>EMP. ID</th>
                                    <th> EMPLOYEE NAME </th>
                                    <th>ROLE</th>
                                    <th> FIELD OFFICE </th>
                                    <th> CLIENT </th>
                                    <th>LOCATION</th>
                                    <th>GH&#x20B5; OTHER DEDUCTIONS </th>
                                    <th>GH&#x20B5; NET SALARY</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                @foreach($salariesoDed as $key => $oDed)
                                <tr>
                                    <td> {{ $key + 1 }} </td>
                                    <td> {{ $oDed->salary_month?->format('F, Y') }} </td>
                                    <td> {{$oDed->employee_id}} </td>
                                    <td> {{ strtoupper($oDed->employee->name) }} </td>
                                    <td> {{ $oDed->role?->name }} </td>
                                    <td> {{ $oDed->field->name }} </td>
                                    <td> {{ $oDed->client?->name }} {{ $oDed->client?->business_name }} </td>
                                    <td> {{ $oDed->location }} </td>
                                    <td>  {{ number_format($oDed->other_deductions, 2) }} </td>
                                    <td>  {{ number_format($oDed->net_salary, 2) }} </td>
                                </tr>
                                @endforeach
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
    <script src="https://cdn.datatables.net/2.3.5/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.5/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.colVis.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>


    <script>
       
      new DataTable('#myTable', {
        //  dom: 'Blfrtip',
        //  stateSave: false,
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
                        title:  "{{ $field->name . ' OTHER DEDUCTIONS ' . \Carbon\Carbon::parse($month)->format('F Y')}}",
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

    @endsection
</x-hr-dashboard>
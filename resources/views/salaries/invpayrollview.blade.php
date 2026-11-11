<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="{{ asset('libs/datatables/2.3.3/css/dataTables.dataTables.css') }}" />
    <link rel="stylesheet" href="{{ asset('libs/datatables/buttons/3.2.4/css/buttons.dataTables.css') }}">
    @endsection


   @section('side_nav')
    @include('partials.payroll_side_nav')
  @endsection


  @section('content')

  <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bx-git-compare"></i> Invoices to Salary </h3>
            </div>
        </div><br>

        @if(Auth::user()->hasRole(['Invoice','Manager', 'Finance Manager' ]))
        <div class="row mb-4">
            <div class="col-lg-4">
                <div  class="card h-100 bg-danger text-white">
                    <div class="card-body">
                            <p class="mb-1"><strong> INVOICES  / For Month: {{  $month }}</strong> </p>
                            <h4 class="card-title mb-3 text-white"><strong> GH&#x20B5; {{ number_format($invoicesTotal, 2) }}  </strong> </h4>
                            <small class="fw-medium"> TOTAL INVOICES GENERATED </small>
                   </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div  class="card h-100 bg-success text-white">
                    <div class="card-body">
                            <p class="mb-1"><strong> SALARIES  / For Month: {{  $month }}</strong>  </p>
                            <h4 class="card-title mb-3 text-white"><strong> GH&#x20B5; {{ number_format($salariesTotal, 2) }}  </strong> </h4>
                            <small class="fw-medium"> TOTAL SALARIES PAID </small>      
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div  class="card h-100 bg-secondary text-white">
                    <div class="card-body">
                            <p class="mb-1"><strong> DIFFERENCE : {{  $month }}</strong>  </p>
                            <h4 class="card-title mb-3 text-white"><strong> GH&#x20B5; {{ number_format($invoicesTotal - $salariesTotal, 2) }}  </strong> </h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong> ACCRA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($AccrainvoicesTotal, 2) }} </strong> </h4>
                        INVOICES TOTAL: {{ $AccrainvoicesCount }}
                        <hr>
                        SALARIES TOTAL: {{ $AccrasalariesCount }}
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($AccrasalariesTotal, 2) }} </strong> </h4>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong> BOTWE </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($BotweinvoicesTotal, 2) }} </strong> </h4>
                        INVOICES TOTAL: {{ $BotweinvoicesCount }}
                        <hr>
                        SALARIES TOTAL: {{ $BotwesalariesCount }}
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($BotwesalariesTotal, 2) }} </strong> </h4>
                    </div>
                </div>
            </div>


            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong> TEMA </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($TemainvoicesTotal, 2) }} </strong> </h4>
                        INVOICES TOTAL: {{ $TemainvoicesCount }}
                        <hr>
                        SALARIES TOTAL: {{ $TemasalariesCount }}
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($TemasalariesTotal, 2) }} </strong> </h4>
                    </div>
                </div>
            </div>



            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1">TAKORADI</p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($TakoradinvocesTotal, 2) }} </strong> </h4>
                        INVOICES TOTAL: {{ $TakoradinvocesCount }}
                        <hr>
                        SALARIES TOTAL: {{ $TakoradisalariesCount }}
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($TakoradisalariesTotal, 2) }} </strong> </h4>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1"> <strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($KoforiduainvoicesTotal, 2) }} </strong> </h4>
                        INVOICES TOTAL: {{ $KoforiduainvoicesCount }}
                        <hr>
                        SALARIES TOTAL: {{ $KoforiduasalariesCount }}
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($KoforiduasalariesTotal, 2) }} </strong> </h4>
                    </div>
                </div>
            </div>


            <div class="col-lg-2">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($KumasinvoicesTotal, 2) }} </strong> </h4>
                        INVOICES TOTAL: {{ $KumasinvoicesCount }}
                        <hr>
                        SALARIES TOTAL: {{ $KumasalariesCount }}
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($KumasalariesTotal, 2) }} </strong> </h4>  
                    </div>
                </div>
            </div>

        </div> <br> <br>
        @endif

        <hr />
        <br>
              <div class="card-header  ml-2  d-none d-lg-block">
                  @include('flash-messages')
              </div> <br>


        <div class="row">
            <form action="/salaries" method="POST">
                @csrf
                <div class="col">
                    <table id="myTable" class="display">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th> Month </th>
                                <th> Field </th>
                              <th> Client Invoice Amount </th>
                              <th>Client Name </th>
                              <th> Guards Salary </th>
                              <th> Difference </th>
                              <th>Status</th>  
                              <th>View Invoices </th>   
                              <th>View Guards</th> 
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ( $invoices as $key => $invoice )
                            <tr>
                                <td> {{ $key + 1 }} </td>
                                <td> {{ $month }} </td>
                                <td> {{ $invoice->client?->field?->name }} </td>
                                <td> GH&#x20B5;  {{number_format($invoice->total_invoice, 2) }} </td>
                                @foreach ($salaries as $salary)
                                    @if ($invoice->client_id == $salary->client_id)
                                        <td> {{$invoice->client?->name }} {{$invoice->client?->business_name }} </td>
                                        <td>GH&#x20B5; {{number_format($salary->total_salary, 2) }} </td>
                                        <td>GH&#x20B5;{{ number_format($invoice->total_invoice - $salary->total_salary, 2)}}</td>
                                            @if($invoice->total_invoice <= $salary->total_salary)
                                                    <td><span class="badge bg-label-danger"> Loss </span></td>
                                            @else
                                                    <td><span class="badge bg-label-success"> Profit </span></td>
                                            @endif
                                    @endif
                                @endforeach
                                <td> <a href="/invToPayrollInvoice/{{ $invoice->client_id }}/{{ $month }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a> 
                                </td>
                                <td> <a href="/invToPayrollGuards/{{ $invoice->client_id }}/{{ $month }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a> 
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </form>
        </div>

    </div>
  <!-- / Content -->
  @endsection




   @section('scripts')
    <script src="{{ asset('libs/jquery/3.7.1/jquery.js') }}"></script>
    <script src="{{ asset('libs/datatables/2.3.3/js/dataTables.js') }}"></script>
    <script src="{{ asset('libs/datatables/buttons/3.2.4/js/dataTables.buttons.js') }}"></script>
    <script src="{{ asset('libs/datatables/buttons/3.2.4/js/buttons.dataTables.js') }}"></script>
    <script src="{{ asset('libs/jszip/3.10.1/jszip.min.js') }}"></script>
    <script src="{{ asset('libs/pdfmake/0.2.7/pdfmake.min.js') }}"></script>
    <script src="{{ asset('libs/pdfmake/0.2.7/vfs_fonts.js') }}"></script>
    <script src="{{ asset('libs/datatables/buttons/3.2.4/js/buttons.html5.min.js') }}"></script>


    <script>
        new DataTable('#myTable', {
            responsive: true,

            layout: {
              topStart: {
                // buttons: [] // No export/download buttons
              }
            }
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






<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">
    @endsection


  @section('side_nav')
    @include('partials.payroll_side_nav')
  @endsection


  @section('content')

  <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bx-git-compare"></i> Salaries For Month : {{ $month }} </h3>
            </div>
        </div><br>
        <div class="row mb-4">
            <div class="col-lg-12">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong>  </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong> GH&#x20B5; {{ number_format($salaries->sum('cost_to_company'), 2) }} </strong> </h4>
                        NUMBER OF EMPLOYEES: {{ count($salaries) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
                <div class="col">
                <div class="table-responsive text-normal-dark">
                    <table id="myTable" class="display">
                        <thead>
                            <tr>
                                <th> id</th>
                                <th> Salary Month </th>
                                <th> Name</th>
                                <th> Field </th>
                                <th>Client </th>
                                <th> SSNIT No.</th>
                                <th> TIN No.</th>
                                <th> Payment Type</th>
                                <th> Bank Name </th>
                                <th> Branch </th>
                                <th>Acc No</th>
                                <th> Total_Salary </th>
                                <th>status</th>
                            </tr>
                        </thead>
                        <tbody>

                        @foreach ($salaries as $key => $salary )
                            <tr>
                                <td> {{ $salary->id }} </td>
                                <td> {{$salary->salary_month?->format('F, Y')}} </td>
                                <td> {{ strtoupper($salary->employee?->name) }} </td>
                                <td> {{ $salary->field?->name }} </td>
                                <td> {{ $salary->client?->name }} {{ $salary->client?->business_name }} </td>
                                <td> {{$salary->employee?->ssnit_number}}</td>
                                <td> {{$salary->employee?->tin_number}}</td>
                                <td> {{$salary->payment_type}}</td>
                                <td> {{$salary->bank?->name}}</td>
                                <td> {{$salary->branch}}</td>   
                                 <td> {{$salary->account_number}}</td>             
                                <td>GHGH&#x20B5; {{$salary->cost_to_company }}</td>
                                <td><span class="badge bg-label-success">{{$salary->payment_status}}</span></td>
                            </tr>
                          @endforeach
                        </tbody>
                    </table>
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


    <script>
        new DataTable('#myTable', {
            responsive: true,
              dom: 'lfrtip',
              lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
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






<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.5/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.5/css/buttons.dataTables.css">    
    <link href="https://cdn.datatables.net/columncontrol/1.1.1/css/columnControl.dataTables.min.css" rel="stylesheet">
    @endsection


  @section('side_nav')
  <!-- Menu -->

    @include('partials.payroll_side_nav')
    
  <!-- / Menu -->
  @endsection


  @section('content')

  <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bx-bxs-user-detail"></i> Payroll / Salaries For : {{$month->format('F, Y')}} </h3>
            </div>
        </div><br>

         @if(Auth::user()->hasRole(['Invoice','Manager', 'Officer', 'Internal Auditor','Finance Manager' ]))
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
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($salariesAccraSum, 2) }} </strong> </h4>
                        <small class="fw-medium"> TOTAL : {{ $salariesAccraCount}}  </small>
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
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($salariesBotweSum, 2) }} </strong> </h4>
                        <small class="fw-medium"> TOTAL : {{ $salariesBotweCount }} </small>
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
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($salariesShyhillsSum, 2) }} </strong> </h4>
                        <small class="fw-medium"> TOTAL : {{ $salariesShyhillsCount }} </small>
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
                        <h4 class="card-title mb-3 text-white"><strong> {{ number_format($salariesTemaSum, 2) }} </strong> </h4>
                        <small class="fw-medium"> TOTAL : {{ $salariesTemaCount }} </small>
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
                        <h4 class="card-title mb-3 text-white">  {{ number_format($salariesTakoradiSum, 2) }} </h4>
                        <small class="fw-medium"> TOTAL : {{ $salariesTakoradiCount }}   </small>
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
                        <h4 class="card-title mb-3 text-white"> {{ number_format($salariesKoforiduaSum, 2) }} </h4>
                        <small class="fw-medium"> TOTAL : {{ $salariesKoforiduaCount }}  </small>
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
                        <h4 class="card-title mb-3 text-white"> {{ number_format($salariesKumasiSum, 2) }} </h4>
                        <small class="fw-medium"> TOTAL : {{ $salariesKumasiCount }}  </small>
                    </div>
                </div>
            </div>

        </div> <br> <br>
                     <div class="card-header  ml-2  d-none d-lg-block">
                  @include('flash-messages')
        </div> <br>
        @endif

        <div class="row">
          <form action="{{ route('salaries.upload') }}" method="POST" enctype="multipart/form-data"> 
            @csrf
            <div class="col">
                <label for="acc_number" class="form-label"> <strong>   UPLOAD SALARIES: </strong> </label>
                <input
                      type="file"
                      id="excelFile"
                      name="excelFile"
                      class="account-file-input  @error('excelFile') is-invalid @enderror"
                      accept=".xls, .xlsx"
                      required/>

                <button class="btn btn-dark ml-5" onclick="return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-recycle"> </i> {{ __('Upload') }}</button>
            </div>
          </form>
        </div> <br> <br>
        <hr> <br> <br>
        <div class="row">
            <form action="/salariesDeleteMultiple" method="POST">
                @csrf
                <div class="col">
                    <input type="hidden" name="action_type" id="salary_action_type" value="" />
                    <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />

                    <div class="form-check form-check-inline">
                        <select name="salary" class="form-select">
                            <option value=""> Select All </option>
                        </select>
                    </div>

                    <div class="form-check form-check-inline">
                        <button class="btn btn-danger" data-action="delete" onclick="document.getElementById('salary_action_type').value='delete'; return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-recycle"> </i> {{ __('Delete') }}</button>                   
                    </div>           
                        <div class="card"> 
                            <div class="card-body"> 
                                <div class="table-responsive text-normal-dark"> 

                                    <table id="myTable" class="display">
                                        <thead>
                                            <tr>
                                                <th> </th>
                                                <th> Edit </th>
                                                <th> id</th>
                                                <th> Salary Month </th>
                                                <th> employee_id </th>
                                                <th> Name</th>
                                                <th> Field </th>
                                                <th> Client </th>
                                                <th> Location </th>
                                                <th> Basic Salary</th>
                                                <th> Allowances</th>
                                                <th> airtime_allowance</th>
                                                <th> overtime</th>
                                                <th> reimbursements </th>
                                                <th> transport_allowance</th>
                                                <th> welfare </th>
                                                <th> maintenance</th>
                                                <th> absent</th>
                                                <th> boot</th>
                                                <th> iou</th>
                                                <th> hostel</th>
                                                <th> insurance</th>
                                                <th> reprimand</th>
                                                <th> scouter </th>
                                                <th> raincoat </th>
                                                <th> meal</th>
                                                <th> loan</th>
                                                <th> walkin</th>
                                                <th> amnt_ded_cof_start_date</th>
                                                <th> other_deductions</th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                        @foreach ($salaries as $key => $salary )
                                            <tr>
                                                <td> <input class="checkBoxes form-check-input" type="checkbox" name="salary[]" value="{{ $salary->id }}" /> </td>
                                                <td><a class="dropdown-item" href="/salaries/{{$salary->id}}/edit"><i class="icon-base bx bx-edit-alt me-1"></i></a> </td>
                                                <td> {{ $salary->id }} </td>
                                                <td> {{$salary->salary_month?->format('F, Y')}} </td>
                                                <td> {{ $salary?->employee_id }} </td>
                                                <td> {{ strtoupper($salary->employee?->name) }} </td>
                                                <td> {{ $salary->field?->name }} </td>
                                                <td> {{ $salary->client?->name }} {{ $salary->client?->business_name }}</td>
                                                <td> {{ $salary->location }} </td>
                                                <td> {{$salary->basic_salary}}</td>
                                                <td> {{$salary->allowances}}</td>
                                                <td> {{$salary->airtime_allowance}}</td>
                                                <td> {{$salary->overtime}}</td>
                                                <td> {{$salary->reimbursements}}</td>
                                                <td> {{$salary->transport_allowance}}</td>
                                               <td> {{$salary->welfare}} </td>                            
                                                <td> {{$salary->maintenance}} </td>                            
                                                <td> {{$salary->absent}} </td>                            
                                                <td> {{$salary->boot}} </td>                            
                                                <td> {{$salary->iou}} </td>                            
                                                <td> {{$salary->hostel}} </td>                            
                                                <td> {{$salary->insurance}} </td>                            
                                                <td> {{$salary->reprimand}} </td>                            
                                                <td> {{$salary->scouter}} </td>                            
                                                <td> {{$salary->raincoat}} </td>                            
                                                <td> {{$salary->meal}} </td>                            
                                                <td> {{$salary->loan}} </td>                            
                                                <td> {{$salary->walkin}} </td>                            
                                                <td> {{$salary->amnt_ded_cof_start_date}} </td>                            
                                                <td> {{$salary->other_deductions}} </td>                            

                                            </tr>
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
    <script src="https://cdn.datatables.net/2.3.5/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.5/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.colVis.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <script src="https://cdn.datatables.net/columncontrol/1.1.1/js/dataTables.columnControl.min.js"></script>
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
                        title: 'Salaries',
                        className: 'btn btn-secondary',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    'colvis'
                ]
            }
        },
                  columnDefs: [
              {
                  targets: [0,1],
                  visible: false
              }
          ]
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
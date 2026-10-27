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
                <h3 class="card-header"> <i class="icon-base bx bxs-user-account"></i> Employees For {{ $groupedBankEmployees[0]->employee->paymentInfo?->bank?->name  }}  </h3>
            </div>
        </div><br>


        <div class="card-header  ml-2  d-none d-lg-block">
            @include('flash-messages')
        </div>

        <div class="row">
            <div class="col">
                <div class="card"> 
                    <div class="card-body"> 
                    <div class="table-responsive text-normal-dark"> 
                    <table id="myTable" class="display">
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
                                <th>TAX</th>
                                <th>SSNIT</th>
                                <th>Basic</th>
                                <th>Allowance</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                            @foreach ($groupedBankEmployees as $key => $data )
                            <tr>
                                <td> {{ $key + 1 }} </td>
                                <td> FWSS {{ $data->employee->id }}  </td>
                                <td>{{$data->employee->name}}  </td>
                                <td>{{ $data->employee->gender }}  </td>
                                <td>{{ $data->employee->phone_number }}  </td>
                                <td>{{ $data->employee->date_of_joining?->diffForHumans() }} </td>
                                <td> {{ $data->employee->department?->name }} </td>
                                <td> {{ $data->employee->role?->name }}  </td>
                                <td> {{ $data->employee->field?->name }}   </td>
                                <td>{{ $data->employee->client?->name }} {{ $data->employee->client?->business_name }} </td>
                                <td> {{ $data->employee->location }} </td>
                                <td> {{ $data->employee->payment_type }}  </td>
                                <td> {{  $data->employee->paymentInfo?->bank?->name  }} </td>
                                <td> {{  $data->employee->paymentInfo?->acc_number  }} </td>
                                @if($data->employee->status == 'Active')
                                <td><span class="badge bg-label-success">{{$data->employee->status}}</span></td>
                                @else
                                <td><span class="badge bg-label-danger">{{$data->employee->status}}</span></td>
                                @endif
                               
                                 @if($data->employee->tax_button == 'on')
                                <td> <span class="badge bg-label-dark"> {{  $data->employee->tax_button }} </span> </td>
                                @else
                                <td> <span class="badge bg-label-danger"> OFF </span> </td>
                                @endif

                                @if($data->employee->ssnit_button == 'on')
                                <td> <span class="badge bg-label-dark"> {{  $data->employee->ssnit_button }} </span> </td>
                                @else
                                <td> <span class="badge bg-label-danger"> OFF </span> </td>
                                @endif
                                <td> {{$data->employee->basic_salary}} </td>
                                <td> {{$data->employee->allowances}} </td>

                                <td>{{ $data->employee->created_at?->format('F, Y') }} </td>
                               <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{url('employees', $data->employee->id)}}"><i class="icon-base bx bxs-bullseye"></i> view</a>
                                        <a class="dropdown-item" href="{{url('employees', $data->employee->id)}}/edit"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>

                                    </div>
                                </div>
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
    @endsection
</x-hr-dashboard>
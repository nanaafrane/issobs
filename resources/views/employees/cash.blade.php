<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">

    <!-- <link href="https://cdn.datatables.net/columncontrol/1.1.1/css/columnControl.dataTables.min.css" rel="stylesheet"> -->
    @endsection


  @section('side_nav')
    @include('partials.receipt_side_nav')
  @endsection


  @section('content')

  <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bxs-user-account"></i> Active Employee Cash </h3>
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
                                <th> Field </th>
                                <th>Total Employees</th>
                                <th>View Employees</th>

                            </tr>
                        </thead>
                        <tbody>

                            @foreach ($groupedCashkEmployees as $key => $cash )
                            <tr>
                                <td> {{ $key + 1 }} </td>
                                <td> {{ strtoupper($cash->field?->name) }} </td>
                                <td> {{ $cash->total_employees }} </td>
                                <td> 
                                    <a href="employeesCashView/{{ $cash->field_id }}" class="btn btn-dark btn-sm">
                                        <i class="bx bx-show"></i> 
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

    <!-- <script src="https://cdn.datatables.net/columncontrol/1.1.1/js/dataTables.columnControl.min.js"></script> -->

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
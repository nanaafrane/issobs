<x-sales-dashboard>

    @section('side_nav')
        @include('partials.receipt_side_nav')
    @endsection
  
    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">
    @endsection

    @section('content')

    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header text-danger"> <i class="icon-base bx bx-receipt text-primary me-2"></i> ProForma Clients </h3>
            </div>
        </div><br>

        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: crimson;" class="card">
                    <div class="d-flex align-items-start row">
                        <div class="col-sm-7">
                            <div class="card-body">
                                <h1 class="text-white">
                                    @if(Auth::user()->hasRole(['Invoice', 'Finance Manager', 'Manager']) )
                                    {{$clientsCount}}
                                    @endif

                                </h1>
                                <h6 class="card-title text-white mb-3">TOTAL NUMBER OF CLIENTS </h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-header  ml-2  d-none d-lg-block">
            @include('flash-messages')
        </div>

        <div class="row">
            <div class="col">
                <table id="myTable" class="display">
                    <thead>
                        <tr>
                            <th> # ID.</th>
                            <th> Name</th>
                            <th>Phone No.</th>
                            <th>Business Name </th>
                            <th> Field Office </th>
                            <th> Address </th>
                            <th> Branch </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach($clients as $client)
                        <tr>
                            <td> {{$client->id}} </td>
                            <td> {{$client->name}}</td>
                            <td> {{$client->phone_number}}, {{$client->phone_number1}} </td>
                            <td> {{$client->business_name}} </td>
                            <td>
                                @if($client->field->name == 'Accra')
                                <span class="badge bg-label-danger">{{$client->field->name}} </span>
                                @endif

                                @if($client->field->name == 'Botwe')
                                <span class="badge bg-label-secondary">{{$client->field->name}} </span>
                                @endif

                                @if($client->field->name == 'Tema')
                                <span class="badge bg-label-info">{{$client->field->name}} </span>
                                @endif

                                @if($client->field->name == 'ShaiHills')
                                <span class="badge bg-label-info">{{$client->field->name}} </span>
                                @endif

                                @if($client->field->name == 'Takoradi')
                                <span class="badge bg-label-warning ">{{$client->field->name}} </span>
                                @endif

                                @if($client->field->name == 'Koforidua')
                                <span class="badge bg-label-success">{{$client->field->name}} </span>
                                @endif

                                @if($client->field->name == 'Kumasi')
                                <span class="badge bg-label-primary">{{$client->field->name}} </span>
                                @endif

                            </td>

                            <td> {{$client->address}} </td>
                            <td> {{$client->branch}} </td>

                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="icon-base bx bx-dots-vertical-rounded text-primary"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="proformaClient/{{$client->id}}/edit"><i class="icon-base bx bx-edit-alt me-2 text-primary"></i> Edit</a>
                                        @if(Auth::user()->hasPermission('HR') || Auth::user()->hasRole(['Invoice'])  )
                                        <form action="proformaClient/{{$client->id}}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button class="dropdown-item" type="submit"><i class="icon-base bx bx-trash me-2 text-danger"></i>Delete</button>
                                        </form>
                                        @endif

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

</x-sales-dashboard>
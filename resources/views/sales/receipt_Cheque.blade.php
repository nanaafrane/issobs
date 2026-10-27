<x-sales-dashboard>

    @section('side_nav')
        @include('partials.receipt_side_nav')
    @endsection
    
    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">
    <link href="https://cdn.datatables.net/columncontrol/1.1.1/css/columnControl.dataTables.min.css" rel="stylesheet">

    @endsection


    @section('content')

    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header text-primary"> <i class="icon-base bx bx-receipt text-primary me-2"></i> Cheque Payment </h3>
            </div>
        </div><br>

        @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))
        <div class="row">
            <div class="col-lg-2">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> ACCRA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($accraTotal, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$accraCount}}</strong> </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> BOTWE </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($botweTotal,2)}}</strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$botweCount}} </strong> </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> SHAIHILLS </strong></p>
                        <h4 class="card-title mb-3 text-white"> <strong>&#x20B5;{{number_format($shyhillsTotal, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$shyhillsCount}} </strong> </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> TEMA </strong></p>
                        <h4 class="card-title mb-3 text-white"> <strong>&#x20B5;{{number_format($temaTotal, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$temaCount}} </strong> </small>
                    </div>
                </div>
            </div>



            <div class="col-lg-2">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1">TAKORADI</p>
                        <h4 class="card-title mb-3 text-white"> <strong> &#x20B5; {{number_format($takoradiTotal,2)}} </strong></h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : {{$takoradiCount}} </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"> <strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($koforiduaTotal,2)}}</strong></h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$koforiduaCount}} </strong> </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2 m-3">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($kumasiTotal,2)}}</strong></h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$kumasiCount}} </strong> </small>
                    </div>
                </div>
            </div>

        </div>
        @elseif(Auth::user()->field?->name == 'Accra')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> ACCRA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($accraTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$accraCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(Auth::user()->field?->name == 'Botwe')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> BOTWE </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($botweTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$botweCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(Auth::user()->field?->name == 'Tema')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> TEMA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($temaTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$temaCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Takoradi')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> TAKORADI </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($takoradiTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$takoradiCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Koforidua')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($koforiduaTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$koforiduaCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Kumasi')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: #152356; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($kumasiTotal, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{$kumasiCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif
        <br><br>

        <div class="row">
            <div class="col">
                <table id="myTable" class="display">
                    <thead>
                        <tr>
                            <th>id</th>
                            <th>Receipt Month</th>
                            <th>Invoice No.</th>
                            <th>Invoice Amount</th>
                            <th>Invoice Month</th>
                            <th>Client Name</th>
                            <th>Phone No.</th>
                            <th> Field Office </th>
                            <th> Staff </th>
                            <th>Date Created</th>
                            <th>Paid</th>
                            <th>Status</th>
                            <th>Advance</th>
                            <th>View</th>
                        </tr>
                    </thead>

                    <tbody>

                        @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))
                        @foreach($chequeReceipt as $receipt)
                        <tr>
                            <td>FWSSR{{$receipt->id}}</td>
                            <td> {{$receipt->receipt_month?->format('F Y')}} </td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> GH&#x20B5; {{$receipt->invoice?->total}} </td>
                            <td> {{$receipt->invoice?->invoice_month?->format('F Y')}} </td>
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}}</td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>

                            <td> GH&#x20B5; {{$receipt->total}} </td>


                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

                            <td> {{ $receipt->advance_payment }} </td>
                            <td>
                                <a href="{{url('receipt', $receipt->id)}}" class="btn btn-info">
                                    <i class="icon-base bx bx-bullseye text-primary me-2"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @elseif(Auth::user()->field?->name == 'Accra')
                        @foreach($accra as $receipt)
                        <tr>
                            <td>FWSSR{{$receipt->id}}</td>
                            <td> {{$receipt->receipt_month?->format('F Y')}} </td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> GH&#x20B5; {{$receipt->invoice?->total}} </td>
                            <td> {{$receipt->invoice?->invoice_month?->format('F Y')}} </td>
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}}</td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>

                            <td> GH&#x20B5; {{$receipt->total}} </td>


                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

                            <td> {{ $receipt->advance_payment }} </td>
                            <td>
                                <a href="{{url('receipt', $receipt->id)}}" class="btn btn-info">
                                    <i class="icon-base bx bx-bullseye text-primary me-2"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @endif


                        @if(Auth::user()->field?->name == 'Botwe')
                        @foreach($botwe as $receipt)
                        <tr>
                            <td>FWSSR{{$receipt->id}}</td>
                            <td> {{$receipt->receipt_month?->format('F Y')}} </td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> GH&#x20B5; {{$receipt->invoice?->total}} </td>
                            <td> {{$receipt->invoice?->invoice_month?->format('F Y')}} </td>
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}}</td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>

                            <td> GH&#x20B5; {{$receipt->total}} </td>


                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

                            <td> {{ $receipt->advance_payment }} </td>
                            <td>
                                <a href="{{url('receipt', $receipt->id)}}" class="btn btn-info">
                                    <i class="icon-base bx bx-bullseye text-primary me-2"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @endif


                        @if(Auth::user()->field?->name == 'Tema')
                        @foreach($tema as $receipt)
                        <tr>
                            <td>FWSSR{{$receipt->id}}</td>
                            <td> {{$receipt->receipt_month?->format('F Y')}} </td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> GH&#x20B5; {{$receipt->invoice?->total}} </td>
                            <td> {{$receipt->invoice?->invoice_month?->format('F Y')}} </td>
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}}</td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>

                            <td> GH&#x20B5; {{$receipt->total}} </td>


                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

                            <td> {{ $receipt->advance_payment }} </td>
                            <td>
                                <a href="{{url('receipt', $receipt->id)}}" class="btn btn-info">
                                    <i class="icon-base bx bx-bullseye text-primary me-2"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @endif


                        @if(Auth::user()->field?->name == 'Takoradi')
                        @foreach($takoradi as $receipt)
                        <tr>
                            <td>FWSSR{{$receipt->id}}</td>
                            <td> {{$receipt->receipt_month?->format('F Y')}} </td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> GH&#x20B5; {{$receipt->invoice?->total}} </td>
                            <td> {{$receipt->invoice?->invoice_month?->format('F Y')}} </td>
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}}</td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>

                            <td> GH&#x20B5; {{$receipt->total}} </td>


                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

                            <td> {{ $receipt->advance_payment }} </td>
                            <td>
                                <a href="{{url('receipt', $receipt->id)}}" class="btn btn-info">
                                    <i class="icon-base bx bx-bullseye text-primary me-2"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @endif


                        @if(Auth::user()->field?->name == 'Koforidua')
                        @foreach($koforidua as $receipt)
                        <tr>
                            <td>FWSSR{{$receipt->id}}</td>
                            <td> {{$receipt->receipt_month?->format('F Y')}} </td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> GH&#x20B5; {{$receipt->invoice?->total}} </td>
                            <td> {{$receipt->invoice?->invoice_month?->format('F Y')}} </td>
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}}</td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>

                            <td> GH&#x20B5; {{$receipt->total}} </td>


                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

                            <td> {{ $receipt->advance_payment }} </td>
                            <td>
                                <a href="{{url('receipt', $receipt->id)}}" class="btn btn-info">
                                    <i class="icon-base bx bx-bullseye text-primary me-2"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @endif


                        @if(Auth::user()->field?->name == 'Kumasi')
                        @foreach($kumasi as $receipt)
                        <tr>
                            <td>FWSSR{{$receipt->id}}</td>
                            <td> {{$receipt->receipt_month?->format('F Y')}} </td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> GH&#x20B5; {{$receipt->invoice?->total}} </td>
                            <td> {{$receipt->invoice?->invoice_month?->format('F Y')}} </td>
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}}</td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>

                            <td> GH&#x20B5; {{$receipt->total}} </td>


                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

                            <td> {{ $receipt->advance_payment }} </td>
                            <td>
                                <a href="{{url('receipt', $receipt->id)}}" class="btn btn-info">
                                    <i class="icon-base bx bx-bullseye text-primary me-2"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @endif


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

    <script src="https://cdn.datatables.net/columncontrol/1.1.1/js/dataTables.columnControl.min.js"></script>

    <script>
        new DataTable('#myTable', {
            responsive: true,

            layout: {
                topStart: {
                    buttons: [{
                            extend: 'pageLength',
                            text: '<i class="bx bxs-bullseye"></i> Show',
                            className: 'btn btn-secondary',
                            Options: [10, 25, 50, 100, 500], 
                        },
                        {
                            extend: 'excelHtml5',
                            text: '<i class="bx bxs-file-export"></i> Export to Excel',
                            className: 'btn btn-secondary btn-sm',
                            title: "Cheque Receipts",
                            exportOptions: {
                                columns: ':visible'
                            }   
                        }]
                }
            },
             columnControl: [
                ['search']
            ]
        });
    </script>


    @endsection

</x-sales-dashboard>
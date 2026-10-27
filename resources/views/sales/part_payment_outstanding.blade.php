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
                <h3 class="card-header text-primary"> <i class="icon-base bx bx-receipt text-primary me-2"></i> Balance Outstanding  @if (isset($month)) <strong> / For Month: {{  \Carbon\Carbon::parse($month)->format('F Y') }}</strong> @endif </h3>
            </div>
        </div><br>

        @if(isset($invoiceTotal) && isset($invoiceCount))
        <div class="row mb-4">
            <div class="col-lg-12 col-md-6 mb-4 mb-md-0">
                <div  class="card h-100">
                    <div class="card-body">
                            <p class="mb-1"><strong> BALANCES </strong> </p>
                            <h4 class="card-title mb-3"><strong> GH&#x20B5; {{ number_format($invoiceTotal, 2) }}  </strong> </h4>
                            <small class="fw-medium"> TOTAL INVOICES GENERATED : {{ $invoiceCount }}  </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))
        <div class="row">
            <div class="col-lg-2">
                <div class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{$accraTotal}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$accraCount}}</strong> </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{$botweTotal}}</strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$botweCount}} </strong> </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2">
                <div class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{$shyhillsTotal}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$shyhillsCount}} </strong> </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2">
                <div class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{$temaTotal}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$temaCount}} </strong> </small>
                    </div>
                </div>
            </div>



            <div class="col-lg-2">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1">TAKORADI</p>
                        <h4 class="card-title mb-3">&#x20B5; {{$takoradiTotal}}</h4>
                        <small class="fw-medium"> TOTAL INVOICES : {{$takoradiCount}} </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"> <strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3">&#x20B5;{{$koforiduaTotal}}</h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$koforiduaCount}} </strong> </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2 m-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3">&#x20B5;{{$kumasiTotal}}</h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$kumasiCount}} </strong> </small>
                    </div>
                </div>
            </div>

        </div>
        @elseif(Auth::user()->field?->name == 'Accra')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{$accraTotal}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$accraCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(Auth::user()->field?->name == 'Botwe')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{$botweTotal}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$botweCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(Auth::user()->field?->name == 'Tema')
        <div class="row">
            <div class="col-xxl-6 mb-6 order-0">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> SHAIHILLS </strong> </p>
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{$shyhillsTotal}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$shyhillsCount}}</strong> </small>
                    </div>
                </div>
            </div>
            <div class="col-xxl-6 mb-6 order-0">
                <div class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{$temaTotal}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$temaCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Takoradi')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{$takoradiTotal}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$takoradiCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Koforidua')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{$koforiduaTotal}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$koforiduaCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Kumasi')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{$kumasiTotal}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$kumasiCount}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <br><br>

        <div class="row">
            <form action="/searchPartPaymentOutstanding" method="GET">
                @csrf
                <div class="col">

                    <label for="" class="form-label"> <strong>   CHOOSE A MONTH TO SEARCH </strong> </label> <br>

                    <div class="form-check form-check-inline">
                        <input type="month" class="form-control" name="month" required/> <br>
                        
                        <button class="btn btn-dark" type="submit" onclick="return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('') }}</button>
                    </div>
                </div>
            </form>
        </div>
        <hr> <br>  


        <div class="row">
            <div class="col">
                <table id="myTable" class="display">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Invoice No.</th>
                            <th>Invoice Month</th>
                            <th>Client Name</th>
                            <th>Phone No.</th>
                            <th>Business Name </th>
                            <th> Field Office </th>
                            <th> Staff </th>
                            <th>Date Created</th>
                            <th>Due Date</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>

                        @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))
                        @foreach($reportInvoices as $key => $invoice)
                        <tr>
                            <td> {{$key + 1}} </td>
                            <td> #FWSSi{{$invoice->id}} </td>
                                <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                <td> {{$invoice->client->name}}</td>
                                <td> {{$invoice->client->phone_number}} </td>
                                <td> {{$invoice->client->business_name}} </td>
                                <td> {{$invoice->client->field->name}} </td>
                                <td> {{$invoice->user->name}} </td>
                                <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                <td> {{$invoice->due_date->diffForHumans()}} </td>
                                <td> GH&#x20B5; {{number_format($invoice->balance,2)}} </td>
                            @if($invoice->status == 'completed')
                            <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                            @endif
                            <td>
                                <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                    <i class="icon-base bx bx-bullseye text-primary me-2"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @elseif(Auth::user()->field?->name == 'Accra')
                        @foreach($accra as $key => $invoice)
                        <tr>
                            <td> {{$key + 1}} </td>
                            <td> #FWSSi{{$invoice->id}} </td>
                                <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                <td> {{$invoice->client->name}}</td>
                                <td> {{$invoice->client->phone_number}} </td>
                                <td> {{$invoice->client->business_name}} </td>
                                <td> {{$invoice->client->field->name}} </td>
                                <td> {{$invoice->user->name}} </td>
                                <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                <td> {{$invoice->due_date->diffForHumans()}} </td>
                                <td> GH&#x20B5; {{number_format($invoice->balance,2)}} </td>
                            @if($invoice->status == 'completed')
                            <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                            @endif
                            <td>
                                <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                    <i class="icon-base bx bx-bullseye text-primary me-2"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @endif


                        @if(Auth::user()->field?->name == 'Botwe')
                        @foreach($botwe as $key => $invoice)
                        <tr>
                            <td> {{$key + 1}} </td>
                            <td> #FWSSi{{$invoice->id}} </td>
                                <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                <td> {{$invoice->client->name}}</td>
                                <td> {{$invoice->client->phone_number}} </td>
                                <td> {{$invoice->client->business_name}} </td>
                                <td> {{$invoice->client->field->name}} </td>
                                <td> {{$invoice->user->name}} </td>
                                <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                <td> {{$invoice->due_date->diffForHumans()}} </td>
                                <td> GH&#x20B5; {{number_format($invoice->balance,2)}} </td>
                            @if($invoice->status == 'completed')
                            <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                            @endif
                            <td>
                                <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                    <i class="icon-base bx bx-bullseye text-primary me-2"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @endif


                        @if(Auth::user()->field?->name == 'Tema')
                        @foreach($tema as $key => $invoice)
                        <tr>
                            <td> {{$key + 1}} </td>
                            <td> #FWSSi{{$invoice->id}} </td>
                                <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                <td> {{$invoice->client->name}}</td>
                                <td> {{$invoice->client->phone_number}} </td>
                                <td> {{$invoice->client->business_name}} </td>
                                <td> {{$invoice->client->field->name}} </td>
                                <td> {{$invoice->user->name}} </td>
                                <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                <td> {{$invoice->due_date->diffForHumans()}} </td>
                                <td> GH&#x20B5; {{number_format($invoice->balance,2)}} </td>
                            @if($invoice->status == 'completed')
                            <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                            @endif
                            <td>
                                <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                    <i class="icon-base bx bx-bullseye text-primary me-2"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @endif


                        @if(Auth::user()->field?->name == 'Takoradi')
                        @foreach($takoradi as $key => $invoice)
                        <tr>
                            <td> {{$key + 1}} </td>
                            <td> #FWSSi{{$invoice->id}} </td>
                                <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                <td> {{$invoice->client->name}}</td>
                                <td> {{$invoice->client->phone_number}} </td>
                                <td> {{$invoice->client->business_name}} </td>
                                <td> {{$invoice->client->field->name}} </td>
                                <td> {{$invoice->user->name}} </td>
                                <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                <td> {{$invoice->due_date->diffForHumans()}} </td>
                                <td> GH&#x20B5; {{number_format($invoice->balance,2)}} </td>
                            @if($invoice->status == 'completed')
                            <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                            @endif
                            <td>
                                <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                    <i class="icon-base bx bx-bullseye text-primary me-2"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @endif


                        @if(Auth::user()->field?->name == 'Koforidua')
                        @foreach($koforidua as $key => $invoice)
                        <tr>
                            <td> {{$key + 1}} </td>
                            <td> #FWSSi{{$invoice->id}} </td>
                                <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                <td> {{$invoice->client->name}}</td>
                                <td> {{$invoice->client->phone_number}} </td>
                                <td> {{$invoice->client->business_name}} </td>
                                <td> {{$invoice->client->field->name}} </td>
                                <td> {{$invoice->user->name}} </td>
                                <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                <td> {{$invoice->due_date->diffForHumans()}} </td>
                                <td> GH&#x20B5; {{number_format($invoice->balance,2)}} </td>
                            @if($invoice->status == 'completed')
                            <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                            @endif
                            <td>
                                <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                    <i class="icon-base bx bx-bullseye text-primary me-2"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @endif


                        @if(Auth::user()->field?->name == 'Kumasi')
                        @foreach($kumasi as $key => $invoice)
                        <tr>
                            <td> {{$key + 1}} </td>
                            <td> #FWSSi{{$invoice->id}} </td>
                                <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                <td> {{$invoice->client->name}}</td>
                                <td> {{$invoice->client->phone_number}} </td>
                                <td> {{$invoice->client->business_name}} </td>
                                <td> {{$invoice->client->field->name}} </td>
                                <td> {{$invoice->user->name}} </td>
                                <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                <td> {{$invoice->due_date->diffForHumans()}} </td>
                                <td> GH&#x20B5; {{number_format($invoice->balance,2)}} </td>
                            @if($invoice->status == 'completed')
                            <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                            @endif
                            <td>
                                <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
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
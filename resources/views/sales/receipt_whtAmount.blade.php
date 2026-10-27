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
                <h3 class="card-header text-primary"> <i class="icon-base bx bx-receipt text-primary me-2"></i> Payment With Holdings @if (isset($month)) <strong> / For Month: {{  \Carbon\Carbon::parse($month)->format('F Y') }}</strong> @endif </h3>
            </div>
        </div><br>

        @if(isset($whtAmountReceiptAmountReceived) && isset($whtAmountReceiptWHTamount))
        <div class="row mb-4">
            <div class="col-lg-12 col-md-6 mb-4 mb-md-0">
                <div style="background:  #e7e7e7ff;" class="card h-100">
                    <div class="card-body">
                            <p class="mb-1"><strong> AMOUNT RECEIVED </strong> </p>
                            <h4 class="card-title mb-3"><strong> GH&#x20B5; {{ number_format($whtAmountReceiptAmountReceived, 2) }}  </strong> </h4>
                            <small class="fw-medium"> <strong> WHT 7.5% : GH&#x20B5; {{ number_format($whtAmountReceiptWHTamount, 2) }} </strong> </small> <br>
                            <small class="fw-medium">  <strong>  TOTAL : GH&#x20B5; {{ number_format($whtAmountReceiptAmountReceived + $whtAmountReceiptWHTamount, 2) }}</strong> </small> <br>
                            <small class="fw-medium">  <strong>  COUNT : {{ count($whtAmountReceipt) }}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))
        <div class="row">
            <div class="col-lg-2">
                <div style="background:  #e7e7e7ff;" class="card h-100">
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
                        <h4 class="card-title mb-3"><strong> &#x20B5; {{ number_format($accraAmountReceived, 2) }} </strong> </h4>
                        <small class="fw-medium"> <strong> WHT 7.5% : GH&#x20B5; {{ number_format($accraWHTAmount, 2) }}</strong> </small> <br>
                        <small class="fw-medium"> <strong> TOTAL : GH&#x20B5; {{ number_format($accraAmountReceived + $accraWHTAmount, 2) }}</strong> </small> <br>
                            <small class="fw-medium">  <strong>  COUNT : {{ count($accra) }}</strong> </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div style="background:  #e7e7e7ff;" class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{ number_format($botweAmountReceived, 2) }}</strong> </h4>
                        <small class="fw-medium"> WHT 7.5% : <strong> {{ number_format($botweWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium"> TOTAL : <strong> {{ number_format($botweAmountReceived + $botweWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium">  <strong>  COUNT : {{ count($botwe) }}</strong> </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2">
                <div style="background:  #e7e7e7ff;" class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{ number_format($shyhillsAmountReceived, 2) }} </strong> </h4>
                        <small class="fw-medium"> WHT 7.5% : <strong> {{ number_format($shyhillsWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium"> TOTAL : <strong> {{ number_format($shyhillsAmountReceived + $shyhillsWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium">  <strong>  COUNT : {{ count($shyhills) }}</strong> </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2">
                <div style="background:  #e7e7e7ff;" class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{ number_format($temaAmountReceived, 2) }} </strong> </h4>
                        <small class="fw-medium"> WHT 7.5% : <strong> {{ number_format($temaWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium"> TOTAL : <strong> {{ number_format($temaAmountReceived + $temaWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium">  <strong>  COUNT : {{ count($tema) }}</strong> </small>
                    </div>
                </div>
            </div>



            <div class="col-lg-2">
                <div style="background:  #e7e7e7ff;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1">TAKORADI</p>
                        <h4 class="card-title mb-3"><strong> &#x20B5; {{ number_format($takoradiAmountReceived, 2) }} </strong> </h4>
                        <small class="fw-medium"><strong> WHT 7.5% :  {{ number_format($takoradiWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium"> <strong> TOTAL : {{ number_format($takoradiAmountReceived + $takoradiWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium">  <strong>  COUNT : {{ count($takoradi) }}</strong> </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div style="background:  #e7e7e7ff;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"> <strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3"> <strong> &#x20B5;{{number_format($koforiduaAmountReceived, 2)}} </strong> </h4>
                        <small class="fw-medium"> <strong> WHT 7.5% : {{ number_format($koforiduaWHTAmount, 2) }} </strong> </small> <br>
                        <small class="fw-medium"><strong> TOTAL :  {{ number_format($koforiduaAmountReceived + $koforiduaWHTAmount, 2) }} </strong> </small> <br>
                        <small class="fw-medium">  <strong>  COUNT : {{ count($koforidua) }}</strong> </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2 m-3">
                <div style="background:  #e7e7e7ff;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3"> <strong> &#x20B5;{{number_format($kumasiAmountReceived, 2)}} </strong> </h4>
                        <small class="fw-medium"> <strong>  WHT 7.5% : {{ number_format($kumasiWHTAmount, 2) }} </strong> </small> <br>
                        <small class="fw-medium">  <strong> TOTAL : {{ number_format($kumasiAmountReceived + $kumasiWHTAmount, 2) }} </strong> </small> <br>
                        <small class="fw-medium">  <strong>  COUNT : {{ count($kumasi) }}</strong> </small>
                    </div>
                </div>
            </div>

        </div>
        @elseif(Auth::user()->field?->name == 'Accra')
        <div class="row">
            <div class="col-lg-2">
                <div style="background:  #e7e7e7ff;" class="card h-100">
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
                        <h4 class="card-title mb-3"><strong> &#x20B5; {{ number_format($accraAmountReceived, 2) }} </strong> </h4>
                        <small class="fw-medium"> <strong> WHT 7.5% : GH&#x20B5; {{ number_format($accraWHTAmount, 2) }}</strong> </small> <br>
                        <small class="fw-medium"> <strong> TOTAL : GH&#x20B5; {{ number_format($accraAmountReceived + $accraWHTAmount, 2) }}</strong> </small> <br>
                            <small class="fw-medium">  <strong>  COUNT : {{ count($accra) }}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


         @if(Auth::user()->field?->name == 'Botwe')
        <div class="row">
            <div class="col-lg-12">
                <div style="background:  #e7e7e7ff;" class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{ number_format($botweAmountReceived, 2) }}</strong> </h4>
                        <small class="fw-medium"> WHT 7.5% : <strong> {{ number_format($botweWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium"> TOTAL : <strong> {{ number_format($botweAmountReceived + $botweWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium">  <strong>  COUNT : {{ count($botwe) }}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Tema')
        <div class="row">
            <div class="col-lg-6">
                <div style="background:  #e7e7e7ff;" class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{ number_format($temaAmountReceived, 2) }} </strong> </h4>
                        <small class="fw-medium"> WHT 7.5% : <strong> {{ number_format($temaWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium"> TOTAL : <strong> {{ number_format($temaAmountReceived + $temaWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium">  <strong>  COUNT : {{ count($tema) }}</strong> </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
                <div style="background:  #e7e7e7ff;" class="card h-100">
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
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{ number_format($shyhillsAmountReceived, 2) }} </strong> </h4>
                        <small class="fw-medium"> WHT 7.5% : <strong> {{ number_format($shyhillsWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium"> TOTAL : <strong> {{ number_format($shyhillsAmountReceived + $shyhillsWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium">  <strong>  COUNT : {{ count($shyhills) }}</strong> </small>
                    </div>
                </div>
            </div>

        @endif

         @if(Auth::user()->field?->name == 'Takoradi')
        <div class="row">
            <div class="col-lg-12">
                <div style="background:  #e7e7e7ff;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1">TAKORADI</p>
                        <h4 class="card-title mb-3"><strong> &#x20B5; {{ number_format($takoradiAmountReceived, 2) }} </strong> </h4>
                        <small class="fw-medium"><strong> WHT 7.5% :  {{ number_format($takoradiWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium"> <strong> TOTAL : {{ number_format($takoradiAmountReceived + $takoradiWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium">  <strong>  COUNT : {{ count($takoradi) }}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Koforidua')
        <div class="row">
            <div class="col-lg-12">
                <div style="background:  #e7e7e7ff;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"> <strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3"> <strong> &#x20B5;{{number_format($koforiduaAmountReceived, 2)}} </strong> </h4>
                        <small class="fw-medium"> <strong> WHT 7.5% : {{ number_format($koforiduaWHTAmount, 2) }} </strong> </small> <br>
                        <small class="fw-medium"><strong> TOTAL :  {{ number_format($koforiduaAmountReceived + $koforiduaWHTAmount, 2) }} </strong> </small> <br>
                        <small class="fw-medium">  <strong>  COUNT : {{ count($koforidua) }}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif



        @if(Auth::user()->field?->name == 'Kumasi')
        <div class="row">
            <div class="col-lg-12">
                <div style="background:  #e7e7e7ff;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3"> <strong> &#x20B5;{{number_format($kumasiAmountReceived, 2)}} </strong> </h4>
                        <small class="fw-medium"> <strong>  WHT 7.5% : {{ number_format($kumasiWHTAmount, 2) }} </strong> </small> <br>
                        <small class="fw-medium">  <strong> TOTAL : {{ number_format($kumasiAmountReceived + $kumasiWHTAmount, 2) }} </strong> </small> <br>
                        <small class="fw-medium">  <strong>  COUNT : {{ count($kumasi) }}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif
        <br><br>

        <div class="row">
            <form action="/searchReceiptsWHTPayment" method="GET">
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
                            <th>id</th>
                            <th>Invoice No.</th>
                            <th>R. Month</th>
                            <th>Client Name</th>
                            <th>Phone No.</th>
                            <!-- <th>Business Name </th> -->
                            <th> Field Office </th>
                            <!-- <th> Stuff </th> -->
                            <th>Date Created</th>
                            <!-- <th>Date Updated</th> -->
                            <!-- <th>Owing</th> -->
                            <th>Inv_Amount</th>
                            <th>WHT_Amount</th>
                            <th>WHT_7.5%</th>
                            <th>Status</th>
                            <th>View</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))
                        @foreach($whtAmountReceipt as $receipt)
                        <tr>
                            <td>FWSSR{{$receipt->id}}</td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> {{$receipt->receipt_month?->format('F, Y')}} </td>
                            @if ($receipt->client->name === $receipt->client->business_name)
                            <td> {{$receipt->client->business_name}} </td>
                            @else
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}} </td>
                            @endif

                            <td> {{$receipt->client->phone_number}} </td>
                            <!-- <td> </td> -->
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>

                            <td> <strong>GH&#x20B5; {{number_format($receipt->total, 2)}} </strong> </td>
                            <td> <strong>GH&#x20B5; {{number_format($receipt->amount_received, 2)}} </strong> </td>
                            <td> <strong>GH&#x20B5; {{number_format($receipt->wht_amount, 2)}} </strong> </td>

                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

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
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> {{$receipt->receipt_month?->format('F, Y')}} </td>
                            @if ($receipt->client->name === $receipt->client->business_name)
                            <td> {{$receipt->client->business_name}} </td>
                            @else
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}} </td>
                            @endif

                            <td> {{$receipt->client->phone_number}} </td>
                            <!-- <td> </td> -->
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>

                            <td> <strong>GH&#x20B5; {{number_format($receipt->total, 2)}} </strong> </td>
                            <td> <strong>GH&#x20B5; {{number_format($receipt->amount_received, 2)}} </strong> </td>
                            <td> <strong>GH&#x20B5; {{number_format($receipt->wht_amount, 2)}} </strong> </td>

                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

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
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> {{$receipt->receipt_month?->format('F, Y')}} </td>
                            @if ($receipt->client->name === $receipt->client->business_name)
                            <td> {{$receipt->client->business_name}} </td>
                            @else
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}} </td>
                            @endif

                            <td> {{$receipt->client->phone_number}} </td>
                            <!-- <td> </td> -->
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>

                            <td> <strong>GH&#x20B5; {{number_format($receipt->total, 2)}} </strong> </td>
                            <td> <strong>GH&#x20B5; {{number_format($receipt->amount_received, 2)}} </strong> </td>
                            <td> <strong>GH&#x20B5; {{number_format($receipt->wht_amount, 2)}} </strong> </td>

                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

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
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> {{$receipt->receipt_month?->format('F, Y')}} </td>
                            @if ($receipt->client->name === $receipt->client->business_name)
                            <td> {{$receipt->client->business_name}} </td>
                            @else
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}} </td>
                            @endif

                            <td> {{$receipt->client->phone_number}} </td>
                            <!-- <td> </td> -->
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>

                            <td> <strong>GH&#x20B5; {{number_format($receipt->total, 2)}} </strong> </td>
                            <td> <strong>GH&#x20B5; {{number_format($receipt->amount_received, 2)}} </strong> </td>
                            <td> <strong>GH&#x20B5; {{number_format($receipt->wht_amount, 2)}} </strong> </td>

                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

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
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> {{$receipt->receipt_month?->format('F, Y')}} </td>
                            @if ($receipt->client->name === $receipt->client->business_name)
                            <td> {{$receipt->client->business_name}} </td>
                            @else
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}} </td>
                            @endif

                            <td> {{$receipt->client->phone_number}} </td>
                            <!-- <td> </td> -->
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>

                            <td> <strong>GH&#x20B5; {{number_format($receipt->total, 2)}} </strong> </td>
                            <td> <strong>GH&#x20B5; {{number_format($receipt->amount_received, 2)}} </strong> </td>
                            <td> <strong>GH&#x20B5; {{number_format($receipt->wht_amount, 2)}} </strong> </td>

                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

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
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> {{$receipt->receipt_month?->format('F, Y')}} </td>
                            @if ($receipt->client->name === $receipt->client->business_name)
                            <td> {{$receipt->client->business_name}} </td>
                            @else
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}} </td>
                            @endif

                            <td> {{$receipt->client->phone_number}} </td>
                            <!-- <td> </td> -->
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>

                            <td> <strong>GH&#x20B5; {{number_format($receipt->total, 2)}} </strong> </td>
                            <td> <strong>GH&#x20B5; {{number_format($receipt->amount_received, 2)}} </strong> </td>
                            <td> <strong>GH&#x20B5; {{number_format($receipt->wht_amount, 2)}} </strong> </td>

                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

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
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> {{$receipt->receipt_month?->format('F, Y')}} </td>
                            @if ($receipt->client->name === $receipt->client->business_name)
                            <td> {{$receipt->client->business_name}} </td>
                            @else
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}} </td>
                            @endif

                            <td> {{$receipt->client->phone_number}} </td>
                            <!-- <td> </td> -->
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>

                            <td> <strong>GH&#x20B5; {{number_format($receipt->total, 2)}} </strong> </td>
                            <td> <strong>GH&#x20B5; {{number_format($receipt->amount_received, 2)}} </strong> </td>
                            <td> <strong>GH&#x20B5; {{number_format($receipt->wht_amount, 2)}} </strong> </td>

                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

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
                    buttons: ['excelHtml5', 'pdfHtml5']
                }
            },
            columnControl: [
                ['search']
            ]
        });
    </script>
    @endsection

</x-sales-dashboard>
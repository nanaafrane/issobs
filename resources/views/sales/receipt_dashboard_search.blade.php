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
                <h3 class="card-header text-primary"> <i class="icon-base bx bx-receipt text-primary me-2"></i> All Payment @if (isset($month)) <strong> / In The Month: {{  \Carbon\Carbon::parse($month)->format('F Y') }}</strong> @endif </h3>
            </div>
        </div><br>

        @if(isset($reportReceiptTotal) && isset($reportReceiptCount) && Auth::user()->hasRole(['Invoice', 'Finance Manager']))
        <div class="row mb-4">
            <div class="col-lg-12 col-md-6 mb-4 mb-md-0">
                <div style="background:  #152356; color: white;" class="card h-100">
                    <div class="card-body">
                            <p class="mb-1"><strong> PAYMENTS  </strong> </p>
                            <h4 class="card-title mb-3 text-white"><strong> GH&#x20B5; {{ number_format($reportReceiptTotal, 2) }}  </strong> </h4>
                            <small class="fw-medium">  <strong>  TOTAL : {{ number_format( $reportReceiptCount, 2) }}</strong> </small> <br>
                    </div>
                </div>
            </div>
        </div>
        @endif

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
            <form action="/receiptSearch" method="GET">
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
                            <th>Receipt Date</th>
                            <th>Invoice No.</th>
                            <th>Inv. Amount</th>
                            <th>Inv. Month</th>
                            <th>Client Name</th>
                            <th>Client Status</th>
                            <th>Phone No.</th>
                            <th> Field Office </th>
                            <th> Staff </th>
                            <th>Date Created</th>
                            <th>Inv Amount</th>
                            <th>Paid</th>

                            <th>Cheque Bank</th>
                            <th>Cheque Ref</th>
                            <th>Cheque Amnt</th>

                            <th>Transfer Bank</th>
                            <th>Transfer Ref</th>
                            <th>Transfer Amnt</th>

                            <th>MoMo </th>
                            <th>Cash </th>

                            <th>Deductions</th>
                            <th>Other Payment</th>
                            <th>WHT</th>
                            <th>VAT 7%</th>

                            <th>Balance</th>
                            <th>Advance</th>
                            <th>Status</th>
                            <th>View</th>
                        </tr>
                    </thead>

                    <tbody>

                        @if(Auth::user()->hasRole(['Invoice','Finance Manager']))
                        @foreach($reportReceipt as $receipt)
                        <tr>
                            <td>FWSSR{{$receipt->id}}</td>
                            <td> {{ $receipt->receipt_month?->format('l, F j, Y') }} </td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> GH&#x20B5; {{$receipt->invoice->total}} </td>
                            <td> {{ $receipt->invoice?->invoice_month?->format('F, Y') }} </td>
                            @if ($receipt->client->name === $receipt->client->business_name)
                            <td> {{$receipt->client->business_name}} </td>
                            @else
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}} </td>
                            @endif
                            <td> 
                                @if($receipt->client?->status == 'terminated')
                                <span class="badge bg-label-danger">{{ $receipt->client?->status }}</span>
                                @else
                                <span class="badge bg-label-success">{{ $receipt->client?->status }}</span>
                                @endif
                            </td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>
                            <!-- <td>GH&#x20B5; {{$receipt->invoice->total}} </td> -->
                            <td>  GH&#x20B5; {{$receipt->invoice->total}}</td>

                            <td> GH&#x20B5; {{$receipt->total}} </td>

                            <td> {{$receipt->cheque_bank}} </td>
                            <td> {{$receipt->cheque_reference}} </td>
                            <td> GH&#x20B5; {{$receipt->cheque_amount}} </td>
                            <td> {{$receipt->transfer_bank}} </td>
                            <td> {{$receipt->transfer_reference}} </td>
                            <td> GH&#x20B5; {{$receipt->transfer_amount}} </td>
                            <td> GH&#x20B5; {{$receipt->momo_amount}} </td>
                            <td> GH&#x20B5; {{$receipt->cash_amount}} </td>


                           <td> GH&#x20B5; {{$receipt->dAmount}} </td>
                           <td> GH&#x20B5; {{$receipt->other_payment_amnt}} </td>
                           <td> GH&#x20B5; {{$receipt->wht_amount}} </td>
                           <td> GH&#x20B5; {{$receipt->vat7_value}} </td>

                            <td> GH&#x20B5;  {{ $receipt->invoice->total - $receipt->total - $receipt->dAmount }} </td>
                            <td> {{ $receipt->advance_payment }} </td>
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
                            <td> {{ $receipt->receipt_month?->format('l, F j, Y') }} </td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> GH&#x20B5; {{$receipt->invoice->total}} </td>
                            <td> {{ $receipt->invoice?->invoice_month?->format('F, Y') }} </td>
                            @if ($receipt->client->name === $receipt->client->business_name)
                            <td> {{$receipt->client->business_name}} </td>
                            @else
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}} </td>
                            @endif
                            <td> 
                                @if($receipt->client?->status == 'terminated')
                                <span class="badge bg-label-danger">{{ $receipt->client?->status }}</span>
                                @else
                                <span class="badge bg-label-success">{{ $receipt->client?->status }}</span>
                                @endif
                            </td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>
                            <!-- <td>GH&#x20B5; {{$receipt->invoice->total}} </td> -->
                            <td>  GH&#x20B5; {{$receipt->invoice->total}}</td>

                            <td> GH&#x20B5; {{$receipt->total}} </td>

                            <td> {{$receipt->cheque_bank}} </td>
                            <td> {{$receipt->cheque_reference}} </td>
                            <td> GH&#x20B5; {{$receipt->cheque_amount}} </td>
                            <td> {{$receipt->transfer_bank}} </td>
                            <td> {{$receipt->transfer_reference}} </td>
                            <td> GH&#x20B5; {{$receipt->transfer_amount}} </td>
                            <td> GH&#x20B5; {{$receipt->momo_amount}} </td>
                            <td> GH&#x20B5; {{$receipt->cash_amount}} </td>


                           <td> GH&#x20B5; {{$receipt->dAmount}} </td>
                           <td> GH&#x20B5; {{$receipt->other_payment_amnt}} </td>
                           <td> GH&#x20B5; {{$receipt->wht_amount}} </td>
                           <td> GH&#x20B5; {{$receipt->vat7_value}} </td>

                            <td> GH&#x20B5;  {{ $receipt->invoice->total - $receipt->total - $receipt->dAmount }} </td>
                            <td> {{ $receipt->advance_payment }} </td>
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
                            <td> {{ $receipt->receipt_month?->format('l, F j, Y') }} </td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> GH&#x20B5; {{$receipt->invoice->total}} </td>
                            <td> {{ $receipt->invoice?->invoice_month?->format('F, Y') }} </td>
                            @if ($receipt->client->name === $receipt->client->business_name)
                            <td> {{$receipt->client->business_name}} </td>
                            @else
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}} </td>
                            @endif
                            <td> 
                                @if($receipt->client?->status == 'terminated')
                                <span class="badge bg-label-danger">{{ $receipt->client?->status }}</span>
                                @else
                                <span class="badge bg-label-success">{{ $receipt->client?->status }}</span>
                                @endif
                            </td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>
                            <!-- <td>GH&#x20B5; {{$receipt->invoice->total}} </td> -->
                            <td>  GH&#x20B5; {{$receipt->invoice->total}}</td>

                            <td> GH&#x20B5; {{$receipt->total}} </td>

                            <td> {{$receipt->cheque_bank}} </td>
                            <td> {{$receipt->cheque_reference}} </td>
                            <td> GH&#x20B5; {{$receipt->cheque_amount}} </td>
                            <td> {{$receipt->transfer_bank}} </td>
                            <td> {{$receipt->transfer_reference}} </td>
                            <td> GH&#x20B5; {{$receipt->transfer_amount}} </td>
                            <td> GH&#x20B5; {{$receipt->momo_amount}} </td>
                            <td> GH&#x20B5; {{$receipt->cash_amount}} </td>


                           <td> GH&#x20B5; {{$receipt->dAmount}} </td>
                           <td> GH&#x20B5; {{$receipt->other_payment_amnt}} </td>
                           <td> GH&#x20B5; {{$receipt->wht_amount}} </td>
                           <td> GH&#x20B5; {{$receipt->vat7_value}} </td>

                            <td> GH&#x20B5;  {{ $receipt->invoice->total - $receipt->total - $receipt->dAmount }} </td>
                            <td> {{ $receipt->advance_payment }} </td>
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
                            <td> {{ $receipt->receipt_month?->format('l, F j, Y') }} </td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> GH&#x20B5; {{$receipt->invoice->total}} </td>
                            <td> {{ $receipt->invoice?->invoice_month?->format('F, Y') }} </td>
                            @if ($receipt->client->name === $receipt->client->business_name)
                            <td> {{$receipt->client->business_name}} </td>
                            @else
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}} </td>
                            @endif
                            <td> 
                                @if($receipt->client?->status == 'terminated')
                                <span class="badge bg-label-danger">{{ $receipt->client?->status }}</span>
                                @else
                                <span class="badge bg-label-success">{{ $receipt->client?->status }}</span>
                                @endif
                            </td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>
                            <!-- <td>GH&#x20B5; {{$receipt->invoice->total}} </td> -->
                            <td>  GH&#x20B5; {{$receipt->invoice->total}}</td>

                            <td> GH&#x20B5; {{$receipt->total}} </td>

                            <td> {{$receipt->cheque_bank}} </td>
                            <td> {{$receipt->cheque_reference}} </td>
                            <td> GH&#x20B5; {{$receipt->cheque_amount}} </td>
                            <td> {{$receipt->transfer_bank}} </td>
                            <td> {{$receipt->transfer_reference}} </td>
                            <td> GH&#x20B5; {{$receipt->transfer_amount}} </td>
                            <td> GH&#x20B5; {{$receipt->momo_amount}} </td>
                            <td> GH&#x20B5; {{$receipt->cash_amount}} </td>


                           <td> GH&#x20B5; {{$receipt->dAmount}} </td>
                           <td> GH&#x20B5; {{$receipt->other_payment_amnt}} </td>
                           <td> GH&#x20B5; {{$receipt->wht_amount}} </td>
                           <td> GH&#x20B5; {{$receipt->vat7_value}} </td>

                            <td> GH&#x20B5;  {{ $receipt->invoice->total - $receipt->total - $receipt->dAmount }} </td>
                            <td> {{ $receipt->advance_payment }} </td>
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
                            <td> {{ $receipt->receipt_month?->format('l, F j, Y') }} </td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> GH&#x20B5; {{$receipt->invoice->total}} </td>
                            <td> {{ $receipt->invoice?->invoice_month?->format('F, Y') }} </td>
                            @if ($receipt->client->name === $receipt->client->business_name)
                            <td> {{$receipt->client->business_name}} </td>
                            @else
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}} </td>
                            @endif
                            <td> 
                                @if($receipt->client?->status == 'terminated')
                                <span class="badge bg-label-danger">{{ $receipt->client?->status }}</span>
                                @else
                                <span class="badge bg-label-success">{{ $receipt->client?->status }}</span>
                                @endif
                            </td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>
                            <!-- <td>GH&#x20B5; {{$receipt->invoice->total}} </td> -->
                            <td>  GH&#x20B5; {{$receipt->invoice->total}}</td>

                            <td> GH&#x20B5; {{$receipt->total}} </td>

                            <td> {{$receipt->cheque_bank}} </td>
                            <td> {{$receipt->cheque_reference}} </td>
                            <td> GH&#x20B5; {{$receipt->cheque_amount}} </td>
                            <td> {{$receipt->transfer_bank}} </td>
                            <td> {{$receipt->transfer_reference}} </td>
                            <td> GH&#x20B5; {{$receipt->transfer_amount}} </td>
                            <td> GH&#x20B5; {{$receipt->momo_amount}} </td>
                            <td> GH&#x20B5; {{$receipt->cash_amount}} </td>


                           <td> GH&#x20B5; {{$receipt->dAmount}} </td>
                           <td> GH&#x20B5; {{$receipt->other_payment_amnt}} </td>
                           <td> GH&#x20B5; {{$receipt->wht_amount}} </td>
                           <td> GH&#x20B5; {{$receipt->vat7_value}} </td>

                            <td> GH&#x20B5;  {{ $receipt->invoice->total - $receipt->total - $receipt->dAmount }} </td>
                            <td> {{ $receipt->advance_payment }} </td>
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
                            <td> {{ $receipt->receipt_month?->format('l, F j, Y') }} </td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> GH&#x20B5; {{$receipt->invoice->total}} </td>
                            <td> {{ $receipt->invoice?->invoice_month?->format('F, Y') }} </td>
                            @if ($receipt->client->name === $receipt->client->business_name)
                            <td> {{$receipt->client->business_name}} </td>
                            @else
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}} </td>
                            @endif
                            <td> 
                                @if($receipt->client?->status == 'terminated')
                                <span class="badge bg-label-danger">{{ $receipt->client?->status }}</span>
                                @else
                                <span class="badge bg-label-success">{{ $receipt->client?->status }}</span>
                                @endif
                            </td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>
                            <!-- <td>GH&#x20B5; {{$receipt->invoice->total}} </td> -->
                            <td>  GH&#x20B5; {{$receipt->invoice->total}}</td>

                            <td> GH&#x20B5; {{$receipt->total}} </td>

                            <td> {{$receipt->cheque_bank}} </td>
                            <td> {{$receipt->cheque_reference}} </td>
                            <td> GH&#x20B5; {{$receipt->cheque_amount}} </td>
                            <td> {{$receipt->transfer_bank}} </td>
                            <td> {{$receipt->transfer_reference}} </td>
                            <td> GH&#x20B5; {{$receipt->transfer_amount}} </td>
                            <td> GH&#x20B5; {{$receipt->momo_amount}} </td>
                            <td> GH&#x20B5; {{$receipt->cash_amount}} </td>


                           <td> GH&#x20B5; {{$receipt->dAmount}} </td>
                           <td> GH&#x20B5; {{$receipt->other_payment_amnt}} </td>
                           <td> GH&#x20B5; {{$receipt->wht_amount}} </td>
                           <td> GH&#x20B5; {{$receipt->vat7_value}} </td>

                            <td> GH&#x20B5;  {{ $receipt->invoice->total - $receipt->total - $receipt->dAmount }} </td>
                            <td> {{ $receipt->advance_payment }} </td>
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
                            <td> {{ $receipt->receipt_month?->format('l, F j, Y') }} </td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> GH&#x20B5; {{$receipt->invoice->total}} </td>
                            <td> {{ $receipt->invoice?->invoice_month?->format('F, Y') }} </td>
                            @if ($receipt->client->name === $receipt->client->business_name)
                            <td> {{$receipt->client->business_name}} </td>
                            @else
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}} </td>
                            @endif
                            <td> 
                                @if($receipt->client?->status == 'terminated')
                                <span class="badge bg-label-danger">{{ $receipt->client?->status }}</span>
                                @else
                                <span class="badge bg-label-success">{{ $receipt->client?->status }}</span>
                                @endif
                            </td>
                            <td> {{$receipt->client->phone_number}} </td>
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->user->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>
                            <!-- <td>GH&#x20B5; {{$receipt->invoice->total}} </td> -->
                            <td>  GH&#x20B5; {{$receipt->invoice->total}}</td>

                            <td> GH&#x20B5; {{$receipt->total}} </td>

                            <td> {{$receipt->cheque_bank}} </td>
                            <td> {{$receipt->cheque_reference}} </td>
                            <td> GH&#x20B5; {{$receipt->cheque_amount}} </td>
                            <td> {{$receipt->transfer_bank}} </td>
                            <td> {{$receipt->transfer_reference}} </td>
                            <td> GH&#x20B5; {{$receipt->transfer_amount}} </td>
                            <td> GH&#x20B5; {{$receipt->momo_amount}} </td>
                            <td> GH&#x20B5; {{$receipt->cash_amount}} </td>


                           <td> GH&#x20B5; {{$receipt->dAmount}} </td>
                           <td> GH&#x20B5; {{$receipt->other_payment_amnt}} </td>
                           <td> GH&#x20B5; {{$receipt->wht_amount}} </td>
                           <td> GH&#x20B5; {{$receipt->vat7_value}} </td>

                            <td> GH&#x20B5;  {{ $receipt->invoice->total - $receipt->total - $receipt->dAmount }} </td>
                            <td> {{ $receipt->advance_payment }} </td>
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
                buttons: [ 
                {
                     extend: 'pageLength',
                    text: 'Show',
                    className: 'btn btn-secondary',
                    Options: [10, 25, 50, 100, 500], 
                },
                    {
                        extend: 'excelHtml5',
                        title: 'Receipts',
                        className: 'btn btn-secondary',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    'colvis'
                ]
            }


            },
            columnControl: [
                ['search']
            ]
        });
    </script>

    @endsection

</x-sales-dashboard>
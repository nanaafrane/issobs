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
                <h3 class="card-header text-primary"> <i class="icon-base bx bx-receipt text-primary me-2"></i> All Invoices  @if (isset($month)) <strong> / For Month: {{  \Carbon\Carbon::parse($month)->format('F Y') }}</strong> @endif </h3>
            </div>
        </div><br>

        @if(isset($invoiceTotal) && isset($invoiceCount) && Auth::user()->hasRole(['Invoice', 'Finance Manager']))
            <div class="row mb-4">
            <div class="col-lg-12 col-md-6 mb-4 mb-md-0">
                <div  class="card h-100 bg-danger text-white">
                    <div class="card-body">
                            <p class="mb-1"><strong> INVOICES </strong> </p>
                            <h4 class="card-title mb-3 text-white"><strong> GH&#x20B5; {{ number_format($invoiceTotal, 2) }}  </strong> </h4>
                            <small class="fw-medium"> TOTAL INVOICES GENERATED  / TOTAL NUMBER OF INVOICE GUARDS : {{ $invoiceCount }}  / {{ $reportInvoicesGuards }} </small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))
        <div class="row">
            <div class="col-lg-2">
                <div style="background: crimson; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> ACCRA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($accraTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES  : <strong> {{$accraCount}}  </strong> </small>
                        <hr>
                        <small class="fw-medium"> INVOICE GUARDS : <strong>  {{ $accraGuards }}</strong> </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div style="background: crimson; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> BOTWE </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($botweTotal,2)}}</strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$botweCount}} </strong> </small>
                        <hr>
                        <small class="fw-medium"> INVOICE GUARDS : <strong> {{$botweGuards}} </strong> </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2">
                <div style="background: crimson; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> SHAIILLS </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($shyhillsTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$shyhillsCount}} </strong> </small>
                        <hr>
                        <small class="fw-medium"> INVOICE GUARDS : <strong> {{$shyhillsGuards}} </strong> </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2">
                <div style="background: crimson; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> TEMA </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($temaTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$temaCount}} </strong> </small>
                        <hr>
                        <small class="fw-medium"> INVOICE GUARDS : <strong> {{$temaGuards}} </strong> </small>
                    </div>
                </div>
            </div>



            <div class="col-lg-2">
                <div style="background: crimson; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong>TAKORADI </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{number_format($takoradiTotal,2)}}</strong></h4>
                        <small class="fw-medium"> TOTAL INVOICES : {{$takoradiCount}} </small>
                        <hr>
                        <small class="fw-medium"> INVOICE GUARDS : {{$takoradiGuards}} </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div style="background: crimson; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"> <strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5;{{number_format($koforiduaTotal,2)}}</strong></h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$koforiduaCount}} </strong> </small>
                        <hr>
                        <small class="fw-medium"> INVOICE GUARDS : <strong> {{$koforiduaGuards}} </strong> </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2 m-3">
                <div style="background: crimson; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5;{{number_format($kumasiTotal,2)}}</strong></h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$kumasiCount}} </strong> </small>
                        <hr>
                        <small class="fw-medium"> INVOICE GUARDS : <strong> {{$kumasiGuards}} </strong> </small>
                    </div>
                </div>
            </div>

        </div>
        @elseif(Auth::user()->field?->name == 'Accra')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: crimson; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> ACCRA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($accraTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$accraCount}}</strong> </small>
                        <hr>
                        <small class="fw-medium"> INVOICE GUARDS : <strong>  {{ $accraGuards }}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(Auth::user()->field?->name == 'Botwe')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: crimson; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> BOTWE </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($botweTotal,2) }} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$botweCount}}</strong> </small>
                        <hr>
                        <small class="fw-medium"> INVOICE GUARDS : <strong> {{$botweGuards}} </strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(Auth::user()->field?->name == 'Tema')
        <div class="row">
            <div class="col-xxl-6 mb-6 order-0">
                <div style="background: crimson; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> SHAIILLS </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($shyhillsTotal) }} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$shyhillsCount}}</strong> </small>
                         <hr>
                        <small class="fw-medium"> INVOICE GUARDS : <strong> {{$shyhillsGuards}} </strong> </small>
                    </div>
                </div>
            </div>

            <div class="col-xxl-6 mb-6 order-0">
                <div style="background: crimson; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> TEMA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{ number_format($temaTotal,2) }} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$temaCount}}</strong> </small>
                        <hr>
                        <small class="fw-medium"> INVOICE GUARDS : <strong> {{$temaGuards}} </strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Takoradi')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: crimson; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> TAKORADI </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($takoradiTotal,2) }} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$takoradiCount}}</strong> </small>
                        <hr>
                        <small class="fw-medium"> INVOICE GUARDS : {{$takoradiGuards}} </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Koforidua')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: crimson; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($koforiduaTotal,2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$koforiduaCount}}</strong> </small>
                                                <hr>
                        <small class="fw-medium"> INVOICE GUARDS : <strong> {{$koforiduaGuards}} </strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Kumasi')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: crimson; color: white;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{ number_format($kumasiTotal,2) }} </strong> </h4>
                        <small class="fw-medium"> TOTAL INVOICES : <strong> {{$kumasiCount}}</strong> </small>
                                                <hr>
                        <small class="fw-medium"> INVOICE GUARDS : <strong> {{$kumasiGuards}} </strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif
        <br><br>

        <div class="row">
            <form action="/invoiceSearch" method="GET">
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
                            <th>Client Status</th>
                            <th>Phone No.</th>
                            <th> Field Office </th>
                            <th> Staff </th>
                            <th>Date Created</th>
                            <th>Due Date</th>
                            <th>Service </th>
                            <th> Description</th>
                            <th> Gurds </th>
                            <th> Rate </th>
                            <th> Invoice Value </th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Advance</th>
                            <th>Action</th>

                            <th>Reciept Date</th>
                            <th>Deductions</th>
                            <th>Ded Desc</th>
                            <th>Other Payment</th>

                            <th>WHT</th>
                            <th>VAT 7%</th>

                            <th>Cheque Bank</th>
                            <th>Cheque Ref</th>
                            <th>Cheque Amnt</th>

                            <th>Transfer Bank</th>
                            <th>Transfer Ref</th>
                            <th>Transfer Amnt</th>

                            <th>MoMo </th>
                            <th>Cash </th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>

                        @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))
                        @foreach($reportInvoices as $key => $invoice)
                        <tr>
                             <td>{{$key +1 }}</td>
                                <td> FWSSi{{$invoice->id}} </td>
                                <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                @if ($invoice->client->name === $invoice->client->business_name)
                                <td> {{$invoice->client->business_name}} </td>
                                @else
                                <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                @endif
                                <td> 
                                    @if($invoice->client?->status == 'terminated')
                                    <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                    @else
                                    <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                    @endif
                                </td>
                                <td> {{$invoice->client?->phone_number}} </td>
                                <td> {{$invoice->client?->field?->name}} </td>
                                <td> {{$invoice->user?->name}} </td>
                                <td> {{$invoice->created_at?->format('F l d, Y, H:i A')}} </td>
                                <td> {{$invoice->due_date?->diffForHumans()}} </td>
                               
                               
                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $num => $service )
                                            {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                        @endforeach
                                    @else
                                        @foreach ( $invoice->invoice_data as $service )
                                             {{ $service->service_name }} 
                                        @endforeach
                                    @endif

                                </td>
                               
                            
                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $des => $description )
                                      
                                         {{  $des +1  }}. {{ $description->description }} <br> <br>
                                           
                                       @endforeach
                                   
                                    @else
                                       @foreach ( $invoice->invoice_data as $description )
                                      
                                         {{ $description->description }}
                                           
                                       @endforeach
                                   
                                    @endif
                                </td>

                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $guad => $guards )
                                      
                                          {{  $guad +1  }}.  {{ $guards->counts_as_guards ? $guards->quantity : '— (deduction)' }} <br> <br>
                                           
                                       @endforeach
                                   
                                    </ol>
                                    @else
                                       @foreach ( $invoice->invoice_data as $guards )
                                      
                                          {{ $guards->counts_as_guards ? $guards->quantity : '— (deduction)' }} 
                                           
                                       @endforeach
                                   
                                    </ol>
                                    @endif


                                </td>

                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $rat => $rate )
                                      
                                         {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                           
                                       @endforeach
                                   
                                    @else
                                       @foreach ( $invoice->invoice_data as $rate )
                                      
                                          GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                           
                                       @endforeach
                                   
                                    @endif

                                </td>

                                <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                @if ($invoice->status == 'completed')
                                <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                @elseif($invoice->status == 'uncompleted')
                                <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                @else
                                <td> GH&#x20B5; 0.00 </td>
                                @endif

                                @if ($invoice->status == 'unpaid')
                                <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                @else
                                <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                @endif


                                @if($invoice->status == 'completed')
                                <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                @else
                                <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                @endif
                                <td>
                                    @foreach ( $invoice->receipt as $adva => $advance )
                                      
                                            {{ $advance?->advance_payment }}  <br> <br>
                                           
                                       @endforeach

                                </td>
                                <td>
                                    <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                        <i class="icon-base bx bx-bullseye text-primary me-2"></i>
                                    </a>
                                </td>

                                <td>
                                    @foreach ( $invoice->receipt as $dedate => $ddate )
                                      
                                        {{ $ddate->receipt_month?->format('F l d, Y') }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $deda => $dama )
                                      
                                        {{ $dama->dAmount }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $dedesc => $dedescript )
                                      
                                        {{ $dedescript->description }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $other => $otherpay )
                                      
                                        {{ $otherpay->other_payment_amnt }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $wht => $whtamnt )
                                      
                                        {{ $whtamnt->wht_amount }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $vat => $vat7 )
                                      
                                        {{ $vat7->vat7_value }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                    @foreach ( $invoice->receipt as $cheq => $chequ )
                                      
                                        {{ $chequ->cheque_bank }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                      @foreach ( $invoice->receipt as $cheqre => $chequref )
                                      
                                        {{ $chequref->cheque_reference }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $cheqama => $cheqamnt )
                                      
                                       GH&#x20B5; {{ number_format($cheqamnt->cheque_amount, 2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $trans => $tansfer )
                                      
                                        {{ $tansfer->transfer_bank }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td> 
                                      @foreach ( $invoice->receipt as $transref => $tansferref )
                                      
                                        {{ $tansferref->transfer_reference }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $transama => $transamnt )
                                      
                                        {{ $transamnt->transfer_amount }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $mo => $momo )
                                      
                                        GH&#x20B5;{{ number_format($momo->momo_amount,2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $ca => $cash )
                                      
                                        GH&#x20B5;{{ number_format($cash->cash_amount,2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                         @foreach ( $invoice->receipt as $tot => $total )
                                      
                                        GH&#x20B5;{{ number_format($total->total,2) }} <br> <br>
                                           
                                       @endforeach  
                                </td>
                        </tr>
                        @endforeach



                        @elseif(Auth::user()->field?->name == 'Accra')
                        @foreach($accra as $key => $invoice)
                        <tr>
                             <td>{{$key +1 }}</td>
                                <td> FWSSi{{$invoice->id}} </td>
                                <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                @if ($invoice->client->name === $invoice->client->business_name)
                                <td> {{$invoice->client->business_name}} </td>
                                @else
                                <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                @endif
                                <td> 
                                    @if($invoice->client?->status == 'terminated')
                                    <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                    @else
                                    <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                    @endif
                                </td>
                                <td> {{$invoice->client?->phone_number}} </td>
                                <td> {{$invoice->client?->field?->name}} </td>
                                <td> {{$invoice->user?->name}} </td>
                                <td> {{$invoice->created_at?->format('F l d, Y, H:i A')}} </td>
                                <td> {{$invoice->due_date?->diffForHumans()}} </td>
                               
                               
                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $num => $service )
                                            {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                        @endforeach
                                    @else
                                        @foreach ( $invoice->invoice_data as $service )
                                             {{ $service->service_name }} 
                                        @endforeach
                                    @endif

                                </td>
                               
                            
                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $des => $description )
                                      
                                         {{  $des +1  }}. {{ $description->description }} <br> <br>
                                           
                                       @endforeach
                                   
                                    @else
                                       @foreach ( $invoice->invoice_data as $description )
                                      
                                         {{ $description->description }}
                                           
                                       @endforeach
                                   
                                    @endif
                                </td>

                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $guad => $guards )
                                      
                                          {{  $guad +1  }}.  {{ $guards->counts_as_guards ? $guards->quantity : '— (deduction)' }} <br> <br>
                                           
                                       @endforeach
                                   
                                    </ol>
                                    @else
                                       @foreach ( $invoice->invoice_data as $guards )
                                      
                                          {{ $guards->counts_as_guards ? $guards->quantity : '— (deduction)' }} 
                                           
                                       @endforeach
                                   
                                    </ol>
                                    @endif


                                </td>

                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $rat => $rate )
                                      
                                         {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                           
                                       @endforeach
                                   
                                    @else
                                       @foreach ( $invoice->invoice_data as $rate )
                                      
                                          GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                           
                                       @endforeach
                                   
                                    @endif

                                </td>

                                <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                @if ($invoice->status == 'completed')
                                <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                @elseif($invoice->status == 'uncompleted')
                                <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                @else
                                <td> GH&#x20B5; 0.00 </td>
                                @endif

                                @if ($invoice->status == 'unpaid')
                                <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                @else
                                <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                @endif


                                @if($invoice->status == 'completed')
                                <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                @else
                                <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                @endif
                                <td>
                                    @foreach ( $invoice->receipt as $adva => $advance )
                                      
                                            {{ $advance?->advance_payment }}  <br> <br>
                                           
                                       @endforeach

                                </td>
                                <td>
                                    <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                        <i class="icon-base bx bx-bullseye text-primary me-2"></i>
                                    </a>
                                </td>

                                <td>
                                    @foreach ( $invoice->receipt as $dedate => $ddate )
                                      
                                        {{ $ddate->receipt_month?->format('F l d, Y') }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $deda => $dama )
                                      
                                        {{ $dama->dAmount }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $dedesc => $dedescript )
                                      
                                        {{ $dedescript->description }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $other => $otherpay )
                                      
                                        {{ $otherpay->other_payment_amnt }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $wht => $whtamnt )
                                      
                                        {{ $whtamnt->wht_amount }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $vat => $vat7 )
                                      
                                        {{ $vat7->vat7_value }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                    @foreach ( $invoice->receipt as $cheq => $chequ )
                                      
                                        {{ $chequ->cheque_bank }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                      @foreach ( $invoice->receipt as $cheqre => $chequref )
                                      
                                        {{ $chequref->cheque_reference }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $cheqama => $cheqamnt )
                                      
                                       GH&#x20B5; {{ number_format($cheqamnt->cheque_amount, 2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $trans => $tansfer )
                                      
                                        {{ $tansfer->transfer_bank }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td> 
                                      @foreach ( $invoice->receipt as $transref => $tansferref )
                                      
                                        {{ $tansferref->transfer_reference }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $transama => $transamnt )
                                      
                                        {{ $transamnt->transfer_amount }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $mo => $momo )
                                      
                                        GH&#x20B5;{{ number_format($momo->momo_amount,2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $ca => $cash )
                                      
                                        GH&#x20B5;{{ number_format($cash->cash_amount,2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                         @foreach ( $invoice->receipt as $tot => $total )
                                      
                                        GH&#x20B5;{{ number_format($total->total,2) }} <br> <br>
                                           
                                       @endforeach  
                                </td>
                        </tr>
                        @endforeach
                        @endif


                        @if(Auth::user()->field?->name == 'Botwe')
                        @foreach($botwe as $key => $invoice)
                        <tr>
                             <td>{{$key +1 }}</td>
                                <td> FWSSi{{$invoice->id}} </td>
                                <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                @if ($invoice->client->name === $invoice->client->business_name)
                                <td> {{$invoice->client->business_name}} </td>
                                @else
                                <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                @endif
                                <td> 
                                    @if($invoice->client?->status == 'terminated')
                                    <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                    @else
                                    <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                    @endif
                                </td>
                                <td> {{$invoice->client?->phone_number}} </td>
                                <td> {{$invoice->client?->field?->name}} </td>
                                <td> {{$invoice->user?->name}} </td>
                                <td> {{$invoice->created_at?->format('F l d, Y, H:i A')}} </td>
                                <td> {{$invoice->due_date?->diffForHumans()}} </td>
                               
                               
                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $num => $service )
                                            {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                        @endforeach
                                    @else
                                        @foreach ( $invoice->invoice_data as $service )
                                             {{ $service->service_name }} 
                                        @endforeach
                                    @endif

                                </td>
                               
                            
                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $des => $description )
                                      
                                         {{  $des +1  }}. {{ $description->description }} <br> <br>
                                           
                                       @endforeach
                                   
                                    @else
                                       @foreach ( $invoice->invoice_data as $description )
                                      
                                         {{ $description->description }}
                                           
                                       @endforeach
                                   
                                    @endif
                                </td>

                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $guad => $guards )
                                      
                                          {{  $guad +1  }}.  {{ $guards->counts_as_guards ? $guards->quantity : '— (deduction)' }} <br> <br>
                                           
                                       @endforeach
                                   
                                    </ol>
                                    @else
                                       @foreach ( $invoice->invoice_data as $guards )
                                      
                                          {{ $guards->counts_as_guards ? $guards->quantity : '— (deduction)' }} 
                                           
                                       @endforeach
                                   
                                    </ol>
                                    @endif


                                </td>

                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $rat => $rate )
                                      
                                         {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                           
                                       @endforeach
                                   
                                    @else
                                       @foreach ( $invoice->invoice_data as $rate )
                                      
                                          GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                           
                                       @endforeach
                                   
                                    @endif

                                </td>

                                <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                @if ($invoice->status == 'completed')
                                <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                @elseif($invoice->status == 'uncompleted')
                                <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                @else
                                <td> GH&#x20B5; 0.00 </td>
                                @endif

                                @if ($invoice->status == 'unpaid')
                                <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                @else
                                <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                @endif


                                @if($invoice->status == 'completed')
                                <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                @else
                                <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                @endif
                                <td>
                                    @foreach ( $invoice->receipt as $adva => $advance )
                                      
                                            {{ $advance?->advance_payment }}  <br> <br>
                                           
                                       @endforeach

                                </td>
                                <td>
                                    <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                        <i class="icon-base bx bx-bullseye text-primary me-2"></i>
                                    </a>
                                </td>

                                <td>
                                    @foreach ( $invoice->receipt as $dedate => $ddate )
                                      
                                        {{ $ddate->receipt_month?->format('F l d, Y') }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $deda => $dama )
                                      
                                        {{ $dama->dAmount }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $dedesc => $dedescript )
                                      
                                        {{ $dedescript->description }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $other => $otherpay )
                                      
                                        {{ $otherpay->other_payment_amnt }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $wht => $whtamnt )
                                      
                                        {{ $whtamnt->wht_amount }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $vat => $vat7 )
                                      
                                        {{ $vat7->vat7_value }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                    @foreach ( $invoice->receipt as $cheq => $chequ )
                                      
                                        {{ $chequ->cheque_bank }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                      @foreach ( $invoice->receipt as $cheqre => $chequref )
                                      
                                        {{ $chequref->cheque_reference }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $cheqama => $cheqamnt )
                                      
                                       GH&#x20B5; {{ number_format($cheqamnt->cheque_amount, 2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $trans => $tansfer )
                                      
                                        {{ $tansfer->transfer_bank }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td> 
                                      @foreach ( $invoice->receipt as $transref => $tansferref )
                                      
                                        {{ $tansferref->transfer_reference }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $transama => $transamnt )
                                      
                                        {{ $transamnt->transfer_amount }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $mo => $momo )
                                      
                                        GH&#x20B5;{{ number_format($momo->momo_amount,2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $ca => $cash )
                                      
                                        GH&#x20B5;{{ number_format($cash->cash_amount,2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                         @foreach ( $invoice->receipt as $tot => $total )
                                      
                                        GH&#x20B5;{{ number_format($total->total,2) }} <br> <br>
                                           
                                       @endforeach  
                                </td>
                        </tr>
                        @endforeach
                        @endif


                        @if(Auth::user()->field?->name == 'Tema')
                        @foreach($temashai as $key => $invoice)
                        <tr>
                             <td>{{$key +1 }}</td>
                                <td> FWSSi{{$invoice->id}} </td>
                                <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                @if ($invoice->client->name === $invoice->client->business_name)
                                <td> {{$invoice->client->business_name}} </td>
                                @else
                                <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                @endif
                                <td> 
                                    @if($invoice->client?->status == 'terminated')
                                    <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                    @else
                                    <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                    @endif
                                </td>
                                <td> {{$invoice->client?->phone_number}} </td>
                                <td> {{$invoice->client?->field?->name}} </td>
                                <td> {{$invoice->user?->name}} </td>
                                <td> {{$invoice->created_at?->format('F l d, Y, H:i A')}} </td>
                                <td> {{$invoice->due_date?->diffForHumans()}} </td>
                               
                               
                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $num => $service )
                                            {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                        @endforeach
                                    @else
                                        @foreach ( $invoice->invoice_data as $service )
                                             {{ $service->service_name }} 
                                        @endforeach
                                    @endif

                                </td>
                               
                            
                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $des => $description )
                                      
                                         {{  $des +1  }}. {{ $description->description }} <br> <br>
                                           
                                       @endforeach
                                   
                                    @else
                                       @foreach ( $invoice->invoice_data as $description )
                                      
                                         {{ $description->description }}
                                           
                                       @endforeach
                                   
                                    @endif
                                </td>

                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $guad => $guards )
                                      
                                          {{  $guad +1  }}.  {{ $guards->counts_as_guards ? $guards->quantity : '— (deduction)' }} <br> <br>
                                           
                                       @endforeach
                                   
                                    </ol>
                                    @else
                                       @foreach ( $invoice->invoice_data as $guards )
                                      
                                          {{ $guards->counts_as_guards ? $guards->quantity : '— (deduction)' }} 
                                           
                                       @endforeach
                                   
                                    </ol>
                                    @endif


                                </td>

                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $rat => $rate )
                                      
                                         {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                           
                                       @endforeach
                                   
                                    @else
                                       @foreach ( $invoice->invoice_data as $rate )
                                      
                                          GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                           
                                       @endforeach
                                   
                                    @endif

                                </td>

                                <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                @if ($invoice->status == 'completed')
                                <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                @elseif($invoice->status == 'uncompleted')
                                <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                @else
                                <td> GH&#x20B5; 0.00 </td>
                                @endif

                                @if ($invoice->status == 'unpaid')
                                <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                @else
                                <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                @endif


                                @if($invoice->status == 'completed')
                                <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                @else
                                <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                @endif
                                <td>
                                    @foreach ( $invoice->receipt as $adva => $advance )
                                      
                                            {{ $advance?->advance_payment }}  <br> <br>
                                           
                                       @endforeach

                                </td>
                                <td>
                                    <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                        <i class="icon-base bx bx-bullseye text-primary me-2"></i>
                                    </a>
                                </td>

                                <td>
                                    @foreach ( $invoice->receipt as $dedate => $ddate )
                                      
                                        {{ $ddate->receipt_month?->format('F l d, Y') }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $deda => $dama )
                                      
                                        {{ $dama->dAmount }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $dedesc => $dedescript )
                                      
                                        {{ $dedescript->description }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $other => $otherpay )
                                      
                                        {{ $otherpay->other_payment_amnt }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $wht => $whtamnt )
                                      
                                        {{ $whtamnt->wht_amount }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $vat => $vat7 )
                                      
                                        {{ $vat7->vat7_value }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                    @foreach ( $invoice->receipt as $cheq => $chequ )
                                      
                                        {{ $chequ->cheque_bank }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                      @foreach ( $invoice->receipt as $cheqre => $chequref )
                                      
                                        {{ $chequref->cheque_reference }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $cheqama => $cheqamnt )
                                      
                                       GH&#x20B5; {{ number_format($cheqamnt->cheque_amount, 2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $trans => $tansfer )
                                      
                                        {{ $tansfer->transfer_bank }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td> 
                                      @foreach ( $invoice->receipt as $transref => $tansferref )
                                      
                                        {{ $tansferref->transfer_reference }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $transama => $transamnt )
                                      
                                        {{ $transamnt->transfer_amount }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $mo => $momo )
                                      
                                        GH&#x20B5;{{ number_format($momo->momo_amount,2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $ca => $cash )
                                      
                                        GH&#x20B5;{{ number_format($cash->cash_amount,2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                         @foreach ( $invoice->receipt as $tot => $total )
                                      
                                        GH&#x20B5;{{ number_format($total->total,2) }} <br> <br>
                                           
                                       @endforeach  
                                </td>
                        </tr>
                        @endforeach
                        @endif


                        @if(Auth::user()->field?->name == 'Takoradi')
                        @foreach($takoradi as $key => $invoice)
                        <tr>
                             <td>{{$key +1 }}</td>
                                <td> FWSSi{{$invoice->id}} </td>
                                <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                @if ($invoice->client->name === $invoice->client->business_name)
                                <td> {{$invoice->client->business_name}} </td>
                                @else
                                <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                @endif
                                <td> 
                                    @if($invoice->client?->status == 'terminated')
                                    <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                    @else
                                    <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                    @endif
                                </td>
                                <td> {{$invoice->client?->phone_number}} </td>
                                <td> {{$invoice->client?->field?->name}} </td>
                                <td> {{$invoice->user?->name}} </td>
                                <td> {{$invoice->created_at?->format('F l d, Y, H:i A')}} </td>
                                <td> {{$invoice->due_date?->diffForHumans()}} </td>
                               
                               
                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $num => $service )
                                            {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                        @endforeach
                                    @else
                                        @foreach ( $invoice->invoice_data as $service )
                                             {{ $service->service_name }} 
                                        @endforeach
                                    @endif

                                </td>
                               
                            
                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $des => $description )
                                      
                                         {{  $des +1  }}. {{ $description->description }} <br> <br>
                                           
                                       @endforeach
                                   
                                    @else
                                       @foreach ( $invoice->invoice_data as $description )
                                      
                                         {{ $description->description }}
                                           
                                       @endforeach
                                   
                                    @endif
                                </td>

                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $guad => $guards )
                                      
                                          {{  $guad +1  }}.  {{ $guards->counts_as_guards ? $guards->quantity : '— (deduction)' }} <br> <br>
                                           
                                       @endforeach
                                   
                                    </ol>
                                    @else
                                       @foreach ( $invoice->invoice_data as $guards )
                                      
                                          {{ $guards->counts_as_guards ? $guards->quantity : '— (deduction)' }} 
                                           
                                       @endforeach
                                   
                                    </ol>
                                    @endif


                                </td>

                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $rat => $rate )
                                      
                                         {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                           
                                       @endforeach
                                   
                                    @else
                                       @foreach ( $invoice->invoice_data as $rate )
                                      
                                          GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                           
                                       @endforeach
                                   
                                    @endif

                                </td>

                                <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                @if ($invoice->status == 'completed')
                                <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                @elseif($invoice->status == 'uncompleted')
                                <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                @else
                                <td> GH&#x20B5; 0.00 </td>
                                @endif

                                @if ($invoice->status == 'unpaid')
                                <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                @else
                                <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                @endif


                                @if($invoice->status == 'completed')
                                <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                @else
                                <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                @endif
                                <td>
                                    @foreach ( $invoice->receipt as $adva => $advance )
                                      
                                            {{ $advance?->advance_payment }}  <br> <br>
                                           
                                       @endforeach

                                </td>
                                <td>
                                    <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                        <i class="icon-base bx bx-bullseye text-primary me-2"></i>
                                    </a>
                                </td>

                                <td>
                                    @foreach ( $invoice->receipt as $dedate => $ddate )
                                      
                                        {{ $ddate->receipt_month?->format('F l d, Y') }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $deda => $dama )
                                      
                                        {{ $dama->dAmount }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $dedesc => $dedescript )
                                      
                                        {{ $dedescript->description }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $other => $otherpay )
                                      
                                        {{ $otherpay->other_payment_amnt }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $wht => $whtamnt )
                                      
                                        {{ $whtamnt->wht_amount }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $vat => $vat7 )
                                      
                                        {{ $vat7->vat7_value }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                    @foreach ( $invoice->receipt as $cheq => $chequ )
                                      
                                        {{ $chequ->cheque_bank }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                      @foreach ( $invoice->receipt as $cheqre => $chequref )
                                      
                                        {{ $chequref->cheque_reference }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $cheqama => $cheqamnt )
                                      
                                       GH&#x20B5; {{ number_format($cheqamnt->cheque_amount, 2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $trans => $tansfer )
                                      
                                        {{ $tansfer->transfer_bank }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td> 
                                      @foreach ( $invoice->receipt as $transref => $tansferref )
                                      
                                        {{ $tansferref->transfer_reference }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $transama => $transamnt )
                                      
                                        {{ $transamnt->transfer_amount }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $mo => $momo )
                                      
                                        GH&#x20B5;{{ number_format($momo->momo_amount,2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $ca => $cash )
                                      
                                        GH&#x20B5;{{ number_format($cash->cash_amount,2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                         @foreach ( $invoice->receipt as $tot => $total )
                                      
                                        GH&#x20B5;{{ number_format($total->total,2) }} <br> <br>
                                           
                                       @endforeach  
                                </td>
                        </tr>
                        @endforeach
                        @endif


                        @if(Auth::user()->field?->name == 'Koforidua')
                        @foreach($koforidua as $key => $invoice)
                        <tr>
                             <td>{{$key +1 }}</td>
                                <td> FWSSi{{$invoice->id}} </td>
                                <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                @if ($invoice->client->name === $invoice->client->business_name)
                                <td> {{$invoice->client->business_name}} </td>
                                @else
                                <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                @endif
                                <td> 
                                    @if($invoice->client?->status == 'terminated')
                                    <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                    @else
                                    <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                    @endif
                                </td>
                                <td> {{$invoice->client?->phone_number}} </td>
                                <td> {{$invoice->client?->field?->name}} </td>
                                <td> {{$invoice->user?->name}} </td>
                                <td> {{$invoice->created_at?->format('F l d, Y, H:i A')}} </td>
                                <td> {{$invoice->due_date?->diffForHumans()}} </td>
                               
                               
                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $num => $service )
                                            {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                        @endforeach
                                    @else
                                        @foreach ( $invoice->invoice_data as $service )
                                             {{ $service->service_name }} 
                                        @endforeach
                                    @endif

                                </td>
                               
                            
                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $des => $description )
                                      
                                         {{  $des +1  }}. {{ $description->description }} <br> <br>
                                           
                                       @endforeach
                                   
                                    @else
                                       @foreach ( $invoice->invoice_data as $description )
                                      
                                         {{ $description->description }}
                                           
                                       @endforeach
                                   
                                    @endif
                                </td>

                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $guad => $guards )
                                      
                                          {{  $guad +1  }}.  {{ $guards->counts_as_guards ? $guards->quantity : '— (deduction)' }} <br> <br>
                                           
                                       @endforeach
                                   
                                    </ol>
                                    @else
                                       @foreach ( $invoice->invoice_data as $guards )
                                      
                                          {{ $guards->counts_as_guards ? $guards->quantity : '— (deduction)' }} 
                                           
                                       @endforeach
                                   
                                    </ol>
                                    @endif


                                </td>

                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $rat => $rate )
                                      
                                         {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                           
                                       @endforeach
                                   
                                    @else
                                       @foreach ( $invoice->invoice_data as $rate )
                                      
                                          GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                           
                                       @endforeach
                                   
                                    @endif

                                </td>

                                <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                @if ($invoice->status == 'completed')
                                <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                @elseif($invoice->status == 'uncompleted')
                                <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                @else
                                <td> GH&#x20B5; 0.00 </td>
                                @endif

                                @if ($invoice->status == 'unpaid')
                                <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                @else
                                <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                @endif


                                @if($invoice->status == 'completed')
                                <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                @else
                                <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                @endif
                                <td>
                                    @foreach ( $invoice->receipt as $adva => $advance )
                                      
                                            {{ $advance?->advance_payment }}  <br> <br>
                                           
                                       @endforeach

                                </td>
                                <td>
                                    <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                        <i class="icon-base bx bx-bullseye text-primary me-2"></i>
                                    </a>
                                </td>

                                <td>
                                    @foreach ( $invoice->receipt as $dedate => $ddate )
                                      
                                        {{ $ddate->receipt_month?->format('F l d, Y') }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $deda => $dama )
                                      
                                        {{ $dama->dAmount }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $dedesc => $dedescript )
                                      
                                        {{ $dedescript->description }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $other => $otherpay )
                                      
                                        {{ $otherpay->other_payment_amnt }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $wht => $whtamnt )
                                      
                                        {{ $whtamnt->wht_amount }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $vat => $vat7 )
                                      
                                        {{ $vat7->vat7_value }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                    @foreach ( $invoice->receipt as $cheq => $chequ )
                                      
                                        {{ $chequ->cheque_bank }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                      @foreach ( $invoice->receipt as $cheqre => $chequref )
                                      
                                        {{ $chequref->cheque_reference }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $cheqama => $cheqamnt )
                                      
                                       GH&#x20B5; {{ number_format($cheqamnt->cheque_amount, 2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $trans => $tansfer )
                                      
                                        {{ $tansfer->transfer_bank }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td> 
                                      @foreach ( $invoice->receipt as $transref => $tansferref )
                                      
                                        {{ $tansferref->transfer_reference }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $transama => $transamnt )
                                      
                                        {{ $transamnt->transfer_amount }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $mo => $momo )
                                      
                                        GH&#x20B5;{{ number_format($momo->momo_amount,2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $ca => $cash )
                                      
                                        GH&#x20B5;{{ number_format($cash->cash_amount,2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                         @foreach ( $invoice->receipt as $tot => $total )
                                      
                                        GH&#x20B5;{{ number_format($total->total,2) }} <br> <br>
                                           
                                       @endforeach  
                                </td>
                        </tr>
                        @endforeach
                        @endif


                        @if(Auth::user()->field?->name == 'Kumasi')
                        @foreach($kumasi as $key => $invoice)
                        <tr>
                             <td>{{$key +1 }}</td>
                                <td> FWSSi{{$invoice->id}} </td>
                                <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                @if ($invoice->client->name === $invoice->client->business_name)
                                <td> {{$invoice->client->business_name}} </td>
                                @else
                                <td> {{$invoice->client->name}} {{$invoice->client->business_name}} </td>
                                @endif
                                <td> 
                                    @if($invoice->client?->status == 'terminated')
                                    <span class="badge bg-label-danger">{{ $invoice->client?->status }}</span>
                                    @else
                                    <span class="badge bg-label-success">{{ $invoice->client?->status }}</span>
                                    @endif
                                </td>
                                <td> {{$invoice->client?->phone_number}} </td>
                                <td> {{$invoice->client?->field?->name}} </td>
                                <td> {{$invoice->user?->name}} </td>
                                <td> {{$invoice->created_at?->format('F l d, Y, H:i A')}} </td>
                                <td> {{$invoice->due_date?->diffForHumans()}} </td>
                               
                               
                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                        @foreach ( $invoice->invoice_data as $num => $service )
                                            {{  $num +1  }}.  {{ $service->service_name }} <br> <br>
                                        @endforeach
                                    @else
                                        @foreach ( $invoice->invoice_data as $service )
                                             {{ $service->service_name }} 
                                        @endforeach
                                    @endif

                                </td>
                               
                            
                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $des => $description )
                                      
                                         {{  $des +1  }}. {{ $description->description }} <br> <br>
                                           
                                       @endforeach
                                   
                                    @else
                                       @foreach ( $invoice->invoice_data as $description )
                                      
                                         {{ $description->description }}
                                           
                                       @endforeach
                                   
                                    @endif
                                </td>

                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $guad => $guards )
                                      
                                          {{  $guad +1  }}.  {{ $guards->counts_as_guards ? $guards->quantity : '— (deduction)' }} <br> <br>
                                           
                                       @endforeach
                                   
                                    </ol>
                                    @else
                                       @foreach ( $invoice->invoice_data as $guards )
                                      
                                          {{ $guards->counts_as_guards ? $guards->quantity : '— (deduction)' }} 
                                           
                                       @endforeach
                                   
                                    </ol>
                                    @endif


                                </td>

                                <td>
                                    @if (count($invoice->invoice_data) > 1)
                                       @foreach ( $invoice->invoice_data as $rat => $rate )
                                      
                                         {{  $rat +1  }}. GH&#x20B5; {{number_format($rate->unit_price, 2) }} <br> <br>
                                           
                                       @endforeach
                                   
                                    @else
                                       @foreach ( $invoice->invoice_data as $rate )
                                      
                                          GH&#x20B5; {{number_format($rate->unit_price, 2) }}
                                           
                                       @endforeach
                                   
                                    @endif

                                </td>

                                <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                @if ($invoice->status == 'completed')
                                <td> GH&#x20B5;{{ number_format($invoice->total, 2)}} </td>
                                @elseif($invoice->status == 'uncompleted')
                                <td> GH&#x20B5;{{ number_format($invoice->total - $invoice->balance, 2)}} </td>
                                @else
                                <td> GH&#x20B5; 0.00 </td>
                                @endif

                                @if ($invoice->status == 'unpaid')
                                <td> GH&#x20B5; {{number_format($invoice?->total,2)}} </td> 
                                @else
                                <td> GH&#x20B5; {{number_format($invoice?->balance,2)}} </td> 
                                @endif


                                @if($invoice->status == 'completed')
                                <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                @else
                                <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                @endif
                                <td>
                                    @foreach ( $invoice->receipt as $adva => $advance )
                                      
                                            {{ $advance?->advance_payment }}  <br> <br>
                                           
                                       @endforeach

                                </td>
                                <td>
                                    <a href="{{url('invoice', $invoice->id)}}" class="btn btn-danger">
                                        <i class="icon-base bx bx-bullseye text-primary me-2"></i>
                                    </a>
                                </td>

                                <td>
                                    @foreach ( $invoice->receipt as $dedate => $ddate )
                                      
                                        {{ $ddate->receipt_month?->format('F l d, Y') }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $deda => $dama )
                                      
                                        {{ $dama->dAmount }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $dedesc => $dedescript )
                                      
                                        {{ $dedescript->description }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                        @foreach ( $invoice->receipt as $other => $otherpay )
                                      
                                        {{ $otherpay->other_payment_amnt }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $wht => $whtamnt )
                                      
                                        {{ $whtamnt->wht_amount }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $vat => $vat7 )
                                      
                                        {{ $vat7->vat7_value }} <br> <br>
                                           
                                       @endforeach
                                </td>

                                <td>
                                    @foreach ( $invoice->receipt as $cheq => $chequ )
                                      
                                        {{ $chequ->cheque_bank }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                      @foreach ( $invoice->receipt as $cheqre => $chequref )
                                      
                                        {{ $chequref->cheque_reference }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $cheqama => $cheqamnt )
                                      
                                       GH&#x20B5; {{ number_format($cheqamnt->cheque_amount, 2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $trans => $tansfer )
                                      
                                        {{ $tansfer->transfer_bank }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td> 
                                      @foreach ( $invoice->receipt as $transref => $tansferref )
                                      
                                        {{ $tansferref->transfer_reference }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $transama => $transamnt )
                                      
                                        {{ $transamnt->transfer_amount }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $mo => $momo )
                                      
                                        GH&#x20B5;{{ number_format($momo->momo_amount,2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                        @foreach ( $invoice->receipt as $ca => $cash )
                                      
                                        GH&#x20B5;{{ number_format($cash->cash_amount,2) }} <br> <br>
                                           
                                       @endforeach
                                </td>
                                <td>
                                         @foreach ( $invoice->receipt as $tot => $total )
                                      
                                        GH&#x20B5;{{ number_format($total->total,2) }} <br> <br>
                                           
                                       @endforeach  
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
                        title: 'Invoices',
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
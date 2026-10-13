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
                <h3 class="card-header text-primary"> <i class="icon-base bx bx-receipt text-primary me-2"></i> Pending Receipts </h3>
            </div>
        </div><br>

            <div class="card-header  ml-2  d-none d-lg-block">
                @include('flash-messages')
            </div>

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
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5; {{ number_format( $accra->sum('total') - $accra->sum('dAmount') ,2)  }} </strong> </h4>
                        <small class="fw-medium"> TOTAL RECEIPTS : <strong> {{$accra->count()}} </strong> </small>
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
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5; {{ number_format( $botwe->sum('total') - $botwe->sum('dAmount') ,2)  }}</strong> </h4>
                        <small class="fw-medium"> TOTAL RECEIPTS : <strong> {{$botwe->count()}} </strong> </small>
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
                        <h4 class="card-title mb-3 text-white"> <strong>&#x20B5; {{ number_format( $tema->sum('total') - $tema->sum('dAmount') ,2)  }} </strong> </h4>
                        <small class="fw-medium"> TOTAL RECEIPTS : <strong> {{$tema->count()}} </strong> </small>
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
                        <h4 class="card-title mb-3 text-white"> <strong>&#x20B5; {{ number_format( $shaihills->sum('total') - $shaihills->sum('dAmount') ,2)  }} </strong> </h4>
                        <small class="fw-medium"> TOTAL RECEIPTS : <strong> {{$shaihills->count()}} </strong> </small>
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
                        <h4 class="card-title mb-3 text-white"> <strong> &#x20B5; {{ number_format( $takoradi->sum('total') - $takoradi->sum('dAmount') ,2)  }} </strong></h4>
                        <small class="fw-medium"> TOTAL RECEIPTS : {{$takoradi->count()}} </small>
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
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5; {{ number_format( $koforidua->sum('total') - $koforidua->sum('dAmount') ,2)  }}</strong></h4>
                        <small class="fw-medium"> TOTAL RECEIPTS : <strong> {{$koforidua->count()}} </strong> </small>
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
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;  {{ number_format( $kumasi->sum('total') - $kumasi->sum('dAmount') ,2)  }}</strong></h4>
                        <small class="fw-medium"> TOTAL RECEIPTS : <strong> {{$kumasi->count()}}  </strong> </small>
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
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5; {{ number_format( $receipts->sum('total') - $receipts->sum('dAmount') ,2) }} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{ $receipts->count()}}</strong> </small>
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
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5; {{ number_format( $receipts->sum('total') - $receipts->sum('dAmount') ,2) }} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{ $receipts->count()}}</strong> </small>
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
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5; {{ number_format( $receipts->sum('total') - $receipts->sum('dAmount') ,2) }} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{ $receipts->count()}}</strong> </small>
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
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5; {{ number_format( $receipts->sum('total') - $receipts->sum('dAmount') ,2) }} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{ $receipts->count()}}</strong> </small>
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
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5; {{ number_format( $receipts->sum('total') - $receipts->sum('dAmount') ,2) }} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{ $receipts->count()}}</strong> </small>
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
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5; {{ number_format( $receipts->sum('total') - $receipts->sum('dAmount') ,2) }} </strong> </h4>
                        <small class="fw-medium"> TOTAL PAYMENTS : <strong> {{ $receipts->count()}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif
        <br><br>
        <!-- <div class="row">
            <form action="/receiptPendingSearch" method="GET">
                @csrf
                <div class="col">

                    <label for="" class="form-label"> <strong>   CHOOSE A MONTH TO SEARCH </strong> </label> <br>

                    <div class="form-check form-check-inline">
                        <input type="month" class="form-control" name="month" required/> <br>
                        
                        <button class="btn btn-dark" type="submit" onclick="return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('') }}</button>
                    </div>
                </div>
            </form>
        </div> -->
         <hr> <br>  


        <div class="row">
            <form action="{{url('receiptChannels')}}" method="POST">
                @csrf  
                <input type="hidden" name="action_type" id="receipt_action_type" value="" />
                <div class="card-header pb-4">
                    <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />
                  
                    @if(Auth::user()->hasRole(['Manager']))
                    <div class="form-check form-check-inline">                            
                        <button class="btn btn-dark" type="submit" onclick="document.getElementById('receipt_action_type').value='branch'; return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('Approve') }}</button>
                    </div>    

                    @elseif(Auth::user()->hasRole(['Finance Manager']))
                    <div class="form-check form-check-inline">                            
                        <button class="btn btn-success" type="submit" onclick="document.getElementById('receipt_action_type').value='headOffice'; return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('Approve') }}</button>
                    </div>
                    <div class="form-check form-check-inline">                            
                        <button class="btn btn-danger" type="submit" onclick="document.getElementById('receipt_action_type').value='decline'; return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('Decline') }}</button>
                    </div>
                    @endif

                </div>

                <div class="col">
                    <table id="myTable" class="display">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Action</th>
                                <th>#</th>
                                <th>Status</th>
                                <th>Collection</th>
                                <th>Branch</th>
                                <th>H/O</th>
                                <th>id</th>
                                <th>Receipt Date</th>
                                <th>Invoice No.</th>
                                <th>Inv. Month</th>
                                <th>Client Name</th>
                                <th>Phone No.</th>
                                <th> Field Office </th>
                                <th> Created </th>
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
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($receipts as $key => $receipt)
                            <tr>
                                <td> <input class="checkBoxes form-check-input" type="checkbox" name="receipts[{{$receipt->id}}]" value="{{ $receipt->id }}" /></td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                            <i class="icon-base bx bx-dots-vertical-rounded text-primary"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="{{url('receipt', $receipt->id)}}"><i class="icon-base bx bx-bullseye text-primary me-2"></i> view</a>
                                            @if($receipt->ho_status !== 'approved' || Auth::user()->hasRole(['Finance Manager']) )
                                            <a class="dropdown-item" href="/receipt/{{$receipt->id}}/edit"><i class="icon-base bx bx-edit-alt me-2 text-primary"></i> Edit</a>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td> {{$key + 1}} </td>
                                @if($receipt->status == 'completed')
                                <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                                @else
                                <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                                @endif
                                <td>
                                        @if($receipt->coll_status == 'approved')
                                        {{$receipt->user?->name}}
                                        {{ $receipt->coll_date?->format('l, F j, Y') }}
                                        <span class="badge bg-label-success">{{$receipt->coll_status}}</span>
                                        @else
                                        {{$receipt->user?->name}}
                                        {{ $receipt->coll_date?->format('l, F j, Y') }}
                                        <span class="badge bg-label-danger">{{$receipt->coll_status}}</span>
                                        @endif
                                </td>

                                <td>
                                        @if($receipt->bran_status == 'approved')
                                        {{$receipt->user1?->name}}
                                        {{ $receipt->bran_date?->format('l, F j, Y') }}
                                        <span class="badge bg-label-success">{{$receipt->bran_status}}</span>
                                        @else
                                        {{$receipt->user1?->name}}
                                        {{ $receipt->bran_date?->format('l, F j, Y') }}
                                        <span class="badge bg-label-danger">{{$receipt->bran_status}}</span>
                                        @endif
                                </td>

                                <td>
                                        {{$receipt->user2?->name}}
                                        <span class="badge bg-label-danger">{{$receipt->ho_status}}</span>
                                </td>

                                <td>FWSSR{{$receipt->id}}</td>
                                <td> {{ $receipt->receipt_month?->format('l, F j, Y') }} </td>
                                <td>FWSSi{{$receipt->invoice_id}} </td>
                                <td> {{ $receipt->invoice?->invoice_month?->format('F, Y') }} </td>
                                @if ($receipt->client->name === $receipt->client->business_name)
                                <td> {{$receipt->client->business_name}} </td>
                                @else
                                <td> {{$receipt->client->name}} {{$receipt->client->business_name}} </td>
                                @endif
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




                            </tr>
                            @endforeach

                        </tbody>

                    </table>

                </div>
            </form>
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
                        title:  "Receipts",
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

</x-sales-dashboard>
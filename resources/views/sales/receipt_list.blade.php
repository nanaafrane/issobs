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
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row">
                <div class="col-6">
                    <h3 class="card-header text-primary"> <i class="icon-base bx bx-receipt text-primary me-2"></i> Receipt <i class="icon-base bx bx-right-arrow-alt mx-1 text-muted"></i> List </h3>
                </div>
                <div style="padding-left: 350px;" class="col-6">
                    <a class="btn btn-danger" href="{{url('receipt/create')}}"> <i class="icon-base bx bx-user-plus me-2 text-primary"></i> Create </a>
                    <a class="btn btn-primary ms-2" href="{{ route('receipt.multi.create') }}"> <i class="icon-base bx bx-layer me-1"></i> Multiple invoices </a>
                </div>
            </div>
            <br>

            <div class="card-header  ml-2  d-none d-lg-block">
                @include('flash-messages')
            </div>
        @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))
        <!-- <div class="row">
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
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;  {{ number_format( $accra->sum('total') - $accra->sum('dAmount') ,2)  }} </strong> </h4>
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
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5; {{ number_format( $botwe->sum('total') - $botwe->sum('dAmount') ,2)  }} </strong> </h4>
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
                        <h4 class="card-title mb-3 text-white"> <strong>&#x20B5;  {{ number_format( $tema->sum('total') - $tema->sum('dAmount') ,2)  }}  </strong> </h4>
                        <small class="fw-medium"> TOTAL RECEIPTS : <strong> {{$tema->count()}}  </strong> </small>
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
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5; {{ number_format( $koforidua->sum('total') - $koforidua->sum('dAmount') ,2)  }} </strong></h4>
                        <small class="fw-medium"> TOTAL RECEIPTS : <strong> {{$koforidua->count()}}  </strong> </small>
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
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5; {{ number_format( $kumasi->sum('total') - $kumasi->sum('dAmount') ,2)  }} </strong></h4>
                        <small class="fw-medium"> TOTAL RECEIPTS : <strong> {{$kumasi->count()}} </strong> </small>
                    </div>
                </div>
            </div>

        </div> -->
        @elseif(Auth::user()->field?->name == 'Accra')
        <!-- <div class="row">
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
        </div> -->
        @endif

        @if(Auth::user()->field?->name == 'Botwe')
        <!-- <div class="row">
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
        </div> -->
        @endif

        @if(Auth::user()->field?->name == 'Tema')
        <!-- <div class="row">
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
        </div> -->
        @endif


        @if(Auth::user()->field?->name == 'Takoradi')
        <!-- <div class="row">
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
        </div> -->
        @endif


        @if(Auth::user()->field?->name == 'Koforidua')
        <!-- <div class="row">
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
        </div> -->
        @endif


        @if(Auth::user()->field?->name == 'Kumasi')
        <!-- <div class="row">
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
        </div> -->
        @endif
        <br>


         <hr> <br>  

            <div class="row ">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>RECEIPT</h4>
                        </div>
                        <div class="card-header">
                            <div class="table-responsive text-normal-dark">
                                <!-- <div class="card-body demo-vertical-spacing demo-only-element"> Clients </div> -->
                                <table id="myTable" class="display" data-source="{{ route('receipt.data') }}">
                                    <thead>
                                        <tr>
                                            <th>id</th>
                                            <th>Receipt Date</th>
                                            <th>Invoice No.</th>
                                            <th>Inv. Month</th>
                                            <th>Client Name</th>
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
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection

{{-- =============== resources/views/sales/receipt_list.blade.php =============== --}}
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
 
@include('partials.dt_range')
    @include('partials.dt_compact')
 
<script>
    // 1. Mount BEFORE creating the table: it inserts the bar above the table.
    const range = DtRange.mount('#myTable', {
        label: 'Receipt date',
        type: 'date',
        presets: ['today', 'week', 'month', 'lastmonth', 'year'],
        exportUrl: '{{ route('receipt.export') }}',
    });
 
    // 2. Send the range with every request.
    const table = new DataTable('#myTable', {
        processing: true,
        serverSide: true,
        pageLength: 25,
        searchDelay: 500,   // date filters use DATE_FORMAT() LIKE (no index): debounce typing
        // DtCompact.ajax: sends only searched columns, after ColumnControl adds them (partials/dt_compact).
        ajax: DtCompact.ajax({
            url: $('#myTable').data('source'),
            data: range.append,
        }),
        order: [[8, 'desc']],
        columnControl: [{
            target: 1,
            content: ['search']
        }],
        columns: [
            { data: 'receipt_id' }, { data: 'receipt_month' }, { data: 'invoice_id' },
            { data: 'invoice_month' }, { data: 'client_name' }, { data: 'phone_number' },
            { data: 'field_name' }, { data: 'staff_name' }, { data: 'created_at' },
            { data: 'invoice_total' }, { data: 'total' }, { data: 'cheque_bank' },
            { data: 'cheque_reference' }, { data: 'cheque_amount' }, { data: 'transfer_bank' },
            { data: 'transfer_reference' }, { data: 'transfer_amount' }, { data: 'momo_amount' },
            { data: 'cash_amount' }, { data: 'deductions' }, { data: 'other_payment' },
            { data: 'wht_amount' }, { data: 'vat7_value' }, { data: 'balance' },
            { data: 'advance_payment' }, { data: 'status' },
            { data: 'action', orderable: false, searchable: false }
        ],
        layout: {
            topStart: {
                buttons: [
                    {
                        extend: 'pageLength',
                        text: 'Show',
                        className: 'btn btn-secondary',
                        Options: [10, 25, 50, 100, 250, 500, 1000, 2000],
                    },
                    // The built-in Excel button is intentionally gone: in server-side mode it
                    // only exports the visible page. Use "Export all (filtered)" in the bar above.
                ]
            }
        },
    });
 
    // 3. Wire pickers / presets / export button to the table.
    range.bind(table);
</script>
 
@endsection

</x-sales-dashboard>

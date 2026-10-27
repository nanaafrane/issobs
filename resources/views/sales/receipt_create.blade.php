<x-sales-dashboard>

    @section('side_nav')
        @include('partials.receipt_side_nav')
    @endsection
    
    @section('css')
    <link rel="stylesheet" href="{{asset('vendor/css/datatables.css')}}" />
    <link href="https://cdn.datatables.net/columncontrol/1.1.1/css/columnControl.dataTables.min.css" rel="stylesheet">

    @endsection

    @section('content')
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row">
                <div class="col-6">
                    <h3 class="card-header text-primary"> <i class="icon-base bx bx-receipt text-primary me-2"></i> Receipt <i class="icon-base bx bx-right-arrow-alt mx-1 text-muted"></i> Show Invoices </h3>
                </div>
                <div class="col-6 d-flex justify-content-end align-items-center">
                    <a class="btn btn-primary" href="{{ route('receipt.multi.create') }}"><i class="icon-base bx bx-layer me-1"></i> New receipt (several invoices / advance)</a>
                </div>
            </div>
            <br>

            <div class="card-header  ml-2  d-none d-lg-block">
                @include('flash-messages')
            </div>

            <div class="row ">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>INVOICES</h4>
                        </div>
                        <div class="card-header">
                            <div class="table-responsive text-normal-dark">
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
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        @foreach($invoices as $key => $invoice)
                            
                                            <tr>
                                                <td>{{$key +1 }}</td>
                                                <td>FWSSi {{$invoice->id}} </td>
                                                <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                                <td> {{$invoice->client->name}}</td>
                                                <td> {{$invoice->client->phone_number}} </td>
                                                <td> {{$invoice->client->business_name}} </td>
                                                <td> {{$invoice->client->field->name}} </td>
                                                <td> {{$invoice->user->name}} </td>
                                                <td> {{$invoice->created_at->diffForHumans()}} </td>
                                                <td> {{$invoice->due_date->diffForHumans()}} </td>
                                                @if($invoice->balance > 0)
                                                <td>  {{number_format($invoice->balance, 2) }}</td>
                                                @else
                                                <td>  {{ number_format($invoice->total, 2) }}</td>
                                                @endif
                                                <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                                <td> <a href="{{url('receiptCreate', $invoice->id)}}" class="btn btn-info"> <i class="icon-base bx bx-receipt text-primary me-2"> </i> </a></td>
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
    </div>
    @endsection


    @section('scripts')

    <!-- <script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script> -->
    <script src="{{asset('vendor/js/datatables.js')}}"></script>
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
                        title:  "{{ $invoices[0]->client->field->name . ' Invoices '}}",
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
        const mySelect = document.getElementById('mode');

        mySelect.addEventListener('change', function() {
            // Get the selected value
            const selectedValue = this.value;

            if (selectedValue == 'cheque') {

                $("#chequerow").toggle();
            }
            if (selectedValue == 'momo') {
                $("#momorow").toggle();
            }
            if (selectedValue == 'cash') {
                $("#cashrow").toggle();
            }
        });
    </script>


    @endsection

</x-sales-dashboard>
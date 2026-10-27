<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">

    <link href="https://cdn.datatables.net/columncontrol/1.1.1/css/columnControl.dataTables.min.css" rel="stylesheet">

    @endsection

    @section('side_nav')
       @include('partials.receipt_side_nav')   
    @endsection


    @section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-6 mb-8">
                <h3 class="card-header text-info"> <i class="icon-base bx bx-bxs-user-detail"></i> Client </h3>
            </div>
        </div>

        <div class="row">


            <div class="col-md-4 col-xl-4">
                <div class="card text-bg-dark">
                    <div class="card-body">
                        <h5 class="card-title text-white"> Client Details <span class="badge bg-label-dark"> </span></h5>
                        <hr>
                        <p class="card-text">Client ID # : <strong> {{$client->id}}</strong> </p>
                        <p class="card-text">Client Name : <strong>{{$client->name}}</strong> </p>
                        <p class="card-text">Phone Number : <strong>{{$client->phone_number}}, {{ $client->phone_number1}}</strong> </p>
                        <p class="card-text">Business Name : <strong>{{$client->business_name}}</strong> </p>
                        <p class="card-text">Address : <strong>{{$client->address}}</strong> </p>
                        <p class="card-text">Location : <strong>{{$client->field->name}}</strong> </p>

                    </div>
                </div>
            </div>

            @if($total_wth_amount_received > 0.00)
            <div class="col-md-4 col-xl-4">
                <div class="card text-bg-secondary">
                    <div class="card-body">
                        <h5 class="card-title text-white"> Client With Holdings </h5>
                        <hr>
                        <p class="card-text">Total Amount Received : <strong> GH&#x20B5; {{number_format($total_wth_amount_received, 2) }} </strong> </p>
                        <p class="card-text">Total WTH Amount : <strong> GH&#x20B5; {{number_format($total_wth_amount, 2) }} </strong> </p>
                    </div>
                </div>
            </div>
            @endif

            <div class="col-md-4 col-xl-4">
                <div class="card text-bg-danger">
                    <div class="card-body">
                        <h5 class="card-title text-white"> Client Account </h5>
                        <hr>
                        <p class="card-text">Total Invoiced : <strong> GH&#x20B5; {{ number_format($account['invoiced'], 2) }} </strong> </p>
                        <p class="card-text">Money Received : <strong> GH&#x20B5; {{ number_format($account['received'], 2) }} </strong> </p>
                        <p class="card-text">WHT / VAT / Deductions : <strong> GH&#x20B5; {{ number_format($account['withheld'], 2) }} </strong> </p>
                        <p class="card-text">Owed on Invoices : <strong> GH&#x20B5; {{ number_format($account['owed_on_invoices'], 2) }} </strong> </p>
                        @if($account['credit'] > 0)
                        <p class="card-text">Unused Credit : <strong> GH&#x20B5; {{ number_format($account['credit'], 2) }} </strong> </p>
                        @endif
                        <h4 class="card-text text-white"> Balance : <strong> GH&#x20B5; {{ \App\Services\Receipts\ClientStatement::money($account['balance']) }} </strong> </h4>
                        @unless($account['check']['ok'])
                        <small class="text-white-50">Statement and invoice balances differ by GH&#x20B5;{{ number_format($account['check']['difference'], 2) }} — old data, see the statement.</small>
                        @endunless
                    </div>
                </div>
            </div>

        </div><br>

        <a href="{{ url('clientStatement', $client->id )}}" class="btn btn-secondary"  type=" button"> Get Statement of Account </a>
        <a href="{{ route('attendance.show', $client->id) }}" class="btn btn-primary ms-2"><i class="bx bx-qr-scan me-1"></i> Attendance, QR poster &amp; guard PINs</a>


        <div class="row mt-12">

            <div class="col-xl-12">
                <!-- <h6 class="text-body-secondary">Filled Tabs</h6> -->
                <div class="nav-align-top nav-tabs-shadow">
                    <ul class="nav nav-tabs nav-fill" role="tablist">
                        <li class="nav-item">
                            <button
                                type="button"
                                class="nav-link active"
                                role="tab"
                                data-bs-toggle="tab"
                                data-bs-target="#navs-justified-home"
                                aria-controls="navs-justified-home"
                                aria-selected="true">
                                <span class="d-none d-sm-inline-flex align-items-center">
                                    <i class="icon-base bx bx-bxs-receipt icon-sm me-1_5"></i>Account
                                    <span class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-danger ms-1_5">{{ count($account['lines']) }}</span>
                                </span>
                                <i class="icon-base bx bx-bxs-receipt icon-sm d-sm-none"></i>
                            </button>
                        </li>

                    </ul>

                    <div class="tab-content">

                        <div class="tab-pane fade show active" id="navs-justified-home" role="tabpanel">
                            <div class="table-responsive text-normal-dark"> 
                                {{-- Account lines from the client ledger (the old transactions table is no longer read here). --}}
                                <table id="myTabletrans" class="display">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th>Details</th>
                                            <th>Debit</th>
                                            <th>Credit</th>
                                            <th>Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($account['lines'] as $line)
                                        <tr>
                                            <td data-order="{{ $line['date']->format('Y-m-d') }}">{{ $line['date']->format('d M Y') }}</td>
                                            <td>{{ ['invoice' => 'Invoice', 'receipt' => 'Payment', 'wht' => 'WHT', 'vat' => 'VAT 7%', 'deduction' => 'Deduction', 'credit_applied' => 'Credit applied', 'reversal' => 'Correction'][$line['kind']] ?? $line['kind'] }}</td>
                                            <td>@if($line['link'])<a href="{{ $line['link'] }}">{{ $line['description'] }}</a>@else{{ $line['description'] }}@endif</td>
                                            <td class="small">{{ $line['details'] }}</td>
                                            <td class="text-end">{{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '' }}</td>
                                            <td class="text-end">{{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '' }}</td>
                                            <td class="text-end">{{ $line['kind'] === 'credit_applied' ? '' : \App\Services\Receipts\ClientStatement::money($line['balance']) }}</td>
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
        new DataTable('#myTabletrans', {
            responsive: true,
            ordering: false,   // keep date order so the running balance reads correctly

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

    <!-- <script>
        new DataTable('#myTableinv', {
            responsive: true,

            layout: {
                topStart: {
                    buttons: ['excelHtml5', 'pdfHtml5']
                }
            }
        });
    </script>


    <script>
        new DataTable('#myTablerec', {
            responsive: true,

            layout: {
                topStart: {
                    buttons: ['excelHtml5', 'pdfHtml5']
                }
            }
        });
    </script> -->


    @endsection



</x-hr-dashboard>
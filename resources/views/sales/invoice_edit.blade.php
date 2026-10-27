<x-sales-dashboard>

    @section('side_nav')
        @include('partials.receipt_side_nav')
    @endsection

    @section('css')
    <style>
        .invoice-form-shell {
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1rem;
            box-shadow: 0 0.35rem 1rem rgba(15, 23, 42, 0.06);
            background: #fff;
            padding: 1.5rem;
        }

        .invoice-form-title {
            font-weight: 700;
            letter-spacing: 0.02em;
            margin-bottom: 1rem;
        }
    </style>
    @endsection

    @section('content')

    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row">
                <div class="col-12">
                    <!-- <div class="card"> -->
                    <h3 class="card-header text-primary"> <i class="icon-base bx bx-receipt text-primary me-2"></i> Invoice <i class="icon-base bx bx-right-arrow-alt mx-1 text-muted"></i> Edit </h3>
                    <!-- <div class="card-body demo-vertical-spacing demo-only-element"> Invoice / Create </div> -->
                    <!-- </div> -->
                </div>
            </div>

            <div class="card-header  ml-2  d-none d-lg-block">
                @include('flash-messages')
            </div> 
            <!-- Invoice 1 - Bootstrap Brain Component -->
            <section class="py-3 py-md-5">
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-9 col-xl-8 col-xxl-7 invoice-form-shell">
                        <div class="row gy-3 mb-3">
                            <div class="col-6">
                                <h2 class="text-uppercase text-endx m-0 text-danger">Invoice</h2>
                            </div>
                            <div class="col-6">
                                <a class="d-block text-end">
                                    <img width="150px" src="{{asset('img/icons/brands/issobs.png')}}" class="img-fluid" alt="BootstrapBrain Logo" width="135" height="44">
                                </a>
                            </div>
                            <div class="col-12" style="margin-top: -70px;">
                                <h4 class="text-danger">From</h4>
                                <address>
                                    <strong>FIRST WATCH SECURITY SERVICE LIMITED.</strong><br>
                                    P.O.BOX AN 18529, GPS : GA-105-4850,<br>
                                    BOUNDARY ROAD, ACCRA NORTH.<br>
                                    TEL : {{$invoice->client->field->number}}, +233(0) 560 027 411.<br>
                                    EMAIL : info@firstwatchsecgh.com. <br>
                                    EMAIL : invoice.firstwatchsecgh@gmail.com.
                                </address>
                            </div>
                        </div>
            <form method="POST" action="/invoice/{{$invoice->id}}">
                {!! \App\Support\SubmissionGuard::field() !!}
                @csrf
                @method('PUT')
                        <div class="row mb-3">
                            <div class="col-12 col-sm-6 col-md-8">
                                <h4 class="text-danger">Bill To</h4>
                                <address>
                                    <!-- <strong>{{$invoice->client->name}}</strong><br> -->

                                    <select name="client_id" class="form-select @error('client_id') is-invalid @enderror" id="client_id" required>
                                        <option selected disabled> Select </option>
                                        @foreach($clients as $client)
                                        <!-- <option value="{{$client->id}}">{{$client->name}} {{$client->business_name}}</option> -->
                                        <option  @if($client->id == $invoice->client?->id) selected @endif  value="{{$client->id}}">{{$client->name}} {{$client->business_name}}</option>

                                        @endforeach
                                    </select>
                                    @error('client_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror

                                </address>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">

                                <h5 style="background: #f00d0dff;" class="row text-white">
                                    <span class="col-12">Inv #: FWSSi{{$invoice->id}}</span>
                                </h5>
                                <address>
                                    <span class="card-header"> Date Of Invoice : </span>
                                    <span class="col-6"> {{$invoice->created_at->format('d/m/Y')}} </span> <br>

                                    <span class="card-header"> Due For Payment : </span>
                                    <span class="col-6">{{$invoice->due_date->format('d/m/Y')}} </span>

                                </address>
                            </div>
                        </div>

                        <hr />

                            <div class="row">
                                <div class="col-6">
                                    <h6 class="card-header">Due Date</h6>
                                    <div class="input-group">
                                        <input value="{{$invoice->due_date}}" name="due_date" type="datetime-local" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-3"></div>
                                <div class="col-3">
                                    <h6 class="card-header"> Invoice Month </h6>
                                    <div class="input-group">
                                        <input name="invoice_month" type="month" value="{{$invoice->invoice_month?->format('Y-m')}}" class="form-control" required>
                                    </div>
                                </div>
                            </div> <br>
                            <hr />
                            <div class="row">
                                <div class="col-md-4 form-check form-switch">
                                    <input name="vat_standard" class="form-check-input" type="checkbox" id="vat_standard" checked>
                                    <label class="form-check-label" for="vat_standard"> VAT 20% STANDARD RATE </label>
                                </div>
                                <div class="col-md-4 form-check form-switch">
                                    <input name="vat_standard_21" class="form-check-input" type="checkbox" id="vat_standard_21" >
                                    <label class="form-check-label" for="vat_standard_21"> VAT 21% STANDARD RATE </label>
                                </div>

                                <div class="col-md-4" style="padding-left: 250px;">
                                    <a id="add" href="javascript:void(0);" class="btn btn-danger text-white" role="button">+</a>
                                </div>
                            </div>
                            <br>
                            <div id="product_form">
                                @foreach($invoice_data as $key => $data)
                                <div class="row invoice-row">
                                    <div class="col-2">
                                        <h5 class="card-header"> Services </h5>
                                        <select name="service[]" class="form-select service" required>
                                            @foreach($services as $service)
                                            <option @if ($data->service_name == $service->name) selected @endif value="{{$service->name}}">{{$service->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-3">
                                        <h5 class="card-header">Description</h5>
                                        <textarea name="description[]" class="form-control description" placeholder="Description" rows="1" required>{{$data->description}}</textarea>
                                    </div>

                                    <div class="col-2">
                                        <h5 class="card-header">Quantity</h5>
                                        <div class="input-group">
                                            <input type="number" name="quantity[]" value="{{$data->quantity}}" class="form-control quantity" step="any" placeholder="Quantity" required>
                                        </div>
                                    </div>

                                    <div class="col-2">
                                        <h5 class="card-header">Unit Price</h5>
                                        <div class="input-group">
                                            <input type="number" name="unit_price[]" value="{{$data->unit_price}}" class="form-control unit_price" step="any" placeholder="GH&#8373;" required>
                                        </div>
                                    </div>

                                    <div class="col-2">
                                        <h5 class="card-header">Amount</h5>
                                        <div class="input-group">
                                            <input type="number" name="amount[]" value="{{$data->amount}}" class="form-control amount" step="any" placeholder="GH&#8373;" readonly>
                                        </div>
                                    </div>

                                    <div class="col-1 d-flex align-items-end">
                                        <a href="javascript:void(0);" class="btn btn-danger btn_remove w-100 text-white" role="button">-</a>
                                    </div>

                                </div>
                                @endforeach

                                <div id="invoice_row_template" class="d-none">
                                    <div class="row invoice-row">
                                        <div class="col-2">
                                            <h5 class="card-header"> Services </h5>
                                            <select name="service[]" class="form-select service" required disabled>
                                                <option selected disabled> Select </option>
                                                @foreach($services as $service)
                                                <option value="{{$service->name}}">{{$service->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-3">
                                            <h5 class="card-header">Description</h5>
                                            <textarea name="description[]" class="form-control description" placeholder="Description" rows="1" required disabled></textarea>
                                        </div>

                                        <div class="col-2">
                                            <h5 class="card-header">Quantity</h5>
                                            <div class="input-group">
                                                <input type="number" name="quantity[]" class="form-control quantity" placeholder="Quantity" step="any" required disabled>
                                            </div>
                                        </div>

                                        <div class="col-2">
                                            <h5 class="card-header">Unit Price</h5>
                                            <div class="input-group">
                                                <input type="number" name="unit_price[]" class="form-control unit_price" placeholder="GH&#8373;" step="any" required disabled>
                                            </div>
                                        </div>

                                        <div class="col-2">
                                            <h5 class="card-header">Amount</h5>
                                            <div class="input-group">
                                                <input type="number" name="amount[]" class="form-control amount" placeholder="GH&#8373;" step="any" readonly disabled>
                                            </div>
                                        </div>

                                        <div class="col-1 d-flex align-items-end">
                                            <a href="javascript:void(0);" class="btn btn-danger btn_remove w-100 text-white" role="button">-</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div style="padding-top: 30px;" class="row">
                                    <div class="col-12">
                                        <button type="submit" id="submit" class="btn btn-danger btn-lg btn-block" data-loading onclick="return confirm('Kindly Confirm?')"> Update </button>
                                    </div>
                                </div>


                        </form>

                    </div>
                </div>
        </div>
        </section>


    </div>
    </div>
        <div class="buy-now">
            <button id="print"
                class="btn btn-danger btn-buy-now"> <i class="icon-base bx bxs-printer"></i> Print Invoice</button>
        </div> 

    @endsection

    @section('scripts')
    <script>
        function updateRowAmount(row) {
            var quantity = parseFloat(row.find('.quantity').val()) || 0;
            var unitPrice = parseFloat(row.find('.unit_price').val()) || 0;
            row.find('.amount').val((quantity * unitPrice).toFixed(2));
        }

        $(document).ready(function() {

            $('#vat_standard_21').change(function() {
                if ($(this).is(':checked')) {
                     $('#vat_standard').not(this).prop('checked', false);
                } else {
                     $('#vat_standard').prop('checked', true);
                }
            });

            $('#add').click(function(event) {
                event.preventDefault();
                var newRow = $('#invoice_row_template .invoice-row').clone();
                newRow.find('input, select, textarea').prop('disabled', false);
                $('#product_form').prepend(newRow);
                toggleRemoveButtons();
            });

            $(document).on('input', '.quantity, .unit_price', function() {
                var row = $(this).closest('.invoice-row');
                updateRowAmount(row);
            });

            $(document).on('click', '.btn_remove', function(event) {
                event.preventDefault();
                $(this).closest('.invoice-row').remove();
                toggleRemoveButtons();
            });

            function toggleRemoveButtons() {
                var rowCount = $('#product_form .invoice-row').length;
                $('#product_form .btn_remove').each(function() {
                    $(this).toggle(rowCount > 1);
                });
            }

            toggleRemoveButtons();
        });
    </script>

    <script>
        $(document).ready(function() {
            $('#print').on('click', function() {
                $.ajax({
                    url: '/printInvoice/{{$invoice->id}}', // The route to your dedicated print view
                    method: 'GET',
                    success: function(response) {
                        var printWindow = window.open('url', '_parent');
                        var originalContents = $('body').html();
                        printWindow.document.write(response);
                        printWindow.print();
                        $('body').html(originalContents);
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching print content:", error);
                    }
                });
            });
        });
    </script>

    @endsection

</x-sales-dashboard>
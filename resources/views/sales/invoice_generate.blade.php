<x-sales-dashboard>

    @section('side_nav')
        @include('partials.receipt_side_nav')
    @endsection

    @section('content')

    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row">
                <div class="col-12">
                    <!-- <div class="card"> -->
                    <h3 class="card-header text-primary"> <i class="icon-base bx bx-receipt text-primary me-2"></i> Invoice <i class="icon-base bx bx-right-arrow-alt mx-1 text-muted"></i> Generate </h3>
                    <!-- <div class="card-body demo-vertical-spacing demo-only-element"> Invoice / Create </div> -->
                    <!-- </div> -->
                </div>
            </div>
            <!-- Invoice 1 - Bootstrap Brain Component -->
            <section class="py-3 py-md-5">
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-9 col-xl-8 col-xxl-7">
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
                                    TEL : {{$client->field->number}}, +233(0) 560 027 411.<br>
                                    EMAIL : info@firstwatchsecgh.com. <br>
                                    EMAIL : invoice.firstwatchsecgh@gmail.com.
                                </address>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12 col-sm-6 col-md-8">
                                <h4 class="text-danger">Bill To</h4>
                                <address>
                                    @if($client->name)
                                    <strong>{{$client->name}}</strong><br>
                                    @endif

                                    @if($client->business_name)
                                    <strong> Business Name : {{$client->business_name}} </strong> <br>
                                    @endif
                                    Location: {{$client->address}},<br>
                                    {{$client->field->name}},<br>
                                    Phone: {{$client->phone_number}}, {{$client->phone_number1}}<br>
                                </address>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <h4 style="background: #f00d0dff;" class="row text-white">
                                    <span class="col-6">Inv #</span>
                                    <span class="col-6 text-sm-end"></span>
                                </h4>
                            </div>
                        </div>

                        <hr />
                        <form method="POST" action="/invoice">
                            @csrf
                            {!! \App\Support\SubmissionGuard::field() !!}
                            <input type="text" name="client_id" value="{{$client->id}}" hidden>
                            <div class="row">
                                <div class="col-6">
                                    <h6 class="card-header">Due For Payment</h6>
                                    <div class="input-group">
                                        <input name="due_date" type="datetime-local" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-3"></div>
                                <div class="col-3">
                                    <h6 class="card-header"> Invoice Month</h6>
                                    <div class="input-group">
                                        <input name="invoice_month" type="month" class="form-control" required>
                                    </div>
                                </div>
                            </div> <br>
                            <hr />
                            <div class="row">
                                <div class="col-md-6 form-check form-switch">
                                    <input name="vat_standard" class="form-check-input" type="checkbox" id="vat_standard" checked>
                                    <label class="form-check-label" for="vat_standard"> VAT STANDARD RATE </label>
                                </div>

                                <div class="col-md-6" style="padding-left: 250px;">
                                    <a id="add" href="javascript:void(0);" class="btn btn-danger text-white" role="button">+</a>
                                </div>
                            </div>
                            <br>


                            <div id="product_form">
                                <div class="row invoice-row">
                                    <div class="col-2">
                                        <h5 class="card-header"> Services </h5>
                                        <select name="service[]" class="form-select @error('service') is-invalid @enderror service" required>
                                            <option selected disabled> Select </option>
                                            @foreach($services as $service)
                                            <option value="{{$service->name}}">{{$service->name}}</option>
                                            @endforeach
                                        </select>
                                        @error('service')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                    <div class="col-3">
                                        <h5 class="card-header">Description</h5>
                                        <textarea name="description[]" class="form-control description" placeholder="Description" rows="1" required></textarea>
                                    </div>

                                    <div class="col-2">
                                        <h5 class="card-header">Quantity</h5>
                                        <div class="input-group">
                                            <input type="number" name="quantity[]" class="form-control quantity" placeholder="Quantity" step="any" required>
                                        </div>
                                    </div>

                                    <div class="col-2">
                                        <h5 class="card-header">Unit Price</h5>
                                        <div class="input-group">
                                            <input type="number" name="unit_price[]" class="form-control unit_price" placeholder="GH&#8373;" step="any" required>
                                        </div>
                                    </div>

                                    <div class="col-2">
                                        <h5 class="card-header">Amount</h5>
                                        <div class="input-group">
                                            <input type="number" name="amount[]" class="form-control amount" placeholder="GH&#8373;" step="any" readonly>
                                        </div>
                                    </div>

                                    <div class="col-1 d-flex align-items-end">
                                        <a href="javascript:void(0);" class="btn btn-danger btn_remove w-100 text-white" style="display:none;" role="button">-</a>
                                    </div>

                                </div>
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
                                    <center>
                                        <button type="submit" id="submit" class="btn btn-danger btn-lg btn-block" data-loading onclick="return confirm('Kindly Confirm?')"> Generate </button>
                                    </center>
                                </div>
                            </div>

                        </form>

                    </div>
                </div>
        </div>
        </section>


    </div>
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

            $(document).on('click', '.btn_remove', function() {
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
    @endsection


</x-sales-dashboard>
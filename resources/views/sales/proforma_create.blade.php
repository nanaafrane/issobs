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
                    <h3 class="card-header text-primary"> <i class="icon-base bx bx-receipt text-primary me-2"></i> Pro Forma <i class="icon-base bx bx-right-arrow-alt mx-1 text-muted"></i> Generate </h3>
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
                    <div class="col-12 col-lg-9 col-xl-8 col-xxl-7">
                        <div class="row gy-3 mb-3">
                            <div class="col-6">
                                <h2 class="text-uppercase text-endx m-0 text-danger">PRO FORMA</h2>
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
                                    P.O.BOX AN 18529,<br>
                                    GPS: GA-105-4850,<br>
                                    BOUNDARY ROAD, ACCRA NORTH.<br>
                                    Tel: +233(0) 501 696 315, +233(0) 560 027 411.<br>
                                    Email: info@firstwatchsecgh.com.
                                </address>
                            </div>
                        </div>
                    <form method="POST" action="/proforma">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-12 col-sm-6 col-md-8">
                                <h4 class="text-danger">Bill To</h4>
                                <address>
                                    <select name="client_id" class="form-select @error('client_id') is-invalid @enderror" id="client_id" required>
                                        <option selected disabled> Select </option>
                                        @foreach($clients as $client)
                                        <option value="{{$client->id}}">{{$client->name}} {{$client->business_name}}</option>
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
                                <h4 style="background: #f00d0dff;" class="row text-white">
                                    <span class="col-6">Inv #</span>
                                    <span class="col-6 text-sm-end"></span>
                                </h4>
                            </div>
                        </div>

                        <hr />
                            <div class="row">

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
                                        <textarea name="description[]" class="form-control description" placeholder="Description" rows="1"></textarea>
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
                                            <textarea name="description[]" class="form-control description" placeholder="Description" rows="1" disabled></textarea>
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
        <div class="buy-now">
                    <button
                        style="margin-bottom: 75px;"
                        type="button"
                        class="btn btn-danger btn-buy-now"
                        data-bs-toggle="modal"
                        data-bs-target="#basicModal">
                        <i class="icon-base bx bx-user-plus me-2 text-primary"> </i>Add ProForma Client
                    </button>
        </div>
        <div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel1">Add ProForma Client</h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <form method="POST" action="/proformaClient">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                <div class="col mb-0">
                                    <label for="name" class="form-label"> {{ __('Full Name') }}</label>
                                    <input
                                        type="text"
                                        name="name"
                                        id="name"
                                        class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name')}}"
                                        placeholder="Full Name"

                                        autocomplete="name"
                                        autofocus>

                                    @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror


                                </div>
                                <div class="col mb-0">
                                    <label for="phone_number" class="form-label"> {{ __('Phone Number 1') }} </label>
                                    <input
                                        type="number"
                                        id="phone_number"
                                        name="phone_number"
                                        class="form-control @error('phone_number') is-invalid @enderror"
                                        value="{{ old('phone_number')}}"
                                        placeholder="Phone Number 1"
                                        autocomplete=" phone_number"
                                        autofocus>

                                    @error('phone_number')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                                <div class="col mb-0">
                                    <label for="phone_number1" class="form-label"> {{ __('Phone Number 2') }} </label>
                                    <input
                                        type="number"
                                        id="phone_number1"
                                        name="phone_number1"
                                        class="form-control @error('phone_number1') is-invalid @enderror"
                                        value="{{ old('phone_number1')}}"
                                        placeholder="Phone Number 2"
                                        autocomplete=" phone_number1"
                                        autofocus>

                                    @error('phone_number1')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row g-6">
                                <div class="col mb-0">
                                    <label for="business_name" class="form-label"> {{ __('Business Name') }} </label>
                                    <input
                                        type="text"
                                        id="business_name"
                                        name="business_name"
                                        class="form-control @error('business_name') is-invalid @enderror"
                                        value="{{ old('business_name')}}"
                                        placeholder="Business Name"

                                        autocomplete="business_name"
                                        autofocus>

                                    @error('business_name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                                <div class="col mb-0">
                                    <label for="address" class="form-label"> {{ __('Address') }} </label>
                                    <input
                                        type="text"
                                        id="address"
                                        name="address"
                                        class="form-control @error('address') is-invalid @enderror"
                                        value="{{ old('address')}}"
                                        placeholder="Address"

                                        autocomplete="address"
                                        autofocus>

                                    @error('address')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror

                                </div>
                            </div>

                            <br>
                            <div class="row g-6">
                                <div class="col mb-0">
                                    <div class="input-group">
                                        <label class="input-group-text" for="inputGroupSelect01">{{ __('Location') }}</label>
                                        <select name="field_id" class="form-select @error('inputGroupSelect01') is-invalid @enderror" id="inputGroupSelect01" value="{{ old('field_id')}}" required>
                                            <option selected>Choose...</option>
                                            @foreach($fields as $field)
                                            <option value="{{$field->id}}">{{$field->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    @error('field_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                                <div class="col mb-0">
                                    <div class="input-group">
                                        <input
                                            type="text"
                                            id="branch"
                                            name="branch"
                                            class="form-control @error('branch') is-invalid @enderror"
                                            value="{{ old('branch')}}"
                                            placeholder="branch"
                                            autocomplete="branch"
                                            autofocus>

                                    </div>

                                    @error('branch')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-info d-grid w-100">{{ __('Add') }}</button>
                        </div>
                    </form>

                </div>
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
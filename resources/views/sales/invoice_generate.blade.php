<x-sales-dashboard>

    @section('side_nav')
    <!-- Menu -->
    <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
        <div class="app-brand demo">
            <a href="#" class="app-brand-link">
                <span class="app-brand-logo demo">
                    <img width="70px" src="{{asset('img/icons/brands/issobs.png')}}" alt="">
                    <!-- Logo -->
                </span>
                <span class="app-brand-text demo menu-text fw-bold ms-2">ISSOBS</span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                <i class="bx bx-chevron-left d-block d-xl-none align-middle"></i>
            </a>
        </div>

        <div class="menu-divider mt-0"></div>

        <div class="menu-inner-shadow"></div>

        <ul class="menu-inner py-1">
            <!-- Dashboards -->
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-home-smile"></i>
                    <div class="text-truncate" data-i18n="Dashboards"><strong>Dashboard</strong></div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{url('home')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="Dashboard">Dashboard</div>
                        </a>
                    </li>
                </ul>
            </li>
            <!-- Apps & Pages -->
            <li class="menu-header small text-uppercase ">
                <span class="menu-header-text text-primary">Transactions</span>
            </li>
            <!-- Pages -->
            <li class="menu-item">
                <a href="{{url('transaction')}}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-transfer-alt bg-primary"></i>
                    <div class="text-truncate" data-i18n="Transaction">Transactions</div>
                </a>
            </li>
            @if(Auth::user()->hasRole('Invoice') || Auth::user()->hasRole('Finance Manager'))

            <li class="menu-item active">
                <a href="{{ url('invoice') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bxs-receipt bg-primary"></i>
                    <div class="text-truncate" data-i18n="Invoices"><strong>Invoices</strong></div>
                </a>
            </li>
            @endif
            <li class="menu-item">
                <a href="{{url('receipt')}}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-money-withdraw bg-primary"></i>
                    <div class="text-truncate" data-i18n="Receipts">Receipts</div>
                </a>
            </li>
            <!-- Components -->
            <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
            <li class="menu-item">
                <a href="{{url('client')}}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bxs-user-detail bg-info"></i>
                    <div class="text-truncate" data-i18n="Clients">Clients</div>
                </a>
            </li>

            @if(Auth::user()->hasRole('Manager') || Auth::user()->hasRole('Officer') || Auth::user()->hasRole('Finance Manager') )

            <li class="menu-header small text-uppercase"> <span class="menu-header-text text-danger">Accounts</span></li>

            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bxs-analyse bg-danger"></i>
                    <div class="text-truncate" data-i18n="Accounts"> Accounts </div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{url('collections')}}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-add-to-queue bg-danger"></i>
                            <div class="text-truncate" data-i18n="ARegister">Collections</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-arrow-from-left bg-danger"></i>
                            <div class="text-truncate" data-i18n="AList">Bank Deposit</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="" class="menu-link">
                            <i class="menu-icon tf-icons bx bxs-bank bg-danger"></i>
                            <div class="text-truncate" data-i18n="AList">Banks</div>
                        </a>
                    </li>
                </ul>
            </li>

            @endif

        </ul>
    </aside>
    @endsection



    @section('content')

    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row">
                <div class="col-12">
                    <!-- <div class="card"> -->
                    <h3 class="card-header text-info"> <i class="icon-base bx bx-bxs-receipt"></i> Invoice <i class="icon-base bx bx-bxs-right-arrow-alt"></i> Generate </h3>
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
                                    P.O.BOX AN 18529,<br>
                                    GPS: GA-105-4850,<br>
                                    BOUNDARY ROAD, ACCRA NORTH.<br>
                                    Tel: +233(0) 501 696 315, +233(0) 560 027 411.<br>
                                    Email: info@firstwatchsecgh.com.
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
                                    Phone: {{$client->phone_number}},<br>
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
                            <input type="text" name="client_id" value="{{$client->id}}" hidden>
                            <div class="row">
                                <div class="col-6">
                                    <h6 class="card-header">Due Date</h6>
                                    <div class="input-group">
                                        <input name="due_date" type="datetime-local" class="form-control" required>
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
                                    <button id="add" type="button" class="btn btn-danger">+</button>
                                </div>
                            </div>
                            <br>


                            <div id="product_form">
                                <div class="row">
                                    <div class="col-2">
                                        <h5 class="card-header" for="service" class="form-label"> Services </h5>
                                        <select name="service[]" class="form-select" id="service" required>
                                            <option selected disabled> Select </option>
                                            @foreach($services as $service)
                                            <option value="{{$service->name}}">{{$service->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-3">
                                        <h5 class="card-header" for="description" class="form-label">Description</h5>
                                        <textarea name="description[]"
                                            id="description"
                                            class="form-control"
                                            placeholder="Description" class="form-control" rows="1" required></textarea>
                                    </div>

                                    <div class="col-2">
                                        <h5 class="card-header">Quantity</h5>
                                        <div class="input-group">
                                            <input
                                                type="number"
                                                name="quantity[]"
                                                id="quantity"
                                                class="form-control"
                                                placeholder="Quantity" required>
                                        </div>
                                    </div>

                                    <div class="col-2">
                                        <h5 class="card-header">Unit Price</h5>
                                        <div class="input-group">
                                            <input
                                                type="number"
                                                name="unit_price[]"
                                                id="unit_price"
                                                oninput="unitPrice()"
                                                class="form-control"
                                                placeholder="GH&#8373;" step="any" required>
                                        </div>
                                    </div>

                                    <div class="col-3">
                                        <h5 class="card-header">Amount</h5>
                                        <div class="input-group">
                                            <input
                                                type="number"
                                                name="amount[]"
                                                id="amount"

                                                class="form-control"
                                                placeholder="GH&#8373;" step="any" required>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div style="padding-top: 30px;" class="row">
                                <div class="col-12">
                                    <center>
                                        <button type="submit" id="submit" class="btn btn-danger btn-lg btn-block" onclick="return confirm('Kindly Confirm?')"> Generate </button>
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
        function unitPrice() {
            let amount;
            let quantity = document.getElementById("quantity").value;

            let unit_price = document.getElementById("unit_price").value;
            // console.log(unit_price);
            amount = quantity * unit_price;

            document.getElementById("amount").value = amount;
            // // console.log(amount);
        }
    </script>

    <script>
        $(document).ready(function() {

            $('#add').click(function(e) {
                e.preventDefault();

                $('#product_form').prepend(`<div class="row">
                                    <div class="col-2">
                                        <h5 class="card-header" for="service" class="form-label"> Services </h5>
                                        <select name="service[]" class="form-select" id="service" required>
                                            <option selected disabled> Select </option>
                                            @foreach($services as $service)
                                            <option value="{{$service->name}}">{{$service->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-3">
                                        <h5 class="card-header" for="description" class="form-label">Description</h5>
                                        <textarea name="description[]"
                                            id="description"
                                            class="form-control"
                                            placeholder="Description" class="form-control" rows="1" required></textarea>
                                    </div>

                                    <div class="col-2">
                                        <h5 class="card-header">Quantity</h5>
                                        <div class="input-group">
                                            <input
                                                type="number"
                                                name="quantity[]"
                                                id="quantity"
                                                class="form-control"
                                                placeholder="Quantity" step="any" required>
                                        </div>
                                    </div>

                                    <div class="col-2">
                                        <h5 class="card-header">Unit Price</h5>
                                        <div class="input-group">
                                            <input
                                                type="number"
                                                name="unit_price[]"
                                                id="unit_price"
                                                oninput="unitPrice()"
                                                class="form-control"
                                                placeholder="GH&#8373;" step="any" required>
                                        </div>
                                    </div>

                                    <div class="col-3">
                                        <h5 class="card-header">Amount</h5>
                                        <div class="input-group">
                                            <input
                                                type="number"
                                                name="amount[]"
                                                id="amount"

                                                class="form-control"
                                                placeholder="GH&#8373;" required>
                                        </div>
                                    </div>
                                </div>
                                `);
            });

        });
    </script>
    @endsection



</x-sales-dashboard>
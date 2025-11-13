<!doctype html>

<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title></title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{asset('img/favicon/favicon.ico')}}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="{{asset('vendor/fonts/iconify-icons.css')}}" />

    <!-- Core CSS -->
    <!-- build:css assets/vendor/css/theme.css  -->

    <link rel="stylesheet" href="{{asset('vendor/css/core.css')}}" />
    <link rel="stylesheet" href="{{asset('css/demo.css')}}" />

    <!-- Vendors CSS -->

    <link rel="stylesheet" href="{{asset('vendor/libs/perfect-scrollbar/perfect-scrollbar.css')}}" />

    <!-- endbuild -->

    <link rel="stylesheet" href="{{asset('vendor/libs/apex-charts/apex-charts.css')}}" />

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="{{asset('vendor/js/helpers.js')}}"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->

    <script src="{{asset('js/config.js')}}"></script>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">

        <div style="background: #fbfbfbff;" class="layout-container">

            <!-- Content wrapper -->
            <div class="content-wrapper">
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <!-- Invoice 1 - Bootstrap Brain Component -->
                        <section class="py-3 py-md-5">
                            <div class="row justify-content-center">
                                <div style="margin-top: -40px;" class="col-12 col-lg-9 col-xl-8 col-xxl-7">
                                    <div class="row gy-3 mb-3">
                                        <div class="col-6">
                                            <h2 class="text-uppercase text-endx m-0 text-danger">Invoice</h2>
                                        </div>
                                        <div class="col-6">
                                            <a class="d-block text-end">
                                                <img width="150px" src="{{asset('img/icons/brands/issobs.png')}}" class="img-fluid" alt="BootstrapBrain Logo" width="135" height="44">
                                            </a>
                                        </div>
                                        <div class="col-12" style="margin-top: -100px;">
                                            <h5 class="text-danger">From</h5>
                                            <address style="font-size: 0.7rem">
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
                                        <div class="col-12 col-sm-6 col-md-6" style="margin-top: -20px;">
                                            <h5 class="text-danger">Bill To</h5>
                                            <address style="margin-top: -10px; font-size: 0.7rem">
                                                <strong>{{$invoice->client->name}}</strong><br>
                                                <strong> Business Name : </strong> <br>
                                                <strong>{{$invoice->client->business_name}}.</strong> <br>
                                                Location: {{$invoice->client->address}},<br>
                                                {{$invoice->client->field->name}},<br>
                                                Phone: {{$invoice->client->phone_number}},{{$invoice->client->phone_number}}<br>
                                            </address>
                                        </div>
                                        <!-- <div class="col-3 col-sm-3 col-md-3"></div> -->
                                        <div class="col-12 col-sm-6 col-md-3">
                                            <h6 style="background: #f00d0dff;" class="row text-white">
                                                <span class="col-12">Inv #: FWSSi{{$invoice->id}}</span>
                                            </h6>
                                            <address style="font-size: 0.7rem">
                                                <span class="card-header"> Issued : </span>
                                                <span class="col-6"> {{$invoice->created_at->format('d/m/Y H:i A')}} </span> <br>

                                                <span class="card-header">Due : </span>
                                                <span class="col-6">{{$invoice->due_date->format('d/m/Y H:i A')}} </span>

                                            </address>
                                        </div>

                                    </div>

                                    <div class="table-responsive text-normal">
                                        <table class="table table-bordered">
                                            <thead class="table-light">
                                                <tr style="font-size: 0.3rem">
                                                    <th>#</th>
                                                    <th>Description</th>
                                                    <th>Qty</th>
                                                    <th>Unit_price</th>
                                                    <th style="white-space: nowrap; overflow: hidden;">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">

                                                @foreach($invoice_data as $index => $data)
                                                <tr style="font-size: 0.6rem">
                                                    <td> <strong>{{$index + 1}}</strong></td>
                                                    <td>
                                                        <h7 class="mb-1">{{$data->service_name}}</h7><br>
                                                        <h7> <strong>{{$data->description}}</strong></h7>
                                                    </td>
                                                    <td><strong> {{$data->quantity}}</strong> </td>
                                                    <td> <strong> GH&#8373; {{number_format($data->unit_price, 2)}} </strong> </td>
                                                    <td style="white-space: nowrap; overflow: hidden;"><strong> GH&#8373; {{number_format($data->amount, 2)}}</strong> </td>
                                                    @endforeach
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-12 col-sm-6 col-md-8">
                                            <address style="font-size: 0.7em" class="mt-5">
                                                <div> <strong> BANK DETAILS </strong> </div><br>
                                                <strong> Bank : ECOBANK</strong><br>
                                                <strong> A/C Name. : FIRST WATCH SECURITY SERVICES LIMITED </strong> <br>
                                                <strong> A/C No. : 1441003309093 </strong> <br>
                                                <strong> Branch : RE INSURANCE-H3 </strong> <br><br>
                                                <!-- <hr class="text-dark"> -->
                                                <div> <strong> MOMO DETAILS </strong> </div><br>
                                                <strong> MoMo No. : 0555062422 </strong> <br>
                                                <strong> MoMo Name : FIRST WATCH SECURITY SERVICES LIMITED </strong> <br>
                                            </address>
                                        </div>
                                        <div class="col-12 col-sm-6 col-md-4 text-end">
                                            <address style="font-size: 0.8rem">
                                                <strong>Sub Amount : GH&#8373; {{number_format($invoice->sub_amount, 2) }}</strong><br> <br>
                                                NHIL (2.5%) : GH&#8373; {{number_format($invoice->nhil, 2) }} <br>
                                                GETFUND (2.5%) : GH&#8373; {{ number_format($invoice->getfund, 2) }} <br>
                                                CHRL (1%) : GH&#8373; {{number_format($invoice->chrl, 2) }} <br> <br>
                                                <strong> SUB TOTAL : GH&#8373; {{ number_format($invoice->sub_total, 2) }} </strong> <br> <br>
                                                VAT (15%) : GH&#8373; {{number_format($invoice->vat_amount, 2) }} <br> <br>
                                                <strong>GRAND TOTAL: GH&#8373; {{number_format($invoice->total, 2) }}</strong> <br>
                                            </address>
                                        </div>
                                    <h7><small> Invoice created by {{$invoice->user->name}} on {{$invoice->updated_at}} </small></h7>

                                    </div>
                                </div>
                        </section>
                    </div>
                </div>

                <div class="content-backdrop fade"></div>

            </div>

            <div class="layout-overlay layout-menu-toggle"></div>
        </div>


        <script src="{{asset('vendor/libs/jquery/jquery.js')}}"></script>

        <script src="{{asset('vendor/libs/popper/popper.js')}}"></script>
        <script src="{{asset('vendor/js/bootstrap.js')}}"></script>

        <script src="{{asset('vendor/libs/perfect-scrollbar/perfect-scrollbar.js')}}"></script>

        <script src="{{asset('vendor/js/menu.js')}}"></script>

        <!-- endbuild -->

        <!-- Vendors JS -->
        <script src="{{asset('vendor/libs/apex-charts/apexcharts.js')}}"></script>

        <!-- Main JS -->

        <script src="{{asset('js/main.js')}}"></script>

        <!-- Page JS -->
        <script src="{{asset('js/dashboards-analytics.js')}}"></script>



</body>

</html>
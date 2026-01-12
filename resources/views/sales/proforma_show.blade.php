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
            @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))

            <li class="menu-item ">
                <a href="{{ url('invoice') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bxs-receipt bg-primary"></i>
                    <div class="text-truncate" data-i18n="Invoices"><strong>Invoices</strong></div>
                </a>
            </li>
            <li class="menu-item active open">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-bxs-receipt bg-primary"></i>
                <div class="text-truncate" data-i18n="Staffs">Pro Forma</div>
                </a>
                <ul class="menu-sub">
                <li class="menu-item ">
                    <a href="{{url('proforma/create')}}" class="menu-link">
                    <div class="text-truncate" data-i18n="SRegister">Generate</div>
                    </a>
                </li>
                <li class="menu-item active">
                    <a href="{{url('proforma')}}" class="menu-link">
                    <div class="text-truncate" data-i18n="SList">List</div>
                    </a>
                </li>
                </ul>
            </li>
            @endif


      @if(Auth::user()->hasRole(['Finance Manager']))
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
      @elseif(Auth::user()->hasRole(['Invoice']))

      <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
        <li class="menu-item ">
            <a class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-bxs-user-detail bg-info"></i>
                <div class="text-truncate" data-i18n="Clients"><strong>Clients</strong></div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item ">
                    <a href="{{url('client/create')}}" class="menu-link">
                        <div class="text-truncate" data-i18n="CRegister">Register</div>
                    </a>
                </li>
                <li class="menu-item ">
                    <a href="{{url('client')}}" class="menu-link">
                        <div class="text-truncate" data-i18n="CList">List</div>
                    </a>
                </li>
            </ul>
        </li>
        <li class="menu-item ">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bxs-user-account"></i>
          <div class="text-truncate" data-i18n="Staffs">Employees</div>
          </a>
          <ul class="menu-sub">
          <li class="menu-item ">
              <a href="{{url('employees/create')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SRegister">Register</div>
              </a>
          </li>
          <li class="menu-item">
              <a href="{{url('employees')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">List</div>
              </a>
          </li>
          </ul>
      </li>
      <li class="menu-item">
          <a href="{{url('departments')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bxs-buildings"></i>
          <div class="text-truncate" data-i18n="depnroles">Department & Roles </div>
          </a>
      </li>
      <li class="menu-item">
          <a href="{{url('field')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-bxs-location-plus"></i>
          <div class="text-truncate" data-i18n="fOffices">Field Offices</div>
          </a>
      </li>
      @endif

      @if(Auth::user()->hasRole(['Manager','Officer','Finance Manager']) )

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
            <a href="{{url('deposit')}}" class="menu-link">
              <i class="menu-icon tf-icons bx bx-arrow-from-left bg-danger"></i>
              <div class="text-truncate" data-i18n="AList">Bank Deposit</div>
            </a>
          </li>
          <li class="menu-item">
            <a href="{{url('banks')}}" class="menu-link">
              <i class="menu-icon tf-icons bx bxs-bank bg-danger"></i>
              <div class="text-truncate" data-i18n="AList">Banks</div>
            </a>
          </li>
        </ul>
      </li>
      <li class="menu-item">
        <a href="{{url('expense')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-bxs-credit-card bg-secondary"></i>
          <div class="text-truncate" data-i18n="Expense"> Expense </div>
        </a>
       </li>
      @endif

      @if(Auth::user()->hasPermission('Accounts') )
      <li class="menu-header small text-uppercase"><span class="menu-header-text">PAYROLL</span></li>
        <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                <div class="text-truncate" data-i18n="Payroll">Payroll</div>
                </a>
                <ul class="menu-sub">
            @if(Auth::user()->hasRole(['Invoice']))
                <li class="menu-item">
                    <a href="{{ url('salaries') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bxs-user-account"></i>
                    <div class="text-truncate" data-i18n="Employees">Add to Salaries</div>
                    </a>
                </li>
                @endif

                <li class="menu-item">
                    <a href="{{ url('salaries/create') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                    <div class="text-truncate" data-i18n="Salaries">Salaries</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="{{ url('salariesTransaction') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-transfer-alt"></i>
                    <div class="text-truncate" data-i18n="Transaction">Transactions</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="{{ url('salariesInvPayroll') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-git-compare"></i>
                    <div class="text-truncate" data-i18n="InvtoPayroll">Invoice to Payroll</div>
                    </a>
                </li>
                </ul>
            </li>
      @endif

        </ul>
    </aside>
            <!-- / Menu -->



            <!-- Layout container -->

            <!-- Content wrapper -->
            <div class="content-wrapper">
                <div id="printContent" class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">

                    <div class="card-header  ml-2  d-none d-lg-block">
                        @include('flash-messages')
                    </div>
                        <!-- proforma 1 - Bootstrap Brain Component -->
                        <section class="py-3 py-md-5">
                            <div class="row justify-content-center">
                                <div style="margin-top: -40px;" class="col-12 col-lg-9 col-xl-8 col-xxl-7">
                                    <div class="row gy-3 mb-3">
                                        <div class="col-6">
                                            <h2 class="text-uppercase text-endx m-0 text-danger">Pro Forma</h2>
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
                                                <strong>{{$proforma->client?->name}}</strong><br>
                                                <strong> Business Name : {{$proforma->client?->business_name}}, </strong> <br>
                                                Location: {{$proforma->client?->address}},<br>
                                                {{$proforma->client->field?->name}},<br>
                                                Phone: {{$proforma->client?->phone_number}}, {{$proforma->client?->phone_number1}}<br>
                                            </address>
                                        </div>
                                        <div class="col-12 col-sm-6 col-md-4">
                                            <h5 style="background: #f00d0dff;" class="row text-white">
                                                <span class="col-12">Inv #: FWSSi{{$proforma->id}}</span>
                                            </h5>
                                            <address>
                                                <span class="card-header"> Issued : </span>
                                                <span class="col-6"> {{$proforma->created_at->format('d/m/Y H:i A')}} </span> <br>

                                                <span class="card-header">Due : </span>
                                                <span class="col-6">{{$proforma->due_date->format('d/m/Y H:i A')}} </span>

                                            </address>
                                        </div>

                                    </div>

                                    <div class="table-responsive text-normal-dark">
                                        <table class="table table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Description</th>
                                                    <th>Quantity</th>
                                                    <th>Unit Price</th>
                                                    <th>Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">

                                                @foreach($proforma_data as $index => $data)
                                                <tr>
                                                    <td> <strong>{{$index + 1}}</strong></td>
                                                    <td>
                                                        <h6 class="mb-1">{{$data->service_name}}</h6>
                                                        <span class="fs-7"> <strong>{{$data->description}}</strong></span>
                                                    </td>
                                                    <td><strong> {{$data->quantity}}</strong> </td>
                                                    <td> <strong> GH&#8373; {{number_format($data->unit_price, 2)}} </strong> </td>
                                                    <td><strong> GH&#8373; {{number_format($data->amount, 2)}}</strong> </td>
                                                    @endforeach
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-12 col-sm-6 col-md-8">
                                            <address class="mt-5">
                                                <span><strong><small> Bank Details</small> </strong> </span><br>
                                                <span><small> <strong> Bank : ECOBANK</strong> </small> </span><br>
                                                <span><small> <strong> A/C Name. : FIRST WATCH SECURITY SERVICES LIMITED </strong> </small> </span><br>
                                                <span> <small> <strong> A/C No. : 1441003309093 </strong> </small> </span><br>
                                                <span> <small> <strong> Branch : RE INSURANCE-H3 </strong> </small> </span><br><br>
                                                <!-- <hr class="text-dark"> -->
                                                <span><strong> <small> MoMo Details </small> </strong> </span><br>
                                                <span> <small> <strong> MoMo No. : 0555062422 </strong> </small> </span><br>
                                                <span> <small> <strong> MoMo Name : FIRST WATCH SECURITY SERVICES LIMITED </strong> </small> </span><br>
                                            </address>
                                        </div>
                                        <div class="col-12 col-sm-6 col-md-4 text-end">
                                            <address>
                                                <h6><strong>Sub Amount : GH&#8373; {{number_format($proforma->sub_amount, 2) }}</strong> </h6>
                                                <h6> NHIL (2.5%) : GH&#8373; {{number_format($proforma->nhil, 2) }}</h6>
                                                <h6>GETFUND (2.5%) : GH&#8373; {{ number_format($proforma->getfund, 2) }}</h6>
                                                <!-- <h6> CHRL (1%) : GH&#8373; {{number_format($proforma->chrl, 2) }}</h6> -->
                                                <!-- <h6><strong> SUB TOTAL : GH&#8373; {{ number_format($proforma->sub_total, 2) }} </strong> </h6> -->
                                                <h6>VAT (15%) : GH&#8373; {{number_format($proforma->vat_amount, 2) }}</h6>
                                                <h6><strong>GRAND TOTAL: GH&#8373; {{number_format($proforma->total, 2) }}</strong></h6>
                                            </address>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row mb-3">
                                        <div class="col-12 float-end">
                                            <address>
                                                <h6><strong>WITHHOLDING TAX 7.5 % : GH&#8373; {{number_format($proforma->wht_amount, 2) }} </strong> </h6>
                                                <h6><strong> AMOUNT AFTER WTH : GH&#8373; {{number_format($proforma->amount_received, 2) }} </strong></h6>
                                                <h6><strong> 7 % VAT AMOUNT : GH&#8373; {{ number_format($proforma->vat7_value, 2) }} </strong></h6>
                                                <h6><strong> AMOUNT AFTER 7 % VAT : GH&#8373; {{number_format($proforma->vat7_amount, 2) }} </strong></h6>
                                                <h6><strong> OTHER DEDUCTIONS : GH&#8373; {{ number_format($proforma->dAmount, 2) }} </strong> </h6>
                                                <h6><strong> PAYMENT STATUS :  {{ $proforma->status }} </strong> </h6>
                                            </address>
                                        </div>
                                    </div>

                                    <h5><small> proforma created by {{$proforma->user->name}} on {{$proforma->updated_at}} </small></h5>
                                </div>
                        </section>
                    </div>
                </div>

                <div class="content-backdrop fade"></div>

                <!-- Content wrapper -->

                <!-- / Layout page -->
            </div>

            <!-- Overlay -->
            <div class="layout-overlay layout-menu-toggle"></div>
        </div>
        <!-- / Layout wrapper -->
        @if($proforma->status != 'completed' && $proforma->status != 'uncompleted')
        <div class="buy-now">
            <a style="margin-bottom: 75px;" href="/proforma/{{$proforma->id}}/edit" class="btn btn-danger btn-buy-now"> <i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>
        </div>
        @endif
        <div class="buy-now">
            <button id="print"
                class="btn btn-danger btn-buy-now"> <i class="icon-base bx bxs-printer"></i> Print proforma</button>
        </div>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script>
            $(document).ready(function() {
                $('#print').on('click', function() {
                    $.ajax({
                        url: '/printProforma/{{$proforma->id}}', // The route to your dedicated print view
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
@php
    $stamp = match ($salary->payment_status) {
        'approved' => ['text' => 'PAID',     'color' => '#198754'],
        'hold'     => ['text' => 'ON HOLD',  'color' => '#ffab00'],
        'rejected' => ['text' => 'REJECTED', 'color' => '#dc3545'],
        default    => ['text' => 'PENDING',  'color' => '#ff3e1d'],
    };
@endphp

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
    <style>
        #printContent { position: relative; }

        .status-stamp {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            z-index: 0;
            pointer-events: none;
            text-align: center;
            color: var(--stamp-color);
            border: 10px double var(--stamp-color);
            border-radius: 18px;
            padding: 10px 60px;
            opacity: 0.18;
            font-weight: 900;
            line-height: 1;
            text-transform: uppercase;
            white-space: nowrap;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .status-stamp .stamp-text {
            display: block;
            font-size: 120px;
            letter-spacing: 12px;
        }

        .status-stamp .stamp-date {
            display: block;
            font-size: 28px;
            letter-spacing: 4px;
            margin-top: 6px;
        }

        /* keep payslip content above the stamp */
        .watermarked { position: relative; z-index: 1; }

        @media print {
            .status-stamp { position: fixed; opacity: 0.15; }
        }
    </style>

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

            <!-- Layout container -->
                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <div id="printContent" class="content-wrapper">
                            <div class="status-stamp" style="--stamp-color: {{ $stamp['color'] }};">
                                <span class="stamp-text">{{ $stamp['text'] }}</span>
                                @if($salary->payment_status === 'approved' && $salary->approval_date)
                                    <span class="stamp-date">{{ $salary->approval_date->format('d M Y') }}</span>
                                @endif
                            </div>
                    <div class="watermarked"> 
                            <div class="container-xxl flex-grow-1 container-p-y">
                                <div class="card-header  ml-2  d-none d-lg-block">
                                    @include('flash-messages')
                                </div>
                                <!-- Invoice 1 - Bootstrap Brain Component -->
                                <section class="py-3 py-md-5">
                                    <div class="row justify-content-center">
                                        <div style="margin-top: -40px;" class="col-12 col-lg-9 col-xl-8 col-xxl-7">
                                            <div class="row gy-3 mb-3">
                                    <div class="col-8">
                                        <h5 class="text-uppercase text-endx m-0 text-danger"><strong>FIRST WATCH SECURITY SERVICE LIMITED.</strong></h5> <br>
                                        <h4><strong>PAYSLIP FOR : {{ strtoupper($salary->salary_month?->format('F, Y')) }}</strong></h4>
                                    </div>
                                                <div class="col-4">
                                                    <a class="d-block text-end">
                                                        <img width="100px" src="{{asset('img/icons/brands/issobs.png')}}" class="img-fluid" alt="BootstrapBrain Logo" width="135" height="44">
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-12 col-sm-6 col-md-6">
                                                    <h5 class="text-danger"> <strong> BASIC INFO </strong>  </h5>
                                                    <address>
                                                        <h7> EMPLOYEE ID : <strong> FWSS {{ $salary->employee_id }} </strong> </h7> <br>
                                                        <h7>   EMPLOYEE NAME :<strong> {{ strtoupper($salary->employee?->name)  }} </strong>  </h7> <br>
                                                        <h7>  WORKER TYPE  :<strong> {{ strtoupper($salary->employee?->worker_type) }} </strong> </h7> <br>
                                                        <h7>  DEPARTMENT  : <strong>{{ strtoupper($salary->employee?->department?->name) }} </strong> </h7>  <br>
                                                        <h7>  ROLE  : <strong> {{ strtoupper($salary->employee?->role?->name) }} </strong> </h7>  
                                                    </address>
                                                </div>
                                                <div class="col-12 col-sm-6 col-md-6">
                                                <h5 class="text-danger text-end"> <strong> PAYMENT INFO </strong>  </h5>
                                                    <address>
                                                        <h7> PAYMENT TYPE : <strong> {{ strtoupper($salary->payment_type ) }} </strong> </h7> <br>
                                                        @if($salary->payment_type == 'Bank')
                                                        <h7>  BANK NAME : <strong> {{ strtoupper($salary->bank?->name ) }} </strong>  </h7> <br>
                                                        <h7> BANK ACCOUNT   : <strong> {{ strtoupper($salary?->account_number ) }} </strong> </h7> <br>
                                                        <h7> BANK BRANCH  : <strong> {{ strtoupper($salary?->branch ) }} </strong> </h7>  <br>
                                                        @endif
                                                        <br>
                                                        @if($salary->employee->tax_button == 'on')
                                                        <h5 > <span class="text-danger">TAX</span>   : <strong> GH&#8373; {{ strtoupper($salary?->tax ) }} </strong> </h5>  
                                                        @endif
                                                    </address>
                                                </div>
                                            </div>
                                            <hr style="height: 5px; background-color : black; margin-top: -16px;"/>
                                            @if($salary->employee->ssnit_button == 'on')
                                            <div class="row mb-3">
                                                <div class="col 8">
                                                    <h5 class="text-danger"> <strong> EARNINGS </strong>  </h5>
                                                    <div class="row">
                                                    <div class="col"> 
                                                    <address>
                                                        <h7> BASIC SALARY </h7> <br>
                                                        <h7 > ALLOWANCE  </h7>  <br>
                                                        <h7 > SSNIT 5% ADD UP </h7>   <br>
                                                        <h7> OVERTIME </h7>   <br>
                                                        <h7> AIRTIME ALLOWANCE </h7> <br>
                                                        <h7> REIMBURSEMENT </h7> <br>
                                                        <h7> T&T ALLOWANCE </h7> <br>
                                                        <h5> <strong>GROSS SALARY </strong>  </h5> 
                                                    </address>
                                                    </div>

                                                    <div class="col text-end">
                                                        <address>
                                                            <h7> <strong>   GH&#8373; {{ number_format($salary->basic_salary, 2)  }} </strong>  </h7> <br> 
                                                            <h7 > <strong>  GH&#8373; {{ number_format($salary->allowances, 2)  }} </strong> </h7>  <br> 
                                                            <h7 > <strong>   {{ $salary->ssnit_tier2_5 }} </strong> </h7>  <br>
                                                            <h7> <strong>    {{ $salary->overtime }} </strong> </h7>   <br>
                                                            <h7> <strong>    {{ $salary->airtime_allowance }} </strong>  </h7> <br>
                                                            <h7>  <strong>   {{ $salary->reimbursements }} </strong>  </h7> <br>
                                                            <h7>  <strong>   {{ $salary->transport_allowance }} </strong>  </h7> <br>
                                                            <h5> <strong>   GH&#8373; {{ number_format($salary->gross_salary, 2) }} </strong>  </h5> 
                                                        </address>
                                                    </div>
                                                    </div>
                                                </div>
                                                <div class="col 4">
                                                <h5 class="text-danger text-end"> <strong> PENSIONS </strong>  </h5>
                                                    <address class="text-end">
                                                        <h7> TIER 1 : <strong> {{ number_format($salary->ssnit_tier1_0_5, 2) }}  </strong>  </h7> <br>
                                                        <h7> TIER 2 : <strong>  {{ number_format($salary->ssnit_tier2_5, 2) }}  </strong> </h7> <br>
                                                        <h5> SUMMARY  : <strong>  GH&#8373; {{ number_format($salary->ssnit_tier1_0_5 + $salary->ssnit_tier2_5, 2) }}  </strong> </h5>  
                                                    </address>
                                                </div>
                                            </div>
                                            @else

                                            <div class="row mb-3">
                                                <div class="col">
                                                    <h5 class="text-danger"> <strong> EARNINGS </strong>  </h5>
                                                    <div class="row">
                                                    <div class="col"> 
                                                    <address>
                                                        <h7> BASIC SALARY   </h7> <br> 
                                                        <h7 > ALLOWANCE </h7>  <br> 
                                                        <h7 > SSNIT 5% ADD UP  </h7>  <br>
                                                        <h7> OVERTIME   <br>
                                                        <h7> AIRTIME ALLOWANCE  <br>
                                                        <h7> REIMBURSEMENT  <br>
                                                        <h7> T&T ALLOWANCE  <br>
                                                        <h5> <strong>GROSS SALARY </strong>  </h5> 
                                                    </address>

                                                    </div>

                                                    <div class="col text-end">
                                                        <address>
                                                            <h7> <strong>   GH&#8373; {{ number_format($salary->basic_salary, 2)  }} </strong>  </h7> <br> 
                                                            <h7 > <strong>  GH&#8373; {{ number_format($salary->allowances, 2)  }} </strong> </h7>  <br> 
                                                            <h7 ><strong>  {{ $salary->ssnit_tier2_5 }} </strong> </h7>  <br>
                                                            <h7> <strong> {{ $salary->overtime }} </strong> </h7>   <br>
                                                            <h7> <strong>  {{ $salary->airtime_allowance }} </strong>  </h7> <br>
                                                            <h7>  <strong>  {{ $salary->reimbursements }} </strong>  </h7> <br>
                                                            <h7>  <strong>  {{ $salary->transport_allowance }} </strong>  </h7> <br>
                                                            <h5> <strong>  GH&#8373; {{ number_format($salary->gross_salary, 2) }} </strong>  </h5> 
                                                        </address>
                                                    </div>


                                                    </div>
                                                </div>
                                                @if($salary->employee->ssnit_button == 'on')
                                                <div class="col">
                                                <h5 class="text-danger text-end"> <strong> PENSIONS </strong>  </h5>
                                                    <address>
                                                        <h7> TIER 1 : <strong> {{ number_format($salary->ssnit_tier1_0_5, 2) }}  </strong>  </h7> <br>
                                                        <h7> TIER 2   : <strong>  {{ number_format($salary->ssnit_tier2_5, 2) }}  </strong> </h7> <br>
                                                        <h5> SUMMARY  : <strong> {{ number_format($salary->ssnit_tier1_0_5 + $salary->ssnit_tier2_5, 2) }}  </strong> </h5>  
                                                    </address>
                                                </div>
                                                @endif
                                            </div>
                                            @endif
        

                                            <hr style="height: 5px; background-color : black; margin-top: -20px;"/>
                                            <div class="row mb-3">
                                                <div class="col-12 col-sm-12 col-md-12">
                                                    <h5 class="text-danger"> <strong> DEDUCTIONS  </strong>  </h5>
                                                <div class="row"> 

                                                <div class="col">
                                                    <address>
                                                        <h7> SSNIT 5% DEDUCTED  </h7> <br> 
                                                        <h7> TAX  </h7> <br> 
                                                        <h7> WELFARE  </h7> <br> 
                                                        <h7 > MAINTENANCE </h7>  <br> 
                                                        <h7 >ABSENT </h7>  <br>
                                                        <h7> AMOUNT DEDUCTED COS OF START DATE </h7>   <br>
                                                        <h7> BOOT   </h7> <br>
                                                        <h7> IOU    </h7> <br>
                                                        <h7> HOSTEL   </h7> <br>
                                                        <h7> INSURANCE  </h7> <br>
                                                        <h7> OTHER   </h7> <br>
                                                        <h7> REPRIMAND  </h7> <br>
                                                        <h7> RAINCOAT </h7> <br>
                                                        <h7> MEAL   </h7> <br>
                                                        <h7> LOAN  </h7> <br>
                                                        <h7> WALK IN  </h7> <br>
                                                        <h7> TOTAL DEDUCTIONS </h7> <br>
                                                        
                                                        <h5><strong> NET SALARY  </strong>  </h5> 
                                                    </address>

                                                </div>

                                                <div class="col text-end">
                                                    <address>
                                                        <h7> <strong>    {{ $salary->ssnit_tier2_5  }} </strong>  </h7> <br> 
                                                        <h7> <strong>    {{ $salary->tax  }} </strong>  </h7> <br> 
                                                        <h7> <strong>    {{ $salary->welfare  }} </strong>  </h7> <br> 
                                                        <h7 > <strong>   {{ $salary->maintenance  }} </strong> </h7>  <br> 
                                                        <h7 > <strong>   {{ $salary->absent }} </strong> </h7>  <br>
                                                        <h7> <strong>  {{ $salary->amnt_ded_cof_start_date }} </strong> </h7>   <br>
                                                        <h7> <strong>   {{ $salary->boot }} </strong>  </h7> <br>
                                                        <h7> <strong>   {{ $salary->iou }} </strong>  </h7> <br>
                                                        <h7>  <strong>   {{ $salary->hostel }} </strong>  </h7> <br>
                                                        <h7> <strong>   {{ $salary->insurance }} </strong>  </h7> <br>
                                                        <h7> <strong>   {{ $salary->other_deductions }} </strong>  </h7> <br>
                                                        <h7> <strong>   {{ $salary->reprimand }} </strong>  </h7> <br>
                                                        <h7> <strong>   {{ $salary->raincoat }} </strong>  </h7> <br>
                                                        <h7> <strong>   {{ $salary->meal }} </strong>  </h7> <br>
                                                        <h7>  <strong>   {{ $salary->loan }} </strong>  </h7> <br>
                                                        <h7>  <strong>   {{ $salary->walkin }} </strong>  </h7> <br>
                                                        <h7> <strong>  GH&#8373; {{ number_format($salary->total_deductions, 2) }} </strong>  </h7> <br>
                                                        
                                                        <h5> <strong>  GH&#8373; {{ number_format($salary->net_salary, 2) }} </strong>  </h5> 
                                                    </address>

                                                </div>
                                                </div>
                                                </div>
                                            </div>

                                            <hr style="height: 5px; background-color : black; margin-top: -20px;"/>
                                            <div class="row mb-3">
                                            <div class="col-12 col-sm-12 col-md-12">
                                                    <h5 class="text-danger"> <strong> EMPLOYER CONTRIBUTION  </strong>  </h5>
                                                <div class="row"> 
                                                <div class="col">
                                                    <address>
                                                        <h5> SOCIAL SECURITY TIER 1  </h5> <br> 
                                                    </address>
                                                </div>

                                                <div class="col text-end">
                                                    <address>
                                                        <h5> <strong>   GH&#8373; {{ number_format($salary->ssnit_tobe_paid13_5, 2)  }} </strong>  </h5> <br> 
                                                    </address>
                                                </div>

                                                </div>
                                                </div>
                                            </div> 

                                            <hr style="height: 5px; background-color : black ; margin-top: -40px;"/>
                                            <div class="row mb-3">
                                                <div class="col-12 col-sm-12 col-md-12">
                                                <div class="row"> 
                                                <div class="col">
                                                    <address>
                                                        <h5> TOTAL  </h5> <br> 
                                                    </address>
                                                </div>

                                                <div class="col text-end">
                                                    <address>
                                                        <h5> <strong>   GH&#8373; {{ number_format($salary->ssnit_tobe_paid13_5, 2)  }} </strong>  </h5> <br> 
                                                    </address>
                                                </div>

                                                </div>
                                                </div>
                                            </div> 


                                        </div>
                                </section>
                            </div>
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
   
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

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
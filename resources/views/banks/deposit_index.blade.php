<x-sales-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">
    @endsection



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
            <li class="menu-item">
                <a href="{{ url('invoice') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bxs-receipt bg-primary"></i>
                    <div class="text-truncate" data-i18n="Invoices">Invoices</div>
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
            <li class="menu-header small text-uppercase text-info"><span class="menu-header-text">Management</span></li>
            <li class="menu-item">
                <a href="{{url('client')}}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bxs-user-detail bg-info"></i>
                    <div class="text-truncate" data-i18n="Clients">Clients</div>
                </a>
            </li>

            @if(Auth::user()->hasRole('Manager') || Auth::user()->hasRole('Officer') || Auth::user()->hasRole('Finance Manager') )

            <li class="menu-header small text-uppercase"> <span class="menu-header-text text-dark">Accounts</span></li>

            <li class="menu-item active open">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bxs-analyse bg-danger"></i>
                    <div class="text-truncate" data-i18n="Accounts"> <strong>Accounts </strong></div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{url('collections')}}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-add-to-queue bg-danger"></i>
                            <div class="text-truncate" data-i18n="ARegister">Collections</div>
                        </a>
                    </li>
                    <li class="menu-item active">
                        <a href="{{url('deposit')}}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-arrow-from-left bg-danger"></i>
                            <div class="text-truncate" data-i18n="AList">Bank Deposit</div>
                        </a>
                    </li>
                    <li class="menu-item ">
                        <a href="{{url('banks')}}" class="menu-link">
                            <i class="menu-icon tf-icons bx bxs-bank bg-danger"></i>
                            <div class="text-truncate" data-i18n="AList">Banks</div>
                        </a>
                    </li>
                </ul>
            </li>

            @endif
        </ul>
    </aside>
    <!-- / Menu -->
    @endsection



    @section('content')

    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header text-dark"> <i class="icon-base bx bx-arrow-from-left"></i> Bank Deposit </h3>
            </div>
        </div><br>

        @if(Auth::user()->hasRole('Invoice') || Auth::user()->hasRole('Finance Manager'))
        <div class="row">
            <div class="col-lg-2">
                <div class="card h-100 bg-dark">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{asset('img/icons/unicons/paypal.png')}}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> ACCRA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>GH&#x20B5;{{number_format(0, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL DEPOSITS : <strong> {{0}}</strong> </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div class="card h-100 bg-dark">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{asset('img/icons/unicons/paypal.png')}}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> BOTWE </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong>GH&#x20B5;{{number_format(0, 2)}}</strong> </h4>
                        <small class="fw-medium"> TOTAL DEPOSITS : <strong> {{0}} </strong> </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2">
                <div class="card h-100 bg-dark">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{asset('img/icons/unicons/paypal.png')}}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> TEMA </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format(0, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL DEPOSITS : <strong> {{0}} </strong> </small>
                    </div>
                </div>
            </div>



            <div class="col-lg-2">
                <div class="card h-100 bg-dark">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{asset('img/icons/unicons/paypal.png')}}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1">TAKORADI</p>
                        <h4 class="card-title mb-3 text-white">GH&#x20B5; {{number_format(0, 2)}}</h4>
                        <small class="fw-medium"> TOTAL DEPOSITS : {{0}} </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div class="card h-100 bg-dark">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{asset('img/icons/unicons/paypal.png')}}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"> <strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3 text-white">GH&#x20B5;{{number_format(0, 2)}}</h4>
                        <small class="fw-medium"> TOTAL DEPOSITS : <strong> {{0}} </strong> </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2">
                <div class="card h-100 bg-dark">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{asset('img/icons/unicons/paypal.png')}}"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3 text-white">GH&#x20B5;{{number_format(0, 2)}}</h4>
                        <small class="fw-medium"> TOTAL DEPOSITS : <strong> {{0}} </strong> </small>
                    </div>
                </div>
            </div>

        </div>
        @elseif(Auth::user()->field?->name == 'Accra')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div class="card h-100 bg-dark">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{asset('img/icons/unicons/paypal.png')}}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> ACCRA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>GH&#x20B5;{{number_format(0, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL DEPOSITS : <strong> {{0}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(Auth::user()->field?->name == 'Botwe')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div class="card h-100 bg-dark">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{asset('img/icons/unicons/paypal.png')}}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> BOTWE </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format(0, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL DEPOSITS : <strong> {{0}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(Auth::user()->field?->name == 'Tema')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div class="card h-100 bg-dark">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{asset('img/icons/unicons/paypal.png')}}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> TEMA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>GH&#x20B5;{{number_format(0, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL DEPOSITS : <strong> {{0}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Takoradi')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div class="card h-100 bg-dark">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{asset('img/icons/unicons/paypal.png')}}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> TAKORADI </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format(0, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL DEPOSITS : <strong> {{0}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Koforidua')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div class="card h-100 bg-dark">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{asset('img/icons/unicons/paypal.png')}}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>GH&#x20B5;{{number_format(0, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL DEPOSITS : <strong> {{0}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif


        @if(Auth::user()->field?->name == 'Kumasi')
        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div class="card h-100 bg-dark">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{asset('img/icons/unicons/paypal.png')}}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>GH&#x20B5;{{number_format(0, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL DEPOSITS : <strong> {{0}}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif
        <br><br>

        <hr><br>
        <div class="card-header  ml-2  d-none d-lg-block">
            @include('flash-messages')
        </div>
        <br>

        <div class="row">
            <form action="/deposit" method="POST">
                @csrf

                <div class="col">
                    <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />

                    <div class="form-check form-check-inline">
                        <select name="collections" class="form-select">
                            <option value="">Deposit All </option>
                        </select>
                    </div>

                    <div class="form-check form-check-inline">
                        <button class="btn btn-dark" type="submit"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('Deposit') }}</button>
                    </div>

                    <table id="myTable1" class="display">
                        <thead>
                            <tr>
                                <th> </th>
                                <th>#</th>
                                <th>Period</th>
                                <th>Cash Amount</th>
                                <th>Momo Amount</th>
                                <th>Cheque Amount </th>
                                <th> Transfer Amount </th>
                                <th> Total Amount </th>
                                <th>Date Created</th>
                                <th>Branch</th>
                                <th>status</th>
                                <th>Bank</th>
                                <!-- <th>Deposit</th> -->
                            </tr>
                        </thead>
                        <tbody>

                            @if(Auth::user()->hasRole('Invoice') || Auth::user()->hasRole('Finance Manager'))
                            @foreach($collections as $collection)
                            <tr>
                                <td> <input class="checkBoxes form-check-input" type="checkbox" name="collections[]" value="{{$collection->id}}" /></td>
                                <td> {{$collection->id}}</td>
                                <td> {{$collection->created_at->diffForHumans()}}</td>
                                <td>GH&#x20B5; {{number_format($collection->cash_amount, 2)}}</td>
                                <td>GH&#x20B5; {{number_format($collection->momo_amount, 2)}}</td>
                                <td>GH&#x20B5; {{number_format($collection->cheque_amount, 2)}}</td>
                                <td>GH&#x20B5; {{number_format($collection->transfer_amount, 2)}}</td>
                                <td>GH&#x20B5; {{number_format($collection->total_amount, 2)}}</td>
                                <td> {{$collection->created_at->format('l F d, Y, H:i A')}}</td>
                                <td> {{$collection->field?->name}} </td>
                                <td><span class="badge bg-label-danger">{{$collection->status}}</span></td>
                                <td>
                                    <select name="bank_id[]" class="form-select @error('bank_id') is-invalid @enderror" id="bank_id" value="{{ old('bank_id')}}" required>
                                        <option selected disabled>Choose...</option>
                                        @foreach($banks as $bank)
                                        <option value="{{$bank->id}}">{{$bank->name}}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <!-- <td><button name="deposit_single" value="Deposit" class="btn btn-dark" type="submit"> <i class="icon-base bx bx-arrow-from-left"> </i></button> </td> -->

                            </tr>
                            @endforeach
                            @elseif(Auth::user()->field?->name == 'Accra')

                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>

                            @endif


                            @if(Auth::user()->field?->name == 'Botwe')

                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>

                            @endif


                            @if(Auth::user()->field?->name == 'Tema')

                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>

                            @endif


                            @if(Auth::user()->field?->name == 'Takoradi')

                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>

                            @endif


                            @if(Auth::user()->field?->name == 'Koforidua')

                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>

                            @endif


                            @if(Auth::user()->field?->name == 'Kumasi')

                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>

                            @endif
                        </tbody>
                    </table>
                </div>
            </form>
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


    <script>
        new DataTable('#myTable', {
            responsive: true,

            layout: {
                topStart: {
                    buttons: ['excelHtml5', 'pdfHtml5']
                }
            }
        });
    </script>

    <script>
        new DataTable('#myTable1', {
            responsive: true
        });
    </script>

    <script>
        $(document).ready(function() {
            $('#options').change(function() {
                $('.checkBoxes').prop('checked', function(i, val) {
                    return !val;
                });
                // if ($(this).is(':checked')) {
                //     $('.checkBoxes').toggle();
                // } else {
                //     $('.checkBoxes').toggle();
                // }
            });
        });
    </script>


    @endsection

</x-sales-dashboard>
<x-sales-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">

    <link href="https://cdn.datatables.net/columncontrol/1.1.1/css/columnControl.dataTables.min.css" rel="stylesheet">
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

            @if(Auth::user()->hasRole(['Invoice','Finance Manager']))
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

            @if(Auth::user()->hasRole(['Manager','Officer', 'Finance Manager']) )

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
                    <li class="menu-item">
                        <a href="{{url('deposit')}}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-arrow-from-left bg-danger"></i>
                            <div class="text-truncate" data-i18n="AList">Bank Deposit</div>
                        </a>
                    </li>
                    <li class="menu-item active">
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
                <h3 class="card-header text-dark"> <i class="icon-base bx bxs-bank"></i> Banks </h3>
            </div>
        </div><br>

        @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))
        <div class="row">
            <div class="col-lg-2">
                <div class="card h-100 bg-dark">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> ACCRA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format(0, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div class="card h-100 bg-dark">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> BOTWE </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format(0, 2)}}</strong> </h4>
                        <small class="fw-medium"> TOTAL </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2">
                <div class="card h-100 bg-dark">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> TEMA </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format(0, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL </small>
                    </div>
                </div>
            </div>



            <div class="col-lg-2">
                <div class="card h-100 bg-dark">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1">TAKORADI</p>
                        <h4 class="card-title mb-3 text-white">&#x20B5; {{number_format(0, 2)}}</h4>
                        <small class="fw-medium"> TOTAL </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div class="card h-100 bg-dark">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"> <strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3 text-white">&#x20B5;{{number_format(0, 2)}}</h4>
                        <small class="fw-medium"> TOTAL </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2">
                <div class="card h-100 bg-dark">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/paypal.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3 text-white">&#x20B5;{{number_format(0, 2)}}</h4>
                        <small class="fw-medium"> TOTAL </small>
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
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> ACCRA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format(0, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL </small>
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
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> BOTWE </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format(0, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL </small>
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
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> TEMA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format(0, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL </small>
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
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> TAKORADI </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format(0, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL </small>
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
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format(0, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL </small>
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
                                    src="img/icons/unicons/paypal.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format(0, 2)}} </strong> </h4>
                        <small class="fw-medium"> TOTAL </small>
                    </div>
                </div>
            </div>
        </div>
        @endif
        <br><br>

        <hr> <br>
        <div class="card-header  ml-2  d-none d-lg-block">
            @include('flash-messages')
        </div>
        <div class="row">
            <div class="col-8">
                <h4 class="card-header text-dark"> Bank Transactions </h4>
            </div>
            <div class="col-4 ">
                <!-- add bank -->
                <div class="float-end">
                    <button
                        type="button"
                        class="btn btn-dark"
                        data-bs-toggle="modal"
                        data-bs-target="#basicModal">
                        <i class="icon-base bx bxs-bank"> </i>Add Bank
                    </button>
                </div>
                <div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel1">Add Bank</h5>
                                <button
                                    type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>

                            <form method="POST" action="/banks">
                                @csrf
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col mb-0">
                                            <label for="name" class="form-label"> {{ __('Name') }}</label>
                                            <input
                                                type="text"
                                                name="name"
                                                id="name"
                                                class="form-control @error('name') is-invalid @enderror"
                                                value="{{ old('name')}}"
                                                placeholder="Bank Name"
                                                required
                                                autocomplete="name"
                                                autofocus>

                                            @error('name')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror


                                        </div>

                                    </div>
                                    <div class="row g-6">

                                        <div class="col mb-0">
                                            <label for="acc_number" class="form-label"> {{ __('Account Number') }} </label>
                                            <input
                                                type="text"
                                                id="acc_number"
                                                name="acc_number"
                                                class="form-control @error('acc_number') is-invalid @enderror"
                                                value="{{ old('acc_number')}}"
                                                placeholder="Account Number"
                                                autocomplete=" acc_number"
                                                required
                                                autofocus>

                                            @error('acc_number')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>

                                    </div>

                                    <div class="row g-6">

                                        <div class="col mb-0">
                                            <label for="branch" class="form-label"> {{ __('Branch') }} </label>
                                            <input
                                                type="text"
                                                id="branch"
                                                name="branch"
                                                class="form-control @error('branch') is-invalid @enderror"
                                                value="{{ old('branch')}}"
                                                placeholder=" Branch"
                                                required
                                                autocomplete="branch"
                                                autofocus>

                                            @error('branch')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>


                                    </div>

                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-dark d-grid w-100">{{ __('Add') }}</button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>

            </div>
        </div>
        <br>

        <div class="row">

            <div class="col-8">
                <table id="myTable" class="display">
                    <thead>
                        <tr>
                            <!-- <th>#</th> -->
                            <th>Bank ID</th>
                            <th>Credit</th>
                            <th>Deposit ID</th>
                            <th>Receipt ID</th>
                            <th>Debit</th>
                            <th>Expense ID</th>
                            <th>Closing Balance</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>

                        @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))
                        @foreach($bankTransactions as $transaction)
                        <tr>

                            <td> {{$transaction->bank->name}} </td>
                            <td> {{$transaction->credit}} </td>
                            <td> {{$transaction->deposit_id}} </td>
                            <td> {{$transaction->receipt_id}} </td>
                            <td> {{$transaction->debit}} </td>
                            <td> {{$transaction->expense_id}} </td>
                            <td> {{$transaction->balance}} </td>
                            <td> {{$transaction->created_at->format('F l d, Y, H:i A')}} </td>
                        </tr>
                        @endforeach
                        @elseif(Auth::user()->field?->name == 'Accra')

                        <tr>
                            <td></td>


                        </tr>

                        @endif


                        @if(Auth::user()->field?->name == 'Botwe')

                        <tr>
                            <td></td>


                        </tr>

                        @endif


                        @if(Auth::user()->field?->name == 'Tema')

                        <tr>
                            <td></td>


                        </tr>

                        @endif


                        @if(Auth::user()->field?->name == 'Takoradi')

                        <tr>
                            <td></td>


                        </tr>

                        @endif


                        @if(Auth::user()->field?->name == 'Koforidua')

                        <tr>
                            <td></td>


                        </tr>

                        @endif


                        @if(Auth::user()->field?->name == 'Kumasi')

                        <tr>

                            <td></td>


                        </tr>

                        @endif
                    </tbody>
                </table>
            </div>

            <div class="col-4">
                <table id="myTable1" class="display">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>

                        @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))
                        @foreach($banks as $bank)
                        <tr>
                            <td>{{$bank->id}}</td>
                            <td>{{$bank->name}} </td>
                            <td>{{$bank->total}} </td>

                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{url('banks', $bank->id)}}"><i class="icon-base bx bxs-bullseye"></i> view</a>

                                        <!-- <a class="dropdown-item" href="banks/{{$bank->id}}/edit"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a> -->

                                        <form action="banks/{{$bank->id}}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button class="dropdown-item" type="submit"><i class="icon-base bx bx-trash me-1"></i>Delete</button>
                                        </form>

                                    </div>
                                </div>
                            </td>

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
        new DataTable('#myTable', {
            responsive: true,

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

    <script>
        new DataTable('#myTable1', {
            responsive: true
        });
    </script>


    @endsection

</x-sales-dashboard>
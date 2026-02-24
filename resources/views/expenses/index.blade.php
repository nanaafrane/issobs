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

            @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))
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
            <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
            <li class="menu-item">
                <a href="{{url('client')}}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bxs-user-detail bg-info"></i>
                    <div class="text-truncate" data-i18n="Clients">Clients</div>
                </a>
            </li>

            @if(Auth::user()->hasRole(['Manager', 'Officer', 'Finance Manager']) )

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

            <li class="menu-item active">
                <a href="{{url('expense')}}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bxs-credit-card bg-secondary"></i>
                    <div class="text-truncate" data-i18n="Expense"> Expense </div>
                </a>
            </li>

            @endif

        </ul>
    </aside>
    <!-- / Menu -->
    @endsection




    @section('content')
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row">
                <div class="col-6">
                    <h3 class="card-header text-secondary"> <i class="icon-base bx bx-bxs-credit-card bg-secondary"></i> Expense <i class="icon-base bx bx-bxs-right-arrow-alt"></i> Claims </h3>
                </div>
                <div style="padding-left: 350px;" class="col-6">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-toggle="modal"
                        data-bs-target="#basicModal">
                        <i class="icon-base bx bx-bxs-credit-card"> </i>Add Expense
                    </button>

                    <div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel1">ADD EXPENSE</h5>
                                    <button
                                        type="button"
                                        class="btn-close"
                                        data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <hr>
                                <form method="POST" action="expense" enctype="multipart/form-data">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="button-wrapper">
                                                <div>Kindly attach copy of Invoice, Max size of 2MB </div> <br>

                                                <label for="image" class="btn btn-dark  me-3 mb-4" tabindex="0">
                                                    <!-- <span class="d-none d-sm-block"> Attach   </span> -->
                                                    <input
                                                        type="file"
                                                        id="image"
                                                        name="image"
                                                        class="account-file-input  @error('image') is-invalid @enderror"
                                                        accept="image/png, image/jpeg" />
                                                    @error('image')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                    @enderror
                                                </label>

                                            </div>
                                        </div>

                                        <div class="row g-6">
                                            <div class="col mb-0">
                                                <label for="description" class="form-label"> {{ __(' Description') }}</label>
                                                <input
                                                    type="text"
                                                    name="description"
                                                    id="description"
                                                    class="form-control @error('description') is-invalid @enderror"
                                                    value="{{ old('description')}}"
                                                    placeholder="Description"

                                                    autocomplete="description"
                                                    autofocus>

                                                @error('description')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                                @enderror
                                            </div>
                                        </div>

                                        <br>
                                        <div class="row g-6">
                                            <div class="col mb-0">
                                                <label for="amount" class="form-label"> {{ __('Amount') }} </label>
                                                <input
                                                    type="number"
                                                    id="amount"
                                                    name="amount"
                                                    class="form-control @error('amount') is-invalid @enderror"
                                                    value="{{ old('amount')}}"
                                                    placeholder="GH&#x20B5;"
                                                    autocomplete=" amount"
                                                    step="any"
                                                    autofocus>

                                                @error('amount')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                                @enderror
                                            </div>

                                        </div>

                                        <div class="row g-6">

                                            <label for="" class="form-label"> {{ __('Assign') }} </label>
                                            <div class="input-group">
                                                <label class="input-group-text" for="user_2">{{ __('Assign To') }}</label>
                                                <select name="user_2" class="form-select @error('user_2') is-invalid @enderror" id="user_2" value="{{ old('user_2')}}" required>
                                                    <option selected disabled>Choose...</option>
                                                    @foreach($assigning_users as $staff)
                                                    <option value="{{$staff->id}}">{{$staff->name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            @error('user_2')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror

                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-secondary d-grid w-100">{{ __('Add') }}</button>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>

                </div>


            </div>
            <br>

            <div class="card-header  ml-2  d-none d-lg-block">
                @include('flash-messages')
            </div>

            <div class="row ">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Expense Claims</h4>
                        </div>
                        <div class="card-header">
                            <div class="table-responsive text-normal-dark">
                                <!-- <div class="card-body demo-vertical-spacing demo-only-element"> Clients </div> -->
                                <table id="myTable" class="display">
                                    <thead>
                                        <tr>
                                            <th> #</th>
                                            <th> Description </th>
                                            <th> Created by</th>
                                            <th> Date </th>
                                            <th> Amount </th>
                                            <th> Branch </th>
                                            <th>Branch Manager </th>
                                            <th>Account Manager</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        @foreach($expenses as $expense)
                                        <tr>
                                            <td> {{$expense->id}} </td>
                                            <td> {{$expense->description}} </td>
                                            <td> {{$expense->user_1}} </td>
                                            <td> {{$expense->created_at}} </td>
                                            <td> {{number_format($expense->amount, 2 )}} </td>
                                            <td> </td>
                                            <td> {{$expense->user_2}} </td>
                                            <td> {{$expense->user_3}} <span class="badge bg-label-success"></span></td>
                                            <td>
                                                <div class="dropdown">
                                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                        <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item" href=""><i class="icon-base bx bxs-bullseye"></i> view</a>
                                                        <a class="dropdown-item" href=""><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>

                                                        <!-- <a class="dropdown-item" href=""><i class="icon-base bx bx-trash me-1"></i> Delete</a> -->
                                                        <form action="" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="dropdown-item" type="submit"><i class="icon-base bx bx-trash me-1"></i>Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </td>
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

    @endsection

</x-sales-dashboard>
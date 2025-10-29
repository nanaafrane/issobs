<x-hr-dashboard>

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
                    <div class="text-truncate" data-i18n="Dashboards">Dashboard</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{url('home')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="Dashboard">Dashboard</div>
                        </a>
                    </li>
                </ul>
            </li>
            <!-- Components -->
            <li class="menu-header small text-uppercase"><span class="menu-header-text">Management</span></li>
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-bxs-group"></i>
                    <div class="text-truncate" data-i18n="Staffs">Staffs</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{url('staffAdd')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="SRegister">Register</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{url('staff')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="SList">List</div>
                        </a>
                    </li>
                </ul>
            </li>
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-bxs-user-detail"></i>
                    <div class="text-truncate" data-i18n="Clients">Clients</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{url('client/create')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="CRegister">Register</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{url('client')}}" class="menu-link">
                            <div class="text-truncate" data-i18n="CList">List</div>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="menu-item active">
                <a href="{{url('field')}}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bxs-location-plus"></i>
                    <div class="text-truncate" data-i18n="Locations">Locations</div>
                </a>
            </li>

            <li class="menu-header small text-uppercase"><span class="menu-header-text">PAYROLL</span></li>
            <li class="menu-item">
                <a href="" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                    <div class="text-truncate" data-i18n="Locations">Payroll</div>
                </a>
            </li>


        </ul>
    </aside>
    <!-- / Menu -->
    @endsection



    @section('content')

    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header text-info"> <i class="icon-base bx bx-bxs-receipt"></i> All Locations </h3>
            </div>
        </div><br>

        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div style="background: #cbfcffff;" class="card">
                    <div class="d-flex align-items-start row">
                        <div class="col-sm-7">
                            <div class="card-body">
                                <h1>{{$LocationsCount}}</h1>
                                <h6 class="card-title  mb-3">TOTAL NUMBER OF LOCATIONS </h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> <br> <br>

        <div class="card-header  ml-2  d-none d-lg-block">
            @include('flash-messages')
        </div>



        <div class="row">
            <div class="col-8">
                <table id="myTable" class="display">
                    <thead>
                        <tr>
                            <th> # ID.</th>
                            <th> Name</th>
                            <th>Created By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach($Locations as $field)
                        <tr>
                            <td> {{$field?->id}} </td>
                            <td> {{$field?->name}}</td>
                            <td> {{$field->user?->name}}</td>

                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{url('field', $field?->id)}}"><i class="icon-base bx bxs-bullseye"></i> view</a>
                                        <a class="dropdown-item" href="field/{{$field?->id}}/edit"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>

                                        <form action="field/{{$field?->id}}" method="POST">
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

            <div class="col-4">
                <br>
                <h3 class="card-header text-info"> <i class="icon-base bx bx-bxs-receipt"></i> Add Location </h3>
                <br>

                <form method="POST" action="field">
                    @csrf
                    <div class="row">
                        <div class="col mb-0">
                            <label for="name" class="form-label"> {{ __('Name') }}</label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name')}}"
                                placeholder="Location Name"
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

                    <br>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-info d-grid w-100">{{ __('Add') }}</button>
                    </div>
                </form>
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


    @endsection

</x-hr-dashboard>
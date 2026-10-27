<x-sales-dashboard>
  
    @section('side_nav')
        @include('partials.receipt_side_nav')
    @endsection
    
    @section('css')
    <link rel="stylesheet" href="{{asset('vendor/css/datatables.css')}}" />
    @endsection


    @section('css')
    <style>
        .invoice-form-shell {
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1rem;
            box-shadow: 0 0.35rem 1rem rgba(15, 23, 42, 0.06);
            background: #fff;
            padding: 1.5rem;
        }

        .invoice-form-title {
            font-weight: 700;
            letter-spacing: 0.02em;
            margin-bottom: 1rem;
        }
    </style>
    @endsection


    @section('content')
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row">
                <div class="col-6">
                    <h3 class="card-header text-primary"> <i class="icon-base bx bx-receipt text-primary me-2"></i> Show <i class="icon-base bx bx-right-arrow-alt mx-1 text-muted"></i> Clients </h3>
                </div>
                <div style="padding-left: 200px;" class="col-6">
                    <!-- add client -->
                    <button
                        type="button"
                        class="btn btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#basicModal">
                        <i class="icon-base bx bx-user-plus me-2 text-primary"> </i>Add Client
                    </button>

                    <div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel1">Add Client</h5>
                                    <button
                                        type="button"
                                        class="btn-close"
                                        data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>

                                <form method="POST" action="/client">
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

                                                            <br>
                    <div class="row g-6">
                        <div class="col mb-0">
                            <label for="rate" class="form-label"> {{ __('Rate') }} </label>
                            <input
                                type="number"
                                id="rate"
                                name="rate"
                                class="form-control @error('rate') is-invalid @enderror"
                                value="{{ old('rate')}}"
                                placeholder="GH&#x20B5;"

                                autocomplete="rate"
                                step="any"
                                autofocus>

                            @error('rate')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col mb-0">
                            <label for="guards" class="form-label"> {{ __('Guards') }} </label>
                            <input
                                type="text"
                                id="guards"
                                name="guards"
                                class="form-control @error('guards') is-invalid @enderror"
                                value="{{ old('guards')}}"
                                placeholder="Number of guards"

                                autocomplete="guards"
                                autofocus>

                            @error('guards')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror

                        </div>
                    </div>

                    <br>
                    <div class="row g-6">
                        <div class="col mb-0">
                            <label for="start_date" class="form-label"> {{ __('Start Date') }} </label>
                            <input
                                type="date"
                                id="start_date"
                                name="start_date"
                                class="form-control @error('start_date') is-invalid @enderror"
                                required
                                autocomplete="start_date"
                                autofocus>

                            @error('start_date')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col mb-0">
                            <label for="scope_of_work" class="form-label"> {{ __('Scope of Work') }} </label>
                           <textarea name="scope_of_work" id="scope_of_work" class="form-control @error('scope_of_work') is-invalid @enderror">{{ old('scope_of_work') }}</textarea>

                            @error('scope_of_work')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror

                        </div>
                    </div>

                    <br>
                    <div class="row g-6">

                        <div class="col mb-0">

                                <input name="state_institution" class="form-check-input" type="checkbox" value="1" id="state_institution" />
                                <label class="form-check-label" for="state_institution"> Tick For State Institution </label>

                            @error('state_institution')
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

                    <!-- add service -->
                    <button
                        type="button"
                        class="btn btn-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#basicModalservice">
                        <i class="icon-base bx bx-bxs-bolt"> </i>Add Service
                    </button>

                    <div class="modal fade" id="basicModalservice" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel1">Add Service</h5>
                                    <button
                                        type="button"
                                        class="btn-close"
                                        data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>

                                <form method="POST" action="/service">
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
                                                    placeholder="Full Name"
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
                                        <div class="form-check mt-3">
                                            <input class="form-check-input" type="checkbox" name="counts_as_guards" id="counts_as_guards" value="1" checked>
                                            <label class="form-check-label" for="counts_as_guards">Quantity counts as guards</label>
                                            <div class="form-text">Untick for deductions such as ABSENT DEDUCTION, so their quantity is not reported as extra guards.</div>
                                        </div>

                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-info d-grid w-100">{{ __('Add') }}</button>
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

                <div class="col-8">
                    <div class="card">
                        <div class="card-header">
                            <h4>Clients</h4>
                            <h6><small>Select a client create to generate invoice</small></h6>
                        </div>
                        <div style="margin-top: -50px;" class="card-header">
                            <div class="table-responsive">
                                <!-- {{session('msg')}} -->
                                <!-- <div class="card-body demo-vertical-spacing demo-only-element"> Clients </div> -->
                                <table id="myTable" class="display">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Number</th>
                                            <th> Business Name </th>
                                            <th>Locations</th>
                                            <th>Field Office</th>
                                            <th>Generate</th>
                                            <th>Branch</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        @foreach ($clients as $client)
                                        <tr>
                                            <td> {{$client->id}} </td>
                                            <td> {{$client->name}}</td>
                                            <td> {{$client->phone_number}}, {{$client->phone_number1}} </td>
                                            <td> {{$client->business_name}} </td>
                                            <td> {{$client->address}} </td>
                                            <td> {{$client->field->name}} </td>
                                            <!-- <td><span class="btn bg-danger text-white btn-block">Unpaid</span></td> -->
                                            <td>
                                                <a href="{{url('generate', $client->id )}}" class="btn btn-info" data-loading>
                                                    <i class="icon-base bx bx-receipt text-primary me-2"></i>
                                                </a>
                                            </td>
                                            <td> {{$client->branch}} </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-4">
                    <div class="card">
                        <div class="card-header">
                            <h4>Services</h4>
                        </div>
                        <div style="margin-top: -50px;" class="card-header">
                            <div class="table-responsive">
                                <table id="myTable1" class="display">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>staff</th>
                                            <th>action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        @foreach ($services as $service)
                                        <tr>
                                            <td> {{$service->name}}</td>
                                            <td> {{$service->user->name}} </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                        <i class="icon-base bx bx-dots-vertical-rounded text-primary"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item" href="/service/{{$service->id}}/edit"><i class="icon-base bx bx-edit-alt me-2 text-primary"></i> Edit</a>
                                                        @if(Auth::user()->hasRole(['Finance Manager']))
                                                        <!-- <a class="dropdown-item" href=""><i class="icon-base bx bx-trash me-2 text-danger"></i> Delete</a> -->
                                                        <form action="/service/{{$service->id}}" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="dropdown-item" type="submit"><i class="icon-base bx bx-trash me-2 text-danger"></i>Delete</button>
                                                        </form>
                                                        @endif
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

    <script src="{{asset('js/ui-modals.js')}}"></script>
    <script src="{{asset('vendor/js/datatables.js')}}"></script>
    <script>
        let table = new DataTable('#myTable');

        let table1 = new DataTable('#myTable1');
    </script>


    @endsection

</x-sales-dashboard>
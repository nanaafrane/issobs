<x-hr-dashboard>
    @section('side_nav')
        @include('partials.receipt_side_nav')
    @endsection

    @section('content')

    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row ">
            <div class="col-12">
                <h3 class="card-header text-info"> <i class="icon-base bx bx-receipt text-primary me-2"></i> Profoma Client <i class="icon-base bx bx-right-arrow-alt mx-1 text-muted"></i> Edit </h3>
            </div>
        </div><br>

        <div class="card-header  ml-2  d-none d-lg-block">
            @include('flash-messages')
        </div>

        <div class="row gy-3 mt-3">

            <div class="col-2"></div>
            <div class="col-8">
                <form method="POST" action="/proformaClient/{{$proformaClient->id}}">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col mb-0">
                            <label for="name" class="form-label"> {{ __('Full Name') }}</label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{$proformaClient->name}}"
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
                                value="{{$proformaClient->phone_number}}"
                                placeholder="Phone Number 1"
                                autocomplete="phone_number"
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
                                class="form-control @error('phone_number') is-invalid @enderror"
                                value="{{$proformaClient->phone_number1}}"
                                placeholder="Phone Number 2"
                                autocomplete="phone_number1"
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
                                value="{{$proformaClient->business_name}}"
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
                                value="{{$proformaClient->address}}"
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
                                    @foreach($fields as $field)
                                    <option @if($field->name == $proformaClient->field->name) selected @endif value="{{$field->id}}">{{$field->name}}</option>
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
                                    value="{{$proformaClient->branch}}"
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
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-info d-grid w-100">{{ __('Update') }}</button>
                    </div>
                </form>

            </div>
            <div class="col-2"></div>

        </div>
        <br><br><br><br><br><br><br><br>
        @endsection



        </x-sales-dashboard>
<x-hr-dashboard>

    @section('side_nav')
       @include('partials.receipt_side_nav')   
    @endsection


    @section('content')

    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row ">
            <div class="col-12">
                <h3 class="card-header text-info"> <i class="icon-base bx bx-bxs-receipt"></i> Client <i class="icon-base bx bx-bxs-right-arrow-alt"></i> Create </h3>
            </div>
        </div><br>

        <div class="card-header  ml-2  d-none d-lg-block">
            @include('flash-messages')
        </div>

        <div class="row gy-3 mt-3">

            <div class="col-2"></div>
            <div class="col-8">
                <form method="POST" action="/client">
                    @csrf
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
                                autocomplete="phone_number1"
                                autofocus>

                            @error('phone_number1')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>
                    <div class="row g-6 mt-1">
                        <div class="col mb-0">
                            <label for="email" class="form-label"> {{ __('Email (for statements)') }} </label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', '') }}" placeholder="accounts@client.com" autocomplete="email">
                            @error('email')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="col mb-0 d-flex align-items-end">
                            <div class="form-check">
                                <input type="hidden" name="auto_apply_credit" value="0">
                                <input class="form-check-input" type="checkbox" name="auto_apply_credit" value="1" id="auto_apply_credit" {{ old('auto_apply_credit', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="auto_apply_credit"> Use unused credit on new invoices automatically </label>
                            </div>
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
                                <label class="input-group-text" for="inputGroupSelect01">{{ __('Field') }}</label>
                                <select name="field_id" class="form-select @error('inputGroupSelect01') is-invalid @enderror" id="inputGroupSelect01" value="{{ old('field_id')}}" required>
                                    <option selected>Choose...</option>
                                    @foreach($fields as $field)
                                    <option value="{{$field->id}}" >{{$field->name}}</option>
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


                    </div>

                    @include('attendance._plan_fields', ['plan' => null, 'editing' => false])


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

                        <div class="col mb-0">
                            <div class="input-group">
                                <label class="input-group-text" for="inputGroupSelect01">{{ __('ASSIGN TO') }}</label>
                                <select name="staff" class="form-select @error('inputGroupSelect01') is-invalid @enderror" id="inputGroupSelect01" value="{{ old('staff')}}" >
                                    <option selected disabled>Choose...</option>
                                            @foreach($assign_staff as $user)
                                                @if($user->field_id == Auth::user()->field_id)
                                                <option value="{{$user->id}}"> {{$user->name}} </option>
                                                @elseif(Auth::user()->hasRole(['Manager']))
                                                <option value="{{$user->id}}"> {{$user->name}} </option>
                                                @endif
                                            @endforeach
                                </select>
                            </div>

                            @error('staff')
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
            <div class="col-2"></div>

        </div>
        <br><br><br><br><br><br><br><br>
        @endsection

        </x-sales-dashboard>
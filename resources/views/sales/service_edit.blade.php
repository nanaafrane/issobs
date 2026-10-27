<x-sales-dashboard>

    @section('side_nav')
        @include('partials.receipt_side_nav')
    @endsection
    
    @section('css')
    <link rel="stylesheet" href="{{asset('vendor/css/datatables.css')}}" />
    @endsection

    @section('content')
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row">
                <div class="col-6">
                    <h3 class="card-header text-primary"> <i class="icon-base bx bx-receipt text-primary me-2"></i> Service <i class="icon-base bx bx-right-arrow-alt mx-1 text-muted"></i> Edit </h3>
                </div>
            </div>
            <br>

            <div class="card-header  ml-2  d-none d-lg-block">
                @include('flash-messages')
            </div>

            <div class="row">
                <div class="col-6">
                    <form method="POST" action="/service/{{$service->id}}">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="row">
                                <div class="col mb-0">
                                    <input type="text" name="user_id" value="{{Auth::user()->id}}" hidden>
                                    <label for="name" class="form-label"> {{ __('Name') }}</label>
                                    <input
                                        type="text"
                                        name="name"
                                        id="name"
                                        class="form-control @error('name') is-invalid @enderror"
                                        placeholder="Full Name"
                                        required
                                        autocomplete="name"
                                        value="{{ $service->name }}"
                                        autofocus>

                                    @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="counts_as_guards" id="counts_as_guards" value="1" {{ $service->counts_as_guards ? 'checked' : '' }}>
                                <label class="form-check-label" for="counts_as_guards">Quantity counts as guards</label>
                                <div class="form-text">Untick for deductions such as ABSENT DEDUCTION. Only affects invoices saved from now on.</div>
                            </div>
                        </div><br>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-info d-grid w-100">{{ __('Update') }}</button>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
    @endsection


    @section('scripts')

    <script src="{{asset('js/ui-modals.js')}}"></script>


    @endsection

</x-sales-dashboard>
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                    @endif

                    {{ __('You are logged in!') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


<!-- <div class="row">

    <div class="col-6 ">

        <button
            type="button"
            class="btn btn-dark"
            data-bs-toggle="modal"
            data-bs-target="#basicModal">
            <i class="icon-base bx bx-arrow-from-left"> </i>Add Bank Deposit
        </button>

        <div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel1">Add Bank Deposit</h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <form method="POST" action="/deposit">
                        @csrf
                        <div class="modal-body">

                            <div class="row">
                                <div class="col mb-0">
                                    <div class="input-group">
                                        <label class="input-group-text" for="bank_id">{{ __('Bank') }}</label>
                                        <select name="bank_id" class="form-select @error('bank_id') is-invalid @enderror" id="bank_id" value="{{ old('bank_id')}}" required>
                                            <option selected disabled>Choose...</option>

                                            <option value=""></option>

                                        </select>
                                    </div>

                                    @error('bank_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>


                            <div class="row">
                                <div class="col mb-0">
                                    <label for="dpst_name" class="form-label"> {{ __('Name of Depositor') }}</label>
                                    <input
                                        type="text"
                                        name="dpst_name"
                                        id="dpst_name"
                                        class="form-control @error('dpst_name') is-invalid @enderror"
                                        value="{{ old('dpst_name')}}"
                                        placeholder="Name of Depositor"
                                        autocomplete="dpst_name"
                                        required
                                        autofocus>

                                    @error('dpst_name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col mb-0">
                                    <label for="reason" class="form-label"> {{ __('Description') }}</label>
                                    <input
                                        type="text"
                                        name="reason"
                                        id="reason"
                                        class="form-control @error('reason') is-invalid @enderror"
                                        value="{{ old('reason')}}"
                                        placeholder="Description"
                                        autocomplete="reason"
                                        autofocus>

                                    @error('reason')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row g-6">
                                <div class="col mb-0">
                                    <label for="cash_amount" class="form-label"> {{ __('Cash Amount') }} </label>
                                    <input
                                        type="number"
                                        id="cash_amount"
                                        name="cash_amount"
                                        class="form-control @error('cash_amount') is-invalid @enderror"
                                        value="{{ old('cash_amount')}}"
                                        placeholder="Cash Amount"
                                        autocomplete=" cash_amount"
                                        step="any"
                                        autofocus>

                                    @error('cash_amount')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row g-6">
                                <div class="col mb-0">
                                    <label for="cheque_amount" class="form-label"> {{ __('Cheque Amount') }} </label>
                                    <input
                                        type="number"
                                        id="cheque_amount"
                                        name="cheque_amount"
                                        class="form-control @error('cheque_amount') is-invalid @enderror"
                                        value="{{ old('cheque_amount')}}"
                                        placeholder="Cheque Amount"
                                        autocomplete=" cheque_amount"
                                        step="any"
                                        autofocus>

                                    @error('cheque_amount')
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
</div> -->
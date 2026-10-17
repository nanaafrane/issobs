<x-sales-dashboard>
    @section('side_nav')
        @include('partials.receipt_side_nav')
    @endsection


    @section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-6">
                <h3 class="card-header text-primary"> <i class="icon-base bx bx-receipt text-primary me-2"></i> Receipt <i class="icon-base bx bx-right-arrow-alt mx-1 text-muted"></i> Edit </h3>
            </div>
        </div>
        <br>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row mb-6 gy-6">
            <div class="col-xl">
                <div class="card">
                    <form method="POST" action="/receipt/{{$receipt->id}}" enctype="multipart/form-data">
                        @csrf
                        {!! \App\Support\SubmissionGuard::field() !!}
                        @method('PUT')
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"> Receipt </h5>
                            <!-- <small class="text-body float-end">Merged input group</small> -->

                            <div class="button-wrapper">
                                <label for="image" class="btn btn-dark  me-3 mb-4" tabindex="0">
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
                                <div>Kindly attach copy of cheque, Max size of 2MB </div>

                            </div>

                        </div>
                        <hr>
                        <div class="card-body">

                            <div class="row">
                                <div class="col-md-6 form-check form-switch">
                                    <input name="wth" class="form-check-input" type="checkbox" @if ($receipt->wht_amount > 0) checked @endif id="wth">
                                    <label class="form-check-label" for="wth"> 7.5 % WITHHOLDING TAX </label>
                                </div>

                                <div id="wht_value" @if ($receipt->wht_amount > 0) style="display: flex;" @else style="display: none;" @endif  class="col-md-6">
                                    <input name="wht_amount" type="number" class="form-control" value="{{$invoice->sub_amount * 0.075}}" step="any">
                                </div>
                            </div>
                            <br>
                            <div class="row">
                                <div class="col-md-6 form-check form-switch">
                                    <input name="vat" class="form-check-input" type="checkbox" @if ($receipt->vat7_value > 0) checked @endif id="vat" >
                                    <label class="form-check-label" for="vat"> 7 % VAT </label>
                                </div>

                                 @if ($invoice->sub_total > 0.00)
                                    <div id="vat7_value" @if ($receipt->vat7_value > 0) style="display: flex;" @else style="display: none;" @endif class="col-md-6">
                                        <input name="vat7_value" type="number" class="form-control" value="{{ $invoice->sub_total * 0.07 }}" step="any">
                                    </div>
                                @else
                                    <div id="vat7_value" @if ($receipt->vat7_value > 0) style="display: flex;" @else style="display: none;" @endif class="col-md-6">
                                        <input name="vat7_value" type="number" class="form-control" value="{{ $invoice->sub_amount * 0.07 }}" step="any">
                                    </div>
                                @endif
                           
                            </div>
                            <br>
                                                     
                            <div class="row" >
                                <div class="col-md-6 form-check form-switch">
                                    <input name="deductions" class="form-check-input" type="checkbox" id="deductions"  @if ($receipt->dAmount > 0 )   checked  @endif>
                                    <label class="form-check-label" for="deductions"> OTHER DEDUCTIONS </label>
                                </div>

                                <div class="col mb-0">
                                        <label for="receipt_month" class="form-label"> {{ __('RECEIPT DATE') }}</label>
                                        <input
                                           name="receipt_month" type="date"
                                           class="form-control @error('receipt_month') is-invalid @enderror"
                                           value="{{ \Carbon\Carbon::parse($receipt->receipt_month)->format('Y-m-d') }}"
                                            required>
                                        @error('receipt_month')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                </div>
                            </div>
                            <br>
                
                            <input type="number" name="invoice_id" value="{{$invoice->id}}" hidden>
                            <input type="number" name="client_id" value="{{$invoice->client_id}}" hidden>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col mb-0">
                                        <label for="from" class="form-label"> {{ __('FROM') }}</label>
                                        <input
                                            type="text"
                                            name="from"
                                            id="from"
                                            class="form-control @error('from') is-invalid @enderror"
                                            value=" {{$receipt->from}} "
                                            placeholder="Full Name"
                                            required
                                            autocomplete="from"
                                            autofocus>

                                        @error('from')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="col mt-6">
                                        <label class="form-label d-block">{{ __('MODE') }} <small class="text-muted">(select one or more)</small></label>
                                        <div id="mode" class="d-flex flex-wrap gap-4 @error('mode') is-invalid @enderror">
                                            @php
                                                $modeInputId = fn ($name) => 'mode_' . str_replace(' ', '_', strtolower($name));
                                                // $receipt->mode is cast to array on the model; old() wins on a
                                                // validation round-trip, otherwise fall back to the saved value(s).
                                                $selectedModes = old('mode', $receipt->mode ?? []);
                                            @endphp
                                            @foreach ($mode as $modes)
                                            <div class="form-check">
                                                <input
                                                    type="checkbox"
                                                    name="mode[]"
                                                    id="{{ $modeInputId($modes->name) }}"
                                                    class="form-check-input"
                                                    value="{{ $modes->name }}"
                                                    {{ in_array($modes->name, $selectedModes) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="{{ $modeInputId($modes->name) }}">{{ $modes->name }}</label>
                                            </div>
                                            @endforeach
                                        </div>
                                        @error('mode')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                        @error('mode.*')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>
                                <br>
                                <div class="row"  id="deduction_field" @if ($receipt->dAmount > 0 ) style="display: flex;" @else style="display: none;" @endif>
                                    <div class="col mb-0">
                                        <label for="dAmount" class="form-label"> {{ __('DEDUCTED AMOUNT') }}</label>
                                        <input
                                            type="number"
                                            name="dAmount"
                                            id="dAmount"
                                            class="form-control @error('dAmount') is-invalid @enderror"
                                            placeholder="Deducted Amount"
                                            value="{{$receipt->dAmount}}"
                                            autocomplete="dAmount"
                                            autofocus
                                            step="any">

                                        @error('dAmount')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="col mb-0">
                                        <label for="description" class="form-label"> {{ __('DESCRIPTION') }}</label>
                                        <input
                                            type="text"
                                            name="description"
                                            value="{{$receipt->description}}"
                                            id="description"
                                            class="form-control @error('description') is-invalid @enderror"
                                            placeholder="Description"
                                            autocomplete="description"
                                            autofocus>

                                        @error('description')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                </div> <br>

                                <!-- show if cheque value is selected -->
                                <div id="chequerow" @if ($receipt->mode == 'cheque') style="display: flex;" @else style="display: none;" @endif   class="row g-6">
                                    <div class="col mb-0">
                                        <label for="cheque_reference" class="form-label"> {{ __('CHEQUE REFERENCE #') }} </label>
                                        <input
                                            type="text"
                                            id="cheque_reference"
                                            name="cheque_reference"
                                            value="{{$receipt->cheque_reference}}"
                                            class="form-control"
                                            placeholder="Cheque Reference"
                                            autocomplete="cheque_reference"
                                            autofocus>
                                    </div>

                                    <div class="col mb-0">
                                        <label for="cheque_amount" class="form-label"> {{ __('CHEQUE AMOUNT') }} </label>
                                        <input
                                            type="number"
                                            id="cheque_amount"
                                            name="cheque_amount"
                                            value="{{$receipt->cheque_amount}}"
                                            class="form-control"
                                            placeholder="GH&#8373;"
                                            autocomplete="cheque_amount"
                                            step="any"
                                            autofocus>
                                    </div>

                                    <div class="col mb-0">
                                        <label for="cheque_bank" class="form-label"> {{ __("DRAWN ON (PAYER'S BANK)") }} </label>
                                        <input
                                            type="text"
                                            id="cheque_bank"
                                            name="cheque_bank"
                                            value="{{$receipt->cheque_bank}}"
                                            class="form-control"
                                            placeholder="Cheque Bank"
                                            autocomplete="cheque_bank"
                                            autofocus>
                                    </div>

                                    <div class="col mb-0">
                                        @include('partials.receipt_bank_select', ['name' => 'cheque_to_bank_id', 'label' => 'PAID INTO (OUR BANK)', 'banks' => $banks, 'selected' => $receipt->cheque_to_bank_id])
                                    </div>

                                </div>
                                <!-- end of cheque field -->
                                <br>

                                <!-- show if Bank Transfer value is selected -->
                                <div id="transferrow" @if ($receipt->mode == 'transfer') style="display: flex;" @else style="display: none;" @endif  class="row g-6">
                                    <div class="col mb-0">
                                        <label for="transfer_reference" class="form-label"> {{ __('TRANSFER REFERENCE #') }} </label>
                                        <input
                                            type="text"
                                            id="transfer_reference"
                                            name="transfer_reference"
                                            value="{{$receipt->transfer_reference}}"
                                            class="form-control"
                                            placeholder="Transfer Reference"
                                            autocomplete="transfer_reference"
                                            autofocus>
                                    </div>

                                    <div class="col mb-0">
                                        <label for="transfer_amount" class="form-label"> {{ __('TRANSFER AMOUNT') }} </label>
                                        <input
                                            type="number"
                                            id="transfer_amount"
                                            name="transfer_amount"
                                            value="{{$receipt->transfer_amount}}"
                                            class="form-control"
                                            placeholder="GH&#8373;"
                                            autocomplete="transfer_amount"
                                            step="any"
                                            autofocus>
                                    </div>

                                    <div class="col mb-0">
                                        <label for="transfer_bank" class="form-label"> {{ __("FROM (PAYER'S BANK)") }} </label>
                                        <input
                                            type="text"
                                            id="transfer_bank"
                                            name="transfer_bank"
                                            value="{{$receipt->transfer_bank}}"
                                            class="form-control"
                                            placeholder="Cheque Bank"
                                            autocomplete="transfer_bank"
                                            autofocus>
                                    </div>

                                    <div class="col mb-0">
                                        @include('partials.receipt_bank_select', ['name' => 'transfer_to_bank_id', 'label' => 'RECEIVED INTO (OUR BANK)', 'banks' => $banks, 'selected' => $receipt->transfer_to_bank_id])
                                    </div>

                                </div>
                                <!-- end of cheque field -->
                                <br>

                                <!-- show if Momo value is selected -->
                                <div id="momorow" @if ($receipt->mode == 'momo') style="display: flex;" @else style="display: none;" @endif class="row g-6">
                                    <div class="col mb-0">
                                        <label for="momo_transactin_id" class="form-label"> {{ __('MOMO TRANSACTION ID') }} </label>
                                        <input
                                            type="text"
                                            id="momo_transactin_id"
                                            name="momo_transactin_id"
                                            value="{{$receipt->momo_transactin_id}}"
                                            class="form-control"
                                            placeholder="MoMo Transaction ID"
                                            autocomplete="momo_transactin_id"
                                            autofocus>
                                    </div>

                                    <div class="col mb-0">
                                        <label for="momo_amount" class="form-label"> {{ __('MOMO AMOUNT') }} </label>
                                        <input
                                            type="number"
                                            id="momo_amount"
                                            name="momo_amount"
                                            value="{{$receipt->momo_amount}}"
                                            class="form-control"
                                            placeholder="GH&#8373;"
                                            autocomplete="momo_amount"
                                            step="any"
                                            autofocus>
                                    </div>
                                </div>
                                <!-- end of MoMo field -->
                                <br>

                                <!-- show if Momo value is selected -->
                                <div id="otherpayrow" @if ($receipt->other_payment > 0.00) style="display: flex;" @endif class="row g-6">
                                    <div class="col mb-0">
                                        <label for="other_payment_descri" class="form-label"> {{ __('OTHER PAYMENT DESCRIPTION') }} </label>
                                        <input
                                            type="text"
                                            id="other_payment_descri"
                                            name="other_payment_descri"
                                            value="{{$receipt->other_payment_descri}}"
                                            class="form-control"
                                            placeholder="Other Payment Description"
                                            autocomplete="other_payment_descri"
                                            autofocus>
                                    </div>

                                    <div class="col mb-0">
                                        <label for="other_payment_amnt" class="form-label"> {{ __('AMOUNT') }} </label>
                                        <input
                                            type="number"
                                            id="other_payment_amnt"
                                            name="other_payment_amnt"
                                            value="{{$receipt->other_payment_amnt}}"
                                            class="form-control"
                                            placeholder="GH&#8373;"
                                            autocomplete="other_payment_amnt"
                                            step="any"
                                            autofocus>
                                    </div>
                                </div>
                                <!-- end of MoMo field -->
                                <br>



                                <div class="row">
                                    <!-- show if cash value is selected -->
                                    <div id="cashrow" @if ($receipt->mode == 'cash') style="display: flex;" @else style="display: none;" @endif  class="col mb-0">
                                        <label for="cash_amount" class="form-label"> {{ __('CASH AMOUNT') }}</label>
                                        <input
                                            type="number"
                                            name="cash_amount"
                                            value="{{$receipt->cash_amount}}"
                                            id="cash_amount"
                                            class="form-control"
                                            placeholder="GH&#8373;"
                                            autocomplete="cash_amount"
                                            step="any"
                                            autofocus>
                                    </div>
                                    <!-- end of cash value -->

                                    <div class="col mt-6">
                                        <div class="form-check">
                                            <input name="keep_credit" class="form-check-input" type="checkbox" value="1" id="keep_credit" {{ old('keep_credit', $receipt->unapplied_amount > 0) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="keep_credit"> Keep any extra as client credit (advance) </label>
                                        </div>
                                        <small class="text-muted d-block">Status and balance are worked out from the payment. Paying more than is owed needs this box ticked; the extra can be applied to the client's next invoice.</small>
                                    </div>
                                </div>

                                <div class="row text-center">
                                    <div class="col-6 mt-6">
                                        <div class="input-group">
                                            <label class="input-group-text" for="staff">{{ __('ASSIGN TO') }}</label>
                                            <select name="staff" class="form-select @error('staff') is-invalid @enderror" id="staff" required>
                                                <option disabled {{ old('staff', $receipt->user2_id2) ? '' : 'selected' }}>Choose...</option>
                                            @foreach($assign_staff as $user)
                                                @if($user->field_id == Auth::user()->field_id)
                                                <option value="{{$user->id}}" {{ old('staff', $receipt->user_id1) == $user->id ? 'selected' : '' }}> {{$user->name}} </option>
                                                @elseif(Auth::user()->hasRole(['Manager']))
                                                <option value="{{$user->id}}" {{ old('staff', $receipt->user_id2) == $user->id ? 'selected' : '' }}> {{$user->name}} </option>
                                                @endif
                                            @endforeach
                                            </select>
                                            @error('staff')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <br>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-info d-grid w-100" data-loading onclick="return confirm('Kindly Confirm?')">{{ __('Update') }}</button>
                            </div>
                    </form>

                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card text-bg-dark">
                <div class="card-body">
                    <h5 class="card-title text-white"> Invoice Details <span class="badge bg-label-dark">{{$invoice->status}}</span> </h5>
                    <hr>
                    <p class="card-text">Invoice # : FWSSi{{$invoice->id}}</p>
                    <p class="card-text">Invoice Total : GH&#8373;{{number_format($invoice->total, 2)}}</p>
                    <p style="display: none;" id="amount_payable" class="card-text text-info"> <strong> Amount Payable : GH&#8373; {{ number_format($invoice->total - ($invoice->sub_amount * 0.075), 2)   }} </strong></p>
                    <p class="card-text text-info">Still owed (other receipts) : <strong> GH&#8373;{{ number_format(max(0, $invoice->total - $invoice->allocations()->where('receipt_id', '!=', $receipt->id)->sum('settled')), 2)}} </strong> </p>
                    <p class="card-text">Invoice Issued : {{$invoice->created_at->format('l, F j, Y H:i A')}}</p>
                    <p class="card-text">Invoice Due Date : {{$invoice->due_date->format('l, F j, Y H:i A')}}</p>

                    <br>
                    <h5 class="card-title text-white"> Client Details </h5>
                    <hr>
                    <p class="card-text">Client Name : {{$invoice->client->name}}</p>
                    <p class="card-text">Number : {{$invoice->client->phone_number}}</p>
                    <p class="card-text">Business : {{$invoice->client->business_name}}</p>
                    <p class="card-text">Field Office : {{$invoice->client->field->name}}</p>

                </div>
            </div>

        </div>

    </div>
    </div>
    @endsection


    @section('scripts')
    <script>
/**
 * Payment mode handler for the Receipt form.
 *
 * `#mode` is now a group of checkboxes (name="mode[]"), one per payment
 * mode, instead of a single <select>. A receipt can be paid across
 * multiple modes at once — e.g. part cash, part cheque — so each mode's
 * detail row is shown/hidden independently based on its own checkbox,
 * not exclusively of the others.
 *
 * Behavior:
 *  - Checking a mode's checkbox reveals its detail row and makes that
 *    row's fields required.
 *  - Unchecking it hides the row, clears its fields, and removes the
 *    `required` attribute — so unchecked modes never submit stale or
 *    empty required data.
 *  - Any number of modes can be checked simultaneously.
 *  - State is resynced on page load so a validation-error round-trip
 *    (old('mode', ...) re-checking boxes) shows the right rows without
 *    the user re-clicking anything.
 */
$(document).ready(function () {
    // Maps each mode checkbox to its detail row and the fields inside
    // that row which should be required only while the mode is checked.
    const paymentModes = {
        'cheque': {
            checkbox: '#mode_cheque',
            row: '#chequerow',
            required: ['#cheque_reference', '#cheque_amount', '#cheque_bank', '#cheque_to_bank_id']
        },
        'transfer': {
            checkbox: '#mode_transfer',
            row: '#transferrow',
            required: ['#transfer_reference', '#transfer_amount', '#transfer_bank', '#transfer_to_bank_id']
        },
        'momo': {
            checkbox: '#mode_momo',
            row: '#momorow',
            required: ['#momo_transactin_id', '#momo_amount']
        },
        'other payments': {
            checkbox: '#mode_other_payments',
            row: '#otherpayrow',
            required: ['#other_payment_descri', '#other_payment_amnt']
        },
        'cash': {
            checkbox: '#mode_cash',
            row: '#cashrow',
            required: ['#cash_amount']
        }
    };
 
    function syncModeRow(config) {
        const isChecked = $(config.checkbox).is(':checked');
        const $row = $(config.row);
 
        if (isChecked) {
            $row.slideDown(150);
        } else {
            $row.slideUp(150);
        }
 
        config.required.forEach((selector) => {
            $(selector).prop('required', isChecked);
            if (!isChecked) {
                $(selector).val('');
            }
        });
    }
 
    Object.values(paymentModes).forEach((config) => {
        // Sync once on load (handles old('mode', ...) pre-checked boxes
        // after a validation error, or an existing receipt being edited).
        syncModeRow(config);
 
        $(config.checkbox).on('change', function () {
            syncModeRow(config);
        });
    });
 
    // At least one mode must be checked before submit — the browser's
    // native `required` on a checkbox group doesn't work across multiple
    // checkboxes, so this is a light client-side backstop (server-side
    // validation via `mode.*` rules is still the source of truth).
    $('form').on('submit', function (e) {
        const anyChecked = Object.values(paymentModes).some((config) => $(config.checkbox).is(':checked'));
        if (!anyChecked) {
            e.preventDefault();
            alert('Please select at least one payment mode.');
        }
    });
});
    </script>

    <script>
        $(document).ready(function() {

            $('#wth').change(function() {
                if ($(this).is(':checked')) {
                    var value = $(this).val();
                    $('#amount_payable').toggle();
                    $('#wht_value').toggle();
                    // console.log("Checkbox checked! Value: " + value);
                } else {
                    $('#amount_payable').toggle();
                    $('#wht_value').toggle();
                }
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            $('#vat').change(function() {
                if ($(this).is(':checked')) {
                    $('#vat7_value').toggle();
                    // console.log("Checkbox checked! Value: " + value);
                } else {
                    $('#vat7_value').toggle();
                }
            });
        });
    </script>

    <script>
    $(document).ready(function() {
        $('#deductions').change(function() {
            if ($(this).is(':checked')) {
                $('#deduction_field').toggle();
                // console.log("Checkbox checked! Value: " + value);
            } else {
                $('#deduction_field').toggle();
            }
        });
    });
</script>
    @endsection
</x-sales-dashboard>
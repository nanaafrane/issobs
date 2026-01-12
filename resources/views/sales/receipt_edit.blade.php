<x-sales-dashboard>

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
                  <li class="menu-item ">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-bxs-receipt bg-primary"></i>
          <div class="text-truncate" data-i18n="Staffs">Pro Forma</div>
          </a>
          <ul class="menu-sub">
          <li class="menu-item ">
              <a href="{{url('proforma/create')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SRegister">Generate</div>
              </a>
          </li>
          <li class="menu-item">
              <a href="{{url('proforma')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">List</div>
              </a>
          </li>
          </ul>
      </li>
            @endif

      @if(Auth::user()->hasRole(['Finance Manager']))
                  <li class="menu-item active">
                <a href="{{url('receipt')}}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                    <div class="text-truncate" data-i18n="Receipts"><strong>Receipts</strong> </div>
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
      @elseif(Auth::user()->hasRole(['Invoice']))

      <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
        <li class="menu-item ">
            <a class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-bxs-user-detail bg-info"></i>
                <div class="text-truncate" data-i18n="Clients"><strong>Clients</strong></div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item ">
                    <a href="{{url('client/create')}}" class="menu-link">
                        <div class="text-truncate" data-i18n="CRegister">Register</div>
                    </a>
                </li>
                <li class="menu-item ">
                    <a href="{{url('client')}}" class="menu-link">
                        <div class="text-truncate" data-i18n="CList">List</div>
                    </a>
                </li>
            </ul>
        </li>
        <li class="menu-item ">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bxs-user-account"></i>
          <div class="text-truncate" data-i18n="Staffs">Employees</div>
          </a>
          <ul class="menu-sub">
          <li class="menu-item ">
              <a href="{{url('employees/create')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SRegister">Register</div>
              </a>
          </li>
          <li class="menu-item">
              <a href="{{url('employees')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">List</div>
              </a>
          </li>
          </ul>
      </li>
      <li class="menu-item">
          <a href="{{url('departments')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bxs-buildings"></i>
          <div class="text-truncate" data-i18n="depnroles">Department & Roles </div>
          </a>
      </li>
      <li class="menu-item">
          <a href="{{url('field')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-bxs-location-plus"></i>
          <div class="text-truncate" data-i18n="fOffices">Field Offices</div>
          </a>
      </li>
      @endif

      @if(Auth::user()->hasRole(['Manager','Officer','Finance Manager']) )

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
      <li class="menu-item">
        <a href="{{url('expense')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-bxs-credit-card bg-secondary"></i>
          <div class="text-truncate" data-i18n="Expense"> Expense </div>
        </a>
       </li>
      @endif

      @if(Auth::user()->hasPermission('Accounts') )
      <li class="menu-header small text-uppercase"><span class="menu-header-text">PAYROLL</span></li>
        <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                <div class="text-truncate" data-i18n="Payroll">Payroll</div>
                </a>
                <ul class="menu-sub">
                @if(Auth::user()->hasRole(['Invoice']))
                <li class="menu-item">
                    <a href="{{ url('salaries') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bxs-user-account"></i>
                    <div class="text-truncate" data-i18n="Employees">Add to Salaries</div>
                    </a>
                </li>
                @endif

                <li class="menu-item">
                    <a href="{{ url('salaries/create') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                    <div class="text-truncate" data-i18n="Salaries">Salaries</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="{{ url('salariesTransaction') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-transfer-alt"></i>
                    <div class="text-truncate" data-i18n="Transaction">Transactions</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="{{ url('salariesInvPayroll') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-git-compare"></i>
                    <div class="text-truncate" data-i18n="InvtoPayroll">Invoice to Payroll</div>
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
            <div class="col-6">
                <h3 class="card-header text-primary"> <i class="icon-base bx bx-bxs-receipt"></i> Receipt <i class="icon-base bx bx-bxs-right-arrow-alt"></i> Edit </h3>
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
                                    <label class="form-check-label" for="wth">  % WITHHOLDING TAX </label>
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

                                <div id="vat7_value" @if ($receipt->vat7_value > 0) style="display: flex;" @else style="display: none;" @endif class="col-md-6">
                                    <input name="vat7_value" type="number" class="form-control" value="{{$invoice->sub_total * 0.07 }}" step="any">
                                </div>
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
                                        <div class="input-group">
                                            <label class="input-group-text" for="inputGroupSelect01">{{ __('MODE') }}</label>
                                            <select name="mode" class="form-select @error('mode') is-invalid @enderror" id="mode" required>
                                               @foreach ($mode as $modes )      
                                                <option @if ($receipt->mode == $modes->name ) selected @endif  value="{{ $modes->name }}"> {{ $modes->name }}</option>                                                  
                                               @endforeach
                                                <option value="cheque">Cheque </option>
                                               
                                            </select>
                                            @error('mode')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>
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
                                        <label for="cheque_bank" class="form-label"> {{ __('CHEQUE BANK') }} </label>
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

                                </div>
                                <!-- end of cheque field -->
                                <br>

                                <!-- show if Bank Transfer value is selected -->
                                <div id="transferrow" @if ($receipt->mode == 'bank_transfer') style="display: flex;" @else style="display: none;" @endif  class="row g-6">
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
                                        <label for="transfer_bank" class="form-label"> {{ __('TRANSFER BANK') }} </label>
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
                                        <div class="input-group">
                                            <label class="input-group-text" for="status">{{ __('STATUS') }}</label>
                                            <select name="status" class="form-select @error('status') is-invalid @enderror" id="status" required>
                                                @foreach ( $status as $stat )      
                                                <option @if ($receipt->status == $stat->name ) selected @endif  value="{{ $stat->name }}"> {{ $stat->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('status')
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
                                <button type="submit" class="btn btn-info d-grid w-100" onclick="return confirm('Kindly Confirm?')">{{ __('Update') }}</button>
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
                    <p class="card-text text-info">Invoice Balance : <strong> GH&#8373;{{ number_format($invoice->balance, 2)}} </strong> </p>
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
        const mySelect = document.getElementById('mode');

        mySelect.addEventListener('change', function() {
            // Get the selected value
            const selectedValue = this.value;

            if (selectedValue == 'cheque') {

                $("#chequerow").toggle();
            }
            if (selectedValue == 'transfer') {

                $("#transferrow").toggle();
            }
            if (selectedValue == 'momo') {
                $("#momorow").toggle();
            }
            if (selectedValue == 'cash') {
                $("#cashrow").toggle();
            }
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
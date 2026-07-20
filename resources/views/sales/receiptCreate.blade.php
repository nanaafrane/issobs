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
      <li class="menu-item  ">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-home-smile"></i>
          <div class="text-truncate" data-i18n="Dashboards"><strong>Dashboard</strong></div>
        </a>
        <ul class="menu-sub">
          <li class="menu-item ">
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

      @if(Auth::user()->hasRole(['Invoice','Finance Manager', 'Director']))
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
          <li class="menu-item">
              <a href="{{url('proformaClient')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">ProForma Clients</div>
              </a>
          </li>
          </ul>
      </li>
      @endif


      @if(Auth::user()->hasRole(['Finance Manager', 'Manager','Admin Assistant' ]))
            
     <!-- <li class="menu-item">
        <a href="{{url('receipt')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-money-withdraw bg-primary"></i>
          <div class="text-truncate" data-i18n="Receipts">Receipts</div>
        </a>
      </li> -->

      <li class="menu-item active open">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-money-withdraw bg-primary"></i>
          <div class="text-truncate" data-i18n="Receipts">Receipts</div>
          </a>
          <ul class="menu-sub">

            <li class="menu-item active">
                <a href="{{url('receipt')}}" class="menu-link">
                <div class="text-truncate" data-i18n="RList">List</div>
                </a>
            </li>
            <li class="menu-item">
                <a href="{{url('receiptPending')}}" class="menu-link">
                <div class="text-truncate" data-i18n="RPending">Pending </div>
                </a>
            </li>

          </ul>
      </li>
       @endif

       @if(Auth::user()->hasRole(['Finance Manager' ]))
      <!-- Components -->
      <li class="menu-header small text-uppercase"><span class="menu-header-text text-info">Management</span></li>
      <li class="menu-item">
        <a href="{{url('client')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-bxs-user-detail bg-info"></i>
          <div class="text-truncate" data-i18n="Clients">Clients</div>
        </a>
      </li>
      <li class="menu-item ">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bxs-user-account bg-info"></i>
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
          <li class="menu-item">
              <a href="{{url('employeesnrrit')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">Terminate / Recruit</div>
              </a>
          </li>
          <li class="menu-item">
              <a href="{{url('employeesBank')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">Employee Banks</div>
              </a>
          </li>
          <li class="menu-item">
              <a href="{{url('employeesCash')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">Employee Cash</div>
              </a>
          </li>

          </ul>
      </li>

      <li class="menu-item">
        <a href="{{url('category')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bxs-category bg-info"></i>
          <div class="text-truncate" data-i18n="Categories">Categories</div>
        </a>
      </li>
      @elseif(Auth::user()->hasRole(['Invoice' ,'Director', 'Manager', 'Admin Assistant']))

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

                <li class="menu-item ">
                    <a href="{{url('clientTerminated')}}" class="menu-link">
                        <div class="text-truncate" data-i18n="CList">Terminated</div>
                    </a>
                </li>

                <li class="menu-item ">
                    <a href="{{url('clientPending')}}" class="menu-link">
                        <div class="text-truncate" data-i18n="CList">Pending</div>
                    </a>
                </li>
            </ul>
        </li>
        <li class="menu-item ">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bxs-category bg-info"></i>
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
          <li class="menu-item">
                <a href="{{url('employeesPending')}}" class="menu-link">
                <div class="text-truncate" data-i18n="SList">Pending</div>
                </a>
            </li>
          <li class="menu-item">
              <a href="{{url('employeesnrrit')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">Terminate / Recruit </div>
              </a>
          </li>
          <li class="menu-item">
              <a href="{{url('employeesBank')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">Employee Banks</div>
              </a>
          </li>

          <li class="menu-item">
              <a href="{{url('employeesCash')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">Employee Cash</div>
              </a>
          </li>

          </ul>
      </li>

      <!-- <li class="menu-item">
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
      </li> -->
      @endif

      @if(Auth::user()->hasRole(['Finance Manager', 'Director']) )

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

      @if(Auth::user()->hasPermission('Accounts') && Auth::user()->hasRole(['Invoice','Officer', 'Director', 'Finance Manager']) )

      <li class="menu-header small text-uppercase"><span class="menu-header-text">PAYROLL</span></li>
        <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                <div class="text-truncate" data-i18n="Payroll">Payroll</div>
                </a>
                <ul class="menu-sub">
                @if(Auth::user()->hasRole([ 'Finance Manager']))
                <li class="menu-item">
                    <a href="{{ url('salaries') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bxs-user-account"></i>
                    <div class="text-truncate" data-i18n="Employees">Add to Salaries</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="{{ url('salaries/create') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                    <div class="text-truncate" data-i18n="Salaries">Salaries</div>
                    </a>
                </li>
                @endif

                <li class="menu-item">
                    <a href="{{ url('salariesTransaction') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-transfer-alt"></i>
                    <div class="text-truncate" data-i18n="Transaction">Transactions</div>
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
                <h3 class="card-header text-primary"> <i class="icon-base bx bx-bxs-receipt"></i> Receipt <i class="icon-base bx bx-bxs-right-arrow-alt"></i> Create </h3>
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
            </div><br>
        @endif

        <div class="card-header  ml-2  d-none d-lg-block">
                  @include('flash-messages')
        </div> <br>
        <div class="row mb-6 gy-6">
            <div class="col-xl">
                <div class="card">
                    <form method="POST" action="/receipt" enctype="multipart/form-data">
                        @csrf
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"> Receipt </h5>
                            <div class="form-check mt-3">
                                <input name="advance_payment" class="form-check-input" type="checkbox" value="advance" id="defaultCheck1" />
                                <label class="form-check-label" for="defaultCheck1"> Tick For Advance Payment </label>
                            </div>

                            <!-- <small class="text-body float-end">Merged input group</small> -->

                            <div class="button-wrapper">
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
                                <div>Kindly attach copy of cheque, Max size of 2MB </div>

                            </div>


                        </div>
                        <hr>
                        <div class="card-body">

                            <div class="row">
                                <div class="col-md-6 form-check form-switch">
                                    <input name="wth" class="form-check-input" type="checkbox" id="wth" {{ old('wth') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="wth"> {{$wht_rate->wht_rate * 100}} % WITHHOLDING TAX </label>
                                </div>

                                <div id="wht_value" style="display: {{ old('wth') ? 'block' : 'none' }};" class="col-md-6">
                                    <input name="wht_amount" type="number" class="form-control" value="{{$invoice->sub_amount * 0.075}}" step="any">
                                </div>
                            </div>
                            <br>
                            <div class="row">
                                @if ($invoice->sub_total > 0.00)
                                <div class="col-md-6 form-check form-switch">
                                    <input name="vat" class="form-check-input" type="checkbox" id="vat" {{ old('vat') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="vat"> 7 % VAT </label>
                                </div>

                                <div id="vat7_value" style="display: {{ old('vat') ? 'block' : 'none' }};" class="col-md-6">
                                    <input name="vat7_value" type="number" class="form-control" value="{{$invoice->sub_total * 0.07 }}" step="any">
                                </div>
                               @else
                                    <div class="col-md-6 form-check form-switch">
                                        <input name="vat" class="form-check-input" type="checkbox" id="vat" {{ old('vat') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="vat"> 7 % VAT </label>
                                    </div>

                                    <div id="vat7_value" style="display: {{ old('vat') ? 'block' : 'none' }};" class="col-md-6">
                                        <input name="vat7_value" type="number" class="form-control" value="{{$invoice->sub_amount * 0.07 }}" step="any">
                                    </div>

                                @endif
                            </div>

                            <br>
                            <div class="row">
                                <div class="col-md-6 form-check form-switch">
                                    <input name="deductions" class="form-check-input" type="checkbox" id="deductions" {{ old('deductions') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="deductions"> OTHER DEDUCTIONS </label>
                                </div>

                                <div class="col mb-0">
                                        <label for="receipt_month" class="form-label"> {{ __('RECEIPT DATE') }}</label>
                                        <input
                                           name="receipt_month" type="date"
                                           class="form-control @error('receipt_month') is-invalid @enderror"
                                           value="{{ old('receipt_month') }}"
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
                                            value="{{ old('from') }}"
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
                                                // Machine-readable id per mode, e.g. "other payments" -> "mode_other_payments"
                                                $modeInputId = fn ($name) => 'mode_' . str_replace(' ', '_', strtolower($name));
                                                $selectedModes = old('mode', []);
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
                                <div class="row"  id="deduction_field"  style="display: {{ old('deductions') ? 'flex' : 'none' }};">
                                    <div class="col mb-0">
                                        <label for="dAmount" class="form-label"> {{ __('DEDUCTED AMOUNT') }}</label>
                                        <input
                                            type="number"
                                            name="dAmount"
                                            id="dAmount"
                                            class="form-control @error('dAmount') is-invalid @enderror"
                                            value="{{ old('dAmount') }}"
                                            placeholder="Deducted Amount"
                                            autocomplete="dAmount"
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
                                            id="description"
                                            class="form-control @error('description') is-invalid @enderror"
                                            value="{{ old('description') }}"
                                            placeholder="Description"
                                            autocomplete="description">

                                        @error('description')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                </div> <br>

                                <!-- show if cheque value is selected -->
                                <div id="chequerow" style="display: {{ old('mode') == 'cheque' ? 'flex' : 'none' }};" class="row g-6">
                                    <div class="col mb-0">
                                        <label for="cheque_reference" class="form-label"> {{ __('CHEQUE REFERENCE #') }} </label>
                                        <input
                                            type="text"
                                            id="cheque_reference"
                                            name="cheque_reference"
                                            class="form-control"
                                            value="{{ old('cheque_reference') }}"
                                            placeholder="Cheque Reference"
                                            autocomplete="cheque_reference">
                                    </div>

                                    <div class="col mb-0">
                                        <label for="cheque_amount" class="form-label"> {{ __('CHEQUE AMOUNT') }} </label>
                                        <input
                                            type="number"
                                            id="cheque_amount"
                                            name="cheque_amount"
                                            class="form-control"
                                            value="{{ old('cheque_amount') }}"
                                            placeholder="GH&#8373;"
                                            autocomplete="cheque_amount"
                                            step="any">
                                    </div>

                                    <div class="col mb-0">
                                        <label for="cheque_bank" class="form-label"> {{ __('CHEQUE BANK') }} </label>
                                        <input
                                            type="text"
                                            id="cheque_bank"
                                            name="cheque_bank"
                                            class="form-control"
                                            value="{{ old('cheque_bank') }}"
                                            placeholder="Cheque Bank"
                                            autocomplete="cheque_bank">
                                    </div>

                                </div>
                                <!-- end of cheque field -->
                                <br>

                                <!-- show if Bank Transfer value is selected -->
                                <div id="transferrow" style="display: {{ old('mode') == 'transfer' ? 'flex' : 'none' }};" class="row g-6">
                                    <div class="col mb-0">
                                        <label for="transfer_reference" class="form-label"> {{ __('TRANSFER REFERENCE #') }} </label>
                                        <input
                                            type="text"
                                            id="transfer_reference"
                                            name="transfer_reference"
                                            class="form-control"
                                            value="{{ old('transfer_reference') }}"
                                            placeholder="Transfer Reference"
                                            autocomplete="transfer_reference">
                                    </div>

                                    <div class="col mb-0">
                                        <label for="transfer_amount" class="form-label"> {{ __('TRANSFER AMOUNT') }} </label>
                                        <input
                                            type="number"
                                            id="transfer_amount"
                                            name="transfer_amount"
                                            class="form-control"
                                            value="{{ old('transfer_amount') }}"
                                            placeholder="GH&#8373;"
                                            autocomplete="transfer_amount"
                                            step="any">
                                    </div>

                                    <div class="col mb-0">
                                        <label for="transfer_bank" class="form-label"> {{ __('TRANSFER BANK') }} </label>
                                        <input
                                            type="text"
                                            id="transfer_bank"
                                            name="transfer_bank"
                                            class="form-control"
                                            value="{{ old('transfer_bank') }}"
                                            placeholder="Cheque Bank"
                                            autocomplete="transfer_bank">
                                    </div>

                                </div>
                                <!-- end of cheque field -->
                                <br>

                                <!-- show if Momo value is selected -->
                                <div id="momorow" style="display: {{ old('mode') == 'momo' ? 'flex' : 'none' }};" class="row g-6">
                                    <div class="col mb-0">
                                        <label for="momo_transactin_id" class="form-label"> {{ __('MOMO TRANSACTION ID') }} </label>
                                        <input
                                            type="text"
                                            id="momo_transactin_id"
                                            name="momo_transactin_id"
                                            class="form-control"
                                            value="{{ old('momo_transactin_id') }}"
                                            placeholder="MoMo Transaction ID"
                                            autocomplete="momo_transactin_id">
                                    </div>

                                    <div class="col mb-0">
                                        <label for="momo_amount" class="form-label"> {{ __('MOMO AMOUNT') }} </label>
                                        <input
                                            type="number"
                                            id="momo_amount"
                                            name="momo_amount"
                                            class="form-control"
                                            value="{{ old('momo_amount') }}"
                                            placeholder="GH&#8373;"
                                            autocomplete="momo_amount"
                                            step="any">
                                    </div>

                                </div>
                                <!-- end of MoMo field -->
                                <br>

                                <!-- show if otherpayment value is selected -->
                                <div id="otherpayrow" style="display: {{ old('mode') == 'other payments' ? 'flex' : 'none' }};" class="row g-6">
                                    <div class="col mb-0">
                                        <label for="other_payment_descri" class="form-label"> {{ __('OTHER PAYMENT DESCRIPTION') }} </label>
                                        <input
                                            type="text"
                                            id="other_payment_descri"
                                            name="other_payment_descri"
                                            class="form-control"
                                            value="{{ old('other_payment_descri') }}"
                                            placeholder="Other Payment Description"
                                            autocomplete="other_payment_descri">
                                    </div>

                                    <div class="col mb-0">
                                        <label for="other_payment_amnt" class="form-label"> {{ __('AMOUNT') }} </label>
                                        <input
                                            type="number"
                                            id="other_payment_amnt"
                                            name="other_payment_amnt"
                                            class="form-control"
                                            value="{{ old('other_payment_amnt') }}"
                                            placeholder="GH&#8373;"
                                            autocomplete="other_payment_amnt"
                                            step="any">
                                    </div>

                                </div>
                                <!-- end of otherpay field -->
                                <br>


                                <div class="row">
                                    <!-- show if cash value is selected -->
                                    <div id="cashrow" style="display: {{ old('mode') == 'cash' ? 'block' : 'none' }};" class="col mb-0">
                                        <label for="cash_amount" class="form-label"> {{ __('CASH AMOUNT') }}</label>
                                        <input
                                            type="number"
                                            name="cash_amount"
                                            id="cash_amount"
                                            class="form-control"
                                            value="{{ old('cash_amount') }}"
                                            placeholder="GH&#8373;"
                                            autocomplete="cash_amount"
                                            step="any">
                                    </div>
                                    <!-- end of cash value -->

                                    <div class="col mt-6">
                                        <div class="input-group">
                                            <label class="input-group-text" for="status">{{ __('STATUS') }}</label>
                                            <select name="status" class="form-select @error('status') is-invalid @enderror" id="status" required>
                                                <option disabled {{ old('status') ? '' : 'selected' }}>Choose...</option>
                                               @foreach ( $status as $stat )
                                                <option value="{{ $stat->name }}" {{ old('status') == $stat->name ? 'selected' : '' }}> {{ $stat->name }}</option>
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


                                <div class="row text-center">

                                    <div class="col-6 mt-6">
                                        <div class="input-group">
                                            <label class="input-group-text" for="staff">{{ __('ASSIGN TO') }}</label>
                                            <select name="staff" class="form-select @error('staff') is-invalid @enderror" id="staff" required>
                                                <option disabled {{ old('staff') ? '' : 'selected' }}>Choose...</option>
                                            @foreach($assign_staff as $user)
                                                @if($user->field_id == Auth::user()->field_id)
                                                <option value="{{$user->id}}" {{ old('staff') == $user->id ? 'selected' : '' }}> {{$user->name}} </option>
                                                @elseif(Auth::user()->hasRole(['Manager']))
                                                <option value="{{$user->id}}" {{ old('staff') == $user->id ? 'selected' : '' }}> {{$user->name}} </option>
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
                                <button type="submit" class="btn btn-info d-grid w-100" data-loading onclick="return confirm('Kindly Confirm?')">{{ __('Generate') }}</button>
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


    @endsection


    @section('scripts')

    <script >
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
            required: ['#cheque_reference', '#cheque_amount', '#cheque_bank']
        },
        'transfer': {
            checkbox: '#mode_transfer',
            row: '#transferrow',
            required: ['#transfer_reference', '#transfer_amount', '#transfer_bank']
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

/**
 * WHT / VAT / Deductions checkbox handlers.
 *
 * Fixes vs. original:
 *  - Original used `.toggle()` on both the checkbox `change` handler AND
 *    relied on the row starting hidden via inline `style="display:none;"`.
 *    That works for a fresh page load, but if the form re-renders after a
 *    validation error and Blade re-checks the box with old('wth') / etc.,
 *    the JS never re-syncs — the checkbox shows checked but its field
 *    stays hidden, because `.toggle()` only flips state on `change`
 *    events, not on page load.
 *  - Each handler now derives visibility directly from `.is(':checked')`
 *    (show if checked, hide if not) instead of blindly toggling, and the
 *    same function runs once on page load to sync state — so a
 *    pre-checked box (validation round-trip, browser back button, etc.)
 *    always shows its matching field.
 *  - Consolidated into one small config-driven block instead of three
 *    separate near-identical `$(...).change(...)` handlers.
 */
$(document).ready(function () {
    const conditionalFields = [
        { checkbox: '#wth', targets: ['#amount_payable', '#wht_value'] },
        { checkbox: '#vat', targets: ['#vat7_value'] },
        { checkbox: '#deductions', targets: ['#deduction_field'] }
    ];

    function syncField(checkboxSelector, targetSelectors) {
        const isChecked = $(checkboxSelector).is(':checked');
        targetSelectors.forEach((selector) => {
            const $target = $(selector);
            if (isChecked) {
                $target.show();
            } else {
                $target.hide();
            }
        });
    }

    conditionalFields.forEach(({ checkbox, targets }) => {
        // Sync once on load, in case the checkbox is pre-checked
        // (e.g. Blade `old('wth')` after a validation error).
        syncField(checkbox, targets);

        // Sync on every change instead of blindly toggling.
        $(checkbox).on('change', function () {
            syncField(checkbox, targets);
        });
    });
});
    </script>
    
    @endsection
</x-sales-dashboard>
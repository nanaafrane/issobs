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
            @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))

            <li class="menu-item active">
                <a href="{{ url('invoice') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bxs-receipt bg-primary"></i>
                    <div class="text-truncate" data-i18n="Invoices"><strong>Invoices</strong></div>
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


      @if(Auth::user()->hasRole(['Finance Manager']))
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
          <li class="menu-item">
              <a href="{{url('employeesBank')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">Employee Banks</div>
              </a>
          </li>
          </ul>
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
                    <li class="menu-item">
              <a href="{{url('employeesBank')}}" class="menu-link">
              <div class="text-truncate" data-i18n="SList">Employee Banks</div>
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

            @if(Auth::user()->hasPermission('Accounts') && Auth::user()->hasRole(['Invoice', 'Officer', 'Director', 'Finance Manager']) )

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

                </ul>
            </li>
      @endif

        </ul>
    </aside>
    @endsection



    @section('content')

    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row">
                <div class="col-12">
                    <!-- <div class="card"> -->
                    <h3 class="card-header text-primary"> <i class="icon-base bx bx-bxs-receipt"></i> Invoice <i class="icon-base bx bx-bxs-right-arrow-alt"></i> Generate </h3>
                    <!-- <div class="card-body demo-vertical-spacing demo-only-element"> Invoice / Create </div> -->
                    <!-- </div> -->
                </div>
            </div>
            <!-- Invoice 1 - Bootstrap Brain Component -->
            <section class="py-3 py-md-5">
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-9 col-xl-8 col-xxl-7">
                        <div class="row gy-3 mb-3">
                            <div class="col-6">
                                <h2 class="text-uppercase text-endx m-0 text-danger">Invoice</h2>
                            </div>
                            <div class="col-6">
                                <a class="d-block text-end">
                                    <img width="150px" src="{{asset('img/icons/brands/issobs.png')}}" class="img-fluid" alt="BootstrapBrain Logo" width="135" height="44">
                                </a>
                            </div>
                            <div class="col-12" style="margin-top: -70px;">
                                <h4 class="text-danger">From</h4>
                                <address>
                                    <strong>FIRST WATCH SECURITY SERVICE LIMITED.</strong><br>
                                    P.O.BOX AN 18529, GPS : GA-105-4850,<br>
                                    BOUNDARY ROAD, ACCRA NORTH.<br>
                                    TEL : {{$client->field->number}}, +233(0) 560 027 411.<br>
                                    EMAIL : info@firstwatchsecgh.com. <br>
                                    EMAIL : invoice.firstwatchsecgh@gmail.com.
                                </address>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12 col-sm-6 col-md-8">
                                <h4 class="text-danger">Bill To</h4>
                                <address>
                                    @if($client->name)
                                    <strong>{{$client->name}}</strong><br>
                                    @endif

                                    @if($client->business_name)
                                    <strong> Business Name : {{$client->business_name}} </strong> <br>
                                    @endif
                                    Location: {{$client->address}},<br>
                                    {{$client->field->name}},<br>
                                    Phone: {{$client->phone_number}}, {{$client->phone_number1}}<br>
                                </address>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <h4 style="background: #f00d0dff;" class="row text-white">
                                    <span class="col-6">Inv #</span>
                                    <span class="col-6 text-sm-end"></span>
                                </h4>
                            </div>
                        </div>

                        <hr />
                        <form method="POST" action="/invoice">
                            @csrf
                            <input type="text" name="client_id" value="{{$client->id}}" hidden>
                            <div class="row">
                                <div class="col-6">
                                    <h6 class="card-header">Due For Payment</h6>
                                    <div class="input-group">
                                        <input name="due_date" type="datetime-local" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-3"></div>
                                <div class="col-3">
                                    <h6 class="card-header"> Invoice Month</h6>
                                    <div class="input-group">
                                        <input name="invoice_month" type="month" class="form-control" required>
                                    </div>
                                </div>
                            </div> <br>
                            <hr />
                            <div class="row">
                                <div class="col-md-6 form-check form-switch">
                                    <input name="vat_standard" class="form-check-input" type="checkbox" id="vat_standard" checked>
                                    <label class="form-check-label" for="vat_standard"> VAT STANDARD RATE </label>
                                </div>

                                <div class="col-md-6" style="padding-left: 250px;">
                                    <a id="add" href="javascript:void(0);" class="btn btn-danger text-white" role="button">+</a>
                                </div>
                            </div>
                            <br>


                            <div id="product_form">
                                <div class="row invoice-row">
                                    <div class="col-2">
                                        <h5 class="card-header"> Services </h5>
                                        <select name="service[]" class="form-select @error('service') is-invalid @enderror service" required>
                                            <option selected disabled> Select </option>
                                            @foreach($services as $service)
                                            <option value="{{$service->name}}">{{$service->name}}</option>
                                            @endforeach
                                        </select>
                                        @error('service')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                    <div class="col-3">
                                        <h5 class="card-header">Description</h5>
                                        <textarea name="description[]" class="form-control description" placeholder="Description" rows="1" required></textarea>
                                    </div>

                                    <div class="col-2">
                                        <h5 class="card-header">Quantity</h5>
                                        <div class="input-group">
                                            <input type="number" name="quantity[]" class="form-control quantity" placeholder="Quantity" step="any" required>
                                        </div>
                                    </div>

                                    <div class="col-2">
                                        <h5 class="card-header">Unit Price</h5>
                                        <div class="input-group">
                                            <input type="number" name="unit_price[]" class="form-control unit_price" placeholder="GH&#8373;" step="any" required>
                                        </div>
                                    </div>

                                    <div class="col-2">
                                        <h5 class="card-header">Amount</h5>
                                        <div class="input-group">
                                            <input type="number" name="amount[]" class="form-control amount" placeholder="GH&#8373;" step="any" readonly>
                                        </div>
                                    </div>

                                    <div class="col-1 d-flex align-items-end">
                                        <a href="javascript:void(0);" class="btn btn-danger btn_remove w-100 text-white" style="display:none;" role="button">-</a>
                                    </div>

                                </div>
                                <div id="invoice_row_template" class="d-none">
                                    <div class="row invoice-row">
                                        <div class="col-2">
                                            <h5 class="card-header"> Services </h5>
                                            <select name="service[]" class="form-select service" required disabled>
                                                <option selected disabled> Select </option>
                                                @foreach($services as $service)
                                                <option value="{{$service->name}}">{{$service->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-3">
                                            <h5 class="card-header">Description</h5>
                                            <textarea name="description[]" class="form-control description" placeholder="Description" rows="1" required disabled></textarea>
                                        </div>

                                        <div class="col-2">
                                            <h5 class="card-header">Quantity</h5>
                                            <div class="input-group">
                                                <input type="number" name="quantity[]" class="form-control quantity" placeholder="Quantity" step="any" required disabled>
                                            </div>
                                        </div>

                                        <div class="col-2">
                                            <h5 class="card-header">Unit Price</h5>
                                            <div class="input-group">
                                                <input type="number" name="unit_price[]" class="form-control unit_price" placeholder="GH&#8373;" step="any" required disabled>
                                            </div>
                                        </div>

                                        <div class="col-2">
                                            <h5 class="card-header">Amount</h5>
                                            <div class="input-group">
                                                <input type="number" name="amount[]" class="form-control amount" placeholder="GH&#8373;" step="any" readonly disabled>
                                            </div>
                                        </div>

                                        <div class="col-1 d-flex align-items-end">
                                            <a href="javascript:void(0);" class="btn btn-danger btn_remove w-100 text-white" role="button">-</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div style="padding-top: 30px;" class="row">
                                <div class="col-12">
                                    <center>
                                        <button type="submit" id="submit" class="btn btn-danger btn-lg btn-block" data-loading onclick="return confirm('Kindly Confirm?')"> Generate </button>
                                    </center>
                                </div>
                            </div>

                        </form>

                    </div>
                </div>
        </div>
        </section>


    </div>
    </div>

    @endsection


    @section('scripts')
    <script>
        function updateRowAmount(row) {
            var quantity = parseFloat(row.find('.quantity').val()) || 0;
            var unitPrice = parseFloat(row.find('.unit_price').val()) || 0;
            row.find('.amount').val((quantity * unitPrice).toFixed(2));
        }

        $(document).ready(function() {
            $('#add').click(function(event) {
                event.preventDefault();
                var newRow = $('#invoice_row_template .invoice-row').clone();
                newRow.find('input, select, textarea').prop('disabled', false);
                $('#product_form').prepend(newRow);
                toggleRemoveButtons();
            });

            $(document).on('input', '.quantity, .unit_price', function() {
                var row = $(this).closest('.invoice-row');
                updateRowAmount(row);
            });

            $(document).on('click', '.btn_remove', function() {
                $(this).closest('.invoice-row').remove();
                toggleRemoveButtons();
            });

            function toggleRemoveButtons() {
                var rowCount = $('#product_form .invoice-row').length;
                $('#product_form .btn_remove').each(function() {
                    $(this).toggle(rowCount > 1);
                });
            }

            toggleRemoveButtons();
        });
    </script>
    @endsection


</x-sales-dashboard>
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
            <!-- Apps & Pages -->
            <li class="menu-header small text-uppercase ">
                <span class="menu-header-text">Transactions</span>
            </li>
            <!-- Pages -->
            <li class="menu-item">
                <a href="{{url('transaction')}}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-transfer-alt"></i>
                    <div class="text-truncate" data-i18n="Transaction">Transactions</div>
                </a>
            </li>
            @if(Auth::user()->hasRole(['Invoice','Finance Manager']))
            <li class="menu-item">
                <a href="{{ url('invoice') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bxs-receipt"></i>
                    <div class="text-truncate" data-i18n="Invoices">Invoices</div>
                </a>
            </li>
            <li class="menu-item active open">
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
                <li class="menu-item active">
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
                    <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
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

    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row">
                <div class="col-12">
                    <!-- <div class="card"> -->
                    <h3 class="card-header text-primary"> <i class="icon-base bx bx-bxs-receipt"></i> Pro Forma <i class="icon-base bx bx-bxs-right-arrow-alt"></i> Edit </h3>
                    <!-- <div class="card-body demo-vertical-spacing demo-only-element"> Invoice / Create </div> -->
                    <!-- </div> -->
                </div>
            </div>

            <div class="card-header  ml-2  d-none d-lg-block">
                @include('flash-messages')
            </div> 
            <!-- Invoice 1 - Bootstrap Brain Component -->
            <section class="py-3 py-md-5">
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-9 col-xl-8 col-xxl-7">
                        <div class="row gy-3 mb-3">
                            <div class="col-6">
                                <h2 class="text-uppercase text-endx m-0 text-danger">Pro Forma</h2>
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
                                    P.O.BOX AN 18529,<br>
                                    GPS: GA-105-4850,<br>
                                    BOUNDARY ROAD, ACCRA NORTH.<br>
                                    Tel: +233(0) 501 696 315, +233(0) 560 027 411.<br>
                                    Email: info@firstwatchsecgh.com.
                                </address>
                            </div>
                        </div>
        <form method="POST" action="/proforma/{{$proforma->id}}">
                @csrf
                @method('PUT')
                        <div class="row mb-3">
                            <div class="col-12 col-sm-6 col-md-8">
                                <h4 class="text-danger">Bill To</h4>
                                <address>
                                    <select name="client_id" class="form-select @error('client_id') is-invalid @enderror" id="client_id" required>
                                        <option selected disabled> Select </option>
                                        @foreach($clients as $client)
                                        <!-- <option value="{{$client->id}}">{{$client->name}} {{$client->business_name}}</option> -->
                                        <option  @if($client->id == $proforma->client?->id) selected @endif  value="{{$client->id}}">{{$client->name}} {{$client->business_name}}</option>

                                        @endforeach
                                    </select>
                                    @error('client_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </address>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">

                                <h5 style="background: #f00d0dff;" class="row text-white">
                                    <span class="col-12">Inv #: FWSSi{{$proforma->id}}</span>
                                </h5>
                                <address>
                                    <span class="card-header"> Issued : </span>
                                    <span class="col-6"> {{$proforma->created_at->format('d/m/Y H:i A')}} </span> <br>

                                    <span class="card-header">Due : </span>
                                    <span class="col-6">{{$proforma->due_date->format('d/m/Y H:i A')}} </span>

                                </address>
                            </div>
                        </div>

                        <hr />

                            <div class="row">
                                <div class="col-6">
                                    <h6 class="card-header">Due Date</h6>
                                    <div class="input-group">
                                        <input value="{{$proforma->due_date}}" name="due_date" type="datetime-local" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-3"></div>
                                <div class="col-3">
                                    <h6 class="card-header"> Invoice Month</h6>
                                    <div class="input-group">
                                        <input name="invoice_month" type="month" value="{{$proforma->invoice_month}}" class="form-control" required>
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
                                    <button id="add" type="button" class="btn btn-danger">+</button>
                                </div>
                            </div>
                            <br>
                            <div id="product_form">
                                @foreach($proforma_data as $key => $data)
                                <div class="row" id="roww{{$key}}">
                                    <div class="col-2">
                                        <h5 class="card-header" for="service" class="form-label"> Services </h5>
                                        <select name="service[]" class="form-select" id="service">
                                            @foreach($services as $service)
                                            <option @if ($data->service_name == $service->name) selected @endif value="{{$service->name}}">{{$service->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-3">
                                        <h5 class="card-header" for="description" class="form-label">Description</h5>
                                        <textarea name="description[]"
                                            id="description"
                                            class="form-control"
                                            placeholder="Description" class="form-control" rows="1">{{$data->description}}</textarea>
                                    </div>

                                    <div class="col-2">
                                        <h5 class="card-header">Quantity</h5>
                                        <div class="input-group">
                                            <input
                                                type="number"
                                                name="quantity[]"
                                                id="quantity"
                                                value="{{$data->quantity}}"
                                                class="form-control"
                                                step="any"
                                                placeholder="Quantity">
                                        </div>
                                    </div>

                                    <div class="col-2">
                                        <h5 class="card-header">Unit Price</h5>
                                        <div class="input-group">
                                            <input
                                                type="number"
                                                name="unit_price[]"
                                                id="unit_price"
                                                value="{{$data->unit_price}}"
                                                oninput="unitPrice()"
                                                class="form-control" step="any"
                                                placeholder="GH&#8373;">
                                        </div>
                                    </div>

                                    <div class="col-2">
                                        <h5 class="card-header">Amount</h5>
                                        <div class="input-group">
                                            <input
                                                type="number"
                                                name="amount[]"
                                                id="amount"
                                                value="{{$data->amount}}"
                                                class="form-control" step="any"
                                                placeholder="GH&#8373;">
                                        </div>
                                        <button type="button" id="del" class="btn btn-danger del" data-index="{{$key}}" style="margin-left: 130px;margin-top: -65px;">-</button>
                                    </div>

                                </div>
                                @endforeach


                                <div style="padding-top: 30px;" class="row">
                                    <div class="col-12">
                                        <button type="submit" id="submit" class="btn btn-danger btn-lg btn-block" onclick="return confirm('Kindly Confirm?')"> Update </button>
                                    </div>
                                </div>


                        </form>

                    </div>
                </div>
        </div>
        </section>


    </div>
    </div>
        <div class="buy-now">
            <button id="print"
                class="btn btn-danger btn-buy-now"> <i class="icon-base bx bxs-printer"></i> Print Invoice</button>
        </div> 

    @endsection

    @section('scripts')
    <script>
        function unitPrice() {
            let amount;
            let quantity = document.getElementById("quantity").value;

            let unit_price = document.getElementById("unit_price").value;
            // console.log(unit_price);
            amount = quantity * unit_price;

            document.getElementById("amount").value = amount;
            // // console.log(amount);
        }
    </script>

    <script>
        $(document).ready(function() {

            $(".del").click(function() {
                // var index = $(this).data('index');
                var button_id1 = $(this).data('index');
                $('#roww' + button_id1 + '').remove();
                // console.log("Clicked button ID:", button_id1);
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            var i = 1;
            $('#add').click(function() {
                i++;
                $('#product_form').prepend('<div class="row" id="row' + i + '"><div class="col-2"><h5 class="card-header" for="service" class="form-label"> Services </h5><select name="service[]" class="form-select" id="service" required><option selected disabled> Select </option>@foreach($services as $service)<option value="{{$service->name}}">{{$service->name}}</option>@endforeach</select></div><div class="col-3"><h5 class="card-header" for="description" class="form-label">Description</h5><textarea name="description[] "id="description" class="form-control" placeholder="Description" class="form-control" rows="1" required></textarea></div> <div class="col-2"><h5 class="card-header">Quantity</h5><div class="input-group"><input type="number" name="quantity[]" id="quantity" class="form-control" placeholder="Quantity" step="any" required></div></div> <div class="col-2"><h5 class="card-header">Unit Price</h5><div class="input-group"><input type="number" name="unit_price[] "id="unit_price" oninput="unitPrice()" class="form-control" placeholder="GH&#8373;" step="any" required></div></div> <div class="col-2"><h5 class="card-header">Amount</h5><div class="input-group"><input type="number" name="amount[]" id="amount" step="any" class="form-control" placeholder="GH&#8373;" required></div><button type="button" name="remove" id="' + i + '" class="btn btn-danger btn_remove" style="margin-left: 130px;margin-top: -65px;">-</button></div></div>');
            });
            $(document).on('click', '.btn_remove', function() {
                var button_id = $(this).attr("id");
                // console.log(button_id);
                $('#row' + button_id + '').remove();
            });

        });
    </script>

    <script>
        $(document).ready(function() {
            $('#print').on('click', function() {
                $.ajax({
                    url: '/printProforma/{{$proforma->id}}', // The route to your dedicated print view
                    method: 'GET',
                    success: function(response) {
                        var printWindow = window.open('url', '_parent');
                        var originalContents = $('body').html();
                        printWindow.document.write(response);
                        printWindow.print();
                        $('body').html(originalContents);
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching print content:", error);
                    }
                });
            });
        });
    </script>

    @endsection

</x-sales-dashboard>
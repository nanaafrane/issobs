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

            @if(Auth::user()->hasRole(['Invoice', 'Finance Manager' ]))
            <li class="menu-item">
                <a href="{{ url('invoice') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bxs-receipt bg-primary"></i>
                    <div class="text-truncate" data-i18n="Invoices">Invoices</div>
                </a>
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
            <div class="col-6 mb-8">
                <h3 class="card-header text-primary"> <i class="icon-base bx bx-bxs-receipt"></i> Receipt </h3>
            </div>
        </div>

        <div class="row ms-10">
            <div class="col-md-2 col-xl-2">
                <div class="card-body">
                    <div>
                        <button
                            class="btn btn-dark"
                            type="button"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvasDark"
                            aria-controls="offcanvasDark">
                            <i class="icon-base bx bxs-bullseye"> </i>
                            Client
                        </button>

                        <div
                            class="offcanvas offcanvas-end"
                            tabindex="-1"
                            id="offcanvasDark"
                            aria-labelledby="offcanvasDarkLabel"
                            data-bs-theme="dark">
                            <div class="offcanvas-header">
                                <h5 class="offcanvas-title text-info" id="offcanvasDarkLabel">Client Details</h5>
                                <button
                                    type="button"
                                    class="btn-close"
                                    data-bs-dismiss="offcanvas"
                                    aria-label="Close"></button>
                            </div>
                            <div class="offcanvas-body my-auto mx-0 flex-grow-0">

                                <h5 class="card-title text-info"> Client Details <span class="badge bg-label-dark"></span> </h5>
                                <hr>
                                <p class="card-text text-info">Client Name : <strong> {{$receipt->client->name}}</strong> </p>
                                <p class="card-text text-info">Phone Number : <strong>{{$receipt->client->phone_number}}</strong></p>
                                <p class="card-text text-info">Business Name : <strong> {{$receipt->client->business_name}} </strong> </p>
                                <p class="card-text text-info">Address : <strong> {{$receipt->client->address}} </strong></p>
                                <p class="card-text text-info">Location : <strong> {{$receipt->client->field->name}} </strong> </p>

                                <button
                                    type="button"
                                    class="btn btn-outline-danger d-grid w-100"
                                    data-bs-dismiss="offcanvas">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5 col-xl-5">
                <div class="card shadow-none bg-transparent border border-secondary text-secondary">
                    <div class="card-body">
                        <h4 class="card-title"> Receipt Details <span class="badge bg-dark"> {{$receipt->status}} </span></h4>
                        <hr>
                        <p class="card-text text-dark"><strong>Receipt #. : FWSSR{{$receipt->id}} </strong> </p>
                        <p class="card-text text-dark"> <strong>From : {{$receipt->from}} </strong> </p>
                        <p class="card-text text-dark"> <strong>Receipt Date : {{$receipt->receipt_month?->format('l F d, Y')}} </strong> <i class="icon-base bx bx-bxs-time"></i>  {{$receipt->receipt_month?->diffForHumans()}}</p>
                       
                        <p class="card-text text-dark"> <strong>Date System Generated : {{$receipt->created_at->format('l F d, Y, H:i A')}} </strong> </p>
                        <p class="card-text text-dark"> <strong><i class="icon-base bx bx-bxs-time"></i> {{$receipt->created_at?->diffForHumans()}} </strong> </p>
                        <hr>
                        <p class="card-text text-dark"> <strong>WITHHOLDING TAX {{$wht->wht_rate * 100}}% : GH&#8373; {{number_format($receipt->wht_amount, 2)}} </strong> </p>
                        <p class="card-text text-dark"> <strong>AMOUNT AFTER WTH : GH&#8373; {{number_format($receipt->amount_received, 2)}} </strong> </p>
                        <p class="card-text text-dark"> <strong>7% VAT AMOUNT : GH&#8373; {{number_format($receipt->vat7_value, 2)}} </strong> </p>
                        <p class="card-text text-dark"> <strong>AMOUNT AFTER 7% VAT : GH&#8373; {{number_format($receipt->vat7_amount, 2)}} </strong> </p>
                        <hr>
                        <p class="card-text text-dark"> <strong>OTHER DEDUCTIONS  : GH&#8373; {{number_format($receipt->dAmount, 2)}} </strong> </p>
                        <p class="card-text text-dark"> <strong>DESCRIPTION  : {{$receipt->description}} </strong> </p>
                        
                        <h4 class="card-text text-dark"> <strong>Receipt Total : GH&#8373; {{number_format($receipt->total + $receipt->dAmount, 2) }} </strong> </h4>
                    </div>
                </div>
            </div>

            <div class="col-md-5 col-xl-5">
                <div class="card text-bg-dark">
                    <div class="card-body">
                        <h5 class="card-title text-white"> Invoice Details <span class="badge bg-label-dark"> {{$receipt->invoice->status}} </span></h5>
                        <hr>
                        <p class="card-text">Invoice # : FWSSi{{$receipt->invoice_id}} </p>
                        <p class="card-text">Invoice Amount : GH&#8373; {{ number_format($receipt->invoice->total, 2) }} </p>
                        <!-- <p class="card-text">Initial Payment : GH&#8373; {{number_format($receipt->total, 2) }} </p> -->
                        <p class="card-text text-info">Invoice Balance : <strong> GH&#8373; {{ number_format($receipt->invoice->balance, 2) }} </strong> </p>
                        <p class="card-text">Invoice Date Created : {{$receipt->invoice->created_at->format('l F d, Y, H:i A')}} </p>
                        <p class="card-text">Invoice Due Date : {{$receipt->invoice->due_date->format('l F d, Y, H:i A')}} </p>

                        @if($receipt->invoice->status == 'completed')
                        <div class="divider divider-dashed">
                            <div class="divider-text"></div>
                        </div>
                        <!-- <p class="card-text text-danger">Total Amount Receiced : <strong> GH&#8373; {{number_format($receipt->invoice->amount_received, 2) }} </strong> </p>
                        <p class="card-text text-danger">Total With Holdings : <strong> GH&#8373; {{number_format($receipt->invoice->wht_amount, 2) }} </strong> </p> -->
                        @endif
                    </div>
                </div>

                                @if($receipt->cash_amount > 0.00 )
                <br>
                <div class="card text-bg-success">
                    <div class="card-body">
                        <h5 class="card-title text-white"> CASH </h5>
                        <hr>
                        <p class="card-text"><strong>Amount : GH&#8373; {{ number_format($receipt->cash_amount, 2) }} </strong> </p>
                    </div>
                </div>
                @endif
                <br>

                @if($receipt->momo_amount > 0.00)
                <div class="card text-bg-warning">
                    <div class="card-body">
                        <h5 class="card-title text-white"> MOMO </h5>
                        <hr>
                        <p class="card-text"><strong>Transaction ID : {{$receipt->momo_transactin_id}} </strong> </p>
                        <p class="card-text"><strong>Amount : GH&#8373; {{number_format($receipt->momo_amount, 2) }} </strong> </p>
                    </div>
                </div>
                <br>
                @endif

                
                @if($receipt->transfer_amount > 0.00)
                <div class="card text-bg-secondary">
                    <div class="card-body">
                        <h5 class="card-title text-white"> TRANSFER </h5>
                        <hr>
                        <p class="card-text"><strong>Reference : {{$receipt->transfer_reference}} </strong> </p>
                        <p class="card-text"><strong>Bank : {{$receipt->transfer_bank}} </strong> </p>
                        <p class="card-text"><strong>Amount : GH&#8373; {{ number_format($receipt->transfer_amount, 2) }} </strong> </p>
                    </div>
                </div>
                <br>
                @endif
                
                
                @if($receipt->cheque_amount > 0.00)
                <div class="card text-bg-info">
                    <div class="card-body">
                        <h5 class="card-title text-white">CHEQUE</h5>
                        <p class="card-text"><strong>Reference : {{$receipt->cheque_reference}} </strong> </p>
                        <p class="card-text"><strong>Bank : {{$receipt->cheque_bank}} </strong> </p>
                        <p class="card-text"><strong>Amount : GH&#8373; {{ number_format($receipt->cheque_amount, 2) }} </strong> </p>
                    </div>
                    @if($receipt->image)
                    <img class="card-img-top" src="@if($receipt->image) {{asset('storage/'.$receipt->image)}}  @endif" alt="Card image cap" />
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>

    @endsection





</x-sales-dashboard>
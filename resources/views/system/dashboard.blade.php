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
      <li class="menu-item active open">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-home-smile"></i>
          <div class="text-truncate" data-i18n="Dashboards">Dashboard</div>
        </a>
        <ul class="menu-sub">
          <li class="menu-item active">
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
      <li class="menu-item">
        <a href="{{ url('invoice') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-bxs-receipt"></i>
          <div class="text-truncate" data-i18n="Invoices">Invoices</div>
        </a>
      </li>
      <li class="menu-item">
        <a href="{{url('receipt')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
          <div class="text-truncate" data-i18n="Receipts">Receipts</div>
        </a>
      </li>
      <!-- Components -->
      <li class="menu-header small text-uppercase"><span class="menu-header-text">Management</span></li>
      <li class="menu-item">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-bxs-group"></i>
          <div class="text-truncate" data-i18n="Staffs">Staffs</div>
        </a>
        <ul class="menu-sub">
          <li class="menu-item">
            <a href="" class="menu-link" target="_blank">
              <div class="text-truncate" data-i18n="SRegister">Register</div>
            </a>
          </li>
          <li class="menu-item">
            <a href="" class="menu-link" target="_blank">
              <div class="text-truncate" data-i18n="SList">List</div>
            </a>
          </li>
        </ul>
      </li>
      <li class="menu-item">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons bx bx-bxs-user-detail"></i>
          <div class="text-truncate" data-i18n="Clients">Clients</div>
        </a>
        <ul class="menu-sub">
          <li class="menu-item">
            <a href="{{url('client/create')}}" class="menu-link">
              <div class="text-truncate" data-i18n="CRegister">Register</div>
            </a>
          </li>
          <li class="menu-item">
            <a href="{{url('client')}}" class="menu-link">
              <div class="text-truncate" data-i18n="CList">List</div>
            </a>
          </li>
        </ul>
      </li>

      <li class="menu-item">
        <a href="{{url('field')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-bxs-location-plus"></i>
          <div class="text-truncate" data-i18n="Locations">Locations</div>
        </a>
      </li>
    </ul>
  </aside>
  <!-- / Menu -->
  @endsection


  @section('content')

  <!-- Content -->
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
      <div class="col-xxl-8 mb-6 order-0">

        <div class="row">
          <div class="col-lg-3">
            <div style="background: crimson; color: white;" class="card h-100">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img
                      src="img/icons/unicons/paypal.png"
                      alt="chart success"
                      class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt3"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                      <a class="dropdown-item" href=" {{url('invoiceDashboard')}} ">View</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1">Invoice</p>
                <h4 class="card-title mb-3 text-white"><strong>&#x20B5;{{number_format($sum_invoices, 2) }} </strong> </h4>
                <small class="fw-medium"> ALL INVOICES </small>
              </div>
            </div>
          </div>

          <div class="col-lg-3">
            <div style="background: #ffe0e0ff;" class="card h-100 text-danger">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img
                      src="img/icons/unicons/paypal.png"
                      class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt3"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                      <a class="dropdown-item" href="{{url('invoiceOutstanding')}}">View</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1">Invoice</p>
                <h4 class="card-title mb-3">&#x20B5;{{ number_format($invoices_outstanding, 2)}}</h4>
                <small class="fw-medium"> OUTSTANDING </small>
              </div>
            </div>
          </div>

          <div class="col-lg-3">
            <div style="background: #e7e7e7ff;" class="card h-100">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img
                      src="img/icons/unicons/paypal.png"
                      class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt3"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                      <a class="dropdown-item" href="{{url('receiptWHTPayment')}}">View</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1">PAYMENTS WITH </p>
                <h4 class="card-title mb-3">&#x20B5;{{ number_format($total_after_wht, 2)}}</h4>
                <small class="fw-medium"> WITH HOLDINGS </small>
              </div>
            </div>
          </div>


          <div class="col-lg-3">
            <div style="background: #e7e7e7ff;" class="card h-100">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img
                      src="img/icons/unicons/paypal.png"
                      class="rounded" />
                  </div>
                  <!-- <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt3"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                      <a class="dropdown-item" href="#">View More</a>
                    </div>
                  </div> -->
                </div>
                <p class="mb-1">WITH HOLDINGS TAX</p>
                <h4 class="card-title mb-3">&#x20B5;{{ number_format($total_wth, 2)}}</h4>
                <small class="fw-medium"> TOTAL </small>
              </div>
            </div>
          </div>

        </div>


      </div>
      <div class="col-xxl-4 col-lg-12 col-md-4 order-1">
        <div class="row">
          <div class="col-lg-6 col-md-12 col-6 mb-6">
            <div class="card h-100">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img
                      src="img/icons/unicons/wallet-info.png"
                      class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt3"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                      <a class="dropdown-item" href="{{url('partPaymentOutstanding')}}">View</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1 text-info">Part Payment</p>
                <h4 class="card-title mb-3"> &#x20B5;{{number_format($balance_outstanding, 2)}}</h4>
                <small class="text-info fw-medium"> OUTSTANDING </small>
              </div>
            </div>
          </div>
          <div class="col-lg-6 col-md-12 col-6 mb-6">
            <div style="background: #152356; color: white;" class="card h-100">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img
                      src="img/icons/unicons/wallet-info.png"
                      alt="wallet info"
                      class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt6"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt6">
                      <a class="dropdown-item" href="{{url('receiptAllpayment')}}">View</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1">Payments</p>
                <h4 style="color:white" class="card-title mb-3"> &#x20B5;{{ number_format($receipt_AllPayment, 2)}} </h4>
                <small class="text-info fw-medium"> ALL PAYMENTS </small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Total Revenue -->
      <div class="col-12 col-xxl-8 order-2 order-md-3 order-xxl-2 mb-6 total-revenue">
        <div class="card">
          <div class="row row-bordered g-0">
            <div class="col-lg-12">
              <div class="card-header d-flex align-items-center justify-content-between">
                <div class="card-title mb-0">
                  <h5 class="m-0 me-2">Invoices per year </h5>
                </div>
                <div class="dropdown">
                  <button
                    class="btn p-0"
                    type="button"
                    id="totalRevenue"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false">
                    <i class="icon-base bx bx-dots-vertical-rounded icon-lg text-body-secondary"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end" aria-labelledby="totalRevenue">
                    <a class="dropdown-item" href="javascript:void(0);">Select All</a>
                    <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
                    <a class="dropdown-item" href="javascript:void(0);">Share</a>
                  </div>
                </div>
              </div>
              <div id="totalRevenueChart" class="px-3"></div>
            </div>

          </div>
        </div>
      </div>
      <!--/ Total Revenue -->

      <div class="col-12 col-md-8 col-lg-12 col-xxl-4 order-3 order-md-2 profile-report">
        <div class="row">

          <div class="col-6 mb-6 payments">
            <div style="background: #cbfcffff;" class="card h-100">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img src="img/icons/unicons/wallet-info.png" alt="paypal" class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt4"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt4">
                      <a class="dropdown-item" href="{{url('receiptTransferPayment')}}">View</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1">Payments</p>
                <h4 class="card-title mb-3"> {{number_format($receiept_TransferAmount, 2) }} </h4>
                <small class="text-default fw-medium">BANK TRANSFERS</small>
              </div>
            </div>
          </div>

          <div class="col-6 mb-6 transactions">
            <div style="background: #cbfcffff;" class="card h-100">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img src="img/icons/unicons/wallet-info.png" alt="Credit Card" class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt1"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu" aria-labelledby="cardOpt1">
                      <a class="dropdown-item" href="{{url('receiptCashPayment')}}">View</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1">Payments</p>
                <h4 class="card-title mb-3">&#x20B5;{{number_format($receiept_CashAmount, 2)}}</h4>
                <small class="text-default fw-medium">CASH PAYMENTS</small>
              </div>
            </div>
          </div>

          <div class="col-6 mb-6 payments">
            <div style="background: #cbfcffff;" class="card h-100">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img src="img/icons/unicons/wallet-info.png" alt="paypal" class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button class="btn p-0" type="button" id="cardOpt4" data-bs-toggle="dropdown" aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt4">
                      <a class="dropdown-item" href="{{url('receiptChequePayment')}}">View</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1">Payments</p>
                <h4 class="card-title mb-3">&#x20B5;{{number_format($receiept_BankAmount, 2)}}</h4>
                <small class="text-default fw-medium">CHEQUE PAYMENTS</small>
              </div>
            </div>
          </div>

          <div class="col-6 mb-6 transactions">
            <div style="background: #cbfcffff;" class="card h-100">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img src="img/icons/unicons/wallet-info.png" alt="Credit Card" class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button class="btn p-0" type="button" id="cardOpt1" data-bs-toggle="dropdown" aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu" aria-labelledby="cardOpt1">
                      <a class="dropdown-item" href="{{url('receiptMoMoPayment')}}">View</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1">Payments</p>
                <h4 class="card-title mb-3">&#x20B5;{{number_format($receiept_MoMoAmount, 2)}}</h4>
                <small class="text-default fw-medium">MOMO PAYMENTS</small>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
    <div class="row">

      <!-- Transactions -->
      <div class="col-md-4 col-lg-4 order-0 mb-6">
        <div style="background: #000000ff; " class="card h-100 text-white">
          <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2 text-white">Banks</h5>
          </div>
          <hr style="color: white;" />
          <div class="card-body pt-4">
            <ul class="p-0 m-0">
              <li class="d-flex align-items-center mb-6">
                <div class="avatar flex-shrink-0 me-3">
                  <img src="img/icons/unicons/paypal.png" alt="User" class="rounded" />
                </div>
                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                  <div class="me-2">
                    <small class="d-block">Branch</small>
                    <h6 class="fw-normal mb-0 text-white">GCB</h6>
                  </div>
                  <div class="user-progress d-flex align-items-center gap-2">
                    <span class="text-body-secondary">GH&#x20B5;</span>
                    <h6 class="fw-normal mb-0 text-white">500,025.00</h6>

                  </div>
                </div>
              </li>
              <li class="d-flex align-items-center mb-6">
                <div class="avatar flex-shrink-0 me-3">
                  <img src="img/icons/unicons/wallet.png" alt="User" class="rounded" />
                </div>
                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                  <div class="me-2">
                    <small class="d-block">Branch</small>
                    <h6 class="fw-normal mb-0 text-white">ECOBANK</h6>
                  </div>
                  <div class="user-progress d-flex align-items-center gap-2">
                    <span class="text-body-secondary">GH&#x20B5;</span>
                    <h6 class="fw-normal mb-0 text-white">648,354.00</h6>

                  </div>
                </div>
              </li>
              <li class="d-flex align-items-center mb-6">
                <div class="avatar flex-shrink-0 me-3">
                  <img src="img/icons/unicons/chart.png" alt="User" class="rounded" />
                </div>
                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                  <div class="me-2">
                    <small class="d-block">Branch</small>
                    <h6 class="fw-normal mb-0 text-white">ZENITH</h6>
                  </div>
                  <div class="user-progress d-flex align-items-center gap-2">
                    <span class="text-body-secondary">GH&#x20B5;</span>
                    <h6 class="fw-normal mb-0 text-white">205,000.00</h6>

                  </div>
                </div>
              </li>
              <li class="d-flex align-items-center mb-6">
                <div class="avatar flex-shrink-0 me-3">
                  <img src="img/icons/unicons/cc-primary.png" alt="User" class="rounded" />
                </div>
                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                  <div class="me-2">
                    <small class="d-block"> Branch </small>
                    <h6 class="fw-normal mb-0 text-white">ACCESS</h6>
                  </div>
                  <div class="user-progress d-flex align-items-center gap-2">
                    <span class="text-body-secondary">GH&#x20B5;</span>
                    <h6 class="fw-normal mb-0 text-white">838,486.701</h6>

                  </div>
                </div>
              </li>
              <li class="d-flex align-items-center mb-6">
                <div class="avatar flex-shrink-0 me-3">
                  <img src="img/icons/unicons/wallet.png" alt="User" class="rounded" />
                </div>
                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                  <div class="me-2">
                    <small class="d-block">Branch</small>
                    <h6 class="fw-normal mb-0 text-white">SGSSB</h6>
                  </div>
                  <div class="user-progress d-flex align-items-center gap-2">
                    <span class="text-body-secondary">GH&#x20B5;</span>
                    <h6 class="fw-normal mb-0 text-white">203,987.033</h6>

                  </div>
                </div>
              </li>

            </ul>
          </div>
        </div>
      </div>
      <!--/ Transactions -->


      <!-- Expense Overview -->
      <div class="col-md-8 col-lg-8 order-1 mb-6">
        <div class="card h-100">
          <div class="card-header nav-align-top">
            <ul class="nav nav-pills flex-wrap row-gap-2" role="tablist">
              <li class="nav-item">
                <button
                  type="button"
                  class="nav-link active"
                  role="tab"
                  data-bs-toggle="tab"
                  data-bs-target="#navs-tabs-line-card-income"
                  aria-controls="navs-tabs-line-card-income"
                  aria-selected="true">
                  Payments
                </button>
              </li>
              <li class="nav-item">
                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                  data-bs-target="#navs-tabs-line-card-invoices"
                  aria-controls="navs-tabs-line-card-invoices">Invoices</button>
              </li>
            </ul>
          </div>
          <div class="card-body">
            <div class="tab-content p-0">
              <hr />
              <div class="tab-pane fade show active" id="navs-tabs-line-card-income" role="tabpanel">
                <div class="d-flex mb-6">
                  <div class="card-body p-4">
                    <h5 class="card-title widget-card-title mb-4">Monthly Transactions</h5>
                    <div class="table-responsive">
                      <table class="table table-borderless bsb-table-xl text-nowrap align-middle m-0">
                        <thead>
                          <tr>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          @foreach($current_5_receipts as $receipt)
                          <tr>
                            <td>
                              <h6 class="mb-1">#FWSSR{{$receipt->id}}</h6>
                              <span class="text-secondary fs-7">{{$receipt->client->business_name}}</span>
                            </td>
                            <td>
                              <h6 class="mb-1">{{$receipt->client->name}}</h6>
                              <span class="text-secondary fs-7">{{$receipt->client->field->name}}</span>
                            </td>
                            <td>
                              <h6 class="mb-1">{{$receipt->created_at->diffForHumans()}}</h6>
                              <span class="text-secondary fs-7">{{$receipt->invoice->due_date->diffForHumans()}}</span>
                            </td>
                            <td>GH&#x20B5;{{number_format($receipt->total, 2)}}</td>

                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

                          </tr>
                          @endforeach
                        </tbody>
                      </table>
                    </div>
                  </div>

                </div>
              </div>

              <div class="tab-pane fade" id="navs-tabs-line-card-invoices" role="tabpane2">
                <div class="d-flex mb-6">

                  <div class="card-body p-4">
                    <h5 class="card-title widget-card-title mb-4">Monthly Transactions</h5>
                    <div class="table-responsive">
                      <table class="table table-borderless bsb-table-xl text-nowrap align-middle m-0">
                        <thead>
                          <tr>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          @foreach($current_5_invoices as $current)
                          <tr>
                            <td>
                              <h6 class="mb-1">#FWSSI{{$current->id}}</h6>
                              <span class="text-secondary fs-7">{{$current->client->business_name}}</span>
                            </td>
                            <td>
                              <h6 class="mb-1">{{$current->client->name}}</h6>
                              <span class="text-secondary fs-7">{{$current->client->field->name}}</span>
                            </td>
                            <td>
                              <h6 class="mb-1">{{$current->updated_at->diffForHumans()}}</h6>
                              <span class="text-secondary fs-7">Due {{$current->due_date->diffForHumans()}}</span>
                            </td>
                            <td>GH&#x20B5;{{number_format($current->total, 2)}}</td>

                            <td>
                              @if($current->status == 'completed')
                              <span class="badge bg-success text-white btn-block">{{$current->status}}</span>
                              @else
                              <span class="badge bg-danger text-white btn-block">{{$current->status}}</span>
                              @endif
                            </td>

                          </tr>
                          @endforeach

                        </tbody>
                      </table>
                    </div>
                  </div>

                </div>
              </div>


            </div>
          </div>
        </div>
      </div>
      <!--/ Expense Overview -->
    </div>
  </div>
  <!-- / Content -->

  @endsection
</x-sales-dashboard>
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
          <div class="text-truncate" data-i18n="Dashboards"><strong>Dashboard</strong></div>
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
        <span class="menu-header-text text-primary">Transactions</span>
      </li>
      <!-- Pages -->
      <li class="menu-item">
        <a href="{{url('transaction')}}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-transfer-alt bg-primary"></i>
          <div class="text-truncate" data-i18n="Transaction">Transactions</div>
        </a>
      </li>

      @if(Auth::user()->hasRole('Invoice') || Auth::user()->hasRole('Finance Manager'))
      <li class="menu-item">
        <a href="{{ url('invoice') }}" class="menu-link">
          <i class="menu-icon tf-icons bx bx-bxs-receipt bg-primary"></i>
          <div class="text-truncate" data-i18n="Invoices">Invoices</div>
        </a>
      </li>
      @endif


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

      @if(Auth::user()->hasRole('Manager') || Auth::user()->hasRole('Officer') || Auth::user()->hasRole('Finance Manager') )

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
            <a href="" class="menu-link">
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

      @endif

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
                <h4 class="card-title mb-3 text-white"><strong>&#x20B5;
                    @if(Auth::user()->hasRole('Invoice') || Auth::user()->hasRole('Finance Manager'))
                    {{number_format(array_sum( $sum_invoices),2)}}
                    @elseif(Auth::user()->field?->name == 'Accra')
                    {{number_format($sum_invoices[0], 2)}}
                    @endif

                    @if(Auth::user()->field?->name == 'Botwe')
                    {{number_format($sum_invoices[1], 2)}}
                    @endif


                    @if(Auth::user()->field?->name == 'Tema')
                    {{number_format($sum_invoices[2], 2)}}
                    @endif

                    @if(Auth::user()->field?->name == 'Takoradi')
                    {{number_format($sum_invoices[3], 2)}}
                    @endif

                    @if(Auth::user()->field?->name == 'Koforidua')
                    {{number_format($sum_invoices[4], 2)}}
                    @endif

                    @if(Auth::user()->field?->name == 'Kumasi')
                    {{number_format($sum_invoices[5], 2)}}
                    @endif
                  </strong>
                </h4>
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
                <h4 class="card-title mb-3">&#x20B5;
                  @if(Auth::user()->hasRole('Invoice') || Auth::user()->hasRole('Finance Manager'))
                  {{number_format(array_sum( $invoices_outstanding),2)}}
                  @elseif(Auth::user()->field?->name == 'Accra')
                  {{number_format($invoices_outstanding[0], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Botwe')
                  {{number_format($invoices_outstanding[1], 2)}}
                  @endif


                  @if(Auth::user()->field?->name == 'Tema')
                  {{number_format($invoices_outstanding[2], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Takoradi')
                  {{number_format($invoices_outstanding[3], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Koforidua')
                  {{number_format($invoices_outstanding[4], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Kumasi')
                  {{number_format($invoices_outstanding[5], 2)}}
                  @endif
                </h4>
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
                <h4 class="card-title mb-3">&#x20B5;
                  @if(Auth::user()->hasRole('Invoice') || Auth::user()->hasRole('Finance Manager'))
                  {{number_format(array_sum( $total_after_wht),2)}}
                  @elseif(Auth::user()->field?->name == 'Accra')
                  {{number_format($total_after_wht[0], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Botwe')
                  {{ number_format($total_after_wht[1], 2)}}
                  @endif


                  @if(Auth::user()->field?->name == 'Tema')
                  {{number_format($total_after_wht[2], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Takoradi')
                  {{number_format($total_after_wht[3], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Koforidua')
                  {{number_format($total_after_wht[4], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Kumasi')
                  {{number_format($total_after_wht[5], 2)}}
                  @endif
                </h4>
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
                </div>
                <p class="mb-1">WITH HOLDINGS TAX</p>
                <h4 class="card-title mb-3">&#x20B5;

                  @if(Auth::user()->hasRole('Invoice') || Auth::user()->hasRole('Finance Manager'))
                  {{number_format(array_sum( $total_wth),2)}}
                  @elseif(Auth::user()->field?->name == 'Accra')
                  {{number_format($total_wth[0], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Botwe')
                  {{ number_format($total_wth[1], 2)}}
                  @endif


                  @if(Auth::user()->field?->name == 'Tema')
                  {{number_format($total_wth[2], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Takoradi')
                  {{number_format($total_wth[3], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Koforidua')
                  {{number_format($total_wth[4], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Kumasi')
                  {{number_format($total_wth[5], 2)}}
                  @endif
                </h4>
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
                <h4 class="card-title mb-3"> &#x20B5;

                  @if(Auth::user()->hasRole('Invoice') || Auth::user()->hasRole('Finance Manager'))
                  {{number_format(array_sum($balance_outstanding), 2)}}
                  @elseif(Auth::user()->field?->name == 'Accra')
                  {{number_format($balance_outstanding[0], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Botwe')
                  {{number_format($balance_outstanding[1], 2)}}
                  @endif


                  @if(Auth::user()->field?->name == 'Tema')
                  {{number_format($balance_outstanding[2], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Takoradi')
                  {{number_format($balance_outstanding[3], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Koforidua')
                  {{number_format($balance_outstanding[4], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Kumasi')
                  {{number_format($balance_outstanding[5], 2)}}
                  @endif


                </h4>
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
                <h4 style="color:white" class="card-title mb-3"> &#x20B5;
                  @if(Auth::user()->hasRole('Invoice') || Auth::user()->hasRole('Finance Manager'))
                  {{number_format(array_sum($receipt_AllPayment), 2)}}
                  @elseif(Auth::user()->field?->name == 'Accra')
                  {{number_format($receipt_AllPayment[0], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Botwe')
                  {{number_format($receipt_AllPayment[1], 2)}}
                  @endif


                  @if(Auth::user()->field?->name == 'Tema')
                  {{number_format($receipt_AllPayment[2], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Takoradi')
                  {{number_format($receipt_AllPayment[3], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Koforidua')
                  {{number_format($receipt_AllPayment[4], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Kumasi')
                  {{number_format($receipt_AllPayment[5], 2)}}
                  @endif
                </h4>
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
                <h4 class="card-title mb-3">&#x20B5;

                  @if(Auth::user()->hasRole('Invoice') || Auth::user()->hasRole('Finance Manager'))
                  {{number_format(array_sum($receiept_TransferAmount), 2)}}
                  @elseif(Auth::user()->field?->name == 'Accra')
                  {{number_format($receiept_TransferAmount[0], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Botwe')
                  {{number_format($receiept_TransferAmount[1], 2)}}
                  @endif


                  @if(Auth::user()->field?->name == 'Tema')
                  {{number_format($receiept_TransferAmount[2], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Takoradi')
                  {{number_format($receiept_TransferAmount[3], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Koforidua')
                  {{number_format($receiept_TransferAmount[4], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Kumasi')
                  {{number_format($receiept_TransferAmount[5], 2)}}
                  @endif

                </h4>
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
                <h4 class="card-title mb-3">&#x20B5;

                  @if(Auth::user()->hasRole('Invoice') || Auth::user()->hasRole('Finance Manager'))
                  {{number_format(array_sum($receiept_CashAmount), 2)}}
                  @elseif(Auth::user()->field?->name == 'Accra')
                  {{number_format($receiept_CashAmount[0], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Botwe')
                  {{number_format($receiept_CashAmount[1], 2)}}
                  @endif


                  @if(Auth::user()->field?->name == 'Tema')
                  {{number_format($receiept_CashAmount[2], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Takoradi')
                  {{number_format($receiept_CashAmount[3], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Koforidua')
                  {{number_format($receiept_CashAmount[4], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Kumasi')
                  {{number_format($receiept_CashAmount[5], 2)}}
                  @endif

                </h4>
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
                <h4 class="card-title mb-3">&#x20B5;

                  @if(Auth::user()->hasRole('Invoice') || Auth::user()->hasRole('Finance Manager'))
                  {{number_format(array_sum($receiept_BankAmount), 2)}}
                  @elseif(Auth::user()->field?->name == 'Accra')
                  {{number_format($receiept_BankAmount[0], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Botwe')
                  {{number_format($receiept_BankAmount[1], 2)}}
                  @endif


                  @if(Auth::user()->field?->name == 'Tema')
                  {{number_format($receiept_BankAmount[2], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Takoradi')
                  {{number_format($receiept_BankAmount[3], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Koforidua')
                  {{number_format($receiept_BankAmount[4], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Kumasi')
                  {{number_format($receiept_BankAmount[5], 2)}}
                  @endif
                </h4>
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
                <h4 class="card-title mb-3">&#x20B5;

                  @if(Auth::user()->hasRole('Invoice') || Auth::user()->hasRole('Finance Manager'))
                  {{number_format(array_sum($receiept_MoMoAmount), 2)}}
                  @elseif(Auth::user()->field?->name == 'Accra')
                  {{number_format($receiept_MoMoAmount[0], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Botwe')
                  {{number_format($receiept_MoMoAmount[1], 2)}}
                  @endif


                  @if(Auth::user()->field?->name == 'Tema')
                  {{number_format($receiept_MoMoAmount[2], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Takoradi')
                  {{number_format($receiept_MoMoAmount[3], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Koforidua')
                  {{number_format($receiept_MoMoAmount[4], 2)}}
                  @endif

                  @if(Auth::user()->field?->name == 'Kumasi')
                  {{number_format($receiept_MoMoAmount[5], 2)}}
                  @endif


                </h4>
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
                    <h6 class="fw-normal mb-0 text-white">SGB</h6>
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



    </div>
  </div>
  <!-- / Content -->

  @endsection
</x-sales-dashboard>
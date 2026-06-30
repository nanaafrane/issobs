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

      @if(Auth::user()->hasPermission('Accounts') || Auth::user()->hasRole(['Director']) )
      <li class="menu-header small text-uppercase"><span class="menu-header-text">PAYROLL</span></li>
        <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-money-withdraw"></i>
                <div class="text-truncate" data-i18n="Payroll">Payroll</div>
                </a>
                <ul class="menu-sub">
                @if(Auth::user()->hasRole(['Invoice', 'Director', 'Finance Manager']))
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
                    <a href="{{ url('salariesBulkCash') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bxs-group"></i>
                    <div class="text-truncate" data-i18n="Transaction">Bulk Cash Salaries</div>
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

    @section('css')
    <style>
        .receipt-show-page .card {
            border-radius: 1rem;
        }

        .receipt-show-page .card-body {
            padding: 1.4rem;
        }

        .receipt-show-page .receipt-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
        }

        .receipt-show-page .receipt-summary-label {
            color: #6c757d;
            font-size: 0.95rem;
        }

        .receipt-show-page .receipt-summary-value {
            font-size: 1rem;
        }

        .receipt-show-page .receipt-status-badge {
            min-width: 90px;
        }

        .receipt-show-page .table-sm td {
            padding: 0.7rem 0.75rem;
        }

        .receipt-show-page .card.bg-light {
            border: 1px solid #e9ecef;
        }

        .receipt-show-page .btn-outline-secondary {
            min-width: 120px;
        }

        .receipt-show-page .offcanvas-body {
            padding-top: 0;
        }

        /* Credit/Master card style for cash amount */
        .receipt-show-page .cash-card {
            background: linear-gradient(135deg,#1e3c72 0%,#2a5298 100%);
            border: none;
            color: #fff;
            overflow: hidden;
            position: relative;
            box-shadow: 0 6px 18px rgba(28, 51, 84, 0.25);
        }

        .receipt-show-page .cash-card .amount-display {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: 0.6px;
        }

        .receipt-show-page .cash-card::after {
            content: '';
            position: absolute;
            right: -40px;
            top: -40px;
            width: 120px;
            height: 120px;
            background: rgba(255,255,255,0.06);
            transform: rotate(45deg);
            border-radius: 8px;
        }

        /* Generic payment card base */
        .receipt-show-page .payment-card {
            border: none;
            border-radius: 0.75rem;
            overflow: hidden;
            position: relative;
            color: #fff;
            box-shadow: 0 6px 18px rgba(0,0,0,0.12);
        }

        .receipt-show-page .payment-card .amount-display {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        .receipt-show-page .payment-card.momo {
            background: linear-gradient(135deg,#ffd89b 0%,#19547b 100%);
            color: #1f2937;
        }

        .receipt-show-page .payment-card.transfer {
            background: linear-gradient(135deg,#4b5563 0%,#111827 100%);
        }

        .receipt-show-page .payment-card.cheque {
            background: linear-gradient(135deg,#4dd0e1 0%,#006064 100%);
        }

        .receipt-show-page .payment-card.other {
            background: linear-gradient(135deg,#6a11cb 0%,#2575fc 100%);
        }

        @media (max-width: 991px) {
            .receipt-show-page .receipt-header-actions {
                flex-direction: column;
                gap: 0.75rem;
                align-items: stretch;
            }
        }

        @media print {
            .receipt-show-page .btn,
            .receipt-show-page .offcanvas,
            .receipt-show-page .layout-navbar,
            .receipt-show-page footer,
            .receipt-show-page .card-header,
            .receipt-show-page .btn-outline-secondary {
                display: none !important;
            }
            .receipt-show-page .container-xxl {
                padding-top: 0 !important;
            }
        }
    </style>
    @endsection

    @section('content')
        <div class="container-xxl flex-grow-1 container-p-y receipt-show-page">

            <div class="d-flex align-items-center justify-content-between mb-3 receipt-header">
                <div class="d-flex align-items-center">
                    <img src="{{asset('img/icons/brands/issobs.png')}}" alt="ISSOBS" width="48" class="me-3">
                    <div>
                        <h3 class="mb-0">Receipt # : FWSSR{{$receipt->id}} </h3>
                        <div class="text-muted small">Generated {{$receipt->created_at->format('F d, Y')}}</div>
                    </div>
                </div>
                @if(Auth::user()->hasRole(['Finance Manager']))
                <div class="d-flex gap-2 receipt-header-actions">
                    <a href="/receipt/{{$receipt->id}}/edit" class="btn btn-danger"><i class="bx bx-edit-alt me-1"></i> Edit</a>
                    <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bx bx-printer me-1"></i> Print</button>
                </div>
                @endif
            </div>

            <div class="card-header  ml-2  d-none d-lg-block">
                @include('flash-messages')
            </div>

            <div class="row g-3 mt-2">
                <div class="col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-2">Client</h5>
                            <p class="mb-1"><strong>{{$receipt->client->name}}</strong></p>
                            <p class="text-muted mb-1 small">{{$receipt->client->business_name}}</p>
                            <p class="text-muted mb-1 small"> {{$receipt->client->phone_number}} | {{$receipt->client->phone_number1}} </p>
                            <p class="text-muted mb-1 small">{{$receipt->client->field->name}} </p>
                            <p class="text-muted mb-1 small"> {{$receipt->client->address}} </p>
                        </div>
                    </div>
                 
                    @if($receipt->cash_amount > 0)
                    <div class="card cash-card mt-3 text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">Payment</small>
                                </div>
                                <div class="text-end">
                                    <small class="small">CASH</small>
                                </div>
                            </div>

                            <div class="mt-3">
                                <div class="amount-display">GH&#8373; {{ number_format($receipt->cash_amount, 2) }}</div>
                                <div class="text-muted small">Amount received</div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($receipt->momo_amount > 0.00)
                    <div class="card payment-card momo mt-3 text-dark">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">Payment</small>
                                </div>
                                <div class="text-end">
                                    <small class="small">MOMO</small>
                                </div>
                            </div>

                            <div class="mt-3">
                                <div class="amount-display">GH&#8373; {{number_format($receipt->momo_amount, 2) }}</div>
                                <div class="text-muted small mt-1">Trans ID: {{$receipt->momo_transactin_id}}</div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($receipt->transfer_amount > 0.00)
                    <div class="card payment-card transfer mt-3 text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block text-white-50">Payment</small>
                                </div>
                                <div class="text-end">
                                    <small class="small text-white-50">BANK TRANSFER</small>
                                </div>
                            </div>

                            <div class="mt-3">
                                <div class="amount-display">GH&#8373; {{ number_format($receipt->transfer_amount, 2) }}</div>
                                <div class="text-white-50 small mt-1">Ref: {{$receipt->transfer_reference}} • {{$receipt->transfer_bank}}</div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($receipt->cheque_amount > 0.00)
                    <div class="card payment-card cheque mt-3 text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block text-white-50">Payment</small>
                                </div>
                                <div class="text-end">
                                    <small class="small text-white-50">CHEQUE</small>
                                </div>
                            </div>

                            <div class="mt-3">
                                <div class="amount-display">GH&#8373; {{ number_format($receipt->cheque_amount, 2) }}</div>
                                <div class="text-white-50 small mt-1">Ref: {{$receipt->cheque_reference}} • {{$receipt->cheque_bank}}</div>
                            </div>
                        </div>
                        @if($receipt->image)
                        <img class="card-img-top" src="@if($receipt->image) {{asset('storage/'.$receipt->image)}}  @endif" alt="Receipt image" />
                        @endif
                    </div>
                    @endif

                    @if($receipt->other_payment_amnt > 0.00 )
                    <div class="card payment-card other mt-3 text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block text-white-50">Payment</small>
                                </div>
                                <div class="text-end">
                                    <small class="small text-white-50">OTHER</small>
                                </div>
                            </div>

                            <div class="mt-3">
                                <div class="amount-display">GH&#8373; {{ number_format($receipt->other_payment_amnt, 2) }}</div>
                                <div class="text-white-50 small mt-1">{{ $receipt->other_payment_descri }}</div>
                            </div>
                        </div>
                    </div>
                    @endif

                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h5>Receipt details <span class="badge bg-info ms-2">{{$receipt->status}}</span></h5>
                            <hr>
                            <div class="row mb-2">
                                <div class="col-sm-6 text-muted">From</div>
                                <div class="col-sm-6"><strong>{{$receipt->from}}</strong></div>

                                <div class="col-sm-6 text-muted">Receipt Date</div>
                                <div class="col-sm-6"><strong>{{$receipt->receipt_month?->format('F d, Y')}}</strong></div>

                                <div class="col-sm-6 text-muted">Generated</div>
                                <div class="col-sm-6"><strong>{{$receipt->created_at->format('F d, Y, H:i A')}}</strong></div>

                                 <div class="col-sm-6 text-muted">Updated</div>
                                <div class="col-sm-6 text-muted"><strong>{{$receipt->updated_at->format('F d, Y, H:i A')}}</strong></div>
                            </div>

                            <hr>

                            <table class="table table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <td class="text-muted">Withholding ({{$wht->wht_rate * 100}}%)</td>
                                        <td class="text-end">GH&#8373; {{number_format($receipt->wht_amount,2)}}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">After WHT</td>
                                        <td class="text-end">GH&#8373; {{number_format($receipt->amount_received,2)}}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">7% VAT</td>
                                        <td class="text-end">GH&#8373; {{number_format($receipt->vat7_value,2)}}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">After VAT</td>
                                        <td class="text-end">GH&#8373; {{number_format($receipt->vat7_amount,2)}}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Other Deductions <br> Description </td>
                                        <td class="text-end">GH&#8373; {{number_format($receipt->dAmount,2)}} <br> {{ strtoupper($receipt->description) }} </td>

                                    </tr>
                                    <tr class="table-active">
                                        <td class="fw-bold">Total</td>
                                        <td class="fw-bold text-end">GH&#8373; {{number_format($receipt->total + $receipt->dAmount,2)}}</td>
                                    </tr>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="card bg-dark text-white h-100">
                        <div class="card-body">
                            <h6 class="text-white">Invoice details <span class="badge bg-danger ms-2">payment {{$receipt->invoice?->status }}</span> </h6>
                            <hr>
                            <p class="mb-1">#FWSSi{{$receipt->invoice_id}}</p>
                            <p class="mb-1">Month : {{$receipt->invoice_id}}</p>
                            <p class="mb-1">Amount : GH&#8373; {{ number_format($receipt->invoice->total,2) }}</p>
                            <p class="mb-1">Balance : <strong>GH&#8373; {{ number_format($receipt->invoice->balance,2) }}</strong></p>
                            <p class="small text-muted">Due: {{$receipt->invoice->due_date->format('F d, Y')}}</p>


                        </div>
                    </div>
                </div>

            </div>

        </div>

    @endsection





</x-sales-dashboard>
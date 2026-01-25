<x-sales-dashboard>


    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">
    <link href="https://cdn.datatables.net/columncontrol/1.1.1/css/columnControl.dataTables.min.css" rel="stylesheet">
    @endsection


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
            @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']))
            <li class="menu-item">
                <a href="{{ url('invoice') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bxs-receipt"></i>
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
            <div class="col-12">
                <h3 class="card-header text-primary"> <i class="icon-base bx bx-bxs-receipt"></i> Payment With Holdings @if (isset($month)) <strong> / For Month: {{  \Carbon\Carbon::parse($month)->format('F Y') }}</strong> @endif </h3>
            </div>
        </div><br>

        @if(isset($whtAmountReceiptAmountReceived) && isset($whtAmountReceiptWHTamount))
        <div class="row mb-4">
            <div class="col-lg-12 col-md-6 mb-4 mb-md-0">
                <div style="background:  #e7e7e7ff;" class="card h-100">
                    <div class="card-body">
                            <p class="mb-1"><strong> AMOUNT RECEIVED </strong> </p>
                            <h4 class="card-title mb-3"><strong> GH&#x20B5; {{ number_format($whtAmountReceiptAmountReceived, 2) }}  </strong> </h4>
                            <small class="fw-medium"> <strong> WHT 7.5% : GH&#x20B5; {{ number_format($whtAmountReceiptWHTamount, 2) }} </strong> </small> <br>
                            <small class="fw-medium">  <strong>  TOTAL : GH&#x20B5; {{ number_format($whtAmountReceiptAmountReceived + $whtAmountReceiptWHTamount, 2) }}</strong> </small> <br>
                            <small class="fw-medium">  <strong>  COUNT : {{ count($whtAmountReceipt) }}</strong> </small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="row">
            <div class="col-lg-2">
                <div style="background:  #e7e7e7ff;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> ACCRA </strong> </p>
                        <h4 class="card-title mb-3"><strong> &#x20B5; {{ number_format($accraAmountReceived, 2) }} </strong> </h4>
                        <small class="fw-medium"> <strong> WHT 7.5% : GH&#x20B5; {{ number_format($accraWHTAmount, 2) }}</strong> </small> <br>
                        <small class="fw-medium"> <strong> TOTAL : GH&#x20B5; {{ number_format($accraAmountReceived + $accraWHTAmount, 2) }}</strong> </small> <br>
                            <small class="fw-medium">  <strong>  COUNT : {{ count($accra) }}</strong> </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div style="background:  #e7e7e7ff;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> BOTWE </strong></p>
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{ number_format($botweAmountReceived, 2) }}</strong> </h4>
                        <small class="fw-medium"> WHT 7.5% : <strong> {{ number_format($botweWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium"> TOTAL : <strong> {{ number_format($botweAmountReceived + $botweWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium">  <strong>  COUNT : {{ count($botwe) }}</strong> </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2">
                <div style="background:  #e7e7e7ff;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> SHAIHILLS </strong></p>
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{ number_format($shyhillsAmountReceived, 2) }} </strong> </h4>
                        <small class="fw-medium"> WHT 7.5% : <strong> {{ number_format($shyhillsWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium"> TOTAL : <strong> {{ number_format($shyhillsAmountReceived + $shyhillsWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium">  <strong>  COUNT : {{ count($shyhills) }}</strong> </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2">
                <div style="background:  #e7e7e7ff;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    alt="chart success"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> TEMA </strong></p>
                        <h4 class="card-title mb-3"><strong>&#x20B5;{{ number_format($temaAmountReceived, 2) }} </strong> </h4>
                        <small class="fw-medium"> WHT 7.5% : <strong> {{ number_format($temaWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium"> TOTAL : <strong> {{ number_format($temaAmountReceived + $temaWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium">  <strong>  COUNT : {{ count($tema) }}</strong> </small>
                    </div>
                </div>
            </div>



            <div class="col-lg-2">
                <div style="background:  #e7e7e7ff;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1">TAKORADI</p>
                        <h4 class="card-title mb-3"><strong> &#x20B5; {{ number_format($takoradiAmountReceived, 2) }} </strong> </h4>
                        <small class="fw-medium"><strong> WHT 7.5% :  {{ number_format($takoradiWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium"> <strong> TOTAL : {{ number_format($takoradiAmountReceived + $takoradiWHTAmount, 2) }} </strong> </small>
                        <small class="fw-medium">  <strong>  COUNT : {{ count($takoradi) }}</strong> </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div style="background:  #e7e7e7ff;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"> <strong> KOFORIDUA </strong> </p>
                        <h4 class="card-title mb-3"> <strong> &#x20B5;{{number_format($koforiduaAmountReceived, 2)}} </strong> </h4>
                        <small class="fw-medium"> <strong> WHT 7.5% : {{ number_format($koforiduaWHTAmount, 2) }} </strong> </small> <br>
                        <small class="fw-medium"><strong> TOTAL :  {{ number_format($koforiduaAmountReceived + $koforiduaWHTAmount, 2) }} </strong> </small> <br>
                        <small class="fw-medium">  <strong>  COUNT : {{ count($koforidua) }}</strong> </small>
                    </div>
                </div>
            </div>


            <div class="col-lg-2 m-3">
                <div style="background:  #e7e7e7ff;" class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="img/icons/unicons/wallet-info.png"
                                    class="rounded" />
                            </div>

                        </div>
                        <p class="mb-1"><strong> KUMASI </strong> </p>
                        <h4 class="card-title mb-3"> <strong> &#x20B5;{{number_format($kumasiAmountReceived, 2)}} </strong> </h4>
                        <small class="fw-medium"> <strong>  WHT 7.5% : {{ number_format($kumasiWHTAmount, 2) }} </strong> </small> <br>
                        <small class="fw-medium">  <strong> TOTAL : {{ number_format($kumasiAmountReceived + $kumasiWHTAmount, 2) }} </strong> </small> <br>
                        <small class="fw-medium">  <strong>  COUNT : {{ count($kumasi) }}</strong> </small>
                    </div>
                </div>
            </div>

        </div><br><br>

        <div class="row">
            <form action="/searchReceiptsWHTPayment" method="GET">
                @csrf
                <div class="col">

                    <label for="" class="form-label"> <strong>   CHOOSE A MONTH TO SEARCH </strong> </label> <br>

                    <div class="form-check form-check-inline">
                        <input type="month" class="form-control" name="month" required/> <br>
                        
                        <button class="btn btn-dark" type="submit" onclick="return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('') }}</button>
                    </div>
                </div>
            </form>
        </div>
         <hr> <br>  

        <div class="row">
            <div class="col">
                <table id="myTable" class="display">
                    <thead>
                        <tr>
                            <th>id</th>
                            <th>Invoice No.</th>
                            <th>R. Month</th>
                            <th>Client Name</th>
                            <th>Phone No.</th>
                            <!-- <th>Business Name </th> -->
                            <th> Field Office </th>
                            <!-- <th> Stuff </th> -->
                            <th>Date Created</th>
                            <!-- <th>Date Updated</th> -->
                            <!-- <th>Owing</th> -->
                            <th>Inv_Amount</th>
                            <th>WHT_Amount</th>
                            <th>WHT_7.5%</th>
                            <th>Status</th>
                            <th>View</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @foreach($whtAmountReceipt as $receipt)
                        <tr>
                            <td>FWSSR{{$receipt->id}}</td>
                            <td>FWSSi{{$receipt->invoice_id}} </td>
                            <td> {{$receipt->receipt_month?->format('F, Y')}} </td>
                            @if ($receipt->client->name === $receipt->client->business_name)
                            <td> {{$receipt->client->business_name}} </td>
                            @else
                            <td> {{$receipt->client->name}} {{$receipt->client->business_name}} </td>
                            @endif

                            <td> {{$receipt->client->phone_number}} </td>
                            <!-- <td> </td> -->
                            <td> {{$receipt->client->field->name}} </td>
                            <td> {{$receipt->created_at->diffForHumans()}} </td>

                            <td> <strong>GH&#x20B5; {{number_format($receipt->total, 2)}} </strong> </td>
                            <td> <strong>GH&#x20B5; {{number_format($receipt->amount_received, 2)}} </strong> </td>
                            <td> <strong>GH&#x20B5; {{number_format($receipt->wht_amount, 2)}} </strong> </td>

                            @if($receipt->status == 'completed')
                            <td><span class="badge bg-label-success">{{$receipt->status}}</span></td>
                            @else
                            <td><span class="badge bg-label-danger">{{$receipt->status}}</span></td>
                            @endif

                            <td>
                                <a href="{{url('receipt', $receipt->id)}}" class="btn btn-info">
                                    <i class="icon-base bx bxs-bullseye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
        </div>
    </div>
    @endsection


    @section('scripts')

    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.dataTables.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/columncontrol/1.1.1/js/dataTables.columnControl.min.js"></script>


    <script>
        new DataTable('#myTable', {
            responsive: true,

            layout: {
                topStart: {
                    buttons: ['excelHtml5', 'pdfHtml5']
                }
            },
            columnControl: [
                ['search']
            ]
        });
    </script>
    @endsection

</x-sales-dashboard>
<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.5/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.5/css/buttons.dataTables.css">   
     <link rel="stylesheet" href="{{asset('vendor/css/datatables.css')}}" /> 


    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/4.0.5/css/fixedHeader.dataTables.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/5.0.5/css/fixedColumns.dataTables.css">
    <link href="https://cdn.datatables.net/columncontrol/1.1.1/css/columnControl.dataTables.min.css" rel="stylesheet">

    
    @endsection


  @section('side_nav')
    @include('partials.payroll_side_nav')
  @endsection

  @section('content')

  <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bx-transfer-alt"></i> Salaries Transaction   @if (isset($month)) <strong> / For Month: {{  \Carbon\Carbon::parse($month)->format('F Y') }}  </strong>  @endif </h3>

            </div>
        </div><br>

         @if(Auth::user()->hasRole(['Invoice','Manager',  'Internal Auditor', 'Officer', 'Finance Manager' ]))
        <div class="row">

        <div class="col-lg-3">
                <div  class="card h-100 bg-secondary text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart dark"
                                    class="rounded" />
                            </div>
                        <h5 class="mb-1 text-white"><strong> CATEGORY A  </strong></h5>

                        </div>
                        <h2 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($clientA->sum('net_salary'), 2) }} </strong> </h2> 
                        <small class="fw-medium"> <strong>  INVOICES :  &#x20B5; {{ number_format($clientAInvoices->sum('total'), 2) }}  </strong> </small> <br>
                        <small class="fw-medium"> <strong>  RECEIPTS :  &#x20B5; @if (isset($clientAReceipts['transfer'])) {{ number_format( collect($clientAReceipts['transfer'])->flatten()->sum() + collect($clientAReceipts['cheque'])->flatten()->sum() + collect($clientAReceipts['cash'])->flatten()->sum() + collect($clientAReceipts['momo'])->flatten()->sum(), 2) }} @endif </strong> </small> <br>
                        <small class="fw-medium"> CLIENTS :   {{ $clientA->count()  }} </small> <br>
                        
                        <div class="d-flex justify-content-between">
                            <div> <small class="fw-medium"> EMPLOYEES : {{ $clientA->sum('total_employees') }} </small>    </div>   
                            <div>  <small class="fw-medium text-danger"> <strong> HOLD : {{ $clientAHold->sum('total_employees') }} </strong> </small> </div>
                        </div>
                        <small class="fw-medium">  INVOICE GUARDS : {{ $clientAInvoicesGuards }}</small>
                        <hr>
                        <small class="fw-medium"> <strong>  MOMO : &#x20B5; {{ number_format($clientACash->sum('net_salary'), 2) }} | {{ $clientACash->count() }} </strong> </small> <br>
                        <small class="fw-medium"> <strong>  BANK : &#x20B5; {{ number_format($clientABank->sum('net_salary'), 2) }} | {{ $clientABank->count() }} </strong> </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-3">
                <div  class="card h-100 bg-secondary text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart dark"
                                    class="rounded" />
                            </div>
                        <h5 class="mb-1 text-white"><strong> CATEGORY B </strong></h5>

                        </div>
                        <h2 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($clientB->sum('net_salary'), 2) }} </strong> </h2> 
                        <small class="fw-medium"> <strong>  INVOICES :  &#x20B5; {{ number_format($clientBInvoices->sum('total'), 2) }} </strong> </small> <br>
                        <small class="fw-medium"> <strong>  RECEIPTS :  &#x20B5; @if (isset($clientBReceipts['transfer'])) {{ number_format( collect($clientBReceipts['transfer'])->flatten()->sum() + collect($clientBReceipts['cheque'])->flatten()->sum() + collect($clientBReceipts['cash'])->flatten()->sum() + collect($clientBReceipts['momo'])->flatten()->sum(), 2) }} @endif </strong> </small> <br>
                        <small class="fw-medium"> CLIENTS :  {{ $clientB->count() }} </small> <br>
                        <div class="d-flex justify-content-between">
                            <small class="fw-medium"> EMPLOYEES : {{ $clientB->sum('total_employees') }} </small>  
                            <small class="fw-medium text-danger"> <strong> HOLD : {{ $clientBHold->sum('total_employees') }} </strong> </small> 
                        </div>
                         <small class="fw-medium">  INVOICE GUARDS : {{ $clientBInvoicesGuards }}</small>
                        <hr>
                        <small class="fw-medium"> <strong>  MOMO : &#x20B5; {{ number_format($clientBCash->sum('net_salary'), 2) }} | {{ $clientBCash->count() }} </strong> </small> <br>
                        <small class="fw-medium"> <strong>  BANK : &#x20B5; {{ number_format($clientBBank->sum('net_salary'), 2) }} | {{ $clientBBank->count() }} </strong> </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-3">
                <div  class="card h-100 bg-secondary text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        <h5 class="mb-1 text-white"><strong> CATEGORY C  </strong></h5>

                        </div>
                        <h2 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($clientC->sum('net_salary'), 2) }} </strong> </h2> 
                        <small class="fw-medium"> <strong>  INVOICES :  &#x20B5; {{ number_format($clientCInvoices->sum('total'), 2) }} </strong> </small> <br>
                        <small class="fw-medium"> <strong>  RECEIPTS :  &#x20B5; @if (isset($clientCReceipts['transfer'])) {{ number_format( collect($clientCReceipts['transfer'])->flatten()->sum() + collect($clientCReceipts['cheque'])->flatten()->sum() + collect($clientCReceipts['cash'])->flatten()->sum() + collect($clientCReceipts['momo'])->flatten()->sum(), 2) }} @endif </strong> </small> <br>
                        <small class="fw-medium"> CLIENTS : {{ $clientC->count() }} </small> <br>
                       <div class="d-flex justify-content-between">
                            <small class="fw-medium"> EMPLOYEES : {{ $clientC->sum('total_employees') }} </small> 
                            <small class="fw-medium text-danger"> <strong> HOLD : {{ $clientCHold->sum('total_employees') }} </strong> </small>
                       </div>
                         <small class="fw-medium">  INVOICE GUARDS : {{ $clientCInvoicesGuards }}</small>
                        <hr>
                        <small class="fw-medium"> <strong>  MOMO : &#x20B5; {{ number_format($clientCCash->sum('net_salary'), 2)  }} |  {{  $clientCCash->count() }} </strong> </small> <br>
                        <small class="fw-medium"> <strong>  BANK : &#x20B5; {{ number_format($clientCBank->sum('net_salary'), 2) }} | {{ $clientCBank->count() }} </strong> </small> 
                    </div>
                </div>
            </div>

            <div class="col-lg-3">
                <div  class="card h-100 bg-secondary text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        <h5 class="mb-1 text-white"><strong> CATEGORY D  </strong></h5>

                        </div>
                        <h2 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($clientD->sum('net_salary'), 2) }} </strong> </h2> 
                        <small class="fw-medium"> <strong>  INVOICES :  &#x20B5; {{ number_format($clientDInvoices->sum('total'), 2) }} </strong> </small> <br>
                        <small class="fw-medium"> <strong>  RECEIPTS :  &#x20B5; @if (isset($clientDReceipts['transfer'])) {{ number_format( collect($clientDReceipts['transfer'])->flatten()->sum() + collect($clientDReceipts['cheque'])->flatten()->sum() + collect($clientDReceipts['cash'])->flatten()->sum() + collect($clientDReceipts['momo'])->flatten()->sum(), 2) }} @endif </strong> </small> <br>
                        <small class="fw-medium"> CLIENTS :  {{ $clientD->count() }} </small> <br>
                       <div class="d-flex justify-content-between">
                            <small class="fw-medium"> EMPLOYEES : {{ $clientD->sum('total_employees') }} </small>
                            <small class="fw-medium text-danger"> <strong> HOLD : {{ $clientDHold->sum('total_employees') }} </strong> </small> 
                       </div>
                         <small class="fw-medium">  INVOICE GUARDS : {{ $clientDInvoicesGuards }}</small>
                        <hr>
                        <small class="fw-medium"> <strong>  MOMO : &#x20B5; {{ number_format($clientDCash->sum('net_salary'), 2) }} | {{ $clientDCash->count() }} </strong> </small> <br>
                        <small class="fw-medium"> <strong>  BANK : &#x20B5; {{ number_format($clientDBank->sum('net_salary'), 2) }} | {{ $clientDBank->count() }} </strong> </small>  
                    </div>
                </div>
            </div>            

        </div> 
        
        
        
            <div class="card-header  ml-2  d-none d-lg-block">
                @include('flash-messages')
            </div> <br>
        @endif

        <!-- Table -->  
         <br> 
        <div class="nav-align-top">
            <ul class="nav nav-pills mb-4 nav-fill" role="tablist">

                @if(Auth::user()->hasRole(['Finance Manager', 'Officer',  'Internal Auditor', 'Invoice', 'Manager']) )
                <li class="nav-item mb-1 mb-sm-0">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-clients"
                        aria-controls="navs-pills-justified-clients"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center">
                            <i class="icon-base bx bx-home icon-sm me-1_5"></i>Clients
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesClients->count() }} </span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>
              
                <li class="nav-item mb-1 mb-sm-0">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-accra"
                        aria-controls="navs-pills-justified-accra"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center">
                            <i class="icon-base bx bx-home icon-sm me-1_5"></i>Banks
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $groupedBankSalaries->count() }} </span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>

                <li class="nav-item">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-botwe"
                        aria-controls="navs-pills-justified-botwe"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>MoMo
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $groupedCashkSalaries->count() }} </span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>


                <li class="nav-item mb-1 mb-sm-0">
                    <button
                        type="button"
                        class="nav-link active"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-master"
                        aria-controls="navs-pills-justified-master"
                        aria-selected="true">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i> Master
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesMaster->count() }} </span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>

                <li class="nav-item mb-1 mb-sm-0">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-hold"
                        aria-controls="navs-pills-justified-hold"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i> HOLD
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>
                @endif

            </ul>


            <div class="tab-content">
                <div class="tab-pane fade " id="navs-pills-justified-clients" role="tabpanel">

                             <div class="nav-align-top">
                                <ul class="nav nav-pills mb-4 nav-fill" role="tablist">
                                    
                                    <li class="nav-item mb-1 mb-sm-0">
                                        <button
                                            type="button"
                                            class="nav-link"
                                            role="tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#navs-pills-justified-categorya"
                                            aria-controls="navs-pills-justified-categorya"
                                            aria-selected="true">
                                            <span class="d-none d-sm-inline-flex align-items-center">
                                                <i class="icon-base bx bx-home icon-sm me-1_5"></i>CATEGORY A
                                                <span class="badge rounded-pill bg-danger ms-1_5"> {{ $clientA->count() }} </span>
                                            </span>
                                            <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                                        </button>
                                    </li>

                                    <li class="nav-item mb-1 mb-sm-0">
                                        <button
                                            type="button"
                                            class="nav-link"
                                            role="tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#navs-pills-justified-categoryb"
                                            aria-controls="navs-pills-justified-categoryb"
                                            aria-selected="true">
                                            <span class="d-none d-sm-inline-flex align-items-center">
                                                <i class="icon-base bx bx-home icon-sm me-1_5"></i>CATEGORY B
                                                <span class="badge rounded-pill bg-danger ms-1_5"> {{ $clientB->count() }} </span>
                                            </span>
                                            <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                                        </button>
                                    </li>

                                    <li class="nav-item mb-1 mb-sm-0">
                                        <button
                                            type="button"
                                            class="nav-link"
                                            role="tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#navs-pills-justified-categoryc"
                                            aria-controls="navs-pills-justified-categoryc"
                                            aria-selected="true">
                                            <span class="d-none d-sm-inline-flex align-items-center">
                                                <i class="icon-base bx bx-home icon-sm me-1_5"></i>CATEGORY C
                                                <span class="badge rounded-pill bg-danger ms-1_5"> {{ $clientC->count() }} </span>
                                            </span>
                                            <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                                        </button>
                                    </li>

                                    <li class="nav-item mb-1 mb-sm-0">
                                        <button
                                            type="button"
                                            class="nav-link"
                                            role="tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#navs-pills-justified-categoryd"
                                            aria-controls="navs-pills-justified-categoryd"
                                            aria-selected="true">
                                            <span class="d-none d-sm-inline-flex align-items-center">
                                                <i class="icon-base bx bx-home icon-sm me-1_5"></i>CATEGORY D
                                                <span class="badge rounded-pill bg-danger ms-1_5"> {{ $clientD->count() }} </span>
                                            </span>
                                            <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                                        </button>
                                    </li>

                                    <li class="nav-item mb-1 mb-sm-0">
                                        <button
                                            type="button"
                                            class="nav-link"
                                            role="tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#navs-pills-justified-clientmaster"
                                            aria-controls="navs-pills-justified-clientmaster"
                                            aria-selected="true">
                                            <span class="d-none d-sm-inline-flex align-items-center">
                                                <i class="icon-base bx bx-home icon-sm me-1_5"></i>CLIENT MASTER
                                                <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesClients->count() }} </span>
                                            </span>
                                            <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                                        </button>
                                    </li>

                                </ul>
                             </div>

                             <div class="tab-content">
                                
                                <div class="tab-pane fade " id="navs-pills-justified-categorya" role="tabpanel">
                                    <form action="/categoryReAssign" method="POST">
                                        @csrf
                                        <input type="text" name="month" value="{{ $month }}" hidden>
                                        <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />
                                    
                                        <div class="form-check form-check-inline">
                                             @if(Auth::user()->hasRole(['Finance Manager']))
                                            <button class="btn btn-dark" name="submit" value="" onclick="return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-recycle"> </i> {{ __('ReAssign Category') }}</button>                   
                                            @endif
                                            <a href="/exportCategory/{{ $month }}/Category A" class="btn btn-success m-4" > <i class="icon-base bx bx-bxs-file-plus"> </i> {{ __(' Excel Download Category') }}</a>                   

                                        </div>
                                    <div class="table-responsive">
                                        <table id="myTablecategorya" class="display">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>#</th>
                                                    <th>Client Name</th>
                                                    <th>Category_Name</th>
                                                    <th>Category_Date</th>
                                                    <th>Field</th>
                                                    <th>Inv Value</th>
                                                    <th>Inv Guards</th>
                                                    <th>Inv Status</th>
                                                    <th>Net Salary</th>
                                                    <th> Difference </th>
                                                    <th>Status</th>
                                                    <th>Employees</th>
                                                    <th> View Employees</th>

                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">
                                                @foreach($clientA as $key => $catA)
                                                <tr>
                                                    <td> <input class="checkBoxes form-check-input" type="checkbox" name="client[{{ $key + 1 }}]" value="{{ $catA->client?->id }}" /> </td>
                                                    <td> {{ $key + 1 }} </td>
                                                    <td> {{ $catA->client?->name }} {{ $catA->client?->business_name }} </td>
                                                    <td>
                                                        <select name="name[{{ $key + 1 }}]" class="form-select @error('name') is-invalid @enderror" id="name" value="{{ old('name')}}" required>
                                                            <option >Choose...</option>
                                                            <option selected value="Category A">Category A </option>
                                                            <option value="Category B">Category B </option>
                                                            <option value="Category C">Category C </option>
                                                            <option value="Category D">Category D </option>
                                                        </select>
                                                    </td>
                                                    <td> @if( $catA->client?->category_month == \Carbon\Carbon::parse($month)->format('Y-m-d') ) {{ $catA->client?->category?->updated_at?->format('F l d, Y') }} @endif </td>
                                                    <td> {{ $catA->client?->field?->name }} </td>
                                                    <td> @php  $catAinv = $catA->client?->invoices; @endphp GH&#x20B5; {{  number_format($catAinv->sum('total'), 2)  }} </td>
                                                  
                                                    <td>  
                                                        @php
                                                            $guardsa = [];
                                                            foreach ($catAinv as $invGuard) {
                                                                $guardsa[] = $invGuard->invoice_data->sum('quantity');
                                                            }
                                                        @endphp
                                                        {{ collect($guardsa)->sum() }}
                                                    </td>

                                                    <td>  {{ $catAinv->pluck('status') }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catA->net_salary, 2) }} </td>
                                                    <td> GH&#x20B5; {{ number_format( $catAinv->sum('total') - $catA->net_salary, 2) }} </td>
                                                
                                                    @if( $catAinv->sum('total') <= $catA->net_salary )
                                                        <td><span class="badge bg-label-danger"> Loss </span></td>
                                                    @else
                                                        <td><span class="badge bg-label-success"> Profit </span></td>
                                                    @endif
                                                
                                                    <td> {{ $catA->total_employees }} </td>
                                                    <td> 
                                                        <a href="/salariesClientMonth/{{$catA->client_id}} / {{$month}} " class="btn btn-dark btn-sm">
                                                            <i class="bx bx-show"></i> 
                                                        </a>    
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    </form>
                                </div>

                                <div class="tab-pane fade " id="navs-pills-justified-categoryb" role="tabpanel">
                                    <form action="/categoryReAssign" method="POST">
                                        @csrf
                                        <input type="text" name="month" value="{{ $month }}" hidden>
                                        <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />
                                    
                                        <div class="form-check form-check-inline">
                                             @if(Auth::user()->hasRole(['Finance Manager']))
                                            <button class="btn btn-dark" name="submit" value="" onclick="return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-recycle"> </i> {{ __('ReAssign Category') }}</button>                   
                                            @endif
                                            <a href="/exportCategory/{{ $month }}/Category B" class="btn btn-success m-4" > <i class="icon-base bx bx-bxs-file-plus"> </i> {{ __(' Excel Download Category') }}</a>                   
                                       
                                        </div>
                                    <div class="table-responsive">
                                        <table id="myTablecategoryb" class="display">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>#</th>
                                                    <th>Client Name</th>
                                                    <th>Category_Name</th>
                                                    <th> Category_Date </th>
                                                    <th>Field</th>
                                                    <th>Inv Value</th>
                                                    <th>Inv Guards</th>
                                                    <th>Inv Status</th>
                                                    <th>Net Salary</th>
                                                    <th> Difference </th>
                                                    <th>Status</th>
                                                    <th>Employees</th>
                                                    <th> View Employees</th>
                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">
                                                @foreach($clientB as $keyb => $catB)
                                                <tr>
                                                    <td> <input class="checkBoxes form-check-input" type="checkbox" name="client[{{ $keyb + 1 }}]" value="{{ $catB->client?->id }}" /> </td>
                                                   <td> {{ $keyb + 1 }} </td>
                                                    <td> {{ $catB->client?->name }} {{ $catB->client?->business_name }} </td>
                                                    <td> 
                                                                <select name="name[{{ $keyb + 1 }}]" class="form-select @error('name') is-invalid @enderror" id="name" value="{{ old('name')}}" required>
                                                                    <option value="">Choose...</option>
                                                                    <option value="Category A">Category A </option>
                                                                    <option selected value="Category B">Category B </option>
                                                                    <option value="Category C">Category C </option>
                                                                    <option value="Category D">Category D </option>
                                                                </select>    
                                                    </td>
                                                    <td>  @if( $catB->client?->category_month == \Carbon\Carbon::parse($month)->format('Y-m-d') ) {{ $catB->client?->category?->updated_at?->format('F l d, Y') }} @endif </td>
                                                    <td> {{ $catB->client?->field?->name }} </td>
                                                    <td> @php $catBinv = $catB->client?->invoices @endphp GH&#x20B5; {{ number_format( $catBinv->sum('total'),2)  }} </td>
                                                    <td> 
                                                        @php
                                                            $guardsb = [];
                                                            foreach ($catBinv as $invGuard) {
                                                                $guardsb[] = $invGuard->invoice_data->sum('quantity');
                                                            }
                                                        @endphp
                                                        {{ collect($guardsb)->sum() }}
                                                    </td>
                                                    <td>  {{ $catBinv->pluck('status') }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catB->net_salary, 2) }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catBinv->sum('total') - $catB->net_salary, 2) }} </td>
                                                
                                                    @if( $catBinv->sum('total') <= $catB->net_salary )
                                                        <td><span class="badge bg-label-danger"> Loss </span></td>
                                                    @else
                                                        <td><span class="badge bg-label-success"> Profit </span></td>
                                                    @endif
                                                
                                                    <td> {{ $catB->total_employees }} </td>
                                                    <td> 
                                                        <a href="/salariesClientMonth/{{$catB->client_id}} / {{$month}} " class="btn btn-dark btn-sm">
                                                            <i class="bx bx-show"></i> 
                                                        </a>    
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    </form>
                                </div>


                                <div class="tab-pane fade " id="navs-pills-justified-categoryc" role="tabpanel">
                                    <form action="/categoryReAssign" method="POST">
                                        @csrf
                                        <input type="text" name="month" value="{{ $month }}" hidden>
                                        <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />
                                    
                                        <div class="form-check form-check-inline">
                                             @if(Auth::user()->hasRole(['Finance Manager']))
                                            <button class="btn btn-dark" name="submit" value="" onclick="return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-recycle"> </i> {{ __('ReAssign Category') }}</button>                   
                                            @endif
                                            <a href="/exportCategory/{{ $month }}/Category C" class="btn btn-success m-4" > <i class="icon-base bx bx-bxs-file-plus"> </i> {{ __(' Excel Download Category') }}</a>                   
                                       
                                        </div>

                                    <div class="table-responsive">
                                        <table id="myTablecategoryc" class="display">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>#</th>
                                                    <th>Client Name</th>
                                                    <th>Category_Name</th>
                                                    <th>Category_Date</th>
                                                    <th>Field</th>
                                                    <th>Inv Value</th>
                                                    <th>Inv Guards</th>
                                                    <th>Inv Status</th>
                                                    <th>Net Salary</th>
                                                    <th> Difference </th>
                                                    <th>Status</th>
                                                    <th>Employees</th>
                                                    <th> View Employees</th>
                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">
                                                @foreach($clientC as $keyc => $catC)
                                                <tr>
                                                    <td> <input class="checkBoxes form-check-input" type="checkbox" name="client[{{ $keyc + 1 }}]" value="{{ $catC->client?->id }}" /> </td>
                                                   <td> {{ $keyc + 1 }} </td>
                                                    <td> {{ $catC->client?->name }} {{ $catC->client?->business_name }} </td>
                                                    <td> 
                                                                <select name="name[{{ $keyc + 1 }}]" class="form-select @error('name') is-invalid @enderror" id="name" value="{{ old('name')}}" required>
                                                                    <option value="">Choose...</option>
                                                                    <option value="Category A">Category A </option>
                                                                    <option value="Category B">Category B </option>
                                                                    <option selected value="Category C">Category C </option>
                                                                    <option value="Category D">Category D </option>
                                                                </select>
                                                    </td>
                                                    <td> @if( $catC->client?->category_month == \Carbon\Carbon::parse($month)->format('Y-m-d') ) {{ $catC->client?->category?->updated_at?->format('F l d, Y') }} @endif </td>
                                                    <td> {{ $catC->client?->field?->name }} </td>
                                                    <td>  @php $catCinv = $catC->client?->invoices @endphp GH&#x20B5; {{ number_format( $catCinv->sum('total'),2)  }} </td>
                                                    <td>
                                                        @php
                                                            $guardsc = [];
                                                            foreach ($catCinv as $invGuard) {
                                                                $guardsc[] = $invGuard->invoice_data->sum('quantity');
                                                            }
                                                        @endphp
                                                        {{ collect($guardsc)->sum() }}
                                                    </td>
                                                    <td>  {{ $catCinv->pluck('status') }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catC->net_salary, 2) }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catCinv->sum('total') - $catC->net_salary, 2) }} </td>
                                                
                                                    @if( $catCinv->sum('total') <= $catC->net_salary )
                                                        <td><span class="badge bg-label-danger"> Loss </span></td>
                                                    @else
                                                        <td><span class="badge bg-label-success"> Profit </span></td>
                                                    @endif
                                                
                                                    <td> {{ $catC->total_employees }} </td>
                                                    <td> 
                                                        <a href="/salariesClientMonth/{{$catC->client_id}} / {{$month}} " class="btn btn-dark btn-sm">
                                                            <i class="bx bx-show"></i> 
                                                        </a>    
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    </form>
                                </div>


                                <div class="tab-pane fade " id="navs-pills-justified-categoryd" role="tabpanel">
                                    <form action="/categoryReAssign" method="POST">
                                        @csrf
                                        <input type="text" name="month" value="{{ $month }}" hidden>
                                        <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />
                                    
                                        <div class="form-check form-check-inline">
                                             @if(Auth::user()->hasRole(['Finance Manager']))
                                            <button class="btn btn-dark" name="submit" value="" onclick="return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-recycle"> </i> {{ __('ReAssign Category') }}</button>                   
                                           @endif
                                            <a href="/exportCategory/{{ $month }}/Category D" class="btn btn-success m-4" > <i class="icon-base bx bx-bxs-file-plus"> </i> {{ __(' Excel Download Category') }}</a>                   
                                       
                                        </div>

                                    <div class="table-responsive">
                                        <table id="myTablecategoryd" class="display">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>#</th>
                                                    <th>Client Name</th>
                                                    <th>Category_Name</th>
                                                    <th> Category_Date </th>
                                                    <th>Field</th>
                                                    <th>Inv Value</th>
                                                    <th>Inv Guards</th>
                                                    <th>Inv Status</th>
                                                    <th>Net Salary</th>
                                                    <th> Difference </th>
                                                    <th>Status</th>
                                                    <th>Employees</th>
                                                    <th> View Employees</th>
                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">
                                                @foreach($clientD as $keyd => $catD)
                                                <tr>
                                                    <td> <input class="checkBoxes form-check-input" type="checkbox" name="client[{{ $keyd + 1 }}]" value="{{ $catD->client?->id }}" /> </td>
                                                   <td> {{ $keyd + 1 }} </td>
                                                    <td> {{ $catD->client?->name }} {{ $catD->client?->business_name }} </td>
                                                   <td> 
                                                        <select name="name[{{ $keyd + 1 }}]" class="form-select @error('name') is-invalid @enderror" id="name" value="{{ old('name')}}" required>
                                                            <option value="">Choose...</option>
                                                            <option value="Category A">Category A </option>
                                                            <option value="Category B">Category B </option>
                                                            <option value="Category C">Category C </option>
                                                            <option selected value="Category D">Category D </option>
                                                        </select>
                                                   </td>
                                                    <td> @if( $catD->client?->category_month == \Carbon\Carbon::parse($month)->format('Y-m-d') ) {{ $catD->client?->category?->updated_at?->format('F l d, Y') }} @endif </td>
                                                    <td> {{ $catD->client?->field?->name }} </td>
                                                    <td> @php $catDinv = $catD->client?->invoices @endphp GH&#x20B5; {{ number_format( $catDinv->sum('total'),2)  }} </td>
                                                    <td> 
                                                        @php
                                                            $guardsd = [];
                                                            foreach ($catDinv as $invGuard) {
                                                                $guardsd[] = $invGuard->invoice_data->sum('quantity');
                                                            }
                                                        @endphp
                                                        {{ collect($guardsd)->sum() }}
                                                    </td>
                                                    <td>  {{ $catDinv->pluck('status') }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catD->net_salary, 2) }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catDinv->sum('total') - $catD->net_salary, 2) }} </td>
                                                
                                                    @if( $catDinv->sum('total') <= $catD->net_salary )
                                                        <td><span class="badge bg-label-danger"> Loss </span></td>
                                                    @else
                                                        <td><span class="badge bg-label-success"> Profit </span></td>
                                                    @endif
                                                
                                                    <td> {{ $catD->total_employees }} </td>
                                                    <td> 
                                                        <a href="/salariesClientMonth/{{$catD->client_id}} / {{$month}} " class="btn btn-dark btn-sm">
                                                            <i class="bx bx-show"></i> 
                                                        </a>    
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    </form>
                                </div>

                            
                                <div class="tab-pane fade " id="navs-pills-justified-clientmaster" role="tabpanel">
                                    <form action="/categoryAssign" method="POST">
                                        @csrf
                                        <input type="text" name="month" value="{{ $month }}" hidden>
                                        <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />
                                    
                                        <div class="form-check form-check-inline">
                                             @if(Auth::user()->hasRole(['Finance Manager']))
                                            <button class="btn btn-dark" name="submit" value="" onclick="return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-recycle"> </i> {{ __('Assign Category') }}</button>                   
                                            @endif
                                        </div>
                                        
                                        <div class="table-responsive">
                                            <table id="myTableiclientmaster" class="display">
                                                <thead>
                                                    <tr>
                                                        <th></th>
                                                        <th>#</th>
                                                        <th>Client Name</th>
                                                        <th>Category</th>
                                                        <th> Category_Date </th>
                                                        <th> Assign New_Category</th>
                                                        <th>Field</th>
                                                        <th>Inv Value</th>
                                                        <th>Inv Guards</th>
                                                        <th>Inv Status</th>
                                                        <th>Net Salary</th>
                                                        <th> Difference </th>
                                                        <th>Status</th>
                                                        <th>Employees</th>
                                                        <th> View Employees</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    @foreach($salariesClients as $cm => $clientMaster)
                                                    <tr>
                                                        <td> <input class="checkBoxes form-check-input" type="checkbox" name="client[]" value="{{ $clientMaster->client?->id }}" /> </td>
                                                        <td> {{ $cm + 1 }} </td>
                                                        <td> {{ $clientMaster->client?->name }} {{ $clientMaster->client?->business_name }} </td>
                                                        <td> @if ( $clientMaster->client?->category_month == \Carbon\Carbon::parse($month)->format('Y-m-d') ) {{ $clientMaster->client?->category_name }} @endif  </td>
                                                        <!-- <td>   {{ $clientMaster->client?->category?->name }}  </td> -->
                                                        <td> @if ( $clientMaster->client?->category_month == \Carbon\Carbon::parse($month)->format('Y-m-d') ) {{ $clientMaster->client?->category?->updated_at?->format('F l d, Y') }} @endif </td>
                                                        <td>
                                                                <select name="name[]" class="form-select @error('name') is-invalid @enderror" id="name" value="{{ old('name')}}" required>
                                                                    <option selected disabled>Choose...</option>
                                                                    <option value="Category A">Category A </option>
                                                                    <option value="Category B">Category B </option>
                                                                    <option value="Category C">Category C </option>
                                                                    <option value="Category D">Category D </option>
                                                                </select>
                                                        </td>
                                                        <td> {{ $clientMaster->client?->field?->name }} </td>
                                                        <td> @php $clientInv = $clientMaster->client?->invoices @endphp GH&#x20B5; {{ number_format($clientInv->sum('total'), 2) }} </td>
                                                        <td> 
                                                            @php
                                                                $guards = [];
                                                                foreach ($clientInv as $invGuard) {
                                                                    $guards[] = $invGuard->invoice_data->sum('quantity');
                                                                }
                                                            @endphp
                                                            {{ collect($guards)->sum() }}
                                                        </td>
                                                        <td>  {{ $clientInv->pluck('status') }} </td>
                                                        <td> GH&#x20B5; {{ number_format($clientMaster->paid, 2) }} </td>
                                                        <td> GH&#x20B5; {{ number_format($clientInv->sum('total') - $clientMaster->paid, 2) }} </td>

                                                        @if( $clientInv->sum('total') <= $clientMaster->paid )
                                                            <td><span class="badge bg-label-danger"> Loss </span></td>
                                                        @else
                                                            <td><span class="badge bg-label-success"> Profit </span></td>
                                                        @endif
                                                    
                                                        <td> {{ $clientMaster->total_employees }} </td>
                                                        <td> 
                                                            <a href="/salariesClientMonth/{{$clientMaster->client_id}} / {{$month}} " class="btn btn-dark btn-sm">
                                                                <i class="bx bx-show"></i> 
                                                            </a>    
                                                        </td>

                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                    </form>
                                </div>

                             </div>
               
                </div>

                <div class="tab-pane fade " id="navs-pills-justified-accra" role="tabpanel">
                    <div class="table-responsive">
                        <table id="myTableiAccra" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Bank Name</th>
                                    <th>Gross Salary</th>
                                    <th>Total Deductions</th>
                                    <th>Net Salary</th>
                                    <th>Employees</th>
                                    <th> View Employees</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                @foreach($groupedBankSalaries as $key => $banks)
                                <tr>
                                    <td> {{ $key + 1 }} </td>
                                    <td> {{ $banks->bank->name }} </td>
                                    <td> GH&#x20B5; {{ number_format($banks->gross, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($banks->deductions, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($banks->paid, 2) }} </td>
                                    <td> {{ $banks->total_employees }} </td>
                                    <td> 
                                        <a href="/salariesBankMonth/{{ $banks->bank->id }}/{{ $month }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a>    
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>


                <div class="tab-pane fade" id="navs-pills-justified-botwe" role="tabpanel">

                    <div class="table-responsive text-nowrap">
                        <table id="myTableiBotwe" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Field</th>
                                    <th>Gross Salary</th>
                                    <th>Total Deductions</th>
                                    <th>Net Salary </th>
                                    <th>Employees</th>
                                    <th> View Employees</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                @foreach($groupedCashkSalaries as $key => $cash)
                                <tr>
                                    <td> {{ $key + 1 }} </td>
                                    <td>{{ $cash->field?->name }}</td>
                                    <td> GH&#x20B5; {{ number_format($cash->gross, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($cash->deductions, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($cash->paid, 2) }} </td>
                                    <td> {{ $cash->total_employees }} </td>
                                    <td> 
                                        <a href="/salariesCashMonth/{{ $cash->field?->id }}/{{ $month->format('F Y') }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a>    
                                    </td>
                                   
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                 <div class="tab-pane fade show active" id="navs-pills-justified-master" role="tabpanel">
                    @php $canEditMaster = Auth::user()->hasRole(['Finance Manager']); @endphp
                    <form action="/salariesBulkCash" method="POST" id="masterForm">
                        @csrf
                        <input type="hidden" name="action_type" id="salaries_bulk_action_type" value="" />
                        <div id="masterSelectionInputs"></div>

                        {{-- Filters (server-side). Export always exports ALL filtered rows, not just the visible page. --}}
                        <div class="row g-2 align-items-end mb-2">
                            <div class="col-auto">
                                <label class="form-label small mb-0" for="m_status">Status</label>
                                <select id="m_status" class="form-select form-select-sm master-filter">
                                    <option value="">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved (paid)</option>
                                    <option value="outstanding">Outstanding (pending + hold + rejected)</option>
                                    <option value="hold">Hold</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <label class="form-label small mb-0" for="m_priority">Pay priority</label>
                                <select id="m_priority" class="form-select form-select-sm master-filter">
                                    <option value="">All</option>
                                    <option value="flagged">Pay first + pay early</option>
                                    <option value="urgent">Pay first only</option>
                                    <option value="priority">Pay early only</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <label class="form-label small mb-0" for="m_type">Payment type</label>
                                <select id="m_type" class="form-select form-select-sm master-filter">
                                    <option value="">All</option>
                                    <option value="bank">Bank</option>
                                    <option value="cash">MoMo / Cash</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <label class="form-label small mb-0" for="m_field">Field office</label>
                                <select id="m_field" class="form-select form-select-sm master-filter">
                                    <option value="">All fields</option>
                                    @foreach($fields as $field)
                                        <option value="{{ $field->id }}">{{ $field->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                <label class="form-label small mb-0" for="m_category">Category</label>
                                <select id="m_category" class="form-select form-select-sm master-filter">
                                    <option value="">All</option>
                                    @foreach(['A','B','C','D'] as $L)
                                        <option value="Category {{ $L }}">Category {{ $L }}</option>
                                    @endforeach
                                    <option value="none">No category</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-sm btn-success" id="masterExport">
                                    <i class="bx bx-spreadsheet me-1"></i> Export filtered rows to Excel
                                </button>
                            </div>
                        </div>

                        <div class="small text-muted mb-2" id="masterTotals" aria-live="polite">Loading totals…</div>
                        {{-- Payment priority summary + alert for pay-first salaries stuck on hold (filled from the data response). --}}
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2" id="masterPriority" aria-live="polite"></div>

                        @if($canEditMaster)
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <button class="btn btn-danger btn-sm" type="submit" data-action="hold" disabled>
                                <i class="icon-base bx bx-bxs-file-plus"></i> Hold selected
                            </button>
                            <button class="btn btn-dark btn-sm" type="submit" data-action="topup" disabled>
                                <i class="icon-base bx bx-bxs-file-plus"></i> Add salary top ups
                            </button>
                            <span class="small" id="masterSelectionText">No salary selected.</span>
                            <a href="#" class="small text-danger d-none" id="masterClear">Clear selection</a>
                        </div>
                        @endif

                        <div class="table-responsive text-nowrap">
                            <table id="myTableimaster" class="display"
                                   data-source="{{ route('salaries.salariesMonthData') }}"
                                   data-export="{{ route('salaries.salariesMonthExport') }}"
                                   data-month="{{ $month->format('Y-m') }}"
                                   data-can-edit="{{ $canEditMaster ? 1 : 0 }}">
                                <thead>
                                    <tr>
                                        <th>@if($canEditMaster)<input class="form-check-input" type="checkbox" id="masterOptions" title="Select all on this page" />@endif</th>
                                        <th> Action </th>
                                        <th> Payment Status</th>
                                        <th> Hold Reason</th>
                                        <th> Category </th>
                                        <th> id</th>
                                        <th> Salary Month </th>
                                        <th> employee_id </th>
                                        <th> Name</th>
                                        <th> Department </th>
                                        <th> Role</th>
                                        <th> Field </th>
                                        <th> Emp. Type </th>
                                        <th> Client </th>
                                        <th> Location </th>
                                        <th> Inv. Status </th>
                                        <th> SSNIT No.</th>
                                        <th> TIN No.</th>
                                        <th> Payment Type</th>
                                        <th> Bank Name </th>
                                        <th> Branch </th>
                                        <th> Account No.</th>
                                        <th> Basic Salary</th>
                                        <th> Allowances</th>
                                        <th> airtime_allowance</th>
                                        <th> overtime</th>
                                        <th> reimbursements </th>
                                        <th> transport_allowance</th>
                                        <th> ssnit_tier2_5</th>
                                        <th> ssnit_tier2_5d</th>
                                        <th> tax</th>
                                        <th> ssnit_tier1_0_5</th>
                                        <th> welfare </th>
                                        <th> maintenance</th>
                                        <th> absent</th>
                                        <th> boot</th>
                                        <th> iou</th>
                                        <th> hostel</th>
                                        <th> insurance</th>
                                        <th> reprimand</th>
                                        <th> scouter </th>
                                        <th> raincoat </th>
                                        <th> meal </th>
                                        <th> loan </th>
                                        <th> walkin </th>
                                        <th> amnt_ded_cof_start_date</th>
                                        <th> other_deductions</th>
                                        <th> gross_salary </th>
                                        <th> total_deductions</th>
                                        <th> net_salary </th>
                                        <th> ssnit_comp_cont_13 </th>
                                        <th> ssnit_tobe_paid13_5</th>
                                        <th> cost_to_company </th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">{{-- Loaded page by page by the server-side DataTable. --}}</tbody>
                            </table>
                        </div>
                    </form>
                </div>

                <div class="tab-pane fade" id="navs-pills-justified-hold" role="tabpanel">
                   
                             <div class="nav-align-top">
                                <ul class="nav nav-pills mb-4 nav-fill" role="tablist">
                                    
                                    <li class="nav-item mb-1 mb-sm-0">
                                        <button
                                            type="button"
                                            class="nav-link"
                                            role="tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#navs-pills-justified-categoryahold"
                                            aria-controls="navs-pills-justified-categoryahold"
                                            aria-selected="true">
                                            <span class="d-none d-sm-inline-flex align-items-center">
                                                <i class="icon-base bx bx-home icon-sm me-1_5"></i>CATEGORY A HOLD
                                                <span class="badge rounded-pill bg-danger ms-1_5"> {{ $clientAHold->count() }} </span>
                                            </span>
                                            <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                                        </button>
                                    </li>

                                    <li class="nav-item mb-1 mb-sm-0">
                                        <button
                                            type="button"
                                            class="nav-link"
                                            role="tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#navs-pills-justified-categorybhold"
                                            aria-controls="navs-pills-justified-categorybhold"
                                            aria-selected="true">
                                            <span class="d-none d-sm-inline-flex align-items-center">
                                                <i class="icon-base bx bx-home icon-sm me-1_5"></i>CATEGORY B HOLD
                                                <span class="badge rounded-pill bg-danger ms-1_5"> {{ $clientBHold->count() }} </span>
                                            </span>
                                            <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                                        </button>
                                    </li>

                                    <li class="nav-item mb-1 mb-sm-0">
                                        <button
                                            type="button"
                                            class="nav-link"
                                            role="tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#navs-pills-justified-categorychold"
                                            aria-controls="navs-pills-justified-categorychold"
                                            aria-selected="true">
                                            <span class="d-none d-sm-inline-flex align-items-center">
                                                <i class="icon-base bx bx-home icon-sm me-1_5"></i>CATEGORY C HOLD
                                                <span class="badge rounded-pill bg-danger ms-1_5"> {{ $clientCHold->count() }} </span>
                                            </span>
                                            <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                                        </button>
                                    </li>

                                    <li class="nav-item mb-1 mb-sm-0">
                                        <button
                                            type="button"
                                            class="nav-link"
                                            role="tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#navs-pills-justified-categorydhold"
                                            aria-controls="navs-pills-justified-categorydhold"
                                            aria-selected="true">
                                            <span class="d-none d-sm-inline-flex align-items-center">
                                                <i class="icon-base bx bx-home icon-sm me-1_5"></i>CATEGORY D HOLD
                                                <span class="badge rounded-pill bg-danger ms-1_5"> {{ $clientDHold->count() }} </span>
                                            </span>
                                            <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                                        </button>
                                    </li>

                                    <li class="nav-item mb-1 mb-sm-0">
                                        <button
                                            type="button"
                                            class="nav-link"
                                            role="tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#navs-pills-justified-clientmasterhold"
                                            aria-controls="navs-pills-justified-clientmasterhold"
                                            aria-selected="true">
                                            <span class="d-none d-sm-inline-flex align-items-center">
                                                <i class="icon-base bx bx-home icon-sm me-1_5"></i>CLIENT MASTER HOLD
                                                <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesClientsHold->count() }} </span>
                                            </span>
                                            <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                                        </button>
                                    </li>

                                </ul>
                             </div>

                             <div class="tab-content">
                                
                                <div class="tab-pane fade " id="navs-pills-justified-categoryahold" role="tabpanel">
                                    <div class="table-responsive">
                                        <table id="myTablecategoryahold" class="display">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Client Name</th>
                                                    <th>Field</th>
                                                    <th>Inv Value</th>
                                                    <th>Inv Status</th>
                                                    <th>Net Salary</th>
                                                    <th> Difference </th>
                                                    <th>Status</th>
                                                    <th>Employees</th>
                                                    <th> View Employees</th>

                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">
                                                @foreach($clientAHold as $keyah => $catAHold)
                                                <tr>
                                                    <td> {{ $keyah + 1 }} </td>
                                                    <td> {{ $catAHold->client?->name }} {{ $catAHold->client?->business_name }} </td>
                                                    <td> {{ $catAHold->client?->field?->name }} </td>

                                                    <td> GH&#x20B5; {{ number_format( $catAHold->client?->invoices->sum('total'),2)  }} </td>
                                                    <td>  {{ $catAHold->client?->invoices->pluck('status') }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catAHold->net_salary, 2) }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catAHold->client?->invoices->sum('total') - $catAHold->net_salary, 2) }} </td>
                                                
                                                    @if( $catAHold->client?->invoices->sum('total') <= $catAHold->net_salary )
                                                        <td><span class="badge bg-label-danger"> Loss </span></td>
                                                    @else
                                                        <td><span class="badge bg-label-success"> Profit </span></td>
                                                    @endif
                                                
                                                    <td> {{ $catAHold->total_employees }} </td>
                                                    <td> 
                                                        <a href="/salariesClientHoldMonth/{{$catAHold->client_id}} / {{$month}} " class="btn btn-dark btn-sm">
                                                            <i class="bx bx-show"></i> 
                                                        </a>    
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade " id="navs-pills-justified-categorybhold" role="tabpanel">
                                    <div class="table-responsive">
                                        <table id="myTablecategorybhold" class="display">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Client Name</th>
                                                    <th>Field</th>
                                                    <th>Inv Value</th>
                                                    <th>Inv Status</th>
                                                    <th>Net Salary</th>
                                                    <th> Difference </th>
                                                    <th>Status</th>
                                                    <th>Employees</th>
                                                    <th> View Employees</th>
                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">
                                                @foreach($clientBHold as $keybh => $catBHold)
                                                <tr>
                                                   <td> {{ $keybh + 1 }} </td>
                                                    <td> {{ $catBHold->client?->name }} {{ $catBHold->client?->business_name }} </td>
                                                    <td> {{ $catBHold->client?->field?->name }} </td>

                                                    <td> GH&#x20B5; {{ number_format( $catBHold->client?->invoices->sum('total'),2)  }} </td>
                                                    <td>  {{ $catBHold->client?->invoices->pluck('status') }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catBHold->net_salary, 2) }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catBHold->client?->invoices->sum('total') - $catBHold->net_salary, 2) }} </td>
                                                
                                                    @if( $catBHold->client?->invoices->sum('total') <= $catBHold->net_salary )
                                                        <td><span class="badge bg-label-danger"> Loss </span></td>
                                                    @else
                                                        <td><span class="badge bg-label-success"> Profit </span></td>
                                                    @endif
                                                
                                                    <td> {{ $catBHold->total_employees }} </td>
                                                    <td> 
                                                        <a href="/salariesClientHoldMonth/{{$catBHold->client_id}} / {{$month}} " class="btn btn-dark btn-sm">
                                                            <i class="bx bx-show"></i> 
                                                        </a>    
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>


                                <div class="tab-pane fade " id="navs-pills-justified-categorychold" role="tabpanel">
                                    <div class="table-responsive">
                                        <table id="myTablecategorychold" class="display">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Client Name</th>
                                                    <th>Field</th>
                                                    <th>Inv Value</th>
                                                    <th>Inv Status</th>
                                                    <th>Net Salary</th>
                                                    <th> Difference </th>
                                                    <th>Status</th>
                                                    <th>Employees</th>
                                                    <th> View Employees</th>
                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">
                                                @foreach($clientCHold as $keych => $catCHold)
                                                <tr>
                                                   <td> {{ $keych + 1 }} </td>
                                                    <td> {{ $catCHold->client?->name }} {{ $catCHold->client?->business_name }} </td>
                                                    <td> {{ $catCHold->client?->field?->name }} </td>

                                                    <td> GH&#x20B5; {{ number_format( $catCHold->client?->invoices->sum('total'),2)  }} </td>
                                                    <td>  {{ $catCHold->client?->invoices->pluck('status') }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catCHold->net_salary, 2) }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catCHold->client?->invoices->sum('total') - $catCHold->net_salary, 2) }} </td>
                                                
                                                    @if( $catCHold->client?->invoices->sum('total') <= $catCHold->net_salary )
                                                        <td><span class="badge bg-label-danger"> Loss </span></td>
                                                    @else
                                                        <td><span class="badge bg-label-success"> Profit </span></td>
                                                    @endif
                                                
                                                    <td> {{ $catCHold->total_employees }} </td>
                                                    <td> 
                                                        <a href="/salariesClientHoldMonth/{{$catCHold->client_id}} / {{$month}} " class="btn btn-dark btn-sm">
                                                            <i class="bx bx-show"></i> 
                                                        </a>    
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>


                                <div class="tab-pane fade " id="navs-pills-justified-categorydhold" role="tabpanel">
                                    <div class="table-responsive">
                                        <table id="myTablecategorydhold" class="display">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Client Name</th>
                                                    <th>Field</th>
                                                    <th>Inv Value</th>
                                                    <th>Inv Status</th>
                                                    <th>Net Salary</th>
                                                    <th> Difference </th>
                                                    <th>Status</th>
                                                    <th>Employees</th>
                                                    <th> View Employees</th>
                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">
                                                @foreach($clientDHold as $keydh => $catDHold)
                                                <tr>
                                                   <td> {{ $keydh + 1 }} </td>
                                                    <td> {{ $catDHold->client?->name }} {{ $catDHold->client?->business_name }} </td>
                                                    <td> {{ $catDHold->client?->field?->name }} </td>

                                                    <td> GH&#x20B5; {{ number_format( $catDHold->client?->invoices->sum('total'),2)  }} </td>
                                                    <td>  {{ $catDHold->client?->invoices->pluck('status') }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catDHold->net_salary, 2) }} </td>
                                                    <td> GH&#x20B5; {{ number_format($catDHold->client?->invoices->sum('total') - $catDHold->net_salary, 2) }} </td>
                                                
                                                    @if( $catDHold->client?->invoices->sum('total') <= $catDHold->net_salary )
                                                        <td><span class="badge bg-label-danger"> Loss </span></td>
                                                    @else
                                                        <td><span class="badge bg-label-success"> Profit </span></td>
                                                    @endif
                                                
                                                    <td> {{ $catDHold->total_employees }} </td>
                                                    <td> 
                                                        <a href="/salariesClientHoldMonth/{{$catDHold->client_id}} / {{$month}} " class="btn btn-dark btn-sm">
                                                            <i class="bx bx-show"></i> 
                                                        </a>    
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            
                                <div class="tab-pane fade " id="navs-pills-justified-clientmasterhold" role="tabpanel">
                                    <div class="table-responsive">
                                        <table id="myTableiclientmasterhold" class="display">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Client Name</th>
                                                    <th>Field</th>
                                                    <th>Inv Value</th>
                                                    <th>Inv Status</th>
                                                    <th>Net Salary</th>
                                                    <th> Difference </th>
                                                    <th>Status</th>
                                                    <th>Employees</th>
                                                    <th> View Employees</th>
                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">
                                                @foreach($salariesClientsHold as $cm => $clientMasterHold)
                                                <tr>
                                                    <td> {{ $cm + 1 }} </td>
                                                    <td> {{ $clientMasterHold->client?->name }} {{ $clientMasterHold->client?->business_name }} </td>
                                                    <td> {{ $clientMasterHold->client?->field?->name }} </td>

                                                    <td> GH&#x20B5; {{ number_format( $clientMasterHold->client?->invoices->sum('total'),2)  }} </td>
                                                    <td>  {{ $clientMasterHold->client?->invoices->pluck('status') }} </td>
                                                    <td> GH&#x20B5; {{ number_format($clientMasterHold->paid, 2) }} </td>
                                                    <td> GH&#x20B5; {{ number_format($clientMasterHold->client?->invoices->sum('total') - $clientMasterHold->paid, 2) }} </td>
                                                
                                                    @if( $clientMasterHold->client?->invoices->sum('total') <= $clientMasterHold->paid )
                                                        <td><span class="badge bg-label-danger"> Loss </span></td>
                                                    @else
                                                        <td><span class="badge bg-label-success"> Profit </span></td>
                                                    @endif
                                                
                                                    <td> {{ $clientMasterHold->total_employees }} </td>
                                                    <td> 
                                                        <a href="/salariesClientHoldMonth/{{$clientMasterHold->client_id}} / {{$month}} " class="btn btn-dark btn-sm">
                                                            <i class="bx bx-show"></i> 
                                                        </a>    
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

        <br> <br>

        <!-- cards -->

        <div class="row">

            <div class="col-lg-2">
                <div  class="card bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong> TAX </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($salariesTaxes->sum('tax'), 2) }} </strong> </h4> 
                        <small class="fw-medium"> EMPLOYEES : {{ $salariesTaxes->sum('total_employees') }} </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2" >
                <div  class="card bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong> PENSIONS </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($salariesPensions->sum('cont13_5'), 2) }} </strong> </h4> 
                        <small class="fw-medium"> EMPLOYEES : {{ $salariesPensions->sum('total_employees') }} </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div  class="card bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong> BOOTS </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($salariesBoots->sum('boot'), 2) }} </strong> </h4> 
                        <small class="fw-medium"> EMPLOYEES : {{ $salariesBoots->sum('total_employees') }} </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div  class="card bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong> OVERTIME </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($salariesOvertime->sum('overtime'), 2) }} </strong> </h4> 
                        <small class="fw-medium"> EMPLOYEES : {{ $salariesOvertime->sum('total_employees') }} </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-2">
                <div  class="card bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong> IOU </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($salariesIOU->sum('iou'), 2) }} </strong> </h4> 
                        <small class="fw-medium"> EMPLOYEES : {{ $salariesIOU->sum('total_employees') }} </small>
                    </div>
                </div>
            </div>  

            <div class="col-lg-2">
                <div  class="card bg-dark text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong> ABSENT </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($salariesAbsent->sum('absent'), 2) }} </strong> </h4> 
                        <small class="fw-medium"> EMPLOYEES : {{ $salariesAbsent->sum('total_employees') }} </small>
                    </div>
                </div>
            </div>  

            <div class="col-lg-2 mt-2">
                <div  class="card bg-dark h-100 text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong> AMNT DEDUCTED START DATE </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($salariesAmtdedstart->sum('sDate_ded'), 2) }} </strong> </h4> 
                        <small class="fw-medium"> EMPLOYEES : {{ $salariesAmtdedstart->sum('total_employees') }} </small>
                    </div>
                </div>
            </div>  

            <div class="col-lg-2 mt-2">
                <div  class="card bg-dark h-100 text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong> OTHER DEDUCTION </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($salariesOtherDed->sum('odeduct'), 2) }} </strong> </h4> 
                        <small class="fw-medium"> EMPLOYEES : {{ $salariesOtherDed->sum('total_employees') }} </small>
                    </div>
                </div>
            </div>  

            <div class="col-lg-2 mt-2">
                <div  class="card bg-dark h-100 text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong> REPRIMAND </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($salariesReprimand->sum('reprimand'), 2) }} </strong> </h4> 
                        <small class="fw-medium"> EMPLOYEES : {{ $salariesReprimand->sum('total_employees') }} </small>
                    </div>
                </div>
            </div>  

            <div class="col-lg-2 mt-2">
                <div  class="card bg-dark h-100 text-white">
                    <div class="card-body">
                        <p class="mb-1"><strong> LOAN </strong></p>
                        <h4 class="card-title mb-3 text-white"><strong> &#x20B5; {{ number_format($salariesLoan->sum('loan'), 2) }} </strong> </h4> 
                        <small class="fw-medium"> EMPLOYEES : {{ $salariesLoan->sum('total_employees') }} </small>
                    </div>
                </div>
            </div>  

        </div>

        <!-- end cards -->



    <!-- Table -->  
         <br> 
        <div class="nav-align-top">
            <ul class="nav nav-pills mb-4 nav-fill" role="tablist">

                @if(Auth::user()->hasRole(['Finance Manager', 'Invoice', 'Officer',  'Internal Auditor', 'Manager']) )


                <li class="nav-item mb-1 mb-sm-0">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-tax"
                        aria-controls="navs-pills-justified-tax"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>Tax
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesTaxes->count() }} </span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>

                <li class="nav-item mb-1 mb-sm-0">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-pensions"
                        aria-controls="navs-pills-justified-pensions"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>Pensions
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesPensions->count() }}</span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>

                <li class="nav-item mb-1 mb-sm-0">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-overtime"
                        aria-controls="navs-pills-justified-overtime"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>Overtime
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesOvertime->count() }}</span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>

                <li class="nav-item mb-1 mb-sm-0">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-iou"
                        aria-controls="navs-pills-justified-iou"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>IOU
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesIOU->count() }}</span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>

                <li class="nav-item mb-1 mb-sm-0">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-boot"
                        aria-controls="navs-pills-justified-boot"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>Boots
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesBoots->count() }}</span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>

                <li class="nav-item mb-1 mb-sm-0">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-absent"
                        aria-controls="navs-pills-justified-absent"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>Absent
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesAbsent->count() }}</span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>

                <li class="nav-item mb-1 mb-sm-0">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-sDate_ded"
                        aria-controls="navs-pills-justified-sDate_ded"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>S.Date
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesAmtdedstart->count() }}</span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>

                <li class="nav-item mb-1 mb-sm-0">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-odeduct"
                        aria-controls="navs-pills-justified-odeduct"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>O.Ded
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesOtherDed->count() }}</span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>

                <li class="nav-item mb-1 mb-sm-0">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-reprimand"
                        aria-controls="navs-pills-justified-reprimand"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>Reprimand
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesReprimand->count() }}</span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>

                <li class="nav-item mb-1 mb-sm-0">
                    <button
                        type="button"
                        class="nav-link"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#navs-pills-justified-loan"
                        aria-controls="navs-pills-justified-loan"
                        aria-selected="false">
                        <span class="d-none d-sm-inline-flex align-items-center"><i class="icon-base bx bx-home icon-sm me-1_5"></i>Loan
                            <span class="badge rounded-pill bg-danger ms-1_5"> {{ $salariesLoan->count() }}</span>
                        </span>
                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                    </button>
                </li>
                @endif

            </ul>


            <div class="tab-content">

                <div class="tab-pane fade" id="navs-pills-justified-tax" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                        <table id="myTableiShyhills" class="display">
                            <thead>
                                <tr>
                                   
                                    <th>#</th>
                                    <th>Field</th>
                                    <th>Net Salary</th>
                                    <th>Total Tax</th>
                                    <th>Employees</th>
                                    <th>View Employees</th>
                                   
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                               @foreach ($salariesTaxes as $key => $taxes)
                                   <tr>
                                    <td> {{ $key + 1 }} </td>
                                    <td>{{ $taxes->field?->name }}</td>
                                    <td> GH&#x20B5; {{ number_format($taxes->paid, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($taxes->tax, 2) }} </td>
                                    <td> {{ $taxes->total_employees }} </td>
                                    <td> 
                                        <a href="/salariesTaxMonth/{{ $taxes->field?->id }}/{{ $month->format('F Y') }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a>    
                                    </td>   
                                </tr>
                                 @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>

                <div class="tab-pane fade" id="navs-pills-justified-pensions" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                        <table id="myTableiTema" class="display">
                            <thead>
                                <tr>
                                   
                                    <th>#</th>
                                    <th>Field</th>
                                    <th>Tier 1</th>
                                    <th>Tier 2</th>
                                    <th>Net Salary</th>
                                    <th>Cont 13</th>
                                    <th>Cont 13.5</th>
                                    <th>Employees</th>
                                    <th> View Employees</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                              @foreach ($salariesPensions as $key => $pensions)
                                   <tr>
                                    <td> {{ $key + 1 }} </td>
                                    <td>{{ $pensions->field?->name }}</td>
                                    <td> GH&#x20B5; {{ number_format($pensions->tier1, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($pensions->tier2, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($pensions->paid, 2) }}</td>
                                    <td> GH&#x20B5; {{ number_format($pensions->cont13, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($pensions->cont13_5, 2) }} </td>
                                    <td> {{ $pensions->total_employees }} </td>
                                    <td> 
                                        <a href="/salariesPensionMonth/{{ $pensions->field?->id }}/{{ $month->format('F Y') }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a>    
                                    </td>   
                                </tr>
                                    @endforeach 
                            </tbody>
                        </table>
                    </div>
                </div>


                <div class="tab-pane fade" id="navs-pills-justified-overtime" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                        <table id="myTableiOvertime" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Field</th>
                                    <th>Overtime</th>
                                    <th>Net Salary</th>
                                    <th>Employees</th>
                                    <th> View Employees</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                              @foreach ($salariesOvertime as $key => $overtime)
                                   <tr>
                                    <td> {{ $key + 1 }} </td>
                                    <td>{{ $overtime->field?->name }}</td>
                                    <td> GH&#x20B5; {{ number_format($overtime->overtime, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($overtime->paid, 2) }}</td>
                                    <td> {{ $overtime->total_employees }} </td>
                                    <td> 
                                        <a href="/salariesOvertimeMonth/{{ $overtime->field?->id }}/{{ $month->format('F Y') }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a>    
                                    </td>   
                                </tr>
                                    @endforeach 
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="navs-pills-justified-iou" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                        <table id="myTableiIou" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Field</th>
                                    <th>IOU</th>
                                    <th>Net Salary</th>
                                    <th>Employees</th>
                                    <th> View Employees</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                              @foreach ($salariesIOU as $key => $iou)
                                   <tr>
                                    <td> {{ $key + 1 }} </td>
                                    <td>{{ $iou->field?->name }}</td>
                                    <td> GH&#x20B5; {{ number_format($iou->iou, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($iou->paid, 2) }}</td>
                                    <td> {{ $iou->total_employees }} </td>
                                    <td> 
                                        <a href="/salariesIouMonth/{{ $iou->field?->id }}/{{ $month->format('F Y') }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a>    
                                    </td>   
                                </tr>
                                    @endforeach 
                            </tbody>
                        </table>
                    </div>
                </div>


                <div class="tab-pane fade" id="navs-pills-justified-boot" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                        <table id="myTableiboot" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Field</th>
                                    <th>Boots</th>
                                    <th>Net Salary</th>
                                    <th>Employees</th>
                                    <th> View Employees</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                              @foreach ($salariesBoots as $key => $boots)
                                   <tr>
                                    <td> {{ $key + 1 }} </td>
                                    <td>{{ $boots->field?->name }}</td>
                                    <td> GH&#x20B5; {{ number_format($boots->boot, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($boots->paid, 2) }}</td>
                                    <td> {{ $boots->total_employees }} </td>
                                    <td> 
                                        <a href="/salariesBootMonth/{{ $boots->field?->id }}/{{ $month->format('F Y') }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a>    
                                    </td>   
                                </tr>
                                    @endforeach 
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="navs-pills-justified-absent" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                        <table id="myTableiabsent" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Field</th>
                                    <th>Absent</th>
                                    <th>Net Salary</th>
                                    <th>Employees</th>
                                    <th> View Employees</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                              @foreach ($salariesAbsent as $key => $absent)
                                   <tr>
                                    <td> {{ $key + 1 }} </td>
                                    <td>{{ $absent->field?->name }}</td>
                                    <td> GH&#x20B5; {{ number_format($absent->absent, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($absent->paid, 2) }}</td>
                                    <td> {{ $absent->total_employees }} </td>
                                    <td> 
                                        <a href="/salariesAbsentMonth/{{ $absent->field?->id }}/{{ $month->format('F Y') }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a>    
                                    </td>   
                                </tr>
                                    @endforeach 
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="navs-pills-justified-sDate_ded" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                        <table id="myTableisDate_ded" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Field</th>
                                    <th>Start Date Ded</th>
                                    <th>Net Salary</th>
                                    <th>Employees</th>
                                    <th> View Employees</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                              @foreach ($salariesAmtdedstart as $key => $sdate)
                                   <tr>
                                    <td> {{ $key + 1 }} </td>
                                    <td>{{ $sdate->field?->name }}</td>
                                    <td> GH&#x20B5; {{ number_format($sdate->sDate_ded, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($sdate->paid, 2) }}</td>
                                    <td> {{ $sdate->total_employees }} </td>
                                    <td> 
                                        <a href="/salariessDateMonth/{{ $sdate->field?->id }}/{{ $month->format('F Y') }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a>    
                                    </td>   
                                </tr>
                                    @endforeach 
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="navs-pills-justified-odeduct" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                        <table id="myTableiodeduct" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Field</th>
                                    <th>Other Ded</th>
                                    <th>Net Salary</th>
                                    <th>Employees</th>
                                    <th> View Employees</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                              @foreach ($salariesOtherDed as $key => $odeduct)
                                   <tr>
                                    <td> {{ $key + 1 }} </td>
                                    <td>{{ $odeduct->field?->name }}</td>
                                    <td> GH&#x20B5; {{ number_format($odeduct->odeduct, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($odeduct->paid, 2) }}</td>
                                    <td> {{ $odeduct->total_employees }} </td>
                                    <td> 
                                        <a href="/salariesOdedMonth/{{ $odeduct->field?->id }}/{{ $month->format('F Y') }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a>    
                                    </td>   
                                </tr>
                                    @endforeach 
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="navs-pills-justified-reprimand" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                        <table id="myTableireprimand" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Field</th>
                                    <th>Reprimand</th>
                                    <th>Net Salary</th>
                                    <th>Employees</th>
                                    <th> View Employees</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                              @foreach ($salariesReprimand as $key => $reprimand)
                                   <tr>
                                    <td> {{ $key + 1 }} </td>
                                    <td>{{ $reprimand->field?->name }}</td>
                                    <td> GH&#x20B5; {{ number_format($reprimand->reprimand, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($reprimand->paid, 2) }}</td>
                                    <td> {{ $reprimand->total_employees }} </td>
                                    <td> 
                                        <a href="/salariesReprimandMonth/{{ $reprimand->field?->id }}/{{ $month->format('F Y') }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a>    
                                    </td>   
                                </tr>
                                    @endforeach 
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="navs-pills-justified-loan" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                        <table id="myTableiloan" class="display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Field</th>
                                    <th>Loan</th>
                                    <th>Net Salary</th>
                                    <th>Employees</th>
                                    <th> View Employees</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                              @foreach ($salariesLoan as $key => $loan)
                                   <tr>
                                    <td> {{ $key + 1 }} </td>
                                    <td>{{ $loan->field?->name }}</td>
                                    <td> GH&#x20B5; {{ number_format($loan->loan, 2) }} </td>
                                    <td> GH&#x20B5; {{ number_format($loan->paid, 2) }}</td>
                                    <td> {{ $loan->total_employees }} </td>
                                    <td> 
                                        <a href="/salariesLoanMonth/{{ $loan->field?->id }}/{{ $month->format('F Y') }}" class="btn btn-dark btn-sm">
                                            <i class="bx bx-show"></i> 
                                        </a>    
                                    </td>   
                                </tr>
                                    @endforeach 
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>



        <hr> <br> 
        <h4> Salary TopUps </h4>
            <div class="row">
                <div class="card">
                    <h5 class="card-header"> Salaries Top Up via MoMo  </h5>
                    <div class="card-body">
                        <form action="{{ route('salariestopups.store') }}" method="POST">
                            @csrf
                            <div class="table-responsive text-nowrap">

                                <input type="hidden" name="topups_action_type" id="topups_action_type" value="" />
                                 @if(Auth::user()->hasRole(['Finance Manager']) )
                                <input class="form-check-input form-check-inline" type="checkbox" value="" id="topups" />

                                <div class="form-check form-check-inline">
                                    <button class="btn btn-success"  onclick="document.getElementById('topups_action_type').value='topup'; return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-bxs-file-plus"> </i> {{ __('Approve Top Up') }}</button>                   
                                </div>

                                <div class="form-check form-check-inline">
                                    <button class="btn btn-dark "  onclick="document.getElementById('topups_action_type').value='reverse_topup'; return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-bxs-file-plus"> </i> {{ __('Reverse Top Up') }}</button>                   
                                </div>

                                <div class="form-check form-check-inline">
                                    <button class="btn btn-primary"  onclick="document.getElementById('topups_action_type').value='save'; return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-bxs-file-plus"> </i> {{ __('Save') }}</button>                   
                                </div>
                                <div class="form-check form-check-inline">
                                    <button class="btn btn-danger m-15"  onclick="document.getElementById('topups_action_type').value='delete_topup'; return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-bxs-file-plus"> </i> {{ __('Delete') }}</button>                   
                                </div>
                                @endif
                                <table id="topupsTT" class="display">
                                    <thead>
                                        <tr>
                                            <th> </th>
                                            <th>#</th>
                                            <th>Employee ID</th>
                                            <th>Status</th>
                                            <th>Month</th>
                                            <th>Name</th>
                                            <th>Reason</th>
                                            <th>Payment Type</th>
                                            <th> Top Up Amount</th>
                                            <th>GH&#x20B5; Net</th>
                                            <th>Field </th>
                                            <th>Client</th>
                                            <th>Location</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                    @foreach($topUpSalaries as $key => $topup)
                                        <tr class="{{ $topup->status === 'approved' ? 'table-secondary text-muted' : '' }}">
                                            <td> <input class="checkBoxestopups form-check-input" type="checkbox" name="salary[{{ $topup->id }}]" value="{{  $topup->id }}"  @disabled($topup->status === 'approved' ?? 'saved' )/> </td>
                                            <td> {{$key + 1}} </td>
                                            <td> FWSS {{ $topup->salary?->employee_id }} </td>
                                            <td> 
                                                @if($topup->status == 'pending' )
                                                    {{ $topup->status_date2?->format('F l d, Y') }} <br>
                                                    By {{  $topup->user2?->name }} <br>
                                                   <span class="badge bg-label-info">{{ $topup->status }} </span>
                                                @elseif( $topup->status == 'reversed')
                                                    {{ $topup->status_date2?->format('F l d, Y') }} <br>
                                                    By {{  $topup->user2?->name }} <br>
                                                   <span class="badge bg-label-dark">{{ $topup->status }} </span>
                                                @elseif($topup->status == 'saved')
                                                    {{ $topup->status_date?->format('F l d, Y') }} <br>
                                                    By {{  $topup->user1?->name }} <br>
                                                   <span class="badge bg-label-primary">{{ $topup->status }} </span>
                                                @else
                                                    {{ $topup->status_date?->format('F l d, Y') }} <br>
                                                    By {{  $topup->user1?->name }} <br>
                                                    <span class="badge bg-label-success"> {{ $topup->status }} </span>

                                                @endif

                                            </td>

                                            <td> {{ $topup->salary_month?->format('F, Y') }} </td>
                                            <td> {{ $topup->salary?->employee?->name }} </td>
                                            <td> 
                                                @if($topup->status == 'approved' || $topup->status == 'saved')
                                                {{ $topup->reason }}
                                                @else
                                                <textarea name="reason[{{ $topup->id }}]" id="reason" class="form-control @error('reason') is-invalid @enderror" > {{$topup->reason}} </textarea> 
                                               @endif
                                                @error('reason')
                                                <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                                </span>
                                                @enderror
                                            </td>
                                            <td> 
                                                @if($topup->status == 'approved' || $topup->status == 'saved')
                                                    MoMo
                                                @else
                                                <select name="payment_type[{{ $topup->id }}]" class="form-select @error('payment_type') is-invalid @enderror" id="payment_type">
                                                
                                                    <!-- <option default selected disabled>Choose...</option> -->
                                                    <!-- <option @if($topup->payment_type == "Bank") selected @endif value="Bank">Bank</option> -->
                                                    <option  selected default value="Cash">MoMo</option>
                                                
                                                </select>  
                                                @endif
                                                @error('payment_type')
                                                <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                                </span>
                                                @enderror
                                            </td>
                                            <td> 
                                                @if($topup->status == 'approved' || $topup->status == 'saved')
                                                {{ number_format($topup->top_up_amount, 2) }}
                                                @else
                                                <input type="text" name="top_up_amount[{{ $topup->id }}]" class="form-control @error('top_up_amount') is-invalid @enderror" id="top_up_amount" step="any" placeholder=" GH&#x20B5; " value="{{ $topup->top_up_amount}}" >
                                               @endif
                                                @error('top_up_amount')
                                                <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                                </span>
                                                @enderror
                                            </td>
                                            <td>  {{ number_format($topup->salary?->net_salary, 2) }} </td>
                                            <td> {{ $topup->salary?->field?->name }} </td>
                                            <td> {{ $topup->salary?->client?->name }} {{ $topup->salary?->client?->business_name }}</td>
                                            <td> {{ $topup->salary?->location }} </td>
 
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <hr> <br>

        <h4> Salary Summary</h4>
        <div class="row">
            <!-- FIELD OFFICES SUMMARY -->
            <div class="col-lg-6">
                    <div class="nav-align-top">
                        <ul class="nav nav-pills mb-4 nav-fill" role="tablist">
                                <li class="nav-item mb-1 mb-sm-0">
                                    <button
                                        type="button"
                                        class="nav-link active"
                                        role="tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#navs-pills-justified-accrasummary"
                                        aria-controls="navs-pills-justified-accrasummary"
                                        aria-selected="false">
                                        <span class="d-none d-sm-inline-flex align-items-center">
                                            <i class="icon-base bx bx-home icon-sm me-1_5"></i>ACCRA
                                            <span class="badge rounded-pill bg-danger ms-1_5">  </span>
                                        </span>
                                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                                    </button>
                                </li>  
                                
                                <li class="nav-item mb-1 mb-sm-0">
                                    <button
                                        type="button"
                                        class="nav-link"
                                        role="tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#navs-pills-justified-botwesummary"
                                        aria-controls="navs-pills-justified-botwesummary"
                                        aria-selected="false">
                                        <span class="d-none d-sm-inline-flex align-items-center">
                                            <i class="icon-base bx bx-home icon-sm me-1_5"></i>BOTWE
                                            <span class="badge rounded-pill bg-danger ms-1_5">  </span>
                                        </span>
                                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                                    </button>
                                </li>
                                
                                <li class="nav-item mb-1 mb-sm-0">
                                    <button
                                        type="button"
                                        class="nav-link"
                                        role="tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#navs-pills-justified-temasummary"
                                        aria-controls="navs-pills-justified-temasummary"
                                        aria-selected="false">
                                        <span class="d-none d-sm-inline-flex align-items-center">
                                            <i class="icon-base bx bx-home icon-sm me-1_5"></i>TEMA
                                            <span class="badge rounded-pill bg-danger ms-1_5">  </span>
                                        </span>
                                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                                    </button>
                                </li>
                                
                                
                                <li class="nav-item mb-1 mb-sm-0">
                                    <button
                                        type="button"
                                        class="nav-link"
                                        role="tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#navs-pills-justified-shyhillssummary"
                                        aria-controls="navs-pills-justified-shyhillssummary"
                                        aria-selected="false">
                                        <span class="d-none d-sm-inline-flex align-items-center">
                                            <i class="icon-base bx bx-home icon-sm me-1_5"></i>SHAIHILLS
                                            <span class="badge rounded-pill bg-danger ms-1_5">  </span>
                                        </span>
                                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                                    </button>
                                </li> 

                                <li class="nav-item mb-1 mb-sm-0">
                                    <button
                                        type="button"
                                        class="nav-link"
                                        role="tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#navs-pills-justified-takoradisummary"
                                        aria-controls="navs-pills-justified-takoradisummary"
                                        aria-selected="false">
                                        <span class="d-none d-sm-inline-flex align-items-center">
                                            <i class="icon-base bx bx-home icon-sm me-1_5"></i>TAKORADI
                                            <span class="badge rounded-pill bg-danger ms-1_5">  </span>
                                        </span>
                                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                                    </button>
                                </li> 

                                <li class="nav-item mb-1 mb-sm-0">
                                    <button
                                        type="button"
                                        class="nav-link"
                                        role="tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#navs-pills-justified-koforiduasummary"
                                        aria-controls="navs-pills-justified-koforiduasummary"
                                        aria-selected="false">
                                        <span class="d-none d-sm-inline-flex align-items-center">
                                            <i class="icon-base bx bx-home icon-sm me-1_5"></i>KOFORIDUA
                                            <span class="badge rounded-pill bg-danger ms-1_5">  </span>
                                        </span>
                                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                                    </button>
                                </li> 

                                <li class="nav-item mb-1 mb-sm-0">
                                    <button
                                        type="button"
                                        class="nav-link"
                                        role="tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#navs-pills-justified-kumasisummary"
                                        aria-controls="navs-pills-justified-kumasisummary"
                                        aria-selected="false">
                                        <span class="d-none d-sm-inline-flex align-items-center">
                                            <i class="icon-base bx bx-home icon-sm me-1_5"></i>KUMASI
                                            <span class="badge rounded-pill bg-danger ms-1_5">  </span>
                                        </span>
                                        <i class="icon-base bx bx-home icon-sm d-sm-none"></i>
                                    </button>
                                </li> 

                        </ul>

                        <div class="tab-content">
                    
                            <div class="tab-pane fade show active" id="navs-pills-justified-accrasummary" role="tabpanel">
                                <!-- ACCRA -->
                                <div class="row">
                                    <div class="col h-100" >
                                        <div  class="card h-100 bg-dark text-white">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                                                    <div class="avatar flex-shrink-0">
                                                        <img
                                                            src="{{ asset('img/icons/unicons/paypal.png') }}"
                                                            alt="chart success"
                                                            class="rounded" />
                                                    </div>
                                                    <h3 class="card-title text-white"> <strong> ACCRA </strong>  </h3>
                                                </div>
                                                <div class="row">
                                                    <div class="col">
                                                        <!-- TOTAL -->
                                                        <p class="mb-1"><strong>  TOTAL SALARY </strong> </p>
                                                        <h4 class="card-title text-white"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 1)->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-white"><strong> NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 1)->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: -8px;"> <br>
                                                    
                                                        <!-- PAID -->
                                                        <p class="mb-1"><strong>  TOTAL  PAID </strong> </p>
                                                        <h4 class="card-title text-info"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 1)->where('payment_status', 'approved')->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-info"><strong> NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 1)->where('payment_status', 'approved')->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: -8px;"> <br>
                                                    
                                                        <!-- OUTSTANDING -->
                                                        <p class="mb-0"><strong>  TOTAL  OUTSTANDING </strong> </p>
                                                        <h4 class="card-title text-danger"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 1)->whereIn('payment_status', ['pending', 'hold', 'rejected'])->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-danger"><strong>  NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 1)->whereIn('payment_status', ['pending', 'hold', 'rejected'])->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: 210px;"> <br>
                                                   
                                                          <!--  MOMO TOP UPS -->
                                                        <p class="mb-0"><strong>  TOTAL  MOMO TOP UPS </strong> </p>
                                                        <h4 class="card-title text-primary"><strong> &#x20B5;  {{ number_format($topUpSalaries->where('field_id', 1)->sum('top_up_amount'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-primary"><strong>  NUMBER OF EMPLOYEES : {{ $topUpSalaries->where('field_id', 1)->count() }} </strong> </h6> 
                                                        <!-- <br> <hr style="margin-top: -8px;"> <br> -->
                                                   
                                                    </div>

                                                    <div class="col ">
                                                        <!-- TOTAL -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK AMOUNT  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO AMOUNT  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-white"><strong> {{ number_format($salariesMaster->where('field_id', 1)->where('payment_type', 'Bank')->sum('net_salary'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-white"><strong>  {{ number_format($salariesMaster->where('field_id', 1)->where('payment_type', 'Cash')->sum('net_salary'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 1)->where('payment_type', 'Bank')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 1)->where('payment_type', 'Cash')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <hr> <br>     
                                                        
                                                        <!-- PAID -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK PAID  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO PAID  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-info"><strong> {{ number_format($salariesMaster->where('field_id', 1)->where('payment_type', 'Bank')->where('payment_status', 'approved')->sum('net_salary'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-info"><strong>  {{ number_format($salariesMaster->where('field_id', 1)->where('payment_type', 'Cash')->where('payment_status', 'approved')->sum('net_salary'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-info"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 1)->where('payment_type', 'Bank')->where('payment_status', 'approved')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-info"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 1)->where('payment_type', 'Cash')->where('payment_status', 'approved')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <hr> <br>  

                                                        <!-- OUTSTANDING -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK OUTSTD'N  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO OUTSTD'N  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-danger"><strong> {{ number_format($salariesMaster->where('field_id', 1)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->sum('net_salary'), 2) }}  </strong> </h4> <p> PENDING <br>{{ number_format($salariesMaster->where('field_id', 1)->where('payment_type', 'Bank')->where('payment_status', 'pending')->sum('net_salary'), 2) }}  </p> 
                                                                    <p>  HOLD <br> {{ number_format($salariesMaster->where('field_id', 1)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'rejected'])->sum('net_salary'), 2) }} </p>
                                                                </div> 
                                                                <div>  
                                                                    <h4 class="card-title text-danger"><strong>  {{ number_format($salariesMaster->where('field_id', 1)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->sum('net_salary'), 2) }} </strong> </h4> <p>  PENDING <br>{{ number_format($salariesMaster->where('field_id', 1)->where('payment_type', 'Cash')->where('payment_status', 'pending')->sum('net_salary'), 2) }}    </p> 
                                                                    <p>   HOLD <br> {{ number_format($salariesMaster->where('field_id', 1)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'rejected'])->sum('net_salary'), 2) }} </p>  
                                                                </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-danger"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 1)->where('payment_type', 'Bank')->whereIn('payment_status', ['pending','hold','rejected'])->count() }} </strong> </h6>  PENDING  <br>  {{ $salariesMaster->where('field_id', 1)->where('payment_type', 'Bank')->where('payment_status', 'pending')->count() }}
                                                                    <p>HOLD <br> {{ $salariesMaster->where('field_id', 1)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'rejected'])->count() }} </p>
                                                                </div>  
                                                                <div> <h6 class="card-title text-danger"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 1)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->count() }} </strong> </h6>  PENDING <br>  {{ $salariesMaster->where('field_id', 1)->where('payment_type', 'Cash')->where('payment_status', 'pending')->count() }}
                                                                    <p> HOLD <br> {{ $salariesMaster->where('field_id', 1)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'rejected'])->count() }} </p>
                                                                </div>  
                                                        </div> 
                                                        <hr> <br> 
                                                        <!-- MOMO TOP UPS -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-info">PAID  </strong>  </div>   | 
                                                                <div> <strong class="text-danger">OUTSTD'N  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-white"><strong> {{ number_format($topUpSalaries->where('field_id', 1)->where('status', 'approved')->sum('top_up_amount'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-white"><strong>  {{ number_format($topUpSalaries->where('field_id', 1)->where('status', 'saved')->sum('top_up_amount'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $topUpSalaries->where('field_id', 1)->where('status', 'approved')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $topUpSalaries->where('field_id', 1)->where('status', 'saved')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <!-- <hr> <br>   -->
                                                    
                                                    </div>





                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>
                           
                            </div>

                            <div class="tab-pane fade" id="navs-pills-justified-botwesummary" role="tabpanel">  

                                <!-- BOTWE -->
                                <div class="row">
                                    <div class="col h-100" >
                                        <div  class="card h-100 bg-dark text-white">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                                                    <div class="avatar flex-shrink-0">
                                                        <img
                                                            src="{{ asset('img/icons/unicons/paypal.png') }}"
                                                            alt="chart success"
                                                            class="rounded" />
                                                    </div>
                                                    <h3 class="card-title text-white"> <strong> BOTWE </strong>  </h3>
                                                </div>
                                                <div class="row">
                                                    <div class="col">
                                                        <!-- TOTAL -->
                                                        <p class="mb-1"><strong>  TOTAL SALARY </strong> </p>
                                                        <h4 class="card-title text-white"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 2)->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-white"><strong> &#x20B5; NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 2)->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: -8px;"> <br>
                                                    
                                                        <!-- PAID -->
                                                        <p class="mb-1"><strong>  TOTAL  PAID </strong> </p>
                                                        <h4 class="card-title text-info"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 2)->where('payment_status', 'approved')->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-info"><strong> &#x20B5; NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 2)->where('payment_status', 'approved')->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: -8px;"> <br>
                                                    
                                                        <!-- OUTSTANDING -->
                                                        <p class="mb-0"><strong>  TOTAL  OUTSTANDING </strong> </p>
                                                        <h4 class="card-title text-danger"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 2)->whereIn('payment_status', ['pending', 'hold', 'rejected'])->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-danger"><strong> &#x20B5; NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 2)->whereIn('payment_status', ['pending', 'hold', 'rejected'])->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: 200px;"> <br>
                                                   
                                                        <!--  MOMO TOP UPS -->
                                                        <p class="mb-0"><strong>  TOTAL  MOMO TOP UPS </strong> </p>
                                                        <h4 class="card-title text-primary"><strong> &#x20B5;  {{ number_format($topUpSalaries->where('field_id', 2)->sum('top_up_amount'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-primary"><strong>  NUMBER OF EMPLOYEES : {{ $topUpSalaries->where('field_id', 2)->count() }} </strong> </h6> 
                                                        <!-- <br> <hr style="margin-top: -8px;"> <br> -->
                                                    </div>

                                                    <div class="col ">
                                                        <!-- TOTAL -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK AMOUNT  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO AMOUNT  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-white"><strong> {{ number_format($salariesMaster->where('field_id', 2)->where('payment_type', 'Bank')->sum('net_salary'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-white"><strong>  {{ number_format($salariesMaster->where('field_id', 2)->where('payment_type', 'Cash')->sum('net_salary'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 2)->where('payment_type', 'Bank')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 2)->where('payment_type', 'Cash')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <hr> <br>     
                                                        
                                                        <!-- PAID -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK PAID  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO PAID  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-info"><strong> {{ number_format($salariesMaster->where('field_id', 2)->where('payment_type', 'Bank')->where('payment_status', 'approved')->sum('net_salary'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-info"><strong>  {{ number_format($salariesMaster->where('field_id', 2)->where('payment_type', 'Cash')->where('payment_status', 'approved')->sum('net_salary'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-info"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 2)->where('payment_type', 'Bank')->where('payment_status', 'approved')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-info"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 2)->where('payment_type', 'Cash')->where('payment_status', 'approved')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <hr> <br>  

                                                        <!-- OUTSTANDING -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK OUTSTD'N  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO OUTSTD'N  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-danger"><strong> {{ number_format($salariesMaster->where('field_id', 2)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->sum('net_salary'), 2) }}  </strong> </h4> <p> PENDING <br>{{ number_format($salariesMaster->where('field_id', 2)->where('payment_type', 'Bank')->where('payment_status', 'pending')->sum('net_salary'), 2) }}  </p> 
                                                                    <p>  HOLD <br> {{ number_format($salariesMaster->where('field_id', 2)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'rejected'])->sum('net_salary'), 2) }} </p>
                                                                </div> 
                                                                <div>  
                                                                    <h4 class="card-title text-danger"><strong>  {{ number_format($salariesMaster->where('field_id', 2)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->sum('net_salary'), 2) }} </strong> </h4> <p>  PENDING <br>{{ number_format($salariesMaster->where('field_id', 2)->where('payment_type', 'Cash')->where('payment_status', 'pending')->sum('net_salary'), 2) }}    </p> 
                                                                    <p>   HOLD <br> {{ number_format($salariesMaster->where('field_id', 2)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'rejected'])->sum('net_salary'), 2) }} </p>  
                                                                </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-danger"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 2)->where('payment_type', 'Bank')->whereIn('payment_status', ['pending','hold','rejected'])->count() }} </strong> </h6>  PENDING  <br>  {{ $salariesMaster->where('field_id', 2)->where('payment_type', 'Bank')->where('payment_status', 'pending')->count() }}
                                                                    <p>HOLD <br> {{ $salariesMaster->where('field_id', 2)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'rejected'])->count() }} </p>
                                                                </div>  
                                                                <div> <h6 class="card-title text-danger"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 2)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->count() }} </strong> </h6>  PENDING <br>  {{ $salariesMaster->where('field_id', 2)->where('payment_type', 'Cash')->where('payment_status', 'pending')->count() }}
                                                                    <p> HOLD <br> {{ $salariesMaster->where('field_id', 2)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'rejected'])->count() }} </p>
                                                                </div>  
                                                        </div> 
                                                        <hr> <br> 
                                                          <!-- MOMO TOP UPS -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-info">PAID  </strong>  </div>   | 
                                                                <div> <strong class="text-danger">OUTSTD'N  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-white"><strong> {{ number_format($topUpSalaries->where('field_id', 2)->where('status', 'approved')->sum('top_up_amount'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-white"><strong>  {{ number_format($topUpSalaries->where('field_id', 2)->where('status', 'saved')->sum('top_up_amount'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $topUpSalaries->where('field_id', 2)->where('status', 'approved')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $topUpSalaries->where('field_id', 2)->where('status', 'saved')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <!-- <hr> <br>   -->
                                                    </div>
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <br>

                            </div>

                            <div class="tab-pane fade" id="navs-pills-justified-temasummary" role="tabpanel">  

                                <!-- TEMA -->
                                <div class="row">
                                    <div class="col h-100" >
                                        <div  class="card h-100 bg-dark text-white">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                                                    <div class="avatar flex-shrink-0">
                                                        <img
                                                            src="{{ asset('img/icons/unicons/paypal.png') }}"
                                                            alt="chart success"
                                                            class="rounded" />
                                                    </div>
                                                    <h3 class="card-title text-white"> <strong> TEMA </strong>  </h3>
                                                </div>
                                                <div class="row">
                                                    <div class="col">
                                                        <!-- TOTAL -->
                                                        <p class="mb-1"><strong>  TOTAL SALARY </strong> </p>
                                                        <h4 class="card-title text-white"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 3)->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-white"><strong> &#x20B5; NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 3)->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: -8px;"> <br>
                                                    
                                                        <!-- PAID -->
                                                        <p class="mb-1"><strong>  TOTAL  PAID </strong> </p>
                                                        <h4 class="card-title text-info"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 3)->where('payment_status', 'approved')->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-info"><strong> &#x20B5; NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 3)->where('payment_status', 'approved')->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: -8px;"> <br>
                                                    
                                                        <!-- OUTSTANDING -->
                                                        <p class="mb-0"><strong>  TOTAL  OUTSTANDING </strong> </p>
                                                        <h4 class="card-title text-danger"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 3)->whereIn('payment_status', ['pending', 'hold', 'rejected'])->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-danger"><strong> &#x20B5; NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 3)->whereIn('payment_status', ['pending', 'hold', 'rejected'])->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: 200px;"> <br>
                                                    
                                                        <!--  MOMO TOP UPS -->
                                                        <p class="mb-0"><strong>  TOTAL  MOMO TOP UPS </strong> </p>
                                                        <h4 class="card-title text-primary"><strong> &#x20B5;  {{ number_format($topUpSalaries->where('field_id', 3)->sum('top_up_amount'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-primary"><strong>  NUMBER OF EMPLOYEES : {{ $topUpSalaries->where('field_id', 3)->count() }} </strong> </h6> 
                                                        <!-- <br> <hr style="margin-top: -8px;"> <br> -->
                                                    </div>

                                                    <div class="col ">
                                                        <!-- TOTAL -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK AMOUNT  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO AMOUNT  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-white"><strong> {{ number_format($salariesMaster->where('field_id', 3)->where('payment_type', 'Bank')->sum('net_salary'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-white"><strong>  {{ number_format($salariesMaster->where('field_id', 3)->where('payment_type', 'Cash')->sum('net_salary'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 3)->where('payment_type', 'Bank')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 3)->where('payment_type', 'Cash')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <hr> <br>     
                                                        
                                                        <!-- PAID -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK PAID  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO PAID  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-info"><strong> {{ number_format($salariesMaster->where('field_id', 3)->where('payment_type', 'Bank')->where('payment_status', 'approved')->sum('net_salary'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-info"><strong>  {{ number_format($salariesMaster->where('field_id', 3)->where('payment_type', 'Cash')->where('payment_status', 'approved')->sum('net_salary'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-info"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 3)->where('payment_type', 'Bank')->where('payment_status', 'approved')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-info"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 3)->where('payment_type', 'Cash')->where('payment_status', 'approved')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <hr> <br>  

                                                        <!-- OUTSTANDING -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK OUTSTD'N  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO OUTSTD'N  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-danger"><strong> {{ number_format($salariesMaster->where('field_id', 3)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->sum('net_salary'), 2) }}  </strong> </h4> <p> PENDING <br>{{ number_format($salariesMaster->where('field_id', 3)->where('payment_type', 'Bank')->where('payment_status', 'pending')->sum('net_salary'), 2) }}  </p> 
                                                                    <p>  HOLD <br> {{ number_format($salariesMaster->where('field_id', 3)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'rejected'])->sum('net_salary'), 2) }} </p>
                                                                </div> 
                                                                <div>  
                                                                    <h4 class="card-title text-danger"><strong>  {{ number_format($salariesMaster->where('field_id', 3)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->sum('net_salary'), 2) }} </strong> </h4> <p>  PENDING <br>{{ number_format($salariesMaster->where('field_id', 3)->where('payment_type', 'Cash')->where('payment_status', 'pending')->sum('net_salary'), 2) }}    </p> 
                                                                    <p>   HOLD <br> {{ number_format($salariesMaster->where('field_id', 3)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'rejected'])->sum('net_salary'), 2) }} </p>  
                                                                </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-danger"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 3)->where('payment_type', 'Bank')->whereIn('payment_status', ['pending','hold','rejected'])->count() }} </strong> </h6>  PENDING  <br>  {{ $salariesMaster->where('field_id', 3)->where('payment_type', 'Bank')->where('payment_status', 'pending')->count() }}
                                                                    <p>HOLD <br> {{ $salariesMaster->where('field_id', 3)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'rejected'])->count() }} </p>
                                                                </div>  
                                                                <div> <h6 class="card-title text-danger"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 3)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->count() }} </strong> </h6>  PENDING <br>  {{ $salariesMaster->where('field_id', 3)->where('payment_type', 'Cash')->where('payment_status', 'pending')->count() }}
                                                                    <p> HOLD <br> {{ $salariesMaster->where('field_id', 3)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'rejected'])->count() }} </p>
                                                                </div>  
                                                        </div> 
                                                        <hr> <br> 
                                                          <!-- MOMO TOP UPS -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-info">PAID  </strong>  </div>   | 
                                                                <div> <strong class="text-danger">OUTSTD'N  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-white"><strong> {{ number_format($topUpSalaries->where('field_id', 3)->where('status', 'approved')->sum('top_up_amount'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-white"><strong>  {{ number_format($topUpSalaries->where('field_id', 3)->where('status', 'saved')->sum('top_up_amount'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $topUpSalaries->where('field_id', 3)->where('status', 'approved')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $topUpSalaries->where('field_id', 3)->where('status', 'saved')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <!-- <hr> <br>   -->
                                                    </div>
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <br>

                            </div>


                            <div class="tab-pane fade" id="navs-pills-justified-shyhillssummary" role="tabpanel">  

                                <!-- SHHILLS -->
                                <div class="row">
                                    <div class="col h-100" >
                                        <div  class="card h-100 bg-dark text-white">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                                                    <div class="avatar flex-shrink-0">
                                                        <img
                                                            src="{{ asset('img/icons/unicons/paypal.png') }}"
                                                            alt="chart success"
                                                            class="rounded" />
                                                    </div>
                                                    <h3 class="card-title text-white"> <strong> SHAIHILLS </strong>  </h3>
                                                </div>
                                                <div class="row">
                                                    <div class="col">
                                                        <!-- TOTAL -->
                                                        <p class="mb-1"><strong>  TOTAL SALARY </strong> </p>
                                                        <h4 class="card-title text-white"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 7)->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-white"><strong> &#x20B5; NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 7)->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: -8px;"> <br>
                                                    
                                                        <!-- PAID -->
                                                        <p class="mb-1"><strong>  TOTAL  PAID </strong> </p>
                                                        <h4 class="card-title text-info"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 7)->where('payment_status', 'approved')->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-info"><strong> &#x20B5; NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 7)->where('payment_status', 'approved')->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: -8px;"> <br>
                                                    
                                                        <!-- OUTSTANDING -->
                                                        <p class="mb-0"><strong>  TOTAL  OUTSTANDING </strong> </p>
                                                        <h4 class="card-title text-danger"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 7)->whereIn('payment_status', ['pending', 'hold', 'rejected'])->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-danger"><strong> &#x20B5; NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 7)->whereIn('payment_status', ['pending', 'hold', 'rejected'])->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: 200px;"> <br>

                                                        <!--  MOMO TOP UPS -->
                                                        <p class="mb-0"><strong>  TOTAL  MOMO TOP UPS </strong> </p>
                                                        <h4 class="card-title text-primary"><strong> &#x20B5;  {{ number_format($topUpSalaries->where('field_id', 7)->sum('top_up_amount'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-primary"><strong>  NUMBER OF EMPLOYEES : {{ $topUpSalaries->where('field_id', 7)->count() }} </strong> </h6> 
                                                        <!-- <br> <hr style="margin-top: -8px;"> <br> -->
                                                    </div>

                                                    <div class="col ">
                                                        <!-- TOTAL -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK AMOUNT  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO AMOUNT  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-white"><strong> {{ number_format($salariesMaster->where('field_id', 7)->where('payment_type', 'Bank')->sum('net_salary'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-white"><strong>  {{ number_format($salariesMaster->where('field_id', 7)->where('payment_type', 'Cash')->sum('net_salary'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 7)->where('payment_type', 'Bank')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 7)->where('payment_type', 'Cash')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <hr> <br>     
                                                        
                                                        <!-- PAID -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK PAID  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO PAID  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-info"><strong> {{ number_format($salariesMaster->where('field_id', 7)->where('payment_type', 'Bank')->where('payment_status', 'approved')->sum('net_salary'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-info"><strong>  {{ number_format($salariesMaster->where('field_id', 7)->where('payment_type', 'Cash')->where('payment_status', 'approved')->sum('net_salary'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-info"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 7)->where('payment_type', 'Bank')->where('payment_status', 'approved')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-info"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 7)->where('payment_type', 'Cash')->where('payment_status', 'approved')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <hr> <br>  

                                                        <!-- OUTSTANDING -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK OUTSTD'N  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO OUTSTD'N  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-danger"><strong> {{ number_format($salariesMaster->where('field_id', 7)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->sum('net_salary'), 2) }}  </strong> </h4> <p> PENDING <br>{{ number_format($salariesMaster->where('field_id', 7)->where('payment_type', 'Bank')->where('payment_status', 'pending')->sum('net_salary'), 2) }}  </p> 
                                                                    <p>  HOLD <br> {{ number_format($salariesMaster->where('field_id', 7)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'rejected'])->sum('net_salary'), 2) }} </p>
                                                                </div> 
                                                                <div>  
                                                                    <h4 class="card-title text-danger"><strong>  {{ number_format($salariesMaster->where('field_id', 7)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->sum('net_salary'), 2) }} </strong> </h4> <p>  PENDING <br>{{ number_format($salariesMaster->where('field_id', 7)->where('payment_type', 'Cash')->where('payment_status', 'pending')->sum('net_salary'), 2) }}    </p> 
                                                                    <p>   HOLD <br> {{ number_format($salariesMaster->where('field_id', 7)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'rejected'])->sum('net_salary'), 2) }} </p>  
                                                                </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-danger"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 7)->where('payment_type', 'Bank')->whereIn('payment_status', ['pending','hold','rejected'])->count() }} </strong> </h6>  PENDING  <br>  {{ $salariesMaster->where('field_id', 7)->where('payment_type', 'Bank')->where('payment_status', 'pending')->count() }}
                                                                    <p>HOLD <br> {{ $salariesMaster->where('field_id', 7)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'rejected'])->count() }} </p>
                                                                </div>  
                                                                <div> <h6 class="card-title text-danger"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 7)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->count() }} </strong> </h6>  PENDING <br>  {{ $salariesMaster->where('field_id', 7)->where('payment_type', 'Cash')->where('payment_status', 'pending')->count() }}
                                                                    <p> HOLD <br> {{ $salariesMaster->where('field_id', 7)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'rejected'])->count() }} </p>
                                                                </div>  
                                                        </div> 
                                                        <hr> <br> 
                                                          <!-- MOMO TOP UPS -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-info">PAID  </strong>  </div>   | 
                                                                <div> <strong class="text-danger">OUTSTD'N  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-white"><strong> {{ number_format($topUpSalaries->where('field_id', 7)->where('status', 'approved')->sum('top_up_amount'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-white"><strong>  {{ number_format($topUpSalaries->where('field_id', 7)->where('status', 'saved')->sum('top_up_amount'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $topUpSalaries->where('field_id', 7)->where('status', 'approved')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $topUpSalaries->where('field_id', 7)->where('status', 'saved')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <!-- <hr> <br>   -->
                                                    </div>
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <br>

                            </div>


                            <div class="tab-pane fade" id="navs-pills-justified-takoradisummary" role="tabpanel">  

                                <!-- TAKORADI -->
                                <div class="row">
                                    <div class="col h-100" >
                                        <div  class="card h-100 bg-dark text-white">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                                                    <div class="avatar flex-shrink-0">
                                                        <img
                                                            src="{{ asset('img/icons/unicons/paypal.png') }}"
                                                            alt="chart success"
                                                            class="rounded" />
                                                    </div>
                                                    <h3 class="card-title text-white"> <strong> TAKORADI </strong>  </h3>
                                                </div>
                                                <div class="row">
                                                    <div class="col">
                                                        <!-- TOTAL -->
                                                        <p class="mb-1"><strong>  TOTAL SALARY </strong> </p>
                                                        <h4 class="card-title text-white"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 4)->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-white"><strong> &#x20B5; NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 4)->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: -8px;"> <br>
                                                    
                                                        <!-- PAID -->
                                                        <p class="mb-1"><strong>  TOTAL  PAID </strong> </p>
                                                        <h4 class="card-title text-info"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 4)->where('payment_status', 'approved')->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-info"><strong> &#x20B5; NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 4)->where('payment_status', 'approved')->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: -8px;"> <br>
                                                    
                                                        <!-- OUTSTANDING -->
                                                        <p class="mb-0"><strong>  TOTAL  OUTSTANDING </strong> </p>
                                                        <h4 class="card-title text-danger"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 4)->whereIn('payment_status', ['pending', 'hold', 'rejected'])->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-danger"><strong> &#x20B5; NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 4)->whereIn('payment_status', ['pending', 'hold', 'rejected'])->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: 200px;"> <br>

                                                        <!--  MOMO TOP UPS -->
                                                        <p class="mb-0"><strong>  TOTAL  MOMO TOP UPS </strong> </p>
                                                        <h4 class="card-title text-primary"><strong> &#x20B5;  {{ number_format($topUpSalaries->where('field_id', 4)->sum('top_up_amount'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-primary"><strong>  NUMBER OF EMPLOYEES : {{ $topUpSalaries->where('field_id', 4)->count() }} </strong> </h6> 
                                                        <!-- <br> <hr style="margin-top: -8px;"> <br> -->
                                                    </div>

                                                    <div class="col ">
                                                        <!-- TOTAL -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK AMOUNT  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO AMOUNT  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-white"><strong> {{ number_format($salariesMaster->where('field_id', 4)->where('payment_type', 'Bank')->sum('net_salary'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-white"><strong>  {{ number_format($salariesMaster->where('field_id', 4)->where('payment_type', 'Cash')->sum('net_salary'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 4)->where('payment_type', 'Bank')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 4)->where('payment_type', 'Cash')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <hr> <br>     
                                                        
                                                        <!-- PAID -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK PAID  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO PAID  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-info"><strong> {{ number_format($salariesMaster->where('field_id', 4)->where('payment_type', 'Bank')->where('payment_status', 'approved')->sum('net_salary'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-info"><strong>  {{ number_format($salariesMaster->where('field_id', 4)->where('payment_type', 'Cash')->where('payment_status', 'approved')->sum('net_salary'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-info"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 4)->where('payment_type', 'Bank')->where('payment_status', 'approved')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-info"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 4)->where('payment_type', 'Cash')->where('payment_status', 'approved')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <hr> <br>  

                                                        <!-- OUTSTANDING -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK OUTSTD'N  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO OUTSTD'N  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-danger"><strong> {{ number_format($salariesMaster->where('field_id', 4)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->sum('net_salary'), 2) }}  </strong> </h4> <p> PENDING <br>{{ number_format($salariesMaster->where('field_id', 4)->where('payment_type', 'Bank')->where('payment_status', 'pending')->sum('net_salary'), 2) }}  </p> 
                                                                    <p>  HOLD <br> {{ number_format($salariesMaster->where('field_id', 4)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'rejected'])->sum('net_salary'), 2) }} </p>
                                                                </div> 
                                                                <div>  
                                                                    <h4 class="card-title text-danger"><strong>  {{ number_format($salariesMaster->where('field_id', 4)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->sum('net_salary'), 2) }} </strong> </h4> <p>  PENDING <br>{{ number_format($salariesMaster->where('field_id', 4)->where('payment_type', 'Cash')->where('payment_status', 'pending')->sum('net_salary'), 2) }}    </p> 
                                                                    <p>   HOLD <br> {{ number_format($salariesMaster->where('field_id', 4)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'rejected'])->sum('net_salary'), 2) }} </p>  
                                                                </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-danger"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 4)->where('payment_type', 'Bank')->whereIn('payment_status', ['pending','hold','rejected'])->count() }} </strong> </h6>  PENDING  <br>  {{ $salariesMaster->where('field_id', 4)->where('payment_type', 'Bank')->where('payment_status', 'pending')->count() }}
                                                                    <p>HOLD <br> {{ $salariesMaster->where('field_id', 4)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'rejected'])->count() }} </p>
                                                                </div>  
                                                                <div> <h6 class="card-title text-danger"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 4)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->count() }} </strong> </h6>  PENDING <br>  {{ $salariesMaster->where('field_id', 4)->where('payment_type', 'Cash')->where('payment_status', 'pending')->count() }}
                                                                    <p> HOLD <br> {{ $salariesMaster->where('field_id', 4)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'rejected'])->count() }} </p>
                                                                </div>  
                                                        </div> 
                                                        <hr> <br> 
                                                          <!-- MOMO TOP UPS -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-info">PAID  </strong>  </div>   | 
                                                                <div> <strong class="text-danger">OUTSTD'N  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-white"><strong> {{ number_format($topUpSalaries->where('field_id', 4)->where('status', 'approved')->sum('top_up_amount'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-white"><strong>  {{ number_format($topUpSalaries->where('field_id', 4)->where('status', 'saved')->sum('top_up_amount'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $topUpSalaries->where('field_id', 4)->where('status', 'approved')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $topUpSalaries->where('field_id', 4)->where('status', 'saved')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <!-- <hr> <br>   -->
                                                    </div>
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <br>

                            </div>


                            <div class="tab-pane fade" id="navs-pills-justified-koforiduasummary" role="tabpanel">  

                                <!-- KOFORIDUA -->
                                <div class="row">
                                    <div class="col h-100" >
                                        <div  class="card h-100 bg-dark text-white">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                                                    <div class="avatar flex-shrink-0">
                                                        <img
                                                            src="{{ asset('img/icons/unicons/paypal.png') }}"
                                                            alt="chart success"
                                                            class="rounded" />
                                                    </div>
                                                    <h3 class="card-title text-white"> <strong> KOFORIDUA </strong>  </h3>
                                                </div>
                                                <div class="row">
                                                    <div class="col">
                                                        <!-- TOTAL -->
                                                        <p class="mb-1"><strong>  TOTAL SALARY </strong> </p>
                                                        <h4 class="card-title text-white"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 5)->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-white"><strong> &#x20B5; NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 5)->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: -8px;"> <br>
                                                    
                                                        <!-- PAID -->
                                                        <p class="mb-1"><strong>  TOTAL  PAID </strong> </p>
                                                        <h4 class="card-title text-info"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 5)->where('payment_status', 'approved')->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-info"><strong> &#x20B5; NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 5)->where('payment_status', 'approved')->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: -8px;"> <br>
                                                    
                                                        <!-- OUTSTANDING -->
                                                        <p class="mb-0"><strong>  TOTAL  OUTSTANDING </strong> </p>
                                                        <h4 class="card-title text-danger"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 5)->whereIn('payment_status', ['pending', 'hold', 'rejected'])->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-danger"><strong> &#x20B5; NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 5)->whereIn('payment_status', ['pending', 'hold', 'rejected'])->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: 200px;"> <br>

                                                        <!--  MOMO TOP UPS -->
                                                        <p class="mb-0"><strong>  TOTAL  MOMO TOP UPS </strong> </p>
                                                        <h4 class="card-title text-primary"><strong> &#x20B5;  {{ number_format($topUpSalaries->where('field_id', 5)->sum('top_up_amount'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-primary"><strong>  NUMBER OF EMPLOYEES : {{ $topUpSalaries->where('field_id', 5)->count() }} </strong> </h6> 
                                                        <!-- <br> <hr style="margin-top: -8px;"> <br> -->
                                                    </div>

                                                    <div class="col ">
                                                        <!-- TOTAL -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK AMOUNT  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO AMOUNT  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-white"><strong> {{ number_format($salariesMaster->where('field_id', 5)->where('payment_type', 'Bank')->sum('net_salary'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-white"><strong>  {{ number_format($salariesMaster->where('field_id', 5)->where('payment_type', 'Cash')->sum('net_salary'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 5)->where('payment_type', 'Bank')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 5)->where('payment_type', 'Cash')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <hr> <br>     
                                                        
                                                        <!-- PAID -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK PAID  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO PAID  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-info"><strong> {{ number_format($salariesMaster->where('field_id', 5)->where('payment_type', 'Bank')->where('payment_status', 'approved')->sum('net_salary'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-info"><strong>  {{ number_format($salariesMaster->where('field_id', 5)->where('payment_type', 'Cash')->where('payment_status', 'approved')->sum('net_salary'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-info"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 5)->where('payment_type', 'Bank')->where('payment_status', 'approved')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-info"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 5)->where('payment_type', 'Cash')->where('payment_status', 'approved')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <hr> <br>  

                                                        <!-- OUTSTANDING -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK OUTSTD'N  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO OUTSTD'N  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-danger"><strong> {{ number_format($salariesMaster->where('field_id', 5)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->sum('net_salary'), 2) }}  </strong> </h4> <p> PENDING <br>{{ number_format($salariesMaster->where('field_id', 5)->where('payment_type', 'Bank')->where('payment_status', 'pending')->sum('net_salary'), 2) }}  </p> 
                                                                    <p>  HOLD <br> {{ number_format($salariesMaster->where('field_id', 5)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'rejected'])->sum('net_salary'), 2) }} </p>
                                                                </div> 
                                                                <div>  
                                                                    <h4 class="card-title text-danger"><strong>  {{ number_format($salariesMaster->where('field_id', 5)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->sum('net_salary'), 2) }} </strong> </h4> <p>  PENDING <br>{{ number_format($salariesMaster->where('field_id', 5)->where('payment_type', 'Cash')->where('payment_status', 'pending')->sum('net_salary'), 2) }}    </p> 
                                                                    <p>   HOLD <br> {{ number_format($salariesMaster->where('field_id', 5)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'rejected'])->sum('net_salary'), 2) }} </p>  
                                                                </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-danger"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 5)->where('payment_type', 'Bank')->whereIn('payment_status', ['pending','hold','rejected'])->count() }} </strong> </h6>  PENDING  <br>  {{ $salariesMaster->where('field_id', 5)->where('payment_type', 'Bank')->where('payment_status', 'pending')->count() }}
                                                                    <p>HOLD <br> {{ $salariesMaster->where('field_id', 5)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'rejected'])->count() }} </p>
                                                                </div>  
                                                                <div> <h6 class="card-title text-danger"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 5)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->count() }} </strong> </h6>  PENDING <br>  {{ $salariesMaster->where('field_id', 5)->where('payment_type', 'Cash')->where('payment_status', 'pending')->count() }}
                                                                    <p> HOLD <br> {{ $salariesMaster->where('field_id', 5)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'rejected'])->count() }} </p>
                                                                </div>  
                                                        </div> 
                                                        <hr> <br> 
                                                      <!-- MOMO TOP UPS -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-info">PAID  </strong>  </div>   | 
                                                                <div> <strong class="text-danger">OUTSTD'N  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-white"><strong> {{ number_format($topUpSalaries->where('field_id', 5)->where('status', 'approved')->sum('top_up_amount'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-white"><strong>  {{ number_format($topUpSalaries->where('field_id', 5)->where('status', 'saved')->sum('top_up_amount'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $topUpSalaries->where('field_id', 5)->where('status', 'approved')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $topUpSalaries->where('field_id', 5)->where('status', 'saved')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <!-- <hr> <br>   -->
                                                    </div>
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <br>

                            </div>


                            <div class="tab-pane fade" id="navs-pills-justified-kumasisummary" role="tabpanel">  

                                <!-- KUMASI -->
                                <div class="row">
                                    <div class="col h-100" >
                                        <div  class="card h-100 bg-dark text-white">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                                                    <div class="avatar flex-shrink-0">
                                                        <img
                                                            src="{{ asset('img/icons/unicons/paypal.png') }}"
                                                            alt="chart success"
                                                            class="rounded" />
                                                    </div>
                                                    <h3 class="card-title text-white"> <strong> KUMASI </strong>  </h3>
                                                </div>
                                                <div class="row">
                                                    <div class="col">
                                                        <!-- TOTAL -->
                                                        <p class="mb-1"><strong>  TOTAL SALARY </strong> </p>
                                                        <h4 class="card-title text-white"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 6)->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-white"><strong> &#x20B5; NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 6)->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: -8px;"> <br>
                                                    
                                                        <!-- PAID -->
                                                        <p class="mb-1"><strong>  TOTAL  PAID </strong> </p>
                                                        <h4 class="card-title text-info"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 6)->where('payment_status', 'approved')->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-info"><strong> &#x20B5; NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 6)->where('payment_status', 'approved')->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: -8px;"> <br>
                                                    
                                                        <!-- OUTSTANDING -->
                                                        <p class="mb-0"><strong>  TOTAL  OUTSTANDING </strong> </p>
                                                        <h4 class="card-title text-danger"><strong> &#x20B5;  {{ number_format($salariesMaster->where('field_id', 6)->whereIn('payment_status', ['pending', 'hold', 'rejected'])->sum('net_salary'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-danger"><strong> &#x20B5; NUMBER OF EMPLOYEES : {{ $salariesMaster->where('field_id', 6)->whereIn('payment_status', ['pending', 'hold', 'rejected'])->count() }} </strong> </h6> 
                                                        <br> <hr style="margin-top: 200px;"> <br>

                                                        <!--  MOMO TOP UPS -->
                                                        <p class="mb-0"><strong>  TOTAL  MOMO TOP UPS </strong> </p>
                                                        <h4 class="card-title text-primary"><strong> &#x20B5;  {{ number_format($topUpSalaries->where('field_id', 5)->sum('top_up_amount'), 2) }} </strong> </h4> 
                                                        <h6 class="card-title text-primary"><strong>  NUMBER OF EMPLOYEES : {{ $topUpSalaries->where('field_id', 5)->count() }} </strong> </h6> 
                                                        <!-- <br> <hr style="margin-top: -8px;"> <br> -->
                                                    </div>

                                                    <div class="col ">
                                                        <!-- TOTAL -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK AMOUNT  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO AMOUNT  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-white"><strong> {{ number_format($salariesMaster->where('field_id', 6)->where('payment_type', 'Bank')->sum('net_salary'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-white"><strong>  {{ number_format($salariesMaster->where('field_id', 6)->where('payment_type', 'Cash')->sum('net_salary'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 6)->where('payment_type', 'Bank')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 6)->where('payment_type', 'Cash')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <hr> <br>     
                                                        
                                                        <!-- PAID -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK PAID  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO PAID  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-info"><strong> {{ number_format($salariesMaster->where('field_id', 6)->where('payment_type', 'Bank')->where('payment_status', 'approved')->sum('net_salary'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-info"><strong>  {{ number_format($salariesMaster->where('field_id', 6)->where('payment_type', 'Cash')->where('payment_status', 'approved')->sum('net_salary'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-info"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 6)->where('payment_type', 'Bank')->where('payment_status', 'approved')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-info"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 6)->where('payment_type', 'Cash')->where('payment_status', 'approved')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <hr> <br>  

                                                        <!-- OUTSTANDING -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-success">BANK OUTSTD'N  </strong>  </div>   | 
                                                                <div> <strong class="text-warning">MOMO OUTSTD'N  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-danger"><strong> {{ number_format($salariesMaster->where('field_id', 6)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->sum('net_salary'), 2) }}  </strong> </h4> <p> PENDING <br>{{ number_format($salariesMaster->where('field_id', 6)->where('payment_type', 'Bank')->where('payment_status', 'pending')->sum('net_salary'), 2) }}  </p> 
                                                                    <p>  HOLD <br> {{ number_format($salariesMaster->where('field_id', 6)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'rejected'])->sum('net_salary'), 2) }} </p>
                                                                </div> 
                                                                <div>  
                                                                    <h4 class="card-title text-danger"><strong>  {{ number_format($salariesMaster->where('field_id', 6)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->sum('net_salary'), 2) }} </strong> </h4> <p>  PENDING <br>{{ number_format($salariesMaster->where('field_id', 6)->where('payment_type', 'Cash')->where('payment_status', 'pending')->sum('net_salary'), 2) }}    </p> 
                                                                    <p>   HOLD <br> {{ number_format($salariesMaster->where('field_id', 6)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'rejected'])->sum('net_salary'), 2) }} </p>  
                                                                </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-danger"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 6)->where('payment_type', 'Bank')->whereIn('payment_status', ['pending','hold','rejected'])->count() }} </strong> </h6>  PENDING  <br>  {{ $salariesMaster->where('field_id', 6)->where('payment_type', 'Bank')->where('payment_status', 'pending')->count() }}
                                                                    <p>HOLD <br> {{ $salariesMaster->where('field_id', 6)->where('payment_type', 'Bank')->whereIn('payment_status', ['hold', 'rejected'])->count() }} </p>
                                                                </div>  
                                                                <div> <h6 class="card-title text-danger"><strong>  N0 of Emp' : {{ $salariesMaster->where('field_id', 6)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'pending', 'rejected'])->count() }} </strong> </h6>  PENDING <br>  {{ $salariesMaster->where('field_id', 6)->where('payment_type', 'Cash')->where('payment_status', 'pending')->count() }}
                                                                    <p> HOLD <br> {{ $salariesMaster->where('field_id', 6)->where('payment_type', 'Cash')->whereIn('payment_status', ['hold', 'rejected'])->count() }} </p>
                                                                </div>  
                                                        </div> 
                                                        <hr> <br> 
                                                     <!-- MOMO TOP UPS -->
                                                        <div class="d-flex justify-content-between">
                                                                <div> <strong class="text-info">PAID  </strong>  </div>   | 
                                                                <div> <strong class="text-danger">OUTSTD'N  </strong> </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between">
                                                                <div>  <h4 class="card-title text-white"><strong> {{ number_format($topUpSalaries->where('field_id', 6)->where('status', 'approved')->sum('top_up_amount'), 2) }}  </strong> </h4>   </div> 
                                                                <div>  <h4 class="card-title text-white"><strong>  {{ number_format($topUpSalaries->where('field_id', 6)->where('status', 'saved')->sum('top_up_amount'), 2) }} </strong> </h4>  </div>   
                                                        </div>
                                                    
                                                        <div class="d-flex justify-content-between">
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $topUpSalaries->where('field_id', 6)->where('status', 'approved')->count() }} </strong> </h6>   </div>  
                                                                <div> <h6 class="card-title text-white"><strong>  N0 of Emp' : {{ $topUpSalaries->where('field_id', 6)->where('status', 'saved')->count() }} </strong> </h6>   </div>  
                                                        </div> 
                                                        <!-- <hr> <br>   -->
                                                    </div>
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <br>

                            </div>

                        </div>
                   
                    </div>

            </div>

            <!-- OVERALL SUMMARY -->
            <div class="col-lg-6 " style="margin-top: 50px;">
                <div class="row">
                    <div class="col h-100" >
                        <div  class="card h-100 bg-dark text-white">
                            <div class="card-body">

                                <div class="row">

                                    <div class="col">

                                        <!-- TOTAL -->
                                        <p class="mb-1"><strong>  TOTAL SALARIES </strong> </p>
                                        <h4 class="card-title text-white"><strong> &#x20B5;  {{ number_format($salariesMaster->sum('net_salary'), 2) }} </strong> </h4> 
                                        <h6 class="card-title text-white"><strong> &#x20B5; NUMBER OF EMPLOYEES : {{ $salariesMaster->count() }} </strong> </h6> 
                                        
                                        <div class="mt-6"> <strong>PAID   </strong>  </div>   
                                        <div>  <h4 class="card-title text-info"><strong> {{ number_format($salariesMaster->where('payment_status', 'approved')->sum('net_salary'), 2) }}  </strong> </h4>   </div> 
                                        <div> <h6 class="card-title text-info"><strong>  N0 of Emp' : {{ $salariesMaster->where('payment_status', 'approved')->count() }} </strong> </h6>   </div>  
                                   
                                    </div>

                                    <div class="col">

                                        <!-- TOTAL -->
                                        <div class="d-flex justify-content-between">
                                                <div> <strong>OUTSTANDINGS   </strong> </div>
                                                <div> <strong>N0 OF EMP'   </strong> </div>

                                        </div>

                                        <div class="d-flex justify-content-between">
                                                <div>  <h4 class="card-title text-danger"><strong>  {{ number_format( $salariesMaster->whereIn('payment_status', ['pending', 'hold', 'rejected'])->sum('net_salary'), 2)  }} </strong> </h4> <p> PENDING <br> {{ number_format( $salariesMaster->where('payment_status', 'pending')->sum('net_salary'), 2)  }} </p> 
                                               <p> HOLD  <br>{{ number_format( $salariesMaster->whereIn('payment_status', ['rejected', 'hold'])->sum('net_salary'), 2)  }} </p>
                                            </div>   
                                      
                                            <div> <h4 class="card-title text-danger"><strong>   {{ $salariesMaster->whereIn('payment_status', ['pending', 'hold', 'rejected'])->count() }} </strong> </h4> <p> PENDING <br> {{ $salariesMaster->where('payment_status', 'pending')->count() }} </p> 
                                                <p>HOLD <br> {{ $salariesMaster->whereIn('payment_status', ['hold', 'rejected'])->count() }} </p>
                                            </div>  
                                       
                                        </div>
                                      
                                   </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <br>
                <div class="row">
                    <div class="col h-100">
                        <div  class="card h-100 bg-dark text-white">
                            <div class="card-body">

                                <div class="row">

                                    <div class="col">

                                        <!-- TOTAL -->
                                        <p class="mb-1"><strong class="text-success">  TOTAL BANKS </strong> </p>
                                        <h4 class="card-title text-white"><strong> &#x20B5;  {{ number_format($salariesMaster->where('payment_type', 'Bank')->sum('net_salary'), 2) }} </strong> </h4> 
                                        <h6 class="card-title text-white"><strong>  NUMBER OF EMPLOYEES : {{ $salariesMaster->where('payment_type', 'Bank')->count() }} </strong> </h6> 
                                        
                                        <div class="mt-6"> <strong>PAID   </strong>  </div>  
                                        <div>  <h4 class="card-title text-info"><strong> {{ number_format($salariesMaster->where('payment_type', 'Bank')->where('payment_status', 'approved')->sum('net_salary'), 2) }}  </strong> </h4>   </div> 
                                        <div> <h6 class="card-title text-info"><strong>  N0 of Emp' : {{ $salariesMaster->where('payment_type', 'Bank')->where('payment_status', 'approved')->count() }} </strong> </h6>   </div>  

                                    </div>

                                    <div class="col">

                                        <!-- TOTAL -->
                                        <div class="d-flex justify-content-between">
                                                
                                                <div> <strong>OUTSTANDINGS   </strong> </div>
                                                <div> <strong>N0 OF EMP'   </strong> </div>
                                        </div>

                                        <div class="d-flex justify-content-between">
                                                <div>  <h4 class="card-title text-danger"><strong>  {{ number_format($salariesMaster->where('payment_type','Bank')->whereIn('payment_status', ['pending', 'hold', 'rejected'])->sum('net_salary'), 2)  }} </strong> </h4> <p> PENDIND <br> {{ number_format($salariesMaster->where('payment_type','Bank')->where('payment_status', 'pending')->sum('net_salary'), 2)  }}  </p>
                                                <p> HOLD <br> {{ number_format($salariesMaster->where('payment_type','Bank')->whereIn('payment_status', [ 'hold', 'rejected'])->sum('net_salary'), 2)  }} </p>
                                                </div>  
                                                
                                                <div> <h4 class="card-title text-danger"><strong> {{ $salariesMaster->where('payment_type', 'Bank')->whereIn('payment_status', ['pending', 'hold', 'rejected'])->count() }} </strong> </h4> <p> PENDING <br> {{ $salariesMaster->where('payment_type', 'Bank')->where('payment_status', 'pending')->count() }} </p> 
                                                 <p> HOLD <br> {{ $salariesMaster->where('payment_type', 'Bank')->whereIn('payment_status', ['rejected', 'hold'])->count() }} </p>
                                                 </div>  
                                        </div>
                                      
                                   </div>
                                </div>

                            </div>
                        </div>
                    </div> 
                </div>
  
                <br>
                <div class="row">
                    <div class="col h-100">
                        <div  class="card h-100 bg-dark text-white">
                            <div class="card-body">
                                <div class="row">

                                    <div class="col">

                                        <!-- TOTAL -->
                                        <p class="mb-1"><strong class="text-warning">  TOTAL MOMO </strong> </p>
                                        <h4 class="card-title text-white"><strong> &#x20B5;  {{ number_format(       $salariesMaster->where('payment_type', 'Cash')->sum('net_salary')    + $salariesMaster->where('payment_type', 'cash')->sum('net_salary'), 2) }} </strong> </h4> 
                                        <h6 class="card-title text-white"><strong> &#x20B5; NUMBER OF EMPLOYEES : {{ $salariesMaster->where('payment_type', 'Cash')->count()       + $salariesMaster->where('payment_type', 'cash')->count() }} </strong> </h6> 
                                        
                                        <div class="mt-6"> <strong>PAID   </strong>  </div>   
                                        <div>  <h4 class="card-title text-info"><strong> {{ number_format($salariesMaster->where('payment_type', 'Cash')->where('payment_status', 'approved')->sum('net_salary')  + $salariesMaster->where('payment_type', 'cash')->where('payment_status', 'approved')->sum('net_salary'), 2) }}  </strong> </h4>   </div> 
                                        <div> <h6 class="card-title text-info"><strong>  N0 of Emp' : {{ $salariesMaster->where('payment_type', 'Cash')->where('payment_status', 'approved')->count()  + $salariesMaster->where('payment_type', 'cash')->where('payment_status', 'approved')->count() }} </strong> </h6>   </div>  

                                    </div>

                                    <div class="col">

                                        <!-- TOTAL -->
                                        <div class="d-flex justify-content-between">
                                                <div> <strong>OUTSTANDINGS   </strong> </div>
                                                <div> <strong>N0 OF EMP'   </strong> </div>
                                        </div>

                                        <div class="d-flex justify-content-between">
                                                <div>  <h4 class="card-title text-danger"><strong>  {{ number_format($salariesMaster->where('payment_type', 'Cash')->whereIn('payment_status', ['pending', 'rejected', 'hold'])->sum('net_salary')  + $salariesMaster->where('payment_type', 'cash')->whereIn('payment_status', ['pending', 'rejected', 'hold'])->sum('net_salary'), 2)  }} </strong> </h4>  
                                            
                                                    <P> PENDING <br> {{ number_format($salariesMaster->where('payment_type', 'Cash')->where('payment_status', 'pending')->sum('net_salary')  + $salariesMaster->where('payment_type', 'cash')->where('payment_status', 'pending')->sum('net_salary'), 2)  }} </P>
                                                    
                                                    <P>HOLD <br> {{ number_format($salariesMaster->where('payment_type', 'Cash')->whereIn('payment_status', [ 'rejected', 'hold'])->sum('net_salary')  + $salariesMaster->where('payment_type', 'cash')->whereIn('payment_status', ['rejected', 'hold'])->sum('net_salary'), 2)  }} </P>
                                                </div>   
                                        
                                                <div> <h4 class="card-title text-danger"><strong> {{ $salariesMaster->where('payment_type', 'Cash')->whereIn('payment_status', ['pending', 'rejected', 'hold'])->count()  + $salariesMaster->where('payment_type', 'cash')->whereIn('payment_status', ['pending', 'rejected', 'hold'])->count() }} </strong> </h4>  
                                                    <p> PENDING <br>  {{ $salariesMaster->where('payment_type', 'Cash')->where('payment_status', 'pending')->count()  + $salariesMaster->where('payment_type', 'cash')->where('payment_status', 'pending')->count() }}</p>
                                                    <p> HOLD <br>  {{ $salariesMaster->where('payment_type', 'Cash')->whereIn('payment_status', ['rejected', 'hold'])->count()  + $salariesMaster->where('payment_type', 'cash')->whereIn('payment_status', [ 'rejected', 'hold'])->count() }} </p>
                                                </div>  
                                        
                                        </div>
                                      

                                   </div>
                                </div>


                            </div>
                        </div>
                    </div> 
                </div>


                <br>
                <div class="row">
                    <div class="col h-100">
                        <div  class="card h-100 bg-dark text-white">
                            <div class="card-body">
                                <div class="row">

                                    <div class="col">

                                        <!-- TOTAL -->
                                        <p class="mb-1"><strong class="text-primary">  TOTAL MOMO TOP UPS</strong> </p>
                                        <h4 class="card-title text-primary"><strong> &#x20B5;  {{ number_format( $topUpSalaries->sum('top_up_amount'), 2) }} </strong> </h4> 
                                        <h6 class="card-title text-primary"><strong>  NUMBER OF EMPLOYEES : {{ $topUpSalaries->count() }} </strong> </h6> 
                                      
                                    </div>

                                    <div class="col">

                                        <!-- TOTAL -->
                                        <div class="d-flex justify-content-between">
                                                <div> <strong>PAID   </strong> </div>
                                                <div> <strong>N0 OF EMP'   </strong> </div>
                                        </div>

                                        <div class="d-flex justify-content-between">
                                                <div>  <h4 class="card-title text-info"><strong>  {{ number_format($topUpSalaries->where('status', 'approved')->sum('top_up_amount'), 2)  }} </strong> </h4>  </div>   
                                                <div> <h4 class="card-title text-info"><strong> {{ $topUpSalaries->where('status', 'approved')->count() }} </strong> </h4>  </div>  
                                        </div>


                                        <div class="d-flex justify-content-between">
                                                <div> <strong>OUTSTANDINGS   </strong> </div>
                                                <div> <strong>N0 OF EMP'   </strong> </div>
                                        </div>

                                        <div class="d-flex justify-content-between">
                                                <div>  <h4 class="card-title text-danger"><strong>  {{ number_format($topUpSalaries->where('status', 'saved')->sum('top_up_amount'), 2)  }} </strong> </h4>  </div>   
                                                <div> <h4 class="card-title text-danger"><strong> {{ $topUpSalaries->where('status', 'saved')->count() }} </strong> </h4>  </div>  
                                        </div>
                                      

                                   </div>
                                </div>


                            </div>
                        </div>
                    </div> 
                </div>

            </div>

        </div> 
        <br>

    </div>
  <!-- / Content -->

  @endsection


    @section('scripts')
    @include('partials.dt_compact')

     <script src="{{asset('vendor/js/datatables.js')}}"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.dataTables.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.html5.min.js"></script>

    <script src="https://cdn.datatables.net/fixedcolumns/5.0.5/js/dataTables.fixedColumns.js"> </script>           
    <script src="https://cdn.datatables.net/fixedcolumns/5.0.5/js/fixedColumns.dataTables.js"></script>     
    <script src="https://cdn.datatables.net/fixedheader/4.0.5/js/dataTables.fixedHeader.js"></script>      
    <script src="https://cdn.datatables.net/fixedheader/4.0.5/js/fixedHeader.dataTables.js"></script>  
    <script src="https://cdn.datatables.net/columncontrol/1.1.1/js/dataTables.columnControl.min.js"></script>


    <script>
        let myTableiAccra = new DataTable('#myTableiAccra'); 
        let myTableiBotwe = new DataTable('#myTableiBotwe');
        let myTableiShyhills = new DataTable('#myTableiShyhills');
        let myTableiTema = new DataTable('#myTableiTema');
        let myTableiOvertime = new DataTable('#myTableiOvertime');
        let myTableiIou = new DataTable('#myTableiIou');
        let myTableiboot = new DataTable('#myTableiboot');
        let myTableiabsent = new DataTable('#myTableiabsent');
        let myTableisDate_ded = new DataTable('#myTableisDate_ded');
        let myTableiodeduct = new DataTable('#myTableiodeduct');
        let myTableireprimand = new DataTable('#myTableireprimand');
        let myTableiloan = new DataTable('#myTableiloan');
        let myTableiholdcash = new DataTable('#myTableiholdcash');
        let myTableiholdbank = new DataTable('#myTableiholdbank');

        let myTablecategorya = new DataTable('#myTablecategorya', {
                responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
        }); 

        let topupsTT = new DataTable('#topupsTT', {
                responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
                    columnControl: [ ['search'] ],
        });

        let myTablecategoryb = new DataTable('#myTablecategoryb', {
                responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
        }); 
        let myTablecategoryc = new DataTable('#myTablecategoryc', {
                responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
        }); 
        let myTablecategoryd = new DataTable('#myTablecategoryd', {
                responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
        }); 

        let myTablecategoryahold = new DataTable('#myTablecategoryahold', {
                responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
        }); 
        let myTablecategorybhold = new DataTable('#myTablecategorybhold', {
                            responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
        }); 
        let myTablecategorychold = new DataTable('#myTablecategorychold', {
                            responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
        }); 
        let myTablecategorydhold = new DataTable('#myTablecategorydhold', {
                            responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
        }); 

        let myTableiclientmasterhold = new DataTable('#myTableiclientmasterhold', {
                responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
                lengthMenu: [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]],
                columnControl: [ ['search'] ]
        }); 

        let myTableiclientmaster = new DataTable('#myTableiclientmaster', {
                responsive: true,
                    dom: 'Bflrtip',
                    buttons: [
                        'excel'
                    ],
                lengthMenu: [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]],
                columnControl: [ ['search'] ]
        }); 
        // ---- Master salaries: server-side ------------------------------------------------
        (function () {
            const $t = $('#myTableimaster');
            const canEdit = String($t.data('can-edit')) === '1';
            const money = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const filterVal = id => document.getElementById(id).value;
            const moneyCols = ['basic_salary','allowances','airtime_allowance','overtime','reimbursements','transport_allowance',
                'ssnit_tier2_5','ssnit_tier2_5d','tax','ssnit_tier1_0_5','welfare','maintenance','absent','boot','iou','hostel',
                'insurance','reprimand','scouter','raincoat','meal','loan','walkin','amnt_ded_cof_start_date','other_deductions',
                'gross_salary','total_deductions','net_salary','ssnit_comp_cont_13','ssnit_tobe_paid13_5','cost_to_company'];

            // Selected salary ids and edited hold reasons survive paging, sorting and searching.
            const selected = new Set();
            const reasons = new Map();
            let lastParams = {};

            const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

            const table = $t.DataTable({
                processing: true,
                serverSide: true,
                pageLength: 50,
                searchDelay: 400,
                scrollX: true,
                scrollY: 600,
                scrollCollapse: true,
                fixedHeader: { header: true },
                ajax: {
                    url: $t.data('source'),
                    type: 'GET',
                    data: function (d) {
                        d.month = $t.data('month');
                        d.status = filterVal('m_status');
                        d.payment_type = filterVal('m_type');
                        d.field_id = filterVal('m_field');
                        d.category = filterVal('m_category');
                        d.priority = filterVal('m_priority');
                        lastParams = DtCompact.request(d);   // 53 columns: full DataTables params exceed the URL limit
                        return lastParams;
                    },
                    dataSrc: function (json) {
                        const t = json.totals || {};
                        $('#masterTotals').text(json.recordsFiltered + ' salaries shown' +
                            ' | Gross GH₵ ' + money.format(t.gross || 0) +
                            ' | Deductions GH₵ ' + money.format(t.deductions || 0) +
                            ' | Net GH₵ ' + money.format(t.net || 0) +
                            ' | Cost to company GH₵ ' + money.format(t.ctc || 0));

                        const p = json.priority || {};
                        const u = p.urgent || {}, pr = p.priority || {};
                        let html = '';
                        if (u.total) html += '<button type="button" class="btn btn-sm btn-danger js-priority-pick" data-priority="urgent">'
                            + '<i class="bx bxs-bolt"></i> Pay first: ' + u.unpaid + ' unpaid of ' + u.total + '</button>';
                        if (pr.total) html += '<button type="button" class="btn btn-sm btn-warning js-priority-pick" data-priority="priority">'
                            + '<i class="bx bx-time-five"></i> Pay early: ' + pr.unpaid + ' unpaid of ' + pr.total + '</button>';
                        if (u.held) html += '<span class="alert alert-danger py-1 px-2 mb-0 small"><i class="bx bx-error"></i> '
                            + u.held + ' pay-first salar' + (u.held === 1 ? 'y is' : 'ies are') + ' on hold or rejected.</span>';
                        if (pr.held) html += '<span class="alert alert-warning py-1 px-2 mb-0 small"><i class="bx bx-error"></i> '
                            + pr.held + ' pay-early salar' + (pr.held === 1 ? 'y is' : 'ies are') + ' on hold or rejected.</span>';
                        $('#masterPriority').html(html);
                        return json.data;
                    },
                },
                order: [],   // no column => server orders pay-first, then pay-early, then by name
                columnControl: [{ target: 1, content: ['search'] }],
                lengthMenu: [[25, 50, 100, 250, 500], [25, 50, 100, 250, 500]],
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: id => canEdit
                        ? '<input class="masterBox form-check-input" type="checkbox" value="' + id + '"' + (selected.has(id) ? ' checked' : '') + ' />' : '' },
                    { data: 'action', orderable: false, searchable: false },
                    { data: 'payment_status' },
                    { data: 'hold_reason_raw', orderable: false, render: function (raw, type, row) {
                        const value = reasons.has(row.id) ? reasons.get(row.id) : raw;
                        return canEdit
                            ? '<textarea class="form-control form-control-sm masterReason" data-id="' + row.id + '" rows="1">' + esc(value) + '</textarea>'
                            : esc(value);
                    } },
                    { data: 'category' }, { data: 'salary_id' }, { data: 'salary_month', orderable: false },
                    { data: 'employee_id' }, { data: 'name' }, { data: 'department' }, { data: 'role' }, { data: 'field' },
                    { data: 'worker_type' }, { data: 'client' }, { data: 'location' }, { data: 'invoice_status', orderable: false },
                    { data: 'ssnit_number', orderable: false }, { data: 'tin_number', orderable: false }, { data: 'payment_type' },
                    { data: 'bank' }, { data: 'branch', orderable: false }, { data: 'account_number', orderable: false },
                ].concat(moneyCols.map(c => ({ data: c, className: 'text-end' }))),
            });

            function refreshSelection() {
                if (!canEdit) return;
                const n = selected.size;
                $('#masterSelectionText').text(n === 0 ? 'No salary selected.' : n + ' salary(ies) selected across all pages.');
                $('#masterClear').toggleClass('d-none', n === 0);
                $('#masterForm [data-action]').prop('disabled', n === 0);
                const $boxes = $t.find('tbody .masterBox');
                $('#masterOptions').prop('checked', $boxes.length > 0 && $boxes.filter(':checked').length === $boxes.length);
            }

            $t.on('change', 'tbody .masterBox', function () {
                this.checked ? selected.add(Number(this.value)) : selected.delete(Number(this.value));
                refreshSelection();
            });
            $t.on('input', 'tbody .masterReason', function () { reasons.set(Number(this.dataset.id), this.value); });
            // Header checkbox is cloned into the scroll header by DataTables, so bind by delegation.
            $(document).on('change', '#masterOptions', function () {
                const on = this.checked;
                $t.find('tbody .masterBox').each(function () {
                    if (this.checked !== on) { this.checked = on; $(this).trigger('change'); }
                });
            });
            $('#masterClear').on('click', function (e) { e.preventDefault(); selected.clear(); table.draw(false); });
            $('.master-filter').on('change', () => table.draw());

            // Priority buttons in the summary toggle the "Pay priority" filter.
            $('#masterPriority').on('click', '.js-priority-pick', function () {
                const v = $(this).data('priority');
                $('#m_priority').val($('#m_priority').val() === v ? '' : v).trigger('change');
            });
            table.on('draw.dt', refreshSelection);

            $('#masterForm [data-action]').on('click', function () {
                $('#salaries_bulk_action_type').val(this.dataset.action);
            });
            $('#masterForm').on('submit', function (e) {
                const action = $('#salaries_bulk_action_type').val();
                const label = action === 'topup' ? 'add top ups for' : 'HOLD';
                if (selected.size === 0 || !confirm('Kindly confirm: ' + label + ' ' + selected.size + ' salary(ies)?')) { e.preventDefault(); return; }
                const $box = $('#masterSelectionInputs').empty();
                selected.forEach(function (id) {
                    $box.append($('<input type="hidden" name="salary[]">').val(id));
                    if (reasons.has(id)) $box.append($('<input type="hidden">').attr('name', 'hold_reason[' + id + ']').val(reasons.get(id)));
                });
            });

            // Export every row matching the current filters (compact params keep the URL short).
            $('#masterExport').on('click', function () {
                const q = new URLSearchParams({ month: $t.data('month') });
                ['status', 'payment_type', 'field_id', 'category', 'priority'].forEach(k => { if (lastParams[k]) q.set(k, lastParams[k]); });
                if (lastParams.search && lastParams.search.value) q.set('search[value]', lastParams.search.value);
                if (lastParams.order && lastParams.order[0]) {
                    q.set('order[0][column]', lastParams.order[0].column);
                    q.set('order[0][dir]', lastParams.order[0].dir);
                }
                DtCompact.eachSearch(lastParams.columns, (i, v) => q.set('columns[' + i + '][columnControl][search][value]', v));
                (window.salariesNavigate || (u => { window.location.href = u; }))($t.data('export') + '?' + q.toString()); // overridable by tests, like DtRange.navigate
            });

            // A table initialised inside a hidden tab measures 0px wide; re-measure when shown.
            $('button[data-bs-toggle="tab"], button[data-bs-toggle="pill"]').on('shown.bs.tab', () => table.columns.adjust());
        })();

    </script>

    <script>
        $(document).ready(function() {

        });


            $(document).ready(function() {
            $('#topups').change(function() {
                $('.checkBoxestopups').prop('checked', function(i, val) {
                    return !val;
                });
            });
        });
    </script>

    @endsection
</x-hr-dashboard>
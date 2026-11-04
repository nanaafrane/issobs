<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.5/css/dataTables.dataTables.css" />
    <!-- <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.5/css/buttons.dataTables.css">     -->
    <link href="https://cdn.datatables.net/columncontrol/1.1.1/css/columnControl.dataTables.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/4.0.5/css/fixedHeader.dataTables.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/5.0.5/css/fixedColumns.dataTables.css">

    @endsection


  @section('side_nav')
    @include('partials.payroll_side_nav')
  @endsection


  @section('content')

  <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bx-transfer-alt"></i> {{ $bank->name }}   @if (isset($month)) <strong> / For Month: {{  \Carbon\Carbon::parse($month)->format('F Y') }}</strong> @endif </h3>

            </div>
        </div><br>

         @if(Auth::user()->hasRole(['Invoice','Manager', 'Finance Manager' ]))
        <div class="row">
            <div class="col-lg-12 mb-4">
                <div  class="card h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img
                                    src="{{ asset('img/icons/unicons/paypal.png') }}"
                                    alt="chart success"
                                    class="rounded" />
                            </div>
                        </div>
                        <p class="mb-1"><strong> BANK NAME :  {{ strtoupper($bank->name) }}  </strong> </p> <br>
                        <h4 class="card-title mb-3 text-white"><strong> GH&#x20B5;  {{ number_format($BankSalaries->sum('net_salary'), 2) }} </strong> </h4> <br>
                        <h6  class="card-title mb-3 text-white">TOTAL PENDING : GH&#x20B5;  {{ number_format($BankSalariespending->sum('net_salary'), 2) }}   |   PENDING EMPLOYEES :  {{ $BankSalariespending->count() }}  </h6> 
                        <h6  class="card-title mb-3 text-white"> TOTAL APPROVED : GH&#x20B5; {{ number_format($BankSalariesapproved->sum('net_salary'), 2) }} |   APPROVED EMPLOYEES :  {{ $BankSalariesapproved->count() }} </h6> 
                        <h6  class="card-title mb-3 text-white"> TOTAL HOLD : GH&#x20B5; {{ number_format($BankSalarieshold->sum('net_salary'), 2) }}  |   HOLD EMPLOYEES :  {{ $BankSalarieshold->count() }} </h6> <hr>
                        <h6  class="card-title mb-3 text-white"> OVERALL TOTAL  : GH&#x20B5; {{ number_format($BankSalariesAll->sum('net_salary'), 2) }} |   OVERALL EMPLOYEES :  {{ $BankSalariesAll->count() }} </h6>

                    </div>
                </div>
            </div>
        </div> <br>
        @endif
       
        <div class="card-header  ml-2  d-none d-lg-block">
                  @include('flash-messages')
        </div> <br>
      
        <!-- Table -->  
        <hr> <br>
        <div class="row">
            @include('partials.pay_priority_summary', ['rows' => $BankSalaries, 'held' => $BankSalarieshold, 'holdUrl' => route('salaries.bankholdMonth', [$bank->id, \Carbon\Carbon::parse($month)->format('Y-m-d')])])
            @include('partials.invoice_status_summary', ['rows' => $BankSalaries, 'clientPayments' => $clientPayments, 'month' => $month])
            <form action="/salariesDeleteMultiple" method="POST">
                @csrf
                    <div class="col-lg-12 mb-4">
                            <input type="hidden" name="action_type" id="action_type" value="" />
                            <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />

                        @include('partials.payment_run_banner', ['month' => $month])
                            <div class="form-check form-check-inline">
                                 @if(Auth::user()->hasRole(['Finance Manager']))
                                <button class="btn btn-success" data-loading="true" onclick="document.getElementById('action_type').value='approve'; return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-recycle"> </i> {{ __('Approve') }}</button>                   
                                <button class="btn btn-primary" data-loading="true" onclick="document.getElementById('action_type').value='unapprove'; return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-recycle"> </i> {{ __('UnApprove') }}</button>                   
                                @endif
                                <a href="/exportBank/{{ $month }}/{{ $bank->id }}" class="btn btn-dark m-4" > <i class="icon-base bx bx-bxs-file-plus"> </i> {{ __(' Excel Bank Download') }}</a>                   
                            </div>

                            
                        <div class="card">
                            <h5 class="card-header"> Salaries Paid via Banks  </h5>
                            <div class="card-body"> 
                            <div class="table-responsive text-nowrap">
                                <table class="display" id="myTable">
                                    <thead class="table-border-bottom-0">
                                        <tr>
                                            <th></th>
                                            <th>#</th>
                                            <th>PAY</th>
                                            <th>STAFF ID</th>
                                            <th>STATUS</th>
                                            <th>CATEGORY</th>
                                            <th> NAME </th>
                                            <th>FIELD</th>
                                            <th> ROLE</th>
                                            <th>CLIENT</th>
                                            <th>CLIENT INVOICE</th>
                                            <th>LOCATION</th>
                                            <th>BRANCH CODE</th>
                                            <th>BRANCH</th>
                                            <th>ACCOUNT NUMBER</th>
                                            <th>  GH&#x20B5; NET SALARY</th>
                                            <th>CREATED BY</th>
                                            <th>UPDATED</th>
                                            <th>UPDATED BY</th>
                                            <th>PERIOD</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        @foreach($BankSalaries as $key => $salary)
                                        <tr>
                                            <td> <input class="checkBoxes form-check-input" type="checkbox" name="salary[]" value="{{ $salary->id }}" /> </td>
                                            <td> {{ $key + 1 }} </td>
                                            <td data-order="{{ (int) $salary->pay_priority }}" data-search="pay-{{ ['normal', 'priority', 'urgent'][(int) $salary->pay_priority] ?? 'normal' }}"><x-pay-priority :level="$salary->pay_priority" :reason="$salary->pay_priority_reason" /></td>
                                            <td> FWSS{{ $salary->employee?->id }} </td>
                                                                                                                                        
                                                    @if($salary->payment_status == 'pending')
                                                        <td> <span class="badge bg-label-info"> {{ $salary->payment_status }} </span>  <br> {{ $salary->status2 }} <br> {{$salary->approval_date?->format('F l d, Y')}} <br> {{  $salary->user2?->name }} </td>
                                                    @else 
                                                        <td> <span class="badge bg-label-success"> {{ $salary->payment_status }} </span> <br> {{ $salary->status2 }} <br> {{$salary->approval_date?->format('F l d, Y')}} <br> {{  $salary->user2?->name }}  </td>
                                                    @endif
                                            <td>
                                                @foreach($categories as $category)
                                                    @if($salary->client_id == $category->client_id)
                                                        {{ $category->name }}
                                                    @endif
                                                @endforeach
                                            </td>
                                            <td> {{ strtoupper($salary->employee?->name) }} </td>
                                            <td> {{ strtoupper($salary->field?->name) }} </td>
                                            <td> {{ strtoupper( $salary->employee?->role?->name) }} </td>
                                            <td> {{ $salary->client?->name || $salary->client?->business_name ? strtoupper($salary->client?->name) . strtoupper($salary->client?->business_name) :  strtoupper($salary->location) }} </td>
                                            @php $inv = $clientPayments[$salary->client_id] ?? null; @endphp
                                            <td data-search="{{ $inv ? \App\Support\ClientInvoiceStatus::searchKey($inv['status']) : 'inv-noclient' }}"
                                                data-order="{{ $inv ? ['paid' => 0, 'paid_late' => 1, 'part' => 2, 'unpaid' => 3, 'none' => 4][$inv['status']['status']] + ($inv['status']['overdue'] ? 1 : 0) : 9 }}">
                                                @if($inv)
                                                    {!! \App\Support\ClientInvoiceStatus::badge($inv['status']) !!}
                                                    <div class="mt-1">{!! \App\Support\ClientInvoiceStatus::recordBadge($inv['record']) !!}</div>
                                                @else
                                                    <span class="text-muted small">No client</span>
                                                @endif
                                            </td>
                                            <td> {{ strtoupper($salary?->location) }} </td>
                                            <td> {{ $salary->paymentInfo?->branch_code }} </td>
                                            <td> {{ strtoupper($salary->branch)}} </td>
                                            <td> {{ $salary->account_number }} </td>
                                            <td> {{ number_format($salary->net_salary, 2) }} </td>
                                            <td> {{  $salary->user?->name }} </td>
                                            <td> {{$salary->updated_at->format('F l d, Y, H:i A')}} </td>
                                            <td> {{ $salary->user1?->name }} </td>
                                            <td> {{$salary->updated_at->diffForHumans()}} </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                        <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item" href="{{url('salaries', $salary->id)}}"><i class="icon-base bx bxs-bullseye"></i> view</a>
                                                        <a class="dropdown-item" href="/salaries/{{$salary->id}}/edit"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>

                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            </div>
                        </div>
                    </div>
            </form>
        </div>



    </div>
  <!-- / Content -->

  @endsection


    @section('scripts')

    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.3.5/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/5.0.5/js/dataTables.fixedColumns.js"> </script>           
    <script src="https://cdn.datatables.net/fixedcolumns/5.0.5/js/fixedColumns.dataTables.js"></script>     
    <script src="https://cdn.datatables.net/fixedheader/4.0.5/js/dataTables.fixedHeader.js"></script>      
    <script src="https://cdn.datatables.net/fixedheader/4.0.5/js/fixedHeader.dataTables.js"></script>  
    <script src="https://cdn.datatables.net/columncontrol/1.1.1/js/dataTables.columnControl.min.js"></script>

    <!-- <script src="https://cdn.datatables.net/buttons/3.2.5/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.colVis.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script> -->


    <script>
       
    //   new DataTable('#myTable', {
    //     //  dom: 'Blfrtip',
    //     //  stateSave: false,
    //     columnControl: [ ['search'] ],
    //     layout: {
    //         topStart: {
    //             buttons: [ 
    //             {
    //                  extend: 'pageLength',
    //                 text: 'Show',
    //                 className: 'btn btn-secondary',
    //                 Options: [10, 25, 50, 100, 250, 500, 1000, 2000], 
    //             },
    //                 {
    //                     extend: 'excelHtml5',
    //                     title: 'Salaries',
    //                     className: 'btn btn-secondary',
    //                     exportOptions: {
    //                         columns: ':visible'
    //                     }
    //                 },
    //             ]
    //         }
    //     },
                
    // });


        //     new DataTable('#myTable', {
        //     responsive: true,
        //       dom: 'flip',
        //       lengthMenu: [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]],
        //       columnControl: [ ['search'] ]

              
        // });

        new DataTable('#myTable', {
            order: [[2, 'desc']],   // PAY column: pay first, then pay early (rows arrive name-sorted; sort is stable)
              columnControl: [ ['search'] ],
                    fixedColumns: {
                    start: 0,
                    end: 0
                },
                fixedHeader: {
                    header: true,
                    footer: true
                },
                paging: false,
                scrollCollapse: true,
                scrollX: true,
                scrollY: 500
        });

    </script>

    <script>
        $(document).ready(function() {
            $('#options').change(function() {
                $('.checkBoxes').prop('checked', function(i, val) {
                    return !val;
                });
            });
        });
    </script>

        @include('partials.pay_priority_script', ['table' => '#myTable', 'col' => 2])
        @include('partials.invoice_status_script', ['table' => '#myTable', 'col' => 10])

    @endsection
</x-hr-dashboard>
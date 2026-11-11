<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="{{ asset('libs/datatables/2.3.5/css/dataTables.dataTables.css') }}" />
    <link rel="stylesheet" href="{{ asset('libs/datatables/buttons/3.2.5/css/buttons.dataTables.css') }}">    
    <link href="{{ asset('libs/datatables/columncontrol/1.1.1/css/columnControl.dataTables.min.css') }}" rel="stylesheet">  
    @endsection


  @section('side_nav')
    @include('partials.payroll_side_nav')
  @endsection


  @section('content')

  <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bx-transfer-alt"></i> {{ $field->name }}   @if (isset($month)) <strong> / For Month: {{  \Carbon\Carbon::parse($month)->format('F Y') }}</strong> @endif </h3>

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
                        <p class="mb-1"><strong> FIELD OFFICE CASH :  {{ strtoupper($field->name) }}  </strong> </p> <br>
                        <h4 class="card-title mb-3 text-white"><strong> GH&#x20B5;  {{ number_format($CashSalaries->sum('net_salary'), 2) }} </strong> </h4> <br>
                         <h6  class="card-title mb-3 text-white">TOTAL PENDING : GH&#x20B5;  {{ number_format($CashSalariespending->sum('net_salary'), 2) }}   |   PENDING EMPLOYEES :  {{ $CashSalariespending->count() }}  </h6> 
                        <h6  class="card-title mb-3 text-white"> TOTAL APPROVED : GH&#x20B5; {{ number_format($CashSalariesapproved->sum('net_salary'), 2) }} |   APPROVED EMPLOYEES :  {{ $CashSalariesapproved->count() }} </h6> 
                        <h6  class="card-title mb-3 text-white"> TOTAL HOLD : GH&#x20B5; {{ number_format($CashSalarieshold->sum('net_salary'), 2) }}  |   HOLD EMPLOYEES :  {{ $CashSalarieshold->count() }} </h6> <hr>
                        <h6  class="card-title mb-3 text-white"> OVERALL TOTAL  : GH&#x20B5; {{ number_format($CashSalariesAll->sum('net_salary'), 2) }} |   OVERALL EMPLOYEES :  {{ $CashSalariesAll->count() }} </h6>

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
            @include('partials.pay_priority_summary', ['rows' => $CashSalaries, 'held' => $CashSalarieshold, 'holdUrl' => route('salaries.cashholdMonth', [$field->id, \Carbon\Carbon::parse($month)->format('Y-m-d')])])
            @include('partials.invoice_status_summary', ['rows' => $CashSalaries, 'clientPayments' => $clientPayments, 'month' => $month])
            <form action="/salariesDeleteMultiple" method="POST">
                @csrf
                    <div class="col-lg-12 mb-4">
                        <input type="hidden" name="action_type" id="salary_action_type" value="" />
                        <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />
                        @include('partials.payment_run_banner', ['month' => $month])
                        @if(Auth::user()->hasRole(['Finance Manager']))
                        <div class="form-check form-check-inline">
                            <button class="btn btn-success" data-action="approve" onclick="document.getElementById('salary_action_type').value='approve'; return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-recycle"> </i> {{ __('Approve') }}</button>                   
                        </div>

                        <div class="form-check form-check-inline">
                            <button class="btn btn-primary" data-action="unapprove" onclick="document.getElementById('salary_action_type').value='unapprove'; return confirm('Kindly Confirm?')" type="submit"> <i class="icon-base bx bx-recycle"> </i> {{ __('UnApprove') }}</button>                   
                        </div>
                        @endif
                        <div class="card">
                            <h5 class="card-header"> Salaries Paid via Cash  </h5>
                            <div class="card-body"> 
                            <div class="table-responsive text-nowrap">
                                @php $shiftInfo = \App\Support\SalaryShifts::forSalaries($CashSalaries); @endphp
                                @include('salaries._shifts_bar')
                                <table class="display" id="myTable">
                                    <thead class="table-border-bottom-0">
                                        <tr>
                                            <th></th>
                                            <th>#</th>
                                            <th>PAY</th>
                                            <th>STAFF ID</th>
                                            <!-- <th>APPROVED BY</th> -->
                                            <th>STATUS</th>
                                            <th>CATEGORY</th>
                                            <th> NAME </th>
                                            <th>SHIFT</th>
                                            <th>WORKED</th>
                                            <th>FIELD</th>
                                            <th> ROLE</th>
                                            <th>CLIENT</th>
                                            <th>CLIENT INVOICE</th>
                                            <th>LOCATION </th>
                                            <th> NET SALARY </th>
                                            <th>CREATED BY</th>
                                            <th>UPDATED</th>
                                            <th>UPDATED BY</th>
                                            <th>PERIOD</th>
                                            <th>ACTION</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        @foreach($CashSalaries as $key => $salary)
                                        <tr>
                                            <td> <input class="checkBoxes form-check-input" type="checkbox" name="salary[]" value="{{ $salary->id }}" /> </td>
                                            <td> {{ $key + 1 }} </td>
                                            <td data-order="{{ (int) $salary->pay_priority }}" data-search="pay-{{ ['normal', 'priority', 'urgent'][(int) $salary->pay_priority] ?? 'normal' }}"><x-pay-priority :level="$salary->pay_priority" :reason="$salary->pay_priority_reason" /></td>
                                            <td> FWSS{{ $salary->employee?->id }} </td>
                                            <!-- <td>  </td> -->
                                                            @if($salary->payment_status == 'pending')
                                                                <td>  <span class="badge bg-label-info"> {{ $salary->payment_status }} </span> <br> {{ $salary->status2 }} <br> {{$salary->approval_date?->format('F l d, Y')}} <br> {{  $salary->user2?->name }} </td>
                                                            @else 
                                                                <td>  <span class="badge bg-label-success"> {{ $salary->payment_status }} </span> <br> {{ $salary->status2 }} <br> {{$salary->approval_date?->format('F l d, Y')}} <br> {{  $salary->user2?->name }}  </td>
                                                            @endif
                                            <td> 
                                                @foreach($categories as $category)
                                                    @if($salary->client_id == $category->client_id)
                                                        {{ $category->name }}
                                                    @endif
                                                @endforeach
                                            </td>
                                            <td> {{ strtoupper($salary->employee?->name) }} </td>
                                            <td data-order="{{ $shiftInfo[$salary->id]['shift'] ?? 'zz' }}">{!! \App\Support\SalaryShifts::badge($shiftInfo[$salary->id]['shift'] ?? null, $shiftInfo[$salary->id]['snapshot'] ?? true, $shiftInfo[$salary->id]['now'] ?? null) !!}</td>
                                            <td>{!! \App\Support\SalaryShifts::worked($shiftInfo[$salary->id]['worked'] ?? null, $shiftInfo[$salary->id]['shift'] ?? null) !!}</td>
                                            <td> {{ strtoupper($salary->field?->name) }} </td>
                                            <td> {{ $salary->employee?->role?->name }} </td>
                                            <td> {{ $salary->client?->name || $salary->client?->business_name ? $salary->client?->name . $salary->client?->business_name :  $salary->location }} </td>
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
                                            <td> {{  strtoupper($salary?->location) }} </td>
                                            <td> GH&#x20B5; {{ number_format($salary->net_salary, 2) }} </td>
                                            <td>{{  $salary->user?->name }}</td>
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

    <script src="{{ asset('libs/jquery/3.7.1/jquery.js') }}"></script>
    <script src="{{ asset('libs/datatables/2.3.5/js/dataTables.js') }}"></script>
    <script src="{{ asset('libs/datatables/buttons/3.2.5/js/dataTables.buttons.js') }}"></script>
    <script src="{{ asset('libs/datatables/buttons/3.2.5/js/buttons.dataTables.js') }}"></script>
    <script src="{{ asset('libs/datatables/buttons/3.2.5/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('libs/datatables/buttons/3.2.5/js/buttons.colVis.min.js') }}"></script>
    <script src="{{ asset('libs/jszip/3.10.1/jszip.min.js') }}"></script>
    <script src="{{ asset('libs/pdfmake/0.2.7/pdfmake.min.js') }}"></script>
    <script src="{{ asset('libs/pdfmake/0.2.7/vfs_fonts.js') }}"></script>

    <script src="{{ asset('libs/datatables/columncontrol/1.1.1/js/dataTables.columnControl.min.js') }}"></script>


    <script>
       
      new DataTable('#myTable', {
          order: [[2, 'desc']],   // PAY column: pay first, then pay early (rows arrive name-sorted; sort is stable)
        //  dom: 'Blfrtip',
        //  stateSave: false,
        columnControl: [ ['search'] ],
        layout: {
            topStart: {
                buttons: [ 
                {
                     extend: 'pageLength',
                    text: 'Show',
                    className: 'btn btn-secondary',
                    Options: [10, 25, 50, 100, 250, 500, 1000, 2000], 
                },
                    {
                        extend: 'excelHtml5',
                        title:  "{{ $field->name . ' CASH SALARIES ' . \Carbon\Carbon::parse($month)->format('F Y')}}",
                        className: 'btn btn-secondary',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                ]
            }
        },
                
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
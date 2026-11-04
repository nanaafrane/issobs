<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.5/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.5/css/buttons.dataTables.css">   
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
                <h3 class="card-header"> <i class="icon-base bx bx-transfer-alt"></i> Payroll / Bulk Cash Salaries   @if (isset($data))/ History @endif </h3>
            </div>
        </div><br>
          <div class="card-header  ml-2  d-none d-lg-block">
                  @include('flash-messages')
        </div> <br>
        <hr />

            <div class="row">
                <form action="/payCashSalary" method="POST">
                    @csrf
                    <input type="hidden" name="action_type" id="paycash_action_type" value="" />
                    <div class="col">
                        @if ( !isset($data))
                            
                        <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />
                    
                        <div class="form-check form-check-inline">                            
                            <button class="btn btn-success" type="submit" data-action="pay" onclick="document.getElementById('paycash_action_type').value='pay'; return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('PAY SALARIES') }}</button>
                        </div>

                        <div class="form-check form-check-inline">                            
                            <button class="btn btn-dark" type="submit" data-action="confirm" onclick="document.getElementById('paycash_action_type').value='confirm'; return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('CONFIRM') }}</button>
                        </div>
                        <hr>
                        @endif
                        <div class="card"> 
                            <div class="card-body"> 
                                <div class="table-responsive text-normal-dark"> 
                            <table id="myTableimaster" class="display">
                                <thead>
                                    <tr>
                                         @if (!isset($data))
                                        <th></th>
                                        @endif
                                        <th>#</th>
                                        <th>STAFF ID</th>
                                        <th> NAME </th>
                                        <th> CONTACT </th>
                                        <th>FIELD</th>
                                        <th> ROLE</th>
                                        <th>CLIENT</th>
                                        <th>LOCATION </th>
                                        <th> NET SALARY </th>
                                        <th> CHARGES </th>
                                        <th> STATUS </th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                @foreach ($salaries as $key => $salary)

                                    <tr>
                                         @if (!isset($data))
                                        <td> <input class="checkBoxes form-check-input" type="checkbox" name="salary[]" value="{{ $salary->id }}" /> </td>
                                        @endif
                                        <td> {{ $key + 1 }} </td>
                                        <td> FWSS{{ $salary->employee?->id }} </td>
                                        <td> {{ strtoupper($salary->employee?->name) }} </td>
                                        <td> {{ $salary->employee?->phone_number }} </td>
                                        <td> {{ strtoupper($salary->field?->name) }} </td>
                                        <td> {{ $salary->employee?->role?->name }} </td>
                                        <td> {{ $salary->client?->name || $salary->client?->business_name ? $salary->client?->name . $salary->client?->business_name :  $salary->location }} </td>
                                        <td> {{  strtoupper($salary?->location) }} </td>
                                        <td> GH&#x20B5; {{ number_format($salary->net_salary, 2) }} </td>
                                        <td>
                                            @if (isset($data))
                                                 @foreach ($data as $hubtel)
                                                    @if ($salary->hubtel_id == $hubtel['id'])
                                                    GH&#x20B5;  {{ number_format($hubtel['fees'],2) }}
                                                    @endif 
                                                @endforeach
                                            @endif

                                       </td>
                                        @if($salary->status1 == 'Bulk Cash')
                                            <td> <span class="badge bg-label-dark"> {{ $salary->status1 }} </span> </td>
                                        @else
                                            <td> <span class="badge bg-label-danger">  {{ $salary->status1 }} </span> </td>
                                        @endif

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
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.dataTables.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.html5.min.js"></script>

    <script src="https://cdn.datatables.net/columncontrol/1.1.1/js/dataTables.columnControl.min.js"></script>


    <script>
        new DataTable('#myTableimaster', {
            responsive: true,
              dom: 'lpftrip',
              lengthMenu: [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]],
              columnControl: [ ['search'] ]
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

    @endsection
</x-hr-dashboard>
<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">

    <link href="https://cdn.datatables.net/columncontrol/1.1.1/css/columnControl.dataTables.min.css" rel="stylesheet">
    @endsection


  @section('side_nav')
    @include('partials.receipt_side_nav')
  @endsection

  @section('content')

  <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bxs-user-account"></i> Employees Cash For {{ $groupedCashEmployees[0]->field?->name  }}  </h3>
            </div>
        </div><br>


        <div class="card-header  ml-2  d-none d-lg-block">
            @include('flash-messages')
        </div>

        <div class="row">
            <form action="/employeesCashVerify" method="POST">
            @csrf
                <div class="col">
                     @if(Auth::user()->hasRole([ 'Invoice']))
                    <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />

                    <div class="form-check form-check-inline">                            
                        <button class="btn btn-dark" type="submit" name="submit" value="confirm" onclick="return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('VERIFY') }}</button>
                    </div>
                    @endif
                    <hr>

                    <div class="card"> 
                        <div class="card-body"> 
                        <div class="table-responsive text-normal-dark"> 
                        <table id="myTable" class="display">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>#</th>
                                    <th> Employee ID </th>
                                    <th>Name</th>
                                    <th>Number</th>
                                    <th>Channel</th>
                                    <th> MoMo Status </th>
                                    <th> Reg. Name </th>
                                    <th> Description </th>
                                    <th> Code </th>
                                    <th> Employment Date </th>
                                    <th> Department </th>
                                    <th>Role</th>
                                    <th>Field Office</th>
                                    <th>Client </th>
                                    <th> Location </th>
                                    <th>Payment Type</th>
                                    <th>TAX</th>
                                    <th>SSNIT</th>
                                    <th>Basic</th>
                                    <th>Allowance</th>
                                    <th>Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>

                                @foreach ($groupedCashEmployees as $key => $data )
                                <tr>
                                    <td> <input class="checkBoxes form-check-input" type="checkbox" name="employees[]" value="{{ $data->id }}" /> </td>
                                    <td> {{ $key + 1 }} </td>
                                    <td> FWSS {{ $data->id }}  </td>
                                    <td>{{$data->name}}  </td>
                                    <td>{{ $data->phone_number }}  </td>
                                    <td> {{$data->channel }}</td>
                                    <td> 
                                        @if( $data->status_momo == 1)
                                         <i class="icon-base bx bxs-badge-check bg-success"></i>   Verified
                                        @endif
                                    </td>
                                    <td> {{ $data->reg_name }}  </td>
                                    <td> {{ $data->message }}  </td>
                                    <td> {{ $data->responseCode }}  </td>
                                    <td>{{ $data->date_of_joining?->format('F, Y') }} </td>
                                    <td> {{ $data->department?->name }} </td>
                                    <td> {{ $data->role?->name }}  </td>
                                    <td> {{ $data->field?->name }}   </td>
                                    <td>{{ $data->client?->name }} {{ $data->client?->business_name }} </td>
                                    <td> {{ $data->location }} </td>
                                    <td> {{ $data->payment_type }}  </td>
                                
                                    @if($data->tax_button == 'on')
                                    <td> <span class="badge bg-label-dark"> {{  $data->tax_button }} </span> </td>
                                    @else
                                    <td> <span class="badge bg-label-danger"> OFF </span> </td>
                                    @endif

                                    @if($data->ssnit_button == 'on')
                                    <td> <span class="badge bg-label-dark"> {{  $data->ssnit_button }} </span> </td>
                                    @else
                                    <td> <span class="badge bg-label-danger"> OFF </span> </td>
                                    @endif
                                    <td> {{$data->basic_salary}} </td>
                                    <td> {{$data->allowances}} </td>

                                    <td>{{ $data->created_at?->format('F, Y') }} </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                            <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="{{url('employees', $data->id)}}"><i class="icon-base bx bxs-bullseye"></i> view</a>
                                            <a class="dropdown-item" href="{{url('employees', $data->id)}}/edit"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>

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
              dom: 'lftrip',
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
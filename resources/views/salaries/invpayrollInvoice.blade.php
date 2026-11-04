<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">
    @endsection


  @section('side_nav')
    @include('partials.payroll_side_nav')
  @endsection


  @section('content')

  <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bx-git-compare"></i> Invoices For Month : {{ $month }} </h3>
            </div>
        </div><br>
            <div class="row ">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>INVOICES</h4>
                        </div>
                        <div class="card-header">
                            <div class="table-responsive text-normal-dark">
                                <!-- <div class="card-body demo-vertical-spacing demo-only-element"> Clients </div> -->
                                <table id="myTable" class="display">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Invoice No.</th>
                                            <th>Invoice Month</th>
                                            <th>Client Name</th>
                                            <th>Phone No.</th>
                                            <th>Business Name </th>
                                            <th> Field Office </th>
                                            <th> Staff </th>
                                            <th>Date Created</th>
                                            <th>Due Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>View</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        @foreach($invoices as $key => $invoice)
                                        <tr>
                                            <td>{{$key +1 }}</td>
                                            <td> #FWSSi{{$invoice->id}} </td>
                                            <td> {{ $invoice->invoice_month?->format('F, Y') }}</td>
                                            <td> {{$invoice->client->name}}</td>
                                            <td> {{$invoice->client->phone_number}} </td>
                                            <td> {{$invoice->client->business_name}} </td>
                                            <td> {{$invoice->client->field->name}} </td>
                                            <td> {{$invoice->user->name}} </td>
                                            <td> {{$invoice->created_at->format('F l d, Y, H:i A')}} </td>
                                            <td> {{$invoice->due_date->diffForHumans()}} </td>
                                            <td> GH&#x20B5; {{number_format($invoice->total,2)}} </td>
                                            @if($invoice->status == 'completed')
                                            <td><span class="badge bg-label-success">{{$invoice->status}}</span></td>
                                            @else
                                            <td><span class="badge bg-label-danger">{{$invoice->status}}</span></td>
                                            @endif
                                            <td> 
                                                <a class="dropdown-item" href="{{url('invoice', $invoice->id)}}"><i class="icon-base bx bxs-bullseye"></i> </a>
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


    <script>
        new DataTable('#myTable', {
            responsive: true,

            layout: {
              topStart: {
                // buttons: [] // No export/download buttons
              }
            }
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






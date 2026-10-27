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
                <h3 class="card-header"> <i class="icon-base bx bxs-user-account"></i>  Attach Guard </h3>
            </div>
        </div><br>


        <div class="row">
            <div class="col-xxl-12 mb-6 order-0">
                <div class="card bg-dark">
                    <div class="d-flex align-items-start row">
                        <div class="col-sm-7">
                            <div class="card-body">
                               <p class="mb-1 text-white"><strong>  </strong> </p>
                                <h1 class="text-white">  </h1>
                                <h6 class="card-title text-white mb-3">TOTAL NUMBER OF GUARDS </h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> <br><br>

        <div class="card-header  ml-2  d-none d-lg-block">
            @include('flash-messages')
        </div>


          <div class="row">

            <form action="/employeesGuardReAassign"  method="POST">
              @csrf

              <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />

              <div class="form-check form-check-inline">
                  <select name="employees" class="form-select">
                      <option value=""> Select All </option>
                  </select>
              </div>

              <div class="form-check form-check-inline">
                <button class="btn btn-dark" type="submit" onclick="return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('Re-Assign') }}</button>
              </div>

              <div class="col">
                  <table id="myTable" class="display">
                      <thead>
                          <tr>
                              <th> </th>

                          </tr>
                      </thead>
                      <tbody>

                          <tr>
                              <td> </td>
                              
                          </tr>
                      </tbody>
                  </table>
              </form>

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

    <script src="https://cdn.datatables.net/columncontrol/1.1.1/js/dataTables.columnControl.min.js"></script>
    <script>
        new DataTable('#myTable', {
            responsive: true,
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
                            title: 'Employees',
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
        new DataTable('#myTable', { responsive: true, });
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
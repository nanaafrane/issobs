<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="{{ asset('libs/datatables/2.3.5/css/dataTables.dataTables.css') }}" />
    <link rel="stylesheet" href="{{ asset('libs/datatables/buttons/3.2.5/css/buttons.dataTables.css') }}">    
    <link href="{{ asset('libs/datatables/columncontrol/1.1.1/css/columnControl.dataTables.min.css') }}" rel="stylesheet">
    @endsection


  @section('side_nav')
  <!-- Menu -->

    @include('partials.payroll_side_nav')
  <!-- / Menu -->
  @endsection


  @section('content')

  <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bx-bxs-user-detail"></i> Payroll / Salaries </h3>
            </div>
        </div><br>

                <hr />

        <div class="row">
                <form action="/salariesCreate" method="GET">
                    @csrf
                    <div class="col">

                        <label for="month" class="form-label"> <strong>   CHOOSE A MONTH TO SEARCH </strong> </label> <br>

                        <div class="form-check form-check-inline">
                            <input type="month" class="form-control" name="month" required/> <br>
                            
                            <button class="btn btn-dark" type="submit"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('') }}</button>
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
                        title: 'Salaries',
                        className: 'btn btn-secondary',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    'colvis'
                ]
            }
        },
                  columnDefs: [
              {
                  targets: [0,1],
                  visible: false
              }
          ]
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
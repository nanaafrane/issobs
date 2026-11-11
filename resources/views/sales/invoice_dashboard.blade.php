<x-sales-dashboard>
    @section('side_nav')
        @include('partials.receipt_side_nav')
    @endsection

    @section('css')
    <link rel="stylesheet" href="{{ asset('libs/datatables/2.3.3/css/dataTables.dataTables.css') }}" />
    <link rel="stylesheet" href="{{ asset('libs/datatables/buttons/3.2.4/css/buttons.dataTables.css') }}">
    <link href="{{ asset('libs/datatables/columncontrol/1.1.1/css/columnControl.dataTables.min.css') }}" rel="stylesheet">

    @endsection

    @section('content')

    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <form action="/invoiceSearch" method="GET">
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


    </div>
    @endsection


    @section('scripts')

    <script src="{{ asset('libs/jquery/3.7.1/jquery.js') }}"></script>
    <script src="{{ asset('libs/datatables/2.3.3/js/dataTables.js') }}"></script>
    <script src="{{ asset('libs/datatables/buttons/3.2.4/js/dataTables.buttons.js') }}"></script>
    <script src="{{ asset('libs/datatables/buttons/3.2.4/js/buttons.dataTables.js') }}"></script>
    <script src="{{ asset('libs/jszip/3.10.1/jszip.min.js') }}"></script>
    <script src="{{ asset('libs/pdfmake/0.2.7/pdfmake.min.js') }}"></script>
    <script src="{{ asset('libs/pdfmake/0.2.7/vfs_fonts.js') }}"></script>
    <script src="{{ asset('libs/datatables/buttons/3.2.4/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('libs/datatables/columncontrol/1.1.1/js/dataTables.columnControl.min.js') }}"></script>


    <script>
        new DataTable('#myTable', {
            responsive: true,

            layout: {
              topStart: {
                buttons: [ 
                {
                     extend: 'pageLength',
                    text: 'Show',
                    className: 'btn btn-secondary',
                    Options: [10, 25, 50, 100, 500], 
                },
                    {
                        extend: 'excelHtml5',
                        title: 'Receipts',
                        className: 'btn btn-secondary',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    'colvis'
                ]
            }

            },
            columnControl: [
                ['search']
            ]
        });
    </script>


    @endsection

</x-sales-dashboard>
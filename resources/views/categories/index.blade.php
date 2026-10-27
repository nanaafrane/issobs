<x-sales-dashboard>

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
                <h3 class="card-header text-info"> <i class="icon-base bx bx-bxs-receipt"></i> Categories </h3>
            </div>
        </div><br>

        <div class="row">
            <div class="col-12">
                <a href="{{ url('categoryClients') }}" class="btn btn-info">
                    <i class="icon-base bx bx-list-check"></i> Assign Active Clients For A Month
                </a>
            </div>
        </div><br>

        <div class="row">
                <form action="/categorySearch" method="GET">
                    @csrf
                    <div class="col">

                        <label for="month" class="form-label"> <strong>   CHOOSE A MONTH TO SEARCH </strong> </label> <br>

                        <div class="form-check form-check-inline">
                            <input type="month" class="form-control" name="month" required/> <br>
                            
                            <button class="btn btn-dark" type="submit"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('') }}</button>
                        </div>
                    </div>
                </form>
           

        </div> <br> <br>


        @if ( isset($categories) && count($categories) > 0 )

        <div class="col-lg-8">
            <div  class="card h-100 bg-info text-white">
                <div class="card-body">
                    <p class="mb-1"><strong> TOTAL CATEGORIES CLIENTS FOR : {{ strtoupper($categories[0]->category_month?->format('F Y')) }}  </strong></p>
                    <h4 class="card-title mb-3 text-white"><strong> {{ $categories->count() }} </strong> </h4> 
                </div>
            </div>
        </div> <br> <br>

        <div class="row">
            <div class="col-8">
                <table id="myTable" class="display">
                    <thead>
                        <tr>
                            <th> #</th>
                            <th>Client Name</th>
                            <th>Field</th>
                            <th> Name</th>
                            <th> Month</th>
                            <th>Period</th>
                            <th>Staff</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach($categories as $key => $category)
                        <tr>
                            <td> {{$key + 1}} </td>
                            <td> {{ $category->client?->name }} {{ $category->client?->business_name }} </td>
                            <td> {{ $category->client?->field?->name }} </td>
                            <td> {{$category?->name}}</td>
                            <td> {{ $category->category_month?->format('F Y') }} </td>
                            <td> {{ $category->updated_at?->format('F l d, Y, H:i A') }} </td>
                            <td> {{ $category->user?->name }} </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="categories/{{$category?->id}}/edit"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>


                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>

        </div> 
        @else

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-danger"> <i class="icon-base bx bx-error-alt"></i> No Categories,  Please select a month to view categories.  </h5>
                    </div>      
                </div>
            </div>
        </div>

        @endif

        
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
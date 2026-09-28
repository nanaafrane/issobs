<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.5/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.5/css/buttons.dataTables.css">    
    @endsection


  @section('side_nav')

    @include('partials.payroll_side_nav')
    
  @endsection


  @section('content')

  <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bx-transfer-alt"></i> Payroll / Transaction </h3>
            </div>
        </div><br>
        <hr />

        <div class="row">
                <form action="/salariesMonth" method="GET">
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
</x-hr-dashboard>
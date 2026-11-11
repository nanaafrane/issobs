<x-hr-dashboard>

    @section('css')
    <link rel="stylesheet" href="{{ asset('libs/datatables/2.3.5/css/dataTables.dataTables.css') }}" />
    <link rel="stylesheet" href="{{ asset('libs/datatables/buttons/3.2.5/css/buttons.dataTables.css') }}">    
    @endsection


  @section('side_nav')
    @include('partials.payroll_side_nav')
  @endsection


  @section('content')

  <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bx-transfer-alt"></i> Payroll / Bulk Cash Salaries </h3>
            </div>
        </div><br>
        <hr />

        <div class="row">
            <div class="col">
                <h4> Run </h4>
 
                <form action="/salariesBulkCashm" method="GET">
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

            <div class="col">
                <h4> Check History </h4>

                <form action="/salariesBulkHisory" method="GET">
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



    </div>
  <!-- / Content -->

  @endsection
</x-hr-dashboard>
<x-hr-dashboard>

  @section('side_nav')
    @include('partials.payroll_side_nav')
  @endsection


  @section('content')

  <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-12">
                <h3 class="card-header"> <i class="icon-base bx bx-git-compare"></i> Invoices to Payroll </h3>
            </div>
        </div><br>
        <hr />

        <div class="card-header  ml-2  d-none d-lg-block">
            @include('flash-messages')
        </div> <br>
        <div class="row">
            <form action="/invToPayroll" method="GET">
                @csrf
                <div class="col">

                    <label for="" class="form-label"> <strong>   CHOOSE A MONTH </strong> </label> <br>

                    <div class="form-check form-check-inline">
                         <input type="month" class="form-control" name="month" required/> <br>
                        
                        <button class="btn btn-dark" type="submit" onclick="return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('') }}</button>
                    </div>


                </div>

            </form>
        </div>
    </div>
  <!-- / Content -->
  @endsection


</x-hr-dashboard>
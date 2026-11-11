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
                <h3 class="card-header"> <i class="icon-base bx bx-transfer-alt"></i> Payroll / Send Money </h3>
            </div>
        </div><br>
        <hr />

        <div class="row">
                <form action="/sendMoney" method="POST">
                    @csrf
                    <div class="row">

                        <div class="mb-3 col-md-4">
                        <label for="recipient_name" class="form-label"> <strong>  Recipient Name * </strong> </label>
                        <input  class="form-control @error('recipient_name') is-invalid @enderror" type="text" id="recipient_name" name="recipient_name" />
                            @error('recipient_name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        
                        <button class="btn btn-dark" type="submit" onclick="return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('') }}</button>

                    </div>      
                </form>
        </div>



    </div>
  <!-- / Content -->

  @endsection
</x-hr-dashboard>
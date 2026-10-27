<x-sales-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">

    <link href="https://cdn.datatables.net/columncontrol/1.1.1/css/columnControl.dataTables.min.css" rel="stylesheet">
    @endsection


    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

@section('content')
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">

            <!-- <div class="container py-4"> -->
                <h4 class="mb-4">Edit Expense</h4>
            
                <form action="{{ route('expense.update', $expense) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('expenses._form')
                </form>
            <!-- </div> -->

        </div>
    </div>
    @endsection


</x-sales-dashboard>
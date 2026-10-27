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

    <div class="container-xxl flex-grow-1 container-p-y">

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="bx bx-bxs-user-detail me-2"></i>Pending Clients</h3>
                </div>
            </div>
        </div>

        <!-- Summary Statistics Cards -->
        @if(Auth::user()->hasRole(['Invoice', 'Finance Manager']) || (Auth::user()->department?->name == 'HR' && Auth::user()->role?->name == 'Manager') )
        <div class="row mb-4">
                <div class="col-12">
                     <div class="card border-left-dark bg-warning text-white h-100 py-2">
                    <div class="card-body">
                        <div class="text-uppercase mb-1"><small class="fw-bold">Total Clients / Guards</small></div>
                        <div class="h3 text-white mb-0 fw-bold">{{  $clients->count() }} <span class="text-muted fs-6">/ {{ 0 }} Guards</span></div>
                        <small class="text-muted">Clients & Operations</small>
                    </div>
                </div>
                </div>
        </div>

        <div class="row mb-4">

            <div class="col-12">
                <div class="row">
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-dark shadow-sm">
                            <div class="card-body text-center p-8">
                                <h6 class="mb-2 text-white fw-bold">ACCRA</h6>
                                <div class="mb-2">
                                    <span class="badge bg-white text-dark">{{ $clients->where('field_id', '1')->count() }}</span>
                                    <span class="badge bg-white text-dark">{{ 0 }}</span>
                                </div>
                                <small class="text-white text-muted d-block">CLIENTS - GUARDS</small>
                            </div> 
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-dark shadow-sm ">
                            <div class="card-body text-center p-8">
                                <h6 class="mb-2 text-white fw-bold">BOTWE</h6>
                                <div class="mb-2">
                                    <span class="badge bg-white text-dark">{{ $clients->where('field_id', '2')->count() }}</span>
                                    <span class="badge bg-white text-dark">{{ 0 }}</span>
                                </div>
                                <small class="text-white text-muted d-block">CLIENTS - GUARDS</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-dark shadow-sm ">
                            <div class="card-body text-center p-8">
                                <h6 class="mb-2 text-white fw-bold">TEMA</h6>
                                <div class="mb-2">
                                    <span class="badge bg-white text-dark">{{ $clients->where('field_id', '3')->count() }}</span>
                                    <span class="badge bg-white text-dark">{{ 0 }}</span>
                                </div>
                                <small class="text-white text-muted d-block">CLIENTS - GUARDS</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-dark shadow-sm ">
                            <div class="card-body text-center p-8">
                                <h6 class="mb-2 text-white fw-bold">SHAIHILLS</h6>
                                <div class="mb-2">
                                    <span class="badge bg-white text-dark">{{ $clients->where('field_id', '7')->count() }}</span>
                                    <span class="badge bg-white text-dark">{{ 0 }}</span>
                                </div>
                                <small class="text-white text-muted d-block">CLIENTS - GUARDS </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-dark shadow-sm ">
                            <div class="card-body text-center p-8">
                                <h6 class="mb-2 text-white fw-bold">TAKORADI</h6>
                                <div class="mb-2">
                                    <span class="badge bg-white text-dark">{{ $clients->where('field_id', '4')->count() }}</span>
                                    <span class="badge bg-white text-dark">{{ 0 }}</span>
                                </div>
                                <small class="text-white text-muted d-block">CLIENTS - GUARDS</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-dark shadow-sm ">
                            <div class="card-body text-center p-8">
                                <h6 class="mb-2 text-white fw-bold">KOFORIDUA</h6>
                                <div class="mb-2">
                                    <span class="badge bg-white text-dark">{{ $clients->where('field_id', '5')->count() }}</span>
                                    <span class="badge bg-white text-dark">{{ 0 }}</span>
                                </div>
                                <small class="text-white text-muted d-block">CLIENTS - GUARDS</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-dark shadow-sm ">
                            <div class="card-body text-center p-8">
                                <h6 class="mb-2 text-white fw-bold">KUMASI</h6>
                                <div class="mb-2">
                                    <span class="badge bg-white text-dark">{{ $clients->where('field_id', '6')->count() }}</span>
                                    <span class="badge bg-white text-dark">{{ 0 }}</span>
                                </div>
                                <small class="text-white text-muted d-block">CLIENTS - GUARDS</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>

        @else

        <div class="row mb-4">
                <div class="col-12">
                     <div class="card border-left-dark bg-warning text-white h-100 py-2">
                    <div class="card-body">
                        <div class="text-uppercase mb-1"><small class="fw-bold"> {{strtoupper(Auth::user()->field?->name)}} PENDING CLIENTS / GUARDS</small></div>
                        <div class="h3 text-white mb-0 fw-bold">{{  $clients->count() }} <span class="text-muted fs-6">/ {{ 0 }} Guards</span></div>
                        <small class="text-muted">Clients & Operations</small>
                    </div>
                </div>
                </div>
                <!-- <div class="col-lg-6 col-md-4 col-sm-6 mb-3">
                        <div class="card bg-dark shadow-sm">
                            <div class="card-body text-center p-8">
                                <h6 class="mb-2 text-white fw-bold"> {{strtoupper(Auth::user()->field?->name)}} </h6>
                                <div class="mb-2">
                                    <span class="badge bg-white text-dark">{{ $clients->where('field_id', '1')->count() }}</span>
                                    <span class="badge bg-white text-dark">{{ 0 }}</span>
                                </div>
                                <small class="text-white text-muted d-block">CLIENTS - GUARDS</small>
                            </div> 
                        </div>
                </div> -->
        </div>

        @endif

        <div class="card-header ml-2 d-none d-lg-block">
            @include('flash-messages')
        </div>



        <!-- Tab Content -->
            <!-- Standard Clients Tab -->
            <form action="/clientAproval" method="POST">
                @csrf  
                <input type="hidden" name="action_type" id="client_action_type" value="" />
                <div class="card">


                    <div class="card-header pb-4">
                    <input class="form-check-input form-check-inline" type="checkbox" value="" id="options" />
                  
                    @if(Auth::user()->hasRole(['Manager']) && Auth::user()->hasPermission('Administration'))
                    <div class="form-check form-check-inline">                            
                        <button class="btn btn-dark" type="submit" onclick="document.getElementById('client_action_type').value='branch'; return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('Approve') }}</button>
                    </div>    

                    @elseif(Auth::user()->hasRole(['Invoice']))
                    <div class="form-check form-check-inline">                            
                        <button class="btn btn-success" type="submit" onclick="document.getElementById('client_action_type').value='headOffice'; return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('Approve') }}</button>
                    </div>
                    <div class="form-check form-check-inline">                            
                        <button class="btn btn-danger" type="submit" onclick="document.getElementById('client_action_type').value='decline'; return confirm('Kindly Confirm?')"> <i class="icon-base bx bx-arrow-from-left"> </i> {{ __('Decline') }}</button>
                    </div>
                    @endif

                    </div>

                    <div class="table-responsive ">
                        <table id="standardClientsTable" class="display">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th style="width: 50px;"> # </th>
                                    <th style="width: 60px;"> ID </th>
                                    <th>Status </th>

                                    <th>Admin Assit.</th>
                                    <th>Branch</th>
                                    <th>H/O</th>

                                    <th> Re-Instate Date </th>
                                    <th> Contract Date </th>
                                    <th> Type  </th>
                                    <th> Business Name </th>
                                    <th> Contact </th>
                                    <th> Field Office </th>
                                    <th> Address </th>
                                    <th> Branch </th>
                                    <th style="width: 80px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clients as $key => $client)
                                <tr class="table-light">
                                    <td> <input class="checkBoxes form-check-input" type="checkbox" name="clients[{{$client->id}}]" value="{{ $client->id }}" /></td>

                                    <td>  {{ $key + 1 }} </td>

                                    <td><strong>{{ $client->id }}</strong></td>
                                    <td>
                                        <span class="badge bg-label-warning">{{ $client->status }}</span>
                                    </td>
                                    <td> 
                                        @if($client->coll_status == 'approved')
                                        {{$client->user?->name}}
                                        {{ $client->coll_date?->format('l, F j, Y') }}
                                        <span class="badge bg-label-success">{{$client->coll_status}}</span>
                                        @else
                                        {{$client->user?->name}}
                                        {{ $client->coll_date?->format('l, F j, Y') }}
                                        <span class="badge bg-label-danger">{{$client->coll_status}}</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($client->bran_status == 'approved')
                                        {{$client->user1?->name}}
                                        {{ $client->bran_date?->format('l, F j, Y') }}
                                        <span class="badge bg-label-success">{{$client->bran_status}}</span>
                                        @else
                                        {{$client->user1?->name}}
                                        {{ $client->bran_date?->format('l, F j, Y') }}
                                        <span class="badge bg-label-danger">{{$client->bran_status}}</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{$client->user2?->name}}
                                        <span class="badge bg-label-danger">{{$client->ho_status}}</span>
                                    </td>
                                    <td>  {{ $client->status_date?->format('F l d, Y') }} </td>
                                    <td>  {{ $client->start_date?->format('F l d, Y') }} </td>
                                    <td>
                                        @if($client->state_institution == '1' )
                                            <i class="bx bx-building text-danger me-2"></i>State
                                        @else
                                            <i class="bx bx-bxs-group text-danger me-2"></i> Standard
                                        @endif
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $client->business_name }}</strong>
                                            <br><small class="text-muted">{{ $client->name }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong> <small>{{ $client->phone_number }}</small> </strong> 
                                            @if($client->phone_number1)
                                            <br><small class="text-muted">{{ $client->phone_number1 }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $fieldColors = [
                                                'Accra' => 'danger',
                                                'Botwe' => 'secondary',
                                                'Tema' => 'info',
                                                'ShaiHills' => 'info',
                                                'Takoradi' => 'warning',
                                                'Koforidua' => 'success',
                                                'Kumasi' => 'primary'
                                            ];
                                            $color = $fieldColors[$client->field->name] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-label-{{ $color }}">{{ $client->field->name }}</span>
                                    </td>
                                    <td><small>{{ $client->address }}</small></td>
                                    <td><small>{{ $client->branch }}</small></td>
                                    <td>
                                                <a class="btn btn-warning btn-sm" href="client/{{ $client->id }}/edit"><i class="bx bx-edit-alt me-1"></i> Edit</a>

                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($clients->isEmpty())
                        <div class="text-center py-5">
                            <p class="text-muted">No standard clients found</p>
                        </div>
                        @endif
                    </div>
                </div>
            </form>

    </div>
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
        // Initialize Standard Clients Table
        new DataTable('#standardClientsTable', {
            responsive: true,
            columnControl: [['search']],
            layout: {
                topStart: {
                    buttons: [
                        {
                            extend: 'pageLength',
                            text: 'Show',
                            className: 'btn btn-secondary btn-sm',
                            Options: [10, 25, 50, 100, 250, 500, 1000],
                        },
                        {
                            extend: 'excelHtml5',
                            title: 'Standard Clients',
                            className: 'btn btn-secondary btn-sm',
                            exportOptions: {
                                columns: ':visible'
                            }
                        }
                    ]
                }
            },
        });

        // Initialize State Institution Clients Table
        new DataTable('#stateClientsTable', {
            responsive: true,
            columnControl: [['search']],
            layout: {
                topStart: {
                    buttons: [
                        {
                            extend: 'pageLength',
                            text: 'Show',
                            className: 'btn btn-secondary btn-sm',
                            Options: [10, 25, 50, 100, 250, 500, 1000],
                        },
                        {
                            extend: 'excelHtml5',
                            title: 'State Institution Clients',
                            className: 'btn btn-secondary btn-sm',
                            exportOptions: {
                                columns: ':visible'
                            }
                        }
                    ]
                }
            },
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
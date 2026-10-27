<x-sales-dashboard>
    @section('side_nav')
        @include('partials.receipt_side_nav')
    @endsection

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">

    <link href="https://cdn.datatables.net/columncontrol/1.1.1/css/columnControl.dataTables.min.css" rel="stylesheet">

    <style>
        .invoice-list-card {
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 1rem;
            box-shadow: 0 0.35rem 1rem rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }

        .invoice-list-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .invoice-list-header {
            margin: 0;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .invoice-list-button {
            border-radius: 0.75rem;
            padding: 0.7rem 1.1rem;
            font-weight: 600;
        }

        .invoice-table thead th {
            background: rgba(220, 38, 38, 0.04);
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #374151;
        }

        .invoice-table tbody tr {
            transition: background-color 0.2s ease;
        }

        .invoice-table tbody tr:hover {
            background: rgba(220, 38, 38, 0.02);
        }

        .invoice-action-toggle {
            border: 1px solid rgba(220, 38, 38, 0.12);
            border-radius: 0.6rem;
            padding: 0.35rem 0.55rem;
            background: #fff;
        }

        .invoice-action-toggle:hover {
            background: rgba(220, 38, 38, 0.04);
        }
    </style>
    @endsection

    @section('content')
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row">
                <div class="col-6">
                    <h3 class="card-header text-primary"> <i class="icon-base bx bx-receipt text-primary me-2"></i> Invoice <i class="icon-base bx bx-right-arrow-alt mx-1 text-muted"></i> List </h3>
                </div>
                <div style="padding-left: 350px;" class="col-6">
                    <a class="btn btn-danger" href="{{url('invoice/create')}}"> <i class="icon-base bx bx-user-plus me-2 text-primary"></i> Create </a>
                </div>
            </div>
            <br>

            <div class="card-header  ml-2  d-none d-lg-block">
                @include('flash-messages')
            </div>

            <div class="row ">
                <div class="col-12">
                    <div class="invoice-list-card card">
                        <div class="card-header border-0 pb-1">
                            <h4 class="mb-0 text-dark fw-bold">INVOICES</h4>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive text-normal-dark">
                                <!-- <div class="card-body demo-vertical-spacing demo-only-element"> Clients </div> -->
                                <table id="myTable" class="invoice-table display" data-source="{{ route('invoice.data') }}">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Invoice No.</th>
                                            <th>Invoice Month</th>
                                            <th>Client Name</th>
                                            <th>Phone No.</th>
                                            <th>Field Office</th>
                                            <th>Staff</th>
                                            <th>Date Created</th>
                                            <th>Due Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection


  {{-- =============== resources/views/sales/invoice_list.blade.php =============== --}}
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
 
@include('partials.dt_range')
    @include('partials.dt_compact')
 
<script>
    $(function () {
        // invoice_month is always the 1st of a month, so MONTH pickers are the honest control:
        // a mid-month day picker could never match anything.
        const range = DtRange.mount('#myTable', {
            label: 'Invoice month',
            type: 'month',
            presets: ['month', 'lastmonth', 'year'],
            exportUrl: '{{ route('invoice.export') }}',
        });
 
        const table = $('#myTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: 25,
            ordering: true,
            searching: true,
            searchDelay: 500,
            // DtCompact.ajax: sends only searched columns, after ColumnControl adds them (partials/dt_compact).
            ajax: DtCompact.ajax({
                url: $('#myTable').data('source'),
                data: range.append,
            }),
            order: [[7, 'desc']],
            columnControl: [{
                target: 1,
                content: ['search']
            }],
            columns: [
                { data: 'row_number', orderable: false, searchable: false, render: function (data, type, row, meta) {
                    return meta.row + 1 + meta.settings._iDisplayStart;
                } },
                { data: 'invoice_id', render: function (data) { return '#FWSSi' + data; }, orderable: true, searchable: true },
                { data: 'invoice_month', orderable: true, searchable: true },
                { data: 'client_name', orderable: true, searchable: true },
                { data: 'phone_number', orderable: true, searchable: true },
                { data: 'field_name', orderable: true, searchable: true },
                { data: 'staff_name', orderable: true, searchable: true },
                { data: 'created_at', orderable: true, searchable: true },
                { data: 'due_date', orderable: true, searchable: true },
                { data: 'amount', orderable: true, searchable: true },
                { data: 'status', orderable: true, searchable: true },
                { data: 'action', orderable: false, searchable: false }
            ],
            layout: {
                topStart: {
                    buttons: [
                        {
                            extend: 'pageLength',
                            text: 'Show',
                            className: 'btn btn-secondary',
                            Options: [10, 25, 50, 100, 250, 500, 1000, 2000],
                        },
                    ]
                }
            }
        });
 
        range.bind(table);
 
        table.on('draw.dt', function () {
            $('select[name="myTable_length"]').addClass('form-select form-select-sm');
        });
    });
</script>
 
@endsection

</x-sales-dashboard>

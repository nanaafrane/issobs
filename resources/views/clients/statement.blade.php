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
    @php
        $money = fn ($v) => \App\Services\Receipts\ClientStatement::money((float) $v);
        $st = $statement;
        $kindBadge = ['invoice' => 'bg-label-danger', 'receipt' => 'bg-label-success', 'wht' => 'bg-label-info', 'vat' => 'bg-label-info', 'deduction' => 'bg-label-warning', 'credit_applied' => 'bg-label-secondary', 'reversal' => 'bg-label-dark'];
        $kindLabel = ['invoice' => 'Invoice', 'receipt' => 'Payment', 'wht' => 'WHT', 'vat' => 'VAT 7%', 'deduction' => 'Deduction', 'credit_applied' => 'Credit applied', 'reversal' => 'Correction'];
    @endphp
    <style>
        .st-num { font-variant-numeric: tabular-nums; white-space: nowrap; text-align: right; }
        .st-note td { color: var(--bs-secondary-color, #8592a3); font-style: italic; }
        .st-summary dl { display: grid; grid-template-columns: 1fr auto; gap: .35rem 1rem; margin: 0; }
        .st-summary dd { margin: 0; text-align: right; font-variant-numeric: tabular-nums; }
        @media print {
            .layout-menu, .layout-navbar, .content-footer, .st-noprint, .dt-layout-row:not(.dt-layout-table) { display: none !important; }
            .layout-page { padding: 0 !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd; }
        }
    </style>
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h3 class="text-info mb-1"><i class="icon-base bx bx-bxs-user-detail"></i> Statement of Account</h3>
                <div><strong>{{ $client->business_name ?: $client->name }}</strong>@if($client->business_name && $client->name !== $client->business_name) — {{ $client->name }}@endif</div>
                <div class="text-muted small">{{ $client->phone_number }} · {{ $client->address }} · {{ $client->field?->name }}</div>
                <div class="text-muted small">
                    Period: {{ $st['from'] ? $st['from']->format('d M Y') : 'start' }} – {{ $st['to'] ? $st['to']->format('d M Y') : now()->format('d M Y') }}
                </div>
            </div>
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-end st-noprint">
                <div><label class="form-label small mb-0" for="from">From</label><input type="date" id="from" name="from" class="form-control form-control-sm" value="{{ request('from') }}"></div>
                <div><label class="form-label small mb-0" for="to">To</label><input type="date" id="to" name="to" class="form-control form-control-sm" value="{{ request('to') }}"></div>
                <button class="btn btn-sm btn-primary" type="submit">Apply</button>
                <a class="btn btn-sm btn-outline-secondary" href="{{ url('clientStatement', $client->id) }}">All</a>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('receivables.aged') }}">Aged receivables</a>
                <button class="btn btn-sm btn-outline-secondary" type="button" onclick="window.print()"><i class="bx bx-printer"></i> Print</button>
                @if($pdfAvailable)
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('client.statement.pdf', ['client' => $client->id, 'from' => request('from'), 'to' => request('to')]) }}"><i class="bx bxs-file-pdf"></i> PDF</a>
                @endif
                <button class="btn btn-sm btn-info" type="button" data-bs-toggle="collapse" data-bs-target="#emailBox"><i class="bx bx-envelope"></i> Email</button>
            </form>
        </div>

        @include('flash-messages')

        <div class="collapse st-noprint mb-4 {{ $errors->has('to') ? 'show' : '' }}" id="emailBox">
            <div class="card border border-info"><div class="card-body">
                <form method="POST" action="{{ route('client.statement.email', $client->id) }}" class="row g-3">
                    @csrf
                    <input type="hidden" name="from" value="{{ request('from') }}">
                    <input type="hidden" name="period_to" value="{{ request('to') }}">
                    <div class="col-md-6">
                        <label class="form-label" for="st_to">To</label>
                        <input type="text" class="form-control" id="st_to" name="to" value="{{ old('to', $client->email) }}" placeholder="accounts@client.com" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="st_cc">Cc <small class="text-muted">(optional, comma separated)</small></label>
                        <input type="text" class="form-control" id="st_cc" name="cc" value="{{ old('cc') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="st_note">Message <small class="text-muted">(optional — a standard greeting is used if empty)</small></label>
                        <textarea class="form-control" id="st_note" name="note" rows="3">{{ old('note') }}</textarea>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-3 align-items-center">
                        <button class="btn btn-info" type="submit"><i class="bx bx-send"></i> Send statement for this period</button>
                        <small class="text-muted">{{ $pdfAvailable ? 'A PDF copy is attached.' : 'The full statement is in the email body (install dompdf to also attach a PDF).' }}</small>
                    </div>
                </form>
                @if($sends->isNotEmpty())
                <hr>
                <div class="small text-muted mb-1">Recently sent</div>
                @foreach($sends as $send)
                    <div class="small">{{ $send->created_at->format('d M Y H:i') }} — {{ $send->sent_to }} — balance {{ \App\Services\Receipts\ClientStatement::money($send->closing_balance) }} — by {{ $send->user?->name }}</div>
                @endforeach
                @endif
            </div></div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger st-noprint"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        @unless($st['check']['ok'])
        <div class="alert alert-warning st-noprint">
            This statement (GH&#8373;{{ $money($st['check']['statement']) }}) and the invoice-by-invoice balance
            (GH&#8373;{{ $money($st['check']['per_invoice']) }}) differ by GH&#8373;{{ number_format($st['check']['difference'], 2) }}.
            This comes from receipts recorded by the old system. Ask an administrator to run
            <code>php artisan receipts:recompute --client={{ $client->id }}</code>.
        </div>
        @endunless

        <div class="row g-4 mb-4">
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 st-summary"><div class="card-body">
                    <dl>
                        <dt>Opening balance</dt><dd>{{ $money($st['opening']) }}</dd>
                        <dt>Invoiced</dt><dd>{{ number_format($st['invoiced'], 2) }}</dd>
                        <dt>Money received</dt><dd>({{ number_format($st['received'], 2) }})</dd>
                        <dt>WHT / VAT / deductions</dt><dd>({{ number_format($st['withheld'], 2) }})</dd>
                        @if(abs($st['corrections']) > 0.004)
                        <dt>Corrections / voids</dt><dd>{{ $st['corrections'] < 0 ? '(' . number_format(-$st['corrections'], 2) . ')' : number_format($st['corrections'], 2) }}</dd>
                        @endif
                        <dt class="fw-bold">Closing balance</dt><dd class="fw-bold fs-5">GH&#8373; {{ $money($st['closing']) }}</dd>
                    </dl>
                    <small class="text-muted">"CR" means the client has paid ahead and is in credit.</small>
                </div></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100"><div class="card-body">
                    <h6 class="mb-2">Open invoices (today)</h6>
                    @forelse($st['open_invoices'] as $inv)
                        <div class="d-flex justify-content-between small">
                            <a href="{{ url('invoice/' . $inv->id) }}">FWSSi{{ $inv->id }} · {{ $inv->invoice_month?->format('M Y') }}</a>
                            <span class="st-num">{{ number_format($inv->outstanding, 2) }}@if($inv->days_overdue) <span class="text-danger">· {{ $inv->days_overdue }}d overdue</span>@endif</span>
                        </div>
                    @empty
                        <p class="small text-muted mb-0">Nothing owed.</p>
                    @endforelse
                    @if($st['open_invoices']->isNotEmpty())
                        <hr class="my-2"><div class="d-flex justify-content-between small fw-bold"><span>Total owed</span><span class="st-num">{{ number_format($st['open_invoices']->sum('outstanding'), 2) }}</span></div>
                    @endif
                </div></div>
            </div>
            <div class="col-md-12 col-xl-4">
                <div class="card h-100"><div class="card-body">
                    <h6 class="mb-2">Unused credit</h6>
                    @forelse($st['credit_receipts'] as $cr)
                        <div class="d-flex justify-content-between small">
                            <a href="{{ url('receipt/' . $cr->id) }}">FWSSR{{ $cr->id }} · {{ $cr->receipt_month?->format('d M Y') }}</a>
                            <span class="st-num">{{ number_format($cr->unapplied_amount, 2) }}</span>
                        </div>
                    @empty
                        <p class="small text-muted mb-0">None.</p>
                    @endforelse
                    @if($st['credit_receipts']->isNotEmpty())
                        <hr class="my-2"><div class="d-flex justify-content-between small fw-bold"><span>Total credit</span><span class="st-num">{{ number_format($st['credit_receipts']->sum('unapplied_amount'), 2) }}</span></div>
                        <small class="text-muted st-noprint">Apply it from the receipt page.</small>
                    @endif
                </div></div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table id="statementTable" class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Details</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="st-note">
                            <td>{{ $st['from'] ? $st['from']->format('d M Y') : '' }}</td><td></td>
                            <td>Opening balance</td><td></td><td></td><td></td>
                            <td class="st-num">{{ $money($st['opening']) }}</td>
                        </tr>
                        @foreach($st['lines'] as $line)
                            <tr class="{{ $line['kind'] === 'credit_applied' ? 'st-note' : '' }}">
                                <td data-order="{{ $line['date']->format('Y-m-d') }}">{{ $line['date']->format('d M Y') }}</td>
                                <td><span class="badge {{ $kindBadge[$line['kind']] }}">{{ $kindLabel[$line['kind']] }}</span></td>
                                <td>@if($line['link'])<a href="{{ $line['link'] }}">{{ $line['description'] }}</a>@else{{ $line['description'] }}@endif</td>
                                <td class="small">{{ $line['details'] }}</td>
                                <td class="st-num">{{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '' }}</td>
                                <td class="st-num">{{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '' }}</td>
                                <td class="st-num">{{ $line['kind'] === 'credit_applied' ? '' : $money($line['balance']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="4">Closing balance</td>
                            <td class="st-num">{{ number_format(collect($st['lines'])->sum('debit'), 2) }}</td>
                            <td class="st-num">{{ number_format(collect($st['lines'])->sum('credit'), 2) }}</td>
                            <td class="st-num">{{ $money($st['closing']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>

    @endsection



    @section('scripts')

    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.dataTables.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.4/js/buttons.html5.min.js"></script>

    <script>
        // A running balance only makes sense in date order, so sorting is switched off.
        new DataTable('#statementTable', {
            ordering: false,
            paging: false,
            layout: {
                topStart: {
                    buttons: [{
                        extend: 'excelHtml5',
                        text: '<i class="bx bxs-file-export"></i> Export to Excel',
                        className: 'btn btn-secondary btn-sm',
                        title: @json(($client->business_name ?: $client->name) . ' — Statement of Account ' . ($statement['from'] ? $statement['from']->format('d M Y') : '') . ' to ' . ($statement['to'] ? $statement['to']->format('d M Y') : now()->format('d M Y'))),
                        footer: true,
                    }]
                }
            }
        });
    </script>

    @endsection



</x-hr-dashboard>

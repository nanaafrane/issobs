<x-sales-dashboard>

    @section('side_nav')
        @include('partials.receipt_side_nav')
    @endsection

    @section('css')
    <style>
        .os-card { display: block; border-radius: 10px; padding: .9rem 1.1rem; background: #fff; border: 1px solid #e5e7eb; color: inherit; height: 100%; }
        a.os-card:hover { border-color: #696cff; }
        .os-card.active { border-color: #696cff; box-shadow: 0 0 0 2px rgba(105,108,255,.18); }
        .os-card .value { font-size: 1.35rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        .os-card .sub { font-size: .82rem; color: #6b7280; }
        .os-num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .os-table thead th { position: sticky; top: 0; background: #f9fafb; z-index: 1; }
        .os-table tfoot td { font-weight: 700; background: #f9fafb; }
    </style>
    @endsection

    @section('content')
    @php
        $ghs = fn ($v) => 'GH₵ ' . number_format((float) $v, 2);
        $base = $ctx->query();
        $link = fn (array $over = []) => route('invoices.outstanding', array_filter(array_merge($base, ['status' => $status, 'client' => $clientId], $over), fn ($v) => $v !== null && $v !== ''));
        $s = $summary;
        $offices = $ctx->officeOptions();
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
            <div>
                <h3 class="mb-0"><i class="bx bx-error-circle"></i> Outstanding invoices</h3>
                <div class="text-muted small">
                    Invoices for <strong>{{ $book->monthsLabel() }}</strong> not yet fully paid · {{ $ctx->scopeLabel() }} · balances as at today ({{ now()->format('j M Y') }})
                </div>
            </div>
            <a href="{{ $link(['export' => 'xlsx']) }}" class="btn btn-success btn-sm" @if($rows->isEmpty()) aria-disabled="true" @endif>
                <i class="bx bx-spreadsheet me-1"></i> Download Excel
            </a>
        </div>

        <form method="GET" action="{{ route('invoices.outstanding') }}" class="row g-2 align-items-end mb-3" id="os-window">
            @include('partials.period_picker', ['id' => 'os', 'period' => $ctx->period, 'anchor' => $ctx->anchor, 'from' => $ctx->from, 'to' => $ctx->to])
            @if($offices->count() > 1)
            <div class="col-auto">
                <label class="form-label small mb-0" for="os-field">Office</label>
                <select name="field" id="os-field" class="form-select form-select-sm" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()">
                    <option value="">{{ $ctx->allowedIds === null ? 'All offices' : 'All my offices' }}</option>
                    @foreach($offices as $o)
                        <option value="{{ $o->id }}" @selected($ctx->fieldId === (int) $o->id)>{{ $o->parent_field_id ? '— ' : '' }}{{ $o->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            @if($clientId)<input type="hidden" name="client" value="{{ $clientId }}">@endif
            <div class="col-auto"><button class="btn btn-sm btn-dark">Show</button></div>
            <div class="col-auto small text-muted pb-1">{{ $ctx->label() }}@if($ctx->period !== 'monthly' || ! $ctx->isWholeMonths()) → invoice months {{ $book->monthsLabel() }}@endif</div>
        </form>

        <div class="row g-3 mb-3">
            <div class="col-lg-3 col-6">
                <a href="{{ $link(['status' => null]) }}" class="os-card {{ $status ? '' : 'active' }}">
                    <small class="text-muted">OUTSTANDING</small>
                    <div class="value text-danger">{{ $ghs($s['owed']) }}</div>
                    <div class="sub">{{ number_format($s['count']) }} {{ \Illuminate\Support\Str::plural('invoice', $s['count']) }} · {{ number_format($s['clients']) }} {{ \Illuminate\Support\Str::plural('client', $s['clients']) }}</div>
                </a>
            </div>
            <div class="col-lg-3 col-6">
                <a href="{{ $link(['status' => 'unpaid']) }}" class="os-card {{ $status === 'unpaid' ? 'active' : '' }}">
                    <small class="text-muted">UNPAID</small>
                    <div class="value">{{ $ghs($s['unpaid']['owed']) }}</div>
                    <div class="sub">{{ number_format($s['unpaid']['count']) }} with nothing paid yet</div>
                </a>
            </div>
            <div class="col-lg-3 col-6">
                <a href="{{ $link(['status' => 'part']) }}" class="os-card {{ $status === 'part' ? 'active' : '' }}">
                    <small class="text-muted">PART PAID (UNCOMPLETED)</small>
                    <div class="value">{{ $ghs($s['part']['owed']) }}</div>
                    <div class="sub">{{ number_format($s['part']['count']) }} with a balance left</div>
                </a>
            </div>
            <div class="col-lg-3 col-6">
                <div class="os-card">
                    <small class="text-muted">BILLED ON THESE INVOICES</small>
                    <div class="value">{{ $ghs($s['billed']) }}</div>
                    <div class="sub">{{ $s['billed'] > 0 ? number_format(($s['billed'] - $s['owed']) / $s['billed'] * 100, 1) . '% of it paid' : '—' }}</div>
                </div>
            </div>
        </div>

        @if($offices->count() > 1 && $book->byOffice()->count() > 1)
        <div class="d-flex flex-wrap gap-1 mb-3 small align-items-center">
            <span class="text-muted me-1">By office:</span>
            @foreach($book->byOffice() as $o)
                @if($o['id'])
                <a href="{{ route('invoices.outstanding', array_filter(array_merge($base, ['field' => $o['id'], 'status' => $status]))) }}" class="btn btn-sm py-0 btn-outline-secondary">
                    {{ $o['name'] }} <span class="opacity-75">· {{ $o['count'] }} · {{ $ghs($o['owed']) }}</span>
                </a>
                @endif
            @endforeach
        </div>
        @endif

        @if($clientId)
            <div class="alert alert-secondary py-2 small d-flex justify-content-between align-items-center">
                <span>Showing one client: <strong>{{ $rows->first()?->client ?? 'nothing outstanding' }}</strong></span>
                <a href="{{ $link(['client' => null]) }}">Show all clients</a>
            </div>
        @endif

        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
                <h6 class="mb-0">{{ $status ? \App\Support\OutstandingInvoices::STATUSES[$status] : 'Unpaid and part paid' }} <span class="text-muted fw-normal">· {{ number_format($rows->count()) }}</span></h6>
                <input type="search" class="form-control form-control-sm" style="max-width: 16rem" placeholder="Filter client, office, invoice…" id="os-filter" aria-label="Filter the list">
            </div>
            <div class="table-responsive" style="max-height: 70vh">
                <table class="table table-sm table-hover mb-0 os-table">
                    <thead><tr>
                        <th>Invoice</th><th>Month</th><th>Client</th><th>Office</th><th>Due</th><th class="os-num">Overdue</th><th>Status</th>
                        <th class="os-num">Total</th><th class="os-num">Paid</th><th class="os-num">Outstanding</th><th></th>
                    </tr></thead>
                    <tbody>
                    @forelse($rows as $r)
                        <tr data-text="{{ strtolower('FWSSI' . $r->id . ' ' . $r->client . ' ' . $r->office) }}" data-owed="{{ $r->owed }}">
                            <td><a href="{{ url('invoice/' . $r->id) }}">FWSSI{{ $r->id }}</a></td>
                            <td class="text-nowrap">{{ $r->invoice_month?->format('M Y') }}</td>
                            <td><a href="{{ $link(['client' => $r->client_id]) }}" class="text-body">{{ $r->client }}</a></td>
                            <td>{{ $r->office }}</td>
                            <td class="text-nowrap">{{ $r->due?->format('d M Y') }}</td>
                            <td class="os-num {{ $r->days_overdue > 90 ? 'text-danger fw-semibold' : ($r->days_overdue > 0 ? 'text-warning' : 'text-muted') }}">{{ $r->days_overdue ? $r->days_overdue . ' d' : 'not due' }}</td>
                            <td><span class="badge {{ $r->state === 'part' ? 'bg-label-warning' : 'bg-label-danger' }}">{{ \App\Support\OutstandingInvoices::STATUSES[$r->state] }}</span></td>
                            <td class="os-num">{{ number_format($r->total, 2) }}</td>
                            <td class="os-num">{{ $r->paid > 0 ? number_format($r->paid, 2) : '' }}</td>
                            <td class="os-num fw-semibold">{{ number_format($r->owed, 2) }}</td>
                            <td class="text-end text-nowrap"><a href="{{ route('receipt.multi.create', ['client_id' => $r->client_id, 'invoice' => $r->id]) }}" class="btn btn-sm btn-outline-primary py-0">Receive</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-4">Nothing outstanding for {{ $book->monthsLabel() }}.</td></tr>
                    @endforelse
                    </tbody>
                    @if($rows->isNotEmpty())
                    <tfoot><tr>
                        <td colspan="7">Total <span class="fw-normal text-muted" id="os-shown"></span></td>
                        <td class="os-num">{{ number_format($rows->sum('total'), 2) }}</td>
                        <td class="os-num">{{ number_format($rows->sum('paid'), 2) }}</td>
                        <td class="os-num" id="os-total">{{ number_format($rows->sum('owed'), 2) }}</td>
                        <td></td>
                    </tr></tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
    @endsection

    @section('scripts')
    <script>
        (function () {
            var box = document.getElementById('os-filter');
            if (!box) return;
            var rows = document.querySelectorAll('.os-table tbody tr[data-text]');
            var total = document.getElementById('os-total'), shown = document.getElementById('os-shown');
            box.addEventListener('input', function () {
                var q = box.value.trim().toLowerCase(), sum = 0, n = 0;
                rows.forEach(function (tr) {
                    var on = !q || tr.dataset.text.indexOf(q) >= 0;
                    tr.hidden = !on;
                    if (on) { sum += parseFloat(tr.dataset.owed) || 0; n++; }
                });
                if (total) total.textContent = sum.toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                if (shown) shown.textContent = q ? '(' + n + ' shown)' : '';
            });
        })();
    </script>
    @endsection
</x-sales-dashboard>

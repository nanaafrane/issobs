<x-sales-dashboard>

    @section('side_nav')
        @include('partials.receipt_side_nav')
    @endsection

    @section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h3 class="text-primary mb-1"><i class="icon-base bx bx-block me-2"></i> Voided receipts</h3>
        <p class="text-muted">Receipts are voided, not deleted: each one is kept here with the reason. Its payments were reversed on the day shown.</p>

        @include('flash-messages')

        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Receipt</th>
                            <th>Client</th>
                            <th>Receipt date</th>
                            <th class="text-end">Money</th>
                            <th>Paid invoices</th>
                            <th>Reason</th>
                            <th>Voided by</th>
                            <th>Voided on</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($voids as $v)
                        @php $r = $v->snapshot['receipt'] ?? []; @endphp
                        <tr>
                            <td><strong>FWSSR{{ $v->receipt_id }}</strong></td>
                            <td>
                                @if($v->client)
                                    <a href="{{ url('clientStatement', $v->client_id) }}">{{ $v->client->business_name ?: $v->client->name }}</a>
                                @endif
                            </td>
                            <td>{{ !empty($r['receipt_month']) ? \Carbon\Carbon::parse($r['receipt_month'])->format('d M Y') : '' }}</td>
                            <td class="text-end">{{ number_format($v->amount, 2) }}</td>
                            <td class="small">{{ collect($v->snapshot['allocations'] ?? [])->map(fn ($a) => 'FWSSi' . $a['invoice_id'] . ' ' . number_format((float) $a['settled'], 2))->implode(', ') ?: '—' }}</td>
                            <td class="small">{{ $v->reason }}</td>
                            <td>{{ $v->voidedBy?->name }}</td>
                            <td>{{ $v->created_at->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-muted">No receipts have been voided.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endsection
</x-sales-dashboard>

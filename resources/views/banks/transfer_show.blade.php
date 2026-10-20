<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <style>.mv-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }</style>
    @endsection

    @section('content')
    @php $first = $batch->first(); $reversed = $batch->whereNull('reversed_at')->isEmpty(); @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <a href="{{ route('money.transfers', ['month' => $first->transfer_date->format('Y-m')]) }}" class="small"><i class="bx bx-arrow-back"></i> Transfers</a>
        <h3 class="text-primary mt-1 mb-1">GH₵ {{ number_format($batch->sum('amount'), 2) }} into {{ $first->to?->name }}
            @if($reversed)<span class="badge bg-label-secondary align-middle fs-6">reversed</span>@endif</h3>
        <div class="text-muted small mb-3">{{ $first->transfer_date->format('l d M Y') }} · recorded by {{ $first->user?->name }} {{ $first->created_at?->format('d M Y H:i') }}@if($first->notes) · {{ $first->notes }}@endif</div>

        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        @if($reversed)<div class="alert alert-secondary">Reversed {{ $first->reversed_at?->format('d M Y H:i') }} by {{ $first->reversedBy?->name }}: {{ $first->reversal_reason }}</div>@endif

        <div class="card mb-3">
            <div class="card-body pb-0"><h6>Cheques / transfers</h6></div>
            <div class="table-responsive"><table class="table table-sm mb-0">
                <thead><tr><th>From</th><th>How</th><th>Ref</th><th class="mv-num">Amount</th></tr></thead>
                <tbody>
                @foreach($batch as $t)
                    <tr><td>{{ $t->from?->name }}</td><td>{{ \App\Models\AccountTransfer::METHODS[$t->method] ?? $t->method }}</td><td>{{ $t->reference }}</td><td class="mv-num">{{ number_format($t->amount, 2) }}</td></tr>
                @endforeach
                </tbody>
            </table></div>
        </div>

        <div class="card mb-3">
            <div class="card-body pb-0"><h6>On the accounts</h6></div>
            <div class="table-responsive"><table class="table table-sm mb-0">
                <thead><tr><th>Account</th><th>Details</th><th class="mv-num">Money in</th><th class="mv-num">Money out</th><th class="mv-num">Balance after</th></tr></thead>
                <tbody>
                @foreach($lines as $l)
                    <tr><td><a href="{{ route('banks.show', $l->bank_id) }}">{{ $l->bank?->name }}</a></td><td>@include('banks.partials.channel_badge', ['t' => $l]) {{ $l->narration }}</td>
                        <td class="mv-num text-success">{{ $l->credit > 0 ? number_format($l->credit, 2) : '' }}</td><td class="mv-num text-danger">{{ $l->debit > 0 ? number_format($l->debit, 2) : '' }}</td><td class="mv-num">{{ number_format($l->balance, 2) }}</td></tr>
                @endforeach
                </tbody>
            </table></div>
        </div>

        @if($canCorrect && ! $reversed)
        <div class="card border-danger"><div class="card-body">
            <h6 class="text-danger mb-1">Reverse</h6>
            <p class="small text-muted mb-2">Entered in error? Reversing moves the money back on every line above (nothing is deleted). A cash account must still hold the money.</p>
            <form method="POST" action="{{ route('money.transfers.reverse', $first) }}" class="d-flex flex-wrap gap-2" onsubmit="return confirm('Reverse these transfers?');">
                @csrf
                {!! \App\Support\SubmissionGuard::field() !!}
                <input name="reversal_reason" class="form-control form-control-sm" style="max-width: 28rem" maxlength="300" placeholder="Reason" required>
                <button class="btn btn-sm btn-danger" data-loading>Reverse</button>
            </form>
        </div></div>
        @endif
    </div>
    @endsection
</x-sales-dashboard>

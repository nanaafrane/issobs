<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <style>.mv-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }</style>
    @endsection

    @section('content')
    @php $first = $batch->first(); $reversed = $move['reversed']; $m = fn ($v) => number_format((float) $v, 2); @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <a href="{{ route('money.transfers', ['month' => $first->transfer_date->format('Y-m')]) }}" class="small"><i class="bx bx-arrow-back"></i> Transfers</a>
        <h3 class="text-primary mt-1 mb-1">GH₵ {{ $m($move['total']) }} moved
            @if($reversed)<span class="badge bg-label-secondary align-middle fs-6">reversed</span>@endif</h3>
        <div class="text-muted small mb-3">{{ $move['from'] }} → {{ $move['to'] }} · {{ $first->transfer_date->format('l d M Y') }} · recorded by {{ $first->user?->name }} {{ $first->created_at?->format('d M Y H:i') }}@if($first->notes) · {{ $first->notes }}@endif</div>

        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        @if($reversed)<div class="alert alert-secondary">Reversed {{ $first->reversed_at?->format('d M Y H:i') }} by {{ $first->reversedBy?->name }}: {{ $first->reversal_reason }}</div>@endif

        <div class="row g-3 mb-3">
            <div class="col-lg-6"><div class="card h-100" style="border-top: 3px solid #ff3e1d">
                <div class="card-header py-2"><h6 class="mb-0">Out of</h6></div>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead><tr><th>Account</th><th>How</th><th>Date</th><th class="mv-num">Amount</th></tr></thead>
                    <tbody>
                    @foreach($move['outs'] as $o)
                        <tr><td><a href="{{ route('banks.show', $o['bank']) }}">{{ $o['bank']?->name }}</a></td>
                            <td>{{ \App\Models\AccountTransfer::METHODS[$o['method']] ?? $o['method'] }} {{ $o['reference'] }}</td>
                            <td class="text-nowrap">{{ $o['date']?->format('d M Y') }}</td><td class="mv-num">{{ $m($o['amount']) }}</td></tr>
                    @endforeach
                    </tbody>
                </table></div>
            </div></div>
            <div class="col-lg-6"><div class="card h-100" style="border-top: 3px solid #71dd37">
                <div class="card-header py-2"><h6 class="mb-0">Into</h6></div>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead><tr><th>Account</th><th>How it arrived</th><th>Date</th><th class="mv-num">Amount</th></tr></thead>
                    <tbody>
                    @foreach($move['ins'] as $i)
                        <tr><td><a href="{{ route('banks.show', $i['bank']) }}">{{ $i['bank']?->name }}</a></td>
                            <td>@if($i['method']){{ $i['method'] === 'cash' ? ($i['bank']?->isCash() ? 'Cash received' : 'Cash deposited') : (\App\Models\AccountTransfer::IN_METHODS[$i['method']] ?? $i['method']) }}@else{{ $i['from'] }}@endif
                                {{ $i['reference'] }}<div class="small text-muted">{{ $i['method'] ? $i['from'] : '' }}</div></td>
                            <td class="text-nowrap">{{ $i['date']?->format('d M Y') }}</td><td class="mv-num">{{ $m($i['amount']) }}</td></tr>
                    @endforeach
                    </tbody>
                </table></div>
            </div></div>
        </div>

        @if($batch->count() > 1)
        <div class="card mb-3">
            <div class="card-header py-2"><h6 class="mb-0">Who funded what</h6></div>
            <div class="table-responsive"><table class="table table-sm mb-0">
                <thead><tr><th>From</th><th>Route</th><th>To</th><th class="mv-num">Amount</th></tr></thead>
                <tbody>
                @foreach($batch as $t)
                    <tr><td>{{ $t->from?->name }}</td><td>{{ $t->routeLabel() }}</td><td>{{ $t->to?->name }}</td><td class="mv-num">{{ $m($t->amount) }}</td></tr>
                @endforeach
                </tbody>
            </table></div>
        </div>
        @endif

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
            <p class="small text-muted mb-2">Entered in error? Reversing posts the opposite of every line above (nothing is deleted). A cash account must still hold the money.</p>
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

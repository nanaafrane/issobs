<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <style>.fl-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }</style>
    @endsection

    @section('content')
    @php $m = fn ($v) => number_format((float) $v, 2); @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <a href="{{ route('fleet.fuel.payouts') }}" class="small"><i class="bx bx-arrow-back"></i> Fuel pay-outs</a>
        <h3 class="mt-1 mb-1">{{ $payout->label() }} <span class="badge {{ $payout->status === 'cancelled' ? 'bg-label-secondary' : 'bg-label-success' }} align-middle fs-6">{{ $payout->status }}</span></h3>
        <div class="text-muted small mb-3">GH₵ {{ $m($payout->total) }} · {{ $m($payout->litres) }} L · paid to {{ $payout->payee }} from {{ $payout->bank?->name }}
            @if($payout->payment) · <a href="{{ route('money.payments.show', $payout->payment) }}">Payment #{{ $payout->payment_id }}</a>@endif
            · raised by {{ $payout->user?->name }} {{ $payout->created_at?->format('d M Y H:i') }}</div>
        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        @if($payout->status === 'cancelled')<div class="alert alert-secondary">Cancelled {{ $payout->cancelled_at?->format('d M Y H:i') }}: {{ $payout->cancel_reason }}</div>@endif

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card"><div class="table-responsive"><table class="table table-sm mb-0">
                    <thead><tr><th>Date</th><th>Vehicle</th><th>Driver</th><th>Trip</th><th>Station</th><th class="fl-num">km</th><th class="fl-num">Litres</th><th class="fl-num">Amount</th></tr></thead>
                    <tbody>
                    @forelse($entries as $f)
                        <tr>
                            <td class="text-nowrap">{{ $f->entry_date->format('d M Y') }}</td>
                            <td><a href="{{ route('fleet.vehicles.show', $f->vehicle_id) }}">{{ $f->vehicle?->reg_no }}</a></td>
                            <td class="small">{{ $f->driverLabel() }}</td>
                            <td class="small">{{ $f->purpose }}</td>
                            <td class="small">{{ $f->station }}</td>
                            <td class="fl-num small">{{ $f->odometer ? number_format($f->odometer) : '' }}</td>
                            <td class="fl-num">{{ $m($f->litres) }}</td>
                            <td class="fl-num">{{ $m($f->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-3">{{ $payout->status === 'cancelled' ? 'The entries are owed again.' : 'No entries.' }}</td></tr>
                    @endforelse
                    </tbody>
                    @if($entries->isNotEmpty())<tfoot><tr class="fw-bold"><td colspan="6">Total</td><td class="fl-num">{{ $m($entries->sum('litres')) }}</td><td class="fl-num">{{ $m($entries->sum('amount')) }}</td></tr></tfoot>@endif
                </table></div></div>
            </div>
            @if($canCorrect && $payout->status !== 'cancelled')
            <div class="col-xl-4">
                <div class="card"><div class="card-body">
                    <h6>Cancel this pay-out</h6>
                    <p class="small text-muted">Entered in error? The payment is reversed (the money goes back on {{ $payout->bank?->name }}), the expense is removed and the fuel is owed again.</p>
                    <form method="POST" action="{{ route('fleet.fuel.payouts.cancel', $payout) }}" onsubmit="return confirm('Cancel this pay-out?');">
                        @csrf
                        {!! \App\Support\SubmissionGuard::field() !!}
                        <input name="cancel_reason" class="form-control form-control-sm mb-2" placeholder="Reason" required minlength="5" maxlength="500">
                        <button class="btn btn-sm btn-outline-danger">Cancel pay-out</button>
                    </form>
                </div></div>
            </div>
            @endif
        </div>
    </div>
    @endsection
</x-sales-dashboard>

<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('css')
    <style>
        .cr-num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        .cr-tile { border-left: 3px solid var(--bs-dark, #233446); }
    </style>
    @endsection

    @section('content')
    @php $owed = $creditors->sum('balance'); @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div>
                <h3 class="text-primary mb-1"><i class="bx bx-building-house me-1"></i> Creditors (loans to the company)</h3>
                <div class="text-muted small">People or institutions the company has borrowed from — e.g. a director's loan — and what it still owes them. Borrowed money is not income, and paying it back is not an expense.</div>
            </div>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCreditor"><i class="bx bx-plus"></i> Add creditor</button>
        </div>

        @include('flash-messages')
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

        <div class="row g-3 mb-3">
            <div class="col-md-4"><div class="card cr-tile"><div class="card-body py-3">
                <div class="small text-muted">The company owes creditors</div>
                <div class="fs-4 fw-bold">GH₵ {{ number_format($owed, 2) }}</div>
                <div class="small text-muted">{{ $creditors->where('balance', '>', 0)->count() }} of {{ $creditors->count() }} with a balance</div>
            </div></div></div>
            <div class="col-md-4"><div class="card cr-tile"><div class="card-body py-3">
                <div class="small text-muted">Borrowed this month</div>
                <div class="fs-5 fw-bold">GH₵ {{ number_format($thisMonth->sum('added'), 2) }}</div>
                <div class="small text-muted">received + paid on our behalf</div>
            </div></div></div>
            <div class="col-md-4"><div class="card cr-tile"><div class="card-body py-3">
                <div class="small text-muted">Repaid this month</div>
                <div class="fs-5 fw-bold">GH₵ {{ number_format($thisMonth->sum('reduced'), 2) }}</div>
                <div class="small text-muted">&nbsp;</div>
            </div></div></div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Creditor</th><th>Type</th><th class="cr-num">Owed now</th><th class="cr-num">This month +</th><th class="cr-num">This month −</th><th>Last movement</th><th></th></tr></thead>
                    <tbody>
                    @forelse($creditors as $c)
                        <tr>
                            <td><a href="{{ route('creditors.show', $c) }}" class="fw-semibold">{{ $c->name }}</a>@if($c->phone)<div class="small text-muted">{{ $c->phone }}</div>@endif</td>
                            <td>{{ $c->kind_label }}</td>
                            <td class="cr-num fw-bold">{{ number_format($c->balance, 2) }}</td>
                            <td class="cr-num">{{ ($thisMonth[$c->id]->added ?? 0) > 0 ? number_format($thisMonth[$c->id]->added, 2) : '' }}</td>
                            <td class="cr-num">{{ ($thisMonth[$c->id]->reduced ?? 0) > 0 ? number_format($thisMonth[$c->id]->reduced, 2) : '' }}</td>
                            <td>{{ isset($last[$c->id]) ? \Carbon\Carbon::parse($last[$c->id])->format('d M Y') : '—' }}</td>
                            <td class="text-end"><a href="{{ route('creditors.show', $c) }}" class="btn btn-sm btn-outline-primary py-0">Statement</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No creditors yet. Add the director (or any lender) to record money lent to the company.</td></tr>
                    @endforelse
                    </tbody>
                    @if($creditors->isNotEmpty())
                    <tfoot><tr class="fw-bold"><td colspan="2">Total owed</td><td class="cr-num">{{ number_format($owed, 2) }}</td><td colspan="4"></td></tr></tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addCreditor" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('creditors.store') }}" class="modal-content">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Add creditor</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body row g-2">
                    <div class="col-8"><label class="form-label small mb-0">Name</label><input name="name" class="form-control" value="{{ old('name') }}" required placeholder="e.g. Nana Afrane (Director)"></div>
                    <div class="col-4"><label class="form-label small mb-0">Type</label>
                        <select name="kind" class="form-select">@foreach(\App\Models\Creditor::KINDS as $k => $v)<option value="{{ $k }}" @selected(old('kind') === $k)>{{ $v }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label small mb-0">Phone</label><input name="phone" class="form-control" value="{{ old('phone') }}"></div>
                    <div class="col-6"><label class="form-label small mb-0">Email</label><input type="email" name="email" class="form-control" value="{{ old('email') }}"></div>
                    <div class="col-6"><label class="form-label small mb-0">Already owed (GH₵)</label><input type="number" step="0.01" min="0" name="opening_balance" class="form-control" value="{{ old('opening_balance', '0.00') }}"></div>
                    <div class="col-6"><label class="form-label small mb-0">As at</label><input type="date" name="opening_date" class="form-control" value="{{ old('opening_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}"></div>
                    <div class="col-12 small text-muted">"Already owed" is what the company owes them today from before this system. New money lent is recorded on their page.</div>
                    <div class="col-12"><label class="form-label small mb-0">Notes</label><input name="notes" class="form-control" maxlength="500" value="{{ old('notes') }}" placeholder="terms, e.g. interest-free, repay when cash allows"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Add creditor</button></div>
            </form>
        </div>
    </div>
    @endsection
</x-sales-dashboard>

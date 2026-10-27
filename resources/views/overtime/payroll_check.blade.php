<x-sales-dashboard>

    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection

    @section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <a href="{{ route('overtime.payouts') }}" class="small"><i class="bx bx-arrow-back"></i> Overtime pay-outs</a>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mt-1 mb-3">
            <div>
                <h3 class="text-primary mb-1">Overtime payroll check · {{ $month->format('F Y') }}</h3>
                <div class="text-muted small">For each guard: approved overtime sent to this month's payroll (payroll pay-outs) against the "overtime" on their salary.</div>
            </div>
            <form method="GET"><label class="form-label small mb-0" for="month">Month</label>
                <input type="month" id="month" name="month" class="form-control form-control-sm" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()"></form>
        </div>
        <div class="card">
            @if($rows->isEmpty())
                <div class="card-body"><i class="bx bx-check-circle text-success"></i> No differences for {{ $month->format('F Y') }}.</div>
            @else
            <div class="table-responsive"><table class="table table-sm mb-0">
                <thead><tr><th>Guard</th><th class="text-end">Sent to payroll</th><th class="text-end">On the salary</th><th class="text-end">Difference</th></tr></thead>
                <tbody>
                @foreach($rows as $r)
                    <tr><td>{{ $r['name'] }}</td><td class="text-end">{{ number_format($r['daily'], 2) }}</td><td class="text-end">{{ number_format($r['salary'], 2) }}</td>
                        <td class="text-end {{ $r['difference'] > 0 ? 'text-danger' : 'text-warning' }}">{{ ($r['difference'] > 0 ? '+' : '') . number_format($r['difference'], 2) }}</td></tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="card-body small text-muted">+ the salary pays more overtime than was approved and sent to payroll (possible double pay, or overtime paid in cash/MoMo too). − approved overtime is missing from the salary.</div>
            @endif
        </div>
    </div>
    @endsection
</x-sales-dashboard>

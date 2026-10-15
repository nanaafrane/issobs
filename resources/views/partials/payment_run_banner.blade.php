{{-- Which payment an approval on this page goes into. Needs $month (any date in the payroll month). --}}
@if(Auth::user()?->hasRole(['Finance Manager']) && isset($month))
    @php
        $prMonth = \App\Support\PaymentRuns::month($month);
        $prOpen = \App\Support\PaymentRuns::openRun($prMonth);
        $prNext = ((int) \Illuminate\Support\Facades\DB::table('payment_runs')->where('salary_month', $prMonth->toDateString())->max('number')) + 1;
    @endphp
    <div class="alert {{ $prOpen ? 'alert-success' : 'alert-secondary' }} py-2 small mb-2 payment-run-banner" role="status">
        <i class="bx bx-list-check"></i>
        @if($prOpen)
            Approving into <strong>{{ $prMonth->format('F') }} · Payment {{ $prOpen->number }}</strong> ({{ \Carbon\Carbon::parse($prOpen->run_date)->format('d M Y') }}).
        @else
            No {{ $prMonth->format('F') }} payment is open: approving starts <strong>Payment {{ $prNext }}</strong> today.
        @endif
        <a href="{{ route('salary-payments.index', ['month' => $prMonth->format('Y-m')]) }}" class="alert-link ms-1">Manage payments</a>
    </div>
@endif

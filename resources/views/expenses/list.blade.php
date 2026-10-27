<x-sales-dashboard>

    @section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.dataTables.css">

    <link href="https://cdn.datatables.net/columncontrol/1.1.1/css/columnControl.dataTables.min.css" rel="stylesheet">
    @endsection


    @section('side_nav')
        @include('partials.bank_side_nav')
    @endsection




    @section('content')
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">

<div class="container py-4">
 
    <style>
        .expense-row { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: .9rem 1.1rem; margin-bottom: .6rem; }
        .expense-row .desc { font-weight: 500; color: #111827; }
        .expense-row .meta { font-size: .8125rem; color: #6b7280; }
        .pipeline { display: flex; align-items: center; gap: .35rem; }
        .pipeline .dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }
        .pipeline .dot.approved { background: #16a34a; }
        .pipeline .dot.rejected { background: #dc2626; }
        .pipeline .dot.pending  { background: #f59e0b; }
        .pipeline .dot.waiting  { background: #d1d5db; }
        .pipeline .stage-label { font-size: .7rem; color: #9ca3af; }
        .amount-figure { font-size: 1.05rem; font-weight: 700; color: #111827; }
    </style>
 
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Expense Entries</h4>
        <a href="{{ route('expense.create') }}" class="btn btn-success btn-sm">+ New Expense</a>
    </div>
 
    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
 
    @forelse($expenses as $expense)
        <div class="expense-row d-flex justify-content-between align-items-center flex-wrap gap-2">
 
            <div style="min-width: 220px;">
                <div class="desc">{{ Str::limit($expense->description, 50) }}</div>
                <div class="meta">
                    {{ $expense->field?->name }} &middot; {{ $expense->type?->name }} &middot;
                    {{ optional($expense->expense_date)->format('d M Y') }}
                </div>
            </div>
 
            <div class="amount-figure">{{ number_format($expense->amount, 2) }}</div>
 
            {{-- approval pipeline: 3 stages, colored by status --}}
            <div class="pipeline" title="Approval progress">
                @foreach($expense->approvalTimeline() as $stage)
                    <span class="dot {{ $stage['status'] }}"></span>
                    <span class="stage-label">{{ $stage['label'] }}</span>
                    @if(!$loop->last)<span class="text-muted">→</span>@endif
                @endforeach
                @if($expense->payment_id)
                    <span class="text-muted">→</span>
                    <span class="dot approved"></span>
                    <span class="stage-label">
                        @if(Auth::user()?->hasRole(\App\Http\Controllers\BankController::VIEW_ROLES))
                            <a href="{{ route('money.payments.show', $expense->payment_id) }}">Paid</a>
                        @else Paid @endif
                    </span>
                @elseif($expense->creditor_entry_id)
                    <span class="text-muted">→</span>
                    <span class="dot approved"></span>
                    <span class="stage-label">
                        @if(Auth::user()?->hasRole(\App\Http\Controllers\BankController::VIEW_ROLES))
                            <a href="{{ route('creditors.entry', $expense->creditor_entry_id) }}">Paid by a creditor</a>
                        @else Paid by a creditor @endif
                    </span>
                @elseif($expense->isFullyApproved())
                    <span class="text-muted">→</span>
                    <span class="dot pending"></span>
                    <span class="stage-label">Awaiting payment</span>
                @endif
            </div>
 
            <div class="d-flex gap-1">
                @if(! $expense->isPaid() && $expense->isFullyApproved() && Auth::user()?->hasRole(\App\Http\Controllers\BankController::VIEW_ROLES))
                    <a href="{{ route('money.payments.create', ['expenses' => [$expense->id]]) }}" class="btn btn-sm btn-outline-primary">Pay</a>
                @endif
                @if($expense->currentStage() && $expense->canActOnStage(Auth::user(), $expense->currentStage()))
                    <form action="{{ route('expense.approve', $expense) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-success">Approve</button>
                    </form>
                    <form action="{{ route('expense.reject', $expense) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Reject this expense?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                    </form>
                @endif
 
                @if($expense->isEditableBy(Auth::user()))
                    <a href="{{ route('expense.edit', $expense) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                    <form action="{{ route('expense.destroy', $expense) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Delete this expense?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Delete</button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <div class="text-center text-muted py-5">No expenses found for this filter.</div>
    @endforelse
 
    <div class="mt-3">{{ $expenses->links() }}</div>
</div>


        </div>
    </div>
    @endsection


</x-sales-dashboard>
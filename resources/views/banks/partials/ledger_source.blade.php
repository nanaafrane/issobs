{{-- Link to the document that posted this ledger line. @include('banks.partials.ledger_source', ['t' => $line]) --}}
@if($t->receipt_id)<a href="{{ url('receipt', $t->receipt_id) }}">Receipt FWSSR{{ $t->receipt_id }}</a>
@elseif($t->deposit_id)<a href="{{ route('deposit.show', $t->deposit_id) }}">Deposit #{{ $t->deposit_id }}</a>
@elseif($t->payment_id)<a href="{{ route('money.payments.show', $t->payment_id) }}">Payment #{{ $t->payment_id }}</a>
@elseif($t->creditor_entry_id)<a href="{{ route('creditors.entry', $t->creditor_entry_id) }}">Creditor entry #{{ $t->creditor_entry_id }}</a>
@elseif($t->transfer_id)<a href="{{ route('money.transfers.show', $t->transfer_id) }}">Transfer #{{ $t->transfer_id }}</a>
@elseif($t->expense_id)Expense #{{ $t->expense_id }}
@else <span class="text-muted">{{ $t->channel === 'adjustment' ? 'Adjustment' : 'Manual' }}</span>@endif

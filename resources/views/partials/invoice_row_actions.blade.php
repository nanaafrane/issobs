<div class="dropdown">
    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
        <i class="icon-base bx bx-dots-vertical-rounded text-primary"></i>
    </button>
    <div class="dropdown-menu">
        <a class="dropdown-item" href="{{ url('invoice', $invoice->id) }}"><i class="icon-base bx bx-bullseye text-primary me-2"></i> view</a>
        <a class="dropdown-item" href="{{ route('invoice.duplicate', ['invoice' => $invoice->id]) }}"><i class="icon-base bx bx-copy me-2 text-primary"></i> Duplicate</a>

        @if(($invoice->status ?? '') !== 'completed' && ($invoice->status ?? '') !== 'uncompleted')
            <a class="dropdown-item" href="{{ url('invoice/' . $invoice->id . '/edit') }}"><i class="icon-base bx bx-edit-alt me-2 text-primary"></i> Edit</a>
            <form action="{{ url('invoice/' . $invoice->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <button class="dropdown-item" type="submit"><i class="icon-base bx bx-trash me-2 text-danger"></i>Delete</button>
            </form>
        @endif
    </div>
</div>

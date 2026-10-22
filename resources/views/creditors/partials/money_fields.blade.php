{{-- Account / how / amount / date / ref / note fields for money received from or repaid to a creditor. --}}
<div class="col-12 row g-2 m-0 p-0" data-money-form>
    <div class="col-md-4">
        <label class="form-label small mb-0">{{ $label }} account <span class="text-danger">*</span></label>
        <select name="bank_id" class="form-select" required>
            <option value="">Choose…</option>
            @foreach($accounts as $a)
                <option value="{{ $a->id }}" data-cash="{{ $a->isCash() ? 1 : 0 }}" @selected((string) old('bank_id') === (string) $a->id && old('_tab') === ($prefix === 'rc' ? 'receive' : 'repay'))>{{ $a->label }} · {{ number_format($a->total, 2) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-0">How</label>
        <select name="method" class="form-select">
            <option value="transfer">Bank transfer</option>
            <option value="cheque">Cheque</option>
            <option value="momo">MoMo</option>
        </select>
    </div>
    <div class="col-md-2"><label class="form-label small mb-0">Amount (GH₵) <span class="text-danger">*</span></label>
        <input type="number" step="0.01" min="0.01" @isset($max) max="{{ $max }}" @endisset name="amount" class="form-control text-end" required></div>
    <div class="col-md-2"><label class="form-label small mb-0">Date</label><input type="date" name="entry_date" class="form-control" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required></div>
    <div class="col-md-2"><label class="form-label small mb-0">Ref / cheque no.</label><input name="reference" class="form-control" maxlength="60"></div>
    <div class="col-md-12"><input name="description" class="form-control form-control-sm" maxlength="500" placeholder="note, e.g. to pay October salaries shortfall"></div>
</div>

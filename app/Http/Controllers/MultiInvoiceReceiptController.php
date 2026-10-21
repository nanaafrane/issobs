<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMultiInvoiceReceiptRequest;
use App\Models\Bank;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\User;
use App\Models\Wht;
use App\Services\Receipts\ReceiptRecorder;
use App\Services\Receipts\CreditApplier;
use App\Support\SubmissionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * One receipt, many invoices.
 *
 * The cashier picks a client, ticks the invoices the payment covers, enters
 * the money received once (cash / momo / cheque / transfer / other), and the
 * amount is spread over the ticked invoices oldest first (or as typed).
 * Anything left over stays on the receipt as client credit and can be
 * applied to later invoices from the receipt page.
 */
class MultiInvoiceReceiptController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create(Request $request)
    {
        $clients = $this->scopeToUserFields(Client::query())
            ->whereHas('invoices', fn ($q) => $q->whereIn('status', ['unpaid', 'uncompleted']))
            ->with('field')
            ->orderBy('business_name')
            ->get();

        $client = null;
        $invoices = collect();
        $creditReceipts = collect();

        // Opened from a particular invoice ("Create receipt" on the invoice list): that
        // invoice is ticked and its client chosen. ?invoice= can be one id or several.
        $preselected = array_values(array_filter(array_map('intval', (array) $request->input('invoice', []))));
        $clientId = $request->integer('client_id') ?: ($preselected ? (int) Invoice::whereKey($preselected[0])->value('client_id') : 0);

        if ($clientId) {
            $client = $this->scopeToUserFields(Client::query())->with('field')->findOrFail($clientId);
            $invoices = $this->openInvoices($client->id)->get();
            $creditReceipts = Receipt::where('client_id', $client->id)
                ->where('unapplied_amount', '>', 0)
                ->orderBy('id')
                ->get();
        }

        $mode = DB::table('receipt_mode')->get();
        $banks = Bank::banksOnly()->operating()->orderBy('name')->get();
        $wht_rate = new Wht();
        $assign_staff = $this->assignableStaff();

        return view('sales.receipt_multi_create', compact(
            'clients', 'client', 'invoices', 'creditReceipts', 'mode', 'banks', 'wht_rate', 'assign_staff', 'preselected'
        ));
    }

    /**
     * Edit a receipt on the same screen as a new one: the client is fixed, the invoices
     * it pays are ticked with their amounts, its payment details are filled in. Saving
     * goes to ReceiptController@update (ReceiptRecorder::revise).
     */
    public function edit(Receipt $receipt, ReceiptRecorder $recorder)
    {
        $client = $this->scopeToUserFields(Client::query())->with('field')->findOrFail($receipt->client_id);

        if (\App\Services\Banking\DepositBook::anyPartDeposited(\App\Models\Collection::where('receipt_id', $receipt->id)->first())) {
            return redirect()->route('receipt.show', ['receipt' => $receipt->id])
                ->with('error', 'Money on this receipt has already been deposited, so it cannot be edited. Reverse the deposit first (Bank Deposit › the deposit › Reverse).');
        }

        // What this receipt itself paid on each invoice (its credit applied elsewhere stays as it is).
        $own = $receipt->allocations()->where(fn ($q) => $q->where('source', '!=', 'credit')->orWhereNull('source'))->get()->keyBy('invoice_id');
        $creditLines = $receipt->allocations()->where('source', 'credit')->get();
        $creditUsed = $recorder->creditUsed($receipt);

        $prefill = $own->map(fn ($a) => [
            'applied' => (float) $a->amount_applied,
            'wht' => (float) $a->wht_amount,
            'vat7' => (float) $a->vat7_amount,
            'deduction' => (float) $a->deduction_amount,
        ])->all();
        // A receipt from before allocations existed: its one invoice with the receipt's own figures.
        if (! $prefill && $receipt->invoice_id && $creditLines->isEmpty()) {
            $prefill[$receipt->invoice_id] = [
                'applied' => null,
                'wht' => (float) $receipt->wht_amount,
                'vat7' => (float) $receipt->vat7_value,
                'deduction' => (float) $receipt->dAmount,
            ];
        }

        // Open invoices plus the ones this receipt pays; amounts as if this receipt were not there.
        $notMine = fn ($q) => $q->where(fn ($w) => $w->where('receipt_id', '!=', $receipt->id)->orWhere('source', 'credit'));
        $invoices = Invoice::where('client_id', $client->id)
            ->where(fn ($q) => $q->whereIn('status', ['unpaid', 'uncompleted'])->orWhereIn('id', array_keys($prefill) ?: [0]))
            ->withSum(['allocations as settled_sum' => $notMine], 'settled')
            ->withSum(['allocations as wht_taken' => $notMine], 'wht_amount')
            ->withSum(['allocations as vat_taken' => $notMine], 'vat7_amount')
            ->orderBy('invoice_month')->orderBy('id')
            ->get()
            ->filter(fn ($inv) => isset($prefill[$inv->id]) || round((float) $inv->total - (float) $inv->settled_sum, 2) > 0.004)
            ->values();

        $creditReceipts = Receipt::where('client_id', $client->id)->where('unapplied_amount', '>', 0)
            ->where('id', '!=', $receipt->id)->orderBy('id')->get();
        $clients = collect([$client]);
        $mode = DB::table('receipt_mode')->get();
        $banks = Bank::banksOnly()->operating()->orderBy('name')
            ->orWhereIn('id', array_filter([$receipt->cheque_to_bank_id, $receipt->transfer_to_bank_id]))->get();
        $wht_rate = new Wht();
        $assign_staff = collect();     // the approval routing is not changed by an edit
        $preselected = array_keys($prefill);
        $editing = $receipt;

        return view('sales.receipt_multi_create', compact(
            'clients', 'client', 'invoices', 'creditReceipts', 'mode', 'banks', 'wht_rate', 'assign_staff', 'preselected',
            'editing', 'prefill', 'creditLines', 'creditUsed'
        ));
    }

    public function store(StoreMultiInvoiceReceiptRequest $request, ReceiptRecorder $recorder, SubmissionGuard $guard)
    {
        $client = $this->scopeToUserFields(Client::query())->findOrFail($request->integer('client_id'));

        // Saved once per form: a re-sent form (dropped connection, double click) shows the first receipt.
        return $guard->once($request, 'receipt', function () use ($request, $recorder, $client) {
        $receipt = $recorder->create($client, ReceiptRecorder::multiInvoiceInput($request), $request->file('image'));

        $count = $receipt->allocations()->count();
        $message = 'Receipt FWSSR' . $receipt->id . ' created for ' . $count . ' invoice' . ($count === 1 ? '' : 's') . '.';
        if ($receipt->hasUnappliedCredit()) {
            $message .= ' GH₵' . number_format($receipt->unapplied_amount, 2) . ' kept as client credit.';
        }
        if ($recorder->notes) {
            $message .= ' ' . implode(' ', $recorder->notes);
        }

        return redirect()->route('receipt.show', ['receipt' => $receipt->id])->with($recorder->notes ? 'warning' : 'primary', $message);
        });
    }

    /**
     * Apply a receipt's unapplied credit (advance / overpayment) to the
     * client's open invoices. No new money moves, so collections and bank
     * balances are untouched.
     */
    public function applyCredit(Request $request, Receipt $receipt, CreditApplier $credits, SubmissionGuard $guard)
    {
        $request->validate([
            'invoices' => ['required', 'array'],
            'invoices.*.selected' => ['nullable', 'in:1'],
            'invoices.*.applied' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Only from a client this user may work with.
        $this->scopeToUserFields(Client::query())->findOrFail($receipt->client_id);

        $invoices = collect((array) $request->input('invoices', []))
            ->filter(fn ($row) => ($row['selected'] ?? null) === '1')
            ->map(fn ($row) => $row['applied'] ?? null)
            ->all();

        return $guard->once($request, 'apply_credit', function () use ($credits, $receipt, $invoices) {
            $credits->apply($receipt, $invoices);

            return redirect()->route('receipt.show', ['receipt' => $receipt->id])
                ->with('primary', 'Credit applied. Remaining credit on this receipt: GH₵' . number_format($receipt->fresh()->unapplied_amount, 2));
        });
    }

    /** Invoices still owing money, oldest first, with what has already been settled. */
    private function openInvoices(int $clientId)
    {
        return Invoice::where('client_id', $clientId)
            ->whereIn('status', ['unpaid', 'uncompleted'])
            ->withSum('allocations as settled_sum', 'settled')
            ->withSum('allocations as wht_taken', 'wht_amount')     // WHT already withheld on earlier receipts
            ->withSum('allocations as vat_taken', 'vat7_amount')    // 7% VAT already withheld
            ->orderBy('invoice_month')
            ->orderBy('id');
    }

    /** Same office rules as the receipt list: head office sees all, Tema also sees Shai Hills. */
    private function scopeToUserFields($query)
    {
        $user = Auth::user();
        if ($user?->hasRole(['Finance Manager', 'Invoice'])) {
            return $query;
        }

        $fieldIds = $user?->field_id == 3 ? [3, 7] : array_filter([$user?->field_id]);

        return $fieldIds ? $query->whereIn('field_id', $fieldIds) : $query->whereRaw('1 = 0');
    }

    /** Same "assign to" list as the single-invoice receipt form. */
    private function assignableStaff()
    {
        $user = Auth::user();
        $staff = User::all();

        if ($user?->role?->name == 'Admin Assistant') {
            return $staff->where('department_id', '7')->where('role_id', '3')
                ->filter(fn ($u) => $u->field_id == $user->field_id);
        }
        if ($user?->role?->name == 'Manager') {
            return $staff->where('department_id', '1')->where('role_id', '2');
        }

        return collect();
    }
}

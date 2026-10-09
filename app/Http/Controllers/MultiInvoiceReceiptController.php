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

        if ($request->filled('client_id')) {
            $client = $this->scopeToUserFields(Client::query())->with('field')->findOrFail($request->integer('client_id'));
            $invoices = $this->openInvoices($client->id)->get();
            $creditReceipts = Receipt::where('client_id', $client->id)
                ->where('unapplied_amount', '>', 0)
                ->orderBy('id')
                ->get();
        }

        $mode = DB::table('receipt_mode')->get();
        $banks = Bank::orderBy('name')->get();
        $wht_rate = new Wht();
        $assign_staff = $this->assignableStaff();

        return view('sales.receipt_multi_create', compact(
            'clients', 'client', 'invoices', 'creditReceipts', 'mode', 'banks', 'wht_rate', 'assign_staff'
        ));
    }

    public function store(StoreMultiInvoiceReceiptRequest $request, ReceiptRecorder $recorder)
    {
        $client = $this->scopeToUserFields(Client::query())->findOrFail($request->integer('client_id'));

        $receipt = $recorder->create($client, ReceiptRecorder::multiInvoiceInput($request), $request->file('image'));

        $count = $receipt->allocations()->count();
        $message = 'Receipt FWSSR' . $receipt->id . ' created for ' . $count . ' invoice' . ($count === 1 ? '' : 's') . '.';
        if ($receipt->hasUnappliedCredit()) {
            $message .= ' GH₵' . number_format($receipt->unapplied_amount, 2) . ' kept as client credit.';
        }

        return redirect()->route('receipt.show', ['receipt' => $receipt->id])->with('primary', $message);
    }

    /**
     * Apply a receipt's unapplied credit (advance / overpayment) to the
     * client's open invoices. No new money moves, so collections and bank
     * balances are untouched.
     */
    public function applyCredit(Request $request, Receipt $receipt, CreditApplier $credits)
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

        $credits->apply($receipt, $invoices);

        return redirect()->route('receipt.show', ['receipt' => $receipt->id])
            ->with('primary', 'Credit applied. Remaining credit on this receipt: GH₵' . number_format($receipt->fresh()->unapplied_amount, 2));
    }

    /** Invoices still owing money, oldest first, with what has already been settled. */
    private function openInvoices(int $clientId)
    {
        return Invoice::where('client_id', $clientId)
            ->whereIn('status', ['unpaid', 'uncompleted'])
            ->withSum('allocations as settled_sum', 'settled')
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

<?php

namespace App\Services\Receipts;

use App\Models\category;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Approval routing and client payment-category rules, in one place so the
 * single-invoice and multi-invoice receipt screens behave the same.
 */
class ReceiptWorkflow
{
    /**
     * Same routing as ReceiptController::createReceipt():
     *  - Finance Manager (role 2): approved at head office straight away.
     *  - Branch Manager (dept 7, role 3): branch approved, head office pending with $staff.
     *  - Branch staff (dept 7, role 27): branch + collector pending, assigned to $staff.
     */
    public function applyApproval(Receipt $receipt, $staff = null): void
    {
        $user = Auth::user();
        $userId = $user?->id;

        if ($user?->role?->id == '2') {
            $receipt->ho_status = 'approved';
            $receipt->user_id2 = $userId;
        }
        if ($user?->department?->id == '7' && $user?->role?->id == '3') {
            $receipt->ho_status = 'pending';
            $receipt->user_id2 = $staff;
            $receipt->bran_status = 'approved';
            $receipt->user_id1 = $userId;
        }
        if ($user?->department?->id == '7' && $user?->role?->id == '27') {
            $receipt->bran_status = 'pending';
            $receipt->user_id1 = $staff;
            $receipt->coll_status = 'pending';
        }
    }

    /**
     * Payment-promptness category for the client for the invoice's month
     * (A: paid in the invoice month or earlier; B: 1st-9th of next month;
     * C: 10th-15th; D: 16th-25th). When one already exists for that month it
     * is left alone on a new receipt, and re-graded when a receipt is edited
     * (its date may have changed) — same as the old create/edit screens.
     *
     * @return bool true if a category was assigned or re-graded
     */
    public function assignCategory(Receipt $receipt, Invoice $invoice, bool $updateExisting = false): bool
    {
        if (! $invoice->invoice_month || ! $receipt->receipt_month) {
            return false;
        }

        $inv = Carbon::parse($invoice->invoice_month);
        $rec = Carbon::parse($receipt->receipt_month);

        $existing = category::where('client_id', $receipt->client_id)
            ->whereYear('category_month', $inv->year)
            ->whereMonth('category_month', $inv->month)
            ->first();
        if ($existing && ! $updateExisting) {
            return false;
        }

        $name = null;
        if ($rec->isSameMonth($inv) || $rec->lt($inv)) {
            $name = 'Category A';
        } elseif ($rec->isSameMonth($inv->copy()->addMonth())) {
            $name = match (true) {
                $rec->day <= 9 => 'Category B',
                $rec->day <= 15 => 'Category C',
                $rec->day <= 25 => 'Category D',
                default => null,
            };
        }

        if (! $name) {
            return false;
        }

        $category = $existing ?? new category();
        $category->name = $name;
        $category->client_id = $receipt->client_id;
        $category->user_id = Auth::id();
        $category->category_month = $invoice->invoice_month;
        $category->save();

        $client = Client::find($receipt->client_id);
        if ($client) {
            $client->category_id = $category->id;
            $client->category_name = $name;
            $client->category_month = $invoice->invoice_month;
            $client->user_id = Auth::id();
            $client->save();
        }

        return true;
    }
}

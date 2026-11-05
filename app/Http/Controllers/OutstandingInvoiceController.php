<?php

namespace App\Http\Controllers;

use App\Exports\OutstandingInvoicesExport;
use App\Support\Dashboards\DashboardContext;
use App\Support\OutstandingInvoices;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Outstanding invoices (unpaid and part paid) for a period, with an Excel download.
 * Opened from the summaries on the Accounts dashboard, the receipts report and the invoices
 * report; the period and office come in the same query string they use.
 *
 * Offices: as on the Accounts dashboard — head-office roles see every office, everyone else
 * their own office and its sub-offices (?field narrows further; another office is refused).
 */
class OutstandingInvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $ctx = DashboardContext::fromRequest($request, $request->user(), DashboardContext::ACCOUNTS);
        $book = new OutstandingInvoices($ctx->fieldIds, $ctx->from, $ctx->to);
        $status = array_key_exists((string) $request->input('status'), OutstandingInvoices::STATUSES) ? (string) $request->input('status') : null;
        $clientId = $request->integer('client') ?: null;

        $rows = $book->rows($status, $clientId);

        if ($request->input('export') === 'xlsx') {
            $name = 'Outstanding_invoices_' . str_replace([' ', '–'], ['_', 'to'], $book->monthsLabel())
                . ($status ? '_' . $status : '') . '.xlsx';

            return Excel::download(new OutstandingInvoicesExport($rows, $book, $ctx->scopeLabel(), $status), $name);
        }

        return view('invoices_outstanding.index', [
            'ctx' => $ctx,
            'book' => $book,
            'rows' => $rows,
            'summary' => $book->summary(),
            'offices' => $book->byOffice(),
            'status' => $status,
            'clientId' => $clientId,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Support\ReceiptReport;
use App\Http\Requests\StoreReceiptRequest;
use App\Http\Requests\UpdateReceiptRequest;
use App\Exports\FilteredQueryExport;
use App\Http\Controllers\Concerns\SearchesDates;
use App\Models\Field;
use Carbon\Carbon;
use App\Http\Requests\InvoiceToPayrollSearchRequest;
use Illuminate\Http\Request;
use App\Models\category;
use App\Models\Client;
use App\Models\Collection;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wht;
use App\Models\Bank;
use App\Services\Receipts\BankPosting;
use App\Services\Receipts\InvoiceSettlement;
use App\Services\Receipts\ReceiptRecorder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
// use Illuminate\Support\Number;
use Illuminate\Support\Str;
use function Symfony\Component\Clock\now;

class ReceiptController extends Controller
{
    use SearchesDates;


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Receipts report for a period (daily ... yearly) or any date range, optionally
     * narrowed to the invoice months the receipts paid: totals, comparison with the
     * previous window, which invoice months were paid and how late, trend, payment
     * methods, field offices, top clients / collectors. Figures: App\Support\ReceiptReport.
     */
    public function report(Request $request)
    {
        $report = ReceiptReport::fromRequest($request, $this->reportAllowedFieldIds());
        $previous = $report->shifted(1);
        $totals = $report->totals();

        // Invoice months paid in this window, ignoring the invoice-month filter (the picker chips).
        $allMonths = $report->hasInvoiceFilter() ? (clone $report)->withInvoiceMonths(null, null)->byInvoiceMonth() : null;
        $byInvoiceMonth = $report->byInvoiceMonth();

        return view('sales.receipt_report', [
            'report' => $report,
            'byInvoiceMonth' => $byInvoiceMonth,
            'invoiceMonthChips' => $allMonths ?? $byInvoiceMonth,
            'timing' => $report->timing(),
            'matrix' => $report->matrix(),
            'collection' => $report->invoiceCollection($totals->received),
            'period' => $report->period,
            'from' => $report->from,
            'to' => $report->to,
            'anchor' => $report->anchor,
            'totals' => $totals,
            'prevTotals' => $previous->totals(),
            'previous' => $previous,
            'pending' => $report->awaitingApproval(),
            'byField' => $report->byField(),
            'topClients' => $report->topClients(),
            'topCollectors' => $report->topCollectors(),
            'trend' => $report->trend(),
            'projection' => $report->projection(),
        ]);
    }



    /**
     * Field offices the authenticated user may see in the receipt report.
     * Mirrors the access rule used by the receipt list's datatable()
     * (and index()): a user is limited to their own field office, with
     * Tema (field 3) also covering Shaihills (field 7). The sole
     * exception is Finance Manager, who can see every field office.
     *
     * Returns null when unrestricted (see every field office), or an
     * array of allowed field ids (possibly empty, meaning "see nothing")
     * otherwise.
     */
    protected function reportAllowedFieldIds(): ?array
    {
        $user = Auth::user();

        if ($user?->hasRole(['Finance Manager'])) {
            return null;
        }

        return $user?->field_id === 3 ? [3, 7] : array_filter([$user?->field_id]);
    }


    /**
     * Clients with receipts in one field office, for the report's window
     * (used by the "By Field Office" drilldown).
     */
    public function fieldDetails(Request $request, $fieldId)
    {
        $fieldIds = $this->reportAllowedFieldIds();
        if ($fieldIds !== null && ! in_array((int) $fieldId, $fieldIds, true)) {
            abort(403);
        }

        $clients = ReceiptReport::fromRequest($request, $fieldIds)->fieldClients((int) $fieldId)
            ->map(fn ($c) => ['id' => $c->id, 'business_name' => $c->business_name, 'name' => $c->name, 'entries' => (int) $c->cnt, 'total' => (float) $c->total]);

        return response()->json(['clients' => $clients]);
    }


    /**
     * Collectors who receipted payments for a client in the report's window
     * (used by the "Top 10 Clients" drilldown).
     */
    public function clientDetails(Request $request, $clientId)
    {
        $fieldIds = $this->reportAllowedFieldIds();
        if ($fieldIds !== null) {
            $clientFieldId = Client::whereKey($clientId)->value('field_id');
            if ($clientFieldId === null || ! in_array((int) $clientFieldId, $fieldIds, true)) {
                abort(403);
            }
        }

        $collectors = ReceiptReport::fromRequest($request, $fieldIds)->clientCollectors((int) $clientId)
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'entries' => (int) $u->cnt, 'total' => (float) $u->total]);

        return response()->json(['collectors' => $collectors]);
    }


    /**
     * Clients a collector receipted in the report's window (used by the
     * "Top 10 Collectors" drilldown). Limited to the user's field offices.
     */
    public function collectorDetails(Request $request, $collectorId)
    {
        $clients = ReceiptReport::fromRequest($request, $this->reportAllowedFieldIds())->collectorClients((int) $collectorId)
            ->map(fn ($c) => ['id' => $c->id, 'business_name' => $c->business_name, 'name' => $c->name, 'entries' => (int) $c->cnt, 'total' => (float) $c->total]);

        return response()->json(['clients' => $clients]);
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $user = Auth::user();

        if($user->role->name == 'Finance Manager' || $user->role->name == 'Invoice' )
        {
            $accra = null;
            $botwe = null;
            $tema = null;
            $shaihills = null;
            $takoradi = null;
            $koforidua = null;
            $kumasi = null;

            $receipts = Receipt::where('ho_status', 'approved')->get();
            $accra = Receipt::whereRelation('client', 'field_id', '1')->where('ho_status', 'approved')->get();
            $botwe = Receipt::whereRelation('client', 'field_id', '2')->where('ho_status', 'approved')->get();

            $tema = Receipt::whereRelation('client', 'field_id', '3')->where('ho_status', 'approved')->get();
            $shaihills = Receipt::whereRelation('client', 'field_id', '7')->where('ho_status', 'approved')->get();
            // $temashai = $tema->concat($shaihills);
            $takoradi = Receipt::whereRelation('client', 'field_id', '4')->where('ho_status', 'approved')->get();
            $koforidua = Receipt::whereRelation('client', 'field_id', '5')->where('ho_status', 'approved')->get();
            $kumasi = Receipt::whereRelation('client', 'field_id', '6')->where('ho_status', 'approved')->get();
            // foreach
            // if($user->feild->name == 'Accra')
            // {

            // }



            return view('sales.receipt_list', compact('receipts', 'accra', 'botwe', 'tema', 'shaihills', 'takoradi', 'koforidua', 'kumasi'));
        }

        if($user->field?->name == 'Accra' )
        {
            $receipts = Receipt::whereRelation('client', 'field_id', '1')->where('ho_status', 'approved')->get();
        }

        if ($user->field?->name == 'Botwe')
        {
            $receipts = Receipt::whereRelation('client', 'field_id', '2')->where('ho_status', 'approved')->get();
        }

        if ($user->field?->name == 'Tema')
        {
            // $receipts = [];
            $receiptsTema = Receipt::whereRelation('client', 'field_id', '3')->where('ho_status', 'approved')->get();
             $receipts1 =  collect($receiptsTema) ;
            $receiptsShaihills = Receipt::whereRelation('client', 'field_id', '7')->where('ho_status', 'approved')->get();
            $receipts2 = collect($receiptsShaihills) ;
            $receipts = $receiptsTema->concat($receipts2);
            // dd($receipts, $receipts1, $receipts2);
        }

        if ($user->field?->name == 'Takoradi')
        {
            $receipts = Receipt::whereRelation('client', 'field_id', '4')->where('ho_status', 'approved')->get();
        }

        if ($user->field?->name == 'Koforidua')
        {
            $receipts = Receipt::whereRelation('client', 'field_id', '5')->where('ho_status', 'approved')->get();
        }

        if ($user->field?->name == 'Kumasi')
        {
            $receipts = Receipt::whereRelation('client', 'field_id', '6')->where('ho_status', 'approved')->get();
        }

        return view('sales.receipt_list', compact('receipts'));
    }



      /** Balance is computed, so it is defined once and reused for filter + sort. */
    private const RECEIPT_BALANCE = '(COALESCE(invoices.total,0) - COALESCE(receipts.total,0) - COALESCE(receipts.dAmount,0))';
 
    /** DataTables column index => SQL column. Indexes match the <thead> / columns[] order. */
    private const RECEIPT_TEXT_COLUMNS = [
        5  => 'clients.phone_number',
        6  => 'fields.name',
        7  => 'users.name',
        11 => 'receipts.cheque_bank',
        12 => 'receipts.cheque_reference',
        14 => 'receipts.transfer_bank',
        15 => 'receipts.transfer_reference',
        24 => 'receipts.advance_payment',
        25 => 'receipts.status',
    ];
 
    private const RECEIPT_NUMBER_COLUMNS = [
        9  => 'invoices.total',
        10 => 'receipts.total',
        13 => 'receipts.cheque_amount',
        16 => 'receipts.transfer_amount',
        17 => 'receipts.momo_amount',
        18 => 'receipts.cash_amount',
        19 => 'receipts.dAmount',
        20 => 'receipts.other_payment_amnt',
        21 => 'receipts.wht_amount',
        22 => 'receipts.vat7_value',
        23 => self::RECEIPT_BALANCE,
    ];
 
    private const RECEIPT_ORDER_COLUMNS = [
        0  => 'receipts.id',
        1  => 'receipts.receipt_month',
        2  => 'receipts.invoice_id',
        3  => 'invoices.invoice_month',
        4  => 'clients.business_name',
        5  => 'clients.phone_number',
        6  => 'fields.name',
        7  => 'users.name',
        8  => 'receipts.created_at',
        9  => 'invoices.total',
        10 => 'receipts.total',
        11 => 'receipts.cheque_bank',
        12 => 'receipts.cheque_reference',
        13 => 'receipts.cheque_amount',
        14 => 'receipts.transfer_bank',
        15 => 'receipts.transfer_reference',
        16 => 'receipts.transfer_amount',
        17 => 'receipts.momo_amount',
        18 => 'receipts.cash_amount',
        19 => 'receipts.dAmount',
        20 => 'receipts.other_payment_amnt',
        21 => 'receipts.wht_amount',
        22 => 'receipts.vat7_value',
        23 => self::RECEIPT_BALANCE,
        24 => 'receipts.advance_payment',
        25 => 'receipts.status',
    ];
 
    /** Select + joins + the user's office scope. No user-supplied filters here. */
    private function receiptListBase()
    {
        $user = Auth::user();
 
        $query = Receipt::query()
            ->select([
                'receipts.id', 'receipts.receipt_month', 'receipts.invoice_id',
                'receipts.created_at', 'receipts.total', 'receipts.cheque_bank',
                'receipts.cheque_reference', 'receipts.cheque_amount',
                'receipts.transfer_bank', 'receipts.transfer_reference',
                'receipts.transfer_amount', 'receipts.momo_amount', 'receipts.cash_amount',
                'receipts.dAmount', 'receipts.other_payment_amnt', 'receipts.wht_amount',
                'receipts.vat7_value', 'receipts.advance_payment', 'receipts.status',
                'invoices.invoice_month', 'invoices.total as invoice_total',
                'clients.business_name as client_business_name', 'clients.name as client_name',
                'clients.phone_number', 'fields.name as field_name', 'users.name as staff_name',
            ])
            // How many invoices this receipt paid (more than one for multi-invoice receipts).
            ->selectSub(
                DB::table('receipt_allocations')->selectRaw('COUNT(DISTINCT invoice_id)')->whereColumn('receipt_allocations.receipt_id', 'receipts.id'),
                'invoice_count'
            )
            ->leftJoin('invoices', 'invoices.id', '=', 'receipts.invoice_id')
            ->leftJoin('clients', 'clients.id', '=', 'receipts.client_id')
            ->leftJoin('fields', 'fields.id', '=', 'clients.field_id')
            ->leftJoin('users', 'users.id', '=', 'receipts.user_id')
            ->where('receipts.ho_status', 'approved');
 
        // Same office access rules as index(), including Tema's shared access to Shaihills.
        if (! $user?->hasRole(['Finance Manager', 'Invoice'])) {
            $fieldIds = $user?->field_id === 3 ? [3, 7] : array_filter([$user?->field_id]);
            $fieldIds ? $query->whereIn('clients.field_id', $fieldIds) : $query->whereRaw('1 = 0');
        }
 
        return $query;
    }
 
    /** Range picker + global search + per-column filters. */
    private function receiptListFilters($query, Request $request): void
    {
        // Range picker (receipt date). Y-m-d or Y-m; blank / invalid edges are ignored.
        $this->whereWithinRange(
            $query,
            'receipts.receipt_month',
            $this->requestString($request, 'from'),
            $this->requestString($request, 'to')
        );
 
        // Global search box.
        $globalSearch = trim((string) $this->requestString($request, 'search.value'));
        if ($globalSearch !== '') {
            $term = $this->likeTerm($globalSearch);
 
            $query->where(function ($q) use ($globalSearch, $term) {
                $q->where('receipts.status', 'like', $term)
                    ->orWhere('clients.business_name', 'like', $term)
                    ->orWhere('clients.name', 'like', $term)
                    ->orWhere('clients.phone_number', 'like', $term)
                    ->orWhere('fields.name', 'like', $term)
                    ->orWhere('users.name', 'like', $term)
                    ->orWhere(function ($w) use ($globalSearch) {
                        $this->whereDateMatches($w, 'receipts.receipt_month', $globalSearch);
                    })
                    ->orWhere(function ($w) use ($globalSearch) {
                        $this->whereDateMatches($w, 'invoices.invoice_month', $globalSearch);
                    });
 
                // Only treat it as an id when it looks like one ("45", "FWSSR45").
                if (preg_match('/^(fwss[ri]?)?\s*#?\d+$/i', $globalSearch)) {
                    $q->orWhere('receipts.id', 'like', '%' . preg_replace('/\D/', '', $globalSearch) . '%');
                }
            });
        }
 
        // Per-column filters (ColumnControl, falling back to the standard field).
        $columns = $request->input('columns', []);
        foreach (is_array($columns) ? $columns : [] as $index => $column) {
            $value = $this->columnFilterValue($column);
            if ($value === '') {
                continue;
            }
 
            $index = (int) $index;
 
            switch (true) {
                case $index === 0:
                    $this->whereIdMatches($query, 'receipts.id', $value);
                    break;
 
                case $index === 1:
                    $this->whereDateMatches($query, 'receipts.receipt_month', $value);
                    break;
 
                case $index === 2:
                    $this->whereIdMatches($query, 'receipts.invoice_id', $value);
                    break;
 
                case $index === 3:
                    $this->whereDateMatches($query, 'invoices.invoice_month', $value);
                    break;
 
                case $index === 4:
                    $term = $this->likeTerm($value);
                    $query->where(function ($q) use ($term) {
                        $q->where('clients.business_name', 'like', $term)
                            ->orWhere('clients.name', 'like', $term);
                    });
                    break;
 
                case $index === 8:
                    $this->whereDateMatches($query, 'receipts.created_at', $value);
                    break;
 
                case isset(self::RECEIPT_TEXT_COLUMNS[$index]):
                    $query->where(self::RECEIPT_TEXT_COLUMNS[$index], 'like', $this->likeTerm($value));
                    break;
 
                case isset(self::RECEIPT_NUMBER_COLUMNS[$index]):
                    $this->whereNumberMatches($query, self::RECEIPT_NUMBER_COLUMNS[$index], $value);
                    break;
            }
        }
    }
 
    private function receiptListOrder($query, Request $request): void
    {
        $orderColumn = (int) $request->input('order.0.column', 8);
        $orderDir = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';   // validated
        $orderExpr = self::RECEIPT_ORDER_COLUMNS[$orderColumn] ?? 'receipts.created_at'; // whitelisted
 
        // Unique tiebreaker: stable paging AND required for chunked Excel export.
        $query->orderByRaw("{$orderExpr} {$orderDir}")->orderBy('receipts.id', 'desc');
    }
 
    /**
     * Paginated receipt data for the receipt-list DataTable.
     */
    public function datatable(Request $request)
    {
        $user = Auth::user();
 
        $query = $this->receiptListBase();
        $recordsTotal = (clone $query)->count();
 
        $this->receiptListFilters($query, $request);
        $recordsFiltered = (clone $query)->count();
 
        $this->receiptListOrder($query, $request);
 
        $start = max(0, (int) $request->input('start', 0));
        $length = max(1, min(2000, (int) $request->input('length', 25)));
        $rows = $query->offset($start)->limit($length)->get();
        $canManage = $user?->hasRole(['Finance Manager']) ?? false;
 
        $data = $rows->map(function ($receipt) use ($canManage) {
            $clientName = $receipt->client_name === $receipt->client_business_name
                ? $receipt->client_business_name
                : trim($receipt->client_name . ' ' . $receipt->client_business_name);
            $statusClass = $receipt->status === 'completed' ? 'bg-label-success' : 'bg-label-danger';
 
            $actions = '<div class="dropdown"><button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded text-primary"></i></button><div class="dropdown-menu"><a class="dropdown-item" href="' . e(url('receipt/' . $receipt->id)) . '"><i class="icon-base bx bx-bullseye text-primary me-2"></i> view</a>';
            if ($canManage) {
                $receiptUrl = e(url('receipt/' . $receipt->id));
                $actions .= '<a class="dropdown-item" href="' . $receiptUrl . '/edit"><i class="icon-base bx bx-edit-alt me-2 text-primary"></i> Edit</a>'
                    . '<form action="' . $receiptUrl . '" method="POST"><input type="hidden" name="_token" value="' . e(csrf_token()) . '"><input type="hidden" name="_method" value="DELETE"><button class="dropdown-item" type="submit"><i class="icon-base bx bx-trash me-2 text-danger"></i>Delete</button></form>';
            }
            $actions .= '</div></div>';
 
            // Absolute date is what the column search matches; the relative label is a tooltip.
            $createdCell = $receipt->created_at
                ? '<span title="' . e(Carbon::parse($receipt->created_at)->diffForHumans()) . '">'
                    . e(Carbon::parse($receipt->created_at)->format('d M Y, H:i')) . '</span>'
                : '';
 
            return [
                'receipt_id' => 'FWSSR' . $receipt->id,
                'receipt_month' => $receipt->receipt_month ? Carbon::parse($receipt->receipt_month)->format('l j, F Y') : '',
                'invoice_id' => ! $receipt->invoice_id
                    ? 'Advance'
                    : 'FWSSi' . $receipt->invoice_id . ((int) $receipt->invoice_count > 1 ? ' +' . ((int) $receipt->invoice_count - 1) . ' more' : ''),
                'invoice_month' => $receipt->invoice_month ? Carbon::parse($receipt->invoice_month)->format('F, Y') : '',
                'client_name' => $clientName, 'phone_number' => $receipt->phone_number,
                'field_name' => $receipt->field_name, 'staff_name' => $receipt->staff_name,
                'created_at' => $createdCell,
                'invoice_total' => number_format((float) $receipt->invoice_total, 2),
                'total' => number_format((float) $receipt->total, 2),
                'cheque_bank' => $receipt->cheque_bank, 'cheque_reference' => $receipt->cheque_reference,
                'cheque_amount' => number_format((float) $receipt->cheque_amount, 2),
                'transfer_bank' => $receipt->transfer_bank, 'transfer_reference' => $receipt->transfer_reference,
                'transfer_amount' => number_format((float) $receipt->transfer_amount, 2),
                'momo_amount' => number_format((float) $receipt->momo_amount, 2), 'cash_amount' => number_format((float) $receipt->cash_amount, 2),
                'deductions' => number_format((float) $receipt->dAmount, 2), 'other_payment' => number_format((float) $receipt->other_payment_amnt, 2),
                'wht_amount' => number_format((float) $receipt->wht_amount, 2), 'vat7_value' => number_format((float) $receipt->vat7_value, 2),
                'balance' => (int) $receipt->invoice_count > 1 || ! $receipt->invoice_id
                    ? '—'
                    : number_format((float) $receipt->invoice_total - (float) $receipt->total - (float) $receipt->dAmount, 2),
                'advance_payment' => $receipt->advance_payment, 'status' => '<span class="badge ' . $statusClass . '">' . e($receipt->status) . '</span>', 'action' => $actions,
            ];
        })->all();
 
        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }
 
    /**
     * Excel export of EVERY receipt matching the current filters (not just the visible page).
     */
    public function export(Request $request)
    {
        $query = $this->receiptListBase();
        $this->receiptListFilters($query, $request);
        $this->receiptListOrder($query, $request);
 
        $headings = [
            'Receipt No.', 'Receipt Date', 'Invoice No.', 'Invoice Month', 'Client', 'Phone', 'Field Office',
            'Staff', 'Date Created', 'Invoice Amount', 'Paid', 'Cheque Bank', 'Cheque Ref', 'Cheque Amount',
            'Transfer Bank', 'Transfer Ref', 'Transfer Amount', 'MoMo', 'Cash', 'Deductions', 'Other Payment',
            'WHT', 'VAT 7%', 'Balance', 'Advance', 'Status',
        ];
 
        $export = new FilteredQueryExport($query, $headings, function ($r) {
            $day = fn ($v) => $v ? Carbon::parse($v)->format('Y-m-d') : '';
            $client = $r->client_name === $r->client_business_name
                ? $r->client_business_name
                : trim($r->client_name . ' ' . $r->client_business_name);
 
            return [
                'FWSSR' . $r->id,
                $day($r->receipt_month),
                'FWSSi' . $r->invoice_id,
                $r->invoice_month ? Carbon::parse($r->invoice_month)->format('F Y') : '',
                $client, $r->phone_number, $r->field_name, $r->staff_name,
                $r->created_at ? Carbon::parse($r->created_at)->format('Y-m-d H:i') : '',
                (float) $r->invoice_total, (float) $r->total,
                $r->cheque_bank, $r->cheque_reference, (float) $r->cheque_amount,
                $r->transfer_bank, $r->transfer_reference, (float) $r->transfer_amount,
                (float) $r->momo_amount, (float) $r->cash_amount, (float) $r->dAmount,
                (float) $r->other_payment_amnt, (float) $r->wht_amount, (float) $r->vat7_value,
                (float) $r->invoice_total - (float) $r->total - (float) $r->dAmount,
                $r->advance_payment, $r->status,
            ];
        });
 
        return $export->download('Receipts-' . now()->format('Ymd-His') . '.xlsx');
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $user = Auth::user();
        // dd($user->field?->name);

        if ($user->role->name == 'Finance Manager' || $user->role->name == 'Invoice')
        {
            $invoices = Invoice::where('status', 'unpaid')->orwhere('status', 'uncompleted')->get();
            return view('sales.receipt_create', compact('invoices'));
        }


        if ($user->field?->name == 'Accra')
        {

            $invoicesAccra = Invoice::whereRelation('client', 'field_id', '1')->where('status', 'unpaid')->orwhere('status', 'uncompleted')->get();
            // dd($invoicesAccra);
            $invoices = [];

            foreach($invoicesAccra as $accra)
                {
                    if($accra->client->field_id == '1')
                    {
                        $invoices[] = $accra;
                    }
                }
            // dd($invoices);

            return view('sales.receipt_create', compact('invoices'));
            
            // return "Accra";
        }

        if ($user->field?->name == 'Botwe')
        {
            $invoicesBotwe = Invoice::whereRelation('client', 'field_id', '2')->where('status', 'unpaid')->orwhere('status', 'uncompleted')->get();
            // dd($invoicesBotwe);
            $invoices = [];

            foreach($invoicesBotwe as $botwe)
                {
                    if($botwe->client->field_id == '2')
                    {
                        $invoices[] = $botwe;
                    }
                }

            return view('sales.receipt_create', compact('invoices'));
           
            // return "Botwe";

        }

        if ($user->field?->name == 'Tema')
        {
            $invoicesTema = Invoice::whereRelation('client', 'field_id', '3')->where('status', 'unpaid')->orwhere('status', 'uncompleted')->get();
            $invoicesShaihills = Invoice::whereRelation('client', 'field_id', '7')->where('status', 'unpaid')->orwhere('status', 'uncompleted')->get();
            // dd($invoicesTema);
            $invoices = [];

            foreach($invoicesTema as $tema)
                {
                    if($tema->client->field_id == '3')
                    {
                        $invoices[] = $tema;
                    }
                }
            foreach($invoicesShaihills as $shai)
                {
                    if($shai->client->field_id == '7')
                    {
                        $invoices[] = $shai;
                    }
                }
            
            return view('sales.receipt_create', compact('invoices'));
       
        }

        if ($user->field?->name == 'Takoradi')
        {
            $invoicesTakoradi = Invoice::whereRelation('client', 'field_id', '4')->where('status', 'unpaid')->orwhere('status', 'uncompleted')->get();
            // dd($invoicesTakoradi);
           
            $invoices = [];

            foreach($invoicesTakoradi as $takoradi)
                {
                    if($takoradi->client->field_id == '4')
                    {
                        $invoices[] = $takoradi;
                    }
                }

            return view('sales.receipt_create', compact('invoices'));
       
            // return "Takoradi";
       
        }

        if ($user->field?->name == 'Koforidua')
        {
            $invoicesKoforidua = Invoice::whereRelation('client', 'field_id', '5')->where('status', 'unpaid')->orwhere('status', 'uncompleted')->get();
            // dd($invoicesKoforidua);
            
            $invoices = [];

            foreach($invoicesKoforidua as $koforidua)
                {
                    if($koforidua->client->field_id == '5')
                    {
                        $invoices[] = $koforidua;
                    }
                }

            return view('sales.receipt_create', compact('invoices'));
           
            // return "Koforidua";
        
        }

        if ($user->field?->name == 'Kumasi')
        {
            $invoicesKumasi = Invoice::whereRelation('client', 'field_id', '6')->where('status', 'unpaid')->orwhere('status', 'uncompleted')->get();
            // dd($invoicesKumasi);
            $invoices = [];

            foreach($invoicesKumasi as $kumasi)
                {
                    if($kumasi->client->field_id == '6')
                    {
                        $invoices[] = $kumasi;
                    }
                }
            return view('sales.receipt_create', compact('invoices'));
           
            // return "Kumasi";
        
        }

        // return view('sales.receipt_create', compact('invoices'));

    }



    /**
     * Create a receipt for one invoice.
     *
     * Status and balance are no longer chosen on the form: the payment is
     * applied to what the invoice still owes and the invoice is re-derived
     * from all of its receipts (see App\Services\Receipts\ReceiptRecorder).
     * Money beyond what is owed becomes client credit, only when the cashier
     * ticks "keep as credit".
     */
    public function store(StoreReceiptRequest $request, ReceiptRecorder $recorder)
    {
        $invoice = Invoice::findOrFail($request->integer('invoice_id'));
        $client = Client::findOrFail($invoice->client_id);   // the invoice decides the client, not the form

        $receipt = $recorder->create(
            $client,
            ReceiptRecorder::singleInvoiceInput($request, $invoice->id),
            $request->file('image')
        );

        return redirect()->route('receipt.show', ['receipt' => $receipt->id])
            ->with($recorder->notes ? 'warning' : 'primary', $this->recordedMessage($receipt, $recorder->notes));
    }

    private function recordedMessage(Receipt $receipt, array $notes = [], string $verb = 'created'): string
    {
        $message = 'Receipt FWSSR' . $receipt->id . ' ' . $verb . ' successfully.';
        if ($receipt->hasUnappliedCredit()) {
            $message .= ' GH₵' . number_format($receipt->unapplied_amount, 2) . ' kept as client credit.';
        }

        return trim($message . ' ' . implode(' ', $notes));
    }


    /**
     * Display the specified resource.
     */
    public function show(Receipt $receipt)
    {
        //
        $wht = new Wht();
        $receipt->load(['allocations.invoice', 'chequeToBank', 'transferToBank']);

        // Open invoices of this client, for applying any unapplied credit.
        $creditInvoices = collect();
        if ($receipt->hasUnappliedCredit()) {
            $creditInvoices = Invoice::where('client_id', $receipt->client_id)
                ->whereIn('status', ['unpaid', 'uncompleted'])
                ->withSum('allocations as settled_sum', 'settled')
                ->orderBy('invoice_month')->orderBy('id')
                ->get();
        }

        return view('sales.receipt_show', compact('receipt','wht', 'creditInvoices'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Receipt $receipt)
    {
        //
        // dd($receipt);
        if ($this->isManagedByMultiInvoiceFlow($receipt)) {
            return redirect()->route('receipt.show', ['receipt' => $receipt->id])
                ->with('warning', 'This receipt pays several invoices or holds client credit. To change it, delete it and record it again with "Pay multiple invoices".');
        }

        $mode = DB::table('receipt_mode')->get();
        $status = DB::table('receipt_status')->get();
         $wht = new Wht();
         $invoice = Invoice::findorFail($receipt->invoice_id);
        $banks = Bank::orderBy('name')->get();

        $user = Auth::user();
        $staff =  User::all();
        $manager = $staff->where('department_id', '7')->where('role_id', '3');
        $finance_manager = $staff->where('department_id', '1')->where('role_id', '2');
        $assign_staff = [];
        // dd($manager);
        if($user->role->name == 'Admin Assistant')
            {
                $assign_staff = $manager;
            }

        if($user->role->name == 'Manager')
            {
                $assign_staff = $finance_manager;
            }


        return view('sales.receipt_edit', compact('receipt','wht','invoice','mode','status', 'assign_staff', 'user', 'banks'));
    }

    /**
     * Edit a receipt. Its old effect on the invoice is removed and the new
     * amounts applied from scratch, so an edit can never subtract twice,
     * overwrite another receipt's WHT, or reopen a paid invoice.
     */
    public function update(UpdateReceiptRequest $request, Receipt $receipt, ReceiptRecorder $recorder)
    {
        // Cash already taken to the bank cannot be changed here.
        $collection = Collection::where('receipt_id', $receipt->id)->first();
        if (self::isDeposited($collection))
        {
            return redirect()->back()->with('error', 'Receipt has been Deposited and cannot be edited');
        }

        if ($this->isManagedByMultiInvoiceFlow($receipt)) {
            return redirect()->route('receipt.show', ['receipt' => $receipt->id])
                ->with('warning', 'This receipt pays several invoices or its credit has been applied to other invoices, so it cannot be changed from the single-invoice edit form.');
        }

        $receipt = $recorder->revise(
            $receipt,
            ReceiptRecorder::singleInvoiceInput($request, (int) $receipt->invoice_id),
            $request->file('image')
        );

        return redirect()->route('receipt.show', ['receipt' => $receipt->id])
            ->with('primary', $this->recordedMessage($receipt, [], 'updated'));
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Receipt $receipt)
    {
        // A receipt whose money has been banked cannot be deleted.
        $collection = Collection::where('receipt_id', $receipt->id)->first();
        if (self::isDeposited($collection))
        {
            return redirect()->back()->with('error', 'Receipt has been Deposited and cannot be Deleted');
        }

        DB::transaction(function () use ($receipt, $collection) {
            // Take a posted transfer back out of the bank (adds a reversal line).
            app(BankPosting::class)->reverse($receipt);

            $collection?->delete();

            // Remove this receipt's allocation lines and ledger rows, then
            // re-derive every invoice it touched from the receipts that remain.
            // (Previously the invoice was reset to "unpaid" even when other
            // receipts had part-paid it.)
            app(InvoiceSettlement::class)->release($receipt);

            $receipt->delete();
        });

        return redirect('receipt')->with('error', 'Receipt Deleted Successfully');
    }


    public function receiptCreate(int $invoice_id)
    {
        $invoice = Invoice::findorFail($invoice_id);

        $wht_rate = new Wht();
        // dd($wht_rate->wht_rate);
        $mode = DB::table('receipt_mode')->get();
        $status = DB::table('receipt_status')->get();

        $user = Auth::user();
        $staff =  User::all();
        $manager = $staff->where('department_id', '7')->where('role_id', '3');
        $finance_manager = $staff->where('department_id', '1')->where('role_id', '2');
        $assign_staff = [];
        // dd($manager);
        if($user->role->name == 'Admin Assistant')
            {
                $assign_staff = $manager;
            }

        if($user->role->name == 'Manager')
            {
                $assign_staff = $finance_manager;
            }


        $banks = Bank::orderBy('name')->get();

        // What is really still owed (from the receipts on it), and any credit the client already holds.
        $outstanding = $invoice->outstandingAmount();
        $creditReceipts = Receipt::where('client_id', $invoice->client_id)->where('unapplied_amount', '>', 0)->orderBy('id')->get();

        return view('sales.receiptCreate', compact('invoice', 'wht_rate', 'mode', 'status', 'assign_staff', 'banks', 'outstanding', 'creditReceipts'));
    }




    /**
     * Receipts that pay several invoices, or carry an advance/credit, are
     * created and maintained by MultiInvoiceReceiptController; the classic
     * one-invoice edit form would corrupt their allocations.
     */
    private function isManagedByMultiInvoiceFlow(Receipt $receipt): bool
    {
        if (! $receipt->invoice_id) {
            return true;
        }

        // A single-invoice receipt holding unused credit can still be edited;
        // once its credit has been applied to other invoices it cannot.
        return $receipt->allocations()->distinct()->count('invoice_id') > 1
            || $receipt->allocations()->where('source', 'credit')->exists();
    }

    private static function isDeposited(?Collection $collection): bool
    {
        // Deposits save "Deposited"; older code compared against "deposited".
        return $collection && strcasecmp((string) $collection->status, 'deposited') === 0;
    }


    public function dashboardAllPayment()
    {
        // $receipt_AllPayment = Receipt::sum('total');

        // $reportReceipt =  Receipt::all();
        // $accra = Receipt::whereRelation('client', 'field_id', 1)->get();
        // $accraTotal = $accra->sum('total');
        // $accraCount = count($accra);

        // $botwe = Receipt::whereRelation('client', 'field_id', 2)->get();
        // $botweTotal = $botwe->sum('total');
        // $botweCount = count($botwe);

        // $tema = Receipt::whereRelation('client', 'field_id', 3)->get();
        // $temaTotal = $tema->sum('total');
        // $temaCount = count($tema);

        // $takoradi = Receipt::whereRelation('client', 'field_id', 4)->get();
        // $takoradiTotal = $takoradi->sum('total');
        // $takoradiCount = count($takoradi);

        // $koforidua = Receipt::whereRelation('client', 'field_id', 5)->get();
        // $koforiduaTotal = $koforidua->sum('total');
        // $koforiduaCount = count($koforidua);

        // $kumasi = Receipt::whereRelation('client', 'field_id', 6)->get();
        // $kumasiTotal = $kumasi->sum('total');
        // $kumasiCount = count($kumasi);

        // $shyhills = Receipt::whereRelation('client', 'field_id', 7)->get();
        // $shyhillsTotal = $shyhills->sum('total');
        // $shyhillsCount = count($shyhills);

        return view('sales.receipt_dashboard');
    }

    // Search all receipts for a given month
    public function receiptSearch(InvoiceToPayrollSearchRequest $request)
    {
       $month = Carbon::parse($request->month);
        // dd($month);
         $reportReceipt =  Receipt::whereMonth('receipt_month', $month->month)->get();
         $reportReceiptCount = count($reportReceipt);
         $reportReceiptTotal = $reportReceipt->sum('total');
        // dd($reportReceipt);

        $accra = Receipt::whereRelation('client', 'field_id', 1)->whereMonth('receipt_month', $month->month)->get();
        $accraTotal = $accra->sum('total');
        $accraCount = count($accra);

        $botwe = Receipt::whereRelation('client', 'field_id', 2)->whereMonth('receipt_month', $month->month)->get();
        $botweTotal = $botwe->sum('total');
        $botweCount = count($botwe);

        $tema = Receipt::whereRelation('client', 'field_id', 3)->whereMonth('receipt_month', $month->month)->get();
        $temaTotal = $tema->sum('total');
        $temaCount = count($tema);

        $takoradi = Receipt::whereRelation('client', 'field_id', 4)->whereMonth('receipt_month', $month->month)->get();
        $takoradiTotal = $takoradi->sum('total');
        $takoradiCount = count($takoradi);

        $koforidua = Receipt::whereRelation('client', 'field_id', 5)->whereMonth('receipt_month', $month->month)->get();
        $koforiduaTotal = $koforidua->sum('total');
        $koforiduaCount = count($koforidua);

        $kumasi = Receipt::whereRelation('client', 'field_id', 6)->whereMonth('receipt_month', $month->month)->get();
        $kumasiTotal = $kumasi->sum('total');
        $kumasiCount = count($kumasi);

        $shyhills = Receipt::whereRelation('client', 'field_id', 7)->whereMonth('receipt_month', $month->month)->get();
        $shyhillsTotal = $shyhills->sum('total');
        $shyhillsCount = count($shyhills);

        return view('sales.receipt_dashboard_search', compact('reportReceipt', 'reportReceiptCount', 'month','reportReceiptTotal','accra', 'botwe', 'tema', 'shyhills','takoradi', 'koforidua', 'kumasi' ,'accraTotal', 'accraCount', 'botweTotal', 'botweCount', 'temaTotal', 'temaCount', 'shyhillsTotal', 'shyhillsCount', 'takoradiTotal', 'takoradiCount', 'koforiduaTotal', 'koforiduaCount', 'kumasiTotal', 'kumasiCount'));
    }

        // Search all receipts for a given month
    public function receiptListSearch(InvoiceToPayrollSearchRequest $request)
    {
       $month = Carbon::parse($request->month);
        // dd($month);
         $receipts =  Receipt::whereMonth('receipt_month', $month->month)->where('ho_status', 'approved')->get();
         $reportReceiptCount = count($receipts);
         $reportReceiptTotal = $receipts->sum('total');
        // dd($reportReceipt);

        $accra = Receipt::whereRelation('client', 'field_id', 1)->whereMonth('receipt_month', $month->month)->where('ho_status', 'approved')->get();
        $accraTotal = $accra->sum('total');
        $accraCount = count($accra);

        $botwe = Receipt::whereRelation('client', 'field_id', 2)->whereMonth('receipt_month', $month->month)->where('ho_status', 'approved')->get();
        $botweTotal = $botwe->sum('total');
        $botweCount = count($botwe);

        $tema = Receipt::whereRelation('client', 'field_id', 3)->whereMonth('receipt_month', $month->month)->where('ho_status', 'approved')->get();
        $temaTotal = $tema->sum('total');
        $temaCount = count($tema);

        $takoradi = Receipt::whereRelation('client', 'field_id', 4)->whereMonth('receipt_month', $month->month)->where('ho_status', 'approved')->get();
        $takoradiTotal = $takoradi->sum('total');
        $takoradiCount = count($takoradi);

        $koforidua = Receipt::whereRelation('client', 'field_id', 5)->whereMonth('receipt_month', $month->month)->where('ho_status', 'approved')->get();
        $koforiduaTotal = $koforidua->sum('total');
        $koforiduaCount = count($koforidua);

        $kumasi = Receipt::whereRelation('client', 'field_id', 6)->whereMonth('receipt_month', $month->month)->where('ho_status', 'approved')->get();
        $kumasiTotal = $kumasi->sum('total');
        $kumasiCount = count($kumasi);

        $shaihills = Receipt::whereRelation('client', 'field_id', 7)->whereMonth('receipt_month', $month->month)->where('ho_status', 'approved')->get();
        $shaihillsTotal = $shaihills->sum('total');
        $shaihillsCount = count($shaihills);

        return view('sales.receipt_list', compact('receipts', 'accra', 'botwe', 'tema', 'shaihills', 'takoradi', 'koforidua', 'kumasi'));

    }


            // Search all receipts for a given month
    public function receiptPendingSearch(InvoiceToPayrollSearchRequest $request)
    {

        $accra = null;
        $botwe = null;
        $tema = null;
        $shaihills = null;
        $takoradi = null;
        $koforidua = null;
        $kumasi = null;

       $month = Carbon::parse($request->month);
        // dd($month);
         $receipts =  Receipt::whereMonth('receipt_month', $month->month)->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();

        $receiptsAccra = Receipt::whereRelation('client', 'field_id', 1)->whereMonth('receipt_month', $month->month)->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
        $accraR = [];

            foreach($receiptsAccra as $accra)
                {
                    if($accra->client->field_id == '1')
                    {
                        $accraR[] = $accra;
                    }
                }
            $accra = collect($accraR);

        $botweReceipt = Receipt::where('receipt_month', $request->month)->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
           dd($botweReceipt);
        $botweR = [];
            foreach($botweReceipt as $botwe)
                {
                    if($botwe->client->field_id == '2')
                    {
                        $botweR[] = $botwe;
                    }
                }
            $botwe = collect($botweR);
        // dd($botwe);
        $temaReceipt = Receipt::whereRelation('client', 'field_id', 3)->whereMonth('receipt_month', $month->month)->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
            $temaR = [];
            foreach($temaReceipt as $tema)
                {
                    if($tema->client->field_id == '3')
                    {
                        $temaR[] = $tema;
                    }
                }
            $tema = collect($temaR);

        $takoradiReceipt = Receipt::whereRelation('client', 'field_id', 4)->whereMonth('receipt_month', $month->month)->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
            $takoradiR = [];
            foreach($takoradiReceipt as $takoradi)
                {
                    if($takoradi->client->field_id == '4')
                    {
                        $takoradiR[] = $takoradi;
                    }
                }
            $takoradi = collect($takoradiR);

        $koforiduaReceipt = Receipt::whereRelation('client', 'field_id', 5)->whereMonth('receipt_month', $month->month)->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
            $koforiduaR = [];
            foreach($koforiduaReceipt as $koforidua)
                {
                    if($koforidua->client->field_id == '5')
                    {
                        $koforiduaR[] = $koforidua;
                    }
                }
            $koforidua = collect($koforiduaR);

        $kumasiReceipt = Receipt::whereRelation('client', 'field_id', 6)->whereMonth('receipt_month', $month->month)->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
            $kumasiR = [];
            foreach($kumasiReceipt as $kumasi)
                {
                    if($kumasi->client->field_id == '6')
                    {
                        $kumasiR[] = $kumasi;
                    }
                }
            $kumasi = collect($kumasiR);

        $shaihillsReceipt = Receipt::whereRelation('client', 'field_id', 7)->whereMonth('receipt_month', $month->month)->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
            $shaihillsR = [];
            foreach($shaihillsReceipt as $shaihills)
                {
                    if($shaihills->client->field_id == '7')
                    {
                        $shaihillsR[] = $shaihills;
                    }
                }
            $shaihills = collect($shaihillsR);

        return view('sales.receipt_pending', compact('receipts', 'accra', 'botwe', 'tema', 'shaihills', 'takoradi', 'koforidua', 'kumasi', 'month'));

    }



    public function dashboardCashPayment()
    {
        $cashReceipt =  Receipt::where('cash_amount', '>', 0.00)->get();
        // dd($cashReceipt);
        $accra = Receipt::whereRelation('client', 'field_id', 1)->where('cash_amount', '>', 0.00)->get();
        $accraTotal = $accra->sum('cash_amount');
        $accraCount = count($accra);

        // dd($accraTotal, $accraCount);

        $botwe = Receipt::whereRelation('client', 'field_id', 2)->where('cash_amount', '>', 0.00)->get();
        $botweTotal = $botwe->sum('total');
        $botweCount = count($botwe);

        $tema = Receipt::whereRelation('client', 'field_id', 3)->where('cash_amount', '>', 0.00)->get();
        $temaTotal = $tema->sum('total');
        $temaCount = count($tema);

        $takoradi = Receipt::whereRelation('client', 'field_id', 4)->where('cash_amount', '>', 0.00)->get();
        $takoradiTotal = $takoradi->sum('total');
        $takoradiCount = count($takoradi);

        $koforidua = Receipt::whereRelation('client', 'field_id', 5)->where('cash_amount', '>', 0.00)->get();
        $koforiduaTotal = $koforidua->sum('total');
        $koforiduaCount = count($koforidua);

        $kumasi = Receipt::whereRelation('client', 'field_id', 6)->where('cash_amount', '>', 0.00)->get();
        $kumasiTotal = $kumasi->sum('total');
        $kumasiCount = count($kumasi);

        $shyhills = Receipt::whereRelation('client', 'field_id', 7)->where('cash_amount', '>', 0.00)->get();
        $shyhillsTotal = $shyhills->sum('total');
        $shyhillsCount = count($shyhills);

        return view('sales.receipt_Cash', compact('cashReceipt', 'accra', 'botwe', 'tema', 'shyhills', 'takoradi', 'koforidua', 'kumasi' ,'accraTotal', 'accraCount', 'botweTotal', 'botweCount', 'temaTotal', 'temaCount', 'shyhillsTotal', 'shyhillsCount', 'takoradiTotal', 'takoradiCount', 'koforiduaTotal', 'koforiduaCount', 'kumasiTotal', 'kumasiCount'));
    }


    public function dashboardTransferPayment()
    {

        $transferReceipt =  Receipt::where('transfer_amount', '>', 0.00)->get();
        // dd($cashReceipt);
        $accra = Receipt::whereRelation('client', 'field_id', 1)->where('transfer_amount', '>', 0.00)->get();
        $accraTotal = $accra->sum('transfer_amount');
        $accraCount = count($accra);

        // dd($accraTotal, $accraCount);

        $botwe = Receipt::whereRelation('client', 'field_id', 2)->where('transfer_amount', '>', 0.00)->get();
        $botweTotal = $botwe->sum('transfer_amount');
        $botweCount = count($botwe);

        $tema = Receipt::whereRelation('client', 'field_id', 3)->where('transfer_amount', '>', 0.00)->get();
        $temaTotal = $tema->sum('transfer_amount');
        $temaCount = count($tema);

        $takoradi = Receipt::whereRelation('client', 'field_id', 4)->where('transfer_amount', '>', 0.00)->get();
        $takoradiTotal = $takoradi->sum('transfer_amount');
        $takoradiCount = count($takoradi);

        $koforidua = Receipt::whereRelation('client', 'field_id', 5)->where('transfer_amount', '>', 0.00)->get();
        $koforiduaTotal = $koforidua->sum('transfer_amount');
        $koforiduaCount = count($koforidua);

        $kumasi = Receipt::whereRelation('client', 'field_id', 6)->where('transfer_amount', '>', 0.00)->get();
        $kumasiTotal = $kumasi->sum('transfer_amount');
        $kumasiCount = count($kumasi);

        $shyhills = Receipt::whereRelation('client', 'field_id', 7)->where('transfer_amount', '>', 0.00)->get();
        $shyhillsTotal = $shyhills->sum('transfer_amount');
        $shyhillsCount = count($shyhills);

        return view('sales.receipt_Transfer', compact('transferReceipt', 'accra', 'botwe', 'tema', 'shyhills', 'takoradi', 'koforidua', 'kumasi', 'accraTotal', 'accraCount', 'botweTotal', 'botweCount', 'temaTotal', 'temaCount', 'shyhillsTotal', 'shyhillsCount', 'takoradiTotal', 'takoradiCount', 'koforiduaTotal', 'koforiduaCount', 'kumasiTotal', 'kumasiCount'));

    }


    public function dashboardChequePayment()
    {
        $chequeReceipt =  Receipt::where('cheque_amount', '>', 0.00)->get();
        // dd($cashReceipt);
        $accra = Receipt::whereRelation('client', 'field_id', 1)->where('cheque_amount', '>', 0.00)->get();
        $accraTotal = $accra->sum('cheque_amount');
        $accraCount = count($accra);

        // dd($accraTotal, $accraCount);

        $botwe = Receipt::whereRelation('client', 'field_id', 2)->where('cheque_amount', '>', 0.00)->get();
        $botweTotal = $botwe->sum('cheque_amount');
        $botweCount = count($botwe);

        $tema = Receipt::whereRelation('client', 'field_id', 3)->where('cheque_amount', '>', 0.00)->get();
        $temaTotal = $tema->sum('cheque_amount');
        $temaCount = count($tema);

        $takoradi = Receipt::whereRelation('client', 'field_id', 4)->where('cheque_amount', '>', 0.00)->get();
        $takoradiTotal = $takoradi->sum('cheque_amount');
        $takoradiCount = count($takoradi);

        $koforidua = Receipt::whereRelation('client', 'field_id', 5)->where('cheque_amount', '>', 0.00)->get();
        $koforiduaTotal = $koforidua->sum('cheque_amount');
        $koforiduaCount = count($koforidua);

        $kumasi = Receipt::whereRelation('client', 'field_id', 6)->where('cheque_amount', '>', 0.00)->get();
        $kumasiTotal = $kumasi->sum('cheque_amount');
        $kumasiCount = count($kumasi);

        $shyhills = Receipt::whereRelation('client', 'field_id', 7)->where('cheque_amount', '>', 0.00)->get();
        $shyhillsTotal = $shyhills->sum('cheque_amount');
        $shyhillsCount = count($shyhills);

        return view('sales.receipt_Cheque', compact('chequeReceipt', 'accra', 'botwe', 'tema', 'shyhills', 'takoradi', 'koforidua', 'kumasi', 'accraTotal', 'accraCount', 'botweTotal', 'botweCount', 'temaTotal', 'temaCount', 'shyhillsTotal', 'shyhillsCount', 'takoradiTotal', 'takoradiCount', 'koforiduaTotal', 'koforiduaCount', 'kumasiTotal', 'kumasiCount'));
    }


    public function dashboardMoMoPayment()
    {
        $momoReceipt =  Receipt::where('momo_amount', '>', 0.00)->get();
        // dd($cashReceipt);
        $accra = Receipt::whereRelation('client', 'field_id', 1)->where('momo_amount', '>', 0.00)->get();
        $accraTotal = $accra->sum('momo_amount');
        $accraCount = count($accra);

        // dd($accraTotal, $accraCount);

        $botwe = Receipt::whereRelation('client', 'field_id', 2)->where('momo_amount', '>', 0.00)->get();
        $botweTotal = $botwe->sum('momo_amount');
        $botweCount = count($botwe);

        $tema = Receipt::whereRelation('client', 'field_id', 3)->where('momo_amount', '>', 0.00)->get();
        $temaTotal = $tema->sum('momo_amount');
        $temaCount = count($tema);

        $takoradi = Receipt::whereRelation('client', 'field_id', 4)->where('momo_amount', '>', 0.00)->get();
        $takoradiTotal = $takoradi->sum('momo_amount');
        $takoradiCount = count($takoradi);

        $koforidua = Receipt::whereRelation('client', 'field_id', 5)->where('momo_amount', '>', 0.00)->get();
        $koforiduaTotal = $koforidua->sum('momo_amount');
        $koforiduaCount = count($koforidua);

        $kumasi = Receipt::whereRelation('client', 'field_id', 6)->where('momo_amount', '>', 0.00)->get();
        $kumasiTotal = $kumasi->sum('momo_amount');
        $kumasiCount = count($kumasi);

        $shyhills = Receipt::whereRelation('client', 'field_id', 7)->where('momo_amount', '>', 0.00)->get();
        $shyhillsTotal = $shyhills->sum('momo_amount');
        $shyhillsCount = count($shyhills);

        

        return view('sales.receipt_MoMo', compact('momoReceipt', 'accra', 'botwe', 'tema', 'shyhills', 'takoradi', 'koforidua', 'kumasi', 'accraTotal', 'accraCount', 'botweTotal', 'botweCount', 'temaTotal', 'temaCount', 'shyhillsTotal', 'shyhillsCount', 'takoradiTotal', 'takoradiCount', 'koforiduaTotal', 'koforiduaCount', 'kumasiTotal', 'kumasiCount'));
    }


    public function dashboardWHTPayment()
    {

        $whtAmountReceipt =  Receipt::where('amount_received', '>', 0.00)->get();
        // dd($cashReceipt);
        $accra = Receipt::whereRelation('client', 'field_id', 1)->where('amount_received', '>', 0.00)->get();
        $accraAmountReceived = $accra->sum('amount_received');
        $accraWHTAmount = $accra->sum('wht_amount');


        // dd($accraTotal, $accraCount);
        $botwe = Receipt::whereRelation('client', 'field_id', 2)->where('amount_received', '>', 0.00)->get();
        $botweAmountReceived = $botwe->sum('amount_received');
        $botweWHTAmount = $botwe->sum('wht_amount');

        $tema = Receipt::whereRelation('client', 'field_id', 3)->where('amount_received', '>', 0.00)->get();
        $temaAmountReceived = $tema->sum('amount_received');
        $temaWHTAmount = $tema->sum('wht_amount');

        $takoradi = Receipt::whereRelation('client', 'field_id', 4)->where('amount_received', '>', 0.00)->get();
        $takoradiAmountReceived = $takoradi->sum('amount_received');
        $takoradiWHTAmount = $takoradi->sum('wht_amount');

        $koforidua = Receipt::whereRelation('client', 'field_id', 5)->where('amount_received', '>', 0.00)->get();
        $koforiduaAmountReceived = $koforidua->sum('amount_received');
        $koforiduaWHTAmount = $koforidua->sum('wht_amount');

        $kumasi = Receipt::whereRelation('client', 'field_id', 6)->where('amount_received', '>', 0.00)->get();
        $kumasiAmountReceived = $kumasi->sum('amount_received');
        $kumasiWHTAmount = $kumasi->sum('wht_amount');

        $shyhills = Receipt::whereRelation('client', 'field_id', 7)->where('amount_received', '>', 0.00)->get();
        $shyhillsAmountReceived = $shyhills->sum('amount_received');
        $shyhillsWHTAmount = $shyhills->sum('wht_amount');

        return view('sales.receipt_whtAmount', compact('whtAmountReceipt', 'accra', 'botwe', 'tema', 'shyhills', 'takoradi', 'koforidua', 'kumasi', 'accraAmountReceived', 'accraWHTAmount', 'botweAmountReceived', 'botweWHTAmount', 'temaAmountReceived', 'temaWHTAmount', 'shyhillsAmountReceived', 'shyhillsWHTAmount', 'takoradiAmountReceived', 'takoradiWHTAmount', 'koforiduaAmountReceived', 'koforiduaWHTAmount', 'kumasiAmountReceived', 'kumasiWHTAmount'));
    }

    // Search for receipts with WHT payments deducted
    public function searchReceiptsWHTPayment(InvoiceToPayrollSearchRequest $request)
    {
         $month = Carbon::parse($request->month);

        // dd($month->month);

         $whtAmountReceipt =  Receipt::where('amount_received', '>', 0.00)->whereMonth('receipt_month', $month->month)->get();
        //  $whtAmountReceipt =  Receipt::where('amount_received', '>', 0.00)->where('receipt_month', $month)->get();
        // dd($whtAmountReceipt);

         $whtAmountReceiptWHTamount = $whtAmountReceipt->sum('wht_amount');
         $whtAmountReceiptAmountReceived = $whtAmountReceipt->sum('amount_received');

        $accra = Receipt::whereRelation('client', 'field_id', 1)->where('amount_received', '>', 0.00)->whereMonth('receipt_month', $month->month)->get();
        $accraAmountReceived = $accra->sum('amount_received');
        $accraWHTAmount = $accra->sum('wht_amount');

        $botwe = Receipt::whereRelation('client', 'field_id', 2)->where('amount_received', '>', 0.00)->whereMonth('receipt_month', $month->month)->get();
        $botweAmountReceived = $botwe->sum('amount_received');
        $botweWHTAmount = $botwe->sum('wht_amount');

        $tema = Receipt::whereRelation('client', 'field_id', 3)->where('amount_received', '>', 0.00)->whereMonth('receipt_month', $month->month)->get();
        $temaAmountReceived = $tema->sum('amount_received');
        $temaWHTAmount = $tema->sum('wht_amount');

        $takoradi = Receipt::whereRelation('client', 'field_id', 4)->where('amount_received', '>', 0.00)->whereMonth('receipt_month', $month->month)->get();
        $takoradiAmountReceived = $takoradi->sum('amount_received');
        $takoradiWHTAmount = $takoradi->sum('wht_amount');

        $koforidua = Receipt::whereRelation('client', 'field_id', 5)->where('amount_received', '>', 0.00)->whereMonth('receipt_month', $month->month)->get();
        $koforiduaAmountReceived = $koforidua->sum('amount_received');
        $koforiduaWHTAmount = $koforidua->sum('wht_amount');    

        $kumasi = Receipt::whereRelation('client', 'field_id', 6)->where('amount_received', '>', 0.00)->whereMonth('receipt_month', $month->month)->get();
        $kumasiAmountReceived = $kumasi->sum('amount_received');
        $kumasiWHTAmount = $kumasi->sum('wht_amount');

        $shyhills = Receipt::whereRelation('client', 'field_id', 7)->where('amount_received', '>', 0.00)->whereMonth('receipt_month', $month->month)->get();
        $shyhillsAmountReceived = $shyhills->sum('amount_received');
        $shyhillsWHTAmount = $shyhills->sum('wht_amount');


        return view('sales.receipt_whtAmount', compact('whtAmountReceipt', 'month','whtAmountReceiptWHTamount', 'accra', 'botwe', 'tema', 'shyhills', 'takoradi', 'koforidua', 'kumasi', 'whtAmountReceiptAmountReceived', 'accraAmountReceived', 'accraWHTAmount', 'botweAmountReceived', 'botweWHTAmount', 'temaAmountReceived', 'temaWHTAmount', 'shyhillsAmountReceived', 'shyhillsWHTAmount', 'takoradiAmountReceived', 'takoradiWHTAmount', 'koforiduaAmountReceived', 'koforiduaWHTAmount', 'kumasiAmountReceived', 'kumasiWHTAmount'));
    }



    // public function dashboardWHTDeducted()
    // {

    //     $whtDeductedReceipt =  Receipt::where('wht_amount', '>', 0.00)->get();
    //     // dd($cashReceipt);
    //     $accra = Receipt::whereRelation('client', 'field_id', 1)->where('wht_amount', '>', 0.00)->get();
    //     $accraTotal = $accra->sum('wht_amount');
    //     $accraCount = count($accra);

    //     // dd($accraTotal, $accraCount);

    //     $botwe = Receipt::whereRelation('client', 'field_id', 2)->where('wht_amount', '>', 0.00)->get();
    //     $botweTotal = $botwe->sum('wht_amount');
    //     $botweCount = count($botwe);

    //     $tema = Receipt::whereRelation('client', 'field_id', 3)->where('wht_amount', '>', 0.00)->get();
    //     $temaTotal = $tema->sum('wht_amount');
    //     $temaCount = count($tema);

    //     $takoradi = Receipt::whereRelation('client', 'field_id', 4)->where('wht_amount', '>', 0.00)->get();
    //     $takoradiTotal = $takoradi->sum('wht_amount');
    //     $takoradiCount = count($takoradi);

    //     $koforidua = Receipt::whereRelation('client', 'field_id', 5)->where('wht_amount', '>', 0.00)->get();
    //     $koforiduaTotal = $koforidua->sum('wht_amount');
    //     $koforiduaCount = count($koforidua);

    //     $kumasi = Receipt::whereRelation('client', 'field_id', 6)->where('wht_amount', '>', 0.00)->get();
    //     $kumasiTotal = $kumasi->sum('wht_amount');
    //     $kumasiCount = count($kumasi);

    //     return view('sales.receipt_whtDeducted', compact('whtDeductedReceipt', 'accra', 'botwe', 'tema', 'takoradi', 'koforidua', 'kumasi', 'accraTotal', 'accraCount', 'botweTotal', 'botweCount', 'temaTotal', 'temaCount', 'takoradiTotal', 'takoradiCount', 'koforiduaTotal', 'koforiduaCount', 'kumasiTotal', 'kumasiCount'));
    // }

    public function PendingReceipts()
    {
        // dd($pendingReceipts);

        // return view('sales.receipt_pending', compact('pendingReceipts'));   

        $user = Auth::user();

        if($user->role->name == 'Finance Manager' || $user->role->name == 'Invoice' )
        {
            // $receipts = Receipt::all();
            $accra = null;
            $botwe = null;
            $tema = null;
            $shaihills = null;
            $takoradi = null;
            $koforidua = null;
            $kumasi = null;

            $receiptsAccra = Receipt::whereRelation('client', 'field_id', '1')->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
            $accraR = [];

            foreach($receiptsAccra as $accra)
                {
                    if($accra->client->field_id == '1')
                    {
                        $accraR[] = $accra;
                    }
                }
            $accra = collect($accraR);

            $botweReceipt = Receipt::whereRelation('client', 'field_id', '2')->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
            $botweR = [];
            foreach($botweReceipt as $botwe)
                {
                    if($botwe->client->field_id == '2')
                    {
                        $botweR[] = $botwe;
                    }
                }
            $botwe = collect($botweR);
            
            $temaReceipt = Receipt::whereRelation('client', 'field_id', '3')->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();

            $temaR = [];
            foreach($temaReceipt as $tema)
                {
                    if($tema->client->field_id == '3')
                    {
                        $temaR[] = $tema;
                    }
                }
            $tema = collect($temaR);

            $shaihillsReceipt = Receipt::whereRelation('client', 'field_id', '7')->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
               
            $shaihillsR = [];
            foreach($shaihillsReceipt as $shaihills)
                {
                    if($shaihills->client->field_id == '7')
                    {
                        $shaihillsR[] = $shaihills;
                    }
                }
            $shaihills = collect($shaihillsR);

            $takoradiReceipt = Receipt::whereRelation('client', 'field_id', '4')->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
            $takoradiR = [];
            foreach($takoradiReceipt as $takoradi)
                {
                    if($takoradi->client->field_id == '4')
                    {
                        $takoradiR[] = $takoradi;
                    }
                }
            $takoradi = collect($takoradiR);
            
            $koforiduaReceipt = Receipt::whereRelation('client', 'field_id', '5')->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
            $koforiduaR = [];
            foreach($koforiduaReceipt as $koforidua)
                {
                    if($koforidua->client->field_id == '5')
                    {
                        $koforiduaR[] = $koforidua;
                    }
                }
            $koforidua = collect($koforiduaR);

            $kumasiReceipt = Receipt::whereRelation('client', 'field_id', '6')->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
            $kumasiR = [];
            foreach($kumasiReceipt as $kumasi)
                {
                    if($kumasi->client->field_id == '6')
                    {
                        $kumasiR[] = $kumasi;
                    }
                }
            $kumasi = collect($kumasiR);

            $receipt = Receipt::where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
            $receipts = collect($receipt);
            return view('sales.receipt_pending', compact('receipts', 'accra', 'botwe', 'tema', 'shaihills', 'takoradi','koforidua', 'kumasi'));
        }

        if($user->field?->name == 'Accra' )
        {
            $receiptsAccra = Receipt::whereRelation('client', 'field_id', '1')->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
        
            $receipt = [];

            foreach($receiptsAccra as $accra)
                {
                    if($accra->client->field_id == '1')
                    {
                        $receipt[] = $accra;
                    }
                }
            $receipts = collect($receipt);
            
            return view('sales.receipt_pending', compact('receipts'));

        }

        if ($user->field?->name == 'Botwe')
        {
            $receiptsBotwe = Receipt::whereRelation('client', 'field_id', '2')->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
        
            $receipt = [];

            foreach($receiptsBotwe as $botwe)
                {
                    if($botwe->client->field_id == '2')
                    {
                        $receipt[] = $botwe;
                    }
                }
            $receipts = collect($receipt);
            
            return view('sales.receipt_pending', compact('receipts'));

        }

        if ($user->field?->name == 'Tema')
        {
            $receipt = [];
            $receiptsTema = Receipt::whereRelation('client', 'field_id', '3')->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
            //  $receipts1 =  collect($receiptsTema) ;
            foreach($receiptsTema as $tema)
                {
                    if($tema->client->field_id == '3')
                    {
                        $receipt[] = $tema;
                    }
                }

            // dd($receipt);
            
            $receiptsShaihills = Receipt::whereRelation('client', 'field_id', '7')->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
            // $receipts2 = collect($receiptsShaihills) ;
            // $receipt = $receiptsTema->concat($receiptsShaihills);
            // dd($receipt);

            foreach($receiptsShaihills as $shai)
                {
                    if($shai->client->field_id == '7')
                    {
                        $receipt[] = $shai;
                    }
                }
            $receipts = collect($receipt);
            // dd($receipts);

            return view('sales.receipt_pending', compact('receipts'));
            

        }

        if ($user->field?->name == 'Takoradi')
        {
            $receiptsTakoradi = Receipt::whereRelation('client', 'field_id', '4')->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
        
            $receipt = [];

            foreach($receiptsTakoradi as $takoradi)
                {
                    if($takoradi->client->field_id == '4')
                    {
                        $receipt[] = $takoradi;
                    }
                }
            $receipts = collect($receipt);
            
            return view('sales.receipt_pending', compact('receipts'));

        }

        if ($user->field?->name == 'Koforidua')
        {
            $receiptsKoforidua = Receipt::whereRelation('client', 'field_id', '5')->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
        
            $receipt = [];

            foreach($receiptsKoforidua as $koforidua)
                {
                    if($koforidua->client->field_id == '5')
                    {
                        $receipt[] = $koforidua;
                    }
                }
            $receipts = collect($receipt);
            
            return view('sales.receipt_pending', compact('receipts'));

        }

        if ($user->field?->name == 'Kumasi')
        {
            $receiptsKumasi = Receipt::whereRelation('client', 'field_id', '6')->where('ho_status', '!=' , 'approved')->orwhere('ho_status', null)->get();
       
            $receipt = [];

            foreach($receiptsKumasi as $kumasi)
                {
                    if($kumasi->client->field_id == '6')
                    {
                        $receipt[] = $kumasi;
                    }
                }
            $receipts = collect($receipt);
            
            return view('sales.receipt_pending', compact('receipts'));
        }

        // return view('sales.receipt_pending', compact('receipts'));

    }

    public function receiptChannels(Request $request)
    {
        // dd($request->all());
        $action = $request->input('action_type') ?? $request->input('submit') ?? $request->input('decline');
        // dd($submitValue);
         $receiptsIDS = $request->input('receipts', []);
        //  dd($receiptsIDS);
        if (empty($receiptsIDS)) {
            return back()->with('error', 'No Receipt selected !');
        }
        
         $receipts =  Receipt::findOrFail($request->receipts);
        //  dd($receipts);
        $alreadyProcessed = [];

        foreach( $receipts as $receipt)
        {


            if( $action == 'branch' )
                
                {
                    // dd($receipt);
                    $exists = Receipt::where('id', $receipt->id)
                        ->where('bran_status', 'approved')
                        ->exists();
                    if ($exists) {
                        $alreadyProcessed[] =  " Receipt: " . $receipt->id;
                        continue;
                    }
                    // update branch to approved, collection to approved and dates.
                    $receipt->bran_status = 'approved';
                    $receipt->user_id1 = Auth::id();
                    $receipt->bran_date = now();

                    $receipt->coll_status = 'approved';
                    $receipt->ho_status = 'pending';

                    $receipt->save();
                    // return 'you are a Branch Manager';

                }

            if($action == 'headOffice' )
                {
                    // // dd($receipt);
                    $exists = Receipt::where('id', $receipt->id)
                        ->where('ho_status', 'approved')
                        ->exists();
                    if ($exists) {
                        $alreadyProcessed[] =  " Receipt: " . $receipt->id;
                        continue;
                    }
                    // update branch to approved, collection to approved and dates.
                    // $receipt->bran_status = 'approved';
                    // $receipt->user_id2 = Auth::id();
                    // $receipt->bran_date = now();

                    // $receipt->coll_status = 'approved';
                    $receipt->ho_status = 'approved';
                    $receipt->user_id2 = Auth::id();
                    $receipt->ho_date = now();

                    $receipt->save();

                    // return 'you are at the head office';
                    
                }


            if($action == 'decline' )
                {
                    //                     // dd($receipt);
                    // $exists = Receipt::where('id', $receipt->id)
                    //     ->where('bran_status', 'approved')
                    //     ->exists();
                    // if ($exists) {
                    //     $alreadyProcessed[] =  " Receipt: " . $receipt->id;
                    //     continue;
                    // }
                    // update branch to approved, collection to approved and dates.
                    $receipt->bran_status = 'pending';
                    // $receipt->user_id2 = Auth::id();
                    $receipt->coll_status = 'pending';
                    $receipt->coll_date = now();


                    $receipt->save();
                    // return 'you are at the head office Declining'; 

                }



        }
    


            return back()->with('primary', 'Receipts with the IDs have already been processed: '. implode(', ', $alreadyProcessed). ', - The remaining have been Processed ')  ;
    



    }

}
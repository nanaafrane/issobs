<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Requests\InvoiceToPayrollSearchRequest;
use App\Models\Client;
use App\Models\Field;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\Vat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoiceController extends Controller
{

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
     * Invoices reporting: period windows with breakdowns by field, status,
     * top clients and top issuers, plus trend data for charts.
     */
    public function report(Request $request)
    {
        $period = $request->input('period', 'monthly');
        $anchor = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();

        [$from, $to] = match ($period) {
            'daily' => [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()],
            'weekly' => [$anchor->copy()->startOfWeek(), $anchor->copy()->endOfWeek()],
            'yearly' => [$anchor->copy()->startOfYear(), $anchor->copy()->endOfYear()],
            default => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
        };

        $base = Invoice::whereBetween('invoice_month', [$from, $to]);

        $total = (clone $base)->sum('total');
        $count = (clone $base)->count();

        $byField = (clone $base)->join('clients', 'clients.id', '=', 'invoices.client_id')
            ->join('fields', 'fields.id', '=', 'clients.field_id')
            ->select('fields.name as field_name', DB::raw('SUM(invoices.total) as total'), DB::raw('COUNT(invoices.id) as cnt'))
            ->groupBy('fields.name')->orderByDesc('total')->get();

        $byStatus = (clone $base)->select('status', DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')->get();

        $topClients = (clone $base)->join('clients', 'clients.id', '=', 'invoices.client_id')
            ->select('clients.id', 'clients.business_name', 'clients.name', DB::raw('SUM(invoices.total) as total'), DB::raw('COUNT(invoices.id) as cnt'))
            ->groupBy('clients.id', 'clients.business_name', 'clients.name')
            ->orderByDesc('total')->limit(10)->get();

        $topIssuers = (clone $base)->join('users', 'users.id', '=', 'invoices.user_id')
            ->select('users.id', 'users.name', DB::raw('SUM(invoices.total) as total'), DB::raw('COUNT(invoices.id) as cnt'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')->limit(10)->get();

        $trend = (clone $base)->select('invoice_month', DB::raw('SUM(total) as total'))
            ->groupBy('invoice_month')->orderBy('invoice_month')->get();

        $projection = $this->projectNextPeriodInvoices($period, $anchor);

        return view('sales.invoice_report', compact(
            'period', 'anchor', 'from', 'to', 'total', 'count',
            'byField', 'byStatus', 'topClients', 'topIssuers', 'trend', 'projection'
        ));
    }

    protected function projectNextPeriodInvoices(string $period, Carbon $anchor): float
    {
        $samples = [];
        for ($i = 1; $i <= 4; $i++) {
            [$from, $to] = match ($period) {
                'daily' => [$anchor->copy()->subDays($i)->startOfDay(), $anchor->copy()->subDays($i)->endOfDay()],
                'weekly' => [$anchor->copy()->subWeeks($i)->startOfWeek(), $anchor->copy()->subWeeks($i)->endOfWeek()],
                'yearly' => [$anchor->copy()->subYears($i)->startOfYear(), $anchor->copy()->subYears($i)->endOfYear()],
                default => [$anchor->copy()->subMonths($i)->startOfMonth(), $anchor->copy()->subMonths($i)->endOfMonth()],
            };
            $samples[] = Invoice::whereBetween('invoice_month', [$from, $to])->sum('total');
        }

        return $samples ? round(array_sum($samples) / count($samples), 2) : 0.0;
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('sales.invoice_list');
    }

    public function datatable(Request $request)
    {
        $query = Invoice::query()
            ->select([
                'invoices.id',
                'invoices.client_id',
                'invoices.user_id',
                'invoices.due_date',
                'invoices.invoice_month',
                'invoices.created_at',
                'invoices.total',
                'invoices.status',
                'clients.business_name as client_business_name',
                'clients.name as client_name',
                'clients.phone_number',
                'fields.name as field_name',
                'users.name as staff_name',
            ])
            ->leftJoin('clients', 'clients.id', '=', 'invoices.client_id')
            ->leftJoin('fields', 'fields.id', '=', 'clients.field_id')
            ->leftJoin('users', 'users.id', '=', 'invoices.user_id');

        $search = trim((string) $request->input('search.value', ''));
        $columns = $request->input('columns', []);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('invoices.id', 'like', "%{$search}%")
                    ->orWhere('invoices.status', 'like', "%{$search}%")
                    ->orWhere('clients.business_name', 'like', "%{$search}%")
                    ->orWhere('clients.name', 'like', "%{$search}%")
                    ->orWhere('clients.phone_number', 'like', "%{$search}%")
                    ->orWhere('fields.name', 'like', "%{$search}%")
                    ->orWhere('users.name', 'like', "%{$search}%")
                    ->orWhereRaw("DATE_FORMAT(invoices.invoice_month, '%M, %Y') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("DATE_FORMAT(invoices.created_at, '%M %d, %Y') LIKE ?", ["%{$search}%"]);
            });
        }

        foreach ($columns as $index => $column) {
            $value = trim((string) ($column['search']['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            switch ($index) {
                case 2:
                    $query->whereRaw("DATE_FORMAT(invoices.invoice_month, '%M, %Y') LIKE ?", ["%{$value}%"]);
                    break;
                case 3:
                    $query->where(function ($q) use ($value) {
                        $q->where('clients.business_name', 'like', "%{$value}%")
                            ->orWhere('clients.name', 'like', "%{$value}%");
                    });
                    break;
                case 4:
                    $query->where('clients.phone_number', 'like', "%{$value}%");
                    break;
                case 5:
                    $query->where('fields.name', 'like', "%{$value}%");
                    break;
                case 6:
                    $query->where('users.name', 'like', "%{$value}%");
                    break;
                case 7:
                    $query->whereRaw("DATE_FORMAT(invoices.created_at, '%M %d, %Y') LIKE ?", ["%{$value}%"]);
                    break;
                case 8:
                    $query->whereRaw("DATE_FORMAT(invoices.due_date, '%M %d, %Y') LIKE ?", ["%{$value}%"]);
                    break;
                case 10:
                    $query->where('invoices.status', 'like', "%{$value}%");
                    break;
                default:
                    $query->where('invoices.id', 'like', "%{$value}%");
                    break;
            }
        }

        $recordsTotal = Invoice::count();
        $recordsFiltered = (clone $query)->count();

        $columnMap = [
            0 => 'invoices.id',
            1 => 'invoices.id',
            2 => 'invoices.invoice_month',
            3 => 'client_business_name',
            4 => 'clients.phone_number',
            5 => 'field_name',
            6 => 'staff_name',
            7 => 'invoices.created_at',
            8 => 'invoices.due_date',
            9 => 'invoices.total',
            10 => 'invoices.status',
        ];

        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');
        $sortColumn = $columnMap[$orderColumn] ?? 'invoices.created_at';

        if (in_array($sortColumn, ['client_business_name', 'field_name', 'staff_name'], true)) {
            $query->orderByRaw($sortColumn . ' ' . $orderDir);
        } else {
            $query->orderBy($sortColumn, $orderDir);
        }

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        $length = $length > 0 ? $length : 25;

        $rows = $query->offset($start)->limit($length)->get();

        $data = $rows->map(function ($invoice) {
            $clientName = trim((string) ($invoice->client_business_name ?: $invoice->client_name));
            $badgeClass = $invoice->status === 'completed' ? 'bg-label-success' : 'bg-label-danger';
            $dueLabel = $invoice->due_date ? Carbon::parse($invoice->due_date)->diffForHumans() : '';

            return [
                'row_number' => '',
                'invoice_id' => $invoice->id,
                'invoice_month' => $invoice->invoice_month ? Carbon::parse($invoice->invoice_month)->format('F, Y') : '',
                'client_name' => $clientName,
                'phone_number' => $invoice->phone_number,
                'field_name' => $invoice->field_name,
                'staff_name' => $invoice->staff_name,
                'created_at' => $invoice->created_at ? $invoice->created_at->format('F l d, Y, H:i A') : '',
                'due_date' => $dueLabel,
                'amount' => 'GH₵ ' . number_format((float) $invoice->total, 2),
                'status' => '<span class="badge ' . $badgeClass . '">' . e($invoice->status) . '</span>',
                'action' => view('partials.invoice_row_actions', ['invoice' => $invoice])->render(),
            ];
        })->all();

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function printInvoice($invoice_id)
    {
        $invoice = Invoice::findOrFail($invoice_id);
        $invoice_data = DB::table('invoice_data')->where('invoice_id', $invoice_id)->orderBy('id', 'desc')->get();
        return view('sales.print', compact('invoice', 'invoice_data'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $clients = Client::all();
        $fields = Field::all();
        $services = Service::all();
        return view('sales.invoice_create', compact('clients', 'fields', 'services'));

    }

    public function duplicate(Invoice $invoice)
    {
        $services = Service::all();
        $invoice_data = DB::table('invoice_data')->where('invoice_id', $invoice->id)->get();
        $clients = Client::all();

        return view('sales.invoice_duplicate', compact('invoice', 'invoice_data', 'services', 'clients'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInvoiceRequest $request)
    {
        //
        // dd($request->all());
        //    $user = Auth::user();
           $invoice = new Invoice();
           $invoice->client_id = $request->input('client_id');
           $invoice->due_date = $request->input('due_date');
           $invoice->invoice_month = $request->input('invoice_month');
           $vat_standard = $request->input('vat_standard');
           $invoice->status = 'unpaid';

           $amount = $request->input('amount');
           $sum_amount_from_invoice = array_sum($amount);

           $invoice->sub_amount = $sum_amount_from_invoice;
           $invoice->user_id = Auth::user()->id;
           $invoice->save();

           $invoice_id =  $invoice->id;

            $service = $request->input('service');
            $description   = $request->input('description');
            $quantity   = $request->input('quantity');
            $quantity_count = count($quantity);
            $unit_price = $request->input('unit_price');

            $nhilAmount = null;
            $getfundAmount = null;
            // $chrlAmount = null;
            $sub_total_without_vat = null;
            $vatAmount = null;
            $total = $sum_amount_from_invoice;
            // dd($vat_standard);

        // dd($invoice_id, $description, $quantity, $unit_price, $amount, $quantity_count);
        if($quantity_count > 0)
        {
            for($i=0; $i<$quantity_count; $i++)
            {
                DB::table('invoice_data')->insert([
                'invoice_id' => $invoice_id,
                'service_name' => $service[$i],
                'description' => $description[$i],
                'quantity' => $quantity[$i],
                'unit_price' => $unit_price[$i],
                'amount' => $amount[$i],
                ]);
            }

            if($vat_standard == 'on'){
            // Call a vat class to get the standard vat values
            $vat =  new Vat();
            $nhilAmount = $vat->getNhilAmount($sum_amount_from_invoice);
            $getfundAmount = $vat->getGetFundAmount($sum_amount_from_invoice);
            $vatAmount = $vat->getVatAmount($sum_amount_from_invoice);

            // for standard vat calculations, first sum the amounts of invoice, then add the percentage values for nhil,getfund
            // and chrl respectively.

            $total = $sum_amount_from_invoice + $nhilAmount + $getfundAmount + $vatAmount;

            //   after summing get 15%vat of that value to get the 15% amount of that value.
            // $vatAmount = $vat->getVatAmount($sub_total_without_vat);

            // now add the value + the 15%vat amount to give the total of the invoice.
            // $total = $sub_total_without_vat + $vatAmount;
            }

            $update_invoice = Invoice::findOrFail($invoice_id);
            $update_invoice->nhil = $nhilAmount;
            $update_invoice->getfund = $getfundAmount;
            // $update_invoice->chrl = $chrlAmount;
            $update_invoice->sub_total = $sub_total_without_vat;
            $update_invoice->vat_amount = $vatAmount;
            $update_invoice->total = $total;
            $update_invoice->save();


            $transaction = new Transaction();
            $transaction->client_id = $request->input('client_id');
            $transaction->invoice_id = $invoice_id;
            $transaction->invoice_amount = $total;
            $transaction->status = 'unpaid';
            $transaction->save();


            // // select all transactions with this current invoice iD and assign value D to the checks culumn
            // Transaction::where('invoice_id', $invoice_id)->update(['checks' => 'd']);

            return redirect()->route('invoice.show',['invoice' => $invoice_id])->with('primary', 'Invoice Generated Successfully');

        }
        //    dd($invoice_id);
    }

    /**
     * Display the specified resource.
     */
    public function show( Invoice $invoice)
    {
        //
        // dd($invoice);
        $invoice_data = DB::table('invoice_data')->where('invoice_id', $invoice->id)->orderBy('id', 'DESC')->get();
        return view('sales.invoice_show', compact('invoice', 'invoice_data'));

    }


    public function generate($client_id)
    {
        $services = Service::all();
        $client = Client::findOrFail($client_id);
        // $clients = Client::all();
        return view('sales.invoice_generate', compact('client','services'));
    }

    /**
     * Search the invoices per month.
     */
    public function invoiceSearch(InvoiceToPayrollSearchRequest $request) 

    {
        $month = Carbon::parse($request->month)->format('Y-m-d');

        // $reportInvoices = Invoice::with('invoice_data')->where('invoice_month', $month)->get();
        // dd($reportInvoices);

        // dd($month);
        $reportInvoices = Invoice::where('invoice_month', $month)->get();
        $invoiceTotal = $reportInvoices->sum('total');
        $invoiceCount = $reportInvoices->count();
        $reportInvoicesGuards = $this->totalInvoiceGuards($reportInvoices);


        $accra = Invoice::whereRelation('client', 'field_id', 1)->where('invoice_month', $month)->get();
        $accraTotal = $accra->sum('total');
        $accraCount = count($accra);
        $accraGuards = $this->totalInvoiceGuards($accra);
        

        $botwe = Invoice::whereRelation('client', 'field_id', 2)->where('invoice_month', $month)->get();
        $botweTotal = $botwe->sum('total');
        $botweCount = count($botwe);
        $botweGuards = $this->totalInvoiceGuards($botwe);

        $tema = Invoice::whereRelation('client', 'field_id', 3)->where('invoice_month', $month)->get();
        $temaTotal = $tema->sum('total');
        $temaCount = count($tema);

        $temaGuards = $this->totalInvoiceGuards($tema);


        $takoradi = Invoice::whereRelation('client', 'field_id', 4)->where('invoice_month', $month)->get();
        $takoradiTotal = $takoradi->sum('total');
        $takoradiCount = count($takoradi);
        $takoradiGuards = $this->totalInvoiceGuards($takoradi);


        $koforidua = Invoice::whereRelation('client', 'field_id', 5)->where('invoice_month', $month)->get();
        $koforiduaTotal = $koforidua->sum('total');
        $koforiduaCount = count($koforidua);
        $koforiduaGuards = $this->totalInvoiceGuards($koforidua);

        $kumasi = Invoice::whereRelation('client', 'field_id', 6)->where('invoice_month', $month)->get();
        $kumasiTotal = $kumasi->sum('total');
        $kumasiCount = count($kumasi);
        $kumasiGuards = $this->totalInvoiceGuards($kumasi);


        $shyhills = Invoice::whereRelation('client', 'field_id', 7)->where('invoice_month', $month)->get();
        $shyhillsTotal = $shyhills->sum('total');
        $shyhillsCount = count($shyhills);
        $temashai = $tema->concat($shyhills);
        $shyhillsGuards = $this->totalInvoiceGuards($shyhills);


        // dd($invoicemonth);
        return view('sales.invoice_dashboard_month', compact('temashai', 'reportInvoicesGuards', 'accraGuards', 'botwe','botweGuards', 'temaGuards', 'takoradiGuards', 'koforiduaGuards', 'kumasiGuards', 'shyhillsGuards', 'invoiceTotal', 'month','invoiceCount', 'reportInvoices', 'accra','accraTotal', 'accraCount', 'botweTotal', 'botweCount', 'tema','temaTotal', 'temaCount', 'takoradi','takoradiTotal', 'takoradiCount', 'koforidua','koforiduaTotal', 'koforiduaCount', 'kumasi','kumasiTotal', 'kumasiCount', 'shyhillsTotal', 'shyhillsCount'));
         
    }   


        /**
     * Function to take invoices and return the total number of guards for that invoice
     */

        public function totalInvoiceGuards($invoices)
        {
            $guards = [];

            foreach($invoices as $invoice)
            {
                foreach($invoice->invoice_data as $data)
                    {
                        $guards[] =  $data->quantity;
                        //   $data->quantity . "<br>";
                    }
            }
        return collect($guards)->sum() ;

        }

        protected function groupInvoicesByAging($invoices)
        {
            $groupedInvoices = collect($invoices)->groupBy(function ($invoice) {
                $dueDate = $invoice->due_date ? Carbon::parse($invoice->due_date) : Carbon::now();
                $now = Carbon::now();
                $diffInDays = $dueDate->diffInDays($now);

                if ($diffInDays <= 30) {
                    return '0-30 days';
                } elseif ($diffInDays <= 60) {
                    return '31-60 days';
                } elseif ($diffInDays <= 90) {
                    return '61-90 days';
                }

                return '90+ days';
            });

            $agingBuckets = collect([
                '0-30 days' => collect(),
                '31-60 days' => collect(),
                '61-90 days' => collect(),
                '90+ days' => collect(),
            ]);

            foreach ($agingBuckets->keys() as $bucket) {
                $agingBuckets[$bucket] = $groupedInvoices->has($bucket) ? $groupedInvoices->get($bucket) : collect();
            }

            return $agingBuckets;
        }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice)
    {
        //
        // dd($invoice);
        $services = Service::all();
        $invoice_data = DB::table('invoice_data')->where('invoice_id', $invoice->id)->get();
        $clients = Client::all();
        return view('sales.invoice_edit', compact('invoice', 'invoice_data' ,'services', 'clients'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function storeDuplicate(StoreInvoiceRequest $request, Invoice $sourceInvoice)
    {
        $invoice = new Invoice();
        $invoice->client_id = $request->input('client_id');
        $invoice->due_date = $request->input('due_date');
        $invoice->invoice_month = Carbon::parse($request->input('invoice_month'))->format('Y-m-d');
        $vat_standard = $request->input('vat_standard');
        $vat_standard_21 = $request->input('vat_standard_21');
        $invoice->status = 'unpaid';

        $amount = $request->input('amount', []);
        $sum_amount_from_invoice = array_sum(array_map('floatval', $amount));

        $invoice->sub_amount = $sum_amount_from_invoice;
        $invoice->user_id = Auth::user()->id;
        $invoice->save();

        $invoice_id = $invoice->id;

        $service = $request->input('service', []);
        $description = $request->input('description', []);
        $quantity = $request->input('quantity', []);
        $unit_price = $request->input('unit_price', []);

        $nhilAmount = null;
        $getfundAmount = null;
        $chrlAmount = null;
        $sub_total_without_vat = null;
        $vatAmount = null;
        $total = $sum_amount_from_invoice;

        $quantity_count = count($quantity);

        if ($quantity_count > 0) {
            for ($i = 0; $i < $quantity_count; $i++) {
                DB::table('invoice_data')->insert([
                    'invoice_id' => $invoice_id,
                    'service_name' => $service[$i],
                    'description' => $description[$i],
                    'quantity' => $quantity[$i],
                    'unit_price' => $unit_price[$i],
                    'amount' => $amount[$i],
                ]);
            }
        }

        if ($vat_standard == 'on') {
            $vat = new Vat();
            $nhilAmount = $vat->getNhilAmount($sum_amount_from_invoice);
            $getfundAmount = $vat->getGetFundAmount($sum_amount_from_invoice);
            $vatAmount = $vat->getVatAmount($sum_amount_from_invoice);
            $total = $sum_amount_from_invoice + $nhilAmount + $getfundAmount + $vatAmount;
        } elseif ($vat_standard_21 == 'on') {
            $vat = new Vat();
            $nhilAmount = $vat->getNhilAmount($sum_amount_from_invoice);
            $getfundAmount = $vat->getGetFundAmount($sum_amount_from_invoice);
            $chrlAmount = $vat->getChrlAmount($sum_amount_from_invoice);

            $sub_total_without_vat = $sum_amount_from_invoice + $nhilAmount + $getfundAmount + $chrlAmount;
            $vatAmount = $vat->getVatAmount($sub_total_without_vat);
            $total = $sub_total_without_vat + $vatAmount;
        }

        $invoice->nhil = $nhilAmount;
        $invoice->getfund = $getfundAmount;
        $invoice->chrl = $chrlAmount;
        $invoice->sub_total = $sub_total_without_vat;
        $invoice->vat_amount = $vatAmount;
        $invoice->total = $total;
        $invoice->save();

        $transaction = new Transaction();
        $transaction->client_id = $request->input('client_id');
        $transaction->invoice_id = $invoice_id;
        $transaction->invoice_amount = $total;
        $transaction->status = 'unpaid';
        $transaction->save();

        return redirect()->route('invoice.show', ['invoice' => $invoice_id])->with('primary', 'Invoice Duplicated Successfully');
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        //
        // dd($invoice, $request->input('invoice_month'))->format('Y-m-d');
        $service_name = $request->input('service');
        $client_id = $request->input('client_id');
        $due_date = $request->input('due_date');
        $invoice_month = Carbon::parse($request->input('invoice_month'))->format('Y-m-d');
        // dd($invoice_month);

        $description   = $request->input('description');
        $quantity   = $request->input('quantity');
        $quantity_count = count($quantity);
        $unit_price = $request->input('unit_price');
        $amount = $request->input('amount');
        $sum_amount_from_invoice = array_sum($amount);
        $vat_standard = $request->input('vat_standard');
        $vat_standard_21 = $request->input('vat_standard_21');

        $nhilAmount = null;
        $getfundAmount = null;
        $chrlAmount = null;
        $sub_total_without_vat = null;
        $vatAmount = null;
        $total = $sum_amount_from_invoice;

        if($vat_standard == 'on'){
            // echo "You are working with 20%";
        // // dd($description, $quantity, $quantity_count, $unit_price, $amount, $sum_amount_from_invoice);
        $vat =  new Vat();
        $nhilAmount = $vat->getNhilAmount($sum_amount_from_invoice);
        $getfundAmount = $vat->getGetFundAmount($sum_amount_from_invoice);
        $vatAmount = $vat->getVatAmount($sum_amount_from_invoice);

        $total = $sum_amount_from_invoice + $nhilAmount + $getfundAmount + $vatAmount;
       
        }elseif($vat_standard_21 == 'on')
        {
            // echo "You are working with 21%";
            $vat =  new Vat();
            $nhilAmount = $vat->getNhilAmount($sum_amount_from_invoice);
            $getfundAmount = $vat->getGetFundAmount($sum_amount_from_invoice);
            $chrlAmount = $vat->getChrlAmount($sum_amount_from_invoice);

            $sub_total_without_vat = $sum_amount_from_invoice + $nhilAmount + $getfundAmount + $chrlAmount ;

            $vatAmount = $vat->getVatAmount($sub_total_without_vat);

            $total = $sub_total_without_vat + $vatAmount;

        }

        // dd($description, $service_name, $quantity, $quantity_count, $unit_price, $amount, $sum_amount_from_invoice, $nhilAmount, $getfundAmount, $chrlAmount, $sub_total_without_vat, $vatAmount, $total);
        if ($quantity_count > 0)
        {
            DB::table('invoice_data')->where('invoice_id', $invoice->id)->delete();
            for($i = 0; $i<$quantity_count; $i++) {

                DB::table('invoice_data')->upsert(
                    [
                        'invoice_id'=> $invoice->id, 'service_name'=> $service_name[$i], 'description' => $description[$i], 'quantity' => $quantity[$i], 'unit_price' => $unit_price[$i], 'amount' => $amount[$i],
                    ],

                    ['invoice_id'],

                    ['description','quantity', 'unit_price', 'amount']

            );
                // dd($i );
                // dd($description[$i], $quantity[$i], $quantity_count, $unit_price[$i], $amount[$i], $sum_amount_from_invoice);

            }
        }

        Invoice::where('id', $invoice->id)->update([
            'client_id' => $client_id,
            'nhil' => $nhilAmount,
            'getfund' => $getfundAmount,
            'chrl' => $chrlAmount,
            'sub_amount' => $sum_amount_from_invoice,
            'vat_amount' => $vatAmount,
            'sub_total' => $sub_total_without_vat,
            'total' => $total,
            'due_date' => $due_date,
            'invoice_month' => $invoice_month,
            'user_id1' => Auth::user()->id,
        ]);

        Transaction::where('invoice_id', $invoice->id)->update([
            'invoice_amount' => $total,
        ]);

        return redirect()->route('invoice.show',['invoice' => $invoice])->with('primary', 'Invoice  Updated Successfully');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        //
        // Invoice::transaction()->where('invoice_id', $invoice->id)->destroy();

          Invoice::destroy($invoice->id);
          Transaction::where('invoice_id', $invoice->id)->delete();
          DB::table('invoice_data')->where('invoice_id', $invoice->id)->delete();

        return redirect('invoice')->with('error', 'Invoices Deleted Successfully');
    }

    public function dashboardViewAllInvoices()
    {
        // $reportInvoices =  Invoice::with('invoice_data')->get();
        
        // $accra = Invoice::whereRelation('client', 'field_id', 1)->get();
        // $accraTotal = $accra->sum('total');
        // $accraCount = count($accra);

        // $botwe = Invoice::whereRelation('client', 'field_id', 2)->get();
        // $botweTotal = $botwe->sum('total');
        // $botweCount = count($botwe);

        // $tema = Invoice::whereRelation('client', 'field_id', 3)->get();
        // $temaTotal = $tema->sum('total');
        // $temaCount = count($tema);

        // $takoradi = Invoice::whereRelation('client', 'field_id', 4)->get();
        // $takoradiTotal = $takoradi->sum('total');
        // $takoradiCount = count($takoradi);

        // $koforidua = Invoice::whereRelation('client', 'field_id', 5)->get();
        // $koforiduaTotal = $koforidua->sum('total');
        // $koforiduaCount = count($koforidua);

        // $kumasi = Invoice::whereRelation('client', 'field_id', 6)->get();
        // $kumasiTotal = $kumasi->sum('total');
        // $kumasiCount = count($kumasi);

        // $shyhills = Invoice::whereRelation('client', 'field_id', 7)->get();
        // $shyhillsTotal = $shyhills->sum('total');
        // $shyhillsCount = count($shyhills);

        // dd($reportInvoices);
       return view('sales.invoice_dashboard');
    }

    // Display invoices with part payment outstanding

    public function dashboardInvoiceWithOutstanding()
    {

        $reportInvoices =  Invoice::whereIn('status', ['unpaid', 'uncompleted'])->get();
        // dd($reportInvoices);
        $reportPinvoices = Invoice::where('balance', '>', 0.00)->where('status', 'uncompleted')->get();
        

        // Get invoices Group each invoice by aging periods (0-30 days, 31-60 days, 61-90 days, 90+ days) 

        $reportInvoicesAging = $this->groupInvoicesByAging(
            Invoice::whereIn('status', ['unpaid', 'uncompleted'])->get()
        );

        $accra = Invoice::whereRelation('client', 'field_id', 1)->whereIn('status', ['unpaid', 'uncompleted'])->get();
        $accraAging = $this->groupInvoicesByAging($accra);
        // dd($accraAging);
        // foreach($accraAging['0-30 days'] as $key => $invoice)
        //     {
        //         echo $key +1 . " ". $invoice . "<br>".  "<br>";
        //     }
        
        $accraPcount = Invoice::whereRelation('client', 'field_id', 1)->where('balance', '>', 0.00)->where('status', 'uncompleted')->get();

        $accraTotal = $accra->sum('total');
        $accraCount = count($accra);

        $botwe = Invoice::whereRelation('client', 'field_id', 2)->whereIn('status', ['unpaid', 'uncompleted'])->get();
        $botweAging = $this->groupInvoicesByAging($botwe);
        $botwePcount = Invoice::whereRelation('client', 'field_id', 2)->where('balance', '>', 0.00)->where('status', 'uncompleted')->get();

        $botweTotal = $botwe->sum('total');
        $botweCount = count($botwe);

        $tema = Invoice::whereRelation('client', 'field_id', 3)->whereIn('status', ['unpaid', 'uncompleted'])->get();
        $temaAging = $this->groupInvoicesByAging($tema);
        $temaPcount = Invoice::whereRelation('client', 'field_id', 3)->where('balance', '>', 0.00)->where('status', 'uncompleted')->get();

        $temaTotal = $tema->sum('total');
        $temaCount = count($tema);

        $takoradi = Invoice::whereRelation('client', 'field_id', 4)->whereIn('status', ['unpaid', 'uncompleted'])->get();
        $takoradiAging = $this->groupInvoicesByAging($takoradi);
        $takoradiPcount = Invoice::whereRelation('client', 'field_id', 4)->where('balance', '>', 0.00)->where('status', 'uncompleted')->get();

        $takoradiTotal = $takoradi->sum('total');
        $takoradiCount = count($takoradi);

        $koforidua = Invoice::whereRelation('client', 'field_id', 5)->whereIn('status', ['unpaid', 'uncompleted'])->get();
        $koforiduaAging = $this->groupInvoicesByAging($koforidua);
        $koforiduaPcount = Invoice::whereRelation('client', 'field_id', 5)->where('balance', '>', 0.00)->where('status', 'uncompleted')->get();

        $koforiduaTotal = $koforidua->sum('total');
        $koforiduaCount = count($koforidua);

        $kumasi = Invoice::whereRelation('client', 'field_id', 6)->whereIn('status', ['unpaid', 'uncompleted'])->get();
        $kumasiAging = $this->groupInvoicesByAging($kumasi);
        $kumasiPcount = Invoice::whereRelation('client', 'field_id', 6)->where('balance', '>', 0.00)->where('status', 'uncompleted')->get();

        $kumasiTotal = $kumasi->sum('total');
        $kumasiCount = count($kumasi);

        $shyhills = Invoice::whereRelation('client', 'field_id', 7)->whereIn('status', ['unpaid', 'uncompleted'])->get();
        $shyhillsAging = $this->groupInvoicesByAging($shyhills);
        $shyhillsPcount = Invoice::whereRelation('client', 'field_id', 7)->where('balance', '>', 0.00)->where('status', 'uncompleted')->get();

        $shyhillsTotal = $shyhills->sum('total');
        $shyhillsCount = count($shyhills);


        return view('sales.invoice_outstanding', compact( 'reportInvoicesAging', 'reportPinvoices', 'accraAging', 'botweAging', 'temaAging', 'takoradiAging', 'koforiduaAging', 'kumasiAging', 'shyhillsAging', 'accraPcount','botwePcount', 'temaPcount', 'takoradiPcount', 'koforiduaPcount', 'kumasiPcount', 'shyhillsPcount','reportInvoices', 'accra', 'botwe', 'tema', 'shyhills','takoradi', 'koforidua', 'kumasi','accraTotal', 'accraCount', 'botweTotal', 'botweCount', 'temaTotal', 'shyhillsTotal', 'shyhillsCount','temaCount', 'takoradiTotal', 'takoradiCount', 'koforiduaTotal', 'koforiduaCount', 'kumasiTotal', 'kumasiCount'));
    }

    // Search invoices with part payment outstanding   
    public function searchOutstandingInvoices(InvoiceToPayrollSearchRequest $request)
    {
        $month = Carbon::parse($request->month)->format('Y-m-d');

        $reportInvoices = Invoice::whereIn('status', ['unpaid','uncompleted'])->where('invoice_month', $month)->get();
        $reportPinvoices = Invoice::where('balance', '>', 0.00)->where('status', 'uncompleted')->where('invoice_month', $month)->get();

        $invoiceTotal = $reportInvoices->sum('total');
        $invoiceCount = $reportInvoices->count();

        $accra = Invoice::whereRelation('client', 'field_id', 1)->whereIn('status', ['unpaid','uncompleted'])->where('invoice_month', $month)->get();
        $accraPcount = Invoice::whereRelation('client', 'field_id', 1)->where('balance', '>', 0.00)->where('status', 'uncompleted')->where('invoice_month', $month)->get();
        $accraTotal = $accra->sum('total');
        $accraCount = count($accra);

        $botwe = Invoice::whereRelation('client', 'field_id', 2)->whereIn('status', ['unpaid','uncompleted'])->where('invoice_month', $month)->get();
        $botwePcount = Invoice::whereRelation('client', 'field_id', 2)->where('balance', '>', 0.00)->where('status', 'uncompleted')->where('invoice_month', $month)->get();
        $botweTotal = $botwe->sum('total');
        $botweCount = count($botwe);

        $tema = Invoice::whereRelation('client', 'field_id', 3)->whereIn('status', ['unpaid','uncompleted'])->where('invoice_month', $month)->get();
        $temaPcount = Invoice::whereRelation('client', 'field_id', 3)->where('balance', '>', 0.00)->where('status', 'uncompleted')->where('invoice_month', $month)->get();
        $temaTotal = $tema->sum('total');
        $temaCount = count($tema);

        $takoradi = Invoice::whereRelation('client', 'field_id', 4)->whereIn('status', ['unpaid','uncompleted'])->where('invoice_month', $month)->get();
        $takoradiPcount = Invoice::whereRelation('client', 'field_id', 4)->where('balance', '>', 0.00)->where('status', 'uncompleted')->where('invoice_month', $month)->get();
        $takoradiTotal = $takoradi->sum('total');
        $takoradiCount = count($takoradi);

        $koforidua = Invoice::whereRelation('client', 'field_id', 5)->whereIn('status', ['unpaid','uncompleted'])->where('invoice_month', $month)->get();
        $koforiduaPcount = Invoice::whereRelation('client', 'field_id', 5)->where('balance', '>', 0.00)->where('status', 'uncompleted')->where('invoice_month', $month)->get();
        $koforiduaTotal = $koforidua->sum('total');
        $koforiduaCount = count($koforidua);

        $kumasi = Invoice::whereRelation('client', 'field_id', 6)->whereIn('status', ['unpaid','uncompleted'])->where('invoice_month', $month)->get();
        $kumasiPcount = Invoice::whereRelation('client', 'field_id', 6)->where('balance', '>', 0.00)->where('status', 'uncompleted')->where('invoice_month', $month)->get();
        $kumasiTotal = $kumasi->sum('total');
        $kumasiCount = count($kumasi);

        $shyhills = Invoice::whereRelation('client', 'field_id', 7)->whereIn('status', ['unpaid','uncompleted'])->where('invoice_month', $month)->get();
        $shyhillsPcount = Invoice::whereRelation('client', 'field_id', 7)->where('balance', '>', 0.00)->where('status', 'uncompleted')->where('invoice_month', $month)->get();
        $shyhillsTotal = $shyhills->sum('total');
        $shyhillsCount = count($shyhills);

        return view('sales.invoice_outstanding', compact('reportPinvoices', 'accraPcount', 'botwePcount', 'temaPcount', 'takoradiPcount', 'koforiduaPcount', 'kumasiPcount', 'shyhillsPcount', 'month', 'reportInvoices', 'invoiceTotal', 'invoiceCount', 'accra', 'botwe', 'tema', 'shyhills','takoradi', 'koforidua', 'kumasi','accraTotal', 'accraCount', 'botweTotal', 'botweCount', 'temaTotal', 'shyhillsTotal', 'shyhillsCount','temaCount', 'takoradiTotal', 'takoradiCount', 'koforiduaTotal', 'koforiduaCount', 'kumasiTotal', 'kumasiCount'));
    }


    public function dashboardPartPaymentOutstanding ()
    {

        $reportInvoices = Invoice::where('balance', '>', 0.00)->where('status', 'uncompleted')->get();

        // dd($part_payment_outstanding);->orwhere('status', 'uncompleted')
        $accra = Invoice::whereRelation('client', 'field_id', 1)->where('balance', '>', 0.00)->where('status', 'uncompleted')->get();
    //    dd($accra);
        $accraTotal = $accra->sum('balance');
        $accraCount = count($accra);

        $botwe = Invoice::whereRelation('client', 'field_id', 2)->where('balance', '>', 0.00)->where('status', 'uncompleted')->get();
        $botweTotal = $botwe->sum('balance');
        $botweCount = count($botwe);

        $tema = Invoice::whereRelation('client', 'field_id', 3)->where('balance', '>', 0.00)->where('status', 'uncompleted')->get();
        $temaTotal = $tema->sum('balance');
        $temaCount = count($tema);

        $takoradi = Invoice::whereRelation('client', 'field_id', 4)->where('balance', '>', 0.00)->where('status', 'uncompleted')->get();
        $takoradiTotal = $takoradi->sum('balance');
        $takoradiCount = count($takoradi);

        $koforidua = Invoice::whereRelation('client', 'field_id', 5)->where('balance', '>', 0.00)->where('status', 'uncompleted')->get();
        $koforiduaTotal = $koforidua->sum('balance');
        $koforiduaCount = count($koforidua);

        $kumasi = Invoice::whereRelation('client', 'field_id', 6)->where('balance', '>', 0.00)->where('status', 'uncompleted')->get();
        $kumasiTotal = $kumasi->sum('balance');
        $kumasiCount = count($kumasi);

        $shyhills = Invoice::whereRelation('client', 'field_id', 7)->where('balance', '>', 0.00)->where('status', 'uncompleted')->get();
        $shyhillsTotal = $shyhills->sum('balance');
        $shyhillsCount = count($shyhills);

        return view('sales.part_payment_outstanding', compact('reportInvoices', 'accra', 'botwe', 'tema',  'shyhills','takoradi', 'koforidua', 'kumasi', 'accraTotal', 'accraCount', 'botweTotal', 'botweCount', 'temaTotal', 'temaCount', 'shyhillsTotal', 'shyhillsCount','takoradiTotal', 'takoradiCount', 'koforiduaTotal', 'koforiduaCount', 'kumasiTotal', 'kumasiCount'));


    }

    // Search invoices with part payment outstanding
    public function searchPartPaymentOutstanding(InvoiceToPayrollSearchRequest $request)
    {
        $month = Carbon::parse($request->month)->format('Y-m-d');  
        $reportInvoices = Invoice::where('balance', '>', 0.00)->where('status', 'uncompleted')->where('invoice_month', $month)->get();
        $invoiceTotal = $reportInvoices->sum('balance');
        $invoiceCount = $reportInvoices->count();
        
        $accra = Invoice::whereRelation('client', 'field_id', 1)->where('balance', '>', 0.00)->where('status', 'uncompleted')->where('invoice_month', $month)->get();
        $accraTotal = $accra->sum('balance');
        $accraCount = count($accra);

        $botwe = Invoice::whereRelation('client', 'field_id', 2)->where('balance', '>', 0.00)->where('status', 'uncompleted')->where('invoice_month', $month)->get();
        $botweTotal = $botwe->sum('balance');
        $botweCount = count($botwe);

        $tema = Invoice::whereRelation('client', 'field_id', 3)->where('balance', '>', 0.00)->where('status', 'uncompleted')->where('invoice_month', $month)->get();
        $temaTotal = $tema->sum('balance');
        $temaCount = count($tema);

        $takoradi = Invoice::whereRelation('client', 'field_id', 4)->where('balance', '>', 0.00)->where('status', 'uncompleted')->where('invoice_month', $month)->get();
        $takoradiTotal = $takoradi->sum('balance');
        $takoradiCount = count($takoradi);  

        $koforidua = Invoice::whereRelation('client', 'field_id', 5)->where('balance', '>', 0.00)->where('status', 'uncompleted')->where('invoice_month', $month)->get();
        $koforiduaTotal = $koforidua->sum('balance');
        $koforiduaCount = count($koforidua);    

        $kumasi = Invoice::whereRelation('client', 'field_id', 6)->where('balance', '>', 0.00)->where('status', 'uncompleted')->where('invoice_month', $month)->get();
        $kumasiTotal = $kumasi->sum('balance');
        $kumasiCount = count($kumasi);  

        $shyhills = Invoice::whereRelation('client', 'field_id', 7)->where('balance', '>', 0.00)->where('status', 'uncompleted')->where('invoice_month', $month)->get();
        $shyhillsTotal = $shyhills->sum('balance');
        $shyhillsCount = count($shyhills);  

        return view('sales.part_payment_outstanding', compact('reportInvoices', 'month', 'invoiceTotal', 'invoiceCount', 'accra', 'botwe', 'tema',  'shyhills','takoradi', 'koforidua', 'kumasi', 'accraTotal', 'accraCount', 'botweTotal', 'botweCount', 'temaTotal', 'temaCount', 'shyhillsTotal', 'shyhillsCount','takoradiTotal', 'takoradiCount', 'koforiduaTotal', 'koforiduaCount', 'kumasiTotal', 'kumasiCount'));
    }


    /**
     * Get all invoices with the client ID and Month
     */
    public function PayrollInvoice ($client_id, $month)
    {
        // dd($client_id, $month);
        // $date = Carbon::parse($month)->format('Y-m-d');
        $date = Carbon::createFromFormat('F, Y',$month)->startOfMonth()->format('Y-m-d');

        $invoices = Invoice::where('invoice_month', $date)->where('client_id', $client_id)->get();
        // dd($invoices);
        return view('salaries.invpayrollInvoice', compact('invoices', 'month'));

    }

}

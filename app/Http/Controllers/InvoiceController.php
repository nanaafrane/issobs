<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Client;
use App\Models\Field;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\Vat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $invoices = Invoice::all();
        return view('sales.invoice_list', compact('invoices'));

    }

    public function printInvoice($invoice_id)
    {
        $invoice = Invoice::findOrFail($invoice_id);
        $invoice_data = DB::table('invoice_data')->where('invoice_number', $invoice_id)->get();
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
           $vat_standard = $request->input('vat_standard');
           $invoice->status = 'unpaid';

           $amount = $request->input('amount');
           $sum_amount_from_invoice = array_sum($amount);

           $invoice->sub_amount = $sum_amount_from_invoice;
           $invoice->user_id = Auth::user()->id;
           $invoice->save();

           $invoice_number =  $invoice->id;

            $service = $request->input('service');
            $description   = $request->input('description');
            $quantity   = $request->input('quantity');
            $quantity_count = count($quantity);
            $unit_price = $request->input('unit_price');

            $nhilAmount = null;
            $getfundAmount = null;
            $chrlAmount = null;
            $sub_total_without_vat = null;
            $vatAmount = null;
            $total = $sum_amount_from_invoice;
            // dd($vat_standard);

        // dd($invoice_number, $description, $quantity, $unit_price, $amount, $quantity_count);
        if($quantity_count > 0)
        {
            for($i=0; $i<$quantity_count; $i++)
            {
                DB::table('invoice_data')->insert([
                'invoice_number' => $invoice_number,
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
            $chrlAmount = $vat->getChrlAmount($sum_amount_from_invoice);

            // for standard vat calculations, first sum the amounts of invoice, then add the percentage values for nhil,getfund
            // and chrl respectively.

            $sub_total_without_vat = $sum_amount_from_invoice + $nhilAmount + $getfundAmount + $chrlAmount;

            //   after summing get 15%vat of that value to get the 15% amount of that value.
            $vatAmount = $vat->getVatAmount($sub_total_without_vat);

            // now add the value + the 15%vat amount to give the total of the invoice.
            $total = $sub_total_without_vat + $vatAmount;
            }

            $update_invoice = Invoice::findOrFail($invoice_number);
            $update_invoice->nhil = $nhilAmount;
            $update_invoice->getfund = $getfundAmount;
            $update_invoice->chrl = $chrlAmount;
            $update_invoice->sub_total = $sub_total_without_vat;
            $update_invoice->vat_amount = $vatAmount;
            $update_invoice->total = $total;
            $update_invoice->save();


            $transaction = new Transaction();
            $transaction->client_id = $request->input('client_id');
            $transaction->invoice_id = $invoice_number;
            $transaction->invoice_amount = $total;
            $transaction->status = 'unpaid';
            $transaction->save();


            // // select all transactions with this current invoice iD and assign value D to the checks culumn
            // Transaction::where('invoice_id', $invoice_number)->update(['checks' => 'd']);

            return redirect('invoice')->with('success', 'Invoice Generated Successfully');

        }
        //    dd($invoice_number);
    }

    /**
     * Display the specified resource.
     */
    public function show( Invoice $invoice)
    {
        //
        // dd($invoice);
        $invoice_data = DB::table('invoice_data')->where('invoice_number', $invoice->id)->get();
        return view('sales.invoice_show', compact('invoice', 'invoice_data'));

    }


    public function generate($client_id)
    {
        $services = Service::all();
        $client = Client::findOrFail($client_id);
        return view('sales.invoice_generate', compact('client', 'services'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice)
    {
        //
        // dd($invoice);
        $services = Service::all();
        $invoice_data = DB::table('invoice_data')->where('invoice_number', $invoice->id)->get();
        return view('sales.invoice_edit', compact('invoice', 'invoice_data' ,'services'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        //
        // dd($invoice, $request->all());
        $service_name = $request->input('service_name');
        $due_date = $request->input('due_date');
        $description   = $request->input('description');
        $quantity   = $request->input('quantity');
        $quantity_count = count($quantity);
        $unit_price = $request->input('unit_price');
        $amount = $request->input('amount');
        $sum_amount_from_invoice = array_sum($amount);

        // dd($description, $quantity, $quantity_count, $unit_price, $amount, $sum_amount_from_invoice);
        $vat =  new Vat();
        $nhilAmount = $vat->getNhilAmount($sum_amount_from_invoice);
        $getfundAmount = $vat->getGetFundAmount($sum_amount_from_invoice);
        $chrlAmount = $vat->getChrlAmount($sum_amount_from_invoice);

        $sub_total_without_vat = $sum_amount_from_invoice + $nhilAmount + $getfundAmount + $chrlAmount;

        $vatAmount = $vat->getVatAmount($sub_total_without_vat);

        $total = $sub_total_without_vat + $vatAmount;


        // dd($description, $service_name, $quantity, $quantity_count, $unit_price, $amount, $sum_amount_from_invoice, $nhilAmount, $getfundAmount, $chrlAmount, $sub_total_without_vat, $vatAmount, $total);
        if ($quantity_count > 0)
        {
            DB::table('invoice_data')->where('invoice_number', $invoice->id)->delete();
            for($i = 0; $i<$quantity_count; $i++) {

                DB::table('invoice_data')->upsert(
                    [
                        'invoice_number'=> $invoice->id, 'service_name'=> $service_name[$i], 'description' => $description[$i], 'quantity' => $quantity[$i], 'unit_price' => $unit_price[$i], 'amount' => $amount[$i],
                    ],

                    ['invoice_number'],

                    ['description','quantity', 'unit_price', 'amount']

            );
                // dd($i );
                // dd($description[$i], $quantity[$i], $quantity_count, $unit_price[$i], $amount[$i], $sum_amount_from_invoice);

            }
        }

        Invoice::where('id', $invoice->id)->update([
            'nhil' => $nhilAmount,
            'getfund' => $getfundAmount,
            'chrl' => $chrlAmount,
            'sub_amount' => $sum_amount_from_invoice,
            'vat_amount' => $vatAmount,
            'sub_total' => $sub_total_without_vat,
            'total' => $total,
            'due_date' => $due_date,
            'user_id' => Auth::user()->id,
        ]);

        return redirect('invoice')->with('info', 'Invoice Updated Successfully');

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
          DB::table('invoice_data')->where('invoice_number', $invoice->id)->delete();

        return redirect('invoice')->with('error', 'Invoices Deleted Successfully');
    }

    public function dashboardViewAllInvoices()
    {
        $reportInvoices =  Invoice::all();
        $accra = Invoice::whereRelation('client', 'field_id', 1)->get();
        $accraTotal = $accra->sum('total');
        $accraCount = count($accra);

        $botwe = Invoice::whereRelation('client', 'field_id', 2)->get();
        $botweTotal = $botwe->sum('total');
        $botweCount = count($botwe);

        $tema = Invoice::whereRelation('client', 'field_id', 3)->get();
        $temaTotal = $tema->sum('total');
        $temaCount = count($tema);

        $takoradi = Invoice::whereRelation('client', 'field_id', 4)->get();
        $takoradiTotal = $takoradi->sum('total');
        $takoradiCount = count($takoradi);

        $koforidua = Invoice::whereRelation('client', 'field_id', 5)->get();
        $koforiduaTotal = $koforidua->sum('total');
        $koforiduaCount = count($koforidua);

        $kumasi = Invoice::whereRelation('client', 'field_id', 6)->get();
        $kumasiTotal = $kumasi->sum('total');
        $kumasiCount = count($kumasi);

        // dd($koforidua->sum('total'), count($koforidua));
       return view('sales.invoice_dashboard', compact('reportInvoices', 'accra' , 'botwe' , 'tema' , 'takoradi' , 'koforidua' , 'kumasi' ,'accraTotal', 'accraCount', 'botweTotal', 'botweCount', 'temaTotal', 'temaCount', 'takoradiTotal', 'takoradiCount', 'koforiduaTotal', 'koforiduaCount', 'kumasiTotal', 'kumasiCount'));
    }

    public function dashboardInvoiceWithOutstanding()
    {

        $reportInvoices =  Invoice::where('status', 'unpaid')->get();

        $accra = Invoice::whereRelation('client', 'field_id', 1)->where('status', 'unpaid')->get();
        $accraTotal = $accra->sum('total');
        $accraCount = count($accra);

        $botwe = Invoice::whereRelation('client', 'field_id', 2)->where('status', 'unpaid')->get();
        $botweTotal = $botwe->sum('total');
        $botweCount = count($botwe);

        $tema = Invoice::whereRelation('client', 'field_id', 3)->where('status', 'unpaid')->get();
        $temaTotal = $tema->sum('total');
        $temaCount = count($tema);

        $takoradi = Invoice::whereRelation('client', 'field_id', 4)->where('status', 'unpaid')->get();
        $takoradiTotal = $takoradi->sum('total');
        $takoradiCount = count($takoradi);

        $koforidua = Invoice::whereRelation('client', 'field_id', 5)->where('status', 'unpaid')->get();
        $koforiduaTotal = $koforidua->sum('total');
        $koforiduaCount = count($koforidua);

        $kumasi = Invoice::whereRelation('client', 'field_id', 6)->where('status', 'unpaid')->get();
        $kumasiTotal = $kumasi->sum('total');
        $kumasiCount = count($kumasi);


        return view('sales.invoice_outstanding', compact('reportInvoices', 'accra', 'botwe', 'tema', 'takoradi', 'koforidua', 'kumasi','accraTotal', 'accraCount', 'botweTotal', 'botweCount', 'temaTotal', 'temaCount', 'takoradiTotal', 'takoradiCount', 'koforiduaTotal', 'koforiduaCount', 'kumasiTotal', 'kumasiCount'));
    }


    public function dashboardPartPaymentOutstanding ()
    {

        $part_payment_outstanding = Invoice::where('balance', '>', 0.00)->where('status', 'uncompleted')->get();

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

        return view('sales.part_payment_outstanding', compact('part_payment_outstanding', 'accra', 'botwe', 'tema', 'takoradi', 'koforidua', 'kumasi', 'accraTotal', 'accraCount', 'botweTotal', 'botweCount', 'temaTotal', 'temaCount', 'takoradiTotal', 'takoradiCount', 'koforiduaTotal', 'koforiduaCount', 'kumasiTotal', 'kumasiCount'));


    }


}

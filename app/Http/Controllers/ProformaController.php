<?php

namespace App\Http\Controllers;

use App\Models\Proforma;
use App\Http\Requests\StoreProformaRequest;
use App\Http\Requests\UpdateProformaRequest;
use App\Models\Client;
use App\Models\Field;
use App\Models\ProformaClient;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\Vat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProformaController extends Controller
{

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
        $proforma = Proforma::all();
        return view('sales.proformalist', compact('proforma'));

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $services = Service::all(); 
        $clients = ProformaClient::all();
        $fields = Field::all();

        return view('sales.proforma_create', compact('clients', 'services', 'fields'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProformaRequest $request)
    {
        //
        // dd($request->all());
                // dd($request->all());
        //    $user = Auth::user();
           $invoice = new Proforma();
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
            $vatAmount = $vat->getVatAmount($sum_amount_from_invoice);

            // for standard vat calculations, first sum the amounts of invoice, then add the percentage values for nhil,getfund
            // and chrl respectively.

            $total = $sum_amount_from_invoice + $nhilAmount + $getfundAmount + $vatAmount;

            //   after summing get 15%vat of that value to get the 15% amount of that value.
            // $vatAmount = $vat->getVatAmount($sub_total_without_vat);

            // now add the value + the 15%vat amount to give the total of the invoice.
            // $total = $sub_total_without_vat + $vatAmount;
            }

            $update_invoice = Proforma::findOrFail($invoice_number);
            $update_invoice->nhil = $nhilAmount;
            $update_invoice->getfund = $getfundAmount;
            $update_invoice->chrl = $chrlAmount;
            $update_invoice->sub_total = $sub_total_without_vat;
            $update_invoice->vat_amount = $vatAmount;
            $update_invoice->total = $total;
            $update_invoice->save();


            // // select all transactions with this current invoice iD and assign value D to the checks culumn
            // Transaction::where('invoice_id', $invoice_number)->update(['checks' => 'd']);

            return redirect()->route('proforma.show',['proforma' => $invoice_number])->with('primary', 'Proforma Generated Successfully');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Proforma $proforma)
    {
        //
        // dd($proforma);
         $proforma_data = DB::table('invoice_data')->where('invoice_number', $proforma->id)->orderBy('invoice_number', 'desc')->get();
        return view('sales.proforma_show', compact('proforma', 'proforma_data'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Proforma $proforma)
    {
        //
        $services = Service::all();
        $clients = ProformaClient::all();
        $proforma_data = DB::table('invoice_data')->where('invoice_number', $proforma->id)->get();
        return view('sales.proforma_edit', compact('proforma', 'proforma_data' ,'services', 'clients'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProformaRequest $request, Proforma $proforma)
    {
        //
         // dd($invoice, $request->all());
        $service_name = $request->input('service');
        $due_date = $request->input('due_date');
        $client_id = $request->input('client_id');
        $invoice_month = Carbon::parse($request->input('invoice_month'))->format('Y-m-d') ;
        // $month = Carbon::parse($request->month)->format('Y-m-d');
        // dd($invoice_month);

        $description   = $request->input('description');
        $quantity   = $request->input('quantity');
        $quantity_count = count($quantity);
        $unit_price = $request->input('unit_price');
        $amount = $request->input('amount');
        $sum_amount_from_invoice = array_sum($amount);
        $vat_standard = $request->input('vat_standard');

        $nhilAmount = null;
        $getfundAmount = null;
        $chrlAmount = null;
        $sub_total_without_vat = null;
        $vatAmount = null;
        $total = $sum_amount_from_invoice;

        if($vat_standard == 'on'){
        // dd($description, $quantity, $quantity_count, $unit_price, $amount, $sum_amount_from_invoice);
        $vat =  new Vat();
        $nhilAmount = $vat->getNhilAmount($sum_amount_from_invoice);
        $getfundAmount = $vat->getGetFundAmount($sum_amount_from_invoice);
        $vatAmount = $vat->getVatAmount($sum_amount_from_invoice);

        $total = $sum_amount_from_invoice + $nhilAmount + $getfundAmount + $vatAmount;

        // $vatAmount = $vat->getVatAmount($sub_total_without_vat);

        // $total = $sub_total_without_vat + $vatAmount;
        }

        // dd($description, $service_name, $quantity, $quantity_count, $unit_price, $amount, $sum_amount_from_invoice, $nhilAmount, $getfundAmount, $chrlAmount, $sub_total_without_vat, $vatAmount, $total);
        if ($quantity_count > 0)
        {
            DB::table('invoice_data')->where('invoice_number', $proforma->id)->delete();
            for($i = 0; $i<$quantity_count; $i++) {

                DB::table('invoice_data')->upsert(
                    [
                        'invoice_number'=> $proforma->id, 'service_name'=> $service_name[$i], 'description' => $description[$i], 'quantity' => $quantity[$i], 'unit_price' => $unit_price[$i], 'amount' => $amount[$i],
                    ],

                    ['invoice_number'],

                    ['description','quantity', 'unit_price', 'amount']

            );
                // dd($i );
                // dd($description[$i], $quantity[$i], $quantity_count, $unit_price[$i], $amount[$i], $sum_amount_from_invoice);

            }
        }

        Proforma::where('id', $proforma->id)->update([
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
            'user_id' => Auth::user()->id,
        ]);



        // return back()->with('primary', 'Invoice Updated Successfully');
        return redirect()->route('proforma.show',['proforma' => $proforma])->with('primary', 'Pro Forma  Updated Successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Proforma $proforma)
    {
        //
        
          Proforma::destroy($proforma->id);
          DB::table('invoice_data')->where('invoice_number', $proforma->id)->delete();

        return redirect('proforma')->with('error', 'Proforma Deleted Successfully');
    }

    public function printProforma($invoice_id)
    {
        $invoice = Proforma::findOrFail($invoice_id);
        $invoice_data = DB::table('invoice_data')->where('invoice_number', $invoice_id)->orderBy('invoice_number', 'desc')->get();
        return view('sales.printProforma', compact('invoice', 'invoice_data'));
    }


}

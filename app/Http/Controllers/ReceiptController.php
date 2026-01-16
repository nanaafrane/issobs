<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Http\Requests\StoreReceiptRequest;
use App\Http\Requests\UpdateReceiptRequest;
use App\Http\Requests\InvoiceToPayrollSearchRequest;
use App\Models\Client;
use App\Models\Collection;
use App\Models\Field;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\Wht;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReceiptController extends Controller
{
    public $wht_amount;

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
        $user = Auth::user();

        if($user->role->name == 'Finance Manager' || $user->role->name == 'Invoice' )
        {
            $receipts = Receipt::all();
            return view('sales.receipt_list', compact('receipts'));
        }

        if($user->field?->name == 'Accra' )
        {
            $receipts = Receipt::whereRelation('client', 'field_id', '1')->get();
        }

        if ($user->field?->name == 'Botwe')
        {
            $receipts = Receipt::whereRelation('client', 'field_id', '2')->get();
        }

        if ($user->field?->name == 'Tema')
        {
            $receipts = Receipt::whereRelation('client', 'field_id', '3')->get();
        }

        if ($user->field?->name == 'Takoradi')
        {
            $receipts = Receipt::whereRelation('client', 'field_id', '4')->get();
        }

        if ($user->field?->name == 'Koforidua')
        {
            $receipts = Receipt::whereRelation('client', 'field_id', '5')->get();
        }

        if ($user->field?->name == 'Kumasi')
        {
            $receipts = Receipt::whereRelation('client', 'field_id', '6')->get();
        }

        return view('sales.receipt_list', compact('receipts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $user = Auth::user();

        if ($user->role->name == 'Finance Manager' || $user->role->name == 'Invoice')
        {
            $invoices = Invoice::where('status', 'unpaid')->orwhere('status', 'uncompleted')->get();
            return view('sales.receipt_create', compact('invoices'));
        }


        if ($user->field?->name == 'Accra')
        {
            $invoices = Invoice::whereRelation('client', 'field_id', '1')->where('status', 'unpaid')->orwhere('status', 'uncompleted')->get();
        }

        if ($user->field?->name == 'Botwe')
        {
            $invoices = Invoice::whereRelation('client', 'field_id', '2')->where('status', 'unpaid')->orwhere('status', 'uncompleted')->get();
        }

        if ($user->field?->name == 'Tema')
        {
            $invoices = Invoice::whereRelation('client', 'field_id', '3')->where('status', 'unpaid')->orwhere('status', 'uncompleted')->get();
        }

        if ($user->field?->name == 'Takoradi')
        {
            $invoices = Invoice::whereRelation('client', 'field_id', '4')->where('status', 'unpaid')->orwhere('status', 'uncompleted')->get();
        }

        if ($user->field?->name == 'Koforidua')
        {
            $invoices = Invoice::whereRelation('client', 'field_id', '5')->where('status', 'unpaid')->orwhere('status', 'uncompleted')->get();
        }

        if ($user->field?->name == 'Kumasi')
        {
            $invoices = Invoice::whereRelation('client', 'field_id', '6')->where('status', 'unpaid')->orwhere('status', 'uncompleted')->get();
        }

        return view('sales.receipt_create', compact('invoices'));

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReceiptRequest $request)
    {

        // dd($request->all());
        // Get all values from receipt form
        $status = $request->input('status');
        $wth_from_form = $request->input('wth');
        $vat_from_form = $request->input('vat');
        $deductions_from_form = $request->input('deductions');
        $invoice_id = $request->input('invoice_id');
        // dd($invoice_id);
        $invoice_data = Invoice::where('id',$invoice_id)->first();
        // dd($invoice_data);
        $client_id = $request->input('client_id');
        $from = $request->input('from');
        $mode = $request->input('mode');
        $receipt_date = $request->input('receipt_month');
        $vat7_value = $request->float('vat7_value');
        $dAmount = $request->float('dAmount');
        $description = $request->input('description');

        $transfer_reference = $request->input('transfer_reference');
        $transfer_amount = $request->float('transfer_amount');
        $transfer_bank = $request->input('transfer_bank');

        $cheque_reference = $request->input('cheque_reference');
        $cheque_amount = $request->float('cheque_amount');
        $cheque_bank = $request->input('cheque_bank');

        $momo_transactin_id = $request->input('momo_transactin_id');
        $momo_amount = $request->float('momo_amount');

        $other_payment_descri = $request->input('other_payment_descri');
        $other_payment_amnt = $request->float('other_payment_amnt');

        $cash_amount = $request->float('cash_amount');
        $user_id = Auth::user()->id;

        // if receipt has image
        $image = null;
        if($request->file('image'))
        {
           $image = ($request->file('image'))->store('images', 'public_html_disk');
        }

        // sum all input payments
        $total = $cheque_amount + $momo_amount + $cash_amount + $transfer_amount + $other_payment_amnt;
        $sum_of_amountPaid_minus_wht = null;
        $wht_amount = null;
        $vat7_amount = null;

        // if the payment has with holding turned on;
        if($wth_from_form == "on")
        {
            $wht_amount = $request->float('wht_amount');
            // dd($wht_amount, $total);
            //    $wht = new Wht();
               $this->wht_amount = $wht_amount;
               $sum_of_amountPaid_minus_wht = $total;
               $total = $total + $wht_amount;
        }
        if($vat_from_form == "on")
        {
            $vat7_amount = $sum_of_amountPaid_minus_wht - $vat7_value ;
        }
        // if($deductions_from_form == "on")
        // {
        //     $dAmount;
        // }
        // dd($sum_of_amountPaid_minus_wht, $total);
        // end of if the payment has with holding turned on

        $check1 =  $invoice_data->total + $invoice_data->balance;
        $check2 = $invoice_data->balance - $total - $dAmount ;

    //    $fields = Field::pluck('id');
        $client = Client::findOrFail($client_id);
        // dd($client->field->id, $fields);
        $current_collection = Collection::where('field_id', $client->field->id)->latest()->first();

        // dd( $check1, $check2, $total );
        // if payment is one time and is complete //
        if($status == 'completed' && $check1 == $total + $dAmount )
        {
            // return "you're here! full payment one time payment";

            // // create Receipt
               $receipt_id = $this->createReceipt($invoice_id, $dAmount, $description, $receipt_date, $vat7_value, $vat7_amount, $client_id, $from, $mode, $cheque_reference, $cheque_amount, $cheque_bank, $transfer_reference, $transfer_amount, $transfer_bank, $momo_transactin_id, $momo_amount, $cash_amount, $other_payment_descri, $other_payment_amnt, $user_id, $status, $total, $this->wht_amount, $sum_of_amountPaid_minus_wht, $image);

                // update the invoice status to completed and update the balance to 0
                $invoice_data->status = $status;
                $invoice_data->wht_amount = $invoice_data->wht_amount + $this->wht_amount;
                $invoice_data->amount_received = $invoice_data->amount_received + $sum_of_amountPaid_minus_wht;
                $invoice_data->vat7_value = $vat7_value;
                $invoice_data->vat7_amount = $vat7_amount;
                $invoice_data->save();

                $transaction = new Transaction();
                $transaction->client_id = $client_id;
                $transaction->invoice_id = $invoice_id;
                $transaction->invoice_amount = $invoice_data->total;
                $transaction->receipt_id = $receipt_id;
                $transaction->receipt_amount = $total + $dAmount;
                $transaction->balance = 0;
                $transaction->status = $status;
                $transaction->save();

            // // CREATE A COLLECTION HERE.................
            $this->create_collection($current_collection, $receipt_id, $cash_amount, $momo_amount, $cheque_amount, $transfer_amount, $total, $client->field->id);
            // END OF CREATE COLLECTIONS //

            //     // select all transactions with this current invoice iD and assign value d to the checks culumn
            Transaction::where('invoice_id', $invoice_id)->update(['checks' => 'd']);

                // return redirect('receipt')->with('primary', 'Receipt created Successfully');
            return redirect()->route('receipt.show',['receipt' => $receipt_id])->with('primary', 'Receipt  Craeated Successfully');

        }
        elseif($status == 'completed' && $check2 == 0 )
        {

            // return "you're here! full payment after part payment";
            $receipt_id = $this->createReceipt($invoice_id, $dAmount, $description, $receipt_date, $vat7_value, $vat7_amount, $client_id, $from, $mode, $cheque_reference, $cheque_amount, $cheque_bank, $transfer_reference, $transfer_amount, $transfer_bank, $momo_transactin_id, $momo_amount, $cash_amount, $other_payment_descri, $other_payment_amnt, $user_id, $status, $total, $this->wht_amount, $sum_of_amountPaid_minus_wht, $image);

            // get balance
            $balance = $invoice_data->balance - $total;

            // update the invoice status from partpayment(uncomplete) to complete and update the balance to 0
            $invoice_data->balance = $balance;
            $invoice_data->wht_amount = $invoice_data->wht_amount + $this->wht_amount;
            $invoice_data->amount_received = $invoice_data->amount_received + $sum_of_amountPaid_minus_wht;
            $invoice_data->status = $status;
            $invoice_data->vat7_value = $vat7_value;
            $invoice_data->vat7_amount = $vat7_amount;
            $invoice_data->save();


            $transaction = new Transaction();
            $transaction->client_id = $client_id;
            $transaction->invoice_id = $invoice_id;
            $transaction->invoice_amount = $invoice_data->total;
            $transaction->receipt_id = $receipt_id;
            $transaction->receipt_amount = $total + $dAmount;
            $transaction->balance = 0;
            $transaction->status = $status;
            $transaction->save();

            // CREATE A COLLECTION HERE.................
            $this->create_collection($current_collection, $receipt_id, $cash_amount, $momo_amount, $cheque_amount, $transfer_amount, $total, $client->field->id);

            // // select all transactions with this current invoice iD and assign value D to the checks culumn
            Transaction::where('invoice_id', $invoice_id)->update(['checks' => 'd']);

            // return redirect('receipt')->with('success', 'Receipt created Successfully');
            return redirect()->route('receipt.show',['receipt' => $receipt_id])->with('primary', 'Receipt  Craeated Successfully');

        }
        else {
            // return "you're here! part payment ";

            // // get the balance of the invoice
            if($invoice_data->balance > 0)
            {
                $balance = $invoice_data->balance - $total - $dAmount ;
            }
            else{
                // get the balance of the invoice
                $balance = $invoice_data->total - $total - $dAmount ;
            }


            // dd($balance, $invoice_data->balance, $invoice_data->total, $total, $dAmount);

            // create a receipt
            // $sum_of_amountPaid_minus_wht = null;
            $receipt_id = $this->createReceipt($invoice_id, $dAmount, $description, $receipt_date, $vat7_value, $vat7_amount, $client_id, $from, $mode, $cheque_reference, $cheque_amount, $cheque_bank, $transfer_reference, $transfer_amount, $transfer_bank, $momo_transactin_id, $momo_amount, $cash_amount,$other_payment_descri, $other_payment_amnt, $user_id, $status, $total, $this->wht_amount, $sum_of_amountPaid_minus_wht, $image);

            // update the invoice status to partpayment(uncomplete) and update the balance to current balance
            $invoice_data->balance = $balance;
            $invoice_data->wht_amount = $invoice_data->wht_amount + $this->wht_amount;
            $invoice_data->amount_received = $invoice_data->amount_received + $sum_of_amountPaid_minus_wht;
            $invoice_data->status = $status;
            $invoice_data->vat7_value = $vat7_value;
            $invoice_data->vat7_amount = $vat7_amount;
            $invoice_data->save();

            // create a new transaction
            $transaction = new Transaction();
            $transaction->client_id = $client_id;
            $transaction->invoice_id = $invoice_id;
            $transaction->invoice_amount = $invoice_data->total;
            $transaction->receipt_id = $receipt_id;
            $transaction->receipt_amount = $total + $dAmount;
            $transaction->balance = $balance;
            $transaction->status = $status;
            $transaction->save();
            // // echo "uncompleted " . " " . $invoice_data->total - $total ." ". "Balance is ";

            // CREATE A COLLECTION HERE.................
            $this->create_collection($current_collection, $receipt_id, $cash_amount, $momo_amount, $cheque_amount, $transfer_amount, $total, $client->field->id );


            return redirect('receipt')->with('success', 'Receipt created Successfully');
            return redirect()->route('receipt.show',['receipt' => $receipt_id])->with('primary', 'Receipt  Craeated Successfully');
    
        }

    }


    // public function create_new_Collection($cash_amount, $momo_amount, $cheque_amount, $transfer_amount, $total, $field_id)
    // {
    //     // create a collection
    //     $collection = new Collection();
    //     $collection->user_id = Auth::user()->id;
    //     $collection->cash_amount = $cash_amount;
    //     $collection->momo_amount = $momo_amount;
    //     $collection->cheque_amount = $cheque_amount;
    //     $collection->transfer_amount = $transfer_amount;
    //     $collection->total_amount = $total;
    //     $collection->field_id = $field_id;
    //     $collection->save();
    // }


    public function create_collection ($current_collection, $receipt_id, $cash_amount, $momo_amount, $cheque_amount, $transfer_amount, $total, $field_id)
    {
                // create a collection
        $collection = new Collection();
        $collection->user_id = Auth::user()->id;
        $collection->receipt_id = $receipt_id;
        $collection->cash_amount = $cash_amount;
        $collection->momo_amount = $momo_amount;
        $collection->cheque_amount = $cheque_amount;
        $collection->transfer_amount = $transfer_amount;
        $collection->total_amount = $total;
        $collection->field_id = $field_id;
        $collection->save();

        // if (is_null($current_collection)) {
        //     //  create a collection
        //     $this->create_new_Collection($cash_amount, $momo_amount, $cheque_amount, $transfer_amount, $total,  $field_id);
        // } elseif (!is_null($current_collection)) {
        //     // dd($current_collection);
        //     // echo $current_collection->created_at->toDateString();
        //     // if()
        //     $currentTime = Carbon::now();
        //     // echo $currentTime->toDateString();
        //     if ($currentTime->toDateString() == $current_collection->created_at->toDateString()) {
        //         // add current collections to the same day collections
        //         // return true;
        //         $current_collection->user_id = Auth::user()->id;
        //         $current_collection->cash_amount += $cash_amount;
        //         $current_collection->momo_amount += $momo_amount;
        //         $current_collection->cheque_amount += $cheque_amount;
        //         $current_collection->transfer_amount += $transfer_amount;
        //         $current_collection->total_amount += $total;
        //         $current_collection->field_id =  $field_id;
        //         $current_collection->save();
        //     } else {
        //         // return false;
        //         // // create a collection
        //         $this->create_new_Collection($cash_amount, $momo_amount, $cheque_amount, $transfer_amount, $total,  $field_id);
        //     }
        // }
    }


    /**
     * Display the specified resource.
     */
    public function show(Receipt $receipt)
    {
        //
        $wht = new Wht();
        return view('sales.receipt_show', compact('receipt','wht'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Receipt $receipt)
    {
        //
        // dd($receipt);
        $mode = DB::table('receipt_mode')->get();
        $status = DB::table('receipt_status')->get();
         $wht = new Wht();
         $invoice = Invoice::findorFail($receipt->invoice_id);
        return view('sales.receipt_edit', compact('receipt','wht','invoice','mode','status'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReceiptRequest $request, Receipt $receipt)
    {
        //
        // dd($request->all());

        // if receipt has been deposited, do not allow update   
        $collection = Collection::where('receipt_id', $receipt->id)->first();

        if($collection?->status == 'deposited')
        {
            return redirect()->back()->with('error', 'Receipt has been Deposited and cannot be edited');
        }

        // update receipt 
        // $receipt->update($request->all()); 
        $image = null;
        if($request->file('image'))
        {
           $image = ($request->file('image'))->store('images', 'public_html_disk');
        }

        $cheque_amount = $request->float('cheque_amount');
        $momo_amount = $request->float('momo_amount');
        $cash_amount = $request->float('cash_amount');
        $transfer_amount = $request->float('transfer_amount');
        $other_payment_amnt = $request->float('other_payment_amnt');
        

        // sum all input payments
        $total = $cheque_amount + $momo_amount + $cash_amount + $transfer_amount + $other_payment_amnt;
        $sum_of_amountPaid_minus_wht = null;
        $wht_amount = null;
        $vat7_amount = null;
        $sum_of_amountPaid_minus_wht = null;
        $wth_from_form = $request->input('wth');
        $vat_from_form = $request->input('vat');
        $vat7_value = $request->float('vat7_value');
        $dAmount = $request->float('dAmount');



        // if the payment has with holding turned on;
        if($wth_from_form == "on")
        {
            $wht_amount = $request->float('wht_amount');

               $this->wht_amount = $wht_amount;
               $sum_of_amountPaid_minus_wht = $total;
               $total = $total + $wht_amount;
        }
        if($vat_from_form == "on")
        {
            $vat7_amount = $sum_of_amountPaid_minus_wht - $vat7_value ;
        }

        $receipt->invoice_id = $request->input('invoice_id');
        $receipt->client_id = $request->input('client_id');
        $receipt->from = $request->input('from');
        $receipt->mode = $request->input('mode');
        $receipt->dAmount = $dAmount;
        $receipt->description = $request->input('description');
        $receipt->receipt_month = $request->input('receipt_month');
        $receipt->vat7_value = $request->input('vat7_value');
        $receipt->vat7_amount = $vat7_amount;

        $receipt->transfer_reference = $request->input('transfer_reference');
        $receipt->transfer_amount = $request->float('transfer_amount');
        $receipt->transfer_bank = $request->input('transfer_bank');

        $receipt->cheque_reference = $request->input('cheque_reference');
        $receipt->cheque_amount = $request->float('cheque_amount');
        $receipt->cheque_bank = $request->input('cheque_bank');

        $receipt->momo_transactin_id = $request->input('momo_transactin_id');
        $receipt->momo_amount = $request->float('momo_amount');
        $receipt->cash_amount = $request->float('cash_amount');
        $receipt->other_payment_descri = $request->input('other_payment_descri');
        $receipt->user_id = Auth::user()->id;
        $receipt->status = $request->input('status');
        $receipt->total = $total;
        $receipt->wht_amount = $this->wht_amount;
        $receipt->amount_received = $sum_of_amountPaid_minus_wht;
        $receipt->image = $image;
        $receipt->save();


        // update the invoice 
        $invoice = Invoice::findorFail($receipt->invoice_id);
        $invoice->balance = $invoice->total - $total - $dAmount;
        $invoice->status = $request->input('status');
        $invoice->wht_amount = $this->wht_amount;
        $invoice->amount_received = $sum_of_amountPaid_minus_wht;
        $invoice->vat7_value = $vat7_value;
        $invoice->vat7_amount = $vat7_amount;
        $invoice->save();

        if($receipt->status == "completed")
        {
            // update transaction
            Transaction::where('receipt_id', $receipt->id)->update([ 
                'receipt_amount' => $total,
                'status' => $request->input('status'),
                'balance' => 0,
                'checks' => 'd',
            ]);
        }
        elseif($receipt->status == "uncompleted")
        {
            // update transaction
            $transaction = Transaction::where('receipt_id', $receipt->id)->first();
            $transaction->receipt_amount = $total;
            $transaction->status = $request->input('status');
            $transaction->balance = $transaction->invoice->balance;
            $transaction->checks = null;
            $transaction->save();
        }


        // update collection
        $collection = Collection::where('receipt_id', $receipt->id);
        $collection->update([
            'receipt_id' => $receipt->id,
            'cash_amount' => $cash_amount,
            'momo_amount' => $momo_amount,
            'cheque_amount' => $cheque_amount,
            'transfer_amount' => $transfer_amount,
            'total_amount' => $total,
        ]); 

        return redirect()->route('receipt.show',['receipt' => $receipt->id])->with('primary', 'Receipt  Updated Successfully');


    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Receipt $receipt)
    {
        //
        // dd($receipt->id);
        // REMOVE FROM COLLECTIONS
        // $deleteCollections = Collection::where()->deleteOrFail();
        //   dd($deleteCollections);
        // if(isset($deleteCollections))
        // {
        // DELETE FROM TRANSACTION
        // Transaction::where('receipt_id', $receipt->id)->deleteOrFail();

        // UPDATE THE INVOICE TO DEFAULT
        //  $receipt->deleteOrFail();
        // }

        
        
    }


    public function receiptCreate($invoice_id)
    {
        $invoice = Invoice::findorFail($invoice_id);

        $wht_rate = new Wht();
        // dd($wht_rate->wht_rate);
        $mode = DB::table('receipt_mode')->get();
        $status = DB::table('receipt_status')->get();


        return view('sales.receiptCreate', compact('invoice', 'wht_rate', 'mode', 'status'));
    }




// , $other_payment_amnt, $other_payment_descri,
    public function createReceipt($invoice_id, $dAmount, $description, $receipt_date, $vat7_value, $vat7_amount, $client_id, $from, $mode, $cheque_reference, $cheque_amount, $cheque_bank, $transfer_reference, $transfer_amount, $transfer_bank, $momo_transactin_id, $momo_amount, $cash_amount,$other_payment_descri, $other_payment_amnt ,$user_id, $status, $total, $wht_amount, $sum_of_amountPaid_minus_wht, $image)
    {

        // dd($other_payment_amnt, $other_payment_descri);
        $newReceipt = new Receipt();

        $newReceipt->invoice_id = $invoice_id;
        $newReceipt->client_id = $client_id;
        $newReceipt->from = $from;
        $newReceipt->mode = $mode;
        $newReceipt->dAmount = $dAmount;
        $newReceipt->description = $description;
        $newReceipt->receipt_month = $receipt_date;
        $newReceipt->vat7_value = $vat7_value;
        $newReceipt->vat7_amount = $vat7_amount;

        $newReceipt->transfer_reference = $transfer_reference;
        $newReceipt->transfer_amount = $transfer_amount;
        $newReceipt->transfer_bank = $transfer_bank;

        $newReceipt->cheque_reference = $cheque_reference;
        $newReceipt->cheque_amount = $cheque_amount;
        $newReceipt->cheque_bank = $cheque_bank;

        $newReceipt->momo_transactin_id = $momo_transactin_id;
        $newReceipt->momo_amount = $momo_amount;
        $newReceipt->cash_amount = $cash_amount;

        $newReceipt->other_payment_descri = $other_payment_descri;
        $newReceipt->other_payment_amnt = $other_payment_amnt;

        $newReceipt->user_id = $user_id;
        $newReceipt->status = $status;
        $newReceipt->total = $total;
        $newReceipt->wht_amount = $wht_amount;
        $newReceipt->amount_received = $sum_of_amountPaid_minus_wht;
        $newReceipt->image = $image;
        $newReceipt->save();

        return $newReceipt->id;
    }


    public function dashboardAllPayment()
    {
        // $receipt_AllPayment = Receipt::sum('total');

        $reportReceipt =  Receipt::all();
        $accra = Receipt::whereRelation('client', 'field_id', 1)->get();
        $accraTotal = $accra->sum('total');
        $accraCount = count($accra);

        $botwe = Receipt::whereRelation('client', 'field_id', 2)->get();
        $botweTotal = $botwe->sum('total');
        $botweCount = count($botwe);

        $tema = Receipt::whereRelation('client', 'field_id', 3)->get();
        $temaTotal = $tema->sum('total');
        $temaCount = count($tema);

        $takoradi = Receipt::whereRelation('client', 'field_id', 4)->get();
        $takoradiTotal = $takoradi->sum('total');
        $takoradiCount = count($takoradi);

        $koforidua = Receipt::whereRelation('client', 'field_id', 5)->get();
        $koforiduaTotal = $koforidua->sum('total');
        $koforiduaCount = count($koforidua);

        $kumasi = Receipt::whereRelation('client', 'field_id', 6)->get();
        $kumasiTotal = $kumasi->sum('total');
        $kumasiCount = count($kumasi);

        $shyhills = Receipt::whereRelation('client', 'field_id', 7)->get();
        $shyhillsTotal = $shyhills->sum('total');
        $shyhillsCount = count($shyhills);

        return view('sales.receipt_dashboard', compact('reportReceipt', 'accra', 'botwe', 'tema', 'shyhills','takoradi', 'koforidua', 'kumasi' ,'accraTotal', 'accraCount', 'botweTotal', 'botweCount', 'temaTotal', 'temaCount', 'shyhillsTotal', 'shyhillsCount', 'takoradiTotal', 'takoradiCount', 'koforiduaTotal', 'koforiduaCount', 'kumasiTotal', 'kumasiCount'));
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

        return view('sales.receipt_dashboard', compact('reportReceipt', 'reportReceiptCount', 'month','reportReceiptTotal','accra', 'botwe', 'tema', 'shyhills','takoradi', 'koforidua', 'kumasi' ,'accraTotal', 'accraCount', 'botweTotal', 'botweCount', 'temaTotal', 'temaCount', 'shyhillsTotal', 'shyhillsCount', 'takoradiTotal', 'takoradiCount', 'koforiduaTotal', 'koforiduaCount', 'kumasiTotal', 'kumasiCount'));
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



}

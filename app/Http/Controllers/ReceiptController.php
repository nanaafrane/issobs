<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Http\Requests\StoreReceiptRequest;
use App\Http\Requests\UpdateReceiptRequest;
use App\Http\Requests\InvoiceToPayrollSearchRequest;
use Illuminate\Http\Request;
use App\Models\category;
use App\Models\Client;
use App\Models\Collection;
use App\Models\Field;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wht;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
// use Illuminate\Support\Number;
use Illuminate\Support\Str;

use function Symfony\Component\Clock\now;

class ReceiptController extends Controller
{
    public ?float $wht_amount = null;
    public ?float $vat7_amount = null;

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
        $staff = $request->input('staff');
        // dd($invoice_id);
        $invoice_data = Invoice::where('id',$invoice_id)->first();
        // dd($invoice_data);
        $client_id = $request->input('client_id');
        $from = $request->input('from');
        $mode = $request->input('mode');

        $advance_payment = null;
         if(isset($request->advance_payment) && $request->input('advance_payment') == "advance")
         {
            $advance_payment =  "advance";
         }
        $receipt_date = $request->input('receipt_month');
        // $vat7_value = $request->float('vat7_value');

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
        $vat7_value = null;


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
            $vat7_value = $request->float('vat7_value');
            $this->vat7_amount = $sum_of_amountPaid_minus_wht - $vat7_value ;
            $total = $total + $vat7_value;
            
        }

        $dAmount = null;
        $description = null;
        if($deductions_from_form == "on")
        {
            $dAmount = $request->float('dAmount');
            $description = $request->input('description');
        }
        // dd($sum_of_amountPaid_minus_wht, $total);
        // end of if the payment has with holding turned on

        // dd( $wth_from_form, $vat_from_form, $wht_amount, $this->vat7_amount, $vat7_value); 
        $invoiceTotal = Str::of($invoice_data->total)->toFloat();
        $invoiceBalance = Str::of($invoice_data->balance)->toFloat();
        $check1 =  $invoiceTotal + $invoiceBalance;
        $check2 = $invoiceBalance - $total - $dAmount ;

         //    $fields = Field::pluck('id');
        $client = Client::findOrFail($client_id);
        // dd($client->field->id, $fields);
        $current_collection = Collection::where('field_id', $client->field->id)->latest()->first();

        // dd( $check1, $check2, $total );
        // if payment is one time and is complete //
        if($status == 'completed' && $check1 == $total + $dAmount )
        {
            // return "you're here! full payment one time payment" . " - advance - ". $advance_payment. " - " . "deduction ". $dAmount;

            // // create Receipt
               $receipt_id = $this->createReceipt( $invoice_id, $dAmount, $description, $advance_payment, $receipt_date, $vat7_value, $this->vat7_amount, $client_id, $from, $mode, $cheque_reference, $cheque_amount, $cheque_bank, $transfer_reference, $transfer_amount, $transfer_bank, $momo_transactin_id, $momo_amount, $cash_amount, $other_payment_descri, $other_payment_amnt, $user_id, $status, $total, $this->wht_amount, $sum_of_amountPaid_minus_wht, $image, $staff);

            //     // get balance
                $balance =  $invoiceTotal - $total - $dAmount;
                // dd($balance);
                // update the invoice status to completed and update the balance to 0
                $invoice_data->status = $status;
                $invoice_data->wht_amount = $invoice_data->wht_amount + $this->wht_amount;
                $invoice_data->amount_received = $invoice_data->amount_received + $sum_of_amountPaid_minus_wht;
                $invoice_data->vat7_value = $vat7_value;
                $invoice_data->vat7_amount = $this->vat7_amount;
                $invoiceBalance = $balance;
                $invoice_data->save();

                $transaction = new Transaction();
                $transaction->client_id = $client_id;
                $transaction->invoice_id = $invoice_id;
                $transaction->invoice_amount =  $invoiceTotal;
                $transaction->receipt_id = $receipt_id;
                $transaction->receipt_amount = $total + $dAmount;
                $transaction->balance = $balance;
                $transaction->status = $status;
                $transaction->save();

            // // CREATE A COLLECTION HERE.................
            $this->create_collection($current_collection, $receipt_id, $cash_amount, $momo_amount, $cheque_amount, $transfer_amount, $total, $client->field->id);
            // END OF CREATE COLLECTIONS //

            //     // select all transactions with this current invoice iD and assign value d to the checks culumn
            Transaction::where('invoice_id', $invoice_id)->update(['checks' => 'd']);



            // // ASSIGN TO A CATEGORY
            // return $invoice->invoice_month?->format('Y-m') . " / ". $receipt->receipt_month?->format('Y-m');
            $inv  = Carbon::parse($invoice_data->invoice_month);
            $created_receipt = Receipt::findOrfail($receipt_id);

                        //  CHECK IF CLIENT CATEGORY ALREADY EXIST FOR THE SAME MONTH
            $exists = category::where('client_id', $created_receipt->client_id)
                            ->whereMonth('category_month', $inv->month)
                            ->exists();

            if ($exists) {
                    return redirect()->route('receipt.show',['receipt' => $receipt_id])->with('warning', 'Receipt  Craeated Successfully, This Receipt Client Aleady Has an Assigned Category');

                    // return back()->with('error', 'This Receipt Client Has Aleady been Assigned a Category, Kindly Remove existing one from Categories Model and Create the Receipt againg : '. $created_receipt->client_id) ;                      
                }

            $rec  = Carbon::parse($created_receipt->receipt_month);
            // $client = Client::findOrFail($receipt->client_id);
            $category = new category();
            $client = Client::findOrfail($created_receipt->client_id);

            // 2. Condition: Same Month
            if ($rec->isSameMonth($inv) || $rec->lt($inv)) 
                
            {
                // ASSIGN TO CATEGORY A
                $category->name = 'Category A';
                $category->client_id = $created_receipt->client_id;
                $category->user_id = Auth::id();
                $category->category_month = $invoice_data->invoice_month;
                $category->save();

                // UPDATE CLIENT AND MONTH
                $client->category_id = $category->id;
                $client->category_name = 'Category A';
                $client->category_month = $invoice_data->invoice_month;
                $client->user_id = Auth::id();
                $client->save();
            }

            else
            {

                if ($rec->isSameMonth($inv->copy()->addMonth()) && $rec->day >= 1 && $rec->day <= 9) {
                //    ASSIGN TO CATEGORY B
                    $category->name = 'Category B';
                    $category->client_id = $created_receipt->client_id;
                    $category->user_id = Auth::id();
                    $category->category_month = $invoice_data->invoice_month;
                    $category->save();

                    // UPDATE CLIENT AND MONTH
                    $client->category_id = $category->id;
                    $client->category_name = 'Category B';
                    $client->category_month = $invoice_data->invoice_month;
                    $client->user_id = Auth::id();
                    $client->save();
                }

                // 4. Condition: 10th to 15th of the following month
                if ($rec->isSameMonth($inv->copy()->addMonth()) && $rec->day >= 10 && $rec->day <= 15) {
                //    ASSIGN TO CATEGORY C
                    $category->name = 'Category C';
                    $category->client_id = $created_receipt->client_id;
                    $category->user_id = Auth::id();
                    $category->category_month = $invoice_data->invoice_month;
                    $category->save();  
                    
                    // UPDATE CLIENT AND MONTH
                    $client->category_id = $category->id;
                    $client->category_name = 'Category C';
                    $client->category_month = $invoice_data->invoice_month;
                    $client->user_id = Auth::id();
                    $client->save();
                }

                // 5. Condition: 16th to 20th of the following month
                if ($rec->isSameMonth($inv->copy()->addMonth()) && $rec->day >= 16 && $rec->day <= 25) {
                //    ASSIGN TO CATEGORY D
                    $category->name = 'Category D';
                    $category->client_id = $created_receipt->client_id;
                    $category->user_id = Auth::id();
                    $category->category_month = $invoice_data->invoice_month;
                    $category->save();   
                    
                    // UPDATE CLIENT AND MONTH
                    $client->category_id = $category->id;
                    $client->category_name = 'Category D';
                    $client->category_month = $invoice_data->invoice_month;
                    $client->user_id = Auth::id();
                    $client->save();
                }

                // return false; 
                
            }
              // // END ASSIGN TO A CATEGORY



                // return redirect('receipt')->with('primary', 'Receipt created Successfully');
            return redirect()->route('receipt.show',['receipt' => $receipt_id])->with('primary', 'Receipt  Craeated Successfully');

        }
        elseif($status == 'completed' && $total - $dAmount > $invoiceTotal &&  $invoiceBalance == 0 )
        {
            // return "you're here! full payment one time payment" . " - advance - ". $advance_payment. " - " . "deduction ". $dAmount;

            // // create Receipt
               $receipt_id = $this->createReceipt( $invoice_id, $dAmount, $description, $advance_payment, $receipt_date, $vat7_value, $this->vat7_amount, $client_id, $from, $mode, $cheque_reference, $cheque_amount, $cheque_bank, $transfer_reference, $transfer_amount, $transfer_bank, $momo_transactin_id, $momo_amount, $cash_amount, $other_payment_descri, $other_payment_amnt, $user_id, $status, $total, $this->wht_amount, $sum_of_amountPaid_minus_wht, $image, $staff);

            //     // get balance
                $balance =  $invoiceTotal - $total - $dAmount;
                // dd($balance);
                // update the invoice status to completed and update the balance to 0
                $invoice_data->status = $status;
                $invoice_data->wht_amount = $invoice_data->wht_amount + $this->wht_amount;
                $invoice_data->amount_received = $invoice_data->amount_received + $sum_of_amountPaid_minus_wht;
                $invoice_data->vat7_value = $vat7_value;
                $invoice_data->vat7_amount = $this->vat7_amount;
                $invoiceBalance = $balance;
                $invoice_data->save();

                $transaction = new Transaction();
                $transaction->client_id = $client_id;
                $transaction->invoice_id = $invoice_id;
                $transaction->invoice_amount =  $invoiceTotal;
                $transaction->receipt_id = $receipt_id;
                $transaction->receipt_amount = $total + $dAmount;
                $transaction->balance = $balance;
                $transaction->status = $status;
                $transaction->save();

            // // CREATE A COLLECTION HERE.................
            $this->create_collection($current_collection, $receipt_id, $cash_amount, $momo_amount, $cheque_amount, $transfer_amount, $total, $client->field->id);
            // END OF CREATE COLLECTIONS //

            //     // select all transactions with this current invoice iD and assign value d to the checks culumn
            Transaction::where('invoice_id', $invoice_id)->update(['checks' => 'd']);



            // // ASSIGN TO A CATEGORY
            // return $invoice->invoice_month?->format('Y-m') . " / ". $receipt->receipt_month?->format('Y-m');
            $inv  = Carbon::parse($invoice_data->invoice_month);
            $created_receipt = Receipt::findOrfail($receipt_id);

                        //  CHECK IF CLIENT CATEGORY ALREADY EXIST FOR THE SAME MONTH
            $exists = category::where('client_id', $created_receipt->client_id)
                            ->whereMonth('category_month', $inv->month)
                            ->exists();

            if ($exists) {
                    return redirect()->route('receipt.show',['receipt' => $receipt_id])->with('warning', 'Receipt  Craeated Successfully, This Receipt Client Aleady Has an Assigned Category');

                    // return back()->with('error', 'This Receipt Client Has Aleady been Assigned a Category, Kindly Remove existing one from Categories Model and Create the Receipt againg : '. $created_receipt->client_id) ;                      
                }

            $rec  = Carbon::parse($created_receipt->receipt_month);
            // $client = Client::findOrFail($receipt->client_id);
            $category = new category();
            $client = Client::findOrfail($created_receipt->client_id);

            // 2. Condition: Same Month
            if ($rec->isSameMonth($inv) || $rec->lt($inv)) 
                
            {
                // ASSIGN TO CATEGORY A
                $category->name = 'Category A';
                $category->client_id = $created_receipt->client_id;
                $category->user_id = Auth::id();
                $category->category_month = $invoice_data->invoice_month;
                $category->save();

                // UPDATE CLIENT AND MONTH
                $client->category_id = $category->id;
                $client->category_name = 'Category A';
                $client->category_month = $invoice_data->invoice_month;
                $client->user_id = Auth::id();
                $client->save();
            }

            else
            {

                if ($rec->isSameMonth($inv->copy()->addMonth()) && $rec->day >= 1 && $rec->day <= 9) {
                //    ASSIGN TO CATEGORY B
                    $category->name = 'Category B';
                    $category->client_id = $created_receipt->client_id;
                    $category->user_id = Auth::id();
                    $category->category_month = $invoice_data->invoice_month;
                    $category->save();

                    // UPDATE CLIENT AND MONTH
                    $client->category_id = $category->id;
                    $client->category_name = 'Category B';
                    $client->category_month = $invoice_data->invoice_month;
                    $client->user_id = Auth::id();
                    $client->save();
                }

                // 4. Condition: 10th to 15th of the following month
                if ($rec->isSameMonth($inv->copy()->addMonth()) && $rec->day >= 10 && $rec->day <= 15) {
                //    ASSIGN TO CATEGORY C
                    $category->name = 'Category C';
                    $category->client_id = $created_receipt->client_id;
                    $category->user_id = Auth::id();
                    $category->category_month = $invoice_data->invoice_month;
                    $category->save();  
                    
                    // UPDATE CLIENT AND MONTH
                    $client->category_id = $category->id;
                    $client->category_name = 'Category C';
                    $client->category_month = $invoice_data->invoice_month;
                    $client->user_id = Auth::id();
                    $client->save();
                }

                // 5. Condition: 16th to 20th of the following month
                if ($rec->isSameMonth($inv->copy()->addMonth()) && $rec->day >= 16 && $rec->day <= 25) {
                //    ASSIGN TO CATEGORY D
                    $category->name = 'Category D';
                    $category->client_id = $created_receipt->client_id;
                    $category->user_id = Auth::id();
                    $category->category_month = $invoice_data->invoice_month;
                    $category->save();   
                    
                    // UPDATE CLIENT AND MONTH
                    $client->category_id = $category->id;
                    $client->category_name = 'Category D';
                    $client->category_month = $invoice_data->invoice_month;
                    $client->user_id = Auth::id();
                    $client->save();
                }

                // return false; 
                
            }
              // // END ASSIGN TO A CATEGORY



                // return redirect('receipt')->with('primary', 'Receipt created Successfully');
            return redirect()->route('receipt.show',['receipt' => $receipt_id])->with('primary', 'Receipt  Craeated Successfully');

        }
        elseif($status == 'completed' && $check2 <= 0  &&  $invoiceBalance > 0 )
        {

            // return "you're here! full payment after part payment" . "- advance - ". $advance_payment. " - " . "deduction ". $dAmount;
            $receipt_id = $this->createReceipt($invoice_id, $dAmount, $description, $advance_payment, $receipt_date, $vat7_value, $this->vat7_amount, $client_id, $from, $mode, $cheque_reference, $cheque_amount, $cheque_bank, $transfer_reference, $transfer_amount, $transfer_bank, $momo_transactin_id, $momo_amount, $cash_amount, $other_payment_descri, $other_payment_amnt, $user_id, $status, $total, $this->wht_amount, $sum_of_amountPaid_minus_wht, $image, $staff);

            // get balance
            $balance = $invoiceBalance - ($total + $dAmount); 

            // update the invoice status from partpayment(uncomplete) to complete and update the balance to 0
            $invoice_data->balance = $balance;
            $invoice_data->wht_amount = $invoice_data->wht_amount + $this->wht_amount;
            $invoice_data->amount_received = $invoice_data->amount_received + $sum_of_amountPaid_minus_wht;
            $invoice_data->status = $status;
            $invoice_data->vat7_value = $vat7_value;
            $invoice_data->vat7_amount = $this->vat7_amount;
            $invoice_data->save();


            $transaction = new Transaction();
            $transaction->client_id = $client_id;
            $transaction->invoice_id = $invoice_id;
            $transaction->invoice_amount =  $invoiceTotal;
            $transaction->receipt_id = $receipt_id;
            $transaction->receipt_amount = $total + $dAmount;
            $transaction->balance = $balance;
            $transaction->status = $status;
            $transaction->save();

            // CREATE A COLLECTION HERE.................
            $this->create_collection($current_collection, $receipt_id, $cash_amount, $momo_amount, $cheque_amount, $transfer_amount, $total, $client->field->id);

            // // select all transactions with this current invoice iD and assign value D to the checks culumn
            Transaction::where('invoice_id', $invoice_id)->update(['checks' => 'd']);

            // // ASSIGN TO A CATEGORY
            // return $invoice->invoice_month?->format('Y-m') . " / ". $receipt->receipt_month?->format('Y-m');
            $inv  = Carbon::parse($invoice_data->invoice_month);
            $created_receipt = Receipt::findOrfail($receipt_id);
            $rec  = Carbon::parse($created_receipt->receipt_month);

                        //  CHECK IF CLIENT CATEGORY ALREADY EXIST FOR THE SAME MONTH
            $exists = category::where('client_id', $created_receipt->client_id)
                            ->whereMonth('category_month', $inv->month)
                            ->exists();

            if ($exists) {
                    return redirect()->route('receipt.show',['receipt' => $receipt_id])->with('warning', 'Receipt  Craeated Successfully, This Receipt Client Aleady Has an Assigned Category');

                    // return back()->with('error', 'This Receipt Client Has Aleady been Assigned a Category, Kindly Remove existing one from Categories Model and Create the Receipt againg : '. $created_receipt->client_id) ;           
                }


            // $client = Client::findOrFail($receipt->client_id);
            $category = new category();
            $client = Client::findOrfail($created_receipt->client_id);

            // 2. Condition: Same Month
            if ($rec->isSameMonth($inv) || $rec->lt($inv)) 
                
            {
                // ASSIGN TO CATEGORY A
                $category->name = 'Category A';
                $category->client_id = $created_receipt->client_id;
                $category->user_id = Auth::id();
                $category->category_month = $invoice_data->invoice_month;
                $category->save();

                // UPDATE CLIENT AND MONTH
                $client->category_id = $category->id;
                $client->category_name = 'Category A';
                $client->category_month = $invoice_data->invoice_month;
                $client->user_id = Auth::id();
                $client->save();
            }

            else
            {

                if ($rec->isSameMonth($inv->copy()->addMonth()) && $rec->day >= 1 && $rec->day <= 9) {
                //    ASSIGN TO CATEGORY B
                    $category->name = 'Category B';
                    $category->client_id = $created_receipt->client_id;
                    $category->user_id = Auth::id();
                    $category->category_month = $invoice_data->invoice_month;
                    $category->save();

                    // UPDATE CLIENT AND MONTH
                    $client->category_id = $category->id;
                    $client->category_name = 'Category B';
                    $client->category_month = $invoice_data->invoice_month;
                    $client->user_id = Auth::id();
                    $client->save();
                }

                // 4. Condition: 10th to 15th of the following month
                if ($rec->isSameMonth($inv->copy()->addMonth()) && $rec->day >= 10 && $rec->day <= 15) {
                //    ASSIGN TO CATEGORY C
                    $category->name = 'Category C';
                    $category->client_id = $created_receipt->client_id;
                    $category->user_id = Auth::id();
                    $category->category_month = $invoice_data->invoice_month;
                    $category->save();  
                    
                    // UPDATE CLIENT AND MONTH
                    $client->category_id = $category->id;
                    $client->category_name = 'Category C';
                    $client->category_month = $invoice_data->invoice_month;
                    $client->user_id = Auth::id();
                    $client->save();
                }

                // 5. Condition: 16th to 20th of the following month
                if ($rec->isSameMonth($inv->copy()->addMonth()) && $rec->day >= 16 && $rec->day <= 25) {
                //    ASSIGN TO CATEGORY D
                    $category->name = 'Category D';
                    $category->client_id = $created_receipt->client_id;
                    $category->user_id = Auth::id();
                    $category->category_month = $invoice_data->invoice_month;
                    $category->save();   
                    
                    // UPDATE CLIENT AND MONTH
                    $client->category_id = $category->id;
                    $client->category_name = 'Category D';
                    $client->category_month = $invoice_data->invoice_month;
                    $client->user_id = Auth::id();
                    $client->save();
                }
                
            }
               // // END ASSIGN TO A CATEGORY

            // return redirect('receipt')->with('success', 'Receipt created Successfully');
            return redirect()->route('receipt.show',['receipt' => $receipt_id])->with('primary', 'Receipt  Craeated Successfully');

        }
        else {
            // return "you're here! part payment " . " - advance - ". $advance_payment. " - " . "deduction ". $dAmount;

            // // get the balance of the invoice
            if($invoiceBalance > 0.00)
            {
                $balance = $invoiceBalance - $total - $dAmount ;
            }
            else{
                // get the balance of the invoice
                $balance =  $invoiceTotal - $total - $dAmount ;
            }


            // dd($balance, $invoice_data->balance, $invoice_data->total, $total, $dAmount);

            // create a receipt
            // $sum_of_amountPaid_minus_wht = null;
            $receipt_id = $this->createReceipt($invoice_id, $dAmount, $description, $advance_payment, $receipt_date, $vat7_value, $this->vat7_amount, $client_id, $from, $mode, $cheque_reference, $cheque_amount, $cheque_bank, $transfer_reference, $transfer_amount, $transfer_bank, $momo_transactin_id, $momo_amount, $cash_amount,$other_payment_descri, $other_payment_amnt, $user_id, $status, $total, $this->wht_amount, $sum_of_amountPaid_minus_wht, $image, $staff);

            // update the invoice status to partpayment(uncomplete) and update the balance to current balance
            $invoice_data->balance = $balance;
            $invoice_data->wht_amount = $invoice_data->wht_amount + $this->wht_amount;
            $invoice_data->amount_received = $invoice_data->amount_received + $sum_of_amountPaid_minus_wht;
            $invoice_data->status = $status;
            $invoice_data->vat7_value = $vat7_value;
            $invoice_data->vat7_amount = $this->vat7_amount;
            $invoice_data->save();

            // create a new transaction
            $transaction = new Transaction();
            $transaction->client_id = $client_id;
            $transaction->invoice_id = $invoice_id;
            $transaction->invoice_amount =  $invoiceTotal;
            $transaction->receipt_id = $receipt_id;
            $transaction->receipt_amount = $total + $dAmount;
            $transaction->balance = $balance;
            $transaction->status = $status;
            $transaction->save();
            // // echo "uncompleted " . " " . $invoice_data->total - $total ." ". "Balance is ";

            // CREATE A COLLECTION HERE.................
            $this->create_collection($current_collection, $receipt_id, $cash_amount, $momo_amount, $cheque_amount, $transfer_amount, $total, $client->field->id );


            // // ASSIGN TO A CATEGORY
            // return $invoice->invoice_month?->format('Y-m') . " / ". $receipt->receipt_month?->format('Y-m');
            $inv  = Carbon::parse($invoice_data->invoice_month);
            $created_receipt = Receipt::findOrfail($receipt_id);
            $rec  = Carbon::parse($created_receipt->receipt_month);

                //  CHECK IF CLIENT CATEGORY ALREADY EXIST FOR THE SAME MONTH
            $exists = category::where('client_id', $created_receipt->client_id)
                            ->whereMonth('category_month', $inv->month)
                            ->exists();

            if ($exists) {
                    // return back()->with('error', 'This Receipt Client Has Aleady been Assigned a Category, Kindly Remove existing one from Categories Model and update the Receipt : '. $created_receipt->client_id) ;
                    return redirect()->route('receipt.show',['receipt' => $receipt_id])->with('warning', 'Receipt  Craeated Successfully, This Receipt Client Aleady Has an Assigned Category');
                }

            // $client = Client::findOrFail($receipt->client_id);
            $category = new category();
            $client = Client::findOrfail($created_receipt->client_id);

            // 2. Condition: Same Month
            if ($rec->isSameMonth($inv) || $rec->lt($inv)) 
                
            {
                // ASSIGN TO CATEGORY A
                $category->name = 'Category A';
                $category->client_id = $created_receipt->client_id;
                $category->user_id = Auth::id();
                $category->category_month = $invoice_data->invoice_month;
                $category->save();

                // UPDATE CLIENT AND MONTH
                $client->category_id = $category->id;
                $client->category_name = 'Category A';
                $client->category_month = $invoice_data->invoice_month;
                $client->save();
            }

            else
            {

                if ($rec->isSameMonth($inv->copy()->addMonth()) && $rec->day >= 1 && $rec->day <= 9) {
                //    ASSIGN TO CATEGORY B
                    $category->name = 'Category B';
                    $category->client_id = $created_receipt->client_id;
                    $category->user_id = Auth::id();
                    $category->category_month = $invoice_data->invoice_month;
                    $category->save();

                    // UPDATE CLIENT AND MONTH
                    $client->category_id = $category->id;
                    $client->category_name = 'Category B';
                    $client->category_month = $invoice_data->invoice_month;
                    $client->save();
                }

                // 4. Condition: 10th to 15th of the following month
                if ($rec->isSameMonth($inv->copy()->addMonth()) && $rec->day >= 10 && $rec->day <= 15) {
                //    ASSIGN TO CATEGORY C
                    $category->name = 'Category C';
                    $category->client_id = $created_receipt->client_id;
                    $category->user_id = Auth::id();
                    $category->category_month = $invoice_data->invoice_month;
                    $category->save();  
                    
                    // UPDATE CLIENT AND MONTH
                    $client->category_id = $category->id;
                    $client->category_name = 'Category C';
                    $client->category_month = $invoice_data->invoice_month;
                    $client->save();
                }

                // 5. Condition: 16th to 20th of the following month
                if ($rec->isSameMonth($inv->copy()->addMonth()) && $rec->day >= 16 && $rec->day <= 25) {
                //    ASSIGN TO CATEGORY D
                    $category->name = 'Category D';
                    $category->client_id = $created_receipt->client_id;
                    $category->user_id = Auth::id();
                    $category->category_month = $invoice_data->invoice_month;
                    $category->save();   
                    
                    // UPDATE CLIENT AND MONTH
                    $client->category_id = $category->id;
                    $client->category_name = 'Category D';
                    $client->category_month = $invoice_data->invoice_month;
                    $client->save();
                }

                // return false; 
                
            }
               // // END ASSIGN TO A CATEGORY

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


        return view('sales.receipt_edit', compact('receipt','wht','invoice','mode','status', 'assign_staff', 'user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReceiptRequest $request, Receipt $receipt)
    {
        
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
        $deductions_from_form = $request->input('deductions');   
        
        $dAmount = null;
        $description = null;
        if($deductions_from_form == "on")
        {
            $dAmount = $request->float('dAmount');
            $description = $request->input('description');
        }

        // sum all input payments
        $total = $cheque_amount + $momo_amount + $cash_amount + $transfer_amount + $other_payment_amnt;
        // $wht_amount = 0.00;
        $vat7_value = 0.00;
        $sum_of_amountPaid_minus_wht = 0.00;
        $wth_from_form = $request->input('wth');
        $vat_from_form = $request->input('vat');
        $dAmount = $request->float('dAmount');


        // if the payment has with holding turned on;
        if($wth_from_form == "on")
        {
               $this->wht_amount = $request->float('wht_amount');

               //  $this->wht_amount = $wht_amount;
               $sum_of_amountPaid_minus_wht = $total;
               $total = $total +  $this->wht_amount;
        }
        if($vat_from_form == "on")
        {
            //  $request->input('vat7_value');
            $vat7_value = $request->float('vat7_value');
            $this->vat7_amount = $sum_of_amountPaid_minus_wht - $vat7_value ;
            $total = $total + $vat7_value;
        }

        $advance_payment = null;
         if(isset($request->advance_payment) && $request->input('advance_payment') == "advance")
         {
            $advance_payment =  "advance";
         }


        $receipt->invoice_id = $request->input('invoice_id');
        $receipt->client_id = $request->input('client_id');
        $receipt->from = $request->input('from');
        $receipt->mode = $request->input('mode');
        $receipt->advance_payment = $advance_payment;
        $receipt->dAmount = $dAmount;
        $receipt->description = $description;
        $receipt->receipt_month = $request->input('receipt_month');
        $receipt->vat7_value = $vat7_value;
        $receipt->vat7_amount = $this->vat7_amount;

        $receipt->transfer_reference = $request->input('transfer_reference');
        $receipt->transfer_bank = $request->input('transfer_bank');

        $receipt->cheque_reference = $request->input('cheque_reference');
        $receipt->cheque_bank = $request->input('cheque_bank');

        $receipt->momo_transactin_id = $request->input('momo_transactin_id');
        $receipt->other_payment_descri = $request->input('other_payment_descri');
        $receipt->user_id = Auth::user()->id;
        $receipt->status = $request->input('status');
        $receipt->total = $total;
        $receipt->wht_amount = $this->wht_amount;
        $receipt->amount_received = $sum_of_amountPaid_minus_wht;
        $receipt->image = $image;
        $receipt->save();


        // // update the invoice 
        $invoice = Invoice::findorFail($receipt->invoice_id);
        // $balance = $invoice->balance - $total - $dAmount ;

        if($request->input('reset_balance') == 'reset')
        {
            $invoice->balance = 0;
            $invoice->status = 'unpaid';
            $invoice->save();

            return redirect()->back()->with('info', 'Invoice balances reset completed, update all receipt with #FWSSi'. $invoice->id);
        }

        $invoiceTotal = Str::of($invoice->total)->toFloat();
        $invoiceBalance = Str::of($invoice->balance)->toFloat();
        $check1 =   $invoiceTotal  + $invoiceBalance;
        $check2 = $invoiceBalance - $total - $dAmount ;

        // dd( $invoiceTotal, $invoiceBalance, $check1, $check2, $total, $dAmount);

        if($receipt->status == "completed" &&  $check1 == $total + $dAmount )
        {

            // // get balance
            $balance = $invoiceTotal - $total - $dAmount;


            $invoice->balance = $balance;
            $invoice->status = $request->input('status');
            $invoice->wht_amount = $this->wht_amount;
            $invoice->amount_received = $sum_of_amountPaid_minus_wht;
            $invoice->vat7_value = $vat7_value;
            $invoice->vat7_amount = $this->vat7_amount;
            $invoice->save();


            // update transaction
            Transaction::where('receipt_id', $receipt->id)->update([ 
                'receipt_amount' => $total + $dAmount,
                'status' => $request->input('status'),
                'balance' => $balance,
                'checks' => 'd',
            ]);


        }
        elseif($receipt->status == "completed" &&  $total - $dAmount > $invoiceTotal &&  $invoiceBalance == 0 )
        {
                        // // get balance
            $balance = $invoiceTotal - $total - $dAmount;


            $invoice->balance = $balance;
            $invoice->status = $request->input('status');
            $invoice->wht_amount = $this->wht_amount;
            $invoice->amount_received = $sum_of_amountPaid_minus_wht;
            $invoice->vat7_value = $vat7_value;
            $invoice->vat7_amount = $this->vat7_amount;
            $invoice->save();


            // update transaction
            Transaction::where('receipt_id', $receipt->id)->update([ 
                'receipt_amount' => $total + $dAmount,
                'status' => $request->input('status'),
                'balance' => $balance,
                'checks' => 'd',
            ]);
        }
        elseif($receipt->status == "completed" && $check2 <= 0  &&  $invoiceBalance > 0)
        {

            // get balance
            $balance = $invoiceBalance - ($total + $dAmount); 

            $invoice->balance = $balance;
            $invoice->status = $request->input('status');
            $invoice->wht_amount = $this->wht_amount;
            $invoice->amount_received = $sum_of_amountPaid_minus_wht;
            $invoice->vat7_value = $vat7_value;
            $invoice->vat7_amount = $this->vat7_amount;
            $invoice->save();

            // update transaction
            Transaction::where('receipt_id', $receipt->id)->update([ 
                'receipt_amount' => $total + $dAmount,
                'status' => $request->input('status'),
                'balance' => $balance,
                'checks' => 'd',
            ]);
            
        }
        elseif($receipt->status == "uncompleted")
        {

            // // get the balance of the invoice
            $balance = 0;
            if($invoiceBalance > 0.00)
            {
                $balance = $invoiceBalance - $total - $dAmount ;
            }
            elseif($invoice->status == 'completed')
                {
                    $balance = $invoiceTotal - $total - $dAmount ;
                }
            else{
                // get the balance of the invoice
                $balance = $invoiceTotal - $total - $dAmount ;
            }

            $invoice->balance = $balance;
            $invoice->status = $request->input('status');
            $invoice->wht_amount = $this->wht_amount;
            $invoice->amount_received = $sum_of_amountPaid_minus_wht;
            $invoice->vat7_value = $vat7_value;
            $invoice->vat7_amount = $this->vat7_amount;
            $invoice->save();

            // update transaction
            $transaction = Transaction::where('receipt_id', $receipt->id)->first();
            $transaction->receipt_amount = $total + $dAmount;
            $transaction->status = $request->input('status');
            $transaction->balance = $balance;
            $transaction->checks = null;
            $transaction->save();
            
        }

        // // REASSIGN TO A CATEGORY
        // return $invoice->invoice_month?->format('Y-m') . " / ". $receipt->receipt_month?->format('Y-m');
        $inv  = Carbon::parse($invoice->invoice_month);
        $rec  = Carbon::parse($receipt->receipt_month);
        // $client = Client::findOrFail($receipt->client_id);
        // dd($receipt->client_id, $receipt->receipt_month?->format('Y-m'));

        $category = category::where('client_id', $receipt->client_id)->whereMonth('category_month', $inv->month)->first();
        $client = Client::findOrfail($receipt->client_id);

        // dd($category);
        if($category && isset($category))
            {
                    // 2. Condition: Same Month
                    if ($rec->isSameMonth($inv) || $rec->lt($inv)) 
                        
                    {
                        // ASSIGN TO CATEGORY A
                        $category->name = 'Category A';
                        $category->client_id = $receipt->client_id;
                        $category->user_id = Auth::id();
                        $category->category_month = $invoice->invoice_month;
                        $category->save();

                        // UPDATE CLIENT AND MONTH
                        $client->category_name = 'Category A';
                        $client->category_month = $invoice->invoice_month;
                        $client->save();
                    }

                    else
                    {

                        if ($rec->isSameMonth($inv->copy()->addMonth()) && $rec->day >= 1 && $rec->day <= 9) {
                        //    ASSIGN TO CATEGORY B
                            $category->name = 'Category B';
                            $category->client_id = $receipt->client_id;
                            $category->user_id = Auth::id();
                            $category->category_month = $invoice->invoice_month;
                            $category->save();

                            // UPDATE CLIENT AND MONTH
                            $client->category_name = 'Category B';
                            $client->category_month = $invoice->invoice_month;
                            $client->save();
                        }

                        // 4. Condition: 10th to 15th of the following month
                        if ($rec->isSameMonth($inv->copy()->addMonth()) && $rec->day >= 10 && $rec->day <= 15) {
                        //    ASSIGN TO CATEGORY C
                            $category->name = 'Category C';
                            $category->client_id = $receipt->client_id;
                            $category->user_id = Auth::id();
                            $category->category_month = $invoice->invoice_month;
                            $category->save();  
                            
                            // UPDATE CLIENT AND MONTH
                            $client->category_name = 'Category C';
                            $client->category_month = $invoice->invoice_month;
                            $client->save();
                        }

                        // 5. Condition: 16th to 20th of the following month
                        if ($rec->isSameMonth($inv->copy()->addMonth()) && $rec->day >= 16 && $rec->day <= 25) {
                        //    ASSIGN TO CATEGORY D
                            $category->name = 'Category D';
                            $category->client_id = $receipt->client_id;
                            $category->user_id = Auth::id();
                            $category->category_month = $invoice->invoice_month;
                            $category->save();   
                            
                            // UPDATE CLIENT AND MONTH
                            $client->category_name = 'Category D';
                            $client->category_month = $invoice->invoice_month;
                            $client->save();
                        }

                        // return false; 
                        
                    }
                // // END ASSIGN TO A CATEGORY
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
        // dd($receipt);
        // REMOVE FROM COLLECTIONS

        // if receipt has been deposited, do not allow update   
        $collection = Collection::where('receipt_id', $receipt->id)->first();
        // dd($collection);
        if($collection?->status == 'deposited')
        {
            return redirect()->back()->with('error', 'Receipt has been Deposited and cannot be Deleted');
        }
        // elseif(empty($collection))
        // {
        //     // return redirect()->back()->with('error', 'Receipt has No Collection');
        //     // continue ;
        // }

       $collection?->delete();

        //   dd($deleteCollections);
        // if(isset($deleteCollections) && !empty($deleteCollections))
        // {

        // SET ALL TRANSACTIONS TO EMPTY STRING
        //  $invoice =  Invoice::where('receipt_id',  $receipt->id)->get();
        //  dd($invoice);
         Transaction::where('invoice_id', $receipt->invoice->id)->update(['checks' => '']);
        // DELETE FROM TRANSACTION
        Transaction::where('receipt_id', $receipt->id)->delete();

        // UPDATE THE INVOICE TO DEFAULT
         Invoice::where('id', $receipt->invoice->id)->update(['status' => 'unpaid', 'balance' => 0.00 ]); 
        // }

        // DELETE THE RECEIPT
         $receipt->delete();

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


        return view('sales.receiptCreate', compact('invoice', 'wht_rate', 'mode', 'status', 'assign_staff'));
    }




    /**
     * @param int $invoice_id
     * @param float $dAmount
     * @param string $description
     * @param float $advance_payment
     * @param string $receipt_date
     * @param float $vat7_value
     * @param float $vat7_amount
     * @param int $client_id
     * @param string $from
     * @param string $mode
     * @param string $transfer_reference
     * @param float $transfer_amount
     * @param string $transfer_bank
     * @param string $cheque_reference
     * @param float $cheque_amount
     * @param string $cheque_bank
     * @param string $momo_transactin_id
     * @param float $momo_amount
     * @param float $cash_amount
     * @param string $other_payment_descri
     * @param float $other_payment_amnt
     * @param int $user_id
     * @param string $status
     * @param float $total
     * @param float $wht_amount
     * @param float $sum_of_amountPaid_minus_wht
     * @param string|null $image
     * @return int
     */
    public function createReceipt($invoice_id, $dAmount, $description, $advance_payment, $receipt_date, $vat7_value, $vat7_amount, $client_id, $from, $mode, $transfer_reference, $transfer_amount, $transfer_bank, $cheque_reference, $cheque_amount, $cheque_bank, $momo_transactin_id, $momo_amount, $cash_amount, $other_payment_descri, $other_payment_amnt, $user_id, $status, $total, $wht_amount, $sum_of_amountPaid_minus_wht, $image, $staff)
    {

        // dd($other_payment_amnt, $other_payment_descri);
        $newReceipt = new Receipt();

        $newReceipt->invoice_id = $invoice_id;

        $newReceipt->dAmount = $dAmount;
        $newReceipt->description = $description;
        $newReceipt->advance_payment = $advance_payment;
        $newReceipt->receipt_month = $receipt_date;
        $newReceipt->vat7_value = $vat7_value;
        $newReceipt->vat7_amount = $vat7_amount;

        $newReceipt->client_id = $client_id;
        $newReceipt->from = $from;
        $newReceipt->mode = $mode;


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
        
        if(Auth::user()->role?->id == '2')
            {
               $newReceipt->ho_status = 'approved';
               $newReceipt->user_id2 = $user_id;
            }
        if(Auth::user()->department?->id == '7' && Auth::user()->role?->id == '3')
            {
               $newReceipt->ho_status = 'pending';
               $newReceipt->user_id2 = $staff;

               $newReceipt->bran_status = 'approved';
               $newReceipt->user_id1 = $user_id;
            }
        if(Auth::user()->department?->id == '7' && Auth::user()->role?->id == '27')
            {
               $newReceipt->bran_status = 'pending';
               $newReceipt->user_id1 = $staff;

               $newReceipt->coll_status = 'pending';

            }

        $newReceipt->save();

        return $newReceipt->id;
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
        $botweRmountReceived = $botwe->sum('amount_received');
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
                    if($kumasi->client->field_id == '4')
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
        $submitValue = $request->input('submit'); 
        $declineValue = $request->input('decline'); 
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


            if( $submitValue == 'branch' )
                
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

            if($submitValue == 'headOffice' )
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


            if($declineValue == 'headOffice' )
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

<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\employee;
use App\Models\Field;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Service;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
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
        $user = Auth::user();

        $clients = Client::all();
        $clientIds = Client::pluck('id');
        $clientsCount = count($clients);
        $fields = Field::all();
        $totalGuards = employee::where('department_id', '6')->count();
       
        // $clientGuards = DB::table('employees')
        //                         ->where('department_id', '6')
        //                         // ->select('client_id')
        //                         ->whereIn('client_id', $clientIds)
        //                         ->groupBy('client_id')
        //                         ->get([DB::select('select * from clients'), DB::raw('count(*) as total_employees')]);
       
    //    $salariesTaxes = Salary::where('salary_month', $date)->where('tax', '>', 0)->whereIn('field_id', $fields->pluck('id')->toArray())->groupBy('field_id')->get(['field_id', DB::raw('SUM(cost_to_company) as paid'), DB::raw('SUM(tax) as tax'),  DB::raw('COUNT(*) as total_employees')]);

        // $clientGuards = Employee::whereIn('client_id', $clientIds)
        //                                 ->get()
        //                                 ->groupBy('client_id');

        // $clientGuards = Client::whereIn('id', $clientIds)
        //                 ->with('employees') // Assuming Client hasMany Employee
        //                 ->get()
        //                 ->groupBy('id');

        // dd($clientGuards);
        // foreach ($clientGuards as $data)
        //     {
        //         // dd($client);
        //         // echo $client->id ."<br>". "<br>"; ->name . " / ". $client->employees->count
        //         foreach($data as $client)
        //             {
        //                 echo $client->id . " / ". $client->employees?->count() ."<br>". "<br>";
        //             }
        //     }

        $accra = Client::where('field_id', 1)->get();
        $accraCount = count($accra);
        $totalaccraGuards = employee::where('department_id', '6')->where('field_id', 1)->count();


        $botwe = Client::where('field_id', 2)->get();
        $botweCount = count($botwe);
        $totalbotweGuards = employee::where('department_id', '6')->where('field_id', 2)->count();


        $tema = Client::where('field_id', 3)->get();
        $temaCount = count($tema);
        $totaltemaGuards = employee::where('department_id', '6')->where('field_id', 3)->count();


        $takoradi = Client::where('field_id', 4)->get();
        $takoradiCount = count($takoradi);
        $totaltakoradiGuards = employee::where('department_id', '6')->where('field_id', 4)->count();


        $koforidua = Client::where('field_id', 5)->get();
        $koforiduaCount = count($koforidua);
        $totalkoforiduaGuards = employee::where('department_id', '6')->where('field_id', 5)->count();


        $kumasi = Client::where('field_id', 6)->get();
        $kumasiCount = count($kumasi);
        $totalkumasiGuards = employee::where('department_id', '6')->where('field_id', 6)->count();


        $shyhills = Client::where('field_id', 7)->get();
        $shyhillsCount = count($shyhills);
        $totalshyhillsGuards = employee::where('department_id', '6')->where('field_id', 7)->count();

        // dd($accraCount, $botweCount, $temaCount, $takoradiCount, $koforiduaCount, $kumasiCount);

        if($user->role->name == 'Invoice' || $user->role->name == 'Finance Manager' || $user->role->name == 'Manager' )
        {
            return view('clients.index', compact('totalaccraGuards', 'totalbotweGuards', 'totaltemaGuards', 'totaltakoradiGuards', 'totalkoforiduaGuards', 'totalkumasiGuards', 'totalshyhillsGuards', 'clients', 'clientsCount', 'fields', 'accraCount', 'botweCount', 'temaCount', 'takoradiCount', 'koforiduaCount', 'kumasiCount', 'shyhills', 'shyhillsCount', 'totalGuards'));

        }elseif($user->field?->name == 'Accra')
        {
            $clients = $accra;
            return view('clients.index', compact('clients', 'clientsCount', 'fields', 'accraCount'));
        }

        if ($user->field?->name == 'Botwe')
        {
            $clients = $botwe;
            return view('clients.index', compact('clients', 'clientsCount', 'fields', 'botweCount', 'shyhills', 'shyhillsCount'));

        }


        if ($user->field?->name == 'Tema')
        {
            $clients = $tema;
            return view('clients.index', compact('clients', 'clientsCount', 'fields', 'temaCount'));

        }

        if ($user->field?->name == 'Takoradi')
        {
            $clients = $takoradi;
            return view('clients.index', compact('clients', 'clientsCount', 'fields', 'takoradiCount'));
        }


        if ($user->field?->name == 'Koforidua')
        {
            $clients = $koforidua;
            return view('clients.index', compact('clients', 'clientsCount', 'fields', 'koforiduaCount'));
        }


        if ($user->field?->name == 'Kumasi')
        {
            $clients = $kumasi;
            return view('clients.index', compact('clients', 'clientsCount', 'fields', 'kumasiCount'));
        }

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $fields = Field::all();
        return view('clients.create', compact('fields'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request)
    {
        //
        // dd($request->all());
        $client = new Client();
        $client->create([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'phone_number1' => $request->phone_number1,
            'business_name' => $request->business_name,
            'address' => $request->address,
            'field_id' => $request->field_id,
            'branch' => $request->branch,
            'rate' => $request->rate,
            'guards' => $request->guards,
            'start_date' => $request->start_date,
            'scope_of_work' => $request->scope_of_work,
            'state_institution' => $request->state_institution,
            'status' => 'pending',
            'user_id' => Auth::id(),    
        ]);
        return redirect('client')->with('info', $client->id. ' '.'Client Added Sucessfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        $total_invoice_amount = Invoice::where('client_id', $client->id)->sum('total');
        $outstanding = Invoice::where('client_id', $client->id)->where('status', 'unpaid')->sum('total');
        $balance_outstanding = Invoice::where('client_id', $client->id)->where('balance', '>', 0.00)->where('status', 'uncompleted')->sum('balance');

        $total_wth_amount = Receipt::where('client_id', $client->id)->sum('wht_amount');
        $total_wth_amount_received = Receipt::where('client_id', $client->id)->sum('amount_received');

        // dd($outstanding, $balance_outstanding);
        // $receipts = Receipt::where('client_id', $client->id)->get();
        // $receiptsCount = count($receipts);

        $transactions = Transaction::where('client_id', $client->id)->get();
        $transactionsCount = count($transactions);
        return view('clients.show', compact('client','transactionsCount', 'outstanding', 'balance_outstanding', 'transactions', 'total_invoice_amount', 'total_wth_amount', 'total_wth_amount_received'));
    }


    /**
     * Get clients statement of Accounts
     */

    public function statementOfAccount (Client $client)
    {
            // dd($client);
            // $previousInvoices = Invoice::where('client_id', $client->id)->sum('total');
            // dd($previousInvoices);
            // $previousPayments = Receipt::where('client_id', $client->id)->sum('total');
            // $openingBalance = $previousInvoices - $previousPayments;
            // dd($previousInvoices, $previousPayments, $openingBalance);

            $transactions = Transaction::where('client_id', $client->id)->get();

            // dd( $transactions);


            // $transactions->prepend((object)[
            //     'type' => 'Opening Balance',
            //     'amount' => $openingBalance,
            //     'date' => $startDate->format('Y-m-d'),
            // ]);
            $data = [
                'date' => [],
                'description' =>[],
                'details' => [],
                'debit' =>[],
                'credit'=>[],
                'balance'=>[]
            ];

            foreach($transactions as $key => $statement)
                {
                    if($statement->receipt_amount == null)
                        {
                            $data['date'][$key] = $statement->invoice?->invoice_month?->format('F, Y') ;
                            $data['debit'][$key] = $statement->invoice_amount;
                            $data['description'][] = 'Debit '. "<br>". "Invoice ID : ". $statement->invoice_id  ;
                            $data['balance'][] +=  $statement->invoice_amount ;
        
                            // if($statement->status == 'completed')
                            //     {
                            //         $data['credit'][] =  $statement->receipt_amount;
                            //         $data['balance'][] +=  $statement->invoice_amount - $statement->receipt_amount;
                            //     }
                            // $data['balance'] =  $statement->invoice_amount;
        
                        }
                    else
                        {
                            $data['date'][$key] = $statement->receipt?->receipt_month?->format('F l d, Y');
                            $data['description'][] = 'Credit'. "<br>". " Invoice Month : ". $statement->invoice?->invoice_month?->format('F, Y').  "<br>". "Invoice ID : ".$statement->invoice_id .   "<br>". " Invoice Amount : ". $statement->invoice_amount ;

                            if( $statement->receipt?->cash_amount > 0.00 )
                                {
                                    $data['details'][$key] =  "Cash ";
                                }
                            if( $statement->receipt?->momo_amount > 0.00 )
                                {
                                    $data['details'][$key] =  "MoMo ";
                                }
                            if( $statement->receipt?->transfer_amount > 0.00 )
                                {
                                    $data['details'][$key] =  "Transfer Bank : ". $statement->receipt?->transfer_bank . "<br> ". "Transfer Reference : ". $statement->receipt?->transfer_reference . "<br> ". "Amount : ". $statement->receipt?->transfer_amount . "<br>". "WTH : ". $statement->receipt?->wht_amount . "<br>". "VAT 7% :  ". $statement->receipt?->vat7_value;
                                }
                            if( $statement->receipt?->cheque_amount > 0.00 )
                                {
                                    $data['details'][$key] =  "Cheque Bank : ". $statement->receipt?->cheque_bank . "<br>". "Cheque Reference : ". $statement->receipt?->cheque_reference . "<br>". "Amount : ". $statement->receipt?->cheque_amount . "<br>". "WTH : ". $statement->receipt?->wht_amount . "<br>". "VAT 7% :  ". $statement->receipt?->vat7_value;
                                }
                            $credit = $statement->receipt?->cash_amount + $statement->receipt?->momo_amount + $statement->receipt?->transfer_amount + $statement->receipt?->cheque_amount + $statement->receipt?->other_payment_amnt + $statement->receipt?->dAmount + $statement->receipt?->wht_amount  ;
                            $data['credit'][$key] =  $credit;

                            $data['balance'][] -=  $credit;
                            //  $data['balance'] =  $statement->invoice_amount;
                        }

                }

            // dd( $data);

            // return $openingBalance;

            // $statementLines = $statementLines->sortBy('date');
           
            // $statementLines->each(function ($line) {
            //     if (isset($line->type) == 'Invoice') {
            //       echo  $line->type = 'Invoice' . " - ". $line->amount = $line->total . "<br>";
                
            //     } elseif (isset($line->Receipt) == 'Receipt') {
            //        echo $line->type = 'Receipt'. " - ". $line->amount = $line->amount_received . "<br>";
                    
            //     }
            // });


            // $client = Client::findOrFail($clientId);
            // $startDate = $request->input('start_date', now()->startOfMonth());
            // $endDate = $request->input('end_date', now()->endOfMonth());

            // Calculate opening balance 
            // $invoices = $client->invoices()->get();
            // $receipts = $client->receipts()->get();
            // $transactions = Transaction::where('client_id', $client->id)->get();
            // dd($invoices, $receipts);
            // $openingBalance = $previousInvoices - $previousPayments;

            // Fetch all invoices and payments within the specified date range
            // $invoices = $client->invoices()->whereBetween('date', [$startDate, $endDate])->get();
            // $transactions = $client->transactions()->whereBetween('date', [$startDate, $endDate])->get();

            // Combine and sort chronologically
            // $statementLines = $invoices->merge($receipts)->sortBy('invoice_month');
            // dd($statementLines);
            // foreach( $statementLines as $statement)
            //     {
            //         if($statement->count() <= 21 )
            //             {
            //                 echo $statement->id . " ". $statement->invoice_month?->format('F, Y').  ;
            //             }
            //     }   
            return view('clients.statement', compact('client', 'data'));
    }




    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        //dd($client);
        $fields = Field::all();
        $categories = ['Category A', 'Category B', 'Category C', 'Category D']; 
        // Employees assigned to this client (based on employee.client_id)
        $attachedEmployees = employee::where('client_id', $client->id)->get();

        // Available employees (not assigned to this client) — limit to guards (department_id = 6) and active status
        $availableEmployees = employee::where(function($q) use ($client) {
            $q->where('client_id', '!=', $client->id)->orWhereNull('client_id');
        })->where('department_id', 6)->where('status', 'Active')->get();

        return view('clients.edit', compact('client', 'fields', 'categories', 'attachedEmployees', 'availableEmployees'));
    }


    /**
     * Attach selected employees to a client (sets employee.client_id and pivot attach).
     */
    public function attachEmployees(\Illuminate\Http\Request $request, Client $client)
    {
        $employeeIds = $request->input('employees', []);
        if (empty($employeeIds)) {
            return back()->with('error', 'No employee selected to attach.');
        }

        foreach ($employeeIds as $id) {
            $emp = employee::find($id);
            if ($emp) {
                // set direct client_id for compatibility with existing code
                $emp->client_id = $client->id;
                $emp->save();
                // ensure pivot reflects association
                $emp->clients()->syncWithoutDetaching([$client->id]);
            }
        }

        return back()->with('success', 'Selected employees attached to client.');
    }


    /**
     * Detach selected employees from a client (removes pivot and clears employee.client_id where matching).
     */
    public function detachEmployees(\Illuminate\Http\Request $request, Client $client)
    {
        $employeeIds = $request->input('employees', []);
        if (empty($employeeIds)) {
            return back()->with('error', 'No employee selected to detach.');
        }

        foreach ($employeeIds as $id) {
            $emp = employee::find($id);
            if ($emp) {
                // remove pivot
                $emp->clients()->detach([$client->id]);
                // if the employee's client_id equals this client, clear it
                if ($emp->client_id == $client->id) {
                    $emp->client_id = null;
                    $emp->save();
                }
            }
        }

        return back()->with('success', 'Selected employees detached from client.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientRequest $request, Client $client)
    {
        //dd($request->all(), $client);
        $client->update($request->all());
        return redirect('client')->with('info', $client->id. ' '.'Client Updated Sucessfully');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        //->orWhere('status', 'uncompleted')
        $invoice = Invoice::where('client_id', $client->id)->where('status', 'unpaid')->get();
        // dd($invoice);
        if($invoice->isNotEmpty())
        {
            // dd($invoice);
            return back()->with('warning', 'Client Have an Unpaid Invoice');
        }else{

            $client->delete();
            return back()->with('error', 'Client Deleted Successfully');

        }

    }


    /**
     * Attach Guards to Cleint.
     */
    public function clientAttachGuards($client_id)
    {
    //    $guards =  employee::where('client_id',$id)->where('department_id', 6)->where('status', 'Active')->get();

    //    if($guards->isEmpty())
    //    {
    //         return back()->with('error', 'Client has no current Guards');
    //    }

    //    $clients = $guards->isNotEmpty() ? Client::all() : null ;

        return view('clients.GuardView');
    
    }

}

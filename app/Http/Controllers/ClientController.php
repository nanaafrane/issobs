<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\employee;
use App\Models\Field;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
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
        $clients = null;
        $fields = null;
        $totalGuards = null;

        if($user->role->name == 'Invoice' || $user->role->name == 'Finance Manager' || ($user->role->name == 'Manager' && $user->department->name == 'HR' ) )
        {
                $clients = Client::where('status', 'active')->where('ho_status', 'approved')->get();
                $standardClients = $clients->where('state_institution', null)->values();
                $stateClients = $clients->where('state_institution', 1)->values();

                $fields = Field::all();

                $totalGuards = employee::where('department_id', '6')->count();
            // return "You're the Manager";
            return view('clients.index', compact('clients', 'stateClients' ,'standardClients','fields','totalGuards'));


        }elseif($user->field?->name == 'Accra')
        {
            $clients = Client::where('field_id', 1)->where('ho_status', 'approved')->where('status', 'active')->get();
            $standardClients = $clients->where('state_institution', null)->values();
            $stateClients = $clients->where('state_institution', 1)->values();
            // $botweCount = count($clients);
            $totalGuards = employee::where('department_id', '6')->where('field_id', 1)->count();
            return view('clients.index', compact('clients', 'standardClients', 'stateClients'));
            // return "You're in Accra";

        }

        if ($user->field?->name == 'Botwe')
        {

            $clients = Client::where('field_id', 2)->where('ho_status', 'approved')->where('status', 'active')->get();
            $standardClients = $clients->where('state_institution', null)->values();
            $stateClients = $clients->where('state_institution', 1)->values();
            // $botweCount = count($clients);
            $totalGuards = employee::where('department_id', '6')->where('field_id', 2)->count();
            return view('clients.index', compact('clients', 'standardClients', 'stateClients'));

            // return "You're in Botwe";

        }

        if ($user->field?->name == 'Tema')
        {
            $clients = Client::whereIn('field_id', [3,7])->where('ho_status', 'approved')->where('status', 'active')->get();
            $standardClients = $clients->where('state_institution', null)->values();
            $stateClients = $clients->where('state_institution', 1)->values();
            // $botweCount = count($clients);
            $totalGuards = employee::where('department_id', '6')->whereIn('field_id', [3,7])->count();
            return view('clients.index', compact('clients', 'standardClients', 'stateClients'));
            // return "You're in Tema";

        }

        if ($user->field?->name == 'Takoradi')
        {
            $clients = Client::where('field_id', 4)->where('ho_status', 'approved')->where('status', 'active')->get();
            $standardClients = $clients->where('state_institution', null)->values();
            $stateClients = $clients->where('state_institution', 1)->values();
            // $botweCount = count($clients);
            $totalGuards = employee::where('department_id', '6')->where('field_id', 4)->count();
            return view('clients.index', compact('clients', 'standardClients', 'stateClients'));
            // return "You're in Takoradi";
        }

        if ($user->field?->name == 'Koforidua')
        {
            $clients = Client::where('field_id', 5)->where('ho_status', 'approved')->where('status', 'active')->get();
            $standardClients = $clients->where('state_institution', null)->values();
            $stateClients = $clients->where('state_institution', 1)->values();
            // $botweCount = count($clients);
            $totalGuards = employee::where('department_id', '6')->where('field_id', 5)->count();
            return view('clients.index', compact('clients', 'standardClients', 'stateClients'));
            // return "You're in Koforidua";
        }

        if ($user->field?->name == 'Kumasi')
        {
            $clients = Client::where('field_id', 6)->where('ho_status', 'approved')->where('status', 'active')->get();
            $standardClients = $clients->where('state_institution', null)->values();
            $stateClients = $clients->where('state_institution', 1)->values();
            // $botweCount = count($clients);
            $totalGuards = employee::where('department_id', '6')->where('field_id', 6)->count();
            return view('clients.index', compact('clients', 'standardClients', 'stateClients'));
            // return "You're in Kumasi";

        }

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $fields = null;
        $fieldsAll = Field::all();
        $user =  Auth::user();

        $staff =  User::all();
        $manager = $staff->where('department_id', '7')->where('role_id', '3');
        $invoice_manager = $staff->where('department_id', '1')->where('role_id', '1');
        $assign_staff = [];

        if($user->role->name == 'Admin Assistant')
            {
                $assign_staff = $manager;
            }

        if($user->role->name == 'Manager')
            {
                $assign_staff = $invoice_manager;
            }

        // dd($fieldsAll);
        // if($user->role?->name == 'Finance Manager')
        // {
            
        // }
        foreach($fieldsAll as $field )
        {
            if( $user->role?->name == 'Finance Manager' || $user->role?->name == 'Invoice' || ($user->department?->name == 'HR' && $user->role?->name == 'Manager') )
            {
                $fields = $fieldsAll;
                break;
            }
            if($user->field_id == '3')
            {
                $fields = $fieldsAll->whereIn('id', ['3','7'])->values();
                break;
            }
            if($field->name == $user->field?->name)
            {
                $fields[] = $field;
            }
        }
        // dd($fields);
        // foreach($fields as $field)
        //     {
        //         echo $field->name . "<br>";
        //     }

        return view('clients.create', compact('fields', 'assign_staff'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request)
    {
        //
        // dd($request->all());
           $user_id = Auth::id();

           $client = new Client();
           $client->name = $request->name;
           $client->phone_number = $request->phone_number;
           $client->phone_number1 = $request->phone_number;
           $client->business_name = $request->business_name;
           $client->address = $request->address;
           $client->field_id = $request->field_id;
           $client->branch = $request->branch;
           $client->rate = $request->rate;
           $client->guards = $request->guards;
           $client->start_date = $request->start_date;
           $client->scope_of_work = $request->scope_of_work;
           $client->state_institution = $request->state_institution;
           $client->status = 'pending';
           $client->user_id = $user_id;


            if(Auth::user()->role?->id == '1')
                {
                    // return "You are at head office";

                $client->ho_status = 'approved';
                $client->user_id2 = $user_id;
                }
            if(Auth::user()->department?->id == '7' && Auth::user()->role?->id == '3')
                {
                    // return "You are a Manager";

                $client->ho_status = 'pending';
                $client->user_id2 = $request->staff;

                $client->bran_status = 'approved';
                $client->user_id1 = $user_id;
                }
            if(Auth::user()->department?->id == '7' && Auth::user()->role?->id == '27')
                {
                    // return "You are an Admin Assistance";
                $client->bran_status = 'pending';
                $client->user_id1 = $request->staff;
                $client->coll_status = 'pending';

                }
            $client->save();
        // $client->create([
        //     'name' => $request->name,
        //     'phone_number' => $request->phone_number,
        //     'phone_number1' => $request->phone_number1,
        //     'business_name' => $request->business_name,
        //     'address' => $request->address,
        //     'field_id' => $request->field_id,
        //     'branch' => $request->branch,
        //     'rate' => $request->rate,
        //     'guards' => $request->guards,
        //     'start_date' => $request->start_date,
        //     'scope_of_work' => $request->scope_of_work,
        //     'state_institution' => $request->state_institution,


        //     'status' => 'pending',
        //     'user_id' => Auth::id(),    
        // ]);


        return redirect('clientPending')->with('info', $client->id. ' '.'Client Added Sucessfully, awaiting approval !.');
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
     * Display a listing of the resource.
     */
    public function clientPending()
    {
  
        $user = Auth::user();
        $clients = null;
        $fields = null;
        $totalGuards = null;
    
    
         if($user->role->name == 'Invoice' || $user->role->name == 'Finance Manager' || ($user->role->name == 'Manager' && $user->department->name == 'HR' ) )
        {
                $clients = Client::where('status', '!=','active')->where('ho_status', 'pending')->orwhere('ho_status', null)->get();


                $fields = Field::all();

                $totalGuards = employee::where('department_id', '6')->count();
            // return "You're the Manager";
            return view('clients.pending', compact('clients','fields','totalGuards'));


        }elseif($user->field?->name == 'Accra')
        {
            $clients = Client::where('field_id', 1)->where('status', '!=','active')->where('ho_status', 'pending')->orwhere('ho_status', null)->get();

            $totalGuards = employee::where('department_id', '6')->where('field_id', 1)->count();
            return view('clients.pending', compact('clients'));
            // return "You're in Accra";

        }

        if ($user->field?->name == 'Botwe')
        {

            $clients = Client::where('field_id', 2)->where('status', '!=','active')->where('ho_status', 'pending')->orwhere('ho_status', null)->get();

            $totalGuards = employee::where('department_id', '6')->where('field_id', 2)->count();
            return view('clients.pending', compact('clients'));

            // return "You're in Botwe";

        }

        if ($user->field?->name == 'Tema')
        {
            $clients = Client::whereIn('field_id', [3,7])->where('status', '!=','active')->where('ho_status', 'pending')->orwhere('ho_status', null)->get();

            $totalGuards = employee::where('department_id', '6')->whereIn('field_id', [3,7])->count();
            return view('clients.pending', compact('clients'));
            // return "You're in Tema";

        }

        if ($user->field?->name == 'Takoradi')
        {
            $clients = Client::where('field_id', 4)->where('status', '!=','active')->where('ho_status', 'pending')->orwhere('ho_status', null)->get();

            $totalGuards = employee::where('department_id', '6')->where('field_id', 4)->count();
            return view('clients.pending', compact('clients'));
            // return "You're in Takoradi";
        }

        if ($user->field?->name == 'Koforidua')
        {
            $clients = Client::where('field_id', 5)->where('status', '!=','active')->where('ho_status', 'pending')->orwhere('ho_status', null)->get();

            $totalGuards = employee::where('department_id', '6')->where('field_id', 5)->count();
            return view('clients.pending', compact('clients'));
            // return "You're in Koforidua";
        }

        if ($user->field?->name == 'Kumasi')
        {
            $clients = Client::where('field_id', 6)->where('status', '!=','active')->where('ho_status', 'pending')->orwhere('ho_status', null)->get();

            $totalGuards = employee::where('department_id', '6')->where('field_id', 6)->count();
            return view('clients.pending', compact('clients'));
            // return "You're in Kumasi";

        }

 
    }


    /**
     * Display a listing of the resource.
     */
    public function clientTerminated()
    {

        $user = Auth::user();
        $clients = null;
        $fields = null;
        $totalGuards = null;
    
    
         if($user->role->name == 'Invoice' || $user->role->name == 'Finance Manager' || ($user->role->name == 'Manager' && $user->department->name == 'HR' ) )
        {
                $clients = Client::where('status', 'terminated')->where('ho_status', 'approved')->get();


                $fields = Field::all();

                $totalGuards = employee::where('department_id', '6')->count();
            // return "You're the Manager";
            return view('clients.terminate', compact('clients','fields','totalGuards'));


        }elseif($user->field?->name == 'Accra')
        {
            $clients = Client::where('field_id', 1)->where('status', 'terminated')->where('ho_status', 'approved')->get();

            $totalGuards = employee::where('department_id', '6')->where('field_id', 1)->count();
            return view('clients.terminate', compact('clients'));
            // return "You're in Accra";

        }

        if ($user->field?->name == 'Botwe')
        {

            $clients = Client::where('field_id', 2)->where('status', 'terminated')->where('ho_status', 'approved')->get();

            $totalGuards = employee::where('department_id', '6')->where('field_id', 2)->count();
            return view('clients.terminate', compact('clients'));

            // return "You're in Botwe";

        }

        if ($user->field?->name == 'Tema')
        {
            $clients = Client::whereIn('field_id', [3,7])->where('status', 'terminated')->where('ho_status', 'approved')->get();

            $totalGuards = employee::where('department_id', '6')->whereIn('field_id', [3,7])->count();
            return view('clients.terminate', compact('clients'));
            // return "You're in Tema";

        }

        if ($user->field?->name == 'Takoradi')
        {
            $clients = Client::where('field_id', 4)->where('status', 'terminated')->where('ho_status', 'approved')->get();

            $totalGuards = employee::where('department_id', '6')->where('field_id', 4)->count();
            return view('clients.terminate', compact('clients'));
            // return "You're in Takoradi";
        }

        if ($user->field?->name == 'Koforidua')
        {
            $clients = Client::where('field_id', 5)->where('status', 'terminated')->where('ho_status', 'approved')->get();

            $totalGuards = employee::where('department_id', '6')->where('field_id', 5)->count();
            return view('clients.terminate', compact('clients'));
            // return "You're in Koforidua";
        }

        if ($user->field?->name == 'Kumasi')
        {
            $clients = Client::where('field_id', 6)->where('status', 'terminated')->where('ho_status', 'approved')->get();

            $totalGuards = employee::where('department_id', '6')->where('field_id', 6)->count();
            return view('clients.terminate', compact('clients'));
            // return "You're in Kumasi";

        }





    }


    /**
     * Display a listing of the resource.
     */

    public function clientAproval(Request $request)
    {
        // dd($request->all());
        
        //  $clientIds = $request->input('clients', []);
        // //  dd($clientIds);
        // if (empty($clientIds)) {
        //     return back()->with('error', 'No client selected !');
        // }
        
        //  $clients =  Client::findOrFail($request->clients);
        // //  dd($clients);

        //  foreach ($clients as $client )
        //     {
        //         $client->status = 'active';
        //         $client->user_id = Auth::id();
        //         $client->save();

        //     }

    
        $submitValue = $request->input('submit'); 
        $declineValue = $request->input('decline'); 
        // dd($submitValue);
         $clientIds = $request->input('clients', []);
        //  dd($clientIds);
        if (empty($clientIds)) {
            return back()->with('error', 'No client selected !');
        }
        
         $clients =  Client::findOrFail($request->clients);
        //  dd($receipts);
        $alreadyProcessed = [];

        $invoice_manager =  User::where('department_id', '1')->where('role_id', '1')->first();
        $user = Auth::user();

        // $invoice_manager = $staff->;

        foreach( $clients as $client)
        {


            if( $submitValue == 'branch' )
                
                {
                    // dd($client);
                    $exists = Client::where('id', $client->id)
                        ->where('bran_status', 'approved')
                        ->exists();
                    if ($exists) {
                        $alreadyProcessed[] =  " client: " . $client->id;
                        continue;
                    }
                    // update branch to approved, collection to approved and dates.
                    $client->bran_status = 'approved';
                    $client->user_id1 = $user->id;
                    $client->bran_date = now();

                    $client->coll_status = 'approved';
                    $client->ho_status = 'pending';
                    $client->user_id2 =  $invoice_manager->id;

                    $client->save();
                    // return 'you are a Branch Manager';

                }

            if($submitValue == 'headOffice' )
                {
                    // // dd($client);
                    $exists = Client::where('id', $client->id)
                        ->where('ho_status', 'approved')
                        ->exists();
                    if ($exists) {
                        $alreadyProcessed[] =  " client: " . $client->id;
                        continue;
                    }
                    // update branch to approved, collection to approved and dates.
                    // $client->bran_status = 'approved';
                    // $client->user_id2 = Auth::id();
                    // $client->bran_date = now();

                    // $client->coll_status = 'approved';
                    $client->ho_status = 'approved';
                    $client->user_id2 = $user->id;
                    $client->ho_date = now();

                    if($client->status == 'pending' || $client->status == 're-instate')
                    {
                        $client->status = 'active';

                    }

                    if($client->status == 'terminated')
                    {
                        $client->status = 'terminated';

                    }
                    

                    // $client->status = 'active';
                    // $client->user_id = Auth::id();


                    $client->save();

                    // return 'you are at the head office';
                    
                }


            if($declineValue == 'headOffice' )
                {
                    //                     // dd($client);
                    // $exists = Client::where('id', $client->id)
                    //     ->where('bran_status', 'approved')
                    //     ->exists();
                    // if ($exists) {
                    //     $alreadyProcessed[] =  " client: " . $client->id;
                    //     continue;
                    // }
                    // update branch to approved, collection to approved and dates.
                    $client->bran_status = 'pending';
                    // $client->user_id2 = Auth::id();
                    $client->coll_status = 'pending';
                    $client->coll_date = now();


                    $client->save();
                    // return 'you are at the head office Declining'; 

                }



        }

               return back()->with('success', 'Selected Clients or Client Has Been Approved !.');
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



 
            return view('clients.statement', compact('client', 'data'));
    }




    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        // dd($client);
        $fields = Field::all();
        $categories = ['Category A', 'Category B', 'Category C', 'Category D']; 
        // Employees assigned to this client (based on employee.client_id)
        // $attachedEmployees = employee::where('client_id', $client->id)->where('status', 'Active')->get();

        // Available employees (not assigned to this client) — limit to guards (department_id = 6) and active status
        // $availableEmployees = employee::where(function($q) use ($client) {
        //     $q->where('client_id', '!=', $client->id)->orWhereNull('client_id');
        // })->where('department_id', 6)->where('status', 'Active')->get();
        $user = Auth::user();
        $staff =  User::all();
        $manager = $staff->where('department_id', '7')->where('role_id', '3');
        $finance_manager = $staff->where('department_id', '1')->where('role_id', '1');
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

        return view('clients.edit', compact('client', 'fields', 'categories', 'staff', 'assign_staff'));
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
     * Terminate the specified resource in storage.
     */
    public function terminateClient(Request $request, int $id)
    {
        // dd($request->all());
        $month = Carbon::parse($request->month)->format('Y-m-d');
        // dd($month);
        $client = Client::findOrFail($id);
        $client->status = 'terminated';
        $client->status_date = $month;
        $user_id = Auth::id();

        if(Auth::user()->role?->id == '1')
            {
                // return "You are at head office";
            // $client->status = 'terminated';

            $client->ho_status = 'approved';
            $client->ho_date = $month;
            $client->user_id2 = $user_id;
            $client->save();

            return redirect('clientTerminated')->with('info', $client->id. ' '.'Client Has Been Terminated !.');

            }
        if(Auth::user()->department?->id == '7' && Auth::user()->role?->id == '3')
            {
                // return "You are a Manager";
            // $client->status = 'pending';

            $client->ho_status = 'pending';
            $client->user_id2 = $request->staff;

            $client->bran_status = 'approved';
            $client->bran_date = $month;
            $client->user_id1 = $user_id;
            $client->save();

            return redirect('clientPending')->with('info', $client->id. ' '.'Client Has Been Re-Instated, now at pending Waiting for approval from head office!.');

            }



        // return  back()->with('info', 'Client Terminated Successfully');

    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientRequest $request, Client $client)
    {
        // dd($request->all());
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

    public function clientReinstate(Request $request, int $id)
    {
        // dd($request->all(), $id);
        $month = Carbon::parse($request->status_date)->format('Y-m-d');
        // dd($month);
        $client = Client::findOrFail($id);
        $client->status_date = $month;
        $user_id = Auth::id();

        if(Auth::user()->role?->id == '1')
            {
                // return "You are at head office";
            $client->status = 'active';

            $client->ho_status = 'approved';
            $client->ho_date = $month;
            $client->user_id2 = $user_id;
            }
        if(Auth::user()->department?->id == '7' && Auth::user()->role?->id == '3')
            {
                // return "You are a Manager";
            $client->status = 're-instate';

            $client->ho_status = 'pending';
            $client->user_id2 = $request->staff;

            $client->bran_status = 'approved';
            $client->bran_date = $month;
            $client->user_id1 = $user_id;
            }


        $client->save();

        // return back()->with('success', 'Selected Client Has Been Re-Instated, now at pending !.');
        return redirect('clientPending')->with('info', $client->id. ' '.'Client Has Been Re-Instated, now at pending !.');
   
    }

}

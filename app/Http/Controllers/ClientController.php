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
        $client = new Client();
        $client->create($request->all());
        return redirect()->back()->with('success', 'Client Added Sucessfully');
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
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        //dd($client);
        $fields = Field::all();
        return view('clients.edit', compact('client', 'fields'));
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

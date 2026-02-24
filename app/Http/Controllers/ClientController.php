<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Field;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Service;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

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
        $clientsCount = count($clients);
        $fields = Field::all();

        $accra = Client::where('field_id', 1)->get();
        $accraCount = count($accra);

        $botwe = Client::where('field_id', 2)->get();
        $botweCount = count($botwe);

        $tema = Client::where('field_id', 3)->get();
        $temaCount = count($tema);

        $takoradi = Client::where('field_id', 4)->get();
        $takoradiCount = count($takoradi);

        $koforidua = Client::where('field_id', 5)->get();
        $koforiduaCount = count($koforidua);

        $kumasi = Client::where('field_id', 6)->get();
        $kumasiCount = count($kumasi);
        // dd($accraCount, $botweCount, $temaCount, $takoradiCount, $koforiduaCount, $kumasiCount);

        if($user->role->name == 'Invoice' || $user->role->name == 'Finance Manager' )
        {
            return view('clients.index', compact('clients', 'clientsCount', 'fields', 'accraCount', 'botweCount', 'temaCount', 'takoradiCount', 'koforiduaCount', 'kumasiCount'));

        }elseif($user->field?->name == 'Accra')
        {
            $clients = $accra;
            return view('clients.index', compact('clients', 'clientsCount', 'fields', 'accraCount'));
        }

        if ($user->field?->name == 'Botwe')
        {
            $clients = $botwe;
            return view('clients.index', compact('clients', 'clientsCount', 'fields', 'botweCount'));

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
            return back()->with('info', 'Client Have an Unpaid Invoice');
        }else{

            $client->delete();
            return back()->with('error', 'Client Deleted Successfully');

        }



    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
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

        // dd(Auth::user()->field);
        //
        $accraTransactions = Transaction::whereRelation('client', 'field_id', '1')->where('checks', null)->get();
        $count_accraTransactions = count($accraTransactions);

        $botweTransactions = Transaction::whereRelation('client', 'field_id', '2')->where('checks', null)->get();
        $count_botweTransactions = count($botweTransactions);

        $temaTransactions = Transaction::whereRelation('client', 'field_id', '3')->where('checks', null)->get();
        $count_temaTransactions = count($temaTransactions);

        $takoradiTransactions = Transaction::whereRelation('client', 'field_id', '4')->where('checks', null)->get();
        $count_takoradiTransactions = count($takoradiTransactions);

        $koforiduaTransactions = Transaction::whereRelation('client', 'field_id', '5')->where('checks', null)->get();
        $count_koforiduaTransactions = count($koforiduaTransactions);

        $kumasiTransactions = Transaction::whereRelation('client', 'field_id', '6')->where('checks', null)->get();
        $count_kumasiTransactions = count($kumasiTransactions);
        // dd($kumasiTransactions);
        return view('transactions.index', compact('accraTransactions', 'count_accraTransactions', 'botweTransactions', 'count_botweTransactions', 'temaTransactions', 'count_temaTransactions', 'takoradiTransactions', 'count_takoradiTransactions', 'koforiduaTransactions', 'count_koforiduaTransactions', 'kumasiTransactions', 'count_kumasiTransactions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransactionRequest $request)
    {
        //


    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        //
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Http\Requests\StoreBankRequest;
use App\Http\Requests\UpdateBankRequest;
use App\Models\BankTransaction;

class BankController extends Controller
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
        $banks = Bank::all();
        $bankTransactions = BankTransaction::all();
        return view('banks.index', compact('banks', 'bankTransactions'));
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
    public function store(StoreBankRequest $request)
    {
        //
        $banks = new Bank();
        $banks->create($request->all());
        return back()->with('success', 'Bank Added Sucessfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Bank $bank)
    {
        //
        return view('banks.show', compact('bank'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bank $bank)
    {
        //

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBankRequest $request, Bank $bank)
    {
        //
        $bank->update($request->all());
        return back()->with('success', 'Bank Updated Successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bank $bank)
    {
        //
        return back()->with('danger', 'Delete Feature not functional yet');
    }
}

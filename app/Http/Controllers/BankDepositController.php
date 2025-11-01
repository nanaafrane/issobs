<?php

namespace App\Http\Controllers;

use App\Models\BankDeposit;
use App\Http\Requests\StoreBankDepositRequest;
use App\Http\Requests\UpdateBankDepositRequest;
use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\Collection;
use Illuminate\Support\Facades\Auth;

class BankDepositController extends Controller
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
        // $collections = Collection::where('status', 'undeposited')->get();
        $banks = Bank::all();
        // return view('banks.deposit_list', compact('collections', 'banks'));
        // dd($collections);
        $deposits = BankDeposit::all();
        return view('banks.deposit_list', compact('deposits', 'banks'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $collections = Collection::where('status', 'undeposited')->get();
        $banks = Bank::all();
        return view('banks.deposit_index', compact('collections', 'banks'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBankDepositRequest $request)
    {
        //
        // dd($request->all());
        // FIND COLLECTIONS
        $collections = Collection::findOrFail($request->collections);

        foreach($collections as $key => $collection){
            // echo $request->bank_id[$key] ."<br>";
            // DEPOSIT EACH BANK TRANSACTION
            $current_deposited_id = $this->bank_deposit($collection, $request->bank_id[$key]);

             // GET THE BANK
            $bank = Bank::findOrFail($request->bank_id[$key]);

            $this->bank_transaction($bank,  $current_deposited_id, $collection);

            // UPDATE THE BANK TOTAL
            $bank->total = $bank->total + $collection->cash_amount + $collection->cheque_amount;
            $bank->save();
            // UPDATE STATUS TO DEPOSITED

            $collection->status = 'Deposited';
            $collection->save();

        }

        return back()->with('success', 'Bank Deposit Added Successfully');

    }

    public function bank_deposit ($collection, $bank)
    {

        // dd($collection , $bank);
        // CREATE A BANK DEPOSIT AND RETURN THE DEPOSITED ID
        $deposit = new BankDeposit();
        $deposit->bank_id = $bank;
        $deposit->user_id = Auth::user()->id;
        $deposit->cash_amount = $collection->cash_amount;
        $deposit->cheque_amount = $collection->cheque_amount;
        $deposit->total =  $collection->cash_amount + $collection->cheque_amount;
        $deposit->save();

         return $deposit->id;
    }

    public function bank_transaction($bank, $current_deposited_id, $collection)
    {
        // dd($bank->total);
        // CREATE A BANK TRANSACTION
        $bank_transaction = new BankTransaction();
        $bank_transaction->bank_id = $bank->id;
        $bank_transaction->credit = $collection->cash_amount + $collection->cheque_amount;;
        $bank_transaction->deposit_id = $current_deposited_id;
        $bank_transaction->balance = $bank->total + $collection->cash_amount + $collection->cheque_amount;
        $bank_transaction->save();
    }


    /**
     * Display the specified resource.
     */
    public function show(BankDeposit $bankDeposit)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BankDeposit $bankDeposit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBankDepositRequest $request, BankDeposit $bankDeposit)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BankDeposit $bankDeposit)
    {
        //
    }
}

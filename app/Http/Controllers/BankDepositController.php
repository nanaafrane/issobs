<?php

namespace App\Http\Controllers;

use App\Models\BankDeposit;
use App\Http\Requests\StoreBankDepositRequest;
use App\Http\Requests\UpdateBankDepositRequest;
use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\Collection;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;
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

        // Pre-select the bank chosen on the receipt for its cheque (or transfer).
        $suggestedBanks = Receipt::whereIn('id', $collections->pluck('receipt_id')->filter())
            ->get(['id', 'cheque_to_bank_id', 'transfer_to_bank_id'])
            ->mapWithKeys(fn ($r) => [$r->id => $r->cheque_to_bank_id ?: $r->transfer_to_bank_id]);

        return view('banks.deposit_index', compact('collections', 'banks', 'suggestedBanks'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBankDepositRequest $request)
    {
        $collectionIds = array_filter((array) $request->input('collections', []));
        if (empty($collectionIds)) {
            return back()->with('error', 'Tick at least one collection to deposit.');
        }

        // bank_id is keyed by collection id (bank_id[<collection id>]) so the bank
        // always matches its own row. Previously both were plain lists, and
        // ticking only some rows paired collections with other rows' banks.
        $bankIds = (array) $request->input('bank_id', []);
        $missing = array_filter($collectionIds, fn ($id) => empty($bankIds[$id]));
        if ($missing) {
            return back()->withInput()->with('error', 'Choose a bank for every ticked collection (collection #' . implode(', #', $missing) . ').');
        }

        DB::transaction(function () use ($collectionIds, $bankIds) {
            $collections = Collection::whereIn('id', $collectionIds)
                ->where('status', 'undeposited')
                ->lockForUpdate()
                ->get();

            foreach ($collections as $collection) {
                $bank = Bank::whereKey($bankIds[$collection->id])->lockForUpdate()->firstOrFail();

                $current_deposited_id = $this->bank_deposit($collection, $bank->id);
                $this->bank_transaction($bank, $current_deposited_id, $collection);

                // UPDATE THE BANK TOTAL
                $bank->total = $bank->total + $collection->cash_amount + $collection->cheque_amount;
                $bank->save();

                $collection->status = 'Deposited';
                $collection->save();
            }
        });

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
        $bank_transaction->narration = 'Deposit of collection #' . $collection->id
            . ($collection->receipt_id ? ' (receipt FWSSR' . $collection->receipt_id . ')' : '');
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

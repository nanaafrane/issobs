<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{

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
        $expenses = Expense::all();
        $user = Auth::user();

        if($user->role->name == 'Officer')
        {

            $assigning_users = User::where('department_id', 6)->where('field_id',  $user->field->id)->where('role_id', 6)->get();

        }elseif($user->role->name == 'Manager')
        {
            $assigning_users = User::where('department_id',  1)->where('role_id', 6)->get();
        }

        return view('expenses.index', compact('expenses', 'assigning_users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('expenses.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpenseRequest $request)
    {
        //
        // dd($request->all());
        // if receipt has image
        $image = null;
        if ($request->file('image')) {
            $image = ($request->file('image'))->store('images', 'public');
        }

        $expense = new Expense();
        $expense->description = $request->input('description');
        $expense->amount = $request->input('amount');
        $expense->user_1 = Auth::user()->id;
        $expense->status_1 = 'created';
        $expense->user_2 = $request->input('user_2');
        $expense->status_2 = 'pending';
        $expense->image = $image;
        $expense->save();

        return back()->with('secondary', 'Expense created successfully, kindly await approval');

    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expense $expense)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense)
    {
        //
    }
}

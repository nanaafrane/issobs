<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Department;
use App\Models\Field;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
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
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = Auth::user();
        $accra_sum_invoices = Invoice::whereRelation('client', 'field_id', '1')->sum('total');
        $botwe_sum_invoices = Invoice::whereRelation('client', 'field_id', '2')->sum('total');
        $tema_sum_invoices = Invoice::whereRelation('client', 'field_id', '3')->sum('total');
        $takoradi_sum_invoices = Invoice::whereRelation('client', 'field_id', '4')->sum('total');
        $koforidua_sum_invoices = Invoice::whereRelation('client', 'field_id', '5')->sum('total');
        $kumasi_sum_invoices = Invoice::whereRelation('client', 'field_id', '6')->sum('total');
        $sum_invoices = [$accra_sum_invoices, $botwe_sum_invoices, $tema_sum_invoices,  $takoradi_sum_invoices,  $koforidua_sum_invoices, $kumasi_sum_invoices];
        // dd($sum_invoices);

        $accra_invoices_outstanding = Invoice::whereRelation('client', 'field_id', '1')->where('status', 'unpaid')->sum('total');
        $botwe_invoices_outstanding = Invoice::whereRelation('client', 'field_id', '2')->where('status', 'unpaid')->sum('total');
        $tema_invoices_outstanding = Invoice::whereRelation('client', 'field_id', '3')->where('status', 'unpaid')->sum('total');
        $takoradi_invoices_outstanding = Invoice::whereRelation('client', 'field_id', '4')->where('status', 'unpaid')->sum('total');
        $koforidua_invoices_outstanding = Invoice::whereRelation('client', 'field_id', '5')->where('status', 'unpaid')->sum('total');
        $kumasi_invoices_outstanding = Invoice::whereRelation('client', 'field_id', '6')->where('status', 'unpaid')->sum('total');

        $invoices_outstanding = [$accra_invoices_outstanding, $botwe_invoices_outstanding, $tema_invoices_outstanding, $takoradi_invoices_outstanding, $koforidua_invoices_outstanding, $kumasi_invoices_outstanding];
        // dd($invoices_outstanding);

        $accra_balance_outstanding = Invoice::whereRelation('client', 'field_id', 1)->where('balance', '>', 0.00)->where('status', 'uncompleted')->sum('balance');
        $botwe_balance_outstanding = Invoice::whereRelation('client', 'field_id', 2)->where('balance', '>', 0.00)->where('status', 'uncompleted')->sum('balance');
        $tema_balance_outstanding = Invoice::whereRelation('client', 'field_id', 3)->where('balance', '>', 0.00)->where('status', 'uncompleted')->sum('balance');
        $takoradi_balance_outstanding = Invoice::whereRelation('client', 'field_id', 4)->where('balance', '>', 0.00)->where('status', 'uncompleted')->sum('balance');
        $koforidua_balance_outstanding = Invoice::whereRelation('client', 'field_id', 5)->where('balance', '>', 0.00)->where('status', 'uncompleted')->sum('balance');
        $kumasi_balance_outstanding = Invoice::whereRelation('client', 'field_id', 6)->where('balance', '>', 0.00)->where('status', 'uncompleted')->sum('balance');


        $balance_outstanding = [$accra_balance_outstanding,$botwe_balance_outstanding,$tema_balance_outstanding,$takoradi_balance_outstanding,$koforidua_balance_outstanding,$kumasi_balance_outstanding];
        // dd($balance_outstanding);

        $accra_receipt_AllPayment = Receipt::whereRelation('client', 'field_id', '1')->sum('total');
        $botwe_receipt_AllPayment = Receipt::whereRelation('client', 'field_id', '2')->sum('total');
        $tema_receipt_AllPayment = Receipt::whereRelation('client', 'field_id', '3')->sum('total');
        $takoradi_receipt_AllPayment = Receipt::whereRelation('client', 'field_id', '4')->sum('total');
        $koforidua_receipt_AllPayment = Receipt::whereRelation('client', 'field_id', '5')->sum('total');
        $kumasi_receipt_AllPayment = Receipt::whereRelation('client', 'field_id', '6')->sum('total');

        $receipt_AllPayment = [$accra_receipt_AllPayment, $botwe_receipt_AllPayment, $tema_receipt_AllPayment, $takoradi_receipt_AllPayment,  $koforidua_receipt_AllPayment, $kumasi_receipt_AllPayment];

        $accra_total_after_wht = Receipt::whereRelation('client', 'field_id', '1')->sum('amount_received');
        $botwe_total_after_wht = Receipt::whereRelation('client', 'field_id', '2')->sum('amount_received');
        $tema_total_after_wht = Receipt::whereRelation('client', 'field_id', '3')->sum('amount_received');
        $takoradi_total_after_wht = Receipt::whereRelation('client', 'field_id', '4')->sum('amount_received');
        $koforidua_total_after_wht = Receipt::whereRelation('client', 'field_id', '5')->sum('amount_received');
        $kumasi_total_after_wht = Receipt::whereRelation('client', 'field_id', '6')->sum('amount_received');

        $total_after_wht = [$accra_total_after_wht, $botwe_total_after_wht, $tema_total_after_wht, $takoradi_total_after_wht, $koforidua_total_after_wht, $kumasi_total_after_wht];

        $accra_total_wth = Receipt::whereRelation('client', 'field_id', '1')->sum('wht_amount');
        $botwe_total_wth = Receipt::whereRelation('client', 'field_id', '2')->sum('wht_amount');
        $tema_total_wth = Receipt::whereRelation('client', 'field_id', '3')->sum('wht_amount');
        $takoradi_total_wth = Receipt::whereRelation('client', 'field_id', '4')->sum('wht_amount');
        $koforidua_total_wth = Receipt::whereRelation('client', 'field_id', '5')->sum('wht_amount');
        $kumasi_total_wth = Receipt::whereRelation('client', 'field_id', '6')->sum('wht_amount');

        $total_wth = [$accra_total_wth, $botwe_total_wth, $tema_total_wth, $takoradi_total_wth, $koforidua_total_wth, $kumasi_total_wth];

        $accra_receiept_CashAmount = Receipt::whereRelation('client', 'field_id', '1')->sum('cash_amount');
        $botwe_receiept_CashAmount = Receipt::whereRelation('client', 'field_id', '2')->sum('cash_amount');
        $tema_receiept_CashAmount = Receipt::whereRelation('client', 'field_id', '3')->sum('cash_amount');
        $takoradi_receiept_CashAmount = Receipt::whereRelation('client', 'field_id', '4')->sum('cash_amount');
        $koforidua_receiept_CashAmount = Receipt::whereRelation('client', 'field_id', '5')->sum('cash_amount');
        $kumasi_receiept_CashAmount = Receipt::whereRelation('client', 'field_id', '6')->sum('cash_amount');

        $receiept_CashAmount = [$accra_receiept_CashAmount, $botwe_receiept_CashAmount, $tema_receiept_CashAmount, $takoradi_receiept_CashAmount, $koforidua_receiept_CashAmount, $kumasi_receiept_CashAmount];

        $accra_receiept_MoMoAmount = Receipt::whereRelation('client', 'field_id', '1')->sum('momo_amount');
        $botwe_receiept_MoMoAmount = Receipt::whereRelation('client', 'field_id', '2')->sum('momo_amount');
        $tema_receiept_MoMoAmount = Receipt::whereRelation('client', 'field_id', '3')->sum('momo_amount');
        $takoradi_receiept_MoMoAmount = Receipt::whereRelation('client', 'field_id', '4')->sum('momo_amount');
        $koforidua_receiept_MoMoAmount = Receipt::whereRelation('client', 'field_id', '5')->sum('momo_amount');
        $kumasi_receiept_MoMoAmount = Receipt::whereRelation('client', 'field_id', '6')->sum('momo_amount');

        $receiept_MoMoAmount = [$accra_receiept_MoMoAmount, $botwe_receiept_MoMoAmount, $tema_receiept_MoMoAmount,  $takoradi_receiept_MoMoAmount,  $koforidua_receiept_MoMoAmount,  $kumasi_receiept_MoMoAmount];

        $accra_receiept_BankAmount = Receipt::whereRelation('client', 'field_id', '1')->sum('cheque_amount');
        $botwe_receiept_BankAmount = Receipt::whereRelation('client', 'field_id', '2')->sum('cheque_amount');
        $tema_receiept_BankAmount = Receipt::whereRelation('client', 'field_id', '3')->sum('cheque_amount');
        $takoradi_receiept_BankAmount = Receipt::whereRelation('client', 'field_id', '4')->sum('cheque_amount');
        $koforidua_receiept_BankAmount = Receipt::whereRelation('client', 'field_id', '5')->sum('cheque_amount');
        $kumasi_receiept_BankAmount = Receipt::whereRelation('client', 'field_id', '6')->sum('cheque_amount');

        $receiept_BankAmount = [$accra_receiept_BankAmount, $botwe_receiept_BankAmount, $tema_receiept_BankAmount,  $takoradi_receiept_BankAmount, $koforidua_receiept_BankAmount, $kumasi_receiept_BankAmount];

        $accra_receiept_TransferAmount = Receipt::whereRelation('client', 'field_id', '1')->sum('transfer_amount');
        $botwe_receiept_TransferAmount = Receipt::whereRelation('client', 'field_id', '2')->sum('transfer_amount');
        $tema_receiept_TransferAmount = Receipt::whereRelation('client', 'field_id', '3')->sum('transfer_amount');
        $takoradi_receiept_TransferAmount = Receipt::whereRelation('client', 'field_id', '4')->sum('transfer_amount');
        $koforidua_receiept_TransferAmount = Receipt::whereRelation('client', 'field_id', '5')->sum('transfer_amount');
        $kumasi_receiept_TransferAmount = Receipt::whereRelation('client', 'field_id', '6')->sum('transfer_amount');

        $receiept_TransferAmount = [$accra_receiept_TransferAmount, $botwe_receiept_TransferAmount,  $tema_receiept_TransferAmount, $takoradi_receiept_TransferAmount, $koforidua_receiept_TransferAmount, $kumasi_receiept_TransferAmount];

        $banks = Bank::all();

        // $current_5_invoices = Invoice::orderBy('id', 'desc')->take(5)->get();
        // $current_5_receipts = Receipt::orderBy('id', 'desc')->take(5)->get();
        // dd($user->department->name);
        if($user->department?->name == 'Guest')
        {
            // return view('sales.dashboard');
            return view('auth.login');
        }

        if($user->department?->name == 'Accounts' )
        {
            return view('sales.dashboard', compact('banks','total_after_wht', 'total_wth' ,'sum_invoices' ,'invoices_outstanding','balance_outstanding', 'receiept_CashAmount', 'receiept_MoMoAmount', 'receiept_BankAmount', 'receiept_TransferAmount', 'receipt_AllPayment'));
        }

        if($user->department?->name == 'HR')
        {
            return view('hr.dashboard');
        }

        if($user->department?->name == 'System')
        {
            return view('system.dashboard');
        }



    }

    public function showAllStaffs()
    {
        $users = User::all();
        $UsersCount = count($users);
        return view('users.index', compact('users', 'UsersCount'));
    }


    public function staffProfile($id)
    {
        // dd($id);
        $user = User::findOrFail($id);
        if($user->department->name == 'Accounts')
        {
            return view('sales.userProfile', compact('user'));
        }
    }

    public function staffProfileImage(Request $request, $id)
    {

        if ($request['image']) {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);
            // $user = User::findOrFail($id);
            // $user->path   = $request->file('path')->store('public','images');
            // $user->save();
            // return back()->with('success', 'User Images Updated Successfully');
        }

        $user = User::findOrFail($id);
        // dd($user);
        // delete old image
        if ($user->path) {
            Storage::disk('public')->delete($user->path);
        }
        $user->path   = $request->file('image')->store('images', 'public');
        $user->save();

        return back()->with('success', 'Staff Image Updated Successfully');

    }


    public function show($id)
    {
        $user = User::findOrFail($id);

        return view('hr.userProfile', compact('user'));
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $Departments = Department::all();
        $Roles = Role::all();
        $fields = Field::all();
        return view('users.edit', compact('user', 'Departments', 'Roles', 'fields'));
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone_number' => ['required', 'min:10', 'max:10'],
        ]);
       $user = User::findOrFail($id);
       $user->update($request->all());
       return back()->with('info', 'Staff Details Updated Successfull');
    }

    public function staffPasswordReset(Request $request, $id)
    {
        // dd($request->all());
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // 'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::findOrFail($id);
        $pwd = Hash::make($request->input('password'));
        // $user->update(['password', $pwd]);
        // dd($user);
        $user->password = $pwd;
        $user->save();

        return back()->with('success', 'Staff Password Updated Successfully');

    }

    public function staffCreate()
    {
        $Departments = Department::all();
        $Roles = Role::all();
        $fields = Field::all();
        return view('users.create', compact('Departments', 'Roles', 'fields'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone_number' => ['required', 'min:10', 'max:10'],
            'password' => ['required', 'unique:users', 'string', 'min:8', 'confirmed'],
        ]);

        $user = new User();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->phone_number = $request->input('phone_number');
        $user->gender = $request->input('gender');
        $user->department_id = $request->input('department_id');
        $user->role_id = $request->input('role_id');
        $user->field_id = $request->input('field_id');
        $user->password = Hash::make($request->input('password'));
        $user->save();

        return redirect('staff')->with('info', 'New User Created Successfully');
    }


}

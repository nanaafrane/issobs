<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceToPayrollSearchRequest;
use App\Http\Requests\SalariesUploadRequest;
use App\Models\Salary;
use App\Http\Requests\StoreSalaryRequest;
use App\Http\Requests\UpdateSalaryRequest;
use App\Models\Bank;
use App\Models\Client;
use App\Models\Department;
use App\Models\employee;
use App\Models\Field;
use App\Models\Invoice;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;


class SalaryController extends Controller
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
        // Pass only active employees to the salaries index view, as it displays salary data for currently active staff.
        $employees =  employee::where('status', 'Active')->get();
        $employeeAccra =  employee::where('field_id', 1)->where('status', 'Active')->count();
        $employeeBotwe =  employee::where('field_id', 2)->where('status', 'Active')->count();
        $employeeTema =  employee::where('field_id', 3)->where('status', 'Active')->count();
        $employeeTakoradi =  employee::where('field_id', 4)->where('status', 'Active')->count();
        $employeeKoforidua =  employee::where('field_id', 5)->where('status', 'Active')->count();
        $employeeKumasi =  employee::where('field_id', 6)->where('status', 'Active')->count();
        $employeeShyhills =  employee::where('field_id', 7)->where('status', 'Active')->count();


        $Departments = Department::all(); 
        $Roles = Role::all();
        $Fields = Field::all();
        $clients = Client::all();
        $banks = Bank::all();
        return view('salaries.index', compact('employees', 'employeeAccra', 'employeeBotwe', 'employeeTema', 'employeeTakoradi', 'employeeKoforidua', 'employeeKumasi', 'employeeShyhills','Departments', 'Roles', 'Fields', 'clients', 'banks'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $salaries =  Salary::where('payment_status', 'pending')->get();
        $salariesAccra =  Salary::where('field_id', 1)->where('payment_status', 'pending')->count();
        $salariesBotwe =  Salary::where('field_id', 2)->where('payment_status', 'pending')->count();
        $salariesTema =  Salary::where('field_id', 3)->where('payment_status', 'pending')->count();
        $salariesTakoradi =  Salary::where('field_id', 4)->where('payment_status', 'pending')->count();
        $salariesKoforidua =  Salary::where('field_id', 5)->where('payment_status', 'pending')->count();
        $salariesKumasi =  Salary::where('field_id', 6)->where('payment_status', 'pending')->count();
        $salariesShyhills =  Salary::where('field_id', 7)->where('payment_status', 'pending')->count();

        return view('salaries.create', compact('salaries', 'salariesAccra', 'salariesBotwe','salariesTema', 'salariesTakoradi', 'salariesKoforidua', 'salariesKumasi', 'salariesShyhills'));
    }


    public function transactionSalary()
    {
        //
        return view('salaries.transaction');
    }


    /**
     * Show the form for comparing invoices to payroll.
     */
    public function InvToParoll()
    {
        //

        return view('salaries.invpayroll');
    }


        /**
     * get all invoices and salaries for incoming month request.
     */
    public function InvToParollMonth(InvoiceToPayrollSearchRequest $request)
    {
        // format date of incoming request
        // dd($request->all()); 

        // get invoices for incoming month
        $invoices = Invoice::where('invoice_month', Carbon::parse($request->month)->format('Y-m-d'))->get();
        // dd($invoices);
        
        // get salaries for incoming month
        $salaries = Salary::select('client_id', DB::raw('SUM(cost_to_company) as total_salary'))
                            ->groupBy('client_id')
                            ->get();
        // dd($salaries);
        return view('salaries.invpayrollview', compact('invoices', 'salaries'));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSalaryRequest $request)
    {

        $employees = $request->input('employees', []);
        // dd($employees);
        if (empty($employees)) {
            return back()->with('error', 'No employee selected to add to  salaries.');
        }


        // get current month and year
        $date = Carbon::parse($request->input('salary_month'));

        // dd($date->format('Y-m-d H:i:s'));
        // get date of the last salary entry
        // $lastSalary = Salary::where('payment_status', 'pending')->orderBy('created_at', 'desc')->first();

        // $lastSalaryMonth = $lastSalary ? Carbon::parse($lastSalary->salary_month) : null;   
       
        // // Check if a salary entry for the current month already exists
        // if ($lastSalaryMonth && $lastSalaryMonth->format('F Y') === $date->format('F Y')) {
           
           
        //     return back()->with('error', 'Salary for this month has already been added.');
        // }   
      
        // Process salary entries for selected employees
        $alreadyProcessed = [];
        $employees = employee::findOrFail($request->employees);
       
        // dd($employees, $date->format('Y-m-d H:i:s'));

        if ($request->has('employees')) {
            foreach ($employees as $employee) {
                $exists = Salary::where('employee_id', $employee->id)
                                ->where('salary_month', $date->format('Y-m-d H:i:s'))
                                ->exists();
                if ($exists) {
                    $alreadyProcessed[] = $employee->id;
                    continue;
                }

                // return "Processing salary for Employee payment ID: " . $employee->paymentInfo->id . "\n";
                $salary = new Salary();
                $salary->employee_id = $employee->id;
                $salary->salary_month = $request->input('salary_month');
                $salary->field_id = $employee->field_id;
                $salary->department_id = $employee->department_id;
                $salary->role_id = $employee->role_id;
                $salary->client_id = $employee->client_id;
                $salary->location = $employee->location;
                $salary->payment_type = $employee->payment_type;
                $salary->account_number = $employee->paymentInfo?->acc_number;
                $salary->bank_id = $employee->paymentInfo?->bank_id;
                $salary->branch = $employee->paymentInfo?->branch;
                $salary->payment_type = $employee->payment_type;
               
                $salary->basic_salary = $employee->basic_salary;
                $salary->allowances = $employee->allowances;
                $salary->user_id = Auth::id();
                $salary->save();



            }
        }

        // dd($alreadyProcessed);

        if (!empty($alreadyProcessed)) {
            return back()->with('error', 'The employees with the IDs have already been add to salary to be processed for this month: '. implode(', ', $alreadyProcessed)) ;
        }
        return back()->with('success', 'Employees added for this month salary to be processed.'); 

        // dd($request->all(), $date->format('F Y'));


    }

    /**
     * Display the specified resource.
     */
    public function show(Salary $salary)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Salary $salary)
    {
        //
        // dd($salary);
        $banks = Bank::all();
        $clients = Client::where('field_id', $salary->field_id)->get();
        
        return view('salaries.edit', compact('salary', 'clients', 'banks'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSalaryRequest $request, Salary $salary)
    {
        //
        // dd($request->all()); 

        $salary->update($request->all());
        return back()->with('success', 'Salary Updated Successfully');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Salary $salary)
    {
        //
        // dd($salary);
        // $salary->delete();
    }

    public function deleteMultiple(StoreSalaryRequest $request)
    {
        $salaryIds = $request->input('salary', []);
        // dd($salaryIds);
        if (empty($salaryIds)) {
            return back()->with('error', 'No salaries selected for deletion.');
        }

        Salary::whereIn('id', $salaryIds)->delete();
        // return "Deleting salaries with IDs: " . implode(', ', $salaryIds);


        return back()->with('success', 'Selected salaries have been deleted successfully.');
    }





}

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
use App\Imports\SalaryImport;


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
        $salariesAccra =  Salary::where('field_id', 1)->where('payment_status', 'pending')->get();
        $salariesAccraSum = $salariesAccra->sum('cost_to_company');
        $salariesAccraCount = $salariesAccra->count();
        // dd( $salariesAccra->sum('cost_to_company'));

        $salariesBotwe =  Salary::where('field_id', 2)->where('payment_status', 'pending')->get();
        $salariesBotweSum = $salariesBotwe->sum('cost_to_company');
        $salariesBotweCount = $salariesBotwe->count();

        $salariesTema =  Salary::where('field_id', 3)->where('payment_status', 'pending')->get();
        $salariesTemaSum = $salariesTema->sum('cost_to_company');
        $salariesTemaCount = $salariesTema->count();

        $salariesTakoradi =  Salary::where('field_id', 4)->where('payment_status', 'pending')->get();
        $salariesTakoradiSum = $salariesTakoradi->sum('cost_to_company');
        $salariesTakoradiCount = $salariesTakoradi->count();

        $salariesKoforidua =  Salary::where('field_id', 5)->where('payment_status', 'pending')->get();
        $salariesKoforiduaSum = $salariesKoforidua->sum('cost_to_company');
        $salariesKoforiduaCount = $salariesKoforidua->count();  

        $salariesKumasi =  Salary::where('field_id', 6)->where('payment_status', 'pending')->get();
        $salariesKumasiSum = $salariesKumasi->sum('cost_to_company');
        $salariesKumasiCount = $salariesKumasi->count();    

        $salariesShyhills =  Salary::where('field_id', 7)->where('payment_status', 'pending')->get();
        $salariesShyhillsSum = $salariesShyhills->sum('cost_to_company');
        $salariesShyhillsCount = $salariesShyhills->count();    
        // // dd($salaries->paymentInfo());
        // foreach ($salaries as $salary) {
        //     echo $salary->paymentInfo ? $salary->paymentInfo->ssnit_number .'<br>' : 'No Payment Info';
        // }
        return view('salaries.create', compact('salaries', 'salariesAccraSum', 'salariesAccraCount', 'salariesBotweSum', 'salariesBotweCount', 'salariesTemaSum', 'salariesTemaCount', 'salariesTakoradiSum', 'salariesTakoradiCount', 'salariesKoforiduaSum', 'salariesKoforiduaCount', 'salariesKumasiSum', 'salariesKumasiCount', 'salariesShyhillsSum', 'salariesShyhillsCount'));
    }


    /**
     * Display salaries transaction view.
     */
    public function salariesMonth(InvoiceToPayrollSearchRequest $request)
    {
        //
        // dd($request->month);
        //  Carbon::createFromFormat('F, Y',$request->input('salary_month'))->startOfMonth()->format('Y-m-d H:i:s');
         $date = Carbon::createFromFormat('Y-m',$request->month)->startOfMonth()->format('Y-m-d H:i:s');

        //  dd($date);
         $salaries = Salary::where('salary_month', $date)->get();
        //  dd($salaries);
        $banks = Bank::all();
        $groupedBankSalaries = Salary::select('bank_id', DB::raw('SUM(cost_to_company) as total_salary'))
                                    ->whereIn('bank_id', $banks->pluck('id')->toArray())
                                    ->groupBy('bank_id')
                                    ->get();

        // $salariesBanks = Salary::where('salary_month', $date)->get();
        // $groupedBankSalaries = $salariesBanks->groupBy('bank_id');
        // dd($groupedBankSalaries);



        $salariesAccra =  Salary::where('field_id', 1)->where('salary_month', $date)->get();
        $salariesAccraSum = $salariesAccra->sum('cost_to_company');
        $salariesAccraCount = $salariesAccra->count();
        // dd( $salariesAccra->sum('cost_to_company'));

        $salariesBotwe =  Salary::where('field_id', 2)->where('salary_month', $date)->get();
        $salariesBotweSum = $salariesBotwe->sum('cost_to_company');
        $salariesBotweCount = $salariesBotwe->count();

        $salariesTema =  Salary::where('field_id', 3)->where('salary_month', $date)->get();
        $salariesTemaSum = $salariesTema->sum('cost_to_company');
        $salariesTemaCount = $salariesTema->count();

        $salariesTakoradi =  Salary::where('field_id', 4)->where('salary_month', $date)->get();
        $salariesTakoradiSum = $salariesTakoradi->sum('cost_to_company');
        $salariesTakoradiCount = $salariesTakoradi->count();

        $salariesKoforidua =  Salary::where('field_id', 5)->where('salary_month', $date)->get();
        $salariesKoforiduaSum = $salariesKoforidua->sum('cost_to_company');
        $salariesKoforiduaCount = $salariesKoforidua->count();  

        $salariesKumasi =  Salary::where('field_id', 6)->where('salary_month', $date)->get();
        $salariesKumasiSum = $salariesKumasi->sum('cost_to_company');
        $salariesKumasiCount = $salariesKumasi->count();    

        $salariesShyhills =  Salary::where('field_id', 7)->where('salary_month', $date)->get();
        $salariesShyhillsSum = $salariesShyhills->sum('cost_to_company');
        $salariesShyhillsCount = $salariesShyhills->count(); 


        return view('salaries.salariesmonth', compact('groupedBankSalaries','salaries', 'salariesAccra','salariesAccraSum', 'salariesAccraCount',  'salariesBotwe','salariesBotweSum', 'salariesBotweCount', 'salariesTema','salariesTemaSum', 'salariesTemaCount',  'salariesTakoradi','salariesTakoradiSum', 'salariesTakoradiCount', 'salariesKoforidua','salariesKoforiduaSum', 'salariesKoforiduaCount', 'salariesKumasi','salariesKumasiSum', 'salariesKumasiCount', 'salariesShyhills','salariesShyhillsSum', 'salariesShyhillsCount'));
    }


    public function transactionSalary()
    {
        //
        $salaries =  Salary::where('payment_status', 'approved')->get();
        $salariesAccra =  Salary::where('field_id', 1)->where('payment_status', 'approved')->get();
        $salariesAccraSum = $salariesAccra->sum('cost_to_company');
        $salariesAccraCount = $salariesAccra->count();
        // dd( $salariesAccra->sum('cost_to_company'));

        $salariesBotwe =  Salary::where('field_id', 2)->where('payment_status', 'approved')->get();
        $salariesBotweSum = $salariesBotwe->sum('cost_to_company');
        $salariesBotweCount = $salariesBotwe->count();

        $salariesTema =  Salary::where('field_id', 3)->where('payment_status', 'approved')->get();
        $salariesTemaSum = $salariesTema->sum('cost_to_company');
        $salariesTemaCount = $salariesTema->count();

        $salariesTakoradi =  Salary::where('field_id', 4)->where('payment_status', 'approved')->get();
        $salariesTakoradiSum = $salariesTakoradi->sum('cost_to_company');
        $salariesTakoradiCount = $salariesTakoradi->count();

        $salariesKoforidua =  Salary::where('field_id', 5)->where('payment_status', 'approved')->get();
        $salariesKoforiduaSum = $salariesKoforidua->sum('cost_to_company');
        $salariesKoforiduaCount = $salariesKoforidua->count();  

        $salariesKumasi =  Salary::where('field_id', 6)->where('payment_status', 'approved')->get();
        $salariesKumasiSum = $salariesKumasi->sum('cost_to_company');
        $salariesKumasiCount = $salariesKumasi->count();    

        $salariesShyhills =  Salary::where('field_id', 7)->where('payment_status', 'approved')->get();
        $salariesShyhillsSum = $salariesShyhills->sum('cost_to_company');
        $salariesShyhillsCount = $salariesShyhills->count(); 
        // return view('salaries.transaction');
        return view('salaries.transaction', compact('salaries', 'salariesAccraSum', 'salariesAccraCount', 'salariesBotweSum', 'salariesBotweCount', 'salariesTemaSum', 'salariesTemaCount', 'salariesTakoradiSum', 'salariesTakoradiCount', 'salariesKoforiduaSum', 'salariesKoforiduaCount', 'salariesKumasiSum', 'salariesKumasiCount', 'salariesShyhillsSum', 'salariesShyhillsCount'));

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
        // dd($request->all()); 

        // get invoices for incoming month
        $invoices = Invoice::where('invoice_month', Carbon::parse($request->month)->format('Y-m-d'))->get();
        $invoicesTotal = $invoices->sum('total');
        $invoicesCount = $invoices->count();
        // dd(, $invoices->sum('total'));

        $Accrainvoices = Invoice::whereRelation('client', 'field_id', '1')->where('invoice_month', Carbon::parse($request->month)->format('Y-m-d'))->get();
        $AccrainvoicesTotal = $Accrainvoices->sum('total');
        $AccrainvoicesCount = $Accrainvoices->count();
        // dd($Accrainvoices->count(), $Accrainvoices->sum('total'));

        $Botweinvoices = Invoice::whereRelation('client', 'field_id', '2')->where('invoice_month', Carbon::parse($request->month)->format('Y-m-d'))->get();
        $BotweinvoicesTotal = $Botweinvoices->sum('total');
        $BotweinvoicesCount = $Botweinvoices->count();

        $Temainvoices = Invoice::whereRelation('client', 'field_id', '3')->where('invoice_month', Carbon::parse($request->month)->format('Y-m-d'))->get();
        $TemainvoicesTotal = $Temainvoices->sum('total');
        $TemainvoicesCount = $Temainvoices->count();

        $Takoradinvoces = Invoice::whereRelation('client', 'field_id', '4')->where('invoice_month', Carbon::parse($request->month)->format('Y-m-d'))->get();
        $TakoradinvocesTotal = $Takoradinvoces->sum('total');
        $TakoradinvocesCount = $Takoradinvoces->count();
        $Koforiduainvoices = Invoice::whereRelation('client', 'field_id', '5')->where('invoice_month', Carbon::parse($request->month)->format('Y-m-d'))->get();
        $KoforiduainvoicesTotal = $Koforiduainvoices->sum('total');
        $KoforiduainvoicesCount = $Koforiduainvoices->count();  

        $Kumasinvoices = Invoice::whereRelation('client', 'field_id', '6')->where('invoice_month', Carbon::parse($request->month)->format('Y-m-d'))->get();
        $KumasinvoicesTotal = $Kumasinvoices->sum('total');             
        $KumasinvoicesCount = $Kumasinvoices->count();

        $Shyhillsinvoices = Invoice::whereRelation('client', 'field_id', '7')->where('invoice_month', Carbon::parse($request->month)->format('Y-m-d'))->get();
        $ShyhillsinvoicesTotal = $Shyhillsinvoices->sum('total');             
        $ShyhillsinvoicesCount = $Shyhillsinvoices->count();    



        // get salaries for incoming month
        $salaries = Salary::select('client_id', DB::raw('SUM(cost_to_company) as total_salary'))
                            ->groupBy('client_id')
                            ->where('salary_month', Carbon::parse($request->month)->startOfMonth()->format('Y-m-d H:i:s'))
                            ->get();
        $salariesTotal = $salaries->sum('total_salary');

        $Accrasalaries = Salary::select('client_id', DB::raw('SUM(cost_to_company) as total_salary'))
                        ->groupBy('client_id')
                        ->where('field_id', 1)
                        ->where('salary_month', Carbon::parse($request->month)->startOfMonth()->format('Y-m-d H:i:s'))
                        ->get();
        // dd( $Accrasalaries);
        $AccrasalariesTotal = $Accrasalaries->sum('total_salary');
        $AccrasalariesCount = $Accrasalaries->count();

        $Botwesalaries = Salary::select('client_id', DB::raw('SUM(cost_to_company) as total_salary'))   
                        ->groupBy('client_id')
                        ->where('field_id', 2)
                        ->where('salary_month', Carbon::parse($request->month)->startOfMonth()->format('Y-m-d H:i:s'))
                        ->get();    
        $BotwesalariesTotal = $Botwesalaries->sum('total_salary');
        $BotwesalariesCount = $Botwesalaries->count();

        $Temasalaries = Salary::select('client_id', DB::raw('SUM(cost_to_company) as total_salary'))   
                        ->groupBy('client_id')
                        ->where('field_id', 3)
                        ->where('salary_month', Carbon::parse($request->month)->startOfMonth()->format('Y-m-d H:i:s'))
                        ->get();
        $TemasalariesTotal = $Temasalaries->sum('total_salary');
        $TemasalariesCount = $Temasalaries->count();

        $Takoradisalaries = Salary::select('client_id', DB::raw('SUM(cost_to_company) as total_salary'))   
                        ->groupBy('client_id')
                        ->where('field_id', 4)
                        ->where('salary_month', Carbon::parse($request->month)->startOfMonth()->format('Y-m-d H:i:s'))
                        ->get();
        $TakoradisalariesTotal = $Takoradisalaries->sum('total_salary');
        $TakoradisalariesCount = $Takoradisalaries->count();


        $Koforiduasalaries = Salary::select('client_id', DB::raw('SUM(cost_to_company) as total_salary'))   
                        ->groupBy('client_id')
                        ->where('field_id', 5)
                        ->where('salary_month', Carbon::parse($request->month)->startOfMonth()->format('Y-m-d H:i:s'))
                        ->get();
        $KoforiduasalariesTotal = $Koforiduasalaries->sum('total_salary');
        $KoforiduasalariesCount = $Koforiduasalaries->count();

        $Kumasalaries = Salary::select('client_id', DB::raw('SUM(cost_to_company) as total_salary'))   
                        ->groupBy('client_id')
                        ->where('field_id', 6)
                        ->where('salary_month', Carbon::parse($request->month)->startOfMonth()->format('Y-m-d H:i:s'))
                        ->get();
        $KumasalariesTotal = $Kumasalaries->sum('total_salary');
        $KumasalariesCount = $Kumasalaries->count();

        $Shyhillssalaries = Salary::select('client_id', DB::raw('SUM(cost_to_company) as total_salary'))   
                        ->groupBy('client_id')
                        ->where('field_id', 7)
                        ->where('salary_month', Carbon::parse($request->month)->startOfMonth()->format('Y-m-d H:i:s'))
                        ->get();
        $ShyhillssalariesTotal = $Shyhillssalaries->sum('total_salary');
        $ShyhillssalariesCount = $Shyhillssalaries->count();  
      
        // dd($salaries);
        return view('salaries.invpayrollview', compact('invoices', 'AccrainvoicesTotal', 'AccrainvoicesCount', 'BotweinvoicesTotal', 'BotweinvoicesCount', 'TemainvoicesTotal', 'TemainvoicesCount', 'TakoradinvocesTotal', 'TakoradinvocesCount', 'KoforiduainvoicesTotal', 'KoforiduainvoicesCount', 'KumasinvoicesTotal', 'KumasinvoicesCount', 'ShyhillsinvoicesTotal', 'ShyhillsinvoicesCount',  'salaries',  'invoicesTotal', 'invoicesCount', 'salariesTotal', 'AccrasalariesTotal', 'AccrasalariesCount', 'BotwesalariesTotal', 'BotwesalariesCount', 'TemasalariesTotal', 'TemasalariesCount', 'TakoradisalariesTotal', 'TakoradisalariesCount', 'KoforiduasalariesTotal', 'KoforiduasalariesCount', 'KumasalariesTotal', 'KumasalariesCount', 'ShyhillssalariesTotal', 'ShyhillssalariesCount'));
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
     * Upload salaries from Excel file.
     */
    public function uploadSalaries(SalariesUploadRequest $request)
    {

        $collection = (new SalaryImport)->toCollection( $request->file('excelFile'));
        // dd($collection);
        foreach ($collection[0] as $row) {
            // echo 'Updating salary for Employee ID: '. $row['employee_id']. ' -  ' . Carbon::createFromFormat('F, Y', $row['salary_month'])->startOfMonth()->format('Y-m-d H:i:s') . '<br>';   
        //  change date to start of month format Y-m-d H:i:s

            $salary = Salary::where('employee_id', $row['employee_id'])
                            ->where('salary_month', Carbon::createFromFormat('F, Y', $row['salary_month'])->startOfMonth()->format('Y-m-d H:i:s'))
                            ->first();
          
        //   echo 'Updating salary for Employees: ' . $salary . '<br>';   
            
            $salary->gross_salary = $row['gross_salary'] ?? $salary->gross_salary ;
            $salary->total_deductions = $row['total_deductions'] ?? $salary->total_deductions ;
            $salary->net_salary = $row['net_salary'] ?? $salary->net_salary ;
            $salary->ssnit_comp_cont_13 = $row['ssnit_comp_cont_13'] ?? $salary->ssnit_comp_cont_13 ;
            $salary->ssnit_tobe_paid13_5 = $row['ssnit_tobe_paid13_5'] ?? $salary->ssnit_tobe_paid13_5 ;
            $salary->cost_to_company = $row['cost_to_company'] ?? $salary->cost_to_company ;  
            $salary->airtime_allowance = $row['airtime_allowance'] ?? $salary->airtime_allowance ;
            $salary->overtime = $row['overtime'] ?? $salary->overtime ;
            $salary->reimbursements = $row['reimbursements'] ?? $salary->reimbursements ;
            $salary->transport_allowance = $row['transport_allowance'] ?? $salary->transport_allowance ;
            $salary->ssnit_tier2_5d = $row['ssnit_tier2_5d'] ?? $salary->ssnit_tier2_5d ;
            $salary->ssnit_tier2_5 = $row['ssnit_tier2_5'] ?? $salary->ssnit_tier2_5 ;
            $salary->tax = $row['tax'] ?? $salary->tax ;
            $salary->ssnit_tier1_0_5 = $row['ssnit_tier1_0_5'] ?? $salary->ssnit_tier1_0_5 ;
            $salary->welfare = $row['welfare'] ?? $salary->welfare ;
            $salary->maintenance = $row['maintenance'] ?? $salary->maintenance ;
            $salary->absent = $row['absent'] ?? $salary->absent ;
            $salary->boot = $row['boot'] ?? $salary->boot ;                            
            $salary->iou = $row['iou'] ?? $salary->iou ;
            $salary->hostel = $row['hostel'] ?? $salary->hostel ;
            $salary->insurance = $row['insurance'] ?? $salary->insurance ;
            $salary->reprimand = $row['reprimand'] ?? $salary->reprimand ;          
            $salary->scouter = $row['scouter'] ?? $salary->scouter ;
            $salary->raincoat = $row['raincoat'] ?? $salary->raincoat ;
            $salary->meal = $row['meal'] ?? $salary->meal ;
            $salary->loan = $row['loan'] ?? $salary->loan ;
            $salary->walkin = $row['walkin'] ?? $salary->walkin ;
            $salary->amnt_ded_cof_start_date = $row['amnt_ded_cof_start_date'] ?? $salary->amnt_ded_cof_start_date ;
            $salary->other_deductions = $row['other_deductions'] ?? $salary->other_deductions ;
            $salary->save();    
            
            }

        return back()->with('success', 'Salaries uploaded and updated successfully.');
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

        // dd($request->submit);
        
        $salaryIds = $request->input('salary', []);
        // dd($salaryIds);
        if (empty($salaryIds)) {
            return back()->with('error', 'No salaries selected for DELETION or APPROVAL.');
        }

        if(isset($request->submit) && $request->submit == 'delete')
        {
            // echo 'Deleting';
            Salary::whereIn('id', $salaryIds)->delete();
            return back()->with('danger', 'Deleted salaries with IDs: ' . implode(', ', $salaryIds));
        }

       elseif(isset($request->submit) && $request->submit == 'approve')
        {
            // echo 'Approving';
            
            Salary::whereIn('id', $salaryIds)->update(['payment_status' => 'approved']);
            return back()->with('success', 'Approved salaries with IDs: ' . implode(', ', $salaryIds));
        }
    }





}

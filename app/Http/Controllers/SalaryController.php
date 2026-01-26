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
use App\Models\PaymentInfo;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Sum;

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


        $banks = Bank::all();
        $fields = Field::all(); 

        $groupedBankSalaries = Salary::where('salary_month', $date)->whereIn('bank_id', $banks->pluck('id')->toArray())->groupBy('bank_id')->get(['bank_id', DB::raw('SUM(gross_salary) as gross'), DB::raw('SUM(total_deductions) as deductions'),  DB::raw('SUM(cost_to_company) as paid'),  DB::raw('COUNT(*) as total_employees')]);
        // dd($groupedBankSalaries->sum('total_employees'));

        $groupedCashkSalaries = Salary::where('salary_month', $date)->where('payment_type', 'Cash')->whereIn('field_id', $fields->pluck('id')->toArray())->groupBy('field_id')->get(['field_id', DB::raw('SUM(gross_salary) as gross'), DB::raw('SUM(total_deductions) as deductions'),  DB::raw('SUM(cost_to_company) as paid'),  DB::raw('COUNT(*) as total_employees')]);

        $salariesTaxes = Salary::where('salary_month', $date)->where('tax', '>', 0)->whereIn('field_id', $fields->pluck('id')->toArray())->groupBy('field_id')->get(['field_id', DB::raw('SUM(cost_to_company) as paid'), DB::raw('SUM(tax) as tax'),  DB::raw('COUNT(*) as total_employees')]);

        $salariesPensions = Salary::where('salary_month', $date)->where('ssnit_tobe_paid13_5', '>', 0)->whereIn('field_id', $fields->pluck('id')->toArray())->groupBy('field_id')->get(['field_id', DB::raw('SUM(ssnit_tier1_0_5) as tier1'), DB::raw('SUM(ssnit_tier2_5) as tier2'), DB::raw('SUM(ssnit_comp_cont_13) as cont13'), DB::raw('SUM(ssnit_tobe_paid13_5) as cont13_5'),  DB::raw('SUM(cost_to_company) as paid'),   DB::raw('COUNT(*) as total_employees')]);

        $salariesMaster = Salary::where('salary_month', $date)->get();

        return view('salaries.salariesmonth', compact('groupedBankSalaries','groupedCashkSalaries', 'salariesTaxes', 'salariesPensions', 'date', 'salariesMaster'));
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
     * Display salaries bank month view.
     */
    public function bankMonth($bank_id, $month)
    {
        // get all from salaries where payment type is bank and is equal to incoming bank_id and month is in current month
        $bank = Bank::findOrfail($bank_id);
        $BankSalaries = Salary::where('salary_month', $month)->where('bank_id', $bank_id)->get();
        // dd( $BankSalaries);
        return view('salaries.bankmonth', compact('BankSalaries', 'bank', 'month'));
    }



    /**
     * Display salaries Cash month view.
     */
    public function cashMonth($field_id, $month)
    {
        // get all cash salaries where field office is field_id and month is incoming month
        $field = Field::findOrfail($field_id);
        // dd($field->name, $month);
        $CashSalaries = Salary::where('salary_month', $month)->where('payment_type', 'Cash')->where('field_id', $field_id)->get();
        // dd($CashSalaries);
        return view('salaries.cashmonth', compact('CashSalaries', 'field', 'month'));

    }


    /**
     * Display salaries Taxes for a month.
     */
    public function TaxMonth($field_id, $month)
    {
        // dd($field_id, $month);
        $field = Field::findOrfail($field_id);
        $salariesTaxes = Salary::where('salary_month', $month)->where('tax', '>', 0)->where('field_id', $field_id)->get();
        return view('salaries.taxmonth', compact('salariesTaxes', 'field', 'month'));
    }


        /**
     * Display salaries Pensions for a month.
     */
    public function PensionMonth($field_id, $month)
    {
        // dd($field_id, $month);
        $field = Field::findOrfail($field_id);
        $salariesPensions = Salary::where('salary_month', $month)->where('ssnit_tobe_paid13_5', '>', 0)->where('field_id', $field_id)->get();
        return view('salaries.pensionmonth', compact('salariesPensions', 'field', 'month'));
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
        $month = Carbon::parse($request->month)->format('F, Y');
        // $invoices = Invoice::where('invoice_month', Carbon::parse($request->month)->format('Y-m-d'))->get();
        $invoices = Invoice::select('client_id', DB::raw('SUM(total) as total_invoice'))
                            ->groupBy('client_id')
                            ->where('invoice_month', Carbon::parse($request->month)->format('Y-m-d'))->get();

        $invoicesTotal = $invoices->sum('total_invoice');
        // $invoicesCount = $invoices->count();
        // dd( $invoices);

        // foreach($invoices as $invoice)
        //     {
        //         echo $invoice . "<br>";
        //     }
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
        return view('salaries.invpayrollview', compact('month', 'invoices', 'AccrainvoicesTotal', 'AccrainvoicesCount', 'BotweinvoicesTotal', 'BotweinvoicesCount', 'TemainvoicesTotal', 'TemainvoicesCount', 'TakoradinvocesTotal', 'TakoradinvocesCount', 'KoforiduainvoicesTotal', 'KoforiduainvoicesCount', 'KumasinvoicesTotal', 'KumasinvoicesCount', 'ShyhillsinvoicesTotal', 'ShyhillsinvoicesCount',  'salaries',  'invoicesTotal', 'salariesTotal', 'AccrasalariesTotal', 'AccrasalariesCount', 'BotwesalariesTotal', 'BotwesalariesCount', 'TemasalariesTotal', 'TemasalariesCount', 'TakoradisalariesTotal', 'TakoradisalariesCount', 'KoforiduasalariesTotal', 'KoforiduasalariesCount', 'KumasalariesTotal', 'KumasalariesCount', 'ShyhillssalariesTotal', 'ShyhillssalariesCount'));
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

                // Deduct Tax
                // $ssnitNumber = PaymentInfo::where('employee_id', $employee->id)->whereNotNull('ssnit_number')->get();
                // $ssnit_tier2_5 = 0;
                // $ssnit_tier1_0_5 = 0;
                // $ssnit_13 = 0;
                // $ssnit_13_5 = 0;

                // $tax = 0;
                // $employee_basic_taxAmount_minus_ssnt = 0;

                // if($employee->paymentInfo?->ssnit_number !== '' && $employee->paymentInfo?->ssnit_number !== null)
                //     {
                //         // echo "Processing salary for Employee SSNIT ID: " . $employee->paymentInfo->ssnit_number . "<br><br>";

                //         //  SSNIT transactions HERE
                //         $ssnit_tier2_5 = $employee->basic_salary * 0.05;
                //         $ssnit_tier1_0_5 = $employee->basic_salary * 0.005;
                //         $ssnit5_5 = $employee->basic_salary * 0.055;
                //         $ssnit_13 = $employee->basic_salary * 0.13;
                //         $ssnit_13_5 = $employee->basic_salary * 0.135;

                //         $employee_basic_taxAmount_minus_ssnt = $employee->basic_salary - $ssnit5_5  ;
                //         // echo "Processing salary Tier 2 (5%) = " . $ssnit_tier2_5 .  " Tier 1 (0.5%) = ". $ssnit_tier1_0_5 . "  SSNIT (13%) = ".$ssnit_13. " SSNIT (13.5%) = ".  $ssnit_13_5   ."<br><br>";
                //     }

                // if($employee->basic_salary > 490.00 )
                // {
                //     // TAX CALCULATIONS
                //         // echo "Employee Basic salary = "  .$employee->basic_salary ." TAX = ".  $this->taxEmployee($employee->basic_salary)  ."<br>";
                //       if($employee->paymentInfo?->ssnit_number !== '' && $employee->paymentInfo?->ssnit_number !== null && $employee_basic_taxAmount_minus_ssnt >= 0)
                //         {
                //         $tax = $this->taxEmployee($employee_basic_taxAmount_minus_ssnt) ;
                //         // echo "Employee Basic salary = "  .$employee->basic_salary ." TAX = ".  $tax  ."<br>";

                //         }else{
                //         $tax = $this->taxEmployee($employee->basic_salary) ;
                //         // echo "Employee Basic salary = "  .$employee->basic_salary ." TAX = ".  $tax  ."<br>";
                //         }                    
                // }

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
                
                // $salary->ssnit_tier2_5d = $ssnit_tier2_5;
                // $salary->ssnit_tier2_5 = $ssnit_tier2_5;
                // $salary->ssnit_tier1_0_5 = $ssnit_tier1_0_5;
                // $salary->ssnit_comp_cont_13 = $ssnit_13;
                // $salary->ssnit_tobe_paid13_5 = $ssnit_13_5;
                // $salary->tax = $tax;
               
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

    }


    /**
     * Calculating TAX on Basic Salary
     */
    public $next1Tax = 0;
    public $next2Tax = 0;
    public $next3Tax = 0;
    public $CummulativeTaxHold = [] ;

    public function taxEmployee($basic_salary)
    {
            $next1 = 110;
            $next2 = 130;
            // $next3 = 3166.67;
            $next1Tax = 0.00;
            $next3Tax = 0.00;


            $next1Tax = $this->next1Tax($basic_salary);
            // return $next1Tax[0]. " -- ". $next1Tax[1];
            if($next1Tax[0] <= 0 ){
                // $this->next1Tax = 0;
                // $this->CummulativeTaxHold[0] = 0;
                return 0;
            }else{
                //  $this->next1Tax = $next1Tax[0];
                  $this->next1Tax = $next1Tax[0];

                if($next1Tax[1] > $next1 &&  $next1Tax[0] !== 0)
                {
                    //  return  "run NEXT2 with this balances  " . $next1Tax[1];
                    $this->next2Tax = $this->next2Tax($next1Tax[1]);
                    //  $this->CummulativeTaxHold[1] =  $this->next2Tax[0];
                    // return $this->next2Tax[0]. " / " .$this->next2Tax[1]; //. " / ".$this->CummulativeTaxHold[0] + $this->CummulativeTaxHold[1];
                }
                if($this->next2Tax[1]  < 3166.67){
                    
                //    return  "run NEXT with this balances  " . $this->next2Tax[1];
                    $this->next3Tax = $this->next2Tax[1] * 0.175 ;
                    return $this->next3Tax + $this->next2Tax[0] + $this->next1Tax ; //. " / ".$this->CummulativeTaxHold[0] + $this->CummulativeTaxHold[1];
                }
            }
          
    }


    public function next1Tax($basic_salary)
    {
            $first = 490;
            $next1 = 110;
            $next1_rate = 0.05;
            $next1_amnt = 0;
            $next1_tax = 0;
            $balance = 0;

            // subtract 110 of the next1 and find 10% of that
            $next1_amnt = $basic_salary - $first;
            if($next1_amnt < $next1)
            {
                 $next1_tax =  $next1_amnt * $next1_rate;

                //  return $next1_tax;
            }else
                {
                  $balance =  $next1_amnt -  $next1;
                  $next1_tax =  $next1 * $next1_rate;
                }

            return [$next1_tax,  $balance ] ;

    }

    public function next2Tax($balance)
    {
            $next1 = 110;
            $next2 = 130;
            $next2_rate = 0.10;
            $next2_amnt = 0;
            $next2_tax = 0;

        $next2_amnt = $balance - $next2;

        if ($next2_amnt < $next2)
            {
                $next2_tax =  $next2_amnt * $next2_rate;
            }
        else{
                $next2_tax  =  $next2 * $next2_rate ;
            }
      
            // $this->CummulativeTaxHold[] =  $next2_tax;
            return  [$next2_tax ,$next2_amnt] ;
        
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
        $clients = Client::all();
        
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
          
        //   echo 'Updating salary for Employees: '. $salary->employee->tax_button. " ---".$row['employee_id'] . " " .$row['basic_salary']. " / ". $row['allowances'] . " / ". $row['airtime_allowance'] . " / ". $row['reimbursements'] . " / ". $row['transport_allowance'] ." / ". $row['welfare'] ." / ". $row['maintenance'] ." / ". $row['absent'] ." / ". $row['boot'] ." / ". $row['iou'] ." / ". $row['hostel'] ." / ". $row['insurance'] ." / ". $row['reprimand'] ." / ". $row['scouter'] ." / ". $row['raincoat'] ." / ". $row['meal'] ." / ". $row['loan'] ." / ". $row['meal']  ." / ". $row['walkin'] ." / ". $row['amnt_ded_cof_start_date'] ." / ". $row['other_deductions'] .'<br>';   
            

                // Deduct Tax
                // $ssnitNumber = PaymentInfo::where('employee_id', $employee->id)->whereNotNull('ssnit_number')->get();
                $ssnit_tier2_5 = 0;
                $ssnit_tier1_0_5 = 0;
                $ssnit_13 = 0;
                $ssnit_13_5 = 0;

                $tax = 0;
                $employee_basic_taxAmount_minus_ssnt = 0;

                // CHECK IF THE PERSON HAS SSNIT BUTTON TURNED ON
                if(isset($salary->employee->ssnit_button) == "on")
                {
                    if($salary->paymentInfo?->ssnit_number !== '' && $salary->paymentInfo?->ssnit_number !== null)
                        {
                            // echo "Processing salary for Employee SSNIT ID: " . $employee->paymentInfo->ssnit_number . "<br><br>";

                            //  SSNIT transactions HERE
                            $ssnit_tier2_5 = $row['basic_salary'] * 0.05;
                            $ssnit_tier1_0_5 = $row['basic_salary'] * 0.005;
                            $ssnit5_5 = $row['basic_salary'] * 0.055;
                            $ssnit_13 = $row['basic_salary'] * 0.13;
                            $ssnit_13_5 = $row['basic_salary'] * 0.135;

                            $employee_basic_taxAmount_minus_ssnt = $row['basic_salary'] - $ssnit5_5  ;
                            // echo "Processing salary Tier 2 (5%) = " . $ssnit_tier2_5 .  " Tier 1 (0.5%) = ". $ssnit_tier1_0_5 . "  SSNIT (13%) = ".$ssnit_13. " SSNIT (13.5%) = ".  $ssnit_13_5   ."<br><br>";
                        }
                }

                 // CHECK IF THE PERSON HAS TAX BUTTON TURNED ON
                if(isset($salary->employee->tax_button) == "on")
                {
                    
                    if($row['basic_salary'] > 490.00 )
                    {
                        // TAX CALCULATIONS
                            // echo "Employee Basic salary = "  .$row['basic_salary'] ." ----/ TAX === ".  $this->taxEmployee($employee_basic_taxAmount_minus_ssnt)  ."<br>";
                        if($salary->paymentInfo?->ssnit_number !== '' && $salary->paymentInfo?->ssnit_number !== null && $employee_basic_taxAmount_minus_ssnt >= 0)
                            {
                            $tax = $this->taxEmployee($employee_basic_taxAmount_minus_ssnt) ;
                            // echo "Employee Basic salary = ".$row['employee_id'] . " / "  .$row['basic_salary'] ." TAX = ".  $tax  ."<br>";

                            }else{
                            $tax = $this->taxEmployee($row['basic_salary']) ;
                            // echo "Employee Basic salary = ".$row['employee_id'] . " / "  .$row['basic_salary'] ." TAX = ".  $tax  ."<br>";
                            }                    
                    } 
                }




            $salary->basic_salary = $row['basic_salary'] ?? $salary->basic_salary ;
            $salary->allowances = $row['allowances'] ?? $salary->allowances ;
            $salary->airtime_allowance = $row['airtime_allowance'] ?? $salary->airtime_allowance ;
            $salary->overtime = $row['overtime'] ?? $salary->overtime ;
            $salary->reimbursements = $row['reimbursements'] ?? $salary->reimbursements ;
            $salary->transport_allowance = $row['transport_allowance'] ?? $salary->transport_allowance ;
           
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

            // // WORK SSNIT
            $salary->ssnit_tier2_5d = $ssnit_tier2_5  ?? $salary->ssnit_tier2_5d ;
            $salary->ssnit_tier2_5 = $ssnit_tier2_5 ?? $salary->ssnit_tier2_5 ;
            $salary->ssnit_tier1_0_5 =  $ssnit_tier1_0_5 ?? $salary->ssnit_tier1_0_5 ;

            // // WORK TAX
            $salary->tax = $tax ??  $salary->tax;

            // // SUM GROSS, TOTAL DEDUCTTIONS AND NET SALARY
            $grossSalary = $row['basic_salary'] + $row['allowances'] + $row['airtime_allowance'] + $row['overtime'] + $row['reimbursements'] + $row['transport_allowance'];
            $totalDeduction = $tax + $ssnit_tier2_5 + $ssnit_tier1_0_5 +  $row['welfare'] + $row['maintenance'] + $row['absent']  +  $row['boot'] +  $row['iou'] +  $row['hostel'] + $row['insurance'] + $row['reprimand'] + $row['scouter'] +  $row['raincoat'] +  $row['meal'] + $row['loan'] + $row['walkin'] + $row['amnt_ded_cof_start_date'] + $row['other_deductions'];
            $netSalay = $grossSalary - $totalDeduction ;
           
            $salary->gross_salary = $grossSalary ?? $salary->gross_salary ;
            $salary->total_deductions = $totalDeduction ?? $salary->total_deductions ;
            $salary->net_salary = $netSalay ?? $salary->net_salary ;

            // // WORK 
            $salary->ssnit_comp_cont_13 =  $ssnit_13 ?? $salary->ssnit_comp_cont_13 ;
            $salary->ssnit_tobe_paid13_5 = $ssnit_13_5 ?? $salary->ssnit_tobe_paid13_5 ;
            $salary->cost_to_company = $netSalay + $ssnit_13_5 ?? $salary->cost_to_company ;  

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


    /**
     * Get all guards with the client ID and Month
     */
    public function PayrollGuards ($client_id, $month)
    {

            // dd($client_id, $month);
        $date = Carbon::createFromFormat('F, Y',$month)->startOfMonth()->format('Y-m-d H:i:s');
        // dd($date);
        $salaries = Salary::where('salary_month', $date)->where('client_id', $client_id)->get();
        // dd($salaries);
        return view('salaries.invpayrollGuards', compact('salaries', 'month'));

    }



}

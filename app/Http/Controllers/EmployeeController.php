<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeClientRequest;
use App\Models\employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Requests\StoreemployeeRequest;
use App\Http\Requests\UpdateemployeeRequest;
use App\Http\Requests\StorePaymentInfoRequest;
use App\Http\Requests\UpdatePaymentInfoRequest;
use App\Models\Bank;
use App\Models\Client;
use App\Models\Department;
use App\Models\Field;
use App\Models\PaymentInfo;
use App\Models\Role;
use App\Models\Salary;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
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
        $employees = employee::where('ho_status', 'approved')->where('status', 'Active')->orwhere('status', 'Terminated')->get();
        $activeEmployees = employee::where('ho_status', 'approved')->where('status', 'Active')->count();
        $terminatedEmployees = employee::where('ho_status', 'approved')->where('status', 'Terminated')->count();  


        $Accra = employee::where('ho_status', 'approved')->where('field_id', 1)->where('status', 'Active')->orwhere('status', 'Terminated')->get();
        $employeeAccra=[];
        foreach($Accra as $accra)
            {
                if($accra->field_id == 1)
                    {
                        $employeeAccra[] = $accra;
                    }
                // echo $data->field_id . " ". "<br>";
            }
        $employeeAccraTerminated = employee::where('ho_status', 'approved')->where('field_id', 1)->where('status', 'Terminated')->count();  
        $employeeAccraActive = employee::where('ho_status', 'approved')->where('field_id', 1)->where('status', 'Active')->count();

        $Botwe = employee::where('ho_status', 'approved')->where('field_id', 2)->where('status', 'Active')->orwhere('status', 'Terminated')->get();
        $employeeBotwe=[];
        foreach($Botwe as $botw)
            {
                if($botw->field_id == 2)
                    {
                        $employeeBotwe[] = $botw;
                    }
                // echo $data->field_id . " ". "<br>";
            }
        $employeeBotweTerminated = employee::where('ho_status', 'approved')->where('field_id', 2)->where('status', 'Terminated')->count();  
        $employeeBotweActive = employee::where('ho_status', 'approved')->where('field_id', 2)->where('status', 'Active')->count();

        $Tema = employee::where('ho_status', 'approved')->where('field_id', 3)->where('status', 'Active')->orwhere('status', 'Terminated')->get();
        $employeeTema1= collect();
        // $employeeTema=[];
        foreach($Tema as $tem)
            {
                if($tem->field_id == 3)
                    {
                         $employeeTema1->push($tem);
                    }
                // echo $data->field_id . " ". "<br>";
            }
        // dd($employeeTema1);
        $employeeTemaTerminated = employee::where('ho_status', 'approved')->where('field_id', 3)->where('status', 'Terminated')->count();  
        $employeeTemaActive = employee::where('ho_status', 'approved')->where('field_id', 3)->where('status', 'Active')->count();  
        
        $Shyhills = employee::where('ho_status', 'approved')->where('field_id', 7)->where('status', 'Active')->orwhere('status', 'Terminated')->get();
       
        $employeeShyhills=collect();
        foreach($Shyhills as $shai)
            {
                if($shai->field_id == 7)
                    {
                        $employeeShyhills->push($shai);
                    }
                // echo $data->field_id . " ". "<br>";
            }
        // dd($employeeShyhills);
        $employeeTema = $employeeTema1->concat( $employeeShyhills);

        $employeeShyhillsTerminated = employee::where('ho_status', 'approved')->where('field_id', 7)->where('status', 'Terminated')->count();  
        $employeeShyhillsActive = employee::where('ho_status', 'approved')->where('field_id', 7)->where('status', 'Active')->count();
        
        // $employeeTema = employee::where('ho_status', 'approved')->whereIn('field_id', [3,7])->where('status', 'Active')->orwhere('status', 'Terminated')->get();


        $Takoradi = employee::where('field_id', 4)->where('ho_status', 'approved')->where('status', 'Active')->orwhere('status', 'Terminated')->get();
        $employeeTakoradi=[];
        foreach($Takoradi as $tako)
            {
                if($tako->field_id == 4)
                    {
                        $employeeTakoradi[] = $tako;
                    }
                // echo $data->field_id . " ". "<br>";
            }
        // dd($employeeTakoradi);

        $employeeTakoradiTerminated = employee::where('ho_status', 'approved')->where('field_id', 4)->where('status', 'Terminated')->count();  
        $employeeTakoradiActive = employee::where('ho_status', 'approved')->where('field_id', 4)->where('status', 'Active')->count();

        $Koforidua = employee::where('ho_status', 'approved')->where('field_id', 5)->where('status', 'Active')->orwhere('status', 'Terminated')->get();
        
        $employeeKoforidua=[];
        foreach($Koforidua as $kofo)
            {
                if($kofo->field_id == 5)
                    {
                        $employeeKoforidua[] = $kofo;
                    }
                // echo $data->field_id . " ". "<br>";
            }
        // dd($employeeKoforidua);

        $employeeKoforiduaTerminated = employee::where('ho_status', 'approved')->where('field_id', 5)->where('status', 'Terminated')->count();  
        $employeeKoforiduaActive = employee::where('ho_status', 'approved')->where('field_id', 5)->where('status', 'Active')->count();  

        $Kumasi = employee::where('ho_status', 'approved')->where('field_id', 6)->where('status', 'Active')->orwhere('status', 'Terminated')->get();
        $employeeKumasi=[];
        foreach($Kumasi as $kuma)
            {
                if($kuma->field_id == 6)
                    {
                        $employeeKumasi[] = $kuma;
                    }
                // echo $data->field_id . " ". "<br>";
            }
        // dd($employeeKumasi);
       
        $employeeKumasiTerminated = employee::where('ho_status', 'approved')->where('field_id', 6)->where('status', 'Terminated')->count();  
        $employeeKumasiActive = employee::where('ho_status', 'approved')->where('field_id', 6)->where('status', 'Active')->count();


        return view('employees.index', compact( 'employees', 'activeEmployees', 'terminatedEmployees', 'employeeAccra', 'employeeAccraTerminated', 'employeeAccraActive', 'employeeBotwe', 'employeeBotweTerminated', 'employeeBotweActive', 'employeeTema', 'employeeTemaTerminated', 'employeeTemaActive', 'employeeTakoradiActive', 'employeeTakoradiTerminated','employeeTakoradi', 'employeeKoforiduaActive', 'employeeKoforiduaTerminated','employeeKoforidua', 'employeeKumasiActive', 'employeeKumasiTerminated','employeeKumasi', 'employeeShyhills', 'employeeShyhillsTerminated', 'employeeShyhillsActive'));
   
        }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $Departments = Department::all();
        $Roles = Role::all();
        $fieldsAll = Field::all();
        $fields = null;
        $clients = null;
        $banks = Bank::all();
        $staff =  User::all();
        $user =  Auth::user();

        $manager = $staff->where('department_id', '7')->where('role_id', '3');
        $headOfficemanager = $staff->whereIn('department_id', ['1','4'])->whereIn('role_id', ['1','3']);
        $assign_staff = [];

        if($user->role->name == 'Admin Assistant')
            {
                $assign_staff = $manager;
            }

        if($user->role->name == 'Manager')
            {
                $assign_staff = $headOfficemanager;
            }


        foreach($fieldsAll as $field )
        {
            if( $user->role?->name == 'Finance Manager' || $user->role?->name == 'Invoice' || ($user->department?->name == 'HR' && $user->role?->name == 'Manager') )
            {
                $clients = Client::where('status', 'Active')->where('ho_status', 'approved')->get();
                $fields = $fieldsAll;
                break;
            }
            if($user->field_id == '3')
            {
                $clients = Client::whereIn('id', ['3','7'])->where('status', 'Active')->where('ho_status', 'approved')->get();
                $fields = $fieldsAll->whereIn('id', ['3','7'])->values();
                break;
            }
            if($field->name == $user->field?->name)
            {
                $clients = Client::where('field_id', $field->id)->where('status', 'Active')->where('ho_status', 'approved')->get();
                $fields[] = $field;
            }
        }


        return view('employees.create', compact('Departments', 'Roles', 'clients', 'banks', 'fields', 'assign_staff'));
    }

    public function EmpPayInfo($id)
    {
        //
        $employee_pay_info = PaymentInfo::where('employee_id', $id)->first();
        $banks =  Bank::all();
        return view('employees.payinfo', compact('employee_pay_info', 'banks'));
    }


    public function EmpPayInfoUpdate(UpdatePaymentInfoRequest $request, $id)
    {
        //
        $employee_pay_info = PaymentInfo::findOrFail($id);
        // dd($request->all(), $employee_pay_info->employee->id);
        $employee_pay_info->bank_id = $request->input('bank_id');
        $employee_pay_info->acc_number = $request->input('acc_number');
        $employee_pay_info->branch = $request->input('branch');
        $employee_pay_info->branch_code = $request->input('branch_code');
        // $employee_pay_info->tin_number = $request->input('tin_number');
        // $employee_pay_info->ssnit_number = $request->input('ssnit_number');
        $employee_pay_info->user_id = Auth::id();
        $employee_pay_info->save();

        // Update Employee Model payment_type
        $employee = employee::findOrFail( $employee_pay_info->employee->id);
        // dd($employee);
        $employee->update(['payment_type' => $request->payment_type]);


        return redirect()->route('employees.ViewPayInfo',['id' => $employee_pay_info->employee_id])->with('success', 'Employee Payment Information updated successfully.');
        
        // return back()->with('success', 'Employee Payment Information updated successfully.');
    }

    

    public function EmpViewPayInfo($id)
    {
        //
        $employee_pay_info = PaymentInfo::where('employee_id', $id)->first();
        $banks =  Bank::all();
        return view('employees.viewpayinfo', compact('employee_pay_info', 'banks'));
    }



    public function EmpSalary($id)
    {
        // employee Id to get employee salaries
        $employee = employee::findOrFail($id);
        // dd($employee->salaries);

        return view('employees.salaries', compact('employee'));
    }


    public function EmpViewSalaryInfo()
    {
        //
        return view('employees.viewsalaryinfo');
    }   

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreemployeeRequest $request, StorePaymentInfoRequest $payRequest) 
    {
        //
        // dd($request->all());
        // dd(Auth::id());
        $user_id = Auth::id();
        $image = null;
        $tin_number = null;
        $ssnit_number = null;

        if($request->file('image'))
        {
           $image = ($request->file('image'))->store('images', 'public_html_disk');
        }

        if($request->input('tin'))
        {
            $tin_number = $request->input('tin_number');
        }
        if( $request->input('ssnit'))
        {
            $ssnit_number = $request->input('ssnit_number');
        }

        // dd($request->input('tin'), $tin_number, $request->input('ssnit'), $ssnit_number);
        $staff = $request->input('staff');
        $employee = new employee();
        $employee->name = $request->input('name');
        $employee->user_id = $user_id;
        $employee->status = 'Pending';
        $employee->gender = $request->input('gender');
        $employee->phone_number = $request->input('phone_number');
        $employee->channel = $request->input('channel');
        $employee->date_of_birth = $request->input('date_of_birth');
        $employee->nia_number = $request->input('nia_number');
        $employee->address = $request->input('address');
        $employee->marital_status = $request->input('marital_status');
        $employee->worker_type = $request->input('worker_type');
        $employee->date_of_joining = $request->input('date_of_joining');
        $employee->department_id = $request->input('department_id');
        $employee->role_id = $request->input('role_id');
        $employee->field_id = $request->input('field_id');
        $employee->client_id = $request->input('client_id');
        $employee->location = $request->input('location');
        $employee->tax_button = $request->input('tin');
        $employee->tin_number = $tin_number;
        $employee->ssnit_button = $request->input('ssnit');
        $employee->ssnit_number = $ssnit_number;
        $employee->basic_salary = $request->input('basic_salary');
        $employee->allowances = $request->input('allowances');
        $employee->payment_type = $request->input('payment_type');
        $employee->gurantor_name = $request->input('gurantor_name');
        $employee->gurantor_number = $request->input('gurantor_number');
        $employee->gurantor_address = $request->input('gurantor_address');
        $employee->gurantor_nia_number = $request->input('gurantor_nia_number');
        $employee->relationship = $request->input('relationship');
        $employee->image = $image;

        if(Auth::user()->role?->id == '1')
            {
               $employee->ho_status = 'approved';
               $employee->user_id2 = $user_id;
            }
        if(Auth::user()->department?->id == '7' && Auth::user()->role?->id == '3')
            {
               $employee->ho_status = 'pending';
               $employee->user_id2 = $staff;

               $employee->bran_status = 'approved';
               $employee->user_id1 = $user_id;
            }
        if(Auth::user()->department?->id == '7' && Auth::user()->role?->id == '27')
            {
               $employee->bran_status = 'pending';
               $employee->user_id1 = $staff;

               $employee->assit_status = 'pending';

            }
        $employee->save();

        $employee_pay_info = new PaymentInfo();
        $employee_pay_info->employee_id = $employee->id;
        $employee_pay_info->bank_id = $payRequest->input('bank_id');
        $employee_pay_info->acc_number = $payRequest->input('acc_number');
        $employee_pay_info->branch = $payRequest->input('branch');
        $employee_pay_info->branch_code = $payRequest->input('branch_code');
        // $employee_pay_info->tin_number = $payRequest->input('tin_number');
        // $employee_pay_info->ssnit_number = $payRequest->input('ssnit_number');
        $employee_pay_info->user_id = $user_id;
        $employee_pay_info->save();


        // LAST STEP: UPDATE EMPLOYEE WITH PAYMENT INFO ID
        $employee->payment_infos_id = $employee_pay_info->id;
        $employee->save();

        return redirect()->route('employees.show',['employee' => $employee->id])->with('success', 'Employee created successfully.');
        //return redirect()->route('employees.index')->with('success', 'Employee created successfully.');

    }

    /**
     * Display the specified resource.
     */
    public function show(employee $employee)
    {
        //
        $channels = DB::table('hubtel_channel')->get();
        $Departments = Department::all();
        $Roles = Role::all();
        $Fields = Field::all();
        $clients = Client::all();
        $banks = Bank::all();

        $user = Auth::user();
        $staff =  User::all();
        $manager = $staff->where('department_id', '7')->where('role_id', '3');
        $finance_manager = $staff->where('department_id', '1')->where('role_id', '1');
        $assign_staff = [];
        // dd($manager);
        if($user->role->name == 'Admin Assistant')
            {
                $assign_staff = $manager;
            }

        if($user->role->name == 'Manager')
            {
                $assign_staff = $finance_manager;
            }


        return view('employees.show', compact('employee', 'Departments', 'Roles', 'Fields', 'clients', 'banks', 'channels', 'assign_staff')); 
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(employee $employee)
    {
        //
        // dd($employee);
        $channels = DB::table('hubtel_channel')->get();
        $Departments = Department::all();
        $Roles = Role::all();
        $fieldsAll = Field::all();
        $Fields= null;
        $clients = null;
        $banks = Bank::all();

        $user = Auth::user();
        $staff =  User::all();
        $manager = $staff->where('department_id', '7')->where('role_id', '3');
        $finance_manager = $staff->where('department_id', '1')->where('role_id', '1');
        $assign_staff = [];
        // dd($manager);
        if($user->role->name == 'Admin Assistant')
            {
                $assign_staff = $manager;
            }

        if($user->role->name == 'Manager')
            {
                $assign_staff = $finance_manager;
            }



        foreach($fieldsAll as $field )
        {
            if( $user->role?->name == 'Finance Manager' || $user->role?->name == 'Invoice' || ($user->department?->name == 'HR' && $user->role?->name == 'Manager') )
            {
                $clients = Client::where('status', 'Active')->where('ho_status', 'approved')->get();
                $Fields = $fieldsAll;
                break;
            }
            if($user->field_id == '3')
            {
                $clients = Client::whereIn('id', ['3','7'])->where('status', 'Active')->where('ho_status', 'approved')->get();
                $Fields = $fieldsAll->whereIn('id', ['3','7'])->values();
                break;
            }
            if($field->name == $user->field?->name)
            {
                $clients = Client::where('field_id', $field->id)->where('status', 'Active')->where('ho_status', 'approved')->get();
                $Fields[] = $field;
            }
        }

        return view('employees.edit', compact('employee', 'Departments', 'Roles', 'Fields', 'clients', 'banks', 'channels', 'assign_staff'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateemployeeRequest $request, employee $employee)
    {
        //
        // dd($request->all());
        if ($request->hasFile('image')) {

            // get current image path to delete old file
            $employee = employee::findOrFail($employee->id);
            $employeeImagePath = $employee?->image; 
            // remove old file — ensure the stored value matches what you saved earlier
            $employeeImagePath = $employeeImagePath ? Storage::disk('public')->path($employeeImagePath) : null;
            // Storage::disk('public')->delete($employee?->image);
            // store returns a string path (or false on failure)
            $path = $request->file('image')->store('images', 'public_html_disk');

            if ($path === false) {
                return back()->withErrors(['image' => 'Failed to store image.']);
            }
            // save the stored path (or use basename($path) if you only store filename)
            // dd($path);
        }

        // $tax_button = null ;
        $tin_number = null;
        // $ssnit_button = null;
        $ssnit_number = null;
        $staff = $request->input('staff');

        if($request->input('tin'))
        {
            $tin_number = $request->input('tin_number');
        }
        if( $request->input('ssnit'))
        {
            $ssnit_number = $request->input('ssnit_number');
        }

        $user_id = Auth::id();
        $employee->image = $path ?? $employee->image;
        $employee->name = $request->input('name');
        $employee->gender = $request->input('gender');
        $employee->phone_number = $request->input('phone_number');
        $employee->channel = $request->input('channel');
        $employee->date_of_birth = $request->input('date_of_birth');
        $employee->nia_number = $request->input('nia_number');
        $employee->address = $request->input('address');
        $employee->marital_status = $request->input('marital_status');
        $employee->worker_type = $request->input('worker_type');
        $employee->date_of_joining = $request->input('date_of_joining');
        $employee->department_id = $request->input('department_id');
        $employee->role_id = $request->input('role_id');
        $employee->field_id = $request->input('field_id');
        $employee->client_id = $request->input('client_id');
        $employee->location = $request->input('location');
        $employee->payment_type = $request->input('payment_type');
        $employee->basic_salary = $request->input('basic_salary');

        $employee->tax_button = $request->input('tin');
        $employee->ssnit_button = $request->input('ssnit');

        $employee->tin_number = $tin_number;
        $employee->ssnit_number = $ssnit_number;

        $employee->allowances = $request->input('allowances');
        $employee->gurantor_name = $request->input('gurantor_name');
        $employee->gurantor_number = $request->input('gurantor_number');
        $employee->gurantor_address = $request->input('gurantor_address');
        $employee->gurantor_nia_number = $request->input('gurantor_nia_number');
        $employee->relationship = $request->input('relationship');


        if(Auth::user()->role?->id == '1')
            {
                $employee->status = 'Active';
               $employee->ho_status = 'approved';
               $employee->user_id2 = $user_id;
            }
        if(Auth::user()->department?->id == '7' && Auth::user()->role?->id == '3')
            {
               $employee->ho_status = 'pending';
               $employee->user_id2 = $staff;
               $employee->status = 'Pending';

               $employee->bran_status = 'approved';
               $employee->user_id1 = $user_id;
            }
        if(Auth::user()->department?->id == '7' && Auth::user()->role?->id == '27')
            {
               $employee->ho_status = null;
               $employee->bran_status = 'pending';
               $employee->user_id1 = $staff;
               $employee->status = 'Pending';

               $employee->assit_status = 'pending';

            }

        // $employee->user_id1 = Auth::id();

        $employee->save();

        return redirect()->route('employees.show',['employee' => $employee])->with('success', 'Employee updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(employee $employee)
    {
        //
        dd($employee);
    }

    
    /**
     * Get All Employees with role of security guard for incoming client.
     */
    public function GuardClient($id)
    {


       $guards =  employee::where('client_id',$id)->where('department_id', 6)->where('status', 'Active')->get();

       if($guards->isEmpty())
       {
            return back()->with('error', 'Client has no current Guards');
       }

       $clients = $guards->isNotEmpty() ? Client::all() : null ;

        return view('employees.GuardView', compact('guards', 'clients'));
    }

    public function GuardReAassign(EmployeeClientRequest $request) 
    {
        // dd($request->all());

        $employees = $request->input('employees', []);
        // $clients = $request->input('client_id', []);
        // $locations = $request->input('location', []);

        // dd($employees, $clients, $locations);
        if (empty($employees)) {
            return back()->with('error', 'No Guard Selected to ReAssign.');
        }

        collect($request->employees)->map(function ($employeeId, $index) use ($request) {

            //  $clients = $request->input('client_id', []);
            //  $locations = $request->input('location', []);

            // update category for the client and month
            $employee = employee::findOrFail($employeeId);
            if ($employee) {
                $employee->client_id = $request->client_id[$index];
                $employee->location = $request->location[$index];
                $employee->save();
            }

            
        });

         return back()->with('success', 'Guard successfully ReAssigned!');  
        

        // // dd($employees);
        // foreach ($employees as $key => $employee)
        // {

        //     // echo $employee .' ' . $clients[$key]. ' ' .$locations[$key] .'<br>';
        //     $employee = employee::findOrFail($employee);
        //     $employee->client_id = $clients[$key];
        //     $alreadyProcessed[] =  $clients[$key];
        //     $employee->location = $locations[$key];
        //     $employee->save();

        //     $employee->update([
        //         'client_id' => $clients[$key],
        //         'location' => $locations[$key],
        //     ]);
        // }

        // if(!empty( $alreadyProcessed))
        // {
        //     return redirect()->route('client.index')->with('success', 'Guard successfully ReAssigned! Client: '. implode(', ', $alreadyProcessed));
        // }
        // // // return back()->with('success', 'Guard successfully ReAssigned!');
   
    }

    public function terminateEmployee(Request $request, $id)
    {
        // dd($id, $request->status_date);
        $employee = employee::findOrFail($id);
        $employee->update(['status' => 'Terminated', 'status_date' =>  $request->status_date, 'user_id1' => Auth::id()]);

        $parsedDate = Carbon::createFromFormat('Y-m', $request->status_date)->format('Y-m-d'); 

        DB::table('nrrit')->insert([
                    'employee_id' =>  $employee->id,
                    'name' =>  $employee->name,
                    'status_month' =>    $parsedDate,
                    'status' => 'Terminated',
                ]);

        $user_id = Auth::id();


        if(Auth::user()->role?->id == '1')
            {
                // return "You are at head office";
            // $client->status = 'terminated';

            $employee->ho_status = 'approved';
            $employee->ho_date = $parsedDate;
            $employee->user_id2 = $user_id;
            $employee->save();

        return redirect()->route('employees.index')->with('error', 'Employee successfully Terminated!, ID No: and Name '. implode(', ', [$employee->id, $employee->name]));


            }
        if(Auth::user()->department?->id == '7' && Auth::user()->role?->id == '3')
            {
                // return "You are a Manager";
            // $client->status = 'pending';

            $employee->ho_status = 'pending';
            $employee->user_id2 = $request->staff;

            $employee->bran_status = 'approved';
            $employee->bran_date = $parsedDate;
            $employee->user_id1 = $user_id;
            $employee->save();

        return redirect()->route('employees.index')->with('error', 'Employee successfully Terminated!, ID No: and Name '. implode(', ', [$employee->id, $employee->name]));


            }


        // return redirect()->route('employees.index')->with('error', 'Employee successfully Terminated!, ID No: and Name '. implode(', ', [$employee->id, $employee->name]));

    }

    public function employeeReinstate(Request $request, $id)
    {
        $employee = employee::findOrFail($id);
        $employee->update(['status' => 'Re-Instate', 'status_date' =>  $request->status_date , 'user_id1' => Auth::id()]);
       
        // dd($request->status_date) ;
      
        $parsedDate = Carbon::createFromFormat('Y-m', $request->status_date)->format('Y-m-d'); 
        // dd($parsedDate) ;
      
        DB::table('nrrit')->insert([
                    'employee_id' =>  $employee->id,
                    'name' =>  $employee->name,
                    'status_month' =>   $parsedDate,
                    'status' => 'Active',
                    'status1' => 'Re-Instate',
                ]);

        $user_id = Auth::id();


        if(Auth::user()->role?->id == '1')
            {
                // return "You are at head office";
            // $client->status = 'terminated';

            $employee->ho_status = 'approved';
            $employee->ho_date = $parsedDate;
            $employee->user_id2 = $user_id;
            $employee->save();

        return redirect()->route('employees.index')->with('error', 'Employee successfully Re-instated!, ID No: and Name '. implode(', ', [$employee->id, $employee->name]));


            }
        if(Auth::user()->department?->id == '7' && Auth::user()->role?->id == '3')
            {
                // return "You are a Manager";
            // $client->status = 'pending';

            $employee->ho_status = 'pending';
            $employee->user_id2 = $request->staff;

            $employee->bran_status = 'approved';
            $employee->bran_date = $parsedDate;
            $employee->user_id1 = $user_id;
            $employee->save();

        return redirect()->route('employees.index')->with('error', 'Employee successfully Re-instated!, ID No: and Name '. implode(', ', [$employee->id, $employee->name]));


            }

        // return redirect()->route('employees.index')->with('success', 'Employee successfully Re-instated! ID No: and Name '. implode(', ', [$employee->id, $employee->name]));
    }

    public function employeesBank()
    {
        // LIST ALL BANKS , EACH WITH ALL EMPLOYEES ASSIGNED TO THAT BANK
        $bankIds = Bank::pluck('id');
        $banks = Bank::all();

        // $groupedBankEmployees = PaymentInfo::with('employee') ->whereIn('bank_id', $bankIds)->get()->groupBy('bank_id');

        $groupedBankEmployees = PaymentInfo::whereIn('bank_id', $bankIds)
                                    ->select('bank_id', DB::raw('count(employee_id) as total_employees'))
                                    ->groupBy('bank_id')
                                    ->get();

        // dd($groupedBankEmployees);
        return view('employees.banks', compact('groupedBankEmployees', 'banks'));

    }

    public function employeesBankView($bank_id)
    {
        // dd($bank_id);
        // $bank = Bank::findOrfail($bank_id);
        // $groupedBankEmployees = employee::where('bank_id', $bank_id)->get();
        $groupedBankEmployees = PaymentInfo::with('employee')->where('bank_id', $bank_id)->get();

        // dd($groupedBankEmployees);

        // foreach($groupedBankEmployees as $employee)
        //     {
        //         echo $employee->employee->name . " / " . $employee . "<br/>";
        //     } 

        return view('employees.bank_view', compact('groupedBankEmployees'));

    }


    public function employeesCash()
    {

        $fields = Field::all(); 

        $groupedCashkEmployees = employee::where('status', 'Active')->where('payment_type', 'Cash')->whereIn('field_id', $fields->pluck('id')->toArray())->groupBy('field_id')->get(['field_id',  DB::raw('COUNT(*) as total_employees')]);

        // dd($groupedCashkEmployees);
        return view('employees.cash', compact('groupedCashkEmployees', 'fields'));

    }

    public function employeesCashView($field_id)
    {

        $groupedCashEmployees = employee::where('status', 'Active')->where('payment_type', 'Cash')->where('field_id', $field_id)->get();

        // dd($groupedCashEmployees);
        return view('employees.cash_view', compact('groupedCashEmployees'));

    }


    public function employeesCashVerify(Request $request)
    {

        // dd($request->all());
         $employeeIds = $request->input('employees', []);
        //  dd($salaryIds);
        if (empty($employeeIds)) {
            return back()->with('error', 'No employee selected MoMo Name and Number Processing.');
        }

        $employees =  employee::findOrFail($request->employees);
        // dd($employees);

        foreach($employees as $employee)
            {
              $result =   $this->verifyMoMoName($employee->phone_number, $employee->channel);

                //   UPDATE DATABASE OF EMPLOYEE
                $employee->message =  $result['message'];
                $employee->responseCode =  $result['responseCode'];
                $employee->reg_name =  $result['data']['name'] ?? null;
                $employee->status_momo =  $result['data']['isRegistered'] ?? null;
                $employee->save();
                
            }

        return back()->with('success', 'Selected Employees MoMo Name and Number has completed Processing.');


    }

    public function verifyMoMoName($number , $channel)
    {
        // echo $number . " ". $channel . "<br>";
        $url = "https://rnv.hubtel.com/v2/merchantaccount/merchants/2037745/mobilemoney/verify?channel=".$channel."&customerMsisdn=".$number ;
        // $url = "https://webhook.site/832174de-8b79-4453-ac08-168031956be1";
            
        // echo $url  . "<br>";
           $data = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode('B81PkQQ:a239c6cf6e8d4dec8ae1d866ef0c633a')
            ])->get($url);

            $result =  $data->json();
        // dd( $result);
            return $result;

    }

    public function newRecruitTerminate()
    {
        return view('employees.nrrit');
    }

    public function newRecruitTerminateView (Request $request)
    {
        $reinstate = [];
        $terminate = [];
         $month = Carbon::parse($request->month);
        // New Recruit
        $newRecruit = employee::whereMonth('date_of_joining', $month->month)->where('status', 'Active')->get();
        // $newRecruitCount = count($newRecruit);
        // dd($newRecruit);

        $reinstate_id = DB::table('nrrit')->whereMonth('status_month', $month->month)->where('status1', 'Re-Instate')->pluck('employee_id')->toArray();
        // $ids = $reinstate_id->implode(', ');
        // $arrayIds = explode(',', $ids);
        // dd($reinstate_id);
        foreach($reinstate_id as $id)
            {
                $reinstate[] =  employee::findOrFail($id);
            }

        // $reinstateCount = count($reinstate);

        //  $reinstate =  employee::whereIn('id', $reinstate_id)->get();
        // dd($reinstate);
        $terminate_id = DB::table('nrrit')->whereMonth('status_month', $month->month)->where('status', 'Terminated')->pluck('employee_id')->toArray();
        foreach($terminate_id as $id)
            {
                $terminate[] =  employee::findOrFail($id);
            }

        $activeEmployees = employee::where('status', 'Active')->count();
        $activeSalaryEmployees = Salary::whereMonth('salary_month', $month->month)->count();
        // dd($activeSalaryEmployees);
        // $terminateCount = count($terminate);
        // dd($newRecruit, $reinstate, $terminate);
        // $netEmployees = $

        return view('employees.nrritview', compact('newRecruit', 'reinstate', 'terminate', 'month', 'activeSalaryEmployees', 'activeEmployees'));
    }

    public function employeesPending()
    {

            //where('status', '!=','active')->where('ho_status', 'pending')->orwhere('ho_status', null)
        $pendingEmployees = employee::whereIn('status', ['Pending','Terminated','Re-Instate'])->where('ho_status', 'pending')->orwhere('ho_status', null)->get();

        $employeeAccra = employee::where('field_id', 1)->whereIn('status', ['Pending','Terminated','Re-Instate'])->where('ho_status', 'pending')->orwhere('ho_status', null)->get();

        $employeeBotwe = employee::where('field_id', 2)->whereIn('status', ['Pending','Terminated','Re-Instate'])->where('ho_status', 'pending')->orwhere('ho_status', null)->get();

        $employeeTema = employee::where('field_id', 3)->whereIn('status', ['Pending','Terminated','Re-Instate'])->where('ho_status', 'pending')->orwhere('ho_status', null)->count();
        $Tema = employee::whereIn('field_id', [3,7])->whereIn('status', ['Pending','Terminated','Re-Instate'])->where('ho_status', 'pending')->orwhere('ho_status', null)->get();
        $employeeShyhills = employee::where('field_id', 7)->whereIn('status', ['Pending','Terminated','Re-Instate'])->where('ho_status', 'pending')->orwhere('ho_status', null)->count();


        $employeeTakoradi = employee::where('field_id', 4)->whereIn('status', ['Pending','Terminated','Re-Instate'])->where('ho_status', 'pending')->orwhere('ho_status', null)->get();

        $employeeKoforidua = employee::where('field_id', 5)->whereIn('status', ['Pending','Terminated','Re-Instate'])->where('ho_status', 'pending')->orwhere('ho_status', null)->get();

        $employeeKumasi = employee::where('field_id', 6)->whereIn('status', ['Pending','Terminated','Re-Instate'])->where('ho_status', 'pending')->orwhere('ho_status', null)->get();


        return view('employees.pending', compact( 'Tema', 'pendingEmployees',  'employeeAccra', 'employeeBotwe', 'employeeTema', 'employeeTakoradi',  'employeeKoforidua', 'employeeKumasi', 'employeeShyhills'));

    }


    public function employeesAproval(Request $request)
    {
                // dd($request->all());
         $employeeIds = $request->input('employees', []);
        //  dd($employeeIds);
        if (empty($employeeIds)) {
            return back()->with('error', 'No employee selected !');
        }
        
         $employees =  employee::findOrFail($request->employees);
        //  dd($employees);

        //  foreach ($employees as $employee )
        //     {
        //         $employee->status = 'Active';
        //         $employee->user_id2 = Auth::id();
        //         $employee->save();

        //         DB::table('nrrit')->insert([
        //             'employee_id' =>  $employee->id,
        //             'name' => $employee->name,
        //             'status_month' =>  $employee->date_of_joining,
        //             'status' => 'Active',
        //             'status1' => 'New Recruit',
        //         ]);
        //     }

        $action = $request->input('action_type') ?? $request->input('submit') ?? $request->input('decline'); 

        $alreadyProcessed = [];

        $invoice_manager =  User::where('department_id', '1')->where('role_id', '1')->first();
        $user = Auth::user();

        // $invoice_manager = $staff->;

        foreach( $employees as $employee)
        {

            if( $action == 'branch' )
                
                {
                    // dd($employee);
                    $exists = employee::where('id', $employee->id)
                        ->where('bran_status', 'approved')
                        ->exists();
                    if ($exists) {
                        $alreadyProcessed[] =  " employee: " . $employee->id;
                        continue;
                    }
                    // update branch to approved, collection to approved and dates.
                    $employee->bran_status = 'approved';
                    $employee->user_id1 = $user->id;
                    $employee->bran_date = now();

                    $employee->assit_status = 'approved';
                    $employee->ho_status = 'pending';
                    $employee->user_id2 =  $invoice_manager->id;

                    $employee->save();
                    // return 'you are a Branch Manager';

                }

            if($action == 'headOffice' )
                {
                    // // dd($client);
                    $exists = employee::where('id', $employee->id)
                        ->where('ho_status', 'approved')
                        ->exists();
                    if ($exists) {
                        $alreadyProcessed[] =  " employee: " . $employee->id;
                        continue;
                    }

                    $employee->ho_status = 'approved';
                    $employee->user_id2 = $user->id;
                    $employee->ho_date = now();

                    if($employee->status == 'Pending' || $employee->status == 'Re-Instate')
                    {
                        $employee->status = 'Active';

                            DB::table('nrrit')->insert([
                                'employee_id' =>  $employee->id,
                                'name' => $employee->name,
                                'status_month' =>  $employee->date_of_joining,
                                'status' => 'Active',
                                'status1' => 'New Recruit',
                            ]);
                    }

                    if($employee->status == 'Terminated')
                    {
                        $employee->status = 'Terminated';

                    }
                    
                    $employee->save();

                    // return 'you are at the head office';
                    
                }


            if($action == 'decline' )
                {

                    $employee->bran_status = 'pending';
                    // $employee->user_id2 = Auth::id();
                    $employee->assit_status = 'pending';
                    $employee->assit_date = now();


                    $employee->save();
                    // return 'you are at the head office Declining'; 

                }



        }



               return back()->with('success', 'Selected Employees Has Been Approved !.');


    }



}

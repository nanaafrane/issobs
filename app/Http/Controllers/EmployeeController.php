<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeClientRequest;
use App\Models\employee;
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
use Illuminate\Support\Facades\Auth;
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
        $employees = employee::all();
        $Departments = Department::all();
        $Roles = Role::all();
        $Fields = Field::all();
        $clients = Client::all();
        $banks = Bank::all();
        return view('employees.index', compact('employees','Departments', 'Roles', 'Fields', 'clients', 'banks'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $Departments = Department::all();
        $Roles = Role::all();
        $Fields = Field::all();
        $clients = Client::all();
        $banks = Bank::all();
        return view('employees.create', compact('Departments', 'Roles', 'Fields', 'clients', 'banks'));
    }


    /**
     * show for edit employee payment info.
     */
    public function EmpPayInfo($id)
    {
        //
        $employee_pay_info = PaymentInfo::where('employee_id', $id)->first();
        $banks =  Bank::all();
        return view('employees.payinfo', compact('employee_pay_info', 'banks'));
    }


    /**
     * employee update payment info.
     */
    public function EmpPayInfoUpdate(UpdatePaymentInfoRequest $request, $id)
    {
        //
        $employee_pay_info = PaymentInfo::findOrFail($id);
        // dd($employee_pay_info);
        $employee_pay_info->bank_id = $request->input('bank_id');
        $employee_pay_info->acc_number = $request->input('acc_number');
        $employee_pay_info->branch = $request->input('branch');
        $employee_pay_info->branch_code = $request->input('branch_code');
        $employee_pay_info->tin_number = $request->input('tin_number');
        $employee_pay_info->ssnit_number = $request->input('ssnit_number');
        $employee_pay_info->user_id = Auth::id();
        $employee_pay_info->save();

        return redirect()->route('employees.ViewPayInfo',['id' => $employee_pay_info->employee_id])->with('success', 'Employee Payment Information updated successfully.');
        
        // return back()->with('success', 'Employee Payment Information updated successfully.');
    }

    
    /**
     *  View Employee Bank Payment Info
     */
    public function EmpViewPayInfo($id)
    {
        //
        $employee_pay_info = PaymentInfo::where('employee_id', $id)->first();
        $banks =  Bank::all();
        return view('employees.viewpayinfo', compact('employee_pay_info', 'banks'));
    }


    /**
     *  View Employee Employee Salary Info
     */
    public function EmpSalaryInfo($id)
    {
        //
        $employee = employee::findOrFail($id);

        $salaries = $employee->salary;
        // dd($salaries);

        return view('employees.salaryinfo' , compact('salaries'));
    }

    /**
     *  View Employee Employee Salary Info
     */
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

        $image = null;
        if($request->file('image'))
        {
           $image = ($request->file('image'))->store('images', 'public_html_disk');
        }

        $employee = new employee();
        $employee->name = $request->input('name');
        $employee->user_id = Auth::id();
        $employee->status = 'Active';
        $employee->gender = $request->input('gender');
        $employee->phone_number = $request->input('phone_number');
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
        $employee->basic_salary = $request->input('basic_salary');
        $employee->allowances = $request->input('allowances');
        $employee->payment_type = $request->input('payment_type');
        $employee->gurantor_name = $request->input('gurantor_name');
        $employee->gurantor_number = $request->input('gurantor_number');
        $employee->gurantor_address = $request->input('gurantor_address');
        $employee->gurantor_nia_number = $request->input('gurantor_nia_number');
        $employee->relationship = $request->input('relationship');
        $employee->image = $image;
        $employee->save();


        $employee_pay_info = new PaymentInfo();
        $employee_pay_info->employee_id = $employee->id;
        $employee_pay_info->bank_id = $payRequest->input('bank_id');
        $employee_pay_info->acc_number = $payRequest->input('acc_number');
        $employee_pay_info->branch = $payRequest->input('branch');
        $employee_pay_info->branch_code = $payRequest->input('branch_code');
        $employee_pay_info->tin_number = $payRequest->input('tin_number');
        $employee_pay_info->ssnit_number = $payRequest->input('ssnit_number');
        $employee_pay_info->user_id = Auth::id();
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
        $Departments = Department::all();
        $Roles = Role::all();
        $Fields = Field::all();
        $clients = Client::all();
        $banks = Bank::all();
        return view('employees.show', compact('employee', 'Departments', 'Roles', 'Fields', 'clients', 'banks')); 
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(employee $employee)
    {
        //
        // dd($employee);
        $Departments = Department::all();
        $Roles = Role::all();
        $Fields = Field::all();
        $clients = Client::all();
        $banks = Bank::all();
        return view('employees.edit', compact('employee', 'Departments', 'Roles', 'Fields', 'clients', 'banks'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateemployeeRequest $request, employee $employee)
    {
        //
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

        $employee->image = $path ?? $employee->image;
        $employee->name = $request->input('name');
        $employee->gender = $request->input('gender');
        $employee->phone_number = $request->input('phone_number');
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
        $employee->allowances = $request->input('allowances');
        $employee->gurantor_name = $request->input('gurantor_name');
        $employee->gurantor_number = $request->input('gurantor_number');
        $employee->gurantor_address = $request->input('gurantor_address');
        $employee->gurantor_nia_number = $request->input('gurantor_nia_number');
        $employee->relationship = $request->input('relationship');
        $employee->user_id = Auth::id();
        $employee->save();

        return redirect()->route('employees.show',['employee' => $employee])->with('success', 'Employee updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(employee $employee)
    {
        //
    }

    
    /**
     * Get All Employees with role of security guard for incoming client.
     */
    public function GuardClient($id)
    {


       $guards =  employee::where('client_id',$id)->where('role_id', 7)->get();

       if($guards->isEmpty())
       {
            return back()->with('error', 'Client has no current Guards');
       }

       $clients = $guards->isNotEmpty() ? Client::where('field_id', $guards[0]->field_id)->get() : null ;

        return view('employees.GuardView', compact('guards', 'clients'));
    }

    public function GuardReAassign(EmployeeClientRequest $request) 
    {
        // dd($request->all());

        $employees = $request->input('employees', []);
        $clients = $request->input('client_id', []);
        $locations = $request->input('location', []);

        // dd($employees, $clients, $locations);
        if (empty($employees)) {
            return back()->with('error', 'No Guard Selected to ReAssign.');
        }

        // dd($employees);
        foreach ($employees as $key => $employee)
        {
            // echo $employee .' ' . $clients[$key]. ' ' .$locations[$key] .'<br>';
            $employee = employee::findOrFail($employee);
            $employee->client_id = $clients[$key];
            $alreadyProcessed[] =  $clients[$key];
            $employee->location = $locations[$key];
            $employee->save();

            // $employee->update([
            //     'client_id' => $clients[$key],
            //     'location' => $locations[$key],
            // ]);
        }

        if(!empty( $alreadyProcessed))
        {
            return redirect()->route('client.index')->with('success', 'Guard successfully ReAssigned! Client: '. implode(', ', $alreadyProcessed));
        }
        // return back()->with('success', 'Guard successfully ReAssigned!');
    }


}

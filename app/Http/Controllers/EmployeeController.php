<?php

namespace App\Http\Controllers;

use App\Models\employee;
use App\Http\Requests\StoreemployeeRequest;
use App\Http\Requests\UpdateemployeeRequest;
use App\Models\Bank;
use App\Models\Client;
use App\Models\Department;
use App\Models\Field;
use App\Models\Role;

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
        return view('employees.index');
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

    public function EmpPayInfo()
    {
        //
        return view('employees.payinfo');
    }

    public function EmpSalaryInfo()
    {
        //
        return view('employees.salaryinfo');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreemployeeRequest $request)
    {
        //
        dd($request->all());
    }

    /**
     * Display the specified resource.
     */
    public function show(employee $employee)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(employee $employee)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateemployeeRequest $request, employee $employee)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(employee $employee)
    {
        //
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
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
        $departments = Department::all();
        $roles = Role::all();
        $departmentsCount = count($departments);
        $rolesCount = count($roles);

        return view('department.index', compact('departments', 'departmentsCount', 'roles', 'rolesCount'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        // Department::create([
        //     // 'name' => 'Accounts',
        //     // 'name' => 'IT',
        //     // 'name' => 'Guest',
        //     // 'name' => 'HR',
        //     // 'name' => 'System'
        //     // 'name' => 'Branch'
        //     // 'name' => 'Operations'

        // ]);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDepartmentRequest $request)
    {
        //
        $department = new Department();
        // $department->create($request->all());
        $department->name = $request->input('name');
        $department->save();
        return back()->with('success', 'Department Created Sucessfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Department $department)
    {
        //
        return view('department.edit', compact('department'));

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        //
        // dd($request->all(), $department);
        $department->name = $request->input('name');

        if($department->isDirty()) {
            $department->name = $request->input('name');
            $department->save();
            return redirect()->route('departments.index')->with('success', 'Department has been successfully updated!');
        }
        else{
            return back()->with('error', 'No changes detected. Please edit Department Name to Update.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department)
    {
        //
        // dd($department);
        $department->delete();
        return back()->with('error', 'Department has been successfully deleted!');
    }
}

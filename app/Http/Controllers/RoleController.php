<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;

class RoleController extends Controller
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
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        // Role::create([
        //     // 'name' => 'Invoice'
        //     // 'name' => 'Finance Manager'
        //     // 'name' => 'Manager',
        //     // 'name' => 'Director',
        //     // 'name' => 'Recovery',
        //     // 'name' => 'Officer',
        // ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        //
        $Role = new Role();
        $Role->name = $request->input('name');
        $Role->save();
        return back()->with('success', 'Role Created Sucessfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        //
        return view('department.roleedit', compact('role'));

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        //
        $role->name = $request->input('name');

        if($role->isDirty()) {
            $role->name = $request->input('name');
            $role->save();
            return redirect()->route('departments.index')->with('success', 'Role has been successfully updated!');
        }
        else{
            return back()->with('error', 'No changes detected. Please edit Role Name to Update.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        //
        $role->delete();
        return back()->with('error', 'Role has been successfully deleted!');
    }
}

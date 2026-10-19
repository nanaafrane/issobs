<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Field;
use App\Models\User;
use App\Models\Role;
use App\Support\Dashboards\DashboardContext;
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
     * Show the application dashboard: the Accounts, HR or Operations dashboard for the
     * user's department (see DashboardController), the System dashboard for System users.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->department?->name == 'Guest') {
            return view('auth.login');
        }

        if ($user->department?->name == 'System') {
            return view('system.dashboard');
        }

        $board = DashboardContext::homeBoardFor($user);
        if ($board === null) {
            // No dashboard for this department (as before): send them somewhere useful instead of a blank page.
            $boards = DashboardContext::boardsFor($user);
            abort_if(! $boards, 403, 'No dashboard is set up for your department.');
            $board = $boards[0];
        }

        return app(DashboardController::class)->show($request, $board);
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
        $user->path   = $request->file('image')->store('images', 'public_html_disk');
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

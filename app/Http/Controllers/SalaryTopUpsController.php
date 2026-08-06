<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalaryTopUpsRequest;
use App\Http\Requests\UpdateSalaryTopUpsRequest;
use App\Models\Salary;
use App\Models\SalaryTopUps;
use Illuminate\Support\Facades\Auth;

class SalaryTopUpsController extends Controller
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
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSalaryTopUpsRequest $request)
    {
        //
        $action = $request->input('topups_action_type') ?? $request->input('submit');
        $salaryIds = $request->input('salary', []);
        if (empty($salaryIds)) {
            return back()->with('error', 'No salaries selected');
        }
        // dd($salaryIds);
    // $alreadyProcessed = [];
    $notCompleted = [];
        if ($action === 'topup')
            {
                    $salaries = SalaryTopUps::findOrFail($salaryIds);
                    foreach ($salaries as $salarytopup) 
                    {
                        // $exists = $salarytopup->where('status', 'approved')
                        //                 ->exists();
                        // if ($exists) {
                        //     $alreadyProcessed[] = $salarytopup->salary?->employee?->name . " with Salary ID: " . $salarytopup->salary_id;
                        //     continue;
                        // }

                        $salarytopup->status = 'approved';
                        $salarytopup->user_id1 = Auth::id();
                        $salarytopup->status_date = now();
                        if(empty($request->payment_type[$salarytopup->id]) || empty($request->top_up_amount[$salarytopup->id]) || empty($request->reason[$salarytopup->id]) )
                        {
                            $notCompleted[] = $salarytopup->salary?->employee?->name . " with Salary ID: " . $salarytopup->salary_id;
                            continue;
                        }
                        $salarytopup->payment_type = $request->payment_type[$salarytopup->id];
                        $salarytopup->top_up_amount = $request->top_up_amount[$salarytopup->id];
                        $salarytopup->reason = $request->reason[$salarytopup->id];

                        $salarytopup->save();
                    }
                //  dd(count($alreadyProcessed));
                // if (!empty($alreadyProcessed))
                //     {
                //         return back()->with('error', 'Salaries have already been Approved : '. implode(', ', $alreadyProcessed))  ;
                //     }
                if (!empty($notCompleted))
                    {
                        return back()->with('error', 'The following Topups were not completed '. implode(', ', $notCompleted));
                    }
                return back()->with('success', 'Selected salaries approved for Top Ups : ' . implode(', ', $salaryIds));
            }
        if ($action === 'reverse_topup')
            {
                    $salaries = SalaryTopUps::findOrFail($salaryIds);
                    foreach ($salaries as $salarytopup) 
                    {
                        // $exists = $salarytopup->where('status', 'approved')
                        //                 ->exists();
                        // if ($exists) {
                        //     $alreadyProcessed[] = $salarytopup->salary?->employee?->name . " with Salary ID: " . $salarytopup->salary_id;
                        //     continue;
                        // }

                        $salarytopup->status = 'reversed';
                        $salarytopup->user_id2 = Auth::id();
                        $salarytopup->status_date2 = now();
                        if(empty($request->payment_type[$salarytopup->id]) || empty($request->top_up_amount[$salarytopup->id]) || empty($request->reason[$salarytopup->id]) )
                        {
                            $notCompleted[] = $salarytopup->salary?->employee?->name . " with Salary ID: " . $salarytopup->salary_id;
                            continue;
                        }
                        $salarytopup->payment_type = null;
                        $salarytopup->top_up_amount = null;
                        $salarytopup->reason = null;

                        $salarytopup->save();
                    }
                //  dd(count($alreadyProcessed));
                // if (!empty($alreadyProcessed))
                //     {
                //         return back()->with('error', 'Salaries have already been Approved : '. implode(', ', $alreadyProcessed))  ;
                //     }
                if (!empty($notCompleted))
                    {
                        return back()->with('error', 'The following Topups were not completed '. implode(', ', $notCompleted));
                    }
                return back()->with('success', 'Selected salaries reversed for Top Ups : ' . implode(', ', $salaryIds));
            }
        if ($action === 'delete_topup')
            {
                    $alreadyProcessed = [];
                    $salaries = SalaryTopUps::findOrFail($salaryIds);
                    foreach ($salaries as $salarytopup) 
                    {
                        $exists = SalaryTopUps::where('id', $salarytopup->id)->where('status', 'approved')
                                        ->exists();
                        if ($exists) {
                            $alreadyProcessed[] = $salarytopup->salary?->employee?->name . " with Salary ID: " . $salarytopup->salary_id;
                            continue;
                        }
                        
                        $salarytopup->delete();
                    }
                //  dd(count($alreadyProcessed));
                if (!empty($alreadyProcessed))
                    {
                        return back()->with('error', 'Salaries have already been Approved : '. implode(', ', $alreadyProcessed))  ;
                    }
                return back()->with('error', 'Selected salaries deleted from Top Ups : ' . implode(', ', $salaryIds));
            }

    }

    /**
     * Display the specified resource.
     */
    public function show(SalaryTopUps $salaryTopUps)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SalaryTopUps $salaryTopUps)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSalaryTopUpsRequest $request, SalaryTopUps $salaryTopUps)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalaryTopUps $salaryTopUps)
    {
        //
    }
}

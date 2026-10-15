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
        $isFinanceManager = Auth::user()?->hasRole(['Finance Manager']);

        if ($action === 'topup')
            {
                if (! $isFinanceManager) {
                    return back()->with('error', 'Only a Finance Manager can approve top-ups.');
                }
                $skipped = [];
                $runs = [];
                foreach (SalaryTopUps::findOrFail($salaryIds) as $salarytopup)
                {
                    $who = $salarytopup->salary?->employee?->name . " with Salary ID: " . $salarytopup->salary_id;
                    // Only a saved top-up with an amount can be approved (paid), and only once.
                    if ($salarytopup->status === 'approved' || (float) $salarytopup->top_up_amount <= 0) {
                        $skipped[] = $who;
                        continue;
                    }
                    $salarytopup->status = 'approved';
                    $salarytopup->user_id1 = Auth::id();
                    $salarytopup->status_date = now();
                    $salarytopup->save();
                    if ($run = \App\Support\PaymentRuns::recordTopUp($salarytopup)) {
                        $runs[$run->id] = \App\Support\PaymentRuns::label($run);
                    }
                }
                $into = $runs ? ' Recorded in: ' . implode('; ', $runs) . '.' : '';
                if (!empty($skipped))
                    {
                        return back()->with('error', 'Already approved or no amount saved, so skipped: ' . implode(', ', $skipped) . '. The remaining have been approved.' . $into);
                    }
                return back()->with('success', 'Selected salaries approved for Top Ups : ' . implode(', ', $salaryIds) . '.' . $into);
            }
        if ($action === 'save')
            {
                    $salaries = SalaryTopUps::findOrFail($salaryIds);
                    foreach ($salaries as $salarytopup) 
                    {
                        if ($salarytopup->status === 'approved') {
                            $notCompleted[] = $salarytopup->salary?->employee?->name . " with Salary ID: " . $salarytopup->salary_id . " (already approved)";
                            continue;
                        }
                        $salarytopup->status = 'saved';
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

                if (!empty($notCompleted))
                    {
                        return back()->with('error', 'The following Topups were not completed '. implode(', ', $notCompleted));
                    }
                return back()->with('primary', 'Selected salaries Saved for Top Ups : ' . implode(', ', $salaryIds));
            }
        if ($action === 'reverse_topup')
            {
                if (! $isFinanceManager) {
                    return back()->with('error', 'Only a Finance Manager can reverse top-ups.');
                }
                $blocked = [];
                foreach (SalaryTopUps::findOrFail($salaryIds) as $salarytopup)
                {
                    // Paid in a closed payment: that is history, it cannot be reversed away.
                    if ($run = \App\Support\PaymentRuns::removeTopUpPayment($salarytopup->id)) {
                        $blocked[] = $salarytopup->salary?->employee?->name . ' (Salary ID ' . $salarytopup->salary_id . ', ' . \App\Support\PaymentRuns::label($run) . ')';
                        continue;
                    }
                    $salarytopup->status = 'reversed';
                    $salarytopup->user_id2 = Auth::id();
                    $salarytopup->status_date2 = now();
                    $salarytopup->payment_type = null;
                    $salarytopup->top_up_amount = null;
                    $salarytopup->reason = null;
                    $salarytopup->save();
                }
                if (!empty($blocked))
                    {
                        return back()->with('error', 'Paid in a closed payment, so not reversed: ' . implode(', ', $blocked) . '. The remaining have been reversed.');
                    }
                return back()->with('dark', 'Selected salaries reversed for Top Ups : ' . implode(', ', $salaryIds));
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

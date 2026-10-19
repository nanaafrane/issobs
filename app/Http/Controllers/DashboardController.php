<?php

namespace App\Http\Controllers;

use App\Support\Dashboards\AccountsDashboard;
use App\Support\Dashboards\DashboardContext;
use App\Support\Dashboards\HrDashboard;
use App\Support\Dashboards\OperationsDashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The Accounts, HR and Operations dashboards. /home opens the one for the user's
 * department; /dashboard/{board} opens any board the user may see (directors and
 * auditors can switch between all three).
 */
class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(Request $request, string $board)
    {
        $user = Auth::user();
        abort_unless(in_array($board, DashboardContext::boardsFor($user), true), 403);

        $ctx = DashboardContext::fromRequest($request, $user, $board);

        $data = match ($board) {
            DashboardContext::ACCOUNTS => AccountsDashboard::build($ctx),
            DashboardContext::HR => HrDashboard::build($ctx),
            DashboardContext::OPERATIONS => OperationsDashboard::build($ctx),
        };

        return view('dashboards.' . $board, [
            'ctx' => $ctx,
            'd' => $data,
            'boards' => DashboardContext::boardsFor($user),
            // Keep the user's own side menu whichever dashboard they are looking at.
            'nav' => DashboardContext::homeBoardFor($user) ?? $board,
        ]);
    }
}

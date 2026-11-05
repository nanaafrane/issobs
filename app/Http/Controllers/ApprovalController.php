<?php

namespace App\Http\Controllers;

use App\Support\Approvals\ApprovalInbox;
use App\Support\Dashboards\DashboardContext;
use Illuminate\Support\Facades\Auth;

/**
 * /approvals: everything waiting for the signed-in user, oldest first, grouped by type
 * and stage. Expenses, overtime, fuel and maintenance can be approved / rejected here
 * (the existing routes, which redirect back); receipts, clients and employees link to
 * their pending pages, where they are approved in bulk.
 */
class ApprovalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $inbox = ApprovalInbox::for($user);
        $summary = $inbox->summary(fresh: true);

        $sections = collect($summary['items'])->map(fn ($s) => $s + [
            'rows' => $s['count'] ? $inbox->rows($s['key']) : collect(),
        ]);
        $watch = collect($summary['watch'])->map(fn ($s) => $s + ['rows' => $inbox->rows($s['key'])]);

        return view('approvals.index', [
            'summary' => $summary,
            'sections' => $sections,
            'watch' => $watch,
            'nav' => DashboardContext::homeBoardFor($user) ?? DashboardContext::ACCOUNTS,
        ]);
    }
}

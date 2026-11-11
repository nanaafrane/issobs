<?php

namespace App\Http\Controllers;

use App\Support\SubmissionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Drafts kept on the device (public/offline/drafts.js) ask here whether the form they were made
 * from has since been saved: the one-time save code (SubmissionGuard) of a saved form is in
 * form_submissions. Saved drafts are then removed from the device, wherever the save ended.
 */
class FormDraftController extends Controller
{
    public function saved(Request $request)
    {
        $keys = array_slice(array_filter((array) $request->query('keys', []), fn ($k) => is_string($k) && strlen($k) <= 64), 0, 50);
        if (! $keys) {
            return response()->json(['saved' => []]);
        }

        $saved = DB::table('form_submissions')->whereIn('submission_key', $keys)->whereNotNull('result_url')
            ->where(fn ($q) => $q->where('user_id', Auth::id())->orWhereNull('user_id'))
            ->pluck('submission_key');

        return response()->json(['saved' => $saved->values()]);
    }
}

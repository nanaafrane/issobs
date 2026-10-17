<?php

namespace App\Http\Middleware;

use App\Support\SubmissionGuard;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * If this exact form (same one-time code) was already saved, go straight to
 * what it saved — before the record is looked up or the form re-validated,
 * so a second "void" or a re-sent invoice form gets "already saved" instead
 * of "not found" or a validation error. SubmissionGuard does the actual
 * once-only save inside the transaction.
 */
class SaveFormOnce
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = trim((string) $request->input(SubmissionGuard::FIELD));

        if ($key !== '' && ! $request->isMethodSafe() && Schema::hasTable('form_submissions')) {
            $row = DB::table('form_submissions')->where('submission_key', $key)->whereNotNull('result_url')->first();
            if ($row) {
                return redirect($row->result_url)
                    ->with('info', 'This was already saved — the connection probably dropped after saving. Nothing was recorded twice.');
            }
        }

        return $next($request);
    }
}

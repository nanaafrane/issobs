<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Makes sure a money form is saved only once.
 *
 * The form carries a hidden one-time code (submission_key, made when the page
 * is shown). The code is recorded in form_submissions INSIDE the same database
 * transaction as the receipt/invoice itself, so either both are saved or
 * neither is. If the same form arrives again — e.g. the internet dropped after
 * the server saved it and the cashier pressed the button again — the second
 * arrival is sent to what the first one saved, with a note, and nothing new is
 * written. Two copies arriving at the same instant are handled by the unique
 * index: the second waits for the first, then finds its code taken.
 *
 * Forms without a code (an old page left open from before this change) are
 * saved normally.
 */
class SubmissionGuard
{
    public const FIELD = 'submission_key';

    /** A fresh code for a form being shown. */
    public static function newKey(): string
    {
        return (string) Str::uuid();
    }

    /** The hidden input for a Blade form. */
    public static function field(): string
    {
        return '<input type="hidden" name="' . self::FIELD . '" value="' . e(self::newKey()) . '">';
    }

    /**
     * Run $work (which returns a redirect) once per submission key.
     *
     * @param  callable(): RedirectResponse  $work
     */
    public function once(Request $request, string $form, callable $work): RedirectResponse
    {
        $key = $this->key($request);
        if ($key === null) {
            return DB::transaction($work);
        }

        if ($done = $this->previous($key)) {
            return $done;
        }

        try {
            return DB::transaction(function () use ($key, $form, $work) {
                DB::table('form_submissions')->insert([
                    'submission_key' => $key,
                    'form' => $form,
                    'user_id' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $response = $work();

                DB::table('form_submissions')->where('submission_key', $key)
                    ->update(['result_url' => mb_substr($response->getTargetUrl(), 0, 500), 'updated_at' => now()]);

                return $response;
            });
        } catch (QueryException $e) {
            // The same form was saved by a request that finished a moment earlier.
            if ($this->isDuplicateKey($e) && ($done = $this->previous($key))) {
                return $done;
            }
            throw $e;
        }
    }

    private function previous(string $key): ?RedirectResponse
    {
        $row = DB::table('form_submissions')->where('submission_key', $key)->first();
        if (! $row) {
            return null;
        }

        return redirect($row->result_url ?: url()->previous())
            ->with('info', 'This was already saved — the connection probably dropped after saving. Nothing was recorded twice.');
    }

    private function key(Request $request): ?string
    {
        $key = trim((string) $request->input(self::FIELD));

        return preg_match('/^[A-Za-z0-9\-]{16,64}$/', $key) ? $key : null;
    }

    private function isDuplicateKey(QueryException $e): bool
    {
        $code = (string) ($e->errorInfo[1] ?? '');
        $state = (string) ($e->errorInfo[0] ?? $e->getCode());

        return $code === '1062'                      // MySQL / MariaDB duplicate entry
            || $state === '23000' || $state === '23505'
            || str_contains(strtolower($e->getMessage()), 'unique');
    }
}

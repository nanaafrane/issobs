<?php

namespace App\Http\Middleware;

use App\Support\Subscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * When the subscription is more than the grace period overdue, the system is
 * read-only: GET/HEAD/OPTIONS work, every POST/PUT/PATCH/DELETE is refused.
 *
 * This is the real lock. Greyed-out buttons in the pages are only a courtesy:
 * a request sent any other way (Postman, browser console, an old tab) is still
 * stopped here.
 */
class ReadOnlyWhenUnpaid
{
    public function __construct(private Subscription $subscription)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->blocks($request)) {
            return $next($request);
        }

        $message = 'Subscription overdue: the system is read-only until payment is made. '
            .'You can still view and print, but not save or change anything.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'code' => 'SUBSCRIPTION_LOCKED',
                'message' => $message,
                'due_date' => $this->subscription->dueDate()->toDateString(),
                'pay_url' => url('billing'),
            ], 402);
        }

        return redirect()->back(fallback: url('billing'))->with('error', $message);
    }

    private function blocks(Request $request): bool
    {
        if (! $this->subscription->locked()) {
            return false;
        }

        if ($request->is(...(array) config('subscription.always_allowed', []))) {
            return false;
        }

        $writes = ! $request->isMethodSafe();
        $mutatingGet = $request->isMethodSafe() && $request->is(...(array) config('subscription.blocked_get', []));
        if (! $writes && ! $mutatingGet) {
            return false;
        }

        return ! $this->subscription->bypasses($request->user());
    }
}

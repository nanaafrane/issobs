<?php

namespace App\Http\Controllers;

use App\Services\Billing\HubtelCheckout;
use App\Support\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Subscription page: when the next payment is due, what has been paid, and
 * (once Hubtel is switched on) a button to pay. Works while the system is locked.
 */
class BillingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except('hubtelCallback');
    }

    public function index(Request $request, Subscription $subscription, HubtelCheckout $hubtel)
    {
        abort_unless($subscription->canManage($request->user()), 403, 'Only the Finance Manager or Director can view the subscription.');

        // Back from Hubtel: check that payment straight away rather than waiting for the callback.
        if ($request->filled('paid')) {
            $payment = $hubtel->confirm((string) $request->query('paid'));
            if ($payment?->status === 'paid') {
                session()->now('success', 'Payment received. Thank you, the system is fully open again.');
            } elseif ($payment) {
                session()->now('info', 'Payment is still being confirmed by Hubtel. Refresh this page in a minute.');
            }
        }
        if ($request->boolean('cancelled')) {
            session()->now('warning', 'Payment was cancelled. Nothing was charged.');
        }

        return view('billing.index', [
            'subscription' => $subscription,
            'onlinePayment' => $hubtel->enabled(),
        ]);
    }

    public function pay(Request $request, HubtelCheckout $hubtel, Subscription $subscription)
    {
        abort_unless($subscription->canManage($request->user()), 403, 'Only the Finance Manager or Director can pay the subscription.');

        $data = $request->validate(['months' => ['required', 'integer', \Illuminate\Validation\Rule::in(HubtelCheckout::monthChoices())]]);

        try {
            return redirect()->away($hubtel->start((int) $data['months'], $request->user()?->id));
        } catch (RuntimeException $e) {
            return redirect('billing')->with('error', $e->getMessage());
        }
    }

    /**
     * Hubtel tells us a checkout finished. We only take the reference from it and
     * ask Hubtel ourselves whether it is paid (HubtelCheckout::confirm).
     */
    public function hubtelCallback(Request $request, HubtelCheckout $hubtel)
    {
        $reference = (string) ($request->input('Data.ClientReference')
            ?? $request->input('data.clientReference')
            ?? $request->input('ClientReference', ''));

        Log::info('Hubtel subscription callback', ['reference' => $reference]);

        if ($reference !== '') {
            $hubtel->confirm($reference);
        }

        return response()->json(['received' => true]);
    }
}

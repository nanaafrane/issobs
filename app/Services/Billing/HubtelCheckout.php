<?php

namespace App\Services\Billing;

use App\Models\SubscriptionPayment;
use App\Support\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Pay the subscription through Hubtel Online Checkout.
 *
 *   1. start()   - create a pending payment, ask Hubtel for a checkout page, send the user there.
 *   2. Hubtel calls billing/hubtel/callback when the customer has paid (or failed).
 *   3. confirm() - we never believe the callback alone: we ask Hubtel's status API
 *                  whether that reference is really paid, for the right amount, then
 *                  mark it paid once (a repeated callback changes nothing).
 *
 * Off until config('subscription.hubtel.enabled') and the credentials are set in .env.
 */
class HubtelCheckout
{
    /** Months that can be paid for at once (config subscription.pay_months). */
    public static function monthChoices(): array
    {
        $months = array_values(array_unique(array_filter(array_map('intval', (array) config('subscription.pay_months', [1])), fn ($m) => $m >= 1 && $m <= 36)));
        sort($months);

        return $months ?: [1];
    }

    public function enabled(): bool
    {
        $c = config('subscription.hubtel');

        return (bool) ($c['enabled'] ?? false) && filled($c['api_id'] ?? null) && filled($c['api_key'] ?? null)
            && filled($c['merchant_account'] ?? null) && (float) config('subscription.monthly_fee') > 0;
    }

    /** Returns the Hubtel page to send the user to. */
    public function start(int $months, ?int $userId): string
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Online payment is not switched on yet.');
        }

        if (! in_array($months, self::monthChoices(), true)) {
            throw new RuntimeException('That number of months cannot be paid for.');
        }
        $amount = round((float) config('subscription.monthly_fee') * $months, 2);

        $payment = SubscriptionPayment::create([
            'provider' => 'hubtel',
            'client_reference' => 'SUB'.strtoupper(Str::random(20)),   // Hubtel allows at most 32 characters
            'months' => $months,
            'amount' => $amount,
            'currency' => config('subscription.currency', 'GHS'),
            'status' => SubscriptionPayment::PENDING,
            'user_id' => $userId,
        ]);

        $response = $this->http()->post(config('subscription.hubtel.initiate_url'), [
            'totalAmount' => $amount,
            'description' => config('app.name')." subscription - {$months} month(s)",
            'callbackUrl' => url('billing/hubtel/callback'),
            'returnUrl' => url('billing?paid='.$payment->client_reference),
            'cancellationUrl' => url('billing?cancelled=1'),
            'merchantAccountNumber' => config('subscription.hubtel.merchant_account'),
            'clientReference' => $payment->client_reference,
        ]);

        $json = $response->json() ?? [];
        $payment->update(['payload' => $json, 'provider_reference' => data_get($json, 'data.checkoutId')]);

        $url = data_get($json, 'data.checkoutUrl');
        if (! $response->successful() || ! $url) {
            $payment->update(['status' => SubscriptionPayment::FAILED]);
            Log::warning('Hubtel checkout could not start', ['reference' => $payment->client_reference, 'response' => $json]);
            throw new RuntimeException('Hubtel did not accept the payment request. Please try again later.');
        }

        return $url;
    }

    /**
     * Check one of our references with Hubtel and record the result.
     * Safe to call many times: a payment is marked paid only once.
     */
    public function confirm(string $clientReference): ?SubscriptionPayment
    {
        $payment = SubscriptionPayment::where('client_reference', $clientReference)->first();
        if (! $payment || $payment->status === SubscriptionPayment::PAID || ! $this->enabled()) {
            return $payment;
        }

        $url = str_replace('{merchant}', (string) config('subscription.hubtel.merchant_account'), config('subscription.hubtel.status_url'));
        $response = $this->http()->get($url, ['clientReference' => $clientReference]);
        $json = $response->json() ?? [];

        $status = strtolower((string) data_get($json, 'data.status', ''));
        $amount = (float) data_get($json, 'data.amount', 0);

        DB::transaction(function () use ($payment, $status, $amount, $json) {
            $payment = SubscriptionPayment::whereKey($payment->id)->lockForUpdate()->first();
            if ($payment->status === SubscriptionPayment::PAID) {
                return;
            }

            if ($status === 'paid' && $amount + 0.001 >= (float) $payment->amount) {
                $payment->update(['status' => SubscriptionPayment::PAID, 'paid_at' => now(), 'payload' => $json]);
            } elseif (in_array($status, ['unpaid', 'failed', 'refunded'], true)) {
                $payment->update(['status' => SubscriptionPayment::FAILED, 'payload' => $json]);
            } else {
                $payment->update(['payload' => $json]);   // still pending; check again later
            }
        });

        app(Subscription::class)->refresh();

        return $payment->fresh();
    }

    private function http()
    {
        return Http::acceptJson()->asJson()->timeout(20)
            ->withBasicAuth((string) config('subscription.hubtel.api_id'), (string) config('subscription.hubtel.api_key'));
    }
}

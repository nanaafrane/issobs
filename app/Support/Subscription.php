<?php

namespace App\Support;

use App\Models\SubscriptionPayment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Where the monthly subscription stands today. Worked out from the dates in
 * config/subscription.php plus paid Hubtel payments; nothing about the lock is
 * stored, so it can never get stuck "locked" after a payment or a code renewal.
 *
 * One instance per request (bound as scoped in AppServiceProvider).
 */
class Subscription
{
    public const ACTIVE = 'active';     // paid up
    public const DUE_SOON = 'due_soon'; // paid up, due date within warn_days_before
    public const OVERDUE = 'overdue';   // past due, still in the grace period: works, with a warning
    public const LOCKED = 'locked';     // past due + grace: read-only

    private ?array $state = null;

    public function enforced(): bool
    {
        return (bool) config('subscription.enforce', true);
    }

    public function today(): CarbonImmutable
    {
        return CarbonImmutable::now(config('subscription.timezone', 'Africa/Accra'))->startOfDay();
    }

    /** The day the next payment is due. */
    public function dueDate(): CarbonImmutable
    {
        return $this->state()['due'];
    }

    /** First day the system is read-only if still unpaid. */
    public function lockDate(): CarbonImmutable
    {
        return $this->dueDate()->addDays((int) config('subscription.grace_days', 30) + 1);
    }

    public function status(): string
    {
        if (! $this->enforced()) {
            return self::ACTIVE;
        }

        $today = $this->today();
        if ($today->greaterThanOrEqualTo($this->lockDate())) {
            return self::LOCKED;
        }
        if ($today->greaterThan($this->dueDate())) {
            return self::OVERDUE;
        }
        if ($today->diffInDays($this->dueDate(), false) <= (int) config('subscription.warn_days_before', 7)) {
            return self::DUE_SOON;
        }

        return self::ACTIVE;
    }

    public function locked(): bool
    {
        return $this->status() === self::LOCKED;
    }

    /** Whole days until the due date (negative once past it). */
    public function daysUntilDue(): int
    {
        return (int) $this->today()->diffInDays($this->dueDate(), false);
    }

    /** Whole days until read-only (0 or less once locked). */
    public function daysUntilLock(): int
    {
        return (int) $this->today()->diffInDays($this->lockDate(), false);
    }

    /** May this user see payment details and pay (Finance Manager, Director)? */
    public function canManage($user): bool
    {
        return $user && method_exists($user, 'hasRole')
            && $user->hasRole((array) config('subscription.billing_roles', ['Finance Manager', 'Director']));
    }

    /** Is this user never locked (developer/support login)? */
    public function bypasses($user): bool
    {
        if (! $user || ! $user->email) {
            return false;
        }
        $emails = array_map('strtolower', (array) config('subscription.bypass_emails', []));

        return in_array(strtolower($user->email), $emails, true);
    }

    /**
     * Every payment counted, oldest first: manual renewals from config and paid
     * Hubtel payments. Each: [paid_on, months, reference, source, amount].
     */
    public function history(): Collection
    {
        return $this->state()['history'];
    }

    /** Forget the cached state (after recording a payment in the same request). */
    public function refresh(): void
    {
        $this->state = null;
    }

    private function state(): array
    {
        if ($this->state !== null) {
            return $this->state;
        }

        $tz = config('subscription.timezone', 'Africa/Accra');

        $manual = collect((array) config('subscription.renewals', []))->map(fn ($r) => [
            'paid_on' => isset($r['paid_on']) ? CarbonImmutable::parse($r['paid_on'], $tz) : null,
            'months' => max(0, (int) ($r['months'] ?? 1)),
            'reference' => (string) ($r['reference'] ?? ''),
            'source' => 'manual',
            'amount' => $r['amount'] ?? null,
        ]);

        $online = collect();
        // Before the migration has run, count manual renewals only instead of failing every page.
        if (Schema::hasTable('subscription_payments')) {
            $online = SubscriptionPayment::where('status', SubscriptionPayment::PAID)->orderBy('paid_at')->get()
                ->map(fn (SubscriptionPayment $p) => [
                    'paid_on' => $p->paid_at ? CarbonImmutable::parse($p->paid_at)->setTimezone($tz) : null,
                    'months' => (int) $p->months,
                    'reference' => $p->client_reference,
                    'source' => $p->provider,
                    'amount' => $p->amount,
                ]);
        }

        $history = $manual->concat($online)->sortBy(fn ($r) => $r['paid_on']?->timestamp ?? 0)->values();

        $due = CarbonImmutable::parse(config('subscription.starts_on'), $tz)->startOfDay()
            ->addMonthsNoOverflow((int) $history->sum('months'));

        return $this->state = ['due' => $due, 'history' => $history];
    }
}

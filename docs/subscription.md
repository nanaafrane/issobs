# Monthly subscription and read-only lock

## The rule

| Stage | When | What users see |
|---|---|---|
| Paid up | before the due date | nothing |
| Due soon | 7 days before, up to the due date | blue reminder on every page |
| Overdue | 1 to 30 days after the due date | yellow banner: "read-only on <date>, N days left" |
| **Read-only** | from the 31st day after the due date | red banner; Save / Create / Register / Edit / Delete greyed out; server refuses every POST, PUT, PATCH, DELETE |

**Who sees what.** Only the Finance Manager and Director (`billing_roles`) see the reminder, overdue and read-only messages with dates, the Subscription page and the Pay button. Everyone else sees nothing until the system is locked, then a red **SYSTEM INACTIVE – READ ONLY** banner; their buttons are greyed out the same way, and the billing page and pay request return 403 for them.

Read-only ends the moment a renewal or payment moves the due date forward. Nothing about the lock is stored, so it can't get stuck.

Dates are judged in Ghana time (Africa/Accra) on the server.

## Renewing by hand (now)

Edit `config/subscription.php`, add a line to `renewals`, deploy:

```php
'renewals' => [
    ['months' => 1, 'paid_on' => '2026-11-01', 'reference' => 'Cash - November 2026'],
    ['months' => 3, 'paid_on' => '2026-12-03', 'reference' => 'MoMo 0244xxxxxx - Dec to Feb'],
],
```

Due date = `starts_on` + all months paid. Never delete old lines: they are the payment history shown on the Subscription page.

If config is cached in production, run `php artisan config:clear` (or `config:cache`) after deploying.

Check the state any time: `php artisan subscription:status`.

## Settings (config/subscription.php)

- `enforce` - false switches the whole thing off.
- `starts_on` - first due date.
- `grace_days` (30), `warn_days_before` (7).
- `pay_months` - choices in the Months list on the Pay screen, e.g. `[1, 3, 6]`.
- `monthly_fee`, `currency`.
- `billing_roles` - roles that see payment details and can pay (Finance Manager, Director).
- `bypass_emails` - logins never locked (your own developer/support account).
- `always_allowed` - writes that keep working when locked: login, logout, password reset, billing, Hubtel callbacks for salary payouts already sent.
- `blocked_get` - old links that change data with a GET (terminate/reinstate client, sendMoney); blocked while locked.

These live in code, not `.env` and not on a screen, so the client can't change their own due date.

## How the lock works

1. `App\Http\Middleware\ReadOnlyWhenUnpaid` (web and api groups) is the real lock. Locked + write request → normal pages get redirected back with an error message; AJAX/JSON gets HTTP 402 with `{code: "SUBSCRIPTION_LOCKED", due_date, pay_url}`.
2. `partials/subscription_readonly` (in both layouts) only makes it visible: greys out submit buttons of non-GET forms, "create"/"edit" links, buttons that open a modal containing a save form, and stops jQuery/fetch writes. Mark anything that must stay clickable with `data-readonly-allow`.
3. `partials/subscription_banner` shows the reminder / overdue / read-only banner.

Note: the public attendance scan (`attend/{token}`) and kiosk are writes too, so guards can't clock in while read-only. Add `'attend/*'` to `always_allowed` if you'd rather keep attendance running.

## Online payment with Hubtel (later)

Already built, switched off. To turn on, put the Hubtel **Online Checkout** credentials in `.env` (never in code):

```
HUBTEL_CHECKOUT_ENABLED=true
HUBTEL_CHECKOUT_API_ID=...
HUBTEL_CHECKOUT_API_KEY=...
HUBTEL_CHECKOUT_MERCHANT_ACCOUNT=...
# HUBTEL_CHECKOUT_STATUS_URL=...   confirm with Hubtel; needs your server IP whitelisted
```

and set `monthly_fee` above 0. Then run the migration (`subscription_payments` table) and make sure the scheduler runs (`php artisan schedule:run` every minute via cron).

Flow: Subscription page → Pay with Hubtel → Hubtel page → Hubtel calls `POST /billing/hubtel/callback` → we ask Hubtel's status API whether that reference is really paid and for the right amount → mark paid (once; repeated callbacks do nothing) → due date moves forward. `subscription:check-pending` runs every 10 minutes for payments whose callback never arrived. The callback alone is never trusted.

Manual renewals in config and Hubtel payments both count; you can mix them.

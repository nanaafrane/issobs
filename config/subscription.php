<?php

/*
|--------------------------------------------------------------------------
| Monthly subscription (system usage fee)
|--------------------------------------------------------------------------
|
| How it works (see docs/subscription.md):
|
|   due date  = starts_on + every month paid (renewals below + Hubtel payments)
|   overdue   = past the due date; everything still works, a warning shows
|   locked    = more than `grace_days` past the due date; the system becomes
|               READ-ONLY: pages and reports open, nothing can be saved,
|               created, edited or deleted until the next payment.
|
| These settings live in code on purpose (not in .env and not on a screen),
| so only the developer can change dates or record a renewal.
|
| TO RENEW BY HAND: add one line to `renewals` and deploy. Each line pushes
| the due date forward by its number of months.
*/

return [

    // Turn the whole feature off (false) without removing anything.
    'enforce' => true,

    // The first payment is due on this date. With no renewals listed, this is the due date.
    'starts_on' => '2026-11-01',

    // Days the system keeps working after the due date before it becomes read-only.
    'grace_days' => 30,

    // Start warning this many days before the due date.
    'warn_days_before' => 7,

    'monthly_fee' => 0.00,          // GHS; shown on the billing page and charged through Hubtel
    'currency' => 'GHS',
    'timezone' => 'Africa/Accra',   // dates are judged in Ghana time, never the user's clock

    // Manual renewals (cash, bank transfer, MoMo outside the system).
    // Keep the history: never delete a line, only add new ones.
    'renewals' => [
        // ['months' => 1, 'paid_on' => '2026-11-01', 'reference' => 'Cash - November 2026'],
    ],

    // Logins that are never locked (e.g. the developer's own account, to fix data
    // or help with payment). Matched on the user's email address.
    'bypass_emails' => [
        // 'developer@example.com',
    ],

    // Write requests (POST/PUT/PATCH/DELETE) that must keep working while locked,
    // otherwise nobody could log in to pay, and Hubtel could not report payments.
    // Paths as matched by $request->is().
    'always_allowed' => [
        'login',
        'logout',
        'password/*',
        'billing',
        'billing/*',
        'sendMoneyCallback',       // Hubtel salary payout callbacks for money already sent
        'api/webhook-endpoint',
    ],

    // Old routes that change data through a plain link (GET). Blocked while locked,
    // like any POST. Prefer moving these to POST over time.
    'blocked_get' => [
        'terminateClient/*',
        'reinstateClient/*',
        'sendMoney',
    ],

    /*
    | Hubtel Online Checkout. Off until you have checkout credentials.
    | Keys belong in .env (never in code): HUBTEL_CHECKOUT_* below.
    */
    'hubtel' => [
        'enabled' => env('HUBTEL_CHECKOUT_ENABLED', false),
        'api_id' => env('HUBTEL_CHECKOUT_API_ID'),
        'api_key' => env('HUBTEL_CHECKOUT_API_KEY'),
        'merchant_account' => env('HUBTEL_CHECKOUT_MERCHANT_ACCOUNT'),
        'initiate_url' => env('HUBTEL_CHECKOUT_INITIATE_URL', 'https://payproxyapi.hubtel.com/items/initiate'),
        // Confirm this URL with Hubtel when you get credentials; it needs your server IP whitelisted.
        // {merchant} is replaced with merchant_account.
        'status_url' => env('HUBTEL_CHECKOUT_STATUS_URL', 'https://api-txnstatus.hubtel.com/transactions/{merchant}/status'),
    ],
];

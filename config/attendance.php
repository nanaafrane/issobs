<?php

/*
| Guard attendance: offline sign-in (see docs/offline-sync.md).
|
| The QR sign-in page and the supervisor kiosk keep working with no signal: a sign-in is saved
| on the phone and uploaded when the phone is back online. These settings decide how much the
| server trusts a sign-in that was made offline.
*/
return [

    'offline' => [
        // Turn the offline outbox off entirely (pages then behave as before: no signal, no sign-in).
        'enabled' => (bool) env('ATTENDANCE_OFFLINE_ENABLED', true),

        // 'flagged': an offline sign-in counts like an online one unless something looks wrong
        //            (phone clock off, uploaded very late, plus all the usual GPS / phone / shift checks).
        // 'all':     every sign-in made offline waits for review on Attendance > Review sign-ins.
        'review' => env('ATTENDANCE_OFFLINE_REVIEW', 'flagged'),

        // Phone clock more than this far from the server's when it uploads: time is corrected and held for review.
        'max_clock_skew_minutes' => (int) env('ATTENDANCE_OFFLINE_MAX_SKEW', 5),

        // Uploaded more than this long after the sign-in: held for review.
        'review_after_hours' => (int) env('ATTENDANCE_OFFLINE_REVIEW_AFTER_HOURS', 12),

        // Older than this: refused (enter it on the monthly sheet instead).
        'max_age_days' => (int) env('ATTENDANCE_OFFLINE_MAX_AGE_DAYS', 7),

        // Kiosk upload tokens (one per supervisor phone) last this long; renewed whenever the kiosk opens online.
        'kiosk_token_days' => (int) env('ATTENDANCE_KIOSK_TOKEN_DAYS', 14),

        // RSA key that seals guards' PINs while they wait on the phone. Made automatically on first use.
        'key_path' => env('ATTENDANCE_OFFLINE_KEY_PATH', storage_path('app/private/attendance-offline.pem')),
    ],

];

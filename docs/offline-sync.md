# Working with a bad connection: offline attendance, local assets, background jobs

ISSOBS is one Laravel app with one MySQL database: every page and every save needs the server. This change
does not try to make the whole system work offline. Accounting (receipts, deposits, payroll, approvals) depends on
balances everyone shares, and two offices saving payments offline against the same invoice would collide. Instead:

| Part | What changed |
|---|---|
| **Every page** | Scripts and fonts come from our own server (`public/libs`), not from CDNs. A slow or blocked CDN no longer breaks DataTables, Excel/PDF export or the QR pages. |
| **Statement emails, Hubtel MoMo checks** | Run on the queue. The page returns at once; if Mailgun or Hubtel is unreachable the job is retried (3 tries) instead of the action being lost. |
| **Guard sign-in (QR page) and supervisor kiosk** | Work with no signal. Sign-ins are saved on the phone and uploaded automatically when the phone is back online. |
| **Every page, when the connection drops** | A "You're offline" panel (no more red banner) and, for a page that can't be reached, an offline page instead of the browser's error. Save is paused while offline instead of ending on an error. |
| **Invoice, proforma, receipt, fuel and maintenance forms** | Kept as a draft on the device as you type. Save pressed offline keeps the entry waiting; the form opens offline and puts it back; a person sends it once online. |

---

## 1. Offline attendance: how it works

```
 phone (sign-in page / kiosk)                         server
 ─────────────────────────────                        ──────────────────────────────────────────
 press Sign in / P / A
   → save in the outbox (IndexedDB)  ── online? ──→  POST attend/{token}/sync   (guard page)
     with its own id (uuid) and the                   POST api/attendance/kiosk-sync (kiosk, Bearer token)
     phone's time                                      → OfflineSync: once per uuid, clock corrected,
   ← answer shown; item removed                          then the normal ScanRecorder / KioskRecorder checks
 no signal: stays in the outbox,
 sent on reconnect (page open, "online"
 event, or Background Sync on Android)
```

* **Same path online and offline.** Every sign-in goes into the outbox first, then is sent at once. With signal the guard
  sees the answer straight away, as before. Without, they see *Saved on this phone* and the page lists what is waiting.
  When it is sent, the box changes to the real answer (signed in / held for review / not accepted).
* **The page opens without signal.** A service worker (`public/sw.js`) keeps a copy of the sign-in page and the kiosk
  from the last time they opened online. It handles only those two pages and their scripts; every other page is untouched.
* **Never saved twice.** Each item carries a uuid; `attendance_sync_items` records it in the same transaction as the
  attendance row. An item uploaded twice (the connection dropped after the server saved it) gets the first answer again.
* **All the usual checks still run** at upload: PIN, roster, shift, GPS distance, phone. A sign-in that fails one is held
  for review exactly like an online one.

### Time

The phone sends its own clock with every upload. The difference from the server's clock is applied to the time the
sign-in was made, so a phone that is 3 hours slow still records the right time. `checked_in_at` is the time the sign-in
was **made**; `synced_at` is when it arrived; `captured_offline` marks it. The note on the row says
*Made offline at Wed 11 Nov 06:05, uploaded Wed 11 Nov 08:00*.

An offline sign-in is **held for review** when:

| Reason | Setting |
|---|---|
| Phone clock more than 5 minutes off (time is corrected, but someone may have changed it) | `ATTENDANCE_OFFLINE_MAX_SKEW` |
| Uploaded more than 12 hours after it was made | `ATTENDANCE_OFFLINE_REVIEW_AFTER_HOURS` |
| Every offline sign-in, if you prefer | `ATTENDANCE_OFFLINE_REVIEW=all` |

Refused (not saved; the guard is told why): a time in the future, or older than 7 days (`ATTENDANCE_OFFLINE_MAX_AGE_DAYS`;
enter those on the monthly sheet).

**What this cannot prove.** A guard who turns the phone's clock back *and* forward again before signal returns can make
a late sign-in look on time; the server only sees a phone whose clock is right at upload. GPS, the PIN and the phone check
still apply. If that risk matters more than the review workload, set `ATTENDANCE_OFFLINE_REVIEW=all`.

### The PIN while it waits on the phone

The sign-in page carries the server's public key. The browser seals `uuid|employee number|PIN` with it (RSA-OAEP) and
keeps only the sealed bytes, so the next person to pick up a shared phone can't read a PIN out of the outbox. Only the
server can open it, and a sealed PIN can't be copied onto another guard's sign-in. A wrong PIN is refused at upload and
shown on the page under *Sign-ins on this phone*. Too many wrong PINs (the 15-minute lock) is retried later, not refused.

The key is made automatically at `storage/app/private/attendance-offline.pem` on first use.
`php artisan attendance:offline-key --rotate` replaces it (sign-ins still waiting on phones then have to be made again).
Back the file up with the rest of `storage/`.

### Supervisor kiosk

* When the kiosk opens online, it gets an **upload token** for that phone (Sanctum, ability `attendance-kiosk`, 14 days,
  renewed each time; `ATTENDANCE_KIOSK_TOKEN_DAYS`). Marks saved overnight still upload after the login session has expired.
* One queue and one token **per supervisor**: if two supervisors share a phone, each one's marks go up under their own name.
* An offline mark **never overwrites a newer entry** (operations' hand entry, an approval, a later sign-in): it is
  *skipped* and the supervisor is told which entry was kept.
* A saved copy of the kiosk shows a banner with the time it was saved: statuses on it may be out of date, and a mark for
  a shift that has passed is refused on upload (same rule as online: current shift only).

### Answers per item

| Result | Meaning | On the phone |
|---|---|---|
| accepted | saved (present / absent) | removed, shown as *Sent* |
| held | saved, waits on *Review sign-ins* | removed, shown as *Sent · operations will check* |
| rejected | not saved (wrong PIN, too old, …) | removed, shown in red with the reason |
| skipped | a newer entry already exists | removed, shown with the entry kept |
| retry | try later (PIN locked) | stays in the outbox |

A poster replaced while the phone was offline answers 410: the waiting sign-ins are shown as *not accepted, scan the new
poster*. A read-only subscription (402) or an expired kiosk token (401) leaves items waiting.

### Needs

* **HTTPS** (production already has it; `localhost` works for testing). Without it, or on very old browsers, the pages
  fall back to the old online-only behaviour.
* Background Sync (upload with the page closed) works on Android Chrome. Elsewhere, items are sent the next time the page
  is opened with signal, or when signal returns while it is open.

---

## 2. When the connection drops (every page)

`resources/views/partials/connection.blade.php` (in the HR and Sales layouts and the login layout) loads
`public/offline/connection.js` and `public/offline/drafts.js`. It replaces the red "Offline - No internet connection
detected" banner.

* **Connection lost on an open page:** a "You're offline" panel says what is safe (everything saved before), what
  still works (attendance, drafts), what to check (Wi-Fi/data, other websites, router, tell the administrator if only
  ISSOBS is down), and lists the drafts on this device. *Keep reading this page* shrinks it to a small note. It asks the
  server (`/up`) every 10 seconds, so Wi-Fi with no internet counts as offline too, and closes itself with
  "Back online" when the server answers.
* **Save pressed while offline:** the form is not sent (the browser would show an error and drop what was typed).
  On a draft form the entry is kept on the device as *waiting to send*; on any other form what was typed stays on the page.
* **A page that can't be reached:** the service worker shows `public/offline/offline.html` instead of the browser's
  error: which page was being opened, the drafts and attendance sign-ins waiting on the device, what to check, and it
  opens the page by itself when the connection is back. Pages that load normally are not touched or kept.
* **Logging out** removes the form pages kept on the device.

## 3. Drafts on the device (invoice, proforma, receipt, fuel, maintenance)

The forms carry `data-draft` (invoice: `sales/invoice_generate`, proforma: `sales/proforma_create`, new receipt:
`sales/receipt_multi_create`, `fleet/fuel`, `fleet/maintenance`). To add another form, give its `<form>`
`data-draft="name"` (plus `data-draft-label`, and `data-draft-add="#add"` if it has a "+" button for repeating rows)
and add its address to `DRAFT_PAGES` in `public/sw.js`.

* What is typed is kept on the device (IndexedDB, per user) a moment after each change. Not kept: the security
  token, the one-time save code, passwords and attached files (attach the scan again).
* The form page, and the theme scripts and styles it needs, are kept on the device each time it opens online, so it
  opens with no signal. Coming back to the form shows *You have an unsent draft… Restore it / Discard*; an entry saved
  while offline is put back at once with *Saved while offline… check and press Save*. Repeating rows (invoice lines)
  are added with the form's own "+" button.
* **Drafts are never sent on their own.** A person checks and saves them online, so every server check runs as usual:
  balances, invoice numbers, duplicate receipts, approvals.
* A copy of the page kept on the device carries an old security token and one-time save code, so Save on it first
  keeps the draft and reloads a fresh page (the draft comes back on it). This also stops the save code of an earlier
  invoice being reused for a new one.
* A draft is removed once its form has been saved: the draft remembers the form's one-time save code and asks
  `GET forms/saved` whether the server saved it (forms without a save code: the page after Save shows a success message).
  Drafts older than 14 days are removed.

## 4. Voided receipts

`receipt-voided` lists voided receipts; each now opens its own page (`receipt-voided/{id}`) with what it recorded:
client, date, payer, how it was paid with the cheque / transfer / MoMo references and the account it went into, who
recorded and approved it, the scanned receipt, the invoices it paid (linked, with their balance now), the bank lines
including the reversal, and the client-ledger entries including the reversal. An old link to a voided receipt
(`receipt/{id}`) opens this page instead of "404".

## 5. Scripts and fonts served locally

`public/libs/` holds the exact versions the views used from CDNs: jQuery 3.7.1, DataTables 2.3.2/2.3.3/2.3.5 with Buttons
3.2.4/3.2.5, ColumnControl, FixedHeader, FixedColumns, JSZip, pdfMake, Select2, qrcodejs, html5-qrcode, and the Public Sans
font (`libs/fonts/public-sans/`). Every view that loaded them from a CDN (about 100) now uses `asset('libs/...')`. Nothing to build: the files are committed.

## 6. Background jobs

* **Statement emails** (`SendClientStatement`): the statement and PDF are built when the job runs; `statement_sends` is
  written only once the mail has gone. Failures after 3 tries are logged.
* **MoMo name check** (`VerifyMomoName` + `App\Services\Hubtel\MomoNameCheck`): one job per employee, 15-second timeout,
  retried if Hubtel can't be reached.
* The scheduler now drains the queue every minute (`queue:work --stop-when-empty`), using the cron that already runs
  `schedule:run`. If you run a real worker (supervisor/systemd), set `QUEUE_WORKER_VIA_SCHEDULER=false`.

### Hubtel credentials (action needed)

The MoMo-check API ID and secret were written in `EmployeeController` (and in comments in `SendMoneyController`) and are
in the repository's history. They now come from `.env`:

```
HUBTEL_MOMO_MERCHANT_ACCOUNT=...
HUBTEL_MOMO_CLIENT_ID=...
HUBTEL_MOMO_CLIENT_SECRET=...
```

**Ask Hubtel for new keys** and put the new ones in `.env`: the old ones should be treated as public.

---

## 7. Install

```bash
git pull
php artisan migrate          # attendances.captured_offline / synced_at, attendance_sync_items
php artisan config:clear && php artisan view:clear
# .env: HUBTEL_MOMO_* (new keys); optional ATTENDANCE_OFFLINE_* settings above
```

Make sure `public/sw.js`, `public/offline/` and `public/libs/` are deployed with the rest of `public/`.

## 8. Tests

`tests/Feature/OfflinePagesTest.php` (offline panel, draft forms, `forms/saved`), `tests/Feature/VoidedReceiptPageTest.php`,
`tests/Feature/AttendanceOfflineSyncTest.php` (18 tests: duplicates, clock correction, night shifts, sealed PINs, locks,
replaced posters, kiosk tokens, newer-entry rule, office permissions) and `tests/Feature/BackgroundJobsTest.php`.
The full offline flow was also run in Chrome: sign in with the network off, reopen the page offline, reconnect, and the
sign-ins arrive with their original times; kiosk marks upload after the login cookie is deleted. Drafts: a 3-line
invoice typed online, connection cut, Save pressed (kept as a draft, nothing sent), another page opened (offline page
listing the draft), the invoice form reopened offline (lines put back), connection restored, page reloaded and saved:
one invoice with 3 lines, and the draft removed.

## 9. Not in this change (next steps)

* Sending drafts from the device in the background (without opening the form). Left out on purpose for money
  documents; possible for fuel and maintenance entries with an upload API like attendance's.
* If head office's line is the main problem: a 4G backup router, or the app on an office server with a one-way copy to
  the cloud.

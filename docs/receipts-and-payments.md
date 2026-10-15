# Receipts & payments: analysis, changes and how to run it

---

## 1. How money flows now

| Paid by | Where it goes | When |
|---|---|---|
| **Cash** | Collection → *Bank Deposit* → the bank picked on the deposit screen | when someone deposits it |
| **Cheque** | straight into the bank picked on the receipt (*Paid into (our bank)*) | the moment the receipt is saved |
| **Transfer** | straight into the bank picked on the receipt (*Received into (our bank)*) | the moment the receipt is saved |
| MoMo / other | not a bank — stays in Collections | — |

* Every bank ledger line now says how the money arrived (`channel`: `cheque`, `transfer`, `deposit`, `expense`) and
  carries a narration ("Cheque received — receipt FWSSR123 (ref CHQ-9)"), shown under *Accounts › Banks*.
* Editing a receipt's cheque/transfer amount or bank adds a **reversal line** and a new posting; deleting the receipt
  adds a reversal. Nothing is ever deleted from the bank ledger.
* Bank Deposit only lists what still has to be carried to the bank: **cash**, plus cheques received **before** this
  change (those were never posted). A receipt paid only by cheque/transfer gets collection status **Banked** and
  never appears on the deposit screen, so a cheque can't be counted twice.

---

## 2. Balances and advances: what was wrong

The old single-invoice receipt code (`ReceiptController::store()` / `update()`, ~900 lines) let the cashier **choose**
"completed" or "uncompleted" and then patched `invoices.balance` up or down in one of four branches. Balances were
therefore only as right as every choice and every edit. Each case below was reproduced by posting to the real
endpoints against the old code, then rerun against the new code:

| # | What the cashier did (GH₵1,000 invoice) | Old result | New result |
|---|---|---|---|
| A | Paid 400, then **edited** the receipt to 450 | balance **150** (subtracted twice) | balance 550 |
| B | Paid 700 but picked "completed" | **completed** with 300 still owed (invoice disappears from the receipt list) | uncompleted, 300 |
| C | Paid 1000 but picked "uncompleted", then another 200 came in | uncompleted, balance **800** after 1,200 paid | completed after the first; second refused |
| D | Paid 400, then 800 | completed, balance **−200** (overpayment hidden) | 800 refused unless "keep extra as credit" is ticked; then 200 becomes client credit |
| F | Edited a receipt without re-attaching the cheque image | image **wiped** | image kept |
| G | Two receipts with WHT 37.50 each, re-saved the first unchanged | invoice reopened at **500**, WHT total overwritten to **37.50** | still completed, WHT 75 |

Root causes:
1. **Balance was a running number, not a derived one.** Every receipt nudged it; edits nudged it again from the
   already-nudged value. The "Reset Invoice Balances" tick box on the edit form existed to undo this by hand.
2. **`balance = 0` meant both "nothing paid" and "fully paid"** — status was the only way to tell, and status was typed.
3. **Status was chosen, not calculated.**
4. **Invoice WHT/VAT were overwritten**, not summed, on edit.
5. **"Advance payment" was a tick box that changed nothing.** There was nowhere to keep money paid ahead, so overpayments
   became negative balances. Worse, `receipts.advance_payment` was created as a **boolean** column while the code
   writes the word `advance`: strict MySQL rejects it, non-strict MySQL stores `0` — so the flag was probably never saved.
6. Editing a receipt replaced its **"received by"** user with whoever edited it.

---

## 3. Balances and advances: how they work now

**One rule.** For every invoice:

```
outstanding = invoice.total − SUM(receipt_allocations.settled)
settled     = cash applied + WHT + 7% VAT + other deductions      (per receipt, per invoice)
```

* Nothing paid → `unpaid`, balance 0 · part paid → `uncompleted`, balance = outstanding · fully paid → `completed`, 0.
* The single-invoice form, the multi-invoice form and the edit form all go through one service
  (`ReceiptRecorder`). The status drop-down is gone; the receipt says *completed* when every invoice it pays is settled.
* **Editing** first removes everything that receipt did (its allocation lines and client-ledger rows), re-derives the
  invoice from the other receipts, then applies the new amounts. It can't double-count or touch another receipt.
* **Deleting** does the same without re-applying, so other part payments survive.

**Advances / client credit**
* Money beyond what the ticked invoice(s) owe is **refused** unless the cashier ticks
  *"Keep any extra as client credit (advance)"* — so an extra zero is caught instead of becoming credit.
  When ticked, the extra is stored on the receipt as `unapplied_amount`.
* A payment with no invoice at all (true advance) is recorded from *Receipts › New Receipt* with no invoice ticked.
* Credit is used from the receipt page (**Apply credit**) against the client's later invoices. No new money moves.
* Both receipt forms warn when the client already holds credit.
* `advance_payment` is now **set automatically**: `advance` when part of the money is kept as credit, or when the
  receipt is dated before the month of an invoice it pays. The old tick box is gone.

---

### One receipt screen
Paying one invoice and paying several use the **same screen** (*Receipts › New Receipt*, `/receipt-multi/create`).
*Create receipt* on the unpaid-invoices list (`/receiptCreate/{id}`) opens it with the client chosen and that invoice
ticked; the client's other open invoices are listed, unticked, in case the payment covers more. So a single payment gets
exactly the same live totals, oldest-first allocation, credit handling, bank picker, approval routing and checks as a
multi-invoice one. Differences from the old single form:
* **WHT / VAT defaults** are the invoice's standard amount **less what earlier receipts already withheld** — the old
  form proposed the full 7.5% WHT again on every part payment, so ticking WHT twice deducted it twice.
* Receipt date defaults to today and *From* to the client's name; due dates are shown; the confirmation states the
  amount and how many invoices it covers.
* The "keep extra as client credit" tick only appears when there is extra money.
* The screen is limited to the user's office (the old form would open any invoice by its number).
* "Client already has a category for that month" is reported, as the old single form did.
The old `receiptCreate` view is removed. Editing a receipt still uses the single-invoice edit form.

## 4. Repairing balances the old code already got wrong

Fixing the code stops new damage; existing invoices still carry old mistakes. Run:

```bash
php artisan receipts:recompute                 # dry run: summary + CSV in storage/app, changes nothing
php artisan receipts:recompute --apply         # write the corrections
```

Every invoice is re-derived from its receipts and put in one bucket:

| Bucket | Meaning | On `--apply` |
|---|---|---|
| Correct already | matches its receipts | nothing |
| Wrong status/balance | e.g. cases A, C, G above | corrected |
| Completed but not fully paid | case B — **may be a deliberate write-off/discount** | left alone, listed for review; add `--reopen-short` to reopen |
| Overpaid | case D — negative balance | recalculated; add `--overpayments-to-credit` to move the extra cash into the latest receipt's client credit |

`--client=<id>` limits it to one client. Running it again after `--apply` should report everything correct except
the short-closed invoices you chose to keep.

---

## 5. Deploying

1. **Back up the database.**
2. `php artisan migrate` — adds `bank_transactions.channel`, widens the bank tables' money columns to
   `decimal(15,2)` (they capped at 999,999.99), and turns `receipts.advance_payment` into text
   (`1` → `advance`, `0` → empty).
3. `php artisan receipts:recompute` → review the CSV (especially *Completed but not fully paid*) → `--apply` with the
   options you want.
4. `php artisan client-ledger:sync` — fills the client ledger from all existing invoices and receipts (dated on the
   documents' own dates). Safe to re-run; afterwards it only posts what changed. It is also scheduled nightly at
   02:10, so make sure the server cron runs `php artisan schedule:run` every minute.
5. **PDF statements:** `composer require dompdf/dompdf` on the server. Without it, statements are still emailed (the
   full statement is in the email body) and can be printed to PDF from the page.
6. **Email:** set `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` and
   `MAIL_FROM_NAME` in `.env` (the default mailer only writes to the log). `APP_NAME` is printed on statements.
7. Add client email addresses (client edit form) — or type one when sending; it is remembered.
8. Make sure every company account exists under *Accounts › Banks*.
9. Cheques/transfers received **before** this release are not in any bank balance unless they were deposited.
   Undeposited old cheques still appear on Bank Deposit as "Old cheque to deposit". Old transfers need a one-off
   manual bank adjustment if balances should include them.

**Testing note:** the suite (66 tests; the PDF test runs once dompdf is installed) passes on SQLite and on MariaDB 10.11. A *fresh* MySQL install of this project
fails on the existing `create_expenses_table` migration (`->after()` inside `Schema::create`, and a foreign key to a
table created later). Existing databases are past that migration, so it only matters for new installs/CI.

---

## 6. Still open / suggested next

* **Bounced cheques.** A cheque is now in the bank balance as soon as it is receipted. If it bounces, edit or delete
  the receipt (void it, giving the reason; that posts the reversals). A "cheque bounced" action that reverses the bank line and reopens the
  invoice, keeping the receipt for the record, would be cleaner.
* **Receipts report timing buckets** (`App\Support\ReceiptReport`) still file a multi-invoice receipt under its first
  invoice's month. Per-invoice "received to date" is already split correctly.
* Widen the remaining money columns (invoices, receipts, collections) to `decimal(15,2)`.
* `collections.total_amount` includes WHT and VAT, so it overstates cash held.

---

## 7. Client credit and the statement of account

### What was wrong
The statement (`ClientController::statementOfAccount`) read the `transactions` table and, for **every row linked to a
receipt, credited the receipt's whole amount** (cash + momo + transfer + cheque + other + WHT + deductions). It ignored
the amount stored on the row. Reproduced with: invoices 1,000 (Aug) + 500 (Sep); receipt 1,300 with 300 kept as
credit; credit applied to September; 200 transfer. Truth: invoiced 1,500, received 1,500, owes 0.

| Old statement row | Credit shown | Should be |
|---|---|---|
| Receipt FWSSR1 → August invoice | 1,300 | 1,300 |
| Credit from FWSSR1 applied to September | **1,300 again** | nothing new — the money was already counted |
| Transfer 200 | 200 | 200 |
| **Final balance** | **1,300** (looks owed) | **0** |

The database itself stored the right figure for the credit application (300 on the allocation and on the
transactions row); only the statement re-read the receipt. Other problems found the same way:
* **7% VAT withheld was never credited** — a fully paid invoice still showed 70 owed.
* A receipt paid only by **"other payments" crashed the page** (HTTP 500).
* **A client in credit looked like a debtor**: the balance was shown with `abs()`, so −150 printed as 150.
* **An advance with no invoice never appeared** (no transactions row).
* A receipt paying two invoices was credited twice (one row per invoice).
* Rows were in creation order, not date order; no period, no opening balance; sorting the table scrambled the balance.
* The client page's "Total Amount Paid" was `invoiced − unpaid − part balances`, so short-closed invoices counted as paid
  and credit was invisible.

### What it does now
`App\Services\Receipts\ClientStatement` builds the statement from the client ledger, which is posted from the documents (see "Built" below):
* **Debit** each invoice. **Credit** each receipt once for the money received, plus separate lines for WHT, 7% VAT
  withheld and deductions.
* **Credit applied** is an information line with no amount: it moves money between invoices, it doesn't change what the
  client owes.
* Signed running balance: `1,250.00` = client owes, `300.00 CR` = client is in credit.
* Date range with opening balance; open invoices with days overdue; unused credit by receipt; print and Excel export;
  sorting disabled so the running balance stays meaningful. Receipts awaiting approval are included and labelled.
* **Self-check:** closing balance must equal *Σ(invoice total − paid against it) − unused credit*. If old data breaks
  that, the page says by how much and points to `receipts:recompute --client=…`.
* The client page summary uses the same figures.

### Built: the better approach

**1. One source of truth — the client ledger** (`client_ledger_entries`, `ClientLedger`).
Every invoice, receipt, WHT/VAT/deduction and credit application is posted once as a permanent entry. Entries are
never edited or deleted. When a document changes, the old entry gets a **reversal dated the day of the change** and
the corrected entry is posted the same day ("Corrected entry, document dated 20 Aug 2026"). So:
* a statement already sent for August reads exactly the same in December;
* the correction is visible on the statement for the period it happened in;
* the ledger is synced at the end of every invoice/receipt change, before every statement is shown, and nightly — it
  cannot drift from the documents, and the self-check still compares it with the invoice-by-invoice position.

**2. Transactions table retired as a source.** The client page's *Transactions* tab is now *Account*, showing the
ledger lines. The *Transactions* menu page carries a notice pointing to Aged receivables. Rows are still written for
one more release, then the table, its page and `TransactionController` can be removed.

**3. Credit applied automatically.** When an invoice is raised (new or duplicated), unused credit is applied to it,
oldest credit first, and the confirmation message says how much. Each client has a
*"Use unused credit on new invoices automatically"* switch (on by default) on the client form. The receipt page's
*Apply credit* form uses the same rules for manual cases.

**4. Statements sent to clients, and aged receivables.**
* Statement page → **Email**: To (pre-filled from the client's email), Cc, optional message. The email body is the full
  statement; the PDF is attached when dompdf is installed (and there is a **PDF** download button). Every send is
  logged (who, to whom, period, balance) and the last five are shown on the page.
* **Receipts › Aged receivables** (`/receivables/aged`): every client's owed amount split into *not yet due*,
  *1–30*, *31–60*, *61–90*, *over 90 days* overdue (from the invoice due date), with unused credit netted off, totals,
  office filter, Excel export and a link to each statement. Branch users see their own office only.

**5. Void, don't delete.** The receipt list's *Delete* is now **Void** (asks for a reason), and the receipt page has a
*Void* button with a reason box. Voiding keeps a full copy in `receipt_voids` (*Receipts › Voided*), reverses
cheque/transfer money out of the bank, reopens the invoices it paid and posts ledger reversals dated today with the
reason. The receipt leaves the receipts table, so every existing report keeps working unchanged.
Invoices with payments recorded against them can no longer be deleted (void the receipts first), and changing an
invoice's total re-derives its status and posts the correction.

---

## Code map

| File | Purpose |
|---|---|
| `app/Services/Receipts/ReceiptRecorder.php` | The one place receipts are created and edited (all forms) |
| `app/Services/Receipts/ReceiptAllocator.php` | Pure maths: split money over invoices, validate, leftover credit |
| `app/Services/Receipts/InvoiceSettlement.php` | Allocation lines + client ledger; re-derives invoice status/balance; release on edit/delete |
| `app/Services/Receipts/BankPosting.php` | Cheque/transfer straight to bank, reversals, what's left to deposit |
| `app/Services/Receipts/BalanceRecompute.php` + `routes/console.php` | `receipts:recompute` |
| `app/Services/Receipts/ReceiptWorkflow.php` | Approval routing and category A–D |
| `app/Http/Controllers/MultiInvoiceReceiptController.php` | Multi-invoice screen, apply credit |
| `tests/Feature/ReceiptBalanceAndBankTest.php` | The A–G cases above, cheque posting, deposits |
| `tests/Feature/ReceiptRecomputeCommandTest.php` | The repair command |
| `app/Services/Receipts/ClientStatement.php` | Statement of account and client page summary |
| `tests/Feature/ClientStatementTest.php` | Credit applied once, VAT/WHT credited, CR balance, advances, date range, self-check |
| `app/Services/Receipts/ClientLedger.php` + `client-ledger:sync` | Permanent client account entries, reversals, nightly safety net |
| `app/Services/Receipts/CreditApplier.php` | Manual and automatic use of client credit |
| `app/Services/Receipts/ReceiptVoider.php` | Void with reason, archive, reversals |
| `app/Services/Receipts/ReceivablesAging.php` | Aged receivables report |
| `app/Services/Receipts/StatementPdf.php`, `app/Mail/StatementOfAccountMail.php` | Statement PDF and email |
| `app/Http/Controllers/ClientAccountController.php` | Email/PDF statement, aged receivables |
| `tests/Feature/ClientLedgerAndReceivablesTest.php`, `tests/Feature/StatementPdfTest.php` | Ledger, corrections, void, auto-credit, email, PDF, aged report, office scope |

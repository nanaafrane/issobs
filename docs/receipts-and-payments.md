# Receipts & payments: analysis, changes and how to run it

---

## 1. How money flows now

| Paid by | Where it goes | When |
|---|---|---|
| **Cash** | Collection → *Bank Deposit* → the bank picked on the deposit slip | when someone deposits it |
| **Cheque** | straight into the bank picked on the receipt (*Paid into (our bank)*) | the moment the receipt is saved |
| **Transfer** | straight into the bank picked on the receipt (*Received into (our bank)*) | the moment the receipt is saved |
| **MoMo** | Collection → withdrawn → *Bank Deposit* (alone or with cash); the withdrawal charge is a separate bank debit | when someone deposits it |
| Other | not a bank — stays in Collections | — |

* Every bank ledger line now says how the money arrived (`channel`: `cheque`, `transfer`, `deposit`, `momo_charge`, `expense`) and
  carries a narration ("Cheque received — receipt FWSSR123 (ref CHQ-9)"), shown under *Accounts › Banks*.
* Editing a receipt's cheque/transfer amount or bank adds a **reversal line** and a new posting; deleting the receipt
  adds a reversal. Nothing is ever deleted from the bank ledger.
* Bank Deposit only lists what still has to be carried to the bank: **cash** and **MoMo**, plus cheques received
  **before** this change (those were never posted). See *Cash and MoMo deposits* below. A receipt paid only by cheque/transfer gets collection status **Banked** and
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
The old `receiptCreate` view is removed.

**Editing uses the same screen** (`/receipt/{id}/edit` → `/receipt-multi/{id}/edit`, saved by `PUT /receipt/{id}`).
The client is fixed; the invoices the receipt pays are ticked with the amounts, WHT, VAT and deductions it saved; its
payment modes, references, payer's banks and *our* banks are filled in; the attachment is kept unless replaced.
Outstanding figures and WHT/VAT defaults are shown **without this receipt**, so money can be moved between invoices,
an invoice added or removed, or the extra kept as credit — then saving re-applies the receipt from scratch
(`ReceiptRecorder::revise`). What the old edit form got wrong:
* It pre-filled WHT/VAT with the **standard amount (hard-coded 7.5%)**, not what was saved, so re-saving silently changed
  the receipt's WHT/VAT.
* Payment rows only opened when the receipt had exactly one mode (`mode == 'cheque'`), so a cheque + cash receipt
  opened with its details hidden; *other payments* never reopened.
* Receipts paying several invoices, advances without an invoice, and receipts whose credit had been applied could not
  be edited at all (void and re-enter was the only way); the invoices paid could not be changed.
* A required *Assign to* box that was ignored on save (an edit never changes the approval routing — still true).

Credit from the receipt already applied to other invoices (*Apply credit*) **stays applied**; the screen shows it and
the edited money must still cover it. A receipt whose cash/MoMo has been deposited cannot be edited (reverse the
deposit first), and only users of the client's office can open or save it.

### If the connection drops while saving
* **Never half-saved.** A receipt (with its invoice updates, collection, bank posting and client-ledger lines), an
  edit, a void, applying credit, and an invoice (with its lines, totals, automatic credit and ledger) are each saved
  in one database transaction: everything or nothing. This needs InnoDB tables — check with
  `php artisan db:check-engines`, which lists any table that isn't and prints the `ALTER TABLE … ENGINE=InnoDB` to fix it.
* **Never saved twice.** Each of those forms carries a hidden one-time code. If the server saved it but the reply was
  lost and the page is sent again, the second arrival is shown what the first saved ("This was already saved — the
  connection probably dropped after saving. Nothing was recorded twice."). Two copies arriving together are handled by
  a unique index. Codes are kept 60 days (`form-submissions:prune` runs nightly).
* **Button locks.** After the confirm dialog, the Generate/Update/Void/Apply buttons lock and show "Saving…"; pressing
  again or Enter does nothing. Going Back unlocks them.
* **Likely repeats.** A new receipt is held for confirmation when its cheque number (same bank), transfer reference or
  MoMo transaction ID is already on a receipt, or when the same client was receipted the same amount in the last
  10 minutes. The screen explains which receipt it looks like; ticking *"Yes, record it as a separate payment"* saves it.
* **Cashier's rule of thumb:** if the page errors after pressing Generate, open the receipt list or the client's
  statement before entering it again — or simply press Generate again on the same page; it will not be saved twice.

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
  office filter and Excel export. Branch users see their own office only. Clicking a client opens
  `/receivables/aged/{client}`: only the invoices that still have something owing (oldest first) with due date, days
  overdue, paid so far, owed and age, a *Receive payment* button per invoice, unused credit, and a link to the full statement.

**5. Void, don't delete.** The receipt list's *Delete* is now **Void** (asks for a reason), and the receipt page has a
*Void* button with a reason box. Voiding keeps a full copy in `receipt_voids` (*Receipts › Voided*), reverses
cheque/transfer money out of the bank, reopens the invoices it paid and posts ledger reversals dated today with the
reason. The receipt leaves the receipts table, so every existing report keeps working unchanged.
Invoices with payments recorded against them can no longer be deleted (void the receipts first), and changing an
invoice's total re-derives its status and posts the correction.

---

## 8. Cash and MoMo deposits

### What was wrong

* **MoMo never reached a bank.** Collections recorded MoMo, but Bank Deposit ignored it, so MoMo receipts sat as
  "undeposited" forever and the bank balance never included them. A receipt paid by cheque *and* MoMo was even marked
  **Banked**, hiding the MoMo completely.
* **No place for the withdrawal charge.** MoMo has to be cashed out before it can be deposited, and the vendor/agent
  keeps a charge. That cost was invisible, so the bank was short by it with no line explaining why.
* **One deposit per receipt, not per slip.** Ticking 10 receipts made 10 deposits and 10 bank lines, while the bank
  shows one slip. No slip number, no date on the slip, no "deposited by" — nothing to reconcile against.
* Every row needed its own bank picked; deposits could not be corrected (only edited in the database); the summary
  tiles were hard-coded to 0; nothing stopped the deposit page being used by anyone.

### How it works now

* **One deposit = one bank slip**: bank account, date on the slip, slip/ref number, deposited by, notes.
* Each receipt's money waits in **two parts**, ticked separately: **Cash** (deposited alone or with MoMo) and **MoMo**
  (deposited alone or with cash). The two parts of one receipt may go on different slips; until both are banked the
  collection shows *Part deposited*.
* When MoMo is ticked, the **MoMo withdrawal charge** must be entered (0 if none). It must be less than the MoMo
  withdrawn. `MOMO_WITHDRAWAL_CHARGE_RATE` in `.env` (e.g. `1` for 1%) only pre-fills the box; the real figure from the
  withdrawal SMS is what's saved.
* The screen adds it up live: *Cash + MoMo − charge = Amount on slip*, and shows exactly what the bank account will
  get before saving.

**On the bank account** (Accounts › Banks), a slip with cash 500, MoMo 300 and a 3.00 charge posts:

| Line | Money in | Money out | Narration |
|---|---|---|---|
| `deposit` | 800.00 | | Deposit slip 1234 of 20 Oct 2026 — cash 500.00 + MoMo 300.00 (slip 797.00 after MoMo charge) · receipts FWSSR… |
| `MoMo charge` | | 3.00 | MoMo withdrawal charge slip 1234 of 20 Oct 2026 — on MoMo 300.00 withdrawn for deposit #… · receipt FWSSR… |

The bank balance moves by **797.00 — exactly the slip**. The charge is its own debit line, labelled *MoMo charge*, so it
can be found, filtered and totalled.

> Why the deposit line is the gross 800 and not the 797 left after the charge: if the books credited 797 **and** also
> debited the 3.00 charge, the balance would go up by only 794 — 3.00 less than the bank actually received. Crediting
> the gross amount and debiting the charge shows the charge clearly *and* keeps the balance equal to the bank's. The
> amount on the slip (797) is stored on the deposit and printed in the narration for reconciliation.

* **Deposits made** (Accounts › Bank Deposit): one row per slip for a month, with cash, MoMo, charge and amount on
  slip, totals, the month's MoMo charges (and the % of MoMo withdrawn they cost), what's still waiting, Excel export.
* **Deposit page**: the slip, its bank lines, and every receipt on it. **Reverse** (Finance Manager / Director, with a
  reason) posts both bank lines back and puts the cash and MoMo back on Bank Deposit. Nothing is deleted.
* A receipt whose cash or MoMo has been deposited **cannot be edited or voided** until that deposit is reversed.
* **Already banked by hand?** Finance Manager / Director can tick old collections and *Mark as already banked* with a
  note (e.g. old MoMo cashed and deposited before this release). They close with the note kept and **nothing is
  posted to a bank** — use this once to clear history, not for new money.
* Who: deposits by Invoice, Finance Manager, Director. Branch staff see what is waiting for their own office (Tema also
  sees Shai Hills) but cannot deposit.
* Same-submission protection as receipts: a double click or a dropped connection never makes two deposits.

### After deploying

1. `php artisan migrate` (adds slip columns to `bank_deposits`, and `cash_deposit_id` / `momo_deposit_id` /
   `deposit_note` to `collections`; zero-date safe and re-runnable).
2. Open Bank Deposit. MoMo from receipts that were never deposited now appears under **MoMo waiting**. Anything there
   that was already banked by hand: tick it and *Mark as already banked* with a note. Everything else: deposit it.
3. Optional: `MOMO_WITHDRAWAL_CHARGE_RATE=` in `.env` to pre-fill the charge.

---

## 9. Money leaving the banks, and Main Cash

### What was wrong

* **Nothing recorded money leaving a bank.** The ledger only had money coming in (receipts, deposits), so bank
  balances only ever went up, and approved expenses were never marked *paid* or tied to where the money came from.
* **Editing a bank could overwrite its balance** (`total` was saved straight from the form, unvalidated) — a balance
  could change with no ledger line. **Delete really deleted** the bank (the message said otherwise), orphaning its
  history; it could also be a bank used on employees' payment details.
* *Statement and details* opened only an edit form: there was **no account statement**.
* Ledger dates were the moment of typing (`created_at`), not the day the money moved.
* The `banks` list is shared with HR/payroll (employees' bank, salaries by bank), so any new kind of account would have
  shown up in those pickers.
* `expenses.expense_date` was used by the expense screens but **no migration created it** (added by hand on live).

### The approach

**Main Cash is a cash-on-hand account** — the *cash book*, run on the **imprest (petty cash) system**: it is funded from
the banks by cheque or withdrawal, holds physical cash with a named custodian, and pays approved expenses. In the
system it is an account of kind **Cash** next to the banks, with the same ledger, balance and statement.

| Movement | From → To | Ledger | In "money in / out"? |
|---|---|---|---|
| **Fund / transfer** | bank → Main Cash (cheque, cash withdrawal); bank → bank (cheque, transfer); Main Cash → bank (cash paid in); cash → cash (handed over) | debit *Moved out* on the source, credit *Moved in* on the destination, linked | **No** — it is our own money changing place |
| **Payment** | bank → someone else (cheque, transfer, MoMo); Main Cash → someone else (cash) | one debit, with payee, category, reference | Yes, by category |
| **Adjustment** | any account, + or − with a reason | one line | Yes, as *Adjustments* |

* **Several cheques from several banks** can fund Main Cash in **one entry** (one line per cheque); they share a batch
  and are reversed together if entered wrongly.
* **Pay approved expenses**: tick them (fully approved, not yet paid); the amount is their total, they show *Paid* with a
  link to the payment, and a paid expense can no longer be edited. The expense list shows *Awaiting payment* and a
  *Pay* button for finance.
* **Cheques** are *issued* until marked **cleared** (date from the bank statement). Uncleared cheques are listed per bank
  for reconciliation: *books + uncleared cheques = what the bank statement should show*.
* **Mistakes are cancelled / reversed with a reason** (Finance Manager / Director): the opposite line is posted, nothing
  is deleted; cancelling an expense payment puts its expenses back to *awaiting payment*.
* **Main Cash can never go below zero**; a payment or transfer that would overdraw it is refused ("fund it from a bank").
* **Balances are never typed in.** A new account starts with an opening balance and date; after that only ledger lines
  move it, so `total = opening balance + money in − money out` always (checked on every page; any account that breaks
  it is flagged). Corrections are adjustments with a reason. An account with history is **closed**, not deleted (only
  at 0.00); only a never-used account can be deleted.
* Ledger lines carry the **date the money moved** (`entry_date`: cheque / payment / deposit date) as well as when they
  were recorded.
* **Cash accounts never appear in bank pickers** — receipts (cheque/transfer *into our bank*), Bank Deposit, employee
  payment details, payroll by bank, employee upload template — and the server rejects them there too.
* Collected cash still goes **to the bank** through Bank Deposit (*bank all takings intact*); Main Cash is funded only
  from the banks. That keeps every cedi received on a bank statement.

### Reports

* **Bank & cash** (overview): total split into *in banks* and *cash on hand*; money in / out for the period **from the
  business's point of view** (transfers between our accounts left out and shown separately); **where money came from
  and where it went** — cheques / transfers from clients, cash & MoMo deposited, then payments by category (approved
  expenses, suppliers, salaries, statutory, bank charges, rent & utilities, loans, refunds, drawings, other), MoMo
  charges and adjustments; **needs attention** — approved expenses not yet paid, uncleared cheques, balance checks;
  every ledger line with what posted it (receipt, deposit, payment, transfer, adjustment).
* **Account statement** (each account): period with presets, opening balance, every line with running balance,
  closing balance, Excel export and print; for a bank, the **reconciliation** box and uncleared cheques (mark cleared);
  for cash, a **cash count** that shows the difference and pre-fills the adjustment.
* **Payments**: by month / account / category / status, totals by category, cash vs cheque/transfer, uncleared cheques,
  approved expenses unpaid; Excel export.
* **Transfers**: by month, into and out of each account.

### After deploying

1. `php artisan migrate`. Existing balances are kept exactly: each account gets an opening balance = its balance minus
   what its ledger already explains (dated at its first ledger line). Existing ledger lines are dated on the day they
   were entered. Zero-date safe, re-runnable.
2. *Accounts › Bank & cash › Add account › Cash on hand*: name **Main Cash**, custodian, opening balance = the cash
   counted today.
3. Fund it (*Fund / transfer*) with the cheque(s) written to it, and pay approved expenses from it (*Make a payment ›
   Pay approved expenses*).
4. When each bank statement arrives: mark cheques cleared, enter bank charges as payments (*Bank charges*) or
   adjustments, and check the reconciliation box.

### Suggested next

* **Payroll into the ledger**: when a payment run's funds are released, post one payment (*Salaries & wages*) from the
  paying bank, so salaries show on the bank statements automatically.
* **Payment vouchers / approval** for large non-expense payments (two people: one records, one approves).
* **Import the bank statement** (CSV) and match lines automatically to receipts, deposits and cheques.
* **Budget vs actual** per expense type and office, from the paid-expense figures.
* A full **chart of accounts / double entry** (income, expense, asset, liability accounts) if the company wants the
  system to produce a trial balance, P&L and balance sheet rather than exporting to an accounting package.

---

## 10. Paying overtime: pay-outs

### What was wrong

* Every approved daily overtime entry created **its own pre-approved expense**, so *Approved expenses waiting to be
  paid* (and the bank payment screen) filled with one line per guard per shift per day, each to be paid separately.
* Editing, rejecting or deleting an entry changed or deleted its expense **even after it was paid**, so payments no
  longer matched the work.
* Nothing recorded that the guard actually received the money (cash handed over, MoMo reference).
* Salaries also carry an `overtime` figure (payroll sheet) and nothing compared it with the daily overtime, so the same
  overtime could be paid twice.
* The old observer's `expenses.overtime_id` was not a fillable field, so those expenses were linked only by
  `overtimes.expense_id`.

### How it works now

* Approved entries are **owed** (*Operations › Overtime › Pay-outs* shows what is owed by office). They no longer create
  expenses and never appear one by one on the bank payment screen.
* **Finance raises one pay-out** for an office and chosen dates, ticks the entries, and picks how it is paid:
  * **Cash, through the office officer** — from Main Cash (cash) or a bank (cheque/transfer) to the named officer;
  * **MoMo from head office** — from a bank by mobile money;
  * **With the monthly salary** — no money moves now; the entries are marked for that month's payroll.
* For cash / MoMo the pay-out creates **one expense** (type *Overtime*, the office, the total) and **one payment**
  (category *Overtime pay-outs*), so the bank or cash statement shows one line:
  "Cash … to Kwame (Tema overtime) — Overtime pay-outs: Overtime pay-out #12 — Tema 14 Oct–20 Oct 2026 · 23 entries".
* **The office ticks each guard as paid** on the pay-out page (date; for MoMo the transaction ID of each guard). The
  page shows *paid to guards*, *still held by the officer* and *returned*; the overview shows **cash held by officers**.
* **Money not paid out** (guard did not collect, MoMo failed) is **returned** by finance into an account with a reason:
  money in on that account, linked to the payment; the expense is reduced to what the guards got; those entries are
  owed again for the next pay-out. When every entry is paid or returned the pay-out closes.
* **Cancel** (Finance Manager / Director) while nothing has been paid or returned: the payment is reversed, the expense
  removed, the entries owed again.
* **Entries on a pay-out are locked**: no edit, reject or delete (cancel or return them first).
* **Payroll check** (per month, and on each payroll pay-out): for each guard, overtime sent to that payroll against the
  `overtime` on their salary. *+* = the salary pays more (possible double pay, or also paid in cash/MoMo);
  *−* = approved overtime missing from the salary.
* Who: finance (Invoice, Finance Manager, Director) raises pay-outs and returns money; the office (and its
  sub-offices, e.g. Tema + Shai Hills) sees its pay-outs and ticks its guards paid.
* Expense reports now show overtime **when it is paid out** (one expense per pay-out, dated at the end of its period),
  not when each entry was approved. Overtime cost by date / client / guard is still on *Overtime › Reports*.

### After deploying

`php artisan migrate`: old per-entry overtime expenses that were **never paid** are removed (their entries become owed
and can go on a pay-out); any that **were already paid** from Bank & cash are kept and their entries are marked paid by
that payment. Zero-date safe, re-runnable.

---

## 11. Creditors: money the company borrows (director's loan)

### What was wrong

When a director put money into a company account there was no right way to record it: an *adjustment* (counted as
**money in**, as if it were income) or nothing at all; paying it back was a payment under *Loan repayment* (counted as
**money out**, as if it were an expense). Nowhere showed how much the company owed the director.

### How it works now

A director's (or shareholder's, or lender's) money is a **liability**: the company owes it back. It is not income, and
repaying it is not an expense. *Accounts › Creditors (loans)*:

| Action | Company account | Creditor's balance owed |
|---|---|---|
| **Money received** — into a bank (transfer / cheque / MoMo) or **Main Cash** | + money in, *Funding (to repay)* (`loan_in`) | + |
| **Paid on our behalf** — the creditor paid a supplier or ticked **approved expenses** from their own money | — no company money moves | + (the expenses show *Paid by a creditor*) |
| **Repay** — from a bank or Main Cash, never more than is owed | − money out, *Loan repayment* (`loan_out`) | − |
| **Interest** (optional, with a repayment) | − a payment, category *Interest on loans* | — (a cost, counted as money out) |
| **Reverse** (Finance Manager / Director, with a reason) | the opposite line | the opposite; expenses back to awaiting payment |

* Each creditor has a **statement**: opening balance owed, every entry, running balance, closing; Excel / print. The
  list shows what is owed to each, borrowed and repaid this month, and the **total owed**.
* **Bank & cash** keeps borrowed money out of *money in / money out* and shows it on its own *Financing* line
  (received / repaid) — so the figures show what the business itself earned and spent; *Needs attention* shows the
  total owed to creditors. Bank statements label the lines *Funding (to repay)* and *Loan repayment*, linked to the
  creditor.
* Main Cash still cannot go below zero; a repayment it cannot cover is refused.
* A creditor set up with money already owed from before gets it as its opening balance.
* The old payment category *Loan repayment* is kept for past entries; new repayments go through Creditors.

---

## Code map

| File | Purpose |
|---|---|
| `app/Services/Receipts/ReceiptRecorder.php` | The one place receipts are created and edited (all forms) |
| `app/Services/Receipts/ReceiptAllocator.php` | Pure maths: split money over invoices, validate, leftover credit |
| `app/Services/Receipts/InvoiceSettlement.php` | Allocation lines + client ledger; re-derives invoice status/balance; release on edit/delete |
| `app/Services/Receipts/BankPosting.php` | Cheque/transfer straight to bank, reversals, what's left to deposit |
| `app/Services/Banking/DepositBook.php` | Cash/MoMo waiting, deposit slips, MoMo charge debit, reversal, mark already banked |
| `app/Http/Controllers/BankDepositController.php`, `resources/views/banks/deposit_*.blade.php` | Bank Deposit screen, deposits made, deposit page |
| `tests/Feature/BankDepositTest.php` | Cash alone, MoMo alone, both, charge rules, part deposits, reversal, office scope |
| `app/Services/Banking/AccountBook.php` | Transfers between our accounts (fund Main Cash), payments, cheque clearing, cancel/reverse, adjustments |
| `app/Services/Banking/AccountReports.php` | Account statement, where money came from / went, balance checks, expenses awaiting payment |
| `app/Http/Controllers/BankController.php`, `AccountMovementController.php`, `resources/views/banks/*` | Bank & cash overview, statement, transfers, payments |
| `tests/Feature/MainCashAndPaymentsTest.php` | Main Cash, multi-cheque funding, expense payments, cheques, cancellations, statement, migration |
| `app/Services/Overtime/OvertimePayouts.php`, `app/Http/Controllers/OvertimePayoutController.php`, `resources/views/overtime/payout*` | Overtime pay-outs: one expense + payment per office and period, disbursement per guard, returns, cancel, payroll check |
| `tests/Feature/OvertimePayoutTest.php` | Cash / MoMo / payroll pay-outs, disbursement, returns, locks, office scope, migration |
| `app/Services/Banking/CreditorBook.php`, `app/Http/Controllers/CreditorController.php`, `resources/views/creditors/*` | Creditors: money received, paid on our behalf, repaid (+ interest), reversals, statements |
| `tests/Feature/CreditorsTest.php` | Director's loan in, on behalf, repay, interest, reversals, statement, financing kept out of in/out |
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

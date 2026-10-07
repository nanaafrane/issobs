# Receipts & payments — analysis, changes and proposal

Branch: `feature/multi-invoice-receipts-bank-selection`

---

## 1. How receipts worked before this change

Everything lives in `ReceiptController::store()` (≈650 lines) and `createReceipt()`.

* A receipt is **always tied to exactly one invoice** (`receipts.invoice_id`, required by `StoreReceiptRequest`).
* The cashier opens *Receipt › Create*, picks one invoice, and fills in the money by mode
  (cash, momo, cheque, transfer, other — several can be ticked).
* `total = cash + momo + cheque + transfer + other + WHT + 7% VAT`; other deductions are kept in `dAmount`.
  The invoice is settled by `total + dAmount`.
* The cashier **chooses the status by hand** (`completed` / `uncompleted`) and the code picks one of four branches:

| Branch | Condition | What happens to the invoice |
|---|---|---|
| 1. Full, one go | status completed and `total + balance == total paid` | status completed (balance column not updated) |
| 2. Overpaid | status completed, paid > invoice total, balance 0 | balance goes **negative** (the overpayment is hidden there) |
| 3. Final part payment | status completed, balance > 0 and fully covered | balance = old balance − paid |
| 4. Part payment (else) | anything else | balance = (balance or total) − paid, status = what was typed |

  Every branch then creates a `Transaction` (client ledger), a `Collection` (cash book), and assigns a
  client payment **category** (A–D). The category block is copied four times.
* `invoices.balance = 0` means *both* "nothing paid yet" and "fully paid" — status is the only way to tell.

### Advance payments
* "Tick for advance payment" only stores the word `advance` on the receipt. It is a **label**, nothing more:
  * you still need an existing invoice to write the receipt against;
  * money paid ahead is not tracked as client credit anywhere;
  * an overpayment ends up as a negative invoice balance, not as credit you can use later.
* Note: the original migration declares `receipts.advance_payment` as `boolean` but the code writes the string `advance`.
  Check the production column type; on strict MySQL a boolean column would reject it.

### Cheque & transfer
* `cheque_bank` / `transfer_bank` are free text — the **payer's** bank. There was no record of which of *our*
  accounts the money went to.
* Cash and cheques reach a bank only through *Collections › Bank Deposit*, which credits `cash + cheque`.
  **Transfers were never credited to any bank account**, so bank balances under-reported every transfer.

### Bugs found while tracing the flow
* **Cheque and transfer details swapped on part payments.** Branch 4 passed cheque details into the transfer slots.
  Already fixed upstream in `42b16de`.

Fixed on this branch:
1. **"Deposited" receipts could still be edited/deleted.** Deposit saves `Deposited`, the guard compared with `deposited`.
2. **Bank deposit paired collections with the wrong bank** when only some rows were ticked
   (`collections[]` and `bank_id[]` were matched by position). Every row's bank was also mandatory.
3. **Deleting a receipt reset the whole invoice to "unpaid"**, wiping out other part payments on the same invoice.

### Other things worth knowing
* All money columns are `decimal(8,2)` — max **999,999.99**. `banks.total` will overflow once an account passes GH₵1m.
  New columns on this branch use `decimal(15,2)`.
* The legacy category check uses `whereMonth()` only, so January 2026 blocks January 2027. The new code checks year + month.
* `collections.total_amount` includes WHT and VAT (not just cash), so it overstates cash to be banked.
* **Receipts report (`App\Support\ReceiptReport`)**: the invoice-month filter and payment-timing buckets follow
  `receipts.invoice_id`, so a multi-invoice receipt is counted under its first (oldest) invoice, and an advance with no
  invoice shows as "No invoice linked". The per-invoice "received to date" figure uses the allocation lines and is
  split correctly. Splitting the timing buckets per invoice would mean reworking the report's queries.
* **Payroll invoice status (`App\Support\ClientInvoiceStatus`)** finds receipts through the allocation lines, so every
  invoice on a multi-invoice receipt gets its paid date; credit applied later counts as paid on the day it was applied.

---

## 2. What this branch adds

### One receipt → many invoices
*Receipts › Pay Multiple Invoices* (`/receipt-multi/create`, also linked from *Receipt › Create* and the receipt list).

1. Pick the client (only clients with unpaid invoices, limited to the user's office like the receipt list).
2. Tick the invoices this payment covers. Each row shows what is **still owed**.
   Optional per-invoice WHT, 7% VAT and deductions (switches pre-fill the standard amounts).
3. Enter the money **once** (any mix of modes). It is applied **oldest invoice first**; typing an amount on a row pins it.
4. A live summary shows money received, applied, credits, and anything left over.
5. One receipt is created: one `Collection`, one bank posting, one allocation line + one ledger `Transaction` per invoice.
   Each invoice's status/balance is **worked out automatically** — no manual "completed/uncompleted".

### Advance payments that actually work
* If more money is received than the ticked invoices owe — or no invoice is ticked — the cashier must tick
  **"Keep the extra as client credit (advance)"**. The extra is stored in `receipts.unapplied_amount`.
* The receipt page shows the credit and an **Apply credit** form: tick the client's new invoices and settle them from
  the credit. No new money is recorded (no extra collection or bank posting).
* The multi-invoice screen warns when the client already holds credit on earlier receipts.

### Which bank the money goes to
* Cheque: **Deposit into (our bank)**. Transfer: **Received into (our bank)**. Both are required when the mode is ticked,
  on the single-invoice create and edit forms and the multi-invoice form. The old free-text fields are relabelled as the
  **payer's** bank.
* **Transfers are posted to the chosen bank immediately** (`bank_transactions` credit with `receipt_id`, bank total updated).
  Editing the receipt re-posts; deleting it adds a **reversal line** (nothing is deleted from the bank ledger).
* Bank Deposit pre-selects the bank chosen on the receipt for each collection.
* Receipt page and printout show the receiving bank and every invoice paid.

### Data model
```
receipts ─┬─< receipt_allocations >─┬─ invoices
          │   amount_applied        │
          │   wht_amount            │   outstanding = invoice.total − SUM(allocations.settled)
          │   vat7_amount           │
          │   deduction_amount      │
          │   settled (sum)         │
          │   source: receipt | credit | legacy
          ├─ unapplied_amount   (client credit)
          ├─ cheque_to_bank_id  → banks
          └─ transfer_to_bank_id → banks
```
* `receipts.invoice_id` is kept (first invoice paid) so every existing report, dashboard and export still works.
* The migration **backfills one allocation per existing receipt** (`source = legacy`, settled = `total + dAmount`),
  so old and new receipts read the same way.
* Classic single-invoice receipts keep their existing logic and now also write/refresh their allocation line.
* Multi-invoice / credit receipts can't be opened in the single-invoice edit form (it would corrupt allocations);
  the user is told to delete and re-record instead.

### Code map
| File | Purpose |
|---|---|
| `app/Services/Receipts/ReceiptAllocator.php` | Pure maths: split money over invoices, validate, leftover credit |
| `app/Services/Receipts/InvoiceSettlement.php` | Writes allocations + ledger rows; re-derives invoice status/balance; releases on delete |
| `app/Services/Receipts/BankPosting.php` | Posts / reverses transfers to our bank accounts |
| `app/Services/Receipts/ReceiptWorkflow.php` | Approval routing and category A–D, shared by both flows |
| `app/Http/Controllers/MultiInvoiceReceiptController.php` | Multi-invoice create/store, apply credit |
| `app/Http/Requests/Concerns/PaymentModeRules.php` | One set of payment-mode validation for all receipt forms |
| `resources/views/sales/receipt_multi_create.blade.php` | The new screen |
| `tests/Unit/ReceiptAllocatorTest.php`, `tests/Feature/MultiInvoiceReceiptTest.php` | 22 tests covering the flows above |

### Deploying
1. Back up the database, then `php artisan migrate` (creates `receipt_allocations`, adds the bank/credit columns, backfills).
2. Make sure every company account exists under *Accounts › Banks* — the new pickers list them.
3. Spot-check: `SELECT COUNT(*) FROM receipts WHERE invoice_id IS NOT NULL` should equal
   `SELECT COUNT(DISTINCT receipt_id) FROM receipt_allocations`.
4. Transfers recorded **before** this release were never posted to a bank. If bank balances should include them,
   post them once with a manual adjustment rather than re-saving old receipts.

---

## 3. Proposal: a simpler way to manage receipts

The branch is built so the system can move to this model step by step without breaking reports.

1. **One rule for invoice status.** Status and balance should always be *derived* from allocation lines
   (`InvoiceSettlement::recalculate()`), never chosen by the cashier. Next step: route the single-invoice form through
   the same allocator (it is just the multi-invoice flow with one row) and delete the four-branch `store()` and
   `update()` logic. That removes ~900 lines and the class of bugs listed above.
2. **Receipt = money in; allocation = what it paid.** A receipt records only money received and how (modes, bank).
   What it paid is the allocation lines. Corrections become "reallocate" (move lines) instead of editing totals.
3. **Client credit as a first-class balance.** `unapplied_amount` per receipt gives a client credit total
   (`SUM(unapplied_amount)`). Show it on the client page and statement; auto-suggest it when a new invoice is raised.
   Optionally backfill legacy negative invoice balances (old overpayments) into credit.
4. **Bank ledger from the source.** Every cedi that reaches a bank should have one ledger line pointing at its
   source: deposit (cash/cheque), receipt (transfer), expense. Momo could get its own wallet "bank" the same way.
   Add a simple **bank reconciliation** screen (mark ledger lines as matched against the statement).
5. **Void instead of delete.** Mark receipts as void (with reason and user) and post reversal lines, so totals,
   collections and bank ledgers keep a full audit trail.
6. **Money columns to `decimal(15,2)`** across invoices, receipts, collections, banks.
7. **Collections = cash actually held.** Store `total_amount` as cash+momo+cheque+transfer only, and keep WHT/VAT
   as tax receivables in their own report.
8. **Numbering.** Keep `FWSSR`/`FWSSi` but generate them as stored, unique document numbers per year/office so
   reprints and deletions don't create gaps that look like missing receipts.

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
* A payment with no invoice at all (true advance) is recorded from *Pay Multiple Invoices* with no invoice ticked.
* Credit is used from the receipt page (**Apply credit**) against the client's later invoices. No new money moves.
* Both receipt forms warn when the client already holds credit.
* `advance_payment` is now **set automatically**: `advance` when part of the money is kept as credit, or when the
  receipt is dated before the month of an invoice it pays. The old tick box is gone.

---

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
4. Make sure every company account exists under *Accounts › Banks*.
5. Cheques/transfers received **before** this release are not in any bank balance unless they were deposited.
   Undeposited old cheques still appear on Bank Deposit as "Old cheque to deposit". Old transfers need a one-off
   manual bank adjustment if balances should include them.

**Testing note:** the suite (43 tests) passes on SQLite and on MariaDB 10.11. A *fresh* MySQL install of this project
fails on the existing `create_expenses_table` migration (`->after()` inside `Schema::create`, and a foreign key to a
table created later). Existing databases are past that migration, so it only matters for new installs/CI.

---

## 6. Still open / suggested next

* **Bounced cheques.** A cheque is now in the bank balance as soon as it is receipted. If it bounces, edit or delete
  the receipt (that posts the reversal). A "cheque bounced" action that reverses the bank line and reopens the
  invoice, keeping the receipt for the record, would be cleaner.
* **Receipts report timing buckets** (`App\Support\ReceiptReport`) still file a multi-invoice receipt under its first
  invoice's month. Per-invoice "received to date" is already split correctly.
* **Client statement** showing invoices, allocations and credit in one place; auto-suggest credit when an invoice is raised.
* **Void instead of delete**, with reason and user, so receipts keep a full audit trail.
* Widen the remaining money columns (invoices, receipts, collections) to `decimal(15,2)`.
* `collections.total_amount` includes WHT and VAT, so it overstates cash held.

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

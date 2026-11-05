# Approvals waiting for you

One place that tells each approver what is waiting for **them**, so branch managers and the
Finance Manager do not have to visit each pending page to find out.

It appears in three places, all built from `App\Support\Approvals\ApprovalInbox`:

| Where | What |
|---|---|
| **Bell in the top bar** (every page using `x-sales-dashboard` / `x-hr-dashboard`) | Total waiting, and a drop-down with a line per type and stage and how long the oldest item has waited. Hidden for users who approve nothing. |
| **"Waiting for your approval" strip** on the Accounts, HR and Operations dashboards | One tile per type and stage. **Not limited to the dashboard period**: an item waits until it is decided. |
| **`/approvals`** (`approvals.index`) | Every waiting item, oldest first, grouped by type and stage. Expenses, overtime, fuel and maintenance can be approved or rejected right there (the existing approve / reject routes, which return to this page). Receipts, clients and employees link to their pending pages, where they are approved in bulk. |

## Who sees what

The counts use the same rules as the approve buttons, so the badge never shows something the
user cannot act on.

| Type | Branch stage | Head-office stage |
|---|---|---|
| Expenses | Field Manager, offices in the same office group (`Field::groupId()`) | Finance Manager / Director |
| Overtime, fuel, maintenance | Field Manager or Manager, same office group | Finance Manager / Director |
| Receipts | Manager in Administration, clients of their office and sub-offices (`bran_status = pending`) | Finance Manager / Director (`ho_status = pending`) |
| Clients (new, terminated, re-instated) | Manager in Administration (`bran_status = pending`) | Invoice (`ho_status = pending`) |
| Employees (new recruit, termination, re-instatement) | Manager in Administration (`bran_status = pending`) | `EmployeeMovements::canDecide()` (Invoice, HR Manager) |

Finance can also give branch review on expenses, overtime, fuel and maintenance. To keep their
badge about *their* work:

* items from an office with **nobody who can give branch review** (head office, for example)
  are counted for Finance as "Branch review (no branch approver)";
* items from an office that has a branch approver are **not** in Finance's badge; they are listed
  at the bottom of `/approvals` under "At branch review", so Finance can step in when one has
  waited too long.

## Age

Each line shows how long the oldest item has waited: since it was submitted, or for the
Finance / H.O stage since branch review was given. 3 days or more is amber, 7 days or more is red
(`ApprovalInbox::LATE_DAYS` / `VERY_LATE_DAYS`).

## Performance and freshness

About two `COUNT` queries per source the user can act on (8 for a Finance Manager), cached for each
user for 60 seconds. Saving or deleting an expense, overtime, fuel, maintenance, receipt, client or
employee record clears every user's cache (`AppServiceProvider`), so a decision shows straight away.
`/approvals` always counts fresh. If counting fails, the bell and strip are left out and the
error is reported. The page around them still loads.

## Also fixed

`ClientController::clientPending()` ended its queries with `->orwhere('ho_status', null)` without
brackets, so every office's pending list also showed clients with no head-office status from
**all** offices. The condition is now grouped, which is what the new counts use too.

Tests: `tests/Feature/ApprovalInboxTest.php`.

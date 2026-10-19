# Dashboards: analysis, changes and definitions

Replaces the Accounts (`sales/dashboard`), HR (`hr/dashboard`) and Operations (`operations/dashboard`)
dashboards that `/home` showed. The System dashboard is unchanged.

---

## 1. What the old dashboards did

All three were built in `HomeController::index()`: about 70 separate queries for Accounts, one query per
office per figure, with office ids 1–6 (or 1–7) typed into the code.

| Dashboard | Showed | Problems |
|---|---|---|
| **Accounts** | All-time totals per office: invoices, "outstanding" (status `unpaid`), part-paid balances, receipts (`total`), "payments with withholding" (`amount_received`), WHT, cash / MoMo / cheque / transfer; bank balances; an "Invoices per year" chart | **No date filter** – every figure was all-time. **"Payments with withholding" summed `receipts.amount_received`**, which ReceiptController only fills when WHT is ticked, so every receipt without WHT was missing (see `ReceiptReport`). **Unapproved receipts counted as received.** Outstanding ignored allocations, client credit and age. **Shai Hills (field 7) and any new office were left out.** The yearly chart was the theme's demo chart – no data was passed to it. |
| **HR** | Guards per office, staff per department, a "Salaries per year" chart | **The Kumasi card showed Shai Hills' count** (`$kumasiGuardsCount` was never passed to the view). Counts included **terminated** employees. Department ids hard-coded. The salaries chart was the theme's demo chart. Nothing about joiners, leavers, turnover or pending approvals, although the movements module records all of it. |
| **Operations** | Guards per office, active / terminated | Nothing from guard attendance: no shift plans, shortages, coverage, absence, flagged sign-ins or overtime. Loaded every guard row into memory to count them. |

## 2. What replaces them

`/home` opens the dashboard for the user's department (Accounts / Administration → Accounts, HR → HR,
Operations → Operations). `/dashboard/{accounts|hr|operations}` opens any board the user may see; directors,
internal auditors and Administration managers get tabs to switch between all three.

Every board has a **month** filter (default: this month, shown as month-to-date) and an **office** filter
(an office includes its sub-offices: Tema includes Shai Hills). Office scope follows the existing reports:
head-office roles see every office; a field-office user sees their office and its sub-offices and gets a
403 for anything else.

Code: `app/Support/Dashboards/*` (one class per board + `DashboardContext` for month/scope),
`DashboardController`, views in `resources/views/dashboards/`. The side menus were moved unchanged into
`resources/views/dashboards/nav/`. Tests: `tests/Feature/DashboardsTest.php`.

Every figure reuses the rule of the report it summarises, so the dashboard and the report always agree,
and each tile links to that report.

### Accounts

| Figure | Definition | Same as |
|---|---|---|
| Invoiced | `invoices.total` with `invoice_month` in the month | Invoice report |
| Collected | Cash + MoMo + cheque + transfer + other on **head-office approved** receipts dated in the month (`receipt_month`, else entry date) | `ReceiptReport` |
| Collection rate | Collected ÷ invoiced for the month. Over 100% means older invoices were cleared. | – |
| Receivables | Invoice total − everything allocated to it, less unused client credit, **as of today** | `ReceivablesAging` / aged receivables / statements |
| Debtor days | Receivables ÷ average daily billing over the last 3 months | – |
| Over 90 days | Owed on invoices more than 90 days past due | Aged receivables |
| Expenses | Expenses approved at head office (stage 3), by entry date; "awaiting approval" = not yet decided | Expense list |
| Payroll | Salaries of the month with status pending/approved; paid = approved; held = hold/rejected | Salaries report |
| Office contribution | Service revenue (`invoices.sub_amount`, before levies & VAT) − payroll cost to company − approved expenses. A management indicator, not profit: head-office overheads are not allocated. | – |

Also: 12-month invoiced / collected / expenses trend, receivables by age, largest balances (link to each
client's aged account), how clients paid, WHT/VAT withheld, expenses by type, receipts and expenses
waiting for approval, and bank balances (Finance Manager and Director only, as before).

### HR

| Figure | Definition | Same as |
|---|---|---|
| Headcount, joined, left, transfers | Approved, dated movements; start + joined + transfers in − left − transfers out = end, per office and in total | `EmployeeMovementsReport` (Employees report) |
| Turnover | Terminations ÷ average headcount | Employees report |
| Why people left / how long they stayed | Last 12 months of terminations (one month is too few to read) | Employees report |
| Awaiting approval | Movement requests waiting for head office, oldest first | Movements page |
| Complete records | Active employees with date of joining, phone, Ghana Card number, payment details, guarantor and date of birth all filled | – |
| Payroll check | Employed during the month vs salaries raised (both ways) | Employees report |

Also: 12-month headcount and joiners/leavers, workforce today (guards, unposted guards, staff, gender),
and the month's net pay, absence deductions and overtime for payroll roles.

### Operations

| Figure | Definition | Same as |
|---|---|---|
| Post coverage | 1 − guard-shifts not covered ÷ guard-shifts required, from approved sign-ins against each client's shift plan, up to today; clients without a plan are left out | `MonthOverview` (Attendance) |
| Shortage | Guards the plans need that are not posted, per shift (rotating guards fill either) | `Roster::shortage` |
| Active / unposted guards | Role 7, status Active or Re-Instate; unposted = no client | Roster |
| Absence rate | Absent ÷ (present + absent) guard-days | Attendance |
| Sign-ins to review | Attendance rows held for review (not counted until approved) | Attendance review |
| Overtime | Overtime entries in the month, count and amount, by reason | Overtime report |

Also: guards present each day vs required, the office table, the clients with the biggest shortages
(link to each client's attendance sheet), set-up gaps (no shift plan, no QR poster, no site location),
and guards on record per office.

## 3. Deploying

No migrations. Clear compiled views after deploying: `php artisan view:clear`.
The old views (`sales/dashboard`, `hr/dashboard`, `operations/dashboard`) are no longer rendered and can be
deleted once the new dashboards are accepted.

Speed: Accounts went from ~70 queries to ~23, Operations from ~25 (loading every guard) to ~15; HR runs
the movements replay twice (month and 12 months), ~55 small queries.

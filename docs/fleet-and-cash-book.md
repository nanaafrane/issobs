# Fleet (fuel & maintenance) and the analysed cash book

---

## 1. What was there before

* **Fuel** was a separate Excel sheet (*FUEL REQUEST AND USAGE REPORT*: date, driver, vehicle no., field, trip,
  quantity, units, fuel type, requested, issued, station, approved by, granted by). Nothing tied a fill-up to
  the money paid for it, and Main Cash only showed lump sums ("Purchase of Kumasi fuel", "Fuel budget for all branches").
* **Maintenance** existed only as Main Cash lines ("Payment for Tema Landcruiser repairs", "Purchase of vehicle
  tyres", "Ashaley Botwe car battery for Isuzu"). Which vehicle, what was done, and when roadworthy or insurance
  expires were nowhere.
* **Main Cash analysis** was done by hand: each payment was typed again under one of ~90 columns, several of them the
  same item for a different office (ACCRA / KUMASI / TEMA … VEH. REP & MAINT.). In the October sheet:
  * the item columns add up to **GH₵ 280,939.37** but the PAYMENT column to **GH₵ 277,994.37** — "Cynthia MoMo"
    (GH₵ 8,000 over three lines) is analysed but never entered as a payment, so the balance never moved;
  * a GH₵ 5,055 payment on 16 Oct has no description and no item.

## 2. The approach

| | Fuel | Maintenance |
|---|---|---|
| One record is | a fill-up (many per vehicle per week) | a job or renewal (one bill) |
| Approval | Submitted → Branch Review → Finance / H.O (same rules as overtime) | same |
| When approved | **owed**, no expense yet | **one approved expense** under the type's expense item |
| Paid by | a **fuel pay-out**: one office + period (optionally one station's bill) → **one expense (Fuel) + one payment** | *Payments › Pay approved expenses*, like any other bill |
| Locked when | it is on a pay-out (cancel the pay-out to change it) | its expense is paid (cancel the payment to change it) |

Fuel follows the **overtime** road because of volume: one expense per fill-up would fill *Approved expenses waiting
to be paid* with dozens of lines a week (the exact problem overtime pay-outs fixed). Maintenance is one bill per job,
so one expense per job is the natural fit — a pay-out would add a step for nothing.

Both share a **vehicle register** (reg. no. normalised: `gr 1788 21` = `GR-1788-21`), so every cedi is tied to a
vehicle and an office.

### Maintenance types (user-defined)

*Fleet › Maintenance types* — Finance and office managers add / edit / retire types. Each type has:

* **expense item** — the Main Cash column it is paid under (seeded: Roadworthy and Vehicle registration → *License &
  Registration*, Insurance → *Insurance*, Tyres / Battery / Brakes / Repairs → *Vehicle Repairs & Maintenance*,
  Servicing → *Lubricant*, Body work → *Accident Car*, Motorbike repairs → *Motor Repairs*);
* **has expiry** (certificate / policy) — the record needs a *valid until* date;
* **repeat every N months / N km** — *valid until* and *next due km* are filled in from it.

*Fleet › Vehicles* and *Maintenance* list what is **overdue / expired**, **due within 30 days (or 500 km)**, and
**missing** (an active vehicle with no roadworthy or insurance on record).

### The cash book (Main Cash statement by item)

*Accounts › Cash book (by item)* (also a button on every account statement). For one account and a month — or any
dates, or **all accounts** — it shows the Main Cash sheet built from the ledger:

* DATE | Payment details | CHEQ NO | PAYMENT | RECEIPT | BALANCE, then the **item(s)** of each line;
* **Total spent per item** and a **per item × office** table (replaces ACCRA / KUMASI / TEMA … columns);
* **Excel (Main Cash layout)**: the same columns as the uploaded sheet, one column per item used that month, TOTAL
  row, then the item × office summary.

How a line is analysed: a payment of approved expenses → each expense's **type and office** (a payment covering
several expenses is split between them); any other payment → its payment category (Salaries & wages, Bank charges…);
cash banked / moved → *Bank deposit / transfer out*; creditor repayments → *Loan repayment*. Money returned on a
payment, or a cancelled payment, comes off the same item. **Every cedi paid out is on an item**, so the item totals
always equal the PAYMENT column.

To get the most from it, pay through **approved expenses** with the right expense type (fuel and maintenance
already do this), rather than as "Other" payments.

### Fleet reports

*Fleet › Monthly cost*: per vehicle — fill-ups, litres, fuel cost, maintenance by type, total, km driven, **km per
litre** (full-tank method on the km readings at the pump) and **cost per km**. Dated by when the fuel was drawn /
the work done; the cash book shows when it was paid. The vehicle page shows the same for any dates, with its history
and due list.

## 3. Who does what

| | Office staff | Field Manager / Manager (same office group) | Finance (Invoice, Finance Manager, Director) |
|---|---|---|---|
| Vehicles | add / edit their office's | ✓ | all offices |
| Fuel / maintenance entries | enter; edit until branch review | review (stage 2) | approve (stage 3), edit until paid |
| Maintenance types | — | add / edit | add / edit |
| Fuel pay-outs | see their office's | see | raise; Finance Manager / Director cancel |
| Cash book | — | — | ✓ |

Offices see only their own office and sub-offices (Tema sees Tema and Shai Hills), as for overtime pay-outs.

## 4. After deploying

1. `php artisan migrate` — creates `vehicles`, `fuel_entries`, `fuel_payouts`, `maintenance_types` (with the common
   types), `vehicle_maintenances`. Re-runnable.
2. Load the vehicles, or import a fuel sheet — vehicles are added as they appear:
   ```
   php artisan fleet:import-fuel "FUEL REQUEST OCT.xlsx" --dry-run     # check first
   php artisan fleet:import-fuel "FUEL REQUEST OCT.xlsx" --approved    # a sheet already signed off
   ```
   `FIELD` is matched to the office (*ASHALEY BOTWE* → Botwe); `--field=ID` forces one office. Rows already loaded
   are skipped. Imported fuel is **not** marked paid: if Main Cash already paid it, do not put it on a pay-out.
3. Complete each vehicle (make / model, driver, km reading at start) on *Fleet › Vehicles*, and record the current
   roadworthy and insurance of each so expiry tracking starts.
4. Ask drivers to give the **km reading** at every fill-up — it is what makes km per litre and cost per km work.

## Suggested next

* Attach the pump receipt / invoice photo to fuel and maintenance entries.
* A monthly **fuel budget per office / vehicle** with the variance on the report (the Main Cash sheet's "fuel budget
  for all branches" line).
* Fuel **coupons / station credit account**: the station as a creditor whose bill is settled by the fuel pay-out.
* Email the due list (roadworthy / insurance expiring within 30 days) to each office every Monday.

## Code map

| File | Purpose |
|---|---|
| `database/migrations/2026_10_23_090000_create_fleet_tables.php` | Tables + seeded maintenance types |
| `app/Models/Vehicle.php`, `FuelEntry.php`, `FuelPayout.php`, `MaintenanceType.php`, `VehicleMaintenance.php` | Fleet models |
| `app/Models/Concerns/HasApprovalStages.php` | The 3-stage approval shared with overtime's rules |
| `app/Services/Fleet/FuelEntries.php` | Fill-up rules: litres ⇄ price, defaults from the vehicle, km never going backwards |
| `app/Services/Fleet/FuelPayouts.php` | Owed fuel, pay-out = one expense (Fuel) + one payment (category Fuel), cancel |
| `app/Services/Fleet/MaintenanceExpenses.php` | Approved job ⇄ its one expense (follows edits, removed on reject / delete, locked when paid) |
| `app/Services/Fleet/FleetReports.php` | Cost per vehicle, km/L, cost per km; due / expired list |
| `app/Services/Fleet/FuelSheetImport.php`, `routes/console.php` | `fleet:import-fuel` |
| `app/Services/Banking/CashBook.php`, `app/Exports/CashBookExport.php`, `CashBookController.php` | Analysed cash book and its Excel |
| `app/Http/Controllers/VehicleController.php`, `FuelController.php`, `FuelPayoutController.php`, `MaintenanceController.php`, `resources/views/fleet/*` | Screens |
| `tests/Feature/FleetTest.php` | Fuel, pay-outs, maintenance → expense, expiry, scope, report, cash book, import |

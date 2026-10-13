# Guard attendance: analysis, design and how to run it

Replaces the monthly Excel time sheet (`2026 MONTHLY TIMESHEET.xlsx`, one tab per month).

---

## 1. What the Excel sheet does today

| Excel | Meaning | In ISSOBS now |
|---|---|---|
| One block per client (e.g. *LANDS COMMISSION – REGIONAL*), rows per guard | guards posted to that client | guards whose `employees.client_id` is the client |
| `EMPLOYEE NUMBER` (FWSS6600), often blank | employee id | always `FWSS {id}` |
| `SHIFT`: DAY / NIGHT / ROTATE / blank (and one typo, "PAUL") | guard's shift | `client_employee.shift`: day / night / rotate |
| Day cells `1` / `0` / `-` / blank | present / absent / not yet posted / not filled | **P** / **A** / blank / blank |
| Legend: OFF DAY = 1, SICK = 1, PASS = 1 | paid days that are not worked | **OFF**, **S**, **PS** count as paid days |
| `PRESENT = SUM(days)`, `ABSENT = 31 − PRESENT` | totals | Worked, Paid, Absent, Blank |
| `TOTAL SHORTAGES = N` per client | guards needed − guards posted | worked out from the client's **shift plan** |

Problems with the sheet that the new code fixes:

* **Absent was `31 − present`**, so a new recruit's days before joining (`-` or blank) counted as absent, as did
  every blank in a half-filled month. Now only a day marked **A** is absent; unfilled days show as *Blank*.
* **Shortages were typed by hand** and the number of guards a client needs was not recorded anywhere. Now each
  client has a shift plan (day guards, night guards, shift times), and the shortage is worked out per shift:
  3 day guards do not cover a missing night guard. Rotating guards fill whichever shift is short.
* Nothing showed whether a guard was actually at the post. Now guards sign in at the site.

---

## 2. Shift plan per client

* **Client › New contract / Edit**: the old free-text *Guards* box is replaced by *Day guards*, *Night guards* and the
  shift times. Put 0 for a shift the client doesn't need, so *Day only*, *Night only* and *Day & Night* all work.
  `clients.guards` is still kept equal to day + night for the screens that show it.
* **Editing takes effect from a month** (*Plan applies from*). Each change is a new row in `client_shift_plans`, so
  September's shortages stay September's when the client adds a night post in October. The attendance page shows
  the history.
* Saving the edit form without changing the numbers does not create a new plan.

---

## 3. How guards sign in: options considered

| Option | Problem |
|---|---|
| A barcode at the site, scanned with no identity | proves the poster was scanned, not who scanned it |
| A guard login (username + password) | guards share phones and forget passwords; still doesn't prove the guard is at the site; a photo of the barcode works from home |
| **QR poster + FWSS number + PIN + GPS + phone check** (built) | covers who, where and which phone, and needs no account |
| **Supervisor kiosk** (built) | for guards without a smartphone, or with a dead phone |

### 3a. QR poster (guards with a phone)

1. Operations print the **QR poster** for the client (*Attendance › client › QR poster*) and fix it at the post.
   The QR holds a 32-character secret link. **Replace QR code** makes a new one and the old poster stops working at once.
2. Operations **issue each guard a 4-digit PIN** (guard row ⋮ › *Issue PIN*). It is shown once and stored hashed.
3. The guard scans the poster with any phone camera, enters **FWSS number + PIN**, allows location, presses *Sign in*.
   No app and no login. The phone remembers the FWSS number (never the PIN).

Each sign-in is checked:

| Check | If it fails |
|---|---|
| PIN | refused; 5 wrong PINs lock that FWSS number for 15 minutes |
| Guard posted to this client | held for review |
| Time fits the guard's own shift (they may sign in up to 2 h early) | held for review |
| Phone within the site radius (+ the phone's own GPS accuracy, up to 100 m) | held for review |
| Location shared at all | held for review |
| Same phone as the guard's first sign-in | held for review (*Trust this phone* on approval re-registers it) |
| Phone not already used by another guard this shift / not another guard's phone | held for review |

A held sign-in is saved as **?** (pending). It doesn't count until operations **approve** it (then it's present) or
**reject** it (then it's absent) on *Review sign-ins*. The guard is never simply turned away. Late arrivals (more than
60 minutes after the shift starts) are noted but not held.

**Night shifts** belong to the date they start: with an 18:00–06:00 night, a 02:00 sign-in on the 5th counts for the night of the 4th.

### 3b. Supervisor kiosk (guards without a phone)

A supervisor logged in to ISSOBS opens *Supervisor kiosk* on their own phone at the site. It shows the current shift
and the guards posted there. The supervisor taps **P** / **A**, types an FWSS number, or **scans the guard's ID card**
(*Print guard ID cards*: credit-card size, QR `ISSOBS-GUARD:{id}`).

* The supervisor's login shows who marked it, and the supervisor's GPS is checked against the site the same way.
  A *present* marked away from the site, or with no location, is held for review.
* Only the current shift (today or last night) can be marked here; other days go on the monthly sheet.
* If a guard's own scan was held for review, a supervisor confirming them in person at the site clears it.

### 3c. Operations by hand

The monthly sheet (*Attendance › client*) is laid out like the Excel sheet. Click a day to set P / A / OFF / Sick / Pass or
clear it, for the day or night shift. *Fill blanks as present* marks every unmarked day so far as present for guards with a
Day or Night shift, which is the old "type 1s then change the 0s" routine. Rotating guards are skipped. **Excel** exports the
month in the old sheet's layout.

---

## 4. Screens

| Where | What |
|---|---|
| Side menu › **Guard Attendance** | every client you can see (your field; head office sees all): plan, posted by shift, shortage, worked, absent, *uncovered guard-shifts* (shift slots in the plan nobody was present for, so far this month) |
| Attendance › client | monthly grid, per-day cover against the plan, shift plan + history, site location (*Use my location* while standing at the post), QR poster, kiosk, ID cards, PIN / phone actions per guard |
| Attendance › Review sign-ins | held sign-ins with the reasons and a map link; approve / reject / trust phone |
| `/attend/{token}` | the page the QR opens (public, no login) |

Access follows the client list: Invoice / Finance Manager / Director and HR Manager see every client. Everyone else sees the
active, approved clients of their own field (Tema also sees field 7).

---

## 5. Data

| Table | Purpose |
|---|---|
| `client_shift_plans` | client, `effective_month`, day/night guards, shift times, note, who |
| `attendances` | client, employee, `work_date`, `shift` (day/night), `status` (present/absent/off/sick/pass/pending), `source` (scan/kiosk/manual), check-in time, GPS, distance, device, `flags` (reasons held), who marked / reviewed. Unique per employee + date + shift, so a double shift is two rows |
| `clients` + | `attendance_token`, `latitude`, `longitude`, `attendance_radius_m` (default 200 m) |
| `client_employee` + | `shift` |
| `employees` + | `attendance_pin` (hashed), `attendance_device` |

The guard's posting is `employees.client_id`, the field *Re-assign guard* changes. `client_employee` rows are not removed on
re-assignment, so they are only used for the shift.

---

## 6. Install

```bash
git apply guard-attendance.patch      # or: git am 0001-*.patch
php artisan migrate
php artisan view:clear
```

Then, per client:

1. Set the **shift plan** (edit the client, or on the attendance page).
2. Set each guard's **shift** in the grid.
3. Stand at the post with a phone: **Use my location**, then **Save site**.
4. **Print the QR poster** and put it up. **Issue PINs** to guards. Print **ID cards** for guards without phones.

Notes:

* The app timezone is UTC, which is Ghana time, so shift times need no conversion.
* The QR poster, ID cards and kiosk camera load small libraries from cdnjs (`qrcodejs 1.0.0`, `html5-qrcode 2.3.8`).
  The kiosk camera needs HTTPS, which production already has.
* `clients.phone_number1` exists in production but no migration creates it. The new client-form test adds it on SQLite.

Tests: `php artisan test --filter=GuardAttendanceTest` (24 tests).

---

## 7. Absent deduction in payroll

`salaries.absent` is filled from the attendance sheet.

**Absent deduction = days marked Absent in the salary month × daily rate**, never more than the pay it is worked out from.

| Setting (Payroll › Absent deductions, Finance Manager) | Options | Default |
|---|---|---|
| Pay a day is worked out from | Basic salary · Basic + allowances | Basic salary |
| Divided by | Days in the month (28–31) · 30 · 26 | Days in the month: guards work every day, and the old sheet counted all of them |
| Fill automatically when salaries are added | on / off | on |

Example: basic GH₵1,550 in October (31 days) gives GH₵50 a day, so 3 absent days come to GH₵150.

* **When a month's salaries are added** (*Add to Salaries*), each guard with absent days gets `absent` filled in, and the
  success message says how many.
* **Payroll › Absent deductions** lists, for a month, every salary of an employee with attendance that month. It shows the
  absent days, the daily rate, the amount on the salary now, the amount from attendance and the change to net pay. Tick and
  **Apply** to bring salaries in line after late attendance changes (approved or rejected sign-ins, a supervisor fixing the sheet).
  If the salary's totals are already worked out, `total_deductions`, `net_salary` and `cost_to_company` move by the same
  difference. Absent is a plain deduction, so tax and SSNIT are unchanged.
* **What is left alone:**
  * Employees with no attendance in that month: office staff, and guards at clients not yet on attendance keep the amount typed in.
  * Paid (*approved*) salaries.
  * Off, Sick and Pass days (paid), blank days, and sign-ins still awaiting review (**?**, shown as a warning; review them first).
* Every change is logged in `absent_deduction_changes`: days, rate, old and new amount, who, and whether it happened when salaries
  were added or on the Apply page. The page shows the month's history.
* **Salary Excel upload:** a value in the upload's `absent` column still replaces the amount. Leave the column empty to keep
  the attendance amount. (The upload now uses the saved amount in its totals; before, an empty cell counted as 0 in the totals
  while the old amount stayed on the salary.)

Tests: `php artisan test --filter=AbsentDeductionTest` (7 tests).

---

## 8. Next steps (not in this patch)

* **Invoicing**: *uncovered guard-shifts* per client is the quantity for an `ABSENT DEDUCTION` line on the client's invoice.
* **Import** Jan–May from the Excel file if history is wanted in ISSOBS.

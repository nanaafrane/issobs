<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\employee;
use App\Models\Invoice;
use App\Models\Overtime;
use App\Models\User;
use App\Services\Receipts\ClientLedger;
use App\Services\Receipts\ReceiptRecorder;
use App\Services\Receipts\ReceiptVoider;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Test data for "overtime paid with the salary", November 2026.
 *
 *   php artisan testdata:november-overtime            add it
 *   php artisan testdata:november-overtime --remove   take it all out again
 *   php artisan testdata:november-overtime --fresh    remove, then add again
 *
 * Everything it makes is named "TEST-NOV ..." (4 clients, 12 guards), so it is easy to
 * find and to remove. Then: Payroll > Add to Salaries, November 2026, office Accra and
 * Tema, tick the TEST-NOV guards (or all), and compare with the table printed at the end.
 *
 *   Client                       Nov invoice           Receipt          Guards' salaries
 *   TEST-NOV Accra Mall          GH₵ 7,500             paid in full     pending
 *   TEST-NOV Ridge Hospital      GH₵ 7,500             part (3,000)     pending
 *   TEST-NOV Tema Port           GH₵ 5,000             none             pending
 *   TEST-NOV Spintex Office      none                  -                rejected (no invoice)
 */
class NovemberOvertimeTestSeeder extends Seeder
{
    public const MARK = 'TEST-NOV';

    private User $user;
    private array $g = [];   // key => employee
    private array $c = [];   // key => client
    private array $expect = [];

    public function run(): void
    {
        if (Client::where('name', 'like', self::MARK . '%')->exists()) {
            $this->command?->warn('TEST-NOV data is already there. Use: php artisan testdata:november-overtime --fresh');

            return;
        }
        $this->user = User::where('role_id', 2)->first() ?? User::where('role_id', 1)->first() ?? User::firstOrFail();
        Auth::login($this->user);

        DB::transaction(function () {
            $this->clients();
            $this->guards();
            $this->invoicesAndReceipts();
            $this->attendance();
            $this->overtime();
        });

        $this->summary();
    }

    // ------------------------------------------------------------------ clients, guards

    private function clients(): void
    {
        foreach ([
            'mall' => ['Accra Mall', 1],
            'ridge' => ['Ridge Hospital', 1],
            'port' => ['Tema Port', 3],
            'spintex' => ['Spintex Office', 1],
        ] as $key => [$name, $field]) {
            $client = new Client();
            $client->forceFill([
                'name' => self::MARK . ' ' . $name, 'business_name' => self::MARK . ' ' . $name,
                'phone_number' => '0240000000', 'address' => $name . ', test data', 'field_id' => $field,
                'status' => 'active', 'ho_status' => 'approved', 'user_id' => $this->user->id,
            ])->save();
            $this->c[$key] = $client;
        }
    }

    private function guards(): void
    {
        // key => [name, client, basic, tax on]
        foreach ([
            'kofi' => ['Kofi Replacement', 'mall', 1500, false],
            'ama' => ['Ama OffDay', 'mall', 1400, false],
            'yaw' => ['Yaw Carried', 'mall', 1300, false],
            'esi' => ['Esi Absent', 'mall', 1300, false],
            'kwame' => ['Kwame Shortage', 'ridge', 1600, true],
            'akosua' => ['Akosua Cash', 'ridge', 1400, false],
            'kojo' => ['Kojo InReview', 'port', 1350, false],
            'abena' => ['Abena Rejected', 'port', 1350, false],
            'kwesi' => ['Kwesi CashShortage', 'port', 1350, false],
            'efua' => ['Efua Absent', 'port', 1350, false],
            'fiifi' => ['Fiifi NoInvoice', 'spintex', 1250, false],
            'adjoa' => ['Adjoa NoOvertime', 'spintex', 1250, false],
        ] as $key => [$name, $client, $basic, $tax]) {
            $e = new employee();
            $e->forceFill([
                'name' => strtoupper(self::MARK . ' ' . $name), 'gender' => 'Male', 'phone_number' => '0550000000',
                'status' => 'Active', 'ho_status' => 'approved', 'department_id' => 1, 'role_id' => 7,
                'field_id' => $this->c[$client]->field_id, 'client_id' => $this->c[$client]->id, 'location' => 'Main gate',
                'basic_salary' => $basic, 'allowances' => 0, 'payment_type' => 'Cash',
                'tax_button' => $tax ? 'on' : null, 'date_of_joining' => '2025-01-01', 'user_id' => $this->user->id,
            ])->save();
            DB::table('payment_infos')->insert(['employee_id' => $e->id, 'user_id' => $this->user->id, 'created_at' => now(), 'updated_at' => now()]);
            $this->g[$key] = $e;
        }
    }

    // ------------------------------------------------------------------ invoices, receipts

    private function invoicesAndReceipts(): void
    {
        $invoices = [];
        foreach (['mall' => [3, 2500], 'ridge' => [3, 2500], 'port' => [2, 2500]] as $key => [$guards, $rate]) {
            $client = $this->c[$key];
            $total = $guards * $rate;
            $inv = new Invoice();
            $inv->forceFill([
                'client_id' => $client->id, 'invoice_month' => '2026-11-01', 'due_date' => '2026-11-30',
                'status' => 'unpaid', 'sub_amount' => $total, 'total' => $total, 'user_id' => $this->user->id,
            ])->save();
            DB::table('invoice_data')->insert([
                'invoice_id' => $inv->id, 'service_name' => 'Security Guard Services',
                'counts_as_guards' => Schema::hasColumn('invoice_data', 'counts_as_guards') ? 1 : null,
                'description' => 'Guards for November 2026 (test data)', 'quantity' => $guards, 'unit_price' => $rate, 'amount' => $total,
            ]);
            DB::table('transactions')->insert([
                'client_id' => $client->id, 'invoice_id' => $inv->id, 'invoice_amount' => $total, 'status' => 'unpaid',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            app(ClientLedger::class)->sync($client->id);
            $invoices[$key] = $inv;
        }

        // Accra Mall pays in full, Ridge Hospital pays part, Tema Port nothing.
        foreach (['mall' => 7500, 'ridge' => 3000] as $key => $amount) {
            app(ReceiptRecorder::class)->create($this->c[$key], [
                'mode' => ['cash'], 'cash_amount' => $amount, 'from' => 'Client accounts office',
                'receipt_month' => '2026-11', 'description' => 'November 2026 (test data)',
                'keep_credit' => false, 'confirm_duplicate' => true,
                'invoices' => [$invoices[$key]->id => ['applied' => $amount, 'wht' => 0, 'vat7' => 0, 'deduction' => 0]],
            ]);
        }
    }

    // ------------------------------------------------------------------ attendance

    private function attendance(): void
    {
        $mark = fn ($key, $client, $date, $shift, $status) => Attendance::create([
            'client_id' => $this->c[$client]->id, 'employee_id' => $this->g[$key]->id, 'work_date' => $date,
            'shift' => $shift, 'status' => $status, 'source' => 'manual', 'marked_by' => $this->user->id,
        ]);
        // Ama: off on Monday 9th, then also off (and working) on Saturday 14th -> warning.
        $mark('ama', 'mall', '2026-11-09', 'day', Attendance::OFF);
        $mark('ama', 'mall', '2026-11-14', 'day', Attendance::OFF);
        // Ama again: off on Sunday 22nd only -> no warning when she works it.
        $mark('ama', 'mall', '2026-11-22', 'day', Attendance::OFF);
        // Kwame worked his own night shift on the 11th, then the short day post on the 12th.
        $mark('kwame', 'ridge', '2026-11-11', 'night', Attendance::PRESENT);
    }

    // ------------------------------------------------------------------ overtime

    private function entry(array $a, string $state = 'approved'): Overtime
    {
        $client = $this->c[$a['client']];
        unset($a['client']);
        $o = Overtime::create($a + [
            'field_id' => $client->field_id, 'client_id' => $client->id, 'amount' => 30,
            'user_1' => $this->user->id, 'status_1' => 'approved', 'date_1' => now(),
            'notes' => 'Test data (TEST-NOV)',
        ]);
        if ($state === 'approved' || $state === 'review' || $state === 'rejected') {
            $o->update(['user_2' => $this->user->id, 'status_2' => $state === 'rejected' ? 'rejected' : 'approved', 'date_2' => now()]);
        }
        if ($state === 'approved') {
            $o->update(['user_3' => $this->user->id, 'status_3' => 'approved', 'date_3' => now()]);
        }

        return $o;
    }

    private function overtime(): void
    {
        $g = fn ($k) => $this->g[$k]->id;

        // 1. Replacements, with salary, approved: + 60 on Kofi's salary.
        $this->entry(['entry_date' => '2026-11-03', 'shift' => 'day', 'type' => 'replacement', 'client' => 'mall',
            'absent_employee_id' => $g('esi'), 'reason' => 'Absent', 'ot_employee_id' => $g('kofi'), 'pay_by' => 'salary']);
        $this->entry(['entry_date' => '2026-11-17', 'shift' => 'night', 'type' => 'replacement', 'client' => 'mall',
            'absent_employee_id' => $g('esi'), 'reason' => 'Sick', 'ot_employee_id' => $g('kofi'), 'pay_by' => 'salary']);
        $this->expect['kofi'] = [60, '2 replacements, with salary'];

        // 2. Off-day work, with salary: 14th (warning: already off on the 9th) and 22nd (fine). + 70.
        $this->entry(['entry_date' => '2026-11-14', 'shift' => 'day', 'type' => 'off_day', 'client' => 'mall',
            'absent_employee_note' => 'OFF-DAY WORK', 'reason' => 'Off-day work', 'ot_employee_id' => $g('ama'), 'pay_by' => 'salary', 'amount' => 35]);
        $this->entry(['entry_date' => '2026-11-22', 'shift' => 'day', 'type' => 'off_day', 'client' => 'mall',
            'absent_employee_note' => 'OFF-DAY WORK', 'reason' => 'Off-day work', 'ot_employee_id' => $g('ama'), 'pay_by' => 'salary', 'amount' => 35]);
        $this->expect['ama'] = [70, '2 off days, with salary (14th has a warning)'];

        // 3. October entry, with salary, never paid: carried into November. + 30.
        $this->entry(['entry_date' => '2026-10-28', 'shift' => 'night', 'type' => 'replacement', 'client' => 'mall',
            'absent_employee_id' => $g('esi'), 'reason' => 'Absent', 'ot_employee_id' => $g('yaw'), 'pay_by' => 'salary']);
        $this->expect['yaw'] = [30, 'October entry carried into November ("from October")'];

        // 4. Shortage (double duty), with salary: 12th after his own night (fine); 19th, no own shift (warning). + 60, tax on.
        $this->entry(['entry_date' => '2026-11-12', 'shift' => 'day', 'type' => 'shortage', 'client' => 'ridge',
            'absent_employee_note' => 'SHORTAGE', 'reason' => 'Shortage', 'ot_employee_id' => $g('kwame'), 'pay_by' => 'salary']);
        $this->entry(['entry_date' => '2026-11-19', 'shift' => 'day', 'type' => 'shortage', 'client' => 'ridge',
            'absent_employee_note' => 'SHORTAGE', 'reason' => 'Shortage', 'ot_employee_id' => $g('kwame'), 'pay_by' => 'salary']);
        $this->expect['kwame'] = [60, '2 shortages, with salary; tax on (on basic) (19th has a warning)'];

        // 5. Cash today, approved: owed as cash, NOT on the salary.
        $this->entry(['entry_date' => '2026-11-06', 'shift' => 'night', 'type' => 'replacement', 'client' => 'ridge',
            'absent_employee_id' => $g('kwame'), 'reason' => 'Pass', 'ot_employee_id' => $g('akosua'), 'pay_by' => 'cash']);
        $this->expect['akosua'] = [0, 'cash today: owed on Overtime pay-outs, not on the salary'];

        // 6. With salary but still in review: not on the salary yet.
        $this->entry(['entry_date' => '2026-11-10', 'shift' => 'day', 'type' => 'replacement', 'client' => 'port',
            'absent_employee_id' => $g('efua'), 'reason' => 'Absent', 'ot_employee_id' => $g('kojo'), 'pay_by' => 'salary'], 'review');
        $this->expect['kojo'] = [0, 'in review (Finance approval pending): approve it after generating -> waits for December, or send to payroll now'];

        // 7. With salary, rejected at branch: never paid.
        $this->entry(['entry_date' => '2026-11-11', 'shift' => 'night', 'type' => 'replacement', 'client' => 'port',
            'absent_employee_id' => $g('efua'), 'reason' => 'Sick', 'ot_employee_id' => $g('abena'), 'pay_by' => 'salary'], 'rejected');
        $this->expect['abena'] = [0, 'rejected: never paid'];

        // 8. Shortage paid in cash as an exception (flagged): not on the salary.
        $this->entry(['entry_date' => '2026-11-15', 'shift' => 'night', 'type' => 'shortage', 'client' => 'port',
            'absent_employee_note' => 'SHORTAGE', 'reason' => 'Shortage', 'ot_employee_id' => $g('kwesi'), 'pay_by' => 'cash',
            'pay_note' => 'Guard needed transport money the next morning']);
        $this->expect['kwesi'] = [0, 'shortage in cash (exception, flagged): owed as cash'];

        // 9. Client has no November invoice: the salary is "rejected" but still unpaid, so overtime goes on it. + 30.
        $this->entry(['entry_date' => '2026-11-08', 'shift' => 'day', 'type' => 'other', 'client' => 'spintex',
            'absent_employee_note' => 'EXTRA POST', 'reason' => 'Extra Post', 'ot_employee_id' => $g('fiifi'), 'pay_by' => 'salary']);
        $this->expect['fiifi'] = [30, 'client not invoiced: salary rejected, overtime still added'];

        $this->expect['esi'] = [0, 'absent on the 3rd and 17th (covered by Kofi): absent deduction from attendance'];
        $this->expect['efua'] = [0, 'covers still in review / rejected: no absent deduction yet'];
        $this->expect['adjoa'] = [0, 'no overtime: salary = basic'];
    }

    // ------------------------------------------------------------------ output

    private function summary(): void
    {
        $rows = [];
        foreach ($this->g as $key => $e) {
            [$ot, $why] = $this->expect[$key] ?? [0, ''];
            $client = Client::find($e->client_id);
            $rows[] = ['FWSS ' . $e->id, $e->name, str_replace(self::MARK . ' ', '', $client->name), number_format((float) $e->basic_salary, 2), number_format($ot, 2), $why];
        }
        $this->command?->info('TEST-NOV data added: 4 clients, 12 guards, 3 November invoices, 2 receipts, ' . Overtime::where('notes', 'Test data (TEST-NOV)')->count() . ' overtime entries.');
        $this->command?->table(['Guard', 'Name', 'Client', 'Basic', 'Expected overtime on Nov salary', 'Case'], $rows);
        $this->command?->line('Next: Payroll > Add to Salaries > November 2026 > tick the TEST-NOV guards > Add. Expect "Overtime with salary added for 5 guard(s): 8 entries, GH₵ 250.00."');
    }

    // ------------------------------------------------------------------ remove

    /** Take every TEST-NOV row out again (receipts are voided, so the books stay right). */
    public static function remove(?\Illuminate\Console\Command $command = null): void
    {
        $clientIds = Client::where('name', 'like', self::MARK . '%')->pluck('id')->all();
        $employeeIds = employee::where('name', 'like', self::MARK . '%')->pluck('id')->all();
        if (! $clientIds && ! $employeeIds) {
            $command?->info('No TEST-NOV data to remove.');

            return;
        }
        $user = User::where('role_id', 2)->first() ?? User::where('role_id', 1)->first() ?? User::firstOrFail();
        Auth::login($user);

        $overtimeIds = Overtime::withTrashed()->whereIn('ot_employee_id', $employeeIds ?: [0])->orWhereIn('client_id', $clientIds ?: [0])->pluck('id')->all();
        $payoutIds = DB::table('overtimes')->whereIn('id', $overtimeIds ?: [0])->whereNotNull('payout_id')->distinct()->pluck('payout_id')->all();
        $moneyPayouts = DB::table('overtime_payouts')->whereIn('id', $payoutIds ?: [0])->where('method', '!=', 'payroll')->where('status', '!=', 'cancelled')->pluck('id')->all();
        if ($moneyPayouts) {
            $command?->error('Cancel these cash / MoMo overtime pay-outs first (they moved money): #' . implode(', #', $moneyPayouts));

            return;
        }

        DB::transaction(function () use ($clientIds, $employeeIds, $overtimeIds, $payoutIds) {
            foreach (\App\Models\Receipt::whereIn('client_id', $clientIds ?: [0])->get() as $r) {
                app(ReceiptVoider::class)->void($r, 'Removing TEST-NOV test data');
            }
            $invoiceIds = Invoice::whereIn('client_id', $clientIds ?: [0])->pluck('id')->all();
            DB::table('overtime_attendance_links')->whereIn('overtime_id', $overtimeIds ?: [0])->delete();
            DB::table('attendances')->whereIn('employee_id', $employeeIds ?: [0])->orWhereIn('client_id', $clientIds ?: [0])->delete();
            DB::table('expenses')->whereIn('overtime_id', $overtimeIds ?: [0])->delete();
            DB::table('overtimes')->whereIn('id', $overtimeIds ?: [0])->delete();
            DB::table('overtime_payouts')->whereIn('id', $payoutIds ?: [0])->where('method', 'payroll')->delete();
            DB::table('salaries')->whereIn('employee_id', $employeeIds ?: [0])->delete();
            foreach (['payment_infos', 'employee_movements', 'absent_deduction_changes', 'client_employee', 'nrrit'] as $t) {
                if (Schema::hasTable($t)) {
                    DB::table($t)->whereIn('employee_id', $employeeIds ?: [0])->delete();
                }
            }
            DB::table('employees')->whereIn('id', $employeeIds ?: [0])->delete();
            DB::table('receipt_allocations')->whereIn('client_id', $clientIds ?: [0])->delete();
            DB::table('invoice_data')->whereIn('invoice_id', $invoiceIds ?: [0])->delete();
            foreach (['transactions', 'client_ledger_entries', 'receipt_voids', 'client_shift_plans', 'categories', 'pay_priority_changes', 'statement_sends'] as $t) {
                if (Schema::hasTable($t)) {
                    DB::table($t)->whereIn('client_id', $clientIds ?: [0])->delete();
                }
            }
            DB::table('invoices')->whereIn('id', $invoiceIds ?: [0])->delete();
            DB::table('clients')->whereIn('id', $clientIds ?: [0])->delete();
        });

        $command?->info('TEST-NOV data removed: ' . count($clientIds) . ' clients, ' . count($employeeIds) . ' guards, their invoices, receipts (voided), overtime, payroll pay-outs and salaries.');
    }
}

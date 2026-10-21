<?php

namespace Tests\Feature;

use App\Models\AccountPayment;
use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\Department;
use App\Models\employee;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\Field;
use App\Models\Overtime;
use App\Models\OvertimePayout;
use App\Models\Role;
use App\Models\User;
use App\Services\Banking\AccountReports;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Overtime is paid through pay-outs: one expense + one payment per office and period,
 * each guard ticked as paid by the office — never one bank payment per daily entry.
 */
class OvertimePayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;
    private Field $accra;
    private Field $tema;
    private Bank $cash;
    private Bank $gcb;
    private employee $yaw;
    private employee $ama;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-22 10:00:00');
        $dept = Department::create(['name' => 'Accounts']);
        $this->finance = User::factory()->create(['department_id' => $dept->id, 'role_id' => Role::forceCreate(['id' => 2, 'name' => 'Finance Manager', 'department_id' => $dept->id])->id, 'name' => 'Abena Finance']);
        $this->accra = Field::create(['name' => 'Accra', 'user_id' => $this->finance->id, 'number' => '1']);
        $this->tema = Field::create(['name' => 'Tema', 'user_id' => $this->finance->id, 'number' => '2']);
        $this->cash = Bank::create(['name' => 'Main Cash', 'kind' => 'cash', 'branch' => '', 'acc_number' => '', 'custodian' => 'Ama', 'total' => 1000]);
        $this->gcb = Bank::create(['name' => 'GCB Main', 'branch' => 'Accra', 'acc_number' => '111', 'total' => 5000]);
        $this->yaw = $this->guard('YAW OWUSU');
        $this->ama = $this->guard('AMA BOATENG');
        $this->actingAs($this->finance);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function guard(string $name, ?Field $field = null): employee
    {
        $e = new employee();
        $e->forceFill(['name' => $name, 'status' => 'Active', 'field_id' => ($field ?? $this->accra)->id, 'basic_salary' => 1500, 'allowances' => 0, 'user_id' => $this->finance->id])->save();

        return $e;
    }

    private function ot(float $amount, string $date = '2026-10-20', ?employee $guard = null, ?Field $field = null, bool $approved = true): Overtime
    {
        return Overtime::withoutEvents(fn () => Overtime::create([
            'entry_date' => $date, 'shift' => 'day', 'field_id' => ($field ?? $this->accra)->id, 'ot_employee_id' => ($guard ?? $this->yaw)->id,
            'reason' => 'Absent', 'amount' => $amount, 'phone_number' => '0244000000', 'absent_employee_note' => 'SHORTAGE', 'client_site_note' => 'DVLA',
            'user_1' => $this->finance->id, 'status_1' => 'approved', 'status_2' => $approved ? 'approved' : null, 'status_3' => $approved ? 'approved' : null,
        ]));
    }

    private function payout(array $ids, array $extra = [])
    {
        return $this->post(route('overtime.payouts.store'), array_merge([
            'field_id' => $this->accra->id, 'period_from' => '2026-10-19', 'period_to' => '2026-10-21', 'method' => 'cash',
            'overtime_ids' => $ids, 'bank_id' => $this->cash->id, 'held_by' => 'Kwame (officer)', 'payment_date' => '2026-10-22',
        ], $extra));
    }

    public function test_approving_overtime_no_longer_puts_each_entry_on_the_bank_payment_screen(): void
    {
        $o = Overtime::create([
            'entry_date' => '2026-10-20', 'shift' => 'day', 'field_id' => $this->accra->id, 'ot_employee_id' => $this->yaw->id,
            'reason' => 'Absent', 'amount' => 30, 'absent_employee_note' => 'SHORTAGE', 'client_site_note' => 'DVLA',
            'user_1' => $this->finance->id, 'status_1' => 'approved', 'status_2' => 'approved', 'status_3' => 'approved',
        ]);

        $this->assertNull($o->fresh()->expense_id);
        $this->assertSame(0, Expense::count());
        $this->assertCount(0, app(AccountReports::class)->awaitingPayment());
        $this->assertTrue(Overtime::owed()->whereKey($o->id)->exists());
    }

    public function test_cash_pay_out_is_one_expense_and_one_payment(): void
    {
        $a = $this->ot(30, '2026-10-19');
        $b = $this->ot(40, '2026-10-20', $this->ama);
        $c = $this->ot(50, '2026-10-21');
        $this->ot(60, '2026-10-20', null, $this->tema);          // other office
        $this->ot(70, '2026-10-20', null, null, false);           // not approved

        $this->payout([$a->id, $b->id, $c->id])->assertSessionHasNoErrors()->assertRedirect();
        $p = OvertimePayout::sole();

        $this->assertEquals(120, $p->total);
        $this->assertSame('open', $p->status);
        $this->assertEquals(880, $this->cash->fresh()->total);
        $this->assertSame(1, BankTransaction::where('bank_id', $this->cash->id)->count());           // one line, not three
        $pay = AccountPayment::sole();
        $this->assertSame('overtime', $pay->category);
        $this->assertSame('cash', $pay->method);
        $this->assertStringContainsString('Kwame (officer)', $pay->payee);
        $expense = Expense::sole();
        $this->assertEquals(120, $expense->amount);
        $this->assertSame('Overtime', $expense->type->name);
        $this->assertSame($pay->id, (int) $expense->payment_id);
        $this->assertSame([$p->id, $p->id, $p->id], [(int) $a->fresh()->payout_id, (int) $b->fresh()->payout_id, (int) $c->fresh()->payout_id]);
        $this->assertSame(0, Overtime::owed()->where('field_id', $this->accra->id)->count());   // nothing approved left owed in Accra
        $this->assertCount(0, app(AccountReports::class)->awaitingPayment());
    }

    public function test_office_ticks_guards_paid_and_returns_what_was_not_collected(): void
    {
        $a = $this->ot(30);
        $b = $this->ot(40, '2026-10-20', $this->ama);
        $c = $this->ot(50);
        $this->payout([$a->id, $b->id, $c->id]);
        $p = OvertimePayout::sole();

        $this->post(route('overtime.payouts.disburse', $p), ['overtime_ids' => [$a->id, $b->id], 'disbursed_at' => '2026-10-22'])->assertSessionHasNoErrors();
        $this->assertSame('2026-10-22', $a->fresh()->disbursed_at->toDateString());
        $this->assertSame('cash', $a->fresh()->disbursed_method);
        $this->assertSame('Cash from Kwame (officer)', $a->fresh()->disbursed_ref);
        $this->assertSame('open', $p->fresh()->status);
        $this->post(route('overtime.payouts.disburse', $p), ['overtime_ids' => [$a->id]])->assertSessionHasErrors('overtime_ids');   // already paid

        // The third guard never came: the 50 goes back into Main Cash and is owed again.
        $this->post(route('overtime.payouts.giveBack', $p), ['overtime_ids' => [$c->id], 'bank_id' => $this->cash->id, 'reason' => 'Guard absent on pay day'])->assertSessionHasNoErrors();
        $this->assertEquals(930, $this->cash->fresh()->total);                       // 1000 − 120 + 50
        $this->assertNull($c->fresh()->payout_id);
        $this->assertTrue(Overtime::owed()->whereKey($c->id)->exists());
        $this->assertEquals(70, Expense::sole()->amount);
        $this->assertEquals(50, $p->fresh()->returned);
        $this->assertSame('closed', $p->fresh()->status);

        // Bank & cash report: overtime out is what the guards actually received.
        $flow = app(AccountReports::class)->flow(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));
        $this->assertEquals(70, $flow['out']->firstWhere('label', 'Overtime pay-outs')['amount']);
    }

    public function test_momo_pay_out_needs_each_transaction_id(): void
    {
        $a = $this->ot(30);
        $b = $this->ot(40, '2026-10-20', $this->ama);
        $this->payout([$a->id], ['method' => 'momo', 'bank_id' => $this->cash->id])->assertSessionHasErrors('bank_id');
        $this->payout([$a->id, $b->id], ['method' => 'momo', 'bank_id' => $this->gcb->id, 'held_by' => null])->assertSessionHasNoErrors();
        $p = OvertimePayout::sole();
        $this->assertSame('momo', AccountPayment::sole()->method);
        $this->assertEquals(4930, $this->gcb->fresh()->total);

        $this->post(route('overtime.payouts.disburse', $p), ['overtime_ids' => [$a->id, $b->id], 'refs' => [$a->id => 'MP123']])->assertSessionHasErrors('refs');
        $this->post(route('overtime.payouts.disburse', $p), ['overtime_ids' => [$a->id, $b->id], 'refs' => [$a->id => 'MP123', $b->id => 'MP124']])->assertSessionHasNoErrors();
        $this->assertSame('MP124', $b->fresh()->disbursed_ref);
        $this->assertSame('closed', $p->fresh()->status);
    }

    public function test_payroll_pay_out_moves_no_money_and_flags_salary_differences(): void
    {
        $a = $this->ot(30);
        $b = $this->ot(40, '2026-10-21');
        $c = $this->ot(25, '2026-10-20', $this->ama);
        $this->payout([$a->id, $b->id, $c->id], ['method' => 'payroll', 'payroll_month' => '2026-10', 'bank_id' => null, 'held_by' => null])->assertSessionHasNoErrors();
        $p = OvertimePayout::sole();

        $this->assertSame(0, AccountPayment::count());
        $this->assertSame(0, Expense::count());
        $this->assertEquals(1000, $this->cash->fresh()->total);
        $this->assertSame('payroll', $a->fresh()->disbursed_method);
        $this->assertSame('closed', $p->status);

        // Yaw's October salary carries 150 overtime (70 approved here): possible double pay. Ama's carries none.
        DB::table('salaries')->insert(['employee_id' => $this->yaw->id, 'salary_month' => '2026-10-01', 'overtime' => 150, 'field_id' => $this->accra->id, 'payment_status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        $rows = app(\App\Services\Overtime\OvertimePayouts::class)->payrollCheck(Carbon::parse('2026-10-01'))->keyBy('employee_id');
        $this->assertEquals(80, $rows[$this->yaw->id]['difference']);
        $this->assertEquals(-25, $rows[$this->ama->id]['difference']);

        $this->get(route('overtime.payouts.payrollCheck', ['month' => '2026-10']))->assertOk()->assertSee('YAW OWUSU')->assertSee('+80.00');
        $this->get(route('overtime.payouts.show', $p))->assertOk()->assertSee('Payroll check')->assertSee('AMA BOATENG');
    }

    public function test_entries_on_a_pay_out_are_locked(): void
    {
        $a = $this->ot(30);
        $this->payout([$a->id]);
        $this->assertFalse($a->fresh()->isEditableBy($this->finance));
        $this->putJson('/overtime/' . $a->id, ['entry_date' => '2026-10-20', 'shift' => 'day', 'field_id' => $this->accra->id, 'ot_employee_id' => $this->yaw->id, 'amount' => 99, 'reason' => 'Absent', 'absent_employee_note' => 'SHORTAGE', 'client_site_note' => 'DVLA']);
        $this->delete('/overtime/' . $a->id)->assertForbidden();
        $this->assertEquals(30, $a->fresh()->amount);
    }

    public function test_what_cannot_go_on_a_pay_out(): void
    {
        $a = $this->ot(30);
        $outside = $this->ot(20, '2026-10-10');
        $tema = $this->ot(20, '2026-10-20', null, $this->tema);
        $pending = $this->ot(20, '2026-10-20', null, null, false);
        foreach ([$outside, $tema, $pending] as $bad) {
            $this->payout([$a->id, $bad->id])->assertSessionHasErrors('overtime_ids');
        }
        $this->payout([$a->id], ['held_by' => ''])->assertSessionHasErrors('held_by');
        $big = $this->ot(2000);
        $this->payout([$a->id, $big->id])->assertSessionHasErrors('amount');                 // Main Cash only holds 1000
        $this->assertSame(0, OvertimePayout::count());
        $this->assertNull($a->fresh()->payout_id);
        $this->assertEquals(1000, $this->cash->fresh()->total);

        $this->payout([$a->id])->assertSessionHasNoErrors();
        $this->payout([$a->id])->assertSessionHasErrors('overtime_ids');                      // already on one
    }

    public function test_cancel_puts_everything_back_while_nothing_is_paid(): void
    {
        $a = $this->ot(30);
        $b = $this->ot(40);
        $this->payout([$a->id, $b->id]);
        $p = OvertimePayout::sole();
        $this->post(route('overtime.payouts.cancel', $p), ['cancel_reason' => 'Wrong office'])->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $p->fresh()->status);
        $this->assertEquals(1000, $this->cash->fresh()->total);
        $this->assertSame('cancelled', AccountPayment::sole()->status);
        $this->assertSame(0, Expense::count());
        $this->assertTrue(Overtime::owed()->whereKey($a->id)->exists());

        $this->payout([$a->id, $b->id]);
        $p2 = OvertimePayout::latest('id')->first();
        $this->post(route('overtime.payouts.disburse', $p2), ['overtime_ids' => [$a->id]]);
        $this->post(route('overtime.payouts.cancel', $p2), ['cancel_reason' => 'Too late now'])->assertSessionHasErrors('cancel_reason');
        $this->assertSame('open', $p2->fresh()->status);
    }

    public function test_offices_see_and_pay_their_own_pay_outs_only(): void
    {
        $a = $this->ot(30);
        $t = $this->ot(20, '2026-10-20', $this->guard('KOJO', $this->tema), $this->tema);
        $this->payout([$a->id]);
        $this->payout([$t->id], ['field_id' => $this->tema->id]);
        [$accraPayout, $temaPayout] = OvertimePayout::orderBy('id')->get()->all();

        $role = Role::create(['name' => 'Manager', 'department_id' => $this->finance->department_id]);
        $officer = User::factory()->create(['department_id' => $this->finance->department_id, 'role_id' => $role->id, 'field_id' => $this->accra->id]);
        $this->actingAs($officer);

        $this->get(route('overtime.payouts'))->assertOk()->assertSee(route('overtime.payouts.show', $accraPayout))->assertDontSee(route('overtime.payouts.show', $temaPayout));
        $this->get(route('overtime.payouts.show', $temaPayout))->assertForbidden();
        $this->get(route('overtime.payouts.create'))->assertForbidden();
        $this->post(route('overtime.payouts.giveBack', $accraPayout), ['overtime_ids' => [$a->id], 'bank_id' => $this->cash->id, 'reason' => 'not allowed'])->assertForbidden();
        $this->post(route('overtime.payouts.disburse', $accraPayout), ['overtime_ids' => [$a->id]])->assertSessionHasNoErrors();
        $this->assertNotNull($a->fresh()->disbursed_at);
        $this->assertSame($officer->id, (int) $a->fresh()->disbursed_by);
    }

    public function test_migration_converts_the_old_expense_per_entry(): void
    {
        $type = ExpenseType::create(['name' => 'Overtime', 'scope' => 'field']);
        $unpaid = $this->ot(30);
        $paid = $this->ot(40);
        // As the old observer left them: linked by overtimes.expense_id (expenses.overtime_id was not fillable).
        $e1 = Expense::create(['description' => 'old', 'amount' => 30, 'field_id' => $this->accra->id, 'expense_type_id' => $type->id, 'status_3' => 'approved']);
        $e2 = Expense::create(['description' => 'old', 'amount' => 40, 'field_id' => $this->accra->id, 'expense_type_id' => $type->id, 'status_3' => 'approved']);
        Overtime::withoutEvents(fn () => [$unpaid->update(['expense_id' => $e1->id]), $paid->update(['expense_id' => $e2->id])]);
        $this->post(route('money.payments.store'), ['mode' => 'expenses', 'expense_ids' => [$e2->id], 'bank_id' => $this->cash->id, 'method' => 'cash', 'payee' => 'Yaw', 'payment_date' => '2026-10-21'])->assertSessionHasNoErrors();

        (require database_path('migrations/2026_10_22_090000_create_overtime_payouts.php'))->up();

        $this->assertSoftDeleted('expenses', ['id' => $e1->id]);
        $this->assertNull($unpaid->fresh()->expense_id);
        $this->assertTrue(Overtime::owed()->whereKey($unpaid->id)->exists());
        $this->assertNotNull(Expense::find($e2->id));
        $this->assertSame('payment', $paid->fresh()->disbursed_method);
        $this->assertSame('2026-10-21', $paid->fresh()->disbursed_at->toDateString());
        $this->assertFalse(Overtime::owed()->whereKey($paid->id)->exists());
    }

    public function test_pages_render(): void
    {
        $a = $this->ot(30);
        $this->ot(45, '2026-10-21', $this->ama);
        $this->get(route('overtime.payouts'))->assertOk()->assertSee('Overtime owed')->assertSee('75.00')->assertSee('Pay these');
        $this->get(route('overtime.payouts.create', ['field' => $this->accra->id, 'from' => '2026-10-19', 'to' => '2026-10-21']))->assertOk()
            ->assertSee('YAW OWUSU')->assertSee('AMA BOATENG')->assertSee('With the monthly salary');
        $this->payout([$a->id]);
        $p = OvertimePayout::sole();
        $this->get(route('overtime.payouts.show', $p))->assertOk()->assertSee('Still held by the officer')->assertSee('Mark ticked as paid')->assertSee('Return money not paid out');
        $this->get(route('money.payments.show', AccountPayment::sole()))->assertOk()->assertSee('Overtime pay-outs');
    }
}

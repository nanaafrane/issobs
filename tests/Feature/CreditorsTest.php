<?php

namespace Tests\Feature;

use App\Models\AccountPayment;
use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\Creditor;
use App\Models\CreditorEntry;
use App\Models\Department;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\Field;
use App\Models\Role;
use App\Models\User;
use App\Services\Banking\AccountReports;
use App\Services\Banking\CreditorBook;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Money borrowed by the company (director's loan) and paid back: a liability with its own
 * running balance, kept out of business money in / out.
 */
class CreditorsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Field $field;
    private Bank $gcb;
    private Bank $cash;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-22 10:00:00');
        $dept = Department::create(['name' => 'Finance']);
        $this->user = User::factory()->create(['department_id' => $dept->id, 'role_id' => Role::forceCreate(['id' => 2, 'name' => 'Finance Manager', 'department_id' => $dept->id])->id]);
        $this->field = Field::create(['name' => 'Accra', 'user_id' => $this->user->id, 'number' => '1']);
        $this->gcb = Bank::create(['name' => 'GCB Main', 'branch' => 'Accra', 'acc_number' => '111', 'total' => 1000]);
        $this->cash = Bank::create(['name' => 'Main Cash', 'kind' => 'cash', 'branch' => '', 'acc_number' => '', 'total' => 0]);
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function director(float $opening = 0): Creditor
    {
        $this->post(route('creditors.store'), ['name' => 'Nana Afrane', 'kind' => 'director', 'phone' => '0244000000', 'opening_balance' => $opening, 'opening_date' => '2026-10-01'])->assertSessionHasNoErrors();

        return Creditor::where('name', 'Nana Afrane')->firstOrFail();
    }

    private function expense(float $amount): Expense
    {
        return Expense::create(['description' => 'Fuel ' . $amount, 'amount' => $amount, 'expense_date' => '2026-10-15', 'field_id' => $this->field->id,
            'expense_type_id' => ExpenseType::firstOrCreate(['name' => 'Transport'], ['scope' => 'field'])->id, 'status_1' => 'approved', 'status_2' => 'approved', 'status_3' => 'approved']);
    }

    public function test_director_funds_a_bank_and_main_cash_and_the_company_owes_it(): void
    {
        $d = $this->director(500);
        $this->assertEquals(500, $d->balance);

        $this->post(route('creditors.receive', $d), ['bank_id' => $this->gcb->id, 'method' => 'transfer', 'amount' => 5000, 'entry_date' => '2026-10-20', 'reference' => 'TRF-1', 'description' => 'Salary shortfall'])->assertSessionHasNoErrors();
        $this->post(route('creditors.receive', $d), ['bank_id' => $this->cash->id, 'amount' => 800, 'entry_date' => '2026-10-21'])->assertSessionHasNoErrors();

        $this->assertEquals(6300, $d->fresh()->balance);
        $this->assertEquals(6000, $this->gcb->fresh()->total);
        $this->assertEquals(800, $this->cash->fresh()->total);
        $line = BankTransaction::where('bank_id', $this->gcb->id)->first();
        $this->assertSame('loan_in', $line->channel);
        $this->assertStringContainsString('Funding from Nana Afrane (Director, to be repaid)', $line->narration);
        $this->assertSame('cash', CreditorEntry::where('bank_id', $this->cash->id)->value('method'));

        // Borrowed money is not income.
        $flow = app(AccountReports::class)->flow(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));
        $this->assertEquals(0, $flow['total_in']);
        $this->assertEquals(5800, $flow['financing']['received']);
        $this->assertEmpty(app(AccountReports::class)->checks(Bank::all()));
    }

    public function test_paid_on_our_behalf_marks_expenses_paid_without_moving_company_money(): void
    {
        $d = $this->director();
        $a = $this->expense(120);
        $b = $this->expense(80);

        $this->post(route('creditors.onBehalf', $d), ['entry_date' => '2026-10-21', 'expense_ids' => [$a->id, $b->id], 'amount' => 999])->assertSessionHasNoErrors();
        $this->assertEquals(200, $d->fresh()->balance);               // the expenses' total
        $this->assertTrue($a->fresh()->isPaid());
        $this->assertFalse($a->fresh()->isEditableBy($this->user));
        $this->assertCount(0, app(AccountReports::class)->awaitingPayment());
        $this->assertSame(0, BankTransaction::count());

        // Paying it again from a bank is refused.
        $this->post(route('money.payments.store'), ['mode' => 'expenses', 'expense_ids' => [$a->id], 'bank_id' => $this->gcb->id, 'method' => 'transfer', 'payee' => 'X', 'payment_date' => '2026-10-22'])
            ->assertSessionHasErrors('expense_ids');

        $this->post(route('creditors.onBehalf', $d), ['entry_date' => '2026-10-21', 'amount' => 350, 'payee' => 'Uniform Ltd', 'description' => 'Inv 9'])->assertSessionHasNoErrors();
        $this->assertEquals(550, $d->fresh()->balance);
        $this->post(route('creditors.onBehalf', $d), ['entry_date' => '2026-10-21', 'amount' => 50])->assertSessionHasErrors('payee');

        $this->get(route('expense.list'))->assertOk()->assertSee('Paid by a creditor');
    }

    public function test_repayment_with_interest_and_never_more_than_owed(): void
    {
        $d = $this->director();
        $this->post(route('creditors.receive', $d), ['bank_id' => $this->gcb->id, 'method' => 'transfer', 'amount' => 2000, 'entry_date' => '2026-10-10']);

        $this->post(route('creditors.repay', $d), ['bank_id' => $this->gcb->id, 'method' => 'transfer', 'amount' => 2500, 'entry_date' => '2026-10-22'])->assertSessionHasErrors('amount');
        $this->post(route('creditors.repay', $d), ['bank_id' => $this->gcb->id, 'method' => 'cheque', 'amount' => 500, 'entry_date' => '2026-10-22'])->assertSessionHasErrors('reference');
        $this->post(route('creditors.repay', $d), ['bank_id' => $this->gcb->id, 'method' => 'cheque', 'reference' => '000777', 'amount' => 1500, 'interest' => 30, 'entry_date' => '2026-10-22'])->assertSessionHasNoErrors();

        $this->assertEquals(500, $d->fresh()->balance);
        $this->assertEquals(3000 - 1500 - 30, $this->gcb->fresh()->total);
        $this->assertSame('loan_out', BankTransaction::where('channel', 'loan_out')->value('channel'));
        $interest = AccountPayment::sole();
        $this->assertSame('interest', $interest->category);
        $this->assertEquals(30, $interest->amount);

        $flow = app(AccountReports::class)->flow(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));
        $this->assertEquals(1500, $flow['financing']['repaid']);
        $this->assertEquals(30, $flow['total_out']);                     // only the interest is a cost
        $this->assertEquals(30, $flow['out']->firstWhere('label', 'Interest on loans')['amount']);

        // Main Cash cannot pay back more than it holds.
        $this->post(route('creditors.repay', $d), ['bank_id' => $this->cash->id, 'amount' => 100, 'entry_date' => '2026-10-22'])->assertSessionHasErrors('amount');
    }

    public function test_reversals_put_everything_back(): void
    {
        $d = $this->director();
        $this->post(route('creditors.receive', $d), ['bank_id' => $this->gcb->id, 'method' => 'transfer', 'amount' => 900, 'entry_date' => '2026-10-20']);
        $in = CreditorEntry::latest('id')->first();
        $e = $this->expense(60);
        $this->post(route('creditors.onBehalf', $d), ['entry_date' => '2026-10-21', 'expense_ids' => [$e->id]]);
        $behalf = CreditorEntry::latest('id')->first();
        $this->post(route('creditors.repay', $d), ['bank_id' => $this->gcb->id, 'method' => 'transfer', 'amount' => 400, 'interest' => 10, 'entry_date' => '2026-10-22']);
        $repay = CreditorEntry::latest('id')->first();
        $this->assertEquals(560, $d->fresh()->balance);

        // The funding cannot be reversed while that would leave a negative balance... after reversing the repayment it can.
        $this->post(route('creditors.reverse', $repay), ['reversal_reason' => 'Paid to the wrong account'])->assertSessionHasNoErrors();
        $this->assertEquals(960, $d->fresh()->balance);
        $this->assertEquals(1900, $this->gcb->fresh()->total);
        $this->assertSame('cancelled', AccountPayment::sole()->status);

        $this->post(route('creditors.reverse', $behalf), ['reversal_reason' => 'Paid by the company after all'])->assertSessionHasNoErrors();
        $this->assertFalse($e->fresh()->isPaid());
        $this->post(route('creditors.reverse', $in), ['reversal_reason' => 'Entered twice'])->assertSessionHasNoErrors();
        $this->assertEquals(0, $d->fresh()->balance);
        $this->assertEquals(1000, $this->gcb->fresh()->total);
        $this->post(route('creditors.reverse', $in), ['reversal_reason' => 'Again please'])->assertSessionHasErrors('reversal_reason');
        $this->assertEmpty(app(AccountReports::class)->checks(Bank::all()));
    }

    public function test_cannot_reverse_funding_already_repaid(): void
    {
        $d = $this->director();
        $this->post(route('creditors.receive', $d), ['bank_id' => $this->gcb->id, 'method' => 'transfer', 'amount' => 300, 'entry_date' => '2026-10-20']);
        $in = CreditorEntry::latest('id')->first();
        $this->post(route('creditors.repay', $d), ['bank_id' => $this->gcb->id, 'method' => 'transfer', 'amount' => 300, 'entry_date' => '2026-10-21']);
        $this->post(route('creditors.reverse', $in), ['reversal_reason' => 'Entered in error'])->assertSessionHasErrors('reversal_reason');
        $this->assertEquals(0, $d->fresh()->balance);
    }

    public function test_statement_running_balance(): void
    {
        $d = $this->director(1000);
        $this->post(route('creditors.receive', $d), ['bank_id' => $this->gcb->id, 'method' => 'transfer', 'amount' => 500, 'entry_date' => '2026-09-15', 'reference' => 'OLD']);
        $this->post(route('creditors.receive', $d), ['bank_id' => $this->gcb->id, 'method' => 'transfer', 'amount' => 250, 'entry_date' => '2026-10-05']);
        $this->post(route('creditors.repay', $d), ['bank_id' => $this->gcb->id, 'method' => 'transfer', 'amount' => 600, 'entry_date' => '2026-10-12']);

        $st = app(CreditorBook::class)->statement($d->fresh(), Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));
        $this->assertEquals(1500, $st['opening']);       // 1000 opening + September's 500
        $this->assertEquals([1750.0, 1150.0], $st['lines']->pluck('running')->all());
        $this->assertEquals(1150, $st['closing']);
        $this->assertEquals(1150, $d->fresh()->balance);

        $this->get(route('creditors.show', ['creditor' => $d->id, 'from' => '2026-10-01', 'to' => '2026-10-31']))->assertOk()
            ->assertSee('1,500.00')->assertSee('1,150.00')->assertSee('Money received')->assertSee('Repaid');
        $this->get(route('creditors.index'))->assertOk()->assertSee('Nana Afrane')->assertSee('1,150.00');
        $this->get(route('banks.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))->assertOk()->assertSee('Financing')->assertSee('Owed to creditors');
        $this->get(route('banks.show', $this->gcb))->assertOk()->assertSee('Funding (to repay)')->assertSee('Loan repayment');
    }

    public function test_only_finance_records_and_only_finance_manager_reverses(): void
    {
        $d = $this->director();
        $this->post(route('creditors.receive', $d), ['bank_id' => $this->gcb->id, 'method' => 'transfer', 'amount' => 100, 'entry_date' => '2026-10-20']);
        $entry = CreditorEntry::first();
        $clerk = User::factory()->create(['department_id' => $this->user->department_id, 'role_id' => Role::create(['name' => 'Invoice', 'department_id' => $this->user->department_id])->id]);
        $branch = User::factory()->create(['department_id' => $this->user->department_id, 'role_id' => Role::create(['name' => 'Manager', 'department_id' => $this->user->department_id])->id]);

        $this->actingAs($clerk);
        $this->get(route('creditors.show', $d))->assertOk();
        $this->post(route('creditors.reverse', $entry), ['reversal_reason' => 'not allowed'])->assertForbidden();

        $this->actingAs($branch);
        $this->get(route('creditors.index'))->assertForbidden();
        $this->post(route('creditors.receive', $d), ['bank_id' => $this->gcb->id, 'amount' => 1, 'entry_date' => '2026-10-20'])->assertForbidden();
    }
}

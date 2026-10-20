<?php

namespace Tests\Feature;

use App\Models\AccountPayment;
use App\Models\AccountTransfer;
use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\Department;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\Field;
use App\Models\Role;
use App\Models\User;
use App\Services\Banking\AccountReports;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Main Cash (cash on hand) funded by cheques from the banks, payments out (approved
 * expenses, suppliers), cheque clearing, cancellations, adjustments and statements.
 */
class MainCashAndPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Field $field;
    private Bank $gcb;
    private Bank $eco;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20 10:00:00');
        $department = Department::create(['name' => 'Finance']);
        $role = Role::forceCreate(['id' => 2, 'name' => 'Finance Manager', 'department_id' => $department->id]);
        $this->user = User::factory()->create(['department_id' => $department->id, 'role_id' => $role->id, 'name' => 'Abena Finance']);
        $this->field = Field::create(['name' => 'Accra', 'user_id' => $this->user->id, 'number' => '1']);
        $this->gcb = Bank::create(['name' => 'GCB Main', 'branch' => 'Accra', 'acc_number' => '111', 'total' => 1000]);
        $this->eco = Bank::create(['name' => 'Ecobank Ops', 'branch' => 'Tema', 'acc_number' => '222', 'total' => 500]);
        foreach (['cash', 'cheque', 'transfer', 'momo', 'other payments'] as $m) {
            DB::table('receipt_mode')->insert(['name' => $m]);
        }
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function mainCash(float $opening = 0): Bank
    {
        $this->post(route('banks.store'), ['kind' => 'cash', 'name' => 'Main Cash', 'custodian' => 'Ama (cashier)', 'opening_balance' => $opening])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        return Bank::where('name', 'Main Cash')->firstOrFail();
    }

    private function fund(Bank $to, array $lines, array $extra = [])
    {
        return $this->post(route('money.transfers.store'), array_merge([
            'to_bank_id' => $to->id, 'transfer_date' => '2026-10-20', 'notes' => 'October imprest', 'lines' => $lines,
        ], $extra));
    }

    private function expense(float $amount, bool $approved = true): Expense
    {
        $type = ExpenseType::firstOrCreate(['name' => 'Transport'], ['scope' => 'field']);

        return Expense::create([
            'description' => 'Fuel ' . $amount, 'amount' => $amount, 'expense_date' => '2026-10-15',
            'field_id' => $this->field->id, 'expense_type_id' => $type->id,
            'user_1' => $this->user->id, 'status_1' => 'approved', 'status_2' => 'approved', 'status_3' => $approved ? 'approved' : null,
        ]);
    }

    public function test_main_cash_is_a_cash_account_kept_out_of_bank_pickers(): void
    {
        $cash = $this->mainCash();
        $this->assertSame('cash', $cash->kind);
        $this->assertEquals(0, $cash->total);
        $this->assertSame('Ama (cashier)', $cash->custodian);

        $this->get(route('receipt.multi.create'))->assertOk()->assertViewHas('banks', fn ($b) => ! $b->contains('id', $cash->id) && $b->contains('id', $this->gcb->id));
        $this->get(route('deposit.create'))->assertOk()->assertViewHas('banks', fn ($b) => ! $b->contains('id', $cash->id));
        $this->post('/deposit', ['bank_id' => $cash->id, 'deposit_date' => '2026-10-20', 'cash' => [1]])->assertSessionHasErrors('bank_id');
    }

    public function test_cheques_from_several_banks_fund_main_cash_in_one_entry(): void
    {
        $cash = $this->mainCash();
        $this->fund($cash, [
            ['from_bank_id' => $this->gcb->id, 'method' => 'cheque', 'reference' => '000123', 'amount' => 300],
            ['from_bank_id' => $this->eco->id, 'method' => 'withdrawal', 'reference' => '', 'amount' => 200],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertEquals(500, $cash->fresh()->total);
        $this->assertEquals(700, $this->gcb->fresh()->total);
        $this->assertEquals(300, $this->eco->fresh()->total);
        $transfers = AccountTransfer::all();
        $this->assertCount(2, $transfers);
        $this->assertSame(1, $transfers->pluck('batch')->unique()->count());
        $out = BankTransaction::where('bank_id', $this->gcb->id)->where('channel', 'transfer_out')->first();
        $this->assertEquals(300, $out->debit);
        $this->assertStringContainsString('Cheque 000123 to Main Cash', $out->narration);
        $this->assertSame('2026-10-20', $out->entry_date->toDateString());

        // Not money in or out of the business.
        $flow = app(AccountReports::class)->flow(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));
        $this->assertEquals(0, $flow['total_in']);
        $this->assertEquals(0, $flow['total_out']);
        $this->assertEquals(500, $flow['internal']);
        $this->assertEmpty(app(AccountReports::class)->checks(Bank::all()));
    }

    public function test_transfer_rules(): void
    {
        $cash = $this->mainCash();
        $this->fund($cash, [['from_bank_id' => $this->gcb->id, 'method' => 'transfer', 'amount' => 50]])->assertSessionHasErrors('lines.0.method');   // bank → cash is a cheque or withdrawal
        $this->fund($cash, [['from_bank_id' => $this->gcb->id, 'method' => 'cheque', 'reference' => '', 'amount' => 50]])->assertSessionHasErrors('lines.0.reference');
        $this->fund($this->gcb, [['from_bank_id' => $this->gcb->id, 'method' => 'cheque', 'reference' => '9', 'amount' => 50]])->assertSessionHasErrors('lines.0.from_bank_id');
        $this->fund($this->gcb, [['from_bank_id' => $cash->id, 'method' => 'deposit', 'amount' => 50]])->assertSessionHasErrors('lines.0.amount');   // cash is empty
        $this->fund($cash, [['from_bank_id' => $this->gcb->id, 'method' => 'cheque', 'reference' => '1', 'amount' => 0]])->assertSessionHasErrors('lines');
        $this->assertSame(0, AccountTransfer::count());
        $this->assertEquals(1000, $this->gcb->fresh()->total);

        $this->fund($cash, [['from_bank_id' => $this->gcb->id, 'method' => 'cheque', 'reference' => '7', 'amount' => 100]])->assertSessionHasNoErrors();
        $this->fund($this->eco, [['from_bank_id' => $cash->id, 'method' => 'deposit', 'amount' => 40]])->assertSessionHasNoErrors();   // unused cash paid back in
        $this->assertEquals(60, $cash->fresh()->total);
        $this->assertEquals(540, $this->eco->fresh()->total);
    }

    public function test_paying_approved_expenses_from_main_cash_marks_them_paid(): void
    {
        $cash = $this->mainCash(200);
        $a = $this->expense(120);
        $b = $this->expense(50);
        $pending = $this->expense(30, approved: false);

        $this->post(route('money.payments.store'), ['mode' => 'expenses', 'expense_ids' => [$a->id, $pending->id], 'bank_id' => $cash->id, 'method' => 'cash', 'payee' => 'Kwame (driver)', 'payment_date' => '2026-10-20'])
            ->assertSessionHasErrors('expense_ids');
        $this->assertNull($a->fresh()->payment_id);

        $this->post(route('money.payments.store'), ['mode' => 'expenses', 'expense_ids' => [$a->id, $b->id], 'bank_id' => $cash->id, 'method' => 'cash', 'payee' => 'Kwame (driver)', 'payment_date' => '2026-10-20', 'amount' => 999, 'category' => 'supplier'])
            ->assertSessionHasNoErrors();
        $p = AccountPayment::latest('id')->first();
        $this->assertEquals(170, $p->amount);                 // the expenses' total, not what was typed
        $this->assertSame('expenses', $p->category);
        $this->assertSame('paid', $p->status);
        $this->assertSame($p->id, (int) $a->fresh()->payment_id);
        $this->assertEquals(30, $cash->fresh()->total);
        $this->assertCount(0, app(AccountReports::class)->awaitingPayment()->whereIn('id', [$a->id, $b->id]));

        // A paid expense can no longer be edited.
        $this->assertFalse($a->fresh()->isEditableBy($this->user));

        // Not enough cash: nothing saved.
        $c = $this->expense(80);
        $this->post(route('money.payments.store'), ['mode' => 'expenses', 'expense_ids' => [$c->id], 'bank_id' => $cash->id, 'method' => 'cash', 'payee' => 'X', 'payment_date' => '2026-10-20'])
            ->assertSessionHasErrors('amount');
        $this->assertNull($c->fresh()->payment_id);
        $this->assertEquals(30, $cash->fresh()->total);
    }

    public function test_cheque_payment_to_a_supplier_is_issued_then_cleared_or_cancelled(): void
    {
        $this->post(route('money.payments.store'), ['mode' => 'other', 'bank_id' => $this->gcb->id, 'method' => 'cheque', 'reference' => '' , 'payee' => 'Uniform Ltd', 'payment_date' => '2026-10-19', 'amount' => 250, 'category' => 'supplier'])
            ->assertSessionHasErrors('reference');
        $this->post(route('money.payments.store'), ['mode' => 'other', 'bank_id' => $this->gcb->id, 'method' => 'cash', 'payee' => 'Uniform Ltd', 'payment_date' => '2026-10-19', 'amount' => 250, 'category' => 'supplier'])
            ->assertSessionHasErrors('method');   // a bank pays by cheque / transfer

        $this->post(route('money.payments.store'), ['mode' => 'other', 'bank_id' => $this->gcb->id, 'method' => 'cheque', 'reference' => '000456', 'payee' => 'Uniform Ltd', 'payment_date' => '2026-10-19', 'amount' => 250, 'category' => 'supplier', 'description' => 'Inv 77'])
            ->assertSessionHasNoErrors();
        $p = AccountPayment::latest('id')->first();
        $this->assertSame('issued', $p->status);
        $this->assertEquals(750, $this->gcb->fresh()->total);

        $this->get(route('banks.show', $this->gcb))->assertOk()->assertSee('000456')->assertSee('not cleared')->assertSee('1,000.00');   // 750 + 250 uncleared
        $this->post(route('money.payments.clear', $p), ['cleared_date' => '2026-10-20'])->assertSessionHasNoErrors();
        $this->assertSame('cleared', $p->fresh()->status);

        $this->post(route('money.payments.cancel', $p), ['cancel_reason' => 'Cheque stopped'])->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $p->fresh()->status);
        $this->assertEquals(1000, $this->gcb->fresh()->total);
        $this->assertSame(2, BankTransaction::where('payment_id', $p->id)->count());
        $this->post(route('money.payments.cancel', $p), ['cancel_reason' => 'Again please'])->assertSessionHasErrors('cancel_reason');

        $flow = app(AccountReports::class)->flow(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));
        $this->assertEquals(0, $flow['total_out']);   // paid and cancelled: nothing went out
    }

    public function test_cancelling_an_expense_payment_puts_the_expenses_back(): void
    {
        $cash = $this->mainCash(100);
        $e = $this->expense(60);
        $this->post(route('money.payments.store'), ['mode' => 'expenses', 'expense_ids' => [$e->id], 'bank_id' => $cash->id, 'method' => 'cash', 'payee' => 'Kofi', 'payment_date' => '2026-10-20']);
        $p = AccountPayment::latest('id')->first();
        $this->post(route('money.payments.cancel', $p), ['cancel_reason' => 'Paid to the wrong person'])->assertSessionHasNoErrors();
        $this->assertNull($e->fresh()->payment_id);
        $this->assertEquals(100, $cash->fresh()->total);
    }

    public function test_reversing_a_funding_and_not_once_the_cash_is_spent(): void
    {
        $cash = $this->mainCash();
        $this->fund($cash, [['from_bank_id' => $this->gcb->id, 'method' => 'cheque', 'reference' => '1', 'amount' => 100], ['from_bank_id' => $this->eco->id, 'method' => 'cheque', 'reference' => '2', 'amount' => 100]]);
        $t = AccountTransfer::first();
        $this->post(route('money.transfers.reverse', $t), ['reversal_reason' => 'Entered twice'])->assertSessionHasNoErrors();
        $this->assertEquals(0, $cash->fresh()->total);
        $this->assertEquals(1000, $this->gcb->fresh()->total);
        $this->assertEquals(500, $this->eco->fresh()->total);
        $this->assertSame(2, AccountTransfer::whereNotNull('reversed_at')->count());

        $this->fund($cash, [['from_bank_id' => $this->gcb->id, 'method' => 'cheque', 'reference' => '3', 'amount' => 100]]);
        $this->post(route('money.payments.store'), ['mode' => 'other', 'bank_id' => $cash->id, 'method' => 'cash', 'payee' => 'Shop', 'payment_date' => '2026-10-20', 'amount' => 80, 'category' => 'other']);
        $t2 = AccountTransfer::latest('id')->first();
        $this->post(route('money.transfers.reverse', $t2), ['reversal_reason' => 'Wrong cheque'])->assertSessionHasErrors('reversal_reason');
        $this->assertNull($t2->fresh()->reversed_at);
    }

    public function test_statement_opening_running_and_closing_balances(): void
    {
        $this->post(route('money.payments.store'), ['mode' => 'other', 'bank_id' => $this->gcb->id, 'method' => 'transfer', 'payee' => 'ECG', 'payment_date' => '2026-09-28', 'amount' => 100, 'category' => 'rent_utilities']);
        $this->post(route('money.payments.store'), ['mode' => 'other', 'bank_id' => $this->gcb->id, 'method' => 'transfer', 'payee' => 'GRA', 'payment_date' => '2026-10-05', 'amount' => 200, 'category' => 'statutory']);
        $this->post(route('banks.adjust', $this->gcb), ['amount' => '15.50', 'reason' => 'Interest on bank statement', 'adjust_date' => '2026-10-10'])->assertSessionHasNoErrors();

        $st = app(AccountReports::class)->statement($this->gcb->fresh(), Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31')->endOfDay());
        $this->assertEquals(900, $st['opening']);       // 1000 − September's 100
        $this->assertEquals(715.5, $st['closing']);
        $this->assertEquals([700.0, 715.5], $st['lines']->pluck('running')->all());
        $this->assertEquals(715.5, $this->gcb->fresh()->total);

        $this->get(route('banks.show', ['bank' => $this->gcb->id, 'from' => '2026-10-01', 'to' => '2026-10-31']))->assertOk()
            ->assertSee('900.00')->assertSee('715.50')->assertSee('Interest on bank statement');
    }

    public function test_balance_is_never_typed_in_and_used_accounts_are_closed_not_deleted(): void
    {
        $this->put(route('banks.update', $this->gcb), ['name' => 'GCB Main Acc', 'acc_number' => '111', 'branch' => 'Accra', 'total' => 999999])->assertSessionHasNoErrors();
        $this->assertSame('GCB Main Acc', $this->gcb->fresh()->name);
        $this->assertEquals(1000, $this->gcb->fresh()->total);

        $this->post(route('money.payments.store'), ['mode' => 'other', 'bank_id' => $this->eco->id, 'method' => 'transfer', 'payee' => 'X', 'payment_date' => '2026-10-20', 'amount' => 10, 'category' => 'bank_charges']);
        $this->delete(route('banks.destroy', $this->eco))->assertSessionHas('error');
        $this->assertNotNull($this->eco->fresh());
        $this->post(route('banks.toggle', $this->eco))->assertSessionHas('error');   // balance not zero
        $this->post(route('banks.adjust', $this->eco), ['amount' => '-490', 'reason' => 'Account emptied for closure']);
        $this->post(route('banks.toggle', $this->eco))->assertSessionHas('success');
        $this->assertFalse($this->eco->fresh()->is_active);
        $this->post(route('money.payments.store'), ['mode' => 'other', 'bank_id' => $this->eco->id, 'method' => 'transfer', 'payee' => 'X', 'payment_date' => '2026-10-20', 'amount' => 1, 'category' => 'other'])
            ->assertSessionHasErrors('bank_id');

        $unused = Bank::create(['name' => 'Never used', 'branch' => 'x', 'acc_number' => '9']);
        $this->delete(route('banks.destroy', $unused))->assertSessionHas('success');
        $this->assertNull(Bank::find($unused->id));
    }

    public function test_only_finance_manager_or_director_correct_things(): void
    {
        $invoiceRole = Role::create(['name' => 'Invoice', 'department_id' => $this->user->department_id]);
        $clerk = User::factory()->create(['department_id' => $this->user->department_id, 'role_id' => $invoiceRole->id]);
        $branchRole = Role::create(['name' => 'Manager', 'department_id' => $this->user->department_id]);
        $branch = User::factory()->create(['department_id' => $this->user->department_id, 'role_id' => $branchRole->id]);

        $this->actingAs($clerk);
        $this->post(route('money.payments.store'), ['mode' => 'other', 'bank_id' => $this->gcb->id, 'method' => 'transfer', 'payee' => 'X', 'payment_date' => '2026-10-20', 'amount' => 10, 'category' => 'other'])->assertSessionHasNoErrors();
        $p = AccountPayment::latest('id')->first();
        $this->post(route('money.payments.cancel', $p), ['cancel_reason' => 'not allowed'])->assertForbidden();
        $this->post(route('banks.adjust', $this->gcb), ['amount' => 5, 'reason' => 'not allowed'])->assertForbidden();

        $this->actingAs($branch);
        $this->get(route('money.payments'))->assertForbidden();
        $this->get(route('banks.show', $this->gcb))->assertForbidden();
        $this->post(route('money.transfers.store'), ['to_bank_id' => $this->gcb->id, 'transfer_date' => '2026-10-20', 'lines' => []])->assertForbidden();
    }

    public function test_existing_balances_are_kept_as_opening_balance_by_the_migration(): void
    {
        $old = Bank::create(['name' => 'Old Bank', 'branch' => 'x', 'acc_number' => '5', 'total' => 0]);
        DB::table('banks')->where('id', $old->id)->update(['total' => 1250, 'opening_balance' => 0, 'opening_date' => null]);
        BankTransaction::create(['bank_id' => $old->id, 'credit' => 200, 'balance' => 1250, 'channel' => 'deposit', 'entry_date' => '2026-08-01']);
        BankTransaction::create(['bank_id' => $old->id, 'debit' => 50, 'balance' => 1200, 'channel' => 'expense', 'entry_date' => '2026-08-02']);
        DB::table('banks')->where('id', $old->id)->update(['total' => 1200]);

        (require database_path('migrations/2026_10_20_160000_add_cash_accounts_transfers_and_payments.php'))->up();

        $fresh = $old->fresh();
        $this->assertEquals(1050, $fresh->opening_balance);   // 1200 − (200 − 50)
        $this->assertSame('2026-08-01', $fresh->opening_date->toDateString());
        $this->assertEmpty(app(AccountReports::class)->checks(collect([$fresh])));
    }

    public function test_pages_render(): void
    {
        $cash = $this->mainCash(50);
        $this->fund($cash, [['from_bank_id' => $this->gcb->id, 'method' => 'cheque', 'reference' => '000789', 'amount' => 100]]);
        $e = $this->expense(40);
        $this->post(route('money.payments.store'), ['mode' => 'expenses', 'expense_ids' => [$e->id], 'bank_id' => $cash->id, 'method' => 'cash', 'payee' => 'Driver', 'payment_date' => '2026-10-20']);
        $this->post(route('money.payments.store'), ['mode' => 'other', 'bank_id' => $this->gcb->id, 'method' => 'cheque', 'reference' => '000790', 'payee' => 'Supplier Co', 'payment_date' => '2026-10-20', 'amount' => 75, 'category' => 'supplier']);
        $this->expense(25);

        $this->get(route('banks.index'))->assertOk()
            ->assertSee('Cash on hand')->assertSee('Main Cash')->assertSee('Approved expenses')->assertSee('Supplier / service provider')
            ->assertSee('between our own accounts')->assertSee('Cheques issued, not yet cleared');
        $this->get(route('banks.show', $cash))->assertOk()->assertSee('Cash count')->assertSee('Held by Ama (cashier)');
        $this->get(route('money.transfers'))->assertOk()->assertSee('000789');
        $this->get(route('money.transfers.show', AccountTransfer::first()))->assertOk()->assertSee('Cheque 000789 to Main Cash');
        $this->get(route('money.transfers.create'))->assertOk()->assertSee('Add another cheque');
        $this->get(route('money.payments'))->assertOk()->assertSee('Supplier Co')->assertSee('Cheque issued');
        $this->get(route('money.payments', ['status' => 'issued']))->assertOk()->assertSee('000790');
        $this->get(route('money.payments.create', ['for' => 'expenses']))->assertOk()->assertSee('Fuel 25');
        $this->get(route('money.payments.show', AccountPayment::where('category', 'expenses')->first()))->assertOk()->assertSee('Expenses paid')->assertSee('Fuel 40');
        $this->get(route('expense.list'))->assertOk()->assertSee('Awaiting payment')->assertSee('Paid');
    }
}

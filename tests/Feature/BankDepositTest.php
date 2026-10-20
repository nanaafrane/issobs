<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\BankDeposit;
use App\Models\BankTransaction;
use App\Models\Client;
use App\Models\Collection;
use App\Models\Department;
use App\Models\Field;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Bank Deposit with cash and MoMo: one record per slip; MoMo withdrawal charge
 * shown as its own bank debit; bank moves by exactly the amount on the slip.
 */
class BankDepositTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Field $field;
    private Client $client;
    private Bank $bank;

    protected function setUp(): void
    {
        parent::setUp();
        $department = Department::create(['name' => 'Finance']);
        $role = Role::forceCreate(['id' => 2, 'name' => 'Finance Manager', 'department_id' => $department->id]); // approval code keys on role id 2
        $this->user = User::factory()->create(['department_id' => $department->id, 'role_id' => $role->id, 'name' => 'Abena Finance']);
        $this->field = Field::create(['name' => 'Accra', 'user_id' => $this->user->id, 'number' => '1']);
        $this->client = Client::create(['name' => 'Kofi Mensah', 'business_name' => 'Alpha Security', 'field_id' => $this->field->id]);
        $this->bank = Bank::create(['name' => 'GCB Main', 'branch' => 'Accra', 'acc_number' => '1234567', 'total' => 1000]);
        foreach (['cash', 'cheque', 'transfer', 'momo', 'other payments'] as $m) {
            DB::table('receipt_mode')->insert(['name' => $m]);
        }
        $this->actingAs($this->user);
    }

    /** A real receipt through the receipt screen; returns its collection. */
    private function receive(float $cash = 0, float $momo = 0, array $extra = []): Collection
    {
        $invoice = Invoice::create([
            'client_id' => $this->client->id, 'total' => 5000, 'sub_amount' => 5000, 'sub_total' => 0,
            'invoice_month' => '2026-10-01', 'due_date' => now()->addDays(10), 'status' => 'unpaid', 'balance' => 0,
        ]);
        $mode = array_values(array_filter([$cash > 0 ? 'cash' : null, $momo > 0 ? 'momo' : null]));
        $this->post(route('receipt.multi.store'), array_merge([
            'client_id' => $this->client->id, 'receipt_month' => now()->toDateString(), 'from' => 'Kofi',
            'mode' => $mode, 'cash_amount' => $cash ?: null, 'momo_amount' => $momo ?: null,
            'momo_transactin_id' => $momo ? 'MP' . random_int(100000, 999999) : null,
            'invoices' => [$invoice->id => ['selected' => '1']],
            'confirm_duplicate' => '1',
        ], $extra))->assertSessionHasNoErrors();

        return Collection::where('receipt_id', Receipt::latest('id')->value('id'))->firstOrFail();
    }

    private function deposit(array $fields)
    {
        return $this->post('/deposit', array_merge(['bank_id' => $this->bank->id, 'deposit_date' => now()->toDateString()], $fields));
    }

    public function test_cash_alone_is_one_credit_with_no_charge(): void
    {
        $c = $this->receive(cash: 500);
        $this->deposit(['cash' => [$c->id], 'slip_number' => 'S-100'])->assertSessionHasNoErrors()->assertSessionHas('success');

        $d = BankDeposit::latest('id')->first();
        $this->assertEquals(500, $d->total);
        $this->assertSame('S-100', $d->slip_number);
        $this->assertEquals(1500, $this->bank->fresh()->total);
        $lines = BankTransaction::where('deposit_id', $d->id)->get();
        $this->assertCount(1, $lines);
        $this->assertEquals(500, $lines[0]->credit);
        $this->assertStringContainsString('slip S-100', $lines[0]->narration);
        $this->assertSame('Deposited', $c->fresh()->status);
    }

    public function test_momo_charge_is_a_separate_bank_debit_and_bank_moves_by_the_slip_amount(): void
    {
        $c = $this->receive(momo: 300);
        $this->assertSame('undeposited', $c->status);   // MoMo now waits for Bank Deposit

        $this->deposit(['momo' => [$c->id], 'momo_charge' => '3.00', 'slip_number' => 'S-7'])->assertSessionHasNoErrors();

        $d = BankDeposit::latest('id')->first();
        $this->assertEquals(300, $d->momo_amount);
        $this->assertEquals(3, $d->momo_charge);
        $this->assertEquals(300, $d->gross_amount);
        $this->assertEquals(297, $d->total);                         // amount on the slip
        $this->assertEquals(1297, $this->bank->fresh()->total);      // not 1294: charge counted once

        [$in, $out] = BankTransaction::where('deposit_id', $d->id)->orderBy('id')->get()->all();
        $this->assertSame('deposit', $in->channel);
        $this->assertEquals(300, $in->credit);
        $this->assertSame('momo_charge', $out->channel);
        $this->assertEquals(3, $out->debit);
        $this->assertStringContainsString('MoMo withdrawal charge slip S-7', $out->narration);
        $this->assertStringContainsString('FWSSR' . $c->receipt_id, $out->narration);
        $this->assertEquals(1297, $out->balance);

        $this->get(route('banks.index'))->assertOk()->assertSee('MoMo charge')->assertSee('MoMo withdrawal charge slip S-7');
    }

    public function test_cash_and_momo_on_one_slip(): void
    {
        $a = $this->receive(cash: 500, momo: 200);
        $b = $this->receive(momo: 100);
        $this->deposit(['cash' => [$a->id], 'momo' => [$a->id, $b->id], 'momo_charge' => '4.50'])->assertSessionHasNoErrors();

        $d = BankDeposit::latest('id')->first();
        $this->assertEquals(500, $d->cash_amount);
        $this->assertEquals(300, $d->momo_amount);
        $this->assertEquals(795.5, $d->total);
        $this->assertEquals(1795.5, $this->bank->fresh()->total);
        $this->assertSame(['Deposited', 'Deposited'], [$a->fresh()->status, $b->fresh()->status]);
        $this->assertStringContainsString('cash 500.00 + MoMo 300.00', BankTransaction::where('deposit_id', $d->id)->value('narration'));
    }

    public function test_cash_first_then_momo_later_and_receipt_is_locked_once_any_part_is_banked(): void
    {
        $c = $this->receive(cash: 400, momo: 150);
        $this->deposit(['cash' => [$c->id]])->assertSessionHasNoErrors();
        $this->assertSame('Part deposited', $c->fresh()->status);

        $this->get(route('deposit.create'))->assertOk()
            ->assertViewHas('waiting', fn ($w) => $w->firstWhere('id', $c->id)?->cash_due == 0 && $w->firstWhere('id', $c->id)?->momo_due == 150);

        $this->delete('/receipt/' . $c->receipt_id, ['void_reason' => 'Entered in error'])->assertSessionHasErrors('void_reason');
        $this->assertNotNull(Receipt::find($c->receipt_id));

        $this->deposit(['momo' => [$c->id], 'momo_charge' => '0'])->assertSessionHasNoErrors();
        $this->assertSame('Deposited', $c->fresh()->status);
        $this->assertEquals(1550, $this->bank->fresh()->total);
        $this->assertSame(0, BankTransaction::where('channel', 'momo_charge')->count());   // charge 0: no debit line
    }

    public function test_cheque_plus_momo_receipt_leaves_the_momo_waiting(): void
    {
        $c = $this->receive(momo: 120, extra: [
            'mode' => ['cheque', 'momo'], 'cheque_amount' => 400, 'cheque_reference' => 'C9', 'cheque_bank' => 'Stanbic', 'cheque_to_bank_id' => $this->bank->id,
        ]);
        $this->assertSame('undeposited', $c->status);   // was "Banked" before: the MoMo was lost from view
        $this->assertEquals(1400, $this->bank->fresh()->total);
        $this->get(route('deposit.create'))->assertViewHas('waiting', fn ($w) => $w->firstWhere('id', $c->id)?->momo_due == 120 && $w->firstWhere('id', $c->id)?->cash_due == 0);
    }

    public function test_single_invoice_edit_is_refused_after_a_part_deposit(): void
    {
        $invoice = Invoice::create([
            'client_id' => $this->client->id, 'total' => 1000, 'sub_amount' => 1000, 'sub_total' => 0,
            'invoice_month' => '2026-10-01', 'due_date' => now()->addDays(10), 'status' => 'unpaid', 'balance' => 0,
        ]);
        $this->post('/receipt', ['invoice_id' => $invoice->id, 'client_id' => $this->client->id, 'from' => 'Kofi', 'receipt_month' => now()->toDateString(),
            'mode' => ['cash', 'momo'], 'cash_amount' => 100, 'momo_amount' => 50, 'momo_transactin_id' => 'MP1'])->assertSessionHasNoErrors();
        $receipt = Receipt::latest('id')->first();
        $c = Collection::where('receipt_id', $receipt->id)->first();
        $this->deposit(['momo' => [$c->id], 'momo_charge' => '0.50'])->assertSessionHasNoErrors();

        $this->put('/receipt/' . $receipt->id, ['invoice_id' => $invoice->id, 'client_id' => $this->client->id, 'from' => 'Kofi', 'receipt_month' => now()->toDateString(),
            'mode' => ['cash'], 'cash_amount' => 900])->assertSessionHas('error');
        $this->assertEquals(100, $receipt->fresh()->cash_amount);
    }

    public function test_charge_rules(): void
    {
        $c = $this->receive(cash: 100, momo: 50);
        $this->deposit(['momo' => [$c->id]])->assertSessionHasErrors('momo_charge');                         // not entered
        $this->deposit(['momo' => [$c->id], 'momo_charge' => ''])->assertSessionHasErrors('momo_charge');    // blank
        $this->deposit(['momo' => [$c->id], 'momo_charge' => '50'])->assertSessionHasErrors('momo_charge');  // the whole lot
        $this->deposit(['cash' => [$c->id], 'momo_charge' => '2'])->assertSessionHasErrors('momo_charge');   // no MoMo ticked
        $this->deposit([])->assertSessionHasErrors('cash');
        $this->assertSame(0, BankDeposit::count());
        $this->assertEquals(1000, $this->bank->fresh()->total);
    }

    public function test_the_same_money_cannot_be_deposited_twice(): void
    {
        $c = $this->receive(cash: 500);
        $this->deposit(['cash' => [$c->id]])->assertSessionHasNoErrors();
        $this->deposit(['cash' => [$c->id]])->assertSessionHasErrors('cash');
        $this->assertSame(1, BankDeposit::count());
        $this->assertEquals(1500, $this->bank->fresh()->total);
    }

    public function test_reversing_a_deposit_posts_both_lines_back_and_frees_the_money(): void
    {
        $c = $this->receive(cash: 200, momo: 100);
        $this->deposit(['cash' => [$c->id], 'momo' => [$c->id], 'momo_charge' => '2'])->assertSessionHasNoErrors();
        $d = BankDeposit::latest('id')->first();
        $this->assertEquals(1298, $this->bank->fresh()->total);

        $this->post(route('deposit.reverse', $d), ['reversal_reason' => 'Wrong bank account'])->assertSessionHasNoErrors();

        $this->assertEquals(1000, $this->bank->fresh()->total);
        $this->assertSame(4, BankTransaction::where('deposit_id', $d->id)->count());
        $this->assertNotNull($d->fresh()->reversed_at);
        $fresh = $c->fresh();
        $this->assertSame('undeposited', $fresh->status);
        $this->assertNull($fresh->cash_deposit_id);
        $this->assertNull($fresh->momo_deposit_id);

        $this->post(route('deposit.reverse', $d), ['reversal_reason' => 'Again please'])->assertSessionHasErrors('reversal_reason');
        $this->assertEquals(1000, $this->bank->fresh()->total);

        $this->get(route('deposit.show', $d))->assertOk()->assertSee('reversed')->assertSee('Wrong bank account');
        $this->get(route('deposit.index'))->assertOk()->assertSee('reversed');
    }

    public function test_mark_already_banked_closes_old_collections_without_touching_banks(): void
    {
        $old = Collection::create(['momo_amount' => 80, 'total_amount' => 80, 'status' => 'undeposited']);
        $this->post(route('deposit.markBanked'), ['collections' => [$old->id], 'banked_reason' => 'Withdrawn and banked by hand, Aug 2026'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame('Deposited', $old->fresh()->status);
        $this->assertStringContainsString('banked by hand', $old->fresh()->deposit_note);
        $this->assertSame(0, BankTransaction::count());
        $this->assertEquals(1000, $this->bank->fresh()->total);
        $this->get(route('deposit.create'))->assertViewHas('waiting', fn ($w) => ! $w->contains('id', $old->id));
    }

    public function test_old_deposited_collections_do_not_reappear(): void
    {
        $old = Collection::create(['cash_amount' => 50, 'momo_amount' => 70, 'total_amount' => 120, 'status' => 'Deposited']);
        $this->get(route('deposit.create'))->assertOk()->assertViewHas('waiting', fn ($w) => ! $w->contains('id', $old->id));
    }

    public function test_branch_staff_see_their_office_only_and_cannot_deposit(): void
    {
        $tema = Field::create(['name' => 'Tema', 'user_id' => $this->user->id, 'number' => '2']);
        $mine = Collection::create(['cash_amount' => 60, 'total_amount' => 60, 'status' => 'undeposited', 'field_id' => $this->field->id]);
        $theirs = Collection::create(['cash_amount' => 90, 'total_amount' => 90, 'status' => 'undeposited', 'field_id' => $tema->id]);

        $role = Role::create(['name' => 'Manager', 'department_id' => $this->user->department_id]);
        $branch = User::factory()->create(['department_id' => $this->user->department_id, 'role_id' => $role->id, 'field_id' => $this->field->id]);
        $this->actingAs($branch);

        $this->get(route('deposit.create'))->assertOk()
            ->assertViewHas('waiting', fn ($w) => $w->contains('id', $mine->id) && ! $w->contains('id', $theirs->id))
            ->assertDontSee('name="cash[]"', false);
        $this->get(route('deposit.create', ['field' => $tema->id]))->assertForbidden();
        $this->deposit(['cash' => [$mine->id]])->assertForbidden();
        $this->get(route('deposit.index'))->assertForbidden();
        $this->assertSame(0, BankDeposit::count());
    }

    public function test_pages_render(): void
    {
        $c = $this->receive(cash: 120, momo: 80);
        $this->get(route('deposit.create'))->assertOk()
            ->assertSee('Deposit slip')->assertSee('MoMo withdrawal charge')->assertSee('FWSSR' . $c->receipt_id)
            ->assertSee('name="cash[]"', false)->assertSee('name="momo[]"', false);

        $this->deposit(['cash' => [$c->id], 'momo' => [$c->id], 'momo_charge' => '1.60', 'slip_number' => 'GCB-55'])
            ->assertRedirect(route('deposit.show', BankDeposit::max('id') ?? 1));
        $d = BankDeposit::latest('id')->first();
        $this->get(route('deposit.show', $d))->assertOk()->assertSee('slip GCB-55')->assertSee('198.40')->assertSee('(1.60)');
        $this->get(route('deposit.index'))->assertOk()->assertSee('GCB-55')->assertSee('198.40')->assertSee('MoMo withdrawal charges');
    }
}

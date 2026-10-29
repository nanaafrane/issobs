<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\Client;
use App\Models\Collection;
use App\Models\Department;
use App\Models\Field;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\ReceiptAllocation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A receipt is edited on the same screen as a new one: everything it saved is shown
 * as saved, invoices can be changed, and multi-invoice / advance / credit-applied
 * receipts can be edited too.
 */
class ReceiptEditScreenTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Field $field;
    private Client $client;
    private Bank $bank;
    private Bank $bank2;

    protected function setUp(): void
    {
        parent::setUp();
        $department = Department::create(['name' => 'Finance']);
        $role = Role::forceCreate(['id' => 2, 'name' => 'Finance Manager', 'department_id' => $department->id]);
        $this->user = User::factory()->create(['department_id' => $department->id, 'role_id' => $role->id]);
        $this->field = Field::create(['name' => 'Accra', 'user_id' => $this->user->id, 'number' => '1']);
        $this->client = Client::create(['name' => 'Kofi Mensah', 'business_name' => 'Alpha Security', 'phone_number' => '0240000000', 'field_id' => $this->field->id]);
        $this->bank = Bank::create(['name' => 'GCB Main', 'branch' => 'Accra', 'acc_number' => '1', 'total' => 0]);
        $this->bank2 = Bank::create(['name' => 'Ecobank Ops', 'branch' => 'Tema', 'acc_number' => '2', 'total' => 0]);
        foreach (['cash', 'cheque', 'transfer', 'momo', 'other payments'] as $m) {
            DB::table('receipt_mode')->insert(['name' => $m]);
        }
        $this->actingAs($this->user);
    }

    private function invoice(float $total, string $month = '2026-09-01'): Invoice
    {
        return Invoice::create([
            'client_id' => $this->client->id, 'total' => $total, 'sub_amount' => $total, 'sub_total' => 0,
            'invoice_month' => $month, 'due_date' => now()->addDays(10), 'status' => 'unpaid', 'balance' => 0,
        ]);
    }

    private function create(array $invoices, array $extra = []): Receipt
    {
        $this->post(route('receipt.multi.store'), array_merge([
            'client_id' => $this->client->id, 'receipt_month' => '2026-09-05', 'from' => 'Kofi Mensah',
            'mode' => ['cash'], 'cash_amount' => 500, 'invoices' => $invoices, 'confirm_duplicate' => '1',
        ], $extra))->assertSessionHasNoErrors();

        return Receipt::latest('id')->first();
    }

    private function save(Receipt $r, array $invoices, array $extra = [])
    {
        return $this->put(route('receipt.update', $r), array_merge([
            'receipt_month' => '2026-09-05', 'from' => 'Kofi Mensah', 'mode' => ['cash'], 'cash_amount' => 500, 'invoices' => $invoices,
        ], $extra));
    }

    public function test_edit_opens_the_create_screen_with_everything_as_saved(): void
    {
        $inv = $this->invoice(1000);
        $r = $this->create([$inv->id => ['selected' => '1', 'applied' => 600, 'wht' => '37.50']], [
            'mode' => ['cash', 'cheque'], 'cash_amount' => 100, 'cheque_amount' => 500, 'cheque_reference' => 'CHQ-9',
            'cheque_bank' => 'Stanbic', 'cheque_to_bank_id' => $this->bank->id, 'receipt_month' => '2026-09-07', 'from' => 'Ama (accounts)',
        ]);

        $this->get(route('receipt.edit', $r))->assertRedirect(route('receipt.multi.edit', $r));
        $page = $this->get(route('receipt.multi.edit', $r))->assertOk()
            ->assertSee('Save changes to FWSSR' . $r->id)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertDontSee('Choose the client who is paying')          // client is fixed
            ->assertSee('value="37.50"', false)                        // the WHT that was saved, not a standard rate
            ->assertSee('value="600.00"', false)
            ->assertSee('value="CHQ-9"', false)->assertSee('value="Stanbic"', false)->assertSee('value="Ama (accounts)"', false)
            ->assertSee('value="2026-09-07"', false)
            ->assertSee('data-outstanding="1000"', false);             // outstanding without this receipt
        $html = $page->getContent();
        $this->assertMatchesRegularExpression('/id="mode_cash"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/id="mode_cheque"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/<option value="' . $this->bank->id . '"\s+selected/', $html);
        $this->assertMatchesRegularExpression('/name="invoices\[' . $inv->id . '\]\[selected\]" value="1" checked/', $html);
    }

    public function test_wht_and_vat_switches_are_on_when_the_receipt_has_them(): void
    {
        $a = $this->invoice(1000, '2026-08-01');
        $b = $this->invoice(1000, '2026-09-01');
        $withWht = $this->create([$a->id => ['selected' => '1', 'wht' => '75']], ['cash_amount' => 925]);
        $html = $this->get(route('receipt.multi.edit', $withWht))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/id="applyWht"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="applyVat"[^>]*checked/', $html);

        $withVat = $this->create([$b->id => ['selected' => '1', 'vat7' => '70']], ['cash_amount' => 500, 'from' => 'Other payer']);
        $html = $this->get(route('receipt.multi.edit', $withVat))->getContent();
        $this->assertMatchesRegularExpression('/id="applyVat"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="applyWht"[^>]*checked/', $html);

        // A new receipt starts with both off; after a validation error they stay as the cashier left them.
        $html = $this->get(route('receipt.multi.create', ['client_id' => $this->client->id]))->getContent();
        $this->assertDoesNotMatchRegularExpression('/id="applyWht"[^>]*checked/', $html);
        $this->from(route('receipt.multi.create', ['client_id' => $this->client->id]))
            ->post(route('receipt.multi.store'), ['client_id' => $this->client->id, 'receipt_month' => '2026-09-05', 'from' => 'X', 'mode' => ['cash'], 'apply_wht' => '1', 'invoices' => []])
            ->assertSessionHasErrors();
        $html = $this->get(route('receipt.multi.create', ['client_id' => $this->client->id]))->getContent();
        $this->assertMatchesRegularExpression('/id="applyWht"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="applyVat"[^>]*checked/', $html);
    }

    public function test_saving_unchanged_keeps_every_figure(): void
    {
        $inv = $this->invoice(1000);
        $r = $this->create([$inv->id => ['selected' => '1', 'wht' => '37.50']], ['cash_amount' => 462.5]);
        $this->save($r, [$inv->id => ['selected' => '1', 'applied' => '462.50', 'wht' => '37.50']], ['cash_amount' => '462.50'])->assertSessionHasNoErrors();

        $fresh = $r->fresh();
        $this->assertEquals(37.5, $fresh->wht_amount);
        $this->assertEquals(500, $inv->fresh()->balance);
        $this->assertSame(1, ReceiptAllocation::where('receipt_id', $r->id)->count());
    }

    public function test_money_can_be_moved_to_other_invoices(): void
    {
        $a = $this->invoice(300, '2026-08-01');
        $b = $this->invoice(400, '2026-09-01');
        $r = $this->create([$a->id => ['selected' => '1']], ['cash_amount' => 300]);
        $this->assertSame('completed', $a->fresh()->status);

        // The paid invoice still shows on the edit screen (it is on this receipt), with the other open one.
        $this->get(route('receipt.multi.edit', $r))->assertSee('FWSSi' . $a->id)->assertSee('FWSSi' . $b->id)->assertSee('on this receipt');

        $this->save($r, [$a->id => ['selected' => '1', 'applied' => 100], $b->id => ['selected' => '1', 'applied' => 200]], ['cash_amount' => 300])->assertSessionHasNoErrors();
        $this->assertSame('uncompleted', $a->fresh()->status);
        $this->assertEquals(200, $a->fresh()->balance);
        $this->assertEquals(200, $b->fresh()->balance);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], ReceiptAllocation::where('receipt_id', $r->id)->pluck('invoice_id')->all());

        // Pay only B now, the rest kept as credit.
        $this->save($r, [$b->id => ['selected' => '1']], ['cash_amount' => 500])->assertSessionHasErrors('invoices');   // 100 extra, not confirmed as credit
        $this->save($r, [$b->id => ['selected' => '1']], ['cash_amount' => 500, 'keep_credit' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('unpaid', $a->fresh()->status);
        $this->assertSame('completed', $b->fresh()->status);
        $this->assertEquals(100, $r->fresh()->unapplied_amount);
        $this->assertEquals(500, Collection::where('receipt_id', $r->id)->value('cash_amount'));
    }

    public function test_changing_the_bank_or_mode_reposts_the_bank_lines(): void
    {
        $inv = $this->invoice(1000);
        $cheque = ['mode' => ['cheque'], 'cheque_amount' => 400, 'cheque_reference' => 'C1', 'cheque_bank' => 'GCB', 'cheque_to_bank_id' => $this->bank->id];
        $r = $this->create([$inv->id => ['selected' => '1']], $cheque);
        $this->assertEquals(400, $this->bank->fresh()->total);

        $this->save($r, [$inv->id => ['selected' => '1']], ['cheque_to_bank_id' => $this->bank2->id] + $cheque)->assertSessionHasNoErrors();
        $this->assertEquals(0, $this->bank->fresh()->total);
        $this->assertEquals(400, $this->bank2->fresh()->total);

        $this->save($r, [$inv->id => ['selected' => '1']], ['mode' => ['cash'], 'cash_amount' => 400])->assertSessionHasNoErrors();
        $this->assertEquals(0, $this->bank2->fresh()->total);
        $this->assertNull($r->fresh()->cheque_amount);
        $this->assertSame(4, BankTransaction::where('receipt_id', $r->id)->count());   // posted, reversed, posted, reversed
    }

    public function test_advance_receipt_with_credit_already_applied_can_still_be_edited(): void
    {
        $r = $this->create([], ['cash_amount' => 900, 'keep_credit' => '1']);
        $inv = $this->invoice(600, '2026-10-01');
        $this->post(route('receipt.applyCredit', $r), ['invoices' => [$inv->id => ['selected' => '1']]])->assertSessionHasNoErrors();
        $this->assertEquals(300, $r->fresh()->unapplied_amount);

        $this->get(route('receipt.multi.edit', $r))->assertOk()->assertSee('Credit already applied to other invoices')->assertSee('600.00');

        // More money came in on the same receipt: the applied credit stays, the rest is credit.
        $this->save($r, [], ['cash_amount' => 1000, 'keep_credit' => '1'])->assertSessionHasNoErrors();
        $fresh = $r->fresh();
        $this->assertEquals(400, $fresh->unapplied_amount);
        $this->assertSame('completed', $inv->fresh()->status);
        $this->assertSame(1, ReceiptAllocation::where('receipt_id', $r->id)->where('source', 'credit')->count());

        // Cannot drop below what is already used.
        $this->save($r, [], ['cash_amount' => 500, 'keep_credit' => '1'])->assertSessionHasErrors('invoices');
        $this->assertEquals(1000, $r->fresh()->cash_amount);

        // Exactly the credit used: nothing left over, no "keep credit" needed.
        $this->save($r, [], ['cash_amount' => 600])->assertSessionHasNoErrors();
        $this->assertEquals(0, $r->fresh()->unapplied_amount);
        $this->assertSame('completed', $r->fresh()->status);
    }

    public function test_deposited_receipts_and_other_offices_cannot_be_edited(): void
    {
        $inv = $this->invoice(1000);
        $r = $this->create([$inv->id => ['selected' => '1']]);

        $tema = Field::create(['name' => 'Tema', 'user_id' => $this->user->id, 'number' => '2']);
        $role = Role::create(['name' => 'Manager', 'department_id' => $this->user->department_id]);
        $branch = User::factory()->create(['department_id' => $this->user->department_id, 'role_id' => $role->id, 'field_id' => $tema->id]);
        $this->actingAs($branch);
        $this->get(route('receipt.multi.edit', $r))->assertNotFound();
        $this->save($r, [$inv->id => ['selected' => '1']], ['cash_amount' => 10])->assertForbidden();

        $this->actingAs($this->user);
        $c = Collection::where('receipt_id', $r->id)->first();
        $this->post('/deposit', ['cash' => [$c->id], 'bank_id' => $this->bank->id, 'deposit_date' => now()->toDateString()])->assertSessionHasNoErrors();
        $this->get(route('receipt.multi.edit', $r))->assertRedirect(route('receipt.show', $r))->assertSessionHas('error');
        $this->save($r, [$inv->id => ['selected' => '1']], ['cash_amount' => 10])->assertSessionHas('error');
        $this->assertEquals(500, $r->fresh()->cash_amount);
    }
}

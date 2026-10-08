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
 * Single-invoice receipts: balances are derived from what was paid, never
 * from the status the cashier picks; cheques and transfers go straight into
 * the chosen bank. Each test is one of the failures the old code produced.
 */
class ReceiptBalanceAndBankTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Client $client;
    private Bank $bank;

    protected function setUp(): void
    {
        parent::setUp();
        $department = Department::create(['name' => 'Finance']);
        $role = Role::forceCreate(['id' => 2, 'name' => 'Finance Manager', 'department_id' => $department->id]); // approval code keys on role id 2
        $this->user = User::factory()->create(['department_id' => $department->id, 'role_id' => $role->id]);
        $field = Field::create(['name' => 'Accra', 'user_id' => $this->user->id, 'number' => '0200000000']);
        $this->client = Client::create(['name' => 'Kofi Mensah', 'business_name' => 'Alpha Security', 'field_id' => $field->id]);
        $this->bank = Bank::create(['name' => 'GCB Main', 'branch' => 'Accra', 'acc_number' => '1234567', 'total' => 1000]);
        foreach (['cash', 'cheque', 'transfer', 'momo', 'other payments'] as $m) {
            DB::table('receipt_mode')->insert(['name' => $m]);
        }
        $this->actingAs($this->user);
    }

    private function invoice(float $total = 1000): Invoice
    {
        return Invoice::create([
            'client_id' => $this->client->id, 'total' => $total, 'sub_amount' => $total, 'sub_total' => 0,
            'invoice_month' => '2026-09-01', 'due_date' => now()->addDays(10), 'status' => 'unpaid', 'balance' => 0,
        ]);
    }

    private function cash(Invoice $invoice, float $amount, array $extra = [])
    {
        return $this->post('/receipt', array_merge([
            'invoice_id' => $invoice->id, 'client_id' => $this->client->id, 'from' => 'Kofi',
            'receipt_month' => '2026-09-03', 'mode' => ['cash'], 'cash_amount' => $amount,
        ], $extra));
    }

    private function edit(Receipt $receipt, array $fields)
    {
        return $this->put('/receipt/' . $receipt->id, array_merge([
            'invoice_id' => $receipt->invoice_id, 'client_id' => $this->client->id, 'from' => 'Kofi',
            'receipt_month' => '2026-09-03', 'mode' => ['cash'],
        ], $fields));
    }

    private function assertInvoice(Invoice $invoice, string $status, float $balance): void
    {
        $fresh = $invoice->fresh();
        $this->assertSame($status, $fresh->status, 'status');
        $this->assertEquals($balance, (float) $fresh->balance, 'balance');
    }

    /* ---------------- balances ---------------- */

    public function test_status_comes_from_the_amount_not_the_form(): void
    {
        $inv = $this->invoice();
        $this->cash($inv, 700, ['status' => 'completed'])->assertSessionHasNoErrors();   // old code closed it with 300 owed
        $this->assertInvoice($inv, 'uncompleted', 300);
        $this->assertSame('uncompleted', Receipt::latest('id')->first()->status);

        $this->cash($inv, 300, ['status' => 'uncompleted'])->assertSessionHasNoErrors(); // old code left it open
        $this->assertInvoice($inv, 'completed', 0);
        $this->assertSame('completed', Receipt::latest('id')->first()->status);
    }

    public function test_editing_a_part_payment_does_not_subtract_twice(): void
    {
        $inv = $this->invoice();
        $this->cash($inv, 400);
        $receipt = Receipt::latest('id')->first();
        $this->assertInvoice($inv, 'uncompleted', 600);

        $this->edit($receipt, ['cash_amount' => 450])->assertSessionHasNoErrors();   // old code: 150
        $this->assertInvoice($inv, 'uncompleted', 550);

        $this->edit($receipt, ['cash_amount' => 450])->assertSessionHasNoErrors();   // re-save unchanged
        $this->assertInvoice($inv, 'uncompleted', 550);
        $this->assertSame(1, ReceiptAllocation::where('receipt_id', $receipt->id)->count());
    }

    public function test_resaving_a_completed_receipt_keeps_invoice_paid_and_wht_total(): void
    {
        $inv = $this->invoice();
        $wht = ['wth' => 'on', 'wht_amount' => 37.5];
        $this->cash($inv, 462.5, $wht);
        $first = Receipt::latest('id')->first();
        $this->cash($inv, 462.5, $wht);
        $this->assertInvoice($inv, 'completed', 0);
        $this->assertEquals(75, $inv->fresh()->wht_amount);

        $this->edit($first, ['cash_amount' => 462.5] + $wht)->assertSessionHasNoErrors(); // old code reopened at 500, WHT 37.5
        $this->assertInvoice($inv, 'completed', 0);
        $this->assertEquals(75, $inv->fresh()->wht_amount);
    }

    public function test_editing_keeps_the_cheque_image_and_original_cashier(): void
    {
        $inv = $this->invoice();
        $this->cash($inv, 200);
        $receipt = Receipt::latest('id')->first();
        DB::table('receipts')->where('id', $receipt->id)->update(['image' => 'images/chq.png']);
        $other = User::factory()->create(['role_id' => $this->user->role_id, 'department_id' => $this->user->department_id]);

        $this->actingAs($other);
        $this->edit($receipt, ['cash_amount' => 250])->assertSessionHasNoErrors();

        $this->assertSame('images/chq.png', $receipt->fresh()->image);
        $this->assertSame($this->user->id, (int) $receipt->fresh()->user_id);
    }

    public function test_overpayment_is_refused_unless_kept_as_credit(): void
    {
        $inv = $this->invoice();
        $this->cash($inv, 400);
        $this->cash($inv, 800, ['status' => 'completed'])->assertSessionHasErrors('invoices'); // old code: balance -200
        $this->assertInvoice($inv, 'uncompleted', 600);

        $this->cash($inv, 800, ['keep_credit' => '1'])->assertSessionHasNoErrors();
        $receipt = Receipt::latest('id')->first();
        $this->assertInvoice($inv, 'completed', 0);
        $this->assertEquals(200, $receipt->unapplied_amount);
        $this->assertSame('advance', $receipt->advance_payment);
        $this->assertEquals(600, ReceiptAllocation::where('receipt_id', $receipt->id)->value('settled'));
    }

    public function test_cannot_pay_an_invoice_that_is_already_paid(): void
    {
        $inv = $this->invoice();
        $this->cash($inv, 1000);
        $this->cash($inv, 200)->assertSessionHasErrors('invoice_id');
        $this->assertSame(1, Receipt::count());
    }

    public function test_advance_flag_is_automatic_for_payment_before_invoice_month(): void
    {
        $inv = $this->invoice();
        $this->cash($inv, 300, ['receipt_month' => '2026-08-20', 'advance_payment' => null])->assertSessionHasNoErrors();
        $this->assertSame('advance', Receipt::latest('id')->first()->advance_payment);

        $this->cash($inv, 300, ['receipt_month' => '2026-09-10', 'advance_payment' => 'advance'])->assertSessionHasNoErrors();
        $this->assertNull(Receipt::latest('id')->first()->advance_payment);   // the old tick box is ignored
    }

    public function test_deleting_one_part_payment_keeps_the_other(): void
    {
        $inv = $this->invoice();
        $this->cash($inv, 300);
        $first = Receipt::latest('id')->first();
        $this->cash($inv, 300);
        $this->delete('/receipt/' . $first->id);
        $this->assertInvoice($inv, 'uncompleted', 700);
    }

    /* ---------------- banks ---------------- */

    public function test_cheque_goes_straight_to_the_bank_not_bank_deposit(): void
    {
        $inv = $this->invoice();
        $this->cash($inv, 0, [
            'mode' => ['cheque', 'cash'], 'cash_amount' => 200,
            'cheque_amount' => 800, 'cheque_reference' => 'CHQ-9', 'cheque_bank' => 'Stanbic', 'cheque_to_bank_id' => $this->bank->id,
        ])->assertSessionHasNoErrors();
        $receipt = Receipt::latest('id')->first();

        $this->assertEquals(1800, $this->bank->fresh()->total);
        $line = BankTransaction::where('receipt_id', $receipt->id)->first();
        $this->assertSame('cheque', $line->channel);
        $this->assertEquals(800, $line->credit);

        // Only the cash is left for Bank Deposit.
        $collection = Collection::where('receipt_id', $receipt->id)->first();
        $this->get('/deposit/create')->assertOk()->assertSee('200.00');
        $this->post('/deposit', ['collections' => [$collection->id], 'bank_id' => [$collection->id => $this->bank->id]])
            ->assertSessionHas('success');
        $this->assertEquals(2000, $this->bank->fresh()->total);   // 1000 + 800 cheque + 200 cash, cheque not counted twice
    }

    public function test_cheque_only_receipt_needs_no_deposit(): void
    {
        $inv = $this->invoice();
        $this->cash($inv, 0, [
            'mode' => ['cheque'], 'cheque_amount' => 500, 'cheque_reference' => 'C1', 'cheque_bank' => 'GCB', 'cheque_to_bank_id' => $this->bank->id,
        ])->assertSessionHasNoErrors();

        $collection = Collection::where('receipt_id', Receipt::latest('id')->value('id'))->first();
        $this->assertSame('Banked', $collection->status);
        $this->get('/deposit/create')->assertOk()
            ->assertViewHas('collections', fn ($list) => ! $list->contains('id', $collection->id));
    }

    public function test_editing_cheque_amount_or_bank_reposts_with_reversal(): void
    {
        $second = Bank::create(['name' => 'Ecobank Ops', 'branch' => 'Tema', 'acc_number' => '999', 'total' => 0]);
        $inv = $this->invoice();
        $cheque = ['mode' => ['cheque'], 'cheque_reference' => 'C1', 'cheque_bank' => 'GCB'];
        $this->cash($inv, 0, $cheque + ['cheque_amount' => 500, 'cheque_to_bank_id' => $this->bank->id]);
        $receipt = Receipt::latest('id')->first();

        $this->edit($receipt, $cheque + ['cheque_amount' => 600, 'cheque_to_bank_id' => $second->id])->assertSessionHasNoErrors();

        $this->assertEquals(1000, $this->bank->fresh()->total);   // 500 posted then reversed
        $this->assertEquals(600, $second->fresh()->total);
        $this->assertSame(3, BankTransaction::where('receipt_id', $receipt->id)->count());
        $this->assertInvoice($inv, 'uncompleted', 400);

        $this->delete('/receipt/' . $receipt->id);
        $this->assertEquals(0, $second->fresh()->total);
    }

    public function test_old_unbanked_cheque_is_still_deposited_through_bank_deposit(): void
    {
        // A collection from before this change: cheque never posted to any bank.
        $collection = Collection::create(['cash_amount' => 0, 'cheque_amount' => 350, 'total_amount' => 350, 'status' => 'undeposited']);
        $this->post('/deposit', ['collections' => [$collection->id], 'bank_id' => [$collection->id => $this->bank->id]])
            ->assertSessionHas('success');
        $this->assertEquals(1350, $this->bank->fresh()->total);
        $this->assertSame('deposit', BankTransaction::latest('id')->value('channel'));
    }
}

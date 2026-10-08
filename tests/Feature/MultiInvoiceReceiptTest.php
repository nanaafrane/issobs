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
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MultiInvoiceReceiptTest extends TestCase
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
        $this->client = Client::create([
            'name' => 'Kofi Mensah', 'business_name' => 'Alpha Security', 'phone_number' => '0240000000',
            'field_id' => $field->id, 'user_id' => $this->user->id,
        ]);
        $this->bank = Bank::create(['name' => 'GCB Main', 'branch' => 'Accra', 'acc_number' => '1234567', 'total' => 1000]);

        foreach (['cash', 'cheque', 'transfer', 'momo', 'other payments'] as $m) {
            DB::table('receipt_mode')->insert(['name' => $m]);
        }

        $this->actingAs($this->user);
    }

    private function invoice(float $total, string $month, string $status = 'unpaid', float $balance = 0): Invoice
    {
        return Invoice::create([
            'client_id' => $this->client->id, 'total' => $total, 'sub_amount' => $total, 'sub_total' => 0,
            'invoice_month' => $month, 'due_date' => now()->addDays(10), 'status' => $status,
            'balance' => $balance, 'user_id' => $this->user->id,
        ]);
    }

    /** An earlier part payment of 400 on a 1000 invoice, as the legacy flow would leave it. */
    private function partPaidInvoice(): Invoice
    {
        $inv = $this->invoice(1000, '2026-08-01', 'uncompleted', 600);
        $old = Receipt::create(['invoice_id' => $inv->id, 'client_id' => $this->client->id, 'total' => 400, 'cash_amount' => 400, 'status' => 'uncompleted', 'ho_status' => 'approved']);
        ReceiptAllocation::create(['receipt_id' => $old->id, 'invoice_id' => $inv->id, 'client_id' => $this->client->id, 'amount_applied' => 400, 'settled' => 400, 'source' => 'legacy']);

        return $inv;
    }

    private function payload(array $invoices, array $extra = []): array
    {
        return array_merge([
            'client_id' => $this->client->id,
            'receipt_month' => '2026-09-05',
            'from' => 'Kofi Mensah',
            'mode' => ['cash'],
            'cash_amount' => 1100,
            'invoices' => $invoices,
        ], $extra);
    }

    public function test_create_page_lists_open_invoices_with_outstanding_amounts(): void
    {
        $a = $this->partPaidInvoice();
        $b = $this->invoice(500, '2026-09-01');

        $this->get(route('receipt.multi.create', ['client_id' => $this->client->id]))
            ->assertOk()
            ->assertSee('FWSSi' . $a->id)
            ->assertSee('FWSSi' . $b->id)
            ->assertSee('600.00')
            ->assertSee('GCB Main');
    }

    public function test_one_receipt_pays_several_invoices(): void
    {
        $a = $this->partPaidInvoice();
        $b = $this->invoice(500, '2026-09-01');

        $this->post(route('receipt.multi.store'), $this->payload([
            $a->id => ['selected' => '1'],
            $b->id => ['selected' => '1'],
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $receipt = Receipt::latest('id')->first();
        $this->assertSame('completed', $receipt->status);
        $this->assertSame($a->id, $receipt->invoice_id);
        $this->assertEquals(1100, $receipt->total);
        $this->assertEquals(0, $receipt->unapplied_amount);
        $this->assertSame('approved', $receipt->ho_status);

        $this->assertEquals(600, ReceiptAllocation::where('receipt_id', $receipt->id)->where('invoice_id', $a->id)->value('settled'));
        $this->assertEquals(500, ReceiptAllocation::where('receipt_id', $receipt->id)->where('invoice_id', $b->id)->value('settled'));

        $this->assertSame('completed', $a->fresh()->status);
        $this->assertEquals(0, $a->fresh()->balance);
        $this->assertSame('completed', $b->fresh()->status);

        $this->assertSame(2, Transaction::where('receipt_id', $receipt->id)->count());
        $this->assertSame(1, Collection::where('receipt_id', $receipt->id)->count());

        // Receipt page and list show every invoice paid.
        $this->get(route('receipt.show', $receipt))->assertOk()
            ->assertSee('Invoices paid')->assertSee('FWSSi' . $a->id)->assertSee('FWSSi' . $b->id);
        $this->getJson(route('receipt.data'))->assertOk()
            ->assertJsonFragment(['invoice_id' => 'FWSSi' . $a->id . ' +1 more']);
        // The single-invoice edit form must not be used on it.
        $this->get(route('receipt.edit', $receipt))->assertRedirect(route('receipt.show', $receipt));
    }

    public function test_single_invoice_screens_render_with_bank_pickers(): void
    {
        $inv = $this->invoice(500, '2026-09-01');
        $this->get('/receiptCreate/' . $inv->id)->assertOk()
            ->assertSee('RECEIVED INTO (OUR BANK)', false)->assertSee('GCB Main');

        $receipt = Receipt::create(['invoice_id' => $inv->id, 'client_id' => $this->client->id, 'total' => 100, 'cash_amount' => 100, 'status' => 'uncompleted', 'mode' => ['cash']]);
        ReceiptAllocation::create(['receipt_id' => $receipt->id, 'invoice_id' => $inv->id, 'amount_applied' => 100, 'settled' => 100]);
        $this->get(route('receipt.edit', $receipt))->assertOk()->assertSee('PAID INTO (OUR BANK)', false);

        $this->get('/deposit/create')->assertOk();
    }

    public function test_payroll_invoice_status_sees_every_invoice_on_a_multi_receipt(): void
    {
        $a = $this->invoice(600, '2026-08-01');
        $b = $this->invoice(500, '2026-09-01');

        $this->post(route('receipt.multi.store'), $this->payload([
            $a->id => ['selected' => '1'],
            $b->id => ['selected' => '1'],
        ]))->assertSessionHasNoErrors();

        // The September invoice is not receipts.invoice_id, but its paid date must still be found.
        $rows = collect(\App\Support\ClientInvoiceStatus::invoices($this->client->id, \Carbon\Carbon::parse('2026-09-01'), 3, \Carbon\Carbon::parse('2026-09-30')));
        $sept = $rows->firstWhere('invoice_id', $b->id);
        $this->assertNotNull($sept);
        $this->assertSame('2026-09-05', $sept['paid_on']);
        $this->assertSame('2026-09-05', $rows->firstWhere('invoice_id', $a->id)['paid_on']);
    }

    public function test_short_payment_completes_oldest_and_part_pays_the_next(): void
    {
        $a = $this->partPaidInvoice();
        $b = $this->invoice(500, '2026-09-01');

        $this->post(route('receipt.multi.store'), $this->payload([
            $a->id => ['selected' => '1'],
            $b->id => ['selected' => '1'],
        ], ['cash_amount' => 800]))->assertSessionHasNoErrors();

        $this->assertSame('completed', $a->fresh()->status);
        $this->assertSame('uncompleted', $b->fresh()->status);
        $this->assertEquals(300, $b->fresh()->balance);
        $this->assertSame('uncompleted', Receipt::latest('id')->first()->status);
    }

    public function test_wht_per_invoice_is_recorded(): void
    {
        $b = $this->invoice(1000, '2026-09-01');

        $this->post(route('receipt.multi.store'), $this->payload([
            $b->id => ['selected' => '1', 'wht' => 75],
        ], ['cash_amount' => 925]))->assertSessionHasNoErrors();

        $receipt = Receipt::latest('id')->first();
        $this->assertEquals(75, $receipt->wht_amount);
        $this->assertEquals(925, $receipt->amount_received);
        $this->assertEquals(1000, $receipt->total);
        $this->assertSame('completed', $b->fresh()->status);
        $this->assertEquals(75, $b->fresh()->wht_amount);
    }

    public function test_overpayment_needs_explicit_credit_then_is_kept_as_advance(): void
    {
        $b = $this->invoice(500, '2026-09-01');

        $this->post(route('receipt.multi.store'), $this->payload([$b->id => ['selected' => '1']], ['cash_amount' => 700]))
            ->assertSessionHasErrors('invoices');
        $this->assertSame(0, Receipt::count());

        $this->post(route('receipt.multi.store'), $this->payload([$b->id => ['selected' => '1']], ['cash_amount' => 700, 'keep_credit' => '1']))
            ->assertSessionHasNoErrors();

        $receipt = Receipt::latest('id')->first();
        $this->assertEquals(200, $receipt->unapplied_amount);
        $this->assertSame('advance', $receipt->advance_payment);
        $this->assertSame('completed', $b->fresh()->status);
    }

    public function test_advance_without_invoice_then_apply_credit_later(): void
    {
        $this->post(route('receipt.multi.store'), $this->payload([], ['cash_amount' => 900, 'keep_credit' => '1']))
            ->assertSessionHasNoErrors();

        $receipt = Receipt::latest('id')->first();
        $this->assertNull($receipt->invoice_id);
        $this->assertEquals(900, $receipt->unapplied_amount);
        $this->get(route('receipt.show', $receipt))->assertOk()->assertSee('Apply credit');
        $this->get(route('receipt.edit', $receipt))->assertRedirect(route('receipt.show', $receipt));

        // Next month's invoice is raised; settle it from the credit.
        $inv = $this->invoice(600, '2026-10-01');
        $this->post(route('receipt.applyCredit', $receipt), ['invoices' => [$inv->id => ['selected' => '1']]])
            ->assertSessionHasNoErrors();

        $receipt->refresh();
        $this->assertEquals(300, $receipt->unapplied_amount);
        $this->assertSame($inv->id, $receipt->invoice_id);
        $this->assertSame('completed', $inv->fresh()->status);
        $this->assertSame('credit', ReceiptAllocation::where('receipt_id', $receipt->id)->value('source'));
        // No new money: still one collection for the receipt.
        $this->assertSame(1, Collection::where('receipt_id', $receipt->id)->count());
    }

    public function test_transfer_is_posted_to_the_chosen_bank_and_reversed_on_delete(): void
    {
        $a = $this->partPaidInvoice();
        $b = $this->invoice(500, '2026-09-01');

        $this->post(route('receipt.multi.store'), $this->payload([
            $a->id => ['selected' => '1'],
            $b->id => ['selected' => '1'],
        ], [
            'mode' => ['transfer'],
            'transfer_amount' => 1100, 'transfer_reference' => 'TRX-77', 'transfer_bank' => 'Ecobank',
            'transfer_to_bank_id' => $this->bank->id,
        ]))->assertSessionHasNoErrors();

        $receipt = Receipt::latest('id')->first();
        $this->assertSame($this->bank->id, (int) $receipt->transfer_to_bank_id);
        $this->assertEquals(2100, $this->bank->fresh()->total);
        $this->assertEquals(1100, BankTransaction::where('receipt_id', $receipt->id)->value('credit'));

        $this->delete(route('receipt.destroy', $receipt))->assertRedirect();

        $this->assertEquals(1000, $this->bank->fresh()->total);
        $this->assertSame(2, BankTransaction::where('receipt_id', $receipt->id)->count()); // credit + reversal kept
        // The earlier 400 part payment survives; the second invoice is unpaid again.
        $this->assertSame('uncompleted', $a->fresh()->status);
        $this->assertEquals(600, $a->fresh()->balance);
        $this->assertSame('unpaid', $b->fresh()->status);
        $this->assertSame(0, ReceiptAllocation::where('receipt_id', $receipt->id)->count());
    }

    public function test_transfer_requires_the_receiving_bank(): void
    {
        $b = $this->invoice(500, '2026-09-01');

        $this->post(route('receipt.multi.store'), $this->payload([$b->id => ['selected' => '1']], [
            'mode' => ['transfer'], 'transfer_amount' => 500, 'transfer_reference' => 'X', 'transfer_bank' => 'Ecobank',
        ]))->assertSessionHasErrors('transfer_to_bank_id');
    }

    public function test_cannot_pay_invoice_of_another_client(): void
    {
        $other = Client::create(['name' => 'Other', 'field_id' => $this->client->field_id]);
        $foreign = Invoice::create(['client_id' => $other->id, 'total' => 100, 'status' => 'unpaid', 'invoice_month' => '2026-09-01', 'due_date' => now()]);

        $this->post(route('receipt.multi.store'), $this->payload([$foreign->id => ['selected' => '1']], ['cash_amount' => 100]))
            ->assertSessionHasErrors('invoices');
        $this->assertSame('unpaid', $foreign->fresh()->status);
    }

    public function test_single_invoice_receipt_records_allocation_and_banks(): void
    {
        $inv = $this->invoice(500, '2026-09-01');

        $this->post('/receipt', [
            'invoice_id' => $inv->id, 'client_id' => $this->client->id, 'from' => 'Kofi', 'status' => 'completed',
            'receipt_month' => '2026-09-03', 'mode' => ['transfer'],
            'transfer_amount' => 500, 'transfer_reference' => 'T1', 'transfer_bank' => 'Ecobank',
            'transfer_to_bank_id' => $this->bank->id,
        ])->assertSessionHasNoErrors();

        $receipt = Receipt::latest('id')->first();
        $this->assertSame($this->bank->id, (int) $receipt->transfer_to_bank_id);
        $this->assertEquals(500, ReceiptAllocation::where('receipt_id', $receipt->id)->value('settled'));
        $this->assertEquals(1500, $this->bank->fresh()->total);
    }

    public function test_part_payment_keeps_cheque_and_transfer_details_in_the_right_columns(): void
    {
        $inv = $this->invoice(1000, '2026-09-01');

        $this->post('/receipt', [
            'invoice_id' => $inv->id, 'client_id' => $this->client->id, 'from' => 'Kofi', 'status' => 'uncompleted',
            'receipt_month' => '2026-09-03', 'mode' => ['cheque'],
            'cheque_amount' => 300, 'cheque_reference' => 'CHQ-1', 'cheque_bank' => 'Stanbic',
            'cheque_to_bank_id' => $this->bank->id,
        ])->assertSessionHasNoErrors();

        $receipt = Receipt::latest('id')->first();
        $this->assertEquals(300, $receipt->cheque_amount);
        $this->assertSame('CHQ-1', $receipt->cheque_reference);
        $this->assertNull($receipt->transfer_amount ? (float) $receipt->transfer_amount ?: null : null);
        $this->assertSame($this->bank->id, (int) $receipt->cheque_to_bank_id);
    }

    public function test_deposit_pairs_each_collection_with_its_own_bank(): void
    {
        $second = Bank::create(['name' => 'Ecobank Ops', 'branch' => 'Tema', 'acc_number' => '999', 'total' => 0]);
        $c1 = Collection::create(['cash_amount' => 100, 'total_amount' => 100, 'status' => 'undeposited']);
        $c2 = Collection::create(['cash_amount' => 250, 'total_amount' => 250, 'status' => 'undeposited']);

        // Only the second collection is ticked; it must go to the bank chosen on ITS row.
        $this->post('/deposit', [
            'collections' => [$c2->id],
            'bank_id' => [$c1->id => $this->bank->id, $c2->id => $second->id],
        ])->assertSessionHas('success');

        $this->assertEquals(250, $second->fresh()->total);
        $this->assertEquals(1000, $this->bank->fresh()->total);
        $this->assertSame('undeposited', $c1->fresh()->status);
    }
}

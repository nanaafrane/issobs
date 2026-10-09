<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\Client;
use App\Models\Department;
use App\Models\Field;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\ReceiptAllocation;
use App\Models\Role;
use App\Models\User;
use App\Services\Receipts\ClientStatement;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The statement of account counts every receipt once, credits WHT/VAT/deductions,
 * treats applying credit as a move (not new money), and shows credit as "CR".
 */
class ClientStatementTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;
    private Bank $bank;

    protected function setUp(): void
    {
        parent::setUp();
        $department = Department::create(['name' => 'Finance']);
        $role = Role::forceCreate(['id' => 2, 'name' => 'Finance Manager', 'department_id' => $department->id]); // approval code keys on role id 2
        $user = User::factory()->create(['department_id' => $department->id, 'role_id' => $role->id]);
        $field = Field::create(['name' => 'Accra', 'user_id' => $user->id, 'number' => '1']);
        $this->client = Client::create(['name' => 'Kofi Mensah', 'business_name' => 'Alpha Security', 'field_id' => $field->id]);
        $this->bank = Bank::create(['name' => 'GCB', 'branch' => 'Accra', 'acc_number' => '1', 'total' => 0]);
        foreach (['cash', 'cheque', 'transfer', 'momo', 'other payments'] as $m) {
            DB::table('receipt_mode')->insert(['name' => $m]);
        }
        $this->actingAs($user);
    }

    private function invoice(float $total, string $month): Invoice
    {
        return Invoice::create(['client_id' => $this->client->id, 'total' => $total, 'sub_amount' => $total, 'invoice_month' => $month, 'due_date' => now()->addDays(10), 'status' => 'unpaid', 'balance' => 0]);
    }

    private function pay(Invoice $invoice, array $fields): Receipt
    {
        $this->post('/receipt', array_merge(['invoice_id' => $invoice->id, 'client_id' => $this->client->id, 'from' => 'Kofi', 'mode' => ['cash']], $fields))
            ->assertSessionHasNoErrors();

        return Receipt::latest('id')->first();
    }

    private function statement(?string $from = null, ?string $to = null): array
    {
        return app(ClientStatement::class)->build($this->client->fresh(), $from ? Carbon::parse($from) : null, $to ? Carbon::parse($to) : null);
    }

    public function test_applied_credit_is_not_counted_as_new_money(): void
    {
        $aug = $this->invoice(1000, '2026-08-01');
        $sep = $this->invoice(500, '2026-09-01');
        $r1 = $this->pay($aug, ['receipt_month' => '2026-08-20', 'cash_amount' => 1300, 'keep_credit' => '1']);
        $this->post(route('receipt.applyCredit', $r1), ['invoices' => [$sep->id => ['selected' => '1', 'applied' => 300]]])->assertSessionHasNoErrors();
        $this->pay($sep, ['receipt_month' => '2026-09-15', 'mode' => ['transfer'], 'transfer_amount' => 200, 'transfer_reference' => 'T1', 'transfer_bank' => 'Eco', 'transfer_to_bank_id' => $this->bank->id]);

        // What is stored for the credit application is the credit, not the receipt amount.
        $this->assertEquals(300, ReceiptAllocation::where('receipt_id', $r1->id)->where('source', 'credit')->value('settled'));

        $st = $this->statement();
        $this->assertEquals(1500, $st['invoiced']);
        $this->assertEquals(1500, $st['received']);          // 1,300 once + 200 — the 300 credit is not added again
        $this->assertEquals(0, $st['closing']);
        $this->assertTrue($st['check']['ok']);
        $this->assertSame(1, collect($st['lines'])->where('kind', 'credit_applied')->count());
        $this->assertEquals(0, collect($st['lines'])->where('kind', 'credit_applied')->sum('credit'));
    }

    public function test_one_receipt_for_two_invoices_is_credited_once(): void
    {
        $a = $this->invoice(600, '2026-08-01');
        $b = $this->invoice(400, '2026-09-01');
        $this->post(route('receipt.multi.store'), [
            'client_id' => $this->client->id, 'receipt_month' => '2026-09-05', 'from' => 'K', 'mode' => ['cash'], 'cash_amount' => 1000,
            'invoices' => [$a->id => ['selected' => '1'], $b->id => ['selected' => '1']],
        ])->assertSessionHasNoErrors();

        $st = $this->statement();
        $this->assertSame(1, collect($st['lines'])->where('kind', 'receipt')->count());
        $this->assertEquals(0, $st['closing']);
    }

    public function test_vat_wht_and_deductions_are_credited(): void
    {
        $inv = $this->invoice(1000, '2026-10-01');
        $this->pay($inv, ['receipt_month' => '2026-10-05', 'cash_amount' => 835, 'wth' => 'on', 'wht_amount' => 75, 'vat' => 'on', 'vat7_value' => 70, 'deductions' => 'on', 'dAmount' => 20, 'description' => 'Late post']);

        $st = $this->statement();
        $this->assertEquals(835, $st['received']);
        $this->assertEquals(165, $st['withheld']);
        $this->assertEquals(0, $st['closing']);
        $this->assertEqualsCanonicalizing(['invoice', 'receipt', 'wht', 'vat', 'deduction'], collect($st['lines'])->pluck('kind')->all());
    }

    public function test_client_in_credit_shows_cr_and_advance_without_invoice_appears(): void
    {
        $this->post(route('receipt.multi.store'), ['client_id' => $this->client->id, 'receipt_month' => '2026-09-01', 'from' => 'K', 'mode' => ['cash'], 'cash_amount' => 500, 'keep_credit' => '1'])
            ->assertSessionHasNoErrors();

        $st = $this->statement();
        $this->assertEquals(-500, $st['closing']);
        $this->assertSame('500.00 CR', ClientStatement::money($st['closing']));
        $this->assertEquals(500, $st['credit_receipts']->sum('unapplied_amount'));

        $this->get('/clientStatement/' . $this->client->id)->assertOk()->assertSee('500.00 CR');
        $this->get('/client/' . $this->client->id)->assertOk()->assertSee('500.00 CR');
    }

    public function test_other_payments_receipt_no_longer_breaks_the_page(): void
    {
        $inv = $this->invoice(400, '2026-11-01');
        $this->pay($inv, ['receipt_month' => '2026-11-05', 'mode' => ['other payments'], 'other_payment_amnt' => 400, 'other_payment_descri' => 'Offset']);

        $this->get('/clientStatement/' . $this->client->id)->assertOk()->assertSee('Offset');
    }

    public function test_date_range_carries_an_opening_balance(): void
    {
        $aug = $this->invoice(1000, '2026-08-01');
        $this->pay($aug, ['receipt_month' => '2026-08-20', 'cash_amount' => 400]);
        $sep = $this->invoice(500, '2026-09-01');
        $this->pay($sep, ['receipt_month' => '2026-09-10', 'cash_amount' => 500]);

        $st = $this->statement('2026-09-01', '2026-09-30');
        $this->assertEquals(600, $st['opening']);
        $this->assertEquals(500, $st['invoiced']);
        $this->assertEquals(500, $st['received']);
        $this->assertEquals(600, $st['closing']);
        $this->assertCount(2, $st['lines']);

        $this->get('/clientStatement/' . $this->client->id . '?from=2026-09-01&to=2026-09-30')->assertOk()->assertSee('Opening balance');
        $this->get('/clientStatement/' . $this->client->id . '?from=2026-09-30&to=2026-09-01')->assertSessionHasErrors('to');
    }

    public function test_check_flags_old_inconsistent_data(): void
    {
        $inv = $this->invoice(1000, '2026-08-01');
        // An old receipt whose allocation line disagrees with its amount.
        $r = Receipt::create(['client_id' => $this->client->id, 'invoice_id' => $inv->id, 'total' => 400, 'cash_amount' => 400, 'receipt_month' => '2026-08-10']);
        ReceiptAllocation::create(['receipt_id' => $r->id, 'invoice_id' => $inv->id, 'client_id' => $this->client->id, 'amount_applied' => 300, 'settled' => 300, 'source' => 'legacy']);

        $st = $this->statement();
        $this->assertFalse($st['check']['ok']);
        $this->assertEquals(-100, $st['check']['difference']);
        $this->get('/clientStatement/' . $this->client->id)->assertOk()->assertSee('receipts:recompute');
    }
}

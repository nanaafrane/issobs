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
use App\Models\ReceiptVoid;
use App\Models\Role;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Receipts\ClientLedger;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * What happens when the connection drops, a button is pressed twice, or a
 * receipt is typed in again: nothing is ever saved twice or half-saved.
 */
class ReceiptSubmitSafetyTest extends TestCase
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
        $field = Field::create(['name' => 'Accra', 'user_id' => $this->user->id, 'number' => '1']);
        $this->client = Client::create(['name' => 'Kofi Mensah', 'business_name' => 'Alpha Security', 'field_id' => $field->id]);
        $this->bank = Bank::create(['name' => 'GCB', 'branch' => 'Accra', 'acc_number' => '1', 'total' => 0]);
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

    private function invoice(float $total = 1000): Invoice
    {
        return Invoice::create(['client_id' => $this->client->id, 'total' => $total, 'sub_amount' => $total, 'sub_total' => 0,
            'invoice_month' => '2026-09-01', 'due_date' => now()->addDays(10), 'status' => 'unpaid', 'balance' => 0]);
    }

    private function receiptForm(Invoice $invoice, float $amount, array $extra = []): array
    {
        return array_merge([
            'client_id' => $this->client->id, 'receipt_month' => '2026-09-05', 'from' => 'Kofi',
            'mode' => ['transfer'], 'transfer_amount' => $amount, 'transfer_reference' => 'TRX-' . $invoice->id . '-' . $amount,
            'transfer_bank' => 'Ecobank', 'transfer_to_bank_id' => $this->bank->id,
            'invoices' => [$invoice->id => ['selected' => '1']],
        ], $extra);
    }

    /* ---------------- the same form sent twice ---------------- */

    public function test_form_sent_again_after_saving_shows_the_first_receipt(): void
    {
        $inv = $this->invoice();
        $form = $this->receiptForm($inv, 400, ['submission_key' => 'form-aaaaaaaaaaaaaaaa']);

        $first = $this->post(route('receipt.multi.store'), $form);
        $receipt = Receipt::first();
        $first->assertRedirect(route('receipt.show', $receipt));

        // The reply was lost; the cashier presses Generate again with the same page.
        $this->post(route('receipt.multi.store'), $form)
            ->assertRedirect(route('receipt.show', $receipt))
            ->assertSessionHas('info', fn ($m) => str_contains($m, 'already saved'));

        $this->assertSame(1, Receipt::count());
        $this->assertSame(1, Collection::count());
        $this->assertSame(1, BankTransaction::count());
        $this->assertEquals(400, $this->bank->fresh()->total);
        $this->assertEquals(600, $inv->fresh()->balance);
    }

    public function test_second_copy_arriving_while_the_first_is_still_saving_saves_nothing(): void
    {
        $inv = $this->invoice();
        // The first copy has claimed the code but has not finished yet.
        DB::table('form_submissions')->insert(['submission_key' => 'form-bbbbbbbbbbbbbbbb', 'form' => 'receipt', 'created_at' => now(), 'updated_at' => now()]);

        $this->from(route('receipt.multi.create'))
            ->post(route('receipt.multi.store'), $this->receiptForm($inv, 400, ['submission_key' => 'form-bbbbbbbbbbbbbbbb']))
            ->assertSessionHas('info');

        $this->assertSame(0, Receipt::count());
        $this->assertEquals(0, $this->bank->fresh()->total);
    }

    public function test_a_save_that_fails_half_way_leaves_nothing_and_can_be_retried(): void
    {
        $inv = $this->invoice();
        $form = $this->receiptForm($inv, 400, ['submission_key' => 'form-cccccccccccccccc']);

        // The very last step fails (as if the server lost its database connection).
        $this->app->instance(ClientLedger::class, new class extends ClientLedger {
            public function sync(int $clientId, ?string $reason = null): int
            {
                throw new \RuntimeException('connection lost');
            }
        });
        $this->post(route('receipt.multi.store'), $form)->assertServerError();
        $this->assertSame(0, Receipt::count());
        $this->assertSame(0, ReceiptAllocation::count());
        $this->assertSame(0, Collection::count());
        $this->assertSame(0, BankTransaction::count());
        $this->assertSame(0, DB::table('form_submissions')->count());
        $this->assertSame('unpaid', $inv->fresh()->status);

        // Connection back: the same page can simply be sent again.
        $this->app->forgetInstance(ClientLedger::class);
        $this->post(route('receipt.multi.store'), $form)->assertSessionHasNoErrors();
        $this->assertSame(1, Receipt::count());
        $this->assertEquals(400, $this->bank->fresh()->total);
    }

    public function test_page_left_open_from_before_still_saves(): void
    {
        $inv = $this->invoice();
        $this->post(route('receipt.multi.store'), $this->receiptForm($inv, 300))->assertSessionHasNoErrors();   // no submission_key
        $this->assertSame(1, Receipt::count());
    }

    public function test_void_sent_twice_says_already_saved_not_not_found(): void
    {
        $inv = $this->invoice();
        $this->post(route('receipt.multi.store'), $this->receiptForm($inv, 400));
        $receipt = Receipt::first();
        $form = ['void_reason' => 'Entered against the wrong client', 'submission_key' => 'form-dddddddddddddddd'];

        $this->delete('/receipt/' . $receipt->id, $form)->assertRedirect('receipt');
        $this->delete('/receipt/' . $receipt->id, $form)->assertRedirect('receipt')->assertSessionHas('info');

        $this->assertSame(1, ReceiptVoid::count());
        $this->assertEquals(0, $this->bank->fresh()->total);
    }

    public function test_apply_credit_sent_twice_applies_it_once(): void
    {
        $this->post(route('receipt.multi.store'), ['client_id' => $this->client->id, 'receipt_month' => '2026-09-01', 'from' => 'K',
            'mode' => ['cash'], 'cash_amount' => 1000, 'keep_credit' => '1']);
        $credit = Receipt::first();
        $a = $this->invoice(300);
        $b = $this->invoice(300);
        $form = ['invoices' => [$a->id => ['selected' => '1'], $b->id => ['selected' => '1']], 'submission_key' => 'form-eeeeeeeeeeeeeeee'];

        $this->post(route('receipt.applyCredit', $credit), $form)->assertSessionHasNoErrors();
        $this->post(route('receipt.applyCredit', $credit), $form)->assertSessionHas('info');

        $this->assertEquals(400, $credit->fresh()->unapplied_amount);
        $this->assertSame(2, ReceiptAllocation::where('source', 'credit')->count());
    }

    /* ---------------- invoices ---------------- */

    public function test_invoice_form_sent_twice_creates_one_invoice(): void
    {
        $service = Service::create(['name' => 'Patrol', 'user_id' => $this->user->id]);
        $source = $this->invoice(100);
        $form = ['client_id' => $this->client->id, 'due_date' => '2026-11-15T09:00', 'invoice_month' => '2026-11',
            'service' => [$service->name], 'description' => ['Patrol'], 'quantity' => [1], 'unit_price' => [100], 'amount' => [100],
            'submission_key' => 'form-ffffffffffffffff'];

        $this->post(route('invoice.storeDuplicate', ['invoice' => $source->id]), $form)->assertRedirect();
        $this->post(route('invoice.storeDuplicate', ['invoice' => $source->id]), $form)->assertSessionHas('info');

        $this->assertSame(2, Invoice::count());   // the source + one new invoice
    }

    public function test_invoice_that_fails_half_way_is_not_left_behind(): void
    {
        $service = Service::create(['name' => 'Patrol', 'user_id' => $this->user->id]);
        $this->app->instance(ClientLedger::class, new class extends ClientLedger {
            public function sync(int $clientId, ?string $reason = null): int
            {
                throw new \RuntimeException('connection lost');
            }
        });

        $this->post('/invoice', ['client_id' => $this->client->id, 'due_date' => '2026-11-15T09:00', 'invoice_month' => '2026-11-01',
            'service' => [$service->name], 'description' => ['Patrol'], 'quantity' => [2], 'unit_price' => [50], 'amount' => [100]])
            ->assertServerError();

        // Before this change the invoice (with no total) and its lines stayed in the database.
        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, DB::table('invoice_data')->count());
        $this->assertSame(0, Transaction::count());
    }

    /* ---------------- likely repeats typed in again ---------------- */

    public function test_same_transfer_reference_asks_for_confirmation(): void
    {
        $a = $this->invoice();
        $b = $this->invoice();
        $this->post(route('receipt.multi.store'), $this->receiptForm($a, 400, ['transfer_reference' => 'GH-77812']));
        $first = Receipt::first();

        $this->post(route('receipt.multi.store'), $this->receiptForm($b, 250, ['transfer_reference' => ' gh-77812 ']))
            ->assertSessionHasErrors('duplicate');
        $this->assertStringContainsString('FWSSR' . $first->id, session('errors')->first('duplicate'));
        $this->assertStringContainsString('transfer reference', session('errors')->first('duplicate'));
        $this->assertSame(1, Receipt::count());

        $this->post(route('receipt.multi.store'), $this->receiptForm($b, 250, ['transfer_reference' => 'GH-77812', 'confirm_duplicate' => '1']))
            ->assertSessionHasNoErrors();
        $this->assertSame(2, Receipt::count());
    }

    public function test_same_amount_for_same_client_within_minutes_asks_for_confirmation(): void
    {
        Carbon::setTestNow('2026-10-17 10:00:00');
        $inv = $this->invoice();
        $cash = fn ($amount) => ['client_id' => $this->client->id, 'receipt_month' => '2026-10-17', 'from' => 'K', 'mode' => ['cash'],
            'cash_amount' => $amount, 'invoices' => [$inv->id => ['selected' => '1']]];

        $this->post(route('receipt.multi.store'), $cash(300))->assertSessionHasNoErrors();
        $this->post(route('receipt.multi.store'), $cash(300))->assertSessionHasErrors('duplicate');
        $this->post(route('receipt.multi.store'), $cash(250))->assertSessionHasNoErrors();   // different amount: fine

        Carbon::setTestNow('2026-10-17 10:11:00');
        $this->post(route('receipt.multi.store'), $cash(300))->assertSessionHasNoErrors();   // later: a new payment
        $this->assertSame(3, Receipt::count());
    }

    public function test_same_cheque_number_from_a_different_bank_is_fine(): void
    {
        $a = $this->invoice();
        $b = $this->invoice();
        $cheque = fn ($inv, $bank) => ['client_id' => $this->client->id, 'receipt_month' => '2026-09-05', 'from' => 'K', 'mode' => ['cheque'],
            'cheque_amount' => 100, 'cheque_reference' => '000123', 'cheque_bank' => $bank, 'cheque_to_bank_id' => $this->bank->id,
            'invoices' => [$inv->id => ['selected' => '1']], 'confirm_duplicate' => null];

        Carbon::setTestNow(now());
        $this->post(route('receipt.multi.store'), $cheque($a, 'Stanbic'))->assertSessionHasNoErrors();
        Carbon::setTestNow(now()->addMinutes(30));
        $this->post(route('receipt.multi.store'), $cheque($b, 'GCB'))->assertSessionHasNoErrors();
        $this->post(route('receipt.multi.store'), $cheque($b, 'stanbic'))->assertSessionHasErrors('duplicate');
    }

    public function test_receipt_screen_shows_the_warning_with_a_tick_box(): void
    {
        $inv = $this->invoice();
        $this->post(route('receipt.multi.store'), $this->receiptForm($inv, 400, ['transfer_reference' => 'R-1']));

        $this->from(route('receipt.multi.create', ['invoice' => $inv->id]))
            ->post(route('receipt.multi.store'), $this->receiptForm($inv, 100, ['transfer_reference' => 'R-1']));

        $this->get(route('receipt.multi.create', ['invoice' => $inv->id]))
            ->assertSee('Possible repeat')->assertSee('Yes, record it as a separate payment')->assertSee('name="submission_key"', false);
    }
}

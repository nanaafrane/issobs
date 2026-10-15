<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\category;
use App\Models\Client;
use App\Models\ClientLedgerEntry;
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
 * Paying one invoice and paying several now use the same receipt screen:
 * "Create receipt" on an invoice opens it with that invoice ticked.
 */
class ReceiptScreenTest extends TestCase
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
        $this->user = User::factory()->create(['department_id' => $department->id, 'role_id' => $role->id]);
        $this->field = Field::create(['name' => 'Accra', 'user_id' => $this->user->id, 'number' => '1']);
        $this->client = Client::create(['name' => 'Kofi Mensah', 'business_name' => 'Alpha Security', 'field_id' => $this->field->id]);
        $this->bank = Bank::create(['name' => 'GCB', 'branch' => 'Accra', 'acc_number' => '1', 'total' => 0]);
        foreach (['cash', 'cheque', 'transfer', 'momo', 'other payments'] as $m) {
            DB::table('receipt_mode')->insert(['name' => $m]);
        }
        $this->actingAs($this->user);
    }

    private function invoice(float $total, string $month, ?Client $client = null): Invoice
    {
        return Invoice::create(['client_id' => ($client ?? $this->client)->id, 'total' => $total, 'sub_amount' => $total, 'sub_total' => 0,
            'invoice_month' => $month, 'due_date' => now()->addDays(10), 'status' => 'unpaid', 'balance' => 0]);
    }

    /** Whether the page has the row for this invoice ticked. */
    private function ticked(string $html, Invoice $invoice): bool
    {
        return (bool) preg_match('/name="invoices\[' . $invoice->id . '\]\[selected\]" value="1"\s+checked/', $html);
    }

    public function test_create_receipt_on_an_invoice_opens_the_shared_screen_with_it_ticked(): void
    {
        $aug = $this->invoice(600, '2026-08-01');
        $sep = $this->invoice(500, '2026-09-01');

        $this->get('/receiptCreate/' . $sep->id)
            ->assertRedirect(route('receipt.multi.create', ['client_id' => $this->client->id, 'invoice' => $sep->id]));

        $html = $this->followingRedirects()->get('/receiptCreate/' . $sep->id)->assertOk()->assertSee('Alpha Security')->getContent();
        $this->assertTrue($this->ticked($html, $sep));
        $this->assertFalse($this->ticked($html, $aug));   // the client's other open invoice is listed but not ticked
    }

    public function test_paid_invoice_is_not_opened(): void
    {
        $inv = $this->invoice(100, '2026-09-01');
        $inv->update(['status' => 'completed']);

        $this->from('/receipt/create')->get('/receiptCreate/' . $inv->id)
            ->assertRedirect('/receipt/create')->assertSessionHas('warning');
    }

    public function test_branch_user_cannot_open_another_offices_invoice(): void
    {
        $tema = Field::create(['name' => 'Tema', 'user_id' => $this->user->id, 'number' => '2']);
        $temaInvoice = $this->invoice(100, '2026-09-01', Client::create(['name' => 'Yaw', 'field_id' => $tema->id]));
        $branchRole = Role::create(['name' => 'Manager', 'department_id' => $this->user->department_id]);
        $branch = User::factory()->create(['department_id' => $this->user->department_id, 'role_id' => $branchRole->id, 'field_id' => $this->field->id]);

        $this->actingAs($branch)->followingRedirects()->get('/receiptCreate/' . $temaInvoice->id)->assertNotFound();
    }

    public function test_wht_default_leaves_out_what_was_already_withheld(): void
    {
        $inv = $this->invoice(1000, '2026-09-01');
        $this->post(route('receipt.multi.store'), [
            'client_id' => $this->client->id, 'receipt_month' => '2026-09-03', 'from' => 'K', 'mode' => ['cash'], 'cash_amount' => 425,
            'invoices' => [$inv->id => ['selected' => '1', 'wht' => 75]],
        ])->assertSessionHasNoErrors();

        $html = $this->get(route('receipt.multi.create', ['invoice' => $inv->id]))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/data-id="' . $inv->id . '"[^>]*data-wht="0"/s', $html);
        $this->assertStringContainsString('75.00 WHT already withheld', $html);

        $fresh = $this->invoice(800, '2026-10-01');
        $html = $this->get(route('receipt.multi.create', ['invoice' => $fresh->id]))->getContent();
        $this->assertMatchesRegularExpression('/data-id="' . $fresh->id . '"[^>]*data-wht="60"/s', $html);   // 7.5% of 800
    }

    public function test_one_invoice_paid_from_the_shared_screen_is_recorded_like_before(): void
    {
        $inv = $this->invoice(1000, '2026-09-01');

        $this->post(route('receipt.multi.store'), [
            'client_id' => $this->client->id, 'receipt_month' => '2026-09-05', 'from' => 'Kofi',
            'mode' => ['cheque'], 'cheque_amount' => 925, 'cheque_reference' => 'CHQ-1', 'cheque_bank' => 'Stanbic', 'cheque_to_bank_id' => $this->bank->id,
            'invoices' => [$inv->id => ['selected' => '1', 'wht' => 75]],
        ])->assertSessionHasNoErrors();

        $r = Receipt::latest('id')->first();
        $this->assertSame($inv->id, (int) $r->invoice_id);
        $this->assertSame('completed', $r->status);
        $this->assertEquals(1000, $r->total);
        $this->assertEquals(75, $r->wht_amount);
        $this->assertEquals(925, $r->amount_received);
        $this->assertSame('completed', $inv->fresh()->status);
        $this->assertEquals(1000, ReceiptAllocation::where('receipt_id', $r->id)->value('settled'));
        $this->assertSame(1, Collection::where('receipt_id', $r->id)->count());
        $this->assertEquals(925, $this->bank->fresh()->total);
        $this->assertSame(['invoice', 'receipt', 'wht'], ClientLedgerEntry::where('client_id', $this->client->id)->orderBy('id')->pluck('kind')->all());
    }

    public function test_category_already_set_is_reported_like_the_old_single_form(): void
    {
        $inv = $this->invoice(500, '2026-09-01');
        category::create(['name' => 'Category A', 'client_id' => $this->client->id, 'category_month' => '2026-09-01']);

        $this->post(route('receipt.multi.store'), [
            'client_id' => $this->client->id, 'receipt_month' => '2026-09-05', 'from' => 'K', 'mode' => ['cash'], 'cash_amount' => 500,
            'invoices' => [$inv->id => ['selected' => '1']],
        ])->assertSessionHas('warning', fn ($m) => str_contains($m, 'already has a category for September 2026'));
    }

    public function test_tick_survives_a_validation_error(): void
    {
        $inv = $this->invoice(500, '2026-09-01');

        $this->from(route('receipt.multi.create', ['invoice' => $inv->id]))
            ->post(route('receipt.multi.store'), [
                'client_id' => $this->client->id, 'receipt_month' => '2026-09-05', 'from' => 'K', 'mode' => ['cash'], 'cash_amount' => 900,
                'invoices' => [$inv->id => ['selected' => '1']],
            ])->assertSessionHasErrors('invoices');   // overpaid without "keep as credit"

        $html = $this->get(route('receipt.multi.create', ['invoice' => $inv->id]))->getContent();
        $this->assertTrue($this->ticked($html, $inv));
        $this->assertSame(0, Receipt::count());
    }
}

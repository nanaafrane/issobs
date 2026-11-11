<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\Client;
use App\Models\Department;
use App\Models\Field;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\ReceiptAllocation;
use App\Models\ReceiptVoid;
use App\Models\Role;
use App\Models\User;
use App\Services\Receipts\ReceiptVoider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** receipt-voided/{void}: a voided receipt's own page, with its references. */
class VoidedReceiptPageTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;
    private ReceiptVoid $void;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $dept = Department::create(['name' => 'Accounts']);
        $this->finance = User::factory()->create(['department_id' => $dept->id, 'name' => 'Abena Fin',
            'role_id' => Role::create(['name' => 'Finance Manager', 'department_id' => $dept->id])->id]);
        $field = Field::create(['name' => 'Accra', 'user_id' => $this->finance->id, 'number' => '030']);
        $client = Client::create(['name' => 'DVLA', 'business_name' => 'DVLA HQ', 'phone_number' => '024', 'address' => 'Accra',
            'field_id' => $field->id, 'status' => 'active', 'ho_status' => 'approved', 'user_id' => $this->finance->id]);
        $bank = Bank::forceCreate(['name' => 'GCB Main', 'branch' => 'Ring Road', 'acc_number' => '1000200', 'field_id' => $field->id, 'user_id' => $this->finance->id, 'total' => 0]);

        $this->invoice = Invoice::create(['client_id' => $client->id, 'sub_amount' => 1000, 'total' => 1000, 'balance' => 0, 'status' => 'completed',
            'invoice_month' => '2026-10-01', 'due_date' => '2026-10-31', 'user_id' => $this->finance->id]);
        $receipt = Receipt::create(['client_id' => $client->id, 'invoice_id' => $this->invoice->id, 'receipt_month' => '2026-11-03', 'from' => 'DVLA Accounts',
            'transfer_amount' => 1000, 'transfer_reference' => 'GIP-778812', 'transfer_bank' => 'Ecobank', 'transfer_to_bank_id' => $bank->id,
            'total' => 1000, 'status' => 'approved', 'user_id' => $this->finance->id]);
        ReceiptAllocation::create(['receipt_id' => $receipt->id, 'invoice_id' => $this->invoice->id, 'client_id' => $client->id,
            'amount_applied' => 1000, 'settled' => 1000, 'user_id' => $this->finance->id]);
        BankTransaction::create(['bank_id' => $bank->id, 'receipt_id' => $receipt->id, 'channel' => 'transfer', 'credit' => 1000, 'balance' => 1000,
            'narration' => 'Transfer — receipt FWSSR' . $receipt->id . ' (ref GIP-778812)']);
        $bank->forceFill(['total' => 1000])->save();

        $this->actingAs($this->finance);
        $this->void = app(ReceiptVoider::class)->void($receipt, 'Client paid twice by mistake');
    }

    public function test_the_list_links_to_the_receipt_not_the_client_statement(): void
    {
        $this->get(route('receipt.voided'))->assertOk()
            ->assertSee(route('receipt.voided.show', $this->void), false)
            ->assertDontSee(url('clientStatement', $this->void->client_id), false);
    }

    public function test_the_page_shows_what_the_receipt_recorded_and_what_was_reversed(): void
    {
        $this->get(route('receipt.voided.show', $this->void))->assertOk()
            ->assertSee('FWSSR' . $this->void->receipt_id)
            ->assertSee('Client paid twice by mistake')
            ->assertSee('GIP-778812')                                    // transfer reference
            ->assertSee('GCB Main')                                      // account it went into
            ->assertSee('FWSSi' . $this->invoice->id)
            ->assertSee(route('invoice.show', $this->invoice->id), false)
            ->assertSee('Receipt voided: Client paid twice by mistake')   // bank reversal line
            ->assertSee('Abena Fin');
    }

    public function test_an_old_link_to_the_voided_receipt_opens_its_voided_page(): void
    {
        $this->get(route('receipt.show', $this->void->receipt_id))
            ->assertRedirect(route('receipt.voided.show', $this->void));

        $this->get(route('receipt.show', 999999))->assertNotFound();
    }
}

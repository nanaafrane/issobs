<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\ReceiptAllocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Repairs the invoice balances the old receipt code left behind. */
class ReceiptRecomputeCommandTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = Client::create(['name' => 'Kofi', 'business_name' => 'Alpha']);
    }

    /** An invoice as the old code stored it, plus its receipts' allocation lines. */
    private function damaged(string $status, float $balance, array $paid, float $total = 1000): Invoice
    {
        $inv = Invoice::create(['client_id' => $this->client->id, 'total' => $total, 'status' => $status, 'balance' => $balance, 'invoice_month' => '2026-08-01']);
        foreach ($paid as $amount) {
            $r = Receipt::create(['client_id' => $this->client->id, 'invoice_id' => $inv->id, 'total' => $amount, 'cash_amount' => $amount]);
            ReceiptAllocation::create(['receipt_id' => $r->id, 'invoice_id' => $inv->id, 'amount_applied' => $amount, 'settled' => $amount, 'source' => 'legacy']);
        }

        return $inv;
    }

    public function test_dry_run_reports_without_changing_anything(): void
    {
        $doubleSub = $this->damaged('uncompleted', 150, [450]);        // edit subtracted twice
        $this->damaged('uncompleted', 600, [400]);                     // correct

        $this->artisan('receipts:recompute')->expectsOutputToContain('DRY RUN')->assertSuccessful();

        $this->assertEquals(150, $doubleSub->fresh()->balance);
    }

    public function test_apply_fixes_wrong_balances_and_statuses(): void
    {
        $doubleSub = $this->damaged('uncompleted', 150, [450]);
        $paidButOpen = $this->damaged('uncompleted', 800, [1000]);   // marked uncompleted, fully paid
        $neverPaid = $this->damaged('uncompleted', 300, []);

        $this->artisan('receipts:recompute --apply')->assertSuccessful();

        $this->assertSame(['uncompleted', 550.0], [$doubleSub->fresh()->status, (float) $doubleSub->fresh()->balance]);
        $this->assertSame(['completed', 0.0], [$paidButOpen->fresh()->status, (float) $paidButOpen->fresh()->balance]);
        $this->assertSame(['unpaid', 0.0], [$neverPaid->fresh()->status, (float) $neverPaid->fresh()->balance]);
    }

    public function test_short_closed_invoice_is_left_alone_unless_asked(): void
    {
        $short = $this->damaged('completed', 300, [700]);

        $this->artisan('receipts:recompute --apply')->assertSuccessful();
        $this->assertSame('completed', $short->fresh()->status);

        $this->artisan('receipts:recompute --apply --reopen-short')->assertSuccessful();
        $this->assertSame(['uncompleted', 300.0], [$short->fresh()->status, (float) $short->fresh()->balance]);
    }

    public function test_overpayment_moves_to_client_credit_when_asked(): void
    {
        $over = $this->damaged('completed', -200, [400, 800]);
        $latest = Receipt::latest('id')->first();

        $this->artisan('receipts:recompute --apply --overpayments-to-credit')->assertSuccessful();

        $this->assertSame(['completed', 0.0], [$over->fresh()->status, (float) $over->fresh()->balance]);
        $this->assertEquals(200, $latest->fresh()->unapplied_amount);
        $this->assertEquals(600, ReceiptAllocation::where('receipt_id', $latest->id)->value('settled'));
    }

    public function test_receipts_missing_an_allocation_line_get_one(): void
    {
        $inv = Invoice::create(['client_id' => $this->client->id, 'total' => 500, 'status' => 'unpaid', 'balance' => 0, 'invoice_month' => '2026-08-01']);
        Receipt::create(['client_id' => $this->client->id, 'invoice_id' => $inv->id, 'total' => 500, 'cash_amount' => 500]);

        $this->artisan('receipts:recompute --apply')->assertSuccessful();

        $this->assertSame('completed', $inv->fresh()->status);
        $this->assertSame(1, ReceiptAllocation::where('invoice_id', $inv->id)->count());
    }
}

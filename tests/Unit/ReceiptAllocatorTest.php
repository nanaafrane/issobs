<?php

namespace Tests\Unit;

use App\Services\Receipts\ReceiptAllocator;
use PHPUnit\Framework\TestCase;

class ReceiptAllocatorTest extends TestCase
{
    private ReceiptAllocator $allocator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->allocator = new ReceiptAllocator();
    }

    public function test_auto_allocates_oldest_first(): void
    {
        $plan = $this->allocator->plan([
            ['invoice_id' => 1, 'outstanding' => 600],
            ['invoice_id' => 2, 'outstanding' => 500],
        ], 800);

        $this->assertSame([], $plan['errors']);
        $this->assertSame(600.0, $plan['lines'][0]['amount_applied']);
        $this->assertTrue($plan['lines'][0]['completes']);
        $this->assertSame(200.0, $plan['lines'][1]['amount_applied']);
        $this->assertSame(300.0, $plan['lines'][1]['remaining']);
        $this->assertFalse($plan['lines'][1]['completes']);
        $this->assertSame(0.0, $plan['unapplied']);
    }

    public function test_wht_and_vat_reduce_the_cash_needed(): void
    {
        $plan = $this->allocator->plan([
            ['invoice_id' => 1, 'outstanding' => 1000, 'wht' => 75, 'vat7' => 70],
        ], 855);

        $this->assertSame([], $plan['errors']);
        $this->assertSame(855.0, $plan['lines'][0]['amount_applied']);
        $this->assertSame(1000.0, $plan['lines'][0]['settled']);
        $this->assertTrue($plan['lines'][0]['completes']);
    }

    public function test_leftover_money_becomes_unapplied_credit(): void
    {
        $plan = $this->allocator->plan([
            ['invoice_id' => 1, 'outstanding' => 300],
        ], 500);

        $this->assertSame(300.0, $plan['lines'][0]['amount_applied']);
        $this->assertSame(200.0, $plan['unapplied']);
    }

    public function test_typed_amounts_are_respected_and_rest_auto_allocated(): void
    {
        $plan = $this->allocator->plan([
            ['invoice_id' => 1, 'outstanding' => 600, 'applied' => null],
            ['invoice_id' => 2, 'outstanding' => 500, 'applied' => '450'],
        ], 700);

        $this->assertSame([], $plan['errors']);
        $this->assertSame(250.0, $plan['lines'][0]['amount_applied']);
        $this->assertSame(450.0, $plan['lines'][1]['amount_applied']);
    }

    public function test_rejects_paying_more_than_owed_on_an_invoice(): void
    {
        $plan = $this->allocator->plan([
            ['invoice_id' => 7, 'outstanding' => 100, 'applied' => 150],
        ], 150);

        $this->assertNotEmpty($plan['errors']);
        $this->assertStringContainsString('FWSSi7', $plan['errors'][0]);
    }

    public function test_rejects_applying_more_than_received(): void
    {
        $plan = $this->allocator->plan([
            ['invoice_id' => 1, 'outstanding' => 500, 'applied' => 400],
            ['invoice_id' => 2, 'outstanding' => 500, 'applied' => 400],
        ], 500);

        $this->assertNotEmpty($plan['errors']);
    }

    public function test_rounding_does_not_leave_cents_behind(): void
    {
        $plan = $this->allocator->plan([
            ['invoice_id' => 1, 'outstanding' => 333.33],
            ['invoice_id' => 2, 'outstanding' => 333.34],
        ], 666.67);

        $this->assertSame([], $plan['errors']);
        $this->assertTrue($plan['lines'][0]['completes']);
        $this->assertTrue($plan['lines'][1]['completes']);
        $this->assertSame(0.0, $plan['unapplied']);
    }
}

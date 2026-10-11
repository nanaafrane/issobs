<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Department;
use App\Models\Field;
use App\Models\Invoice;
use App\Models\InvoiceData;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Support\InvoiceGuards;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * An ABSENT DEDUCTION line needs a quantity (the absent units) so that
 * quantity x unit_price gives the deduction, but those units are not guards and
 * must never show up as extra guards in the reports.
 */
class AbsentDeductionGuardCountTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Client $client;
    private Service $guarding;
    private Service $absent;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::create(['name' => 'Finance']);
        $role = Role::create(['name' => 'Invoice', 'department_id' => $department->id]);
        $this->user = User::factory()->create(['department_id' => $department->id, 'role_id' => $role->id]);

        $field = Field::create(['name' => 'Accra', 'user_id' => $this->user->id, 'number' => '0300000000']);
        $this->client = Client::create([
            'name' => 'Jane Doe',
            'phone_number' => '0240000000',
            'business_name' => 'Alpha Security',
            'address' => 'Accra',
            'field_id' => $field->id,
            'user_id' => $this->user->id,
        ]);

        $this->guarding = Service::create(['name' => 'Guarding', 'user_id' => $this->user->id]);
        $this->absent = Service::create([
            'name' => 'ABSENT DEDUCTION',
            'counts_as_guards' => false,
            'user_id' => $this->user->id,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'client_id' => $this->client->id,
            'due_date' => now()->addDays(30)->format('Y-m-d\TH:i'),
            'invoice_month' => now()->startOfMonth()->format('Y-m-d'),
            // 10 guards at 100 each, then 3 absent units at -50 each
            'service' => ['Guarding', 'ABSENT DEDUCTION'],
            'description' => ['Guards for the month', 'Absent days'],
            'quantity' => [10, 3],
            'unit_price' => [100, -50],
            'amount' => [1000, -150],
        ];
    }

    public function test_services_count_as_guards_by_default(): void
    {
        $this->assertTrue($this->guarding->fresh()->counts_as_guards);
        $this->assertFalse($this->absent->fresh()->counts_as_guards);
    }

    public function test_storing_an_invoice_flags_the_deduction_line_and_keeps_the_money_right(): void
    {
        $this->actingAs($this->user)
            ->post(route('invoice.store'), $this->payload())
            ->assertRedirect();

        $invoice = Invoice::firstOrFail();

        $this->assertEquals(850, $invoice->sub_amount);
        $this->assertEquals(850, $invoice->total);

        $this->assertDatabaseHas('invoice_data', [
            'invoice_id' => $invoice->id, 'service_name' => 'Guarding', 'counts_as_guards' => true, 'quantity' => 10,
        ]);
        $this->assertDatabaseHas('invoice_data', [
            'invoice_id' => $invoice->id, 'service_name' => 'ABSENT DEDUCTION', 'counts_as_guards' => false, 'quantity' => 3,
        ]);

        // 10 guards, not 13
        $this->assertEquals(10, $invoice->guard_count);
        $this->assertEquals(10, InvoiceGuards::total([$invoice]));
    }

    public function test_duplicating_an_invoice_flags_the_deduction_line(): void
    {
        $source = Invoice::create([
            'client_id' => $this->client->id,
            'due_date' => now()->addDays(10),
            'invoice_month' => now()->startOfMonth(),
            'status' => 'unpaid',
            'sub_amount' => 850,
            'total' => 850,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('invoice.storeDuplicate', ['invoice' => $source->id]), $this->payload([
                'invoice_month' => now()->addMonth()->format('Y-m'),
            ]))
            ->assertRedirect();

        $copy = Invoice::where('id', '!=', $source->id)->firstOrFail();

        $this->assertEquals(10, $copy->guard_count);
        $this->assertDatabaseHas('invoice_data', [
            'invoice_id' => $copy->id, 'service_name' => 'ABSENT DEDUCTION', 'counts_as_guards' => false,
        ]);
    }

    public function test_total_ignores_deduction_lines_across_many_invoices(): void
    {
        $a = $this->invoiceWithLines([['Guarding', true, 8], ['ABSENT DEDUCTION', false, 2]]);
        $b = $this->invoiceWithLines([['Guarding', true, 5], ['ABSENT DEDUCTION', false, 7]]);

        $this->assertEquals(13, InvoiceGuards::total(Invoice::all()));
        $this->assertEquals(8, InvoiceGuards::total([$a]));
        $this->assertEquals(0, InvoiceGuards::total([]));
        $this->assertEquals(5, $b->guard_count);
    }

    public function test_a_flag_already_on_a_line_is_not_rewritten_when_the_service_changes_later(): void
    {
        $this->actingAs($this->user)->post(route('invoice.store'), $this->payload())->assertRedirect();
        $invoice = Invoice::firstOrFail();

        $this->absent->update(['counts_as_guards' => true]);

        $this->assertEquals(10, $invoice->fresh()->guard_count);
    }

    public function test_service_form_saves_the_checkbox(): void
    {
        $this->actingAs($this->user)
            ->post(route('service.store'), ['name' => 'PENALTY DEDUCTION'])
            ->assertRedirect();
        $this->assertFalse(Service::where('name', 'PENALTY DEDUCTION')->first()->counts_as_guards);

        $this->actingAs($this->user)
            ->post(route('service.store'), ['name' => 'Escort', 'counts_as_guards' => '1'])
            ->assertRedirect();
        $this->assertTrue(Service::where('name', 'Escort')->first()->counts_as_guards);
    }

    public function test_service_update_toggles_the_flag(): void
    {
        $this->actingAs($this->user)
            ->put(route('service.update', $this->guarding), ['name' => 'Guarding', 'user_id' => $this->user->id])
            ->assertRedirect();
        $this->assertFalse($this->guarding->fresh()->counts_as_guards);

        $this->actingAs($this->user)
            ->put(route('service.update', $this->guarding), ['name' => 'Guarding', 'user_id' => $this->user->id, 'counts_as_guards' => '1'])
            ->assertRedirect();
        $this->assertTrue($this->guarding->fresh()->counts_as_guards);
    }

    public function test_editing_an_invoice_keeps_the_deduction_line_out_of_the_guard_count(): void
    {
        $this->actingAs($this->user)->post(route('invoice.store'), $this->payload())->assertRedirect();
        $invoice = Invoice::firstOrFail();

        $this->actingAs($this->user)
            ->put(route('invoice.update', $invoice), $this->payload([
                'quantity' => [12, 4],
                'unit_price' => [100, -50],
                'amount' => [1200, -200],
            ]))
            ->assertRedirect();

        $this->assertEquals(12, $invoice->fresh()->guard_count);
        $this->assertDatabaseHas('invoice_data', [
            'invoice_id' => $invoice->id, 'service_name' => 'ABSENT DEDUCTION', 'counts_as_guards' => false, 'quantity' => 4,
        ]);
    }

    public function test_monthly_invoice_dashboard_shows_guards_without_the_deduction_units(): void
    {
        $this->invoiceWithLines([['Guarding', true, 8], ['ABSENT DEDUCTION', false, 2]]);
        // SQLite keeps Eloquent's date cast as a datetime string; the report compares a plain date (as MySQL stores it).
        DB::table('invoices')->update(['invoice_month' => now()->startOfMonth()->format('Y-m-d')]);

        $this->actingAs($this->user)
            ->get(route('invoice.invoiceSearch', ['month' => now()->startOfMonth()->format('Y-m')]))
            ->assertOk()
            ->assertSee('TOTAL NUMBER OF INVOICE GUARDS : 1  / 8', false);
    }

    /** @param array<int, array{0:string,1:bool,2:int}> $lines */
    private function invoiceWithLines(array $lines): Invoice
    {
        $invoice = Invoice::create([
            'client_id' => $this->client->id,
            'due_date' => now()->addDays(10),
            'invoice_month' => now()->startOfMonth(),
            'status' => 'unpaid',
            'sub_amount' => 0,
            'total' => 0,
            'user_id' => $this->user->id,
        ]);

        foreach ($lines as [$service, $counts, $qty]) {
            InvoiceData::create([
                'invoice_id' => $invoice->id,
                'service_name' => $service,
                'counts_as_guards' => $counts,
                'description' => $service,
                'quantity' => $qty,
                'unit_price' => 1,
                'amount' => $qty,
            ]);
        }

        return $invoice->fresh();
    }
}

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_duplicate_form_loads_existing_invoice_details(): void
    {
        $department = Department::create(['name' => 'Finance']);
        $role = Role::create(['name' => 'Invoice', 'department_id' => $department->id]);
        $user = User::factory()->create([
            'department_id' => $department->id,
            'role_id' => $role->id,
        ]);
        $field = Field::create([
            'name' => 'Tema',
            'user_id' => $user->id,
            'number' => '0200000000',
        ]);

        $client = Client::create([
            'name' => 'Jane Doe',
            'phone_number' => '0240000000',
            'business_name' => 'Alpha Security',
            'address' => 'Accra',
            'field_id' => $field->id,
            'user_id' => $user->id,
        ]);

        $service = Service::create([
            'name' => 'Guarding',
            'user_id' => $user->id,
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'due_date' => now()->addDays(10),
            'invoice_month' => now()->startOfMonth(),
            'status' => 'unpaid',
            'sub_amount' => 100,
            'total' => 100,
            'user_id' => $user->id,
        ]);

        InvoiceData::create([
            'invoice_id' => $invoice->id,
            'service_name' => $service->name,
            'description' => 'Security service',
            'quantity' => 2,
            'unit_price' => 50,
            'amount' => 100,
        ]);

        $this->actingAs($user)
            ->get(route('invoice.duplicate', ['invoice' => $invoice->id]))
            ->assertOk()
            ->assertSee('Duplicate')
            ->assertSee('Security service')
            ->assertSee('Guarding');
    }

    public function test_invoice_duplicate_creates_new_invoice_record_with_new_month(): void
    {
        $department = Department::create(['name' => 'Finance']);
        $role = Role::create(['name' => 'Invoice', 'department_id' => $department->id]);
        $user = User::factory()->create([
            'department_id' => $department->id,
            'role_id' => $role->id,
        ]);
        $field = Field::create([
            'name' => 'Accra',
            'user_id' => $user->id,
            'number' => '0300000000',
        ]);

        $client = Client::create([
            'name' => 'John Smith',
            'phone_number' => '0550000000',
            'business_name' => 'Beta Guards',
            'address' => 'Tema',
            'field_id' => $field->id,
            'user_id' => $user->id,
        ]);

        $service = Service::create([
            'name' => 'Patrol',
            'user_id' => $user->id,
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'due_date' => now()->addDays(15),
            'invoice_month' => now()->startOfMonth(),
            'status' => 'unpaid',
            'sub_amount' => 100,
            'total' => 100,
            'user_id' => $user->id,
        ]);

        InvoiceData::create([
            'invoice_id' => $invoice->id,
            'service_name' => $service->name,
            'description' => 'Monthly patrol',
            'quantity' => 5,
            'unit_price' => 20,
            'amount' => 100,
        ]);

        $this->actingAs($user)
            ->post(route('invoice.storeDuplicate', ['invoice' => $invoice->id]), [
                'client_id' => $client->id,
                'due_date' => now()->addDays(30)->format('Y-m-d\TH:i'),
                'invoice_month' => now()->addMonth()->format('Y-m'),
                'service' => [$service->name],
                'description' => ['Monthly patrol'],
                'quantity' => [5],
                'unit_price' => [20],
                'amount' => [100],
                'vat_standard' => 'on',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('invoices', 2);
        $this->assertDatabaseHas('invoices', [
            'client_id' => $client->id,
            'invoice_month' => now()->addMonth()->startOfMonth()->format('Y-m-d 00:00:00'),
        ]);
    }
}

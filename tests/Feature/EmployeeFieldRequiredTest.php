<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\employee;
use App\Models\Field;
use App\Models\Role;
use App\Models\User;
use App\Support\EmployeeBulkImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Every employee belongs to a field office: no path may create one without it or clear it
 * (add form, edit form, bulk upload, bulk update, any other save).
 */
class EmployeeFieldRequiredTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;
    private Field $accra;

    protected function setUp(): void
    {
        parent::setUp();
        $dept = Department::create(['name' => 'Finance']);
        $role = Role::create(['name' => 'Finance Manager', 'department_id' => $dept->id]);
        $this->finance = User::factory()->create(['department_id' => $dept->id, 'role_id' => $role->id]);
        $this->accra = Field::create(['name' => 'Accra', 'user_id' => $this->finance->id, 'number' => '0300000000']);
    }

    private function legacyWithoutField(): employee
    {
        $id = DB::table('employees')->insertGetId(['name' => 'Old guard', 'phone_number' => '233240000001', 'status' => 'Active',
            'field_id' => null, 'user_id' => $this->finance->id, 'created_at' => now(), 'updated_at' => now()]);

        return employee::find($id);
    }

    private function row(array $values): array
    {
        return array_merge(array_fill_keys(array_values(EmployeeBulkImport::COLUMNS), null), $values);
    }

    public function test_the_model_refuses_an_employee_without_a_field_office(): void
    {
        $e = new employee();
        $e->forceFill(['name' => 'No office', 'phone_number' => '233240000002', 'status' => 'Pending']);
        try {
            $e->save();
            $this->fail('saved without a field office');
        } catch (ValidationException $ex) {
            $this->assertArrayHasKey('field_id', $ex->errors());
        }
        $this->assertSame(0, employee::count());
    }

    public function test_a_field_office_cannot_be_cleared_but_old_records_still_save(): void
    {
        $e = new employee();
        $e->forceFill(['name' => 'Kofi', 'phone_number' => '233240000003', 'status' => 'Active', 'field_id' => $this->accra->id, 'user_id' => $this->finance->id])->save();
        $e->field_id = null;
        $this->expectException(ValidationException::class);
        try {
            $e->save();
        } finally {
            $this->assertSame($this->accra->id, (int) $e->fresh()->field_id);
            // An employee saved before the rule can still be changed in other ways (terminate, PIN, ...).
            $old = $this->legacyWithoutField();
            $old->location = 'Gate 2';
            $old->save();
            $this->assertSame('Gate 2', $old->fresh()->location);
        }
    }

    public function test_the_edit_form_requires_a_field_office(): void
    {
        $old = $this->legacyWithoutField();
        $this->actingAs($this->finance)->from('/employees/' . $old->id . '/edit')
            ->put('/employees/' . $old->id, ['name' => 'Old guard', 'payment_type' => 'Cash'])
            ->assertSessionHasErrors('field_id');
        $this->assertNull($old->fresh()->field_id);
    }

    public function test_the_add_form_requires_a_field_office(): void
    {
        $this->actingAs($this->finance)->from('/employees/create')
            ->post('/employees', ['name' => 'New guard', 'gender' => 'male', 'phone_number' => '233240000004', 'payment_type' => 'Cash'])
            ->assertSessionHasErrors('field_id');
        $this->assertSame(0, employee::count());
    }

    public function test_bulk_upload_rows_need_a_field_office(): void
    {
        $res = EmployeeBulkImport::validate([2 => $this->row(['full_name' => 'Ama', 'gender' => 'Female', 'phone_number' => '0240000005', 'payment_type' => 'Cash'])], $this->finance);
        $this->assertContains('Field Office is required.', $res['results'][2]['errors']);

        $ok = EmployeeBulkImport::validate([2 => $this->row(['full_name' => 'Ama', 'gender' => 'Female', 'phone_number' => '0240000005', 'payment_type' => 'Cash', 'field_office' => 'Accra'])], $this->finance);
        $this->assertSame([], $ok['results'][2]['errors']);
    }

    public function test_bulk_update_gives_an_old_employee_a_field_office_or_refuses_the_row(): void
    {
        $old = $this->legacyWithoutField();

        $res = EmployeeBulkImport::validateUpdate([2 => $this->row(['employee_id' => 'FWSS ' . $old->id, 'location' => 'Gate 2'])], $this->finance, [$old->id], true);
        $this->assertStringContainsString('Field Office is required', implode(' ', $res['results'][2]['errors']));

        $ok = EmployeeBulkImport::validateUpdate([2 => $this->row(['employee_id' => 'FWSS ' . $old->id, 'field_office' => 'Accra'])], $this->finance, [$old->id], true);
        $this->assertSame([], $ok['results'][2]['errors']);
        $this->assertSame($this->accra->id, (int) $ok['results'][2]['employee']['field_id']);
    }
}

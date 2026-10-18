<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\employee;
use App\Models\Field;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The pending employees page: the office cards must add up to the total, and the list
 * must show the same people. Pending = waiting for head office (status Pending,
 * Terminated or Re-Instate, HO status pending or not set).
 */
class EmployeesPendingCountTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;
    private array $fields = [];

    protected function setUp(): void
    {
        parent::setUp();
        $dept = Department::create(['name' => 'Finance']);
        $role = Role::create(['name' => 'Finance Manager', 'department_id' => $dept->id]);
        $this->finance = User::factory()->create(['department_id' => $dept->id, 'role_id' => $role->id]);
        // The page addresses offices by id 1..7 in this order.
        foreach (['Accra', 'Botwe', 'Tema', 'Takoradi', 'Koforidua', 'Kumasi', 'Shai Hills'] as $i => $name) {
            $this->fields[$name] = Field::create(['name' => $name, 'user_id' => $this->finance->id, 'number' => '03000000' . $i])->id;
        }
    }

    private function make(?string $field, string $status, ?string $ho): employee
    {
        $e = new employee();
        $e->forceFill(['name' => 'Guard ' . uniqid(), 'phone_number' => '05' . random_int(10000000, 99999999), 'status' => $status,
            'ho_status' => $ho, 'field_id' => $field ? $this->fields[$field] : null, 'department_id' => 6, 'user_id' => $this->finance->id])->save();

        return $e;
    }

    public function test_cards_add_up_to_the_total_and_the_list(): void
    {
        $this->make('Accra', 'Pending', 'pending');
        $this->make('Accra', 'Pending', null);
        $this->make('Botwe', 'Terminated', 'pending');
        $this->make('Shai Hills', 'Re-Instate', null);
        $this->make('Tema', 'Pending', 'pending');
        $this->make(null, 'Pending', 'pending');                 // no office
        // Not pending: these used to leak in through "or HO status is empty".
        $this->make('Tema', 'Active', null);
        $this->make('Kumasi', 'Active', null);
        $this->make('Kumasi', 'Re-Instate', 'approved');
        $this->make('Accra', 'Pending', 'approved');

        $res = $this->actingAs($this->finance)->get('/employeesPending')->assertOk();
        $data = $res->viewData('pendingEmployees');

        $this->assertCount(6, $data);
        $cards = $res->viewData('employeeAccra')->count() + $res->viewData('employeeBotwe')->count() + $res->viewData('employeeTema')
            + $res->viewData('employeeShyhills') + $res->viewData('employeeTakoradi')->count() + $res->viewData('employeeKoforidua')->count()
            + $res->viewData('employeeKumasi')->count() + $res->viewData('employeeNoOffice')->count();
        $this->assertSame(6, $cards, 'office cards add up to the total');
        $this->assertSame(2, $res->viewData('employeeAccra')->count());
        $this->assertSame(1, $res->viewData('employeeTema'));
        $this->assertSame(2, $res->viewData('Tema')->count(), 'Tema list also shows Shai Hills');
        $this->assertSame(1, $res->viewData('employeeNoOffice')->count());
        $this->assertSame(0, $res->viewData('employeeKumasi')->count(), 'Active / approved staff are not pending');

        // The Finance Manager's list shows the same 6 people as the total card.
        $this->assertSame(6, substr_count($res->getContent(), 'name="employees[]"'));
        $res->assertSee('NO OFFICE');
    }
}

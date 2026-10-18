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
        if ($field === null) {
            // Saved before a field office was required (the model now refuses this).
            $id = \Illuminate\Support\Facades\DB::table('employees')->insertGetId(['name' => 'Old guard', 'phone_number' => '05' . random_int(10000000, 99999999),
                'status' => $status, 'ho_status' => $ho, 'field_id' => null, 'department_id' => 6, 'user_id' => $this->finance->id,
                'created_at' => now(), 'updated_at' => now()]);

            return employee::find($id);
        }
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

    private function officer(string $field): User
    {
        $dept = Department::firstOrCreate(['name' => 'Operations']);
        $role = Role::firstOrCreate(['name' => 'Officer'], ['department_id' => $dept->id]);

        return User::factory()->create(['department_id' => $dept->id, 'role_id' => $role->id, 'field_id' => $this->fields[$field]]);
    }

    public function test_a_field_office_sees_itself_and_its_sub_offices(): void
    {
        $this->make('Tema', 'Pending', 'pending');
        $this->make('Shai Hills', 'Pending', null);
        $this->make('Shai Hills', 'Terminated', 'pending');
        $this->make('Accra', 'Pending', 'pending');

        // Tema runs Tema and Shai Hills: a card for each, and both in the list.
        $res = $this->actingAs($this->officer('Tema'))->get('/employeesPending')->assertOk();
        $this->assertSame([['name' => 'Tema', 'count' => 1], ['name' => 'Shai Hills', 'count' => 2]], $res->viewData('officeCards')->all());
        $this->assertSame(3, substr_count($res->getContent(), 'name="employees[]"'));

        // Shai Hills on its own (used to show nothing at all).
        $res = $this->actingAs($this->officer('Shai Hills'))->get('/employeesPending')->assertOk();
        $this->assertSame([['name' => 'Shai Hills', 'count' => 2]], $res->viewData('officeCards')->all());
        $this->assertSame(2, substr_count($res->getContent(), 'name="employees[]"'));

        // Accra sees only Accra.
        $res = $this->actingAs($this->officer('Accra'))->get('/employeesPending')->assertOk();
        $this->assertSame([['name' => 'Accra', 'count' => 1]], $res->viewData('officeCards')->all());
        $this->assertSame(1, substr_count($res->getContent(), 'name="employees[]"'));
    }

    public function test_sub_offices_follow_the_fields_table(): void
    {
        // A new sub-office of Accra is picked up without code changes.
        $legon = Field::create(['name' => 'Legon', 'user_id' => $this->finance->id, 'number' => '0300000099', 'parent_field_id' => $this->fields['Accra']]);
        $e = new employee();
        $e->forceFill(['name' => 'Legon guard', 'phone_number' => '0550000001', 'status' => 'Pending', 'ho_status' => 'pending',
            'field_id' => $legon->id, 'department_id' => 6, 'user_id' => $this->finance->id])->save();

        $res = $this->actingAs($this->officer('Accra'))->get('/employeesPending')->assertOk();
        $this->assertSame([['name' => 'Accra', 'count' => 0], ['name' => 'Legon', 'count' => 1]], $res->viewData('officeCards')->all());
        $this->assertSame([3, 7], Field::managedIds($this->fields['Tema']));
    }
}

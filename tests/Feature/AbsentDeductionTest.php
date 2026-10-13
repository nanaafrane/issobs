<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Department;
use App\Models\employee;
use App\Models\Field;
use App\Models\Role;
use App\Models\Salary;
use App\Models\User;
use App\Support\Attendance\AbsentDeduction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * salaries.absent filled from days marked Absent on the guard attendance sheet.
 */
class AbsentDeductionTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;
    private User $ops;
    private Client $client;
    private employee $guard;     // 3 absent days in October
    private employee $clerk;     // office staff, no attendance

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-02 10:00'));

        $fin = Department::create(['name' => 'Accounts']);
        $opsDept = Department::create(['name' => 'Operations']);
        $this->finance = User::factory()->create(['department_id' => $fin->id, 'role_id' => Role::create(['name' => 'Finance Manager', 'department_id' => $fin->id])->id]);
        $this->ops = User::factory()->create(['department_id' => $opsDept->id, 'role_id' => Role::create(['name' => 'Manager', 'department_id' => $opsDept->id])->id]);
        $field = Field::create(['name' => 'Accra', 'user_id' => $this->finance->id, 'number' => '0300000000']);

        $this->client = Client::create(['name' => 'DVLA', 'business_name' => 'DVLA HQ', 'phone_number' => '0240000000', 'address' => 'Accra',
            'field_id' => $field->id, 'status' => 'active', 'ho_status' => 'approved', 'user_id' => $this->finance->id]);

        $this->guard = $this->employee('KOFI MENSAH', $this->client->id, $field->id, 1550, 200);
        $this->clerk = $this->employee('AMA CLERK', null, $field->id, 3000, 0);

        foreach (['2026-10-03', '2026-10-04', '2026-10-20'] as $d) {
            $this->mark($this->guard, $d, 'absent');
        }
        $this->mark($this->guard, '2026-10-05', 'off');       // paid, not absent
        $this->mark($this->guard, '2026-10-06', 'present');
        $this->mark($this->guard, '2026-10-07', 'pending');   // awaiting review: not counted
        $this->mark($this->guard, '2026-09-30', 'absent');    // another month
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function employee(string $name, ?int $clientId, int $fieldId, float $basic, float $allowances): employee
    {
        $e = new employee();
        $e->forceFill(['name' => $name, 'status' => 'Active', 'ho_status' => 'approved', 'client_id' => $clientId, 'field_id' => $fieldId,
            'basic_salary' => $basic, 'allowances' => $allowances, 'payment_type' => 'Cash', 'user_id' => $this->finance->id])->save();

        return $e;
    }

    private function mark(employee $e, string $date, string $status): void
    {
        Attendance::create(['client_id' => $this->client->id, 'employee_id' => $e->id, 'work_date' => $date,
            'shift' => 'day', 'status' => $status, 'source' => 'manual']);
    }

    private function salary(employee $e, array $extra = []): Salary
    {
        $s = new Salary();
        $s->forceFill($extra + ['employee_id' => $e->id, 'salary_month' => '2026-10-01', 'field_id' => $e->field_id, 'client_id' => $e->client_id,
            'basic_salary' => $e->basic_salary, 'allowances' => $e->allowances, 'payment_status' => 'pending'])->save();

        return $s;
    }

    public function test_daily_rate_rules(): void
    {
        // Default: basic / days in the month. October has 31 days: 1550 / 31 = 50.
        $this->assertSame(50.0, AbsentDeduction::dailyRate(1550, 200, '2026-10'));
        $this->assertSame(150.0, AbsentDeduction::amount(3, 1550, 200, '2026-10'));
        // February 2026 has 28 days.
        $this->assertSame(50.0, AbsentDeduction::dailyRate(1400, 0, '2026-02'));

        AbsentDeduction::saveSettings('basic_allowances', '30', true, $this->finance->id);
        $this->assertSame(58.33, AbsentDeduction::dailyRate(1550, 200, '2026-10'));   // 1750 / 30
        // Never more than the pay itself.
        $this->assertSame(1750.0, AbsentDeduction::amount(31, 1550, 200, '2026-10'));
    }

    public function test_adding_salaries_fills_absent_from_attendance_and_logs_it(): void
    {
        $this->actingAs($this->finance)->post('/salaries', ['salary_month' => '2026-10', 'employees' => [$this->guard->id, $this->clerk->id]])
            ->assertRedirect()->assertSessionHas('success', fn ($m) => str_contains($m, 'Absent deduction filled from attendance for 1 guard'));

        $this->assertSame(150.0, (float) Salary::where('employee_id', $this->guard->id)->value('absent'));
        $this->assertNull(Salary::where('employee_id', $this->clerk->id)->value('absent'));

        $log = DB::table('absent_deduction_changes')->sole();
        $this->assertSame([3, 50.0, 150.0, 'salaries_added'], [(int) $log->absent_days, (float) $log->daily_rate, (float) $log->new_amount, $log->trigger]);
    }

    public function test_automatic_fill_can_be_turned_off(): void
    {
        AbsentDeduction::saveSettings('basic', 'month_days', false, $this->finance->id);
        $this->actingAs($this->finance)->post('/salaries', ['salary_month' => '2026-10', 'employees' => [$this->guard->id]])->assertRedirect();
        $this->assertNull(Salary::where('employee_id', $this->guard->id)->value('absent'));
    }

    public function test_apply_updates_absent_and_moves_totals_by_the_difference(): void
    {
        // Totals already worked out, with 20.00 typed in as absent.
        $guardSalary = $this->salary($this->guard, ['absent' => 20, 'gross_salary' => 1750, 'total_deductions' => 120, 'net_salary' => 1630, 'cost_to_company' => 1839.25]);
        $clerkSalary = $this->salary($this->clerk, ['absent' => 75, 'net_salary' => 2925]);

        $lines = AbsentDeduction::preview('2026-10');
        $this->assertCount(1, $lines);   // the clerk has no attendance and is not listed
        $line = $lines->first();
        $this->assertSame([3, 1, 20.0, 150.0, true], [$line['absent_days'], $line['pending_days'], $line['current'], $line['new'], $line['differs']]);

        $this->actingAs($this->finance)->post('/absent-deductions/apply', ['month' => '2026-10', 'salaries' => [$guardSalary->id, $clerkSalary->id]])
            ->assertRedirect()->assertSessionHas('success', fn ($m) => str_contains($m, 'updated on 1') && str_contains($m, '1 skipped'));

        $g = $guardSalary->fresh();
        $this->assertSame([150.0, 250.0, 1500.0, 1709.25], [(float) $g->absent, (float) $g->total_deductions, (float) $g->net_salary, (float) $g->cost_to_company]);
        $this->assertSame(75.0, (float) $clerkSalary->fresh()->absent);
        $this->assertSame(20.0, (float) DB::table('absent_deduction_changes')->where('trigger', 'applied')->value('old_amount'));

        // Applying again changes nothing.
        $this->assertSame(['updated' => 0, 'skipped' => 1], AbsentDeduction::apply('2026-10', [$guardSalary->id], $this->finance->id));
    }

    public function test_paid_salaries_are_never_changed(): void
    {
        $paid = $this->salary($this->guard, ['absent' => 0, 'net_salary' => 1750, 'payment_status' => 'approved']);
        $this->assertFalse(AbsentDeduction::preview('2026-10')->first()['editable']);
        $this->assertSame(['updated' => 0, 'skipped' => 1], AbsentDeduction::apply('2026-10', [$paid->id], $this->finance->id));
        $this->assertSame(1750.0, (float) $paid->fresh()->net_salary);
    }

    public function test_salary_not_yet_worked_out_gets_only_the_absent_amount(): void
    {
        $s = $this->salary($this->guard);
        AbsentDeduction::apply('2026-10', [$s->id], $this->finance->id);
        $s = $s->fresh();
        $this->assertSame(150.0, (float) $s->absent);
        $this->assertNull($s->net_salary);
        $this->assertNull($s->total_deductions);
    }

    public function test_page_is_for_finance_managers(): void
    {
        $this->salary($this->guard, ['absent' => 0]);
        $this->actingAs($this->finance)->get('/absent-deductions?month=2026-10')->assertOk()
            ->assertSee('KOFI MENSAH')->assertSee('150.00')->assertSee('awaiting review')->assertDontSee('AMA CLERK');
        $this->actingAs($this->finance)->post('/absent-deductions/settings', ['basis' => 'basic', 'divisor' => '26', 'auto_on_create' => 1])->assertRedirect();
        $this->assertSame('26', AbsentDeduction::settings()['divisor']);

        $this->actingAs($this->ops)->get('/absent-deductions')->assertForbidden();
        $this->actingAs($this->ops)->post('/absent-deductions/apply', ['month' => '2026-10', 'salaries' => [1]])->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\employee;
use App\Models\EmployeeMovement;
use App\Models\Field;
use App\Models\Role;
use App\Models\User;
use App\Support\EmployeeCreator;
use App\Support\EmployeeMovements;
use App\Support\EmployeeMovementsBackfill;
use App\Support\EmployeeMovementsReport;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * HR movements (replaces NRRIT): every hire, termination, re-instatement and transfer is
 * one dated record, counted only once head office approves it, and the report reconciles:
 * start + in - out = end, per office and in total.
 */
class EmployeeMovementsTest extends TestCase
{
    use RefreshDatabase;

    private User $headOffice;   // role 1: decides at once
    private User $branch;       // department 7, role 3: requests, head office approves
    private User $temaOfficer;
    private array $f = [];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20 10:00:00');

        // The workflow addresses departments and roles by id: create them in that order.
        $depts = [];
        foreach (['Finance', 'D2', 'D3', 'Field', 'D5', 'HR', 'Operations'] as $name) {
            $depts[] = Department::create(['name' => $name]);
        }
        $invoice = Role::create(['name' => 'Invoice', 'department_id' => $depts[0]->id]);       // id 1
        $officer = Role::create(['name' => 'Officer', 'department_id' => $depts[6]->id]);       // id 2
        $manager = Role::create(['name' => 'Manager', 'department_id' => $depts[6]->id]);       // id 3

        $this->headOffice = User::factory()->create(['department_id' => $depts[0]->id, 'role_id' => $invoice->id]);
        foreach (['Accra', 'Botwe', 'Tema', 'Takoradi', 'Koforidua', 'Kumasi', 'Shai Hills'] as $i => $name) {
            $this->f[$name] = Field::create(['name' => $name, 'user_id' => $this->headOffice->id, 'number' => '03000000' . $i])->id;
        }
        Field::whereKey($this->f['Shai Hills'])->update(['parent_field_id' => $this->f['Tema']]);
        $this->branch = User::factory()->create(['department_id' => $depts[6]->id, 'role_id' => $manager->id, 'field_id' => $this->f['Accra']]);
        $this->temaOfficer = User::factory()->create(['department_id' => $depts[6]->id, 'role_id' => $officer->id, 'field_id' => $this->f['Tema']]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function hire(User $by, string $field, string $joined, string $name = 'Guard'): employee
    {
        return EmployeeCreator::create([
            'name' => $name . ' ' . uniqid(), 'gender' => 'male', 'phone_number' => '2332' . random_int(10000000, 99999999),
            'payment_type' => 'Cash', 'field_id' => $this->f[$field], 'date_of_joining' => $joined,
        ], [], $by, $this->headOffice->id);
    }

    private function report(string $from, string $to, ?array $fields = null): array
    {
        return EmployeeMovementsReport::build(Carbon::parse($from), Carbon::parse($to), $fields);
    }

    private function assertReconciles(array $r): void
    {
        foreach ($r['offices'] as $o) {
            $this->assertSame($o['closing'], $o['opening'] + $o['net'], $o['name'] . ' does not add up');
        }
        $this->assertTrue($r['totals']['reconciles']);
    }

    public function test_head_office_hire_counts_at_once_and_a_branch_hire_waits_for_approval(): void
    {
        $ho = $this->hire($this->headOffice, 'Accra', '2026-10-05');
        $this->assertSame('approved', EmployeeMovement::where('employee_id', $ho->id)->value('status'));

        $req = $this->hire($this->branch, 'Accra', '2026-10-07');
        $this->assertSame('pending', EmployeeMovement::where('employee_id', $req->id)->value('status'));
        $this->assertSame(1, $this->report('2026-10-01', '2026-10-31')['totals']['hires'], 'a request counts in nothing');

        // Head office approves on the pending page.
        $req->forceFill(['ho_status' => 'pending'])->save();
        $this->actingAs($this->headOffice)->post('/employeesAproval', ['employees' => [$req->id], 'action_type' => 'headOffice'])->assertRedirect();
        $m = EmployeeMovement::where('employee_id', $req->id)->sole();
        $this->assertSame(['approved', '2026-10-07'], [$m->status, $m->effective_date->toDateString()]);

        $r = $this->report('2026-10-01', '2026-10-31');
        $this->assertSame([0, 2, 2], [$r['totals']['opening'], $r['totals']['hires'], $r['totals']['closing']]);
        $this->assertReconciles($r);
    }

    public function test_a_branch_termination_waits_for_head_office_then_counts_in_its_month(): void
    {
        $e = $this->hire($this->headOffice, 'Accra', '2026-08-01');

        $this->actingAs($this->branch)->post('/terminateEmployee/' . $e->id, ['effective_date' => '2026-10-15', 'reason' => 'absconded'])->assertRedirect();
        $e->refresh();
        $this->assertSame(['Terminated', 'pending'], [$e->status, $e->ho_status]);
        $this->assertSame(0, $this->report('2026-10-01', '2026-10-31')['totals']['terminated'], 'not counted before approval');
        $this->assertCount(1, $this->report('2026-10-01', '2026-10-31')['pending']);

        // A second request while one is waiting is refused.
        $this->actingAs($this->branch)->post('/terminateEmployee/' . $e->id, ['effective_date' => '2026-10-16', 'reason' => 'resigned'])
            ->assertSessionHas('error');
        $this->assertSame(1, EmployeeMovement::where('employee_id', $e->id)->where('type', 'termination')->count());

        $this->actingAs($this->headOffice)->post('/employeesAproval', ['employees' => [$e->id], 'action_type' => 'headOffice']);
        $r = $this->report('2026-10-01', '2026-10-31');
        $this->assertSame(1, $r['totals']['terminated']);
        $this->assertSame(['opening' => 1, 'closing' => 0], ['opening' => $r['totals']['opening'], 'closing' => $r['totals']['closing']]);
        $left = $r['lists']['termination'][0];
        $this->assertSame(['2026-10-15', 'Absconded / stopped reporting', 75], [$left['date'], $left['reason'], $left['tenure_days']]);
        $this->assertSame(1, $r['early_leavers'], 'left within 90 days');
        $this->assertReconciles($r);
        // September still has them employed.
        $this->assertSame(1, $this->report('2026-09-01', '2026-09-30')['totals']['closing']);
    }

    public function test_declining_a_termination_restores_the_employee(): void
    {
        $e = $this->hire($this->headOffice, 'Accra', '2026-08-01');
        $this->actingAs($this->branch)->post('/terminateEmployee/' . $e->id, ['effective_date' => '2026-10-15', 'reason' => 'resigned']);

        $this->actingAs($this->headOffice)->post('/employeesAproval', ['employees' => [$e->id], 'action_type' => 'decline'])->assertSessionHas('success');
        $e->refresh();
        $this->assertSame(['Active', 'approved'], [$e->status, $e->ho_status]);
        $this->assertSame('declined', EmployeeMovement::where('employee_id', $e->id)->where('type', 'termination')->value('status'));
        $this->assertSame(0, $this->report('2026-10-01', '2026-10-31')['totals']['terminated']);
    }

    public function test_termination_needs_a_date_and_reason_and_only_applies_to_active_staff(): void
    {
        $e = $this->hire($this->headOffice, 'Accra', '2026-08-01');
        $this->actingAs($this->headOffice)->post('/terminateEmployee/' . $e->id, ['effective_date' => '2026-10-15'])->assertSessionHasErrors('reason');
        $this->actingAs($this->headOffice)->post('/terminateEmployee/' . $e->id, ['effective_date' => '2026-10-15', 'reason' => 'other'])->assertSessionHasErrors('note');
        $this->actingAs($this->headOffice)->post('/employeeReinstate/' . $e->id, ['effective_date' => '2026-10-15'])->assertSessionHas('error');
        $this->assertSame(1, EmployeeMovement::where('employee_id', $e->id)->count());
        // GET no longer changes anything (it used to terminate from a link).
        $this->actingAs($this->headOffice)->get('/terminateEmployee/' . $e->id)->assertStatus(405);
    }

    public function test_a_reinstatement_is_never_counted_as_a_new_recruit(): void
    {
        $e = $this->hire($this->headOffice, 'Accra', '2026-01-10');
        $this->actingAs($this->headOffice)->post('/terminateEmployee/' . $e->id, ['effective_date' => '2026-06-30', 'reason' => 'resigned']);
        // Branch asks to re-instate, head office approves (this used to log a "New Recruit" too).
        $this->actingAs($this->branch)->post('/employeeReinstate/' . $e->id, ['effective_date' => '2026-10-01']);
        $this->assertSame('Re-Instate', $e->fresh()->status);
        $this->actingAs($this->headOffice)->post('/employeesAproval', ['employees' => [$e->id], 'action_type' => 'headOffice']);

        $this->assertSame('Active', $e->fresh()->status);
        $this->assertSame(1, EmployeeMovement::where('employee_id', $e->id)->where('type', 'hire')->count());
        $r = $this->report('2026-10-01', '2026-10-31');
        $this->assertSame([0, 1, 0, 1], [$r['totals']['hires'], $r['totals']['reinstated'], $r['totals']['opening'], $r['totals']['closing']]);
        $this->assertReconciles($r);
    }

    public function test_an_office_transfer_moves_headcount_between_offices_and_the_sum_still_holds(): void
    {
        $a = $this->hire($this->headOffice, 'Accra', '2026-01-10');
        $this->hire($this->headOffice, 'Botwe', '2026-01-10');
        $a->field_id = $this->f['Botwe'];
        $a->save();
        $this->assertSame('transfer', EmployeeMovement::where('employee_id', $a->id)->latest('id')->value('type'));

        $r = $this->report('2026-10-01', '2026-10-31');
        $by = collect($r['offices'])->keyBy('name');
        $this->assertSame([1, 1, 0], [$by['Accra']['opening'], $by['Accra']['transfers_out'], $by['Accra']['closing']]);
        $this->assertSame([1, 1, 2], [$by['Botwe']['opening'], $by['Botwe']['transfers_in'], $by['Botwe']['closing']]);
        $this->assertSame(0, $r['totals']['net'], 'a transfer inside the company changes nothing overall');
        $this->assertReconciles($r);

        // Accra's own report counts the transfer out; past movements stay with the office of the time.
        $accra = $this->report('2026-10-01', '2026-10-31', [$this->f['Accra']]);
        $this->assertSame([1, 1, 0], [$accra['totals']['opening'], $accra['totals']['transfers_out'], $accra['totals']['closing']]);
        $this->assertSame($this->f['Accra'], (int) EmployeeMovement::where('employee_id', $a->id)->where('type', 'hire')->value('field_id'));
    }

    public function test_payroll_check_lists_staff_without_a_salary_and_salaries_for_people_not_employed(): void
    {
        $paid = $this->hire($this->headOffice, 'Accra', '2026-01-10', 'Paid');
        $unpaid = $this->hire($this->headOffice, 'Accra', '2026-01-10', 'Unpaid');
        $gone = $this->hire($this->headOffice, 'Accra', '2026-01-10', 'Gone');
        $this->actingAs($this->headOffice)->post('/terminateEmployee/' . $gone->id, ['effective_date' => '2026-08-31', 'reason' => 'resigned']);
        foreach ([$paid, $gone] as $e) {
            DB::table('salaries')->insert(['employee_id' => $e->id, 'salary_month' => '2026-10-01', 'field_id' => $this->f['Accra'], 'user_id' => $this->headOffice->id]);
        }

        $p = $this->report('2026-10-01', '2026-10-31')['payroll'];
        $this->assertSame([$unpaid->id], array_column($p['no_salary'], 'employee_id'));
        $this->assertSame([$gone->id], array_column($p['not_employed'], 'employee_id'));
        $this->assertNull($this->report('2026-10-01', '2026-11-15')['payroll'], 'only for one calendar month');
    }

    public function test_backfill_copies_history_once_and_drops_the_wrong_new_recruit_rows(): void
    {
        $id = fn (array $a) => DB::table('employees')->insertGetId($a + ['phone_number' => '2332' . random_int(10000000, 99999999),
            'user_id' => $this->headOffice->id, 'field_id' => $this->f['Accra'], 'created_at' => '2025-01-01', 'updated_at' => '2025-01-01']);
        $left = $id(['name' => 'Left', 'status' => 'Terminated', 'ho_status' => 'approved', 'date_of_joining' => '2025-02-01']);
        $back = $id(['name' => 'Back', 'status' => 'Active', 'ho_status' => 'approved', 'date_of_joining' => '2025-03-01']);
        $noRecord = $id(['name' => 'No record', 'status' => 'Terminated', 'ho_status' => 'approved', 'date_of_joining' => null, 'status_date' => '2026-04-01']);
        DB::table('nrrit')->insert([
            ['employee_id' => $left, 'name' => 'Left', 'status_month' => '2026-05-01', 'status' => 'Terminated', 'status1' => null],
            ['employee_id' => $back, 'name' => 'Back', 'status_month' => '2026-02-01', 'status' => 'Terminated', 'status1' => null],
            ['employee_id' => $back, 'name' => 'Back', 'status_month' => '2026-06-01', 'status' => 'Active', 'status1' => 'Re-Instate'],
            ['employee_id' => $back, 'name' => 'Back', 'status_month' => '2025-03-01', 'status' => 'Active', 'status1' => 'New Recruit'],
        ]);

        $out = EmployeeMovementsBackfill::run();
        $this->assertSame(['hires' => 3, 'nrrit' => 3, 'nrrit_skipped' => 1, 'terminations' => 1], $out);
        $this->assertSame(['hire', 'termination', 'reinstatement'], EmployeeMovement::where('employee_id', $back)->orderBy('effective_date')->pluck('type')->all());
        $this->assertTrue((bool) EmployeeMovement::where('employee_id', $noRecord)->where('type', 'termination')->value('date_estimated'));

        $this->assertSame(['hires' => 0, 'nrrit' => 0, 'nrrit_skipped' => 1, 'terminations' => 0], EmployeeMovementsBackfill::run(), 'nothing twice');
        $this->assertReconciles($this->report('2026-01-01', '2026-12-31'));
    }

    public function test_offices_see_their_own_scope_and_head_office_corrects_estimated_dates(): void
    {
        $this->hire($this->headOffice, 'Tema', '2026-10-02');
        $shai = $this->hire($this->headOffice, 'Shai Hills', '2026-10-03');
        $this->hire($this->headOffice, 'Accra', '2026-10-04');

        $tema = $this->actingAs($this->temaOfficer)->get('/employees-movements?month=2026-10')->assertOk()->viewData('report');
        $this->assertSame(2, $tema['totals']['hires'], 'Tema + Shai Hills');
        // Asking for another office does not widen the scope.
        $this->assertSame(2, $this->actingAs($this->temaOfficer)->get('/employees-movements?month=2026-10&field=' . $this->f['Accra'])->viewData('report')['totals']['hires']);
        $this->assertSame(3, $this->actingAs($this->headOffice)->get('/employees-movements?month=2026-10')->viewData('report')['totals']['hires']);

        // An old record with an estimated date: head office sets the real one.
        $m = EmployeeMovement::where('employee_id', $shai->id)->sole();
        $m->update(['date_estimated' => true]);
        $this->actingAs($this->temaOfficer)->put('/employees-movements/' . $m->id, ['effective_date' => '2026-09-28'])->assertForbidden();
        $this->actingAs($this->headOffice)->put('/employees-movements/' . $m->id, ['effective_date' => '2026-09-28'])->assertSessionHas('success');
        $m->refresh();
        $this->assertSame(['2026-09-28', false], [$m->effective_date->toDateString(), $m->date_estimated]);

        // The old NRRIT address opens the new report for the same month.
        $this->actingAs($this->headOffice)->get('/employeesnrritView?month=2026-09')->assertRedirect(route('employees.movements', ['month' => '2026-09']));
        $this->actingAs($this->headOffice)->get('/employees-movements/export?month=2026-10')->assertOk()->assertDownload('HR_MOVEMENTS_OCTOBER_2026.xlsx');
    }
}

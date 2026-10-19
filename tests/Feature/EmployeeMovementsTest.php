<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
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
    private User $hr;           // Manager in the HR department: decides at once
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
        $this->hr = User::factory()->create(['department_id' => $depts[5]->id, 'role_id' => $manager->id]);
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
        $this->assertSame(['hires' => 3, 'nrrit' => 3, 'nrrit_skipped' => 1, 'terminations' => 1, 'approved_waiting' => 0, 'reinstatements' => 0], $out);
        $this->assertSame(['hire', 'termination', 'reinstatement'], EmployeeMovement::where('employee_id', $back)->orderBy('effective_date')->pluck('type')->all());
        $this->assertTrue((bool) EmployeeMovement::where('employee_id', $noRecord)->where('type', 'termination')->value('date_estimated'));

        $this->assertSame(['hires' => 0, 'nrrit' => 0, 'nrrit_skipped' => 1, 'terminations' => 0, 'approved_waiting' => 0, 'reinstatements' => 0], EmployeeMovementsBackfill::run(), 'nothing twice');
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

    public function test_hr_terminates_from_the_employee_page_and_it_shows_at_once(): void
    {
        $e = $this->hire($this->headOffice, 'Accra', '2026-01-10');
        $this->actingAs($this->hr)->post('/terminateEmployee/' . $e->id, ['effective_date' => '2026-10-12', 'reason' => 'dismissed'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'terminated with effect from 12 Oct 2026'));
        $this->assertSame(['Terminated', 'approved'], [$e->fresh()->status, $e->fresh()->ho_status]);
        $this->assertSame(1, $this->report('2026-10-01', '2026-10-31')['totals']['terminated']);
    }

    public function test_a_termination_of_someone_with_an_unknown_joining_date_still_counts(): void
    {
        // Old record: no date of joining at all (the hire has no date).
        $id = DB::table('employees')->insertGetId(['name' => 'Old guard', 'phone_number' => '233240000009', 'status' => 'Active', 'ho_status' => 'approved',
            'field_id' => $this->f['Accra'], 'user_id' => $this->headOffice->id, 'created_at' => now(), 'updated_at' => now()]);
        EmployeeMovementsBackfill::run();
        $this->actingAs($this->headOffice)->post('/terminateEmployee/' . $id, ['effective_date' => '2026-10-12', 'reason' => 'resigned']);

        $r = $this->report('2026-10-01', '2026-10-31');
        $this->assertSame([1, 1, 0], [$r['totals']['opening'], $r['totals']['terminated'], $r['totals']['closing']]);
        $this->assertReconciles($r);

        // A termination dated before the joining date is not silently lost: it is listed.
        $late = $this->hire($this->headOffice, 'Accra', '2026-10-20');
        $this->actingAs($this->headOffice)->post('/terminateEmployee/' . $late->id, ['effective_date' => '2026-10-05', 'reason' => 'resigned']);
        $r = $this->report('2026-10-01', '2026-10-31');
        $this->assertSame([$late->id], array_column($r['conflicts'], 'employee_id'));
        $this->assertReconciles($r);
    }

    private function attendance(employee $e, string $date, string $status, ?User $by = null): Attendance
    {
        $client = Client::firstOrCreate(['name' => 'DVLA'], ['business_name' => 'DVLA HQ', 'phone_number' => '0240000000', 'address' => 'Accra',
            'field_id' => $this->f['Accra'], 'status' => 'Active', 'ho_status' => 'approved', 'user_id' => $this->headOffice->id]);

        return Attendance::updateOrCreate(['employee_id' => $e->id, 'work_date' => $date, 'shift' => 'day'],
            ['client_id' => $client->id, 'status' => $status, 'source' => 'manual', 'marked_by' => ($by ?? $this->branch)->id]);
    }

    public function test_a_T_on_the_attendance_sheet_asks_head_office_to_terminate(): void
    {
        $e = $this->hire($this->headOffice, 'Accra', '2026-01-10');
        $row = $this->attendance($e, '2026-10-14', Attendance::TERMINATED);

        $m = EmployeeMovement::where('employee_id', $e->id)->where('type', 'termination')->sole();
        $this->assertSame(['pending', 'attendance', '2026-10-13'], [$m->status, $m->source, $m->effective_date->toDateString()]);
        $this->assertSame(['Terminated', 'pending'], [$e->fresh()->status, $e->fresh()->ho_status], 'shows on the pending employees page');
        $this->assertSame(1, $this->actingAs($this->headOffice)->get('/employeesPending')->viewData('pendingEmployees')->count());
        $this->assertSame(0, $this->report('2026-10-01', '2026-10-31')['totals']['terminated'], 'counts only once approved');

        // An earlier T moves the requested date; a second T does not ask twice.
        $this->attendance($e, '2026-10-10', Attendance::TERMINATED);
        $this->assertSame('2026-10-09', $m->fresh()->effective_date->toDateString());
        $this->assertSame(1, EmployeeMovement::where('employee_id', $e->id)->where('type', 'termination')->count());

        // Head office approves: counted in October.
        $this->actingAs($this->headOffice)->post('/employeesAproval', ['employees' => [$e->id], 'action_type' => 'headOffice']);
        $this->assertSame(1, $this->report('2026-10-01', '2026-10-31')['totals']['terminated']);
    }

    public function test_removing_the_T_withdraws_the_sheet_request(): void
    {
        $e = $this->hire($this->headOffice, 'Accra', '2026-01-10');
        $row = $this->attendance($e, '2026-10-14', Attendance::TERMINATED);
        $row->update(['status' => Attendance::PRESENT]);       // corrected on the sheet

        $this->assertSame('cancelled', EmployeeMovement::where('employee_id', $e->id)->where('type', 'termination')->value('status'));
        $this->assertSame(['Active', 'approved'], [$e->fresh()->status, $e->fresh()->ho_status]);

        // Deleting the cell works the same way.
        $row = $this->attendance($e, '2026-10-15', Attendance::TERMINATED);
        $this->assertSame('Terminated', $e->fresh()->status);
        $row->delete();
        $this->assertSame('Active', $e->fresh()->status);
    }

    public function test_a_T_never_overrides_a_request_from_the_employee_page_or_a_terminated_guard(): void
    {
        $e = $this->hire($this->headOffice, 'Accra', '2026-01-10');
        $this->actingAs($this->branch)->post('/terminateEmployee/' . $e->id, ['effective_date' => '2026-10-12', 'reason' => 'resigned']);
        $row = $this->attendance($e, '2026-10-14', Attendance::TERMINATED);
        $row->update(['status' => Attendance::PRESENT]);
        $m = EmployeeMovement::where('employee_id', $e->id)->where('type', 'termination')->sole();
        $this->assertSame(['pending', 'terminate', '2026-10-12'], [$m->status, $m->source, $m->effective_date->toDateString()], 'the page request stands');
    }

    public function test_undo_a_mistaken_termination_leaves_no_termination_or_reinstatement(): void
    {
        $e = $this->hire($this->headOffice, 'Accra', '2026-01-10');
        $this->actingAs($this->hr)->post('/terminateEmployee/' . $e->id, ['effective_date' => '2026-10-12', 'reason' => 'absconded']);
        $m = EmployeeMovement::where('employee_id', $e->id)->where('type', 'termination')->sole();

        $this->actingAs($this->branch)->post('/employees-movements/' . $m->id . '/undo', ['why' => 'wrong guard selected'])->assertSessionHas('error');
        $this->actingAs($this->hr)->post('/employees-movements/' . $m->id . '/undo', [])->assertSessionHasErrors('why');
        $this->actingAs($this->hr)->post('/employees-movements/' . $m->id . '/undo', ['why' => 'wrong guard selected'])->assertSessionHas('success');

        $this->assertSame(['Active', 'approved'], [$e->fresh()->status, $e->fresh()->ho_status]);
        $this->assertStringContainsString('Undone: wrong guard selected', $m->fresh()->note);
        $r = $this->report('2026-10-01', '2026-10-31');
        $this->assertSame([0, 0, 1, 1], [$r['totals']['terminated'], $r['totals']['reinstated'], $r['totals']['opening'], $r['totals']['closing']]);
        $this->actingAs($this->hr)->get('/employees/' . $e->id)->assertOk()->assertSee('Employment history')->assertSee('undone / withdrawn');

        // Only the latest change can be undone: once re-instated properly, the old termination stays.
        $this->actingAs($this->hr)->post('/terminateEmployee/' . $e->id, ['effective_date' => '2026-10-13', 'reason' => 'resigned']);
        $t2 = EmployeeMovement::where('employee_id', $e->id)->where('type', 'termination')->where('status', 'approved')->sole();
        $this->actingAs($this->hr)->post('/employeeReinstate/' . $e->id, ['effective_date' => '2026-10-18']);
        $this->actingAs($this->hr)->post('/employees-movements/' . $t2->id . '/undo', ['why' => 'wrong guard selected'])->assertSessionHas('error');
        $this->assertSame('approved', $t2->fresh()->status);
    }

    public function test_the_requester_can_withdraw_a_waiting_request(): void
    {
        $e = $this->hire($this->headOffice, 'Accra', '2026-01-10');
        $this->actingAs($this->branch)->post('/terminateEmployee/' . $e->id, ['effective_date' => '2026-10-12', 'reason' => 'resigned']);
        $m = EmployeeMovement::where('employee_id', $e->id)->where('type', 'termination')->sole();
        $this->actingAs($this->temaOfficer)->post('/employees-movements/' . $m->id . '/undo', ['why' => 'not my request'])->assertSessionHas('error');
        $this->actingAs($this->branch)->post('/employees-movements/' . $m->id . '/undo', ['why' => 'asked by mistake'])->assertSessionHas('success');
        $this->assertSame(['cancelled', 'Active'], [$m->fresh()->status, $e->fresh()->status]);
    }

    public function test_edits_and_bulk_updates_keep_the_history_right(): void
    {
        $e = $this->hire($this->headOffice, 'Accra', '2026-01-10');

        // A branch edit sends an active employee back to Pending AND moves them to Botwe.
        $updated = EmployeeCreator::update($e->id, ['field_id' => $this->f['Botwe'], 'location' => 'Gate 2'], [], $this->branch, $this->headOffice->id);
        $this->assertSame('transfer', EmployeeMovement::where('employee_id', $e->id)->latest('id')->value('type'), 'transfer recorded although the edit set Pending');

        // Approving the edit is not a new recruit.
        $updated->refresh()->forceFill(['ho_status' => 'pending'])->save();
        $this->actingAs($this->headOffice)->post('/employeesAproval', ['employees' => [$e->id], 'action_type' => 'headOffice']);
        $this->assertSame(1, EmployeeMovement::where('employee_id', $e->id)->where('type', 'hire')->count());
        $this->assertSame('Active', $e->fresh()->status);

        // Correcting the date of joining (edit form / bulk update) moves the hire.
        EmployeeCreator::update($e->id, ['date_of_joining' => '2026-01-03'], [], $this->headOffice);
        $this->assertSame('2026-01-03', EmployeeMovement::where('employee_id', $e->id)->where('type', 'hire')->value('effective_date')->toDateString());

        // Bulk upload records the hire with its source.
        $rows = [2 => array_merge(array_fill_keys(array_values(\App\Support\EmployeeBulkImport::COLUMNS), null),
            ['full_name' => 'Ama', 'gender' => 'Female', 'phone_number' => '0240000077', 'payment_type' => 'Cash', 'field_office' => 'Accra', 'date_of_joining' => '2026-10-02'])];
        $res = \App\Support\EmployeeBulkImport::validate($rows, $this->headOffice)['results'][2];
        $this->assertSame([], $res['errors']);
        $new = EmployeeCreator::create($res['employee'], $res['pay'], $this->headOffice, null, 'bulk_upload');
        $this->assertSame(['approved', 'bulk_upload', '2026-10-02'], EmployeeMovement::where('employee_id', $new->id)->get(['status', 'source', 'effective_date'])
            ->map(fn ($m) => [$m->status, $m->source, $m->effective_date->toDateString()])->first());
        $this->assertReconciles($this->report('2026-10-01', '2026-10-31'));
    }

    public function test_worked_for_is_measured_from_the_joining_date_and_unknown_without_one(): void
    {
        $dated = $this->hire($this->headOffice, 'Accra', '2026-04-01');
        $id = DB::table('employees')->insertGetId(['name' => 'No joining date', 'phone_number' => '233240000011', 'status' => 'Active', 'ho_status' => 'approved',
            'field_id' => $this->f['Accra'], 'user_id' => $this->headOffice->id, 'created_at' => now(), 'updated_at' => now()]);
        EmployeeMovementsBackfill::run();
        $this->actingAs($this->hr)->post('/terminateEmployee/' . $dated->id, ['effective_date' => '2026-10-11', 'reason' => 'resigned']);
        $this->actingAs($this->hr)->post('/terminateEmployee/' . $id, ['effective_date' => '2026-10-11', 'reason' => 'resigned']);

        $rows = collect($this->report('2026-10-01', '2026-10-31')['lists']['termination'])->keyBy('employee_id');
        $this->assertSame([193, '2026-04-01'], [$rows[$dated->id]['tenure_days'], $rows[$dated->id]['tenure_from']]);
        $this->assertSame([null, null], [$rows[$id]['tenure_days'], $rows[$id]['tenure_from']], 'never counted from year 0');
        // Running the history fill-in again (every migration does) keeps the unknown date unknown.
        EmployeeMovementsBackfill::run();
        $this->assertNull(EmployeeMovement::where('employee_id', $id)->where('type', 'hire')->value('effective_date'));
        $this->actingAs($this->hr)->get('/employees-movements?month=2026-10')->assertOk()->assertSee('since 01 Apr 2026')->assertSee('unknown');
    }

    public function test_only_head_office_or_hr_can_approve_and_the_message_says_where_it_counts(): void
    {
        $e = $this->hire($this->headOffice, 'Accra', '2026-01-10');
        $this->actingAs($this->branch)->post('/terminateEmployee/' . $e->id, ['effective_date' => '2026-10-10', 'reason' => 'resigned']);

        $this->actingAs($this->temaOfficer)->post('/employeesAproval', ['employees' => [$e->id], 'action_type' => 'headOffice'])->assertSessionHas('error');
        $this->assertSame('pending', $e->fresh()->ho_status);

        $this->actingAs($this->hr)->post('/employeesAproval', ['employees' => [$e->id], 'action_type' => 'headOffice'])
            ->assertSessionHas('success', 'Approved: 1 termination (HR movements: Oct 2026).');
        $this->assertSame(1, $this->report('2026-10-01', '2026-10-31')['totals']['terminated']);
    }

    public function test_staff_taken_back_on_without_a_reinstatement_get_one_so_a_new_termination_counts(): void
    {
        // History before re-instatement had its own step: terminated in March, back on in May
        // through approval (nrrit "New Recruit"), Active now.
        $id = DB::table('employees')->insertGetId(['name' => 'Back again', 'phone_number' => '233240000012', 'status' => 'Active', 'ho_status' => 'approved',
            'date_of_joining' => '2025-01-01', 'field_id' => $this->f['Accra'], 'user_id' => $this->headOffice->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('nrrit')->insert([
            ['employee_id' => $id, 'name' => 'Back again', 'status_month' => '2026-03-01', 'status' => 'Terminated', 'status1' => null],
            ['employee_id' => $id, 'name' => 'Back again', 'status_month' => '2026-05-01', 'status' => 'Active', 'status1' => 'New Recruit'],
        ]);
        $this->assertSame(1, EmployeeMovementsBackfill::run()['reinstatements']);
        $this->assertSame('2026-05-01', EmployeeMovement::where('employee_id', $id)->where('type', 'reinstatement')->value('effective_date')->toDateString());
        $this->assertSame(0, EmployeeMovementsBackfill::run()['reinstatements'], 'once');

        $this->actingAs($this->hr)->post('/terminateEmployee/' . $id, ['effective_date' => '2026-10-10', 'reason' => 'absconded']);
        $r = $this->report('2026-10-01', '2026-10-31');
        $this->assertSame(1, $r['totals']['terminated']);
        $this->assertSame([], $r['conflicts']);
    }

    public function test_the_employee_page_says_when_a_termination_is_not_counted_and_why(): void
    {
        $e = $this->hire($this->headOffice, 'Accra', '2026-10-20');
        $this->actingAs($this->hr)->post('/terminateEmployee/' . $e->id, ['effective_date' => '2026-10-05', 'reason' => 'resigned']);
        $t = EmployeeMovement::where('employee_id', $e->id)->where('type', 'termination')->sole();
        $this->assertStringStartsWith('Not counted: not employed on 05 Oct 2026', EmployeeMovementsReport::explain($e->id)[$t->id]);
        $this->actingAs($this->hr)->get('/employees/' . $e->id)->assertOk()->assertSee('Not counted: not employed on 05 Oct 2026');
    }

    public function test_approving_by_saving_the_edit_form_also_approves_the_waiting_termination(): void
    {
        $e = $this->hire($this->headOffice, 'Accra', '2026-01-10');
        $this->actingAs($this->branch)->post('/terminateEmployee/' . $e->id, ['effective_date' => '2026-10-10', 'reason' => 'resigned']);

        // Head office opens the employee's edit form and saves it (instead of Approve on the pending page).
        // employees.tin_number / ssnit_number exist in production (the edit form writes them); no migration creates them.
        foreach (['tin_number', 'ssnit_number'] as $col) {
            if (! \Illuminate\Support\Facades\Schema::hasColumn('employees', $col)) {
                \Illuminate\Support\Facades\Schema::table('employees', fn ($t) => $t->string($col)->nullable());
            }
        }
        $this->actingAs($this->headOffice)->put('/employees/' . $e->id, ['name' => $e->name, 'field_id' => $this->f['Accra'], 'payment_type' => 'Cash',
            'phone_number' => $e->phone_number, 'gender' => 'male', 'date_of_joining' => '2026-01-10'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(['Terminated', 'approved'], [$e->fresh()->status, $e->fresh()->ho_status]);
        $this->assertSame('approved', EmployeeMovement::where('employee_id', $e->id)->where('type', 'termination')->value('status'));
        $this->assertSame(1, $this->report('2026-10-01', '2026-10-31')['totals']['terminated']);

        // Approving again on the pending page records nothing twice.
        $this->actingAs($this->headOffice)->post('/employeesAproval', ['employees' => [$e->id], 'action_type' => 'headOffice']);
        $this->assertSame(1, EmployeeMovement::where('employee_id', $e->id)->where('type', 'termination')->count());
    }

    public function test_requests_left_waiting_for_already_approved_employees_are_repaired(): void
    {
        $e = $this->hire($this->headOffice, 'Accra', '2026-01-10');
        $this->actingAs($this->branch)->post('/terminateEmployee/' . $e->id, ['effective_date' => '2026-10-10', 'reason' => 'resigned']);
        // What the edit form did before this fix: approved the employee without the request.
        DB::table('employees')->where('id', $e->id)->update(['ho_status' => 'approved', 'ho_date' => '2026-10-11']);
        $this->assertSame(0, $this->report('2026-10-01', '2026-10-31')['totals']['terminated']);

        $this->assertSame(1, EmployeeMovementsBackfill::run()['approved_waiting']);
        $this->assertSame(1, $this->report('2026-10-01', '2026-10-31')['totals']['terminated']);
    }

    public function test_a_termination_counts_even_when_the_old_history_ends_in_an_earlier_termination_or_has_no_hire(): void
    {
        // Taken back on before re-instatement had its own step: history ends in a March termination.
        $back = $this->hire($this->headOffice, 'Accra', '2025-01-10');
        EmployeeMovement::create(['employee_id' => $back->id, 'type' => 'termination', 'status' => 'approved', 'effective_date' => '2026-03-01',
            'date_estimated' => true, 'field_id' => $this->f['Accra'], 'source' => 'nrrit']);
        // Hire never approved although they are Active.
        $waiting = $this->hire($this->headOffice, 'Accra', '2025-02-10');
        EmployeeMovement::where('employee_id', $waiting->id)->update(['status' => 'pending']);

        foreach ([$back, $waiting] as $e) {
            $this->actingAs($this->branch)->post('/terminateEmployee/' . $e->id, ['effective_date' => '2026-10-10', 'reason' => 'resigned']);
        }
        $this->actingAs($this->hr)->post('/employeesAproval', ['employees' => [$back->id, $waiting->id], 'action_type' => 'headOffice']);

        $r = $this->report('2026-10-01', '2026-10-31');
        $this->assertSame(2, $r['totals']['terminated']);
        $this->assertSame([], $r['conflicts']);
        $this->assertReconciles($r);
        $fix = EmployeeMovement::where('employee_id', $back->id)->where('source', 'history_fix')->sole();
        $this->assertSame(['reinstatement', '2026-03-02', true], [$fix->type, $fix->effective_date->toDateString(), $fix->date_estimated]);
    }

    public function test_the_history_fill_in_repairs_terminations_already_approved_but_not_counted(): void
    {
        $e = $this->hire($this->headOffice, 'Accra', '2025-01-10');
        // State left by earlier versions: old termination, then a new approved one that could not count.
        foreach (['2026-03-01', '2026-10-10'] as $d) {
            EmployeeMovement::create(['employee_id' => $e->id, 'type' => 'termination', 'status' => 'approved', 'effective_date' => $d,
                'date_estimated' => false, 'field_id' => $this->f['Accra'], 'source' => 'terminate']);
        }
        $e->forceFill(['status' => 'Terminated'])->save();
        $this->assertSame(0, $this->report('2026-10-01', '2026-10-31')['totals']['terminated']);

        $this->assertSame(1, EmployeeMovementsBackfill::run()['reinstatements']);
        $this->assertSame(1, $this->report('2026-10-01', '2026-10-31')['totals']['terminated']);
        $this->assertSame(0, EmployeeMovementsBackfill::run()['reinstatements'], 'once');
        $this->artisan('employees:movements-why', ['employee' => $e->id])->assertSuccessful();

        // A hire never approved, status Terminated, approved termination: repaired too.
        $w = $this->hire($this->headOffice, 'Accra', '2025-02-10');
        EmployeeMovement::where('employee_id', $w->id)->update(['status' => 'pending']);
        EmployeeMovement::create(['employee_id' => $w->id, 'type' => 'termination', 'status' => 'approved', 'effective_date' => '2026-10-12',
            'date_estimated' => false, 'field_id' => $this->f['Accra'], 'source' => 'terminate']);
        $w->forceFill(['status' => 'Terminated'])->save();
        EmployeeMovementsBackfill::run();
        $this->assertSame(2, $this->report('2026-10-01', '2026-10-31')['totals']['terminated']);
    }
}

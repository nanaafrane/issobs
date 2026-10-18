<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Department;
use App\Models\employee;
use App\Models\Field;
use App\Models\Role;
use App\Models\User;
use App\Support\Attendance\AbsentDeduction;
use App\Support\Attendance\KioskRecorder;
use App\Support\Attendance\MonthSheet;
use App\Support\Attendance\Roster;
use App\Support\Attendance\ShiftPlans;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A guard taken from their own post to cover another client for a shift: present at the
 * other client, "M - moved to ..." at home, never an absence (so no absent deduction).
 */
class MovedGuardTest extends TestCase
{
    use RefreshDatabase;

    private User $ops;
    private Field $field;
    private Client $home;     // 6 guards
    private Client $newSite;  // nobody at post
    private employee $kofi;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-18 10:00'));

        $dept = Department::create(['name' => 'Operations']);
        $this->ops = User::factory()->create(['department_id' => $dept->id, 'role_id' => Role::create(['name' => 'Manager', 'department_id' => $dept->id])->id]);
        $this->field = Field::create(['name' => 'Accra', 'user_id' => $this->ops->id, 'number' => '0300000000']);
        $this->ops->update(['field_id' => $this->field->id]);

        $this->home = $this->client('DVLA HQ');
        $this->newSite = $this->client('LEXTA AIRPORT');
        ShiftPlans::save($this->home, ['day_guards' => 6, 'night_guards' => 0], '2026-10');
        ShiftPlans::save($this->newSite, ['day_guards' => 1, 'night_guards' => 0], '2026-10');

        $this->kofi = $this->guard('KOFI MENSAH');
        foreach (range(2, 6) as $i) {
            $this->guard('GUARD ' . $i);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function client(string $name): Client
    {
        return Client::create(['name' => $name, 'business_name' => $name, 'phone_number' => '0240000000', 'address' => 'Accra',
            'field_id' => $this->field->id, 'status' => 'active', 'ho_status' => 'approved', 'user_id' => $this->ops->id]);
    }

    private function guard(string $name): employee
    {
        $e = new employee();
        $e->forceFill(['name' => $name, 'status' => 'Active', 'client_id' => $this->home->id, 'field_id' => $this->field->id,
            'basic_salary' => 1550, 'allowances' => 0, 'user_id' => $this->ops->id])->save();
        Roster::setShift($this->home, $e, 'day');

        return $e;
    }

    private function move(array $overrides = [])
    {
        return $this->actingAs($this->ops)->postJson("/attendance/{$this->home->id}/move", $overrides + [
            'employee_id' => $this->kofi->id, 'date' => '2026-10-17', 'shift' => 'day', 'to_client_id' => $this->newSite->id,
        ]);
    }

    private function homeRow(): array
    {
        return collect((new MonthSheet($this->home, '2026-10'))->rows)->firstWhere(fn ($r) => $r['employee']->id === $this->kofi->id);
    }

    public function test_moving_a_guard_marks_him_present_there_and_moved_at_home(): void
    {
        $this->move()->assertOk()->assertJson(['ok' => true, 'to' => 'LEXTA AIRPORT']);

        $a = Attendance::sole();
        $this->assertSame([$this->newSite->id, $this->home->id, 'present'], [$a->client_id, $a->moved_from_client_id, $a->status]);

        // Home sheet: "M" with where he went, not absent, not blank, no cover for home.
        $row = $this->homeRow();
        $this->assertSame([0, 0, 1], [$row['absent'], $row['worked'], $row['moved']]);
        $this->assertArrayHasKey(17, $row['away']);
        $this->assertSame('Moved to LEXTA AIRPORT: present, day shift', MonthSheet::awayTitle($row['away'][17]));
        $home = new MonthSheet($this->home, '2026-10');
        $this->assertSame(0, $home->cover[17]['day']);
        $this->actingAs($this->ops)->get("/attendance/{$this->home->id}?month=2026-10")->assertOk()
            ->assertSee('Moved to LEXTA AIRPORT: present, day shift')->assertSee('1 M');

        // New site: covered, guard shown as Relief.
        $site = new MonthSheet($this->newSite, '2026-10');
        $this->assertSame(1, $site->cover[17]['day']);
        $this->assertTrue(collect($site->rows)->firstWhere(fn ($r) => $r['employee']->id === $this->kofi->id)['relief']);
        $this->actingAs($this->ops)->get("/attendance/{$this->newSite->id}?month=2026-10")->assertSee('Relief');

        // Payroll: no absent day, so no deduction.
        $this->assertSame(0, AbsentDeduction::attendanceFor([$this->kofi->id], '2026-10')[$this->kofi->id]['absent']);

        // Home can't then mark him absent for that shift, and "fill blanks" leaves it alone.
        $this->actingAs($this->ops)->postJson("/attendance/{$this->home->id}/mark",
            ['employee_id' => $this->kofi->id, 'date' => '2026-10-17', 'shift' => 'day', 'status' => 'absent'])->assertStatus(422);
        $this->actingAs($this->ops)->post("/attendance/{$this->home->id}/fill", ['month' => '2026-10', 'employee_id' => $this->kofi->id]);
        $this->assertSame($this->newSite->id, Attendance::where('employee_id', $this->kofi->id)->where('work_date', '2026-10-17')->sole()->client_id);
    }

    public function test_an_absence_marked_before_the_move_was_known_is_replaced(): void
    {
        Attendance::create(['client_id' => $this->home->id, 'employee_id' => $this->kofi->id, 'work_date' => '2026-10-17',
            'shift' => 'day', 'status' => 'absent', 'source' => 'manual']);
        $this->move()->assertOk();
        $this->assertSame(['present', $this->newSite->id], [Attendance::sole()->status, Attendance::sole()->client_id]);
    }

    public function test_cancel_and_guards(): void
    {
        $this->move()->assertOk();
        $this->move(['action' => 'cancel', 'to_client_id' => null])->assertOk();
        $this->assertSame(0, Attendance::count());

        // Signed in at home that shift: not moved.
        Attendance::create(['client_id' => $this->home->id, 'employee_id' => $this->kofi->id, 'work_date' => '2026-10-17',
            'shift' => 'day', 'status' => 'present', 'source' => 'scan']);
        $this->move()->assertStatus(422);

        $this->move(['date' => '2026-10-19'])->assertStatus(422);                       // future
        $this->move(['date' => '2026-10-16', 'to_client_id' => $this->home->id])->assertStatus(422);   // same client
        $other = $this->client('ELSEWHERE');
        $stranger = new employee();
        $stranger->forceFill(['name' => 'STRANGER', 'status' => 'Active', 'client_id' => $other->id, 'field_id' => $this->field->id, 'user_id' => $this->ops->id])->save();
        $this->move(['employee_id' => $stranger->id, 'date' => '2026-10-16'])->assertStatus(422);   // not posted at home
    }

    public function test_supervisor_kiosk_and_scan_review_treat_him_as_moved_not_absent(): void
    {
        // Supervisor at the new site marks him present: recorded as moved from his own post.
        $r = KioskRecorder::record($this->newSite, $this->kofi, '2026-10-18', 'day', 'present', $this->ops, []);
        $this->assertTrue($r['ok']);
        $this->assertSame($this->home->id, Attendance::sole()->moved_from_client_id);

        // ...but can't mark him absent there.
        $guard2 = employee::where('name', 'GUARD 2')->sole();
        $this->assertFalse(KioskRecorder::record($this->newSite, $guard2, '2026-10-18', 'day', 'absent', $this->ops, [])['ok']);

        // A held scan at the new site: approve = moved; reject = removed, not an absence.
        $held = Attendance::create(['client_id' => $this->newSite->id, 'employee_id' => $guard2->id, 'work_date' => '2026-10-17',
            'shift' => 'day', 'status' => 'pending', 'source' => 'scan', 'flags' => ["Not on this client's roster"]]);
        $this->actingAs($this->ops)->post("/attendance/review/{$held->id}", ['decision' => 'approve'])->assertRedirect();
        $this->assertSame([$this->home->id, 'present'], [$held->fresh()->moved_from_client_id, $held->fresh()->status]);

        $held2 = Attendance::create(['client_id' => $this->newSite->id, 'employee_id' => $guard2->id, 'work_date' => '2026-10-16',
            'shift' => 'day', 'status' => 'pending', 'source' => 'scan', 'flags' => ["Not on this client's roster"]]);
        $this->actingAs($this->ops)->post("/attendance/review/{$held2->id}", ['decision' => 'reject'])->assertRedirect();
        $this->assertNull($held2->fresh());
        $this->assertSame(0, AbsentDeduction::attendanceFor([$guard2->id], '2026-10')[$guard2->id]['absent']);
    }
}

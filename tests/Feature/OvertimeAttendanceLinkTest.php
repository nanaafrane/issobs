<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Department;
use App\Models\employee;
use App\Models\Field;
use App\Models\Overtime;
use App\Models\OvertimeAttendanceLink;
use App\Models\Role;
use App\Models\User;
use App\Support\Attendance\AbsentDeduction;
use App\Support\Attendance\MonthSheet;
use App\Support\Attendance\Roster;
use App\Support\Attendance\ShiftPlans;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Overtime entry -> attendance sheet: "?" while in review, the reason once fully
 * approved, and put back exactly as it was when rejected or deleted.
 */
class OvertimeAttendanceLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $ops;
    private User $finance;
    private Field $field;
    private Client $client;
    private Client $other;
    private employee $kofi;   // posted to $client, day shift: the absent guard
    private employee $yaw;    // posted to $other: does the overtime

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-16 20:00'));
        // expenses.expense_date exists in production (OvertimeObserver writes it) but no migration creates it.
        if (! Schema::hasColumn('expenses', 'expense_date')) {
            Schema::table('expenses', fn (Blueprint $t) => $t->date('expense_date')->nullable());
        }

        $opsDept = Department::create(['name' => 'Operations']);
        $finDept = Department::create(['name' => 'Accounts']);
        $this->ops = User::factory()->create(['department_id' => $opsDept->id, 'role_id' => Role::create(['name' => 'Manager', 'department_id' => $opsDept->id])->id]);
        $this->finance = User::factory()->create(['department_id' => $finDept->id, 'role_id' => Role::create(['name' => 'Finance Manager', 'department_id' => $finDept->id])->id]);
        $this->field = Field::create(['name' => 'Accra', 'user_id' => $this->finance->id, 'number' => '0300000000']);
        $this->ops->update(['field_id' => $this->field->id]);
        $this->finance->update(['field_id' => $this->field->id]);

        $this->client = $this->client('DVLA HQ');
        $this->other = $this->client('SSNIT CAR PARK');
        ShiftPlans::save($this->client, ['day_guards' => 1, 'night_guards' => 0], '2026-10');
        $this->kofi = $this->guard('KOFI MENSAH', $this->client, 'day');
        $this->yaw = $this->guard('YAW OWUSU', $this->other, 'night');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function client(string $name): Client
    {
        return Client::create(['name' => $name, 'business_name' => $name, 'phone_number' => '0240000000', 'address' => 'Accra',
            'field_id' => $this->field->id, 'status' => 'active', 'ho_status' => 'approved', 'user_id' => $this->finance->id]);
    }

    private function guard(string $name, Client $client, string $shift): employee
    {
        $e = new employee();
        $e->forceFill(['name' => $name, 'status' => 'Active', 'client_id' => $client->id, 'field_id' => $this->field->id,
            'basic_salary' => 1550, 'allowances' => 0, 'user_id' => $this->finance->id])->save();
        Roster::setShift($client, $e, $shift);

        return $e;
    }

    private function submit(array $overrides = [])
    {
        return $this->actingAs($this->ops)->postJson('/overtime', $overrides + [
            'entry_date' => '2026-10-15', 'shift' => 'day', 'field_id' => $this->field->id,
            'absent_employee_id' => $this->kofi->id, 'client_id' => $this->client->id,
            'ot_employee_id' => $this->yaw->id, 'reason' => 'Sick', 'amount' => 30,
        ]);
    }

    private function cell(employee $e, string $date = '2026-10-15', string $shift = 'day'): ?Attendance
    {
        return Attendance::where('employee_id', $e->id)->where('work_date', $date)->where('shift', $shift)->first();
    }

    private function approveFully(Overtime $o): void
    {
        $this->actingAs($this->finance)->post("/overtime/{$o->id}/approve")->assertRedirect();   // branch review
        $this->actingAs($this->finance)->post("/overtime/{$o->id}/approve")->assertRedirect();   // finance / H.O
    }

    public function test_submitted_entry_shows_as_awaiting_review_with_its_reason(): void
    {
        $this->submit()->assertOk();
        $o = Overtime::sole();

        $k = $this->cell($this->kofi);
        $this->assertSame(['pending', 'Sick', 'overtime', $o->id, $this->client->id], [$k->status, $k->reason, $k->source, $k->overtime_id, $k->client_id]);
        $this->assertStringContainsString('awaiting overtime approval', $k->flags[0]);
        $y = $this->cell($this->yaw);
        $this->assertSame(['pending', $this->client->id], [$y->status, $y->client_id]);

        // Sheet shows what it will become, and nothing counts yet.
        $sheet = new MonthSheet($this->client, '2026-10');
        $rows = collect($sheet->rows)->keyBy(fn ($r) => $r['employee']->name);
        $this->assertSame('S?', MonthSheet::code($rows['KOFI MENSAH']['cells'][15]));
        $this->assertSame('P?', MonthSheet::code($rows['YAW OWUSU']['cells'][15]));
        $this->assertSame(0, $sheet->cover[15]['day']);

        // Not in the scan review queue, and attendance screens can't change it.
        $this->actingAs($this->ops)->get('/attendance/review')->assertOk()->assertDontSee('KOFI MENSAH');
        $this->actingAs($this->ops)->post("/attendance/review/{$k->id}", ['decision' => 'approve'])->assertStatus(422);
        $this->actingAs($this->ops)->postJson("/attendance/{$this->client->id}/mark",
            ['employee_id' => $this->kofi->id, 'date' => '2026-10-15', 'shift' => 'day', 'status' => 'present'])
            ->assertStatus(422)->assertJsonFragment(['message' => "This shift comes from overtime entry #{$o->id}. Change or delete the overtime entry instead."]);

        $this->actingAs($this->ops)->get('/overtime?date=2026-10-15&field_id=' . $this->field->id)->assertOk()->assertSee('Absent: sheet pending');
    }

    public function test_final_approval_puts_the_reason_on_attendance_and_counts_the_cover(): void
    {
        $this->submit(['reason' => 'Absent'])->assertOk();
        $o = Overtime::sole();

        $this->actingAs($this->finance)->post("/overtime/{$o->id}/approve");
        $this->assertSame('pending', $this->cell($this->kofi)->status);   // branch review alone is not final

        $this->actingAs($this->finance)->post("/overtime/{$o->id}/approve");
        $k = $this->cell($this->kofi);
        $this->assertSame(['absent', 'Absent', null, $this->finance->id], [$k->status, $k->reason, $k->flags, $k->reviewed_by]);
        $this->assertSame('present', $this->cell($this->yaw)->status);

        $sheet = new MonthSheet($this->client, '2026-10');
        $this->assertSame(1, $sheet->cover[15]['day']);   // post covered by overtime
        $this->assertSame(1, AbsentDeduction::attendanceFor([$this->kofi->id], '2026-10')[$this->kofi->id]['absent']);
        $this->actingAs($this->ops)->get('/overtime?date=2026-10-15&field_id=' . $this->field->id)->assertSee('Absent: on sheet');
    }

    public function test_deleting_or_rejecting_puts_the_sheet_back(): void
    {
        $this->submit()->assertOk();
        $o = Overtime::sole();
        $this->actingAs($this->ops)->delete("/overtime/{$o->id}")->assertRedirect();
        $this->assertNull($this->cell($this->kofi));
        $this->assertNull($this->cell($this->yaw));
        $this->assertSame(0, OvertimeAttendanceLink::count());

        // Approved, then deleted by Finance: also reversed.
        $this->submit()->assertOk();
        $o = Overtime::latest('id')->first();
        $this->approveFully($o);
        $this->assertSame('sick', $this->cell($this->kofi)->status);
        $this->actingAs($this->finance)->delete("/overtime/{$o->id}")->assertRedirect();
        $this->assertNull($this->cell($this->kofi));

        // Rejected at branch review.
        $this->submit()->assertOk();
        $o = Overtime::latest('id')->first();
        $this->actingAs($this->finance)->post("/overtime/{$o->id}/reject")->assertRedirect();
        $this->assertNull($this->cell($this->kofi));
        $this->assertNull($this->cell($this->yaw));
    }

    public function test_what_was_there_before_comes_back_exactly(): void
    {
        $before = Attendance::create(['client_id' => $this->client->id, 'employee_id' => $this->kofi->id, 'work_date' => '2026-10-15',
            'shift' => 'day', 'status' => 'absent', 'reason' => 'Absent', 'source' => 'manual', 'marked_by' => $this->ops->id, 'note' => 'no show']);

        $this->submit(['reason' => 'Pass'])->assertOk();
        $o = Overtime::sole();
        $this->approveFully($o);
        $this->assertSame(['pass', $before->id], [$this->cell($this->kofi)->status, $this->cell($this->kofi)->id]);

        $this->actingAs($this->finance)->delete("/overtime/{$o->id}");
        $after = $this->cell($this->kofi);
        $this->assertSame(['absent', 'Absent', 'manual', 'no show', $this->ops->id, null],
            [$after->status, $after->reason, $after->source, $after->note, $after->marked_by, $after->overtime_id]);
    }

    public function test_a_guard_who_signed_in_is_not_overwritten(): void
    {
        Attendance::create(['client_id' => $this->client->id, 'employee_id' => $this->kofi->id, 'work_date' => '2026-10-15',
            'shift' => 'day', 'status' => 'present', 'source' => 'scan', 'checked_in_at' => '2026-10-15 05:52:00']);

        $this->submit()->assertOk();
        $o = Overtime::sole();
        $this->assertSame(['present', null], [$this->cell($this->kofi)->status, $this->cell($this->kofi)->overtime_id]);
        $link = OvertimeAttendanceLink::where('role', 'absent')->sole();
        $this->assertSame('conflict', $link->state);
        $this->assertStringContainsString('signed in', $link->message);
        $this->actingAs($this->ops)->get('/overtime?date=2026-10-15&field_id=' . $this->field->id)->assertSee('Absent: not on sheet');

        $this->actingAs($this->finance)->delete("/overtime/{$o->id}");
        $this->assertSame('present', $this->cell($this->kofi)->status);   // untouched
    }

    public function test_editing_the_entry_moves_the_cells(): void
    {
        $this->submit()->assertOk();
        $o = Overtime::sole();
        $ama = $this->guard('AMA BOATENG', $this->client, 'night');

        $this->actingAs($this->ops)->putJson("/overtime/{$o->id}", [
            'entry_date' => '2026-10-15', 'shift' => 'night', 'field_id' => $this->field->id,
            'absent_employee_id' => $ama->id, 'client_id' => $this->client->id,
            'ot_employee_id' => $this->yaw->id, 'reason' => 'Off Duty', 'amount' => 30,
        ])->assertOk();

        $this->assertNull($this->cell($this->kofi));
        $this->assertNull($this->cell($this->yaw, '2026-10-15', 'day'));
        $this->assertSame(['pending', 'Off Duty'], [$this->cell($ama, '2026-10-15', 'night')->status, $this->cell($ama, '2026-10-15', 'night')->reason]);
        $this->assertSame('pending', $this->cell($this->yaw, '2026-10-15', 'night')->status);
    }

    public function test_post_vacant_reasons_only_record_the_cover(): void
    {
        $this->submit(['reason' => 'Shortage', 'absent_employee_id' => null, 'absent_employee_note' => 'SHORTAGE'])->assertOk();
        $this->assertSame(['cover'], OvertimeAttendanceLink::pluck('role')->all());

        $this->submit(['reason' => 'Terminated', 'entry_date' => '2026-10-14'])->assertOk();   // guard named, but nothing to mark on them
        $this->assertNull($this->cell($this->kofi, '2026-10-14'));
    }

    public function test_a_named_guard_needs_a_reason_from_the_shared_list(): void
    {
        $this->submit(['reason' => null])->assertStatus(422)->assertJsonValidationErrors('reason');
        $this->submit(['reason' => 'Holiday'])->assertStatus(422)->assertJsonValidationErrors('reason');
        $this->assertSame(0, Overtime::count());
    }
}

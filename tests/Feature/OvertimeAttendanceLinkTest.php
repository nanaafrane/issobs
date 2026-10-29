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

        $this->submit(['reason' => 'Other', 'entry_date' => '2026-10-14'])->assertOk();   // guard named, but nothing to mark on them
        $this->assertNull($this->cell($this->kofi, '2026-10-14'));
    }

    public function test_a_named_guard_needs_a_reason_from_the_shared_list(): void
    {
        $this->submit(['reason' => null])->assertStatus(422)->assertJsonValidationErrors('reason');
        $this->submit(['reason' => 'Holiday'])->assertStatus(422)->assertJsonValidationErrors('reason');
        $this->assertSame(0, Overtime::count());
    }

    public function test_terminated_goes_on_the_sheet_as_its_own_status(): void
    {
        $this->submit(['reason' => 'Terminated'])->assertOk();
        $o = Overtime::sole();
        $sheet = new MonthSheet($this->client, '2026-10');
        $kofi = collect($sheet->rows)->firstWhere(fn ($r) => $r['employee']->id === $this->kofi->id);
        $this->assertSame('T?', MonthSheet::code($kofi['cells'][15]));

        $this->approveFully($o);
        $k = $this->cell($this->kofi);
        $this->assertSame(['terminated', 'Terminated'], [$k->status, $k->reason]);

        // Not paid, not absent (no absent deduction), and the day isn't blank.
        $kofi = collect((new MonthSheet($this->client, '2026-10'))->rows)->firstWhere(fn ($r) => $r['employee']->id === $this->kofi->id);
        $this->assertSame([0, 0, 0, 'T', 'att-terminated'], [$kofi['worked'], $kofi['paid'], $kofi['absent'],
            MonthSheet::code($kofi['cells'][15]), MonthSheet::css($kofi['cells'][15])]);
        $this->assertSame(0, AbsentDeduction::attendanceFor([$this->kofi->id], '2026-10')[$this->kofi->id]['absent']);

        // Operations can also mark it by hand.
        $this->actingAs($this->ops)->postJson("/attendance/{$this->client->id}/mark",
            ['employee_id' => $this->kofi->id, 'date' => '2026-10-16', 'shift' => 'day', 'status' => 'terminated'])->assertOk();
        $this->assertSame('Terminated', $this->cell($this->kofi, '2026-10-16')->reason);
    }

    public function test_an_approved_terminated_overtime_asks_head_office_and_reversing_it_withdraws(): void
    {
        $this->submit(['reason' => 'Terminated'])->assertOk();
        $o = Overtime::sole();
        $this->assertSame('Active', $this->kofi->fresh()->status, 'a submitted entry asks nothing yet');

        $this->approveFully($o);
        $m = \App\Models\EmployeeMovement::where('employee_id', $this->kofi->id)->where('type', 'termination')->sole();
        $this->assertSame(['pending', 'overtime', '2026-10-14'], [$m->status, $m->source, $m->effective_date->toDateString()]);
        $this->assertSame(['Terminated', 'pending'], [$this->kofi->fresh()->status, $this->kofi->fresh()->ho_status]);

        // Finance deletes the entry: the sheet goes back and the request is withdrawn.
        $this->actingAs($this->finance)->delete("/overtime/{$o->id}")->assertRedirect();
        $this->assertSame(['cancelled', 'Active'], [$m->fresh()->status, $this->kofi->fresh()->status]);
    }

    public function test_the_overtime_link_on_the_sheet_opens_the_entry(): void
    {
        $this->submit(['entry_date' => '2026-10-12'])->assertOk();
        $o = Overtime::sole();

        $this->actingAs($this->ops)->get("/overtime/{$o->id}/open")
            ->assertRedirect(url('overtime') . '?date=2026-10-12&field_id=' . $this->field->id . '&highlight=' . $o->id . '#ot-' . $o->id);
        $this->actingAs($this->ops)->get('/overtime?date=2026-10-12&field_id=' . $this->field->id . '&highlight=' . $o->id)->assertOk()
            ->assertSee('id="ot-' . $o->id . '"', false)->assertSee('ot-highlight', false)->assertSee('KOFI MENSAH');

        $this->actingAs($this->finance)->delete("/overtime/{$o->id}");
        $this->actingAs($this->ops)->get("/overtime/{$o->id}/open")->assertRedirect(url('overtime'))->assertSessionHas('error');
        // The sheet's popover links to /overtime/{id}/open.
        $this->actingAs($this->ops)->get("/attendance/{$this->client->id}")->assertSee("'/open", false);
    }

    public function test_client_first_lookups_show_who_didnt_show_and_who_is_free(): void
    {
        $ama = $this->guard('AMA BOATENG', $this->client, 'night');
        $esi = $this->guard('ESI MENSAH', $this->other, 'day');   // working the day shift at another client
        Attendance::create(['client_id' => $this->other->id, 'employee_id' => $esi->id, 'work_date' => '2026-10-15',
            'shift' => 'day', 'status' => 'present', 'source' => 'scan', 'checked_in_at' => '2026-10-15 05:50:00']);

        // Step 2: guards posted to the client, day-shift guards first, with what the sheet says.
        $groups = $this->actingAs($this->ops)->getJson('/overtime/lookup/client-guards?client_id=' . $this->client->id . '&date=2026-10-15&shift=day')
            ->assertOk()->json();
        $this->assertSame('On the day shift', $groups[0]['text']);
        $this->assertSame($this->kofi->id, $groups[0]['children'][0]['id']);
        $this->assertStringContainsString('nothing recorded', $groups[0]['children'][0]['text']);
        $this->assertSame($ama->id, $groups[1]['children'][0]['id']);   // night guard listed after

        // Step 3: Kofi is the absent guard so he isn't offered. Esi is on duty at her own post: offered,
        // but last and marked as a move. Off-shift guards first.
        $groups = collect($this->actingAs($this->ops)->getJson('/overtime/lookup/available-guards?date=2026-10-15&shift=day&field_id=' . $this->field->id . '&exclude=' . $this->kofi->id)
            ->assertOk()->json());
        $ids = $groups->pluck('children')->flatten(1)->pluck('id')->all();
        $this->assertContains($this->yaw->id, $ids);
        $this->assertContains($ama->id, $ids);
        $this->assertNotContains($this->kofi->id, $ids);
        $this->assertSame('On duty this shift at his own post (will be moved)', $groups->last()['text']);
        $this->assertSame([$esi->id], collect($groups->last()['children'])->pluck('id')->all());
        $this->assertSame('SSNIT CAR PARK', $groups->last()['children'][0]['moved_from']);

        // Once Yaw covers this shift he is no longer free, and Kofi shows as already on overtime.
        $this->submit()->assertOk();
        $ids = collect($this->actingAs($this->ops)->getJson('/overtime/lookup/available-guards?date=2026-10-15&shift=day&field_id=' . $this->field->id)->json())
            ->pluck('children')->flatten(1)->pluck('id')->all();
        $this->assertNotContains($this->yaw->id, $ids);
        $kofi = collect($this->actingAs($this->ops)->getJson('/overtime/lookup/client-guards?client_id=' . $this->client->id . '&date=2026-10-15&shift=day')->json())
            ->pluck('children')->flatten(1)->firstWhere('id', $this->kofi->id);
        $this->assertTrue($kofi['disabled']);
        $this->assertStringContainsString('Already on overtime', $kofi['text']);
    }

    public function test_a_guard_cannot_cover_two_gaps_or_cover_while_working(): void
    {
        $this->submit()->assertOk();
        $ama = $this->guard('AMA BOATENG', $this->client, 'night');
        // Yaw again, same date and shift.
        $this->submit(['absent_employee_id' => $ama->id, 'reason' => 'Absent'])->assertStatus(422)->assertJsonValidationErrors('ot_employee_id');

        // A guard already working at another client this shift (moved / relief) can't cover.
        $esi = $this->guard('ESI MENSAH', $this->other, 'day');
        $third = $this->client('LEXTA');
        Attendance::create(['client_id' => $third->id, 'moved_from_client_id' => $this->other->id, 'employee_id' => $esi->id, 'work_date' => '2026-10-15',
            'shift' => 'day', 'status' => 'present', 'source' => 'manual']);
        $this->submit(['absent_employee_id' => $ama->id, 'ot_employee_id' => $esi->id])->assertStatus(422)
            ->assertJsonFragment(['ot_employee_id' => ['This guard is already present at another client for this shift. Pick a guard who is free for this shift.']]);

        // Editing an entry doesn't clash with itself.
        $o = Overtime::sole();
        $this->actingAs($this->ops)->putJson("/overtime/{$o->id}", [
            'entry_date' => '2026-10-15', 'shift' => 'day', 'field_id' => $this->field->id,
            'absent_employee_id' => $this->kofi->id, 'client_id' => $this->client->id,
            'ot_employee_id' => $this->yaw->id, 'reason' => 'Absent', 'amount' => 40,
        ])->assertOk();
    }

    public function test_cover_guard_row_is_labelled_on_the_client_sheet(): void
    {
        $this->submit()->assertOk();
        $this->approveFully(Overtime::sole());
        $yaw = collect((new MonthSheet($this->client, '2026-10'))->rows)->firstWhere(fn ($r) => $r['employee']->id === $this->yaw->id);
        $this->assertTrue($yaw['ot_cover']);
        $this->actingAs($this->ops)->get("/attendance/{$this->client->id}?month=2026-10")->assertSee('O.T cover');
        $this->actingAs($this->ops)->get('/overtime?date=2026-10-15&field_id=' . $this->field->id)->assertOk()
            ->assertSee("Who didn't show up?", false)->assertSee('Who fills the gap?');
    }

    public function test_free_guards_still_load_when_nobody_is_named(): void
    {
        foreach (['vacant', ''] as $exclude) {
            $ids = collect($this->actingAs($this->ops)
                ->getJson('/overtime/lookup/available-guards?date=2026-10-15&shift=day&field_id=' . $this->field->id . '&exclude=' . $exclude)
                ->assertOk()->json())->pluck('children')->flatten(1)->pluck('id')->all();
            $this->assertContains($this->yaw->id, $ids);
            $this->assertContains($this->kofi->id, $ids);   // nobody absent, so Kofi is free too
        }
        // Post vacant entry saves with the chosen cover.
        $this->submit(['absent_employee_id' => null, 'absent_employee_note' => 'POST VACANT', 'reason' => 'Shortage'])->assertOk();
    }

    public function test_cover_guard_taken_off_his_own_active_shift_is_moved_not_absent(): void
    {
        // Esi works the day shift at SSNIT CAR PARK; ops had already filled her day as present there.
        $esi = $this->guard('ESI MENSAH', $this->other, 'day');
        Attendance::create(['client_id' => $this->other->id, 'employee_id' => $esi->id, 'work_date' => '2026-10-15',
            'shift' => 'day', 'status' => 'present', 'reason' => 'Present', 'source' => 'manual', 'note' => 'filled']);

        // The lookup lists her as on duty and says she'd be moved.
        $groups = $this->actingAs($this->ops)->getJson('/overtime/lookup/available-guards?date=2026-10-15&shift=day&field_id=' . $this->field->id)->json();
        $onDuty = collect($groups)->firstWhere('text', 'On duty this shift at his own post (will be moved)');
        $this->assertSame('SSNIT CAR PARK', collect($onDuty['children'])->firstWhere('id', $esi->id)['moved_from']);

        $this->submit(['ot_employee_id' => $esi->id])->assertOk();
        $o = Overtime::sole();
        $this->approveFully($o);

        $cover = $this->cell($esi);
        $this->assertSame([$this->client->id, $this->other->id, 'present', $o->id], [$cover->client_id, $cover->moved_from_client_id, $cover->status, $cover->overtime_id]);
        $this->assertStringStartsWith('Moved off his own day shift at SSNIT CAR PARK', OvertimeAttendanceLink::where('role', 'cover')->sole()->message);

        // Her own post: "M", not absent, not cover; no absent deduction.
        $home = new MonthSheet($this->other, '2026-10');
        $row = collect($home->rows)->firstWhere(fn ($r) => $r['employee']->id === $esi->id);
        $this->assertSame([0, 1], [$row['absent'], $row['moved']]);
        $this->assertSame('Moved to DVLA HQ: present, day shift (overtime #' . $o->id . ')', MonthSheet::awayTitle($row['away'][15]));
        $this->assertSame(0, $home->cover[15]['day']);
        $this->assertSame(0, AbsentDeduction::attendanceFor([$esi->id], '2026-10')[$esi->id]['absent']);
        $this->actingAs($this->ops)->get('/overtime?date=2026-10-15&field_id=' . $this->field->id)->assertSee('O.T (moved): on sheet');

        // Delete the overtime: her own post's mark comes back as it was.
        $this->actingAs($this->finance)->delete("/overtime/{$o->id}");
        $back = $this->cell($esi);
        $this->assertSame([$this->other->id, null, 'present', 'filled', null], [$back->client_id, $back->moved_from_client_id, $back->status, $back->note, $back->overtime_id]);
    }

    public function test_cover_on_his_off_shift_is_extra_work_not_a_move(): void
    {
        // Yaw works nights at SSNIT CAR PARK; covering a day shift is on his own time.
        $this->submit()->assertOk();
        $this->assertNull($this->cell($this->yaw)->moved_from_client_id);
        $this->assertNull(OvertimeAttendanceLink::where('role', 'cover')->sole()->message);
    }

    public function test_guard_phone_numbers_show_on_overtime_and_the_sheet(): void
    {
        $this->kofi->forceFill(['phone_number' => '0241112222'])->save();
        $this->yaw->forceFill(['phone_number' => '0553334444'])->save();
        $this->submit()->assertOk();

        $this->actingAs($this->ops)->get('/overtime?date=2026-10-15&field_id=' . $this->field->id)->assertOk()
            ->assertSee('href="tel:0241112222"', false)->assertSee('href="tel:0553334444"', false)
            ->assertSee('https://wa.me/233553334444', false);
        $this->actingAs($this->ops)->get("/attendance/{$this->client->id}?month=2026-10")->assertOk()
            ->assertSee('href="tel:0241112222"', false)->assertSee('data-phone="0241112222"', false);

        $who = collect($this->actingAs($this->ops)->getJson('/overtime/lookup/client-guards?client_id=' . $this->client->id . '&date=2026-10-14&shift=day')->json())
            ->pluck('children')->flatten(1)->firstWhere('id', $this->kofi->id);
        $this->assertStringContainsString('0241112222', $who['text']);
        $free = collect($this->actingAs($this->ops)->getJson('/overtime/lookup/available-guards?date=2026-10-14&shift=day&field_id=' . $this->field->id)->json())
            ->pluck('children')->flatten(1)->firstWhere('id', $this->yaw->id);
        $this->assertSame('0553334444', $free['phone']);
    }
}

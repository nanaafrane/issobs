<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\ClientShiftPlan;
use App\Models\Department;
use App\Models\employee;
use App\Models\Field;
use App\Models\Role;
use App\Models\User;
use App\Support\Attendance\MonthOverview;
use App\Support\Attendance\MonthSheet;
use App\Support\Attendance\Roster;
use App\Support\Attendance\ShiftClock;
use App\Support\Attendance\ShiftPlans;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuardAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $ops;          // Accra operations manager
    private User $headOffice;   // sees every client
    private Field $accra;
    private Field $kumasi;
    private Client $client;     // Accra client with a QR token and site coordinates
    private employee $kofi;     // day guard
    private employee $ama;      // night guard

    private const SITE_LAT = 5.6037;
    private const SITE_LNG = -0.1870;
    private const DEVICE_KOFI = 'aaaaaaaa-1111-2222-3333-444444444444';
    private const DEVICE_AMA = 'bbbbbbbb-1111-2222-3333-444444444444';

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-13 09:00:00'));

        $opsDept = Department::create(['name' => 'Operations']);
        $finDept = Department::create(['name' => 'Finance']);
        $manager = Role::create(['name' => 'Manager', 'department_id' => $opsDept->id]);
        $invoice = Role::create(['name' => 'Invoice', 'department_id' => $finDept->id]);

        $this->headOffice = User::factory()->create(['department_id' => $finDept->id, 'role_id' => $invoice->id]);
        $this->accra = Field::create(['name' => 'Accra', 'user_id' => $this->headOffice->id, 'number' => '0300000000']);
        $this->kumasi = Field::create(['name' => 'Kumasi', 'user_id' => $this->headOffice->id, 'number' => '0300000001']);
        $this->ops = User::factory()->create(['department_id' => $opsDept->id, 'role_id' => $manager->id, 'field_id' => $this->accra->id]);

        $this->client = $this->makeClient('DVLA HQ', $this->accra);
        $this->client->forceFill(['attendance_token' => str_repeat('a', 32), 'latitude' => self::SITE_LAT, 'longitude' => self::SITE_LNG, 'attendance_radius_m' => 150])->save();
        ShiftPlans::save($this->client, ['day_guards' => 2, 'night_guards' => 1], '2026-10');

        $this->kofi = $this->makeGuard('KOFI MENSAH', $this->client, 'day', '1234');
        $this->ama = $this->makeGuard('AMA OWUSU', $this->client, 'night', '5678');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeClient(string $name, Field $field): Client
    {
        return Client::create([
            'name' => $name, 'business_name' => $name, 'phone_number' => '0240000000', 'address' => 'Accra',
            'field_id' => $field->id, 'status' => 'active', 'ho_status' => 'approved', 'user_id' => $this->headOffice->id,
        ]);
    }

    private function makeGuard(string $name, Client $client, ?string $shift, ?string $pin): employee
    {
        $e = new employee();
        $e->forceFill([
            'name' => $name, 'phone_number' => '05' . random_int(10000000, 99999999), 'status' => 'Active',
            'client_id' => $client->id, 'field_id' => $client->field_id, 'department_id' => 6, 'user_id' => $this->headOffice->id,
            'attendance_pin' => $pin ? Hash::make($pin) : null,
        ])->save();
        if ($shift) {
            Roster::setShift($client, $e, $shift);
        }

        return $e;
    }

    private function scan(array $overrides = [], string $at = '2026-10-13 05:50:00')
    {
        Carbon::setTestNow(Carbon::parse($at));

        return $this->postJson('/attend/' . $this->client->attendance_token, $overrides + [
            'employee_number' => 'FWSS ' . $this->kofi->id, 'pin' => '1234',
            'latitude' => self::SITE_LAT + 0.0003, 'longitude' => self::SITE_LNG, 'accuracy' => 15,   // ~33 m away
            'device_id' => self::DEVICE_KOFI,
        ]);
    }

    // ------------------------------------------------------------------ shift clock

    public function test_shift_clock_puts_after_midnight_scans_on_the_night_they_started(): void
    {
        $plan = ShiftPlans::forMonth($this->client->id, '2026-10');

        $r = ShiftClock::resolve(Carbon::parse('2026-10-05 02:10'), $plan, 'night');
        $this->assertSame(['2026-10-04', 'night', true], [$r['date']->toDateString(), $r['shift'], $r['in_own_shift']]);

        // Early arrival for the guard's own shift.
        $r = ShiftClock::resolve(Carbon::parse('2026-10-05 05:15'), $plan, 'day');
        $this->assertSame(['2026-10-05', 'day', true], [$r['date']->toDateString(), $r['shift'], $r['in_own_shift']]);
        $r = ShiftClock::resolve(Carbon::parse('2026-10-05 16:45'), $plan, 'night');
        $this->assertSame(['2026-10-05', 'night'], [$r['date']->toDateString(), $r['shift']]);

        // Day guard signing in at 22:00 is on the wrong shift.
        $r = ShiftClock::resolve(Carbon::parse('2026-10-05 22:00'), $plan, 'day');
        $this->assertSame(['night', false], [$r['shift'], $r['in_own_shift']]);

        // Rotating guard: the clock decides. 07:30 day guard is 90 minutes late.
        $this->assertSame('day', ShiftClock::resolve(Carbon::parse('2026-10-05 11:00'), $plan, 'rotate')['shift']);
        $this->assertSame(90, ShiftClock::resolve(Carbon::parse('2026-10-05 07:30'), $plan, 'day')['late_minutes']);
    }

    // ------------------------------------------------------------------ QR scan

    public function test_scan_page_opens_without_login_and_unknown_tokens_404(): void
    {
        $this->get('/attend/' . $this->client->attendance_token)->assertOk()->assertSee('DVLA HQ')->assertSee('Sign in');
        $this->get('/attend/' . str_repeat('z', 32))->assertNotFound();

        $this->client->update(['status' => 'terminated']);
        $this->get('/attend/' . $this->client->attendance_token)->assertNotFound();
    }

    public function test_good_scan_marks_present_and_registers_the_phone(): void
    {
        $this->scan()->assertOk()->assertJson(['ok' => true, 'status' => 'present']);

        $a = Attendance::sole();
        $this->assertSame(['2026-10-13', 'day', 'present', 'scan'], [$a->work_date->toDateString(), $a->shift, $a->status, $a->source]);
        $this->assertLessThan(60, $a->distance_m);
        $this->assertSame(self::DEVICE_KOFI, $this->kofi->fresh()->attendance_device);

        // Scanning again the same shift changes nothing.
        $this->scan([], '2026-10-13 06:20:00')->assertOk()->assertJsonFragment(['status' => 'present']);
        $this->assertSame(1, Attendance::count());
    }

    public function test_night_scan_after_midnight_counts_for_the_previous_date(): void
    {
        $this->scan(['employee_number' => (string) $this->ama->id, 'pin' => '5678', 'device_id' => self::DEVICE_AMA], '2026-10-13 01:30:00')
            ->assertOk()->assertJson(['status' => 'present']);
        $a = Attendance::sole();
        $this->assertSame(['2026-10-12', 'night'], [$a->work_date->toDateString(), $a->shift]);
    }

    public function test_wrong_pin_is_refused_and_locks_after_five_tries(): void
    {
        RateLimiter::clear('attendance-pin:' . $this->kofi->id);
        for ($i = 0; $i < 5; $i++) {
            $this->scan(['pin' => '0000'])->assertStatus(422)->assertJson(['ok' => false, 'message' => 'Employee number or PIN is wrong.']);
        }
        $this->scan()->assertStatus(422)->assertJsonFragment(['ok' => false])->assertSee('Too many wrong PINs');
        $this->assertSame(0, Attendance::count());
    }

    public function test_guard_without_a_pin_is_told_to_ask_for_one(): void
    {
        $new = $this->makeGuard('NEW RECRUIT', $this->client, 'day', null);
        $this->scan(['employee_number' => 'FWSS' . $new->id, 'pin' => '1111'])->assertStatus(422)->assertSee('do not have an attendance PIN');
    }

    public function test_scan_far_from_site_is_held_for_review(): void
    {
        $this->scan(['latitude' => self::SITE_LAT + 0.02])   // ~2.2 km away
            ->assertOk()->assertJson(['ok' => true, 'status' => 'pending']);

        $a = Attendance::sole();
        $this->assertSame('pending', $a->status);
        $this->assertStringContainsString('from the site', $a->flags[0]);
    }

    public function test_scan_without_location_is_held_for_review(): void
    {
        $this->scan(['latitude' => null, 'longitude' => null])->assertOk()->assertJson(['status' => 'pending']);
        $this->assertSame(['Phone did not share its location'], Attendance::sole()->flags);
    }

    public function test_one_phone_signing_in_two_guards_holds_the_second(): void
    {
        $this->scan()->assertJson(['status' => 'present']);
        // Ama (night guard) uses Kofi's phone, at night.
        $this->scan(['employee_number' => (string) $this->ama->id, 'pin' => '5678'], '2026-10-13 18:05:00')
            ->assertJson(['status' => 'pending']);
        $flags = Attendance::where('employee_id', $this->ama->id)->sole()->flags;
        $this->assertStringContainsString('Phone belongs to KOFI MENSAH', implode(' ', $flags));

        // A second day guard borrowing Kofi's phone for the same shift.
        $kwame = $this->makeGuard('KWAME BOATENG', $this->client, 'day', '2468');
        $this->scan(['employee_number' => (string) $kwame->id, 'pin' => '2468'], '2026-10-13 06:10:00')->assertJson(['status' => 'pending']);
        $this->assertStringContainsString('Same phone already signed in KOFI MENSAH', implode(' ', Attendance::where('employee_id', $kwame->id)->sole()->flags));
    }

    public function test_new_phone_and_wrong_shift_and_wrong_client_are_held(): void
    {
        $this->scan()->assertJson(['status' => 'present']);

        // Next day from a different phone.
        $this->scan(['device_id' => 'cccccccc-1111-2222-3333-444444444444'], '2026-10-14 06:00:00')->assertJson(['status' => 'pending']);
        $this->assertContains('Different phone from the one this guard normally uses',
            Attendance::where('work_date', '2026-10-14')->sole()->flags);

        // Day guard signing in at night.
        $this->scan([], '2026-10-14 21:00:00')->assertJson(['status' => 'pending']);
        $this->assertStringContainsString('rostered on day shift', implode(' ', Attendance::where('work_date', '2026-10-14')->where('shift', 'night')->sole()->flags));

        // Guard posted elsewhere scanning this client's poster.
        $other = $this->makeClient('SSNIT CAR PARK', $this->accra);
        $yaw = $this->makeGuard('YAW OWUSU', $other, 'day', '1357');
        $this->scan(['employee_number' => (string) $yaw->id, 'pin' => '1357', 'device_id' => 'dddddddd-1111-2222-3333-444444444444'], '2026-10-15 06:00:00')
            ->assertJson(['status' => 'pending']);
        $this->assertContains('Not on this client\'s roster', Attendance::where('employee_id', $yaw->id)->sole()->flags);
    }

    public function test_scan_does_not_overwrite_what_operations_marked(): void
    {
        Attendance::create(['client_id' => $this->client->id, 'employee_id' => $this->kofi->id, 'work_date' => '2026-10-13',
            'shift' => 'day', 'status' => 'off', 'source' => 'manual']);
        $this->scan()->assertOk()->assertSee('already recorded you as off duty');
        $this->assertSame('off', Attendance::sole()->status);
    }

    // ------------------------------------------------------------------ month sheet

    public function test_month_sheet_counts_only_marked_absences_and_off_days_as_paid(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 12:00'));   // 5 days elapsed
        $mark = fn ($e, $day, $shift, $status) => Attendance::create(['client_id' => $this->client->id, 'employee_id' => $e->id,
            'work_date' => "2026-10-0$day", 'shift' => $shift, 'status' => $status, 'source' => 'manual']);

        foreach ([1, 2, 3] as $d) { $mark($this->kofi, $d, 'day', 'present'); }
        $mark($this->kofi, 4, 'day', 'off');
        $mark($this->kofi, 5, 'day', 'absent');
        foreach ([1, 2] as $d) { $mark($this->ama, $d, 'night', 'present'); }
        $mark($this->ama, 3, 'night', 'pending');
        // Ama days 4-5 not marked: blank, NOT absent (the spreadsheet would have said absent).

        $sheet = new MonthSheet($this->client, '2026-10');
        $rows = collect($sheet->rows)->keyBy(fn ($r) => $r['employee']->name);

        $this->assertSame(['worked' => 3, 'paid' => 4, 'absent' => 1, 'pending' => 0, 'unmarked' => 0],
            array_intersect_key($rows['KOFI MENSAH'], array_flip(['worked', 'paid', 'absent', 'pending', 'unmarked'])));
        $this->assertSame(['worked' => 2, 'paid' => 2, 'absent' => 0, 'pending' => 1, 'unmarked' => 2],
            array_intersect_key($rows['AMA OWUSU'], array_flip(['worked', 'paid', 'absent', 'pending', 'unmarked'])));

        // Plan 2 day + 1 night = 3; 2 guards posted -> shortage 1 ("TOTAL SHORTAGES = 1").
        $this->assertSame(3, $sheet->totals['required']);
        $this->assertSame(2, $sheet->totals['posted']);
        $this->assertSame(1, $sheet->totals['shortage']);
        // Day: 1 present on days 1-3 (need 2) -> 3 short, days 4-5 none -> 4 short. Night: days 3,4,5 short -> 3. Total 10.
        $this->assertSame(10, $sheet->totals['uncovered_guard_days']);
        $this->assertSame('P', MonthSheet::code($rows['KOFI MENSAH']['cells'][1]));
        $this->assertSame('OFF', MonthSheet::code($rows['KOFI MENSAH']['cells'][4]));

        // The overview works the same numbers out with aggregate queries.
        $o = MonthOverview::rows(collect([$this->client]), '2026-10')->first();
        // worked = present guard-shifts: Kofi 3 + Ama 2.
        $this->assertSame([3, 2, 1, 5, 1, 1, 10], [$o['required'], $o['posted'], $o['shortage'], $o['worked'], $o['absent'], $o['pending'], $o['uncovered_guard_days']]);
    }

    public function test_changing_the_plan_keeps_earlier_months(): void
    {
        $this->actingAs($this->ops)->post("/attendance/{$this->client->id}/plan", [
            'day_guards' => 3, 'night_guards' => 3, 'effective_month' => '2026-11',
            'day_start' => '07:00', 'day_end' => '19:00', 'night_start' => '19:00', 'night_end' => '07:00',
        ])->assertRedirect();

        $this->assertSame(3, ShiftPlans::forMonth($this->client->id, '2026-10')->totalGuards());
        $this->assertSame(6, ShiftPlans::forMonth($this->client->id, '2026-12')->totalGuards());
        $this->assertNull(ShiftPlans::forMonth($this->client->id, '2026-09'));
        $this->assertSame('07:00', ShiftPlans::forMonth($this->client->id, '2026-11')->day_start);
        $this->assertSame('3', $this->client->fresh()->guards);   // clients.guards follows the plan in force today
    }

    // ------------------------------------------------------------------ operations screens

    public function test_operations_pages_render(): void
    {
        $this->scan()->assertJson(['status' => 'present']);
        $this->scan(['employee_number' => (string) $this->ama->id, 'pin' => '5678', 'device_id' => self::DEVICE_AMA, 'latitude' => 6.0], '2026-10-13 18:30:00');
        Carbon::setTestNow(Carbon::parse('2026-10-13 19:00'));

        $this->actingAs($this->ops)->get('/attendance')->assertOk()->assertSee('DVLA HQ')->assertSee('Guard attendance');
        $this->actingAs($this->ops)->get("/attendance/{$this->client->id}?month=2026-10")->assertOk()
            ->assertSee('KOFI MENSAH')->assertSee('AMA OWUSU')->assertSee('Shift plan')->assertSee('FWSS ' . $this->kofi->id);
        $this->actingAs($this->ops)->get('/attendance/review')->assertOk()->assertSee('AMA OWUSU')->assertSee('from the site');
        $this->actingAs($this->ops)->get("/attendance/{$this->client->id}/poster")->assertOk()->assertSee('/attend/' . $this->client->attendance_token);

        $res = $this->actingAs($this->ops)->get("/attendance/{$this->client->id}/export?month=2026-10");
        $res->assertOk();
        $this->assertStringContainsString('spreadsheetml', $res->headers->get('content-type'));
    }

    public function test_client_forms_show_shift_plan_fields(): void
    {
        $this->actingAs($this->headOffice)->get("/client/{$this->client->id}/edit")->assertOk()
            ->assertSee('Guards required per shift')->assertSee('name="night_guards"', false)->assertSee('Plan applies from');
        $this->actingAs($this->headOffice)->get('/client/create')->assertOk()
            ->assertSee('name="day_guards"', false)->assertDontSee('Plan applies from');
    }

    public function test_branch_cannot_open_another_fields_client(): void
    {
        $kumasiClient = $this->makeClient('EJURA HOSPITAL', $this->kumasi);

        // Operations department sees every client; other branch staff only their own field.
        $adminDept = Department::create(['name' => 'Administration']);
        $branch = User::factory()->create(['department_id' => $adminDept->id,
            'role_id' => Role::create(['name' => 'Admin Assistant', 'department_id' => $adminDept->id])->id, 'field_id' => $this->accra->id]);

        $this->actingAs($branch)->get("/attendance/{$kumasiClient->id}")->assertForbidden();
        $this->actingAs($branch)->get('/attendance')->assertDontSee('EJURA HOSPITAL')->assertSee('DVLA HQ');
        $this->actingAs($this->ops)->get('/attendance')->assertSee('EJURA HOSPITAL');
        $this->actingAs($this->headOffice)->get('/attendance')->assertSee('EJURA HOSPITAL');
    }

    public function test_marking_a_cell_by_hand(): void
    {
        $url = "/attendance/{$this->client->id}/mark";
        $this->actingAs($this->ops)->postJson($url, ['employee_id' => $this->kofi->id, 'date' => '2026-10-12', 'shift' => 'day', 'status' => 'absent'])->assertOk();
        $a = Attendance::sole();
        $this->assertSame(['absent', 'manual', $this->ops->id], [$a->status, $a->source, $a->marked_by]);

        $this->actingAs($this->ops)->postJson($url, ['employee_id' => $this->kofi->id, 'date' => '2026-10-12', 'shift' => 'day', 'status' => 'sick'])->assertOk();
        $this->assertSame('sick', Attendance::sole()->status);

        // Future present is refused, future off day is fine.
        $this->actingAs($this->ops)->postJson($url, ['employee_id' => $this->kofi->id, 'date' => '2026-10-20', 'shift' => 'day', 'status' => 'present'])->assertStatus(422);
        $this->actingAs($this->ops)->postJson($url, ['employee_id' => $this->kofi->id, 'date' => '2026-10-20', 'shift' => 'day', 'status' => 'off'])->assertOk();

        $this->actingAs($this->ops)->postJson($url, ['employee_id' => $this->kofi->id, 'date' => '2026-10-12', 'shift' => 'day', 'status' => 'clear'])->assertOk();
        $this->assertSame(1, Attendance::count());

        // Not posted here.
        $stranger = $this->makeGuard('STRANGER', $this->makeClient('LEXTA', $this->accra), 'day', null);
        $this->actingAs($this->ops)->postJson($url, ['employee_id' => $stranger->id, 'date' => '2026-10-12', 'shift' => 'day', 'status' => 'present'])->assertStatus(422);
    }

    public function test_fill_blanks_marks_unmarked_days_present_for_guards_with_a_shift(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 12:00'));
        Attendance::create(['client_id' => $this->client->id, 'employee_id' => $this->kofi->id, 'work_date' => '2026-10-02', 'shift' => 'day', 'status' => 'absent', 'source' => 'manual']);
        $this->makeGuard('NO SHIFT YET', $this->client, null, null);

        $this->actingAs($this->ops)->post("/attendance/{$this->client->id}/fill", ['month' => '2026-10'])
            ->assertRedirect()->assertSessionHas('success', fn ($m) => str_contains($m, 'Marked 9') && str_contains($m, 'NO SHIFT YET'));

        $this->assertSame('absent', Attendance::where('employee_id', $this->kofi->id)->where('work_date', '2026-10-02')->value('status'));
        $this->assertSame(5, Attendance::where('employee_id', $this->ama->id)->where('shift', 'night')->where('status', 'present')->count());
    }

    public function test_review_approve_with_trust_phone_and_reject(): void
    {
        $this->scan()->assertJson(['status' => 'present']);
        // Kofi got a new phone.
        $newPhone = 'eeeeeeee-1111-2222-3333-444444444444';
        $this->scan(['device_id' => $newPhone], '2026-10-14 06:00:00')->assertJson(['status' => 'pending']);
        $held = Attendance::where('work_date', '2026-10-14')->sole();

        $this->actingAs($this->ops)->post("/attendance/review/{$held->id}", ['decision' => 'approve', 'trust_phone' => 1])->assertRedirect();
        $this->assertSame('present', $held->fresh()->status);
        $this->assertSame($this->ops->id, $held->fresh()->reviewed_by);
        $this->assertSame($newPhone, $this->kofi->fresh()->attendance_device);

        // Next day the new phone goes straight through.
        $this->scan(['device_id' => $newPhone], '2026-10-15 06:00:00')->assertJson(['status' => 'present']);

        // Reject: the shift becomes absent.
        $this->scan(['latitude' => 7.0], '2026-10-16 06:00:00')->assertJson(['status' => 'pending']);
        $held = Attendance::where('work_date', '2026-10-16')->sole();
        $this->actingAs($this->ops)->post("/attendance/review/{$held->id}", ['decision' => 'reject'])->assertRedirect();
        $this->assertSame('absent', $held->fresh()->status);
    }

    public function test_pin_reset_shift_change_and_new_qr(): void
    {
        $this->actingAs($this->ops)->post("/attendance/{$this->client->id}/guard/{$this->kofi->id}/pin")
            ->assertRedirect()->assertSessionHas('new_pin');
        $pin = session('new_pin')['pin'];
        $this->assertMatchesRegularExpression('/^\d{4}$/', $pin);
        $this->assertTrue(Hash::check($pin, $this->kofi->fresh()->attendance_pin));
        $this->scan(['pin' => '1234'])->assertStatus(422);
        $this->scan(['pin' => $pin])->assertOk();

        $this->actingAs($this->ops)->postJson("/attendance/{$this->client->id}/shift", ['employee_id' => $this->kofi->id, 'shift' => 'rotate'])->assertOk();
        $this->assertSame('rotate', Roster::shiftOf($this->kofi, $this->client));

        $old = $this->client->attendance_token;
        $this->actingAs($this->ops)->post("/attendance/{$this->client->id}/token")->assertRedirect();
        $this->assertNotSame($old, $this->client->fresh()->attendance_token);
        $this->get('/attend/' . $old)->assertNotFound();
    }

    public function test_reassigned_guard_leaves_the_old_roster(): void
    {
        $other = $this->makeClient('ST. MICHAEL', $this->accra);
        $this->kofi->update(['client_id' => $other->id]);   // what "Re-assign guard" does

        $this->assertSame(['AMA OWUSU'], Roster::guards($this->client)->pluck('name')->all());
        $this->assertSame(['KOFI MENSAH'], Roster::guards($other)->pluck('name')->all());
        $this->assertNull(Roster::guards($other)->first()->attendance_shift);   // shift was for the old client
    }

    public function test_creating_and_editing_a_client_saves_the_shift_plan(): void
    {
        // clients.phone_number1 exists in production but no migration creates it.
        if (! Schema::hasColumn('clients', 'phone_number1')) {
            Schema::table('clients', fn (Blueprint $t) => $t->string('phone_number1')->nullable());
        }
        $payload = [
            'name' => 'Jane', 'business_name' => 'LANDS - VALUATION', 'phone_number' => '0241234567', 'address' => 'Accra',
            'field_id' => $this->accra->id, 'rate' => 5000, 'start_date' => '2026-09-01',
            'day_guards' => 4, 'night_guards' => 2,
        ];
        $this->actingAs($this->headOffice)->post('/client', ['day_guards' => 0, 'night_guards' => 0] + $payload)->assertSessionHasErrors('day_guards');

        $this->actingAs($this->headOffice)->post('/client', $payload)->assertRedirect();
        $client = Client::where('business_name', 'LANDS - VALUATION')->sole();
        $plan = ShiftPlans::forMonth($client->id, '2026-09');
        $this->assertSame([4, 2, '2026-09-01'], [$plan->day_guards, $plan->night_guards, $plan->effective_month->toDateString()]);
        $this->assertSame('6', $client->guards);

        // Edit without touching the numbers: no new plan.
        $this->actingAs($this->headOffice)->put("/client/{$client->id}", ['address' => 'Cantonments', 'day_guards' => 4, 'night_guards' => 2])->assertRedirect();
        $this->assertSame(1, ClientShiftPlan::where('client_id', $client->id)->count());
        $this->assertSame('Cantonments', $client->fresh()->address);

        // Night-only from November.
        $this->actingAs($this->headOffice)->put("/client/{$client->id}", ['day_guards' => 0, 'night_guards' => 3, 'effective_month' => '2026-11'])->assertRedirect();
        $this->assertSame(6, ShiftPlans::forMonth($client->id, '2026-10')->totalGuards());
        $this->assertSame('Night only', ShiftPlans::forMonth($client->id, '2026-11')->patternLabel());
    }

    // ------------------------------------------------------------------ supervisor kiosk

    public function test_kiosk_marks_guards_at_the_site_and_holds_marks_made_elsewhere(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-13 06:30'));
        $this->actingAs($this->ops)->get("/attendance/{$this->client->id}/kiosk")->assertOk()
            ->assertSee('Day shift')->assertSee('KOFI MENSAH')->assertSee('Other guards at this client');   // Ama is on nights
        $this->actingAs($this->ops)->get("/attendance/{$this->client->id}/id-cards")->assertOk()->assertSee('ISSOBS-GUARD:' . $this->kofi->id);

        $url = "/attendance/{$this->client->id}/kiosk";
        $here = ['latitude' => self::SITE_LAT, 'longitude' => self::SITE_LNG, 'accuracy' => 10, 'date' => '2026-10-13', 'shift' => 'day'];

        // Tap, and scan of the ID card QR text.
        $this->actingAs($this->ops)->postJson($url, $here + ['employee' => (string) $this->kofi->id, 'status' => 'present'])->assertOk()->assertJson(['status' => 'present']);
        $a = Attendance::sole();
        $this->assertSame(['kiosk', $this->ops->id], [$a->source, $a->marked_by]);

        $this->actingAs($this->ops)->postJson($url, ['employee' => 'ISSOBS-GUARD:' . $this->ama->id, 'status' => 'absent', 'shift' => 'night', 'date' => '2026-10-12'] + $here)
            ->assertOk()->assertJson(['status' => 'absent']);

        // Supervisor not at the site: held for review.
        $kwame = $this->makeGuard('KWAME BOATENG', $this->client, 'day', null);
        $this->actingAs($this->ops)->postJson($url, ['employee' => 'FWSS ' . $kwame->id, 'status' => 'present', 'latitude' => 6.7] + $here)
            ->assertOk()->assertJson(['status' => 'pending']);
        $this->assertStringContainsString('Supervisor marked it', Attendance::where('employee_id', $kwame->id)->sole()->flags[0]);

        // Only the current shift; and only this client's guards.
        $this->actingAs($this->ops)->postJson($url, ['employee' => (string) $this->kofi->id, 'status' => 'present', 'date' => '2026-10-01'] + $here)->assertStatus(422);
        $stranger = $this->makeGuard('STRANGER', $this->makeClient('LEXTA', $this->accra), 'day', null);
        $this->actingAs($this->ops)->postJson($url, ['employee' => (string) $stranger->id, 'status' => 'present'] + $here)->assertStatus(422);

        // A guard's own scan held for review, confirmed by the supervisor in person, is cleared.
        Attendance::where('employee_id', $kwame->id)->delete();
        Attendance::create(['client_id' => $this->client->id, 'employee_id' => $kwame->id, 'work_date' => '2026-10-13', 'shift' => 'day',
            'status' => 'pending', 'source' => 'scan', 'flags' => ['Phone did not share its location']]);
        $this->actingAs($this->ops)->postJson($url, ['employee' => (string) $kwame->id, 'status' => 'present'] + $here)->assertJson(['status' => 'present']);
        $this->assertSame($this->ops->id, Attendance::where('employee_id', $kwame->id)->sole()->reviewed_by);
    }

    public function test_shortage_is_worked_out_per_shift(): void
    {
        // 2 day + 2 night needed, 3 day guards posted: still 2 short at night.
        $this->assertSame(2, Roster::shortage(2, 2, ['day' => 3, 'night' => 0, 'rotate' => 0, 'unset' => 0]));
        $this->assertSame(1, Roster::shortage(2, 2, ['day' => 3, 'night' => 0, 'rotate' => 1, 'unset' => 0]));
        $this->assertSame(0, Roster::shortage(1, 0, ['day' => 2, 'night' => 0, 'rotate' => 0, 'unset' => 0]));
    }

    public function test_issue_pins_in_bulk_prints_slips_once(): void
    {
        $new = $this->makeGuard('NEW RECRUIT', $this->client, 'day', null);

        $res = $this->actingAs($this->ops)->post("/attendance/{$this->client->id}/pins", ['mode' => 'missing']);
        $res->assertOk()->assertSee('NEW RECRUIT')->assertDontSee('KOFI MENSAH')->assertSee('FWSS ' . $new->id);
        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));
        preg_match('/<div class="val pin">(\d{4})<\/div>/', $res->getContent(), $m);
        $this->assertTrue(Hash::check($m[1], $new->fresh()->attendance_pin));
        $this->assertTrue(Hash::check('1234', $this->kofi->fresh()->attendance_pin));   // untouched

        $this->actingAs($this->ops)->post("/attendance/{$this->client->id}/pins", ['mode' => 'missing'])->assertOk()->assertSee('already has a PIN');
        $this->actingAs($this->ops)->post("/attendance/{$this->client->id}/pins", ['mode' => 'all'])->assertOk()->assertSee('KOFI MENSAH');
        $this->assertFalse(Hash::check('1234', $this->kofi->fresh()->attendance_pin));

        // The grid shows a PIN button on every row, and client pages link to attendance.
        $this->actingAs($this->ops)->get("/attendance/{$this->client->id}")->assertOk()->assertSee('Issue PINs')->assertSee('Reset');
        $this->actingAs($this->headOffice)->get("/client/{$this->client->id}")->assertSee('Attendance, QR poster');
    }
}

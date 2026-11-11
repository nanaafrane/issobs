<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Department;
use App\Models\employee;
use App\Models\Field;
use App\Models\Role;
use App\Models\User;
use App\Support\Attendance\PinSeal;
use App\Support\Attendance\Roster;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Offline sign-in: the phone's outbox uploads to attend/{token}/sync and api/attendance/kiosk-sync. */
class AttendanceOfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    private const DEVICE = 'phone-0000000000000001';

    private User $ops;
    private Field $field;
    private Client $client;
    private employee $kofi;   // day guard, PIN 4821
    private employee $esi;    // night guard, PIN 1357

    protected function setUp(): void
    {
        parent::setUp();
        config(['attendance.offline.key_path' => storage_path('framework/testing/attendance-offline-test.pem')]);
        Carbon::setTestNow(Carbon::parse('2026-11-11 08:00'));

        $dept = Department::create(['name' => 'Field Office']);
        $this->ops = User::factory()->create(['department_id' => $dept->id, 'name' => 'Ops Supervisor',
            'role_id' => Role::create(['name' => 'Supervisor', 'department_id' => $dept->id])->id]);
        $this->field = Field::create(['name' => 'Accra', 'user_id' => $this->ops->id, 'number' => '0300000000']);
        $this->ops->update(['field_id' => $this->field->id]);
        $this->client = Client::create(['name' => 'DVLA', 'business_name' => 'DVLA HQ', 'phone_number' => '0240000000', 'address' => 'Accra',
            'field_id' => $this->field->id, 'status' => 'active', 'ho_status' => 'approved', 'user_id' => $this->ops->id]);
        $this->client->forceFill(['attendance_token' => Str::random(32), 'latitude' => 5.6037, 'longitude' => -0.1870, 'attendance_radius_m' => 200])->save();
        DB::table('client_shift_plans')->insert(['client_id' => $this->client->id, 'effective_month' => '2026-11-01', 'day_guards' => 1, 'night_guards' => 1,
            'day_start' => '06:00', 'day_end' => '18:00', 'night_start' => '18:00', 'night_end' => '06:00', 'created_at' => now(), 'updated_at' => now()]);

        $this->kofi = $this->guard('KOFI MENSAH', 'day', '4821');
        $this->esi = $this->guard('ESI BOATENG', 'night', '1357');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function guard(string $name, string $shift, string $pin): employee
    {
        $e = new employee();
        $e->forceFill(['name' => $name, 'status' => 'Active', 'ho_status' => 'approved', 'client_id' => $this->client->id, 'field_id' => $this->field->id,
            'basic_salary' => 1500, 'allowances' => 0, 'payment_type' => 'Cash', 'location' => 'Main gate', 'user_id' => $this->ops->id,
            'attendance_pin' => Hash::make($pin)])->save();
        Roster::setShift($this->client, $e, $shift);

        return $e;
    }

    /** One sign-in as the phone makes it: sealed PIN, phone time, at the site. */
    private function item(employee $guard, string $pin, ?Carbon $phoneTime = null, array $over = []): array
    {
        $uuid = (string) Str::uuid();
        $number = 'FWSS ' . $guard->id;

        return $over + [
            'uuid' => $uuid, 'captured_at' => ($phoneTime ?? now())->getTimestampMs(), 'offline' => $phoneTime !== null,
            'employee_number' => $number, 'pin_sealed' => PinSeal::seal($uuid, $number, $pin),
            'latitude' => 5.6038, 'longitude' => -0.1871, 'accuracy' => 12, 'device_id' => self::DEVICE . $guard->id,
        ];
    }

    private function upload(array $items, ?Carbon $phoneNow = null, ?string $token = null)
    {
        return $this->postJson(route('attend.sync', $token ?? $this->client->attendance_token), [
            'sent_at' => ($phoneNow ?? now())->getTimestampMs(), 'items' => $items,
        ]);
    }

    public function test_an_online_sign_in_through_the_outbox_is_present_and_not_marked_offline(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 06:10'));
        $res = $this->upload([$this->item($this->kofi, '4821')])->assertOk();

        $this->assertSame('accepted', $res->json('results.0.result'));
        $a = Attendance::sole();
        $this->assertSame(Attendance::PRESENT, $a->status);
        $this->assertFalse($a->captured_offline);
        $this->assertNull($a->synced_at);
    }

    public function test_the_same_item_uploaded_twice_is_saved_once_and_gets_the_same_answer(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 06:10'));
        $item = $this->item($this->kofi, '4821');

        $first = $this->upload([$item])->assertOk()->json('results.0');
        $again = $this->upload([$item])->assertOk()->json('results.0');

        $this->assertSame(1, Attendance::count());
        $this->assertSame(1, DB::table('attendance_sync_items')->count());
        $this->assertSame($first['result'], $again['result']);
        $this->assertTrue($again['duplicate']);
        $this->assertSame(Attendance::PRESENT, $again['status']);
    }

    public function test_a_sign_in_made_offline_keeps_the_time_it_was_made_and_says_so(): void
    {
        $madeAt = Carbon::parse('2026-11-11 06:05');   // uploaded at 08:00 when signal came back
        $res = $this->upload([$this->item($this->kofi, '4821', $madeAt)])->assertOk();

        $this->assertSame('accepted', $res->json('results.0.result'));
        $a = Attendance::sole();
        $this->assertSame(Attendance::PRESENT, $a->status);
        $this->assertSame('2026-11-11 06:05', $a->checked_in_at->format('Y-m-d H:i'));
        $this->assertTrue($a->captured_offline);
        $this->assertSame('2026-11-11 08:00', $a->synced_at->format('Y-m-d H:i'));
        $this->assertStringContainsString('Made offline at Wed 11 Nov 06:05, uploaded Wed 11 Nov 08:00', $a->note);
    }

    public function test_a_wrong_phone_clock_is_corrected_and_held_for_review(): void
    {
        // The phone is 3 hours slow: it thinks the sign-in at 06:05 was at 03:05, and that "now" is 05:00.
        $slow = fn (string $t) => Carbon::parse($t)->subHours(3);
        $res = $this->upload([$this->item($this->kofi, '4821', $slow('2026-11-11 06:05'))], $slow('2026-11-11 08:00'))->assertOk();

        $this->assertSame('held', $res->json('results.0.result'));
        $a = Attendance::sole();
        $this->assertSame(Attendance::PENDING, $a->status);
        $this->assertSame('2026-11-11 06:05', $a->checked_in_at->format('Y-m-d H:i'));
        $this->assertSame('day', $a->shift);
        $this->assertStringContainsString('Phone clock was 3h 00m slow', implode(' ', $a->flags));
    }

    public function test_a_night_sign_in_made_offline_after_midnight_belongs_to_the_night_it_started(): void
    {
        $this->upload([$this->item($this->esi, '1357', Carbon::parse('2026-11-11 02:00'))])->assertOk();

        $a = Attendance::sole();
        $this->assertSame('2026-11-10', $a->work_date->toDateString());
        $this->assertSame('night', $a->shift);
    }

    public function test_a_wrong_pin_is_refused_and_the_refusal_is_remembered(): void
    {
        $item = $this->item($this->kofi, '0000', Carbon::parse('2026-11-11 06:05'));
        $res = $this->upload([$item])->assertOk();

        $this->assertSame('rejected', $res->json('results.0.result'));
        $this->assertSame('Employee number or PIN is wrong.', $res->json('results.0.message'));
        $this->assertSame(0, Attendance::count());
        $this->assertSame('rejected', $this->upload([$item])->json('results.0.result'));
    }

    public function test_a_sealed_pin_cannot_be_moved_onto_another_guards_sign_in(): void
    {
        $kofis = $this->item($this->kofi, '4821');
        $stolen = $this->item($this->esi, 'x', null, ['pin_sealed' => $kofis['pin_sealed'], 'employee_number' => 'FWSS ' . $this->esi->id]);

        $res = $this->upload([$stolen])->assertOk();
        $this->assertSame('rejected', $res->json('results.0.result'));
        $this->assertStringContainsString('could not be read', $res->json('results.0.message'));
    }

    public function test_times_in_the_future_or_older_than_a_week_are_refused(): void
    {
        $res = $this->upload([
            $this->item($this->kofi, '4821', Carbon::parse('2026-11-11 09:00')),
            $this->item($this->kofi, '4821', Carbon::parse('2026-11-02 06:05')),
        ])->assertOk();

        $this->assertSame(['rejected', 'rejected'], array_column($res->json('results'), 'result'));
        $this->assertStringContainsString('in the future', $res->json('results.0.message'));
        $this->assertStringContainsString('more than 7 days old', $res->json('results.1.message'));
        $this->assertSame(0, Attendance::count());
    }

    public function test_uploaded_long_after_it_was_made_is_held_for_review(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-12 08:00'));
        $this->upload([$this->item($this->kofi, '4821', Carbon::parse('2026-11-11 06:05'))])->assertOk();

        $a = Attendance::sole();
        $this->assertSame(Attendance::PENDING, $a->status);
        $this->assertSame('2026-11-11', $a->work_date->toDateString());
        $this->assertStringContainsString('Uploaded 25h 55m after', implode(' ', $a->flags));
    }

    public function test_review_all_holds_every_offline_sign_in(): void
    {
        config(['attendance.offline.review' => 'all']);
        $this->upload([$this->item($this->kofi, '4821', Carbon::parse('2026-11-11 06:05'))])->assertOk();

        $this->assertSame(Attendance::PENDING, Attendance::sole()->status);
        $this->assertSame(['Made while the phone was offline'], Attendance::sole()->flags);
    }

    public function test_a_locked_pin_is_retried_later_and_not_recorded(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->upload([$this->item($this->kofi, '0000')]);
        }
        $item = $this->item($this->kofi, '4821', Carbon::parse('2026-11-11 06:05'));
        $res = $this->upload([$item])->assertOk();

        $this->assertSame('retry', $res->json('results.0.result'));
        $this->assertSame(0, DB::table('attendance_sync_items')->where('uuid', $item['uuid'])->count());

        Carbon::setTestNow(now()->addMinutes(16));
        $this->assertSame('accepted', $this->upload([$item])->json('results.0.result'));
    }

    public function test_a_replaced_poster_answers_gone(): void
    {
        $old = $this->client->attendance_token;
        $this->client->forceFill(['attendance_token' => Str::random(32)])->save();

        $this->upload([$this->item($this->kofi, '4821')], null, $old)->assertStatus(410)->assertJson(['gone' => true]);
    }

    public function test_the_sign_in_page_carries_the_key_for_sealing_pins(): void
    {
        $this->get(route('attend.show', $this->client->attendance_token))->assertOk()
            ->assertSee(json_encode(PinSeal::publicKeySpki()), false)->assertSee('offline/outbox.js')
            ->assertSee(json_encode(route('attend.sync', $this->client->attendance_token)), false);
    }

    // ------------------------------------------------------------------ supervisor kiosk

    private function kioskToken(): string
    {
        return $this->actingAs($this->ops)->postJson(route('attendance.kioskToken'), ['device_id' => self::DEVICE])
            ->assertOk()->json('token');
    }

    private function kioskUpload(string $token, array $items, ?Carbon $phoneNow = null)
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token)->postJson(route('api.attendance.kioskSync'), [
            'sent_at' => ($phoneNow ?? now())->getTimestampMs(), 'client_id' => $this->client->id, 'items' => $items,
        ]);
    }

    private function mark(employee $guard, string $status, Carbon $madeAt, string $date, string $shift = 'day'): array
    {
        return ['uuid' => (string) Str::uuid(), 'captured_at' => $madeAt->getTimestampMs(), 'offline' => true, 'employee' => (string) $guard->id,
            'date' => $date, 'shift' => $shift, 'status' => $status, 'latitude' => 5.6038, 'longitude' => -0.1871, 'accuracy' => 10, 'device_id' => self::DEVICE];
    }

    public function test_kiosk_marks_made_offline_upload_with_the_kiosk_token_after_the_session_is_gone(): void
    {
        $token = $this->kioskToken();
        $res = $this->kioskUpload($token, [
            $this->mark($this->kofi, 'present', Carbon::parse('2026-11-11 06:20'), '2026-11-11'),
            $this->mark($this->esi, 'absent', Carbon::parse('2026-11-11 06:20'), '2026-11-10', 'night'),
        ])->assertOk();

        $this->assertSame(['accepted', 'accepted'], array_column($res->json('results'), 'result'));
        $kofi = Attendance::where('employee_id', $this->kofi->id)->sole();
        $this->assertSame(Attendance::PRESENT, $kofi->status);
        $this->assertSame('kiosk', $kofi->source);
        $this->assertSame($this->ops->id, (int) $kofi->marked_by);
        $this->assertTrue($kofi->captured_offline);
        $this->assertSame('06:20', $kofi->checked_in_at->format('H:i'));
        $this->assertSame(Attendance::ABSENT, Attendance::where('employee_id', $this->esi->id)->value('status'));
    }

    public function test_a_kiosk_mark_made_offline_never_overwrites_a_newer_entry(): void
    {
        $token = $this->kioskToken();
        Carbon::setTestNow(Carbon::parse('2026-11-11 07:30'));
        Attendance::create(['client_id' => $this->client->id, 'employee_id' => $this->kofi->id, 'work_date' => '2026-11-11', 'shift' => 'day',
            'status' => Attendance::ABSENT, 'source' => 'manual']);   // operations, 07:30
        Carbon::setTestNow(Carbon::parse('2026-11-11 08:00'));

        $res = $this->kioskUpload($token, [$this->mark($this->kofi, 'present', Carbon::parse('2026-11-11 06:20'), '2026-11-11')])->assertOk();

        $this->assertSame('skipped', $res->json('results.0.result'));
        $this->assertSame(Attendance::ABSENT, Attendance::sole()->status);
    }

    public function test_kiosk_upload_needs_a_kiosk_token(): void
    {
        $this->postJson(route('api.attendance.kioskSync'), ['client_id' => $this->client->id, 'items' => [[]]])->assertUnauthorized();

        $other = $this->ops->createToken('something-else', ['other'])->plainTextToken;
        $this->kioskUpload($other, [$this->mark($this->kofi, 'present', now(), '2026-11-11')])->assertForbidden();
    }

    public function test_asking_for_a_kiosk_token_again_replaces_the_old_one(): void
    {
        $first = $this->kioskToken();
        $second = $this->kioskToken();

        $this->assertSame(1, $this->ops->tokens()->count());
        $this->kioskUpload($first, [$this->mark($this->kofi, 'present', now(), '2026-11-11')])->assertUnauthorized();
        $this->kioskUpload($second, [$this->mark($this->kofi, 'present', now(), '2026-11-11')])->assertOk();
    }

    public function test_a_supervisor_from_another_office_cannot_upload_for_this_client(): void
    {
        $other = User::factory()->create(['name' => 'Tema Sup', 'department_id' => $this->ops->department_id, 'role_id' => $this->ops->role_id,
            'field_id' => Field::create(['name' => 'Tema', 'user_id' => $this->ops->id, 'number' => '1'])->id]);
        $token = $other->createToken('attendance-kiosk:x', ['attendance-kiosk'])->plainTextToken;

        $res = $this->kioskUpload($token, [$this->mark($this->kofi, 'present', now(), '2026-11-11')])->assertOk();
        $this->assertSame('rejected', $res->json('results.0.result'));
        $this->assertSame(0, Attendance::count());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Bank;
use App\Models\Client;
use App\Models\Department;
use App\Models\employee;
use App\Models\Field;
use App\Models\Role;
use App\Models\Salary;
use App\Models\User;
use App\Support\Attendance\Roster;
use App\Support\SalaryShifts;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The guard's shift on the salary pages: kept on the salary, with the month worked. */
class SalaryShiftsTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;
    private Field $field;
    private Client $client;
    private Bank $bank;
    private employee $kofi;   // night, cash (MoMo)
    private employee $ama;    // day, bank
    private employee $yaw;    // no shift

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-25 10:00'));
        $dept = Department::create(['name' => 'Accounts']);
        $this->finance = User::factory()->create(['department_id' => $dept->id, 'role_id' => Role::create(['name' => 'Finance Manager', 'department_id' => $dept->id])->id, 'name' => 'Fin']);
        $this->field = Field::create(['name' => 'Accra', 'user_id' => $this->finance->id, 'number' => '0300000000']);
        $this->finance->update(['field_id' => $this->field->id]);
        $this->client = Client::create(['name' => 'DVLA', 'business_name' => 'DVLA HQ', 'phone_number' => '0240000000', 'address' => 'Accra',
            'field_id' => $this->field->id, 'status' => 'active', 'ho_status' => 'approved', 'user_id' => $this->finance->id]);
        $this->bank = Bank::create(['name' => 'GCB', 'kind' => 'bank', 'branch' => 'HQ', 'acc_number' => '1']);

        $this->kofi = $this->guard('KOFI MENSAH', 'Cash', 'night');
        $this->ama = $this->guard('AMA SERWAA', 'Bank', 'day');
        $this->yaw = $this->guard('YAW OWUSU', 'Cash', null);

        // Kofi: 3 nights, a double on the 4th (day + night), 1 absent, 1 off.
        foreach ([['2026-11-02', 'night', 'present'], ['2026-11-03', 'night', 'present'], ['2026-11-04', 'night', 'present'],
            ['2026-11-04', 'day', 'present'], ['2026-11-05', 'night', 'absent'], ['2026-11-06', 'night', 'off']] as [$d, $sh, $st]) {
            Attendance::create(['client_id' => $this->client->id, 'employee_id' => $this->kofi->id, 'work_date' => $d, 'shift' => $sh, 'status' => $st, 'source' => 'manual']);
        }
        // Ama is rostered on days but worked 4 nights.
        foreach (['2026-11-02', '2026-11-03', '2026-11-04', '2026-11-05'] as $d) {
            Attendance::create(['client_id' => $this->client->id, 'employee_id' => $this->ama->id, 'work_date' => $d, 'shift' => 'night', 'status' => 'present', 'source' => 'manual']);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function guard(string $name, string $pay, ?string $shift): employee
    {
        $e = new employee();
        $e->forceFill(['name' => $name, 'status' => 'Active', 'ho_status' => 'approved', 'client_id' => $this->client->id, 'field_id' => $this->field->id,
            'basic_salary' => 1500, 'allowances' => 0, 'payment_type' => $pay, 'location' => 'Main gate', 'user_id' => $this->finance->id])->save();
        \Illuminate\Support\Facades\DB::table('payment_infos')->insert(['employee_id' => $e->id, 'bank_id' => $pay === 'Bank' ? $this->bank->id : null,
            'acc_number' => $pay === 'Bank' ? '123' : null, 'user_id' => $this->finance->id, 'created_at' => now(), 'updated_at' => now()]);
        if ($shift) {
            Roster::setShift($this->client, $e, $shift);
        }

        return $e;
    }

    private function generate(): void
    {
        $this->actingAs($this->finance)->post('/salaries', ['salary_month' => '2026-11', 'employees' => [$this->kofi->id, $this->ama->id, $this->yaw->id]])->assertRedirect();
    }

    public function test_the_shift_is_kept_on_the_salary_when_salaries_are_generated(): void
    {
        $this->generate();
        $this->assertSame(['night', 'day', null], [
            Salary::where('employee_id', $this->kofi->id)->value('shift'),
            Salary::where('employee_id', $this->ama->id)->value('shift'),
            Salary::where('employee_id', $this->yaw->id)->value('shift'),
        ]);

        // A later roster change does not rewrite the salary; the page shows both.
        Roster::setShift($this->client, $this->kofi, 'day');
        $s = Salary::where('employee_id', $this->kofi->id)->sole();
        $this->assertSame('night', $s->fresh()->shift);
        $info = SalaryShifts::forSalaries([$s])[$s->id];
        $this->assertSame(['night', true, 'day'], [$info['shift'], $info['snapshot'], $info['now']]);
        $this->assertStringContainsString('→ Day now', SalaryShifts::badge($info['shift'], $info['snapshot'], $info['now']));
    }

    public function test_worked_counts_shifts_doubles_absences_and_flags_the_wrong_shift(): void
    {
        $this->generate();
        $k = Salary::where('employee_id', $this->kofi->id)->sole();
        $a = Salary::where('employee_id', $this->ama->id)->sole();
        $info = SalaryShifts::forSalaries([$k, $a]);
        $w = $info[$k->id]['worked'];
        $this->assertSame([3, 1, 1, 1, 1], [$w['night'], $w['day'], $w['double'], $w['absent'], $w['off']]);
        $html = SalaryShifts::worked($w, 'night');
        $this->assertStringContainsString('<strong>4</strong>', $html);
        $this->assertStringContainsString('1 double', $html);
        $this->assertStringContainsString('1 absent', $html);
        $this->assertStringContainsString('mostly night', SalaryShifts::worked($info[$a->id]['worked'], 'day'));
        $this->assertSame(['day' => 1, 'night' => 1, 'rotate' => 0, 'none' => 0], SalaryShifts::counts($info));
    }

    public function test_older_salaries_show_the_current_shift_marked_now(): void
    {
        $s = new Salary();
        $s->forceFill(['employee_id' => $this->kofi->id, 'salary_month' => '2026-10-01', 'client_id' => $this->client->id, 'field_id' => $this->field->id,
            'payment_type' => 'Cash', 'basic_salary' => 1500, 'payment_status' => 'pending'])->save();
        $info = SalaryShifts::forSalaries([$s])[$s->id];
        $this->assertSame(['night', false], [$info['shift'], $info['snapshot']]);
        $this->assertStringContainsString('Night <span style="opacity:.7;font-weight:400">now</span>', SalaryShifts::badge($info['shift'], false));
    }

    public function test_the_salary_pages_show_shift_and_worked(): void
    {
        $this->generate();
        Salary::query()->update(['payment_status' => 'pending']);
        $kofi = Salary::where('employee_id', $this->kofi->id)->sole();

        // Cash (MoMo), bank and client pages; their hold pages; salariesCreate; the edit page.
        $pages = [
            '/salariesCashMonth/' . $this->field->id . '/2026-11-01' => ['KOFI MENSAH', 'Night'],
            '/salariesBankMonth/' . $this->bank->id . '/2026-11-01' => ['AMA SERWAA', 'Day', 'mostly night'],
            '/salariesClientMonth/' . $this->client->id . '/2026-11-01' => ['KOFI MENSAH', 'AMA SERWAA', 'No shift'],
            '/salariesCreate?month=2026-11' => ['KOFI MENSAH', 'Night'],
            '/salaries/' . $kofi->id . '/edit' => ['Night', '1 double', '1 absent'],
        ];
        foreach ($pages as $url => $texts) {
            $res = $this->actingAs($this->finance)->get($url);
            $res->assertOk();
            foreach ($texts as $t) {
                $res->assertSee($t, false);
            }
            if (! str_contains($url, '/edit')) {
                $res->assertSee('<th>SHIFT</th>', false)->assertSee('<th>WORKED</th>', false)->assertSee('class="d-flex flex-wrap align-items-center gap-2 mb-2 shift-bar"', false);
            }
        }

        Salary::query()->update(['payment_status' => 'hold']);
        foreach (['/salariesCashHoldMonth/' . $this->field->id . '/2026-11-01', '/salariesBankHoldMonth/' . $this->bank->id . '/2026-11-01',
            '/salariesClientHoldMonth/' . $this->client->id . '/2026-11-01'] as $url) {
            $this->actingAs($this->finance)->get($url)->assertOk()->assertSee('<th>SHIFT</th>', false);
        }
    }
}

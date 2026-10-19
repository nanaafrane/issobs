<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use App\Support\Attendance\MonthOverview;
use App\Support\Dashboards\AccountsDashboard;
use App\Support\Dashboards\DashboardContext;
use App\Support\Dashboards\HrDashboard;
use App\Support\Dashboards\OperationsDashboard;
use App\Support\EmployeeMovementsReport;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Accounts, HR and Operations dashboards: who lands where, what they may see, and that the
 * figures agree with the reports they summarise (not the old dashboard's definitions).
 */
class DashboardsTest extends TestCase
{
    use RefreshDatabase;

    private array $dept = [];
    private array $role = [];
    private array $field = [];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20 10:00:00');

        foreach (['Accounts', 'Administration', 'System', 'HR', 'Guest', 'Operations'] as $n) {
            $this->dept[$n] = DB::table('departments')->insertGetId(['name' => $n]);
        }
        // Role 7 must be Guard: the system identifies guards by that id.
        foreach (['Invoice', 'Finance Manager', 'Manager', 'Officer', 'Director', 'Admin Assistant', 'Guard'] as $n) {
            $this->role[$n] = DB::table('roles')->insertGetId(['name' => $n]);
        }
        foreach (['Accra', 'Botwe', 'Tema', 'Takoradi', 'Koforidua', 'Kumasi', 'Shai Hills'] as $n) {
            $this->field[$n] = DB::table('fields')->insertGetId(['name' => $n, 'parent_field_id' => $n === 'Shai Hills' ? 3 : null]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(string $dept, string $role, ?string $field = null): User
    {
        return User::factory()->create([
            'department_id' => $this->dept[$dept], 'role_id' => $this->role[$role], 'field_id' => $field ? $this->field[$field] : null,
        ]);
    }

    private function client(string $field, int $day = 0, int $night = 0): int
    {
        $id = DB::table('clients')->insertGetId(['name' => 'C', 'business_name' => 'Client ' . $field . uniqid(), 'field_id' => $this->field[$field],
            'status' => 'Active', 'ho_status' => 'approved']);
        if ($day || $night) {
            DB::table('client_shift_plans')->insert(['client_id' => $id, 'effective_month' => '2026-01-01', 'day_guards' => $day, 'night_guards' => $night]);
        }

        return $id;
    }

    private function guard(string $field, ?int $client = null, string $joined = '2026-01-10', ?string $shift = 'day'): int
    {
        $id = DB::table('employees')->insertGetId(['name' => 'G' . uniqid(), 'role_id' => 7, 'department_id' => $this->dept['Operations'],
            'field_id' => $this->field[$field], 'client_id' => $client, 'status' => 'Active', 'ho_status' => 'approved', 'user_id' => 1,
            'date_of_joining' => $joined, 'gender' => 'Male']);
        DB::table('employee_movements')->insert(['employee_id' => $id, 'type' => 'hire', 'status' => 'approved', 'effective_date' => $joined, 'field_id' => $this->field[$field]]);
        if ($client && $shift) {
            DB::table('client_employee')->insert(['employee_id' => $id, 'client_id' => $client, 'shift' => $shift]);
        }

        return $id;
    }

    private function invoice(int $client, float $total, string $month = '2026-10-01'): int
    {
        return DB::table('invoices')->insertGetId(['client_id' => $client, 'total' => $total, 'sub_amount' => round($total / 1.219, 2),
            'invoice_month' => $month, 'due_date' => Carbon::parse($month)->addDays(30), 'status' => 'unpaid', 'balance' => $total]);
    }

    private function receipt(int $client, ?int $invoice, array $amounts, string $date, string $ho = 'approved', float $wht = 0): void
    {
        $id = DB::table('receipts')->insertGetId(['client_id' => $client, 'invoice_id' => $invoice, 'receipt_month' => $date, 'ho_status' => $ho,
            'wht_amount' => $wht, 'amount_received' => null, 'unapplied_amount' => 0] + $amounts);
        if ($invoice) {
            DB::table('receipt_allocations')->insert(['receipt_id' => $id, 'invoice_id' => $invoice, 'client_id' => $client,
                'amount_applied' => array_sum($amounts), 'settled' => array_sum($amounts) + $wht]);
        }
    }

    private function ctx(User $user, string $board, array $query = []): DashboardContext
    {
        return DashboardContext::fromRequest(Request::create('/', 'GET', $query), $user, $board);
    }

    public function test_each_department_lands_on_its_own_dashboard(): void
    {
        $this->actingAs($this->user('Accounts', 'Finance Manager'))->get('/home')->assertOk()->assertSee('Accounts dashboard');
        $this->actingAs($this->user('HR', 'Manager'))->get('/home')->assertOk()->assertSee('HR dashboard');
        $this->actingAs($this->user('Operations', 'Officer'))->get('/home')->assertOk()->assertSee('Operations dashboard');
    }

    public function test_a_director_can_switch_between_all_three_and_others_cannot(): void
    {
        $director = $this->user('Administration', 'Director');
        foreach (['accounts', 'hr', 'operations'] as $board) {
            $this->actingAs($director)->get("/dashboard/{$board}")->assertOk();
        }

        $hr = $this->user('HR', 'Manager');
        $this->actingAs($hr)->get('/dashboard/accounts')->assertForbidden();
        $this->actingAs($hr)->get('/dashboard/hr')->assertOk()->assertDontSee('aria-label="Dashboards"', false);
    }

    public function test_collected_counts_receipts_without_wht_and_leaves_out_unapproved_ones(): void
    {
        $c = $this->client('Accra');
        $inv = $this->invoice($c, 10000);
        // No WHT ticked: the old dashboard summed amount_received, which is NULL here, so this was lost.
        $this->receipt($c, $inv, ['cash_amount' => 3000], '2026-10-05');
        $this->receipt($c, $inv, ['transfer_amount' => 4000], '2026-10-06', 'approved', 300);
        $this->receipt($c, $inv, ['momo_amount' => 999], '2026-10-07', 'pending');

        $d = AccountsDashboard::build($this->ctx($this->user('Accounts', 'Finance Manager'), 'accounts'));

        $this->assertSame(7000.0, $d['kpi']['collected']);
        $this->assertSame(1, $d['kpi']['awaiting']->cnt);
        $this->assertSame(10000.0, $d['kpi']['invoiced']);
        $this->assertSame(70.0, $d['kpi']['collection_rate']);
        // Receivable is the aged-receivables / statement figure: invoice − everything allocated to it
        // (receipts settle an invoice when entered, before head-office approval).
        $this->assertSame(10000.0 - 3000 - 4300 - 999, $d['kpi']['receivable']);
        $this->assertSame(7000.0, end($d['trend']['collected']));
    }

    public function test_a_field_office_user_sees_only_their_offices_and_cannot_widen_it(): void
    {
        $tema = $this->client('Tema');
        $shai = $this->client('Shai Hills');
        $accra = $this->client('Accra');
        $this->invoice($tema, 100);
        $this->invoice($shai, 50);
        $this->invoice($accra, 1000);

        $user = $this->user('Accounts', 'Officer', 'Tema');
        $d = AccountsDashboard::build($this->ctx($user, 'accounts'));
        $this->assertSame(150.0, $d['kpi']['invoiced']);
        $this->assertEqualsCanonicalizing(['Tema', 'Shai Hills'], array_column($d['offices'], 'name'));

        $this->actingAs($user)->get('/home?field=' . $this->field['Accra'])->assertForbidden();
        $this->assertSame(50.0, AccountsDashboard::build($this->ctx($user, 'accounts', ['field' => $this->field['Shai Hills']]))['kpi']['invoiced']);
    }

    public function test_hr_headcount_is_the_employees_report_and_kumasi_is_kumasi(): void
    {
        $k1 = $this->guard('Kumasi');
        $this->guard('Kumasi', null, '2026-10-03');
        $this->guard('Shai Hills');
        DB::table('employees')->where('id', $k1)->update(['status' => 'Terminated']);
        DB::table('employee_movements')->insert(['employee_id' => $k1, 'type' => 'termination', 'status' => 'approved', 'effective_date' => '2026-10-12',
            'reason' => 'resigned', 'field_id' => $this->field['Kumasi']]);

        $d = HrDashboard::build($this->ctx($this->user('HR', 'Manager'), 'hr'));
        $report = EmployeeMovementsReport::build(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));

        $this->assertSame($report['totals']['closing'], $d['kpi']['closing']);
        $this->assertTrue($d['kpi']['reconciles']);
        $this->assertSame(1, $d['kpi']['hires']);
        $this->assertSame(1, $d['kpi']['terminated']);

        // The old HR dashboard showed Shai Hills' count on the Kumasi card.
        $kumasi = collect($d['offices'])->firstWhere('name', 'Kumasi');
        $this->assertSame(1, $kumasi['closing']);
        $this->assertSame(2, $d['workforce']['active']);
        $this->assertSame([['label' => 'Resigned', 'value' => 1]], $d['by_reason']);
    }

    public function test_operations_shortage_and_coverage_match_the_attendance_overview(): void
    {
        $c = $this->client('Accra', 2, 1);   // needs 2 day + 1 night
        $g1 = $this->guard('Accra', $c, '2026-01-10', 'day');
        $this->guard('Accra', $c, '2026-01-10', 'day');
        $this->client('Botwe');              // no shift plan
        $this->guard('Accra');               // not posted
        DB::table('attendances')->insert(['client_id' => $c, 'employee_id' => $g1, 'work_date' => '2026-10-01', 'shift' => 'day', 'status' => 'present', 'source' => 'kiosk']);
        DB::table('overtimes')->insert(['entry_date' => '2026-10-02', 'shift' => 'night', 'field_id' => $this->field['Accra'], 'client_id' => $c, 'ot_employee_id' => $g1, 'reason' => 'Absent guard', 'amount' => 80]);

        $user = $this->user('Operations', 'Officer');
        $d = OperationsDashboard::build($this->ctx($user, 'operations'));
        $rows = MonthOverview::rows(Client::whereIn('id', [$c])->get(), '2026-10-01');

        $this->assertSame(1, $d['kpi']['shortage']);
        $this->assertSame($rows->sum('shortage'), $d['kpi']['shortage']);
        $this->assertSame(1, $d['kpi']['no_plan']);
        $this->assertSame(3, $d['kpi']['active_guards']);
        $this->assertSame(1, $d['kpi']['unposted']);
        $required = $rows->first()['required_guard_days'];
        $this->assertSame(round(($required - $rows->first()['uncovered_guard_days']) / $required * 100, 1), $d['kpi']['coverage']);
        $this->assertSame(1, $d['kpi']['overtime_count']);
        $this->assertSame(1, $d['daily']['present'][0]);
        $this->assertNull($d['daily']['present'][25]);   // after today: not plotted as zero
    }

    public function test_a_future_or_garbage_month_falls_back_to_this_month(): void
    {
        $this->assertSame('2026-10', DashboardContext::parseMonth('2027-03')->format('Y-m'));
        $this->assertSame('2026-10', DashboardContext::parseMonth('not-a-month')->format('Y-m'));
        $this->assertSame('2026-08', DashboardContext::parseMonth('2026-08')->format('Y-m'));
    }
}

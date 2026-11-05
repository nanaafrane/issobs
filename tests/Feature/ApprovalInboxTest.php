<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Overtime;
use App\Models\User;
use App\Support\Approvals\ApprovalInbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApprovalInboxTest extends TestCase
{
    use RefreshDatabase;

    private array $roles = [];

    private array $depts = [];

    private int $accra;

    private int $tema;

    private int $shai;

    private int $kumasi;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Invoice', 'Finance Manager', 'Manager', 'Field Manager', 'Director', 'Officer'] as $name) {
            $this->roles[$name] = DB::table('roles')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (['Accounts', 'Administration', 'HR', 'Operations'] as $name) {
            $this->depts[$name] = DB::table('departments')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->accra = DB::table('fields')->insertGetId(['name' => 'Accra', 'created_at' => now(), 'updated_at' => now()]);
        $this->tema = DB::table('fields')->insertGetId(['name' => 'Tema', 'created_at' => now(), 'updated_at' => now()]);
        $this->shai = DB::table('fields')->insertGetId(['name' => 'Shai Hills', 'parent_field_id' => $this->tema, 'created_at' => now(), 'updated_at' => now()]);
        $this->kumasi = DB::table('fields')->insertGetId(['name' => 'Kumasi', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function user(string $role, string $dept, ?int $field): User
    {
        static $n = 0;
        $n++;

        return User::create([
            'name' => "$role $n", 'email' => "u$n@example.test", 'password' => 'secret',
            'role_id' => $this->roles[$role], 'department_id' => $this->depts[$dept], 'field_id' => $field,
        ])->fresh();
    }

    private function expense(int $field, array $stages = [], string $created = 'now'): int
    {
        return DB::table('expenses')->insertGetId([
            'description' => 'Test', 'amount' => 100, 'field_id' => $field, 'expense_date' => now()->toDateString(),
            'status_1' => 'approved', 'date_1' => now(),
            'status_2' => $stages[2] ?? null, 'date_2' => isset($stages[2]) ? now() : null,
            'status_3' => $stages[3] ?? null,
            'created_at' => now()->modify($created), 'updated_at' => now(),
        ]);
    }

    private function waiting(User $u, string $key): int
    {
        return collect(ApprovalInbox::for($u)->summary(fresh: true)['items'])->firstWhere('key', $key)['count'] ?? 0;
    }

    public function test_field_manager_sees_branch_review_for_own_office_group_only(): void
    {
        $fm = $this->user('Field Manager', 'Operations', $this->tema);
        $this->expense($this->tema);
        $this->expense($this->shai);
        $this->expense($this->accra);
        $this->expense($this->tema, [2 => 'approved']);   // already at finance
        $this->expense($this->tema, [2 => 'rejected']);   // rejected

        $this->assertSame(2, $this->waiting($fm, 'expense.branch'));
        $this->assertSame(2, ApprovalInbox::for($fm)->summary(fresh: true)['total']);

        // Every counted expense is one the approve button lets them decide.
        $ids = ApprovalInbox::for($fm)->rows('expense.branch')->pluck('id');
        foreach (Expense::whereIn('id', $ids)->get() as $e) {
            $this->assertTrue($e->canActOnStage($fm, $e->currentStage()));
        }
    }

    public function test_finance_gets_final_approval_everywhere_and_branch_review_only_where_no_branch_approver(): void
    {
        $this->user('Field Manager', 'Operations', $this->tema);   // Tema group has a branch approver
        $fin = $this->user('Finance Manager', 'Accounts', $this->accra);

        $this->expense($this->tema);                     // branch's turn → watch only
        $this->expense($this->accra);                    // no branch approver → finance
        $this->expense($this->kumasi, [2 => 'approved']);  // final approval
        $this->expense($this->shai, [2 => 'approved']);    // final approval

        $this->assertSame(2, $this->waiting($fin, 'expense.finance'));
        $this->assertSame(1, $this->waiting($fin, 'expense.branch-ho'));
        $summary = ApprovalInbox::for($fin)->summary(fresh: true);
        $this->assertSame(3, $summary['total']);
        $this->assertSame(1, collect($summary['watch'])->firstWhere('key', 'expense.at-branch')['count']);
    }

    public function test_expense_branch_review_is_field_manager_only_but_overtime_takes_manager_too(): void
    {
        $mgr = $this->user('Manager', 'Operations', $this->tema);
        $this->expense($this->tema);
        DB::table('overtimes')->insert([
            'entry_date' => now()->toDateString(), 'shift' => 'day', 'field_id' => $this->tema, 'ot_employee_id' => 1, 'amount' => 50,
            'status_1' => 'approved', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertSame(0, $this->waiting($mgr, 'expense.branch'));
        $this->assertSame(1, $this->waiting($mgr, 'overtime.branch'));
        $ot = Overtime::first();
        $this->assertTrue($ot->canActOnStage($mgr, $ot->currentStage()));
    }

    public function test_receipts_clients_and_employees_follow_the_pending_pages(): void
    {
        $branch = $this->user('Manager', 'Administration', $this->tema);
        $fin = $this->user('Finance Manager', 'Accounts', $this->accra);
        $invoice = $this->user('Invoice', 'Accounts', $this->accra);

        $temaClient = DB::table('clients')->insertGetId(['name' => 'Tema Co', 'field_id' => $this->tema, 'status' => 'Active', 'ho_status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);
        $kumasiClient = DB::table('clients')->insertGetId(['name' => 'Kumasi Co', 'field_id' => $this->kumasi, 'status' => 'Active', 'ho_status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);
        $receipt = fn (int $client, ?string $bran, ?string $ho) => DB::table('receipts')->insert([
            'client_id' => $client, 'total' => 10, 'bran_status' => $bran, 'ho_status' => $ho, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $receipt($temaClient, 'pending', null);       // branch's turn (Tema)
        $receipt($kumasiClient, 'pending', null);     // branch's turn (Kumasi)
        $receipt($temaClient, 'approved', 'pending'); // head office's turn
        $receipt($kumasiClient, null, 'approved');    // done

        $this->assertSame(1, $this->waiting($branch, 'receipt.branch'));
        $this->assertSame(1, $this->waiting($fin, 'receipt.ho'));

        // Clients: branch approves its own office, Invoice gives head-office approval.
        DB::table('clients')->insert([
            ['name' => 'New Tema', 'field_id' => $this->shai, 'status' => 'pending', 'bran_status' => 'pending', 'ho_status' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'New Kumasi', 'field_id' => $this->kumasi, 'status' => 'pending', 'bran_status' => 'pending', 'ho_status' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'To HO', 'field_id' => $this->kumasi, 'status' => 'pending', 'bran_status' => 'approved', 'ho_status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->assertSame(1, $this->waiting($branch, 'client.branch'));
        $this->assertSame(1, $this->waiting($invoice, 'client.ho'));
        $this->assertSame(0, $this->waiting($fin, 'client.ho'));

        // Employees: same split; terminated + approved is done.
        $emp = fn (array $a) => DB::table('employees')->insert($a + ['name' => 'G', 'user_id' => $branch->id, 'created_at' => now(), 'updated_at' => now()]);
        $emp(['field_id' => $this->tema, 'status' => 'Pending', 'bran_status' => 'pending', 'ho_status' => null]);
        $emp(['field_id' => $this->kumasi, 'status' => 'Terminated', 'bran_status' => 'approved', 'ho_status' => 'pending']);
        $emp(['field_id' => $this->kumasi, 'status' => 'Terminated', 'bran_status' => 'approved', 'ho_status' => 'approved']);
        $emp(['field_id' => $this->tema, 'status' => 'Active', 'bran_status' => 'pending', 'ho_status' => null]);
        $this->assertSame(1, $this->waiting($branch, 'employee.branch'));
        $this->assertSame(1, $this->waiting($invoice, 'employee.ho'));
    }

    public function test_users_who_approve_nothing_get_no_bell(): void
    {
        $officer = $this->user('Officer', 'Accounts', $this->accra);
        $this->expense($this->accra);

        $s = ApprovalInbox::for($officer)->summary(fresh: true);
        $this->assertFalse($s['can']);
        $this->assertSame(0, $s['total']);
    }

    public function test_counts_refresh_when_a_record_changes_and_page_approves_inline(): void
    {
        $fm = $this->user('Field Manager', 'Operations', $this->tema);
        $id = $this->expense($this->tema, [], '-5 days');

        $this->assertSame(1, ApprovalInbox::for($fm)->total());
        $oldest = collect(ApprovalInbox::for($fm)->summary()['items'])->firstWhere('key', 'expense.branch')['oldest'];
        $this->assertSame(5, ApprovalInbox::ageDays($oldest));

        $this->actingAs($fm)->get(route('approvals.index'))
            ->assertOk()
            ->assertSee('Waiting for your approval')
            ->assertSee(route('expense.approve', $id), false);

        $this->actingAs($fm)->from(route('approvals.index'))->post(route('expense.approve', $id))
            ->assertRedirect(route('approvals.index'));

        // Cached summary is invalidated by the save, not only by the 60 s expiry.
        $this->assertSame(0, ApprovalInbox::for($fm)->total());
    }

    public function test_dashboard_shows_the_bell_and_the_waiting_strip(): void
    {
        $fin = $this->user('Finance Manager', 'Accounts', $this->accra);
        $this->expense($this->kumasi, [2 => 'approved']);
        $this->expense($this->kumasi, [2 => 'approved']);

        $this->actingAs($fin)->get(route('dashboard.show', ['board' => 'accounts']))
            ->assertOk()
            ->assertSee('Waiting for your approval')
            ->assertSee('Finance / H.O approval')
            ->assertSee('2 approvals waiting for you')
            ->assertSee(route('approvals.index'), false);
    }
}

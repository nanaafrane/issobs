<?php

namespace Tests\Feature;

use App\Models\AccountTransfer;
use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\Banking\AccountReports;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Moving money between our accounts: several sources into several destinations, by transfer,
 * cheque (deposited, or cashed and shared out), withdrawal or cash — each account getting the
 * lines its own statement shows.
 */
class MoveMoneyTest extends TestCase
{
    use RefreshDatabase;

    private Bank $gcb;
    private Bank $omni;
    private Bank $cbg;
    private Bank $eco;
    private Bank $cash;
    private Bank $petty;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-29 10:00:00');
        $dept = Department::create(['name' => 'Accounts']);
        $role = Role::forceCreate(['id' => 2, 'name' => 'Finance Manager', 'department_id' => $dept->id]);
        $this->actingAs(User::factory()->create(['department_id' => $dept->id, 'role_id' => $role->id]));
        $bank = fn ($name, $total) => Bank::create(['name' => $name, 'branch' => 'Accra', 'acc_number' => (string) random_int(100, 999), 'total' => $total, 'opening_balance' => $total, 'opening_date' => '2026-01-01']);
        $this->gcb = $bank('GCB Main', 100000);
        $this->omni = $bank('OMNIBSIC', 10000);
        $this->cbg = $bank('CBG', 8000);
        $this->eco = $bank('Ecobank', 0);
        $this->cash = Bank::create(['name' => 'Main Cash', 'kind' => 'cash', 'branch' => '', 'acc_number' => '', 'custodian' => 'Ama', 'total' => 1000, 'opening_balance' => 1000, 'opening_date' => '2026-01-01']);
        $this->petty = Bank::create(['name' => 'Kumasi Petty Cash', 'kind' => 'cash', 'branch' => '', 'acc_number' => '', 'custodian' => 'Yaw', 'total' => 0, 'opening_balance' => 0, 'opening_date' => '2026-01-01']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function move(array $sources, array $destinations, array $extra = [])
    {
        return $this->post(route('money.transfers.store'), array_merge(['date' => '2026-10-27', 'notes' => 'October float', 'sources' => $sources, 'destinations' => $destinations], $extra));
    }

    private function lines(Bank $b)
    {
        return BankTransaction::where('bank_id', $b->id)->orderBy('id')->get();
    }

    public function test_a_cheque_cashed_and_shared_is_one_debit_and_one_credit_per_deposit(): void
    {
        $this->move(
            [['bank_id' => $this->gcb->id, 'how' => 'cheque', 'reference' => '000457', 'amount' => 50000]],
            [['bank_id' => $this->cash->id, 'how' => 'cash', 'amount' => 20000],
             ['bank_id' => $this->omni->id, 'how' => 'cash', 'amount' => 25000, 'reference' => 'SLIP 77'],
             ['bank_id' => $this->petty->id, 'how' => 'cash', 'amount' => 5000]],
        )->assertSessionHasNoErrors()->assertRedirect();

        $this->assertEquals(50000, $this->gcb->fresh()->total);
        $this->assertEquals(21000, $this->cash->fresh()->total);
        $this->assertEquals(35000, $this->omni->fresh()->total);
        $this->assertEquals(5000, $this->petty->fresh()->total);

        $gcb = $this->lines($this->gcb);
        $this->assertCount(1, $gcb);                                        // the bank statement shows one cheque
        $this->assertEquals(50000, $gcb[0]->debit);
        $this->assertStringContainsString('Cheque 000457 cashed — Main Cash 20,000.00, OMNIBSIC 25,000.00, Kumasi Petty Cash 5,000.00', $gcb[0]->narration);
        $omni = $this->lines($this->omni);
        $this->assertCount(1, $omni);
        $this->assertStringContainsString('Cash deposited (ref SLIP 77) — cheque 000457 (GCB Main)', $omni[0]->narration);
        $this->assertStringContainsString('Cash received — cheque 000457 (GCB Main)', $this->lines($this->cash)[0]->narration);

        $t = AccountTransfer::orderBy('id')->get();
        $this->assertCount(3, $t);
        $this->assertSame(1, $t->pluck('batch')->unique()->count());
        $this->assertSame(['cheque', 'cheque', 'cheque'], $t->pluck('method')->all());
        $this->assertSame(['cash', 'cash', 'cash'], $t->pluck('in_method')->all());
        $this->assertSame('Cheque 000457 → cashed', $t[1]->routeLabel());

        $flow = app(AccountReports::class)->flow(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));
        $this->assertEquals(0, $flow['total_in']);                         // our own money changing place
        $this->assertEquals(0, $flow['total_out']);
        $this->assertEmpty(app(AccountReports::class)->checks(Bank::all()));
    }

    public function test_several_sources_into_several_destinations_by_different_routes(): void
    {
        $this->move(
            [['bank_id' => $this->gcb->id, 'how' => 'transfer', 'reference' => 'TRF-88', 'amount' => 10000],
             ['bank_id' => $this->cbg->id, 'how' => 'cheque', 'reference' => '222', 'amount' => 4000],
             ['bank_id' => $this->cbg->id, 'how' => 'withdrawal', 'amount' => 1500]],
            [['bank_id' => $this->omni->id, 'how' => 'transfer', 'amount' => 6000],
             ['bank_id' => $this->eco->id, 'how' => 'transfer', 'amount' => 4000],
             ['bank_id' => $this->eco->id, 'how' => 'cheque', 'cheque' => 1],
             ['bank_id' => $this->cash->id, 'how' => 'cash', 'amount' => 1500]],
        )->assertSessionHasNoErrors();

        $this->assertEquals(90000, $this->gcb->fresh()->total);
        $this->assertEquals(2500, $this->cbg->fresh()->total);
        $this->assertEquals(16000, $this->omni->fresh()->total);
        $this->assertEquals(8000, $this->eco->fresh()->total);
        $this->assertEquals(2500, $this->cash->fresh()->total);

        $this->assertCount(2, $this->lines($this->gcb));                   // one debit per transfer sent
        $this->assertCount(2, $this->lines($this->cbg));                   // the cheque and the withdrawal
        $eco = $this->lines($this->eco);
        $this->assertCount(2, $eco);                                       // transfer received + cheque deposited
        $this->assertStringContainsString('Transfer TRF-88 from GCB Main', $eco[0]->narration);
        $this->assertStringContainsString('Cheque deposited — cheque 222 (CBG)', $eco[1]->narration);
        $pairs = AccountTransfer::orderBy('id')->get()->map(fn ($t) => [$t->from->name, $t->to->name, (float) $t->amount, $t->method, $t->in_method])->all();
        $this->assertSame([
            ['GCB Main', 'OMNIBSIC', 6000.0, 'transfer', 'transfer'],
            ['GCB Main', 'Ecobank', 4000.0, 'transfer', 'transfer'],
            ['CBG', 'Ecobank', 4000.0, 'cheque', 'cheque'],
            ['CBG', 'Main Cash', 1500.0, 'withdrawal', 'cash'],
        ], $pairs);
    }

    public function test_the_rules_with_plain_messages(): void
    {
        $chq = ['bank_id' => $this->gcb->id, 'how' => 'cheque', 'reference' => '1', 'amount' => 500];
        // both sides must match
        $this->move([$chq], [['bank_id' => $this->cash->id, 'how' => 'cash', 'amount' => 400]])->assertSessionHasErrors('balance');
        // a transfer cannot land in a cash box
        $this->move([['bank_id' => $this->gcb->id, 'how' => 'transfer', 'amount' => 500]], [['bank_id' => $this->cash->id, 'how' => 'transfer', 'amount' => 500]])->assertSessionHasErrors('destinations.0.how');
        // transfers out must arrive as transfers
        $this->move([['bank_id' => $this->gcb->id, 'how' => 'transfer', 'amount' => 500]], [['bank_id' => $this->omni->id, 'how' => 'cash', 'amount' => 500]])->assertSessionHasErrors('balance');
        // a cheque is deposited whole, into one account
        $this->move([$chq], [['bank_id' => $this->omni->id, 'how' => 'cheque', 'cheque' => 0], ['bank_id' => $this->eco->id, 'how' => 'cheque', 'cheque' => 0]])->assertSessionHasErrors('destinations');
        // cheque number
        $this->move([['bank_id' => $this->gcb->id, 'how' => 'cheque', 'amount' => 500]], [['bank_id' => $this->cash->id, 'how' => 'cash', 'amount' => 500]])->assertSessionHasErrors('sources.0.reference');
        // not out of and into the same account
        $this->move([$chq], [['bank_id' => $this->gcb->id, 'how' => 'cash', 'amount' => 500]])->assertSessionHasErrors('destinations.0.bank_id');
        // cannot arrive before it left
        $this->move([$chq + ['date' => '2026-10-27']], [['bank_id' => $this->cash->id, 'how' => 'cash', 'amount' => 500, 'date' => '2026-10-26']])->assertSessionHasErrors('destinations.0.date');
        // a cash box cannot give more than it holds
        $this->move([['bank_id' => $this->cash->id, 'how' => 'cash', 'amount' => 5000]], [['bank_id' => $this->omni->id, 'how' => 'cash', 'amount' => 5000]])->assertSessionHasErrors('sources.0.amount');

        $this->assertSame(0, AccountTransfer::count());
        $this->assertSame(0, BankTransaction::count());
    }

    public function test_cash_deposited_the_next_day_is_dated_when_it_arrived(): void
    {
        $this->move(
            [['bank_id' => $this->gcb->id, 'how' => 'cheque', 'reference' => '000460', 'amount' => 3000]],
            [['bank_id' => $this->omni->id, 'how' => 'cash', 'amount' => 3000, 'date' => '2026-10-28']],
        )->assertSessionHasNoErrors();

        $this->assertSame('2026-10-27', $this->lines($this->gcb)[0]->entry_date->toDateString());
        $this->assertSame('2026-10-28', $this->lines($this->omni)[0]->entry_date->toDateString());
        $this->assertSame('2026-10-28', AccountTransfer::sole()->in_date->toDateString());
    }

    public function test_reversing_a_shared_move_puts_every_account_back(): void
    {
        $this->move(
            [['bank_id' => $this->gcb->id, 'how' => 'cheque', 'reference' => '000457', 'amount' => 50000]],
            [['bank_id' => $this->cash->id, 'how' => 'cash', 'amount' => 20000], ['bank_id' => $this->omni->id, 'how' => 'cash', 'amount' => 30000]],
        );
        $t = AccountTransfer::first();
        $this->post(route('money.transfers.reverse', $t), ['reversal_reason' => 'Wrong cheque amount'])->assertSessionHasNoErrors();
        $this->assertEquals(100000, $this->gcb->fresh()->total);
        $this->assertEquals(1000, $this->cash->fresh()->total);
        $this->assertEquals(10000, $this->omni->fresh()->total);
        $this->assertSame(2, AccountTransfer::whereNotNull('reversed_at')->count());
        $this->assertCount(2, $this->lines($this->gcb));
        $this->assertStringStartsWith('Reversal (Wrong cheque amount): Cheque 000457 cashed', $this->lines($this->gcb)[1]->narration);
        $this->post(route('money.transfers.reverse', $t), ['reversal_reason' => 'Again please'])->assertSessionHasErrors('reversal_reason');
        $this->assertEmpty(app(AccountReports::class)->checks(Bank::all()));
    }

    public function test_preview_shows_the_plan_and_saves_nothing(): void
    {
        $res = $this->postJson(route('money.transfers.preview'), ['date' => '2026-10-27',
            'sources' => [['bank_id' => $this->gcb->id, 'how' => 'cheque', 'reference' => '000457', 'amount' => 50000]],
            'destinations' => [['bank_id' => $this->cash->id, 'how' => 'cash', 'amount' => 20000], ['bank_id' => $this->omni->id, 'how' => 'cash', 'amount' => 30000]],
        ])->assertOk()->json();
        $this->assertTrue($res['ok']);
        $this->assertEquals(50000, $res['total']);
        $this->assertCount(3, $res['lines']);
        $this->assertSame('GCB Main', $res['lines'][0]['account']);
        $this->assertEquals(50000, $res['lines'][0]['after']);
        $this->assertSame('Cheque 000457 → cash received', $res['pairs'][0]['how']);
        $this->assertSame(0, BankTransaction::count());                    // the preview saves nothing

        $bad = $this->postJson(route('money.transfers.preview'), ['date' => '2026-10-27',
            'sources' => [['bank_id' => $this->gcb->id, 'how' => 'cheque', 'reference' => '1', 'amount' => 500]],
            'destinations' => [['bank_id' => $this->cash->id, 'how' => 'cash', 'amount' => 300]],
        ])->json();
        $this->assertFalse($bad['ok']);
        $this->assertSame('balance', $bad['field']);
        $this->assertStringContainsString('difference GH₵ 200.00', $bad['message']);

        // a cheque number already used from the same bank is flagged (not blocked)
        $this->move([['bank_id' => $this->gcb->id, 'how' => 'cheque', 'reference' => '000457', 'amount' => 100]], [['bank_id' => $this->cash->id, 'how' => 'cash', 'amount' => 100]]);
        $dup = $this->postJson(route('money.transfers.preview'), ['date' => '2026-10-27',
            'sources' => [['bank_id' => $this->gcb->id, 'how' => 'cheque', 'reference' => '000457', 'amount' => 50]],
            'destinations' => [['bank_id' => $this->cash->id, 'how' => 'cash', 'amount' => 50]],
        ])->json();
        $this->assertTrue($dup['ok']);
        $this->assertStringContainsString('Cheque 000457 from GCB Main was already entered on 27 Oct 2026', $dup['warnings'][0]);

        $short = $this->postJson(route('money.transfers.preview'), ['date' => '2026-10-27',
            'sources' => [['bank_id' => $this->cash->id, 'how' => 'cash', 'amount' => 5000]],
            'destinations' => [['bank_id' => $this->omni->id, 'how' => 'cash', 'amount' => 5000]],
        ])->json();
        $this->assertFalse($short['ok']);
        $this->assertStringContainsString('Main Cash only holds GH₵ 1,100.00', $short['message']);   // 1,000 + the 100 cheque above
    }

    public function test_screens_show_a_move_as_entered(): void
    {
        $this->move(
            [['bank_id' => $this->gcb->id, 'how' => 'cheque', 'reference' => '000457', 'amount' => 50000]],
            [['bank_id' => $this->cash->id, 'how' => 'cash', 'amount' => 20000], ['bank_id' => $this->omni->id, 'how' => 'cash', 'amount' => 30000, 'reference' => 'SLIP 77']],
        );
        $this->get(route('money.transfers.create'))->assertOk()->assertSee('Money goes into')->assertSee('Deposit a cheque into another bank');
        $this->get(route('money.transfers', ['month' => '2026-10']))->assertOk()->assertSee('50,000.00')->assertSee('Cheque 000457 → cashed');
        $this->get(route('money.transfers.show', AccountTransfer::first()))->assertOk()
            ->assertSee('GH₵ 50,000.00 moved')->assertSee('Who funded what')->assertSee('Cash deposited')->assertSee('SLIP 77');
    }
}

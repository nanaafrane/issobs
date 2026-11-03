<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\BankDeposit;
use App\Models\BankTransaction;
use App\Models\Client;
use App\Models\Collection;
use App\Models\Department;
use App\Models\Field;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Role;
use App\Models\User;
use App\Services\Banking\AccountReports;
use App\Services\Receipts\ReceiptVoider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * MoMo paid into the company MoMo wallet is credited to the wallet when the receipt is saved.
 * Until the Finance Manager / a Director approves the receipt it is held (cannot be withdrawn or
 * spent). Bank Deposit withdraws it — ticking receipts or typing an amount — with the charge
 * debited on the bank as before and named on the wallet's line.
 */
class MomoWalletReceiptsTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;
    private Field $tema;
    private Client $client;
    private Bank $gcb;
    private Bank $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $dept = Department::create(['name' => 'Finance']);
        $this->finance = User::factory()->create(['department_id' => $dept->id, 'role_id' => Role::forceCreate(['id' => 2, 'name' => 'Finance Manager', 'department_id' => $dept->id])->id]);
        $this->tema = Field::create(['name' => 'Tema', 'user_id' => $this->finance->id, 'number' => '2']);
        $this->client = Client::create(['name' => 'Kofi', 'business_name' => 'Tema Port Ltd', 'field_id' => $this->tema->id]);
        $this->gcb = Bank::create(['name' => 'GCB Main', 'branch' => 'Accra', 'acc_number' => '111', 'total' => 0]);
        $this->wallet = Bank::create(['name' => 'Company MoMo', 'kind' => 'momo', 'branch' => '', 'acc_number' => '0244000000', 'total' => 0]);
        foreach (['cash', 'cheque', 'transfer', 'momo', 'other payments'] as $m) {
            DB::table('receipt_mode')->insert(['name' => $m]);
        }
        $this->actingAs($this->finance);
    }

    private function receipt(float $momo, float $cash = 0, bool $approved = true, ?int $wallet = -1): Receipt
    {
        $inv = Invoice::create(['client_id' => $this->client->id, 'total' => 20000, 'sub_amount' => 20000, 'sub_total' => 0, 'invoice_month' => '2026-10-01', 'due_date' => now()->addDays(10), 'status' => 'unpaid', 'balance' => 0]);
        $this->post(route('receipt.multi.store'), ['client_id' => $this->client->id, 'receipt_month' => now()->toDateString(), 'from' => 'Kofi',
            'mode' => array_values(array_filter(['momo', $cash > 0 ? 'cash' : null])), 'cash_amount' => $cash ?: null,
            'momo_amount' => $momo, 'momo_transactin_id' => 'MP' . random_int(10000, 99999), 'momo_to_bank_id' => $wallet === -1 ? $this->wallet->id : $wallet,
            'invoices' => [$inv->id => ['selected' => '1']], 'confirm_duplicate' => '1'])->assertSessionHasNoErrors();
        $r = Receipt::latest('id')->first();
        $r->forceFill(['ho_status' => $approved ? 'approved' : 'pending'])->save();

        return $r;
    }

    private function col(Receipt $r): Collection
    {
        return Collection::where('receipt_id', $r->id)->firstOrFail();
    }

    private function deposit(array $fields)
    {
        return $this->post('/deposit', array_merge(['bank_id' => $this->gcb->id, 'deposit_date' => now()->toDateString()], $fields));
    }

    public function test_receipt_picks_the_wallet_and_momo_is_credited_at_once(): void
    {
        $inv = Invoice::create(['client_id' => $this->client->id, 'total' => 900, 'sub_amount' => 900, 'sub_total' => 0, 'invoice_month' => '2026-10-01', 'due_date' => now()->addDays(10), 'status' => 'unpaid', 'balance' => 0]);
        $page = $this->get(route('receipt.multi.create', ['client_id' => $this->client->id]))->assertOk()->assertSee('PAID INTO (OUR MOMO WALLET)');
        $this->assertMatchesRegularExpression('/<option value="' . $this->wallet->id . '"\s+selected/', $page->getContent());   // the only wallet is chosen
        $this->post(route('receipt.multi.store'), ['client_id' => $this->client->id, 'receipt_month' => now()->toDateString(), 'from' => 'Kofi', 'mode' => ['momo'],
            'momo_amount' => 400, 'momo_transactin_id' => 'MP1', 'invoices' => [$inv->id => ['selected' => '1']], 'confirm_duplicate' => '1'])
            ->assertSessionHasErrors('momo_to_bank_id');   // once a wallet is set up, the receipt must say which

        $r = $this->receipt(1500);
        $this->assertEquals(1500, $this->wallet->fresh()->total);
        $line = BankTransaction::where('bank_id', $this->wallet->id)->sole();
        $this->assertSame('momo', $line->channel);
        $this->assertSame($r->id, (int) $line->receipt_id);
        $this->assertStringContainsString('FWSSR' . $r->id, $line->narration);
        $this->assertSame('Banked', $this->col($r)->status);   // nothing "waiting" outside an account
    }

    public function test_unapproved_momo_is_held_in_the_wallet(): void
    {
        $r = $this->receipt(1000, approved: false);
        $w = $this->wallet->fresh();
        $this->assertEquals(1000, $w->total);
        $this->assertEquals(1000, $w->awaitingApproval());
        $this->assertEquals(0, $w->available());

        $this->get(route('deposit.create'))->assertOk()
            ->assertViewHas('waiting', fn ($rows) => ! $rows->contains('id', $this->col($r)->id))
            ->assertViewHas('pending', fn ($rows) => $rows->firstWhere('id', $this->col($r)->id)?->wallet_due == 1000);
        $this->deposit(['wallet' => [$this->col($r)->id], 'momo_charge' => '0'])->assertSessionHasErrors('cash');
        $this->deposit(['wallet_id' => $this->wallet->id, 'wallet_amount' => '500', 'momo_charge' => '5'])->assertSessionHasErrors('wallet_amount');
        $this->post(route('money.payments.store'), ['mode' => 'other', 'bank_id' => $this->wallet->id, 'method' => 'momo', 'payee' => 'X',
            'payment_date' => now()->toDateString(), 'amount' => 100, 'category' => 'salaries'])->assertSessionHasErrors('amount');
        $this->assertSame(0, BankDeposit::count());

        $r->forceFill(['ho_status' => 'approved'])->save();
        $this->assertEquals(1000, $this->wallet->fresh()->available());
        $this->get(route('deposit.create'))->assertViewHas('waiting', fn ($rows) => $rows->firstWhere('id', $this->col($r)->id)?->wallet_due == 1000);
    }

    public function test_ticking_wallet_receipts_with_cash_on_one_slip(): void
    {
        $a = $this->receipt(600, cash: 400);
        $b = $this->receipt(300);
        $this->deposit(['cash' => [$this->col($a)->id], 'wallet' => [$this->col($a)->id, $this->col($b)->id], 'wallet_amount' => '100', 'momo_charge' => '9'])
            ->assertSessionHasErrors('wallet_amount');   // ticks decide the amount
        $this->deposit(['cash' => [$this->col($a)->id], 'wallet' => [$this->col($a)->id, $this->col($b)->id]])->assertSessionHasErrors('momo_charge');
        $this->deposit(['cash' => [$this->col($a)->id], 'wallet' => [$this->col($a)->id, $this->col($b)->id], 'momo_charge' => '9', 'slip_number' => 'S77'])->assertSessionHasNoErrors();

        $d = BankDeposit::sole();
        $this->assertEquals(900, $d->wallet_amount);
        $this->assertEquals(900, $d->momo_amount);
        $this->assertEquals(1291, $d->total);                         // 400 + 900 − 9
        $this->assertEquals(0, $this->wallet->fresh()->total);
        $this->assertEquals(1291, $this->gcb->fresh()->total);

        // The charge is booked once, on the bank; the wallet's line names it.
        $walletLine = BankTransaction::where('bank_id', $this->wallet->id)->where('channel', 'momo_withdrawal')->sole();
        $this->assertEquals(900, $walletLine->debit);
        $this->assertStringContainsString('withdrawal charge GH₵ 9.00 (debited on GCB Main)', $walletLine->narration);
        $this->assertEquals(9, BankTransaction::where('bank_id', $this->gcb->id)->where('channel', 'momo_charge')->value('debit'));
        $this->assertSame(0, BankTransaction::where('bank_id', $this->wallet->id)->where('channel', 'momo_charge')->count());

        $this->assertSame($d->id, (int) $this->col($b)->momo_deposit_id);
        $this->get(route('receipt.multi.edit', $b))->assertRedirect();           // banked receipts are locked as before
        $this->get(route('deposit.create'))->assertViewHas('waiting', fn ($rows) => $rows->isEmpty());

        // Reversal puts the MoMo back in the wallet and the receipts back on the list.
        $this->post(route('deposit.reverse', $d), ['reversal_reason' => 'Wrong slip'])->assertSessionHasNoErrors();
        $this->assertEquals(900, $this->wallet->fresh()->total);
        $this->assertEquals(0, $this->gcb->fresh()->total);
        $this->get(route('deposit.create'))->assertViewHas('waiting', fn ($rows) => $rows->sum('wallet_due') == 900 && $rows->sum('cash_due') == 400);
    }

    public function test_typing_the_amount_withdrawn(): void
    {
        $this->receipt(700);
        $this->receipt(500);
        $this->deposit(['wallet_id' => $this->wallet->id, 'wallet_amount' => '1300', 'momo_charge' => '10'])->assertSessionHasErrors('wallet_amount');
        $this->deposit(['bank_id' => $this->wallet->id, 'wallet_id' => $this->wallet->id, 'wallet_amount' => '100', 'momo_charge' => '0'])->assertSessionHasErrors('bank_id');
        $this->deposit(['wallet_id' => $this->wallet->id, 'wallet_amount' => '1000', 'momo_charge' => '10'])->assertSessionHasNoErrors();

        $this->assertEquals(200, $this->wallet->fresh()->total);
        $this->assertEquals(990, $this->gcb->fresh()->total);
        // What is left in the wallet is shown against the newest receipt it can cover — here none (500 > 200).
        $this->get(route('deposit.create'))->assertOk()
            ->assertViewHas('waiting', fn ($rows) => $rows->sum('wallet_due') == 0)
            ->assertViewHas('wallets', fn ($w) => $w->first()->available_amount == 200)
            ->assertSee('MoMo from the wallet');
        $this->deposit(['wallet_id' => $this->wallet->id, 'wallet_amount' => '200', 'momo_charge' => '2'])->assertSessionHasNoErrors();
        $this->assertEquals(0, $this->wallet->fresh()->total);
    }

    public function test_spending_from_the_wallet_shrinks_what_is_listed(): void
    {
        $old = $this->receipt(400);
        $new = $this->receipt(600);
        $this->post(route('money.payments.store'), ['mode' => 'other', 'bank_id' => $this->wallet->id, 'method' => 'momo', 'payee' => 'Guards',
            'payment_date' => now()->toDateString(), 'amount' => 300, 'category' => 'salaries'])->assertSessionHasNoErrors();
        $this->get(route('deposit.create'))->assertViewHas('waiting', fn ($rows) => $rows->firstWhere('id', $this->col($new)->id)?->wallet_due == 600
            && ! $rows->contains(fn ($c) => $c->id === $this->col($old)->id && $c->wallet_due > 0));
        $this->deposit(['wallet' => [$this->col($old)->id], 'momo_charge' => '0'])->assertSessionHasErrors('wallet');
    }

    public function test_editing_and_voiding_a_receipt_moves_the_wallet(): void
    {
        $r = $this->receipt(800);
        $inv = $r->allocations()->value('invoice_id');
        $this->put(route('receipt.update', $r), ['receipt_month' => now()->toDateString(), 'from' => 'Kofi', 'mode' => ['momo'],
            'momo_amount' => 650, 'momo_transactin_id' => 'MP2', 'momo_to_bank_id' => $this->wallet->id,
            'invoices' => [$inv => ['selected' => '1']]])->assertSessionHasNoErrors();
        $this->assertEquals(650, $this->wallet->fresh()->total);

        app(ReceiptVoider::class)->void($r->fresh(), 'Entered twice by mistake');
        $this->assertEquals(0, $this->wallet->fresh()->total);
        // 800 in, 800 back + 650 in (edit), 650 back (void): every step visible.
        $this->assertSame(4, BankTransaction::where('bank_id', $this->wallet->id)->where('channel', 'momo')->count());
    }

    public function test_reports_count_client_momo_once(): void
    {
        $this->receipt(1000, cash: 200);
        $c = Collection::latest('id')->first();
        $this->deposit(['cash' => [$c->id], 'wallet' => [$c->id], 'momo_charge' => '10'])->assertSessionHasNoErrors();

        $flow = app(AccountReports::class)->flow(now()->startOfMonth(), now()->endOfMonth());
        $in = $flow['in']->keyBy('label');
        $this->assertEquals(1000, $in['MoMo received from clients (into the wallet)']['amount']);
        $this->assertEquals(200, $in['Cash & MoMo collected (banked or kept)']['amount']);   // only the cash part
        $this->assertEquals(10, $flow['out']->firstWhere('label', 'MoMo withdrawal charges')['amount']);
        $this->assertEquals(1190, $flow['net']);
        $this->assertEquals(1190, round($this->gcb->fresh()->total + $this->wallet->fresh()->total, 2));
        $this->assertSame([], app(AccountReports::class)->checks(Bank::all()));
    }

    public function test_old_momo_without_a_wallet_still_goes_through_bank_deposit(): void
    {
        $this->wallet->forceFill(['is_active' => false])->save();   // as before any wallet was set up
        $r = $this->receipt(500, wallet: null);
        $this->assertEquals(0, $this->wallet->fresh()->total);
        $c = $this->col($r);
        $this->get(route('deposit.create'))->assertViewHas('waiting', fn ($rows) => $rows->firstWhere('id', $c->id)?->momo_due == 500);
        $this->deposit(['momo' => [$c->id], 'momo_charge' => '5'])->assertSessionHasNoErrors();
        $this->assertEquals(495, $this->gcb->fresh()->total);
        $this->assertNull(BankDeposit::sole()->wallet_id);
    }
}

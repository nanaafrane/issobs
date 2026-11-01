<?php

namespace Tests\Feature;

use App\Models\AccountPayment;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Collections (cash / MoMo from receipts) are available only once the Finance Manager or a
 * Director has approved the receipt; then they are banked, or kept in an office cash box or
 * the MoMo wallet and spent from there (salaries, expenses) with a full trail.
 */
class CollectionsApproveAndSpendTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;
    private Field $tema;
    private Client $client;
    private Bank $gcb;
    private Bank $temaCash;
    private Bank $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $dept = Department::create(['name' => 'Finance']);
        $this->finance = User::factory()->create(['department_id' => $dept->id, 'role_id' => Role::forceCreate(['id' => 2, 'name' => 'Finance Manager', 'department_id' => $dept->id])->id]);
        $this->tema = Field::create(['name' => 'Tema', 'user_id' => $this->finance->id, 'number' => '2']);
        $this->client = Client::create(['name' => 'Kofi', 'business_name' => 'Tema Port Ltd', 'field_id' => $this->tema->id]);
        $this->gcb = Bank::create(['name' => 'GCB Main', 'branch' => 'Accra', 'acc_number' => '111', 'total' => 0]);
        $this->temaCash = Bank::create(['name' => 'Tema office cash', 'kind' => 'cash', 'branch' => '', 'acc_number' => '', 'custodian' => 'Yaw (Tema officer)', 'field_id' => $this->tema->id, 'total' => 0]);
        $this->wallet = Bank::create(['name' => 'Company MoMo', 'kind' => 'momo', 'branch' => '', 'acc_number' => '0244000000', 'total' => 0]);
        foreach (['cash', 'cheque', 'transfer', 'momo', 'other payments'] as $m) {
            DB::table('receipt_mode')->insert(['name' => $m]);
        }
        $this->actingAs($this->finance);
    }

    /** A receipt as the branch enters it: not yet approved at head office. */
    private function receipt(float $cash = 0, float $momo = 0, bool $approved = false): Collection
    {
        $inv = Invoice::create(['client_id' => $this->client->id, 'total' => 9000, 'sub_amount' => 9000, 'sub_total' => 0, 'invoice_month' => '2026-10-01', 'due_date' => now()->addDays(10), 'status' => 'unpaid', 'balance' => 0]);
        $mode = array_values(array_filter([$cash > 0 ? 'cash' : null, $momo > 0 ? 'momo' : null]));
        $this->post(route('receipt.multi.store'), ['client_id' => $this->client->id, 'receipt_month' => now()->toDateString(), 'from' => 'Kofi', 'mode' => $mode,
            'cash_amount' => $cash ?: null, 'momo_amount' => $momo ?: null, 'momo_transactin_id' => $momo ? 'MP' . random_int(1000, 99999) : null,
            'invoices' => [$inv->id => ['selected' => '1']], 'confirm_duplicate' => '1'])->assertSessionHasNoErrors();
        $r = Receipt::latest('id')->first();
        if (! $approved) {
            $r->forceFill(['ho_status' => 'pending'])->save();
        }

        return Collection::where('receipt_id', $r->id)->firstOrFail();
    }

    private function into(Bank $to, array $fields)
    {
        return $this->post('/deposit', array_merge(['bank_id' => $to->id, 'deposit_date' => now()->toDateString()], $fields));
    }

    public function test_unapproved_receipts_wait_for_finance_approval(): void
    {
        $c = $this->receipt(cash: 500);
        $this->get(route('deposit.create'))->assertOk()
            ->assertViewHas('waiting', fn ($w) => ! $w->contains('id', $c->id))
            ->assertViewHas('pending', fn ($p) => $p->contains('id', $c->id))
            ->assertSee('Awaiting finance approval');
        $this->into($this->gcb, ['cash' => [$c->id]])->assertSessionHasErrors('cash');
        $this->assertSame(0, BankDeposit::count());

        // Only the Finance Manager or a Director can approve at head office.
        $clerk = User::factory()->create(['role_id' => Role::create(['name' => 'Invoice', 'department_id' => $this->finance->department_id])->id, 'department_id' => $this->finance->department_id]);
        $this->actingAs($clerk)->post('/receiptChannels', ['receipts' => [$c->receipt_id], 'action_type' => 'headOffice'])->assertSessionHas('error');
        $this->assertSame('pending', Receipt::find($c->receipt_id)->ho_status);

        $director = User::factory()->create(['role_id' => Role::create(['name' => 'Director', 'department_id' => $this->finance->department_id])->id, 'department_id' => $this->finance->department_id]);
        $this->actingAs($director)->post('/receiptChannels', ['receipts' => [$c->receipt_id], 'action_type' => 'headOffice']);
        $this->assertSame('approved', Receipt::find($c->receipt_id)->ho_status);

        $this->actingAs($this->finance);
        $this->get(route('deposit.create'))->assertViewHas('waiting', fn ($w) => $w->contains('id', $c->id));
        $this->into($this->gcb, ['cash' => [$c->id]])->assertSessionHasNoErrors();
        $this->assertEquals(500, $this->gcb->fresh()->total);
    }

    public function test_cash_kept_in_the_office_cash_box_pays_salaries_there(): void
    {
        $c = $this->receipt(cash: 3000, approved: true);
        $this->into($this->temaCash, ['cash' => [$c->id]])->assertSessionHasNoErrors();

        $this->assertEquals(3000, $this->temaCash->fresh()->total);
        $this->assertSame('Deposited', $c->fresh()->status);
        $line = BankTransaction::where('bank_id', $this->temaCash->id)->first();
        $this->assertStringStartsWith('Collections kept as cash', $line->narration);

        $this->post(route('money.payments.store'), ['mode' => 'other', 'bank_id' => $this->temaCash->id, 'method' => 'cash', 'payee' => 'Tema guards (October salaries)',
            'payment_date' => now()->toDateString(), 'amount' => 2800, 'category' => 'salaries'])->assertSessionHasNoErrors();
        $this->assertEquals(200, $this->temaCash->fresh()->total);
        $this->post(route('money.payments.store'), ['mode' => 'other', 'bank_id' => $this->temaCash->id, 'method' => 'cash', 'payee' => 'X',
            'payment_date' => now()->toDateString(), 'amount' => 500, 'category' => 'other'])->assertSessionHasErrors('amount');   // only 200 left

        // Once spent, the collection can no longer be taken back out of the cash box.
        $d = BankDeposit::sole();
        $this->post(route('deposit.reverse', $d), ['reversal_reason' => 'Wrong office'])->assertSessionHasErrors('reversal_reason');
        $this->assertEquals(200, $this->temaCash->fresh()->total);
    }

    public function test_momo_kept_in_the_wallet_has_no_charge_and_pays_by_momo(): void
    {
        $c = $this->receipt(momo: 1200, approved: true);
        $this->into($this->wallet, ['momo' => [$c->id], 'momo_charge' => '12'])->assertSessionHasErrors('momo_charge');
        $this->into($this->wallet, ['momo' => [$c->id]])->assertSessionHasNoErrors();
        $this->assertEquals(1200, $this->wallet->fresh()->total);
        $this->assertSame(0, BankTransaction::where('channel', 'momo_charge')->count());
        $this->assertStringStartsWith('Collections into the MoMo wallet', BankTransaction::where('bank_id', $this->wallet->id)->value('narration'));

        $this->post(route('money.payments.store'), ['mode' => 'other', 'bank_id' => $this->wallet->id, 'method' => 'cash', 'payee' => 'X',
            'payment_date' => now()->toDateString(), 'amount' => 100, 'category' => 'salaries'])->assertSessionHasErrors('method');
        $this->post(route('money.payments.store'), ['mode' => 'other', 'bank_id' => $this->wallet->id, 'method' => 'momo', 'reference' => 'MP-OUT-1', 'payee' => 'Kojo (guard)',
            'payment_date' => now()->toDateString(), 'amount' => 900, 'category' => 'salaries'])->assertSessionHasNoErrors();
        $this->assertEquals(300, $this->wallet->fresh()->total);
        $this->post(route('money.payments.store'), ['mode' => 'other', 'bank_id' => $this->wallet->id, 'method' => 'momo', 'payee' => 'Y',
            'payment_date' => now()->toDateString(), 'amount' => 400, 'category' => 'salaries'])->assertSessionHasErrors('amount');   // a wallet cannot go below zero
    }

    public function test_momo_withdrawn_into_the_office_cash_box_records_the_charge(): void
    {
        $c = $this->receipt(cash: 500, momo: 300, approved: true);
        $this->into($this->temaCash, ['cash' => [$c->id], 'momo' => [$c->id]])->assertSessionHasErrors('momo_charge');
        $this->into($this->temaCash, ['cash' => [$c->id], 'momo' => [$c->id], 'momo_charge' => '3'])->assertSessionHasNoErrors();
        $this->assertEquals(797, $this->temaCash->fresh()->total);
        $this->assertEquals(3, BankTransaction::where('bank_id', $this->temaCash->id)->where('channel', 'momo_charge')->value('debit'));
    }

    public function test_setting_up_office_cash_boxes_and_the_wallet(): void
    {
        $this->post(route('banks.store'), ['kind' => 'momo', 'name' => 'Ops MoMo'])->assertSessionHasErrors('acc_number');
        $this->post(route('banks.store'), ['kind' => 'momo', 'name' => 'Ops MoMo', 'acc_number' => '0555000000', 'custodian' => 'Ama'])->assertSessionHasNoErrors();
        $this->post(route('banks.store'), ['kind' => 'cash', 'name' => 'Kumasi office cash', 'custodian' => 'Kwesi', 'field_id' => $this->tema->id, 'opening_balance' => 150])->assertSessionHasNoErrors();
        $box = Bank::where('name', 'Kumasi office cash')->first();
        $this->assertSame($this->tema->id, (int) $box->field_id);
        $this->assertEquals(150, $box->total);
        $this->assertSame('momo', Bank::where('name', 'Ops MoMo')->value('kind'));

        // Wallets and cash boxes never appear where a real bank is needed (employees, receipts).
        $this->assertFalse(Bank::banksOnly()->whereIn('kind', ['cash', 'momo'])->exists());
        $this->get(route('banks.show', $this->wallet))->assertOk()->assertSee('MoMo wallet')->assertSee('Wallet balance check');
        $this->get(route('banks.index'))->assertOk()->assertSee('MoMo wallet')->assertSee('Tema office cash');
    }

    public function test_wallet_cashed_out_into_the_office_cash_box(): void
    {
        $c = $this->receipt(momo: 1000, approved: true);
        $this->into($this->wallet, ['momo' => [$c->id]]);
        $this->post(route('money.transfers.store'), ['to_bank_id' => $this->temaCash->id, 'transfer_date' => now()->toDateString(),
            'lines' => [['from_bank_id' => $this->wallet->id, 'method' => 'withdrawal', 'amount' => 600]]])->assertSessionHasNoErrors();
        $this->assertEquals(400, $this->wallet->fresh()->total);
        $this->assertEquals(600, $this->temaCash->fresh()->total);
        $this->post(route('money.transfers.store'), ['to_bank_id' => $this->temaCash->id, 'transfer_date' => now()->toDateString(),
            'lines' => [['from_bank_id' => $this->wallet->id, 'method' => 'cheque', 'reference' => '1', 'amount' => 10]]])->assertSessionHasErrors();
    }
}

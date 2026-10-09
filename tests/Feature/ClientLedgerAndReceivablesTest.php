<?php

namespace Tests\Feature;

use App\Mail\StatementOfAccountMail;
use App\Models\Bank;
use App\Models\Client;
use App\Models\ClientLedgerEntry;
use App\Models\Department;
use App\Models\Field;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\ReceiptAllocation;
use App\Models\ReceiptVoid;
use App\Models\Role;
use App\Models\Service;
use App\Models\StatementSend;
use App\Models\User;
use App\Services\Receipts\ClientLedger;
use App\Services\Receipts\ClientStatement;
use App\Services\Receipts\CreditApplier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClientLedgerAndReceivablesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Field $field;
    private Client $client;
    private Bank $bank;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-10 10:00:00');
        $department = Department::create(['name' => 'Finance']);
        $role = Role::forceCreate(['id' => 2, 'name' => 'Finance Manager', 'department_id' => $department->id]); // approval code keys on role id 2
        $this->user = User::factory()->create(['department_id' => $department->id, 'role_id' => $role->id]);
        $this->field = Field::create(['name' => 'Accra', 'user_id' => $this->user->id, 'number' => '1']);
        $this->client = Client::create(['name' => 'Kofi Mensah', 'business_name' => 'Alpha Security', 'field_id' => $this->field->id, 'phone_number' => '0240000000']);
        $this->bank = Bank::create(['name' => 'GCB', 'branch' => 'Accra', 'acc_number' => '1', 'total' => 0]);
        foreach (['cash', 'cheque', 'transfer', 'momo', 'other payments'] as $m) {
            DB::table('receipt_mode')->insert(['name' => $m]);
        }
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function invoice(float $total, string $month, ?string $due = null, ?Client $client = null): Invoice
    {
        return Invoice::create(['client_id' => ($client ?? $this->client)->id, 'total' => $total, 'sub_amount' => $total, 'invoice_month' => $month,
            'due_date' => $due ?? Carbon::parse($month)->addDays(14), 'status' => 'unpaid', 'balance' => 0]);
    }

    private function pay(Invoice $invoice, array $fields): Receipt
    {
        $this->post('/receipt', array_merge(['invoice_id' => $invoice->id, 'client_id' => $invoice->client_id, 'from' => 'Kofi', 'mode' => ['cash']], $fields))
            ->assertSessionHasNoErrors();

        return Receipt::latest('id')->first();
    }

    private function statement(?string $from = null, ?string $to = null): array
    {
        return app(ClientStatement::class)->build($this->client->fresh(), $from ? Carbon::parse($from) : null, $to ? Carbon::parse($to) : null);
    }

    /* ---------------- ledger ---------------- */

    public function test_ledger_posts_each_document_once_and_resync_posts_nothing(): void
    {
        $inv = $this->invoice(1000, '2026-08-01');
        $this->pay($inv, ['receipt_month' => '2026-08-20', 'cash_amount' => 925, 'wth' => 'on', 'wht_amount' => 75]);

        $kinds = ClientLedgerEntry::where('client_id', $this->client->id)->orderBy('id')->pluck('kind')->all();
        $this->assertSame(['invoice', 'receipt', 'wht'], $kinds);
        $this->assertSame(0, app(ClientLedger::class)->sync($this->client->id));
    }

    public function test_history_is_filled_from_existing_documents_by_the_command(): void
    {
        // Documents from before the ledger existed (no entries yet).
        $inv = $this->invoice(500, '2026-07-01');
        $r = Receipt::create(['client_id' => $this->client->id, 'invoice_id' => $inv->id, 'total' => 500, 'cash_amount' => 500, 'receipt_month' => '2026-07-10']);
        ReceiptAllocation::create(['receipt_id' => $r->id, 'invoice_id' => $inv->id, 'client_id' => $this->client->id, 'amount_applied' => 500, 'settled' => 500, 'source' => 'legacy']);
        ClientLedgerEntry::query()->delete();

        $this->artisan('client-ledger:sync')->assertSuccessful();

        $entries = ClientLedgerEntry::where('client_id', $this->client->id)->orderBy('entry_date')->get();
        $this->assertSame(['2026-07-01', '2026-07-10'], $entries->map(fn ($e) => $e->entry_date->toDateString())->all());
        $this->artisan('client-ledger:sync')->expectsOutputToContain('posted 0 entries')->assertSuccessful();
    }

    public function test_a_correction_never_changes_a_statement_already_sent(): void
    {
        $inv = $this->invoice(1000, '2026-08-01');
        $receipt = $this->pay($inv, ['receipt_month' => '2026-08-20', 'cash_amount' => 600]);
        $august = $this->statement('2026-08-01', '2026-08-31');

        // Two months later the receipt is corrected from 600 to 650.
        $this->put('/receipt/' . $receipt->id, ['invoice_id' => $inv->id, 'client_id' => $this->client->id, 'from' => 'Kofi', 'receipt_month' => '2026-08-20', 'mode' => ['cash'], 'cash_amount' => 650])
            ->assertSessionHasNoErrors();

        $augustAgain = $this->statement('2026-08-01', '2026-08-31');
        $this->assertEquals($august['closing'], $augustAgain['closing']);
        $this->assertSame(count($august['lines']), count($augustAgain['lines']));

        $october = $this->statement('2026-10-01', '2026-10-31');
        $this->assertEquals(400, $october['opening']);
        $this->assertSame(['reversal', 'receipt'], collect($october['lines'])->pluck('kind')->all());
        $this->assertEquals(350, $october['closing']);
        $this->assertTrue($october['check']['ok']);
    }

    public function test_statement_and_client_page_read_the_ledger(): void
    {
        $inv = $this->invoice(300, '2026-09-01');
        $this->pay($inv, ['receipt_month' => '2026-09-05', 'cash_amount' => 100]);

        $this->get('/clientStatement/' . $this->client->id)->assertOk()->assertSee('Receipt FWSSR');
        $this->get('/client/' . $this->client->id)->assertOk()->assertSee('Account')->assertSee('200.00');
    }

    /* ---------------- void ---------------- */

    public function test_void_needs_a_reason_and_keeps_a_record(): void
    {
        $inv = $this->invoice(1000, '2026-09-01');
        $receipt = $this->pay($inv, ['receipt_month' => '2026-09-03', 'mode' => ['transfer'], 'transfer_amount' => 400,
            'transfer_reference' => 'T9', 'transfer_bank' => 'Eco', 'transfer_to_bank_id' => $this->bank->id]);
        $this->assertEquals(400, $this->bank->fresh()->total);

        $this->delete('/receipt/' . $receipt->id)->assertSessionHasErrors('void_reason');
        $this->assertNotNull(Receipt::find($receipt->id));

        $this->delete('/receipt/' . $receipt->id, ['void_reason' => 'Transfer bounced back'])->assertRedirect('receipt');

        $this->assertNull(Receipt::find($receipt->id));
        $void = ReceiptVoid::first();
        $this->assertSame('Transfer bounced back', $void->reason);
        $this->assertEquals(400, $void->amount);
        $this->assertSame($this->user->id, (int) $void->voided_by);
        $this->assertSame($inv->id, (int) $void->snapshot['allocations'][0]['invoice_id']);
        $this->assertEquals(0, $this->bank->fresh()->total);
        $this->assertSame('unpaid', $inv->fresh()->status);

        $st = $this->statement();
        $this->assertSame(['invoice', 'receipt', 'reversal'], collect($st['lines'])->pluck('kind')->all());
        $this->assertStringContainsString('Transfer bounced back', collect($st['lines'])->last()['details']);
        $this->assertEquals(1000, $st['closing']);

        $this->get(route('receipt.voided'))->assertOk()->assertSee('FWSSR' . $receipt->id)->assertSee('Transfer bounced back');
    }

    public function test_paid_invoice_cannot_be_deleted_but_unpaid_one_can(): void
    {
        $paid = $this->invoice(500, '2026-09-01');
        $this->pay($paid, ['receipt_month' => '2026-09-03', 'cash_amount' => 100]);
        $this->delete('/invoice/' . $paid->id)->assertSessionHas('error');
        $this->assertNotNull(Invoice::find($paid->id));

        $unpaid = $this->invoice(200, '2026-10-01');
        app(ClientLedger::class)->sync($this->client->id);
        $this->delete('/invoice/' . $unpaid->id);
        $this->assertNull(Invoice::find($unpaid->id));
        $this->assertSame(1, ClientLedgerEntry::where('source_key', 'invoice:' . $unpaid->id)->where('kind', 'reversal')->count());
    }

    public function test_changing_an_invoice_total_re_derives_its_status(): void
    {
        $inv = $this->invoice(500, '2026-09-01');
        $this->pay($inv, ['receipt_month' => '2026-09-03', 'cash_amount' => 500]);
        $this->assertSame('completed', $inv->fresh()->status);

        // The invoice update screen recalculates totals from its lines; simulate the outcome.
        $inv->update(['total' => 600]);
        app(\App\Services\Receipts\InvoiceSettlement::class)->recalculate($inv->fresh());
        app(ClientLedger::class)->sync($this->client->id);

        $this->assertSame('uncompleted', $inv->fresh()->status);
        $this->assertEquals(100, $inv->fresh()->balance);
        $this->assertEquals(100, $this->statement()['closing']);
    }

    /* ---------------- automatic credit ---------------- */

    public function test_new_invoice_uses_unused_credit_automatically(): void
    {
        $this->post(route('receipt.multi.store'), ['client_id' => $this->client->id, 'receipt_month' => '2026-09-01', 'from' => 'K', 'mode' => ['cash'], 'cash_amount' => 700, 'keep_credit' => '1'])
            ->assertSessionHasNoErrors();
        $credit = Receipt::latest('id')->first();

        $inv = $this->invoice(500, '2026-10-01');
        $this->assertEquals(500, app(CreditApplier::class)->autoApply($inv));

        $this->assertSame('completed', $inv->fresh()->status);
        $this->assertEquals(200, $credit->fresh()->unapplied_amount);
        $this->assertEquals(-200, $this->statement()['closing']);
    }

    public function test_duplicated_invoice_gets_credit_through_the_invoice_screen(): void
    {
        $this->post(route('receipt.multi.store'), ['client_id' => $this->client->id, 'receipt_month' => '2026-09-01', 'from' => 'K', 'mode' => ['cash'], 'cash_amount' => 50, 'keep_credit' => '1']);
        $service = Service::create(['name' => 'Patrol', 'user_id' => $this->user->id]);
        $source = $this->invoice(100, '2026-09-01');

        $this->post(route('invoice.storeDuplicate', ['invoice' => $source->id]), [
            'client_id' => $this->client->id, 'due_date' => '2026-11-15T09:00', 'invoice_month' => '2026-11',
            'service' => [$service->name], 'description' => ['Patrol'], 'quantity' => [1], 'unit_price' => [100], 'amount' => [100],
        ])->assertRedirect()->assertSessionHas('primary', fn ($m) => str_contains($m, 'credit was applied'));

        $new = Invoice::latest('id')->first();
        $this->assertSame('uncompleted', $new->status);
        $this->assertEquals(50, ReceiptAllocation::where('invoice_id', $new->id)->where('source', 'credit')->sum('settled'));
    }

    public function test_automatic_credit_can_be_switched_off_per_client(): void
    {
        $this->client->update(['auto_apply_credit' => false]);
        $this->post(route('receipt.multi.store'), ['client_id' => $this->client->id, 'receipt_month' => '2026-09-01', 'from' => 'K', 'mode' => ['cash'], 'cash_amount' => 700, 'keep_credit' => '1']);

        $inv = $this->invoice(500, '2026-10-01');
        $this->assertEquals(0, app(CreditApplier::class)->autoApply($inv));
        $this->assertSame('unpaid', $inv->fresh()->status);
    }

    public function test_client_form_saves_email_and_credit_switch(): void
    {
        $this->put('/client/' . $this->client->id, ['email' => 'accounts@alpha.test', 'auto_apply_credit' => '0'])->assertRedirect();
        $this->assertSame('accounts@alpha.test', $this->client->fresh()->email);
        $this->assertFalse($this->client->fresh()->auto_apply_credit);

        $this->put('/client/' . $this->client->id, ['email' => 'not-an-email'])->assertSessionHasErrors('email');
    }

    /* ---------------- email + PDF ---------------- */

    public function test_statement_is_emailed_and_logged(): void
    {
        Mail::fake();
        $inv = $this->invoice(800, '2026-09-01');
        $this->pay($inv, ['receipt_month' => '2026-09-03', 'cash_amount' => 300]);

        $this->post(route('client.statement.email', $this->client->id), ['to' => 'accounts@alpha.test', 'cc' => 'md@alpha.test', 'note' => 'Please settle the balance.'])
            ->assertSessionHas('primary');

        Mail::assertSent(StatementOfAccountMail::class, function ($mail) {
            $html = $mail->render();

            return $mail->hasTo('accounts@alpha.test') && $mail->hasCc('md@alpha.test')
                && str_contains($html, 'Please settle the balance.') && str_contains($html, '500.00');
        });
        $send = StatementSend::first();
        $this->assertSame('accounts@alpha.test, md@alpha.test', $send->sent_to);
        $this->assertEquals(500, $send->closing_balance);
        $this->assertSame('accounts@alpha.test', $this->client->fresh()->email);   // remembered

        $this->post(route('client.statement.email', $this->client->id), ['to' => 'accounts@alpha.test, nonsense'])->assertSessionHasErrors('to');
        Mail::assertSentCount(1);
    }

    public function test_pdf_download_explains_when_dompdf_is_missing(): void
    {
        if (\App\Services\Receipts\StatementPdf::available()) {
            $this->get(route('client.statement.pdf', $this->client->id))->assertOk()->assertHeader('Content-Type', 'application/pdf');

            return;
        }
        $this->from('/clientStatement/' . $this->client->id)
            ->get(route('client.statement.pdf', $this->client->id))
            ->assertRedirect('/clientStatement/' . $this->client->id)->assertSessionHas('warning');
    }

    /* ---------------- aged receivables ---------------- */

    public function test_aged_receivables_buckets_and_credit(): void
    {
        $this->invoice(100, '2026-10-01', '2026-10-20');   // not yet due
        $this->invoice(200, '2026-09-01', '2026-09-30');   // 10 days
        $this->invoice(300, '2026-08-01', '2026-08-26');   // 45 days
        $old = $this->invoice(400, '2026-06-01', '2026-06-20');   // 112 days
        $this->pay($old, ['receipt_month' => '2026-07-01', 'cash_amount' => 150]);
        $other = Client::create(['name' => 'Ama', 'field_id' => $this->field->id]);
        $this->post(route('receipt.multi.store'), ['client_id' => $other->id, 'receipt_month' => '2026-09-01', 'from' => 'A', 'mode' => ['cash'], 'cash_amount' => 90, 'keep_credit' => '1']);

        $report = app(\App\Services\Receipts\ReceivablesAging::class)->build();
        $kofi = $report['rows']->firstWhere('client_id', $this->client->id);
        $this->assertEquals(100, $kofi['current']);
        $this->assertEquals(200, $kofi['d30']);
        $this->assertEquals(300, $kofi['d60']);
        $this->assertEquals(250, $kofi['d90plus']);
        $this->assertEquals(850, $kofi['owed']);
        $ama = $report['rows']->firstWhere('client_id', $other->id);
        $this->assertEquals(-90, $ama['net']);
        $this->assertEquals(760, $report['totals']['net']);

        $this->get(route('receivables.aged'))->assertOk()->assertSee('Alpha Security')->assertSee('90.00 CR');
    }

    public function test_branch_users_only_see_their_office(): void
    {
        $tema = Field::create(['name' => 'Tema', 'user_id' => $this->user->id, 'number' => '2']);
        $temaClient = Client::create(['name' => 'Yaw', 'business_name' => 'Tema Port Ltd', 'field_id' => $tema->id]);
        $this->invoice(100, '2026-09-01', null, $temaClient);
        $this->invoice(100, '2026-09-01');

        $branchRole = Role::create(['name' => 'Manager', 'department_id' => $this->user->department_id]);
        $branch = User::factory()->create(['department_id' => $this->user->department_id, 'role_id' => $branchRole->id, 'field_id' => $this->field->id]);
        $this->actingAs($branch);

        $this->get(route('receivables.aged'))->assertOk()->assertSee('Alpha Security')->assertDontSee('Tema Port Ltd');
        $this->get(route('receivables.aged', ['field' => $tema->id]))->assertForbidden();
        $this->post(route('client.statement.email', $temaClient->id), ['to' => 'x@y.test'])->assertForbidden();
    }
}

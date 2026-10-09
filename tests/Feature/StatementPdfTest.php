<?php
namespace Tests\Feature;

use App\Models\{Client, Department, Field, Invoice, Role, User};
use App\Services\Receipts\{ClientStatement, StatementPdf};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Renders a real statement PDF. Skipped until dompdf is installed (composer require dompdf/dompdf). */
class StatementPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_statement_renders_as_pdf(): void
    {
        if (! StatementPdf::available()) {
            $this->markTestSkipped('dompdf is not installed.');
        }
        $d = Department::create(['name' => 'Finance']);
        $r = Role::forceCreate(['id' => 2, 'name' => 'Finance Manager', 'department_id' => $d->id]);
        $u = User::factory()->create(['department_id' => $d->id, 'role_id' => $r->id]);
        $f = Field::create(['name' => 'Accra', 'user_id' => $u->id, 'number' => '1']);
        $c = Client::create(['name' => 'Kofi Mensah', 'business_name' => 'Alpha Security Ltd', 'field_id' => $f->id, 'address' => '12 Ring Road, Accra', 'phone_number' => '0240000000']);
        foreach (['cash','cheque','transfer','momo','other payments'] as $m) DB::table('receipt_mode')->insert(['name' => $m]);
        $this->actingAs($u);
        $aug = Invoice::create(['client_id'=>$c->id,'total'=>12500,'sub_amount'=>12500,'invoice_month'=>'2026-08-01','due_date'=>'2026-08-15','status'=>'unpaid','balance'=>0]);
        $sep = Invoice::create(['client_id'=>$c->id,'total'=>12500,'sub_amount'=>12500,'invoice_month'=>'2026-09-01','due_date'=>'2026-09-15','status'=>'unpaid','balance'=>0]);
        $this->post('/receipt', ['invoice_id'=>$aug->id,'client_id'=>$c->id,'from'=>'Kofi','receipt_month'=>'2026-08-20','mode'=>['cash'],'cash_amount'=>11562.5,'wth'=>'on','wht_amount'=>937.5])->assertSessionHasNoErrors();
        $this->post('/receipt', ['invoice_id'=>$sep->id,'client_id'=>$c->id,'from'=>'Kofi','receipt_month'=>'2026-09-25','mode'=>['cash'],'cash_amount'=>5000])->assertSessionHasNoErrors();
        $st = app(ClientStatement::class)->build($c);
        $pdf = app(StatementPdf::class)->render($st);
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->get(route('client.statement.pdf', $c->id))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }
}

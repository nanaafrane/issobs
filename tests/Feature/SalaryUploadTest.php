<?php

namespace Tests\Feature;

use App\Http\Controllers\SalaryController;
use App\Models\Attendance;
use App\Models\Client;
use App\Models\Department;
use App\Models\employee;
use App\Models\Field;
use App\Models\PaymentInfo;
use App\Models\Role;
use App\Models\Salary;
use App\Models\SalaryUploadBatch;
use App\Models\User;
use App\Support\SalaryCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Salary upload: template with row checks, check (nothing saved), apply chosen columns, undo.
 */
class SalaryUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;
    private User $accManager;
    private User $officer;
    private User $hrManager;
    private Client $client;
    private Field $accra;
    private Field $tema;
    private employee $kofi;   // tax + SSNIT, 2 days absent
    private employee $ama;    // no tax / SSNIT
    private employee $yaw;    // already paid
    private Salary $sKofi;
    private Salary $sAma;
    private Salary $sYaw;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-04 10:00'));
        Storage::fake('local');

        $acc = Department::create(['name' => 'Accounts']);
        $hr = Department::create(['name' => 'HR']);
        $fm = Role::create(['name' => 'Finance Manager', 'department_id' => $acc->id]);
        $mgr = Role::create(['name' => 'Manager', 'department_id' => $acc->id]);
        $off = Role::create(['name' => 'Officer', 'department_id' => $acc->id]);
        $this->finance = User::factory()->create(['department_id' => $acc->id, 'role_id' => $fm->id, 'name' => 'Fin Manager']);
        $this->accManager = User::factory()->create(['department_id' => $acc->id, 'role_id' => $mgr->id, 'name' => 'Acc Manager']);
        $this->officer = User::factory()->create(['department_id' => $acc->id, 'role_id' => $off->id, 'name' => 'Acc Officer']);
        $this->hrManager = User::factory()->create(['department_id' => $hr->id, 'role_id' => $mgr->id, 'name' => 'HR Manager']);

        $this->accra = Field::create(['name' => 'Accra', 'user_id' => $this->finance->id, 'number' => '0300000000']);
        $this->tema = Field::create(['name' => 'Tema', 'user_id' => $this->finance->id, 'number' => '0300000001']);
        $this->client = Client::create(['name' => 'DVLA', 'business_name' => 'DVLA HQ', 'phone_number' => '0240000000', 'address' => 'Accra',
            'field_id' => $this->accra->id, 'status' => 'active', 'ho_status' => 'approved', 'user_id' => $this->finance->id]);

        $this->kofi = $this->employee('KOFI MENSAH', $this->accra->id, 1550, 200, ['tax_button' => 'on', 'ssnit_button' => 'on'], 'C123456789');
        $this->ama = $this->employee('AMA SERWAA', $this->tema->id, 800, 0);
        $this->yaw = $this->employee('YAW PAID', $this->accra->id, 900, 0);

        $this->sKofi = $this->salary($this->kofi);
        $this->sAma = $this->salary($this->ama, ['loan' => 50]);
        $this->sYaw = $this->salary($this->yaw, ['payment_status' => 'approved']);
        // Totals as the edit form leaves them.
        foreach ([$this->sKofi, $this->sAma, $this->sYaw] as $s) {
            SalaryCalculator::fill($s->load('employee'), [])->save();
        }

        // Daily rate = basic / days in the month (works with either default of the rule).
        \App\Support\Attendance\AbsentDeduction::saveSettings('basic', 'month_days', true, $this->finance->id);
        foreach (['2026-10-03', '2026-10-04'] as $d) {
            Attendance::create(['client_id' => $this->client->id, 'employee_id' => $this->kofi->id, 'work_date' => $d,
                'shift' => 'day', 'status' => 'absent', 'source' => 'manual']);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function employee(string $name, int $fieldId, float $basic, float $allowances, array $extra = [], ?string $ssnit = null): employee
    {
        $e = new employee();
        $e->forceFill($extra + ['name' => $name, 'status' => 'Active', 'ho_status' => 'approved', 'client_id' => $this->client->id, 'field_id' => $fieldId,
            'basic_salary' => $basic, 'allowances' => $allowances, 'payment_type' => 'Cash', 'user_id' => $this->finance->id])->save();
        $info = new PaymentInfo();
        $info->forceFill(['employee_id' => $e->id, 'ssnit_number' => $ssnit, 'user_id' => $this->finance->id])->save();

        return $e;
    }

    private function salary(employee $e, array $extra = []): Salary
    {
        $s = new Salary();
        $s->forceFill($extra + ['employee_id' => $e->id, 'salary_month' => '2026-10-01', 'field_id' => $e->field_id, 'client_id' => $e->client_id,
            'basic_salary' => $e->basic_salary, 'allowances' => $e->allowances, 'payment_status' => 'pending'])->save();

        return $s;
    }

    /** Download the template and open it. */
    private function template(array $columns, ?int $fieldId = null): Spreadsheet
    {
        $res = $this->actingAs($this->officer)->get(route('salary-upload.template', ['month' => '2026-10', 'columns' => $columns, 'field_id' => $fieldId]));
        $res->assertOk();
        return IOFactory::load($this->downloaded($res));
    }

    private function downloaded($res): string
    {
        $path = tempnam(sys_get_temp_dir(), 'dl') . '.xlsx';
        copy($res->baseResponse->getFile()->getPathname(), $path);

        return $path;
    }

    private function headers(Spreadsheet $book): array
    {
        $ws = $book->getSheetByName('Salaries');
        $out = [];
        foreach ($ws->getRowIterator(2, 2)->current()->getCellIterator() as $cell) {
            if ($cell->getValue() !== null) {
                $out[$cell->getValue()] = $cell->getColumn();
            }
        }

        return $out;
    }

    /** Row number of a salary in the template. */
    private function rowOf(Spreadsheet $book, int $salaryId): int
    {
        $ws = $book->getSheetByName('Salaries');
        for ($r = 3; $r <= $ws->getHighestRow(); $r++) {
            if ((int) $ws->getCell('A' . $r)->getValue() === $salaryId) {
                return $r;
            }
        }
        $this->fail('Salary ' . $salaryId . ' not in the template');
    }

    private function uploadBook(Spreadsheet $book, User $as, string $route = 'salary-upload.check', string $field = 'file')
    {
        $path = tempnam(sys_get_temp_dir(), 'up') . '.xlsx';
        (new Xlsx($book))->setPreCalculateFormulas(false)->save($path);
        $file = new UploadedFile($path, 'october.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        return $this->actingAs($as)->post(route($route), [$field => $file, 'month' => '2026-10']);
    }

    // ------------------------------------------------------------------ calculator

    public function test_tax_is_the_same_as_the_old_tax_function(): void
    {
        $old = app(SalaryController::class);
        foreach ([0, 400, 490, 491, 550, 599.99, 600, 650, 729.99, 730, 731, 1000, 1417.5, 2500, 3896.67, 3900, 5000, 12000] as $amount) {
            $this->assertEqualsWithDelta($old->taxEmployee($amount), SalaryCalculator::tax($amount), 0.000001, "tax on $amount");
        }
        for ($amount = 0; $amount < 8000; $amount += 37.13) {
            $this->assertEqualsWithDelta($old->taxEmployee($amount), SalaryCalculator::tax($amount), 0.000001, "tax on $amount");
        }
    }

    public function test_totals_follow_the_salary_rules(): void
    {
        $t = SalaryCalculator::totals(['basic_salary' => 1550, 'allowances' => 200, 'loan' => 100], $this->kofi);
        // SSNIT on (number in payment details): 5% tier 2 is added to gross and taken off.
        $this->assertEqualsWithDelta(77.5, $t['ssnit_tier2_5'], 0.001);
        $this->assertEqualsWithDelta(7.75, $t['ssnit_tier1_0_5'], 0.001);
        $this->assertEqualsWithDelta(209.25, $t['ssnit_tobe_paid13_5'], 0.001);
        $tax = SalaryCalculator::tax(1550 - 1550 * 0.055);
        $this->assertEqualsWithDelta($tax, $t['tax'], 0.001);
        $this->assertEqualsWithDelta(1550 + 200 + 77.5, $t['gross_salary'], 0.001);
        $this->assertEqualsWithDelta($tax + 77.5 + 100, $t['total_deductions'], 0.001);
        $this->assertEqualsWithDelta(1750 - $tax - 100, $t['net_salary'], 0.001);
        $this->assertEqualsWithDelta(1750 - $tax - 100 + 209.25, $t['cost_to_company'], 0.001);

        // No switches: no tax, no SSNIT.
        $t = SalaryCalculator::totals(['basic_salary' => 800, 'loan' => 50], $this->ama);
        $this->assertSame([0.0, 0.0, 800.0, 750.0], [$t['tax'], $t['ssnit_tier2_5'], $t['gross_salary'], $t['net_salary']]);

        // Tax on, SSNIT off (even with a number): tax on the full basic.
        $this->kofi->ssnit_button = null;
        $t = SalaryCalculator::totals(['basic_salary' => 1550], $this->kofi);
        $this->assertEqualsWithDelta(SalaryCalculator::tax(1550), $t['tax'], 0.001);
        $this->assertSame(0.0, $t['ssnit_tier2_5']);
    }

    public function test_the_edit_form_uses_the_same_calculation(): void
    {
        $payload = array_fill_keys(array_keys(SalaryCalculator::editable()), null) + [
            'payment_type' => 'Cash', 'field_id' => $this->accra->id, 'client_id' => $this->client->id, 'location' => 'HQ',
        ];
        $payload['basic_salary'] = 1550;
        $payload['allowances'] = 200;
        $payload['overtime'] = 120;
        $this->actingAs($this->finance)->put('/salaries/' . $this->sKofi->id, $payload)->assertRedirect();

        $expected = SalaryCalculator::totals(['basic_salary' => 1550, 'allowances' => 200, 'overtime' => 120], $this->kofi);
        $s = $this->sKofi->fresh();
        $this->assertEqualsWithDelta(round($expected['net_salary'], 2), (float) $s->net_salary, 0.001);
        $this->assertEqualsWithDelta(120, (float) $s->overtime, 0.001);
        $this->assertNull($s->loan);
    }

    // ------------------------------------------------------------------ template

    public function test_template_has_unpaid_salaries_only_the_chosen_columns_and_a_working_row_check(): void
    {
        $book = $this->template(['overtime', 'absent', 'loan']);
        $h = $this->headers($book);
        $this->assertSame(['Salary ID', 'Employee ID', 'Name', 'Office', 'Client', 'Status', 'Overtime', 'Absent', 'Loan',
            'Absent days (attendance)', 'Absent from attendance', 'Earnings not in this file', 'Deductions not in this file', 'Net pay now', 'Row check (automatic)'], array_keys($h));

        $ws = $book->getSheetByName('Salaries');
        $this->assertSame(4, $ws->getHighestRow());          // Kofi + Ama; Yaw is paid
        $this->assertNotContains($this->sYaw->id, [(int) $ws->getCell('A3')->getValue(), (int) $ws->getCell('A4')->getValue()]);

        $k = $this->rowOf($book, $this->sKofi->id);
        $a = $this->rowOf($book, $this->sAma->id);
        $chk = function ($r) use ($ws, $book, $h) {
            \PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance($book)->clearCalculationCache();

            return $ws->getCell($h['Row check (automatic)'] . $r)->getCalculatedValue();
        };
        // Attendance: 2 days x 1550/31 = 100.00; Kofi's absent is blank, so the check passes until a figure is typed.
        $this->assertEquals(2, $ws->getCell($h['Absent days (attendance)'] . $k)->getValue());
        $this->assertEqualsWithDelta(100.0, $ws->getCell($h['Absent from attendance'] . $k)->getValue(), 0.001);
        $this->assertEqualsWithDelta(1750, $ws->getCell($h['Earnings not in this file'] . $k)->getValue(), 0.001);
        $this->assertSame('OK', $chk($k));
        $this->assertEquals(50, $ws->getCell($h['Loan'] . $a)->getValue());
        $this->assertSame('OK', $chk($a));

        // Typed mistakes are caught in Excel, before upload.
        $ws->setCellValue($h['Absent'] . $k, 80);
        $this->assertSame('CHECK: absent differs from attendance (100.00)', $chk($k));
        $ws->setCellValue($h['Absent'] . $k, 100);
        $this->assertSame('OK', $chk($k));
        $ws->setCellValue($h['Overtime'] . $k, 'two hundred');
        $this->assertSame('ERROR: Overtime must be an amount of 0 or more', $chk($k));
        $ws->setCellValue($h['Overtime'] . $k, -5);
        $this->assertSame('ERROR: Overtime must be an amount of 0 or more', $chk($k));
        $ws->setCellValue($h['Overtime'] . $k, 0);
        $ws->setCellValue($h['Loan'] . $a, 900);
        $this->assertSame('ERROR: deductions are more than earnings (net below 0)', $chk($a));
        $ws->setCellValue($h['Loan'] . $a, 50);
        $ws->setCellValue('A' . $a, $this->sKofi->id);
        $this->assertSame('ERROR: Salary ID repeated in this file', $chk($a));
        $this->assertStringContainsString('Errors: 2', $ws->getCell($h['Row check (automatic)'] . '1')->getCalculatedValue());

        // Bad cells are shaded as typed; amounts only (validation).
        $this->assertNotEmpty($ws->getStyle($h['Overtime'] . '3:' . $h['Overtime'] . '4')->getConditionalStyles());
        $this->assertSame('decimal', $ws->getCell($h['Overtime'] . $k)->getDataValidation()->getType());
    }

    public function test_template_for_one_office(): void
    {
        $ws = $this->template(['basic_salary'], $this->tema->id)->getSheetByName('Salaries');
        $this->assertSame(3, $ws->getHighestRow());
        $this->assertSame($this->sAma->id, (int) $ws->getCell('A3')->getValue());
    }

    // ------------------------------------------------------------------ check, apply

    public function test_check_saves_nothing_and_explains_errors_then_apply_saves_the_good_rows(): void
    {
        $book = $this->template(['overtime', 'absent', 'loan']);
        $h = $this->headers($book);
        $ws = $book->getSheetByName('Salaries');
        $k = $this->rowOf($book, $this->sKofi->id);
        $a = $this->rowOf($book, $this->sAma->id);
        $ws->setCellValue($h['Overtime'] . $k, 300);
        $ws->setCellValue($h['Absent'] . $k, 100);
        $ws->setCellValue($h['Loan'] . $a, 'fifty');
        $netBefore = (float) $this->sKofi->fresh()->net_salary;

        $res = $this->uploadBook($book, $this->officer);
        $upload = SalaryUploadBatch::sole();
        $res->assertRedirect(route('salary-upload.show', ['upload' => $upload, 'month' => '2026-10']));
        $this->assertNull($this->sKofi->fresh()->overtime);   // nothing saved

        $page = $this->actingAs($this->officer)->get(route('salary-upload.show', $upload));
        $page->assertOk()
            ->assertSee('Loan: &quot;fifty&quot; is not a number', false)
            ->assertSee('Download the 1 rows with errors')
            ->assertSee('A Finance Manager or Accounts Manager applies it')
            ->assertDontSee('id="applyBtn"', false);
        $this->assertSame([2, 1, 1], [$upload->fresh()->total_rows, $upload->fresh()->ready_rows, $upload->fresh()->error_rows]);

        // An officer cannot apply; an Accounts manager can.
        $this->actingAs($this->officer)->post(route('salary-upload.apply', $upload), ['columns' => ['overtime', 'absent', 'loan']])->assertForbidden();
        $this->actingAs($this->accManager)->get(route('salary-upload.show', $upload))->assertSee('Apply 1 salaries');
        $this->actingAs($this->accManager)->post(route('salary-upload.apply', $upload), ['columns' => ['overtime', 'absent', 'loan']])
            ->assertRedirect(route('salary-upload.show', $upload))
            ->assertSessionHas('success', fn ($m) => str_contains($m, '1 salaries updated') && str_contains($m, '1 rows with errors were not saved'));

        $kofi = $this->sKofi->fresh();
        $expected = SalaryCalculator::totals(['basic_salary' => 1550, 'allowances' => 200, 'overtime' => 300, 'absent' => 100], $this->kofi);
        $this->assertEqualsWithDelta(300, (float) $kofi->overtime, 0.001);
        $this->assertEqualsWithDelta(100, (float) $kofi->absent, 0.001);
        $this->assertEqualsWithDelta(round($expected['net_salary'], 2), (float) $kofi->net_salary, 0.001);
        $this->assertSame($this->accManager->id, (int) $kofi->user_id1);
        $this->assertEqualsWithDelta(50, (float) $this->sAma->fresh()->loan, 0.001);   // error row untouched

        $upload->refresh();
        $this->assertSame('applied', $upload->status);
        $this->assertSame($this->accManager->id, (int) $upload->applied_by);
        $this->assertEqualsWithDelta($netBefore, (float) $upload->net_before, 0.01);
        $this->assertEquals(['overtime' => ['old' => null, 'new' => 300], 'absent' => ['old' => null, 'new' => 100]], $upload->changes[$this->sKofi->id]['changes']);
        $this->actingAs($this->accManager)->post(route('salary-upload.apply', $upload), ['columns' => ['overtime']])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'already been applied'));

        // The rows not saved come back as a file to fix: same layout, the bad cell marked and explained.
        $err = $this->actingAs($this->officer)->get(route('salary-upload.errors', $upload));
        $err->assertOk();
        $eb = IOFactory::load($this->downloaded($err));
        $eh = $this->headers($eb);
        $ews = $eb->getSheetByName('Salaries');
        $this->assertSame(3, $ews->getHighestRow());
        $this->assertSame($this->sAma->id, (int) $ews->getCell('A3')->getValue());
        $this->assertSame('fifty', $ews->getCell($eh['Loan'] . '3')->getValue());
        $this->assertStringContainsString('is not a number', $ews->getCell($eh['Errors from upload'] . '3')->getValue());
        $this->assertStringContainsString('is not a number', $ews->getComment($eh['Loan'] . '3')->getText()->getPlainText());
        $this->assertSame('ERROR: Loan must be an amount of 0 or more', $ews->getCell($eh['Row check (automatic)'] . '3')->getCalculatedValue());

        // Fixed and uploaded again: it goes through.
        $ews->setCellValue($eh['Loan'] . '3', 75);
        $this->uploadBook($eb, $this->finance);
        $second = SalaryUploadBatch::latest('id')->first();
        $this->actingAs($this->finance)->post(route('salary-upload.apply', $second), ['columns' => ['loan']])->assertSessionHas('success');
        $this->assertEqualsWithDelta(75, (float) $this->sAma->fresh()->loan, 0.001);
        $this->assertEqualsWithDelta(725, (float) $this->sAma->fresh()->net_salary, 0.001);
    }

    public function test_only_the_ticked_columns_are_applied(): void
    {
        $book = $this->template(['overtime', 'absent']);
        $h = $this->headers($book);
        $ws = $book->getSheetByName('Salaries');
        $k = $this->rowOf($book, $this->sKofi->id);
        $ws->setCellValue($h['Overtime'] . $k, 300);
        $ws->setCellValue($h['Absent'] . $k, 100);
        $this->uploadBook($book, $this->finance);
        $upload = SalaryUploadBatch::sole();

        // Preview with only Absent ticked.
        $this->actingAs($this->finance)->get(route('salary-upload.show', ['upload' => $upload, 'apply_sent' => 1, 'columns' => ['absent']]))
            ->assertOk()->assertSee('Absent: <span class="old">—</span> → <strong>100.00</strong>', false)
            ->assertDontSee('Overtime: <span class="old">', false);

        $this->actingAs($this->finance)->post(route('salary-upload.apply', $upload), ['columns' => ['absent']])->assertSessionHas('success');
        $s = $this->sKofi->fresh();
        $this->assertNull($s->overtime);
        $this->assertEqualsWithDelta(100, (float) $s->absent, 0.001);
        $this->assertSame(['absent'], $upload->fresh()->applied_columns);
    }

    public function test_row_problems_that_block_or_warn(): void
    {
        $book = $this->template(['basic_salary', 'absent', 'loan']);
        $h = $this->headers($book);
        $ws = $book->getSheetByName('Salaries');
        $k = $this->rowOf($book, $this->sKofi->id);
        $a = $this->rowOf($book, $this->sAma->id);
        // Kofi: absent not what attendance gives; basic not the employee record's.
        $ws->setCellValue($h['Absent'] . $k, 40);
        $ws->setCellValue($h['Basic salary'] . $k, 1600);
        // Ama: rows out of line (wrong employee) and a loan blanked.
        $ws->setCellValue($h['Employee ID'] . $a, $this->kofi->id);
        $ws->setCellValue($h['Loan'] . $a, null);
        // A paid salary and a missing one, typed in by hand.
        $ws->fromArray([$this->sYaw->id, $this->yaw->id, 'YAW', 'Accra', '', 'approved', 900, null, null], null, 'A5');
        $ws->fromArray([99999, 1, 'X', '', '', '', 100, null, null], null, 'A6');
        $ws->fromArray([null, $this->ama->id, 'AMA', '', '', '', 100, null, null], null, 'A7');

        $this->uploadBook($book, $this->finance);
        $upload = SalaryUploadBatch::sole();
        $this->actingAs($this->finance)->get(route('salary-upload.show', $upload))->assertOk()
            ->assertSee('Absent 40.00 is not what attendance gives: 2 day(s) absent = 103.22.')   // 2 x (1600/31 = 51.61)
            ->assertSee('is not the 1,550.00 on the employee record')
            ->assertSee('does not match Salary ID ' . $this->sAma->id)
            ->assertSee('Already approved (paid)')
            ->assertSee('Salary ID 99999 does not exist')
            ->assertSee('Salary ID is blank');
        $this->assertSame([5, 1, 4], [$upload->fresh()->total_rows, $upload->fresh()->ready_rows, $upload->fresh()->error_rows]);
        $this->assertSame(1, $upload->fresh()->warning_rows);

        $this->actingAs($this->finance)->post(route('salary-upload.apply', $upload), ['columns' => ['basic_salary', 'absent', 'loan']]);
        $this->assertEqualsWithDelta(40, (float) $this->sKofi->fresh()->absent, 0.001);   // a warning does not block
        $this->assertSame('approved', $this->sYaw->fresh()->payment_status);
        $this->assertEqualsWithDelta(900, (float) $this->sYaw->fresh()->basic_salary, 0.001);
    }

    public function test_blank_keeps_the_value_and_net_below_zero_is_an_error(): void
    {
        $book = $this->template(['loan', 'iou']);
        $h = $this->headers($book);
        $ws = $book->getSheetByName('Salaries');
        $a = $this->rowOf($book, $this->sAma->id);
        $k = $this->rowOf($book, $this->sKofi->id);
        $ws->setCellValue($h['Loan'] . $a, null);
        $ws->setCellValue($h['IOU'] . $a, 25);
        $ws->setCellValue($h['IOU'] . $k, '2,500.00');   // text with a comma is read as 2500: more than the pay
        $this->uploadBook($book, $this->finance);
        $upload = SalaryUploadBatch::sole();
        $this->actingAs($this->finance)->get(route('salary-upload.show', $upload))
            ->assertSee('Loan is blank: kept at 50.00. Type 0 to clear it.')
            ->assertSee('deductions (')
            ->assertSee('Check IOU (2,500.00)');

        $this->actingAs($this->finance)->post(route('salary-upload.apply', $upload), ['columns' => ['loan', 'iou']]);
        $ama = $this->sAma->fresh();
        $this->assertEqualsWithDelta(50, (float) $ama->loan, 0.001);
        $this->assertEqualsWithDelta(25, (float) $ama->iou, 0.001);
        $this->assertEqualsWithDelta(725, (float) $ama->net_salary, 0.001);
        $this->assertNull($this->sKofi->fresh()->iou);
    }

    public function test_old_upload_form_and_old_export_layout_go_through_the_check(): void
    {
        $book = new Spreadsheet();
        $ws = $book->getActiveSheet();
        $ws->fromArray([
            ['id', 'Salary Month', 'employee_id', 'Name', 'Field', 'basic_salary', 'allowances', 'overtime', 'loan'],
            [$this->sAma->id, 'October, 2026', $this->ama->id, 'AMA', 'Tema', 800, 0, 60, 50],
            [null, 'October, 2026', $this->kofi->id, 'KOFI', 'Accra', 1550, 200, 10, null],
        ]);
        $this->uploadBook($book, $this->finance, 'salaries.upload', 'excelFile')
            ->assertRedirect(route('salary-upload.show', ['upload' => SalaryUploadBatch::sole(), 'month' => '2026-10']));
        $upload = SalaryUploadBatch::sole();
        $this->actingAs($this->finance)->get(route('salary-upload.show', $upload))->assertOk()->assertSee('older export')->assertSee('Apply 2 salaries');
        $this->assertNull($this->sAma->fresh()->overtime);

        $this->actingAs($this->finance)->post(route('salary-upload.apply', $upload), ['columns' => ['basic_salary', 'allowances', 'overtime', 'loan']]);
        $this->assertEqualsWithDelta(60, (float) $this->sAma->fresh()->overtime, 0.001);
        $this->assertEqualsWithDelta(10, (float) $this->sKofi->fresh()->overtime, 0.001);    // found by Employee ID + month
    }

    public function test_the_page_stays_on_the_uploads_own_month(): void
    {
        // A September salary, uploaded in November (the page's default month is October).
        $sept = $this->salary($this->ama, ['salary_month' => '2026-09-01']);
        $book = new Spreadsheet();
        $book->getActiveSheet()->fromArray([['id', 'overtime'], [$sept->id, 30]]);
        $path = tempnam(sys_get_temp_dir(), 'up') . '.xlsx';
        (new Xlsx($book))->save($path);
        $this->actingAs($this->finance)->post(route('salary-upload.check'), ['file' => new UploadedFile($path, 'sept.xlsx', null, null, true)]);
        $upload = SalaryUploadBatch::sole();

        $this->actingAs($this->finance)->get(route('salary-upload.show', $upload))->assertOk()
            ->assertSee('salariesCreate?month=2026-09', false)->assertSee('September 2026 salaries')
            ->assertSee(route('salary-upload.index', ['month' => '2026-09']), false)
            ->assertDontSee('October 2026 salaries');

        $this->actingAs($this->finance)->post(route('salary-upload.apply', $upload), ['columns' => ['overtime']])->assertSessionHas('success');
        $this->actingAs($this->finance)->get(route('salary-upload.show', $upload))->assertOk()
            ->assertSee('salariesCreate?month=2026-09', false)->assertDontSee('October 2026 salaries');
    }

    public function test_a_file_that_is_not_a_salary_file(): void
    {
        $book = new Spreadsheet();
        $book->getActiveSheet()->fromArray([['Name', 'Phone'], ['A', '024']]);
        $this->uploadBook($book, $this->finance)->assertRedirect()->assertSessionHasErrors('file');

        $book = new Spreadsheet();
        $book->getActiveSheet()->fromArray([['Name', 'Overtime'], ['A', 5]]);
        $this->uploadBook($book, $this->finance)->assertSessionHasErrors(['file' => 'This file has no "Salary ID" column, so the rows cannot be matched to salaries. Download the template again - it has the Salary ID in column A. (An old export also works: it needs its "id" column, or Employee ID and Salary Month.)']);
    }

    public function test_undo_puts_back_what_was_not_changed_since(): void
    {
        $book = $this->template(['overtime']);
        $h = $this->headers($book);
        $ws = $book->getSheetByName('Salaries');
        $ws->setCellValue($h['Overtime'] . $this->rowOf($book, $this->sKofi->id), 300);
        $ws->setCellValue($h['Overtime'] . $this->rowOf($book, $this->sAma->id), 40);
        $before = [$this->sKofi->fresh()->net_salary, $this->sAma->fresh()->net_salary];
        $this->uploadBook($book, $this->finance);
        $upload = SalaryUploadBatch::sole();
        $this->actingAs($this->finance)->post(route('salary-upload.apply', $upload), ['columns' => ['overtime']]);

        // Ama's salary is edited again afterwards: undo leaves it.
        $ama = $this->sAma->fresh()->load('employee');
        SalaryCalculator::fill($ama, ['overtime' => 55])->save();

        $this->actingAs($this->officer)->post(route('salary-upload.undo', $upload))->assertNotFound();   // not theirs
        $this->actingAs($this->hrManager)->post(route('salary-upload.undo', $upload))->assertForbidden();
        $this->actingAs($this->finance)->post(route('salary-upload.undo', $upload))
            ->assertSessionHas('success', '1 salaries put back to what they were before october.xlsx.')
            ->assertSessionHas('warning', fn ($m) => str_contains($m, 'changed again since the upload'));
        $this->assertNull($this->sKofi->fresh()->overtime);
        $this->assertSame($before[0], $this->sKofi->fresh()->net_salary);
        $this->assertEqualsWithDelta(55, (float) $this->sAma->fresh()->overtime, 0.001);
        $this->assertSame('undone', $upload->fresh()->status);
        $this->actingAs($this->finance)->get(route('salary-upload.show', $upload))->assertOk()->assertSee('Undone by Fin Manager');
    }

    public function test_who_can_use_it(): void
    {
        $this->actingAs($this->hrManager)->get(route('salary-upload.index'))->assertForbidden();
        $this->actingAs($this->officer)->get(route('salary-upload.index'))->assertOk()->assertSee('Download template');
        $this->actingAs($this->finance)->get('/salariesCreate?month=2026-10')->assertOk()->assertSee('Download template (with row checks)');

        // Someone else's check is not open to an officer, but is to the managers who apply.
        $book = $this->template(['overtime']);
        $this->uploadBook($book, $this->accManager);
        $upload = SalaryUploadBatch::sole();
        $this->actingAs($this->officer)->get(route('salary-upload.show', $upload))->assertNotFound();
        $this->actingAs($this->finance)->get(route('salary-upload.show', $upload))->assertOk();
    }
}

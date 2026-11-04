<?php

namespace App\Exports\SalaryUpload;

use App\Support\SalaryCalculator;
use App\Support\SalaryUpload;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The sheet people fill in: intro (row 1), headers (row 2 - read back by
 * SalaryUpload::read), one row per salary from row 3.
 *
 *   grey     Salary ID (the key) and information columns: not uploaded
 *   yellow   the amount columns chosen, pre-filled with today's values
 *   last     "Row check (automatic)": a formula that says OK, CHECK: ... or ERROR: ...
 *            and turns red / amber, with the bad cells shaded red as they are typed
 *
 * The error report uses the same sheet with the uploaded values, an "Errors from
 * upload" column and the cells that failed marked red, so it can be fixed and
 * uploaded again as it is.
 */
class SalariesSheet implements FromArray, WithTitle, WithEvents
{
    /** @var array<int, array{0:string,1:string,2:string,3:int,4:string}> key, header, kind, width, note */
    private array $cols = [];

    /**
     * @param string[] $columns amount columns (keys) in the file
     * @param array    $rows    SalaryUpload::templateRows() shape
     * @param array<int, array{messages: string[], cols: string[]}>|null $errors per row index (error report)
     */
    public function __construct(private array $columns, private array $rows, private ?array $errors = null, private string $intro = '')
    {
        $this->cols[] = ['salary_id', SalaryUpload::KEY_HEADER, 'key', 10, 'Which salary this row is. Do not change, delete or sort this column on its own.'];
        $this->cols[] = ['employee_id', 'Employee ID', 'info', 11, 'Information only. Checked on upload: it must still match the Salary ID.'];
        $this->cols[] = ['name', 'Name', 'info', 26, ''];
        $this->cols[] = ['office', 'Office', 'info', 12, ''];
        $this->cols[] = ['client', 'Client', 'info', 22, ''];
        $this->cols[] = ['status', 'Status', 'info', 10, 'pending, rejected or hold. Approved (paid) salaries are not in this file and cannot be changed by upload.'];
        foreach ($columns as $k) {
            $note = SalaryCalculator::isDeduction($k) ? 'Deduction: type the amount as a positive number.' : 'Earning.';
            $note .= ' Blank keeps the current value; type 0 to clear it.';
            if ($k === 'absent') {
                $note .= ' Compare with "Absent from attendance" at the right.';
            }
            $this->cols[] = [$k, SalaryCalculator::editable()[$k], 'money', 13, $note];
        }
        if (in_array('absent', $columns, true)) {
            $this->cols[] = ['absent_days', SalaryUpload::INFO['absent_days'], 'info_num', 11, 'Days marked Absent on the attendance sheet this month (blank = guard not on attendance).'];
            $this->cols[] = ['absent_attendance', SalaryUpload::INFO['absent_attendance'], 'info_money', 13, 'What those days come to with the Absent deductions rule. The Row check flags an Absent amount that differs.'];
        }
        if (count($columns) < count(SalaryCalculator::editable())) {
            $this->cols[] = ['earnings_elsewhere', SalaryUpload::INFO['earnings_elsewhere'], 'info_money', 13, 'The earnings this file leaves out, as they are now. Used by the Row check only.'];
            $this->cols[] = ['deductions_elsewhere', SalaryUpload::INFO['deductions_elsewhere'], 'info_money', 13, 'The deductions this file leaves out, as they are now (tax and SSNIT not included).'];
        }
        $this->cols[] = ['current_net', SalaryUpload::INFO['current_net'], 'info_money', 12, 'Net pay as saved now (blank = not worked out yet). Tax, SSNIT and net are worked out again on upload.'];
    }

    public function title(): string
    {
        return 'Salaries';
    }

    public function array(): array
    {
        $headers = array_column($this->cols, 1);
        $headers[] = 'Row check (automatic)';
        if ($this->errors !== null) {
            $headers[] = 'Errors from upload';
        }
        $out = [[$this->intro ?: 'One row per salary, filled in with the amounts saved now. Change the yellow cells only. A blank cell keeps the current value. Watch the Row check column - every row should say OK (or a CHECK you agree with) before you upload.'], $headers];
        foreach (array_values($this->rows) as $i => $row) {
            $line = [];
            foreach ($this->cols as [$key]) {
                $line[] = array_key_exists($key, $row['values'] ?? []) ? $row['values'][$key] : ($row[$key] ?? null);
            }
            $line[] = null;   // Row check: formula set below
            if ($this->errors !== null) {
                $line[] = implode("\n", $this->errors[$i]['messages'] ?? []);
            }
            $out[] = $line;
        }

        return $out;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->style($event->sheet->getDelegate());
            },
        ];
    }

    private function style(Worksheet $ws): void
    {
        $first = 3;
        $last = max($first, $first + count($this->rows) - 1);
        $n = count($this->cols);
        $L = fn (int $i) => Coordinate::stringFromColumnIndex($i);
        $col = [];
        foreach ($this->cols as $i => [$key]) {
            $col[$key] = $L($i + 1);
        }
        $lastCol = $L($n);
        $checkCol = $L($n + 1);
        $errCol = $this->errors !== null ? $L($n + 2) : null;
        $endCol = $errCol ?? $checkCol;
        $fill = fn ($range, $argb) => $ws->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($argb);

        $ws->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $ws->mergeCells('A1:' . $lastCol . '1');
        $ws->getStyle('A1')->getFont()->setBold(true)->getColor()->setARGB($this->errors !== null ? 'FFC00000' : 'FF1F3864');
        $ws->getStyle('A1')->getAlignment()->setWrapText(true)->setVertical('top');
        $ws->getRowDimension(1)->setRowHeight(42);

        // Live count above the Row check column.
        $range = "\${$checkCol}\${$first}:\${$checkCol}\${$last}";
        $ws->setCellValue($checkCol . '1', "=\"Errors: \"&COUNTIF({$range},\"ERROR*\")&\"   Checks: \"&COUNTIF({$range},\"CHECK*\")");
        $ws->getStyle($checkCol . '1')->getFont()->setBold(true);
        $ws->getStyle($checkCol . '1')->getAlignment()->setVertical('top');

        $earn = [];
        $ded = [];
        foreach ($this->cols as [$key, , $kind, $width, $note]) {
            $c = $col[$key];
            $ws->getColumnDimension($c)->setWidth($width);
            $fill($c . '2', $kind === 'money' ? 'FF1F3864' : 'FF7F7F7F');
            if ($note !== '') {
                $ws->getComment($c . '2')->getText()->createTextRun($note);
                $ws->getComment($c . '2')->setWidth('240pt')->setHeight('60pt');
            }
            $r = $c . $first . ':' . $c . $last;
            if ($kind === 'money') {
                $fill($r, 'FFFFF2CC');
                $ws->getStyle($r)->getNumberFormat()->setFormatCode('#,##0.00');
                $ws->setDataValidation($r, (new DataValidation())->setType(DataValidation::TYPE_DECIMAL)
                    ->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL)->setFormula1('0')
                    ->setAllowBlank(true)->setShowErrorMessage(true)->setErrorStyle(DataValidation::STYLE_STOP)
                    ->setErrorTitle('Amount only')->setError('Type an amount of 0 or more, e.g. 1250.50 - no letters or minus sign.'));
                SalaryCalculator::isDeduction($key) ? $ded[] = $c : $earn[] = $c;
            } else {
                $fill($r, $kind === 'key' ? 'FFD9D9D9' : 'FFEDEDED');
                $font = $ws->getStyle($r)->getFont();
                $kind === 'key' ? $font->setBold(true) : $font->setItalic(true)->getColor()->setARGB('FF595959');
                if ($kind === 'info_money') {
                    $ws->getStyle($r)->getNumberFormat()->setFormatCode('#,##0.00');
                }
            }
        }
        $ws->getStyle('A2:' . $endCol . '2')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $ws->getStyle('A2:' . $endCol . '2')->getAlignment()->setWrapText(true)->setVertical('center')->setHorizontal('center');
        $ws->getRowDimension(2)->setRowHeight(34);
        $fill($checkCol . '2', 'FF404040');
        $ws->getColumnDimension($checkCol)->setWidth(46);
        $ws->getComment($checkCol . '2')->getText()->createTextRun('Worked out by Excel as you type. ERROR = will not be saved until fixed. CHECK = will be saved, but look again. Tax, SSNIT and net are checked exactly on upload.');
        $ws->getComment($checkCol . '2')->setWidth('240pt')->setHeight('70pt');
        if ($errCol) {
            $fill($errCol . '2', 'FFC00000');
            $ws->getColumnDimension($errCol)->setWidth(60);
        }

        // Row check formula, per row.
        $labels = SalaryCalculator::editable();
        $k = $col['salary_id'];
        for ($r = $first; $r < $first + count($this->rows); $r++) {
            $tests = [
                ["{$k}{$r}=\"\"", '"ERROR: Salary ID missing - copy the row from a new template"'],
                ["COUNTIF(\${$k}\${$first}:\${$k}\${$last},{$k}{$r})>1", '"ERROR: Salary ID repeated in this file"'],
            ];
            foreach ($this->columns as $key) {
                $c = $col[$key];
                $tests[] = ["AND({$c}{$r}<>\"\",OR(NOT(ISNUMBER({$c}{$r})),N({$c}{$r})<0))", '"ERROR: ' . $labels[$key] . ' must be an amount of 0 or more"'];
            }
            $sum = fn (array $cells) => $cells ? 'SUM(' . implode(',', array_map(fn ($c) => $c . $r, $cells)) . ')' : '0';
            $else = fn (string $key) => isset($col[$key]) ? "+N({$col[$key]}{$r})" : '';
            $tests[] = ['(' . $sum($earn) . $else('earnings_elsewhere') . ')-(' . $sum($ded) . $else('deductions_elsewhere') . ')<0',
                '"ERROR: deductions are more than earnings (net below 0)"'];
            if (isset($col['basic_salary'])) {
                $b = $col['basic_salary'];
                $tests[] = ["AND(ISNUMBER({$b}{$r}),N({$b}{$r})=0)", '"CHECK: basic salary is 0"'];
            }
            if (isset($col['absent'], $col['absent_attendance'])) {
                $a = $col['absent'];
                $t = $col['absent_attendance'];
                $tests[] = ["AND({$t}{$r}<>\"\",ISNUMBER({$a}{$r}),ROUND(N({$a}{$r}),2)<>ROUND(N({$t}{$r}),2))",
                    "\"CHECK: absent differs from attendance (\"&TEXT(N({$t}{$r}),\"#,##0.00\")&\")\""];
            }
            $formula = '"OK"';
            foreach (array_reverse($tests) as [$test, $value]) {
                $formula = "IF({$test},{$value},{$formula})";
            }
            $ws->setCellValue($checkCol . $r, "=IF(COUNTA(A{$r}:{$lastCol}{$r})=0,\"\",{$formula})");
        }
        $checkRange = $checkCol . $first . ':' . $checkCol . $last;
        $fill($checkRange, 'FFF2F2F2');
        $ws->getStyle($checkRange)->getFont()->setBold(true);

        // Colours that follow what is typed.
        $red = fn (string $formula) => (new Conditional())->setConditionType(Conditional::CONDITION_EXPRESSION)->addCondition($formula)
            ->setStyle((new \PhpOffice\PhpSpreadsheet\Style\Style(false, true))->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFC7CE'], 'endColor' => ['argb' => 'FFFFC7CE']],
                'font' => ['color' => ['argb' => 'FF9C0006'], 'bold' => true],
            ]));
        $amber = fn (string $formula) => (new Conditional())->setConditionType(Conditional::CONDITION_EXPRESSION)->addCondition($formula)
            ->setStyle((new \PhpOffice\PhpSpreadsheet\Style\Style(false, true))->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFEB9C'], 'endColor' => ['argb' => 'FFFFEB9C']],
                'font' => ['color' => ['argb' => 'FF9C5700']],
            ]));
        $green = fn (string $formula) => (new Conditional())->setConditionType(Conditional::CONDITION_EXPRESSION)->addCondition($formula)
            ->setStyle((new \PhpOffice\PhpSpreadsheet\Style\Style(false, true))->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFC6EFCE'], 'endColor' => ['argb' => 'FFC6EFCE']],
                'font' => ['color' => ['argb' => 'FF006100']],
            ]));

        $ws->getStyle($checkRange)->setConditionalStyles([
            $red("LEFT({$checkCol}{$first},5)=\"ERROR\""),
            $amber("LEFT({$checkCol}{$first},5)=\"CHECK\""),
            $green("{$checkCol}{$first}=\"OK\""),
        ]);
        $ws->getStyle("{$k}{$first}:{$k}{$last}")->setConditionalStyles([
            $red("OR({$k}{$first}=\"\",COUNTIF(\${$k}\${$first}:\${$k}\${$last},{$k}{$first})>1)"),
        ]);
        foreach ($this->columns as $key) {
            $c = $col[$key];
            $styles = [$red("AND({$c}{$first}<>\"\",OR(NOT(ISNUMBER({$c}{$first})),N({$c}{$first})<0))")];
            if ($key === 'absent' && isset($col['absent_attendance'])) {
                $t = $col['absent_attendance'];
                $styles[] = $amber("AND({$t}{$first}<>\"\",ISNUMBER({$c}{$first}),ROUND(N({$c}{$first}),2)<>ROUND(N({$t}{$first}),2))");
            }
            $ws->getStyle("{$c}{$first}:{$c}{$last}")->setConditionalStyles($styles);
        }

        // Error report: the cells the upload rejected, marked and explained.
        if ($this->errors !== null) {
            foreach (array_values($this->rows) as $i => $row) {
                $r = $first + $i;
                foreach ($this->errors[$i]['cols'] ?? [] as $key => $msg) {
                    if (! isset($col[$key])) {
                        continue;
                    }
                    $cell = $col[$key] . $r;
                    $ws->getStyle($cell)->getBorders()->getAllBorders()->setBorderStyle('medium')->getColor()->setARGB('FFC00000');
                    $fill($cell, 'FFFFC7CE');
                    $ws->getComment($cell)->getText()->createTextRun($msg);
                    $ws->getComment($cell)->setWidth('260pt')->setHeight('70pt');
                }
            }
            $ws->getStyle($errCol . $first . ':' . $errCol . $last)->getFont()->getColor()->setARGB('FFC00000');
            $ws->getStyle($errCol . $first . ':' . $errCol . $last)->getAlignment()->setWrapText(true)->setVertical('top');
        }

        $ws->freezePane('D3');
        $ws->setAutoFilter('A2:' . $endCol . $last);
    }
}

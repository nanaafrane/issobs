<?php

namespace App\Exports\SalaryUpload;

use App\Models\User;
use App\Support\SalaryCalculator;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class InstructionsSheet implements FromArray, WithTitle, WithEvents
{
    private const LINES = [
        ['How to use', 'h'],
        ['1.  Go to the "Salaries" sheet. Each row is one salary of the month, filled in with the amounts saved now.', null],
        ['2.  Change only the yellow cells. Grey columns are information: they are not uploaded (Salary ID tells ISSOBS which salary the row is - never change it).', null],
        ['3.  A BLANK cell keeps the current amount. Type 0 to clear an amount. Deductions are typed as positive numbers.', null],
        ['4.  Watch the "Row check (automatic)" column at the right. Its top cell counts the rows with errors and checks.', null],
        ['        OK        the row is fine.', null],
        ['        CHECK: ...   it will be saved, but look again (e.g. Absent differs from attendance).', null],
        ['        ERROR: ...   it will NOT be saved until fixed (e.g. text in an amount, a repeated Salary ID, deductions more than earnings).', null],
        ['    Bad cells turn red as you type. You can delete rows you do not want to change, and sort or filter whole rows.', null],
        ['5.  In ISSOBS: Payroll > Salaries > choose the month > Upload salaries, choose this file and press Check file.', null],
        ['6.  Nothing is saved at the check. The page lists every error (with what to fix), every change (old > new) and net pay per office before and after.', null],
        ['    Tick the columns to apply - e.g. only Absent - and press Apply. Rows with errors are left as they are; download them, fix and upload again.', null],
        ['', null],
        ['Good to know', 'h'],
        ['-  Tax, SSNIT, gross, total deductions, net pay and cost to company are worked out again on upload, as on the salary edit form.', null],
        ['-  Approved (paid) salaries are not in this file and cannot be changed by upload: unapprove first, or pay a top-up.', null],
        ['-  Only this month\'s salary changes. The employee record (basic salary, allowances) stays as it is.', null],
        ['-  An applied upload can be undone from the upload history, for salaries not paid or changed again since.', null],
        ['-  Do not rename or delete column headers (row 2). New employees are added on "Add to Salaries", not here.', null],
    ];

    public function __construct(
        private User $user,
        private array $columns,
        private int $count,
        private string $month,
        private ?string $office,
        private bool $errorReport = false,
    ) {
    }

    public function title(): string
    {
        return 'Instructions';
    }

    public function array(): array
    {
        $labels = array_map(fn ($k) => SalaryCalculator::editable()[$k], $this->columns);
        $rows = [
            ['', ($this->errorReport ? 'ISSOBS - Salary upload: rows to fix' : 'ISSOBS - Salary upload') . ' - ' . $this->month . ($this->office ? ' - ' . $this->office : '')],
            ['', 'Prepared for ' . $this->user->name . ' on ' . now()->format('d M Y H:i') . '. ' . $this->count . ' salaries. Columns in this file: ' . implode(', ', $labels) . '.'],
            ['', ''],
        ];
        foreach (self::LINES as [$text]) {
            $rows[] = ['', $text];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridlines(false);
                $sheet->getColumnDimension('A')->setWidth(3);
                $sheet->getColumnDimension('B')->setWidth(120);
                $sheet->getStyle('B1:B40')->getAlignment()->setWrapText(true);
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB($this->errorReport ? 'FFC00000' : 'FF1F3864');
                $sheet->getStyle('B2')->getFont()->setItalic(true);
                foreach (self::LINES as $i => [$text, $kind]) {
                    if ($kind === 'h') {
                        $sheet->getStyle('B' . ($i + 4))->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FFC00000');
                    }
                    if (str_contains($text, 'ERROR: ...')) {
                        $sheet->getStyle('B' . ($i + 4))->getFont()->getColor()->setARGB('FF9C0006');
                    } elseif (str_contains($text, 'CHECK: ...')) {
                        $sheet->getStyle('B' . ($i + 4))->getFont()->getColor()->setARGB('FF9C5700');
                    } elseif (str_contains($text, 'OK        ')) {
                        $sheet->getStyle('B' . ($i + 4))->getFont()->getColor()->setARGB('FF006100');
                    }
                }
            },
        ];
    }
}

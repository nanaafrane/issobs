<?php

namespace App\Exports\EmployeeUpload;

use App\Models\User;
use App\Support\EmployeeBulkImport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class InstructionsSheet implements FromArray, WithTitle, WithEvents
{
    private const CREATE_LINES = [
        ['How to use', 'h'],
        ['1.  Fill in the "Employees" sheet, one employee per row, starting at row 3.', null],
        ['2.  Red column headers are required. Hover over a header to see what it expects.', null],
        ['3.  Watch the grey "Row check" column: every filled row should say OK before you upload.', null],
        ['4.  In ISSOBS go to Employees > Bulk upload, choose this file and who to ASSIGN TO, then Preview.', null],
        ['5.  Nothing is saved at the preview step. Fix any rows shown in red (or download the error report), then Confirm.', null],
        ['', null],
        ['Rules checked on upload', 'h'],
        ['-  Phone, Ghana Card, account, TIN and SSNIT numbers must not already exist in ISSOBS or repeat in this file.', null],
        ['-  Field office and client must be ones you can assign. Payment Type = Bank needs Bank and Account Number.', null],
        ['-  Deduct Tax = Yes needs a TIN; Deduct SSNIT = Yes needs an SSNIT number.', null],
        ['-  Employees get the same approval status as the single "Add employee" form for your role. Payment priority is set automatically.', null],
        ['', null],
        ['Tips', 'h'],
        ['-  Number columns such as phone and account numbers are formatted as text so Excel keeps leading zeros.', null],
        ['-  Do not rename, reorder or delete columns. Photos are added later from each employee profile.', null],
    ];

    private const UPDATE_LINES = [
        ['How to use', 'h'],
        ['1.  Each row is one existing employee, already filled in with their current details. The grey "Employee ID" column says who it is: do not change it.', null],
        ['2.  Change only the cells you want to update. A BLANK cell means "leave unchanged" - it does not clear the value.', null],
        ['3.  "Current Status" (Active / Terminated) is for sorting and filtering only - e.g. filter to Active before editing. It is never changed by the upload.', null],
        ['4.  You can delete rows you do not want to change. Watch the grey "Row check" column.', null],
        ['5.  In ISSOBS go to Employees > Bulk update, choose this file (and who to ASSIGN TO, if asked), then Preview.', null],
        ['6.  The preview lists every change, field by field, and shows each employee\'s status. Nothing is saved until you confirm.', null],
        ['', null],
        ['Good to know', 'h'],
        ['-  Edits follow the same rules as editing one employee: for some roles the employee goes back to Pending for approval.', null],
        ['-  To terminate or re-instate someone, use the employee page (it records the status month and needs approval).', null],
        ['-  Bank, account and payment type changes are listed separately and must be ticked before confirming.', null],
        ['-  Setting Deduct Tax (or Deduct SSNIT) to No clears the TIN (or SSNIT number), as on the edit form.', null],
        ['-  Phone, Ghana Card, account, TIN and SSNIT numbers must not belong to another employee.', null],
        ['-  Do not rename, reorder or delete columns.', null],
    ];

    public function __construct(private User $user, private string $mode = 'create')
    {
    }

    private function lines(): array
    {
        return $this->mode === 'update' ? self::UPDATE_LINES : self::CREATE_LINES;
    }

    public function title(): string
    {
        return 'Instructions';
    }

    public function array(): array
    {
        $title = $this->mode === 'update' ? 'ISSOBS - Bulk employee UPDATE' : 'ISSOBS - Bulk employee upload template';
        $limit = $this->mode === 'update' ? EmployeeBulkImport::MAX_UPDATE_ROWS : EmployeeBulkImport::MAX_ROWS;
        $rows = [
            ['', $title],
            ['', 'Prepared for ' . $this->user->name . ' on ' . now()->format('d M Y H:i') . '. The lists only show the field offices and clients you can assign. Up to ' . $limit . ' employees per file.'],
            ['', ''],
        ];
        foreach ($this->lines() as [$text]) {
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
                $sheet->getColumnDimension('B')->setWidth(110);
                $sheet->getStyle('B1:B40')->getAlignment()->setWrapText(true);
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FF1F3864');
                $sheet->getStyle('B2')->getFont()->setItalic(true);
                foreach ($this->lines() as $i => [, $kind]) {
                    if ($kind === 'h') {
                        $sheet->getStyle('B' . ($i + 4))->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FFC00000');
                    }
                }
            },
        ];
    }
}

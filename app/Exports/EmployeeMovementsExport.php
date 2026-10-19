<?php

namespace App\Exports;

use App\Support\EmployeeMovements;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** HR movements report: a summary sheet, one sheet of movements, and what needs attention. */
class EmployeeMovementsExport implements WithMultipleSheets
{
    public function __construct(private array $report)
    {
    }

    public function sheets(): array
    {
        $r = $this->report;
        $t = $r['totals'];
        $period = $r['from']->format('d M Y') . ' to ' . $r['to']->format('d M Y');

        $summary = [['HR MOVEMENTS ' . strtoupper($period)], [],
            ['OFFICE', 'ACTIVE AT START', 'NEW RECRUITS', 'RE-INSTATED', 'TRANSFERS IN', 'TERMINATED', 'TRANSFERS OUT', 'ACTIVE AT END', 'NET', 'TURNOVER %']];
        foreach ($r['offices'] as $o) {
            $summary[] = [$o['name'], $o['opening'], $o['hires'], $o['reinstated'], $o['transfers_in'], $o['terminated'], $o['transfers_out'], $o['closing'], $o['net'], $o['turnover']];
        }
        $summary[] = ['TOTAL', $t['opening'], $t['hires'], $t['reinstated'], $t['transfers_in'], $t['terminated'], $t['transfers_out'], $t['closing'], $t['net'], $t['turnover']];
        if (! empty($r['awaiting_termination'])) {
            $summary[] = ['Active at end includes ' . $r['awaiting_termination'] . ' awaiting termination approval'];
        }
        $summary[] = [];
        $summary[] = ['ALL EMPLOYEES ON RECORD TODAY', 'ACTIVE', 'ACTIVE, EDIT AWAITING APPROVAL', 'NEW RECRUITS AWAITING APPROVAL',
            'TERMINATION AWAITING APPROVAL', 'RE-INSTATEMENT AWAITING APPROVAL', 'TERMINATED', 'TOTAL ON RECORD'];
        foreach (array_merge($r['by_status']['offices'], [$r['by_status']['total']]) as $o) {
            $summary[] = [$o['name'], $o['active'], $o['edit_pending'], $o['recruit_pending'], $o['termination_pending'], $o['reinstatement_pending'], $o['terminated'], $o['total']];
        }
        $summary[] = [];
        $summary[] = ['WHY STAFF LEFT', 'COUNT'];
        foreach ($r['by_reason'] as $reason => $n) {
            $summary[] = [$reason, $n];
        }
        $summary[] = [];
        $summary[] = ['HOW LONG LEAVERS HAD WORKED', 'COUNT'];
        foreach ($r['tenure'] as $band => $n) {
            $summary[] = [$band, $n];
        }
        if ($r['payroll']) {
            $summary[] = [];
            $summary[] = ['PAYROLL CHECK', ''];
            $summary[] = ['Employed at some point in the month', $r['payroll']['employed']];
            $summary[] = ['Salaries generated', $r['payroll']['salaries']];
            $summary[] = ['Employed but no salary', count($r['payroll']['no_salary'])];
            $summary[] = ['Salary but not employed', count($r['payroll']['not_employed'])];
        }

        $moves = [['TYPE', 'DATE', 'DATE ESTIMATED', 'EMPLOYEE ID', 'NAME', 'PHONE', 'OFFICE', 'FROM OFFICE', 'CLIENT', 'ROLE', 'REASON', 'DAYS EMPLOYED', 'NOTE', 'REQUESTED BY', 'APPROVED BY']];
        foreach (['hire', 'reinstatement', 'termination', 'transfer'] as $type) {
            foreach ($r['lists'][$type] as $m) {
                $moves[] = [EmployeeMovements::LABELS[$type], $m['date'], $m['estimated'] ? 'Yes' : '', 'FWSS ' . $m['employee_id'], $m['name'], $m['phone'],
                    $m['office'], $m['from_office'] ?? '', $m['client'], $m['role'], $m['reason'] ?? '', $m['tenure_days'], $m['note'], $m['requested_by'], $m['approved_by']];
            }
        }

        $attention = [['ISSUE', 'EMPLOYEE ID', 'NAME', 'DETAIL']];
        foreach ($r['pending'] as $p) {
            $attention[] = ['Waiting for head office', 'FWSS ' . $p['employee_id'], $p['name'], $p['type'] . ($p['date'] ? ' from ' . $p['date'] : '') . ($p['reason'] ? ' · ' . $p['reason'] : '')];
        }
        foreach ($r['undated'] as $p) {
            $attention[] = ['No date recorded', 'FWSS ' . $p['employee_id'], $p['name'], $p['type']];
        }
        foreach ($r['mismatches'] as $p) {
            $attention[] = ['Status disagrees with movements', 'FWSS ' . $p['employee_id'], $p['name'], 'Status ' . $p['status'] . ', movements say ' . strtolower($p['movements_say'])];
        }
        foreach ($r['payroll']['no_salary'] ?? [] as $p) {
            $attention[] = ['Employed but no salary', 'FWSS ' . $p['employee_id'], $p['name'], ''];
        }
        foreach ($r['payroll']['not_employed'] ?? [] as $p) {
            $attention[] = ['Salary but not employed', 'FWSS ' . $p['employee_id'], $p['name'], ''];
        }

        return [
            self::sheet('SUMMARY', $summary, 3),
            self::sheet('MOVEMENTS', $moves, 1),
            self::sheet('NEEDS ATTENTION', $attention, 1),
        ];
    }

    private static function sheet(string $title, array $rows, int $headerRow): object
    {
        return new class($title, $rows, $headerRow) implements FromArray, WithStrictNullComparison, WithStyles, WithTitle {
            public function __construct(private string $title, private array $rows, private int $headerRow)
            {
            }

            public function title(): string
            {
                return $this->title;
            }

            public function array(): array
            {
                return $this->rows;
            }

            public function styles(Worksheet $sheet)
            {
                $sheet->getStyle('A1')->getFont()->setBold(true);
                $sheet->getStyle($this->headerRow . ':' . $this->headerRow)->getFont()->setBold(true);
                foreach (range('A', 'O') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                return [];
            }
        };
    }
}

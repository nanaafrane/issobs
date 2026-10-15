<?php

namespace App\Exports;

use App\Support\PaymentRuns;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * OFFICE | STAFF | ACTUAL PAYTABLE | <PAYMENT n, date> | BALANCE | ... | TOTAL PAID | OUTSTANDING
 * One main / hold / top-ups row per office, then TOTAL, FUNDS RELEASED, VARIANCE and the
 * outstanding breakdown. Every amount is a value from the data, not a typed figure.
 */
class SalaryPaymentsSummarySheet implements FromArray, WithStrictNullComparison, WithStyles, WithTitle
{
    private int $dataRows = 0;
    private int $width = 0;

    public function __construct(private array $report)
    {
    }

    public function title(): string
    {
        return 'SAL SUMM';
    }

    public static function rowLabel(array $row): string
    {
        return strtoupper($row['office']) . match ($row['row']) {
            PaymentRuns::ROW_HOLD => ' - HOLD',
            PaymentRuns::ROW_TOPUP => ' - TOP UPS',
            default => '',
        };
    }

    public function array(): array
    {
        $r = $this->report;
        $cols = $r['columns'];
        $out = [];
        $out[] = [strtoupper($r['month']->format('F Y')) . ' SALARY PAYMENT REPORT'];

        $head = ['OFFICE', 'STAFF', 'ACTUAL PAYTABLE'];
        $dates = ['', '', ''];
        foreach ($cols as $c) {
            $head[] = strtoupper($c['label']);
            $head[] = 'BALANCE';
            $dates[] = $c['date'] ?? '';
            $dates[] = '';
        }
        array_push($head, 'TOTAL PAID', 'OUTSTANDING');
        array_push($dates, '', '');
        $out[] = $head;
        $out[] = $dates;
        $this->width = count($head);

        foreach ($r['rows'] as $row) {
            $line = [self::rowLabel($row), $row['staff'], $row['owed']];
            foreach ($cols as $c) {
                $line[] = round($row['paid'][$c['key']] ?? 0, 2);
                $line[] = $row['balance'][$c['key']];
            }
            array_push($line, $row['paid_total'], $row['outstanding']);
            $out[] = $line;
        }
        $this->dataRows = count($r['rows']);

        $t = $r['totals'];
        $total = ['TOTAL', collect($r['rows'])->sum('staff'), round($t['owed'], 2)];
        $funds = ['FUNDS RELEASED', '', ''];
        $variance = ['VARIANCE (RELEASED - PAID)', '', ''];
        foreach ($cols as $c) {
            array_push($total, round($t['paid'][$c['key']] ?? 0, 2), round($t['balance'][$c['key']] ?? 0, 2));
            $f = $r['funds'][$c['key']] ?? null;
            array_push($funds, $f ? $f['released'] : '', '');
            array_push($variance, $f ? $f['difference'] : '', '');
        }
        array_push($total, round($t['paid_total'], 2), $t['outstanding']);
        $out[] = $total;
        $out[] = $funds;
        $out[] = $variance;

        $b = $r['breakdown'];
        $out[] = [];
        $out[] = ['OUTSTANDING', '', $t['outstanding']];
        $out[] = ['  Not yet paid (pending)', '', $b['not_paid']];
        $out[] = ['  On hold', '', $b['held']];
        $out[] = ['  Top-ups not yet paid', '', $b['topups']];
        $out[] = ['  Owed: net went up after payment', '', $b['owed_after_change']];
        $out[] = ['  Overpaid: net went down after payment', '', $b['overpaid_after_change']];
        if (abs($b['other']) >= 0.01) {
            $out[] = ['  Other', '', $b['other']];
        }

        return $out;
    }

    public function styles(Worksheet $sheet)
    {
        $last = Coordinate::stringFromColumnIndex(max($this->width, 3));
        $firstData = 4;
        $totalRow = $firstData + $this->dataRows;
        $sheet->mergeCells("A1:{$last}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle("A2:{$last}3")->getFont()->setBold(true);
        $sheet->getStyle("A2:{$last}3")->getAlignment()->setHorizontal('center')->setWrapText(true);
        $sheet->getStyle("A{$totalRow}:{$last}" . ($totalRow + 2))->getFont()->setBold(true);
        $sheet->getStyle("A2:{$last}{$totalRow}")->getBorders()->getAllBorders()->setBorderStyle('thin');
        $sheet->getStyle("B{$firstData}:B{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("C{$firstData}:{$last}" . ($totalRow + 12))->getNumberFormat()->setFormatCode('#,##0.00;[Red]-#,##0.00');
        $sheet->getColumnDimension('A')->setWidth(34);
        for ($i = 2; $i <= $this->width; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth($i === 2 ? 8 : 15);
        }
        $sheet->freezePane('D4');

        return [];
    }
}

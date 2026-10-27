<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * The analysed cash book in the Main Cash sheet layout, with a second block of totals per item
 * and office under it. Rows come from App\Services\Banking\CashBook::sheet().
 */
class CashBookExport implements FromArray, WithTitle, WithEvents, WithColumnFormatting
{
    public function __construct(private array $rows, private array $summary, private string $title)
    {
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }

    public function array(): array
    {
        return array_merge($this->rows, [[], []], $this->summary);
    }

    public function columnFormats(): array
    {
        $width = max(array_map('count', $this->rows ?: [[]]));
        $out = [];
        for ($c = 4; $c <= $width; $c++) {
            $out[Coordinate::stringFromColumnIndex($c)] = '#,##0.00;-#,##0.00;""';
        }

        return $out;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {
                $sheet = $e->sheet->getDelegate();
                $width = max(array_map('count', $this->rows ?: [[]]));
                $last = Coordinate::stringFromColumnIndex(max($width, 1));
                $sheet->getStyle('A1:A2')->getFont()->setBold(true);
                $sheet->getStyle('B2')->getFont()->setBold(true);
                $sheet->getStyle('A5:' . $last . '5')->getFont()->setBold(true);
                $sheet->getStyle('A5:' . $last . '5')->getAlignment()->setWrapText(true);
                $sheet->freezePane('C6');
                $totalRow = count($this->rows);
                $sheet->getStyle('A' . $totalRow . ':' . $last . $totalRow)->getFont()->setBold(true);
                $sheet->getColumnDimension('A')->setWidth(10);
                $sheet->getColumnDimension('B')->setWidth(48);
                $sheet->getColumnDimension('C')->setWidth(11);
                for ($c = 4; $c <= $width; $c++) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setWidth(14);
                }
                $start = $totalRow + 3;
                $sheet->getStyle('A' . $start . ':' . $last . ($start + 1))->getFont()->setBold(true);
            },
        ];
    }
}

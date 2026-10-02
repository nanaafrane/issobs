<?php

namespace App\Exports\EmployeeUpload;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/** Dropdown values, one column per list, each exposed as a workbook named range. */
class ListsSheet implements FromArray, WithTitle, WithEvents
{
    /** @param array<string, string[]> $lists named range => values */
    public function __construct(private array $lists)
    {
    }

    public function title(): string
    {
        return 'Lists';
    }

    public function array(): array
    {
        $names = array_keys($this->lists);
        $height = max(array_map('count', $this->lists) ?: [0]);
        $rows = [$names];
        for ($i = 0; $i < $height; $i++) {
            $rows[] = array_map(fn ($name) => $this->lists[$name][$i] ?? null, $names);
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $book = $sheet->getParent();
                $i = 1;
                foreach ($this->lists as $name => $values) {
                    $col = Coordinate::stringFromColumnIndex($i++);
                    $book->addNamedRange(new NamedRange($name, $sheet, '$' . $col . '$2:$' . $col . '$' . max(2, count($values) + 1)));
                    $sheet->getColumnDimension($col)->setWidth($name === 'Clients' ? 40 : 18);
                }
                $lastCol = Coordinate::stringFromColumnIndex(count($this->lists));
                $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A1:' . $lastCol . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F3864');
            },
        ];
    }
}

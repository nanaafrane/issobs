<?php

namespace App\Exports;

use Closure;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exports EVERY row of an already-filtered query (not just the DataTables page).
 *
 * The controller builds the query with the same filter/scope methods its
 * datatable() uses, so the export always matches what the user sees and can
 * never include rows they are not allowed to see.
 *
 * FromQuery reads in chunks (config/excel.php exports.chunk_size = 1000) using
 * forPage(), so the query MUST have a deterministic ORDER BY. Every list query
 * ends with a unique-id tiebreaker for that reason.
 *
 * Map numbers as numbers (not number_format strings) so Excel can sum them.
 * Not queueable: the mapper is a Closure, which cannot be serialised.
 */
class FilteredQueryExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function __construct(
        private $query,
        private array $headings,
        private Closure $mapper,
    ) {
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function map($row): array
    {
        return ($this->mapper)($row);
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}

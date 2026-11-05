<?php

namespace App\Exports;

use App\Support\OutstandingInvoices;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/** The outstanding-invoices list as a sheet: a title block, one row per invoice, a total row. */
class OutstandingInvoicesExport implements FromArray, WithTitle, WithEvents, WithColumnFormatting
{
    private const HEAD_ROW = 4;

    public function __construct(private Collection $rows, private OutstandingInvoices $book, private string $scope, private ?string $status)
    {
    }

    public function title(): string
    {
        return 'Outstanding invoices';
    }

    public function array(): array
    {
        $what = $this->status ? OutstandingInvoices::STATUSES[$this->status] : 'Unpaid and part paid';
        $out = [
            ['Outstanding invoices — ' . $this->book->monthsLabel()],
            [$what . ' · ' . $this->scope . ' · balances as at ' . now()->format('d M Y')],
            [''],   // a blank row (an empty array would be dropped)
            ['Invoice', 'Invoice month', 'Client', 'Office', 'Due date', 'Days overdue', 'Status', 'Invoice total', 'Paid', 'Outstanding'],
        ];
        foreach ($this->rows as $r) {
            $out[] = [
                'FWSSI' . $r->id,
                $r->invoice_month?->format('M Y'),
                $r->client,
                $r->office,
                $r->due?->format('d/m/Y'),
                $r->days_overdue ?: null,
                OutstandingInvoices::STATUSES[$r->state],
                $r->total,
                $r->paid,
                $r->owed,
            ];
        }
        $out[] = ['Total', null, $this->rows->count() . ' invoices', null, null, null, null,
            round($this->rows->sum('total'), 2), round($this->rows->sum('paid'), 2), round($this->rows->sum('owed'), 2)];

        return $out;
    }

    public function columnFormats(): array
    {
        return ['H' => '#,##0.00', 'I' => '#,##0.00', 'J' => '#,##0.00', 'F' => '0'];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {
                $sheet = $e->sheet->getDelegate();
                $last = self::HEAD_ROW + $this->rows->count() + 1;
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
                $sheet->getStyle('A' . self::HEAD_ROW . ':J' . self::HEAD_ROW)->getFont()->setBold(true);
                $sheet->getStyle('A' . $last . ':J' . $last)->getFont()->setBold(true);
                $sheet->freezePane('A' . (self::HEAD_ROW + 1));
                $sheet->setAutoFilter('A' . self::HEAD_ROW . ':J' . max(self::HEAD_ROW, $last - 1));
                foreach (['A' => 12, 'B' => 13, 'C' => 36, 'D' => 16, 'E' => 12, 'F' => 13, 'G' => 11, 'H' => 15, 'I' => 15, 'J' => 15] as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }
            },
        ];
    }
}

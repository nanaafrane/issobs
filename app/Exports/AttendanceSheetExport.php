<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Support\Attendance\MonthSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * One client's month in the same shape as the old Excel time sheet:
 * NO. | EMPLOYEE NUMBER | LOCATION | NAME | TELEPHONE | SHIFT | PRESENT | ABSENT | days 1..31,
 * then the legend and the shortage line.
 */
class AttendanceSheetExport implements FromArray, WithStyles, WithTitle
{
    public function __construct(private MonthSheet $sheet)
    {
    }

    public function title(): string
    {
        return $this->sheet->month->format('M Y');
    }

    public function array(): array
    {
        $s = $this->sheet;
        $client = $s->client;
        $name = $client->business_name ?: $client->name;
        $rows = [];
        $rows[] = [strtoupper($s->month->format('F Y')) . ' TIME SHEET - ' . strtoupper((string) $name)];
        $rows[] = array_merge(array_fill(0, 9, ''), array_map(fn ($d) => $d['weekday'], array_values($s->days)));
        $rows[] = array_merge(['NO.', 'EMPLOYEE NUMBER', 'LOCATION', 'EMPLOYEE NAME', 'TELEPHONE', 'SHIFT', 'WORKED', 'PAID DAYS', 'ABSENT'],
            array_map(fn ($d) => $d['day'], array_values($s->days)));

        foreach ($s->rows as $i => $row) {
            $e = $row['employee'];
            $line = [
                sprintf('%02d', $i + 1), 'FWSS ' . $e->id, $e->location, $e->name . ($row['former'] ? ' (moved)' : ''),
                $e->phone_number, strtoupper((string) ($row['shift'] ?? '')), $row['worked'], $row['paid'], $row['absent'],
            ];
            foreach ($s->days as $n => $_) {
                $line[] = MonthSheet::code($row['cells'][$n] ?? []);
            }
            $rows[] = $line;
        }

        $t = $s->totals;
        $rows[] = [];
        $rows[] = ['', '', 'REQUIRED: ' . $t['required_day'] . ' DAY + ' . $t['required_night'] . ' NIGHT = ' . $t['required']
            . '   POSTED: ' . $t['posted'] . '   TOTAL SHORTAGES = ' . ($t['shortage'] ?: 'NIL')];
        $rows[] = ['', '', 'UNCOVERED GUARD-SHIFTS SO FAR: ' . $t['uncovered_guard_days']];
        $rows[] = [];
        $rows[] = ['', '', 'LEGEND: ' . collect(Attendance::CODES)->map(fn ($c, $k) => $c . ' = ' . Attendance::LABELS[$k])->implode(',  ')
            . '.  OFF, SICK and PASS count as paid days.'];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true, 'size' => 13]], 3 => ['font' => ['bold' => true]]];
    }
}

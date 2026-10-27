<?php

namespace App\Services\Fleet;

use App\Models\Field;
use App\Models\FuelEntry;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Loads a FUEL REQUEST AND USAGE REPORT sheet (DATE | DRIVER NAME | VEHICLE NO. | FIELD |
 * Trip Destination | QUANTITY | UNITS | FUEL TYPE | FUEL REQUESTED | FUEL ISSUED | FUEL STATION |
 * APPROVED BY | GRANTED BY | REMARK) into fuel entries, adding any vehicle not yet on the register.
 *
 * Re-runnable: a row already loaded (same vehicle, date, litres and amount) is skipped.
 * Rows come in as submitted (to be reviewed), or fully approved with $approved (a sheet that
 * was already signed off). Either way they are NOT paid: pay them through a fuel pay-out, or
 * leave them unpaid if the money already went out of Main Cash.
 */
class FuelSheetImport
{
    /** @return array{created:int, skipped:int, vehicles:array<string>, errors:array<string>} */
    public function import(string $path, ?int $fieldId = null, bool $approved = false, bool $dryRun = false, ?int $userId = null): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = $sheet->toArray(null, true, false, false);

        $header = null;
        foreach ($rows as $i => $r) {
            if (strtoupper(trim((string) ($r[0] ?? ''))) === 'DATE') {
                $header = $i;
                break;
            }
        }
        if ($header === null) {
            return ['created' => 0, 'skipped' => 0, 'vehicles' => [], 'errors' => ['No header row starting with DATE was found.']];
        }
        $cols = [];
        foreach ($rows[$header] as $c => $name) {
            $cols[strtoupper(trim((string) $name))] = $c;
        }
        $col = fn (array $r, string $name) => isset($cols[$name]) ? $r[$cols[$name]] : null;

        $out = ['created' => 0, 'skipped' => 0, 'vehicles' => [], 'errors' => []];
        $fields = Field::all();

        DB::beginTransaction();
        try {
            foreach (array_slice($rows, $header + 1, null, true) as $i => $r) {
                $line = $i + 1;
                $reg = Vehicle::normaliseReg((string) $col($r, 'VEHICLE NO.'));
                $amount = round((float) ($col($r, 'FUEL ISSUED') ?: $col($r, 'FUEL REQUESTED')), 2);
                if ($reg === '' || $amount <= 0) {
                    continue;   // blank lines and the TOTAL line
                }
                $date = $this->date($col($r, 'DATE'));
                if (! $date) {
                    $out['errors'][] = "Row {$line}: date not understood.";
                    continue;
                }
                $field = $fieldId ? $fields->firstWhere('id', $fieldId) : $this->field($fields, (string) $col($r, 'FIELD'));
                if (! $field) {
                    $out['errors'][] = "Row {$line}: office \"" . trim((string) $col($r, 'FIELD')) . '" not found (use --field).';
                    continue;
                }
                $type = str_contains(strtolower((string) $col($r, 'FUEL TYPE')), 'diesel') ? 'diesel' : (str_contains(strtolower((string) $col($r, 'FUEL TYPE')), 'super') || str_contains(strtolower((string) $col($r, 'FUEL TYPE')), 'petrol') ? 'super' : 'other');
                $vehicle = Vehicle::firstOrCreate(['reg_no' => $reg], [
                    'kind' => str_starts_with($reg, 'M-') ? 'motorbike' : 'car', 'fuel_type' => $type, 'field_id' => $field->id,
                    'driver_name' => trim((string) $col($r, 'DRIVER NAME')) ?: null, 'status' => 'active', 'user_id' => $userId,
                ]);
                if ($vehicle->wasRecentlyCreated) {
                    $out['vehicles'][] = $reg;
                }
                $price = round((float) $col($r, 'UNITS'), 3) ?: null;
                $litres = round((float) $col($r, 'QUANTITY'), 2) ?: ($price ? round($amount / $price, 2) : 0);
                if ($litres <= 0) {
                    $out['errors'][] = "Row {$line}: no litres or price per litre.";
                    continue;
                }

                $exists = FuelEntry::where('vehicle_id', $vehicle->id)->whereDate('entry_date', $date->toDateString())
                    ->where('amount', $amount)->where('litres', $litres)->exists();
                if ($exists) {
                    $out['skipped']++;
                    continue;
                }
                $note = trim(implode(' · ', array_filter([
                    ($a = trim((string) $col($r, 'APPROVED BY'))) ? 'Approved by ' . $a : null,
                    ($g = trim((string) $col($r, 'GRANTED BY'))) ? 'granted by ' . $g : null,
                    trim((string) $col($r, 'REMARK')) ?: null,
                    'imported from sheet',
                ])));
                $entry = new FuelEntry([
                    'entry_date' => $date->toDateString(), 'vehicle_id' => $vehicle->id, 'field_id' => $field->id,
                    'driver_name' => trim((string) $col($r, 'DRIVER NAME')) ?: null,
                    'purpose' => mb_substr(trim((string) $col($r, 'TRIP DESTINATION')), 0, 150) ?: null,
                    'fuel_type' => $type, 'litres' => $litres, 'price_per_litre' => $price ?? round($amount / $litres, 3),
                    'amount_requested' => round((float) $col($r, 'FUEL REQUESTED'), 2) ?: $amount, 'amount' => $amount,
                    'station' => mb_substr(strtoupper(trim((string) $col($r, 'FUEL STATION'))), 0, 100) ?: null,
                    'notes' => mb_substr($note, 0, 500),
                ]);
                $entry->user_1 = $userId;
                $entry->status_1 = 'approved';
                $entry->date_1 = now();
                if ($approved) {
                    foreach ([2, 3] as $n) {
                        $entry->{"user_{$n}"} = $userId;
                        $entry->{"status_{$n}"} = 'approved';
                        $entry->{"date_{$n}"} = now();
                    }
                }
                $entry->save();
                $out['created']++;
            }
            $dryRun ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $out;
    }

    private function date(mixed $v): ?Carbon
    {
        try {
            if (is_numeric($v)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $v))->startOfDay();
            }
            $s = trim((string) $v);
            if ($s === '') {
                return null;
            }
            foreach (['d/m/Y', 'd.m.y', 'd.m.Y', 'd-m-Y', 'Y-m-d'] as $f) {
                $d = \DateTime::createFromFormat('!' . $f, $s);
                if ($d) {
                    return Carbon::instance($d);
                }
            }

            return Carbon::parse($s)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /** "ASHALEY BOTWE" finds Botwe; "Accra" finds ACCRA. */
    private function field($fields, string $name): ?Field
    {
        $n = strtolower(trim($name));
        if ($n === '') {
            return null;
        }
        $exact = $fields->first(fn ($f) => strtolower(trim($f->name)) === $n);
        if ($exact) {
            return $exact;
        }
        $matches = $fields->filter(fn ($f) => str_contains($n, strtolower(trim($f->name))) || str_contains(strtolower(trim($f->name)), $n));
        if ($matches->count() === 1) {
            return $matches->first();
        }
        $words = array_filter(preg_split('/[^a-z]+/', $n), fn ($w) => strlen($w) > 3);
        $byWord = $fields->filter(fn ($f) => collect($words)->contains(fn ($w) => str_contains(strtolower($f->name), $w)));

        return $byWord->count() === 1 ? $byWord->first() : null;
    }
}

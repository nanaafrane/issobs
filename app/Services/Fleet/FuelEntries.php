<?php

namespace App\Services\Fleet;

use App\Models\FuelEntry;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Saving a fill-up. Works out litres or price per litre from the other two, defaults the office,
 * fuel type and driver from the vehicle, and refuses a km reading that goes backwards (it would
 * spoil the km per litre figures).
 */
class FuelEntries
{
    public function create(array $data): FuelEntry
    {
        $entry = new FuelEntry();
        $entry->fill($this->prepare($data));
        $entry->user_1 = Auth::id();
        $entry->status_1 = 'approved';   // the creator's own confirmation, as for overtime
        $entry->date_1 = now();
        $entry->save();

        return $entry;
    }

    public function update(FuelEntry $entry, array $data): FuelEntry
    {
        if ($entry->isLockedForPay()) {
            throw ValidationException::withMessages(['amount' => 'This fuel is on pay-out #' . $entry->payout_id . ': cancel the pay-out first.']);
        }
        $entry->fill($this->prepare($data, $entry->id));
        $entry->save();

        return $entry;
    }

    private function prepare(array $data, ?int $exceptId = null): array
    {
        $vehicle = Vehicle::findOrFail((int) $data['vehicle_id']);
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $litres = isset($data['litres']) && $data['litres'] !== '' ? round((float) $data['litres'], 2) : null;
        $price = isset($data['price_per_litre']) && $data['price_per_litre'] !== '' ? round((float) $data['price_per_litre'], 3) : null;

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Enter the fuel issued (GH₵).']);
        }
        if (! $litres && ! $price) {
            throw ValidationException::withMessages(['litres' => 'Enter the litres or the price per litre.']);
        }
        $litres ??= round($amount / $price, 2);
        $price ??= round($amount / $litres, 3);
        if (abs($litres * $price - $amount) > max(1.0, $amount * 0.02)) {
            throw ValidationException::withMessages(['litres' => sprintf('%.2f litres at GH₵ %.3f is GH₵ %.2f, not GH₵ %.2f. Check the figures.', $litres, $price, $litres * $price, $amount)]);
        }

        $odometer = isset($data['odometer']) && $data['odometer'] !== '' ? (int) $data['odometer'] : null;
        if ($odometer !== null) {
            $this->checkOdometer($vehicle, $data['entry_date'], $odometer, $exceptId);
        }

        return [
            'entry_date' => $data['entry_date'],
            'vehicle_id' => $vehicle->id,
            'field_id' => $data['field_id'] ?? $vehicle->field_id,
            'driver_employee_id' => $data['driver_employee_id'] ?? ($data['driver_name'] ?? null ? null : $vehicle->driver_employee_id),
            'driver_name' => $this->clean($data['driver_name'] ?? null, 120) ?? ($data['driver_employee_id'] ?? null ? null : $vehicle->driver_name),
            'purpose' => $this->clean($data['purpose'] ?? null, 150),
            'fuel_type' => $data['fuel_type'] ?? $vehicle->fuel_type,
            'litres' => $litres,
            'price_per_litre' => $price,
            'amount_requested' => isset($data['amount_requested']) && $data['amount_requested'] !== '' ? round((float) $data['amount_requested'], 2) : $amount,
            'amount' => $amount,
            'station' => $this->clean(isset($data['station']) ? strtoupper((string) $data['station']) : null, 100),
            'odometer' => $odometer,
            'receipt_no' => $this->clean($data['receipt_no'] ?? null, 60),
            'notes' => $this->clean($data['notes'] ?? null, 500),
        ];
    }

    /** A reading must not be below one taken on an earlier day, nor above one taken on a later day. */
    private function checkOdometer(Vehicle $vehicle, string $date, int $km, ?int $exceptId): void
    {
        $base = FuelEntry::where('vehicle_id', $vehicle->id)->whereNotNull('odometer')
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId));
        $before = (clone $base)->whereDate('entry_date', '<', $date)->max('odometer');
        $after = (clone $base)->whereDate('entry_date', '>', $date)->min('odometer');
        if ($before !== null && $km < (int) $before) {
            throw ValidationException::withMessages(['odometer' => $vehicle->reg_no . ' already read ' . number_format((int) $before) . ' km before this date.']);
        }
        if ($after !== null && $km > (int) $after) {
            throw ValidationException::withMessages(['odometer' => $vehicle->reg_no . ' read only ' . number_format((int) $after) . ' km after this date.']);
        }
        if ($vehicle->opening_odometer && $km < (int) $vehicle->opening_odometer) {
            throw ValidationException::withMessages(['odometer' => $vehicle->reg_no . ' started at ' . number_format((int) $vehicle->opening_odometer) . ' km.']);
        }
    }

    private function clean(?string $v, int $max): ?string
    {
        $v = trim((string) $v);

        return $v === '' ? null : mb_substr($v, 0, $max);
    }
}

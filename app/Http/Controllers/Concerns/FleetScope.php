<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Controllers\BankController;
use App\Models\Field;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Who sees which fleet records: head-office finance (Invoice, Finance Manager, Director) and
 * Operations managers see every office; anyone else sees their own office and its sub-offices
 * (Tema sees Tema and Shai Hills), the rule overtime pay-outs use.
 */
trait FleetScope
{
    /** @return array<int>|null null = every office */
    protected function allowedFieldIds(): ?array
    {
        $user = Auth::user();
        if ($this->seesAllOffices()) {
            return null;
        }

        return Field::managedIds($user?->field_id ? (int) $user->field_id : null);
    }

    protected function seesAllOffices(): bool
    {
        $user = Auth::user();

        return ($user?->hasRole(BankController::VIEW_ROLES) ?? false)
            || ($user?->department?->name === 'Operations' && ($user?->hasRole(['Manager']) ?? false));
    }

    /** Head-office finance raises pay-outs and manages maintenance types. */
    protected function isFinance(): bool
    {
        return Auth::user()?->hasRole(BankController::VIEW_ROLES) ?? false;
    }

    protected function mustSeeField(?int $fieldId): void
    {
        $allowed = $this->allowedFieldIds();
        abort_if($allowed !== null && ! in_array((int) $fieldId, $allowed, true), 403, 'This belongs to another office.');
    }

    /**
     * Active Drivers and Riders of the offices this user sees, for the driver dropdowns.
     * $keep: the driver already on the record, kept in the list even if their role has changed.
     */
    protected function driverOptions(?int $keep = null)
    {
        $allowed = $this->allowedFieldIds();

        return \App\Models\employee::with('role:id,name')
            ->where(fn ($q) => $q->where('status', 'Active')->drivers()
                ->when($allowed !== null, fn ($w) => $w->whereIn('field_id', $allowed ?: [0])))
            ->when($keep, fn ($q) => $q->orWhere('id', $keep))
            ->orderBy('name')->limit(2000)->get(['id', 'name', 'role_id']);
    }

    /** Validation rule: the driver must be a Driver or Rider (or the one already on the record). */
    protected function driverRule(?int $current = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($current) {
            if ($value && (int) $value !== (int) $current && ! \App\Models\employee::drivers()->whereKey((int) $value)->exists()) {
                $fail('Choose a driver or rider: only employees whose role is Driver or Rider can be assigned to a vehicle.');
            }
        };
    }

    protected function visibleFields()
    {
        $allowed = $this->allowedFieldIds();

        return Field::when($allowed !== null, fn ($q) => $q->whereIn('id', $allowed ?: [0]))->orderBy('name')->get();
    }

    /** @return array{0: Carbon, 1: Carbon} a month (?month=2026-10) or a from/to range, default this month */
    protected function period($request): array
    {
        try {
            if ($request->filled('from') || $request->filled('to')) {
                $from = Carbon::parse($request->input('from', now()->startOfMonth()))->startOfDay();
                $to = Carbon::parse($request->input('to', now()))->startOfDay();
            } else {
                $from = Carbon::parse(($request->input('month') ?: now()->format('Y-m')) . '-01')->startOfMonth();
                $to = $from->copy()->endOfMonth()->startOfDay();
            }
        } catch (\Throwable) {
            [$from, $to] = [now()->startOfMonth(), now()->endOfMonth()->startOfDay()];
        }
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }
}

<?php

namespace App\Support\Attendance;

use App\Models\Client;
use App\Models\employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Who is posted where, on which shift, and which clients a user may manage.
 *
 * A guard is posted to the client in employees.client_id: the link every other
 * ISSOBS screen uses, and the one "Re-assign guard" changes. (client_employee rows
 * are not removed on re-assignment, so they cannot be trusted as postings.)
 * The guard's shift at a client is kept on client_employee.shift; the row is
 * created the first time a shift is set, and only read while the guard is
 * still posted to that client.
 */
class Roster
{
    public const ACTIVE_STATUSES = ['Active', 'Re-Instate'];
    public const SHIFTS = ['day' => 'Day', 'night' => 'Night', 'rotate' => 'Rotate'];

    /** Roles that see every client (same as the client list). */
    public const HEAD_OFFICE_ROLES = ['Invoice', 'Finance Manager', 'Director'];

    /**
     * Active guards posted to a client, each with ->attendance_shift (day|night|rotate|null).
     *
     * @return Collection<int, employee>
     */
    public static function guards(Client $client): Collection
    {
        $guards = employee::query()
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where('client_id', $client->id)
            ->orderBy('name')
            ->get(['id', 'name', 'phone_number', 'location', 'client_id', 'status', 'attendance_pin', 'attendance_device']);

        $shifts = self::shiftMap([$client->id], $guards->pluck('id')->all());
        $guards->each(fn ($e) => $e->attendance_shift = $shifts[$client->id][$e->id] ?? null);

        return $guards;
    }

    public static function isPosted(employee $employee, Client $client): bool
    {
        return (int) $employee->client_id === (int) $client->id;
    }

    public static function shiftOf(employee $employee, Client $client): ?string
    {
        return DB::table('client_employee')->where('client_id', $client->id)->where('employee_id', $employee->id)
            ->whereNotNull('shift')->orderByDesc('id')->value('shift');
    }

    /** [client_id => [employee_id => shift]] from client_employee (latest row wins). */
    private static function shiftMap(array $clientIds, ?array $employeeIds = null): array
    {
        $q = DB::table('client_employee')->whereIn('client_id', $clientIds ?: [0])->whereNotNull('shift');
        if ($employeeIds !== null) {
            $q->whereIn('employee_id', $employeeIds ?: [0]);
        }
        $map = [];
        foreach ($q->orderBy('id')->get(['client_id', 'employee_id', 'shift']) as $r) {
            $map[(int) $r->client_id][(int) $r->employee_id] = $r->shift;
        }

        return $map;
    }

    public static function setShift(Client $client, employee $employee, ?string $shift): void
    {
        $shift = array_key_exists((string) $shift, self::SHIFTS) ? $shift : null;
        $row = DB::table('client_employee')->where('client_id', $client->id)->where('employee_id', $employee->id);

        if ($row->exists()) {
            $row->update(['shift' => $shift, 'updated_at' => now()]);
        } else {
            DB::table('client_employee')->insert([
                'client_id' => $client->id, 'employee_id' => $employee->id, 'location' => $employee->location,
                'shift' => $shift, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    /**
     * Guards on each shift for many clients: [client_id => ['day' => n, 'night' => n, 'rotate' => n, 'unset' => n]].
     */
    public static function shiftCounts(array $clientIds): array
    {
        if (! $clientIds) {
            return [];
        }

        $shifts = self::shiftMap($clientIds);
        $out = [];
        $guards = employee::whereIn('status', self::ACTIVE_STATUSES)->whereIn('client_id', $clientIds)->get(['id', 'client_id']);
        foreach ($guards as $e) {
            $c = (int) $e->client_id;
            $shift = $shifts[$c][(int) $e->id] ?? null;
            $out[$c] ??= ['day' => 0, 'night' => 0, 'rotate' => 0, 'unset' => 0];
            $out[$c][isset(self::SHIFTS[$shift]) ? $shift : 'unset']++;
        }

        return $out;
    }

    /**
     * Guards the plan needs that are not posted ("TOTAL SHORTAGES" on the old sheet), worked out per
     * shift: 3 day guards do not cover a missing night guard. Rotating guards, and guards with no
     * shift set yet, fill whichever shift is short.
     *
     * @param  array{day:int, night:int, rotate:int, unset:int}  $posted
     */
    public static function shortage(int $dayRequired, int $nightRequired, array $posted): int
    {
        $short = max(0, $dayRequired - ($posted['day'] ?? 0)) + max(0, $nightRequired - ($posted['night'] ?? 0));

        return max(0, $short - ($posted['rotate'] ?? 0) - ($posted['unset'] ?? 0));
    }

    /** Active, approved clients this user may take attendance for. */
    public static function clientsFor(User $user): Builder
    {
        $q = Client::query()
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->where('ho_status', 'approved');

        if (self::seesAllClients($user)) {
            return $q;
        }
        if (! $user->field_id) {
            return $q->whereRaw('1 = 0');
        }

        // Tema staff also look after field 7 (same rule as client registration).
        $fields = (int) $user->field_id === 3 ? [3, 7] : [(int) $user->field_id];

        return $q->whereIn('field_id', $fields);
    }

    public static function seesAllClients(User $user): bool
    {
        return $user->hasRole(self::HEAD_OFFICE_ROLES)
            || ( $user->hasPermission('Operations'));
    }

    public static function canManage(User $user, Client $client): bool
    {
        return self::clientsFor($user)->whereKey($client->id)->exists();
    }
}

<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copies history into employee_movements, once. Safe to run again: it only adds what is
 * missing (an employee with a hire already gets no second one; an nrrit row is copied once).
 *
 *  1. A hire for every employee: date of joining (else the day the record was created,
 *     marked "date estimated"). Approved, except recruits still Pending.
 *  2. nrrit terminations and re-instatements (month only, so "date estimated"). The
 *     wrong "New Recruit" rows nrrit wrote when a re-instatement was approved are not
 *     copied: hires come from step 1. A request still waiting for head office is pending.
 *  3. A termination for anyone Terminated who still has none (status date, else unknown).
 */
class EmployeeMovementsBackfill
{
    /** @return array{hires:int, nrrit:int, nrrit_skipped:int, terminations:int} */
    public static function run(): array
    {
        $out = ['hires' => 0, 'nrrit' => 0, 'nrrit_skipped' => 0, 'terminations' => 0];
        $now = now();
        $validDate = fn ($d) => $d && ! str_starts_with((string) $d, '0000') && Carbon::hasFormat(substr((string) $d, 0, 10), 'Y-m-d')
            && substr((string) $d, 0, 4) > '1900' ? substr((string) $d, 0, 10) : null;

        // 1. Hires
        DB::table('employees')->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('employee_movements')
            ->whereColumn('employee_movements.employee_id', 'employees.id')->where('employee_movements.type', 'hire'))
            ->orderBy('id')
            ->select(['id', 'status', 'ho_status', 'date_of_joining', 'created_at', 'field_id', 'client_id', 'role_id', 'department_id', 'user_id', 'user_id2'])
            ->chunk(500, function ($rows) use (&$out, $now, $validDate) {
                $insert = [];
                foreach ($rows as $e) {
                    $joined = $validDate($e->date_of_joining);
                    $created = $validDate($e->created_at);
                    $pending = $e->status === 'Pending' || $e->status === null || $e->status === '';
                    $insert[] = [
                        'employee_id' => $e->id, 'type' => 'hire', 'status' => $pending ? 'pending' : 'approved',
                        'effective_date' => $joined ?? $created, 'date_estimated' => $joined ? 0 : 1,
                        'field_id' => $e->field_id ?: null, 'client_id' => $e->client_id ?: null, 'role_id' => $e->role_id ?: null,
                        'department_id' => $e->department_id ?: null, 'source' => 'backfill',
                        'requested_by' => $e->user_id, 'requested_at' => $created, 'decided_by' => $pending ? null : $e->user_id2,
                        'decided_at' => null, 'created_at' => $now, 'updated_at' => $now,
                    ];
                }
                if ($insert) {
                    DB::table('employee_movements')->insert($insert);
                    $out['hires'] += count($insert);
                }
            });

        // 2. nrrit terminations / re-instatements
        if (Schema::hasTable('nrrit')) {
            $latest = DB::table('nrrit')->whereNotNull('employee_id')->where(fn ($q) => $q->where('status', 'Terminated')->orWhere('status1', 'Re-Instate'))
                ->selectRaw('employee_id, MAX(id) as id')->groupBy('employee_id')->pluck('id', 'employee_id');
            $copied = DB::table('employee_movements')->whereNotNull('legacy_nrrit_id')->pluck('legacy_nrrit_id')->flip();

            DB::table('nrrit')->orderBy('id')->chunk(500, function ($rows) use (&$out, $now, $latest, $copied, $validDate) {
                $employees = DB::table('employees')->whereIn('id', $rows->pluck('employee_id')->filter()->all() ?: [0])
                    ->get(['id', 'status', 'ho_status', 'field_id', 'client_id', 'role_id', 'department_id'])->keyBy('id');
                foreach ($rows as $n) {
                    if (isset($copied[$n->id])) {
                        continue;
                    }
                    $type = $n->status === 'Terminated' ? 'termination' : ($n->status1 === 'Re-Instate' ? 'reinstatement' : null);
                    $e = $employees[$n->employee_id] ?? null;
                    if (! $type || ! $e) {               // "New Recruit" rows, or employee no longer exists
                        $out['nrrit_skipped']++;
                        continue;
                    }
                    $date = $validDate($n->status_month);
                    // The same request written twice (double click / repeated terminate): copy once.
                    $dupe = DB::table('employee_movements')->where('employee_id', $e->id)->where('type', $type)
                        ->where('effective_date', $date)->exists();
                    if ($dupe) {
                        $out['nrrit_skipped']++;
                        continue;
                    }
                    // Still waiting for head office: the employee's latest request, HO pending, status unchanged since.
                    $waiting = (int) ($latest[$e->id] ?? 0) === (int) $n->id && $e->ho_status === 'pending'
                        && $e->status === ($type === 'termination' ? 'Terminated' : 'Re-Instate');
                    DB::table('employee_movements')->insert([
                        'employee_id' => $e->id, 'type' => $type, 'status' => $waiting ? 'pending' : 'approved',
                        'effective_date' => $date, 'date_estimated' => 1,
                        'previous_status' => $waiting ? ($type === 'termination' ? 'Active' : 'Terminated') : null,
                        'field_id' => $e->field_id ?: null, 'client_id' => $e->client_id ?: null, 'role_id' => $e->role_id ?: null,
                        'department_id' => $e->department_id ?: null, 'source' => 'nrrit', 'legacy_nrrit_id' => $n->id,
                        'requested_at' => $validDate($n->created_at), 'created_at' => $now, 'updated_at' => $now,
                    ]);
                    $out['nrrit']++;
                }
            });
        }

        // 3. Terminated employees with no termination at all
        DB::table('employees')->where('status', 'Terminated')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('employee_movements')
                ->whereColumn('employee_movements.employee_id', 'employees.id')->where('employee_movements.type', 'termination'))
            ->orderBy('id')
            ->select(['id', 'ho_status', 'status_date', 'field_id', 'client_id', 'role_id', 'department_id', 'user_id1'])
            ->chunk(500, function ($rows) use (&$out, $now, $validDate) {
                $insert = [];
                foreach ($rows as $e) {
                    $pending = $e->ho_status === 'pending';
                    $insert[] = [
                        'employee_id' => $e->id, 'type' => 'termination', 'status' => $pending ? 'pending' : 'approved',
                        'effective_date' => $validDate($e->status_date), 'date_estimated' => 1,
                        'previous_status' => $pending ? 'Active' : null,
                        'field_id' => $e->field_id ?: null, 'client_id' => $e->client_id ?: null, 'role_id' => $e->role_id ?: null,
                        'department_id' => $e->department_id ?: null, 'source' => 'backfill', 'requested_by' => $e->user_id1,
                        'created_at' => $now, 'updated_at' => $now,
                    ];
                }
                if ($insert) {
                    DB::table('employee_movements')->insert($insert);
                    $out['terminations'] += count($insert);
                }
            });

        // 4. A guessed hire date can't be after the employee's first termination / re-instatement
        //    (records keyed in late): move it back to that date.
        $first = DB::table('employee_movements')->whereIn('type', ['termination', 'reinstatement'])->whereNotNull('effective_date')
            ->selectRaw('employee_id, MIN(effective_date) as d')->groupBy('employee_id')->pluck('d', 'employee_id');
        DB::table('employee_movements')->where('type', 'hire')->where('date_estimated', 1)->whereIn('employee_id', $first->keys()->all() ?: [0])
            ->get(['id', 'employee_id', 'effective_date'])
            ->each(function ($h) use ($first) {
                $d = $first[$h->employee_id];
                if ($h->effective_date === null || $h->effective_date > $d) {
                    DB::table('employee_movements')->where('id', $h->id)->update(['effective_date' => $d]);
                }
            });

        return $out;
    }
}

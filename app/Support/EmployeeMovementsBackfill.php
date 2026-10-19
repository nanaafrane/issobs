<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copies history into employee_movements, once. Safe to run again: it only adds what is
 * missing (an employee with a hire already gets no second one; an nrrit row is copied once).
 *
 *  1. A hire for every employee: date of joining. With none, the hire has NO date: the
 *     employee counts as employed from before any period (the day the record was keyed
 *     in is not when they joined - often a bulk upload), and HR is asked for the date.
 *     Approved, except recruits still Pending.
 *  2. nrrit terminations and re-instatements (month only, so "date estimated"). The
 *     wrong "New Recruit" rows nrrit wrote when a re-instatement was approved are not
 *     copied: hires come from step 1. A request still waiting for head office is pending.
 *  3. A termination for anyone Terminated who still has none (status date, else unknown).
 *  5. A re-instatement for anyone Active again whose history ends in a termination (taken
 *     back on through an edit / approval before re-instatement had its own step - nrrit
 *     logged those as "New Recruit"). Without it a later termination could not count.
 *     Dated by that "New Recruit" row, else the head-office approval date, else the
 *     termination date; always marked estimated.
 */
class EmployeeMovementsBackfill
{
    /** @return array{hires:int, nrrit:int, nrrit_skipped:int, terminations:int, approved_waiting:int, reinstatements:int} */
    public static function run(): array
    {
        $out = ['hires' => 0, 'nrrit' => 0, 'nrrit_skipped' => 0, 'terminations' => 0, 'approved_waiting' => 0, 'reinstatements' => 0];
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
                        'effective_date' => $joined, 'date_estimated' => $joined ? 0 : 1,
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
        // An unknown (null) hire date stays unknown: giving it the termination's date would
        // make "worked for" 0 days.
        DB::table('employee_movements')->where('type', 'hire')->where('date_estimated', 1)->whereNotNull('effective_date')
            ->whereIn('employee_id', $first->keys()->all() ?: [0])
            ->get(['id', 'employee_id', 'effective_date'])
            ->each(function ($h) use ($first) {
                $d = $first[$h->employee_id];
                if ($h->effective_date > $d) {
                    DB::table('employee_movements')->where('id', $h->id)->update(['effective_date' => $d]);
                }
            });

        $out['approved_waiting'] = self::approveWhatHeadOfficeApproved();
        $out['reinstatements'] = self::backOnWithoutReinstatement($validDate);
        $out['reinstatements'] += self::fillGapsBeforeTerminations();

        return $out;
    }

    /**
     * Step 4b. Requests still "waiting" for employees head office has already approved
     * (approved through the edit form before approval followed the employee): approve them,
     * dated when head office approved the employee. Returns how many.
     */
    private static function approveWhatHeadOfficeApproved(): int
    {
        $n = 0;
        $now = now();
        $rows = DB::table('employee_movements as m')->join('employees as e', 'e.id', '=', 'm.employee_id')
            ->where('m.status', 'pending')->where('e.ho_status', 'approved')
            ->where(fn ($q) => $q->where(fn ($w) => $w->where('m.type', 'termination')->where('e.status', 'Terminated'))
                ->orWhere(fn ($w) => $w->whereIn('m.type', ['reinstatement', 'hire'])->where('e.status', 'Active')))
            ->get(['m.id', 'e.ho_date', 'e.user_id2']);
        foreach ($rows as $r) {
            DB::table('employee_movements')->where('id', $r->id)->update(['status' => 'approved', 'decided_by' => $r->user_id2,
                'decided_at' => $r->ho_date && ! str_starts_with((string) $r->ho_date, '0000') ? $r->ho_date : $now, 'updated_at' => $now]);
            $n++;
        }

        return $n;
    }

    /**
     * Step 6. A termination that cannot count because the history does not show the employee
     * employed (an older termination with no re-instatement after it, or no hire approved):
     * fill the gap (EmployeeMovements::ensureEmployedBefore). Returns how many were added.
     */
    private static function fillGapsBeforeTerminations(): int
    {
        $n = 0;
        $ids = DB::table('employee_movements')->where('type', 'termination')->where('status', 'approved')->whereNotNull('effective_date')
            ->distinct()->pluck('employee_id');
        foreach ($ids as $id) {
            $e = \App\Models\employee::find($id);
            if (! $e) {
                continue;
            }
            $why = \App\Support\EmployeeMovementsReport::explain($e->id);
            $terms = \App\Models\EmployeeMovement::whereIn('id', array_keys(array_filter($why, fn ($w) => $w && str_starts_with($w, 'Not counted: not employed'))))
                ->where('type', 'termination')->orderBy('effective_date')->get();
            foreach ($terms as $t) {
                if (\App\Support\EmployeeMovements::ensureEmployedBefore($e, $t->effective_date->toDateString())) {
                    $n++;
                }
            }
        }

        return $n;
    }

    /** Step 5. Returns how many re-instatements were added. */
    private static function backOnWithoutReinstatement(callable $validDate): int
    {
        $added = 0;
        $now = now();
        $pending = DB::table('employee_movements')->where('status', 'pending')->where('type', 'reinstatement')->pluck('employee_id')->flip();
        DB::table('employees')->where('status', 'Active')->orderBy('id')
            ->select(['id', 'ho_date', 'field_id', 'client_id', 'role_id', 'department_id', 'user_id2'])
            ->chunk(500, function ($rows) use (&$added, $now, $pending, $validDate) {
                $last = DB::table('employee_movements')->whereIn('employee_id', $rows->pluck('id')->all())->where('status', 'approved')
                    ->whereIn('type', ['hire', 'reinstatement', 'termination'])->whereNotNull('effective_date')
                    ->orderBy('effective_date')->orderBy('id')->get(['employee_id', 'type', 'effective_date'])
                    ->groupBy('employee_id')->map(fn ($ms) => $ms->last());
                foreach ($rows as $e) {
                    $m = $last[$e->id] ?? null;
                    if (! $m || $m->type !== 'termination' || isset($pending[$e->id])) {
                        continue;
                    }
                    $ended = substr((string) $m->effective_date, 0, 10);
                    $rehired = Schema::hasTable('nrrit') ? DB::table('nrrit')->where('employee_id', $e->id)->where('status1', 'New Recruit')
                        ->where('status_month', '>', $ended)->orderBy('status_month')->value('status_month') : null;
                    $approvedOn = $validDate($e->ho_date);
                    $date = $validDate($rehired) ?? ($approvedOn && $approvedOn > $ended ? $approvedOn : $ended);
                    DB::table('employee_movements')->insert([
                        'employee_id' => $e->id, 'type' => 'reinstatement', 'status' => 'approved', 'effective_date' => $date, 'date_estimated' => 1,
                        'note' => 'Active again with no re-instatement on record (added from history).',
                        'field_id' => $e->field_id ?: null, 'client_id' => $e->client_id ?: null, 'role_id' => $e->role_id ?: null,
                        'department_id' => $e->department_id ?: null, 'source' => 'backfill', 'decided_by' => $e->user_id2,
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                    $added++;
                }
            });

        return $added;
    }
}

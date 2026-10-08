<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Everything the Pay priority rules page needs that is not the rule logic itself
 * (that stays in PayPriority): previews of a change before it is saved, how many
 * people each rule matches, locations no rule matches, and the change history.
 *
 * "Employees" here = active employees (Active / Re-Instate), the same set the
 * pay-priority:audit command looks at.
 */
class PayPriorityAdmin
{
    public const ACTIVE_STATUSES = ['Active', 'Re-Instate'];

    /** Active employees of these clients (null = every client that has or may get a rule). */
    public static function employees(?array $clientIds = null)
    {
        return DB::table('employees')
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->whereIn('client_id', $clientIds ?? PayPriority::scopeClientIds() ?: [0])
            ->get(['id', 'name', 'client_id', 'field_id', 'location', 'gender', 'pay_priority']);
    }

    /** Stored rule rows (all, including inactive), as arrays. */
    public static function ruleRows(): array
    {
        return DB::table('pay_priority_rules')->orderBy('id')->get()
            ->map(fn ($r) => (array) $r)->all();
    }

    /**
     * What a change would do, without saving it.
     *
     * @param  array|null  $ruleRows  the full rule set after the change (null = unchanged)
     * @param  array|null  $aliases   the full alias list after the change (null = unchanged)
     * @param  int[]|null  $clientIds only these clients can be affected (null = every rule client)
     */
    public static function preview(?array $ruleRows, ?array $aliases = null, ?array $clientIds = null): array
    {
        $employees = self::employees($clientIds);

        $current = [];
        foreach ($employees as $e) {
            [$current[$e->id]] = PayPriority::evaluate($e->client_id, $e->field_id, $e->location, $e->gender);
        }

        $proposed = PayPriority::simulate($ruleRows, $aliases, function () use ($employees) {
            $out = [];
            foreach ($employees as $e) {
                [$out[$e->id]] = PayPriority::evaluate($e->client_id, $e->field_id, $e->location, $e->gender);
            }

            return $out;
        });

        $moves = [];
        $samples = [];
        $up = $down = 0;
        foreach ($employees as $e) {
            $from = $current[$e->id];
            $to = $proposed[$e->id];
            if ($from === $to) {
                continue;
            }
            $to > $from ? $up++ : $down++;
            $key = PayPriority::LABELS[$from] . ' → ' . PayPriority::LABELS[$to];
            $moves[$key] = ($moves[$key] ?? 0) + 1;
            if (count($samples) < 15) {
                $samples[] = ['id' => $e->id, 'name' => $e->name, 'location' => $e->location, 'from' => PayPriority::LABELS[$from], 'to' => PayPriority::LABELS[$to]];
            }
        }
        arsort($moves);

        // Unpaid salaries of the people who move (paid ones never change).
        $movedIds = array_keys(array_filter($proposed, fn ($lvl, $id) => $lvl !== $current[$id], ARRAY_FILTER_USE_BOTH));
        $salaries = $movedIds
            ? DB::table('salaries')->whereIn('employee_id', $movedIds)
                ->where(fn ($q) => $q->whereNull('payment_status')->orWhereNotIn('payment_status', PayPriority::FROZEN_STATUSES))->count()
            : 0;

        return [
            'changed' => $up + $down,
            'up' => $up,
            'down' => $down,
            'moves' => $moves,
            'samples' => $samples,
            'unpaid_salaries' => $salaries,
            'checked' => $employees->count(),
            'summary' => self::summarise($up + $down, $moves),
        ];
    }

    public static function summarise(int $changed, array $moves): string
    {
        if ($changed === 0) {
            return 'No active employee changes priority.';
        }
        $parts = [];
        foreach ($moves as $label => $n) {
            $parts[] = $n . ' ' . $label;
        }

        return $changed . ' active ' . ($changed === 1 ? 'employee changes' : 'employees change') . ' priority: ' . implode(', ', $parts) . '.';
    }

    /** Rule set with one row added / replaced / removed (for previews). */
    public static function rowsWith(?array $row, ?int $replaceId = null, bool $remove = false): array
    {
        $rows = self::ruleRows();
        if ($replaceId !== null) {
            $rows = array_values(array_filter($rows, fn ($r) => (int) $r['id'] !== $replaceId));
        }
        if ($row !== null && ! $remove) {
            $rows[] = $row + ['id' => $replaceId ?? 0, 'active' => true, 'reason' => null];
        }

        return $rows;
    }

    /**
     * Active employees each rule matches on its own (before "highest level wins"),
     * and how many end up at that rule's level because of it or another rule.
     *
     * @return array<int, int> rule id => employees matched
     */
    public static function matchCounts(): array
    {
        $employees = self::employees()->groupBy('client_id');
        $counts = [];

        foreach (self::ruleRows() as $row) {
            $built = PayPriority::buildRules([$row + ['active' => true]]);
            $rule = $built[(int) $row['client_id']][0] ?? null;
            $n = 0;
            if ($rule) {
                foreach ($employees->get($row['client_id'], collect()) as $e) {
                    if (PayPriority::matches($rule, $e->field_id, PayPriority::normalise($e->location), (string) $e->gender) !== false) {
                        $n++;
                    }
                }
            }
            $counts[(int) $row['id']] = $n;
        }

        return $counts;
    }

    /**
     * Locations of clients with location rules that no rule matches: where a missing
     * spelling variant hides (same idea as `php artisan pay-priority:audit --unmatched`).
     */
    public static function unmatchedLocations(int $limit = 40): array
    {
        $clientIds = PayPriority::ruleClientIds();
        $rows = DB::table('employees')
            ->whereIn('client_id', $clientIds ?: [0])
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->selectRaw('client_id, location, COUNT(*) as people')
            ->groupBy('client_id', 'location')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            // A client's gender / field rules can flag some of its people; this list is about locations.
            $rules = array_filter(PayPriority::rules()[(int) $r->client_id] ?? [], fn ($rule) => $rule['locations']);
            if (! $rules) {
                continue;
            }
            $norm = PayPriority::normalise($r->location);
            $hit = false;
            foreach ($rules as $rule) {
                foreach ($rule['locations'] as $key) {
                    if (preg_match('/(?<![A-Z0-9])' . preg_quote($key, '/') . '(?![A-Z0-9])/', $norm)) {
                        $hit = true;
                        break 2;
                    }
                }
            }
            if (! $hit) {
                $out[] = ['client_id' => (int) $r->client_id, 'location' => (string) $r->location, 'normalised' => $norm, 'people' => (int) $r->people];
            }
        }
        usort($out, fn ($a, $b) => $b['people'] <=> $a['people'] ?: strcmp($a['location'], $b['location']));

        return array_slice($out, 0, $limit);
    }

    /** Record a change in the history. */
    public static function log(string $action, string $summary, ?int $ruleId = null, ?int $clientId = null, $before = null, $after = null, ?int $employeesChanged = null): void
    {
        DB::table('pay_priority_changes')->insert([
            'user_id' => Auth::id(),
            'action' => $action,
            'rule_id' => $ruleId,
            'client_id' => $clientId,
            'summary' => mb_strimwidth($summary, 0, 255, '…'),
            'before' => $before === null ? null : json_encode($before),
            'after' => $after === null ? null : json_encode($after),
            'employees_changed' => $employeesChanged,
            'created_at' => now(),
        ]);
    }

    /** Human description of a rule row: "Pay early · female · Takoradi office · CAPE COAST, ADA". */
    public static function describe(array $row, array $fieldNames = []): string
    {
        $locations = is_array($row['locations'] ?? null) ? $row['locations'] : (json_decode((string) ($row['locations'] ?? ''), true) ?: []);
        $parts = [PayPriority::LABELS[(int) $row['level']] ?? 'Level ' . $row['level']];
        if (! empty($row['gender'])) {
            $parts[] = strtolower($row['gender']) . ' staff';
        }
        if (! empty($row['field_id'])) {
            $parts[] = ($fieldNames[$row['field_id']] ?? 'field ' . $row['field_id']) . ' office';
        }
        $parts[] = $locations ? implode(', ', $locations) : 'any location';

        return implode(' · ', $parts);
    }
}

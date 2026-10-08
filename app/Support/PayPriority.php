<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Payment priority - who payroll must pay first.
 *
 * The ONE place that knows the rules. Everything else (employee list, payroll
 * screens, exports, salary creation) reads the stored result
 * (pay_priority / pay_priority_reason) or calls this class.
 *
 * Rules live in pay_priority_rules. A rule matches when ALL of its conditions
 * match: client_id, and optionally gender, field_id and a list of locations.
 * The highest matching level wins (Urgent beats Priority).
 *
 * Locations are free text on employees / salaries ("Cape Coast Branch",
 * "C/Coast", "Bolgatanga"), so matching is done on a normalised copy and on
 * WHOLE WORDS: "HO" matches "HO" or "HO BRANCH" but never "AHODWO" or "HOHOE",
 * "ADA" never matches "ADABRAKA".
 *
 * Client scope comes from category::DEFAULT_A_CLIENT_IDS (the default Category A
 * clients). That constant is the single list of priority clients: a rule only
 * applies while its client is on it, and a rule cannot be saved for a client
 * that is not. Remove a client from the constant and its pay rules stop.
 */
class PayPriority
{
    public const NORMAL = 0;
    public const PRIORITY = 1;
    public const URGENT = 2;

    public const LABELS = [
        self::URGENT => 'Pay first',
        self::PRIORITY => 'Pay early',
        self::NORMAL => 'Normal',
    ];

    /** Request/filter keywords => level. */
    public const FILTERS = ['urgent' => self::URGENT, 'priority' => self::PRIORITY];

    /** Payment statuses whose priority is frozen (already paid: keep the record of why). */
    public const FROZEN_STATUSES = ['approved'];

    /**
     * Spelling variants seen in free-text locations => the spelling used in rules.
     * Applied to normalised text as whole words, before matching, in this order.
     *
     * Since the Pay priority rules page these live in the pay_priority_aliases table
     * (pre-filled from this list) and are edited there; this list is only the fallback
     * used before that migration has run.
     */
    public const ALIASES = [
        'BOLGATANGA' => 'BOLGA',
        'C COAST' => 'CAPE COAST',
        'CAPECOAST' => 'CAPE COAST',
        'SWEDRU' => 'AGONA SWEDRU',
        'AGONA AGONA SWEDRU' => 'AGONA SWEDRU', // undo double-expansion of "AGONA SWEDRU"
        'WIAWSO' => 'SEFWI WIAWSO',
        'SEFWI SEFWI WIAWSO' => 'SEFWI WIAWSO',
        'TADI' => 'TAKORADI',
        'NKWAKWA' => 'NKAWKAW',
        'EBOTIMA' => 'EBO TIMA',
        'DORMAA AHENKRO' => 'DORMAA',
    ];

    /** @var array<int, array<int, array>>|null rules grouped by client_id, cached per request */
    private static ?array $rules = null;

    /** @var array<string, string>|null spelling variants, cached per request */
    private static ?array $aliases = null;

    /** The single source of priority clients: the default Category A list. */
    public static function scopeClientIds(): array
    {
        return array_map('intval', \App\Models\category::DEFAULT_A_CLIENT_IDS);
    }

    public static function flush(): void
    {
        self::$rules = null;
        self::$aliases = null;
    }

    /** Spelling variants from the pay_priority_aliases table (fallback: ALIASES). */
    public static function aliases(): array
    {
        if (self::$aliases !== null) {
            return self::$aliases;
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('pay_priority_aliases')) {
                return self::$aliases = DB::table('pay_priority_aliases')->orderBy('position')->orderBy('id')
                    ->pluck('to', 'from')->all();
            }
        } catch (\Throwable) {
            // no database yet (e.g. during install): use the built-in list
        }

        return self::$aliases = self::ALIASES;
    }

    /** Uppercase, punctuation to spaces, collapse spaces. No aliases (used to store rule input). */
    public static function clean(?string $text): string
    {
        $text = strtoupper((string) $text);
        $text = preg_replace('/[^A-Z0-9]+/', ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Run $callback as if the rules were $ruleRows and the spelling variants $aliases
     * (either may be null = keep the stored ones). Nothing is saved; the stored rules
     * are restored afterwards. Used by the rules page to preview a change.
     */
    public static function simulate(?array $ruleRows, ?array $aliases, callable $callback)
    {
        $saved = [self::$rules, self::$aliases];
        try {
            if ($aliases !== null) {
                self::$aliases = $aliases;
            }
            self::$rules = $ruleRows !== null ? self::buildRules($ruleRows) : null;
            if ($ruleRows === null) {
                self::rules(); // stored rules, normalised with the (possibly simulated) aliases
            }

            return $callback();
        } finally {
            [self::$rules, self::$aliases] = $saved;
        }
    }

    /** Uppercase, punctuation to spaces, collapse spaces, then apply aliases. */
    public static function normalise(?string $location): string
    {
        $text = strtoupper((string) $location);
        $text = preg_replace('/[^A-Z0-9]+/', ' ', $text);
        $text = trim(preg_replace('/\s+/', ' ', $text));

        foreach (self::aliases() as $from => $to) {
            $text = preg_replace('/(?<![A-Z0-9])' . preg_quote($from, '/') . '(?![A-Z0-9])/', $to, $text);
        }

        return $text;
    }

    /** @return array<int, array<int, array>> */
    public static function rules(): array
    {
        if (self::$rules !== null) {
            return self::$rules;
        }

        $rows = DB::table('pay_priority_rules')->where('active', true)->get();

        return self::$rules = self::buildRules($rows->all());
    }

    /**
     * Group rule rows (objects or arrays with client_id, level, gender, field_id, locations,
     * reason, active) by client. Inactive rows and clients off the default Category A list
     * are left out (see class docblock).
     *
     * @return array<int, array<int, array>>
     */
    public static function buildRules(iterable $rows): array
    {
        $scope = self::scopeClientIds();
        $rows = collect($rows)->map(fn ($r) => (object) $r)
            ->filter(fn ($r) => ($r->active ?? true) && in_array((int) $r->client_id, $scope, true))
            ->values();

        $clientIds = $rows->pluck('client_id')->unique()->all();
        $clients = DB::table('clients')->whereIn('id', $clientIds)->get(['id', 'name', 'business_name'])->keyBy('id');
        $fields = DB::table('fields')->pluck('name', 'id');

        $grouped = [];
        foreach ($rows as $row) {
            $raw = is_array($row->locations ?? null) ? $row->locations : (json_decode((string) ($row->locations ?? ''), true) ?: []);
            $locations = array_values(array_filter(array_map([self::class, 'normalise'], (array) $raw)));

            $client = $clients[$row->client_id] ?? null;
            $clientName = $client ? trim(($client->business_name ?: $client->name) ?? '') : '';
            $clientName = $clientName !== '' ? $clientName : 'Client ' . $row->client_id;

            $grouped[(int) $row->client_id][] = [
                'level' => (int) $row->level,
                'gender' => $row->gender ? strtolower(trim($row->gender)) : null,
                'field_id' => $row->field_id !== null ? (int) $row->field_id : null,
                'locations' => $locations,
                'reason' => $row->reason ?? null,
                'client_name' => $clientName,
                'field_name' => $row->field_id !== null ? ($fields[$row->field_id] ?? 'field ' . $row->field_id) : null,
            ];
        }

        return $grouped;
    }

    /** Client ids that have at least one active rule. */
    public static function ruleClientIds(): array
    {
        return array_keys(self::rules());
    }

    /**
     * Evaluate one person / salary snapshot.
     *
     * @return array{0:int,1:?string} [level, reason]
     */
    public static function evaluate($clientId, $fieldId, ?string $location, ?string $gender): array
    {
        $rules = self::rules()[(int) $clientId] ?? [];
        if (! $rules || ! $clientId) {
            return [self::NORMAL, null];
        }

        $normalised = self::normalise($location);
        $gender = strtolower(trim((string) $gender));

        $best = [self::NORMAL, null];
        foreach ($rules as $rule) {
            $matchedLocation = self::matches($rule, $fieldId, $normalised, $gender);
            if ($matchedLocation === false) {
                continue;
            }
            $matchedLocation = $matchedLocation === true ? null : $matchedLocation;

            if ($rule['level'] > $best[0]) {
                $best = [$rule['level'], $rule['reason'] ?: self::describe($rule, $matchedLocation)];
            }
        }

        return $best;
    }

    /**
     * Does one built rule match? false = no; true = yes (no location condition);
     * string = yes, on that location keyword. $normalised must come from normalise().
     */
    public static function matches(array $rule, $fieldId, string $normalised, string $gender): bool|string
    {
        if ($rule['gender'] !== null && $rule['gender'] !== strtolower(trim($gender))) {
            return false;
        }
        if ($rule['field_id'] !== null && $rule['field_id'] !== (int) $fieldId) {
            return false;
        }
        if (! $rule['locations']) {
            return true;
        }
        foreach ($rule['locations'] as $key) {
            if (preg_match('/(?<![A-Z0-9])' . preg_quote($key, '/') . '(?![A-Z0-9])/', $normalised)) {
                return $key;
            }
        }

        return false;
    }

    /** Neutral, readable reason. Never mentions gender: the label says what to DO. */
    private static function describe(array $rule, ?string $location): string
    {
        $what = $location ? ucwords(strtolower($location))
            : ($rule['field_name'] ? $rule['field_name'] . ' office' : 'client requirement');

        // pay_priority_reason is a varchar: keep well inside it.
        return mb_strimwidth(self::LABELS[$rule['level']] . ' - ' . $rule['client_name'] . ' (' . $what . ')', 0, 190, '...');
    }

    /** Badge HTML shared by Blade (<x-pay-priority>) and JSON datatable endpoints. */
    public static function badge($level, ?string $reason = null): string
    {
        $level = (int) $level;
        if ($level === self::URGENT) {
            $class = 'bg-danger';
            $icon = 'bx bxs-bolt';
        } elseif ($level === self::PRIORITY) {
            $class = 'bg-warning text-dark';
            $icon = 'bx bx-time-five';
        } else {
            return '';
        }

        $title = e($reason ?: self::LABELS[$level]);

        return '<span class="badge ' . $class . ' pay-flag me-1" title="' . $title . '" data-bs-toggle="tooltip">'
            . '<i class="' . $icon . '" aria-hidden="true"></i> ' . e(self::LABELS[$level]) . '</span>';
    }

    /* ------------------------------------------------------------------
     | Bulk recompute. Only rows that can change are touched: rows of a
     | client with rules, or rows currently flagged (in case a rule was
     | removed). Updates are grouped by result, so it is a handful of
     | UPDATE ... WHERE id IN (...) statements, not one per row.
     * ------------------------------------------------------------------ */

    /** @return int rows changed */
    public static function recomputeEmployees(?array $employeeIds = null): int
    {
        self::flush();
        $clientIds = self::ruleClientIds();

        $query = DB::table('employees')
            ->select(['id', 'client_id', 'field_id', 'location', 'gender', 'pay_priority', 'pay_priority_reason'])
            ->where(fn ($q) => $q->whereIn('client_id', $clientIds ?: [0])->orWhere('pay_priority', '>', 0));
        if ($employeeIds !== null) {
            $query->whereIn('id', $employeeIds ?: [0]);
        }

        return self::applyChunked($query, 'employees', 'id');
    }

    /** Unpaid salaries only; approved (paid) salaries keep the priority they were paid under. */
    public static function recomputeSalaries(?array $employeeIds = null): int
    {
        self::flush();
        $clientIds = self::ruleClientIds();

        $query = DB::table('salaries')
            ->leftJoin('employees', 'employees.id', '=', 'salaries.employee_id')
            ->select([
                'salaries.id', 'salaries.client_id', 'salaries.field_id', 'salaries.location', 'employees.gender',
                'salaries.pay_priority', 'salaries.pay_priority_reason',
            ])
            ->where(fn ($q) => $q->whereNull('salaries.payment_status')->orWhereNotIn('salaries.payment_status', self::FROZEN_STATUSES))
            ->where(fn ($q) => $q->whereIn('salaries.client_id', $clientIds ?: [0])->orWhere('salaries.pay_priority', '>', 0));
        if ($employeeIds !== null) {
            $query->whereIn('salaries.employee_id', $employeeIds ?: [0]);
        }

        return self::applyChunked($query, 'salaries', 'salaries.id');
    }

    private static function applyChunked($query, string $table, string $idColumn): int
    {
        $changed = 0;

        $query->orderBy($idColumn)->chunkById(1000, function ($rows) use ($table, &$changed) {
            $groups = [];
            foreach ($rows as $row) {
                [$level, $reason] = self::evaluate($row->client_id, $row->field_id, $row->location, $row->gender);
                if ((int) $row->pay_priority === $level && $row->pay_priority_reason === $reason) {
                    continue;
                }
                $groups[$level . '|' . $reason]['values'] = ['pay_priority' => $level, 'pay_priority_reason' => $reason];
                $groups[$level . '|' . $reason]['ids'][] = $row->id;
            }

            foreach ($groups as $group) {
                foreach (array_chunk($group['ids'], 1000) as $ids) {
                    $changed += DB::table($table)->whereIn('id', $ids)->update($group['values']);
                }
            }
        }, $idColumn, 'id');

        return $changed;
    }
}

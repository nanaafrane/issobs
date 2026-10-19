<?php

namespace App\Support\Dashboards;

use App\Models\Field;
use App\Models\User;
use App\Support\Attendance\Roster;
use App\Support\EmployeeMovementsReport;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * What a dashboard is looking at: which month, which field offices, and for whom.
 *
 * Every figure on the Accounts, HR and Operations dashboards is built from one of
 * these, so the month filter and the office scope are applied the same way
 * everywhere (and the same way the existing reports apply them):
 *
 *  - month:  ?month=YYYY-MM, default the current month. A month in the future falls
 *            back to the current one.
 *  - office: ?field=<id> narrows to that office and its sub-offices (Tema includes
 *            Shai Hills), only within the offices the user may see; anything else
 *            is refused with 403 rather than silently widened.
 *  - scope:  head-office users see every office (fieldIds = null); a field-office
 *            user sees their own office and its sub-offices.
 */
class DashboardContext
{
    public const ACCOUNTS = 'accounts';
    public const HR = 'hr';
    public const OPERATIONS = 'operations';

    public const LABELS = [
        self::ACCOUNTS => 'Accounts',
        self::HR => 'HR',
        self::OPERATIONS => 'Operations',
    ];

    /** The role every guard has (the id the rest of the system already uses for guards). */
    public const GUARD_ROLE_ID = 7;

    /** Roles that see the company-wide figures on every dashboard they can open. */
    public const HEAD_OFFICE_ROLES = ['Invoice', 'Finance Manager', 'Director', 'Internal Auditor'];

    public function __construct(
        public readonly User $user,
        public readonly string $board,
        public readonly Carbon $month,        // first day of the month
        public readonly ?array $fieldIds,     // null = every office
        public readonly ?array $allowedIds,   // null = every office (before the ?field filter)
        public readonly ?int $fieldId,        // the ?field filter, if any
    ) {
    }

    public static function fromRequest(Request $request, User $user, string $board): self
    {
        $month = self::parseMonth($request->input('month'));
        $allowed = self::allowedFieldIds($user, $board);

        $fieldId = $request->integer('field') ?: null;
        $fieldIds = $allowed;
        if ($fieldId) {
            $wanted = Field::managedIds($fieldId);
            if ($allowed !== null && ! in_array($fieldId, $allowed, true)) {
                abort(403, 'You cannot view that field office.');
            }
            $fieldIds = $allowed === null ? $wanted : array_values(array_intersect($wanted, $allowed));
        }

        return new self($user, $board, $month, $fieldIds, $allowed, $fieldId);
    }

    public static function parseMonth(?string $value): Carbon
    {
        $now = Carbon::now()->startOfMonth();
        if (! $value || ! preg_match('/^\d{4}-\d{2}$/', $value)) {
            return $now;
        }
        try {
            $month = Carbon::createFromFormat('!Y-m', $value)->startOfMonth();
        } catch (\Throwable) {
            return $now;
        }

        return $month->gt($now) ? $now : $month;
    }

    /**
     * Offices the user may see on a dashboard: null = all.
     *
     * Accounts: the roles that already see every office's money (old dashboard + receipts report).
     * HR: anyone in HR (the old HR dashboard was company-wide) or the HR report's head-office roles.
     * Operations: whoever may take attendance for every client.
     */
    public static function allowedFieldIds(User $user, string $board): ?array
    {
        $all = match ($board) {
            self::ACCOUNTS => $user->hasRole(self::HEAD_OFFICE_ROLES),
            self::HR => $user->hasPermission('HR') || EmployeeMovementsReport::scopeFor($user)[0],
            self::OPERATIONS => Roster::seesAllClients($user) || $user->hasRole(['Internal Auditor']),
            default => false,
        };

        return $all ? null : Field::managedIds($user->field_id ? (int) $user->field_id : null);
    }

    /** Dashboards this user may open (the tabs at the top of each one). */
    public static function boardsFor(User $user): array
    {
        $dept = $user->department?->name;
        $director = $user->hasRole(['Director', 'Internal Auditor'])
            || ($user->hasRole(['Manager']) && $dept === 'Administration');

        $boards = [];
        if ($director || in_array($dept, ['Accounts', 'Administration'], true) || $user->hasRole(['Finance Manager', 'Invoice'])) {
            $boards[] = self::ACCOUNTS;
        }
        if ($director || $dept === 'HR') {
            $boards[] = self::HR;
        }
        if ($director || $dept === 'Operations') {
            $boards[] = self::OPERATIONS;
        }

        return $boards;
    }

    /** The dashboard /home opens for this user's department (null = keep the old behaviour). */
    public static function homeBoardFor(User $user): ?string
    {
        return match ($user->department?->name) {
            'Accounts', 'Administration' => self::ACCOUNTS,
            'HR' => self::HR,
            'Operations' => self::OPERATIONS,
            default => null,
        };
    }

    /** First and last day of the selected month, as Y-m-d strings. */
    public function span(): array
    {
        return [$this->month->toDateString(), $this->month->copy()->endOfMonth()->toDateString()];
    }

    /** Last day counted: the end of the month, or today while the month is still running. */
    public function asOf(): Carbon
    {
        $end = $this->month->copy()->endOfMonth()->startOfDay();

        return $end->lt(Carbon::today()) ? $end : Carbon::today();
    }

    public function isCurrentMonth(): bool
    {
        return $this->month->isSameMonth(Carbon::now());
    }

    /** The last $n months ending with the selected one, oldest first: [Carbon first day, 'Y-m', 'M y']. */
    public function months(int $n = 12): array
    {
        $out = [];
        for ($i = $n - 1; $i >= 0; $i--) {
            $m = $this->month->copy()->subMonthsNoOverflow($i);
            $out[] = [$m, $m->format('Y-m'), $m->format('M y')];
        }

        return $out;
    }

    /** Offices offered in the office filter: the ones this user may see, top-level first. */
    public function officeOptions()
    {
        return Field::query()
            ->when($this->allowedIds !== null, fn ($q) => $q->whereIn('id', $this->allowedIds ?: [0]))
            ->orderByRaw('parent_field_id IS NOT NULL')->orderBy('name')
            ->get(['id', 'name', 'parent_field_id']);
    }

    /** Offices shown as rows in the per-office tables. */
    public function offices()
    {
        return Field::query()
            ->when($this->fieldIds !== null, fn ($q) => $q->whereIn('id', $this->fieldIds ?: [0]))
            ->orderBy('name')->get(['id', 'name', 'parent_field_id']);
    }

    public function scopeLabel(): string
    {
        if ($this->fieldId) {
            return Field::whereKey($this->fieldId)->value('name') ?? 'Office';
        }

        return $this->fieldIds === null ? 'All offices' : 'Your offices';
    }

    /** Query string for links back to the same view (other dashboards, drill-downs). */
    public function query(array $extra = []): array
    {
        return array_filter(['month' => $this->month->format('Y-m'), 'field' => $this->fieldId] + $extra, fn ($v) => $v !== null);
    }
}

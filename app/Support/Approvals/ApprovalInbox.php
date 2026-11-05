<?php

namespace App\Support\Approvals;

use App\Models\Client;
use App\Models\employee;
use App\Models\Expense;
use App\Models\Field;
use App\Models\FuelEntry;
use App\Models\Overtime;
use App\Models\Receipt;
use App\Models\User;
use App\Models\VehicleMaintenance;
use App\Support\EmployeeMovements;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * "Waiting for you": everything one user can approve right now, counted with the same
 * rules the approve buttons use, so the bell, the dashboard strip and /approvals always
 * agree with the screens where the decision is taken.
 *
 *  Expenses, overtime, fuel, maintenance (stages 1-3, see HasApprovalStages)
 *    Branch review   Field Manager (expenses) / Field Manager or Manager (the others),
 *                    for offices in the same office group (canActOnStage()).
 *    Branch review   Finance Manager / Director, ONLY for offices that have nobody who
 *                    could give branch review (e.g. head office); elsewhere it is the
 *                    branch's turn, shown on /approvals as "at branch review".
 *    Finance / H.O   Finance Manager / Director, every office.
 *  Receipts          branch: Administration manager of the client's office (bran_status pending)
 *                    head office: Finance Manager / Director (ho_status pending).
 *  Clients           branch: Administration manager (bran_status pending)
 *                    head office: Invoice (ho_status pending).
 *  Employees         new recruits, terminations, re-instatements waiting on the pending page.
 *                    branch: Administration manager; head office: EmployeeMovements::canDecide().
 *
 * Nothing here is limited to a period: an item waits until someone decides it.
 */
class ApprovalInbox
{
    public const FINANCE_ROLES = ['Finance Manager', 'Director'];

    /** Ages (days) from which the oldest item is shown as late / very late. */
    public const LATE_DAYS = 3;

    public const VERY_LATE_DAYS = 7;

    private const CACHE_SECONDS = 60;

    private const VERSION_KEY = 'approvals:version';

    /** Items shown per section on /approvals. */
    public const PAGE_LIMIT = 50;

    private ?array $groupFieldIds = null;

    private ?array $managedFieldIds = null;

    /** @var array<string, int[]> */
    private static array $coveredFieldIds = [];

    public function __construct(private User $user) {}

    public static function for(?User $user): ?self
    {
        return $user ? new self($user) : null;
    }

    /** Called when any approvable record changes, so no one sees a stale badge. */
    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, (string) microtime(true));
        self::$coveredFieldIds = [];
    }

    // ------------------------------------------------------------------
    // Summary (bell, dashboard strip)
    // ------------------------------------------------------------------

    /**
     * ['can' => bool, 'total' => int, 'items' => [...], 'watch' => [...]]
     * Each item: key, type, label, stage, count, oldest (Y-m-d H:i:s|null), url.
     * 'items' is what waits for this user; 'watch' is what they could also act on
     * but is someone else's turn first (not in the total).
     */
    public function summary(bool $fresh = false): array
    {
        $key = 'approvals:' . $this->user->id . ':' . Cache::get(self::VERSION_KEY, '0');
        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, self::CACHE_SECONDS, function () {
            $count = fn (array $s) => $this->countSource($s);
            $items = array_map($count, $this->sources());
            $watch = array_map($count, $this->watchSources());

            usort($items, fn ($a, $b) => [$b['count'] > 0, $a['oldest'] ?? '9999'] <=> [$a['count'] > 0, $b['oldest'] ?? '9999']);

            return [
                'can' => $items !== [],
                'total' => array_sum(array_column($items, 'count')),
                'items' => $items,
                'watch' => array_values(array_filter($watch, fn ($w) => $w['count'] > 0)),
            ];
        });
    }

    public function total(): int
    {
        return $this->summary()['total'];
    }

    /** Waiting items of one source, oldest first, as display rows for /approvals. */
    public function rows(string $key, int $limit = self::PAGE_LIMIT): Collection
    {
        $source = collect($this->sources())->merge($this->watchSources())->firstWhere('key', $key);
        if (! $source) {
            return collect();
        }

        return ($source['query'])()
            ->with($this->relationsFor($source['type']))
            ->orderByRaw($source['since'] . ' asc')
            ->orderBy($this->table($source['type']) . '.id')
            ->limit($limit)
            ->get()
            ->map(fn ($m) => $this->row($source, $m));
    }

    public static function ageDays(?string $since): ?int
    {
        return $since ? (int) floor(Carbon::parse($since)->diffInDays(now(), true)) : null;
    }

    /** good | warn | bad for an age in days. */
    public static function ageTone(?int $days): string
    {
        return match (true) {
            $days === null => 'flat',
            $days >= self::VERY_LATE_DAYS => 'bad',
            $days >= self::LATE_DAYS => 'warn',
            default => 'flat',
        };
    }

    public static function ageLabel(?int $days): string
    {
        return match (true) {
            $days === null => '',
            $days === 0 => 'today',
            $days === 1 => '1 day',
            default => $days . ' days',
        };
    }

    // ------------------------------------------------------------------
    // What this user can act on
    // ------------------------------------------------------------------

    /** @return array<int, array{key:string,type:string,label:string,stage:string,query:\Closure,since:string,url:string}> */
    private function sources(): array
    {
        $out = [];

        foreach (self::stagedTypes() as $type => $t) {
            if ($this->hasRole($t['branch_roles']) && $this->groupFieldIds()) {
                $out[] = $this->source($type, 'branch', 'Branch review',
                    fn () => self::atStage2($t['model']::query())->whereIn($this->table($type) . '.field_id', $this->groupFieldIds()));
            }
            if ($this->isFinance()) {
                $out[] = $this->source($type, 'branch-ho', 'Branch review (no branch approver)',
                    fn () => self::atStage2($t['model']::query())->where(fn ($q) => $q
                        ->whereNull($this->table($type) . '.field_id')
                        ->orWhereNotIn($this->table($type) . '.field_id', self::coveredFieldIds($t['branch_roles']) ?: [0])));
                $out[] = $this->source($type, 'finance', 'Finance / H.O approval',
                    fn () => self::atStage3($t['model']::query()), "COALESCE({$this->table($type)}.date_2, {$this->table($type)}.created_at)");
            }
        }

        $branchAdmin = $this->isBranchAdmin() && $this->managedFieldIds();

        if ($branchAdmin) {
            $out[] = $this->source('receipt', 'branch', 'Branch approval', fn () => Receipt::query()
                ->where('bran_status', 'pending')
                ->whereIn('client_id', Client::select('id')->whereIn('field_id', $this->managedFieldIds())));
        }
        if ($this->isFinance()) {
            $out[] = $this->source('receipt', 'ho', 'Head-office approval', fn () => Receipt::query()
                ->where('ho_status', 'pending')
                ->where(fn ($q) => $q->whereNull('bran_status')->orWhere('bran_status', '!=', 'pending')));
        }

        if ($branchAdmin) {
            $out[] = $this->source('client', 'branch', 'Branch approval', fn () => self::pendingClients()
                ->where('bran_status', 'pending')
                ->whereIn('field_id', $this->managedFieldIds()));
        }
        if ($this->hasRole(['Invoice'])) {
            $out[] = $this->source('client', 'ho', 'Head-office approval', fn () => self::pendingClients()
                ->where('ho_status', 'pending')
                ->where(fn ($q) => $q->whereNull('bran_status')->orWhere('bran_status', '!=', 'pending')));
        }

        if ($branchAdmin) {
            $out[] = $this->source('employee', 'branch', 'Branch approval', fn () => self::pendingEmployees()
                ->where('bran_status', 'pending')
                ->whereIn('field_id', $this->managedFieldIds()));
        }
        if (EmployeeMovements::canDecide($this->user)) {
            $out[] = $this->source('employee', 'ho', 'Head-office approval', fn () => self::pendingEmployees()
                ->where(fn ($q) => $q->whereNull('bran_status')->orWhere('bran_status', '!=', 'pending')));
        }

        return $out;
    }

    /** Finance can also decide branch review, but it is the branch's turn first. */
    private function watchSources(): array
    {
        if (! $this->isFinance()) {
            return [];
        }
        $out = [];
        foreach (self::stagedTypes() as $type => $t) {
            $covered = self::coveredFieldIds($t['branch_roles']);
            if ($covered) {
                $out[] = $this->source($type, 'at-branch', 'At branch review',
                    fn () => self::atStage2($t['model']::query())->whereIn($this->table($type) . '.field_id', $covered));
            }
        }

        return $out;
    }

    private function source(string $type, string $stage, string $stageLabel, \Closure $query, ?string $since = null): array
    {
        return [
            'key' => $type . '.' . $stage,
            'type' => $type,
            'label' => self::TYPE_LABELS[$type],
            'stage' => $stageLabel,
            'query' => $query,
            'since' => $since ?? $this->table($type) . '.created_at',
            'url' => route('approvals.index') . '#' . $type . '-' . $stage,
        ];
    }

    private function countSource(array $s): array
    {
        $row = ($s['query'])()->toBase()
            ->selectRaw('COUNT(*) as n, MIN(' . $s['since'] . ') as oldest')
            ->first();

        return [
            'key' => $s['key'],
            'type' => $s['type'],
            'label' => $s['label'],
            'stage' => $s['stage'],
            'count' => (int) ($row->n ?? 0),
            'oldest' => $row->oldest ? (string) $row->oldest : null,
            'url' => $s['url'],
        ];
    }

    // ------------------------------------------------------------------
    // Rules shared with the approval screens
    // ------------------------------------------------------------------

    public const TYPE_LABELS = [
        'expense' => 'Expenses',
        'overtime' => 'Overtime',
        'fuel' => 'Fuel',
        'maintenance' => 'Maintenance',
        'receipt' => 'Receipts',
        'client' => 'Clients',
        'employee' => 'Employees',
    ];

    /** The 3-stage types and who may give branch review (Expense / Overtime / HasApprovalStages::canActOnStage). */
    private static function stagedTypes(): array
    {
        return [
            'expense' => ['model' => Expense::class, 'branch_roles' => ['Field Manager']],
            'overtime' => ['model' => Overtime::class, 'branch_roles' => ['Field Manager', 'Manager']],
            'fuel' => ['model' => FuelEntry::class, 'branch_roles' => ['Field Manager', 'Manager']],
            'maintenance' => ['model' => VehicleMaintenance::class, 'branch_roles' => ['Field Manager', 'Manager']],
        ];
    }

    /** currentStage() === 2 */
    public static function atStage2(Builder $q): Builder
    {
        return $q->where('status_1', 'approved')->whereNull('status_2');
    }

    /** currentStage() === 3 */
    public static function atStage3(Builder $q): Builder
    {
        return $q->where('status_1', 'approved')
            ->whereNotNull('status_2')->where('status_2', '!=', 'rejected')
            ->whereNull('status_3');
    }

    /** As on clientPending: not active, head-office decision still open. */
    public static function pendingClients(): Builder
    {
        return Client::query()
            ->whereRaw("LOWER(COALESCE(clients.status, '')) <> 'active'")
            ->where(fn ($q) => $q->where('ho_status', 'pending')->orWhereNull('ho_status'));
    }

    /** As on employeesPending. */
    public static function pendingEmployees(): Builder
    {
        return employee::query()
            ->whereIn('status', ['Pending', 'Terminated', 'Re-Instate'])
            ->where(fn ($q) => $q->where('ho_status', 'pending')->orWhereNull('ho_status'));
    }

    /**
     * Offices where someone holding one of $roles can give branch review: every office
     * in the same office group as such a user (Field::groupId()).
     *
     * @return int[]
     */
    private static function coveredFieldIds(array $roles): array
    {
        $k = implode('|', $roles);
        if (! array_key_exists($k, self::$coveredFieldIds)) {
            $userFields = User::whereNotNull('field_id')
                ->whereHas('role', fn ($q) => $q->whereIn('name', $roles))
                ->distinct()->pluck('field_id');
            $groups = Field::whereIn('id', $userFields)->get()->map->groupId()->unique()->values()->all();
            self::$coveredFieldIds[$k] = $groups
                ? Field::whereIn('id', $groups)->orWhereIn('parent_field_id', $groups)->pluck('id')->map(fn ($i) => (int) $i)->all()
                : [];
        }

        return self::$coveredFieldIds[$k];
    }

    /** Offices with the same groupId() as the user's office (canActOnStage for stage 2). */
    private function groupFieldIds(): array
    {
        if ($this->groupFieldIds === null) {
            $field = $this->user->field;
            $g = $field?->groupId();
            $this->groupFieldIds = $g
                ? Field::where('id', $g)->orWhere('parent_field_id', $g)->pluck('id')->map(fn ($i) => (int) $i)->all()
                : [];
        }

        return $this->groupFieldIds;
    }

    /** The user's office and its sub-offices, as on the receipts / clients / employees pending pages. */
    private function managedFieldIds(): array
    {
        return $this->managedFieldIds ??= Field::managedIds($this->user->field_id ? (int) $this->user->field_id : null);
    }

    private function isFinance(): bool
    {
        return $this->hasRole(self::FINANCE_ROLES);
    }

    /** Branch manager: Manager in Administration (the branch Approve button on the pending pages). */
    private function isBranchAdmin(): bool
    {
        return $this->hasRole(['Manager']) && $this->user->hasPermission('Administration');
    }

    private function hasRole(array $roles): bool
    {
        return $this->user->hasRole($roles);
    }

    // ------------------------------------------------------------------
    // Rows for /approvals
    // ------------------------------------------------------------------

    private function table(string $type): string
    {
        return match ($type) {
            'expense' => 'expenses',
            'overtime' => 'overtimes',
            'fuel' => (new FuelEntry)->getTable(),
            'maintenance' => (new VehicleMaintenance)->getTable(),
            'receipt' => 'receipts',
            'client' => 'clients',
            'employee' => (new employee)->getTable(),
        };
    }

    private function relationsFor(string $type): array
    {
        return match ($type) {
            'expense' => ['type', 'field'],
            'overtime' => ['field', 'client', 'otEmployee', 'absentEmployee'],
            'fuel' => ['field', 'vehicle'],
            'maintenance' => ['field', 'vehicle', 'type'],
            'receipt' => ['client.field'],
            'client' => ['field'],
            'employee' => ['field'],
        };
    }

    /**
     * One display row: title, detail, office, amount, date, since, open (url),
     * approve / reject (POST urls, only where the existing route takes one record).
     */
    private function row(array $source, $m): array
    {
        $staged = isset(self::stagedTypes()[$source['type']]);
        $since = $source['stage'] === 'Finance / H.O approval' ? ($m->date_2 ?? $m->created_at) : $m->created_at;

        $r = match ($source['type']) {
            'expense' => [
                'title' => $m->description ?: ($m->type?->name ?? 'Expense'),
                'detail' => (string) ($m->type?->name ?? ''),
                'office' => $m->field?->name,
                'amount' => (float) $m->amount,
                'date' => $m->expense_date ?? $m->created_at,
                'open' => route('expense.list', array_filter([
                    'field_id' => $m->field_id,
                    'from_month' => Carbon::parse($m->expense_date ?? $m->created_at)->format('Y-m'),
                    'to_month' => Carbon::parse($m->expense_date ?? $m->created_at)->format('Y-m'),
                ])),
                'approve' => route('expense.approve', $m),
                'reject' => route('expense.reject', $m),
            ],
            'overtime' => [
                'title' => ($m->otEmployee?->name ?? 'Overtime') . ' covering ' . $m->absentLabel(),
                'detail' => $m->clientLabel() . ' · ' . ucfirst((string) $m->shift) . ($m->reason ? ' · ' . $m->reason : ''),
                'office' => $m->field?->name,
                'amount' => (float) $m->amount,
                'date' => $m->entry_date,
                'open' => route('overtime.open', $m->id),
                'approve' => route('overtime.approve', $m),
                'reject' => route('overtime.reject', $m),
            ],
            'fuel' => [
                'title' => ($m->vehicle?->reg_no ?? 'Vehicle') . ' · ' . rtrim(rtrim(number_format((float) $m->litres, 2), '0'), '.') . ' L',
                'detail' => trim(($m->station ?? '') . ($m->purpose ? ' · ' . $m->purpose : ''), ' ·'),
                'office' => $m->field?->name,
                'amount' => (float) $m->amount,
                'date' => $m->entry_date,
                'open' => route('fleet.fuel', array_filter([
                    'from' => optional($m->entry_date)->toDateString(), 'to' => optional($m->entry_date)->toDateString(),
                    'field_id' => $m->field_id, 'state' => 'pending',
                ])),
                'approve' => route('fleet.fuel.approve', $m->id),
                'reject' => route('fleet.fuel.reject', $m->id),
            ],
            'maintenance' => [
                'title' => ($m->type?->name ?? 'Maintenance') . ' · ' . ($m->vehicle?->reg_no ?? ''),
                'detail' => trim(($m->vendor ?? '') . ($m->description ? ' · ' . $m->description : ''), ' ·'),
                'office' => $m->field?->name,
                'amount' => (float) $m->amount,
                'date' => $m->service_date,
                'open' => route('fleet.maintenance', array_filter([
                    'from' => optional($m->service_date)->toDateString(), 'to' => optional($m->service_date)->toDateString(),
                    'field_id' => $m->field_id,
                ])),
                'approve' => route('fleet.maintenance.approve', $m->id),
                'reject' => route('fleet.maintenance.reject', $m->id),
            ],
            'receipt' => [
                'title' => 'Receipt #' . $m->id . ' · ' . ($m->client?->business_name ?: ($m->client?->name ?? 'Client')),
                'detail' => $m->mode ? ucfirst((string) $m->mode) : '',
                'office' => $m->client?->field?->name,
                'amount' => (float) $m->total,
                'date' => $m->receipt_month ?? $m->created_at,
                'open' => route('receipt.pending'),
            ],
            'client' => [
                'title' => $m->business_name ?: ($m->name ?? 'Client'),
                'detail' => match (strtolower((string) $m->status)) {
                    'terminated' => 'Termination', 're-instate' => 'Re-instatement', 'pending' => 'New client', default => ucfirst((string) $m->status),
                },
                'office' => $m->field?->name,
                'amount' => null,
                'date' => $m->created_at,
                'open' => route('client.pending'),
            ],
            'employee' => [
                'title' => $m->name ?? 'Employee',
                'detail' => match ($m->status) {
                    'Terminated' => 'Termination', 'Re-Instate' => 'Re-instatement', default => 'New recruit / change',
                },
                'office' => $m->field?->name,
                'amount' => null,
                'date' => $m->created_at,
                'open' => url('employeesPending'),
            ],
        };

        if (! $staged) {
            $r += ['approve' => null, 'reject' => null];
        }
        $r['id'] = $m->id;
        $r['since'] = $since ? (string) $since : null;

        return $r;
    }
}

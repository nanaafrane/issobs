<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeClientRequest;
use App\Models\employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Requests\StoreemployeeRequest;
use App\Http\Requests\UpdateemployeeRequest;
use App\Http\Requests\StorePaymentInfoRequest;
use App\Http\Requests\UpdatePaymentInfoRequest;
use App\Exports\FilteredQueryExport;
use App\Http\Controllers\Concerns\SearchesDates;
use App\Support\PayPriority;
use App\Models\Bank;
use App\Models\Client;
use App\Models\Department;
use App\Models\Field;
use App\Models\PaymentInfo;
use App\Models\Role;
use App\Models\Salary;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
      use SearchesDates;

    public function __construct()
    {
        $this->middleware('auth');
    }


        public function report(Request $request)
    {
        $period = $request->input('period', 'monthly');
        $anchor = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();

        [$from, $to] = match ($period) {
            'daily' => [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()],
            'weekly' => [$anchor->copy()->startOfWeek(), $anchor->copy()->endOfWeek()],
            'quarterly' => [$anchor->copy()->startOfQuarter(), $anchor->copy()->endOfQuarter()],
            'semiannual' => ($anchor->month <= 6)
                ? [$anchor->copy()->startOfYear(), $anchor->copy()->startOfYear()->addMonths(5)->endOfMonth()]
                : [$anchor->copy()->startOfYear()->addMonths(6), $anchor->copy()->endOfYear()],
            'yearly' => [$anchor->copy()->startOfYear(), $anchor->copy()->endOfYear()],
            default => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
        };

        // Hires, re-instatements and terminations come from employee_movements: approved and
        // dated, each counted once (nrrit counted re-instated people as new recruits too).
        $moves = fn (array $types, $a, $b) => DB::table('employee_movements')->where('status', 'approved')
            ->whereIn('type', $types)->whereBetween('effective_date', [Carbon::parse($a)->toDateString(), Carbon::parse($b)->toDateString()]);
        $base = employee::whereIn('employees.id', $moves(['hire'], $from, $to)->select('employee_id'));

        $total = employee::where('ho_status', 'approved')->where('status', 'Active')->orwhere('status', 'Terminated')->count();
        // new recruits + re-instated in the period
        $count = $moves(['hire', 'reinstatement'], $from, $to)->count();

        // Prefer salary-based active count for the selected period; fallback to employees active status
        $salaryActiveCount = Salary::whereBetween('salary_month', [$from, $to])->count();
        $active = $salaryActiveCount > 0 ? $salaryActiveCount : employee::where('status', 'Active')->count();
        $avgSalary = (float) employee::whereBetween('date_of_joining', [$from, $to])->where('ho_status', 'approved')->where('status', 'Active')->avg('basic_salary') ?: 0.0;
        // Overall headcount by status for KPIs
        $terminated = employee::where('status', 'Terminated')->where('ho_status', 'approved')->count();
        $pending = employee::where('status', 'Pending')->where('ho_status', 'approved')->count();

        $byField = (clone $base)->join('fields', 'fields.id', '=', 'employees.field_id')
            ->select('fields.name as field_name', DB::raw('COUNT(employees.id) as cnt'))
            ->groupBy('fields.name')->orderByDesc('cnt')->get();

        $byDepartment = (clone $base)->join('departments', 'departments.id', '=', 'employees.department_id')
            ->select('departments.name as dept_name', DB::raw('COUNT(employees.id) as cnt'))
            ->groupBy('departments.name')->orderByDesc('cnt')->limit(10)->get();

        // Status counts (from employees table)
        $byStatus = (clone $base)->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')->get();

        // Trend of hires: include reinstate events from nrrit as separate counts when present
        $trend = (clone $base)->select('date_of_joining', DB::raw('COUNT(id) as total'))
            ->groupBy('date_of_joining')->orderBy('date_of_joining')->get();

        // Roles summary (overall headcount per role) and top roles
        // Role summary limited to the selected period (hires in period by role)
        // Role summary: hires in period plus reinstatements mapped to current role
        // role at the time of the movement
        $roleRows = $moves(['hire', 'reinstatement'], $from, $to)
            ->join('roles', 'roles.id', '=', 'employee_movements.role_id')
            ->select('roles.name', DB::raw('COUNT(*) as cnt'))->groupBy('roles.name')->get();
        $roleMap = [];
        foreach ($roleRows as $r) { $roleMap[$r->name] = ($roleMap[$r->name] ?? 0) + $r->cnt; }
        $roleSummary = collect(array_map(function($k,$v){ return (object)['name'=>$k,'cnt'=>$v]; }, array_keys($roleMap), $roleMap))->sortByDesc('cnt')->values();
        $topRoles = $roleSummary->take(10);

        // Terminations in the selected period and by-month terminations
        $terminationsInPeriod = $moves(['termination'], $from, $to)->count();

        $terminationsTrend = $moves(['termination'], $from, $to)->get(['effective_date'])
            ->groupBy(fn ($r) => substr($r->effective_date, 0, 7) . '-01')
            ->map(fn ($g, $month) => (object) ['month' => $month, 'total' => $g->count()])->sortKeys()->values();

        // Field-level summaries: active, hires this period, terminations this period
        $fields = Field::orderBy('name')->get();

        $activePerField = employee::where('ho_status', 'approved')->where('status', 'Active')
            ->select('field_id', DB::raw('COUNT(*) as cnt'))->groupBy('field_id')->pluck('cnt', 'field_id');

        // office at the time of the movement
        $hiresPerField = $moves(['hire', 'reinstatement'], $from, $to)
            ->select('field_id', DB::raw('COUNT(*) as cnt'))->groupBy('field_id')->pluck('cnt', 'field_id');

        $termsPerField = $moves(['termination'], $from, $to)
            ->select('field_id', DB::raw('COUNT(*) as cnt'))->groupBy('field_id')->pluck('cnt', 'field_id');

        // Prepare arrays for charts
        $fieldLabels = $fields->pluck('name');
        $fieldActiveArr = $fields->pluck('id')->map(fn($id) => $activePerField[$id] ?? 0);
        $fieldHiresArr = $fields->pluck('id')->map(fn($id) => $hiresPerField[$id] ?? 0);
        $fieldTermsArr = $fields->pluck('id')->map(fn($id) => $termsPerField[$id] ?? 0);

        $roleLabels = $roleSummary->pluck('name');
        $roleCounts = $roleSummary->pluck('cnt');

        $topRoleLabels = $topRoles->pluck('name');
        $topRoleCounts = $topRoles->pluck('cnt');

        $deptLabels = $byDepartment->pluck('dept_name');
        $deptCounts = $byDepartment->pluck('cnt');

        $terminationMonths = $terminationsTrend->pluck('month');
        $terminationCounts = $terminationsTrend->pluck('total');

        // Build period-aligned buckets for overlay chart (hires vs terminations)
        $overlayLabels = [];
        $overlayHires = [];
        $overlayTerms = [];
        $cursor = $from->copy();
        while ($cursor <= $to) {
            if ($period === 'daily') {
                $start = $cursor->copy()->startOfDay();
                $end = $cursor->copy()->endOfDay();
                $next = $cursor->copy()->addDay();
            } elseif ($period === 'weekly') {
                $start = $cursor->copy()->startOfWeek();
                $end = $cursor->copy()->endOfWeek();
                $next = $cursor->copy()->addWeek();
            } elseif ($period === 'quarterly') {
                $start = $cursor->copy()->startOfQuarter();
                $end = $cursor->copy()->endOfQuarter();
                $next = $cursor->copy()->addQuarter();
            } elseif ($period === 'semiannual') {
                $start = $cursor->month <= 6
                    ? $cursor->copy()->startOfYear()
                    : $cursor->copy()->startOfYear()->addMonths(6);
                $end = $cursor->month <= 6
                    ? $cursor->copy()->startOfYear()->addMonths(5)->endOfMonth()
                    : $cursor->copy()->endOfYear();
                $next = $cursor->copy()->addMonths(6);
            } elseif ($period === 'yearly') {
                $start = $cursor->copy()->startOfYear();
                $end = $cursor->copy()->endOfYear();
                $next = $cursor->copy()->addYear();
            } else {
                // monthly
                $start = $cursor->copy()->startOfMonth();
                $end = $cursor->copy()->endOfMonth();
                $next = $cursor->copy()->addMonth();
            }

            $label = $start->format('Y-m-d');
            $overlayLabels[] = $label;
            $overlayHires[] = $moves(['hire', 'reinstatement'], $start, $end)->count();
            $overlayTerms[] = $moves(['termination'], $start, $end)->count();

            $cursor = $next;
        }

        // projection: average hires of last 4 periods
        $samples = [];
        for ($i = 1; $i <= 4; $i++) {
            [$pf, $pt] = match ($period) {
                'daily' => [$anchor->copy()->subDays($i)->startOfDay(), $anchor->copy()->subDays($i)->endOfDay()],
                'weekly' => [$anchor->copy()->subWeeks($i)->startOfWeek(), $anchor->copy()->subWeeks($i)->endOfWeek()],
                'quarterly' => [
                    $anchor->copy()->subMonths(3 * $i)->startOfQuarter(),
                    $anchor->copy()->subMonths(3 * $i)->endOfQuarter(),
                ],
                'semiannual' => ($anchor->copy()->subMonths(6 * $i)->month <= 6)
                    ? [$anchor->copy()->subMonths(6 * $i)->startOfYear(), $anchor->copy()->subMonths(6 * $i)->startOfYear()->addMonths(5)->endOfMonth()]
                    : [$anchor->copy()->subMonths(6 * $i)->startOfYear()->addMonths(6), $anchor->copy()->subMonths(6 * $i)->endOfYear()],
                'yearly' => [$anchor->copy()->subYears($i)->startOfYear(), $anchor->copy()->subYears($i)->endOfYear()],
                default => [$anchor->copy()->subMonths($i)->startOfMonth(), $anchor->copy()->subMonths($i)->endOfMonth()],
            };
            $samples[] = $moves(['hire', 'reinstatement'], $pf, $pt)->count();
        }

        $projection = $samples ? round(array_sum($samples) / count($samples), 2) : 0.0;

        return view('employees.report', compact(
            'period', 'anchor', 'from', 'to', 'total', 'count', 'active', 'avgSalary',
            'byField', 'byDepartment', 'byStatus', 'trend', 'projection',
            'roleSummary', 'topRoles', 'terminationsInPeriod', 'terminationsTrend', 'fields', 'activePerField', 'hiresPerField', 'termsPerField',
            'fieldLabels', 'fieldActiveArr', 'fieldHiresArr', 'fieldTermsArr',
            'roleLabels', 'roleCounts', 'topRoleLabels', 'topRoleCounts',
            'deptLabels', 'deptCounts', 'terminationMonths', 'terminationCounts',
            'overlayLabels', 'overlayHires', 'overlayTerms', 'terminated', 'pending'
        ));
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // One grouped query instead of loading the whole employees table per field office.
        // Explicit grouping also fixes the old where(...)->orWhere('status','Terminated')
        // precedence bug, which counted unapproved terminated staff in every total.
        $counts = employee::query()
            ->where('ho_status', 'approved')
            ->whereIn('status', ['Active', 'Terminated'])
            ->selectRaw('field_id, status, COUNT(*) as total')
            ->groupBy('field_id', 'status')
            ->get();

        $count = function (string $status, ?int $fieldId = null) use ($counts): int {
            return (int) $counts
                ->when($fieldId !== null, fn ($c) => $c->where('field_id', $fieldId))
                ->where('status', $status)
                ->sum('total');
        };

        $offices = ['Accra' => 1, 'Botwe' => 2, 'Tema' => 3, 'Takoradi' => 4, 'Koforidua' => 5, 'Kumasi' => 6, 'Shyhills' => 7];

        $data = [
            'activeEmployees'     => $count('Active'),
            'terminatedEmployees' => $count('Terminated'),
            'canViewSalary'       => $this->employeeCanViewSalary(),
        ];
        // Pay-priority cards count ACTIVE employees within what this user may see.
        $priorityCounts = $this->employeeListBase()->where('employees.status', 'Active')
            ->reorder()->toBase()
            ->select(DB::raw('employees.pay_priority as level, COUNT(DISTINCT employees.id) as total')) // replaces the list columns
            ->groupBy('employees.pay_priority')->pluck('total', 'level');
        $data['payFirstCount'] = (int) ($priorityCounts[PayPriority::URGENT] ?? 0);
        $data['payEarlyCount'] = (int) ($priorityCounts[PayPriority::PRIORITY] ?? 0);
        $data['offices'] = $offices;

        foreach ($offices as $name => $fieldId) {
            $data["employee{$name}Active"]     = $count('Active', $fieldId);
            $data["employee{$name}Terminated"] = $count('Terminated', $fieldId);
        }

        return view('employees.index', $data);
    }

    private const EMPLOYEE_RANGE_COLUMNS = [
        'date_of_joining' => 'employees.date_of_joining',
        'created_at'      => 'employees.created_at',
        'status_date'     => 'employees.status_date',
    ];
 
    /** DataTables column index => SQL column. Indexes match the columns[] order in the view. */
    private const EMPLOYEE_TEXT_COLUMNS = [
        2  => 'employees.name',
        3  => 'employees.gender',
        4  => 'employees.phone_number',
        6  => 'departments.name',
        7  => 'roles.name',
        8  => 'fields.name',
        10 => 'employees.location',
        11 => 'employees.payment_type',
        12 => 'banks.name',
        13 => 'payment_infos.acc_number',
        14 => 'employees.status',
        17 => 'payment_infos.tin_number',
        19 => 'payment_infos.ssnit_number',
        26 => 'users.name',
    ];
 
    private const EMPLOYEE_DATE_COLUMNS = [
        5  => 'employees.date_of_joining',
        15 => 'employees.status_date',
        22 => 'employees.created_at',
        23 => 'employees.created_at',   // "created (relative)" column
        24 => 'employees.updated_at',
        25 => 'employees.updated_at',   // "updated (relative)" column
    ];
 
    private const EMPLOYEE_SALARY_COLUMNS = [
        20 => 'employees.basic_salary',
        21 => 'employees.allowances',
    ];
 
    private const EMPLOYEE_ORDER_COLUMNS = [
        1  => 'employees.id',
        2  => 'employees.name',
        3  => 'employees.gender',
        4  => 'employees.phone_number',
        5  => 'employees.date_of_joining',
        6  => 'departments.name',
        7  => 'roles.name',
        8  => 'fields.name',
        9  => 'clients.business_name',
        10 => 'employees.location',
        11 => 'employees.payment_type',
        12 => 'banks.name',
        13 => 'payment_infos.acc_number',
        14 => 'employees.status',
        15 => 'employees.status_date',
        20 => 'employees.basic_salary',
        21 => 'employees.allowances',
        22 => 'employees.created_at',
        24 => 'employees.updated_at',
        26 => 'users.name',
    ];
 
    private function employeeCanViewAll(): bool
    {
        $user = Auth::user();
 
        return (bool) ($user?->hasRole(['Invoice', 'Finance Manager'])
            || ($user?->department?->name === 'HR' && $user?->role?->name === 'Manager'));
    }
 
    /** Same rule the original list used: Managers do not see salaries. */
    private function employeeCanViewSalary(): bool
    {
        return \Illuminate\Support\Facades\Gate::allows('view-salaries'); // shared rule (AppServiceProvider)
    }
 
    private function employeeListBase()
    {
        $user = Auth::user();
 
        $query = employee::query()
            ->select([
                'employees.id', 'employees.name', 'employees.gender', 'employees.phone_number',
                'employees.date_of_joining', 'employees.location', 'employees.payment_type',
                'employees.status', 'employees.status_date', 'employees.tax_button', 'payment_infos.tin_number',
                'employees.ssnit_button', 'payment_infos.ssnit_number', 'employees.basic_salary',
                'employees.allowances', 'employees.created_at', 'employees.updated_at', 'employees.ho_status',
                'employees.pay_priority', 'employees.pay_priority_reason',
                'departments.name as department_name', 'roles.name as role_name', 'fields.name as field_name',
                'clients.name as client_name', 'clients.business_name as client_business_name',
                'payment_infos.acc_number', 'banks.name as bank_name', 'users.name as staff_name',
            ])
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->leftJoin('roles', 'roles.id', '=', 'employees.role_id')
            ->leftJoin('fields', 'fields.id', '=', 'employees.field_id')
            ->leftJoin('clients', 'clients.id', '=', 'employees.client_id')
            // payment_infos.employee_id is not unique: join only the first record per employee
            // (what hasOne() returns) so an extra record can never duplicate a row.
            ->leftJoinSub(
                DB::table('payment_infos')->selectRaw('employee_id, MIN(id) as id')->groupBy('employee_id'),
                'pi_first',
                'pi_first.employee_id',
                '=',
                'employees.id'
            )
            ->leftJoin('payment_infos', 'payment_infos.id', '=', 'pi_first.id')
            ->leftJoin('banks', 'banks.id', '=', 'payment_infos.bank_id')
            ->leftJoin('users', 'users.id', '=', 'employees.user_id1')
            ->where('employees.ho_status', 'approved')
            ->whereIn('employees.status', ['Active', 'Terminated']);
 
        if (! $this->employeeCanViewAll()) {
            $fieldIds = $user?->field_id === 3 ? [3, 7] : array_filter([$user?->field_id]);
            $fieldIds ? $query->whereIn('employees.field_id', $fieldIds) : $query->whereRaw('1 = 0');
        }
 
        return $query;
    }
 
    /**
     * Employees in the list's scope that match the list's current filters (date range,
     * cards, search box, column filters) - for the bulk update download.
     */
    public function bulkUpdateEmployeeIds(Request $request): array
    {
        $query = $this->employeeListBase();
        $this->employeeListFilters($query, $request);

        return $query->reorder()->orderBy('employees.name')->orderBy('employees.id')
            ->pluck('employees.id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /** Which of these employees this user may see (and therefore bulk update). */
    public function visibleEmployeeIds(array $ids): array
    {
        return $this->employeeListBase()->whereIn('employees.id', $ids ?: [0])
            ->pluck('employees.id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    private function employeeListFilters($query, Request $request): void
    {
        $canViewSalary = $this->employeeCanViewSalary();
 
        // Range picker. "range_by" is validated against a whitelist.
        $rangeBy = $this->requestString($request, 'range_by');
        $rangeColumn = self::EMPLOYEE_RANGE_COLUMNS[$rangeBy ?? ''] ?? self::EMPLOYEE_RANGE_COLUMNS['date_of_joining'];
        $this->whereWithinRange(
            $query,
            $rangeColumn,
            $this->requestString($request, 'from'),
            $this->requestString($request, 'to')
        );
 
        // Summary-card filters (clickable cards on the page). They narrow WITHIN the
        // user's scope from employeeListBase(), so a card can never widen access.
        $fieldId = $this->requestString($request, 'field_id');
        if ($fieldId !== null && ctype_digit($fieldId)) {
            $query->where('employees.field_id', (int) $fieldId);
        }
        $status = $this->requestString($request, 'status');
        if (in_array($status, ['Active', 'Terminated'], true)) {
            $query->where('employees.status', $status);
        }
        $priority = $this->requestString($request, 'priority');
        if (isset(PayPriority::FILTERS[$priority])) {
            $query->where('employees.pay_priority', PayPriority::FILTERS[$priority]);
        } elseif ($priority === 'flagged') {
            $query->where('employees.pay_priority', '>', PayPriority::NORMAL);
        }

        // Global search box - same rules as the invoice / receipt lists.
        $search = trim((string) $this->requestString($request, 'search.value'));
        if ($search !== '') {
            $term = $this->likeTerm($search);

            $query->where(function ($q) use ($search, $term) {
                foreach ([
                    'employees.name', 'employees.phone_number', 'employees.location', 'employees.status',
                    'employees.gender', 'employees.payment_type', 'departments.name', 'roles.name',
                    'fields.name', 'clients.name', 'clients.business_name', 'banks.name',
                    'payment_infos.acc_number', 'users.name',
                ] as $column) {
                    $q->orWhere($column, 'like', $term);
                }

                foreach (['employees.date_of_joining', 'employees.status_date', 'employees.created_at'] as $column) {
                    $q->orWhere(function ($w) use ($column, $search) {
                        $this->whereDateMatches($w, $column, $search);
                    });
                }

                // Only treat it as an id when it looks like one ("45", "FWSS 45").
                if (preg_match('/^(fwss)?\s*#?\d+$/i', $search)) {
                    $q->orWhere('employees.id', 'like', '%' . preg_replace('/\D/', '', $search) . '%');
                }
            });
        }

        $columns = $request->input('columns', []);
        foreach (is_array($columns) ? $columns : [] as $index => $column) {
            $value = $this->columnFilterValue($column);
            if ($value === '') {
                continue;
            }
 
            $index = (int) $index;
 
            switch (true) {
                case $index === 1:
                    $this->whereIdMatches($query, 'employees.id', $value);   // "FWSS 45" -> 45
                    break;
 
                case $index === 9:
                    $term = $this->likeTerm($value);
                    $query->where(function ($q) use ($term) {
                        $q->where('clients.name', 'like', $term)
                            ->orWhere('clients.business_name', 'like', $term);
                    });
                    break;
 
                case $index === 16 || $index === 18:   // TAX / SSNIT switch columns: on | off
                    $flag = $index === 16 ? 'employees.tax_button' : 'employees.ssnit_button';
                    $word = strtolower($value);
                    if ($word === 'on') {
                        $query->where($flag, 'on');
                    } elseif ($word === 'off') {
                        $query->where(function ($q) use ($flag) {
                            $q->whereNull($flag)->orWhere($flag, '!=', 'on');
                        });
                    }
                    break;
 
                case isset(self::EMPLOYEE_DATE_COLUMNS[$index]):
                    $this->whereDateMatches($query, self::EMPLOYEE_DATE_COLUMNS[$index], $value);
                    break;
 
                case isset(self::EMPLOYEE_SALARY_COLUMNS[$index]):
                    if ($canViewSalary) {   // never let a filter reveal hidden salary data
                        $this->whereNumberMatches($query, self::EMPLOYEE_SALARY_COLUMNS[$index], $value);
                    }
                    break;
 
                case isset(self::EMPLOYEE_TEXT_COLUMNS[$index]):
                    $query->where(self::EMPLOYEE_TEXT_COLUMNS[$index], 'like', $this->likeTerm($value));
                    break;
            }
        }
    }
 
    private function employeeListOrder($query, Request $request): void
    {
        $orderColumn = (int) $request->input('order.0.column', 22);
        $orderDir = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
 
        $sortable = self::EMPLOYEE_ORDER_COLUMNS;
        if (! $this->employeeCanViewSalary()) {
            unset($sortable[20], $sortable[21]);   // sorting by salary would leak it too
        }
 
        $query->orderBy($sortable[$orderColumn] ?? 'employees.created_at', $orderDir)
            ->orderBy('employees.id', 'desc');   // unique tiebreaker (paging + chunked export)
    }
 
    /** Paginated employee data for the employee-list DataTable. */
    public function datatable(Request $request)
    {
        $query = $this->employeeListBase();
 
        $recordsTotal = (clone $query)->count();

        $this->employeeListFilters($query, $request);
        $recordsFiltered = (clone $query)->count();
 
        $this->employeeListOrder($query, $request);
 
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 25);
        $length = $length > 0 ? min($length, 2000) : 25;   // -1 ("All") falls back to 25, as on invoices

        $rows = $query->offset($start)->limit($length)->get();
 
        $user = Auth::user();
        $canViewAll = $this->employeeCanViewAll();
        $canEditAll = $user?->hasRole(['Finance Manager']) ?? false;
        $canViewSalary = $this->employeeCanViewSalary();
 
        $data = $rows->map(function ($employee) use ($canViewAll, $canEditAll, $canViewSalary) {
            $clientName = trim($employee->client_name . ' ' . $employee->client_business_name);
            $actions = '<div class="dropdown"><button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></button><div class="dropdown-menu"><a class="dropdown-item" href="' . e(url('employees/' . $employee->id)) . '"><i class="icon-base bx bxs-bullseye"></i> view</a>';
            if ($canViewAll || $employee->ho_status !== 'approved' || $canEditAll) {
                $actions .= '<a class="dropdown-item" href="' . e(url('employees/' . $employee->id . '/edit')) . '"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>';
            }
            if ($canViewSalary) {
                $actions .= '<hr><a class="dropdown-item" href="' . e(url('employeesSalary/' . $employee->id)) . '"><i class="icon-base bx bx-money-withdraw"></i> Salaries</a>';
            }
            $actions .= '</div></div>';
 
            return [
                'row_number' => '', 'employee_id' => 'FWSS ' . $employee->id,
                'name' => PayPriority::badge($employee->pay_priority, $employee->pay_priority_reason) . e($employee->name),
                'gender' => $employee->gender, 'phone_number' => $employee->phone_number,
                'date_of_joining' => $employee->date_of_joining ? Carbon::parse($employee->date_of_joining)->format('l F d, Y') : '',
                'department' => $employee->department_name, 'role' => $employee->role_name, 'field' => $employee->field_name,
                'client' => $clientName, 'location' => $employee->location, 'payment_type' => $employee->payment_type,
                'bank' => $employee->bank_name, 'account_number' => $employee->acc_number,
                'status' => '<span class="badge ' . ($employee->status === 'Active' ? 'bg-label-success' : 'bg-label-danger') . '">' . e($employee->status) . '</span>',
                'status_date' => $employee->status_date ? Carbon::parse($employee->status_date)->format('F, Y') : '',
                'tax' => $employee->tax_button === 'on' ? '<span class="badge bg-label-dark">on</span>' : '<span class="badge bg-label-danger">OFF</span>',
                'tin' => $employee->tin_number, 'ssnit' => $employee->ssnit_button === 'on' ? '<span class="badge bg-label-dark">on</span>' : '<span class="badge bg-label-danger">OFF</span>',
                'ssnit_number' => $employee->ssnit_number, 'basic_salary' => $canViewSalary ? $employee->basic_salary : '', 'allowances' => $canViewSalary ? $employee->allowances : '',
                'created_at' => $employee->created_at ? Carbon::parse($employee->created_at)->format('F, Y') : '',
                'created_period' => $employee->created_at ? Carbon::parse($employee->created_at)->diffForHumans() : '',
                'updated_at' => $employee->updated_at ? Carbon::parse($employee->updated_at)->format('F, Y') : '',
                'updated_period' => $employee->updated_at ? Carbon::parse($employee->updated_at)->diffForHumans() : '',
                'staff' => $employee->staff_name, 'action' => $actions,
            ];
        })->all();
 
        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }
 
    /** Excel export of EVERY employee matching the current filters. */
    public function export(Request $request)
    {
        $canViewSalary = $this->employeeCanViewSalary();
 
        $query = $this->employeeListBase();
        $this->employeeListFilters($query, $request);
        $this->employeeListOrder($query, $request);
 
        $headings = [
            'Employee ID', 'Name', 'Pay Priority', 'Gender', 'Phone', 'Date Joined', 'Department', 'Role', 'Field Office',
            'Client', 'Location', 'Payment Type', 'Bank', 'Account No.', 'Status', 'Status Date',
            'TIN', 'SSNIT No.',
        ];
        if ($canViewSalary) {
            array_push($headings, 'Basic Salary', 'Allowances');
        }
        array_push($headings, 'Created', 'Updated', 'Updated By');
 
        $export = new FilteredQueryExport($query, $headings, function ($e) use ($canViewSalary) {
            $day = fn ($v) => $v ? Carbon::parse($v)->format('Y-m-d') : '';
 
            $row = [
                'FWSS ' . $e->id, $e->name, $e->pay_priority ? PayPriority::LABELS[(int) $e->pay_priority] : '', $e->gender, $e->phone_number, $day($e->date_of_joining),
                $e->department_name, $e->role_name, $e->field_name,
                trim($e->client_name . ' ' . $e->client_business_name), $e->location, $e->payment_type,
                $e->bank_name, $e->acc_number, $e->status, $day($e->status_date),
                $e->tin_number, $e->ssnit_number,
            ];
            if ($canViewSalary) {
                array_push($row, (float) $e->basic_salary, (float) $e->allowances);
            }
            array_push(
                $row,
                $e->created_at ? Carbon::parse($e->created_at)->format('Y-m-d H:i') : '',
                $e->updated_at ? Carbon::parse($e->updated_at)->format('Y-m-d H:i') : '',
                $e->staff_name
            );
 
            return $row;
        });
 
        return $export->download('Employees-' . now()->format('Ymd-His') . '.xlsx');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $Departments = Department::all();
        $Roles = Role::all();
        $fieldsAll = Field::all();
        $fields = null;
        $clients = null;
        $banks = Bank::all();
        $staff =  User::all();
        $user =  Auth::user();

        $manager = $staff->where('department_id', '7')->where('role_id', '3');
        $headOfficemanager = $staff->whereIn('department_id', ['1','4'])->whereIn('role_id', ['1','3']);
        $assign_staff = [];

        if($user->role->name == 'Admin Assistant')
            {
                $assign_staff = $manager;
            }

        if($user->role->name == 'Manager')
            {
                $assign_staff = $headOfficemanager;
            }


        foreach($fieldsAll as $field )
        {
            if( $user->role?->name == 'Finance Manager' || $user->role?->name == 'Invoice' || ($user->department?->name == 'HR' && $user->role?->name == 'Manager') )
            {
                $clients = Client::where('status', 'Active')->where('ho_status', 'approved')->get();
                $fields = $fieldsAll;
                break;
            }
            if($user->field_id == '3')
            {
                $clients = Client::whereIn('id', ['3','7'])->where('status', 'Active')->where('ho_status', 'approved')->get();
                $fields = $fieldsAll->whereIn('id', ['3','7'])->values();
                break;
            }
            if($field->name == $user->field?->name)
            {
                $clients = Client::where('field_id', $field->id)->where('status', 'Active')->where('ho_status', 'approved')->get();
                $fields[] = $field;
            }
        }


        return view('employees.create', compact('Departments', 'Roles', 'clients', 'banks', 'fields', 'assign_staff'));
    }

    public function EmpPayInfo($id)
    {
        //
        $employee_pay_info = PaymentInfo::where('employee_id', $id)->first();
        $banks =  Bank::all();
        return view('employees.payinfo', compact('employee_pay_info', 'banks'));
    }


    public function EmpPayInfoUpdate(UpdatePaymentInfoRequest $request, $id)
    {
        //
        $employee_pay_info = PaymentInfo::findOrFail($id);
        // dd($request->all(), $employee_pay_info->employee->id);
        $employee_pay_info->bank_id = $request->input('bank_id');
        $employee_pay_info->acc_number = $request->input('acc_number');
        $employee_pay_info->branch = $request->input('branch');
        $employee_pay_info->branch_code = $request->input('branch_code');
        // $employee_pay_info->tin_number = $request->input('tin_number');
        // $employee_pay_info->ssnit_number = $request->input('ssnit_number');
        $employee_pay_info->user_id = Auth::id();
        $employee_pay_info->save();

        // Update Employee Model payment_type
        $employee = employee::findOrFail( $employee_pay_info->employee->id);
        // dd($employee);
        $employee->update(['payment_type' => $request->payment_type]);


        return redirect()->route('employees.ViewPayInfo',['id' => $employee_pay_info->employee_id])->with('success', 'Employee Payment Information updated successfully.');
        
        // return back()->with('success', 'Employee Payment Information updated successfully.');
    }

    

    public function EmpViewPayInfo($id)
    {
        //
        $employee_pay_info = PaymentInfo::where('employee_id', $id)->first();
        $banks =  Bank::all();
        return view('employees.viewpayinfo', compact('employee_pay_info', 'banks'));
    }



    public function EmpSalary($id)
    {
        if(Auth::user()->role?->id == '2' || Auth::user()->role?->id == '3')
            {
                $employee = employee::with('salaries.client')->findOrFail($id);
                return view('employees.salaries', compact('employee'));
            }
        else{
            return back();
        }

    }


    public function EmpViewSalaryInfo()
    {
        //
        return view('employees.viewsalaryinfo');
    }   

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreemployeeRequest $request, StorePaymentInfoRequest $payRequest) 
    {
        //
        // dd($request->all());
        // dd(Auth::id());
        $user_id = Auth::id();
        $image = null;
        $tin_number = null;
        $ssnit_number = null;

        if($request->file('image'))
        {
           $image = ($request->file('image'))->store('images', 'public_html_disk');
        }

        if($request->input('tin'))
        {
            $tin_number = $request->input('tin_number');
        }
        if( $request->input('ssnit'))
        {
            $ssnit_number = $request->input('ssnit_number');
        }

        // dd($request->input('tin'), $tin_number, $request->input('ssnit'), $ssnit_number);
        // Same creation path as the bulk upload (App\Support\EmployeeCreator): same approval
        // status by role, employee + payment details in ONE transaction, and TIN / SSNIT
        // written to every place that has the column.
        $employee = \App\Support\EmployeeCreator::create([
            'name' => $request->input('name'),
            'gender' => $request->input('gender'),
            'phone_number' => $request->input('phone_number'),
            'channel' => $request->input('channel'),
            'date_of_birth' => $request->input('date_of_birth'),
            'nia_number' => $request->input('nia_number'),
            'address' => $request->input('address'),
            'marital_status' => $request->input('marital_status'),
            'worker_type' => $request->input('worker_type'),
            'date_of_joining' => $request->input('date_of_joining'),
            'department_id' => $request->input('department_id'),
            'role_id' => $request->input('role_id'),
            'field_id' => $request->input('field_id'),
            'client_id' => $request->input('client_id'),
            'location' => $request->input('location'),
            'tax_button' => $request->input('tin'),
            'tin_number' => $tin_number,
            'ssnit_button' => $request->input('ssnit'),
            'ssnit_number' => $ssnit_number,
            'basic_salary' => $request->input('basic_salary'),
            'allowances' => $request->input('allowances'),
            'payment_type' => $request->input('payment_type'),
            'gurantor_name' => $request->input('gurantor_name'),
            'gurantor_number' => $request->input('gurantor_number'),
            'gurantor_address' => $request->input('gurantor_address'),
            'gurantor_nia_number' => $request->input('gurantor_nia_number'),
            'relationship' => $request->input('relationship'),
            'image' => $image,
        ], [
            'bank_id' => $payRequest->input('bank_id'),
            'acc_number' => $payRequest->input('acc_number'),
            'branch' => $payRequest->input('branch'),
            'branch_code' => $payRequest->input('branch_code'),
        ], Auth::user(), $request->input('staff'));

        return redirect()->route('employees.show',['employee' => $employee->id])->with('success', 'Employee created successfully.');
        //return redirect()->route('employees.index')->with('success', 'Employee created successfully.');

    }

    /**
     * Display the specified resource.
     */
    public function show(employee $employee)
    {
        //
        $channels = DB::table('hubtel_channel')->get();
        $Departments = Department::all();
        $Roles = Role::all();
        $Fields = Field::all();
        $clients = Client::all();
        $banks = Bank::all();

        $user = Auth::user();
        $staff =  User::all();
        $manager = $staff->where('department_id', '7')->where('role_id', '3');
        $finance_manager = $staff->where('department_id', '1')->where('role_id', '1');
        $assign_staff = [];
        // dd($manager);
        if($user->role->name == 'Admin Assistant')
            {
                $assign_staff = $manager;
            }

        if($user->role->name == 'Manager')
            {
                $assign_staff = $finance_manager;
            }


        return view('employees.show', compact('employee', 'Departments', 'Roles', 'Fields', 'clients', 'banks', 'channels', 'assign_staff')); 
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(employee $employee)
    {
        //
        // dd($employee);
        $channels = DB::table('hubtel_channel')->get();
        $Departments = Department::all();
        $Roles = Role::all();
        $fieldsAll = Field::all();
        $Fields= null;
        $clients = null;
        $banks = Bank::all();

        $user = Auth::user();
        $staff =  User::all();
        $manager = $staff->where('department_id', '7')->where('role_id', '3');
        $finance_manager = $staff->where('department_id', '1')->where('role_id', '1');
        $assign_staff = [];
        // dd($manager);
        if($user->role->name == 'Admin Assistant')
            {
                $assign_staff = $manager;
            }

        if($user->role->name == 'Manager')
            {
                $assign_staff = $finance_manager;
            }



        foreach($fieldsAll as $field )
        {
            if( $user->role?->name == 'Finance Manager' || $user->role?->name == 'Invoice' || ($user->department?->name == 'HR' && $user->role?->name == 'Manager') )
            {
                $clients = Client::where('status', 'Active')->where('ho_status', 'approved')->get();
                $Fields = $fieldsAll;
                break;
            }
            if($user->field_id == '3')
            {
                $clients = Client::whereIn('id', ['3','7'])->where('status', 'Active')->where('ho_status', 'approved')->get();
                $Fields = $fieldsAll->whereIn('id', ['3','7'])->values();
                break;
            }
            if($field->name == $user->field?->name)
            {
                $clients = Client::where('field_id', $field->id)->where('status', 'Active')->where('ho_status', 'approved')->get();
                $Fields[] = $field;
            }
        }

        return view('employees.edit', compact('employee', 'Departments', 'Roles', 'Fields', 'clients', 'banks', 'channels', 'assign_staff'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateemployeeRequest $request, employee $employee)
    {
        //
        // dd($request->all());
        if ($request->hasFile('image')) {

            // get current image path to delete old file
            $employee = employee::findOrFail($employee->id);
            $employeeImagePath = $employee?->image; 
            // remove old file — ensure the stored value matches what you saved earlier
            $employeeImagePath = $employeeImagePath ? Storage::disk('public')->path($employeeImagePath) : null;
            // Storage::disk('public')->delete($employee?->image);
            // store returns a string path (or false on failure)
            $path = $request->file('image')->store('images', 'public_html_disk');

            if ($path === false) {
                return back()->withErrors(['image' => 'Failed to store image.']);
            }
            // save the stored path (or use basename($path) if you only store filename)
            // dd($path);
        }

        // $tax_button = null ;
        $tin_number = null;
        // $ssnit_button = null;
        $ssnit_number = null;
        $staff = $request->input('staff');

        if($request->input('tin'))
        {
            $tin_number = $request->input('tin_number');
        }
        if( $request->input('ssnit'))
        {
            $ssnit_number = $request->input('ssnit_number');
        }

        $user_id = Auth::id();
        $employee->image = $path ?? $employee->image;
        $employee->name = $request->input('name');
        $employee->gender = $request->input('gender');
        $employee->phone_number = $request->input('phone_number');
        $employee->channel = $request->input('channel');
        $employee->date_of_birth = $request->input('date_of_birth');
        $employee->nia_number = $request->input('nia_number');
        $employee->address = $request->input('address');
        $employee->marital_status = $request->input('marital_status');
        $employee->worker_type = $request->input('worker_type');
        $employee->date_of_joining = $request->input('date_of_joining');
        $employee->department_id = $request->input('department_id');
        $employee->role_id = $request->input('role_id');
        $employee->field_id = $request->input('field_id');
        $employee->client_id = $request->input('client_id');
        $employee->location = $request->input('location');
        $employee->payment_type = $request->input('payment_type');
        $employee->basic_salary = $request->input('basic_salary');

        $employee->tax_button = $request->input('tin');
        $employee->ssnit_button = $request->input('ssnit');

        $employee->tin_number = $tin_number;
        $employee->ssnit_number = $ssnit_number;

        $employee->allowances = $request->input('allowances');
        $employee->gurantor_name = $request->input('gurantor_name');
        $employee->gurantor_number = $request->input('gurantor_number');
        $employee->gurantor_address = $request->input('gurantor_address');
        $employee->gurantor_nia_number = $request->input('gurantor_nia_number');
        $employee->relationship = $request->input('relationship');


        // A Terminated employee stays Terminated when edited: the approval rules below set
        // "Active" / "Pending", which would silently re-instate them (no status month, no NRRIT
        // record). Re-instating is done with "Re-instate". Same rule as bulk update.
        $statusBeforeEdit = $employee->getOriginal('status');

        if(Auth::user()->role?->id == '1')
            {
                $employee->status = 'Active';
               $employee->ho_status = 'approved';
               $employee->user_id2 = $user_id;
            }
        if(Auth::user()->department?->id == '7' && Auth::user()->role?->id == '3')
            {
               $employee->ho_status = 'pending';
               $employee->user_id2 = $staff;
               $employee->status = 'Pending';

               $employee->bran_status = 'approved';
               $employee->user_id1 = $user_id;
            }
        if(Auth::user()->department?->id == '7' && Auth::user()->role?->id == '27')
            {
               $employee->ho_status = null;
               $employee->bran_status = 'pending';
               $employee->user_id1 = $staff;
               $employee->status = 'Pending';

               $employee->assit_status = 'pending';

            }

        if(Auth::user()->department?->id == '4' && Auth::user()->role?->id == '27')
            {
               $employee->ho_status = null;
               $employee->bran_status = 'pending';
               $employee->user_id1 = $staff;
               $employee->status = 'Pending';

               $employee->assit_status = 'pending';

            }

        // $employee->user_id1 = Auth::id();

        if (\App\Support\EmployeeCreator::keepsStatus($statusBeforeEdit)) {
            $employee->status = $statusBeforeEdit;
        }

        $employee->save();

        return redirect()->route('employees.show',['employee' => $employee])->with('success', 'Employee updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(employee $employee)
    {
        //
        dd($employee);
    }

    
    /**
     * Get All Employees with role of security guard for incoming client.
     */
    public function GuardClient($id)
    {


       $guards =  employee::where('client_id',$id)->where('department_id', 6)->where('status', 'Active')->get();

       if($guards->isEmpty())
       {
            return back()->with('error', 'Client has no current Guards');
       }

       $client = Client::findOrFail($id);
       $clients = Client::all();

        return view('employees.GuardView', compact('guards', 'clients', 'client'));
    }

    public function GuardReAassign(EmployeeClientRequest $request) 
    {
        // dd($request->all());

        $employees = $request->input('employees', []);
        // $clients = $request->input('client_id', []);
        // $locations = $request->input('location', []);

        // dd($employees, $clients, $locations);
        if (empty($employees)) {
            return back()->with('error', 'No Guard Selected to ReAssign.');
        }

        collect($request->employees)->map(function ($employeeId, $index) use ($request) {

            //  $clients = $request->input('client_id', []);
            //  $locations = $request->input('location', []);

            // update category for the client and month
            $employee = employee::findOrFail($employeeId);
            if ($employee) {
                $employee->client_id = $request->client_id[$index];
                $employee->location = $request->location[$index];
                $employee->save();
            }

            
        });

         return back()->with('success', 'Guard successfully ReAssigned!');  
        

        // // dd($employees);
        // foreach ($employees as $key => $employee)
        // {

        //     // echo $employee .' ' . $clients[$key]. ' ' .$locations[$key] .'<br>';
        //     $employee = employee::findOrFail($employee);
        //     $employee->client_id = $clients[$key];
        //     $alreadyProcessed[] =  $clients[$key];
        //     $employee->location = $locations[$key];
        //     $employee->save();

        //     $employee->update([
        //         'client_id' => $clients[$key],
        //         'location' => $locations[$key],
        //     ]);
        // }

        // if(!empty( $alreadyProcessed))
        // {
        //     return redirect()->route('client.index')->with('success', 'Guard successfully ReAssigned! Client: '. implode(', ', $alreadyProcessed));
        // }
        // // // return back()->with('success', 'Guard successfully ReAssigned!');
   
    }

    public function terminateEmployee(Request $request, $id)
    {
        return $this->changeEmploymentStatus($request, (int) $id, \App\Support\EmployeeMovements::TERMINATION);
    }

    public function employeeReinstate(Request $request, $id)
    {
        return $this->changeEmploymentStatus($request, (int) $id, \App\Support\EmployeeMovements::REINSTATEMENT);
    }

    /**
     * Terminate or re-instate, with the effective date, the reason and who asked.
     * Head office (role 1) decides at once; anyone else's request waits for head office
     * on the pending page (approve = it counts, decline = the employee's status is put back).
     * Recorded in employee_movements (the HR movements report), never in nrrit.
     */
    private function changeEmploymentStatus(Request $request, int $id, string $type)
    {
        $M = \App\Support\EmployeeMovements::class;
        $terminate = $type === $M::TERMINATION;
        $data = $request->validate([
            'effective_date' => ['required', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:' . now()->addDays(90)->toDateString()],
            'reason' => $terminate ? ['required', \Illuminate\Validation\Rule::in(array_keys($M::REASONS))] : ['nullable'],
            'note' => [$terminate && $request->input('reason') === 'other' ? 'required' : 'nullable', 'string', 'max:255'],
            'staff' => ['nullable', 'integer', 'exists:users,id'],
        ], [
            'note.required' => 'Say what the reason is when you choose "Other".',
        ]);

        $employee = employee::findOrFail($id);
        $user = Auth::user();
        $name = $employee->name . ' (FWSS ' . $employee->id . ')';

        // Only an active employee can be terminated, only a terminated one re-instated, and
        // never while a request of that kind is already waiting.
        if ($terminate ? $employee->status !== 'Active' : $employee->status !== 'Terminated') {
            return back()->with('error', $name . ' is ' . ($employee->status ?: 'not active') . ': '
                . ($terminate ? 'only an Active employee can be terminated.' : 'only a Terminated employee can be re-instated.'));
        }
        if ($M::pending($employee->id, $type)) {
            return back()->with('error', 'A ' . strtolower($M::LABELS[$type]) . ' request for ' . $name . ' is already waiting for head office.');
        }

        $headOffice = (string) $user->role?->id === '1';
        $date = \Carbon\Carbon::parse($data['effective_date'])->toDateString();
        $approver = $data['staff'] ?? User::where('department_id', '1')->where('role_id', '1')->value('id');

        DB::transaction(function () use ($employee, $user, $headOffice, $date, $data, $type, $terminate, $approver, $M) {
            $previous = $employee->status;
            $employee->status_date = $date;
            $employee->user_id1 = $user->id;
            if ($headOffice) {
                $employee->status = $terminate ? 'Terminated' : 'Active';
                $employee->ho_status = 'approved';
                $employee->ho_date = now()->toDateString();
                $employee->user_id2 = $user->id;
            } else {
                $employee->status = $terminate ? 'Terminated' : 'Re-Instate';
                $employee->ho_status = 'pending';
                $employee->user_id2 = $approver;
                $employee->bran_status = 'approved';
                $employee->bran_date = now()->toDateString();
            }
            $employee->save();

            $M::record($employee, $type, $headOffice ? $M::APPROVED : $M::PENDING, $date, [
                'reason' => $terminate ? $data['reason'] : null,
                'note' => $data['note'] ?? null,
                'previous_status' => $previous,
                'source' => $terminate ? 'terminate' : 'reinstate',
            ]);
        });

        $what = $terminate ? 'terminated' : 're-instated';

        return redirect()->route('employees.show', $employee->id)->with($terminate ? 'error' : 'success', $headOffice
            ? $name . ' ' . $what . ' with effect from ' . \Carbon\Carbon::parse($date)->format('d M Y') . '.'
            : 'Request to ' . ($terminate ? 'terminate' : 're-instate') . ' ' . $name . ' sent to head office for approval.');
    }

    public function employeesBank()
    {
        // LIST ALL BANKS , EACH WITH ALL EMPLOYEES ASSIGNED TO THAT BANK
        $bankIds = Bank::pluck('id');
        $banks = Bank::all();

        // $groupedBankEmployees = PaymentInfo::with('employee') ->whereIn('bank_id', $bankIds)->get()->groupBy('bank_id');

        $groupedBankEmployees = PaymentInfo::whereIn('bank_id', $bankIds)
                                    ->select('bank_id', DB::raw('count(employee_id) as total_employees'))
                                    ->groupBy('bank_id')
                                    ->get();

        // dd($groupedBankEmployees);
        return view('employees.banks', compact('groupedBankEmployees', 'banks'));

    }

    public function employeesBankView($bank_id)
    {
        // dd($bank_id);
        // $bank = Bank::findOrfail($bank_id);
        // $groupedBankEmployees = employee::where('bank_id', $bank_id)->get();
        $groupedBankEmployees = PaymentInfo::with('employee')->where('status', 'Active')->where('bank_id', $bank_id)->get();

        // dd($groupedBankEmployees);

        // foreach($groupedBankEmployees as $employee)
        //     {
        //         echo $employee->employee->name . " / " . $employee . "<br/>";
        //     } 

        return view('employees.bank_view', compact('groupedBankEmployees'));

    }


    public function employeesCash()
    {

        $fields = Field::all(); 

        $groupedCashkEmployees = employee::where('status', 'Active')->where('payment_type', 'Cash')->whereIn('field_id', $fields->pluck('id')->toArray())->groupBy('field_id')->get(['field_id',  DB::raw('COUNT(*) as total_employees')]);

        // dd($groupedCashkEmployees);
        return view('employees.cash', compact('groupedCashkEmployees', 'fields'));

    }

    public function employeesCashView($field_id)
    {

        $groupedCashEmployees = employee::where('status', 'Active')->where('payment_type', 'Cash')->where('field_id', $field_id)->get();

        // dd($groupedCashEmployees);
        return view('employees.cash_view', compact('groupedCashEmployees'));

    }


    public function employeesCashVerify(Request $request)
    {

        // dd($request->all());
         $employeeIds = $request->input('employees', []);
        //  dd($salaryIds);
        if (empty($employeeIds)) {
            return back()->with('error', 'No employee selected MoMo Name and Number Processing.');
        }

        $employees =  employee::findOrFail($request->employees);
        // dd($employees);

        foreach($employees as $employee)
            {
              $result =   $this->verifyMoMoName($employee->phone_number, $employee->channel);

                //   UPDATE DATABASE OF EMPLOYEE
                $employee->message =  $result['message'];
                $employee->responseCode =  $result['responseCode'];
                $employee->reg_name =  $result['data']['name'] ?? null;
                $employee->status_momo =  $result['data']['isRegistered'] ?? null;
                $employee->save();
                
            }

        return back()->with('success', 'Selected Employees MoMo Name and Number has completed Processing.');


    }

    public function verifyMoMoName($number , $channel)
    {
        // echo $number . " ". $channel . "<br>";
        $url = "https://rnv.hubtel.com/v2/merchantaccount/merchants/2037745/mobilemoney/verify?channel=".$channel."&customerMsisdn=".$number ;
        // $url = "https://webhook.site/832174de-8b79-4453-ac08-168031956be1";
            
        // echo $url  . "<br>";
           $data = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode('B81PkQQ:a239c6cf6e8d4dec8ae1d866ef0c633a')
            ])->get($url);

            $result =  $data->json();
        // dd( $result);
            return $result;

    }

    public function employeesPending()
    {
        // ONE list, and every card is counted from it, so the office cards always add up to
        // the total. Pending = waiting for head-office approval: a new recruit, termination
        // or re-instatement whose HO status is still pending (or not set yet).
        // The old queries ended in ->orwhere('ho_status', null) without brackets, which let
        // ANY employee with an empty HO status in (Active ones too), and each card ran its own
        // query, so cards and total could disagree.
        $pendingEmployees = employee::with(['user', 'user1', 'user2', 'department', 'role', 'field', 'client', 'paymentInfo.bank'])
            ->whereIn('status', ['Pending', 'Terminated', 'Re-Instate'])
            ->where(fn ($q) => $q->where('ho_status', 'pending')->orWhereNull('ho_status'))
            ->orderBy('id')
            ->get();

        $inField = fn (array $ids) => $pendingEmployees->filter(fn ($e) => in_array((int) $e->field_id, $ids, true))->values();

        $employeeAccra = $inField([1]);
        $employeeBotwe = $inField([2]);
        $employeeTema = $inField([3])->count();
        $employeeTakoradi = $inField([4]);
        $employeeKoforidua = $inField([5]);
        $employeeKumasi = $inField([6]);
        $employeeShyhills = $inField([7])->count();
        $Tema = $inField([3, 7]);                       // Tema also sees Shai Hills
        // Pending staff with no (or an unknown) field office: counted in the total, so shown too.
        $employeeNoOffice = $pendingEmployees->reject(fn ($e) => in_array((int) $e->field_id, [1, 2, 3, 4, 5, 6, 7], true))->values();

        // Head office sees every office; a field office sees its own office and its
        // sub-offices (a Tema officer also sees Shai Hills), one card each.
        $user = Auth::user();
        $seesAllOffices = $user->hasRole(['Invoice', 'Finance Manager']) || ($user->hasRole(['Manager']) && $user->hasPermission('HR'));
        $managed = $seesAllOffices ? [] : \App\Models\Field::managedIds($user->field_id ? (int) $user->field_id : null);
        $visiblePending = $seesAllOffices ? $pendingEmployees : $inField($managed);
        $officeNames = \App\Models\Field::whereIn('id', $managed ?: [0])->pluck('name', 'id');
        $officeCards = collect($managed)->filter(fn ($id) => $officeNames->has($id))
            ->map(fn ($id) => ['name' => $officeNames[$id], 'count' => $inField([$id])->count()])->values();

        // The waiting request behind each row (termination / re-instatement date and reason).
        $pendingMoves = \App\Models\EmployeeMovement::whereIn('employee_id', $visiblePending->pluck('id')->all() ?: [0])
            ->where('status', 'pending')->orderBy('id')->get()->keyBy('employee_id');

        return view('employees.pending', compact('pendingMoves', 'Tema', 'pendingEmployees', 'employeeAccra', 'employeeBotwe', 'employeeTema', 'employeeTakoradi',
            'employeeKoforidua', 'employeeKumasi', 'employeeShyhills', 'employeeNoOffice', 'visiblePending', 'officeCards'));
    }


    public function employeesAproval(Request $request)
    {
                // dd($request->all());
         $employeeIds = $request->input('employees', []);
        //  dd($employeeIds);
        if (empty($employeeIds)) {
            return back()->with('error', 'No employee selected !');
        }
        
         $employees =  employee::findOrFail($request->employees);
        //  dd($employees);

        //  foreach ($employees as $employee )
        //     {
        //         $employee->status = 'Active';
        //         $employee->user_id2 = Auth::id();
        //         $employee->save();

        //         DB::table('nrrit')->insert([
        //             'employee_id' =>  $employee->id,
        //             'name' => $employee->name,
        //             'status_month' =>  $employee->date_of_joining,
        //             'status' => 'Active',
        //             'status1' => 'New Recruit',
        //         ]);
        //     }

        $action = $request->input('action_type') ?? $request->input('submit') ?? $request->input('decline'); 

        $alreadyProcessed = [];
        $declined = [];

        $invoice_manager =  User::where('department_id', '1')->where('role_id', '1')->first();
        $user = Auth::user();

        // $invoice_manager = $staff->;

        foreach( $employees as $employee)
        {

            if( $action == 'branch' )
                
                {
                    // dd($employee);
                    $exists = employee::where('id', $employee->id)
                        ->where('bran_status', 'approved')
                        ->exists();
                    if ($exists) {
                        $alreadyProcessed[] =  " employee: " . $employee->id;
                        continue;
                    }
                    // update branch to approved, collection to approved and dates.
                    $employee->bran_status = 'approved';
                    $employee->user_id1 = $user->id;
                    $employee->bran_date = now();

                    $employee->assit_status = 'approved';
                    $employee->ho_status = 'pending';
                    $employee->user_id2 =  $invoice_manager->id;

                    $employee->save();
                    // return 'you are a Branch Manager';

                }

            if($action == 'headOffice' )
                {
                    // // dd($client);
                    $exists = employee::where('id', $employee->id)
                        ->where('ho_status', 'approved')
                        ->exists();
                    if ($exists) {
                        $alreadyProcessed[] =  " employee: " . $employee->id;
                        continue;
                    }

                    $employee->ho_status = 'approved';
                    $employee->user_id2 = $user->id;
                    $employee->ho_date = now();

                    // HR movements: approve what was waiting. A re-instatement is a re-instatement,
                    // never a new recruit (nrrit used to log both).
                    $movement = match (true) {
                        $employee->status === 'Re-Instate' => \App\Support\EmployeeMovements::REINSTATEMENT,
                        $employee->status === 'Terminated' => \App\Support\EmployeeMovements::TERMINATION,
                        in_array($employee->status, ['Pending', null, ''], true) => \App\Support\EmployeeMovements::HIRE,
                        default => null,
                    };
                    if ($employee->status === 'Pending' || $employee->status === 'Re-Instate' || $employee->status === null || $employee->status === '') {
                        $employee->status = 'Active';
                    }

                    \Illuminate\Support\Facades\DB::transaction(function () use ($employee, $movement) {
                        $employee->save();
                        if ($movement) {
                            \App\Support\EmployeeMovements::approve($employee, $movement);
                        }
                    });

                    // return 'you are at the head office';
                    
                }


            if($action == 'decline' )
                {
                    // A termination / re-instatement request declined by head office: the request
                    // is declined and the employee goes back to the status they had.
                    if (in_array($employee->status, ['Terminated', 'Re-Instate'], true) && $employee->ho_status !== 'approved') {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($employee) {
                            $m = \App\Support\EmployeeMovements::decline($employee);
                            $employee->status = $m?->previous_status ?: ($employee->status === 'Terminated' ? 'Active' : 'Terminated');
                            $employee->ho_status = 'approved';
                            $employee->save();
                        });
                        $declined[] = $employee->name . ' (FWSS ' . $employee->id . ')';
                        continue;
                    }

                    $employee->bran_status = 'pending';
                    // $employee->user_id2 = Auth::id();
                    $employee->assit_status = 'pending';
                    $employee->assit_date = now();


                    $employee->save();
                    // return 'you are at the head office Declining'; 

                }



        }



               if (! empty($declined)) {
                   return back()->with('success', 'Request declined; status restored for: ' . implode(', ', $declined) . '.');
               }

               return back()->with('success', 'Selected Employees Has Been Approved !.');


    }



}
<?php

namespace App\Http\Controllers;

use App\Exports\SalaryBankExport;
use App\Exports\SalaryCategoryExport;
use App\Exports\SalaryExport;
use App\Http\Requests\InvoiceToPayrollSearchRequest;
use App\Http\Requests\SalariesUploadRequest;
use App\Models\Salary;
use App\Models\InvoiceData;
use App\Models\Receipt;
use App\Exports\FilteredQueryExport;
use App\Support\PayrollMonth;
use App\Support\PayPriority;
use App\Http\Controllers\Concerns\SearchesDates;
use App\Http\Requests\StoreSalaryRequest;
use App\Http\Requests\UpdateSalaryRequest;
use Illuminate\Http\Request;
use App\Models\Bank;
use App\Models\Client;
use App\Models\Department;
use App\Models\employee;
use App\Models\Field;
use App\Models\Invoice;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SalaryImport;
use App\Models\category;
use App\Models\PaymentInfo;
use App\Models\SalaryTopUps;
use Maatwebsite\Excel\Excel as MaatwebsiteExcel;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Sum;
use Illuminate\Database\Eloquent\Builder;

use function PHPUnit\Framework\isEmpty;

class SalaryController extends Controller
{
    use SearchesDates;

    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // KPI cards: ONE grouped query instead of loading every employee + 7 count queries.
        // Rows for the table are served by employeesData() (server-side DataTable).
        $activeByField = employee::query()
            ->where('status', 'Active')->where('ho_status', 'approved')
            ->selectRaw('field_id, COUNT(*) as cnt')->groupBy('field_id')
            ->pluck('cnt', 'field_id');

        $employeeAccra = (int) ($activeByField[1] ?? 0);
        $employeeBotwe = (int) ($activeByField[2] ?? 0);
        $employeeTema = (int) ($activeByField[3] ?? 0);
        $employeeTakoradi = (int) ($activeByField[4] ?? 0);
        $employeeKoforidua = (int) ($activeByField[5] ?? 0);
        $employeeKumasi = (int) ($activeByField[6] ?? 0);
        $employeeShyhills = (int) ($activeByField[7] ?? 0);

        $fields = Field::orderBy('name')->get(['id', 'name']);

        return view('salaries.index', compact('employeeAccra', 'employeeBotwe', 'employeeTema', 'employeeTakoradi', 'employeeKoforidua', 'employeeKumasi', 'employeeShyhills', 'fields'));
    }

    /* ------------------------------------------------------------------
     | "Add to salaries" list (salaries.index) - server-side DataTable
     | Column indexes below MUST match the columns[] order in the view.
     * ------------------------------------------------------------------ */

    private const PAYROLL_EMP_TEXT_COLUMNS = [
        3  => 'employees.name',
        4  => 'employees.gender',
        5  => 'employees.phone_number',
        7  => 'departments.name',
        8  => 'roles.name',
        9  => 'fields.name',
        11 => 'employees.location',
        12 => 'employees.payment_type',
        13 => 'banks.name',
    ];

    private const PAYROLL_EMP_ORDER_COLUMNS = [
        2  => 'employees.id',
        3  => 'employees.name',
        4  => 'employees.gender',
        5  => 'employees.phone_number',
        6  => 'employees.date_of_joining',
        7  => 'departments.name',
        8  => 'roles.name',
        9  => 'fields.name',
        10 => 'clients.name',
        11 => 'employees.location',
        12 => 'employees.payment_type',
        13 => 'banks.name',
        16 => 'employees.basic_salary',
        17 => 'employees.allowances',
    ];

    /** Latest payment_infos row per employee (hasOne without ordering could duplicate rows in a join). */
    private function latestPaymentInfoSub()
    {
        return DB::table('payment_infos')->selectRaw('employee_id, MAX(id) as id')->groupBy('employee_id');
    }

    /** Active + approved employees, joined for display, with their payroll status for $month. */
    private function payrollEmployeeBase(Carbon $month)
    {
        [$start, $end] = PayrollMonth::span($month);

        return employee::query()
            ->select([
                'employees.id', 'employees.name', 'employees.gender', 'employees.phone_number',
                'employees.date_of_joining', 'employees.location', 'employees.payment_type',
                'employees.tax_button', 'employees.ssnit_button', 'employees.basic_salary', 'employees.allowances',
                'employees.field_id',
                'departments.name as department_name', 'roles.name as role_name', 'fields.name as field_name',
                'clients.name as client_name', 'clients.business_name as client_business_name',
                'banks.name as bank_name',
            ])
            ->selectSub(
                Salary::query()->select('payment_status')
                    ->whereColumn('salaries.employee_id', 'employees.id')
                    ->whereBetween('salaries.salary_month', [$start, $end])
                    ->limit(1),
                'payroll_status'
            )
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->leftJoin('roles', 'roles.id', '=', 'employees.role_id')
            ->leftJoin('fields', 'fields.id', '=', 'employees.field_id')
            ->leftJoin('clients', 'clients.id', '=', 'employees.client_id')
            ->leftJoinSub($this->latestPaymentInfoSub(), 'lpi', 'lpi.employee_id', '=', 'employees.id')
            ->leftJoin('payment_infos', 'payment_infos.id', '=', 'lpi.id')
            ->leftJoin('banks', 'banks.id', '=', 'payment_infos.bank_id')
            ->where('employees.status', 'Active')
            ->where('employees.ho_status', 'approved');
    }

    /**
     * Filters shared by the DataTable AND by store()'s "select all matching" mode,
     * so both always resolve exactly the same employees.
     *
     * @param array<int,string> $columnValues column index => search text
     */
    private function payrollEmployeeFilters($query, Carbon $month, string $global, array $columnValues, ?string $payrollFilter, ?string $fieldId): void
    {
        [$start, $end] = PayrollMonth::span($month);
        $inPayroll = fn ($q) => $q->select(DB::raw(1))->from('salaries')
            ->whereColumn('salaries.employee_id', 'employees.id')
            ->whereBetween('salaries.salary_month', [$start, $end]);

        if ($payrollFilter === 'added') {
            $query->whereExists($inPayroll);
        } elseif ($payrollFilter === 'not_added') {
            $query->whereNotExists($inPayroll);
        }

        if ($fieldId !== null && ctype_digit($fieldId)) {
            $query->where('employees.field_id', (int) $fieldId);
        }

        if ($global !== '') {
            $term = $this->likeTerm($global);
            // Match IDs only when the input looks like one ("45", "FWSS 45", "#45"), not digits inside names.
            $digits = preg_match('/^\s*(FWSS\s*|#)?\d+\s*$/i', $global) ? preg_replace('/\D/', '', $global) : '';
            $query->where(function ($q) use ($term, $digits) {
                if ($digits !== '') {
                    $q->orWhere('employees.id', (int) $digits); // exact id
                }
                foreach (['employees.name', 'employees.phone_number', 'departments.name', 'roles.name', 'fields.name',
                          'clients.name', 'clients.business_name', 'employees.location', 'employees.payment_type', 'banks.name'] as $col) {
                    $q->orWhere($col, 'like', $term);
                }
            });
        }

        foreach ($columnValues as $index => $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }
            $index = (int) $index;

            switch (true) {
                case $index === 2:
                    $this->whereIdMatches($query, 'employees.id', $value);
                    break;
                case $index === 6:
                    $this->whereDateMatches($query, 'employees.date_of_joining', $value);
                    break;
                case $index === 10:
                    $term = $this->likeTerm($value);
                    $query->where(fn ($q) => $q->where('clients.name', 'like', $term)->orWhere('clients.business_name', 'like', $term));
                    break;
                case $index === 14 || $index === 15:
                    $flag = $index === 14 ? 'employees.tax_button' : 'employees.ssnit_button';
                    $word = strtolower($value);
                    if ($word === 'on') {
                        $query->where($flag, 'on');
                    } elseif ($word === 'off') {
                        $query->where(fn ($q) => $q->whereNull($flag)->orWhere($flag, '!=', 'on'));
                    }
                    break;
                case $index === 16 || $index === 17:
                    $this->whereNumberMatches($query, $index === 16 ? 'employees.basic_salary' : 'employees.allowances', $value);
                    break;
                case isset(self::PAYROLL_EMP_TEXT_COLUMNS[$index]):
                    $query->where(self::PAYROLL_EMP_TEXT_COLUMNS[$index], 'like', $this->likeTerm($value));
                    break;
            }
        }
    }

    /** DataTables columns[] -> [index => search text] (ColumnControl or standard search). */
    private function dtColumnValues(Request $request): array
    {
        $values = [];
        foreach ((array) $request->input('columns', []) as $index => $column) {
            $values[(int) $index] = $this->columnFilterValue($column);
        }

        return array_filter($values, fn ($v) => $v !== '');
    }

    /** Paginated employees for the "Add to salaries" DataTable. */
    public function employeesData(Request $request)
    {
        $month = PayrollMonth::parse($this->requestString($request, 'salary_month'));

        $query = $this->payrollEmployeeBase($month);
        $recordsTotal = (clone $query)->count('employees.id');

        $this->payrollEmployeeFilters(
            $query, $month,
            trim((string) $this->requestString($request, 'search.value')),
            $this->dtColumnValues($request),
            $this->requestString($request, 'payroll_filter'),
            $this->requestString($request, 'field_id')
        );
        $recordsFiltered = (clone $query)->count('employees.id');

        // How many of the filtered rows "Select all matching" would actually add (not yet in this month).
        [$start, $end] = PayrollMonth::span($month);
        $selectable = (clone $query)->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('salaries')
            ->whereColumn('salaries.employee_id', 'employees.id')
            ->whereBetween('salaries.salary_month', [$start, $end]))->count('employees.id');

        $orderColumn = (int) $request->input('order.0.column', 2);
        $orderDir = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy(self::PAYROLL_EMP_ORDER_COLUMNS[$orderColumn] ?? 'employees.id', $orderDir)
            ->orderBy('employees.id');

        $rows = $query
            ->offset(max(0, (int) $request->input('start', 0)))
            ->limit(max(1, min(2000, (int) $request->input('length', 25))))
            ->get();

        $badge = fn ($on) => $on === 'on'
            ? '<span class="badge bg-label-dark">on</span>'
            : '<span class="badge bg-label-danger">OFF</span>';
        $statusClass = ['pending' => 'bg-label-info', 'approved' => 'bg-label-success', 'hold' => 'bg-label-warning', 'rejected' => 'bg-label-danger'];

        $data = $rows->map(fn ($e) => [
            'id' => $e->id,
            'in_payroll' => $e->payroll_status !== null,
            'row_number' => '',
            'employee_id' => 'FWSS ' . $e->id,
            'name' => e(strtoupper((string) $e->name)),
            'gender' => e($e->gender),
            'phone_number' => e($e->phone_number),
            'date_of_joining' => $e->date_of_joining ? Carbon::parse($e->date_of_joining)->format('d M Y') : '',
            'department' => e($e->department_name),
            'role' => e($e->role_name),
            'field' => e($e->field_name),
            'client' => e(trim($e->client_name . ' ' . $e->client_business_name)),
            'location' => e($e->location),
            'payment_type' => e($e->payment_type),
            'bank' => e($e->bank_name),
            'tax' => $badge($e->tax_button),
            'ssnit' => $badge($e->ssnit_button),
            'basic_salary' => number_format((float) $e->basic_salary, 2),
            'allowances' => number_format((float) $e->allowances, 2),
            'payroll' => $e->payroll_status === null
                ? '<span class="text-muted">Not added</span>'
                : '<span class="badge ' . ($statusClass[$e->payroll_status] ?? 'bg-label-secondary') . '">Added &middot; ' . e($e->payroll_status) . '</span>',
        ])->all();

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'selectable' => $selectable,
            'data' => $data,
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //

        // foreach ($salaries as $salary) {
        //     echo $salary->paymentInfo ? $salary->paymentInfo->ssnit_number .'<br>' : 'No Payment Info';
        // }
        return view('salaries.create_view');
    }


    /**
     * Show the view for creating a new resource.
     */
    public function CreateSalaries (Request $request)
    {
        // dd($request->all());
         $month = Carbon::parse($request->month);
        // dd($month);

        $salaries =  Salary::whereIn('payment_status', ['pending', 'rejected'])->whereBetween('salary_month', PayrollMonth::span($month))->get();
        $salariesAccra =  Salary::where('field_id', 1)->whereIn('payment_status',  ['pending', 'rejected'])->whereBetween('salary_month', PayrollMonth::span($month))->get();
        $salariesAccraSum = $salariesAccra->sum('cost_to_company');
        $salariesAccraCount = $salariesAccra->count();
        // dd( $salariesAccra->sum('cost_to_company'));

        $salariesBotwe =  Salary::where('field_id', 2)->whereIn('payment_status',  ['pending', 'rejected'])->whereBetween('salary_month', PayrollMonth::span($month))->get();
        $salariesBotweSum = $salariesBotwe->sum('cost_to_company');
        $salariesBotweCount = $salariesBotwe->count();

        $salariesTema =  Salary::where('field_id', 3)->whereIn('payment_status',  ['pending', 'rejected'])->whereBetween('salary_month', PayrollMonth::span($month))->get();
        $salariesTemaSum = $salariesTema->sum('cost_to_company');
        $salariesTemaCount = $salariesTema->count();

        $salariesTakoradi =  Salary::where('field_id', 4)->whereIn('payment_status',  ['pending', 'rejected'])->whereBetween('salary_month', PayrollMonth::span($month))->get();
        $salariesTakoradiSum = $salariesTakoradi->sum('cost_to_company');
        $salariesTakoradiCount = $salariesTakoradi->count();

        $salariesKoforidua =  Salary::where('field_id', 5)->whereIn('payment_status',  ['pending', 'rejected'])->whereBetween('salary_month', PayrollMonth::span($month))->get();
        $salariesKoforiduaSum = $salariesKoforidua->sum('cost_to_company');
        $salariesKoforiduaCount = $salariesKoforidua->count();  

        $salariesKumasi =  Salary::where('field_id', 6)->whereIn('payment_status',  ['pending', 'rejected'])->whereBetween('salary_month', PayrollMonth::span($month))->get();
        $salariesKumasiSum = $salariesKumasi->sum('cost_to_company');
        $salariesKumasiCount = $salariesKumasi->count();    

        $salariesShyhills =  Salary::where('field_id', 7)->whereIn('payment_status',  ['pending', 'rejected'])->whereBetween('salary_month', PayrollMonth::span($month))->get();
        $salariesShyhillsSum = $salariesShyhills->sum('cost_to_company');
        $salariesShyhillsCount = $salariesShyhills->count();    
        // // dd($salaries->paymentInfo());
        return view('salaries.create', compact('salaries', 'salariesAccraSum', 'salariesAccraCount', 'salariesBotweSum', 'salariesBotweCount', 'salariesTemaSum', 'salariesTemaCount', 'salariesTakoradiSum', 'salariesTakoradiCount', 'salariesKoforiduaSum', 'salariesKoforiduaCount', 'salariesKumasiSum', 'salariesKumasiCount', 'salariesShyhillsSum', 'salariesShyhillsCount', 'month'));

    }

    /**
     * Display salaries transaction view.
     */
    public function salariesMonth(InvoiceToPayrollSearchRequest $request)
    {
        $month = PayrollMonth::parse($request->month);
        $span = PayrollMonth::span($month);
        $live = ['pending', 'approved'];
        $held = ['hold', 'rejected'];

        // Every query below is scoped to ONE calendar month of ONE year.
        $inMonth = fn () => Salary::query()->whereBetween('salary_month', $span);

        $categories = category::whereBetween('category_month', $span)->get();

        $groupedBankSalaries = $inMonth()->whereIn('payment_status', $live)->where('payment_type', 'Bank')
            ->whereIn('bank_id', Bank::pluck('id'))->groupBy('bank_id')->with('bank')
            ->get(['bank_id', DB::raw('SUM(gross_salary) as gross'), DB::raw('SUM(total_deductions) as deductions'), DB::raw('SUM(net_salary) as paid'), DB::raw('COUNT(*) as total_employees')]);

        $groupedCashkSalaries = $inMonth()->whereIn('payment_status', $live)->where('payment_type', 'Cash')
            ->whereIn('field_id', Field::pluck('id'))->groupBy('field_id')->with('field')
            ->get(['field_id', DB::raw('SUM(gross_salary) as gross'), DB::raw('SUM(total_deductions) as deductions'), DB::raw('SUM(net_salary) as paid'), DB::raw('COUNT(*) as total_employees')]);

        // Per-field totals for each deduction / earning type (one grouped query each, same shape as before).
        $byField = function (string $column, array $sums) use ($inMonth, $live) {
            $select = ['field_id', DB::raw('SUM(net_salary) as paid'), DB::raw('COUNT(*) as total_employees')];
            foreach ($sums as $alias => $col) {
                $select[] = DB::raw("SUM({$col}) as {$alias}");
            }

            return $inMonth()->whereIn('payment_status', $live)->where($column, '>', 0)
                ->whereNotNull('field_id')->groupBy('field_id')->with('field')->get($select);
        };

        $salariesTaxes = $byField('tax', ['tax' => 'tax']);
        $salariesPensions = $byField('ssnit_tobe_paid13_5', ['tier1' => 'ssnit_tier1_0_5', 'tier2' => 'ssnit_tier2_5', 'cont13' => 'ssnit_comp_cont_13', 'cont13_5' => 'ssnit_tobe_paid13_5']);
        $salariesOvertime = $byField('overtime', ['overtime' => 'overtime']);
        $salariesIOU = $byField('iou', ['iou' => 'iou']);
        $salariesBoots = $byField('boot', ['boot' => 'boot']);
        $salariesAbsent = $byField('absent', ['absent' => 'absent']);
        $salariesAmtdedstart = $byField('amnt_ded_cof_start_date', ['sDate_ded' => 'amnt_ded_cof_start_date']);
        $salariesOtherDed = $byField('other_deductions', ['odeduct' => 'other_deductions']);
        $salariesReprimand = $byField('reprimand', ['reprimand' => 'reprimand']);
        $salariesLoan = $byField('loan', ['loan' => 'loan']);

        // Summary cards only need 4 columns. The full 53-column master table is now served
        // page-by-page by salariesMonthData(), so we no longer hydrate every Salary model here.
        // payment_type is normalised so 'cash'/'Cash' stop being counted as different things.
        $salariesMaster = $inMonth()->toBase()->get([
            'field_id', 'payment_status', 'net_salary',
            DB::raw("CASE LOWER(TRIM(payment_type)) WHEN 'cash' THEN 'Cash' WHEN 'bank' THEN 'Bank' ELSE payment_type END as payment_type"),
        ]);

        $clientTotals = fn (array $statuses, ?array $clientIds = null) => $inMonth()->whereIn('payment_status', $statuses)
            ->when($clientIds !== null, fn ($q) => $q->whereIn('client_id', $clientIds))
            ->whereNotNull('client_id')->groupBy('client_id')
            // The per-client tables show field, invoice totals/status and invoiced guards: load them ONCE,
            // already limited to this month, instead of 4-5 queries per client row in the view.
            ->with(['client.field', 'client.invoices' => fn ($q) => $q->whereBetween('invoice_month', $span), 'client.invoices.invoice_data']);

        $salariesClients = $clientTotals($live)->get(['client_id', DB::raw('SUM(net_salary) as paid'), DB::raw('COUNT(*) as total_employees')]);
        $salariesClientsHold = $clientTotals($held)->get(['client_id', DB::raw('SUM(net_salary) as paid'), DB::raw('COUNT(*) as total_employees')]);

        // Categories A-D: one loop instead of four copy-pasted blocks, and receipts/guards are
        // summed in SQL (previously 4 receipt queries + 1 invoice_data query PER invoice).
        $categoryVars = [];
        foreach (['A', 'B', 'C', 'D'] as $L) {
            $clientIds = category::where('name', "Category {$L}")->whereBetween('category_month', $span)
                ->pluck('client_id')->filter()->unique()->values()->all();

            $invoices = Invoice::whereIn('client_id', $clientIds)->whereBetween('invoice_month', $span)->get();
            $invoiceIds = $invoices->pluck('id');

            $receipts = [];
            if ($invoices->isNotEmpty()) {
                $r = Receipt::whereIn('invoice_id', $invoiceIds)->selectRaw(
                    'COALESCE(SUM(cash_amount),0) as cash, COALESCE(SUM(momo_amount),0) as momo, COALESCE(SUM(transfer_amount),0) as transfer, COALESCE(SUM(cheque_amount),0) as cheque'
                )->first();
                // Same shape the view already reads: collect($x['cash'])->flatten()->sum()
                $receipts = ['cash' => [(float) $r->cash], 'momo' => [(float) $r->momo], 'transfer' => [(float) $r->transfer], 'cheque' => [(float) $r->cheque]];
            }

            $liveRows = $inMonth()->whereIn('payment_status', $live)->whereIn('client_id', $clientIds);

            $categoryVars["client{$L}"] = $clientTotals($live, $clientIds)->get(['client_id', DB::raw('SUM(net_salary) as net_salary'), DB::raw('COUNT(employee_id) as total_employees')]);
            $categoryVars["client{$L}Hold"] = $clientTotals($held, $clientIds)->get(['client_id', DB::raw('SUM(net_salary) as net_salary'), DB::raw('COUNT(employee_id) as total_employees')]);
            $categoryVars["client{$L}Invoices"] = $invoices;
            $categoryVars["client{$L}Receipts"] = $receipts;
            $categoryVars["client{$L}InvoicesGuards"] = (float) InvoiceData::whereIn('invoice_id', $invoiceIds)->sum('quantity');
            $categoryVars["client{$L}Cash"] = (clone $liveRows)->where('payment_type', 'Cash')->get(['id', 'net_salary']);
            $categoryVars["client{$L}Bank"] = (clone $liveRows)->where('payment_type', 'Bank')->get(['id', 'net_salary']);
        }

        $topUpSalaries = SalaryTopUps::with(['salary.employee', 'salary.field', 'salary.client', 'user1', 'user2'])
            ->whereBetween('salary_month', $span)->get();

        $fields = Field::orderBy('name')->get(['id', 'name']);

        return view('salaries.salariesmonth', $categoryVars + compact(
            'topUpSalaries', 'salariesAbsent', 'salariesAmtdedstart', 'salariesOtherDed', 'salariesReprimand', 'salariesLoan',
            'salariesClients', 'salariesClientsHold', 'groupedBankSalaries', 'groupedCashkSalaries', 'salariesTaxes', 'salariesPensions',
            'month', 'salariesMaster', 'salariesOvertime', 'salariesIOU', 'salariesBoots', 'categories', 'fields'
        ));
    }




    /* ------------------------------------------------------------------
     | Master salaries table (salaries.salariesmonth) - server-side DataTable
     | Column indexes MUST match the columns[] order in the view.
     * ------------------------------------------------------------------ */

    /** Money columns, in view order, starting at column index 22. */
    private const MASTER_MONEY_COLUMNS = [
        'basic_salary', 'allowances', 'airtime_allowance', 'overtime', 'reimbursements', 'transport_allowance',
        'ssnit_tier2_5', 'ssnit_tier2_5d', 'tax', 'ssnit_tier1_0_5', 'welfare', 'maintenance', 'absent', 'boot',
        'iou', 'hostel', 'insurance', 'reprimand', 'scouter', 'raincoat', 'meal', 'loan', 'walkin',
        'amnt_ded_cof_start_date', 'other_deductions', 'gross_salary', 'total_deductions', 'net_salary',
        'ssnit_comp_cont_13', 'ssnit_tobe_paid13_5', 'cost_to_company',
    ];
    private const MASTER_MONEY_OFFSET = 22;

    private const MASTER_TEXT_COLUMNS = [
        2  => 'salaries.payment_status',
        3  => 'salaries.hold_reason',
        4  => 'cat.names',
        8  => 'employees.name',
        9  => 'departments.name',
        10 => 'roles.name',
        11 => 'fields.name',
        12 => 'employees.worker_type',
        14 => 'salaries.location',
        15 => 'inv.statuses',
        16 => 'payment_infos.ssnit_number',
        17 => 'payment_infos.tin_number',
        18 => 'salaries.payment_type',
        19 => 'banks.name',
        20 => 'salaries.branch',
        21 => 'salaries.account_number',
    ];

    private const MASTER_ORDER_COLUMNS = [
        2  => 'salaries.payment_status',
        4  => 'cat.names',
        5  => 'salaries.id',
        7  => 'salaries.employee_id',
        8  => 'employees.name',
        9  => 'departments.name',
        10 => 'roles.name',
        11 => 'fields.name',
        12 => 'employees.worker_type',
        13 => 'clients.name',
        14 => 'salaries.location',
        18 => 'salaries.payment_type',
        19 => 'banks.name',
    ];

    private function masterBase(Carbon $month)
    {
        $span = PayrollMonth::span($month);

        // Category and invoice status are pre-aggregated per client ONCE for the month,
        // replacing a categories loop + an invoice query for every row in the old view.
        $categorySub = DB::table('categories')->whereBetween('category_month', $span)
            ->selectRaw('client_id, GROUP_CONCAT(DISTINCT name ORDER BY name SEPARATOR ", ") as names')
            ->groupBy('client_id');
        $invoiceSub = DB::table('invoices')->whereBetween('invoice_month', $span)
            ->selectRaw('client_id, GROUP_CONCAT(DISTINCT status ORDER BY status SEPARATOR ", ") as statuses')
            ->groupBy('client_id');

        $moneyColumns = array_map(fn ($c) => "salaries.{$c}", self::MASTER_MONEY_COLUMNS);

        return Salary::query()
            ->select(array_merge([
                'salaries.id', 'salaries.salary_month', 'salaries.employee_id', 'salaries.client_id', 'salaries.location',
                'salaries.payment_status', 'salaries.status2', 'salaries.approval_date', 'salaries.hold_reason',
                'salaries.payment_type', 'salaries.branch', 'salaries.account_number',
                'salaries.pay_priority', 'salaries.pay_priority_reason',
                'employees.name as employee_name', 'employees.worker_type',
                'departments.name as department_name', 'roles.name as role_name', 'fields.name as field_name',
                'clients.name as client_name', 'clients.business_name as client_business_name',
                'banks.name as bank_name', 'approver.name as approver_name',
                'payment_infos.ssnit_number', 'payment_infos.tin_number',
                'cat.names as category_names', 'inv.statuses as invoice_statuses',
            ], $moneyColumns))
            ->leftJoin('employees', 'employees.id', '=', 'salaries.employee_id')
            ->leftJoin('departments', 'departments.id', '=', 'salaries.department_id')
            ->leftJoin('roles', 'roles.id', '=', 'salaries.role_id')
            ->leftJoin('fields', 'fields.id', '=', 'salaries.field_id')
            ->leftJoin('clients', 'clients.id', '=', 'salaries.client_id')
            ->leftJoin('banks', 'banks.id', '=', 'salaries.bank_id')
            ->leftJoin('users as approver', 'approver.id', '=', 'salaries.user_id2')
            ->leftJoinSub($this->latestPaymentInfoSub(), 'lpi', 'lpi.employee_id', '=', 'salaries.employee_id')
            ->leftJoin('payment_infos', 'payment_infos.id', '=', 'lpi.id')
            ->leftJoinSub($categorySub, 'cat', 'cat.client_id', '=', 'salaries.client_id')
            ->leftJoinSub($invoiceSub, 'inv', 'inv.client_id', '=', 'salaries.client_id')
            ->whereBetween('salaries.salary_month', $span);
    }

    /** Toolbar filters + global search + per-column search, shared by data() and export(). */
    private function masterFilters($query, Request $request): void
    {
        $status = $this->requestString($request, 'status');
        if (in_array($status, ['pending', 'approved', 'hold', 'rejected'], true)) {
            $query->where('salaries.payment_status', $status);
        } elseif ($status === 'outstanding') {
            $query->whereIn('salaries.payment_status', ['pending', 'hold', 'rejected']);
        }

        // Payment priority: urgent | priority | flagged (either).
        $priority = $this->requestString($request, 'priority');
        if (isset(PayPriority::FILTERS[$priority])) {
            $query->where('salaries.pay_priority', PayPriority::FILTERS[$priority]);
        } elseif ($priority === 'flagged') {
            $query->where('salaries.pay_priority', '>', PayPriority::NORMAL);
        }

        $type = strtolower((string) $this->requestString($request, 'payment_type'));
        if (in_array($type, ['bank', 'cash'], true)) {
            $query->whereRaw('LOWER(TRIM(salaries.payment_type)) = ?', [$type]);
        }

        $field = $this->requestString($request, 'field_id');
        if ($field !== null && ctype_digit($field)) {
            $query->where('salaries.field_id', (int) $field);
        }

        $category = $this->requestString($request, 'category');
        if (in_array($category, ['Category A', 'Category B', 'Category C', 'Category D'], true)) {
            $query->where('cat.names', 'like', $this->likeTerm($category));
        } elseif ($category === 'none') {
            $query->whereNull('cat.names');
        }

        $global = trim((string) $this->requestString($request, 'search.value'));
        if ($global !== '') {
            $term = $this->likeTerm($global);
            // Match IDs only when the input looks like one ("45", "FWSS 45", "#45"), not digits inside names.
            $digits = preg_match('/^\s*(FWSS\s*|#)?\d+\s*$/i', $global) ? preg_replace('/\D/', '', $global) : '';
            $query->where(function ($q) use ($term, $digits) {
                if ($digits !== '') {
                    $q->orWhere('salaries.employee_id', (int) $digits)->orWhere('salaries.id', (int) $digits); // exact id
                }
                foreach (['employees.name', 'clients.name', 'clients.business_name', 'fields.name', 'roles.name',
                          'salaries.location', 'salaries.payment_status', 'banks.name', 'salaries.account_number', 'cat.names'] as $col) {
                    $q->orWhere($col, 'like', $term);
                }
            });
        }

        foreach ($this->dtColumnValues($request) as $index => $value) {
            switch (true) {
                case $index === 5:
                    $this->whereIdMatches($query, 'salaries.id', $value);
                    break;
                case $index === 7:
                    $this->whereIdMatches($query, 'salaries.employee_id', $value);
                    break;
                case $index === 13:
                    $term = $this->likeTerm($value);
                    $query->where(fn ($q) => $q->where('clients.name', 'like', $term)->orWhere('clients.business_name', 'like', $term));
                    break;
                case $index >= self::MASTER_MONEY_OFFSET && isset(self::MASTER_MONEY_COLUMNS[$index - self::MASTER_MONEY_OFFSET]):
                    $this->whereNumberMatches($query, 'salaries.' . self::MASTER_MONEY_COLUMNS[$index - self::MASTER_MONEY_OFFSET], $value);
                    break;
                case isset(self::MASTER_TEXT_COLUMNS[$index]):
                    $query->where(self::MASTER_TEXT_COLUMNS[$index], 'like', $this->likeTerm($value));
                    break;
            }
        }
    }

    private function masterOrder($query, Request $request): void
    {
        // No column chosen (the page's default): payment priority first, then name.
        if ($request->input('order.0.column') === null) {
            $query->orderByDesc('salaries.pay_priority')->orderBy('employees.name')->orderBy('salaries.id');
            return;
        }

        $column = (int) $request->input('order.0.column', 8);
        $dir = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $sortable = self::MASTER_ORDER_COLUMNS;
        foreach (self::MASTER_MONEY_COLUMNS as $i => $money) {
            $sortable[self::MASTER_MONEY_OFFSET + $i] = "salaries.{$money}";
        }

        $query->orderBy($sortable[$column] ?? 'employees.name', $dir)->orderBy('salaries.id'); // unique tiebreaker
    }

    /** Paginated master salaries for one month. */
    public function salariesMonthData(Request $request)
    {
        $month = PayrollMonth::parse($this->requestString($request, 'month'));

        $query = $this->masterBase($month);
        $recordsTotal = (clone $query)->count('salaries.id');

        $this->masterFilters($query, $request);

        // Totals of the FILTERED set, for the summary strip above the table.
        $totals = (clone $query)->reorder()->toBase()->select(DB::raw(
            'COUNT(*) as rows_count, COALESCE(SUM(salaries.gross_salary),0) as gross, COALESCE(SUM(salaries.total_deductions),0) as deductions,
             COALESCE(SUM(salaries.net_salary),0) as net, COALESCE(SUM(salaries.cost_to_company),0) as ctc,
             COUNT(CASE WHEN salaries.pay_priority = 2 THEN 1 END) as urgent_total,
             COUNT(CASE WHEN salaries.pay_priority = 2 AND salaries.payment_status <> \'approved\' THEN 1 END) as urgent_unpaid,
             COUNT(CASE WHEN salaries.pay_priority = 2 AND salaries.payment_status IN (\'hold\', \'rejected\') THEN 1 END) as urgent_held,
             COUNT(CASE WHEN salaries.pay_priority = 1 THEN 1 END) as priority_total,
             COUNT(CASE WHEN salaries.pay_priority = 1 AND salaries.payment_status <> \'approved\' THEN 1 END) as priority_unpaid,
             COUNT(CASE WHEN salaries.pay_priority = 1 AND salaries.payment_status IN (\'hold\', \'rejected\') THEN 1 END) as priority_held'
        ))->first();
        $recordsFiltered = (int) $totals->rows_count;

        $this->masterOrder($query, $request);

        $rows = $query
            ->offset(max(0, (int) $request->input('start', 0)))
            ->limit(max(1, min(1000, (int) $request->input('length', 50))))
            ->get();

        $canEdit = Auth::user()?->hasRole(['Finance Manager']) ?? false;
        $badgeClass = ['pending' => 'bg-label-info', 'hold' => 'bg-label-warning', 'rejected' => 'bg-label-danger', 'approved' => 'bg-label-success'];

        $data = $rows->map(function ($s) use ($canEdit, $badgeClass) {
            $status = '<span class="badge ' . ($badgeClass[$s->payment_status] ?? 'bg-label-secondary') . '">' . e($s->payment_status) . '</span>';
            if (in_array($s->payment_status, ['pending', 'approved'], true)) {
                $details = array_filter([$s->status2, $s->approval_date?->format('d M Y'), $s->approver_name]);
                if ($details) {
                    $status .= '<br><small>' . implode('<br>', array_map('e', $details)) . '</small>';
                }
            }

            $actions = '<div class="dropdown"><button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></button><div class="dropdown-menu">'
                . '<a class="dropdown-item" href="' . e(url('salaries/' . $s->id)) . '"><i class="icon-base bx bxs-bullseye"></i> view</a>'
                . '<a class="dropdown-item" href="' . e(url('salaries/' . $s->id . '/edit')) . '"><i class="icon-base bx bx-edit-alt me-1"></i> Edit</a>'
                . '</div></div>';

            $row = [
                'id' => $s->id,
                'hold_reason_raw' => (string) $s->hold_reason,
                'can_edit' => $canEdit,
                'action' => $actions,
                'payment_status' => $status,
                'category' => e($s->category_names),
                'salary_id' => $s->id,
                'salary_month' => $s->salary_month?->format('F, Y'),
                'employee_id' => 'FWSS ' . $s->employee_id,
                'name' => PayPriority::badge($s->pay_priority, $s->pay_priority_reason) . e(strtoupper((string) $s->employee_name)),
                'department' => e($s->department_name),
                'role' => e($s->role_name),
                'field' => e($s->field_name),
                'worker_type' => e($s->worker_type),
                'client' => e(trim($s->client_name . ' ' . $s->client_business_name)),
                'location' => e($s->location),
                'invoice_status' => $s->invoice_statuses === null
                    ? '<span class="badge bg-label-danger">no invoice</span>'
                    : e($s->invoice_statuses),
                'ssnit_number' => e($s->ssnit_number),
                'tin_number' => e($s->tin_number),
                'payment_type' => e($s->payment_type),
                'bank' => e($s->bank_name),
                'branch' => e($s->branch),
                'account_number' => e($s->account_number),
            ];
            foreach (self::MASTER_MONEY_COLUMNS as $money) {
                $row[$money] = $s->{$money} === null ? '' : number_format((float) $s->{$money}, 2);
            }

            return $row;
        })->all();

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'totals' => [
                'gross' => (float) $totals->gross,
                'deductions' => (float) $totals->deductions,
                'net' => (float) $totals->net,
                'ctc' => (float) $totals->ctc,
            ],
            'priority' => [
                'urgent' => ['total' => (int) $totals->urgent_total, 'unpaid' => (int) $totals->urgent_unpaid, 'held' => (int) $totals->urgent_held],
                'priority' => ['total' => (int) $totals->priority_total, 'unpaid' => (int) $totals->priority_unpaid, 'held' => (int) $totals->priority_held],
            ],
            'data' => $data,
        ]);
    }

    /** Excel export of EVERY master row matching the current filters (not just the visible page). */
    public function salariesMonthExport(Request $request)
    {
        $month = PayrollMonth::parse($this->requestString($request, 'month'));

        $query = $this->masterBase($month);
        $this->masterFilters($query, $request);
        $this->masterOrder($query, $request);

        $headings = array_merge([
            'Salary ID', 'Salary Month', 'Employee ID', 'Name', 'Pay Priority', 'Payment Status', 'Hold Reason', 'Category', 'Department',
            'Role', 'Field', 'Emp. Type', 'Client', 'Location', 'Invoice Status', 'SSNIT No.', 'TIN No.', 'Payment Type',
            'Bank', 'Branch', 'Account No.',
        ], self::MASTER_MONEY_COLUMNS);

        $export = new FilteredQueryExport($query, $headings, function ($s) {
            $row = [
                $s->id, $s->salary_month?->format('Y-m'), 'FWSS ' . $s->employee_id, $s->employee_name,
                $s->pay_priority ? PayPriority::LABELS[(int) $s->pay_priority] : '', $s->payment_status,
                $s->hold_reason, $s->category_names, $s->department_name, $s->role_name, $s->field_name, $s->worker_type,
                trim($s->client_name . ' ' . $s->client_business_name), $s->location, $s->invoice_statuses ?? 'no invoice',
                $s->ssnit_number, $s->tin_number, $s->payment_type, $s->bank_name, $s->branch, $s->account_number,
            ];
            foreach (self::MASTER_MONEY_COLUMNS as $money) {
                $row[] = (float) $s->{$money}; // numbers, so Excel can sum them
            }

            return $row;
        });

        return $export->download('Master-Salaries-' . $month->format('Y-m') . '-' . now()->format('His') . '.xlsx');
    }


        /**
         * Calculate the total number of guards from a collection of invoices.
         *
         * @param iterable $invoices  Collection or array of Invoice models
         * @return int  Total number of guards across all invoices
         */
        public function totalInvoiceGuards($invoices)
        {
            $guards = [];
            foreach($invoices as $invoice) {
                foreach($invoice->invoice_data as $data) {
                    $guards[] = $data->quantity;
                }
            }
            return array_sum($guards);

        }


    public function transactionSalary()
    {
        //
        // $salaries =  Salary::where('payment_status', 'approved')->get();
        // $salariesAccra =  Salary::where('field_id', 1)->where('payment_status', 'approved')->get();
        // $salariesAccraSum = $salariesAccra->sum('cost_to_company');
        // $salariesAccraCount = $salariesAccra->count();
        // // dd( $salariesAccra->sum('cost_to_company'));

        // $salariesBotwe =  Salary::where('field_id', 2)->where('payment_status', 'approved')->get();
        // $salariesBotweSum = $salariesBotwe->sum('cost_to_company');
        // $salariesBotweCount = $salariesBotwe->count();

        // $salariesTema =  Salary::where('field_id', 3)->where('payment_status', 'approved')->get();
        // $salariesTemaSum = $salariesTema->sum('cost_to_company');
        // $salariesTemaCount = $salariesTema->count();

        // $salariesTakoradi =  Salary::where('field_id', 4)->where('payment_status', 'approved')->get();
        // $salariesTakoradiSum = $salariesTakoradi->sum('cost_to_company');
        // $salariesTakoradiCount = $salariesTakoradi->count();

        // $salariesKoforidua =  Salary::where('field_id', 5)->where('payment_status', 'approved')->get();
        // $salariesKoforiduaSum = $salariesKoforidua->sum('cost_to_company');
        // $salariesKoforiduaCount = $salariesKoforidua->count();  

        // $salariesKumasi =  Salary::where('field_id', 6)->where('payment_status', 'approved')->get();
        // $salariesKumasiSum = $salariesKumasi->sum('cost_to_company');
        // $salariesKumasiCount = $salariesKumasi->count();    

        // $salariesShyhills =  Salary::where('field_id', 7)->where('payment_status', 'approved')->get();
        // $salariesShyhillsSum = $salariesShyhills->sum('cost_to_company');
        // $salariesShyhillsCount = $salariesShyhills->count(); 
        // return view('salaries.transaction');
        // return view('salaries.transaction', compact('salaries', 'salariesAccraSum', 'salariesAccraCount', 'salariesBotweSum', 'salariesBotweCount', 'salariesTemaSum', 'salariesTemaCount', 'salariesTakoradiSum', 'salariesTakoradiCount', 'salariesKoforiduaSum', 'salariesKoforiduaCount', 'salariesKumasiSum', 'salariesKumasiCount', 'salariesShyhillsSum', 'salariesShyhillsCount'));
        return view('salaries.transaction');

    }


    /**
     * Pick a month to display 
     */
    public function BulkCashStore(Request $request)
    {
        // The Hold / Top-up buttons are only rendered for Finance Managers; enforce it server-side too.
        abort_unless(Auth::user()?->hasRole(['Finance Manager']), 403, 'Only a Finance Manager can hold salaries or add top ups.');

        $action = $request->input('action_type');
        $salaryIds = collect((array) $request->input('salary', []))->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($salaryIds->isEmpty()) {
            return back()->with('error', 'No Employee salary selected.');
        }

        if ($action === 'topup') {
            $salaries = Salary::whereIn('id', $salaryIds)->get(['id', 'salary_month', 'field_id', 'payment_status']);
            $notApproved = $salaries->where('payment_status', '!=', 'approved')->pluck('id');
            // Never create a second top-up for the same salary.
            $alreadyTopped = SalaryTopUps::whereIn('salary_id', $salaries->pluck('id'))->pluck('salary_id');
            $eligible = $salaries->where('payment_status', 'approved')->whereNotIn('id', $alreadyTopped->all());

            DB::transaction(function () use ($eligible) {
                foreach ($eligible as $salary) {
                    SalaryTopUps::create([
                        'salary_id' => $salary->id,
                        'salary_month' => $salary->salary_month,
                        'field_id' => $salary->field_id,
                        'user_id' => Auth::id(),
                        'status' => 'pending',
                    ]);
                }
                Salary::whereIn('id', $eligible->pluck('id'))->update(['status2' => 'Topup Added']);
            });

            $response = $eligible->isEmpty() ? back() : back()->with('success', $eligible->count() . ' salary(ies) added for Top Ups.');
            if ($notApproved->isNotEmpty()) {
                $response->with('primary', 'Cant add topup for these salaries unless after approval: ' . $notApproved->implode(', '));
            }
            if ($alreadyTopped->isNotEmpty()) {
                $response->with('warning', 'Already have a top up (skipped): ' . $alreadyTopped->implode(', '));
            }

            return $response;
        }

        // Hold
        $reasons = (array) $request->input('hold_reason', []);
        DB::transaction(function () use ($salaryIds, $reasons) {
            foreach (Salary::whereIn('id', $salaryIds)->get() as $salary) {
                $reason = trim((string) ($reasons[$salary->id] ?? ''));
                $salary->payment_status = 'hold';
                $salary->user_id1 = Auth::id();
                $salary->hold_reason = $reason !== '' ? mb_substr($reason, 0, 255) : 'No reason provided';
                $salary->save();
            }
        });

        return back()->with('success', 'Employees salaries have been Held: ' . $salaryIds->implode(', '));
    }


    /**
     * Pick a month to display 
     */
    public function BulkCash()
    {
        return view('salaries.BulkCash');
    }


    /**
     * Display bulk cash payment for a month.
     */
    public function BulkCashMonth(Request $request)
    {
        $month = PayrollMonth::parse($request->month);

        // The old query chained ->orwhere() without grouping, which returned failed/Pending
        // rows from EVERY month. The status conditions are now grouped under the month filter.
        $salaries = Salary::where('payment_type', 'Cash')
            ->whereBetween('salary_month', PayrollMonth::span($month))
            ->whereIn('status1', ['Bulk Cash', 'failed', 'Pending'])
            ->get();

        return view('salaries.BulkCashView', compact('salaries'));
    }


    // public function BulkCashMonthHistory(Request $request)
    // {
    // //    dd($request->all());
    //     $month = Carbon::parse($request->month);
    
    //    $salaries = Salary::where('status1', 'success')->whereBetween('salary_month', PayrollMonth::span($month))->get();
    // //    dd($salaries);
    //    foreach($salaries as $salary)
    //     {
    //         $hubtelIDs[] = $salary->hubtel_id;
    //     }

    //     $hubtel = DB::table('hubtel')->whereIn('id', $hubtelIDs)->get();
    //     $data = json_decode($hubtel, true);
    //     // dd($salaries, $data);

    //    return view('salaries.BulkCashView', compact('salaries', 'data'));
       
    // }


    /**
     * Display salaries bank month view.
     */
    public function bankMonth($bank_id, $month)
    {
         $month = Carbon::parse($month);

        $categories = category::whereBetween('category_month', PayrollMonth::span($month))->get();
        // foreach($categories as $category)
        // {
        //     echo   $category->name . ' - ' . $category->client_id . '<br>';

        // }
        // dd($categories);
        // get all from salaries where payment type is bank and is equal to incoming bank_id and month is in current month
        $bank = Bank::findOrfail($bank_id);
        $BankSalariesAll = Salary::with('employee')->whereBetween('salary_month', PayrollMonth::span($month))->where('payment_type', 'Bank')->where('bank_id', $bank_id)->get();

        $BankSalaries =  $BankSalariesAll->whereIn('payment_status', ['pending', 'approved'])
            ->sortBy([['pay_priority', 'desc'], fn ($a, $b) => strcmp((string) $a->employee?->name, (string) $b->employee?->name)])->values(); // payment priority first
       
       
        $BankSalariespending =  $BankSalariesAll->where('payment_status', 'pending');
        $BankSalariesapproved =  $BankSalariesAll->where('payment_status', 'approved');
        $BankSalarieshold =  $BankSalariesAll->where('payment_status', 'hold');
        // dd( $BankSalaries); 
        return view('salaries.bankmonth', compact('BankSalaries', 'BankSalarieshold','BankSalariespending', 'BankSalariesAll','BankSalariesapproved','bank', 'month', 'categories'));
    }


        /**
     * Display salaries bank month view.
     */
    public function bankholdMonth($bank_id, $month)
    {
         $month = Carbon::parse($month);
        // dd($month);
        // get all from salaries where payment type is bank and is equal to incoming bank_id and month is in current month
        $bank = Bank::findOrfail($bank_id);
        $BankSalaries = Salary::whereBetween('salary_month', PayrollMonth::span($month))->whereIn('payment_status', ['hold','rejected'])->where('payment_type', 'Bank')->where('bank_id', $bank_id)->get();
        // dd( $BankSalaries); 
        return view('salaries.holdbankmonth', compact('BankSalaries', 'bank', 'month'));
    }



    /**
     * Display salaries Cash month view.
     */
    public function cashMonth($field_id, $month)
    {
         $month = Carbon::parse($month);
        // get all cash salaries where field office is field_id and month is incoming month
        $field = Field::findOrfail($field_id);
        // dd($field->name, $month);
        $categories = Category::whereBetween('category_month', PayrollMonth::span($month))->get();

        $CashSalaries = Salary::with('employee')->whereBetween('salary_month', PayrollMonth::span($month))->whereIn('payment_status', ['pending', 'approved'])->where('payment_type', 'Cash')->where('field_id', $field_id)->get()
            ->sortBy([['pay_priority', 'desc'], fn ($a, $b) => strcmp((string) $a->employee?->name, (string) $b->employee?->name)])->values(); // payment priority first
        $CashSalariesAll = Salary::whereBetween('salary_month', PayrollMonth::span($month))->where('payment_type', 'Cash')->where('field_id', $field_id)->get();
        $CashSalariespending = Salary::whereBetween('salary_month', PayrollMonth::span($month))->where('payment_status', 'pending')->where('payment_type', 'Cash')->where('field_id', $field_id)->get();
        $CashSalariesapproved = Salary::whereBetween('salary_month', PayrollMonth::span($month))->where('payment_status', 'approved')->where('payment_type', 'Cash')->where('field_id', $field_id)->get();
        $CashSalarieshold = Salary::whereBetween('salary_month', PayrollMonth::span($month))->whereIn('payment_status', ['hold', 'rejected'])->where('payment_type', 'Cash')->where('field_id', $field_id)->get();
        // dd($CashSalaries);
        return view('salaries.cashmonth', compact('CashSalaries', 'CashSalariesAll', 'CashSalariespending',  'CashSalariesapproved', 'CashSalarieshold','field', 'month','categories'));

    }


    /**
     * Display salaries Cash HOLD month view.
     */
    public function cashholdMonth($field_id, $month)
    {
         $month = Carbon::parse($month);
        // get all cash salaries where field office is field_id and month is incoming month
        $field = Field::findOrfail($field_id);
        // dd($field->name, $month);
        $HoldSalaries = Salary::whereBetween('salary_month', PayrollMonth::span($month))->whereIn('payment_status', ['hold','rejected'])->where('payment_type', 'Cash')->where('field_id', $field_id)->get();
        // dd($CashSalaries);
        return view('salaries.holdcashmonth', compact('HoldSalaries', 'field', 'month'));

    }



    /**
     * Display salaries Taxes for a month.
     */
    public function TaxMonth($field_id, $month)
    {
         $month = Carbon::parse($month);

        // dd($field_id, $month);
        $field = Field::findOrfail($field_id);
        $salariesTaxes = Salary::whereBetween('salary_month', PayrollMonth::span($month))->whereIn('payment_status', ['pending', 'approved'])->where('tax', '>', 0)->where('field_id', $field_id)->get();
        return view('salaries.taxmonth', compact('salariesTaxes', 'field', 'month'));
    }


        /**
     * Display salaries Pensions for a month.
     */
    public function PensionMonth($field_id, $month)
    {
         $month = Carbon::parse($month);
        
        // dd($field_id, $month);
        $field = Field::findOrfail($field_id);
        $salariesPensions = Salary::whereBetween('salary_month', PayrollMonth::span($month))->whereIn('payment_status', ['pending', 'approved'])->where('ssnit_tobe_paid13_5', '>', 0)->where('field_id', $field_id)->get();
        return view('salaries.pensionmonth', compact('salariesPensions', 'field', 'month'));
    }


            /**
     * Display salaries Overtime for a month.
     */
    public function OvertimeMonth($field_id, $month)
    {
         $month = Carbon::parse($month);
        
        // dd($field_id, $month);
        $field = Field::findOrfail($field_id);
        $salariesOvertime = Salary::whereBetween('salary_month', PayrollMonth::span($month))->whereIn('payment_status', ['pending', 'approved'])->where('overtime', '>', 0)->where('field_id', $field_id)->get();
        return view('salaries.overtimemonth', compact('salariesOvertime', 'field', 'month'));
    }


            /**
     * Display salaries Iou for a month.
     */
    public function IouMonth($field_id, $month)
    {
         $month = Carbon::parse($month);
        
        // dd($field_id, $month);
        $field = Field::findOrfail($field_id);
        $salariesIou = Salary::whereBetween('salary_month', PayrollMonth::span($month))->whereIn('payment_status', ['pending', 'approved'])->where('iou', '>', 0)->where('field_id', $field_id)->get();
        return view('salaries.ioumonth', compact('salariesIou', 'field', 'month'));
    }


    /**
     * Display salaries Boot for a month.
     */
    public function BootMonth($field_id, $month)
    {
         $month = Carbon::parse($month);
        
        // dd($field_id, $month);
        $field = Field::findOrfail($field_id);
        $salariesBoots = Salary::whereBetween('salary_month', PayrollMonth::span($month))->whereIn('payment_status', ['pending', 'approved'])->where('boot', '>', 0)->where('field_id', $field_id)->get();
        return view('salaries.bootmonth', compact('salariesBoots', 'field', 'month'));
    }


        /**
     * Display salaries Boot for a month.
     */
    public function AbsentMonth($field_id, $month)
    {
         $month = Carbon::parse($month);
        
        // dd($field_id, $month);
        $field = Field::findOrfail($field_id);
        $salariesAbsent = Salary::whereBetween('salary_month', PayrollMonth::span($month))->whereIn('payment_status', ['pending', 'approved'])->where('absent', '>', 0)->where('field_id', $field_id)->get();
        return view('salaries.absentmonth', compact('salariesAbsent', 'field', 'month'));
    }



        /**
     * Display salaries Boot for a month.
     */
    public function sDateMonth($field_id, $month)
    {
         $month = Carbon::parse($month);
        
        // dd($field_id, $month);
        $field = Field::findOrfail($field_id);
        $salariessDate = Salary::whereBetween('salary_month', PayrollMonth::span($month))->whereIn('payment_status', ['pending', 'approved'])->where('amnt_ded_cof_start_date', '>', 0)->where('field_id', $field_id)->get();
        return view('salaries.sDatemonth', compact('salariessDate', 'field', 'month'));
    }



        /**
     * Display salaries Boot for a month.
     */
    public function oDedMonth($field_id, $month)
    {
         $month = Carbon::parse($month);
        
        // dd($field_id, $month);
        $field = Field::findOrfail($field_id);
        $salariesoDed = Salary::whereBetween('salary_month', PayrollMonth::span($month))->whereIn('payment_status', ['pending', 'approved'])->where('other_deductions', '>', 0)->where('field_id', $field_id)->get();
        return view('salaries.oDedmonth', compact('salariesoDed', 'field', 'month'));
    }



        /**
     * Display salaries Boot for a month.
     */
    public function reprimandMonth($field_id, $month)
    {
         $month = Carbon::parse($month);
        
        // dd($field_id, $month);
        $field = Field::findOrfail($field_id);
        $salariesReprimand = Salary::whereBetween('salary_month', PayrollMonth::span($month))->whereIn('payment_status', ['pending', 'approved'])->where('reprimand', '>', 0)->where('field_id', $field_id)->get();
        return view('salaries.reprimandmonth', compact('salariesReprimand', 'field', 'month'));
    }


        /**
     * Display salaries Boot for a month.
     */
    public function loanMonth($field_id, $month)
    {
         $month = Carbon::parse($month);
        
        // dd($field_id, $month);
        $field = Field::findOrfail($field_id);
        $salariesLoan = Salary::whereBetween('salary_month', PayrollMonth::span($month))->whereIn('payment_status', ['pending', 'approved'])->where('loan', '>', 0)->where('field_id', $field_id)->get();
        return view('salaries.loanmonth', compact('salariesLoan', 'field', 'month'));
    }


        /**
     * Display salaries Client for a month.
     */
    public function ClientMonth($client_id, $month)
    {
         $month = Carbon::parse($month);
         $client = Client::findOrfail($client_id);
        
        // dd($client_id, $month);

        //  $CashSalaries = Salary::whereBetween('salary_month', PayrollMonth::span($month))->whereIn('payment_status', ['pending', 'approved'])->where('payment_type', 'Cash')->where('client_id', $client_id)->get();
        $ClientSalariesAll = Salary::whereBetween('salary_month', PayrollMonth::span($month))->where('client_id', $client_id)->get();
       
        $ClientSalariespending = Salary::whereBetween('salary_month', PayrollMonth::span($month))->where('payment_status', 'pending')->where('client_id', $client_id)->get();
        $ClientSalariesapproved = Salary::whereBetween('salary_month', PayrollMonth::span($month))->where('payment_status', 'approved')->where('client_id', $client_id)->get();
        $ClientSalarieshold = Salary::whereBetween('salary_month', PayrollMonth::span($month))->whereIn('payment_status', ['hold','rejected'])->where('client_id', $client_id)->get();
       


        $salariesClients = Salary::with('employee')->whereBetween('salary_month', PayrollMonth::span($month))->whereIn('payment_status', ['pending', 'approved'])->where('client_id', $client_id)->get()
            ->sortBy([['pay_priority', 'desc'], fn ($a, $b) => strcmp((string) $a->employee?->name, (string) $b->employee?->name)])->values(); // payment priority first

        // dd($salariesClients, $ClientSalarieshold, $ClientSalariesapproved, $ClientSalariespending, $ClientSalariesAll);
        return view('salaries.clientmonth', compact('ClientSalarieshold','ClientSalariesapproved','ClientSalariespending','ClientSalariesAll','salariesClients', 'client', 'month'));
    }


            /**
     * Display salaries Client Hold for a month.
     */
    public function ClientHoldMonth($client_id, $month)
    {
         $month = Carbon::parse($month);
         $client = Client::findOrfail($client_id);
        
        // dd($client_id, $month);

        $ClientSalarieshold = Salary::whereBetween('salary_month', PayrollMonth::span($month))->where('payment_status', 'hold')->where('client_id', $client_id)->get();
        $ClientSalariesrejected = Salary::whereBetween('salary_month', PayrollMonth::span($month))->where('payment_status', 'rejected')->where('client_id', $client_id)->get();
        $ClientSalariesHoldAll = Salary::whereBetween('salary_month', PayrollMonth::span($month))->whereIn('payment_status', ['hold','rejected'])->where('client_id', $client_id)->get();
       
        return view('salaries.clientholdmonth', compact('ClientSalarieshold','ClientSalariesrejected','ClientSalariesHoldAll', 'client', 'month'));
    }



    /**
     * Show the form for comparing invoices to payroll.
     */
    public function InvToParoll()
    {
        //

        return view('salaries.invpayroll');
    }


        /**
     * get all invoices and salaries for incoming month request.
     */
    public function InvToParollMonth(InvoiceToPayrollSearchRequest $request)
    {
        // dd($request->all()); 

        // get invoices for incoming month
        $month = Carbon::parse($request->month)->format('F, Y');
        // $invoices = Invoice::where('invoice_month', Carbon::parse($request->month)->format('Y-m-d'))->get();
        $invoices = Invoice::select('client_id', DB::raw('SUM(total) as total_invoice'))
                            ->groupBy('client_id')
                            ->where('invoice_month', Carbon::parse($request->month)->format('Y-m-d'))->get();

        $invoicesTotal = $invoices->sum('total_invoice');
        // $invoicesCount = $invoices->count();
        // dd( $invoices);

        // foreach($invoices as $invoice)
        //     {
        //         echo $invoice . "<br>";
        //     }
        $Accrainvoices = Invoice::whereRelation('client', 'field_id', '1')->where('invoice_month', Carbon::parse($request->month)->format('Y-m-d'))->get();
        $AccrainvoicesTotal = $Accrainvoices->sum('total');
        $AccrainvoicesCount = $Accrainvoices->count();
        // dd($Accrainvoices->count(), $Accrainvoices->sum('total'));

        $Botweinvoices = Invoice::whereRelation('client', 'field_id', '2')->where('invoice_month', Carbon::parse($request->month)->format('Y-m-d'))->get();
        $BotweinvoicesTotal = $Botweinvoices->sum('total');
        $BotweinvoicesCount = $Botweinvoices->count();

        $Temainvoices = Invoice::whereRelation('client', 'field_id', '3')->where('invoice_month', Carbon::parse($request->month)->format('Y-m-d'))->get();
        $TemainvoicesTotal = $Temainvoices->sum('total');
        $TemainvoicesCount = $Temainvoices->count();

        $Takoradinvoces = Invoice::whereRelation('client', 'field_id', '4')->where('invoice_month', Carbon::parse($request->month)->format('Y-m-d'))->get();
        $TakoradinvocesTotal = $Takoradinvoces->sum('total');
        $TakoradinvocesCount = $Takoradinvoces->count();
        $Koforiduainvoices = Invoice::whereRelation('client', 'field_id', '5')->where('invoice_month', Carbon::parse($request->month)->format('Y-m-d'))->get();
        $KoforiduainvoicesTotal = $Koforiduainvoices->sum('total');
        $KoforiduainvoicesCount = $Koforiduainvoices->count();  

        $Kumasinvoices = Invoice::whereRelation('client', 'field_id', '6')->where('invoice_month', Carbon::parse($request->month)->format('Y-m-d'))->get();
        $KumasinvoicesTotal = $Kumasinvoices->sum('total');             
        $KumasinvoicesCount = $Kumasinvoices->count();

        $Shyhillsinvoices = Invoice::whereRelation('client', 'field_id', '7')->where('invoice_month', Carbon::parse($request->month)->format('Y-m-d'))->get();
        $ShyhillsinvoicesTotal = $Shyhillsinvoices->sum('total');             
        $ShyhillsinvoicesCount = $Shyhillsinvoices->count();    



        // get salaries for incoming month
        $salaries = Salary::select('client_id', DB::raw('SUM(cost_to_company) as total_salary'))
                            ->groupBy('client_id')
                            ->where('salary_month', Carbon::parse($request->month)->startOfMonth()->format('Y-m-d H:i:s'))
                            ->get();
        $salariesTotal = $salaries->sum('total_salary');

        $Accrasalaries = Salary::select('client_id', DB::raw('SUM(cost_to_company) as total_salary'))
                        ->groupBy('client_id')
                        ->where('field_id', 1)
                        ->where('salary_month', Carbon::parse($request->month)->startOfMonth()->format('Y-m-d H:i:s'))
                        ->get();
        // dd( $Accrasalaries);
        $AccrasalariesTotal = $Accrasalaries->sum('total_salary');
        $AccrasalariesCount = $Accrasalaries->count();

        $Botwesalaries = Salary::select('client_id', DB::raw('SUM(cost_to_company) as total_salary'))   
                        ->groupBy('client_id')
                        ->where('field_id', 2)
                        ->where('salary_month', Carbon::parse($request->month)->startOfMonth()->format('Y-m-d H:i:s'))
                        ->get();    
        $BotwesalariesTotal = $Botwesalaries->sum('total_salary');
        $BotwesalariesCount = $Botwesalaries->count();

        $Temasalaries = Salary::select('client_id', DB::raw('SUM(cost_to_company) as total_salary'))   
                        ->groupBy('client_id')
                        ->where('field_id', 3)
                        ->where('salary_month', Carbon::parse($request->month)->startOfMonth()->format('Y-m-d H:i:s'))
                        ->get();
        $TemasalariesTotal = $Temasalaries->sum('total_salary');
        $TemasalariesCount = $Temasalaries->count();

        $Takoradisalaries = Salary::select('client_id', DB::raw('SUM(cost_to_company) as total_salary'))   
                        ->groupBy('client_id')
                        ->where('field_id', 4)
                        ->where('salary_month', Carbon::parse($request->month)->startOfMonth()->format('Y-m-d H:i:s'))
                        ->get();
        $TakoradisalariesTotal = $Takoradisalaries->sum('total_salary');
        $TakoradisalariesCount = $Takoradisalaries->count();


        $Koforiduasalaries = Salary::select('client_id', DB::raw('SUM(cost_to_company) as total_salary'))   
                        ->groupBy('client_id')
                        ->where('field_id', 5)
                        ->where('salary_month', Carbon::parse($request->month)->startOfMonth()->format('Y-m-d H:i:s'))
                        ->get();
        $KoforiduasalariesTotal = $Koforiduasalaries->sum('total_salary');
        $KoforiduasalariesCount = $Koforiduasalaries->count();

        $Kumasalaries = Salary::select('client_id', DB::raw('SUM(cost_to_company) as total_salary'))   
                        ->groupBy('client_id')
                        ->where('field_id', 6)
                        ->where('salary_month', Carbon::parse($request->month)->startOfMonth()->format('Y-m-d H:i:s'))
                        ->get();
        $KumasalariesTotal = $Kumasalaries->sum('total_salary');
        $KumasalariesCount = $Kumasalaries->count();

        $Shyhillssalaries = Salary::select('client_id', DB::raw('SUM(cost_to_company) as total_salary'))   
                        ->groupBy('client_id')
                        ->where('field_id', 7)
                        ->where('salary_month', Carbon::parse($request->month)->startOfMonth()->format('Y-m-d H:i:s'))
                        ->get();
        $ShyhillssalariesTotal = $Shyhillssalaries->sum('total_salary');
        $ShyhillssalariesCount = $Shyhillssalaries->count();  
      
        // dd($salaries);
        return view('salaries.invpayrollview', compact('month', 'invoices', 'AccrainvoicesTotal', 'AccrainvoicesCount', 'BotweinvoicesTotal', 'BotweinvoicesCount', 'TemainvoicesTotal', 'TemainvoicesCount', 'TakoradinvocesTotal', 'TakoradinvocesCount', 'KoforiduainvoicesTotal', 'KoforiduainvoicesCount', 'KumasinvoicesTotal', 'KumasinvoicesCount', 'ShyhillsinvoicesTotal', 'ShyhillsinvoicesCount',  'salaries',  'invoicesTotal', 'salariesTotal', 'AccrasalariesTotal', 'AccrasalariesCount', 'BotwesalariesTotal', 'BotwesalariesCount', 'TemasalariesTotal', 'TemasalariesCount', 'TakoradisalariesTotal', 'TakoradisalariesCount', 'KoforiduasalariesTotal', 'KoforiduasalariesCount', 'KumasalariesTotal', 'KumasalariesCount', 'ShyhillssalariesTotal', 'ShyhillssalariesCount'));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSalaryRequest $request)
    {
        // Rules live here (not in StoreSalaryRequest) because deleteMultiple() reuses that request class.
        $validated = $request->validate([
            'salary_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])(-\d{2})?$/'],
            'select_all' => ['nullable', 'boolean'],
            'employees' => ['array', 'required_without:select_all'],
            'employees.*' => ['integer'],
            'excluded' => ['array'],
            'excluded.*' => ['integer'],
        ], [
            'employees.required_without' => 'No employee selected to add to salaries.',
            'salary_month.regex' => 'Select a valid salary month.',
        ]);

        $month = PayrollMonth::parse($validated['salary_month']);
        [$start, $end] = PayrollMonth::span($month);

        // Resolve the target employees. Both modes go through payrollEmployeeBase(), so a
        // tampered request can never add terminated / unapproved employees.
        $base = $this->payrollEmployeeBase($month);
        $notEligible = collect();
        if ($request->boolean('select_all')) {
            // "Select all N matching": re-apply exactly the filters the user saw on screen.
            $this->payrollEmployeeFilters(
                $base, $month,
                trim((string) $request->input('filter_search', '')),
                array_map('strval', (array) $request->input('filter_columns', [])),
                'not_added', // only ever adds people not yet in this month's payroll
                $this->requestString($request, 'filter_field_id')
            );
            $ids = $base->pluck('employees.id')->diff($validated['excluded'] ?? []);
        } else {
            $requested = collect($validated['employees'])->map(fn ($id) => (int) $id)->unique();
            $ids = $base->whereIn('employees.id', $requested)->pluck('employees.id');
            $notEligible = $requested->diff($ids);
        }

        if ($ids->isEmpty()) {
            return back()->with('error', 'No eligible employee selected to add to salaries.');
        }

        // ONE query for "already added this month" instead of one per employee.
        $alreadyProcessed = Salary::whereIn('employee_id', $ids)
            ->whereBetween('salary_month', [$start, $end])
            ->pluck('employee_id');
        $toAdd = $ids->diff($alreadyProcessed)->values();

        $employees = employee::with('paymentInfo')->whereIn('id', $toAdd)->get();

        // ONE query for "which clients were invoiced this month" (guards without an invoice are rejected).
        $invoicedClients = Invoice::whereIn('client_id', $employees->pluck('client_id')->filter()->unique())
            ->whereBetween('invoice_month', [$start, $end])
            ->distinct()->pluck('client_id')->flip();

        $now = now();
        $rows = $employees->map(fn ($employee) => [
            // Salary::insert() skips model events, so payment priority is set here from the snapshot.
            ...array_combine(['pay_priority', 'pay_priority_reason'], PayPriority::evaluate(
                $employee->client_id, $employee->field_id, $employee->location, $employee->gender
            )),
            'employee_id' => $employee->id,
            'salary_month' => $start,
            'field_id' => $employee->field_id,
            'department_id' => $employee->department_id,
            'role_id' => $employee->role_id,
            'client_id' => $employee->client_id,
            'location' => $employee->location,
            'payment_type' => $employee->payment_type,
            'account_number' => $employee->paymentInfo?->acc_number,
            'bank_id' => $employee->paymentInfo?->bank_id,
            'branch' => $employee->paymentInfo?->branch,
            'basic_salary' => $employee->basic_salary,
            'allowances' => $employee->allowances,
            'payment_status' => ((int) $employee->role_id === 7 && ! isset($invoicedClients[$employee->client_id])) ? 'rejected' : 'pending',
            'user_id' => Auth::id(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::transaction(function () use ($rows) {
            foreach ($rows->chunk(500) as $chunk) {
                Salary::insert($chunk->values()->all());
            }
        });

        $rejected = $rows->where('payment_status', 'rejected')->count();
        $message = $rows->count() . ' employee(s) added to ' . $month->format('F Y') . ' salaries.';
        if ($rejected) {
            $message .= " {$rejected} guard(s) were marked 'rejected' because their client has no invoice for the month.";
        }

        $response = $rows->isEmpty() ? back() : back()->with('success', $message);
        if ($alreadyProcessed->isNotEmpty()) {
            $response->with('warning', 'Already in this month\'s salaries (skipped): FWSS ' . $alreadyProcessed->implode(', FWSS '));
        }
        if ($notEligible->isNotEmpty()) {
            $response->with('error', 'Not active/approved (skipped): FWSS ' . $notEligible->implode(', FWSS '));
        }

        return $response;
    }



    /**
     * Calculating TAX on Basic Salary
     */
    public $next1Tax = 0;
    public $next2Tax = 0;
    public $next3Tax = 0;
    public $CummulativeTaxHold = [] ;

    public function taxEmployee($basic_salary)
    {
            $next1 = 110;
            $next2 = 130;
            // $next3 = 3166.67;
            $next1Tax = 0.00;
            $next2Tax = 0.00;
            $next3Tax = 0.00;


            $next1Tax = $this->next1Tax($basic_salary);
            // return $next1Tax[0]. " -- ". $next1Tax[1];
            if($next1Tax[0] <= 0 )
            {
                return 0;
            }elseif($next1Tax[0] < 5.5){
                return $next1Tax[0] ;
            }
            else{

                $next2Tax = $this->next2Tax($next1Tax[1]);
                // return $next2Tax[0] + $next1Tax[0] . " -- ". $next2Tax[1];
                if($next2Tax[1] < 0)
                {
                    return $next2Tax[0] + $next1Tax[0];
                }
                else
                {
                     
                    $next3Tax = $this->next3Tax($next2Tax[1]);

                    if($next3Tax[1] < 0)
                    {
                       
                        return $next3Tax[0] + $next2Tax[0] + $next1Tax[0];

                    }
                    else{

                        return $next3Tax[0] + $next2Tax[0] + $next1Tax[0] + ($next3Tax[1] * 0.25);

                    
                    }

                }

           
            }

          
    }


    public function next1Tax($basic_salary)
    {
            // $first = 490;
            $next1 = 110;
            $next1_rate = 0.05;
            $next1_amnt = 0;
            $next1_tax = 0;
            $balance = 0;

            // subtract 110 of the next1 and find 5% of that
            $next1_amnt = $basic_salary - 490;
           
            if($next1_amnt < $next1)
            {
                 $next1_tax =  $next1_amnt * $next1_rate;

                //  return $next1_tax;
            }else
                {
                  $balance =  $next1_amnt -  $next1;
                  $next1_tax =  $next1 * $next1_rate;
                }

            return [$next1_tax,  $balance ] ;

    }

    public function next2Tax($balance)
    {
            $next2 = 130;
            $next2_rate = 0.10;
            $next2_amnt = 0;
            $next2_tax = 0;


        $next2_amnt = $balance - 130;

        if ($balance < $next2)
            {
                $next2_tax =  $balance * $next2_rate;
            }
        else{
                // $balance1 =  $next2_amnt -  $next2;
                $next2_tax  =  $next2 * $next2_rate ;
            }
      
            // $this->CummulativeTaxHold[] =  $next2_tax;
            return  [$next2_tax ,$next2_amnt] ;
        
    }

    public function next3Tax($balance)
    {
            $next3 = 3166.67;
            $next3_rate = 0.175;
            $next3_amnt = 0;
            $next3_tax = 0;


        $next3_amnt = $balance - $next3;

        if ($balance < $next3)
            {
                $next3_tax =  $balance * $next3_rate;
            }
        else{
                // $balance1 =  $next2_amnt -  $next2;
                $next3_tax  =  $next3 * $next3_rate ;
            }
      
            // $this->CummulativeTaxHold[] =  $next2_tax;
            return  [$next3_tax ,$next3_amnt] ;
        
    }


    /**
     * Display the specified resource.
     */
    public function show(Salary $salary)
    {
        //
        // dd($salary);
        return view('salaries.show', compact('salary'));
    }


        /**
     * Display the specified resource.
     */
    public function printPayslip( $id)
    {
        //
        $salary = Salary::findOrFail($id);
        return view('salaries.print', compact('salary'));
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Salary $salary)
    {
        //
        // dd($salary);
        $banks = Bank::all();
        $clients = Client::all();
        $Fields = Field::all();
        
        return view('salaries.edit', compact('salary', 'clients', 'banks', 'Fields'));
    }


    /**
     * Upload salaries from Excel file.
     */
    public function uploadSalaries(SalariesUploadRequest $request)
    {

        $collection = (new SalaryImport)->toCollection( $request->file('excelFile'));
        // dd($collection);
        foreach ($collection[0] as $row) {
            // echo 'Updating salary for Employee ID: '. $row['employee_id']. ' -  ' . Carbon::createFromFormat('F, Y', $row['salary_month'])->startOfMonth()->format('Y-m-d H:i:s') . '<br>';   
        //  change date to start of month format Y-m-d H:i:s

            $salary = Salary::where('employee_id', $row['employee_id'])
                            ->where('salary_month', Carbon::createFromFormat('F, Y', $row['salary_month'])->startOfMonth()->format('Y-m-d H:i:s'))
                            ->first();
          
        //   echo 'Updating salary for Employees: '. $salary->employee->tax_button. " ---".$row['employee_id'] . " " .$row['basic_salary']. " / ". $row['allowances'] . " / ". $row['airtime_allowance'] . " / ". $row['reimbursements'] . " / ". $row['transport_allowance'] ." / ". $row['welfare'] ." / ". $row['maintenance'] ." / ". $row['absent'] ." / ". $row['boot'] ." / ". $row['iou'] ." / ". $row['hostel'] ." / ". $row['insurance'] ." / ". $row['reprimand'] ." / ". $row['scouter'] ." / ". $row['raincoat'] ." / ". $row['meal'] ." / ". $row['loan'] ." / ". $row['meal']  ." / ". $row['walkin'] ." / ". $row['amnt_ded_cof_start_date'] ." / ". $row['other_deductions'] .'<br>';   
            

                // Deduct Tax
                // $ssnitNumber = PaymentInfo::where('employee_id', $employee->id)->whereNotNull('ssnit_number')->get();
                $ssnit_tier2_5 = 0;
                $ssnit_tier1_0_5 = 0;
                $ssnit_13 = 0;
                $ssnit_13_5 = 0;

                $tax = 0;
                $employee_basic_taxAmount_minus_ssnt = 0;

                // CHECK IF THE PERSON HAS SSNIT BUTTON TURNED ON
                if(isset($salary->employee->ssnit_button) == "on")
                {
                    if($salary->employee?->ssnit_number !== '' && $salary->employee?->ssnit_number !== null)
                        {
                            // echo "Processing salary for Employee SSNIT ID: " . $employee->paymentInfo->ssnit_number . "<br><br>";

                            //  SSNIT transactions HERE
                            $ssnit_tier2_5 = $row['basic_salary'] * 0.05;
                            $ssnit_tier1_0_5 = $row['basic_salary'] * 0.005;
                            $ssnit5_5 = $row['basic_salary'] * 0.055;
                            $ssnit_13 = $row['basic_salary'] * 0.13;
                            $ssnit_13_5 = $row['basic_salary'] * 0.135;

                            $employee_basic_taxAmount_minus_ssnt = $row['basic_salary'] - $ssnit5_5  ;
                            // echo "Processing salary Tier 2 (5%) = " . $ssnit_tier2_5 .  " Tier 1 (0.5%) = ". $ssnit_tier1_0_5 . "  SSNIT (13%) = ".$ssnit_13. " SSNIT (13.5%) = ".  $ssnit_13_5   ."<br><br>";
                        }
                }

                 // CHECK IF THE PERSON HAS TAX BUTTON TURNED ON
                if(isset($salary->employee->tax_button) == "on")
                {
                    
                    if($row['basic_salary'] > 490.00 )
                    {
                        // TAX CALCULATIONS
                            // echo "Employee Basic salary = "  .$row['basic_salary'] ." ----/ TAX === ".  $this->taxEmployee($employee_basic_taxAmount_minus_ssnt)  ."<br>";
                        if($salary->employee?->ssnit_number !== '' && $salary->employee?->ssnit_number !== null && $employee_basic_taxAmount_minus_ssnt >= 0)
                            {
                            $tax = $this->taxEmployee($employee_basic_taxAmount_minus_ssnt) ;
                            // echo "Employee SSNIT Basic salary ---------------" .  " - " . " - "  .$row['basic_salary'] ."-------------- TAX = ".  $tax  ."<br>";

                            }else{
                            $tax = $this->taxEmployee($row['basic_salary']) ;
                            // echo "Employee NO SSNIT Basic salary -----------"  . " - " . " - "   .$row['basic_salary'] ."--------------- TAX = ".  $tax  ."<br>";
                            }                    
                    } 
                }




            $salary->basic_salary = $row['basic_salary'] ?? $salary->basic_salary ;
            $salary->allowances = $row['allowances'] ?? $salary->allowances ;
            $salary->airtime_allowance = $row['airtime_allowance'] ?? $salary->airtime_allowance ;
            $salary->overtime = $row['overtime'] ?? $salary->overtime ;
            $salary->reimbursements = $row['reimbursements'] ?? $salary->reimbursements ;
            $salary->transport_allowance = $row['transport_allowance'] ?? $salary->transport_allowance ;
           
            $salary->welfare = $row['welfare'] ?? $salary->welfare ;
            $salary->maintenance = $row['maintenance'] ?? $salary->maintenance ;
            $salary->absent = $row['absent'] ?? $salary->absent ;
            $salary->boot = $row['boot'] ?? $salary->boot ;                            
            $salary->iou = $row['iou'] ?? $salary->iou ;
            $salary->hostel = $row['hostel'] ?? $salary->hostel ;
            $salary->insurance = $row['insurance'] ?? $salary->insurance ;
            $salary->reprimand = $row['reprimand'] ?? $salary->reprimand ;          
            $salary->scouter = $row['scouter'] ?? $salary->scouter ;
            $salary->raincoat = $row['raincoat'] ?? $salary->raincoat ;
            $salary->meal = $row['meal'] ?? $salary->meal ;
            $salary->loan = $row['loan'] ?? $salary->loan ;
            $salary->walkin = $row['walkin'] ?? $salary->walkin ;
            $salary->amnt_ded_cof_start_date = $row['amnt_ded_cof_start_date'] ?? $salary->amnt_ded_cof_start_date ;
            $salary->other_deductions = $row['other_deductions'] ?? $salary->other_deductions ;

            // // WORK SSNIT
            $salary->ssnit_tier2_5d = $ssnit_tier2_5  ?? $salary->ssnit_tier2_5d ;
            $salary->ssnit_tier2_5 = $ssnit_tier2_5 ?? $salary->ssnit_tier2_5 ;
            $salary->ssnit_tier1_0_5 =  $ssnit_tier1_0_5 ?? $salary->ssnit_tier1_0_5 ;

            // // WORK TAX
            $salary->tax = $tax ??  $salary->tax;

            // // SUM GROSS, TOTAL DEDUCTTIONS AND NET SALARY
            $grossSalary = $row['basic_salary'] + $row['allowances'] + $row['airtime_allowance'] + $row['overtime'] + $row['reimbursements'] + $row['transport_allowance'] +  $ssnit_tier2_5;
            $totalDeduction = $tax + $ssnit_tier2_5 +  $row['welfare'] + $row['maintenance'] + $row['absent']  +  $row['boot'] +  $row['iou'] +  $row['hostel'] + $row['insurance'] + $row['reprimand'] + $row['scouter'] +  $row['raincoat'] +  $row['meal'] + $row['loan'] + $row['walkin'] + $row['amnt_ded_cof_start_date'] + $row['other_deductions'];
            $netSalay = $grossSalary - $totalDeduction ;
           
            $salary->gross_salary = $grossSalary ?? $salary->gross_salary ;
            $salary->total_deductions = $totalDeduction ?? $salary->total_deductions ;
            $salary->net_salary = $netSalay ?? $salary->net_salary ;

            // // WORK 
            $salary->ssnit_comp_cont_13 =  $ssnit_13 ?? $salary->ssnit_comp_cont_13 ;
            $salary->ssnit_tobe_paid13_5 = $ssnit_13_5 ?? $salary->ssnit_tobe_paid13_5 ;
            $salary->cost_to_company = $netSalay + $ssnit_13_5 ?? $salary->cost_to_company ;  
            $salary->user_id1 = Auth::id();

            $salary->save();    
            
            }

        return back()->with('success', 'Salaries uploaded and updated successfully.');
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSalaryRequest $request, Salary $salary)
    {
        //
        // dd($request->all()); 

        // $salary->update($request->all());
               // Deduct Tax
                // $ssnitNumber = PaymentInfo::where('employee_id', $employee->id)->whereNotNull('ssnit_number')->get();
                $ssnit_tier2_5 = 0;
                $ssnit_tier1_0_5 = 0;
                $ssnit_13 = 0;
                $ssnit_13_5 = 0;

                $tax = 0;
                $employee_basic_taxAmount_minus_ssnt = 0;

                // CHECK IF THE PERSON HAS SSNIT BUTTON TURNED ON
                if(isset($salary->employee->ssnit_button) == "on")
                {
                    if($salary->employee?->ssnit_number !== '' && $salary->employee?->ssnit_number !== null)
                        {
                            // echo "Processing salary for Employee SSNIT ID: " . $employee->paymentInfo->ssnit_number . "<br><br>";

                            //  SSNIT transactions HERE
                            $ssnit_tier2_5 = $request['basic_salary'] * 0.05;
                            $ssnit_tier1_0_5 = $request['basic_salary'] * 0.005;
                            $ssnit5_5 = $request['basic_salary'] * 0.055;
                            $ssnit_13 = $request['basic_salary'] * 0.13;
                            $ssnit_13_5 = $request['basic_salary'] * 0.135;

                            $employee_basic_taxAmount_minus_ssnt = $request['basic_salary'] - $ssnit5_5  ;
                            // echo "Processing salary Tier 2 (5%) = " . $ssnit_tier2_5 .  " Tier 1 (0.5%) = ". $ssnit_tier1_0_5 . "  SSNIT (13%) = ".$ssnit_13. " SSNIT (13.5%) = ".  $ssnit_13_5   ."<br><br>";
                        }
                }

                 // CHECK IF THE PERSON HAS TAX BUTTON TURNED ON
                if(isset($salary->employee->tax_button) == "on")
                {
                    
                    if($request['basic_salary'] > 490.00 )
                    {
                        // TAX CALCULATIONS
                            // echo "Employee Basic salary = "  .$request['basic_salary'] ." ----/ TAX === ".  $this->taxEmployee($employee_basic_taxAmount_minus_ssnt)  ."<br>";
                        if($salary->employee?->ssnit_number !== '' && $salary->employee?->ssnit_number !== null && $employee_basic_taxAmount_minus_ssnt >= 0)
                            {
                            $tax = $this->taxEmployee($employee_basic_taxAmount_minus_ssnt) ;
                            // echo "Employee with SSNIT Basic salary = ".$request['employee_id'] . " / "  .$request['basic_salary'] ." TAX = ".  $tax  ."<br>";

                            }else{
                            $tax = $this->taxEmployee($request['basic_salary']) ;
                            // echo "Employee WITHOUT SSNIT Basic salary = ".$request['employee_id'] . " / "  .$request['basic_salary'] ." TAX = ".  $tax  ."<br>";
                            }                    
                    } 
                }

            $salary->basic_salary = $request['basic_salary']  ;
            $salary->allowances = $request['allowances'] ;
            $salary->airtime_allowance = $request['airtime_allowance']  ;
            $salary->overtime = $request['overtime'];
            $salary->reimbursements = $request['reimbursements'] ;
            $salary->transport_allowance = $request['transport_allowance'] ;
           
            $salary->welfare = $request['welfare'] ;
            $salary->maintenance = $request['maintenance'] ;
            $salary->absent = $request['absent'] ;
            $salary->boot = $request['boot'] ;                            
            $salary->iou = $request['iou'] ;
            $salary->hostel = $request['hostel'] ;
            $salary->insurance = $request['insurance'] ;
            $salary->reprimand = $request['reprimand'] ;          
            $salary->scouter = $request['scouter'] ;
            $salary->raincoat = $request['raincoat'] ;
            $salary->meal = $request['meal'] ;
            $salary->loan = $request['loan'] ;
            $salary->walkin = $request['walkin'] ;
            $salary->amnt_ded_cof_start_date = $request['amnt_ded_cof_start_date'] ;
            $salary->other_deductions = $request['other_deductions'] ;

            // // WORK SSNIT
            $salary->ssnit_tier2_5d = $ssnit_tier2_5   ;
            $salary->ssnit_tier2_5 = $ssnit_tier2_5  ;
            $salary->ssnit_tier1_0_5 =  $ssnit_tier1_0_5 ;

            // // WORK TAX
            $salary->tax = $tax ??  $salary->tax;

            // // SUM GROSS, TOTAL DEDUCTTIONS AND NET SALARY
            $grossSalary = $request['basic_salary'] + $request['allowances'] + $request['airtime_allowance'] + $request['overtime'] + $request['reimbursements'] + $request['transport_allowance'] + $ssnit_tier2_5;
            $totalDeduction = $tax + $ssnit_tier2_5 +  $request['welfare'] + $request['maintenance'] + $request['absent']  +  $request['boot'] +  $request['iou'] +  $request['hostel'] + $request['insurance'] + $request['reprimand'] + $request['scouter'] +  $request['raincoat'] +  $request['meal'] + $request['loan'] + $request['walkin'] + $request['amnt_ded_cof_start_date'] + $request['other_deductions'];
            $netSalay = $grossSalary - $totalDeduction ;
           
            $salary->gross_salary = $grossSalary  ;
            $salary->total_deductions = $totalDeduction  ;
            $salary->net_salary = $netSalay ;

            // // WORK 
            $salary->ssnit_comp_cont_13 =  $ssnit_13  ;
            $salary->ssnit_tobe_paid13_5 = $ssnit_13_5 ;
            $salary->cost_to_company = $netSalay + $ssnit_13_5  ;

            // UPDATE PAYMENT DETAILS FOR A MONTH SALARY
            $salary->payment_type = $request['payment_type']  ;
            $salary->bank_id = $request['bank_id']  ;
            $salary->account_number = $request['account_number']  ;
            $salary->branch = $request['branch']  ;

            // UPDATE FIELD ID
            $salary->field_id = $request['field_id'] ;
            // UPDATE CLIENT ID
            $salary->client_id = $request['client_id'] ;
            // UPDATE LOCATION
            $salary->location = $request['location'] ;
            $salary->user_id1 = Auth::id();

            $salary->save(); 

            // // UPDATE EMPLOYEE BASICS AND ALLOWA
            // $salary->employee->update([
            //     'basic_salary' => $request['basic_salary'],
            //     'allowances' => $request['allowances']
            // ]);


            return back()->with('success', 'Salary Updated Successfully');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Salary $salary)
    {
        //
        // dd($salary);
        // $salary->delete();
    }

    public function deleteMultiple(StoreSalaryRequest $request)
    {

        $action = $request->input('action_type') ?? $request->input('submit');
        $salaryIds = $request->input('salary', []);
        if (empty($salaryIds)) {
            return back()->with('error', 'No salaries selected');
        }

        if ($action === 'delete')
        {
            Salary::whereIn('id', $salaryIds)->delete();
            return back()->with('danger', 'Deleted salaries with IDs: ' . implode(', ', $salaryIds));
        }

       elseif ($action === 'approve')
        {
            // echo 'Approving';
                $salaries = Salary::findOrFail($salaryIds);
                // dd($salaries);
                foreach ($salaries as $salary) 
                {
                    $exists = Salary::where('id', $salary->id)
                                    ->where('payment_status', '!=', 'pending')
                                    ->exists();
                    if ($exists) {
                        $alreadyProcessed[] = $salary->employee?->name . " with Salary ID: " . $salary->id;
                        continue;
                    }

                    Salary::where('id', $salary->id)->update(['payment_status' => 'approved', 'approval_date' => now() ,'user_id2' => Auth::id()]);
                }
            //  dd(count($alreadyProcessed));
            if (!empty($alreadyProcessed))
                 {
                    return back()->with('error', 'Salaries with the IDs have already been Approved: '. implode(', ', $alreadyProcessed). ' The remaining have been Appoved ')  ;
                }
            return back()->with('success', 'Approved salaries with IDs: ' . implode(', ', $salaryIds));
        }
        elseif ($action === 'unapprove')
        {
            // echo 'Approving';
                $salaries = Salary::findOrFail($salaryIds);
                // dd($salaries);
                foreach ($salaries as $salary) 
                {
                    $exists = Salary::where('id', $salary->id)
                                    ->where('payment_status', '!=', 'approved')
                                    ->exists();
                    if ($exists) {
                        $alreadyProcessed[] = $salary->employee?->name . " with Salary ID: " . $salary->id;
                        continue;
                    }

                    Salary::where('id', $salary->id)->update(['payment_status' => 'pending', 'approval_date' => now() ,'user_id2' => Auth::id()]);
                }
            //  dd(count($alreadyProcessed));
            if (!empty($alreadyProcessed))
                 {
                    return back()->with('error', 'Salaries with the IDs have already been Approved: '. implode(', ', $alreadyProcessed). ' The remaining have been Appoved ')  ;
                }
            return back()->with('success', 'Approved salaries with IDs: ' . implode(', ', $salaryIds));
        }
        elseif ($action === 'hold')
            {
                        $salaries = Salary::findOrFail($salaryIds);
                    foreach ($salaries as $salary) 
                    {
                        $exists = Salary::where('id', $salary->id)
                                        ->where('payment_status', 'hold')
                                        ->exists();
                        if ($exists) {
                            $alreadyProcessed[] = $salary->employee?->name . " with Salary ID: " . $salary->id;
                            continue;
                        }

                        $salary->payment_status = 'hold';
                        $salary->user_id1 = Auth::id();
                        $salary->hold_reason = $request->hold_reason[$salary->id] ?? 'No reason provided';
                        $salary->save();
                        // Salary::where('id', $salary->id)->update(['payment_status' => 'hold', 'user_id1' => Auth::id()]);
                    }
                //  dd(count($alreadyProcessed));
                if (!empty($alreadyProcessed))
                    {
                        return back()->with('error', 'Salaries with the IDs have already been Held : '. implode(', ', $alreadyProcessed). ' The remaining have been Appoved ')  ;
                    }
                return back()->with('success', 'Moved salaries with IDs: ' . implode(', ', $salaryIds));
            }
        elseif ($action === 'main')
            {
                        // return "you are moving from hold to main";
                    $salaries = Salary::findOrFail($salaryIds);
                    foreach ($salaries as $salary) 
                    {
                        $exists = Salary::where('id', $salary->id)
                                        ->where('payment_status', 'pending')
                                        ->exists();
                        if ($exists) {
                            $alreadyProcessed[] = $salary->employee?->name . " with Salary ID: " . $salary->id;
                            continue;
                        }

                        Salary::where('id', $salary->id)->update(['payment_status' => 'pending', 'user_id1' => Auth::id()]);
                    }
                //  dd(count($alreadyProcessed));
                if (!empty($alreadyProcessed))
                    {
                        return back()->with('error', 'Salaries with the IDs have already been Moved to Main: '. implode(', ', $alreadyProcessed). ' The remaining have been Appoved ')  ;
                    }
                return back()->with('success', 'Moved salaries with IDs: ' . implode(', ', $salaryIds));
            }


    }


    /**
     * Get all guards with the client ID and Month
     */
    public function PayrollGuards ($client_id, $month)
    {

            // dd($client_id, $month);
        $date = Carbon::createFromFormat('F, Y',$month)->startOfMonth()->format('Y-m-d H:i:s');
        // dd($date);
        $salaries = Salary::where('salary_month', $date)->where('client_id', $client_id)->get();
        // dd($salaries);
        return view('salaries.invpayrollGuards', compact('salaries', 'month'));

    }


    public function exportMaster($date) 
    {
        $month = Carbon::createFromFormat('F, Y',$date)->startOfMonth()->format('Y-m-d H:i:s');
        // dd($month);
        return (new SalaryExport($month))->download('Master Salaries For '.$date.'.xlsx');
      
    }

    public function exportBank($date, $bank_id)
    {
        // dd($date, $bank_id);
        // GET SALARY MONTH
         $month = Carbon::parse($date)->format('F, Y');
        // $month = Carbon::createFromFormat('F, Y',$date)->startOfMonth()->format('Y-m-d H:i:s');
       
        // dd($month->month);
        //GET SALARY BANK
        $bank = Bank::findOrFail($bank_id);
        // dd($bank->name);

        return (new SalaryBankExport($date, $bank_id, [ $bank->name, $month]))->download( $bank->name.' Salaries For '.$month.'.xlsx'); 
    }


    public function exportCategory($date, $category)
    {
        // dd($date, $category);
         $month = Carbon::parse($date)->format('F, Y');

        return (new SalaryCategoryExport($date, $category))->download( $category.' Salaries For '.$month.'.xlsx'); 
    }


}

<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\Field;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExpenseController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        [$from, $to] = $this->resolveMonthRange($request);

        $fieldGroups = Field::whereNull('parent_field_id')
            ->whereRaw('LOWER(TRIM(name)) != ?', ['accra'])
            ->get();
        $headOffice  = Field::whereRaw('LOWER(TRIM(name)) = ?', ['accra'])->first();
        $fieldMap    = Field::all()->keyBy('id');

        $fieldTypes     = ExpenseType::field()->orderBy('name')->get();
        $corporateTypes = ExpenseType::corporate()->orderBy('name')->get();

        $totals = Expense::select('expense_type_id', 'field_id',
                DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('expense_type_id')
            ->whereBetween('expense_date', [$from, $to])
            ->groupBy('expense_type_id', 'field_id')
            ->get();

        $matrix = [];
        $masterTotals = [];
        foreach ($totals as $row) {
            $field   = $fieldMap[$row->field_id] ?? null;
            $groupId = $field?->parent_field_id ?? $row->field_id;

            $matrix[$row->expense_type_id][$groupId]['total'] =
                ($matrix[$row->expense_type_id][$groupId]['total'] ?? 0) + (float) $row->total;
            $matrix[$row->expense_type_id][$groupId]['cnt'] =
                ($matrix[$row->expense_type_id][$groupId]['cnt'] ?? 0) + (int) $row->cnt;

            $masterTotals[$row->expense_type_id]['total'] =
                ($masterTotals[$row->expense_type_id]['total'] ?? 0) + (float) $row->total;
            $masterTotals[$row->expense_type_id]['cnt'] =
                ($masterTotals[$row->expense_type_id]['cnt'] ?? 0) + (int) $row->cnt;
        }

        return view('expenses.index', compact(
            'fieldGroups', 'headOffice', 'fieldTypes', 'corporateTypes', 'matrix', 'masterTotals', 'from', 'to'
        ));
    }

    public function list(Request $request)
    {
        $groupId = $request->query('field_group_id');
        $fieldOffices = $this->officeOptionsForGroup($groupId);

        [$from, $to] = $this->resolveMonthRange($request, allowEmpty: true);

        $expenses = Expense::with(['type', 'field'])
            ->when($groupId, fn ($q) => $q->whereIn('field_id', $fieldOffices->pluck('id')))
            ->when($request->filled('field_id'), fn ($q) => $q->where('field_id', $request->field_id))
            ->when($request->filled('expense_type_id'), fn ($q) => $q->where('expense_type_id', $request->expense_type_id))
            ->when($from && $to, fn ($q) => $q->whereBetween('expense_date', [$from, $to]))
            ->latest('expense_date')
            ->paginate(25)
            ->withQueryString();

        return view('expenses.list', compact('expenses'));
    }

    public function create(Request $request)
    {
        $groupId = $request->query('field_group_id');
        $fieldOffices   = $this->officeOptionsForGroup($groupId);
        $fieldTypes     = ExpenseType::field()->orderBy('name')->get();
        $corporateTypes = ExpenseType::corporate()->orderBy('name')->get();

        $typicalTypesByField = $this->typicalTypesByField();
        $selectedFieldId = $request->query('field_id', $groupId ?? Auth::user()->field_id);

        return view('expenses.create', compact(
            'fieldOffices', 'fieldTypes', 'corporateTypes', 'typicalTypesByField', 'selectedFieldId'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description'      => 'required|string|max:1000',
            'amount'           => 'required|numeric|min:0.01',
            'expense_date'     => 'required|date|before_or_equal:today',
            'field_id'         => 'required|exists:fields,id',
            'expense_type_id'  => 'required_without:new_expense_type',
            'new_expense_type' => 'required_without:expense_type_id|nullable|string|max:255',
            'image'            => 'nullable|image|max:5120',
        ]);

        $expenseTypeId = $this->resolveExpenseTypeId($request, (int) $validated['field_id']);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('expenses', 'public')
            : null;

        Expense::create([
            'description'     => $validated['description'],
            'amount'          => $validated['amount'],
            'expense_date'    => $validated['expense_date'],
            'field_id'        => $validated['field_id'],
            'expense_type_id' => $expenseTypeId,
            'image'           => $imagePath,
            // stage 1 = the creator's own submission, auto-confirmed
            'user_1'   => Auth::id(),
            'status_1' => 'approved',
            'date_1'   => now(),
        ]);

        return redirect()->route('expense.index')->with('success', 'Expense recorded.');
    }

    public function edit(Expense $expense)
    {
        abort_unless($expense->isEditableBy(Auth::user()), 403,
            'This expense can no longer be edited -- Branch Approval has already started.');

        $groupId = $expense->field?->groupId();
        $fieldOffices   = $this->officeOptionsForGroup($groupId);
        $fieldTypes     = ExpenseType::field()->orderBy('name')->get();
        $corporateTypes = ExpenseType::corporate()->orderBy('name')->get();

        $typicalTypesByField = $this->typicalTypesByField();
        $selectedFieldId = $expense->field_id;

        return view('expenses.edit', compact(
            'expense', 'fieldOffices', 'fieldTypes', 'corporateTypes', 'typicalTypesByField', 'selectedFieldId'
        ));
    }

    public function update(Request $request, Expense $expense)
    {
        abort_unless($expense->isEditableBy(Auth::user()), 403,
            'This expense can no longer be edited -- Branch Approval has already started.');

        $validated = $request->validate([
            'description'      => 'required|string|max:1000',
            'amount'           => 'required|numeric|min:0.01',
            'expense_date'     => 'required|date|before_or_equal:today',
            'field_id'         => 'required|exists:fields,id',
            'expense_type_id'  => 'required_without:new_expense_type',
            'new_expense_type' => 'required_without:expense_type_id|nullable|string|max:255',
            'image'            => 'nullable|image|max:5120',
        ]);

        $expenseTypeId = $this->resolveExpenseTypeId($request, (int) $validated['field_id']);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('expenses', 'public')
            : $expense->image;

        $expense->update([
            'description'     => $validated['description'],
            'amount'          => $validated['amount'],
            'expense_date'    => $validated['expense_date'],
            'field_id'        => $validated['field_id'],
            'expense_type_id' => $expenseTypeId,
            'image'           => $imagePath,
            'status_1' => 'approved', 'date_1' => now(), // re-confirmed by the edit
            'user_2' => null, 'status_2' => null, 'date_2' => null,
            'user_3' => null, 'status_3' => null, 'date_3' => null,
        ]);

        return redirect()->route('expense.index')->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        abort_unless($expense->isDeletableBy(Auth::user()), 403,
            'This expense can no longer be deleted -- Branch Approval has already started.');

        $expense->delete();

        return redirect()->back()->with('success', 'Expense deleted.');
    }

    /**
     * APPROVE / REJECT: acts on whichever stage (2 or 3) is currently
     * pending for this expense. Authorization is role-based via
     * Expense::canActOnStage() -- see the TODO in the model.
     */
    public function approve(Expense $expense)
    {
        $stage = $expense->currentStage();
        abort_if(is_null($stage), 404, 'There is no pending approval stage on this expense.');
        abort_unless($expense->canActOnStage(Auth::user(), $stage), 403,
            'You are not authorized to approve at this stage.');

        $expense->update([
            "user_{$stage}"   => Auth::id(),
            "status_{$stage}" => 'approved',
            "date_{$stage}"   => now(),
        ]);

        return back()->with('success', 'Expense approved.');
    }

    public function reject(Request $request, Expense $expense)
    {
        $stage = $expense->currentStage();
        abort_if(is_null($stage), 404, 'There is no pending approval stage on this expense.');
        abort_unless($expense->canActOnStage(Auth::user(), $stage), 403,
            'You are not authorized to reject at this stage.');

        $expense->update([
            "user_{$stage}"   => Auth::id(),
            "status_{$stage}" => 'rejected',
            "date_{$stage}"   => now(),
        ]);

        return back()->with('success', 'Expense rejected.');
    }

    // -----------------------------------------------------------------
    // Shared helpers
    // -----------------------------------------------------------------

    private function officeOptionsForGroup(?int $groupId)
    {
        if ($groupId) {
            return Field::where('id', $groupId)->orWhere('parent_field_id', $groupId)->get();
        }

        // includes Accra now -- Head Office needs an entry point too
        return Field::all();
    }

    private function resolveMonthRange(Request $request, bool $allowEmpty = false): array
    {
        $fromMonth = $request->query('from_month');
        $toMonth   = $request->query('to_month');

        if ($allowEmpty && ! $fromMonth && ! $toMonth) {
            return [null, null];
        }

        $fromMonth = $fromMonth ?: now()->format('Y-m');
        $toMonth   = $toMonth ?: now()->format('Y-m');

        return [
            Carbon::createFromFormat('Y-m', $fromMonth)->startOfMonth(),
            Carbon::createFromFormat('Y-m', $toMonth)->endOfMonth(),
        ];
    }

    /**
     * Scope (field vs corporate) is derived from the OFFICE CHOSEN ON THE
     * FORM, not the logged-in user's own office -- a head-office user
     * filling this in on behalf of a field office should still get field
     * types, and vice versa.
     */
    private function resolveExpenseTypeId(Request $request, int $fieldId): int
    {
        if ($request->expense_type_id === 'new' || $request->filled('new_expense_type')) {
            $office = Field::find($fieldId);
            $scope = $office && strtolower(trim($office->name)) === 'accra' ? 'corporate' : 'field';

            $type = ExpenseType::firstOrCreate(
                ['name' => Str::of($request->new_expense_type)->trim()->title(), 'scope' => $scope],
                ['created_by' => Auth::id()]
            );

            return $type->id;
        }

        return (int) $request->expense_type_id;
    }

    private function typicalTypesByField()
    {
        return ExpenseType::field()
            ->with('fields:id')
            ->get()
            ->flatMap(fn ($type) => $type->fields->map(fn ($f) => [
                'field_id' => $f->id,
                'type_id'  => $type->id,
            ]))
            ->groupBy('field_id')
            ->map(fn ($rows) => $rows->pluck('type_id')->values());
    }
}
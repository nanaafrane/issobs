<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\Field;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExpenseController extends Controller
{
    /**
     * DASHBOARD: master type card + field-office cards + Head Office tab.
     */
    public function index()
    {
        $fieldOffices   = Field::where('name', '!=', 'Accra')->get();
        $fieldTypes     = ExpenseType::field()->orderBy('name')->get();
        $corporateTypes = ExpenseType::corporate()->orderBy('name')->get();

        $totals = Expense::select('expense_type_id', 'field_id',
                DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('expense_type_id')
            ->groupBy('expense_type_id', 'field_id')
            ->get();

        $matrix = [];
        $masterTotals = [];
        foreach ($totals as $row) {
            $matrix[$row->expense_type_id][$row->field_id] = [
                'total' => (float) $row->total,
                'cnt'   => (int) $row->cnt,
            ];
            $masterTotals[$row->expense_type_id]['total'] =
                ($masterTotals[$row->expense_type_id]['total'] ?? 0) + (float) $row->total;
            $masterTotals[$row->expense_type_id]['cnt'] =
                ($masterTotals[$row->expense_type_id]['cnt'] ?? 0) + (int) $row->cnt;
        }

        return view('expenses.index', compact(
            'fieldOffices', 'fieldTypes', 'corporateTypes', 'matrix', 'masterTotals'
        ));
    }

    /**
     * DRILL-DOWN LIST: the actual rows behind a type/office combo clicked
     * on the dashboard -- this is what the "View entries" link on each
     * field card and the master card points to. Also doubles as the
     * general expenses list.
     */
    public function list(Request $request)
    {
        $expenses = Expense::with(['type', 'field'])
            ->when($request->filled('field_id'), fn ($q) => $q->where('field_id', $request->field_id))
            ->when($request->filled('expense_type_id'), fn ($q) => $q->where('expense_type_id', $request->expense_type_id))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('expenses.list', compact('expenses'));
    }

    /**
     * CREATE FORM
     */
    public function create(Request $request)
    {
        $fieldOffices = Field::where('name', '!=', 'Accra')->get();
        $fieldTypes   = ExpenseType::field()->orderBy('name')->get();

        $typicalTypesByField = $this->typicalTypesByField();

        $selectedFieldId = $request->query('field_id', Auth::user()->field_id);

        return view('expenses.create', compact(
            'fieldOffices', 'fieldTypes', 'typicalTypesByField', 'selectedFieldId'
        ));
    }

    /**
     * STORE: creates the expense and kicks off the approval chain at stage 1.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'description'      => 'required|string|max:1000',
            'amount'           => 'required|numeric|min:0.01',
            'field_id'         => 'required|exists:fields,id',
            'expense_type_id'  => 'required_without:new_expense_type',
            'new_expense_type' => 'required_without:expense_type_id|nullable|string|max:255',
            'image'            => 'nullable|image|max:5120',
        ]);

        $expenseTypeId = $this->resolveExpenseTypeId($request);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('expenses', 'public')
            : null;

        Expense::create([
            'description'     => $validated['description'],
            'amount'          => $validated['amount'],
            'field_id'        => $validated['field_id'],
            'expense_type_id' => $expenseTypeId,
            'image'           => $imagePath,
            'user_1'          => Auth::id(),
            'status_1'        => 'pending',
            'date_1'          => now(),
        ]);

        return redirect()->route('expense.index')->with('success', 'Expense recorded.');
    }

    /**
     * EDIT FORM: only reachable if the expense is still editable.
     */
    public function edit(Expense $expense)
    {
        abort_unless($expense->isEditableBy(Auth::user()), 403,
            'This expense can no longer be edited -- it has already moved past the first approval stage.');

        $fieldOffices = Field::where('name', '!=', 'Accra')->get();
        $fieldTypes   = ExpenseType::field()->orderBy('name')->get();

        $typicalTypesByField = $this->typicalTypesByField();
        $selectedFieldId = $expense->field_id;

        return view('expenses.edit', compact(
            'expense', 'fieldOffices', 'fieldTypes', 'typicalTypesByField', 'selectedFieldId'
        ));
    }

    /**
     * UPDATE: re-validates the same rules as store(), re-checks
     * authorization (in case status changed between opening the form and
     * submitting it), and resets the approval chain since the figures
     * changed after stage 1 review started.
     */
    public function update(Request $request, Expense $expense)
    {
        abort_unless($expense->isEditableBy(Auth::user()), 403,
            'This expense can no longer be edited -- it has already moved past the first approval stage.');

        $validated = $request->validate([
            'description'      => 'required|string|max:1000',
            'amount'           => 'required|numeric|min:0.01',
            'field_id'         => 'required|exists:fields,id',
            'expense_type_id'  => 'required_without:new_expense_type',
            'new_expense_type' => 'required_without:expense_type_id|nullable|string|max:255',
            'image'            => 'nullable|image|max:5120',
        ]);

        $expenseTypeId = $this->resolveExpenseTypeId($request);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('expenses', 'public')
            : $expense->image;

        $expense->update([
            'description'     => $validated['description'],
            'amount'          => $validated['amount'],
            'field_id'        => $validated['field_id'],
            'expense_type_id' => $expenseTypeId,
            'image'           => $imagePath,
            // editing resets the approval chain back to stage 1 pending,
            // since a downstream approver may have already signed off on
            // the OLD amount/type
            'status_1' => 'pending',
            'date_1'   => now(),
            'user_2' => null, 'status_2' => null, 'date_2' => null,
            'user_3' => null, 'status_3' => null, 'date_3' => null,
        ]);

        return redirect()->route('expense.index')->with('success', 'Expense updated.');
    }

    /**
     * DESTROY: soft delete only, and only if still editable. Approved
     * expenses are never hard- or soft-deleted through this action --
     * that protects the audit trail. A privileged approver can still
     * remove any record (see isDeletableBy).
     */
    public function destroy(Expense $expense)
    {
        abort_unless($expense->isDeletableBy(Auth::user()), 403,
            'This expense can no longer be deleted -- it has already moved past the first approval stage.');

        $expense->delete(); // soft delete via SoftDeletes trait

        return redirect()->back()->with('success', 'Expense deleted.');
    }

    // -----------------------------------------------------------------
    // Shared helpers
    // -----------------------------------------------------------------

    private function resolveExpenseTypeId(Request $request): int
    {
        if ($request->expense_type_id === 'new' || $request->filled('new_expense_type')) {
            $scope = Auth::user()->field?->name === 'Accra' ? 'corporate' : 'field';

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
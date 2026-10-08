<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Field;
use App\Models\PayPriorityRule;
use App\Support\PayPriority;
use App\Support\PayPriorityAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Payroll -> Pay priority rules (Finance Manager only).
 *
 * Every write goes through the PayPriorityRule model one rule at a time, so the
 * model's saved / deleted events recompute employees and unpaid salaries. Spelling
 * variants have no model, so their writes recompute explicitly. Every change is
 * previewed (same calculation as the page's preview) and written to the history.
 */
class PayPriorityRuleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            abort_unless(Auth::user()?->hasRole(['Finance Manager']), 403, 'Only a Finance Manager can manage pay priority rules.');

            return $next($request);
        });
    }

    public function index()
    {
        $rules = PayPriorityRule::orderBy('client_id')->orderByDesc('level')->orderBy('id')->get();
        $scope = PayPriority::scopeClientIds();
        $clients = Client::whereIn('id', $scope ?: [0])->get(['id', 'name', 'business_name', 'status'])
            ->mapWithKeys(fn ($c) => [(int) $c->id => ['name' => trim((string) ($c->business_name ?: $c->name)) ?: 'Client ' . $c->id, 'status' => $c->status]])
            ->all();
        $withRules = $rules->where('active', true)->pluck('client_id')->map(fn ($id) => (int) $id)->unique()->all();

        return view('payPriority.index', [
            'rules' => $rules,
            'clients' => $clients,
            'scope' => $scope,
            'noRuleClients' => array_values(array_diff($scope, $withRules)),
            'fields' => Field::orderBy('name')->pluck('name', 'id')->all(),
            'matchCounts' => PayPriorityAdmin::matchCounts(),
            'flagged' => DB::table('employees')->whereIn('status', PayPriorityAdmin::ACTIVE_STATUSES)
                ->selectRaw('pay_priority, COUNT(*) as n')->groupBy('pay_priority')->pluck('n', 'pay_priority')->all(),
            'aliases' => DB::table('pay_priority_aliases')->orderBy('position')->orderBy('id')->get(),
            'unmatched' => PayPriorityAdmin::unmatchedLocations(),
            'history' => DB::table('pay_priority_changes')->leftJoin('users', 'users.id', '=', 'pay_priority_changes.user_id')
                ->orderByDesc('pay_priority_changes.id')->limit(50)
                ->get(['pay_priority_changes.*', 'users.name as user_name']),
        ]);
    }

    /* ------------------------------------------------------------------
     | Rules
     * ------------------------------------------------------------------ */

    /** Validated rule fields from the form; locations typed one per line or comma separated. */
    private function ruleInput(Request $request): array
    {
        $data = $request->validate([
            'client_id' => ['required', 'integer', Rule::in(PayPriority::scopeClientIds()), Rule::exists('clients', 'id')],
            'level' => ['required', Rule::in([PayPriority::PRIORITY, PayPriority::URGENT])],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'field_id' => ['nullable', 'integer', Rule::exists('fields', 'id')],
            'locations' => ['nullable', 'string', 'max:3000'],
            'reason' => ['nullable', 'string', 'max:190'],
        ], [
            'client_id.in' => 'Pay rules are limited to the default Category A clients.',
        ]);

        $locations = collect(preg_split('/[\r\n,;]+/', (string) ($data['locations'] ?? '')))
            ->map(fn ($l) => PayPriority::clean($l))->filter()->unique()->values();
        if ($locations->count() > 60) {
            throw ValidationException::withMessages(['locations' => 'A rule can list at most 60 locations; split it into two rules.']);
        }
        foreach ($locations as $l) {
            if (mb_strlen($l) > 60) {
                throw ValidationException::withMessages(['locations' => "Location \"{$l}\" is too long (60 characters at most)."]);
            }
        }

        return [
            'client_id' => (int) $data['client_id'],
            'level' => (int) $data['level'],
            'gender' => $data['gender'] ?? null,
            'field_id' => isset($data['field_id']) ? (int) $data['field_id'] : null,
            'locations' => $locations->isEmpty() ? null : $locations->all(),
            'reason' => trim((string) ($data['reason'] ?? '')) ?: null,
        ];
    }

    /** Preview (JSON) of a create / update / enable / disable / delete before it is saved. */
    public function preview(Request $request)
    {
        $action = (string) $request->input('action');
        $rule = $request->filled('rule_id') ? PayPriorityRule::findOrFail((int) $request->input('rule_id')) : null;

        $rows = match ($action) {
            'create' => PayPriorityAdmin::rowsWith($this->ruleInput($request)),
            'update' => PayPriorityAdmin::rowsWith($this->ruleInput($request) + ['active' => (bool) $rule?->active], $rule?->id ?? abort(422)),
            'enable', 'disable' => PayPriorityAdmin::rowsWith(($rule ?? abort(422))->only(['client_id', 'level', 'gender', 'field_id', 'locations', 'reason']) + ['active' => $action === 'enable'], $rule->id),
            'delete' => PayPriorityAdmin::rowsWith(null, ($rule ?? abort(422))->id, true),
            default => abort(422, 'Unknown action.'),
        };

        return response()->json(PayPriorityAdmin::preview($rows));
    }

    public function store(Request $request)
    {
        $data = $this->ruleInput($request);
        $preview = PayPriorityAdmin::preview(PayPriorityAdmin::rowsWith($data));

        $rule = PayPriorityRule::create($data + ['active' => true]); // recomputes (model event)
        PayPriorityAdmin::log('created', 'Added rule for ' . $this->clientName($rule->client_id) . ': ' . PayPriorityAdmin::describe($rule->toArray(), $this->fieldNames()),
            $rule->id, $rule->client_id, null, $this->snapshot($rule), $preview['changed']);

        return back()->with('success', 'Rule added. ' . $preview['summary']);
    }

    public function update(Request $request, PayPriorityRule $rule)
    {
        $data = $this->ruleInput($request);
        $before = $this->snapshot($rule);
        $preview = PayPriorityAdmin::preview(PayPriorityAdmin::rowsWith($data + ['active' => (bool) $rule->active], $rule->id));

        $rule->fill($data);
        if (! $rule->isDirty()) {
            return back()->with('primary', 'Nothing changed.');
        }
        $rule->save(); // recomputes
        PayPriorityAdmin::log('updated', 'Changed rule for ' . $this->clientName($rule->client_id) . ': ' . PayPriorityAdmin::describe($rule->toArray(), $this->fieldNames()),
            $rule->id, $rule->client_id, $before, $this->snapshot($rule), $preview['changed']);

        return back()->with('success', 'Rule saved. ' . $preview['summary']);
    }

    public function toggle(PayPriorityRule $rule)
    {
        $enable = ! $rule->active;
        $before = $this->snapshot($rule);
        $preview = PayPriorityAdmin::preview(PayPriorityAdmin::rowsWith(
            $rule->only(['client_id', 'level', 'gender', 'field_id', 'locations', 'reason']) + ['active' => $enable], $rule->id
        ));

        $rule->active = $enable;
        $rule->save(); // recomputes
        PayPriorityAdmin::log($enable ? 'enabled' : 'disabled', ($enable ? 'Turned on' : 'Turned off') . ' rule for ' . $this->clientName($rule->client_id) . ': '
            . PayPriorityAdmin::describe($rule->toArray(), $this->fieldNames()), $rule->id, $rule->client_id, $before, $this->snapshot($rule), $preview['changed']);

        return back()->with('success', ($enable ? 'Rule turned on. ' : 'Rule turned off. ') . $preview['summary']);
    }

    public function destroy(PayPriorityRule $rule)
    {
        $before = $this->snapshot($rule);
        $preview = PayPriorityAdmin::preview(PayPriorityAdmin::rowsWith(null, $rule->id, true));
        $text = PayPriorityAdmin::describe($rule->toArray(), $this->fieldNames());

        $rule->delete(); // one model: fires the recompute (a query-builder delete would not)
        PayPriorityAdmin::log('deleted', 'Deleted rule for ' . $this->clientName($before['client_id']) . ': ' . $text,
            $before['id'], $before['client_id'], $before, null, $preview['changed']);

        return back()->with('success', 'Rule deleted. ' . $preview['summary']);
    }

    /* ------------------------------------------------------------------
     | Spelling variants
     * ------------------------------------------------------------------ */

    private function aliasInput(Request $request): array
    {
        $request->validate(['from' => ['required', 'string', 'max:60'], 'to' => ['required', 'string', 'max:60']]);
        $from = PayPriority::clean($request->input('from'));
        $to = PayPriority::clean($request->input('to'));
        if ($from === '' || $to === '') {
            throw ValidationException::withMessages(['from' => 'Enter both spellings (letters or numbers).']);
        }
        if ($from === $to) {
            throw ValidationException::withMessages(['from' => 'The two spellings are the same.']);
        }

        return [$from, $to];
    }

    /** Alias list with $from -> $to added (or replaced), in application order. */
    private function aliasesWith(?string $from, ?string $to, ?string $remove = null): array
    {
        $list = DB::table('pay_priority_aliases')->orderBy('position')->orderBy('id')->pluck('to', 'from')->all();
        if ($remove !== null) {
            unset($list[$remove]);
        }
        if ($from !== null) {
            unset($list[$from]);
            $list[$from] = $to;
        }

        return $list;
    }

    public function aliasPreview(Request $request)
    {
        if ($request->filled('remove')) {
            $alias = DB::table('pay_priority_aliases')->where('id', (int) $request->input('remove'))->first() ?? abort(404);

            return response()->json(PayPriorityAdmin::preview(null, $this->aliasesWith(null, null, $alias->from)));
        }
        [$from, $to] = $this->aliasInput($request);

        return response()->json(PayPriorityAdmin::preview(null, $this->aliasesWith($from, $to)) + ['from' => $from, 'to' => $to]);
    }

    public function aliasStore(Request $request)
    {
        [$from, $to] = $this->aliasInput($request);
        $preview = PayPriorityAdmin::preview(null, $this->aliasesWith($from, $to));

        $existing = DB::table('pay_priority_aliases')->where('from', $from)->first();
        if ($existing) {
            DB::table('pay_priority_aliases')->where('id', $existing->id)->update(['to' => $to, 'updated_at' => now()]);
        } else {
            DB::table('pay_priority_aliases')->insert(['from' => $from, 'to' => $to, 'position' => (int) DB::table('pay_priority_aliases')->max('position') + 1, 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->recomputeAll();
        PayPriorityAdmin::log('alias_added', "Spelling \"{$from}\" now read as \"{$to}\"", null, null, $existing ? ['from' => $from, 'to' => $existing->to] : null, ['from' => $from, 'to' => $to], $preview['changed']);

        return back()->with('success', "\"{$from}\" is now read as \"{$to}\". " . $preview['summary']);
    }

    public function aliasDestroy(int $alias)
    {
        $row = DB::table('pay_priority_aliases')->where('id', $alias)->first() ?? abort(404);
        $preview = PayPriorityAdmin::preview(null, $this->aliasesWith(null, null, $row->from));

        DB::table('pay_priority_aliases')->where('id', $alias)->delete();
        $this->recomputeAll();
        PayPriorityAdmin::log('alias_removed', "Removed spelling \"{$row->from}\" → \"{$row->to}\"", null, null, ['from' => $row->from, 'to' => $row->to], null, $preview['changed']);

        return back()->with('success', "Spelling \"{$row->from}\" removed. " . $preview['summary']);
    }

    /** Recalculate everyone (after data fixes outside the app). */
    public function recompute()
    {
        [$e, $s] = $this->recomputeAll();
        PayPriorityAdmin::log('recomputed', "Recalculated everyone: {$e} employee(s), {$s} unpaid salary row(s) updated", null, null, null, null, $e);

        return back()->with('success', "Recalculated: {$e} employee(s) and {$s} unpaid salary row(s) updated.");
    }

    /* ------------------------------------------------------------------ */

    private function recomputeAll(): array
    {
        PayPriority::flush();

        return [PayPriority::recomputeEmployees(), PayPriority::recomputeSalaries()];
    }

    private function snapshot(PayPriorityRule $rule): array
    {
        return $rule->only(['id', 'client_id', 'level', 'gender', 'field_id', 'locations', 'reason', 'active']);
    }

    private ?array $fieldNameCache = null;

    private function fieldNames(): array
    {
        return $this->fieldNameCache ??= Field::pluck('name', 'id')->all();
    }

    private function clientName($id): string
    {
        $c = Client::find($id, ['id', 'name', 'business_name']);

        return $c ? (trim((string) ($c->business_name ?: $c->name)) ?: 'client ' . $id) : 'client ' . $id;
    }
}

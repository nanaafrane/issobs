<?php

namespace App\Http\Controllers;

use App\Models\category;
use App\Http\Requests\StorecategoryRequest;
use App\Http\Requests\UpdatecategoryRequest;
use App\Models\Client;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Concerns\SearchesDates;

class CategoryController extends Controller
{
    use SearchesDates;

    /** Categories a client can be placed in. */
    private const CATEGORIES = ['Category A', 'Category B', 'Category C', 'Category D'];

    /** Hard cap on how many client ids one request may carry. */
    private const MAX_IDS = 20000;

    /** DataTables column index => whitelisted SQL sort expression. Keep in step with the view. */
    private const ORDER_COLUMNS = [
        2 => 'clients.id',
        3 => "COALESCE(NULLIF(clients.business_name, ''), clients.name)",
        4 => 'clients.phone_number',
        5 => 'fields.name',
        6 => 'categories.name',
        7 => 'categories.updated_at',
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        // $categories = category::all();
        return view('categories.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorecategoryRequest $request)
    {
        //
        // dd($request->all());
        $category = new category();
        $category->name = $request->name;
        $category->user_id = Auth::id(); 
        $category->save();
        return back()->with('success', 'Category added successfully');
    }

        /**
     * Category Add Clients For A Month.
     */
    public function categorySearch(Request $request)
    {
        // dd($request->all());
        $date = Carbon::parse($request->input('month')); 
        $categories = category::whereMonth('category_month', $date->month)->get();
        return view('categories.index', compact('categories'));


    }


    // ------------------------------------------------------------------
    //  Active clients by month  (bulk category assignment page)
    // ------------------------------------------------------------------

    /** Month from ?month=Y-m (or any date), falling back to the current month. */
    private function resolveMonth(Request $request): Carbon
    {
        $value = $this->requestString($request, 'month');

        if ($value !== null && preg_match('/^\d{4}-\d{2}(-\d{2})?$/', $value)) {
            try {
                return Carbon::parse($value)->startOfMonth();
            } catch (\Throwable $e) {
                // fall through
            }
        }

        return now()->startOfMonth();
    }

    /** Client ids that are pre-ticked for Category A every month (config/category.php). */
    private function defaultCategoryAIds(): array
    {
         return category::DEFAULT_A_CLIENT_IDS;
    }

    /**
     * Active clients + the category (if any) they hold in $month.
     * One row per client even if duplicate category rows exist for the month.
     */
    private function clientListBase(Carbon $month)
    {
        $start = $month->copy()->startOfMonth()->toDateString();
        $end   = $month->copy()->endOfMonth()->toDateString();

        $latest = DB::table('categories')
            ->select('client_id', DB::raw('MAX(id) as category_pk'))
            ->whereBetween('category_month', [$start, $end])
            ->groupBy('client_id');

        return Client::query()
            ->where('clients.status', 'Active')
            ->select([
                'clients.id',
                'clients.name',
                'clients.business_name',
                'clients.phone_number',
                'fields.name as field_name',
                'categories.id as category_row_id',
                'categories.name as category_name',
                'categories.updated_at as assigned_at',
            ])
            ->leftJoin('fields', 'fields.id', '=', 'clients.field_id')
            ->leftJoinSub($latest, 'latest_cat', 'latest_cat.client_id', '=', 'clients.id')
            ->leftJoin('categories', 'categories.id', '=', 'latest_cat.category_pk');
    }

    /** Restrict to one field office; 0 = clients that have no field office. */
    private function whereFieldOffice($query, int $fieldId): void
    {
        if ($fieldId === 0) {
            $query->where(function ($q) {
                $q->whereNull('clients.field_id')->orWhere('clients.field_id', 0);
            });
        } else {
            $query->where('clients.field_id', $fieldId);
        }
    }

    private function clientListFilters($query, Request $request): void
    {
        // Show: all | outstanding | categorized | one specific category (whitelisted)
        $state = $this->requestString($request, 'state');
        if ($state === 'outstanding') {
            $query->whereNull('categories.id');
        } elseif ($state === 'categorized') {
            $query->whereNotNull('categories.id');
        } elseif ($state !== null && in_array($state, self::CATEGORIES, true)) {
            $query->where('categories.name', $state);
        }

        // Field office (0 = clients with no field office)
        $fieldId = $this->requestString($request, 'field_id');
        if ($fieldId !== null && ctype_digit($fieldId)) {
            $this->whereFieldOffice($query, (int) $fieldId);
        }

        $search = trim((string) $this->requestString($request, 'search.value'));
        if ($search !== '') {
            $term = $this->likeTerm($search);

            $query->where(function ($q) use ($search, $term) {
                $q->where('clients.business_name', 'like', $term)
                    ->orWhere('clients.name', 'like', $term)
                    ->orWhere('clients.phone_number', 'like', $term)
                    ->orWhere('fields.name', 'like', $term)
                    ->orWhere('categories.name', 'like', $term)
                    ->orWhere(function ($w) use ($search) {
                        $this->whereDateMatches($w, 'categories.updated_at', $search);
                    });

                if (preg_match('/^#?\d+$/', $search)) {
                    $q->orWhere('clients.id', 'like', '%' . preg_replace('/\D/', '', $search) . '%');
                }
                if (strtolower($search) === 'outstanding') {
                    $q->orWhereNull('categories.id');
                }
            });
        }

        // Per-column filters. Indexes match the <thead> / columns[] order in the view.
        $columns = $request->input('columns', []);
        foreach (is_array($columns) ? $columns : [] as $index => $column) {
            $value = $this->columnFilterValue($column);
            if ($value === '') {
                continue;
            }

            switch ((int) $index) {
                case 2:
                    $this->whereIdMatches($query, 'clients.id', $value);
                    break;
                case 3:
                    $term = $this->likeTerm($value);
                    $query->where(function ($q) use ($term) {
                        $q->where('clients.business_name', 'like', $term)
                            ->orWhere('clients.name', 'like', $term);
                    });
                    break;
                case 4:
                    $query->where('clients.phone_number', 'like', $this->likeTerm($value));
                    break;
                case 5:
                    $query->where('fields.name', 'like', $this->likeTerm($value));
                    break;
                case 6:
                    strtolower($value) === 'outstanding'
                        ? $query->whereNull('categories.id')
                        : $query->where('categories.name', 'like', $this->likeTerm($value));
                    break;
                case 7:
                    $this->whereDateMatches($query, 'categories.updated_at', $value);
                    break;
                // 0 (checkbox) and 1 (row number) are not filterable.
            }
        }
    }

    private function clientListOrder($query, Request $request): void
    {
        $orderColumn = (int) $request->input('order.0.column', 3);
        $orderDir    = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc'; // validated
        $sortColumn  = self::ORDER_COLUMNS[$orderColumn] ?? self::ORDER_COLUMNS[3];        // whitelisted

        $query->orderByRaw("{$sortColumn} {$orderDir}")->orderBy('clients.id', 'asc');
    }

    /**
     * Active clients per field office per category for the month.
     * Feeds the clickable summary tiles / field office cards.
     *
     * @return array{fields: array<int, array>, totals: array<string, int>}
     */
    private function fieldSummary(Carbon $month): array
    {
        $rows = $this->clientListBase($month)
            ->select([
                'clients.field_id',
                'fields.name as field_name',
                'categories.name as category_name',
                DB::raw('COUNT(*) as total'),
            ])
            ->groupBy('clients.field_id', 'fields.name', 'categories.name')
            ->get();

        $totals = ['all' => 0, 'outstanding' => 0] + array_fill_keys(self::CATEGORIES, 0);
        $fields = [];

        foreach ($rows as $row) {
            $fieldId = (int) $row->field_id;
            $count   = (int) $row->total;

            $fields[$fieldId] ??= [
                'id'          => $fieldId,
                'name'        => $row->field_name ?: 'No field office',
                'total'       => 0,
                'outstanding' => 0,
                'cats'        => array_fill_keys(self::CATEGORIES, 0),
            ];

            $fields[$fieldId]['total'] += $count;
            $totals['all']             += $count;

            if ($row->category_name === null) {
                $fields[$fieldId]['outstanding'] += $count;
                $totals['outstanding']           += $count;
            } elseif (isset($fields[$fieldId]['cats'][$row->category_name])) {
                $fields[$fieldId]['cats'][$row->category_name] += $count;
                $totals[$row->category_name]                   += $count;
            }
        }

        $fields = array_values($fields);
        usort($fields, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return ['fields' => $fields, 'totals' => $totals];
    }

    /**
     * The page. Rows are loaded by clientsData() (server-side DataTable).
     */
    public function activeClientsByMonth(Request $request)
    {
        $month = $this->resolveMonth($request);

        // Default Category A clients: ticked up-front, but only while still outstanding
        // this month (a client already placed in a category is never silently re-ticked).
        $defaultIds = [];
        if ($this->defaultCategoryAIds()) {
            $defaultIds = $this->clientListBase($month)
                ->whereIn('clients.id', $this->defaultCategoryAIds())
                ->whereNull('categories.id')
                ->pluck('clients.id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return view('categories.activeClients', [
            'month'         => $month,
            'categories'    => self::CATEGORIES,
            'summary'       => $this->fieldSummary($month),
            'defaultIds'    => $defaultIds,
            'allDefaultIds' => $this->defaultCategoryAIds(),
        ]);
    }

    /**
     * Server-side DataTable feed.
     */
    public function clientsData(Request $request)
    {
        $month = $this->resolveMonth($request);
        $query = $this->clientListBase($month);

        $recordsTotal = Client::query()->where('clients.status', 'Active')->count();

        $this->clientListFilters($query, $request);
        $recordsFiltered = (clone $query)->count();

        $this->clientListOrder($query, $request);

        $start  = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 25);
        $length = $length > 0 ? min($length, 2000) : 25;

        $rows = $query->offset($start)->limit($length)->get();

        $defaults = $this->defaultCategoryAIds();

        $data = $rows->map(function ($client) use ($defaults) {
            $label = trim((string) ($client->business_name ?: $client->name));

            $category = $client->category_row_id
                ? '<span class="badge bg-label-success">' . e($client->category_name) . '</span>'
                : '<span class="badge bg-label-warning">Outstanding</span>';

            if (in_array((int) $client->id, $defaults, true)) {
                $category .= ' <span class="badge bg-label-info" title="Ticked for Category A every month">Default A</span>';
            }

            return [
                'select'       => '',
                'row_number'   => '',
                'client_id'    => (int) $client->id,
                'client_name'  => e($label),
                'phone_number' => e((string) $client->phone_number),
                'field_name'   => e((string) $client->field_name),
                'category'     => $category,
                'assigned_at'  => $client->assigned_at
                    ? e(Carbon::parse($client->assigned_at)->format('d M Y, H:i'))
                    : '',
            ];
        })->all();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    /**
     * Ids of EVERY client matching the current filters (powers "Select all matching",
     * which must reach past the rows loaded on the current page).
     */
    public function clientIds(Request $request)
    {
        $month = $this->resolveMonth($request);
        $query = $this->clientListBase($month);

        $this->clientListFilters($query, $request);

        $ids = $query->limit(self::MAX_IDS)->pluck('clients.id')->map(fn ($id) => (int) $id)->all();

        return response()->json(['ids' => $ids]);
    }

    /**
     * Shared upsert: put these ACTIVE clients into $category for $month.
     * Clients without a row that month get one; clients that already have one are moved.
     *
     * @param  iterable<int>  $ids
     * @return array{created:int, updated:int, unchanged:int, skipped:int}
     */
    private function applyCategory($ids, string $category, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth()->toDateString();
        $end   = $month->copy()->endOfMonth()->toDateString();

        $ids = collect($ids)
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0)
            ->unique()
            ->take(self::MAX_IDS)
            ->values();

        $clients = Client::query()->where('status', 'Active')->whereIn('id', $ids)->get();
        $skipped = $ids->count() - $clients->count(); // unknown or no longer active

        $existing = category::whereBetween('category_month', [$start, $end])
            ->whereIn('client_id', $clients->pluck('id'))
            ->orderBy('id')
            ->get()
            ->keyBy('client_id'); // latest row wins if duplicates exist

        $created = $updated = $unchanged = 0;

        DB::transaction(function () use ($clients, $existing, $category, $start, $end, &$created, &$updated, &$unchanged) {
            foreach ($clients as $client) {
                $row = $existing->get($client->id);

                if ($row) {
                    if ($row->name === $category) {
                        $unchanged++;
                    } else {
                        $row->name = $category;
                        $row->save();
                        $updated++;
                    }
                } else {
                    $row = new category();
                    $row->client_id      = $client->id;
                    $row->name           = $category;
                    $row->category_month = $start;
                    $row->user_id        = Auth::id();
                    $row->save();
                    $created++;
                }

                // Keep the denormalised copy on clients in step, but never let an
                // older month overwrite a newer one.
                if (! $client->category_month || Carbon::parse($client->category_month)->lte($end)) {
                    $client->category_id    = $row->id;
                    $client->category_name  = $category;
                    $client->category_month = $start;
                    $client->save();
                }
            }
        });

        return compact('created', 'updated', 'unchanged', 'skipped');
    }

    private function applyMessage(array $r, string $category, Carbon $month, string $scope = ''): string
    {
        return "{$category} applied{$scope} for {$month->format('F Y')}: {$r['created']} added, {$r['updated']} moved"
            . ($r['unchanged'] ? ", {$r['unchanged']} already in it" : '')
            . ($r['skipped'] ? ", {$r['skipped']} skipped (not active)" : '') . '.';
    }

    /**
     * Put the selected (ticked) ACTIVE clients into ONE category for a month.
     */
    public function bulkAssign(Request $request)
    {
        abort_unless(Auth::user()->hasRole(['Finance Manager']), 403);

        $data = $request->validate([
            'month'      => ['required', 'regex:/^\d{4}-\d{2}(-\d{2})?$/'],
            'category'   => ['required', Rule::in(self::CATEGORIES)],
            'client_ids' => ['required', 'string'],
        ], [
            'category.required'   => 'Choose a category to apply.',
            'client_ids.required' => 'Tick at least one client.',
        ]);

        $month = Carbon::parse($data['month'])->startOfMonth();

        $ids = collect(explode(',', $data['client_ids']))
            ->map(fn ($v) => (int) trim($v))
            ->filter(fn ($v) => $v > 0);

        if ($ids->isEmpty()) {
            return back()->with('error', 'No Client selected to assign.');
        }

        $result = $this->applyCategory($ids, $data['category'], $month);

        return redirect()
            ->route('category.activeClientsByMonth', ['month' => $month->format('Y-m')])
            ->with('success', $this->applyMessage($result, $data['category'], $month));
    }

    /**
     * Per-card action: put every OUTSTANDING active client of ONE field office
     * into a category. Only clients with no category that month are touched, so
     * it can never move someone who is already categorised.
     */
    public function fieldAssign(Request $request)
    {
        abort_unless(Auth::user()->hasRole(['Finance Manager']), 403);

        $data = $request->validate([
            'month'    => ['required', 'regex:/^\d{4}-\d{2}(-\d{2})?$/'],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'field_id' => ['required', 'integer', 'min:0'],
        ]);

        $month = Carbon::parse($data['month'])->startOfMonth();

        $query = $this->clientListBase($month)->whereNull('categories.id');
        $this->whereFieldOffice($query, (int) $data['field_id']);

        $ids = $query->limit(self::MAX_IDS)->pluck('clients.id');

        $redirect = redirect()->route('category.activeClientsByMonth', ['month' => $month->format('Y-m')]);

        if ($ids->isEmpty()) {
            return $redirect->with('error', 'That field office has no outstanding clients for ' . $month->format('F Y') . '.');
        }

        $result = $this->applyCategory($ids, $data['category'], $month);

        return $redirect->with('success', $this->applyMessage($result, $data['category'], $month, ' to the office\'s outstanding clients'));
    }

    /**
     * Category Add Clients For A Month.
     */
    public function categoryAssign(Request $request)
    {
        // dd($request->all());

        // CHECK IF INCOMING REQUEST IS NOT EMPTY
        $client = $request->input('client', []);
        // dd($employees);
        if (empty($client)) {
            return back()->with('error', 'No Client selected to assign.');
        }


        // get current month and year
        $date = Carbon::parse($request->input('month')); 
        // dd($date->format('Y-m-d'));
        // FIND CATEGORY
        $Clients = Client::findOrFail($request->client);

        foreach($Clients as $key => $categoryClient){

            //  CHECK IF CLIENT ALREADY EXIST FOR THE SAME MONTH
             $exists = category::where('client_id', $request->client[$key])
                                ->whereMonth('category_month', $date->month)
                                ->exists();

                if ($exists) {
                    $alreadyProcessed[] = $request->client[$key];
                    continue;
                }

                // echo  $request->client[$key] .    $request->name[$key] ."<br>"; 
            $category = new category();
            $category->client_id = $request->client[$key];
            $category->name = $request->name[$key];
            $category->category_month = $date->format('Y-m-d');
            $category->user_id = Auth::id(); 
            $category->save();

            // UPDATE CLIENT AND MONTH
            $categoryClient->category_id = $category->id;
            $categoryClient->category_name = $request->name[$key];
            $categoryClient->category_month = $date->format('Y-m-d');
            $categoryClient->save();
        }

        if (!empty($alreadyProcessed)) {
            return back()->with('error', 'The Clients with the IDs have already been Assign to a Category for this month: '. implode(', ', $alreadyProcessed)) ;
        }
        return back()->with('success', 'Clients added to this month salary'); 

    }


    /**
     * Change Category  Clients For A Month.
     */
    public function categoryReAssign(Request $request)
    {
        // dd($request->all());

        // CHECK IF INCOMING REQUEST IS NOT EMPTY
        $client = $request->input('client', []);
        // dd($employees);
        if (empty($client)) {
            return back()->with('error', 'No Client selected to ReAssign.');
        }


        // get current month and year
        // dd($date->format('Y-m-d'));
        // FIND CATEGORY

        $clientData =  collect($request->client)
                        ->map(function ($clientId, $index) use ($request) {

                            // update category for the client and month
                            $date = Carbon::parse($request->input('month')); 
                            $category = category::where('client_id', $clientId)
                                                ->whereMonth('category_month', $date->month)
                                                ->first();
                            if ($category) {
                                $category->name = $request->name[$index];
                                $category->save();
                            }
                            // update client category name
                            $client = Client::findorFail($clientId);
                            if ($client) {
                                // $client->category_id = $category->id;
                                $client->category_name = $request->name[$index];
                                // $client->category_month = $date->format('Y-m-d');
                                $client->save();
                            }

                        });

        // dd($clientData);
         return back()->with('success', 'Clients ReAssigned to this month salary');
    }



    /**
     * Display the specified resource.
     */
    public function show(category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(category $category)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatecategoryRequest $request, category $category)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(category $category)
    {
        //
    }
}
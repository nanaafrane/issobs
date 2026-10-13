<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Http\Requests\StoreBankRequest;
use App\Http\Requests\UpdateBankRequest;
use App\Models\BankTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BankController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * Display a listing of the resource.
     */


// Replace BankController@index with this. Adjust the two model names if yours differ
// (assumed: App\Models\Bank and App\Models\BankTransaction with credit, debit, balance,
// bank_id, receipt_id, deposit_id, expense_id, channel, narration, created_at).

public function index(Request $request)
{
    $group = in_array($request->input('group'), ['day', 'week', 'month', 'year'], true)
        ? $request->input('group') : 'day';

    $from = Carbon::parse($request->input('from', now()->startOfMonth()))->startOfDay();
    $to   = Carbon::parse($request->input('to', now()))->endOfDay();
    if ($from->gt($to)) {
        [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
    }

    $banks = Bank::orderBy('name')->get();
    $inRange = fn () => BankTransaction::whereBetween('created_at', [$from, $to]);

    // Totals per bank for the period
    $perBank = $inRange()
        ->selectRaw('bank_id, COALESCE(SUM(credit),0) AS in_total, COALESCE(SUM(debit),0) AS out_total')
        ->groupBy('bank_id')->get()->keyBy('bank_id');

    $inTotal  = (float) $perBank->sum('in_total');
    $outTotal = (float) $perBank->sum('out_total');
    $net      = $inTotal - $outTotal;
    $totalBalance = (float) $banks->sum('total');

    // Ledger rows (capped so the page stays fast; the view warns when truncated)
    $limit = 2000;
    $transactions = $inRange()->with('bank')->latest()->limit($limit + 1)->get();
    $truncated = $transactions->count() > $limit;
    $transactions = $transactions->take($limit);

    // Day / week / month / year buckets, built in PHP so it works on any database
    $bucket = fn (Carbon $d) => match ($group) {
        'week'  => $d->copy()->startOfWeek()->format('Y-m-d'),
        'month' => $d->format('Y-m'),
        'year'  => $d->format('Y'),
        default => $d->format('Y-m-d'),
    };
    $label = fn (Carbon $d) => match ($group) {
        'week'  => $d->copy()->startOfWeek()->format('d M Y'),
        'month' => $d->format('M Y'),
        'year'  => $d->format('Y'),
        default => $d->format('d M Y'),
    };

    $series = $inRange()
        ->selectRaw('DATE(created_at) AS d, COALESCE(SUM(credit),0) AS i, COALESCE(SUM(debit),0) AS o, COUNT(*) AS c')
        ->groupBy('d')->orderBy('d')->get()
        ->groupBy(fn ($r) => $bucket(Carbon::parse($r->d)))
        ->map(function ($rows) use ($label) {
            $in = (float) $rows->sum('i');
            $out = (float) $rows->sum('o');
            return [
                'label' => $label(Carbon::parse($rows->first()->d)),
                'count' => (int) $rows->sum('c'),
                'in' => $in, 'out' => $out, 'net' => $in - $out,
            ];
        })->values()->all();

    // Last ledger balance per bank, to flag a bank whose stored total has drifted
    $latestIds = BankTransaction::selectRaw('MAX(id) AS id')->groupBy('bank_id')->pluck('id');
    $lastBalances = BankTransaction::whereIn('id', $latestIds)->pluck('balance', 'bank_id');

    // 30-day outlook: if the last 30 days repeat, where do balances land?
    $last30 = BankTransaction::where('created_at', '>=', now()->subDays(30))
        ->selectRaw('COALESCE(SUM(credit),0) AS i, COALESCE(SUM(debit),0) AS o')->first();
    $avgDailyNet = ((float) $last30->i - (float) $last30->o) / 30;
    $projection = [
        'avg_daily_net' => $avgDailyNet,
        'balance'       => $totalBalance + $avgDailyNet * 30,
        'runway_days'   => $avgDailyNet < 0 && $totalBalance > 0
            ? (int) floor($totalBalance / abs($avgDailyNet)) : null,
    ];

    return view('banks.index', compact(
        'banks', 'perBank', 'from', 'to', 'group', 'inTotal', 'outTotal', 'net',
        'totalBalance', 'transactions', 'truncated', 'series', 'lastBalances', 'projection'
    ));
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
    public function store(StoreBankRequest $request)
    {
        //
        $banks = new Bank();
        $banks->create($request->all());
        return back()->with('success', 'Bank Added Sucessfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Bank $bank)
    {
        //
        return view('banks.show', compact('bank'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bank $bank)
    {
        //

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBankRequest $request, Bank $bank)
    {
        //
        $bank->update($request->all());
        return back()->with('success', 'Bank Updated Successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bank $bank)
    {
        //
        $bank->delete();
        return back()->with('error', 'Delete Feature not functional yet');
    }
}

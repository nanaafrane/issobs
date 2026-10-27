<?php

namespace App\Http\Controllers;

use App\Exports\CashBookExport;
use App\Models\Bank;
use App\Services\Banking\CashBook;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

/**
 * The analysed cash book: Main Cash (or any account, or all of them) for a month, every line
 * spread over its items, and the total spent on each item at month end — the uploaded Main
 * Cash sheet, built from the ledger.
 */
class CashBookController extends Controller
{
    public function __construct(private CashBook $book)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        abort_unless(Auth::user()?->hasRole(BankController::VIEW_ROLES), 403);
        [$bank, $from, $to] = $this->inputs($request);
        $a = $this->book->analysis($bank, $from, $to);
        $accounts = Bank::orderByRaw("CASE WHEN kind = 'cash' THEN 0 ELSE 1 END")->orderBy('name')->get();

        return view('banks.cash_book', compact('a', 'accounts'));
    }

    public function export(Request $request)
    {
        abort_unless(Auth::user()?->hasRole(BankController::VIEW_ROLES), 403);
        [$bank, $from, $to] = $this->inputs($request);
        $a = $this->book->analysis($bank, $from, $to);

        $summary = [['TOTAL SPENT PER ITEM ' . strtoupper($from->format('d M Y') . ' – ' . $to->format('d M Y'))],
            array_merge(['ITEM', ''], $a['offices']->map(fn ($o) => strtoupper($o))->all(), ['TOTAL'])];
        foreach ($a['items'] as $item => $total) {
            $summary[] = array_merge([$item, ''], $a['offices']->map(fn ($o) => $a['matrix'][$item][$o] ?? null)->all(), [$total]);
        }
        $summary[] = array_merge(['TOTAL', ''], $a['offices']->map(fn ($o) => round(collect($a['matrix'])->sum(fn ($r) => (float) ($r[$o] ?? 0)), 2))->all(), [$a['analysed']]);

        $name = strtoupper(str_replace(' ', '_', ($bank?->name ?? 'All_accounts') . '_' . $from->format('M_Y'))) . '_CASH_BOOK.xlsx';

        return Excel::download(new CashBookExport($this->book->sheet($a), $summary, $from->format('M Y')), $name);
    }

    /** @return array{0: ?Bank, 1: Carbon, 2: Carbon} */
    private function inputs(Request $request): array
    {
        $bank = null;
        if ($request->input('bank') !== 'all') {
            $bank = $request->integer('bank')
                ? Bank::findOrFail($request->integer('bank'))
                : (Bank::where('kind', 'cash')->operating()->orderBy('id')->first() ?? Bank::orderBy('id')->first());
        }
        try {
            if ($request->filled('from') && $request->filled('to')) {
                $from = Carbon::parse($request->input('from'))->startOfDay();
                $to = Carbon::parse($request->input('to'))->startOfDay();
            } else {
                $from = Carbon::parse(($request->input('month') ?: now()->format('Y-m')) . '-01')->startOfMonth();
                $to = $from->copy()->endOfMonth()->startOfDay();
            }
        } catch (\Throwable) {
            [$from, $to] = [now()->startOfMonth(), now()->endOfMonth()->startOfDay()];
        }
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$bank, $from, $to];
    }
}

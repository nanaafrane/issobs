<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 | Payment priority. Run recompute once after migrating, and after bulk data fixes.
 | Editing a PayPriorityRule model recomputes automatically.
 */
Artisan::command('pay-priority:recompute', function () {
    $e = \App\Support\PayPriority::recomputeEmployees();
    $s = \App\Support\PayPriority::recomputeSalaries();
    $this->info("Updated {$e} employee(s) and {$s} unpaid salary row(s).");
})->purpose('Re-evaluate payment priority for employees and unpaid salaries');

Artisan::command('pay-priority:audit {--unmatched : Only list locations that match no rule}', function () {
    // Every distinct location actually stored for clients that have rules, with the result.
    // Use it to spot spellings the rules miss; add them on Payroll -> Pay priority rules -> Location spellings.
    $clientIds = \App\Support\PayPriority::ruleClientIds();
    $rows = \Illuminate\Support\Facades\DB::table('employees')
        ->whereIn('client_id', $clientIds ?: [0])
        ->whereIn('status', ['Active', 'Re-Instate'])
        ->selectRaw('client_id, field_id, location, COUNT(*) as people')
        ->groupBy('client_id', 'field_id', 'location')
        ->orderBy('client_id')->orderBy('location')
        ->get();

    $table = [];
    foreach ($rows as $r) {
        [$level] = \App\Support\PayPriority::evaluate($r->client_id, $r->field_id, $r->location, null);
        if ($this->option('unmatched') && $level > 0) {
            continue;
        }
        $table[] = [$r->client_id, $r->field_id, $r->location, \App\Support\PayPriority::normalise($r->location),
            $level ? \App\Support\PayPriority::LABELS[$level] : '-', $r->people];
    }
    $this->table(['Client', 'Field', 'Stored location', 'Normalised', 'Result (location rules)', 'Employees'], $table);
    $this->comment('Gender-only rules are not shown here: they do not depend on location.');

    // Link to category::DEFAULT_A_CLIENT_IDS, checked both ways.
    $scope = \App\Support\PayPriority::scopeClientIds();
    $ruleClients = \Illuminate\Support\Facades\DB::table('pay_priority_rules')->where('active', true)
        ->distinct()->pluck('client_id')->map(fn ($id) => (int) $id)->all();
    $noRules = array_values(array_diff($scope, $ruleClients));
    $ignored = array_values(array_diff($ruleClients, $scope));
    $this->newLine();
    $this->info('Default Category A clients: ' . count($scope) . ', with pay rules: ' . count(array_intersect($scope, $ruleClients)));
    if ($noRules) {
        $this->warn('Category A clients with NO pay rule (confirm this is intended): ' . implode(', ', $noRules));
    }
    if ($ignored) {
        $this->error('Rules IGNORED because the client is not in DEFAULT_A_CLIENT_IDS: ' . implode(', ', $ignored));
    }
})->purpose('List stored locations for rule clients and whether they match');

/*
 | Receipts: re-derive every invoice's status and balance from its receipts.
 | Dry run by default — prints a summary and writes a CSV of every invoice that
 | is wrong. Back up the database, review the CSV, then run again with --apply.
 */
Artisan::command('receipts:recompute
        {--apply : Write the corrections (default is a dry run)}
        {--reopen-short : Also reopen invoices marked completed that receipts do not fully cover}
        {--overpayments-to-credit : Move overpaid cash off invoices into the receipt\'s client credit}
        {--client= : Only this client id}', function () {
    $apply = (bool) $this->option('apply');
    $result = app(\App\Services\Receipts\BalanceRecompute::class)->run(
        $apply,
        (bool) $this->option('reopen-short'),
        (bool) $this->option('overpayments-to-credit'),
        $this->option('client') ? (int) $this->option('client') : null,
    );

    $this->info($apply ? 'APPLIED' : 'DRY RUN — nothing changed. Add --apply to write.');
    if ($result['missing_allocations']) {
        $this->warn($result['missing_allocations'] . ' receipt(s) had no allocation line' . ($apply ? ' — created.' : ' — will be created on --apply.'));
    }
    $labels = ['ok' => 'Correct already', 'fix' => 'Wrong status/balance', 'closed_short' => 'Completed but not fully paid', 'overpaid' => 'Overpaid'];
    $this->table(['Invoices', 'Count'], collect($result['summary'])->map(fn ($n, $k) => [$labels[$k], $n])->values()->all());

    if ($result['rows']) {
        $file = 'receipts-recompute-' . now()->format('Ymd-His') . '.csv';
        $path = storage_path('app/' . $file);
        $fh = fopen($path, 'w');
        fputcsv($fh, array_keys($result['rows'][0]));
        foreach ($result['rows'] as $row) {
            fputcsv($fh, $row);
        }
        fclose($fh);
        $this->line('Details: ' . $path);
        $this->table(['Invoice', 'Client', 'Total', 'Paid', 'Stored', 'Correct', 'Issue', 'Action'],
            collect($result['rows'])->take(20)->map(fn ($r) => [
                'FWSSi' . $r['invoice_id'], \Illuminate\Support\Str::limit((string) $r['client'], 24), number_format($r['total'], 2),
                number_format($r['paid_per_receipts'], 2), $r['stored_status'] . ' / ' . number_format($r['stored_balance'], 2),
                $r['correct_status'] . ' / ' . number_format($r['correct_balance'], 2), $r['issue'], $r['action'],
            ])->all());
        if (count($result['rows']) > 20) {
            $this->line('… ' . (count($result['rows']) - 20) . ' more in the CSV.');
        }
    }
})->purpose('Re-derive invoice status and balance from receipts (dry run unless --apply)');

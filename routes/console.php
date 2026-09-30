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
    // Use it before go-live to spot spellings the rules miss (add them to PayPriority::ALIASES).
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

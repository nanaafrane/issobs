<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payment priority: who must be paid first.
 *   2 = Urgent   ("Pay first")
 *   1 = Priority ("Pay early")
 *   0 = Normal
 *
 * Rules are data, not code: every condition on a rule must match (AND);
 * an "OR" is simply a second rule. The highest matching level wins.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rules = [
            // [client, level, gender, field, locations]
            [112, 2, 'female', null, null],
            [112, 1, null, null, ['DAMBAI', 'DAMONGO', 'TECHIMAN', 'SEFWI WIAWSO', 'NALERIGU', 'GOASO', 'CAPE COAST']],
            [111, 1, null, null, ['DENU', 'AKATSI', 'HOHOE', 'ODA', 'HO']],
            [111, 1, null, 4, null], // Takoradi field office, any location
            [194, 1, null, null, ['AGONA SWEDRU', 'TARKWA', 'TAKORADI', 'CAPE COAST']],
            [118, 1, null, null, ['SUNYANI', 'BOLGA', 'CAPE COAST', 'ADA']],
            [109, 1, null, null, ['TAKORADI', 'TARKWA']],
            [103, 1, null, null, ['KOTOBABI', 'ESAASE', 'SOFOLINE', 'SOKOBAN', 'BREMAN', 'CAPE COAST', 'TAKORADI']],
            [110, 1, null, null, ['AHODWO']],
            [100, 1, null, null, ['ASOKORE']],
            [116, 1, null, null, ['ATONSU', 'EMENA', 'FAWOADE']],
            [276, 1, null, null, ['KOFORIDUA', 'EBO TIMA', 'SAMPA', 'DORMAA', 'OBUASI', 'DUNKWA', 'NKAWKAW']],
        ];

        // Checked BEFORE any schema change: MySQL cannot roll back DDL, so failing
        // later would leave a half-created table and block the next migrate.
        // Every rule client must be a default Category A client (the single source of priority clients).
        $outside = array_diff(array_unique(array_column($rules, 0)), \App\Models\category::DEFAULT_A_CLIENT_IDS);
        if ($outside) {
            throw new \RuntimeException('Pay rules for clients not in category::DEFAULT_A_CLIENT_IDS: ' . implode(', ', $outside));
        }

        // Safe to re-run: a run that failed part-way (e.g. on bad dates, below) left some of
        // this in place, and MySQL cannot roll DDL back. Only create what is missing.
        $this->withLegacyDatesAllowed(function () {
            if (! Schema::hasTable('pay_priority_rules')) {
                Schema::create('pay_priority_rules', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('client_id')->index();
                    $table->unsignedTinyInteger('level');              // 2 urgent, 1 priority
                    $table->string('gender')->nullable();              // e.g. "female"; null = any
                    $table->unsignedBigInteger('field_id')->nullable(); // null = any field office
                    $table->json('locations')->nullable();              // null/empty = any location
                    $table->string('reason')->nullable();               // shown in the tooltip
                    $table->boolean('active')->default(true);
                    $table->timestamps();
                });
            }

            foreach (['employees', 'salaries'] as $name) {
                if (! Schema::hasColumn($name, 'pay_priority')) {
                    Schema::table($name, function (Blueprint $table) {
                        $table->unsignedTinyInteger('pay_priority')->default(0)->index();
                    });
                }
                if (! Schema::hasColumn($name, 'pay_priority_reason')) {
                    Schema::table($name, function (Blueprint $table) {
                        $table->string('pay_priority_reason')->nullable();
                    });
                }
            }
        });

        if (DB::table('pay_priority_rules')->exists()) {
            return; // rules already loaded by an earlier run
        }

        DB::table('pay_priority_rules')->insert(array_map(fn ($r) => [
            'client_id' => $r[0],
            'level' => $r[1],
            'gender' => $r[2],
            'field_id' => $r[3],
            'locations' => $r[4] ? json_encode($r[4]) : null,
            'reason' => null, // generated from the rule when empty
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $rules));
    }

    /**
     * Some existing rows hold '0000-00-00 00:00:00' in created_at / updated_at. When MySQL
     * rebuilds a table to add a column it re-checks every row, and Laravel's strict session
     * rejects zero dates, so the ALTER fails. Relax ONLY the date checks, ONLY for this
     * session, ONLY while these tables are altered; the original mode is always restored.
     * No data is changed.
     */
    private function withLegacyDatesAllowed(callable $callback): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $callback();
            return;
        }

        $original = (string) DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;
        $relaxed = array_diff(
            array_map('trim', explode(',', $original)),
            ['NO_ZERO_DATE', 'NO_ZERO_IN_DATE', 'STRICT_TRANS_TABLES', 'STRICT_ALL_TABLES']
        );

        DB::statement('SET SESSION sql_mode = ' . DB::getPdo()->quote(implode(',', $relaxed)));
        try {
            $callback();
        } finally {
            DB::statement('SET SESSION sql_mode = ' . DB::getPdo()->quote($original));
        }
    }

    public function down(): void
    {
        $this->withLegacyDatesAllowed(function () {
            foreach (['employees', 'salaries'] as $name) {
                if (Schema::hasColumn($name, 'pay_priority')) {
                    Schema::table($name, function (Blueprint $table) {
                        $table->dropIndex(['pay_priority']);
                        $table->dropColumn('pay_priority');
                    });
                }
                if (Schema::hasColumn($name, 'pay_priority_reason')) {
                    Schema::table($name, fn (Blueprint $table) => $table->dropColumn('pay_priority_reason'));
                }
            }
            Schema::dropIfExists('pay_priority_rules');
        });
    }
};

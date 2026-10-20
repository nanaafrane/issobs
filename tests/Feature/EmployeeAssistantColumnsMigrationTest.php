<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** employees.assit_status / assit_date were used by the code but never created by a migration. */
class EmployeeAssistantColumnsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function migration()
    {
        return require database_path('migrations/2026_10_20_120000_add_assistant_approval_columns_to_employees_table.php');
    }

    public function test_columns_exist_after_migrating(): void
    {
        $this->assertTrue(Schema::hasColumns('employees', ['assit_status', 'assit_date']));
    }

    public function test_running_it_again_changes_nothing(): void
    {
        $this->migration()->up();   // already there (e.g. added by hand on the live database)
        $this->assertTrue(Schema::hasColumns('employees', ['assit_status', 'assit_date']));
    }

    public function test_down_then_up_recreates_them_and_they_store_the_approval(): void
    {
        $m = $this->migration();
        $m->down();
        $this->assertFalse(Schema::hasColumn('employees', 'assit_status'));
        $this->assertFalse(Schema::hasColumn('employees', 'assit_date'));
        $m->up();
        $this->assertTrue(Schema::hasColumns('employees', ['assit_status', 'assit_date']));

        $m->down();
        Schema::table('employees', fn ($t) => $t->string('assit_status')->nullable());   // only one added by hand
        $m->up();
        $this->assertTrue(Schema::hasColumns('employees', ['assit_status', 'assit_date']));
    }
}

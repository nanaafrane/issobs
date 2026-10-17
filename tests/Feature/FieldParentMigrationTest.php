<?php

namespace Tests\Feature;

use App\Models\Field;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** fields.parent_field_id was used by the code but never created by a migration. */
class FieldParentMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function migration()
    {
        return require database_path('migrations/2026_10_17_230000_add_parent_field_id_to_fields_table.php');
    }

    public function test_column_exists_after_migrating(): void
    {
        $this->assertTrue(Schema::hasColumn('fields', 'parent_field_id'));
    }

    public function test_running_it_again_changes_nothing(): void
    {
        $tema = Field::create(['name' => 'Tema']);
        $shai = Field::create(['name' => 'Shai Hills', 'parent_field_id' => null]);

        $this->migration()->up();   // column already there (e.g. added by hand on the live database)

        $this->assertNull($shai->fresh()->parent_field_id);   // existing data left exactly as it was
    }

    public function test_when_added_shai_hills_becomes_a_sub_office_of_tema(): void
    {
        $tema = Field::create(['name' => 'Tema']);
        $shai = Field::create(['name' => 'Shaihills']);
        $accra = Field::create(['name' => 'Accra']);

        $migration = $this->migration();
        $migration->down();
        $this->assertFalse(Schema::hasColumn('fields', 'parent_field_id'));
        $migration->up();

        $this->assertSame($tema->id, (int) $shai->fresh()->parent_field_id);
        $this->assertNull($accra->fresh()->parent_field_id);
        $this->assertEqualsCanonicalizing(
            [$tema->id, $shai->id],
            Field::where('id', $tema->id)->orWhere('parent_field_id', $tema->id)->pluck('id')->all()
        );
        $this->assertTrue($shai->fresh()->parent->is($tema));
    }
}

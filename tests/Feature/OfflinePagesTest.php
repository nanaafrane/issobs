<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Field;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** "You're offline" panel and page instead of the red banner; drafts on the device. */
class OfflinePagesTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;

    protected function setUp(): void
    {
        parent::setUp();
        $dept = Department::create(['name' => 'Accounts']);
        $this->finance = User::factory()->create(['department_id' => $dept->id, 'name' => 'Abena Fin',
            'role_id' => Role::create(['name' => 'Finance Manager', 'department_id' => $dept->id])->id]);
        $this->finance->update(['field_id' => Field::create(['name' => 'Accra', 'user_id' => $this->finance->id, 'number' => '030'])->id]);
    }

    public function test_pages_carry_the_connection_helper_instead_of_the_red_banner(): void
    {
        $this->actingAs($this->finance)->get(route('receipt.voided'))->assertOk()
            ->assertSee('offline/connection.js')->assertSee('offline/drafts.js')
            ->assertSee(json_encode(route('forms.saved')), false)
            ->assertDontSee('connection-status')->assertDontSee('No internet connection detected');
    }

    public function test_the_login_page_registers_the_offline_page_too(): void
    {
        $this->get('/login')->assertOk()->assertSee('offline/connection.js');
    }

    public function test_the_offline_page_and_worker_ship_together(): void
    {
        $sw = file_get_contents(public_path('sw.js'));
        $page = file_get_contents(public_path('offline/offline.html'));

        $this->assertStringContainsString("'offline/offline.html'", $sw);
        $this->assertStringContainsString('__SCOPE__', $page);
        foreach (['/^generate\\/\\d+$/', '/^proforma\\/create$/', '/^receipt-multi\\/create$/', '/^fleet\\/fuel$/', '/^fleet\\/maintenance$/'] as $pattern) {
            $this->assertStringContainsString($pattern, $sw);
        }
    }

    public function test_draft_forms_are_marked(): void
    {
        foreach ([
            'sales/invoice_generate' => 'data-draft="invoice:',
            'sales/proforma_create' => 'data-draft="proforma"',
            'sales/receipt_multi_create' => 'data-draft="receipt:',
            'fleet/fuel' => 'data-draft="fuel"',
            'fleet/maintenance' => 'data-draft="maintenance"',
        ] as $view => $attr) {
            $this->assertStringContainsString($attr, file_get_contents(resource_path("views/$view.blade.php")), $view);
        }
    }

    public function test_a_draft_learns_only_its_own_saved_forms(): void
    {
        $other = User::factory()->create(['department_id' => $this->finance->department_id, 'role_id' => $this->finance->role_id]);
        DB::table('form_submissions')->insert([
            ['submission_key' => 'mine-saved', 'form' => 'invoice', 'user_id' => $this->finance->id, 'result_url' => '/invoice/1', 'created_at' => now(), 'updated_at' => now()],
            ['submission_key' => 'mine-working', 'form' => 'invoice', 'user_id' => $this->finance->id, 'result_url' => null, 'created_at' => now(), 'updated_at' => now()],
            ['submission_key' => 'theirs', 'form' => 'invoice', 'user_id' => $other->id, 'result_url' => '/invoice/2', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($this->finance)->getJson(route('forms.saved', ['keys' => ['mine-saved', 'mine-working', 'theirs', 'unknown']]))
            ->assertOk()->assertExactJson(['saved' => ['mine-saved']]);

        $this->app['auth']->forgetGuards();
        $this->getJson(route('forms.saved', ['keys' => ['mine-saved']]))->assertUnauthorized();
    }
}

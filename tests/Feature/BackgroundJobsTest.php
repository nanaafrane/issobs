<?php

namespace Tests\Feature;

use App\Jobs\SendClientStatement;
use App\Jobs\VerifyMomoName;
use App\Models\Client;
use App\Models\Department;
use App\Models\employee;
use App\Models\Field;
use App\Models\Role;
use App\Models\User;
use App\Services\Hubtel\MomoNameCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** Calls to outside services (mail server, Hubtel) run on the queue, not while the user waits. */
class BackgroundJobsTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $dept = Department::create(['name' => 'Accounts']);
        $this->finance = User::factory()->create(['department_id' => $dept->id, 'name' => 'Fin',
            'role_id' => Role::create(['name' => 'Finance Manager', 'department_id' => $dept->id])->id]);
        $field = Field::create(['name' => 'Accra', 'user_id' => $this->finance->id, 'number' => '0300000000']);
        $this->client = Client::create(['name' => 'DVLA', 'business_name' => 'DVLA HQ', 'phone_number' => '0240000000', 'address' => 'Accra',
            'field_id' => $field->id, 'status' => 'active', 'ho_status' => 'approved', 'user_id' => $this->finance->id]);
        config(['services.hubtel_momo' => ['merchant_account' => '999', 'client_id' => 'id', 'client_secret' => 'secret',
            'verify_url' => 'https://rnv.example/merchants/{merchant}/verify', 'timeout' => 5]]);
    }

    public function test_emailing_a_statement_queues_it_and_returns_at_once(): void
    {
        Queue::fake();

        $this->actingAs($this->finance)->post(route('client.statement.email', $this->client), ['to' => 'accounts@dvla.example'])
            ->assertRedirect()->assertSessionHas('primary');

        Queue::assertPushed(SendClientStatement::class, fn ($job) => $job->clientId === $this->client->id && $job->to === ['accounts@dvla.example']);
        $this->assertDatabaseCount('statement_sends', 0);   // written only once the mail has gone
    }

    public function test_momo_check_uses_the_credentials_from_config(): void
    {
        Http::fake(['rnv.example/*' => Http::response(['message' => 'Success', 'responseCode' => '0000', 'data' => ['name' => 'KOFI MENSAH', 'isRegistered' => true]])]);

        $r = app(MomoNameCheck::class)->check('0240000001', 'mtn-gh');

        $this->assertSame('KOFI MENSAH', $r['data']['name']);
        Http::assertSent(fn (HttpRequest $req) => str_starts_with($req->url(), 'https://rnv.example/merchants/999/verify')
            && $req['customerMsisdn'] === '0240000001'
            && $req->header('Authorization')[0] === 'Basic ' . base64_encode('id:secret'));
    }

    public function test_an_unreachable_hubtel_gives_a_result_instead_of_an_error(): void
    {
        Http::fake(fn () => throw new ConnectionException('timed out'));

        $r = app(MomoNameCheck::class)->check('0240000001', 'mtn-gh');

        $this->assertFalse($r['reachable']);
        $this->assertStringContainsString('Could not reach Hubtel', $r['message']);
    }

    public function test_the_momo_job_saves_the_registered_name(): void
    {
        Http::fake(['rnv.example/*' => Http::response(['message' => 'Success', 'responseCode' => '0000', 'data' => ['name' => 'KOFI MENSAH', 'isRegistered' => true]])]);
        // These columns exist in production but no migration creates them.
        foreach (['message', 'responseCode', 'reg_name', 'status_momo'] as $column) {
            if (! \Illuminate\Support\Facades\Schema::hasColumn('employees', $column)) {
                \Illuminate\Support\Facades\Schema::table('employees', fn ($t) => $t->string($column)->nullable());
            }
        }
        $e = new employee();
        $e->forceFill(['name' => 'Kofi Mensah', 'status' => 'Active', 'phone_number' => '0240000001', 'channel' => 'mtn-gh',
            'client_id' => $this->client->id, 'field_id' => $this->client->field_id, 'basic_salary' => 1500, 'allowances' => 0,
            'payment_type' => 'Cash', 'location' => 'Gate', 'user_id' => $this->finance->id])->save();

        (new VerifyMomoName($e->id))->handle(app(MomoNameCheck::class));

        $this->assertSame('KOFI MENSAH', $e->fresh()->reg_name);
        $this->assertSame('0000', (string) $e->fresh()->responseCode);
    }

    public function test_no_hubtel_credentials_are_left_in_the_code(): void
    {
        $hits = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path())) as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.php') && preg_match("/base64_encode\\('[^']+:[^']+'\\)/", file_get_contents($file->getPathname()))) {
                $hits[] = $file->getPathname();
            }
        }
        $this->assertSame([], $hits);
    }
}

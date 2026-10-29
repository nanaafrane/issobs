<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Support\Subscription;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Monthly subscription: more than 30 days past the due date the system is
 * read-only. GETs work, every write is refused, until a renewal or payment.
 */
class SubscriptionLockTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'subscription.enforce' => true,
            'subscription.starts_on' => '2026-11-01',
            'subscription.grace_days' => 30,
            'subscription.warn_days_before' => 7,
            'subscription.renewals' => [],
            'subscription.bypass_emails' => [],
            'subscription.monthly_fee' => 500,
            'subscription.pay_months' => [1, 2, 3, 6, 12],
            'subscription.hubtel.enabled' => false,
        ]);

        $dept = Department::create(['name' => 'Finance']);
        $role = Role::forceCreate(['id' => 2, 'name' => 'Finance Manager', 'department_id' => $dept->id]);
        $this->user = User::factory()->create(['department_id' => $dept->id, 'role_id' => $role->id]);

        Route::middleware('web')->group(function () {
            Route::get('_sub/read', fn () => 'read ok');
            Route::post('_sub/save', fn () => 'saved');
            Route::put('_sub/save', fn () => 'saved');
            Route::patch('_sub/save', fn () => 'saved');
            Route::delete('_sub/save', fn () => 'saved');
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function on(string $date): Subscription
    {
        Carbon::setTestNow(Carbon::parse($date.' 10:00:00', 'Africa/Accra'));
        app()->forgetScopedInstances();

        return app(Subscription::class);
    }

    public function test_status_moves_from_paid_up_to_overdue_to_read_only(): void
    {
        $this->assertSame('active', $this->on('2026-10-20')->status());
        $this->assertSame('due_soon', $this->on('2026-10-27')->status());
        $this->assertSame('due_soon', $this->on('2026-11-01')->status());   // due today: not late yet
        $this->assertSame('overdue', $this->on('2026-11-02')->status());
        $this->assertSame('overdue', $this->on('2026-12-01')->status());   // 30 days late: last working day
        $this->assertSame('locked', $this->on('2026-12-02')->status());    // more than 30 days late

        $s = $this->on('2026-12-02');
        $this->assertSame('2026-11-01', $s->dueDate()->toDateString());
        $this->assertSame('2026-12-02', $s->lockDate()->toDateString());
    }

    public function test_writes_work_while_overdue_but_within_grace(): void
    {
        $this->on('2026-11-20');
        $this->actingAs($this->user)->post('_sub/save')->assertOk()->assertSee('saved');
    }

    public function test_locked_system_allows_reads_and_refuses_every_write_method(): void
    {
        $this->on('2026-12-10');
        $this->actingAs($this->user);

        $this->get('_sub/read')->assertOk()->assertSee('read ok');

        foreach (['post', 'put', 'patch', 'delete'] as $method) {
            $this->from('/_sub/read')->{$method}('_sub/save')
                ->assertRedirect('/_sub/read')
                ->assertSessionHas('error');
        }
    }

    public function test_locked_json_requests_get_402_with_payment_link(): void
    {
        $this->on('2026-12-10');

        $this->actingAs($this->user)->postJson('_sub/save')
            ->assertStatus(402)
            ->assertJson(['code' => 'SUBSCRIPTION_LOCKED', 'due_date' => '2026-11-01'])
            ->assertJsonPath('pay_url', url('billing'));
    }

    public function test_locked_system_still_lets_people_log_out_and_reach_billing(): void
    {
        $this->on('2026-12-10');

        $this->actingAs($this->user)->post('logout')->assertRedirect();
        $this->assertGuest();

        $this->actingAs($this->user)->get('billing')->assertOk()
            ->assertSee('Read-only')
            ->assertSee('subscription-readonly', false);   // the page script that greys out buttons
    }

    public function test_links_that_change_data_are_blocked_too(): void
    {
        $this->on('2026-12-10');

        $this->actingAs($this->user)->from('/home')->get('terminateClient/1')
            ->assertRedirect('/home')->assertSessionHas('error');
    }

    public function test_a_renewal_in_code_reopens_the_system(): void
    {
        $this->on('2026-12-10');
        $this->actingAs($this->user)->post('_sub/save')->assertRedirect();

        config(['subscription.renewals' => [
            ['months' => 2, 'paid_on' => '2026-12-10', 'reference' => 'Cash Nov+Dec'],
        ]]);
        $s = $this->on('2026-12-10');

        $this->assertSame('2027-01-01', $s->dueDate()->toDateString());
        $this->assertSame('active', $s->status());
        $this->actingAs($this->user)->post('_sub/save')->assertOk();
    }

    public function test_exempt_login_can_still_save(): void
    {
        $this->on('2026-12-10');
        config(['subscription.bypass_emails' => [strtoupper($this->user->email)]]);

        $this->actingAs($this->user)->post('_sub/save')->assertOk();
    }

    public function test_switching_enforcement_off_never_locks(): void
    {
        config(['subscription.enforce' => false]);
        $this->on('2027-06-01');

        $this->actingAs($this->user)->post('_sub/save')->assertOk();
    }

    public function test_hubtel_payment_counts_only_after_hubtel_confirms_it_and_only_once(): void
    {
        config([
            'subscription.hubtel.enabled' => true,
            'subscription.hubtel.api_id' => 'id',
            'subscription.hubtel.api_key' => 'key',
            'subscription.hubtel.merchant_account' => '123',
        ]);
        $this->on('2026-12-10');

        Http::fake([
            'payproxyapi.hubtel.com/*' => Http::response(['responseCode' => '0000', 'data' => ['checkoutUrl' => 'https://pay.hubtel.com/abc', 'checkoutId' => 'abc']]),
            'api-txnstatus.hubtel.com/*' => Http::sequence()
                ->push(['data' => ['status' => 'Unpaid', 'amount' => 0]])   // a forged/early callback
                ->push(['data' => ['status' => 'Paid', 'amount' => 1000]]),
        ]);

        // Paying works while locked.
        $this->actingAs($this->user)->post('billing/pay', ['months' => 2])->assertRedirect('https://pay.hubtel.com/abc');
        $payment = SubscriptionPayment::sole();
        $this->assertSame('pending', $payment->status);
        $this->assertEquals(1000, $payment->amount);

        $callback = ['ResponseCode' => '0000', 'Status' => 'Success', 'Data' => ['ClientReference' => $payment->client_reference, 'Amount' => 1000]];

        // Callback says success, but Hubtel's status API says unpaid: not counted.
        $this->postJson('billing/hubtel/callback', $callback)->assertOk();
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertTrue($this->on('2026-12-10')->locked());

        // A real payment: counted once, the system opens, a repeated callback adds nothing.
        $payment->update(['status' => 'pending']);
        $this->postJson('billing/hubtel/callback', $callback)->assertOk();
        $this->postJson('billing/hubtel/callback', $callback)->assertOk();

        $this->assertSame('paid', $payment->fresh()->status);
        $s = $this->on('2026-12-10');
        $this->assertSame('2027-01-01', $s->dueDate()->toDateString());
        $this->assertFalse($s->locked());
        Http::assertSentCount(3);   // initiate + two status checks; the repeat callback asked nothing
    }

    private function clerk(): User
    {
        $role = Role::forceCreate(['id' => 9, 'name' => 'Invoice', 'department_id' => $this->user->department_id]);

        return User::factory()->create(['department_id' => $this->user->department_id, 'role_id' => $role->id]);
    }

    public function test_only_finance_manager_and_director_see_overdue_details_and_pay(): void
    {
        $this->on('2026-11-20');   // overdue, not yet read-only
        $director = User::factory()->create(['department_id' => $this->user->department_id,
            'role_id' => Role::forceCreate(['id' => 3, 'name' => 'Director', 'department_id' => $this->user->department_id])->id]);
        $clerk = $this->clerk();

        foreach ([$this->user, $director] as $manager) {
            $this->actingAs($manager)->get('billing')->assertOk()
                ->assertSee('Subscription overdue')->assertSee('Pay now')->assertSee('href="'.url('billing').'"', false);
        }

        // Other roles: no banner, no Subscription link, no billing page, no paying.
        $this->actingAs($clerk);
        $html = $this->get('receivables/aged')->getContent();
        $this->assertStringNotContainsString('Subscription overdue', $html);
        $this->assertStringNotContainsString('SYSTEM INACTIVE', $html);
        $this->assertStringNotContainsString(url('billing'), $html);
        $this->get('billing')->assertForbidden();
        $this->post('billing/pay', ['months' => 1])->assertForbidden();
    }

    public function test_other_roles_only_see_system_inactive_when_locked(): void
    {
        $this->on('2026-12-10');
        $clerk = $this->clerk();

        $html = $this->actingAs($clerk)->get('receivables/aged')->assertOk()->getContent();
        $this->assertStringContainsString('SYSTEM INACTIVE &ndash; READ ONLY', $html);
        $this->assertStringNotContainsString('Pay / renew', $html);
        $this->assertStringNotContainsString('was due on', $html);
        $this->assertStringContainsString('subscription-readonly', $html);   // buttons still greyed out

        $this->from('/home')->post('_sub/save')->assertRedirect('/home')
            ->assertSessionHas('error', fn ($m) => str_starts_with($m, 'SYSTEM INACTIVE'));
        $this->postJson('_sub/save')->assertStatus(402)->assertJsonMissingPath('pay_url')->assertJsonMissingPath('due_date');
    }

    public function test_months_list_comes_from_config_and_other_values_are_refused(): void
    {
        config([
            'subscription.pay_months' => [1, 3],
            'subscription.hubtel.enabled' => true,
            'subscription.hubtel.api_id' => 'id',
            'subscription.hubtel.api_key' => 'key',
            'subscription.hubtel.merchant_account' => '123',
        ]);
        $this->on('2026-11-20');
        Http::fake(['payproxyapi.hubtel.com/*' => Http::response(['data' => ['checkoutUrl' => 'https://pay.hubtel.com/x']])]);

        $this->actingAs($this->user)->get('billing')->assertOk()
            ->assertSee('<option value="1">', false)->assertSee('<option value="3">', false)
            ->assertDontSee('<option value="12">', false);

        $this->post('billing/pay', ['months' => 12])->assertSessionHasErrors('months');
        $this->post('billing/pay', ['months' => 3])->assertRedirect('https://pay.hubtel.com/x');
        $this->assertSame(3, SubscriptionPayment::sole()->months);
    }

    public function test_one_month_only_shows_the_amount_without_a_list(): void
    {
        config([
            'subscription.pay_months' => [1],
            'subscription.hubtel.enabled' => true,
            'subscription.hubtel.api_id' => 'id',
            'subscription.hubtel.api_key' => 'key',
            'subscription.hubtel.merchant_account' => '123',
        ]);
        $this->on('2026-11-20');

        $this->actingAs($this->user)->get('billing')->assertOk()
            ->assertSee('1 month - GHS 500.00')
            ->assertSee('<input type="hidden" name="months" value="1">', false)
            ->assertDontSee('<select name="months"', false);
        $this->post('billing/pay', ['months' => 2])->assertSessionHasErrors('months');
    }

    public function test_pay_button_explains_when_online_payment_is_not_on_yet(): void
    {
        $this->on('2026-12-10');

        $this->actingAs($this->user)->get('billing')->assertOk()->assertSee('Online payment is coming soon');
        $this->actingAs($this->user)->post('billing/pay', ['months' => 1])->assertRedirect('billing')->assertSessionHas('error');
        $this->assertSame(0, SubscriptionPayment::count());
    }
}

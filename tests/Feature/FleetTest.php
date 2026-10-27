<?php

namespace Tests\Feature;

use App\Models\AccountPayment;
use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\Department;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\Field;
use App\Models\FuelEntry;
use App\Models\FuelPayout;
use App\Models\MaintenanceType;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use App\Services\Banking\AccountBook;
use App\Services\Banking\AccountReports;
use App\Services\Banking\CashBook;
use App\Services\Fleet\FleetReports;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fleet: fuel follows the overtime road (entries -> approval -> owed -> one pay-out = one
 * expense + one payment); maintenance is one expense per approved job; the cash book spreads
 * every payment over its items.
 */
class FleetTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;
    private User $accraManager;
    private User $temaManager;
    private User $accraOfficer;
    private Field $accra;
    private Field $tema;
    private Bank $cash;
    private Bank $gcb;
    private Vehicle $patrol;
    private Vehicle $bike;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-22 10:00:00');
        $dept = Department::create(['name' => 'Accounts']);
        $ops = Department::create(['name' => 'Operations Field']);
        $fm = Role::forceCreate(['id' => 2, 'name' => 'Finance Manager', 'department_id' => $dept->id]);
        $field = Role::forceCreate(['id' => 5, 'name' => 'Field Manager', 'department_id' => $ops->id]);
        $officer = Role::forceCreate(['id' => 6, 'name' => 'Officer', 'department_id' => $ops->id]);
        $this->finance = User::factory()->create(['department_id' => $dept->id, 'role_id' => $fm->id, 'name' => 'Abena Finance']);
        $this->accra = Field::create(['name' => 'Accra', 'user_id' => $this->finance->id, 'number' => '1']);
        $this->tema = Field::create(['name' => 'Tema', 'user_id' => $this->finance->id, 'number' => '2']);
        $this->accraManager = User::factory()->create(['department_id' => $ops->id, 'role_id' => $field->id, 'field_id' => $this->accra->id, 'name' => 'Kojo Accra']);
        $this->temaManager = User::factory()->create(['department_id' => $ops->id, 'role_id' => $field->id, 'field_id' => $this->tema->id, 'name' => 'Esi Tema']);
        $this->accraOfficer = User::factory()->create(['department_id' => $ops->id, 'role_id' => $officer->id, 'field_id' => $this->accra->id, 'name' => 'Yaw Officer']);

        $this->gcb = Bank::create(['name' => 'GCB Main', 'branch' => 'Accra', 'acc_number' => '111', 'total' => 50000, 'opening_balance' => 50000, 'opening_date' => '2026-01-01']);
        $this->cash = Bank::create(['name' => 'Main Cash', 'kind' => 'cash', 'branch' => '', 'acc_number' => '', 'custodian' => 'Ama', 'total' => 0, 'opening_balance' => 0, 'opening_date' => '2026-01-01']);
        $this->actingAs($this->finance);
        app(AccountBook::class)->transfer(['to_bank_id' => $this->cash->id, 'transfer_date' => '2026-10-01',
            'lines' => [['from_bank_id' => $this->gcb->id, 'amount' => 20000, 'method' => 'cheque', 'reference' => '000457']]]);

        $this->patrol = Vehicle::create(['reg_no' => 'gn 2064-18', 'name' => 'Nissan Patrol', 'kind' => 'suv', 'fuel_type' => 'diesel', 'field_id' => $this->accra->id, 'driver_name' => 'SIMON EWORYI', 'status' => 'active']);
        $this->bike = Vehicle::create(['reg_no' => 'M-21-GS-1118', 'kind' => 'motorbike', 'fuel_type' => 'super', 'field_id' => $this->accra->id, 'driver_name' => 'EMMANUEL LINCOLN', 'status' => 'active']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function fuel(array $over = [], ?User $as = null)
    {
        return $this->actingAs($as ?? $this->accraOfficer)->post(route('fleet.fuel.store'), array_merge([
            'entry_date' => '2026-10-02', 'vehicle_id' => $this->patrol->id, 'purpose' => 'DEPLOYMENT & PATROL',
            'amount' => 700, 'price_per_litre' => 18.39, 'station' => 'nova', 'odometer' => 120000,
        ], $over));
    }

    private function approveFully(FuelEntry|VehicleMaintenance $r, string $route): void
    {
        $this->actingAs($this->accraManager)->post(route($route, $r))->assertSessionHasNoErrors();
        $this->actingAs($this->finance)->post(route($route, $r))->assertSessionHasNoErrors();
    }

    private function maintenance(string $type, array $over = [])
    {
        return $this->actingAs($this->accraOfficer)->post(route('fleet.maintenance.store'), array_merge([
            'vehicle_id' => $this->patrol->id, 'maintenance_type_id' => MaintenanceType::where('name', $type)->value('id'),
            'service_date' => '2026-10-05', 'vendor' => 'Abossey Okai', 'amount' => 5000, 'odometer' => 121000,
        ], $over));
    }

    public function test_vehicle_plate_is_normalised_and_unique(): void
    {
        $this->assertSame('GN-2064-18', $this->patrol->reg_no);
        $this->post(route('fleet.vehicles.store'), ['reg_no' => 'gr 1788 21', 'kind' => 'pickup', 'fuel_type' => 'super', 'field_id' => $this->accra->id])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Vehicle::where('reg_no', 'GR-1788-21')->exists());
        $this->post(route('fleet.vehicles.store'), ['reg_no' => 'GN 2064 18', 'kind' => 'car', 'fuel_type' => 'diesel', 'field_id' => $this->accra->id])
            ->assertSessionHasErrors('reg_no');                                             // same plate as GN-2064-18
    }

    public function test_fuel_entry_works_out_litres_and_defaults_from_the_vehicle(): void
    {
        $this->fuel()->assertSessionHasNoErrors();
        $f = FuelEntry::sole();
        $this->assertEquals(38.06, (float) $f->litres);           // 700 / 18.39, as on the sheet
        $this->assertSame('diesel', $f->fuel_type);
        $this->assertSame('SIMON EWORYI', $f->driver_name);
        $this->assertSame($this->accra->id, (int) $f->field_id);
        $this->assertSame('NOVA', $f->station);
        $this->assertSame('approved', $f->status_1);
        $this->assertNull($f->status_2);

        $this->fuel(['amount' => 700, 'litres' => 10, 'price_per_litre' => 18.39])->assertSessionHasErrors('litres');   // 10 L is not 700
        $this->fuel(['entry_date' => '2026-10-03', 'odometer' => 119000])->assertSessionHasErrors('odometer');     // km going backwards
        $this->fuel(['entry_date' => '2026-10-01', 'odometer' => 125000])->assertSessionHasErrors('odometer');     // above a later reading
    }

    public function test_fuel_goes_through_branch_review_and_finance_like_overtime(): void
    {
        $this->fuel();
        $f = FuelEntry::sole();

        $this->actingAs($this->temaManager)->post(route('fleet.fuel.approve', $f))->assertForbidden();      // other office
        $this->actingAs($this->accraOfficer)->post(route('fleet.fuel.approve', $f))->assertForbidden();
        $this->actingAs($this->accraManager)->post(route('fleet.fuel.approve', $f))->assertSessionHasNoErrors();
        $this->assertSame(3, $f->fresh()->currentStage());
        $this->actingAs($this->accraManager)->post(route('fleet.fuel.approve', $f))->assertForbidden();     // stage 3 is finance
        $this->actingAs($this->finance)->post(route('fleet.fuel.approve', $f))->assertSessionHasNoErrors();

        $f->refresh();
        $this->assertTrue($f->isFullyApproved());
        $this->assertSame(0, Expense::count());                                                    // no expense per fill-up
        $this->assertCount(0, app(AccountReports::class)->awaitingPayment());
        $this->assertTrue(FuelEntry::owed()->whereKey($f->id)->exists());
        // the officer can no longer edit once branch review has started
        $this->actingAs($this->accraOfficer)->put(route('fleet.fuel.update', $f), ['entry_date' => '2026-10-02', 'vehicle_id' => $this->patrol->id, 'amount' => 800, 'price_per_litre' => 18.39])->assertForbidden();
    }

    public function test_fuel_pay_out_is_one_expense_and_one_payment_and_can_be_cancelled(): void
    {
        $this->fuel();
        $this->fuel(['entry_date' => '2026-10-04', 'odometer' => 120400]);
        $this->fuel(['vehicle_id' => $this->bike->id, 'amount' => 150, 'price_per_litre' => 16.97, 'odometer' => null]);
        $this->fuel(['vehicle_id' => $this->bike->id, 'entry_date' => '2026-10-06', 'amount' => 149, 'price_per_litre' => 16.97, 'odometer' => null]);
        [$a, $b, $c, $pending] = FuelEntry::orderBy('id')->get()->all();
        foreach ([$a, $b, $c] as $f) {
            $this->approveFully($f, 'fleet.fuel.approve');
        }

        $this->actingAs($this->accraManager)->post(route('fleet.fuel.payouts.store'), [])->assertForbidden();   // finance only
        $this->actingAs($this->finance)->post(route('fleet.fuel.payouts.store'), [
            'field_id' => $this->accra->id, 'period_from' => '2026-10-01', 'period_to' => '2026-10-21',
            'fuel_ids' => [$a->id, $b->id, $c->id, $pending->id], 'bank_id' => $this->cash->id, 'payee' => 'NOVA', 'payment_date' => '2026-10-21',
        ])->assertSessionHasErrors('fuel_ids');                                                    // the pending one is refused

        $this->post(route('fleet.fuel.payouts.store'), [
            'field_id' => $this->accra->id, 'period_from' => '2026-10-01', 'period_to' => '2026-10-21', 'station' => 'NOVA',
            'fuel_ids' => [$a->id, $b->id, $c->id], 'bank_id' => $this->cash->id, 'payee' => 'NOVA', 'payment_date' => '2026-10-21',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $p = FuelPayout::sole();
        $this->assertEquals(1550, (float) $p->total);
        $this->assertEquals(18450, (float) $this->cash->fresh()->total);
        $this->assertSame(1, BankTransaction::where('bank_id', $this->cash->id)->where('channel', 'payment')->count());
        $pay = AccountPayment::sole();
        $this->assertSame('fuel', $pay->category);
        $this->assertSame('cash', $pay->method);
        $expense = Expense::sole();
        $this->assertSame('Fuel', $expense->type->name);
        $this->assertSame($pay->id, (int) $expense->payment_id);
        $this->assertSame($this->accra->id, (int) $expense->field_id);
        $this->assertSame(3, FuelEntry::where('payout_id', $p->id)->count());
        $this->assertTrue($a->fresh()->isLockedForPay());
        $this->actingAs($this->finance)->put(route('fleet.fuel.update', $a), ['entry_date' => '2026-10-02', 'vehicle_id' => $this->patrol->id, 'amount' => 1, 'price_per_litre' => 1])->assertForbidden();
        $this->get(route('fleet.fuel.payouts.show', $p))->assertOk()->assertSee('NOVA');

        $this->post(route('fleet.fuel.payouts.cancel', $p), ['cancel_reason' => 'paid twice'])->assertSessionHasNoErrors();
        $this->assertEquals(20000, (float) $this->cash->fresh()->total);
        $this->assertSame('cancelled', $p->fresh()->status);
        $this->assertSame(0, Expense::count());
        $this->assertSame(3, FuelEntry::owed()->count());
    }

    public function test_maintenance_becomes_one_expense_under_its_item_when_approved(): void
    {
        $this->maintenance('Tyres', ['description' => '4 tyres'])->assertSessionHasNoErrors();
        $m = VehicleMaintenance::sole();
        $this->assertNull($m->expense_id);
        $this->assertSame(121000 + 40000, (int) $m->next_due_km);                   // Tyres repeat every 40,000 km

        $this->approveFully($m, 'fleet.maintenance.approve');
        $m->refresh();
        $expense = Expense::findOrFail($m->expense_id);
        $this->assertSame('Vehicle Repairs & Maintenance', $expense->type->name);
        $this->assertEquals(5000, (float) $expense->amount);
        $this->assertSame('approved', $expense->status_3);
        $this->assertStringContainsString('GN-2064-18', $expense->description);
        $this->assertCount(1, app(AccountReports::class)->awaitingPayment());

        // finance corrects the amount before paying: the expense follows
        $this->actingAs($this->finance)->put(route('fleet.maintenance.update', $m), [
            'vehicle_id' => $this->patrol->id, 'maintenance_type_id' => $m->maintenance_type_id, 'service_date' => '2026-10-05', 'amount' => 4800, 'odometer' => 121000,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(4800, (float) $expense->fresh()->amount);

        // paid from Payments › Pay approved expenses: then it is locked
        $this->post(route('money.payments.store'), ['mode' => 'expenses', 'expense_ids' => [$expense->id], 'bank_id' => $this->cash->id, 'method' => 'cash', 'payee' => 'Abossey Okai', 'payment_date' => '2026-10-21'])
            ->assertSessionHasNoErrors();
        $this->assertTrue($m->fresh()->isPaid());
        $this->put(route('fleet.maintenance.update', $m), ['vehicle_id' => $this->patrol->id, 'maintenance_type_id' => $m->maintenance_type_id, 'service_date' => '2026-10-05', 'amount' => 1])->assertForbidden();
        $this->delete(route('fleet.maintenance.destroy', $m))->assertForbidden();
    }

    public function test_rejected_or_deleted_maintenance_leaves_no_expense(): void
    {
        $this->maintenance('Battery', ['amount' => 1100]);
        $m = VehicleMaintenance::sole();
        $this->actingAs($this->accraManager)->post(route('fleet.maintenance.reject', $m))->assertSessionHasNoErrors();
        $this->assertTrue($m->fresh()->isRejected());
        $this->assertSame(0, Expense::count());

        $this->maintenance('Brakes', ['amount' => 400]);
        $b = VehicleMaintenance::latest('id')->first();
        $this->approveFully($b, 'fleet.maintenance.approve');
        $this->assertSame(1, Expense::count());
        $this->actingAs($this->finance)->delete(route('fleet.maintenance.destroy', $b))->assertSessionHasNoErrors();
        $this->assertSame(0, Expense::count());
        $this->assertSoftDeleted($b);
    }

    public function test_roadworthy_and_insurance_track_expiry(): void
    {
        $this->maintenance('Roadworthy', ['service_date' => '2025-11-10', 'amount' => 350]);   // expires 10 Nov 2026: due soon
        $road = VehicleMaintenance::sole();
        $this->assertSame('2026-11-10', $road->valid_until->toDateString());
        $this->approveFully($road, 'fleet.maintenance.approve');
        $this->assertSame('License & Registration', Expense::sole()->type->name);

        $due = app(FleetReports::class)->due();
        $patrolRoad = $due->first(fn ($d) => $d['vehicle']->id === $this->patrol->id && $d['type']->name === 'Roadworthy');
        $this->assertSame('soon', $patrolRoad['state']);
        $this->assertSame(19, $patrolRoad['days']);
        $bikeInsurance = $due->first(fn ($d) => $d['vehicle']->id === $this->bike->id && $d['type']->name === 'Insurance');
        $this->assertSame('missing', $bikeInsurance['state']);                                      // never recorded

        $this->maintenance('Insurance', ['service_date' => '2025-09-01', 'valid_until' => '2026-08-31', 'amount' => 2500]);
        $ins = app(FleetReports::class)->due()->first(fn ($d) => $d['vehicle']->id === $this->patrol->id && $d['type']->name === 'Insurance');
        $this->assertSame('overdue', $ins['state']);
        $this->get(route('fleet.vehicles'))->assertOk()->assertSee('Overdue / expired');
    }

    public function test_users_can_add_maintenance_types_tied_to_an_expense_item(): void
    {
        $item = ExpenseType::firstOrCreate(['name' => 'Fire Safety', 'scope' => 'field']);
        $this->actingAs($this->accraOfficer)->post(route('fleet.maintenance.types.store'), ['name' => 'Fire extinguisher'])->assertForbidden();
        $this->actingAs($this->accraManager)->post(route('fleet.maintenance.types.store'), ['name' => 'Fire extinguisher', 'expense_type_id' => $item->id, 'repeat_months' => 12, 'is_document' => 1])
            ->assertSessionHasNoErrors();
        $t = MaintenanceType::where('name', 'Fire extinguisher')->sole();
        $this->assertTrue($t->is_document);
        $this->assertSame('Fire Safety', $t->resolveExpenseType()->name);
        $this->assertGreaterThanOrEqual(10, MaintenanceType::count());                             // the seeded common types are there
        $this->get(route('fleet.maintenance.types'))->assertOk()->assertSee('Fire extinguisher')->assertSee('Roadworthy');
    }

    public function test_offices_see_only_their_own_vehicles(): void
    {
        $temaCar = Vehicle::create(['reg_no' => 'GR-1788-21', 'kind' => 'pickup', 'fuel_type' => 'super', 'field_id' => $this->tema->id, 'status' => 'active']);
        $this->actingAs($this->accraOfficer)->get(route('fleet.vehicles.show', $temaCar))->assertForbidden();
        $this->actingAs($this->accraOfficer)->get(route('fleet.vehicles'))->assertOk()->assertSee('GN-2064-18')->assertDontSee('GR-1788-21');
        $this->actingAs($this->accraOfficer)->post(route('fleet.fuel.store'), ['entry_date' => '2026-10-02', 'vehicle_id' => $temaCar->id, 'amount' => 100, 'price_per_litre' => 16.97])->assertForbidden();
        $this->actingAs($this->finance)->get(route('fleet.vehicles'))->assertSee('GR-1788-21');
    }

    public function test_fleet_report_gives_cost_and_km_per_litre_per_vehicle(): void
    {
        $this->fuel(['entry_date' => '2026-10-02', 'odometer' => 120000, 'amount' => 700]);     // 38.06 L
        $this->fuel(['entry_date' => '2026-10-04', 'odometer' => 120300, 'amount' => 367.8]);   // 20 L
        $this->fuel(['entry_date' => '2026-10-06', 'odometer' => 120600, 'amount' => 367.8]);   // 20 L
        $this->maintenance('Tyres', ['service_date' => '2026-10-05', 'amount' => 5000, 'odometer' => 120500]);

        $r = app(FleetReports::class)->vehicleSummary(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'))['rows']->firstWhere('vehicle.id', $this->patrol->id);
        $this->assertSame(3, $r['fills']);
        $this->assertEquals(1435.6, $r['fuel']);
        $this->assertEquals(5000, $r['maintenance']);
        $this->assertEquals(5000, $r['by_type']['Tyres']);
        $this->assertSame(600, $r['km']);
        $this->assertEquals(15.0, $r['km_per_litre']);                                            // 600 km on the 40 L put in after the first reading
        $this->assertEquals(round(6435.6 / 600, 2), $r['cost_per_km']);
        $this->get(route('fleet.report', ['month' => '2026-10']))->assertOk()->assertSee('GN-2064-18')->assertSee('15');
    }

    public function test_cash_book_gives_the_total_spent_on_each_item_like_the_main_cash_sheet(): void
    {
        // fuel pay-out (Fuel, Accra)
        $this->fuel();
        $f = FuelEntry::sole();
        $this->approveFully($f, 'fleet.fuel.approve');
        $this->actingAs($this->finance)->post(route('fleet.fuel.payouts.store'), ['field_id' => $this->accra->id, 'period_from' => '2026-10-01', 'period_to' => '2026-10-21',
            'fuel_ids' => [$f->id], 'bank_id' => $this->cash->id, 'payee' => 'NOVA', 'payment_date' => '2026-10-03'])->assertSessionHasNoErrors();
        // tyres (Vehicle Repairs & Maintenance, Accra) and rent (Head office) paid together in one payment
        $this->maintenance('Tyres', ['amount' => 5000]);
        $tyres = VehicleMaintenance::sole();
        $this->approveFully($tyres, 'fleet.maintenance.approve');
        $rent = Expense::create(['description' => 'Office rent', 'amount' => 10000, 'expense_date' => '2026-10-04', 'expense_type_id' => ExpenseType::firstOrCreate(['name' => 'Rent', 'scope' => 'corporate'])->id,
            'user_1' => $this->finance->id, 'status_1' => 'approved', 'status_2' => 'approved', 'status_3' => 'approved']);
        $this->actingAs($this->finance)->post(route('money.payments.store'), ['mode' => 'expenses', 'expense_ids' => [$tyres->fresh()->expense_id, $rent->id], 'bank_id' => $this->cash->id,
            'method' => 'cash', 'payee' => 'Various', 'payment_date' => '2026-10-05'])->assertSessionHasNoErrors();
        // a payment that is not an expense, and cash banked
        app(AccountBook::class)->pay(['bank_id' => $this->cash->id, 'payee' => 'Chemsolve guards', 'method' => 'cash', 'payment_date' => '2026-10-06', 'category' => 'salaries', 'amount' => 3030]);
        app(AccountBook::class)->transfer(['to_bank_id' => $this->gcb->id, 'transfer_date' => '2026-10-07', 'lines' => [['from_bank_id' => $this->cash->id, 'amount' => 1000, 'method' => 'deposit']]]);
        // paid from the bank, not from Main Cash
        app(AccountBook::class)->pay(['bank_id' => $this->gcb->id, 'payee' => 'ECG', 'method' => 'transfer', 'payment_date' => '2026-10-08', 'category' => 'rent_utilities', 'amount' => 500]);

        $a = app(CashBook::class)->analysis($this->cash, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));
        $this->assertEquals(0, $a['opening']);
        $this->assertEquals(20000, $a['receipts']);
        $this->assertEquals(700 + 15000 + 3030 + 1000, $a['payments']);
        $this->assertEquals((float) $this->cash->fresh()->total, $a['closing']);
        $this->assertEquals(700, $a['items']['Fuel']);
        $this->assertEquals(5000, $a['items']['Vehicle Repairs & Maintenance']);
        $this->assertEquals(10000, $a['items']['Rent']);
        $this->assertEquals(3030, $a['items']['Salaries & wages']);
        $this->assertEquals(1000, $a['items'][CashBook::TRANSFER_OUT]);
        $this->assertArrayNotHasKey('Rent & utilities', $a['items']->all());                         // paid from GCB, not Main Cash
        $this->assertEquals($a['payments'], $a['analysed']);                                          // every cedi out is on an item
        $this->assertEquals(5000, $a['matrix']['Vehicle Repairs & Maintenance']['Accra']);
        $this->assertEquals(10000, $a['matrix']['Rent']['Head office / not set']);
        // the shared payment line is split over its two items
        $shared = $a['lines']->first(fn ($l) => count($l['splits']) === 2);
        $this->assertEqualsCanonicalizing([5000.0, 10000.0], array_column($shared['splits'], 'amount'));

        // all accounts: moves between our accounts are not spending
        $all = app(CashBook::class)->analysis(null, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));
        $this->assertArrayNotHasKey(CashBook::TRANSFER_OUT, $all['items']->all());
        $this->assertEquals(500, $all['items']['Rent & utilities']);

        // a cancelled payment comes off its item
        app(AccountBook::class)->cancelPayment(AccountPayment::where('category', 'salaries')->sole(), 'entered twice');
        $after = app(CashBook::class)->analysis($this->cash, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));
        $this->assertArrayNotHasKey('Salaries & wages', $after['items']->all());

        $this->get(route('money.cashbook', ['bank' => $this->cash->id, 'month' => '2026-10']))->assertOk()->assertSee('Vehicle Repairs &amp; Maintenance', false)->assertSee('Total spent per item');
        $this->get(route('money.cashbook', ['bank' => 'all', 'month' => '2026-10']))->assertOk();
        $res = $this->get(route('money.cashbook.export', ['bank' => $this->cash->id, 'month' => '2026-10']));
        $res->assertOk();
        $this->assertStringContainsString('CASH_BOOK.xlsx', (string) $res->headers->get('content-disposition'));
        $this->actingAs($this->accraOfficer)->get(route('money.cashbook'))->assertForbidden();
    }

    public function test_cash_book_sheet_has_the_main_cash_columns(): void
    {
        $this->fuel();
        $f = FuelEntry::sole();
        $this->approveFully($f, 'fleet.fuel.approve');
        $this->actingAs($this->finance)->post(route('fleet.fuel.payouts.store'), ['field_id' => $this->accra->id, 'period_from' => '2026-10-01', 'period_to' => '2026-10-21',
            'fuel_ids' => [$f->id], 'bank_id' => $this->cash->id, 'payee' => 'NOVA', 'payment_date' => '2026-10-03']);

        $book = app(CashBook::class);
        $rows = $book->sheet($book->analysis($this->cash, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31')));
        $this->assertSame(['DATE', 'Payment details', 'CHEQ NO:', 'PAYMENT', 'RECEIPT', 'BALANCE', 'FUEL'], $rows[4]);
        $total = end($rows);
        $this->assertSame('TOTAL', $total[1]);
        $this->assertEquals(700, $total[6]);
        $this->assertEquals(19300, $total[5]);
    }

    public function test_fuel_sheet_import_loads_the_usage_report_once(): void
    {
        $botwe = Field::create(['name' => 'Botwe', 'user_id' => $this->finance->id, 'number' => '3']);
        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $book->getActiveSheet();
        $ws->fromArray([
            [null, null, null, 'FIRST WATCH SECURITY SERVICES GHANA LTD.'],
            [null, null, null, null, 'FUEL REQUEST AND USAGE REPORT'],
            ['DATE', 'DRIVER NAME', 'VEHICLE NO.', 'FIELD', 'Trip Destination', 'QUANTITY', 'UNITS', 'FUEL TYPE', 'FUEL REQUESTED', 'FUEL ISSUED', 'FUEL STATION', 'APPROVED BY ', 'GRANTED BY', 'REMARK'],
            [46297, 'SIMON EWORYI', 'GC-9169-21', 'ASHALEY BOTWE', 'DEPLOYMENT & PATROL', 38.06, 18.39, 'DIESEL ', 700, 700, 'NOVA', 'SARAH AMA NYAME ', 'DIRECTOR', null],
            [46297, 'EMMANUEL LINCOLN', 'M-21-GS-1118', 'ASHALEY BOTWE', 'DEPLOYMENT & PATROL', 8.85, 16.97, 'SUPER', 150.18, 150.18, 'NOVA', 'SARAH AMA NYAME ', 'DIRECTOR', null],
            [null, null, null, null, 'TOTAL BALANCE ON 20TH OCT,2026', null, null, 'TOTAL', null, 850.18],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'fuel') . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);

        $r = app(\App\Services\Fleet\FuelSheetImport::class)->import($path, null, true, false, $this->finance->id);
        $this->assertSame(2, $r['created']);
        $this->assertSame([], $r['errors']);
        $this->assertContains('GC-9169-21', $r['vehicles']);
        $bike = Vehicle::where('reg_no', 'M-21-GS-1118')->sole();        // already on the register: reused
        $this->assertSame($this->bike->id, $bike->id);
        $e = FuelEntry::where('vehicle_id', Vehicle::where('reg_no', 'GC-9169-21')->value('id'))->sole();
        $this->assertSame('2026-10-02', $e->entry_date->toDateString());
        $this->assertSame($botwe->id, (int) $e->field_id);                 // ASHALEY BOTWE -> Botwe
        $this->assertSame('diesel', $e->fuel_type);
        $this->assertTrue($e->isFullyApproved());
        $this->assertNull($e->payout_id);
        $this->assertStringContainsString('SARAH AMA NYAME', $e->notes);

        $again = app(\App\Services\Fleet\FuelSheetImport::class)->import($path);
        $this->assertSame(0, $again['created']);
        $this->assertSame(2, $again['skipped']);
        @unlink($path);
    }

    public function test_fleet_pages_render(): void
    {
        $this->fuel();
        $this->maintenance('Tyres');
        $this->actingAs($this->finance);
        foreach ([route('fleet.vehicles'), route('fleet.vehicles.show', $this->patrol), route('fleet.fuel'), route('fleet.fuel', ['month' => '2026-10']),
            route('fleet.fuel.edit', FuelEntry::sole()), route('fleet.fuel.payouts'), route('fleet.fuel.payouts.create', ['field' => $this->accra->id, 'from' => '2026-10-01']),
            route('fleet.maintenance'), route('fleet.maintenance.edit', VehicleMaintenance::sole()), route('fleet.maintenance.types'), route('fleet.report')] as $url) {
            $this->get($url)->assertOk();
        }
        $this->actingAs($this->accraOfficer)->get(route('fleet.fuel'))->assertOk()->assertSee('GN-2064-18');
    }
}

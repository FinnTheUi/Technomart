<?php

namespace Tests\Feature\Manager;

use App\Enums\ContractStatus;
use App\Enums\StallStatus;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Stall;
use App\Models\User;
use App\Services\Rental\StallLayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StallLayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config(['services.ml.python' => 'python-binary-that-does-not-exist']);
        $this->manager = User::factory()->create(['role' => 'manager']);
    }

    public function test_every_layout_stall_is_created_and_named_by_its_number(): void
    {
        $this->artisan('stalls:sync')->assertSuccessful();

        $this->assertSame(24, Stall::where('floor_number', 1)->count());
        $this->assertSame(10, Stall::where('floor_number', 2)->count());
        $this->assertDatabaseHas('stalls', ['floor_number' => 1, 'stall_number' => 12, 'name' => 'Stall 12', 'status' => 'available', 'manager_id' => $this->manager->id]);
        $this->assertDatabaseHas('stalls', ['floor_number' => 2, 'stall_number' => 3, 'name' => 'Cart 3', 'price' => 2500]);

        // Idempotent.
        $this->artisan('stalls:sync');
        $this->assertSame(34, Stall::count());
    }

    public function test_opening_the_manager_dashboard_generates_the_stalls(): void
    {
        $this->actingAs($this->manager)->get('/manager/dashboard')->assertOk();

        $this->assertSame(34, Stall::where('manager_id', $this->manager->id)->count());
    }

    public function test_legacy_unnumbered_stalls_keep_their_history_and_get_standard_names(): void
    {
        $legacy = Stall::create(['manager_id' => $this->manager->id, 'name' => 'adsdd', 'floor_number' => 1, 'price' => 3000, 'status' => 'available']);

        app(StallLayoutService::class)->sync();

        $legacy->refresh();
        $this->assertSame(1, $legacy->stall_number);
        $this->assertSame('Stall 1', $legacy->name);
        $this->assertEquals(3000, $legacy->price); // manager's price is kept
        $this->assertSame(24, Stall::where('floor_number', 1)->count());
    }

    public function test_new_stalls_go_to_the_manager_who_already_runs_the_mall(): void
    {
        Stall::create(['manager_id' => $this->manager->id, 'name' => 'x', 'floor_number' => 1, 'stall_number' => 1, 'price' => 1, 'status' => 'available']);
        $newcomer = User::factory()->create(['role' => 'manager']);

        app(StallLayoutService::class)->sync($newcomer);

        $this->assertSame(0, Stall::where('manager_id', $newcomer->id)->count());
    }

    public function test_manager_can_assign_a_tenant_which_creates_a_lease_and_marks_the_stall_occupied(): void
    {
        app(StallLayoutService::class)->sync();
        $stall = Stall::where('name', 'Stall 4')->firstOrFail();
        $tenant = User::factory()->create(['role' => 'tenant']);

        $this->actingAs($this->manager)
            ->post("/manager/stalls/{$stall->id}/tenant", ['tenant_id' => $tenant->id, 'months' => 3])
            ->assertSessionHas('success');

        $this->assertSame(StallStatus::Occupied, $stall->fresh()->status);
        $this->assertSame(ContractStatus::Active, Contract::where('stall_id', $stall->id)->firstOrFail()->status);
        $this->assertSame(3, Payment::where('tenant_id', $tenant->id)->count());

        // A second tenant cannot be placed in the same stall.
        $other = User::factory()->create(['role' => 'tenant']);
        $this->actingAs($this->manager)
            ->post("/manager/stalls/{$stall->id}/tenant", ['tenant_id' => $other->id, 'months' => 1])
            ->assertSessionHas('error');
    }

    public function test_only_tenant_accounts_can_be_assigned(): void
    {
        app(StallLayoutService::class)->sync();
        $stall = Stall::firstOrFail();

        $this->actingAs($this->manager)
            ->post("/manager/stalls/{$stall->id}/tenant", ['tenant_id' => $this->manager->id, 'months' => 1])
            ->assertSessionHasErrors('tenant_id');
    }

    public function test_a_stall_cannot_be_marked_occupied_without_a_lease(): void
    {
        app(StallLayoutService::class)->sync();
        $stall = Stall::firstOrFail();

        $this->actingAs($this->manager)->patch("/manager/stalls/{$stall->id}/status", ['status' => 'occupied'])
            ->assertSessionHas('error');

        $this->assertSame(StallStatus::Available, $stall->fresh()->status);
    }

    public function test_availability_is_reconciled_with_leases(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant']);
        $ghost = Stall::create(['manager_id' => $this->manager->id, 'name' => 'x', 'floor_number' => 1, 'stall_number' => 1, 'price' => 1, 'status' => 'occupied']);
        $leased = Stall::create(['manager_id' => $this->manager->id, 'name' => 'y', 'floor_number' => 1, 'stall_number' => 2, 'price' => 1, 'status' => 'available']);
        $repair = Stall::create(['manager_id' => $this->manager->id, 'name' => 'z', 'floor_number' => 1, 'stall_number' => 3, 'price' => 1, 'status' => 'maintenance']);
        Contract::create(['tenant_id' => $tenant->id, 'stall_id' => $leased->id, 'start_date' => today(), 'end_date' => today()->addMonth(), 'status' => 'active']);

        $this->artisan('stalls:sync')->assertSuccessful();

        $this->assertSame(StallStatus::Available, $ghost->fresh()->status);   // no lease → available
        $this->assertSame(StallStatus::Occupied, $leased->fresh()->status);   // lease → occupied
        $this->assertSame(StallStatus::Maintenance, $repair->fresh()->status); // maintenance untouched
    }

    public function test_live_availability_feed_exposes_only_public_fields(): void
    {
        app(StallLayoutService::class)->sync();
        Stall::where('name', 'Stall 2')->update(['status' => 'maintenance']);

        $response = $this->getJson('/stalls/availability')->assertOk();

        $response->assertJsonPath('counts.available', 33)
            ->assertJsonPath('counts.maintenance', 1)
            ->assertJsonCount(34, 'stalls')
            ->assertJsonStructure(['updated_at', 'stalls' => [['id', 'name', 'floor', 'number', 'status', 'status_label', 'price', 'size', 'location']]]);
        $this->assertArrayNotHasKey('manager_id', $response->json('stalls.0'));
        $this->assertSame('no-store, private', $response->headers->get('Cache-Control'));
    }

    public function test_maps_render_a_status_bubble_for_every_stall(): void
    {
        app(StallLayoutService::class)->sync();
        Stall::where('name', 'Cart 1')->update(['status' => 'occupied']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(34, substr_count($html, 'class="fp-bubble"'));
        $this->assertStringContainsString('fp-stall occupied', $html);
        $this->assertStringContainsString('data-count="available">33<', $html);
        // The hero no longer fills the whole screen, so the map sits right under the buttons.
        $this->assertDoesNotMatchRegularExpression('/\.hero \{[^}]*min-height: 100vh/', $html);

        $this->get('/stalls?floor=2')->assertOk()->assertSee('stall-card occupied', false)->assertSee('Cart 1');
        $this->actingAs($this->manager)->get('/manager/floor-plan')->assertOk()->assertSee('fp-bubble', false);
        $this->actingAs($this->manager)->get('/manager/stalls')->assertOk()->assertSee('Stall 10')->assertDontSee('Stall 11');
        $this->actingAs($this->manager)->get('/manager/stalls?page=3')->assertOk()->assertSee('Stall 24')->assertSee('Cart 1');
        $this->actingAs($this->manager)->get('/manager/stalls?page=4')->assertOk()->assertSee('Cart 10')->assertDontSee('Stall 24');
        $this->actingAs($this->manager)->get('/manager/stalls/'.Stall::first()->id.'/edit')->assertOk()->assertSee('Assign tenant');
    }
}

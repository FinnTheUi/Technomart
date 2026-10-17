<?php

namespace Tests\Feature\Tenant;

use App\Models\Contract;
use App\Models\MaintenanceRequest;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;

    private User $manager;

    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $this->tenant = User::factory()->create(['role' => 'tenant', 'is_active' => true]);
        $this->stall = Stall::create([
            'manager_id' => $this->manager->id,
            'name' => 'Stall A',
            'price' => 5000,
            'status' => 'occupied',
        ]);
        Contract::create([
            'tenant_id' => $this->tenant->id,
            'stall_id' => $this->stall->id,
            'start_date' => today(),
            'end_date' => today()->addYear(),
            'terms' => 'Test lease',
            'status' => 'active',
        ]);
    }

    public function test_tenant_can_submit_maintenance_request(): void
    {
        $response = $this->actingAs($this->tenant)->post('/tenant/maintenance', [
            'stall_id' => $this->stall->id,
            'subject' => 'Broken window',
            'description' => 'The window in my stall is cracked',
            'priority' => 'high',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('maintenance_requests', [
            'tenant_id' => $this->tenant->id,
            'stall_id' => $this->stall->id,
            'subject' => 'Broken window',
            'priority' => 'high',
        ]);
    }

    public function test_tenant_cannot_submit_with_missing_fields(): void
    {
        $response = $this->actingAs($this->tenant)->post('/tenant/maintenance', [
            'stall_id' => $this->stall->id,
        ]);

        $response->assertSessionHasErrors(['subject', 'description', 'priority']);
    }

    public function test_tenant_cannot_submit_with_invalid_priority(): void
    {
        $response = $this->actingAs($this->tenant)->post('/tenant/maintenance', [
            'stall_id' => $this->stall->id,
            'subject' => 'Something',
            'description' => 'Description here',
            'priority' => 'critical',
        ]);

        $response->assertSessionHasErrors('priority');
    }

    public function test_tenant_cannot_submit_request_for_a_stall_they_do_not_lease(): void
    {
        $otherStall = Stall::create([
            'manager_id' => $this->manager->id,
            'name' => 'Stall B',
            'price' => 4000,
            'status' => 'occupied',
        ]);

        $response = $this->actingAs($this->tenant)->post('/tenant/maintenance', [
            'stall_id' => $otherStall->id,
            'subject' => 'Unauthorized request',
            'description' => 'This stall belongs to somebody else.',
            'priority' => 'high',
        ]);

        $response->assertSessionHasErrors('stall_id');
        $this->assertDatabaseMissing('maintenance_requests', [
            'tenant_id' => $this->tenant->id,
            'stall_id' => $otherStall->id,
        ]);
    }

    public function test_tenant_cannot_view_another_tenants_maintenance_detail(): void
    {
        $otherTenant = User::factory()->create(['role' => 'tenant', 'is_active' => true]);
        $request = MaintenanceRequest::create([
            'tenant_id' => $otherTenant->id,
            'stall_id' => $this->stall->id,
            'subject' => 'Other tenant issue',
            'description' => 'Not yours to see',
            'priority' => 'low',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->tenant)
            ->get("/tenant/maintenance/{$request->id}");

        $response->assertStatus(403);
    }

    public function test_maintenance_photo_is_sanitized_before_storage(): void
    {
        Storage::fake('public');

        $this->actingAs($this->tenant)->post('/tenant/maintenance', [
            'stall_id' => $this->stall->id,
            'subject' => 'Photo evidence',
            'description' => 'See attached image.',
            'priority' => 'medium',
            'image' => UploadedFile::fake()->image('evidence.png', 800, 600),
        ])->assertSessionHas('success');

        $request = MaintenanceRequest::where('subject', 'Photo evidence')->firstOrFail();
        $this->assertStringEndsWith('.jpg', $request->image_path);
        Storage::disk('public')->assertExists($request->image_path);
    }

    public function test_non_image_and_oversized_dimensions_are_rejected(): void
    {
        $base = [
            'stall_id' => $this->stall->id,
            'subject' => 'Invalid evidence',
            'description' => 'This upload must not be accepted.',
            'priority' => 'medium',
        ];

        $this->actingAs($this->tenant)->post('/tenant/maintenance', $base + [
            'image' => UploadedFile::fake()->create('payload.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('image');

        $this->actingAs($this->tenant)->post('/tenant/maintenance', $base + [
            'image' => UploadedFile::fake()->image('too-wide.jpg', 5000, 100),
        ])->assertSessionHasErrors('image');

        $this->assertDatabaseMissing('maintenance_requests', ['subject' => 'Invalid evidence']);
    }
}

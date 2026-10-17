<?php

namespace Tests\Feature\Tenant;

use App\Models\User;
use App\Models\Stall;
use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantContractTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;
    private Stall $stall;
    private Contract $contract;

    protected function setUp(): void
    {
        parent::setUp();
        $manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $this->tenant = User::factory()->create(['role' => 'tenant', 'is_active' => true]);
        $this->stall = Stall::create([
            'manager_id' => $manager->id,
            'name' => 'Stall A',
            'price' => 5000,
            'status' => 'occupied',
        ]);
        $this->contract = Contract::create([
            'tenant_id' => $this->tenant->id,
            'stall_id' => $this->stall->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-11-01',
            'terms' => 'Rental duration: 3 month(s). Payment via GCash.',
            'status' => 'active',
        ]);
    }

    public function test_tenant_can_view_their_contract(): void
    {
        $response = $this->actingAs($this->tenant)
            ->get("/tenant/contracts/{$this->contract->id}");

        $response->assertStatus(200);
    }

    public function test_tenant_cannot_view_another_tenants_contract(): void
    {
        $otherTenant = User::factory()->create(['role' => 'tenant', 'is_active' => true]);

        $response = $this->actingAs($otherTenant)
            ->get("/tenant/contracts/{$this->contract->id}");

        $response->assertStatus(403);
    }

    public function test_tenant_can_sign_a_contract(): void
    {
        $response = $this->actingAs($this->tenant)
            ->post("/tenant/contracts/{$this->contract->id}/sign");

        $response->assertSessionHas('success');
        $this->contract->refresh();
        $this->assertNotNull($this->contract->signed_at);
        $this->assertNotNull($this->contract->document_hash);
        $this->assertNotNull($this->contract->signed_ip);
    }

    public function test_tenant_cannot_sign_an_already_signed_contract(): void
    {
        $this->contract->update([
            'signed_at' => now(),
            'signed_ip' => '127.0.0.1',
            'signed_user_agent' => 'PHPUnit',
            'document_hash' => 'abc123',
        ]);

        $response = $this->actingAs($this->tenant)
            ->post("/tenant/contracts/{$this->contract->id}/sign");

        $response->assertSessionHas('error', 'This contract has already been signed.');
    }
}

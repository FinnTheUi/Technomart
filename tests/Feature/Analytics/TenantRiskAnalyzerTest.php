<?php

namespace Tests\Feature\Analytics;

use App\Models\Payment;
use App\Models\Stall;
use App\Models\TenantRiskScore;
use App\Models\User;
use App\Services\Analytics\TenantRiskAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantRiskAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();

        // Force the deterministic historical fallback (no Python in CI).
        config(['services.ml.python' => 'python-binary-that-does-not-exist']);

        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->stall = Stall::create(['manager_id' => $this->manager->id, 'name' => 'A', 'price' => 1000, 'status' => 'occupied']);
    }

    private function bill(User $tenant, string $due, string $status, ?string $paid = null): Payment
    {
        return Payment::create([
            'tenant_id' => $tenant->id, 'manager_id' => $this->manager->id, 'stall_id' => $this->stall->id,
            'amount' => 1000, 'due_date' => $due, 'status' => $status, 'paid_date' => $paid,
        ]);
    }

    public function test_paying_after_the_due_date_counts_as_late(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant']);
        $this->bill($tenant, '2026-01-01', 'paid', '2025-12-30');   // early
        $this->bill($tenant, '2026-02-01', 'paid', '2026-02-11');   // 10 days late
        $this->bill($tenant, '2026-03-01', 'overdue');              // unpaid, overdue
        $this->bill($tenant, '2026-04-01', 'paid', '2026-04-01');   // on the due date

        $row = app(TenantRiskAnalyzer::class)->forManager($this->manager->id)->first();

        $this->assertSame(50, $row->risk_score); // 2 of 4 late
        $this->assertSame('High', $row->risk_level);
        $this->assertContains('2 of the last 4 bills were late', $row->factors);
        $this->assertContains('1 bill(s) currently overdue', $row->factors);
        $this->assertContains('Pays about 10 day(s) late on average when late', $row->factors);
    }

    public function test_scores_are_cached_and_refreshed_by_the_scheduler(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant']);
        $this->bill($tenant, '2026-01-01', 'paid', '2026-01-01');

        $this->artisan('ml:score')->assertSuccessful();
        $this->assertSame(0, TenantRiskScore::firstOrFail()->score);
        $this->assertSame('history', TenantRiskScore::firstOrFail()->source);

        $this->bill($tenant, '2026-02-01', 'overdue');

        // Dashboard reads the cache; it changes only after the next refresh.
        $this->assertSame(0, app(TenantRiskAnalyzer::class)->forManager($this->manager->id)->first()->risk_score);
        $this->artisan('ml:score');
        $this->assertSame(50, app(TenantRiskAnalyzer::class)->forManager($this->manager->id)->first()->risk_score);
    }

    public function test_training_rows_only_use_past_history_for_each_bill(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant']);
        $this->bill($tenant, '2026-01-01', 'overdue');
        $this->bill($tenant, '2026-02-01', 'paid', '2026-01-31');
        $this->bill($tenant, '2026-03-01', 'pending'); // not settled: excluded

        $rows = app(TenantRiskAnalyzer::class)->trainingRows();

        $this->assertCount(2, $rows);
        $this->assertSame(['past_paid' => 0, 'past_overdue' => 0, 'overdue_ratio' => 0.0, 'amount' => 1000.0, 'is_overdue' => 1], $rows[0]);
        $this->assertSame(['past_paid' => 0, 'past_overdue' => 1, 'overdue_ratio' => 1.0, 'amount' => 1000.0, 'is_overdue' => 0], $rows[1]);
    }

    public function test_training_is_skipped_without_enough_data(): void
    {
        config(['services.ml.min_training_rows' => 30]);

        $this->artisan('ml:train')->expectsOutputToContain('need 30 to retrain')->assertSuccessful();
    }

    public function test_manager_dashboard_shows_risk_reasons(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant', 'name' => 'Late Larry']);
        $this->bill($tenant, '2026-01-01', 'overdue');

        $this->actingAs($this->manager)->get('/manager/dashboard')
            ->assertOk()
            ->assertSee('Late Larry')
            ->assertSee('1 bill(s) currently overdue');
    }
}

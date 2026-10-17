<?php

namespace Tests\Feature\Billing;

use App\Services\Billing\DocumentNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DocumentNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_numbers_are_sequential_per_prefix_and_restart_each_year(): void
    {
        $numbers = app(DocumentNumberGenerator::class);

        Carbon::setTestNow('2026-12-31 23:00:00');
        $this->assertSame('OR-2026-000001', $numbers->next('OR'));
        $this->assertSame('OR-2026-000002', $numbers->next('OR'));
        $this->assertSame('PO-2026-0001', $numbers->next('PO', 4));

        Carbon::setTestNow('2027-01-01 08:00:00');
        $this->assertSame('OR-2027-000001', $numbers->next('OR'));

        Carbon::setTestNow();
    }
}

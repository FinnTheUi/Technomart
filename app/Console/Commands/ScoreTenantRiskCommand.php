<?php

namespace App\Console\Commands;

use App\Services\Analytics\TenantRiskAnalyzer;
use Illuminate\Console\Command;

class ScoreTenantRiskCommand extends Command
{
    protected $signature = 'ml:score';

    protected $description = 'Recompute and cache late-payment risk scores for all tenants';

    public function handle(TenantRiskAnalyzer $analyzer): int
    {
        $count = $analyzer->refreshAll();

        $this->components->info("Scored {$count} tenant/manager pair(s).");

        return self::SUCCESS;
    }
}

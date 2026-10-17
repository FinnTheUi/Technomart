<?php

namespace App\Console\Commands;

use App\Services\Billing\ReceiptBackfillService;
use Illuminate\Console\Command;

class BackfillReceiptsCommand extends Command
{
    protected $signature = 'receipts:backfill';

    protected $description = 'Issue receipt numbers for historical paid records that do not have one';

    public function handle(ReceiptBackfillService $receipts): int
    {
        $issued = $receipts->run();
        $this->components->info($issued === 0
            ? 'All paid records already have receipt numbers.'
            : "Issued {$issued} missing receipt number(s).");

        return self::SUCCESS;
    }
}

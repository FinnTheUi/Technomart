<?php

namespace App\Console\Commands;

use App\Services\Inventory\OrderService;
use App\Services\Rental\LeaseService;
use Illuminate\Console\Command;

class ExpireLeasesCommand extends Command
{
    protected $signature = 'leases:expire';

    protected $description = 'Expire ended leases (freeing stalls and notifying waitlists) and cancel stale unpaid supply orders';

    public function handle(LeaseService $leases, OrderService $orders): int
    {
        $expired = $leases->expireEndedLeases();
        $cancelled = $orders->cancelStaleUnpaidOrders();

        $this->components->info("Expired {$expired} lease(s); cancelled {$cancelled} stale unpaid order(s).");

        return self::SUCCESS;
    }
}

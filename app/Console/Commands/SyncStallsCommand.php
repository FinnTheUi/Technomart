<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Rental\StallLayoutService;
use Illuminate\Console\Command;

class SyncStallsCommand extends Command
{
    protected $signature = 'stalls:sync {--manager= : Email of the manager who should own newly created stalls}';

    protected $description = 'Create every stall in the floor layout, apply standard names, and make availability match active leases';

    public function handle(StallLayoutService $layout): int
    {
        $manager = $this->option('manager')
            ? User::where('email', $this->option('manager'))->where('role', User::ROLE_MANAGER)->first()
            : null;

        if ($this->option('manager') && ! $manager) {
            $this->components->error('No manager account with that email.');

            return self::FAILURE;
        }

        $created = $layout->sync($manager);
        $reconciled = $layout->reconcileAvailability();

        if ($created === 0 && User::where('role', User::ROLE_MANAGER)->doesntExist()) {
            $this->components->warn('No manager account exists yet; stalls will be created once a manager is added.');
        }

        $this->components->info("Created {$created} stall(s); corrected availability of {$reconciled} stall(s).");

        return self::SUCCESS;
    }
}

<?php

namespace App\Services\Rental;

use App\Enums\ContractStatus;
use App\Enums\StallStatus;
use App\Models\Stall;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the stall table in step with the physical mall layout (config/stalls.php)
 * and keeps each stall's availability consistent with its leases.
 */
class StallLayoutService
{
    public function __construct(private readonly WaitlistService $waitlist) {}

    public static function nameFor(int $floor, int $number): string
    {
        return config("stalls.floors.{$floor}.prefix", 'Stall').' '.$number;
    }

    /**
     * Create any missing stalls, number legacy stalls into free slots, and apply the
     * standard names. Idempotent. Returns the number of stalls created.
     */
    public function sync(?User $preferredManager = null): int
    {
        $manager = $this->owningManager($preferredManager);

        if (! $manager) {
            return 0; // no manager to own the stalls yet
        }

        return DB::transaction(function () use ($manager) {
            $created = 0;

            foreach (config('stalls.floors') as $floor => $settings) {
                $taken = Stall::where('floor_number', $floor)->whereNotNull('stall_number')->pluck('stall_number')->all();
                $free = array_values(array_diff(range(1, $settings['count']), $taken));

                // Legacy stalls created before numbering existed take the first free slots.
                foreach (Stall::where('floor_number', $floor)->whereNull('stall_number')->orderBy('id')->get() as $legacy) {
                    if ($free === []) {
                        break;
                    }
                    $legacy->update(['stall_number' => array_shift($free)]);
                }

                foreach ($free as $number) {
                    Stall::create([
                        'manager_id' => $manager->id,
                        'floor_number' => $floor,
                        'stall_number' => $number,
                        'name' => self::nameFor($floor, $number),
                        'price' => $settings['default_price'],
                        'status' => StallStatus::Available,
                    ]);
                    $created++;
                }

                // Names always follow the numbering.
                Stall::where('floor_number', $floor)->whereNotNull('stall_number')->get()
                    ->each(function (Stall $stall) use ($floor) {
                        $name = self::nameFor($floor, $stall->stall_number);
                        if ($stall->name !== $name) {
                            $stall->update(['name' => $name]);
                        }
                    });
            }

            if ($created > 0) {
                ActivityLogger::log('generated_stalls', "Generated {$created} stall(s) from the floor layout for {$manager->name}", null, null, $manager);
            }

            return $created;
        });
    }

    /**
     * Make each stall's status reflect its lease: occupied if and only if it has an
     * active lease. Stalls under maintenance are left alone. Returns stalls changed.
     */
    public function reconcileAvailability(): int
    {
        $leased = Stall::whereHas('contracts', fn ($q) => $q->where('status', ContractStatus::Active))->pluck('id');
        $changed = 0;

        Stall::where('status', StallStatus::Available)->whereIn('id', $leased)->get()
            ->each(function (Stall $stall) use (&$changed) {
                $stall->update(['status' => StallStatus::Occupied]);
                $changed++;
            });

        Stall::where('status', StallStatus::Occupied)->whereNotIn('id', $leased)->get()
            ->each(function (Stall $stall) use (&$changed) {
                $stall->update(['status' => StallStatus::Available]);
                ActivityLogger::log('released_stall', "\"{$stall->name}\" had no active lease and was marked available", $stall);
                $this->waitlist->notifyStallAvailable($stall);
                $changed++;
            });

        return $changed;
    }

    /**
     * New stalls go to the manager who already runs this mall (owns the most stalls),
     * otherwise the given manager, otherwise the first active manager.
     */
    private function owningManager(?User $preferred): ?User
    {
        $ownerId = Stall::select('manager_id')->groupBy('manager_id')->orderByRaw('COUNT(*) DESC')->value('manager_id');

        return ($ownerId ? User::find($ownerId) : null)
            ?? ($preferred?->isManager() ? $preferred : null)
            ?? User::where('role', User::ROLE_MANAGER)->where('is_active', true)->orderBy('id')->first();
    }
}

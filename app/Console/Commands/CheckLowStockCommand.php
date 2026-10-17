<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckLowStockCommand extends Command
{
    protected $signature = 'inventory:check-low-stock';

    protected $description = 'Email admins a digest of active products at or below their reorder level';

    public function handle(): int
    {
        $products = Product::where('is_active', true)->lowStock()->orderBy('stock')->get();

        if ($products->isEmpty()) {
            $this->components->info('All products are above their reorder level.');

            return self::SUCCESS;
        }

        $admins = User::where('role', User::ROLE_ADMIN)->where('is_active', true)->get();
        Notification::send($admins, new LowStockNotification($products));

        $this->components->warn("{$products->count()} product(s) need restocking; notified {$admins->count()} admin(s).");

        return self::SUCCESS;
    }
}

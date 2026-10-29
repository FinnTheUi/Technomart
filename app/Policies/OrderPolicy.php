<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id || $user->isManager();
    }

    public function pay(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->id === $order->user_id || $user->isManager();
    }

    /** Managers verify order payments and handle delivery. */
    public function review(User $user, Order $order): bool
    {
        return $user->isManager();
    }
}

<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id || $user->isAdmin();
    }

    public function pay(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->id === $order->user_id || $user->isAdmin();
    }

    /** Admins verify order payments and handle delivery. */
    public function review(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }
}

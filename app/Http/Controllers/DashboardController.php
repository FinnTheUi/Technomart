<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $role = auth()->user()->role;
        return match ($role) {
            'admin' => redirect()->route('admin.dashboard'),
            'manager' => redirect()->route('manager.dashboard'),
            default => redirect()->route('tenant.dashboard'),
        };
    }
}

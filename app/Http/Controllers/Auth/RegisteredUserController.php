<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('stalls.index')
            ->with('success', 'Choose an available stall to create a tenant account and apply to rent.');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(): RedirectResponse
    {
        return redirect()->route('stalls.index')
            ->with('success', 'Choose an available stall before creating your tenant account.');
    }
}

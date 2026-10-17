<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\GmailAddress;
use App\Support\SafeMail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:20'],
            'email' => ['required', 'string', 'lowercase', new GmailAddress, 'max:40', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults(), 'max:20'],
            'privacy_consent' => ['accepted'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'privacy_consented_at' => now(),
        ]);

        // The account is created even if the verification email can't be sent; they can resend it.
        $sent = SafeMail::attempt(fn () => event(new Registered($user)), ['user' => $user->id]);

        Auth::login($user);

        return $sent
            ? redirect(route('dashboard', absolute: false))
            : redirect()->route('verification.notice')->withErrors(['mail' => SafeMail::FAILURE_MESSAGE]);
    }
}

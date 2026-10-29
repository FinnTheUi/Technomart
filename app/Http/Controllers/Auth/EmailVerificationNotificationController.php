<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\GmailAddress;
use App\Support\SafeMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $sent = SafeMail::attempt(fn () => $request->user()->sendEmailVerificationNotification(), ['user' => $request->user()->id]);

        return $sent
            ? back()->with('status', 'verification-link-sent')
            : back()->withErrors(['mail' => SafeMail::FAILURE_MESSAGE]);
    }

    public function editAccount(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        abort_unless($request->user()->canEditPendingSignup(), 403);

        return view('auth.correct-account', ['user' => $request->user()]);
    }

    /** Update signup details while the account is still awaiting verification. */
    public function updateAccount(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        abort_unless($request->user()->canEditPendingSignup(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:20'],
            'email' => [
                'required',
                'string',
                'lowercase',
                new GmailAddress,
                'max:40',
                Rule::unique(User::class)->ignore($request->user()->id),
            ],
            'phone_number' => ['nullable', 'string', 'regex:/^(09|\+639)\d{9}$/'],
            'address' => ['nullable', 'string', 'max:100'],
            'current_password' => ['required_with:password', 'nullable', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults(), 'max:20'],
        ]);

        $user = $request->user();
        $emailChanged = $user->email !== $validated['email'];

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            'address' => $validated['address'] ?? null,
            ...($emailChanged ? ['email_verified_at' => null, 'google_id' => null] : []),
            ...(! empty($validated['password']) ? ['password' => Hash::make($validated['password'])] : []),
        ])->save();

        $request->session()->put('pending_signup_email', $user->email);

        if (! $emailChanged) {
            return redirect()->route('verification.notice')->with('status', 'account-details-updated');
        }

        $sent = SafeMail::attempt(fn () => $user->sendEmailVerificationNotification(), ['user' => $user->id]);

        return $sent
            ? redirect()->route('verification.notice')->with('status', 'account-details-updated-and-sent')
            : redirect()->route('verification.notice')->with('status', 'account-details-updated')->withErrors(['mail' => SafeMail::FAILURE_MESSAGE]);
    }
}

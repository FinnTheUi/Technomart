<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        if (! $request->hasValidSignature()) {
            return view('auth.verification-result', ['verified' => false]);
        }

        $user = User::find($request->route('id'));
        if (! $user || ! $user->is_active || ! hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
            return view('auth.verification-result', ['verified' => false]);
        }

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        if ($request->user()?->is($user)) {
            $request->session()->forget('pending_signup_email');

            return redirect()->route('dashboard', ['verified' => 1]);
        }

        // A different signed-in account must keep its own identity. The link
        // proves email ownership only; credentials are still needed to sign in.
        return view('auth.verification-result', ['verified' => true]);
    }
}

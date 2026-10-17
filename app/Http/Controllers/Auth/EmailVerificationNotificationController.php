<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\SafeMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
}

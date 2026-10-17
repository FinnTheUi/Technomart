<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\Uploads\ImageSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, ImageSanitizer $images): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->except('profile_photo'));

        if ($request->hasFile('profile_photo')) {
            try {
                $newPhoto = $images->store($request->file('profile_photo'), 'profile-photos');
            } catch (RuntimeException) {
                throw ValidationException::withMessages(['profile_photo' => 'The profile photo could not be processed.']);
            }

            $oldPhoto = $user->avatar_url;
            $user->avatar_url = $newPhoto;

            if ($oldPhoto && ! str_starts_with($oldPhoto, 'http://') && ! str_starts_with($oldPhoto, 'https://')) {
                Storage::disk('public')->delete($oldPhoto);
            }
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Disable the user's account.
     */
    public function disable(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->update(['is_active' => false]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/')->with('status', 'Account disabled.');
    }
}

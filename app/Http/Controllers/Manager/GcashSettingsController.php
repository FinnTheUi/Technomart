<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Services\Uploads\ImageSanitizer;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class GcashSettingsController extends Controller
{
    public function update(Request $request, ImageSanitizer $images): RedirectResponse
    {
        $data = $request->validate([
            'gcash_qr' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=100,min_height=100,max_width=4096,max_height=4096'],
            'gcash_account_name' => ['nullable', 'string', 'max:100'],
            'gcash_account_number' => ['nullable', 'string', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            'remove_qr' => ['sometimes', 'boolean'],
        ]);
        $user = $request->user();
        $oldPath = $user->gcash_qr_path;
        $path = $oldPath;
        if ($request->hasFile('gcash_qr')) {
            try {
                $path = $images->store($request->file('gcash_qr'), 'gcash-qr', 'public', true);
            } catch (RuntimeException) {
                throw ValidationException::withMessages(['gcash_qr' => 'The QR image could not be processed. Please upload a clear PNG or JPG.']);
            }
        } elseif ($request->boolean('remove_qr')) {
            $path = null;
        }
        $user->forceFill([
            'gcash_qr_path' => $path,
            'gcash_account_name' => $data['gcash_account_name'] ?? null,
            'gcash_account_number' => $data['gcash_account_number'] ?? null,
        ])->save();
        if ($oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('profile.edit')->with('status', 'gcash-updated');
    }
}

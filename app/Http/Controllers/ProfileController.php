<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        if (in_array($request->user()->role, ['admin', 'employer'], true)) {
            return view('profile.account-settings', [
                'user' => $request->user(),
                'layout' => $request->user()->role === 'admin' ? 'layouts.admin' : 'layouts.employer',
                'dashboardRoute' => $request->user()->role === 'admin' ? 'admin.dashboard' : 'employer.dashboard',
            ]);
        }

        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        unset($validated['profile_photo']);
        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $previousPhoto = $request->user()->profile_photo;
        if ($request->hasFile('profile_photo')) {
            $request->user()->profile_photo = $request->file('profile_photo')->store('account-photos', 'local');
        }
        $request->user()->save();
        if ($previousPhoto && $previousPhoto !== $request->user()->profile_photo) {
            Storage::disk('local')->delete($previousPhoto);
        }

        if (in_array($request->user()->role, ['admin', 'employer'], true)) {
            return Redirect::back()->with('status', 'profile-updated');
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function photo(Request $request): StreamedResponse
    {
        abort_unless(in_array($request->user()->role, ['admin', 'employer'], true), 403);

        $path = $request->user()->profile_photo;
        abort_unless($path && str_starts_with($path, 'account-photos/') && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, no-store']);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the visitor to Google's OAuth consent screen.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google's redirect back after consent.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            // Covers a denied/cancelled consent screen and any other OAuth failure.
            return redirect()->route('login')
                ->with('status', __('Google sign-in was cancelled or failed. Please try again.'));
        }

        // Google's own userinfo response reports whether it verified the email; we don't
        // trust an email Google itself flags as unverified any more than a self-reported one.
        if (! ($googleUser->user['email_verified'] ?? true)) {
            return redirect()->route('login')
                ->with('status', __('Google reported this email as unverified. Please try another sign-in method.'));
        }

        $user = $this->findOrCreateUser($googleUser);

        Auth::login($user);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Resolve the existing user for this Google identity, linking or creating one as needed.
     */
    private function findOrCreateUser(SocialiteUser $googleUser): User
    {
        if ($existing = User::where('google_id', $googleUser->getId())->first()) {
            return $existing;
        }

        if ($existing = User::where('email', $googleUser->getEmail())->first()) {
            $existing->forceFill([
                'google_id' => $googleUser->getId(),
                'email_verified_at' => $existing->email_verified_at ?? now(),
            ])->save();

            return $existing;
        }

        $user = User::create([
            'first_name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'password' => null,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }
}

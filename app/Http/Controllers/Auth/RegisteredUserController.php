<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $existing = User::where('email', $request->email)->first();

        if ($existing) {
            // FR-002: a duplicate *verified* email blocks registration, but a duplicate
            // *unverified* one just gets a fresh verification link — otherwise a stale,
            // unverified registration could permanently lock out the email's true owner.
            if ($existing->hasVerifiedEmail()) {
                throw ValidationException::withMessages([
                    'email' => __('The email has already been taken.'),
                ]);
            }

            $existing->sendEmailVerificationNotification();

            // Not logged in here: the visitor hasn't proven ownership of this account
            // (they didn't supply its real password), so authenticating them would let
            // anyone hijack any unverified account just by "registering" its email.
            return redirect()->route('login')
                ->with('status', __('We have sent a fresh verification link to that email address.'));
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}

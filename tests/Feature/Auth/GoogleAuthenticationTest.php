<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(string $id, string $email, string $name, bool $verified = true): SocialiteUser
    {
        $socialiteUser = new SocialiteUser;
        $socialiteUser->id = $id;
        $socialiteUser->email = $email;
        $socialiteUser->name = $name;
        $socialiteUser->user = ['email_verified' => $verified];

        return $socialiteUser;
    }

    public function test_a_new_google_identity_creates_an_already_verified_account_and_logs_in(): void
    {
        $googleUser = $this->fakeGoogleUser('google-123', 'new-person@example.com', 'New Person');

        Socialite::shouldReceive('driver->user')->once()->andReturn($googleUser);

        $response = $this->get('/auth/google/callback');

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'new-person@example.com')->firstOrFail();
        $this->assertSame('google-123', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->password);
    }

    public function test_cancelled_google_consent_creates_no_account(): void
    {
        Socialite::shouldReceive('driver->user')->once()->andThrow(new \Exception('user denied access'));

        $response = $this->get('/auth/google/callback');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $this->assertSame(0, User::count());
    }

    public function test_google_reporting_an_unverified_email_is_rejected(): void
    {
        $googleUser = $this->fakeGoogleUser('google-456', 'unverified@example.com', 'Someone', verified: false);

        Socialite::shouldReceive('driver->user')->once()->andReturn($googleUser);

        $response = $this->get('/auth/google/callback');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $this->assertSame(0, User::count());
    }
}

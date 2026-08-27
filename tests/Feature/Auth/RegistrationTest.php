<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_new_users_are_created_unverified_with_a_hashed_password(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertNull($user->email_verified_at);
        $this->assertNotEquals('password', $user->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password', $user->password));
    }

    public function test_registration_is_rejected_when_email_already_belongs_to_a_verified_account(): void
    {
        User::factory()->create([
            'email' => 'taken@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'taken@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registering_with_an_unverified_duplicate_email_resends_verification_without_logging_in(): void
    {
        Notification::fake();

        $existing = User::factory()->unverified()->create([
            'email' => 'pending@example.com',
        ]);

        $response = $this->post('/register', [
            'name' => 'Someone Else',
            'email' => 'pending@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $this->assertSame(1, User::where('email', 'pending@example.com')->count());
        Notification::assertSentTo($existing, VerifyEmail::class);
    }

    public function test_registration_rejects_a_password_shorter_than_eight_characters(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short1',
            'password_confirmation' => 'short1',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }
}

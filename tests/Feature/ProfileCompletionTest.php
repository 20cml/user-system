<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileCompletionTest extends TestCase
{
    use RefreshDatabase;

    private function completeProfileData(): array
    {
        return [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '4165551234',
            'address_line' => '123 Main St',
            'address_complement' => '',
            'city' => 'Toronto',
            'region' => 'ON',
            'postal_code' => 'M5V 2T6',
            'country' => 'CA',
        ];
    }

    public function test_user_with_incomplete_profile_is_redirected_from_dashboard_to_profile(): void
    {
        $user = User::factory()->profileIncomplete()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('profile.edit'));
    }

    public function test_user_with_incomplete_profile_can_still_view_the_profile_page(): void
    {
        $user = User::factory()->profileIncomplete()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
    }

    public function test_submitting_every_required_field_unlocks_the_dashboard(): void
    {
        $user = User::factory()->profileIncomplete()->create(['email' => 'test@example.com']);

        $response = $this->actingAs($user)->patch('/profile', $this->completeProfileData());

        $response->assertSessionHasNoErrors();
        $this->assertNotNull($user->fresh()->profile_completed_at);

        $dashboard = $this->actingAs($user->fresh())->get('/dashboard');
        $dashboard->assertOk();
    }

    public function test_submission_missing_a_required_field_is_rejected_and_profile_stays_incomplete(): void
    {
        $user = User::factory()->profileIncomplete()->create(['email' => 'test@example.com']);

        $data = $this->completeProfileData();
        unset($data['last_name']);

        $response = $this->actingAs($user)->patch('/profile', $data);

        $response->assertSessionHasErrors('last_name');
        $this->assertNull($user->fresh()->profile_completed_at);
    }

    public function test_gate_applies_the_same_way_regardless_of_how_the_user_authenticated(): void
    {
        $googleUser = User::factory()->profileIncomplete()->create(['google_id' => '123456789']);

        $response = $this->actingAs($googleUser)->get('/dashboard');

        $response->assertRedirect(route('profile.edit'));
    }

    public function test_a_user_with_a_completed_profile_reaches_the_dashboard_directly(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }

    public function test_voluntarily_re_saving_the_profile_does_not_unset_completion(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $completedAt = $user->profile_completed_at;

        $data = $this->completeProfileData();
        $data['first_name'] = 'Updated';

        $this->actingAs($user)->patch('/profile', $data);

        $fresh = $user->fresh();
        $this->assertNotNull($fresh->profile_completed_at);
        $this->assertEquals($completedAt->timestamp, $fresh->profile_completed_at->timestamp);

        $response = $this->actingAs($fresh)->get('/dashboard');
        $response->assertOk();
    }
}

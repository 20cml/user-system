<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AddressSuggestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_query_returns_normalized_suggestions(): void
    {
        config(['services.geoapify.key' => 'test-key']);

        Http::fake([
            'api.geoapify.com/*' => Http::response([
                'results' => [
                    [
                        'address_line1' => '123 Main St',
                        'city' => 'Toronto',
                        'state_code' => 'ON',
                        'postcode' => 'M5V 2T6',
                        'country_code' => 'ca',
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/address-suggestions?query=M5V');

        $response->assertOk();
        $response->assertJson([
            [
                'street' => '123 Main St',
                'city' => 'Toronto',
                'region' => 'ON',
                'postal_code' => 'M5V 2T6',
                'country' => 'CA',
            ],
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.geoapify.com')
                && $request['filter'] === 'countrycode:ca,us';
        });
    }

    public function test_a_failed_third_party_call_returns_an_empty_array_not_an_error(): void
    {
        config(['services.geoapify.key' => 'test-key']);

        Http::fake([
            'api.geoapify.com/*' => Http::response([], 500),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/address-suggestions?query=M5V');

        $response->assertOk();
        $response->assertJson([]);
    }

    public function test_an_empty_query_returns_an_empty_array_without_calling_the_api(): void
    {
        config(['services.geoapify.key' => 'test-key']);

        Http::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/address-suggestions?query=');

        $response->assertOk();
        $response->assertJson([]);
        Http::assertNothingSent();
    }

    public function test_a_missing_api_key_returns_an_empty_array_without_calling_the_api(): void
    {
        config(['services.geoapify.key' => null]);

        Http::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/address-suggestions?query=M5V');

        $response->assertOk();
        $response->assertJson([]);
        Http::assertNothingSent();
    }
}

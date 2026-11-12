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
                'state_province' => 'ON',
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

    public function test_the_fake_provider_returns_stand_in_addresses_without_any_network_call(): void
    {
        config(['services.address.provider' => 'fake', 'services.geoapify.key' => null]);

        Http::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/address-suggestions?query=123 main');

        $response->assertOk();
        $response->assertJsonFragment([
            'street' => '123 Main St',
            'city' => 'Toronto',
            'state_province' => 'ON',
            'postal_code' => 'M5V 2T6',
            'country' => 'CA',
        ]);
        $response->assertJsonFragment(['city' => 'Hamilton']);
        Http::assertNothingSent();
    }

    public function test_the_fake_provider_returns_nothing_when_no_address_matches(): void
    {
        config(['services.address.provider' => 'fake']);

        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/address-suggestions?query=zzzzzz')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_the_same_request_works_with_either_provider_in_the_same_shape(): void
    {
        $user = User::factory()->create();
        $keys = ['street', 'city', 'state_province', 'postal_code', 'country'];

        Http::fake([
            'api.geoapify.com/*' => Http::response(['results' => [[
                'address_line1' => '123 Main St', 'city' => 'Toronto', 'state_code' => 'ON',
                'postcode' => 'M5V 2T6', 'country_code' => 'ca',
            ]]], 200),
        ]);

        config(['services.address.provider' => 'geoapify', 'services.geoapify.key' => 'test-key']);
        $real = $this->actingAs($user)->getJson('/api/address-suggestions?query=123 main')->json();

        config(['services.address.provider' => 'fake']);
        $fake = $this->actingAs($user)->getJson('/api/address-suggestions?query=123 main')->json();

        $this->assertNotEmpty($real);
        $this->assertNotEmpty($fake);
        $this->assertEqualsCanonicalizing($keys, array_keys($real[0]));
        $this->assertEqualsCanonicalizing($keys, array_keys($fake[0]));
    }

    public function test_an_unknown_provider_name_fails_with_a_clear_message(): void
    {
        config(['services.address.provider' => 'nope']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown ADDRESS_PROVIDER "nope"');

        app(\App\Address\AddressProvider::class);
    }

    public function test_the_controller_does_not_know_about_any_specific_provider(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Api/AddressSuggestionController.php'));

        $this->assertStringNotContainsStringIgnoringCase('geoapify', $source);
        $this->assertStringNotContainsStringIgnoringCase('http::', $source);
    }
}

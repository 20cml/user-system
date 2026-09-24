<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_searching_by_address_returns_only_the_agents_own_matching_listings(): void
    {
        $user = User::factory()->create();
        $mine = Listing::factory()->for($user)->create(['address_line' => '123 Main St', 'city' => 'Toronto']);
        Listing::factory()->create(['address_line' => '123 Main St', 'city' => 'Vancouver']);

        $response = $this->actingAs($user)->getJson('/api/listings-search?query=Main');

        $response->assertOk();
        $response->assertJson([
            ['id' => $mine->id, 'label' => "#{$mine->id} — 123 Main St, Toronto"],
        ]);
    }

    public function test_searching_by_id_returns_the_matching_listing(): void
    {
        $user = User::factory()->create();
        $mine = Listing::factory()->for($user)->create();

        $response = $this->actingAs($user)->getJson("/api/listings-search?query={$mine->id}");

        $response->assertOk();
        $response->assertJsonFragment(['id' => $mine->id]);
    }

    public function test_empty_query_returns_an_empty_array(): void
    {
        $user = User::factory()->create();
        Listing::factory()->for($user)->create();

        $response = $this->actingAs($user)->getJson('/api/listings-search?query=');

        $response->assertOk();
        $response->assertJson([]);
    }

    public function test_filtering_by_listing_type_excludes_listings_of_the_other_type(): void
    {
        $user = User::factory()->create();
        $forRent = Listing::factory()->for($user)->create(['listing_type' => 'rent', 'address_line' => '1 Rent St']);
        Listing::factory()->for($user)->create(['listing_type' => 'sale', 'address_line' => '2 Sale St']);

        $response = $this->actingAs($user)->getJson('/api/listings-search?query=St&listing_type=rent');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $forRent->id]);
    }
}

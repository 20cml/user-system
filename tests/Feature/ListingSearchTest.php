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
}

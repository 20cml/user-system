<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_searching_by_name_returns_only_the_agents_own_matching_leads(): void
    {
        $user = User::factory()->create();
        $mine = Lead::factory()->for($user)->create(['first_name' => 'Jane', 'last_name' => 'Smith']);
        Lead::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);

        $response = $this->actingAs($user)->getJson('/api/leads-search?query=Jane');

        $response->assertOk();
        $response->assertJson([
            ['id' => $mine->id, 'label' => 'Jane Smith'],
        ]);
    }

    public function test_empty_query_returns_an_empty_array(): void
    {
        $user = User::factory()->create();
        Lead::factory()->for($user)->create();

        $response = $this->actingAs($user)->getJson('/api/leads-search?query=');

        $response->assertOk();
        $response->assertJson([]);
    }

    public function test_seller_and_landlord_leads_are_excluded_from_results(): void
    {
        $user = User::factory()->create();
        $buyer = Lead::factory()->for($user)->create(['type' => 'buyer', 'first_name' => 'Bori', 'last_name' => 'Lee']);
        Lead::factory()->for($user)->create(['type' => 'seller', 'first_name' => 'Bori', 'last_name' => 'Seller']);
        Lead::factory()->for($user)->create(['type' => 'landlord', 'first_name' => 'Bori', 'last_name' => 'Landlord']);

        $response = $this->actingAs($user)->getJson('/api/leads-search?query=Bori');

        $response->assertOk();
        $response->assertJson([
            ['id' => $buyer->id, 'label' => 'Bori Lee'],
        ]);
    }
}

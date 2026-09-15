<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ListingTest extends TestCase
{
    use RefreshDatabase;

    private function listingData(array $overrides = []): array
    {
        return array_merge([
            'address_line' => '123 Main St',
            'city' => 'Toronto',
            'state_province' => 'ON',
            'postal_code' => 'M5V 2T6',
            'country' => 'CA',
            'price' => 500000,
            'listing_type' => 'sale',
            'property_type' => 'house',
            'area_sqm' => 150,
            'description' => 'A lovely home.',
        ], $overrides);
    }

    public function test_agent_can_create_a_listing_and_it_appears_in_the_index(): void
    {
        $user = User::factory()->create(['country' => 'CA']);

        $response = $this->actingAs($user)->post('/listings', $this->listingData());

        $response->assertRedirect('/listings');
        $this->assertDatabaseHas('listings', [
            'user_id' => $user->id,
            'address_line' => '123 Main St',
            'status' => 'available',
            'country' => 'CA',
            'currency' => 'CAD',
        ]);

        $index = $this->actingAs($user)->get('/listings');
        $index->assertOk();
        $index->assertSee('123 Main St');
    }

    public function test_currency_comes_from_the_agents_own_country_not_the_listings_country(): void
    {
        $user = User::factory()->create(['country' => 'US']);

        $this->actingAs($user)->post('/listings', $this->listingData(['country' => 'CA']));

        $this->assertDatabaseHas('listings', [
            'user_id' => $user->id,
            'country' => 'CA',
            'currency' => 'USD',
        ]);
    }

    public function test_missing_required_field_is_rejected(): void
    {
        $user = User::factory()->create();

        $data = $this->listingData();
        unset($data['address_line']);

        $response = $this->actingAs($user)->post('/listings', $data);

        $response->assertSessionHasErrors('address_line');
        $this->assertDatabaseCount('listings', 0);
    }

    public function test_zero_or_negative_price_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/listings', $this->listingData(['price' => 0]));
        $response->assertSessionHasErrors('price');

        $response = $this->actingAs($user)->post('/listings', $this->listingData(['price' => -100]));
        $response->assertSessionHasErrors('price');

        $this->assertDatabaseCount('listings', 0);
    }

    public function test_status_can_move_through_available_pending_and_closed(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create(['status' => 'available']);

        $this->actingAs($user)
            ->patch("/listings/{$listing->id}", $this->listingData(['status' => 'pending']));
        $this->assertSame('pending', $listing->fresh()->status);

        $this->actingAs($user)
            ->patch("/listings/{$listing->id}", $this->listingData(['status' => 'closed']));
        $this->assertSame('closed', $listing->fresh()->status);

        $index = $this->actingAs($user)->get('/listings');
        $index->assertSee($listing->fresh()->address_line);
    }

    public function test_an_agent_cannot_see_or_edit_another_agents_listing(): void
    {
        $agentA = User::factory()->create();
        $agentB = User::factory()->create();
        $listingA = Listing::factory()->for($agentA)->create(['address_line' => '42 Only Agent A Street']);

        $index = $this->actingAs($agentB)->get('/listings');
        $index->assertDontSee('42 Only Agent A Street');

        $this->actingAs($agentB)->get("/listings/{$listingA->id}/edit")->assertForbidden();
        $this->actingAs($agentB)->patch("/listings/{$listingA->id}", $this->listingData())->assertForbidden();
        $this->actingAs($agentB)->delete("/listings/{$listingA->id}")->assertForbidden();
        $this->assertDatabaseHas('listings', ['id' => $listingA->id]);
    }

    public function test_agent_can_delete_their_own_listing(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create(['photo_path' => 'listing-photos/to-delete.jpg']);
        Storage::disk('public')->put($listing->photo_path, 'fake-contents');

        $response = $this->actingAs($user)->delete("/listings/{$listing->id}");

        $response->assertRedirect('/listings');
        $this->assertDatabaseMissing('listings', ['id' => $listing->id]);
        Storage::disk('public')->assertMissing('listing-photos/to-delete.jpg');
    }

    public function test_uploading_a_photo_on_create_succeeds_and_it_displays(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/listings', $this->listingData([
            'photo' => UploadedFile::fake()->image('front.jpg'),
        ]));

        $listing = Listing::first();
        $this->assertNotNull($listing->photo_path);
        Storage::disk('public')->assertExists($listing->photo_path);

        $index = $this->actingAs($user)->get('/listings');
        $index->assertSee(Storage::url($listing->photo_path), false);
    }

    public function test_removing_the_photo_deletes_the_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create(['photo_path' => 'listing-photos/keep-me.jpg']);
        Storage::disk('public')->put($listing->photo_path, 'fake-contents');

        $this->actingAs($user)->patch("/listings/{$listing->id}", $this->listingData([
            'remove_photo' => '1',
        ]));

        Storage::disk('public')->assertMissing('listing-photos/keep-me.jpg');
        $this->assertNull($listing->fresh()->photo_path);
    }

    public function test_uploading_a_new_photo_replaces_the_existing_one(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create(['photo_path' => 'listing-photos/old.jpg']);
        Storage::disk('public')->put($listing->photo_path, 'fake-contents');

        $this->actingAs($user)->patch("/listings/{$listing->id}", $this->listingData([
            'photo' => UploadedFile::fake()->image('new.jpg'),
        ]));

        Storage::disk('public')->assertMissing('listing-photos/old.jpg');
        $this->assertNotNull($listing->fresh()->photo_path);
        $this->assertNotSame('listing-photos/old.jpg', $listing->fresh()->photo_path);
    }

    public function test_edit_page_renders_correctly_when_the_listing_has_a_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create(['photo_path' => 'listing-photos/existing.jpg']);

        $response = $this->actingAs($user)->get("/listings/{$listing->id}/edit");

        $response->assertOk();
    }

    public function test_filtering_by_status_returns_only_matching_listings(): void
    {
        $user = User::factory()->create();
        Listing::factory()->for($user)->create(['status' => 'available', 'address_line' => 'Available One']);
        Listing::factory()->for($user)->create(['status' => 'closed', 'address_line' => 'Closed One']);

        $response = $this->actingAs($user)->get('/listings?status=closed');

        $response->assertSee('Closed One');
        $response->assertDontSee('Available One');
    }

    public function test_combining_filters_narrows_to_listings_matching_all_of_them(): void
    {
        $user = User::factory()->create();
        Listing::factory()->for($user)->create(['listing_type' => 'sale', 'price' => 100000, 'address_line' => 'Cheap Sale']);
        Listing::factory()->for($user)->create(['listing_type' => 'sale', 'price' => 900000, 'address_line' => 'Expensive Sale']);
        Listing::factory()->for($user)->create(['listing_type' => 'rent', 'price' => 100000, 'address_line' => 'Cheap Rent']);

        $response = $this->actingAs($user)->get('/listings?listing_type=sale&max_price=200000');

        $response->assertSee('Cheap Sale');
        $response->assertDontSee('Expensive Sale');
        $response->assertDontSee('Cheap Rent');
    }

    public function test_no_filter_returns_the_full_list(): void
    {
        $user = User::factory()->create();
        Listing::factory()->for($user)->create(['address_line' => 'Listing One']);
        Listing::factory()->for($user)->create(['address_line' => 'Listing Two']);

        $response = $this->actingAs($user)->get('/listings');

        $response->assertSee('Listing One');
        $response->assertSee('Listing Two');
    }

    public function test_linking_leads_to_a_listing_on_create_is_visible_on_edit(): void
    {
        $user = User::factory()->create();
        $leadA = Lead::factory()->for($user)->create(['first_name' => 'Alice', 'last_name' => '']);
        $leadB = Lead::factory()->for($user)->create(['first_name' => 'Bob', 'last_name' => '']);

        $this->actingAs($user)->post('/listings', $this->listingData([
            'lead_ids' => [$leadA->id, $leadB->id],
        ]));

        $listing = Listing::first();
        $this->assertCount(2, $listing->leads);

        $edit = $this->actingAs($user)->get("/listings/{$listing->id}/edit");
        $edit->assertSee('Alice');
        $edit->assertSee('Bob');
    }

    public function test_linking_another_agents_lead_to_a_listing_is_rejected(): void
    {
        $user = User::factory()->create();
        $otherAgentsLead = Lead::factory()->create();

        $response = $this->actingAs($user)->post('/listings', $this->listingData([
            'lead_ids' => [$otherAgentsLead->id],
        ]));

        $response->assertSessionHasErrors('lead_ids.0');
        $this->assertDatabaseCount('listings', 0);
    }
}

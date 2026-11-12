<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Listing;
use App\Models\ListingNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\SeedsDefaultFunnel;
use Tests\TestCase;

class ListingTest extends TestCase
{
    use RefreshDatabase;
    use SeedsDefaultFunnel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSeedsDefaultFunnel();
    }

    /**
     * Valid form data for a listing. The owner is an existing Seller (for a
     * sale) or Landlord (for a rental) lead belonging to the signed-in agent,
     * created on the spot unless an `owner_lead_id` override says otherwise.
     */
    private function listingData(array $overrides = [], ?User $agent = null): array
    {
        $agent ??= auth()->user();
        $ownerType = ($overrides['listing_type'] ?? 'sale') === 'rent' ? 'landlord' : 'seller';
        $owner = ($agent && ! isset($overrides['owner_lead_id'])) ? Lead::factory()->for($agent)->create(['type' => $ownerType]) : null;

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
            'owner_lead_id' => $owner?->id,
        ], $overrides);
    }

    public function test_agent_can_create_a_listing_and_it_appears_in_the_index(): void
    {
        $user = User::factory()->create(['country' => 'CA']);

        $response = $this->actingAs($user)->post('/listings', $this->listingData());

        $listing = Listing::first();
        $response->assertRedirect(route('listings.edit', [$listing, 'open' => ['details']]));
        $this->assertDatabaseHas('listings', [
            'user_id' => $user->id,
            'address_line' => '123 Main St',
            'country' => 'CA',
            'currency' => 'CAD',
        ]);

        $index = $this->actingAs($user)->get('/listings');
        $index->assertOk();
        $index->assertSee('123 Main St');
    }

    public function test_a_sale_listing_is_created_with_the_chosen_seller_as_its_owner(): void
    {
        $user = User::factory()->create();
        $seller = Lead::factory()->for($user)->create(['type' => 'seller', 'first_name' => 'Sonia', 'last_name' => 'Smith']);

        $this->actingAs($user)->post('/listings', $this->listingData(['owner_lead_id' => $seller->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame($seller->id, Listing::first()->owner_lead_id);
        $this->assertSame(1, Lead::count());
    }

    public function test_a_rental_listing_is_created_with_the_chosen_landlord_as_its_owner(): void
    {
        $user = User::factory()->create();
        $landlord = Lead::factory()->for($user)->create(['type' => 'landlord']);

        $this->actingAs($user)->post('/listings', $this->listingData(['listing_type' => 'rent', 'owner_lead_id' => $landlord->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame($landlord->id, Listing::first()->owner_lead_id);
    }

    public function test_a_listing_can_be_created_without_an_owner_yet(): void
    {
        $user = User::factory()->create();

        $data = $this->listingData([], $user);
        unset($data['owner_lead_id']);

        $this->actingAs($user)->post('/listings', $data)->assertSessionHasNoErrors();

        $this->assertNull(Listing::first()->owner_lead_id);
    }

    public function test_the_owner_can_be_cleared_back_to_none_yet(): void
    {
        $user = User::factory()->create();
        $seller = Lead::factory()->for($user)->create(['type' => 'seller']);
        $listing = Listing::factory()->for($user)->create(['listing_type' => 'sale', 'owner_lead_id' => $seller->id]);

        $this->actingAs($user)->patch("/listings/{$listing->id}", $this->listingData(['owner_lead_id' => '']))
            ->assertSessionHasNoErrors();

        $this->assertNull($listing->fresh()->owner_lead_id);
        $this->assertNotNull($seller->fresh());
    }

    public function test_a_listing_without_an_owner_still_shows_the_seller_dropdown(): void
    {
        $user = User::factory()->create();
        Lead::factory()->for($user)->create(['type' => 'seller', 'first_name' => 'Sonia', 'last_name' => 'Seller']);
        $listing = Listing::factory()->for($user)->create(['listing_type' => 'sale', 'owner_lead_id' => null]);

        $response = $this->actingAs($user)->get("/listings/{$listing->id}/edit");

        $response->assertOk();
        $response->assertSee('None yet');
        $response->assertSee('Sonia Seller');
    }

    public function test_a_sale_listing_cannot_be_owned_by_a_landlord_nor_a_rental_by_a_seller(): void
    {
        $user = User::factory()->create();
        $seller = Lead::factory()->for($user)->create(['type' => 'seller']);
        $landlord = Lead::factory()->for($user)->create(['type' => 'landlord']);

        $this->actingAs($user)->post('/listings', $this->listingData(['listing_type' => 'sale', 'owner_lead_id' => $landlord->id]))
            ->assertSessionHasErrors('owner_lead_id');
        $this->actingAs($user)->post('/listings', $this->listingData(['listing_type' => 'rent', 'owner_lead_id' => $seller->id]))
            ->assertSessionHasErrors('owner_lead_id');
        $this->assertDatabaseCount('listings', 0);
    }

    public function test_a_listing_cannot_be_owned_by_another_agents_lead(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $strangersSeller = Lead::factory()->for($other)->create(['type' => 'seller']);

        $this->actingAs($user)->post('/listings', $this->listingData(['owner_lead_id' => $strangersSeller->id]))
            ->assertSessionHasErrors('owner_lead_id');
        $this->assertDatabaseCount('listings', 0);
    }

    public function test_one_seller_can_own_several_listings(): void
    {
        $user = User::factory()->create();
        $seller = Lead::factory()->for($user)->create(['type' => 'seller']);

        $this->actingAs($user)->post('/listings', $this->listingData(['owner_lead_id' => $seller->id, 'address_line' => '1 First St']))
            ->assertSessionHasNoErrors();
        $this->actingAs($user)->post('/listings', $this->listingData(['owner_lead_id' => $seller->id, 'address_line' => '2 Second St']))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $seller->ownedListings()->count());
    }

    public function test_the_owner_can_be_changed_from_the_listing_page(): void
    {
        $user = User::factory()->create();
        $first = Lead::factory()->for($user)->create(['type' => 'seller']);
        $second = Lead::factory()->for($user)->create(['type' => 'seller']);
        $listing = Listing::factory()->for($user)->create(['owner_lead_id' => $first->id, 'listing_type' => 'sale']);

        $this->actingAs($user)->patch("/listings/{$listing->id}", $this->listingData(['owner_lead_id' => $second->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame($second->id, $listing->fresh()->owner_lead_id);
    }

    public function test_the_create_form_only_asks_for_selling_or_renting_and_the_property_type(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/listings');

        $response->assertSee('Selling');
        $response->assertSee('Renting');
        $response->assertSee('Condo');
        $response->assertDontSee('>Selling or renting?<', false);
        $response->assertDontSee('Street address');
        $response->assertDontSee('Seller / Landlord');
    }

    public function test_listing_type_and_property_type_are_required_to_create_a_listing(): void
    {
        $user = User::factory()->create();

        $data = $this->listingData([], $user);
        unset($data['listing_type'], $data['property_type']);

        $response = $this->actingAs($user)->post('/listings', $data);

        $response->assertSessionHasErrors(['listing_type', 'property_type']);
        $this->assertDatabaseCount('listings', 0);
    }

    public function test_the_owner_is_shown_on_the_listing_edit_page(): void
    {
        $user = User::factory()->create();

        $owner = Lead::factory()->for($user)->create(['type' => 'seller', 'first_name' => 'Ozzy', 'last_name' => 'Owner']);
        $this->actingAs($user)->post('/listings', $this->listingData(['owner_lead_id' => $owner->id]));

        $listing = Listing::first();

        $response = $this->actingAs($user)->get("/listings/{$listing->id}/edit?open=owner");
        $response->assertSee('Ozzy Owner');
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

    public function test_a_listing_needs_only_its_type_and_property_type_to_be_created(): void
    {
        $user = User::factory()->create(['country' => 'CA']);

        $response = $this->actingAs($user)->post('/listings', [
            'listing_type' => 'sale',
            'property_type' => 'condo',
        ]);

        $response->assertSessionHasNoErrors();
        $listing = Listing::first();
        $this->assertNotNull($listing);
        $this->assertNull($listing->address_line);
        $this->assertNull($listing->owner_lead_id);
        $this->assertSame('CAD', $listing->currency);
        $response->assertRedirect(route('listings.edit', [$listing, 'open' => ['details']]));
    }

    public function test_a_listing_without_an_address_shows_a_placeholder_title(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/listings', ['listing_type' => 'sale', 'property_type' => 'condo']);
        $listing = Listing::first();

        $this->actingAs($user)->get('/listings')->assertSee('New listing');
        $this->actingAs($user)->get("/listings/{$listing->id}/edit")->assertOk()->assertSee('New listing');
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

    public function test_deleting_a_listing_keeps_its_owner_lead(): void
    {
        $user = User::factory()->create();
        $owner = Lead::factory()->for($user)->create(['type' => 'seller']);
        $listing = Listing::factory()->for($user)->create(['owner_lead_id' => $owner->id]);

        $this->actingAs($user)->delete("/listings/{$listing->id}");

        $this->assertDatabaseMissing('listings', ['id' => $listing->id]);
        $this->assertDatabaseHas('leads', ['id' => $owner->id]);
    }

    public function test_uploading_a_photo_on_create_succeeds_and_it_displays(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/listings', $this->listingData([
            'photos' => [UploadedFile::fake()->image('front.jpg')],
        ]));

        $listing = Listing::first();
        $this->assertCount(1, $listing->photos);
        $this->assertNotNull($listing->photo_path);
        Storage::disk('public')->assertExists($listing->photo_path);

        $index = $this->actingAs($user)->get('/listings');
        $index->assertSee(Storage::url($listing->photo_path), false);
    }

    public function test_a_listing_can_have_several_photos_and_the_first_is_the_cover(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create();

        $this->actingAs($user)->patch("/listings/{$listing->id}", $this->listingData([
            'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ]));
        $this->actingAs($user)->patch("/listings/{$listing->id}", $this->listingData([
            'photos' => [UploadedFile::fake()->image('c.jpg')],
        ]));

        $listing = $listing->fresh();
        $this->assertCount(3, $listing->photos);
        $this->assertSame($listing->photos->first()->path, $listing->photo_path);
    }

    public function test_a_listing_cannot_have_more_than_six_photos(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create();

        $seven = array_map(fn ($i) => UploadedFile::fake()->image("p{$i}.jpg"), range(1, 7));

        $this->actingAs($user)->patch("/listings/{$listing->id}", $this->listingData(['photos' => $seven]))
            ->assertSessionHasErrors('photos');
        $this->assertCount(0, $listing->fresh()->photos);

        $six = array_map(fn ($i) => UploadedFile::fake()->image("q{$i}.jpg"), range(1, 6));
        $this->actingAs($user)->patch("/listings/{$listing->id}", $this->listingData(['photos' => $six]))
            ->assertSessionHasNoErrors();
        $this->assertCount(6, $listing->fresh()->photos);

        $this->actingAs($user)->patch("/listings/{$listing->id}", $this->listingData(['photos' => [UploadedFile::fake()->image('extra.jpg')]]))
            ->assertSessionHasErrors('photos');
        $this->assertCount(6, $listing->fresh()->photos);
    }

    public function test_removing_a_photo_deletes_the_file_and_updates_the_cover(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create();
        $this->actingAs($user)->patch("/listings/{$listing->id}", $this->listingData([
            'photos' => [UploadedFile::fake()->image('first.jpg'), UploadedFile::fake()->image('second.jpg')],
        ]));
        [$first, $second] = $listing->fresh()->photos->all();

        $this->actingAs($user)->patch("/listings/{$listing->id}", $this->listingData([
            'remove_photos' => [$first->id],
        ]));

        Storage::disk('public')->assertMissing($first->path);
        Storage::disk('public')->assertExists($second->path);
        $this->assertSame($second->path, $listing->fresh()->photo_path);

        $this->actingAs($user)->patch("/listings/{$listing->id}", $this->listingData([
            'remove_photos' => [$second->id],
        ]));
        $this->assertNull($listing->fresh()->photo_path);
    }

    public function test_one_listing_cannot_remove_another_listings_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $mine = Listing::factory()->for($user)->create();
        $other = Listing::factory()->for($user)->create();
        $this->actingAs($user)->patch("/listings/{$other->id}", $this->listingData([
            'photos' => [UploadedFile::fake()->image('other.jpg')],
        ]));
        $theirs = $other->fresh()->photos->first();

        $this->actingAs($user)->patch("/listings/{$mine->id}", $this->listingData(['remove_photos' => [$theirs->id]]));

        $this->assertNotNull($theirs->fresh());
        Storage::disk('public')->assertExists($theirs->path);
    }

    public function test_deleting_a_listing_removes_all_its_photo_files(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create();
        $this->actingAs($user)->patch("/listings/{$listing->id}", $this->listingData([
            'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ]));
        $paths = $listing->fresh()->photos->pluck('path');

        $this->actingAs($user)->delete("/listings/{$listing->id}");

        $paths->each(fn ($path) => Storage::disk('public')->assertMissing($path));
        $this->assertDatabaseCount('listing_photos', 0);
    }

    public function test_edit_page_shows_the_photos_and_the_add_photos_area(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create();
        $this->actingAs($user)->patch("/listings/{$listing->id}", $this->listingData([
            'photos' => [UploadedFile::fake()->image('front.jpg')],
        ]));

        $response = $this->actingAs($user)->get("/listings/{$listing->id}/edit");

        $response->assertOk();
        $response->assertSee('Add photos');
        $response->assertSee('1/6');
        $response->assertSee(Storage::url($listing->fresh()->photos->first()->path), false);
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

    public function test_a_rental_listing_only_accepts_renter_leads(): void
    {
        $user = User::factory()->create();
        $buyer = Lead::factory()->for($user)->create(['first_name' => 'Bella', 'last_name' => '', 'type' => 'buyer']);
        $renter = Lead::factory()->for($user)->create(['first_name' => 'Rita', 'last_name' => '', 'type' => 'renter']);

        $this->actingAs($user)->post('/listings', $this->listingData(['listing_type' => 'rent', 'lead_ids' => [$buyer->id]]))
            ->assertSessionHasErrors('lead_ids.0');
        $this->assertSame(0, Listing::count());

        $this->actingAs($user)->post('/listings', $this->listingData(['listing_type' => 'rent', 'lead_ids' => [$renter->id]]))
            ->assertSessionHasNoErrors();
        $this->assertCount(1, Listing::first()->leads);
    }

    public function test_lead_search_filters_by_lead_type(): void
    {
        $user = User::factory()->create();
        Lead::factory()->for($user)->create(['first_name' => 'Bella', 'last_name' => '', 'type' => 'buyer']);
        Lead::factory()->for($user)->create(['first_name' => 'Rita', 'last_name' => '', 'type' => 'renter']);

        $this->actingAs($user)->getJson('/api/leads-search?lead_type=renter')
            ->assertJsonCount(1)
            ->assertJsonFragment(['label' => 'Rita']);
    }

    public function test_linking_leads_to_a_listing_on_create_is_visible_on_edit(): void
    {
        $user = User::factory()->create();
        $leadA = Lead::factory()->for($user)->create(['first_name' => 'Alice', 'last_name' => '', 'type' => 'buyer']);
        $leadB = Lead::factory()->for($user)->create(['first_name' => 'Bob', 'last_name' => '', 'type' => 'buyer']);

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

    public function test_the_listing_edit_page_shows_the_owners_personal_info_and_documents(): void
    {
        $user = User::factory()->create();
        $owner = Lead::factory()->for($user)->create(['type' => 'seller', 'property_type' => 'condo', 'first_name' => 'Sonia', 'last_name' => 'Smith']);
        $listing = Listing::factory()->for($user)->create(['owner_lead_id' => $owner->id, 'listing_type' => 'sale']);

        $response = $this->actingAs($user)->get("/listings/{$listing->id}/edit?open=owner,owner-profile,personal,documents");

        $response->assertOk();
        $response->assertSee('Sonia Smith');
        $response->assertSee('RECO Information Guide');
    }

    public function test_editing_the_owners_personal_info_from_the_listing_page_redirects_back_to_the_listing(): void
    {
        $user = User::factory()->create();
        $owner = Lead::factory()->for($user)->create(['type' => 'seller', 'property_type' => 'condo', 'first_name' => 'Sonia']);
        $listing = Listing::factory()->for($user)->create(['owner_lead_id' => $owner->id, 'listing_type' => 'sale']);

        $response = $this->actingAs($user)->patch("/leads/{$owner->id}", [
            'first_name' => 'Sonia Updated',
            'type' => 'seller',
            'from_listing' => $listing->id,
        ]);

        $response->assertRedirect("/listings/{$listing->id}/edit");
        $this->assertSame('Sonia Updated', $owner->fresh()->first_name);
    }

    public function test_editing_a_seller_without_a_source_listing_stays_on_the_lead_page(): void
    {
        $user = User::factory()->create();
        $owner = Lead::factory()->for($user)->create(['type' => 'seller', 'first_name' => 'Sonia']);
        Listing::factory()->for($user)->create(['owner_lead_id' => $owner->id, 'listing_type' => 'sale']);

        $this->actingAs($user)->patch("/leads/{$owner->id}", ['first_name' => 'Sonia Two', 'type' => 'seller'])
            ->assertRedirect("/leads/{$owner->id}/edit");
    }

    public function test_checking_the_owners_documents_from_the_listing_page_advances_their_status(): void
    {
        $user = User::factory()->create();
        $owner = Lead::factory()->for($user)->create(['type' => 'seller', 'property_type' => 'condo', 'status' => 'new']);
        Listing::factory()->for($user)->create(['owner_lead_id' => $owner->id, 'listing_type' => 'sale']);

        $this->actingAs($user)->patch("/leads/{$owner->id}", [
            'type' => 'seller',
            'documents' => [
                'reco_information_guide' => '1',
                'id_client_verification' => '1',
            ],
        ]);

        $this->assertSame('new', $owner->fresh()->status);
    }

    public function test_a_buyer_is_picked_from_the_interested_leads(): void
    {
        $user = User::factory()->create();
        $buyer = Lead::factory()->for($user)->create(['type' => 'buyer', 'first_name' => 'Bruno', 'last_name' => 'Buyer']);
        $seller = Lead::factory()->for($user)->create(['type' => 'seller']);
        $listing = Listing::factory()->for($user)->create(['listing_type' => 'sale', 'owner_lead_id' => $seller->id]);

        $this->actingAs($user)->patch("/listings/{$listing->id}", $this->listingData([
            'owner_lead_id' => $seller->id,
            'lead_ids' => [$buyer->id],
            'buyer_lead_id' => $buyer->id,
        ]))->assertSessionHasNoErrors();

        $this->assertSame($buyer->id, $listing->fresh()->buyer_lead_id);
    }

    public function test_a_lead_who_is_not_interested_is_not_kept_as_the_buyer(): void
    {
        $user = User::factory()->create();
        $buyer = Lead::factory()->for($user)->create(['type' => 'buyer']);
        $seller = Lead::factory()->for($user)->create(['type' => 'seller']);
        $listing = Listing::factory()->for($user)->create(['listing_type' => 'sale', 'owner_lead_id' => $seller->id]);

        $this->actingAs($user)->patch("/listings/{$listing->id}", $this->listingData([
            'owner_lead_id' => $seller->id,
            'lead_ids' => [],
            'buyer_lead_id' => $buyer->id,
        ]))->assertSessionHasNoErrors();

        $this->assertNull($listing->fresh()->buyer_lead_id);
    }

    public function test_removing_the_buyer_from_the_interested_leads_clears_the_buyer(): void
    {
        $user = User::factory()->create();
        $buyer = Lead::factory()->for($user)->create(['type' => 'buyer']);
        $seller = Lead::factory()->for($user)->create(['type' => 'seller']);
        $listing = Listing::factory()->for($user)->create(['listing_type' => 'sale', 'owner_lead_id' => $seller->id, 'buyer_lead_id' => $buyer->id]);
        $listing->leads()->sync([$buyer->id]);

        $this->actingAs($user)->patch("/listings/{$listing->id}", $this->listingData([
            'owner_lead_id' => $seller->id,
            'lead_ids' => [],
            'buyer_lead_id' => $buyer->id,
        ]))->assertSessionHasNoErrors();

        $this->assertNull($listing->fresh()->buyer_lead_id);
        $this->assertCount(0, $listing->fresh()->leads);
    }

    public function test_only_a_buyer_can_be_interested_in_a_sale_and_only_a_renter_in_a_rental(): void
    {
        $user = User::factory()->create();
        $buyer = Lead::factory()->for($user)->create(['type' => 'buyer']);
        $renter = Lead::factory()->for($user)->create(['type' => 'renter']);
        $sale = Listing::factory()->for($user)->create(['listing_type' => 'sale']);

        $this->actingAs($user)->patch("/listings/{$sale->id}", $this->listingData(['lead_ids' => [$renter->id], 'buyer_lead_id' => $renter->id]))
            ->assertSessionHasErrors('lead_ids.0');
        $this->actingAs($user)->patch("/listings/{$sale->id}", $this->listingData(['lead_ids' => [$buyer->id], 'buyer_lead_id' => $buyer->id]))
            ->assertSessionHasNoErrors();
    }

    public function test_the_buyer_cannot_be_another_agents_lead(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $strangers = Lead::factory()->for($other)->create(['type' => 'buyer']);
        $listing = Listing::factory()->for($user)->create(['listing_type' => 'sale']);

        $this->actingAs($user)->patch("/listings/{$listing->id}", $this->listingData(['lead_ids' => [$strangers->id], 'buyer_lead_id' => $strangers->id]))
            ->assertSessionHasErrors('lead_ids.0');
        $this->assertNull($listing->fresh()->buyer_lead_id);
    }

    public function test_the_buyer_can_be_cleared(): void
    {
        $user = User::factory()->create();
        $buyer = Lead::factory()->for($user)->create(['type' => 'buyer']);
        $listing = Listing::factory()->for($user)->create(['listing_type' => 'sale', 'buyer_lead_id' => $buyer->id]);

        $this->actingAs($user)->patch("/listings/{$listing->id}", $this->listingData(['buyer_lead_id' => '']))
            ->assertSessionHasNoErrors();

        $this->assertNull($listing->fresh()->buyer_lead_id);
    }

    public function test_the_buyer_is_shown_on_the_listing_edit_page_with_personal_info_and_documents(): void
    {
        $user = User::factory()->create();
        $buyer = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'first_name' => 'Bruno', 'last_name' => 'Buyer']);
        $listing = Listing::factory()->for($user)->create(['listing_type' => 'sale', 'buyer_lead_id' => $buyer->id]);

        $response = $this->actingAs($user)->get("/listings/{$listing->id}/edit?open=buyer,buyer-profile,buyer-personal,buyer-documents");

        $response->assertOk();
        $response->assertSee('Bruno Buyer');
        $response->assertSee('Buyer Representation Agreement');
    }

    public function test_editing_the_buyer_from_the_listing_page_redirects_back_to_the_listing(): void
    {
        $user = User::factory()->create();
        $buyer = Lead::factory()->for($user)->create(['type' => 'buyer', 'first_name' => 'Bruno']);
        $listing = Listing::factory()->for($user)->create(['listing_type' => 'sale', 'buyer_lead_id' => $buyer->id]);

        $response = $this->actingAs($user)->patch("/leads/{$buyer->id}", [
            'first_name' => 'Bruno Updated',
            'type' => 'buyer',
            'from_listing' => $listing->id,
        ]);

        $response->assertRedirect("/listings/{$listing->id}/edit");
        $this->assertSame('Bruno Updated', $buyer->fresh()->first_name);
    }

    public function test_a_listings_buyer_cannot_change_type(): void
    {
        $user = User::factory()->create();
        $buyer = Lead::factory()->for($user)->create(['type' => 'buyer']);
        Listing::factory()->for($user)->create(['listing_type' => 'sale', 'buyer_lead_id' => $buyer->id]);

        $this->actingAs($user)->patch("/leads/{$buyer->id}", ['first_name' => 'Bruno', 'type' => 'renter'])
            ->assertSessionHasErrors('type');
        $this->assertSame('buyer', $buyer->fresh()->type);
    }

    public function test_deleting_the_buyer_lead_clears_it_from_the_listing(): void
    {
        $user = User::factory()->create();
        $buyer = Lead::factory()->for($user)->create(['type' => 'buyer']);
        $listing = Listing::factory()->for($user)->create(['listing_type' => 'sale', 'buyer_lead_id' => $buyer->id]);

        $this->actingAs($user)->delete("/leads/{$buyer->id}");

        $this->assertNull($buyer->fresh());
        $this->assertNull($listing->fresh()->buyer_lead_id);
    }

    public function test_adding_notes_to_a_listing_shows_them_most_recent_first(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create();

        $this->actingAs($user)->post("/listings/{$listing->id}/notes", ['body' => 'Photographer booked.']);
        $this->actingAs($user)->post("/listings/{$listing->id}/notes", ['body' => 'Open house on Sunday.']);

        $edit = $this->actingAs($user)->get("/listings/{$listing->id}/edit");

        $edit->assertOk();
        $edit->assertSeeInOrder(['Open house on Sunday.', 'Photographer booked.']);
    }

    public function test_empty_listing_note_is_rejected(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create();

        $response = $this->actingAs($user)->post("/listings/{$listing->id}/notes", ['body' => '']);

        $response->assertSessionHasErrors('body');
        $this->assertDatabaseCount('listing_notes', 0);
    }

    public function test_agent_can_delete_a_note_from_their_own_listing(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create();
        $note = ListingNote::factory()->for($listing)->create();

        $response = $this->actingAs($user)->delete("/listings/{$listing->id}/notes/{$note->id}");

        $response->assertRedirect(route('listings.edit', $listing));
        $this->assertDatabaseMissing('listing_notes', ['id' => $note->id]);
    }

    public function test_deleting_a_note_that_belongs_to_a_different_listing_is_rejected(): void
    {
        $user = User::factory()->create();
        $listingA = Listing::factory()->for($user)->create();
        $listingB = Listing::factory()->for($user)->create();
        $note = ListingNote::factory()->for($listingA)->create();

        $this->actingAs($user)->delete("/listings/{$listingB->id}/notes/{$note->id}")->assertNotFound();

        $this->assertDatabaseHas('listing_notes', ['id' => $note->id]);
    }

    public function test_an_agent_cannot_add_or_delete_notes_on_another_agents_listing(): void
    {
        $agentA = User::factory()->create();
        $agentB = User::factory()->create();
        $listing = Listing::factory()->for($agentA)->create();
        $note = ListingNote::factory()->for($listing)->create();

        $this->actingAs($agentB)->post("/listings/{$listing->id}/notes", ['body' => 'sneaky'])->assertForbidden();
        $this->actingAs($agentB)->delete("/listings/{$listing->id}/notes/{$note->id}")->assertForbidden();

        $this->assertDatabaseHas('listing_notes', ['id' => $note->id]);
        $this->assertDatabaseMissing('listing_notes', ['body' => 'sneaky']);
    }

    public function test_deleting_a_listing_deletes_its_notes(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create();
        ListingNote::factory()->for($listing)->create();

        $this->actingAs($user)->delete("/listings/{$listing->id}");

        $this->assertDatabaseCount('listing_notes', 0);
    }
}

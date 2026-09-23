<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    private function leadData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '555-1234',
            'email' => 'jane@example.com',
        ], $overrides);
    }

    public function test_agent_can_create_a_lead_and_it_appears_in_the_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/leads', $this->leadData());

        $lead = Lead::first();
        $response->assertRedirect("/leads/{$lead->id}/edit");
        $this->assertDatabaseHas('leads', [
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'status' => 'new',
        ]);

        $index = $this->actingAs($user)->get('/leads');
        $index->assertOk();
        $index->assertSee('Jane Doe');
    }

    public function test_index_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $qualified = Lead::factory()->for($user)->create(['status' => 'qualified', 'first_name' => 'Qualified', 'last_name' => 'Lead']);
        $lost = Lead::factory()->for($user)->create(['status' => 'lost', 'first_name' => 'Lost', 'last_name' => 'Lead']);

        $response = $this->actingAs($user)->get('/leads?' . http_build_query(['status' => ['qualified']]));

        $response->assertOk();
        $response->assertSee('Qualified Lead');
        $response->assertDontSee('Lost Lead');
    }

    public function test_index_can_be_filtered_by_name(): void
    {
        $user = User::factory()->create();
        Lead::factory()->for($user)->create(['first_name' => 'Alice', 'last_name' => 'Nguyen']);
        Lead::factory()->for($user)->create(['first_name' => 'Bruno', 'last_name' => 'Silva']);

        $response = $this->actingAs($user)->get('/leads?name=Alice');

        $response->assertOk();
        $response->assertSee('Alice Nguyen');
        $response->assertDontSee('Bruno Silva');
    }

    public function test_a_lead_can_be_created_with_each_type(): void
    {
        $user = User::factory()->create();

        foreach (['buyer', 'seller', 'investor', 'renter', 'landlord'] as $type) {
            $this->actingAs($user)->post('/leads', $this->leadData(['type' => $type, 'email' => "{$type}@example.com"]));

            $this->assertDatabaseHas('leads', [
                'user_id' => $user->id,
                'email' => "{$type}@example.com",
                'type' => $type,
            ]);
        }
    }

    public function test_a_lead_created_without_a_type_saves_as_null(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/leads', $this->leadData());

        $lead = Lead::first();
        $this->assertNull($lead->type);
    }

    public function test_an_agent_can_still_manually_change_a_lost_leads_status(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'status' => 'lost']);

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData(['status' => 'new']));

        $this->assertSame('new', $lead->fresh()->status);
    }

    public function test_checking_a_document_on_a_buyer_condo_lead_persists_it(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo']);

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'property_type' => 'condo',
            'documents' => ['buyer_representation_agreement' => '1'],
        ]));

        $checklist = $lead->fresh()->documentChecklistForStage('qualified');
        $this->assertTrue($checklist['buyer_representation_agreement']['checked']);
        $this->assertFalse($checklist['disclosures']['checked']);
    }

    public function test_unchecking_a_document_persists_it(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo']);
        $lead->documents()->create(['key' => 'buyer_representation_agreement', 'checked' => true]);

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'property_type' => 'condo',
            'documents' => [],
        ]));

        $this->assertFalse($lead->fresh()->documentChecklistForStage('qualified')['buyer_representation_agreement']['checked']);
    }

    public function test_document_checklists_for_different_stages_are_independent(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo']);

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'property_type' => 'condo',
            'documents' => ['reco_information_guide' => '1'],
        ]));

        $lead->refresh();
        $this->assertTrue($lead->documentChecklistForStage('contacted')['reco_information_guide']['checked']);
        $this->assertFalse($lead->documentChecklistForStage('offer')['agreement_of_purchase_and_sale']['checked']);
    }

    public function test_checking_the_reco_guide_moves_a_new_lead_to_contacted(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'status' => 'new']);

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'property_type' => 'condo',
            'documents' => ['reco_information_guide' => '1'],
        ]));

        $this->assertSame('contacted', $lead->fresh()->status);
    }

    public function test_status_reflects_the_furthest_complete_stage(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'status' => 'new']);
        $listing = Listing::factory()->for($user)->create();

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'property_type' => 'condo',
            'listing_ids' => [$listing->id],
            'documents' => [
                'reco_information_guide' => '1',
                'buyer_representation_agreement' => '1',
                'disclosures' => '1',
                'agreement_of_purchase_and_sale' => '1',
                'schedules_addendums' => '1',
                'deposit_receipt' => '1',
                'proof_of_deposit' => '1',
            ],
        ]));

        $this->assertSame('offer', $lead->fresh()->status);
    }

    public function test_status_stops_at_the_first_incomplete_stage(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'status' => 'new']);

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'property_type' => 'condo',
            'documents' => [
                'reco_information_guide' => '1',
                // 'qualified' and 'offer' are left incomplete, but 'under_contract' is fully checked.
                'amendments' => '1',
                'waivers' => '1',
                'notices_of_fulfillment' => '1',
                'status_certificate' => '1',
            ],
        ]));

        $this->assertSame('contacted', $lead->fresh()->status);
    }

    public function test_status_regresses_when_a_document_is_unchecked(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'status' => 'qualified']);
        $lead->documents()->create(['key' => 'reco_information_guide', 'checked' => true]);
        $lead->documents()->create(['key' => 'buyer_representation_agreement', 'checked' => true]);
        $lead->documents()->create(['key' => 'disclosures', 'checked' => true]);

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'property_type' => 'condo',
            'documents' => [
                'reco_information_guide' => '1',
                'buyer_representation_agreement' => '1',
                // 'disclosures' is left unchecked this time.
            ],
        ]));

        $this->assertSame('contacted', $lead->fresh()->status);
    }

    public function test_advancing_status_from_documents_is_blocked_when_the_lead_is_lost(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'status' => 'lost']);

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'property_type' => 'condo',
            'documents' => ['reco_information_guide' => '1'],
        ]));

        $this->assertSame('lost', $lead->fresh()->status);
    }

    public function test_status_does_not_advance_to_offer_without_a_linked_listing(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'status' => 'qualified']);

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'property_type' => 'condo',
            'documents' => $this->offerDocuments(),
        ]));

        $this->assertSame('qualified', $lead->fresh()->status);
        $this->assertTrue($lead->fresh()->needsListingNarrowedForOffer());
    }

    public function test_status_does_not_advance_to_offer_with_multiple_linked_listings(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'status' => 'qualified']);
        $listingA = Listing::factory()->for($user)->create();
        $listingB = Listing::factory()->for($user)->create();

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'property_type' => 'condo',
            'listing_ids' => [$listingA->id, $listingB->id],
            'documents' => $this->offerDocuments(),
        ]));

        $this->assertSame('qualified', $lead->fresh()->status);
        $this->assertTrue($lead->fresh()->needsListingNarrowedForOffer());
    }

    public function test_status_advances_to_offer_once_narrowed_to_one_listing(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'status' => 'qualified']);
        $listing = Listing::factory()->for($user)->create();

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'property_type' => 'condo',
            'listing_ids' => [$listing->id],
            'documents' => $this->offerDocuments(),
        ]));

        $this->assertSame('offer', $lead->fresh()->status);
        $this->assertFalse($lead->fresh()->needsListingNarrowedForOffer());
    }

    public function test_the_narrow_listing_warning_is_shown_on_the_edit_page(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'status' => 'qualified']);
        $listingA = Listing::factory()->for($user)->create();
        $listingB = Listing::factory()->for($user)->create();
        $lead->listings()->attach([$listingA->id, $listingB->id]);
        foreach ($this->offerDocuments() as $key => $value) {
            $lead->documents()->create(['key' => $key, 'checked' => true]);
        }

        $response = $this->actingAs($user)->get("/leads/{$lead->id}/edit?open=listings");

        $response->assertSee('remove all but the one this offer is for', false);
    }

    private function offerDocuments(): array
    {
        return [
            'reco_information_guide' => '1',
            'buyer_representation_agreement' => '1',
            'disclosures' => '1',
            'agreement_of_purchase_and_sale' => '1',
            'schedules_addendums' => '1',
            'deposit_receipt' => '1',
            'proof_of_deposit' => '1',
        ];
    }

    private function underContractDocuments(): array
    {
        return [
            ...$this->offerDocuments(),
            'amendments' => '1',
            'waivers' => '1',
            'notices_of_fulfillment' => '1',
            'status_certificate' => '1',
        ];
    }

    private function closedDocuments(): array
    {
        return [
            ...$this->underContractDocuments(),
            'final_agreement_of_purchase_and_sale' => '1',
            'final_amendments' => '1',
            'trade_record_sheet' => '1',
        ];
    }

    public function test_linked_listing_becomes_pending_when_a_lead_completes_under_contract(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'status' => 'offer']);
        $listing = Listing::factory()->for($user)->create(['status' => 'available']);
        $lead->listings()->attach($listing);

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'property_type' => 'condo',
            'listing_ids' => [$listing->id],
            'documents' => $this->underContractDocuments(),
        ]));

        $this->assertSame('under_contract', $lead->fresh()->status);
        $this->assertSame('pending', $listing->fresh()->status);
    }

    public function test_linked_listing_becomes_closed_when_a_lead_completes_the_funnel(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'status' => 'under_contract']);
        $listing = Listing::factory()->for($user)->create(['status' => 'pending']);
        $lead->listings()->attach($listing);

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'property_type' => 'condo',
            'listing_ids' => [$listing->id],
            'documents' => $this->closedDocuments(),
        ]));

        $this->assertSame('closed', $lead->fresh()->status);
        $this->assertSame('closed', $listing->fresh()->status);
    }

    public function test_a_closed_listing_stays_closed_even_if_the_closing_leads_documents_regress(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'status' => 'closed']);
        $listing = Listing::factory()->for($user)->create(['status' => 'closed']);
        $lead->listings()->attach($listing);
        foreach ($this->closedDocuments() as $key => $value) {
            $lead->documents()->create(['key' => $key, 'checked' => true]);
        }

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'property_type' => 'condo',
            'listing_ids' => [$listing->id],
            'documents' => $this->underContractDocuments(),
        ]));

        $this->assertSame('under_contract', $lead->fresh()->status);
        $this->assertSame('closed', $listing->fresh()->status);
    }

    public function test_first_lead_to_close_locks_the_listing_even_with_other_interested_leads(): void
    {
        $user = User::factory()->create();
        $closingLead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'status' => 'under_contract']);
        $otherLead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'status' => 'new']);
        $listing = Listing::factory()->for($user)->create(['status' => 'pending']);
        $closingLead->listings()->attach($listing);
        $otherLead->listings()->attach($listing);

        $this->actingAs($user)->patch("/leads/{$closingLead->id}", $this->leadData([
            'property_type' => 'condo',
            'listing_ids' => [$listing->id],
            'documents' => $this->closedDocuments(),
        ]));

        $this->assertSame('closed', $listing->fresh()->status);
    }

    public function test_a_non_buyer_lead_has_no_document_checklist(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'seller', 'property_type' => 'condo']);

        $this->assertFalse($lead->hasDocumentChecklist());
    }

    public function test_a_buyer_lead_with_a_property_type_other_than_condo_has_no_document_checklist_yet(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'house']);

        $this->assertFalse($lead->hasDocumentChecklist());
    }

    public function test_a_buyer_lead_without_a_property_type_has_no_document_checklist(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => null]);

        $this->assertFalse($lead->hasDocumentChecklist());
    }

    public function test_missing_first_name_is_rejected(): void
    {
        $user = User::factory()->create();

        $data = $this->leadData();
        unset($data['first_name']);

        $response = $this->actingAs($user)->post('/leads', $data);

        $response->assertSessionHasErrors('first_name');
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_linking_a_lead_to_listings_on_create_is_visible_on_edit(): void
    {
        $user = User::factory()->create();
        $listingA = Listing::factory()->for($user)->create();
        $listingB = Listing::factory()->for($user)->create();

        $this->actingAs($user)->post('/leads', $this->leadData([
            'listing_ids' => [$listingA->id, $listingB->id],
        ]));

        $lead = Lead::first();
        $this->assertCount(2, $lead->listings);

        $edit = $this->actingAs($user)->get("/leads/{$lead->id}/edit");
        $edit->assertSee($listingA->address_line);
        $edit->assertSee($listingB->address_line);
    }

    public function test_linking_to_another_agents_listing_is_rejected(): void
    {
        $user = User::factory()->create();
        $otherAgentsListing = Listing::factory()->create();

        $response = $this->actingAs($user)->post('/leads', $this->leadData([
            'listing_ids' => [$otherAgentsListing->id],
        ]));

        $response->assertSessionHasErrors('listing_ids.0');
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_editing_a_lead_can_change_its_linked_listings(): void
    {
        $user = User::factory()->create();
        $listingA = Listing::factory()->for($user)->create();
        $listingB = Listing::factory()->for($user)->create();
        $lead = Lead::factory()->for($user)->create();
        $lead->listings()->sync([$listingA->id]);

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'listing_ids' => [$listingB->id],
        ]));

        $lead->refresh();
        $this->assertCount(1, $lead->listings);
        $this->assertSame($listingB->id, $lead->listings->first()->id);
    }

    public function test_status_can_move_between_funnel_values_and_lost_leads_stay_visible(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['status' => 'new']);

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData(['status' => 'qualified']));
        $this->assertSame('qualified', $lead->fresh()->status);

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData(['status' => 'lost']));
        $this->assertSame('lost', $lead->fresh()->status);

        $index = $this->actingAs($user)->get('/leads');
        $index->assertSee($lead->fresh()->name);
    }

    public function test_adding_notes_shows_them_most_recent_first(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create();

        $this->actingAs($user)->post("/leads/{$lead->id}/notes", ['body' => 'First contact, left voicemail.']);
        $this->actingAs($user)->post("/leads/{$lead->id}/notes", ['body' => 'Called back, wants a viewing.']);

        $edit = $this->actingAs($user)->get("/leads/{$lead->id}/edit");

        $edit->assertOk();
        $edit->assertSeeInOrder(['Called back, wants a viewing.', 'First contact, left voicemail.']);
    }

    public function test_empty_note_is_rejected(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create();

        $response = $this->actingAs($user)->post("/leads/{$lead->id}/notes", ['body' => '']);

        $response->assertSessionHasErrors('body');
        $this->assertDatabaseCount('lead_notes', 0);
    }

    public function test_agent_can_delete_a_note_from_their_own_lead(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create();
        $note = LeadNote::factory()->for($lead)->create();

        $response = $this->actingAs($user)->delete("/leads/{$lead->id}/notes/{$note->id}");

        $response->assertRedirect(route('leads.edit', $lead));
        $this->assertDatabaseMissing('lead_notes', ['id' => $note->id]);
    }

    public function test_deleting_a_note_that_belongs_to_a_different_lead_is_rejected(): void
    {
        $user = User::factory()->create();
        $leadA = Lead::factory()->for($user)->create();
        $leadB = Lead::factory()->for($user)->create();
        $note = LeadNote::factory()->for($leadA)->create();

        $response = $this->actingAs($user)->delete("/leads/{$leadB->id}/notes/{$note->id}");

        $response->assertNotFound();
        $this->assertDatabaseHas('lead_notes', ['id' => $note->id]);
    }

    public function test_an_agent_cannot_see_or_edit_another_agents_lead(): void
    {
        $agentA = User::factory()->create();
        $agentB = User::factory()->create();
        $leadA = Lead::factory()->for($agentA)->create(['first_name' => 'Only', 'last_name' => 'AgentAKnowsMe']);
        $noteA = LeadNote::factory()->for($leadA)->create();

        $index = $this->actingAs($agentB)->get('/leads');
        $index->assertDontSee('AgentAKnowsMe');

        $this->actingAs($agentB)->get("/leads/{$leadA->id}/edit")->assertForbidden();
        $this->actingAs($agentB)->patch("/leads/{$leadA->id}", $this->leadData())->assertForbidden();
        $this->actingAs($agentB)->post("/leads/{$leadA->id}/notes", ['body' => 'sneaky'])->assertForbidden();
        $this->actingAs($agentB)->delete("/leads/{$leadA->id}/notes/{$noteA->id}")->assertForbidden();
        $this->actingAs($agentB)->delete("/leads/{$leadA->id}")->assertForbidden();
        $this->assertDatabaseHas('leads', ['id' => $leadA->id]);
        $this->assertDatabaseHas('lead_notes', ['id' => $noteA->id]);
    }

    public function test_agent_can_delete_their_own_lead_and_its_notes(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create();
        $lead->notes()->create(['body' => 'A note that should be cascade-deleted.']);

        $response = $this->actingAs($user)->delete("/leads/{$lead->id}");

        $response->assertRedirect('/leads');
        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
        $this->assertDatabaseMissing('lead_notes', ['lead_id' => $lead->id]);
    }
}

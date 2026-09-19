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

        $response->assertRedirect('/leads');
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

    public function test_first_note_on_a_new_buyer_lead_moves_it_to_contacted(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'status' => 'new']);

        $this->actingAs($user)->post("/leads/{$lead->id}/notes", ['body' => 'Left a voicemail.']);

        $this->assertSame('contacted', $lead->fresh()->status);
    }

    public function test_completing_the_financing_checklist_on_a_contacted_buyer_lead_moves_it_to_qualified(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'status' => 'contacted']);

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'financing_preapproval' => '1',
            'financing_income_proof' => '1',
            'financing_id_document' => '1',
        ]));

        $this->assertSame('qualified', $lead->fresh()->status);
    }

    public function test_linking_a_listing_to_a_qualified_buyer_lead_moves_it_to_active_search(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'status' => 'qualified']);

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'listing_ids' => [$listing->id],
        ]));

        $this->assertSame('active_search', $lead->fresh()->status);
    }

    public function test_linking_a_listing_to_a_new_buyer_lead_does_not_advance_it_to_active_search(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'status' => 'new']);

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'listing_ids' => [$listing->id],
        ]));

        $this->assertSame('new', $lead->fresh()->status);
    }

    public function test_completing_the_checklist_before_any_note_waits_for_the_first_note_then_jumps_to_qualified(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'status' => 'new']);

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'financing_preapproval' => '1',
            'financing_income_proof' => '1',
            'financing_id_document' => '1',
        ]));
        $this->assertSame('new', $lead->fresh()->status);

        $this->actingAs($user)->post("/leads/{$lead->id}/notes", ['body' => 'First contact.']);
        $this->assertSame('qualified', $lead->fresh()->status);
    }

    public function test_removing_a_note_or_unchecking_the_checklist_does_not_move_status_backward(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create([
            'type' => 'buyer',
            'status' => 'active_search',
            'financing_preapproval' => true,
            'financing_income_proof' => true,
            'financing_id_document' => true,
        ]);
        $note = $lead->notes()->create(['body' => 'Only note.']);

        $this->actingAs($user)->delete("/leads/{$lead->id}/notes/{$note->id}");
        $this->assertSame('active_search', $lead->fresh()->status);

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'financing_income_proof' => '1',
            'financing_id_document' => '1',
        ]));
        $this->assertSame('active_search', $lead->fresh()->status);
    }

    public function test_marking_a_lead_lost_blocks_every_automatic_transition(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->for($user)->create();

        // Lost from 'new': adding a note should not move it to Contacted.
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'status' => 'lost']);
        $this->actingAs($user)->post("/leads/{$lead->id}/notes", ['body' => 'Trying anyway.']);
        $this->assertSame('lost', $lead->fresh()->status);

        // Lost from what would have been 'contacted': completing the checklist should not qualify it.
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'status' => 'lost']);
        $lead->notes()->create(['body' => 'Already contacted before going lost.']);
        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'financing_preapproval' => '1',
            'financing_income_proof' => '1',
            'financing_id_document' => '1',
        ]));
        $this->assertSame('lost', $lead->fresh()->status);

        // Lost from what would have been 'qualified': linking a listing should not activate the search.
        $lead = Lead::factory()->for($user)->create([
            'type' => 'buyer',
            'status' => 'lost',
            'financing_preapproval' => true,
            'financing_income_proof' => true,
            'financing_id_document' => true,
        ]);
        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData([
            'listing_ids' => [$listing->id],
        ]));
        $this->assertSame('lost', $lead->fresh()->status);
    }

    public function test_an_agent_can_still_manually_change_a_lost_leads_status(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'status' => 'lost']);

        $this->actingAs($user)->patch("/leads/{$lead->id}", $this->leadData(['status' => 'new']));

        $this->assertSame('new', $lead->fresh()->status);
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

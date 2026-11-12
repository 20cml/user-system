<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\StageDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nothing is built into the funnel: an agent starts with no columns and no
 * documents and sets both up in Settings. These tests do NOT seed any funnel.
 */
class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function columns(User $user, string $type, array $labels, string $propertyType = 'condo'): void
    {
        $this->actingAs($user)->put('/settings/pipeline', [
            'type' => $type,
            'property_type' => $propertyType,
            'stages' => array_map(fn ($label) => ['label' => $label], $labels),
        ])->assertRedirect();
    }

    private function stageKey(User $user, string $type, string $label, string $propertyType = 'condo'): string
    {
        return PipelineStage::where(['user_id' => $user->id, 'lead_type' => $type, 'property_type' => $propertyType, 'label' => $label])->value('key');
    }

    public function test_a_new_agent_starts_with_no_columns_and_no_documents(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseCount('pipeline_stages', 0);
        $this->assertDatabaseCount('stage_documents', 0);

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('No columns yet');
        $this->actingAs($user)->get('/settings/pipeline')->assertOk()->assertSee('No columns yet.');
        $this->actingAs($user)->get('/settings/documents')->assertOk()->assertSee('There are no columns for this lead type and property type yet.');
    }

    public function test_settings_redirects_to_the_pipeline_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/settings')->assertRedirect('/settings/pipeline');
    }

    public function test_settings_need_a_logged_in_agent(): void
    {
        $this->get('/settings/pipeline')->assertRedirect('/login');
        $this->put('/settings/pipeline', ['type' => 'buyer'])->assertRedirect('/login');
    }

    public function test_the_agent_chooses_the_columns_and_their_order(): void
    {
        $user = User::factory()->create();

        $this->columns($user, 'buyer', ['Inquiry', 'Viewing', 'Offer made']);

        $this->assertSame(
            ['Inquiry', 'Viewing', 'Offer made'],
            PipelineStage::where('user_id', $user->id)->orderBy('position')->pluck('label')->all(),
        );
        $this->assertSame(['inquiry', 'viewing', 'offer_made'], PipelineStage::where('user_id', $user->id)->orderBy('position')->pluck('key')->all());

        $this->actingAs($user)->get('/dashboard?pipeline=buyer')->assertSee('Inquiry')->assertSee('Viewing')->assertSee('Offer made');
    }

    public function test_blank_columns_are_ignored_and_each_lead_type_has_its_own(): void
    {
        $user = User::factory()->create();

        $this->columns($user, 'buyer', ['Inquiry', '   ', '']);
        $this->columns($user, 'seller', ['Valuation']);

        $this->assertSame(['Inquiry'], PipelineStage::where(['user_id' => $user->id, 'lead_type' => 'buyer'])->pluck('label')->all());
        $this->assertSame(['Valuation'], PipelineStage::where(['user_id' => $user->id, 'lead_type' => 'seller'])->pluck('label')->all());
    }

    public function test_one_agents_settings_never_touch_anothers(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->columns($a, 'buyer', ['A column']);
        $this->columns($b, 'buyer', ['B column']);
        $this->columns($b, 'buyer', []);

        $this->assertSame(['A column'], PipelineStage::where('user_id', $a->id)->pluck('label')->all());
        $this->assertDatabaseCount('pipeline_stages', 1);
    }

    public function test_an_invalid_lead_type_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/settings/pipeline', ['type' => 'dragon', 'property_type' => 'condo', 'stages' => [['label' => 'x']]])->assertSessionHasErrors('type');
        $this->actingAs($user)->put('/settings/pipeline', ['type' => 'buyer', 'property_type' => 'castle', 'stages' => [['label' => 'x']]])->assertSessionHasErrors('property_type');

        $this->assertDatabaseCount('pipeline_stages', 0);
    }

    public function test_renaming_a_column_keeps_its_leads_and_documents(): void
    {
        $user = User::factory()->create();
        $this->columns($user, 'buyer', ['Inquiry', 'Viewing']);
        $inquiry = $this->stageKey($user, 'buyer', 'Inquiry');

        $this->actingAs($user)->put('/settings/documents', [
            'type' => 'buyer', 'property_type' => 'condo',
            'documents' => [$inquiry => [['label' => 'ID']]],
        ]);
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'status' => $inquiry]);

        $this->actingAs($user)->put('/settings/pipeline', [
            'type' => 'buyer',
            'property_type' => 'condo',
            'stages' => [['key' => $inquiry, 'label' => 'First contact'], ['label' => 'Viewing']],
        ]);

        $this->assertSame($inquiry, $this->stageKey($user, 'buyer', 'First contact'));
        $this->assertSame('First contact', $lead->fresh()->statusLabel());
        $this->assertDatabaseCount('stage_documents', 1);
    }

    public function test_removing_a_column_moves_its_leads_to_a_column_that_exists(): void
    {
        $user = User::factory()->create();
        $this->columns($user, 'buyer', ['Inquiry', 'Viewing']);
        $viewing = $this->stageKey($user, 'buyer', 'Viewing');
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'status' => $viewing]);

        $this->actingAs($user)->put('/settings/pipeline', [
            'type' => 'buyer',
            'property_type' => 'condo',
            'stages' => [['key' => $this->stageKey($user, 'buyer', 'Inquiry'), 'label' => 'Inquiry']],
        ]);

        $this->assertSame('inquiry', $lead->fresh()->status);
        $this->assertDatabaseMissing('pipeline_stages', ['key' => $viewing]);
    }

    public function test_removing_every_column_leaves_leads_without_one(): void
    {
        $user = User::factory()->create();
        $this->columns($user, 'buyer', ['Inquiry']);
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'status' => 'inquiry']);

        $this->columns($user, 'buyer', []);

        $this->assertNull($lead->fresh()->status);
    }

    public function test_a_lead_gets_the_first_column_when_created_and_none_when_there_is_no_column(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/leads', ['type' => 'buyer', 'property_type' => 'condo']);
        $this->assertNull(Lead::first()->status);

        $this->columns($user, 'buyer', ['Inquiry', 'Viewing']);
        $this->assertSame('inquiry', Lead::first()->fresh()->status, 'existing leads get the first column once one exists');

        $this->actingAs($user)->post('/leads', ['type' => 'buyer', 'property_type' => 'condo']);
        $this->assertSame('inquiry', Lead::latest('id')->first()->status);

        $this->actingAs($user)->post('/leads', ['type' => 'buyer']);
        $this->assertNull(Lead::latest('id')->first()->status, 'no property type yet, so no funnel');
    }

    public function test_the_agent_chooses_the_documents_of_each_column(): void
    {
        $user = User::factory()->create();
        $this->columns($user, 'buyer', ['Inquiry', 'Offer']);
        $inquiry = $this->stageKey($user, 'buyer', 'Inquiry');
        $offer = $this->stageKey($user, 'buyer', 'Offer');

        $this->actingAs($user)->put('/settings/documents', [
            'type' => 'buyer', 'property_type' => 'condo',
            'documents' => [
                $inquiry => [['label' => 'ID'], ['label' => 'Amendments']],
                $offer => [['label' => 'Amendments'], ['label' => '  ']],
            ],
        ])->assertRedirect();

        $this->assertSame(['ID', 'Amendments'], StageDocument::whereHas('stage', fn ($q) => $q->where('key', $inquiry))->orderBy('position')->pluck('label')->all());
        $this->assertSame(['id', 'amendments', 'amendments_2'], StageDocument::orderBy('id')->pluck('key')->all(), 'two "Amendments" never share a key');
    }

    public function test_condo_and_land_can_have_different_columns_and_documents(): void
    {
        $user = User::factory()->create();
        $this->columns($user, 'buyer', ['Inquiry', 'Offer'], 'condo');
        $this->columns($user, 'buyer', ['Inquiry', 'Zoning check', 'Offer'], 'land');

        $this->actingAs($user)->put('/settings/documents', [
            'type' => 'buyer', 'property_type' => 'land',
            'documents' => [$this->stageKey($user, 'buyer', 'Zoning check', 'land') => [['label' => 'Zoning letter']]],
        ]);

        $this->assertSame(['Inquiry', 'Offer'], PipelineStage::where(['user_id' => $user->id, 'property_type' => 'condo'])->orderBy('position')->pluck('label')->all());
        $this->assertSame(['Inquiry', 'Zoning check', 'Offer'], PipelineStage::where(['user_id' => $user->id, 'property_type' => 'land'])->orderBy('position')->pluck('label')->all());
        $this->assertDatabaseCount('stage_documents', 1);

        $condoLead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo']);
        $landLead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'land']);
        $this->actingAs($user)->patch("/leads/{$landLead->id}", ['documents' => ['zoning_letter' => '1']]);

        $this->assertSame(['inquiry', 'offer'], $condoLead->fresh()->stageKeys());
        $this->assertSame(['inquiry', 'zoning_check', 'offer'], $landLead->fresh()->stageKeys());
        $this->assertSame('offer', $landLead->fresh()->status);
    }

    public function test_changing_a_leads_property_type_puts_it_in_the_new_funnel(): void
    {
        $user = User::factory()->create();
        $this->columns($user, 'buyer', ['Inquiry'], 'condo');
        $this->columns($user, 'buyer', ['First look'], 'land');
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'status' => 'inquiry']);

        $this->actingAs($user)->patch("/leads/{$lead->id}", ['property_type' => 'land']);

        $this->assertSame('first_look', $lead->fresh()->status);
    }

    public function test_a_lead_with_no_property_type_has_no_column(): void
    {
        $user = User::factory()->create();
        $this->columns($user, 'buyer', ['Inquiry']);
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => null, 'status' => 'inquiry']);

        $this->actingAs($user)->patch("/leads/{$lead->id}", ['first_name' => 'Ana']);

        $this->assertNull($lead->fresh()->status);
    }

    public function test_documents_drive_the_leads_column_and_saving_settings_re_evaluates_leads(): void
    {
        $user = User::factory()->create();
        $this->columns($user, 'buyer', ['Inquiry', 'Offer']);
        $inquiry = $this->stageKey($user, 'buyer', 'Inquiry');

        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'status' => $inquiry]);

        $this->actingAs($user)->put('/settings/documents', [
            'type' => 'buyer', 'property_type' => 'condo',
            'documents' => [$inquiry => [['label' => 'ID']]],
        ]);
        $this->assertSame('inquiry', $lead->fresh()->status, 'ID is not ticked yet');

        $this->actingAs($user)->patch("/leads/{$lead->id}", ['documents' => ['id' => '1']]);
        $this->assertSame('offer', $lead->fresh()->status, 'every document before Offer is ticked');

        // asking for one more document on Inquiry puts the lead back there
        $idKey = StageDocument::where('key', 'id')->value('key');
        $this->actingAs($user)->put('/settings/documents', [
            'type' => 'buyer', 'property_type' => 'condo',
            'documents' => [$inquiry => [['key' => $idKey, 'label' => 'ID'], ['label' => 'Proof of funds']]],
        ]);

        $this->assertSame('inquiry', $lead->fresh()->status);
    }

    public function test_a_property_type_with_no_documents_leaves_the_lead_in_its_column(): void
    {
        $user = User::factory()->create();
        $this->columns($user, 'buyer', ['Inquiry', 'Offer']);
        $offer = $this->stageKey($user, 'buyer', 'Offer');
        $lead = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'land', 'status' => $offer]);

        $this->actingAs($user)->put('/settings/pipeline', [
            'type' => 'buyer',
            'property_type' => 'condo',
            'stages' => [
                ['key' => $this->stageKey($user, 'buyer', 'Inquiry'), 'label' => 'Inquiry'],
                ['key' => $offer, 'label' => 'Offer'],
                ['label' => 'Closing'],
            ],
        ]);

        $this->assertSame($offer, $lead->fresh()->status);
    }

    public function test_an_invalid_property_type_is_rejected_for_documents(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/settings/documents', ['type' => 'buyer', 'property_type' => 'castle'])->assertSessionHasErrors('property_type');
    }

    public function test_the_documents_page_lists_the_columns_with_their_documents(): void
    {
        $user = User::factory()->create();
        $this->columns($user, 'seller', ['Valuation'], 'house');
        $this->columns($user, 'seller', ['Valuation'], 'condo');
        $this->actingAs($user)->put('/settings/documents', [
            'type' => 'seller', 'property_type' => 'house',
            'documents' => [$this->stageKey($user, 'seller', 'Valuation', 'house') => [['label' => 'Title deed']]],
        ]);

        $this->actingAs($user)->get('/settings/documents?type=seller&property_type=house')
            ->assertOk()->assertSee('Valuation')->assertSee('Title deed');
        $this->actingAs($user)->get('/settings/documents?type=seller&property_type=condo')
            ->assertOk()->assertSee('Valuation')->assertDontSee('Title deed');
    }

    public function test_a_funnel_can_have_at_most_twelve_columns(): void
    {
        $user = User::factory()->create();

        $this->columns($user, 'buyer', array_map(fn ($n) => "Column {$n}", range(1, 12)));
        $this->assertDatabaseCount('pipeline_stages', 12);

        $this->actingAs($user)->put('/settings/pipeline', [
            'type' => 'buyer', 'property_type' => 'condo',
            'stages' => array_map(fn ($n) => ['label' => "Column {$n}"], range(1, 13)),
        ])->assertSessionHasErrors('stages');

        $this->assertDatabaseCount('pipeline_stages', 12);
    }

    public function test_a_column_can_ask_for_at_most_twenty_documents(): void
    {
        $user = User::factory()->create();
        $this->columns($user, 'buyer', ['Inquiry']);
        $inquiry = $this->stageKey($user, 'buyer', 'Inquiry');
        $documents = fn (int $count) => [$inquiry => array_map(fn ($n) => ['label' => "Doc {$n}"], range(1, $count))];

        $this->actingAs($user)->put('/settings/documents', ['type' => 'buyer', 'property_type' => 'condo', 'documents' => $documents(20)])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('stage_documents', 20);

        $this->actingAs($user)->put('/settings/documents', ['type' => 'buyer', 'property_type' => 'condo', 'documents' => $documents(21)])->assertSessionHasErrors();
        $this->assertDatabaseCount('stage_documents', 20);
    }
}

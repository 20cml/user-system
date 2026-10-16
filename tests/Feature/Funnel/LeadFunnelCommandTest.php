<?php

namespace Tests\Feature\Funnel;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Artisan command is the funnel's second interface. These tests check
 * it works, and — more importantly — that it reaches the same result as the
 * web form, because both go through LeadFunnelService.
 */
class LeadFunnelCommandTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_DOCS = ['reco_information_guide', 'id_client_verification'];

    private const CONTACTED_DOCS = ['buyer_representation_agreement'];

    private function buyer(): Lead
    {
        return Lead::factory()->create(['type' => 'buyer', 'property_type' => 'condo']);
    }

    public function test_it_shows_the_current_stage_and_the_next_one(): void
    {
        $lead = $this->buyer();

        $this->artisan('lead:funnel', ['lead' => $lead->id])
            ->expectsOutputToContain('Stage: New')
            ->expectsOutputToContain('Next:  Contacted')
            ->assertSuccessful();
    }

    public function test_checking_documents_advances_the_lead(): void
    {
        $lead = $this->buyer();

        $this->artisan('lead:funnel', [
            'lead' => $lead->id,
            '--check' => [...self::NEW_DOCS, ...self::CONTACTED_DOCS],
        ])->assertSuccessful();

        $this->assertSame('contacted', $lead->fresh()->status);
    }

    public function test_unchecking_a_document_moves_the_lead_back(): void
    {
        $lead = $this->buyer();

        $this->artisan('lead:funnel', ['lead' => $lead->id, '--check' => [...self::NEW_DOCS, ...self::CONTACTED_DOCS]]);
        $this->assertSame('contacted', $lead->fresh()->status);

        $this->artisan('lead:funnel', ['lead' => $lead->id, '--uncheck' => ['id_client_verification']]);
        $this->assertSame('new', $lead->fresh()->status);
    }

    public function test_a_lost_lead_is_not_moved_by_documents(): void
    {
        $lead = $this->buyer();

        $this->artisan('lead:funnel', ['lead' => $lead->id, '--lost' => true]);
        $this->artisan('lead:funnel', ['lead' => $lead->id, '--check' => self::NEW_DOCS]);

        $this->assertSame('lost', $lead->fresh()->status);
    }

    public function test_it_rejects_an_unknown_document_key_without_changing_anything(): void
    {
        $lead = $this->buyer();

        $this->artisan('lead:funnel', ['lead' => $lead->id, '--check' => ['not_a_document']])
            ->expectsOutputToContain('Unknown document key')
            ->assertFailed();

        $this->assertSame(0, $lead->documents()->count());
    }

    public function test_it_fails_for_a_missing_lead(): void
    {
        $this->artisan('lead:funnel', ['lead' => 999999])
            ->expectsOutputToContain('not found')
            ->assertFailed();
    }

    public function test_command_and_web_form_reach_the_same_result(): void
    {
        $user = User::factory()->create();
        $viaWeb = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo']);
        $viaCli = Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo']);
        $docs = [...self::NEW_DOCS, ...self::CONTACTED_DOCS];

        $this->actingAs($user)->patch("/leads/{$viaWeb->id}", [
            'first_name' => 'Jane',
            'documents' => array_fill_keys($docs, '1'),
        ])->assertSessionHasNoErrors();

        $this->artisan('lead:funnel', ['lead' => $viaCli->id, '--check' => $docs])->assertSuccessful();

        $this->assertSame($viaWeb->fresh()->status, $viaCli->fresh()->status);
        $this->assertEquals(
            $viaWeb->documents()->orderBy('key')->pluck('checked', 'key')->all(),
            $viaCli->documents()->orderBy('key')->pluck('checked', 'key')->all(),
        );
    }
}

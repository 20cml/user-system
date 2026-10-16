<?php

namespace Tests\Feature\Funnel;

use App\Funnel\LeadFunnelService;
use App\Funnel\Pipelines\BuyerPipeline;
use App\Funnel\Pipelines\LandlordPipeline;
use App\Funnel\Pipelines\PipelineResolver;
use App\Funnel\Pipelines\SellerPipeline;
use App\Funnel\Specifications\DocumentsCompleteSpecification;
use App\Funnel\Specifications\DocumentsCompleteThroughStageSpecification;
use App\Funnel\Specifications\HasExactlyOneListingSpecification;
use App\Funnel\Stages\LostStage;
use App\Funnel\Stages\UnderContractStage;
use App\Models\Lead;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Direct tests of the three patterns behind the funnel: Strategy (pipelines),
 * State (stages) and Specification (rules).
 */
class FunnelPatternsTest extends TestCase
{
    use RefreshDatabase;

    private function buyer(array $attributes = []): Lead
    {
        return Lead::factory()->create(array_merge([
            'type' => 'buyer',
            'property_type' => 'condo',
        ], $attributes));
    }

    private function check(Lead $lead, string $stage): void
    {
        foreach ($lead->documentChecklistForStage($stage) as $key => $_) {
            $lead->documents()->updateOrCreate(['key' => $key], ['checked' => true]);
        }

        $lead->unsetRelation('documents');
    }

    // ---- Strategy -------------------------------------------------------

    public function test_resolver_picks_the_pipeline_for_each_lead_type(): void
    {
        $resolver = new PipelineResolver;

        $this->assertInstanceOf(BuyerPipeline::class, $resolver->forType('buyer'));
        $this->assertInstanceOf(SellerPipeline::class, $resolver->forType('seller'));
        $this->assertInstanceOf(LandlordPipeline::class, $resolver->forType('landlord'));
        $this->assertNull($resolver->forType('unknown'));
        $this->assertNull($resolver->forType(null));
    }

    public function test_each_type_has_its_own_stages_and_listing_gate(): void
    {
        $resolver = new PipelineResolver;

        $this->assertSame(
            ['new', 'contacted', 'qualified', 'property_search', 'showing', 'offer', 'under_contract', 'closed'],
            $resolver->forType('buyer')->stageKeys(),
        );
        $this->assertSame(
            ['new', 'contacted', 'listed', 'application_received', 'lease', 'rented'],
            $resolver->forType('landlord')->stageKeys(),
        );
        $this->assertSame('under_contract', $resolver->forType('buyer')->narrowingStageKey());
        $this->assertNull($resolver->forType('seller')->narrowingStageKey());
    }

    // ---- State ----------------------------------------------------------

    public function test_a_stage_knows_the_next_stage_in_its_pipeline(): void
    {
        $buyer = (new PipelineResolver)->forType('buyer');

        $this->assertSame('contacted', $buyer->findStage('new')->next($buyer)->key());
        $this->assertSame('closed', $buyer->findStage('under_contract')->next($buyer)->key());
        $this->assertNull($buyer->findStage('closed')->next($buyer));
    }

    public function test_the_current_stage_is_resolved_from_status_and_lost_is_terminal(): void
    {
        $service = app(LeadFunnelService::class);

        $this->assertInstanceOf(UnderContractStage::class, $service->stageFor($this->buyer(['status' => 'under_contract'])));

        $lost = $service->stageFor($this->buyer(['status' => 'lost']));
        $this->assertInstanceOf(LostStage::class, $lost);
        $this->assertFalse($lost->allowsAutomaticAdvance());
    }

    public function test_a_lost_lead_stays_lost_whatever_its_documents_say(): void
    {
        $lead = $this->buyer(['status' => 'lost']);
        $this->check($lead, 'new');

        $lead->advanceStatusFromDocuments();

        $this->assertSame('lost', $lead->fresh()->status);
    }

    // ---- Specification --------------------------------------------------

    public function test_documents_complete_specification_follows_the_checklist(): void
    {
        $lead = $this->buyer();
        $spec = new DocumentsCompleteSpecification('new');

        $this->assertFalse($spec->isSatisfiedBy($lead));

        $this->check($lead, 'new');

        $this->assertTrue($spec->isSatisfiedBy($lead));
    }

    public function test_a_stage_without_documents_is_satisfied_automatically(): void
    {
        $this->assertTrue((new DocumentsCompleteSpecification('showing'))->isSatisfiedBy($this->buyer()));
    }

    public function test_through_stage_specification_needs_every_earlier_stage(): void
    {
        $lead = $this->buyer();
        $spec = new DocumentsCompleteThroughStageSpecification('contacted');

        $this->check($lead, 'contacted');
        $this->assertFalse($spec->isSatisfiedBy($lead), "'new' is still incomplete");

        $this->check($lead, 'new');
        $this->assertTrue($spec->isSatisfiedBy($lead));
    }

    public function test_specifications_combine_with_and_and_not(): void
    {
        $lead = $this->buyer();
        $oneListing = new HasExactlyOneListingSpecification;
        $docsNew = new DocumentsCompleteSpecification('new');

        $this->assertFalse($oneListing->isSatisfiedBy($lead));
        $this->assertTrue($oneListing->not()->isSatisfiedBy($lead));

        $lead->listings()->attach(Listing::factory()->create(['user_id' => $lead->user_id]));
        $this->check($lead, 'new');

        $both = $oneListing->and($docsNew);
        $this->assertTrue($both->isSatisfiedBy($lead->fresh()));
    }

    // ---- The funnel service uses them together ---------------------------

    public function test_buyer_advances_through_stages_and_waits_at_the_listing_gate(): void
    {
        $lead = $this->buyer();

        foreach (['new', 'contacted', 'qualified', 'property_search', 'showing', 'offer', 'under_contract'] as $stage) {
            $this->check($lead, $stage);
        }

        $lead->advanceStatusFromDocuments();
        $this->assertSame('offer', $lead->fresh()->status, 'no listing linked yet, so it stops before under_contract');
        $this->assertTrue($lead->fresh()->needsListingNarrowed());

        $lead->listings()->attach(Listing::factory()->create(['user_id' => $lead->user_id]));
        $lead->unsetRelation('documents');
        $lead->advanceStatusFromDocuments();

        $this->assertSame('under_contract', $lead->fresh()->status);
    }
}

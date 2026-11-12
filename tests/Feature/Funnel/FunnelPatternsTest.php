<?php

namespace Tests\Feature\Funnel;

use App\Funnel\LeadFunnelService;
use App\Funnel\Pipelines\ConfiguredPipeline;
use App\Funnel\Pipelines\PipelineResolver;
use App\Funnel\Specifications\DocumentsCompleteSpecification;
use App\Funnel\Stages\ConfiguredStage;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsDefaultFunnel;
use Tests\TestCase;

/**
 * Direct tests of the three patterns behind the funnel: Strategy (pipelines),
 * State (stages) and Specification (rules). The agent's columns and documents
 * come from the database; SeedsDefaultFunnel stands in for an agent who set
 * up a buyer/seller/renter/landlord funnel.
 */
class FunnelPatternsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsDefaultFunnel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSeedsDefaultFunnel();
    }

    private function buyer(array $attributes = []): Lead
    {
        return Lead::factory()->create(array_merge([
            'type' => 'buyer',
            'property_type' => 'condo',
            'status' => 'new',
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

    public function test_resolver_picks_the_pipeline_of_the_agent_and_lead_type(): void
    {
        $lead = $this->buyer();
        $resolver = new PipelineResolver;

        $this->assertInstanceOf(ConfiguredPipeline::class, $resolver->forType($lead->user_id, 'buyer', 'condo'));
        $this->assertSame('seller', $resolver->forType($lead->user_id, 'seller', 'house')->type());
        $this->assertNull($resolver->forType($lead->user_id, 'unknown', 'condo'));
        $this->assertNull($resolver->forType($lead->user_id, null, 'condo'));
        $this->assertNull($resolver->forType(null, 'buyer', 'condo'));
        $this->assertNull($resolver->forType($lead->user_id, 'buyer', null), 'no property type, no funnel');
        $this->assertNull($resolver->forType($lead->user_id, 'buyer', 'castle'));
    }

    public function test_each_type_has_its_own_stages(): void
    {
        $userId = $this->buyer()->user_id;
        $resolver = new PipelineResolver;

        $this->assertSame(
            ['new', 'contacted', 'qualified', 'property_search', 'showing', 'offer', 'under_contract', 'closed'],
            $resolver->forType($userId, 'buyer', 'condo')->stageKeys(),
        );
        $this->assertSame(
            ['new', 'contacted', 'listed', 'application_received', 'lease', 'rented'],
            $resolver->forType($userId, 'landlord', 'house')->stageKeys(),
        );
    }

    public function test_a_pipeline_with_no_columns_has_no_stages_and_no_checklist(): void
    {
        $nothingSetUp = new ConfiguredPipeline(999999, 'buyer', 'condo');

        $this->assertSame([], $nothingSetUp->stages());
        $this->assertFalse($nothingSetUp->hasChecklist());
    }

    // ---- State ----------------------------------------------------------

    public function test_a_stage_knows_the_next_stage_in_its_pipeline(): void
    {
        $buyer = (new PipelineResolver)->forType($this->buyer()->user_id, 'buyer', 'condo');

        $this->assertSame('contacted', $buyer->findStage('new')->next($buyer)->key());
        $this->assertSame('closed', $buyer->findStage('under_contract')->next($buyer)->key());
        $this->assertNull($buyer->findStage('closed')->next($buyer));
    }

    public function test_the_current_stage_is_resolved_from_status(): void
    {
        $service = app(LeadFunnelService::class);

        $stage = $service->stageFor($this->buyer(['status' => 'under_contract']));
        $this->assertInstanceOf(ConfiguredStage::class, $stage);
        $this->assertSame('Under Contract', $stage->label());

        $this->assertNull($service->stageFor($this->buyer(['status' => 'a_column_that_was_removed'])));
        $this->assertNull($service->stageFor($this->buyer(['status' => null])));
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

    public function test_specifications_combine_with_and_and_not(): void
    {
        $lead = $this->buyer();
        $docsNew = new DocumentsCompleteSpecification('new');
        $docsContacted = new DocumentsCompleteSpecification('contacted');

        $this->check($lead, 'new');

        $this->assertTrue($docsNew->isSatisfiedBy($lead));
        $this->assertFalse($docsNew->and($docsContacted)->isSatisfiedBy($lead));
        $this->assertTrue($docsNew->and($docsContacted->not())->isSatisfiedBy($lead));
    }

    // ---- The funnel service uses them together ---------------------------

    public function test_buyer_advances_through_every_column_once_all_documents_are_checked(): void
    {
        $lead = $this->buyer();

        foreach (['new', 'contacted', 'qualified', 'property_search', 'showing', 'offer', 'under_contract'] as $stage) {
            $this->check($lead, $stage);
        }

        $lead->advanceStatusFromDocuments();

        $this->assertSame('under_contract', $lead->fresh()->status);

        $this->check($lead, 'closed');
        $lead->advanceStatusFromDocuments();

        $this->assertSame('closed', $lead->fresh()->status);
    }

    public function test_a_lead_with_no_columns_has_no_status(): void
    {
        $lead = Lead::factory()->create(['type' => 'buyer', 'status' => 'new']);
        \App\Models\PipelineStage::where('user_id', $lead->user_id)->delete();
        app(PipelineResolver::class)->flush();

        $lead->advanceStatusFromDocuments();

        $this->assertNull($lead->fresh()->status);
        $this->assertNull($lead->fresh()->statusLabel());
    }
}

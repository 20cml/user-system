<?php

namespace App\Funnel;

use App\Funnel\Pipelines\PipelineResolver;
use App\Funnel\Pipelines\PipelineStrategy;
use App\Funnel\Specifications\DocumentsCompleteSpecification;
use App\Funnel\Specifications\DocumentsCompleteThroughStageSpecification;
use App\Funnel\Specifications\HasExactlyOneListingSpecification;
use App\Funnel\Specifications\NoCompetingContractSpecification;
use App\Funnel\Stages\LostStage;
use App\Funnel\Stages\Stage;
use App\Models\Lead;

/**
 * The single place the funnel rules live. Every entry point — the web
 * controllers, the REST API, the Artisan command — calls this class rather
 * than re-implementing the rules, so all interfaces behave identically.
 */
class LeadFunnelService
{
    public function __construct(private PipelineResolver $pipelines) {}

    public function pipelineFor(Lead $lead): ?PipelineStrategy
    {
        return $this->pipelines->forType($lead->type);
    }

    /**
     * The lead's current stage object, or null if its status isn't a stage
     * of its pipeline. Lost is a stage of its own, outside every pipeline.
     */
    public function stageFor(Lead $lead): ?Stage
    {
        if ($lead->status === 'lost') {
            return new LostStage;
        }

        return $this->pipelineFor($lead)?->findStage((string) $lead->status);
    }

    public function hasDocumentChecklist(Lead $lead): bool
    {
        return (bool) $this->pipelineFor($lead)?->hasChecklist($lead->property_type);
    }

    /**
     * The lead's document checklist for one stage, each key merged with
     * whether it's currently checked.
     */
    public function documentChecklistForStage(Lead $lead, string $stage): array
    {
        $checked = $lead->documents->pluck('checked', 'key');
        $required = $this->pipelineFor($lead)?->documentsFor($lead->property_type, $stage) ?? [];

        return collect($required)
            ->map(fn ($label, $key) => [
                'label' => $label,
                'checked' => (bool) ($checked[$key] ?? false),
            ])
            ->all();
    }

    /**
     * Whether the lead is ready to advance past its pipeline's listing-
     * narrowing stage on its documents alone, but is held back because it
     * isn't linked to exactly one listing yet.
     */
    public function needsListingNarrowed(Lead $lead): bool
    {
        $gateStage = $this->pipelineFor($lead)?->narrowingStageKey();

        if (! $gateStage || ! $this->hasDocumentChecklist($lead)) {
            return false;
        }

        return (new DocumentsCompleteThroughStageSpecification($gateStage))
            ->and((new HasExactlyOneListingSpecification)->not())
            ->isSatisfiedBy($lead);
    }

    /**
     * The other lead holding this lead's single listing under contract, which
     * is why this lead is held out of 'under_contract'. Reads the
     * `held_under_contract` flag rather than the raw checkboxes, because
     * advance() clears the 'under_contract' checklist the moment it detects
     * the conflict.
     */
    public function blockingUnderContractLead(Lead $lead): ?Lead
    {
        if (! $this->hasDocumentChecklist($lead) || $lead->listings()->count() !== 1 || ! $lead->held_under_contract) {
            return null;
        }

        return $lead->conflictingUnderContractLead();
    }

    /**
     * The stage the lead would land on once its listing gets narrowed to one
     * (documents alone), as a display label — null when it isn't waiting on
     * that.
     */
    public function targetStageLabelOnceListingNarrowed(Lead $lead): ?string
    {
        if (! $this->needsListingNarrowed($lead)) {
            return null;
        }

        $stage = $this->furthestCompleteStage($lead, ignoreListingGate: true);

        return $this->pipelineFor($lead)?->labels()[$stage] ?? null;
    }

    /**
     * Walks the lead's pipeline from the first stage, returning the furthest
     * one whose completion requirement holds — stopping at the first stage
     * that isn't complete, so a later stage being complete doesn't count if
     * an earlier one isn't. With $ignoreListingGate only the documents are
     * considered, to report where the documents alone would take it.
     */
    public function furthestCompleteStage(Lead $lead, bool $ignoreListingGate = false): string
    {
        $pipeline = $this->pipelineFor($lead);
        $stages = $pipeline->stages();
        $furthest = $stages[0]->key();

        foreach ($stages as $stage) {
            $requirement = $ignoreListingGate
                ? new DocumentsCompleteSpecification($stage->key())
                : $stage->completionRequirement($pipeline);

            if (! $requirement->isSatisfiedBy($lead)) {
                break;
            }

            $furthest = $stage->key();
        }

        return $furthest;
    }

    /**
     * Save a submitted set of document checkboxes and re-run the funnel.
     * Every document this lead's type × property type can have is set from
     * $submitted — absent or false means unchecked — and the status is then
     * recomputed. This is the one entry point both the web form and the
     * Artisan command use to change a lead's documents.
     *
     * @param  array<string, mixed>  $submitted  document key => truthy when checked
     */
    public function syncDocuments(Lead $lead, array $submitted): void
    {
        $keys = $this->pipelineFor($lead)?->documentKeys($lead->property_type) ?? [];

        foreach ($keys as $key) {
            $lead->documents()->updateOrCreate(
                ['key' => $key],
                ['checked' => (bool) ($submitted[$key] ?? false)],
            );
        }

        $lead->unsetRelation('documents');

        $this->advance($lead);
    }

    /**
     * A plain-array picture of where the lead stands in its funnel — the
     * current stage, what comes next, what is blocking it, and every
     * stage's checklist. Read by the Artisan command; shaped so a JSON
     * endpoint could return it unchanged.
     *
     * @return array<string, mixed>
     */
    public function summary(Lead $lead): array
    {
        $pipeline = $this->pipelineFor($lead);
        $current = $this->stageFor($lead);

        $summary = [
            'id' => $lead->id,
            'name' => $lead->name,
            'type' => $lead->type,
            'property_type' => $lead->property_type,
            'status' => $lead->status,
            'stage' => $current?->label() ?? $lead->status,
            'next_stage' => ($pipeline && $current) ? $current->next($pipeline)?->label() : null,
            'needs_listing_narrowed' => $this->needsListingNarrowed($lead),
            'blocked_by_lead_id' => $this->blockingUnderContractLead($lead)?->id,
            'stages' => [],
        ];

        foreach ($pipeline?->stages() ?? [] as $stage) {
            $documents = $this->documentChecklistForStage($lead, $stage->key());

            $summary['stages'][] = [
                'key' => $stage->key(),
                'label' => $stage->label(),
                'current' => $stage->key() === $lead->status,
                'complete' => (new DocumentsCompleteSpecification($stage->key()))->isSatisfiedBy($lead),
                'documents' => collect($documents)
                    ->map(fn ($doc, $key) => ['key' => $key] + $doc)
                    ->values()
                    ->all(),
            ];
        }

        return $summary;
    }

    /**
     * Recompute the lead's status from its document checklist and save it.
     * Moves the status up or down to match, so unchecking a document can
     * send it back a stage. No-ops for a Lost lead, a type with no pipeline,
     * or a type × property type with no checklist.
     */
    public function advance(Lead $lead): void
    {
        $pipeline = $this->pipelineFor($lead);
        $current = $this->stageFor($lead);

        if (! $pipeline || ($current && ! $current->allowsAutomaticAdvance()) || ! $this->hasDocumentChecklist($lead)) {
            return;
        }

        $lead->status = $this->furthestCompleteStage($lead);

        // Snapshot whether the lead is fully ready for 'under_contract' but
        // blocked by another lead holding the listing — read before the
        // checklist reset below, and kept in its own column so the warning
        // keeps showing on later visits after those checkboxes are cleared.
        $blocked = (new HasExactlyOneListingSpecification)
            ->and(new DocumentsCompleteThroughStageSpecification('under_contract'))
            ->and((new NoCompetingContractSpecification)->not())
            ->isSatisfiedBy($lead);

        $lead->held_under_contract = $blocked;
        $lead->save();

        if ($blocked) {
            $lead->documents()
                ->whereIn('key', array_keys($pipeline->documentsFor($lead->property_type, 'under_contract')))
                ->update(['checked' => false]);
        }

        $lead->listings()->get()->each->syncStatusFromLeads();
    }
}

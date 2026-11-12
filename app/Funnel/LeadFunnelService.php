<?php

namespace App\Funnel;

use App\Funnel\Pipelines\PipelineResolver;
use App\Funnel\Pipelines\PipelineStrategy;
use App\Funnel\Specifications\DocumentsCompleteSpecification;
use App\Funnel\Stages\Stage;
use App\Models\Lead;

/**
 * The single place the funnel rules live. Every entry point — the web
 * controllers, the Artisan command — calls this class rather than
 * re-implementing the rules, so all interfaces behave identically. The
 * columns and documents themselves are the agent's settings, read through
 * the pipeline; this class has none built in.
 */
class LeadFunnelService
{
    public function __construct(private PipelineResolver $pipelines) {}

    public function pipelineFor(Lead $lead): ?PipelineStrategy
    {
        return $this->pipelines->forType($lead->user_id, $lead->type, $lead->property_type);
    }

    /**
     * The lead's current stage object, or null if its status isn't a column
     * of its pipeline (no column yet, or the column was removed).
     */
    public function stageFor(Lead $lead): ?Stage
    {
        return $this->pipelineFor($lead)?->findStage((string) $lead->status);
    }

    /**
     * The status a brand-new lead starts with: the first column of its
     * pipeline, or null when it has none (no type or property type yet, or no
     * columns set up for them).
     */
    public function initialStatus(int $userId, ?string $type, ?string $propertyType): ?string
    {
        return $this->pipelines->forType($userId, $type, $propertyType)?->stageKeys()[0] ?? null;
    }

    public function hasDocumentChecklist(Lead $lead): bool
    {
        return (bool) $this->pipelineFor($lead)?->hasChecklist();
    }

    /**
     * The lead's document checklist for one stage, each key merged with
     * whether it's currently checked.
     */
    public function documentChecklistForStage(Lead $lead, string $stage): array
    {
        $checked = $lead->documents->pluck('checked', 'key');
        $required = $this->pipelineFor($lead)?->documentsFor($stage) ?? [];

        return collect($required)
            ->map(fn ($label, $key) => [
                'label' => $label,
                'checked' => (bool) ($checked[$key] ?? false),
            ])
            ->all();
    }

    /**
     * Walks the lead's pipeline from the first column, returning the furthest
     * one whose completion requirement holds — stopping at the first column
     * that isn't complete, so a later column being complete doesn't count if
     * an earlier one isn't. Null when the agent has no columns.
     */
    public function furthestCompleteStage(Lead $lead): ?string
    {
        $pipeline = $this->pipelineFor($lead);
        $stages = $pipeline?->stages() ?? [];
        $furthest = isset($stages[0]) ? $stages[0]->key() : null;

        foreach ($stages as $stage) {
            if (! $stage->completionRequirement($pipeline)->isSatisfiedBy($lead)) {
                break;
            }

            $furthest = $stage->key();
        }

        return $furthest;
    }

    /**
     * Save a submitted set of document checkboxes and re-run the funnel.
     * Every document this lead's funnel can have is set from
     * $submitted — absent or false means unchecked — and the status is then
     * recomputed. This is the one entry point both the web form and the
     * Artisan command use to change a lead's documents.
     *
     * @param  array<string, mixed>  $submitted  document key => truthy when checked
     */
    public function syncDocuments(Lead $lead, array $submitted): void
    {
        $keys = $this->pipelineFor($lead)?->documentKeys() ?? [];

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
     * current column, what comes next, and every column's checklist. Read by
     * the Artisan command; shaped so a JSON endpoint could return it
     * unchanged.
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
            'stage' => $current?->label(),
            'next_stage' => ($pipeline && $current) ? $current->next($pipeline)?->label() : null,
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
     * Recompute the lead's column from its document checklist and save it.
     * Moves up or down to match, so unchecking a document can send it back a
     * column. With no column set up (or no property type yet) the status is
     * empty; with columns but no documents it keeps its column (or takes the
     * first one if it has none).
     */
    public function advance(Lead $lead): void
    {
        $pipeline = $this->pipelineFor($lead);

        if (! $pipeline || $pipeline->stages() === []) {
            $lead->status = null;
        } elseif ($this->hasDocumentChecklist($lead)) {
            $lead->status = $this->furthestCompleteStage($lead);
        } elseif (! $this->stageFor($lead)) {
            $lead->status = $pipeline->stageKeys()[0];
        }

        $lead->save();
    }
}

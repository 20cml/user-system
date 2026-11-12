<?php

namespace App\Funnel\Pipelines;

use App\Funnel\Stages\Stage;

/**
 * STRATEGY pattern: each lead type (buyer, seller, renter, landlord) and
 * property type follows its own pipeline. The funnel picks the right one for
 * the agent and the lead, so the funnel code never knows which columns or documents
 * exist — those are the agent's settings.
 */
interface PipelineStrategy
{
    public function type(): string;

    /**
     * @return list<Stage> the stages of this pipeline, in order
     */
    public function stages(): array;

    /**
     * @return list<string>
     */
    public function stageKeys(): array;

    /**
     * @return array<string, string> stage key => display label
     */
    public function labels(): array;

    public function findStage(string $key): ?Stage;

    public function stageAfter(string $key): ?Stage;

    /**
     * Whether any document is required at all.
     */
    public function hasChecklist(): bool;

    /**
     * @return array<string, string> document key => label required at $stageKey
     */
    public function documentsFor(string $stageKey): array;

    /**
     * @return list<string> every document key across all stages
     */
    public function documentKeys(): array;
}

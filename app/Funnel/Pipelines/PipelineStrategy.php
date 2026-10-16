<?php

namespace App\Funnel\Pipelines;

use App\Funnel\Stages\Stage;

/**
 * STRATEGY pattern: each lead type (buyer, seller, renter, landlord) follows
 * its own pipeline. The funnel picks the right one from the lead's type, so
 * adding a new lead type is one new class and no change to existing ones.
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
     * The stage at which a lead must be narrowed to exactly one linked
     * listing, or null if this pipeline has no such gate.
     */
    public function narrowingStageKey(): ?string;

    /**
     * Whether any document checklist is defined for this property type.
     */
    public function hasChecklist(?string $propertyType): bool;

    /**
     * @return array<string, string> document key => label required at $stageKey
     */
    public function documentsFor(?string $propertyType, string $stageKey): array;

    /**
     * @return list<string> every document key across all stages
     */
    public function documentKeys(?string $propertyType): array;
}

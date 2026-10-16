<?php

namespace App\Funnel\Stages;

use App\Funnel\Pipelines\PipelineStrategy;
use App\Funnel\Specifications\DocumentsCompleteSpecification;
use App\Funnel\Specifications\HasExactlyOneListingSpecification;
use App\Funnel\Specifications\Specification;

abstract class AbstractStage implements Stage
{
    public function next(PipelineStrategy $pipeline): ?Stage
    {
        return $pipeline->stageAfter($this->key());
    }

    /**
     * By default a stage is complete once its own documents are all checked.
     * The pipeline's listing-narrowing stage additionally needs exactly one
     * linked listing.
     */
    public function completionRequirement(PipelineStrategy $pipeline): Specification
    {
        $requirement = new DocumentsCompleteSpecification($this->key());

        if ($pipeline->narrowingStageKey() === $this->key()) {
            $requirement = $requirement->and(new HasExactlyOneListingSpecification);
        }

        return $requirement;
    }

    public function allowsAutomaticAdvance(): bool
    {
        return true;
    }
}

<?php

namespace App\Funnel\Stages;

use App\Funnel\Pipelines\PipelineStrategy;
use App\Funnel\Specifications\DocumentsCompleteSpecification;
use App\Funnel\Specifications\Specification;

/**
 * A column the agent set up in Settings. Every column behaves the same way:
 * it is complete once its own documents are all checked (a column with no
 * documents is complete automatically).
 */
class ConfiguredStage implements Stage
{
    public function __construct(private string $key, private string $label) {}

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function next(PipelineStrategy $pipeline): ?Stage
    {
        return $pipeline->stageAfter($this->key);
    }

    public function completionRequirement(PipelineStrategy $pipeline): Specification
    {
        return new DocumentsCompleteSpecification($this->key);
    }
}

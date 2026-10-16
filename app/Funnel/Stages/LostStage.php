<?php

namespace App\Funnel\Stages;

use App\Funnel\Pipelines\PipelineStrategy;
use App\Funnel\Specifications\NeverSpecification;
use App\Funnel\Specifications\Specification;

/**
 * Terminal state outside every pipeline. A Lost lead stays Lost: automation
 * never moves it, only the agent can.
 */
class LostStage implements Stage
{
    public function key(): string
    {
        return 'lost';
    }

    public function label(): string
    {
        return 'Lost';
    }

    public function next(PipelineStrategy $pipeline): ?Stage
    {
        return null;
    }

    public function completionRequirement(PipelineStrategy $pipeline): Specification
    {
        return new NeverSpecification;
    }

    public function allowsAutomaticAdvance(): bool
    {
        return false;
    }
}

<?php

namespace App\Funnel\Stages;

use App\Funnel\Pipelines\PipelineStrategy;
use App\Funnel\Specifications\Specification;

/**
 * STATE pattern: one object per funnel stage. A stage knows its own key and
 * label, which stage follows it in a given pipeline, and what has to be true
 * of a lead before it counts as having completed this stage — so no caller
 * needs a status-checking if/else.
 */
interface Stage
{
    public function key(): string;

    public function label(): string;

    /**
     * The stage after this one in $pipeline, or null at the end.
     */
    public function next(PipelineStrategy $pipeline): ?Stage;

    /**
     * What must hold for a lead to count as having completed this stage.
     */
    public function completionRequirement(PipelineStrategy $pipeline): Specification;

    /**
     * Whether document/listing changes may move a lead out of this stage on
     * their own. False for Lost: only the agent can take a lead out of it.
     */
    public function allowsAutomaticAdvance(): bool;
}

<?php

namespace App\Funnel\Stages;

use App\Funnel\Pipelines\PipelineStrategy;
use App\Funnel\Specifications\NoCompetingContractSpecification;
use App\Funnel\Specifications\Specification;

/**
 * The "Under Contract" stage. A listing can only be under contract with one
 * lead at a time, so on top of the usual requirement a second lead has to
 * wait until no other lead already holds the same listing.
 */
class UnderContractStage extends AbstractStage
{
    public function key(): string
    {
        return 'under_contract';
    }

    public function label(): string
    {
        return 'Under Contract';
    }

    public function completionRequirement(PipelineStrategy $pipeline): Specification
    {
        return parent::completionRequirement($pipeline)->and(new NoCompetingContractSpecification);
    }
}

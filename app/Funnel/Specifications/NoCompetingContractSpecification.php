<?php

namespace App\Funnel\Specifications;

use App\Models\Lead;

/**
 * Fails only when the lead is narrowed to one listing that another lead
 * already holds under contract. Not yet narrowed to one listing is a
 * different problem (see HasExactlyOneListingSpecification), so it passes.
 */
class NoCompetingContractSpecification extends AbstractSpecification
{
    public function isSatisfiedBy(Lead $lead): bool
    {
        if (! (new HasExactlyOneListingSpecification)->isSatisfiedBy($lead)) {
            return true;
        }

        return $lead->conflictingUnderContractLead() === null;
    }
}

<?php

namespace App\Funnel\Specifications;

use App\Models\Lead;

class HasExactlyOneListingSpecification extends AbstractSpecification
{
    public function isSatisfiedBy(Lead $lead): bool
    {
        return $lead->listings()->count() === 1;
    }
}

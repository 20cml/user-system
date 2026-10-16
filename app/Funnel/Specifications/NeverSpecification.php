<?php

namespace App\Funnel\Specifications;

use App\Models\Lead;

class NeverSpecification extends AbstractSpecification
{
    public function isSatisfiedBy(Lead $lead): bool
    {
        return false;
    }
}

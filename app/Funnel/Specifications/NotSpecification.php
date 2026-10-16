<?php

namespace App\Funnel\Specifications;

use App\Models\Lead;

class NotSpecification extends AbstractSpecification
{
    public function __construct(private Specification $inner) {}

    public function isSatisfiedBy(Lead $lead): bool
    {
        return ! $this->inner->isSatisfiedBy($lead);
    }
}

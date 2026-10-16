<?php

namespace App\Funnel\Specifications;

use App\Models\Lead;

class AndSpecification extends AbstractSpecification
{
    public function __construct(
        private Specification $left,
        private Specification $right,
    ) {}

    public function isSatisfiedBy(Lead $lead): bool
    {
        // Left first and short-circuiting: later rules are only evaluated
        // (and their queries only run) once the earlier ones hold.
        return $this->left->isSatisfiedBy($lead) && $this->right->isSatisfiedBy($lead);
    }
}

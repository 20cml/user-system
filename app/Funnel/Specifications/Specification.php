<?php

namespace App\Funnel\Specifications;

use App\Models\Lead;

/**
 * SPECIFICATION pattern: one small, named business rule about a lead
 * ("has exactly one listing", "documents complete through stage X"). Rules
 * combine with and()/not() and are reused by the funnel and by the views.
 */
interface Specification
{
    public function isSatisfiedBy(Lead $lead): bool;

    public function and(Specification $other): Specification;

    public function not(): Specification;
}

<?php

namespace App\Funnel\Specifications;

use App\Models\Lead;

/**
 * Every stage from the first one up to and including $stageKey has a
 * fully-checked document checklist.
 */
class DocumentsCompleteThroughStageSpecification extends AbstractSpecification
{
    public function __construct(private string $stageKey) {}

    public function isSatisfiedBy(Lead $lead): bool
    {
        foreach ($lead->stageKeys() as $key) {
            if (! (new DocumentsCompleteSpecification($key))->isSatisfiedBy($lead)) {
                return false;
            }

            if ($key === $this->stageKey) {
                return true;
            }
        }

        return false;
    }
}

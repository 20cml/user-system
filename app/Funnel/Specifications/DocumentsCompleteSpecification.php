<?php

namespace App\Funnel\Specifications;

use App\Models\Lead;

/**
 * Every document required at one stage is checked. A stage with no documents
 * (like buyer's 'showing') is satisfied automatically.
 */
class DocumentsCompleteSpecification extends AbstractSpecification
{
    public function __construct(private string $stageKey) {}

    public function isSatisfiedBy(Lead $lead): bool
    {
        return ! collect($lead->documentChecklistForStage($this->stageKey))
            ->contains(fn ($document) => ! $document['checked']);
    }
}

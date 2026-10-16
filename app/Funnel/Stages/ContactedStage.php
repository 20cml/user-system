<?php

namespace App\Funnel\Stages;

/**
 * The "Contacted" funnel stage.
 */
class ContactedStage extends AbstractStage
{
    public function key(): string
    {
        return 'contacted';
    }

    public function label(): string
    {
        return 'Contacted';
    }
}

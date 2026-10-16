<?php

namespace App\Funnel\Stages;

/**
 * The "Lease" funnel stage.
 */
class LeaseStage extends AbstractStage
{
    public function key(): string
    {
        return 'lease';
    }

    public function label(): string
    {
        return 'Lease';
    }
}

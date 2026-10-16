<?php

namespace App\Funnel\Stages;

/**
 * The "Closed" funnel stage.
 */
class ClosedStage extends AbstractStage
{
    public function key(): string
    {
        return 'closed';
    }

    public function label(): string
    {
        return 'Closed';
    }
}

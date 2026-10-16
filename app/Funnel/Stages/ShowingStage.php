<?php

namespace App\Funnel\Stages;

/**
 * The "Showing" funnel stage.
 */
class ShowingStage extends AbstractStage
{
    public function key(): string
    {
        return 'showing';
    }

    public function label(): string
    {
        return 'Showing';
    }
}

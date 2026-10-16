<?php

namespace App\Funnel\Stages;

/**
 * The "New" funnel stage.
 */
class NewStage extends AbstractStage
{
    public function key(): string
    {
        return 'new';
    }

    public function label(): string
    {
        return 'New';
    }
}

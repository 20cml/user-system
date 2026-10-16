<?php

namespace App\Funnel\Stages;

/**
 * The "Listed" funnel stage.
 */
class ListedStage extends AbstractStage
{
    public function key(): string
    {
        return 'listed';
    }

    public function label(): string
    {
        return 'Listed';
    }
}

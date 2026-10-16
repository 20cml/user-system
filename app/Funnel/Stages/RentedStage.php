<?php

namespace App\Funnel\Stages;

/**
 * The "Rented" funnel stage.
 */
class RentedStage extends AbstractStage
{
    public function key(): string
    {
        return 'rented';
    }

    public function label(): string
    {
        return 'Rented';
    }
}

<?php

namespace App\Funnel\Stages;

/**
 * The "Offer" funnel stage.
 */
class OfferStage extends AbstractStage
{
    public function key(): string
    {
        return 'offer';
    }

    public function label(): string
    {
        return 'Offer';
    }
}

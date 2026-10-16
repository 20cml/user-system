<?php

namespace App\Funnel\Stages;

/**
 * The "Offer Received" funnel stage.
 */
class OfferReceivedStage extends AbstractStage
{
    public function key(): string
    {
        return 'offer_received';
    }

    public function label(): string
    {
        return 'Offer Received';
    }
}

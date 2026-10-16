<?php

namespace App\Funnel\Stages;

/**
 * The "Property Search" funnel stage.
 */
class PropertySearchStage extends AbstractStage
{
    public function key(): string
    {
        return 'property_search';
    }

    public function label(): string
    {
        return 'Property Search';
    }
}

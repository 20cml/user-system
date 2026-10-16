<?php

namespace App\Funnel\Stages;

/**
 * The "Application Received" funnel stage.
 */
class ApplicationReceivedStage extends AbstractStage
{
    public function key(): string
    {
        return 'application_received';
    }

    public function label(): string
    {
        return 'Application Received';
    }
}

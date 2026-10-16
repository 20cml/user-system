<?php

namespace App\Funnel\Stages;

/**
 * The "Qualified" funnel stage.
 */
class QualifiedStage extends AbstractStage
{
    public function key(): string
    {
        return 'qualified';
    }

    public function label(): string
    {
        return 'Qualified';
    }
}

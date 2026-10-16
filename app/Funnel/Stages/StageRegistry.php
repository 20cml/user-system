<?php

namespace App\Funnel\Stages;

/**
 * Looks up the Stage object for a status key. Stages are stateless, so one
 * shared instance per key is enough.
 */
class StageRegistry
{
    private const STAGES = [
        'new' => NewStage::class,
        'contacted' => ContactedStage::class,
        'qualified' => QualifiedStage::class,
        'property_search' => PropertySearchStage::class,
        'showing' => ShowingStage::class,
        'offer' => OfferStage::class,
        'under_contract' => UnderContractStage::class,
        'closed' => ClosedStage::class,
        'listed' => ListedStage::class,
        'offer_received' => OfferReceivedStage::class,
        'application_received' => ApplicationReceivedStage::class,
        'lease' => LeaseStage::class,
        'rented' => RentedStage::class,
    ];

    /** @var array<string, Stage> */
    private static array $instances = [];

    public static function find(string $key): ?Stage
    {
        if ($key === 'lost') {
            return self::$instances['lost'] ??= new LostStage;
        }

        $class = self::STAGES[$key] ?? null;

        return $class ? (self::$instances[$key] ??= new $class) : null;
    }
}

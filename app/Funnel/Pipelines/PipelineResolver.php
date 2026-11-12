<?php

namespace App\Funnel\Pipelines;

/**
 * Picks the PipelineStrategy for an agent's lead type and property type. One
 * instance is shared for the whole request (see AppServiceProvider), so each
 * pipeline is read from the database once; flush() drops them after Settings
 * change.
 */
class PipelineResolver
{
    public const LEAD_TYPES = ['buyer', 'seller', 'renter', 'landlord'];

    public const PROPERTY_TYPES = ['condo', 'house', 'land', 'commercial'];

    /** @var array<string, PipelineStrategy> */
    private array $instances = [];

    /**
     * Null when the lead has no known type or no property type yet: with
     * neither there is no funnel to follow.
     */
    public function forType(?int $userId, ?string $type, ?string $propertyType): ?PipelineStrategy
    {
        if (! $userId || ! in_array($type, self::LEAD_TYPES, true) || ! in_array($propertyType, self::PROPERTY_TYPES, true)) {
            return null;
        }

        return $this->instances["{$userId}:{$type}:{$propertyType}"] ??= new ConfiguredPipeline($userId, $type, $propertyType);
    }

    public function flush(): void
    {
        $this->instances = [];
    }
}

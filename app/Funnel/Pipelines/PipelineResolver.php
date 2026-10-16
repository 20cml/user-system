<?php

namespace App\Funnel\Pipelines;

/**
 * Picks the PipelineStrategy for a lead type.
 */
class PipelineResolver
{
    private const PIPELINES = [
        'buyer' => BuyerPipeline::class,
        'seller' => SellerPipeline::class,
        'renter' => RenterPipeline::class,
        'landlord' => LandlordPipeline::class,
    ];

    /** @var array<string, PipelineStrategy> */
    private array $instances = [];

    public function forType(?string $type): ?PipelineStrategy
    {
        $class = self::PIPELINES[$type] ?? null;

        return $class ? ($this->instances[$type] ??= new $class) : null;
    }

    /**
     * @return list<string>
     */
    public function allStageKeys(): array
    {
        return collect(array_keys(self::PIPELINES))
            ->flatMap(fn (string $type) => $this->forType($type)->stageKeys())
            ->unique()
            ->values()
            ->all();
    }
}

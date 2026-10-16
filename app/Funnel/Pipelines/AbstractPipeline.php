<?php

namespace App\Funnel\Pipelines;

use App\Funnel\Stages\Stage;
use App\Funnel\Stages\StageRegistry;

abstract class AbstractPipeline implements PipelineStrategy
{
    protected const TYPE = '';

    /** @var list<string> */
    protected const STAGES = [];

    /** @var array<string, array<string, array<string, string>>> */
    protected const DOCUMENTS = [];

    protected const NARROWING_STAGE = null;

    public function type(): string
    {
        return static::TYPE;
    }

    public function stages(): array
    {
        return array_map(fn (string $key) => StageRegistry::find($key), static::STAGES);
    }

    public function stageKeys(): array
    {
        return static::STAGES;
    }

    public function labels(): array
    {
        $labels = [];

        foreach ($this->stages() as $stage) {
            $labels[$stage->key()] = $stage->label();
        }

        return $labels;
    }

    public function findStage(string $key): ?Stage
    {
        return in_array($key, static::STAGES, true) ? StageRegistry::find($key) : null;
    }

    public function stageAfter(string $key): ?Stage
    {
        $position = array_search($key, static::STAGES, true);

        if ($position === false || ! isset(static::STAGES[$position + 1])) {
            return null;
        }

        return StageRegistry::find(static::STAGES[$position + 1]);
    }

    public function narrowingStageKey(): ?string
    {
        return static::NARROWING_STAGE;
    }

    public function hasChecklist(?string $propertyType): bool
    {
        return $propertyType !== null && isset(static::DOCUMENTS[$propertyType]);
    }

    public function documentsFor(?string $propertyType, string $stageKey): array
    {
        if ($propertyType === null) {
            return [];
        }

        return static::DOCUMENTS[$propertyType][$stageKey] ?? [];
    }

    public function documentKeys(?string $propertyType): array
    {
        if ($propertyType === null) {
            return [];
        }

        return collect(static::DOCUMENTS[$propertyType] ?? [])
            ->flatMap(fn ($docs) => array_keys($docs))
            ->unique()
            ->values()
            ->all();
    }
}

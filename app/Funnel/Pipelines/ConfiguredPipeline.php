<?php

namespace App\Funnel\Pipelines;

use App\Funnel\Stages\ConfiguredStage;
use App\Funnel\Stages\Stage;
use App\Models\PipelineStage;

/**
 * The pipeline one agent set up in Settings for one lead type and one
 * property type: their columns, in order, and the documents each column asks
 * for. Nothing is built into the code. Rows are read once, on first use.
 */
class ConfiguredPipeline implements PipelineStrategy
{
    /** @var list<Stage>|null */
    private ?array $stages = null;

    /** @var array<string, array<string, string>> stage key => [doc key => label] */
    private array $documents = [];

    public function __construct(private int $userId, private string $type, private string $propertyType) {}

    public function type(): string
    {
        return $this->type;
    }

    public function stages(): array
    {
        $this->load();

        return $this->stages;
    }

    public function stageKeys(): array
    {
        return array_map(fn (Stage $stage) => $stage->key(), $this->stages());
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
        foreach ($this->stages() as $stage) {
            if ($stage->key() === $key) {
                return $stage;
            }
        }

        return null;
    }

    public function stageAfter(string $key): ?Stage
    {
        $stages = $this->stages();

        foreach ($stages as $position => $stage) {
            if ($stage->key() === $key) {
                return $stages[$position + 1] ?? null;
            }
        }

        return null;
    }

    public function hasChecklist(): bool
    {
        return $this->documentKeys() !== [];
    }

    public function documentsFor(string $stageKey): array
    {
        $this->load();

        return $this->documents[$stageKey] ?? [];
    }

    public function documentKeys(): array
    {
        $this->load();

        return collect($this->documents)
            ->flatMap(fn (array $documents) => array_keys($documents))
            ->unique()
            ->values()
            ->all();
    }

    private function load(): void
    {
        if ($this->stages !== null) {
            return;
        }

        $this->stages = [];

        $rows = PipelineStage::query()
            ->where('user_id', $this->userId)
            ->where('lead_type', $this->type)
            ->where('property_type', $this->propertyType)
            ->orderBy('position')->orderBy('id')
            ->with('documents')
            ->get();

        foreach ($rows as $row) {
            $this->stages[] = new ConfiguredStage($row->key, $row->label);

            foreach ($row->documents as $document) {
                $this->documents[$row->key][$document->key] = $document->label;
            }
        }
    }
}

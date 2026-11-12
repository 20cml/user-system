<?php

namespace App\Funnel;

use App\Funnel\Pipelines\PipelineResolver;
use App\Models\PipelineStage;
use App\Models\StageDocument;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reads and writes what an agent chose in Settings: the columns of the
 * funnel of each lead type × property type, and the documents each column
 * asks for. After every save the affected leads are re-run through the funnel
 * so their column matches the new settings. Both the Settings pages and any
 * other caller go through here.
 */
class FunnelConfigurator
{
    public const LEAD_TYPES = PipelineResolver::LEAD_TYPES;

    public const PROPERTY_TYPES = PipelineResolver::PROPERTY_TYPES;

    /** Most columns one funnel (lead type × property type) can have. */
    public const MAX_STAGES = 12;

    /** Most documents one column can ask for. */
    public const MAX_DOCUMENTS_PER_STAGE = 20;

    public function __construct(private PipelineResolver $pipelines, private LeadFunnelService $funnel) {}

    /**
     * @return Collection<int, PipelineStage>
     */
    public function stages(User $user, string $leadType, string $propertyType): Collection
    {
        return PipelineStage::where('user_id', $user->id)
            ->where('lead_type', $leadType)
            ->where('property_type', $propertyType)
            ->orderBy('position')->orderBy('id')
            ->get();
    }

    /**
     * Replace the columns of one lead type × property type with $rows, in the
     * given order.
     * A row that carries the key of an existing column renames it (its leads
     * and documents stay); any other row is a new column; existing columns
     * missing from $rows are removed along with their documents.
     *
     * @param  list<array{key?: ?string, label?: ?string}>  $rows
     */
    public function saveStages(User $user, string $leadType, string $propertyType, array $rows): void
    {
        DB::transaction(function () use ($user, $leadType, $propertyType, $rows) {
            $existing = $this->stages($user, $leadType, $propertyType)->keyBy('key');
            $taken = $existing->keys()->all();
            $kept = [];

            foreach (array_values($this->cleanRows($rows)) as $position => $row) {
                $stage = isset($row['key']) ? $existing->get($row['key']) : null;

                if ($stage) {
                    $stage->update(['label' => $row['label'], 'position' => $position]);
                } else {
                    $key = $this->uniqueKey($row['label'], $taken);
                    $taken[] = $key;

                    $stage = PipelineStage::create([
                        'user_id' => $user->id,
                        'lead_type' => $leadType,
                        'property_type' => $propertyType,
                        'key' => $key,
                        'label' => $row['label'],
                        'position' => $position,
                    ]);
                }

                $kept[] = $stage->key;
            }

            $existing->reject(fn ($row) => in_array($row->key, $kept, true))->each->delete();
        });

        $this->settingsChanged($user, $leadType, $propertyType);
    }

    /**
     * What each column of this lead type × property type asks for.
     *
     * @return Collection<int, Collection<int, StageDocument>> keyed by column key
     */
    public function documents(User $user, string $leadType, string $propertyType): Collection
    {
        return $this->stages($user, $leadType, $propertyType)->mapWithKeys(fn (PipelineStage $stage) => [
            $stage->key => $stage->documents->values(),
        ]);
    }

    /**
     * Replace the documents of one lead type × property type's columns. $byStage maps a
     * column key to its rows; same rules as saveStages(): a row with an
     * existing key renames the document (its ticks stay), other rows are new,
     * and documents left out are removed.
     *
     * @param  array<string, list<array{key?: ?string, label?: ?string}>>  $byStage
     */
    public function saveDocuments(User $user, string $leadType, string $propertyType, array $byStage): void
    {
        DB::transaction(function () use ($user, $leadType, $propertyType, $byStage) {
            $stages = $this->stages($user, $leadType, $propertyType)->keyBy('key');

            // A document key identifies a tick on the lead, so it has to be
            // unique across all of this funnel's columns.
            $taken = StageDocument::whereIn('pipeline_stage_id', $stages->pluck('id'))->pluck('key')->all();

            foreach ($stages as $stageKey => $stage) {
                $existing = $stage->documents->keyBy('key');
                $kept = [];

                foreach (array_values($this->cleanRows($byStage[$stageKey] ?? [])) as $position => $row) {
                    $document = isset($row['key']) ? $existing->get($row['key']) : null;

                    if ($document) {
                        $document->update(['label' => $row['label'], 'position' => $position]);
                    } else {
                        $key = $this->uniqueKey($row['label'], $taken);
                        $taken[] = $key;

                        $document = StageDocument::create([
                            'pipeline_stage_id' => $stage->id,
                            'key' => $key,
                            'label' => $row['label'],
                            'position' => $position,
                        ]);
                    }

                    $kept[] = $document->key;
                }

                $existing->reject(fn ($row) => in_array($row->key, $kept, true))->each->delete();
            }
        });

        $this->settingsChanged($user, $leadType, $propertyType);
    }

    /**
     * Put the agent's leads of this type × property type back where the new
     * settings say.
     */
    private function settingsChanged(User $user, string $leadType, string $propertyType): void
    {
        $this->pipelines->flush();

        $user->leads()->where('type', $leadType)->where('property_type', $propertyType)->get()->each(fn ($lead) => $this->funnel->advance($lead));
    }

    /**
     * Drop blank rows and trim labels.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{key: ?string, label: string}>
     */
    private function cleanRows(array $rows): array
    {
        return collect($rows)
            ->map(fn ($row) => [
                'key' => ($row['key'] ?? null) ?: null,
                'label' => trim((string) ($row['label'] ?? '')),
            ])
            ->filter(fn ($row) => $row['label'] !== '')
            ->values()
            ->all();
    }

    /**
     * A key made from the label that no other column/document in $taken has,
     * so two "Amendments" never share a tick.
     *
     * @param  list<string>  $taken
     */
    private function uniqueKey(string $label, array $taken): string
    {
        $base = Str::slug($label, '_') ?: 'item';
        $key = $base;

        for ($n = 2; in_array($key, $taken, true); $n++) {
            $key = "{$base}_{$n}";
        }

        return $key;
    }
}

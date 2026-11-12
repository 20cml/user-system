<?php

namespace App\Models;

use App\Funnel\LeadFunnelService;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'first_name', 'last_name', 'phone', 'email', 'status', 'type', 'property_type',
])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    /**
     * The funnel rules (pipelines, stages, document checklists, advancement)
     * live in App\Funnel — this model only exposes them for one lead. See
     * LeadFunnelService, the PipelineStrategy classes, the Stage classes and
     * the Specification classes. The columns and documents are the agent's
     * own settings (App\Funnel\FunnelConfigurator).
     */
    private function funnel(): LeadFunnelService
    {
        return app(LeadFunnelService::class);
    }

    /**
     * The full display name — `last_name` is optional, so this only adds a
     * space and the surname when one is actually on file.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => trim("{$this->first_name} {$this->last_name}") ?: __('New lead'),
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listings(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class, 'lead_listing');
    }

    /**
     * The listings this lead is the seller/landlord of — the reverse of
     * Listing::owner(). A seller/landlord can own several.
     */
    public function ownedListings(): HasMany
    {
        return $this->hasMany(Listing::class, 'owner_lead_id');
    }

    /**
     * The listings this lead has been picked as the buyer (or renter) of —
     * the reverse of Listing::buyer().
     */
    public function boughtListings(): HasMany
    {
        return $this->hasMany(Listing::class, 'buyer_lead_id');
    }

    public function notes(): HasMany
    {
        // Ordering by created_at alone isn't reliable when two notes are added within the
        // same second (equal timestamps); id always reflects true insertion order.
        return $this->hasMany(LeadNote::class)->latest('id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(LeadDocument::class);
    }

    /**
     * The stage keys of this lead's type's pipeline, in order. Empty for a
     * type with no pipeline.
     *
     * @return list<string>
     */
    public function stageKeys(): array
    {
        return $this->funnel()->pipelineFor($this)?->stageKeys() ?? [];
    }

    /**
     * Display label for each stage of this lead's pipeline, keyed by stage.
     *
     * @return array<string, string>
     */
    public function stageLabels(): array
    {
        return $this->funnel()->pipelineFor($this)?->labels() ?? [];
    }

    /**
     * The label of the column this lead is in, or null when it has none.
     */
    public function statusLabel(): ?string
    {
        return $this->stageLabels()[$this->status] ?? null;
    }

    /**
     * Every document key this lead's type × property type can have, across
     * all stages.
     *
     * @return list<string>
     */
    public function documentKeys(): array
    {
        return $this->funnel()->pipelineFor($this)?->documentKeys() ?? [];
    }

    /**
     * Whether this lead's type × property type combination has any document
     * checklist defined at all (across every funnel stage).
     */
    public function hasDocumentChecklist(): bool
    {
        return $this->funnel()->hasDocumentChecklist($this);
    }

    /**
     * This lead's document checklist for one funnel stage, each key merged
     * with whether it's currently checked. Empty when that stage has no
     * checklist defined for this lead's type × property type.
     */
    public function documentChecklistForStage(string $stage): array
    {
        return $this->funnel()->documentChecklistForStage($this, $stage);
    }

    /**
     * Save a submitted set of document checkboxes and recompute the status.
     * See LeadFunnelService::syncDocuments().
     *
     * @param  array<string, mixed>  $submitted
     */
    public function updateDocuments(array $submitted): void
    {
        $this->funnel()->syncDocuments($this, $submitted);
    }

    /**
     * Recompute this lead's status from its document checklist and save it.
     * See LeadFunnelService::advance().
     */
    public function advanceStatusFromDocuments(): void
    {
        $this->funnel()->advance($this);
    }
}

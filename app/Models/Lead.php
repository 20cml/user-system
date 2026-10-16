<?php

namespace App\Models;

use App\Funnel\LeadFunnelService;
use App\Funnel\Pipelines\PipelineResolver;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
     * the Specification classes.
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
            get: fn () => trim("{$this->first_name} {$this->last_name}"),
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
     * The listing this lead is the seller/landlord of, if any — the reverse
     * of Listing::owner(). A lead can own at most one listing.
     */
    public function ownedListing(): HasOne
    {
        return $this->hasOne(Listing::class, 'owner_lead_id');
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
     * Every document key this lead's type × property type can have, across
     * all stages.
     *
     * @return list<string>
     */
    public function documentKeys(): array
    {
        return $this->funnel()->pipelineFor($this)?->documentKeys($this->property_type) ?? [];
    }

    /**
     * Every funnel stage key across all pipelines — for validating a status.
     *
     * @return list<string>
     */
    public static function allStageKeys(): array
    {
        return app(PipelineResolver::class)->allStageKeys();
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
     * Whether this lead is ready to advance past its pipeline's listing-
     * narrowing stage on its documents alone, but is being held back because
     * it isn't linked to exactly one listing yet.
     */
    public function needsListingNarrowed(): bool
    {
        return $this->funnel()->needsListingNarrowed($this);
    }

    /**
     * The other lead (if any) that already has this lead's single linked
     * listing under contract — the reason this lead can't advance into
     * 'under_contract' itself.
     */
    public function blockingUnderContractLead(): ?self
    {
        return $this->funnel()->blockingUnderContractLead($this);
    }

    /**
     * The other lead, if any, currently 'under_contract' on this lead's
     * single linked listing. Assumes the caller already knows there's
     * exactly one linked listing.
     */
    public function conflictingUnderContractLead(): ?self
    {
        $listing = $this->listings()->first();

        return $listing?->leads()
            ->where('leads.id', '!=', $this->id)
            ->where('status', 'under_contract')
            ->first();
    }

    /**
     * The stage this lead would actually land on once its listing gets
     * narrowed to one — not necessarily its narrowing stage itself.
     */
    public function targetStageLabelOnceListingNarrowed(): ?string
    {
        return $this->funnel()->targetStageLabelOnceListingNarrowed($this);
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

<?php

namespace App\Models;

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
    /** @use HasFactory<\Database\Factories\LeadFactory> */
    use HasFactory;

    /**
     * The document checklist each lead type × property type × funnel stage
     * combination needs, keyed by document key → label. 'new' has none — it's
     * the default starting stage — and checking off the RECO Information
     * Guide is what moves a lead to 'contacted'. Only buyer + condo is
     * defined for now — every other combination gets an empty checklist.
     */
    public const DOCUMENTS_BY_TYPE = [
        'buyer' => [
            'condo' => [
                'new' => [],
                'contacted' => [
                    'reco_information_guide' => 'RECO Information Guide',
                ],
                'qualified' => [
                    'buyer_representation_agreement' => 'Buyer Representation Agreement',
                    'disclosures' => 'Disclosure(s)',
                ],
                'offer' => [
                    'agreement_of_purchase_and_sale' => 'Agreement of Purchase and Sale',
                    'schedules_addendums' => 'Schedule(s) / Addendum(s)',
                    'deposit_receipt' => 'Deposit Receipt',
                    'proof_of_deposit' => 'Proof of Deposit',
                ],
                'under_contract' => [
                    'amendments' => 'Amendment(s)',
                    'waivers' => 'Waiver(s)',
                    'notices_of_fulfillment' => 'Notice(s) of Fulfillment',
                    'status_certificate' => 'Status Certificate',
                ],
                'closed' => [
                    'final_agreement_of_purchase_and_sale' => 'Final Agreement of Purchase and Sale',
                    'final_amendments' => 'Final Amendment(s)',
                    'trade_record_sheet' => 'Trade Record Sheet',
                ],
            ],
        ],
    ];

    /**
     * The funnel stages each lead type's pipeline moves through, in order.
     * Only 'buyer' has a pipeline defined today — seller, investor, renter,
     * and landlord leads keep a manually-set status until their own
     * pipelines are defined here.
     */
    public const PIPELINE_STAGES_BY_TYPE = [
        'buyer' => ['new', 'contacted', 'qualified', 'offer', 'under_contract', 'closed'],
    ];

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
     * Whether this lead's type × property type combination has any document
     * checklist defined at all (across every funnel stage).
     */
    public function hasDocumentChecklist(): bool
    {
        return isset(self::DOCUMENTS_BY_TYPE[$this->type][$this->property_type]);
    }

    /**
     * This lead's document checklist for one funnel stage, each key merged
     * with whether it's currently checked. Empty when that stage has no
     * checklist defined for this lead's type × property type.
     */
    public function documentChecklistForStage(string $stage): array
    {
        $checked = $this->documents->pluck('checked', 'key');

        return collect(self::DOCUMENTS_BY_TYPE[$this->type][$this->property_type][$stage] ?? [])
            ->map(fn ($label, $key) => [
                'label' => $label,
                'checked' => (bool) ($checked[$key] ?? false),
            ])
            ->all();
    }

    /**
     * Recompute this lead's status from its document checklist: the furthest
     * pipeline stage (PIPELINE_STAGES_BY_TYPE) that's fully checked, walking
     * from 'new' and stopping at the first incomplete stage — so a later
     * stage being complete doesn't count if an earlier one isn't. A stage
     * with no documents defined (like 'new') counts as satisfied
     * automatically. Moves the status up or down to match, so unchecking a
     * document can send it back a stage. No-ops when the lead is Lost, its
     * type has no pipeline defined, or it has no checklist defined for its
     * type × property type.
     */
    public function advanceStatusFromDocuments(): void
    {
        $stages = self::PIPELINE_STAGES_BY_TYPE[$this->type] ?? null;

        if ($this->status === 'lost' || ! $stages || ! $this->hasDocumentChecklist()) {
            return;
        }

        $furthestComplete = $stages[0];

        foreach ($stages as $stage) {
            $checklist = $this->documentChecklistForStage($stage);

            if (collect($checklist)->contains(fn ($doc) => ! $doc['checked'])) {
                break;
            }

            $furthestComplete = $stage;
        }

        $this->status = $furthestComplete;
        $this->save();
    }
}

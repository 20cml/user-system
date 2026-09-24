<?php

namespace App\Models;

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
    /** @use HasFactory<\Database\Factories\LeadFactory> */
    use HasFactory;

    /**
     * The document checklist each lead type × property type × funnel stage
     * combination needs, keyed by document key → label. Buyer's and
     * renter's 'showing' stage has none — it's just the agent taking them
     * to view properties, with nothing to check off before an offer gets
     * made. Only buyer + condo, seller + condo, renter + condo, and
     * landlord + condo are defined for now — every other combination gets
     * an empty checklist.
     */
    public const DOCUMENTS_BY_TYPE = [
        'buyer' => [
            'condo' => [
                'new' => [
                    'reco_information_guide' => 'RECO Information Guide',
                    'id_client_verification' => 'ID/Client Verification',
                ],
                'contacted' => [
                    'buyer_representation_agreement' => 'Buyer Representation Agreement',
                ],
                'qualified' => [
                    'mortgage_pre_approval_proof_of_funds' => 'Mortgage Pre-Approval / Proof of Funds',
                ],
                'property_search' => [
                    'mls_listing' => 'MLS Listing',
                    'status_certificate' => 'Status Certificate',
                    'condo_documents' => 'Condo Documents',
                ],
                // No documents gate this stage — it's just the agent taking the
                // buyer to view properties before an offer gets made.
                'showing' => [],
                'offer' => [
                    'agreement_of_purchase_and_sale' => 'Agreement of Purchase and Sale (APS)',
                    'deposit_receipt' => 'Deposit Receipt',
                    // Distinct key from under_contract's amendments below — an
                    // amendment at offer stage and one after firming up are
                    // different documents, even though both are just labeled
                    // "Amendments".
                    'offer_amendments' => 'Amendments',
                ],
                'under_contract' => [
                    'waivers_notices_of_fulfillment' => 'Waivers/Notices of Fulfillment',
                    'under_contract_amendments' => 'Amendments',
                ],
                'closed' => [
                    'final_aps' => 'Final APS',
                    'closing_records' => 'Closing Records',
                ],
            ],
        ],
        'seller' => [
            'condo' => [
                'new' => [
                    'reco_information_guide' => 'RECO Information Guide',
                    'id_client_verification' => 'ID/Client Verification',
                ],
                'contacted' => [
                    'seller_representation_agreement' => 'Seller Representation Agreement',
                    'property_title_information' => 'Property/Title Information',
                    'condo_corporation_information' => 'Condo Corporation Information',
                ],
                'listed' => [
                    'listing_agreement' => 'Listing Agreement',
                    'mls_listing' => 'MLS Listing',
                    'status_certificate' => 'Status Certificate',
                    'condo_declaration_bylaws_rules' => 'Condo Declaration/By-laws/Rules',
                    'condo_fees_assessment_information' => 'Condo Fees/Assessment Information',
                ],
                'offer_received' => [
                    'agreement_of_purchase_and_sale' => 'Agreement of Purchase and Sale (APS)',
                    'offer_counteroffer' => 'Offer/Counteroffer',
                    'deposit_details' => 'Deposit Details',
                    // Distinct key from under_contract's amendments below — an
                    // amendment at offer stage and one after firming up are
                    // different documents, even though both are just labeled
                    // "Amendments".
                    'offer_amendments' => 'Amendments',
                ],
                'under_contract' => [
                    'waivers_notices_of_fulfillment' => 'Waivers/Notices of Fulfillment',
                    'under_contract_amendments' => 'Amendments',
                    'lawyer_closing_information' => 'Lawyer/Closing Information',
                ],
                'closed' => [
                    'final_aps_amendments' => 'Final APS/Amendments',
                    'closing_transaction_records' => 'Closing/Transaction Records',
                    'statement_of_adjustments' => 'Statement of Adjustments',
                ],
            ],
        ],
        'renter' => [
            'condo' => [
                'new' => [
                    'reco_information_guide' => 'RECO Information Guide',
                    'id_client_verification' => 'ID/Client Verification',
                ],
                'contacted' => [
                    'tenant_representation_agreement' => 'Tenant Representation Agreement',
                ],
                'qualified' => [
                    'rental_application' => 'Rental Application',
                    'proof_of_income_employment' => 'Proof of Income / Employment',
                ],
                'property_search' => [
                    'mls_listing' => 'MLS Listing',
                    'condo_documents' => 'Condo Documents',
                ],
                // No documents gate this stage — it's just the agent taking the
                // renter to view properties before an offer gets made.
                'showing' => [],
                'offer' => [
                    'agreement_to_lease' => 'Agreement to Lease',
                    'deposit_receipt' => 'Deposit Receipt',
                ],
                'under_contract' => [
                    'signed_agreement_to_lease' => 'Signed Agreement to Lease',
                    'under_contract_amendments' => 'Amendments',
                ],
                'closed' => [
                    'final_agreement_to_lease' => 'Final Agreement to Lease',
                    'move_in_records' => 'Move-in Records',
                ],
            ],
        ],
        'landlord' => [
            'condo' => [
                'new' => [
                    'reco_information_guide' => 'RECO Information Guide',
                    'id_client_verification' => 'ID/Client Verification',
                ],
                'contacted' => [
                    'listing_representation_agreement' => 'Listing / Representation Agreement',
                ],
                'listed' => [
                    'mls_listing' => 'MLS Listing',
                    'condo_documents' => 'Condo Documents',
                    'rental_details' => 'Rental Details',
                ],
                'application_received' => [
                    'rental_application' => 'Rental Application',
                    'proof_of_income_employment' => 'Proof of Income / Employment',
                ],
                'lease' => [
                    'residential_tenancy_agreement' => 'Residential Tenancy Agreement (Standard Form of Lease)',
                    'deposit_receipt' => 'Deposit Receipt',
                ],
                'rented' => [
                    'signed_lease' => 'Signed Lease',
                    'amendments' => 'Amendments',
                    'move_in_records' => 'Move-in Records',
                ],
            ],
        ],
    ];

    /**
     * The funnel stages each lead type's pipeline moves through, in order.
     */
    public const PIPELINE_STAGES_BY_TYPE = [
        'buyer' => ['new', 'contacted', 'qualified', 'property_search', 'showing', 'offer', 'under_contract', 'closed'],
        'seller' => ['new', 'contacted', 'listed', 'offer_received', 'under_contract', 'closed'],
        'renter' => ['new', 'contacted', 'qualified', 'property_search', 'showing', 'offer', 'under_contract', 'closed'],
        'landlord' => ['new', 'contacted', 'listed', 'application_received', 'lease', 'rented'],
    ];

    /**
     * Display label for each stage in each type's pipeline — the stage keys
     * themselves aren't always presentable as-is (e.g. 'offer_received').
     */
    public const PIPELINE_STAGE_LABELS = [
        'buyer' => [
            'new' => 'New',
            'contacted' => 'Contacted',
            'qualified' => 'Qualified',
            'property_search' => 'Property Search',
            'showing' => 'Showing',
            'offer' => 'Offer',
            'under_contract' => 'Under Contract',
            'closed' => 'Closed',
        ],
        'seller' => [
            'new' => 'New',
            'contacted' => 'Contacted',
            'listed' => 'Listed',
            'offer_received' => 'Offer Received',
            'under_contract' => 'Under Contract',
            'closed' => 'Closed',
        ],
        'renter' => [
            'new' => 'New',
            'contacted' => 'Contacted',
            'qualified' => 'Qualified',
            'property_search' => 'Property Search',
            'showing' => 'Showing',
            'offer' => 'Offer',
            'under_contract' => 'Under Contract',
            'closed' => 'Closed',
        ],
        'landlord' => [
            'new' => 'New',
            'contacted' => 'Contacted',
            'listed' => 'Listed',
            'application_received' => 'Application Received',
            'lease' => 'Lease',
            'rented' => 'Rented',
        ],
    ];

    /**
     * The pipeline stage at which each lead type must be narrowed down to
     * exactly one linked listing before it can advance further. Both buyer
     * and renter are narrowed at 'under_contract', not 'offer' — in reality
     * the same listing can have several competing offers/applications out
     * at once, so it's only once one of them is actually firmed up that
     * "Interested Leads" needs to be down to the one. Seller has no entry
     * here since it has no such gate at all.
     */
    public const LISTING_NARROWING_STAGE_BY_TYPE = [
        'buyer' => 'under_contract',
        'renter' => 'under_contract',
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
     * Whether every stage from 'new' up to and including $targetStage has a
     * fully-checked document checklist.
     */
    private function documentsCompleteThroughStage(string $targetStage): bool
    {
        $stages = self::PIPELINE_STAGES_BY_TYPE[$this->type] ?? [];

        foreach ($stages as $stage) {
            $checklist = $this->documentChecklistForStage($stage);

            if (collect($checklist)->contains(fn ($doc) => ! $doc['checked'])) {
                return false;
            }

            if ($stage === $targetStage) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether this lead is ready to advance past its type's listing-
     * narrowing stage (LISTING_NARROWING_STAGE_BY_TYPE) on its documents
     * alone, but is being held back because it isn't linked to exactly one
     * listing yet. The agent needs to narrow "Interested Leads" down to
     * that one listing before the funnel can move past it. Always false for
     * a type with no narrowing stage defined (e.g. seller).
     */
    public function needsListingNarrowed(): bool
    {
        $gateStage = self::LISTING_NARROWING_STAGE_BY_TYPE[$this->type] ?? null;

        if (! $gateStage || ! $this->hasDocumentChecklist()) {
            return false;
        }

        return $this->documentsCompleteThroughStage($gateStage) && $this->listings()->count() !== 1;
    }

    /**
     * The other lead (if any) that already has this lead's single linked
     * listing under contract — the reason this lead can't advance into
     * 'under_contract' itself. A listing can only be genuinely under
     * contract with one tenant/buyer at a time, so a second lead reaching
     * the same point on the same listing has to wait. Null whenever there's
     * no such conflict (including when this lead isn't narrowed to exactly
     * one listing yet — needsListingNarrowed() covers that case instead).
     *
     * Reads the `held_under_contract` flag rather than re-checking the raw
     * document checkboxes, because advanceStatusFromDocuments() clears the
     * 'under_contract' checklist the moment it detects this conflict (so the
     * checklist doesn't sit there looking "done" while blocked) — the flag
     * is what keeps this warning showing on every later visit regardless.
     */
    public function blockingUnderContractLead(): ?self
    {
        if (! $this->hasDocumentChecklist() || $this->listings()->count() !== 1 || ! $this->held_under_contract) {
            return null;
        }

        return $this->conflictingUnderContractLead();
    }

    /**
     * The other lead, if any, currently 'under_contract' on this lead's
     * single linked listing. Assumes the caller already knows there's
     * exactly one linked listing.
     */
    private function conflictingUnderContractLead(): ?self
    {
        $listing = $this->listings()->first();

        return $listing?->leads()
            ->where('leads.id', '!=', $this->id)
            ->where('status', 'under_contract')
            ->first();
    }

    /**
     * The stage this lead would actually land on once its listing gets
     * narrowed to one — not necessarily its narrowing stage itself. A lead
     * whose documents are already checked off further along (e.g. all the
     * way through 'under_contract') jumps straight there the moment the
     * listing count gate clears, so the "narrow the listing" prompt should
     * name that real destination rather than always naming the gate stage.
     */
    public function targetStageLabelOnceListingNarrowed(): ?string
    {
        if (! $this->needsListingNarrowed()) {
            return null;
        }

        $stage = $this->furthestCompleteStage(ignoreListingGate: true);

        return self::PIPELINE_STAGE_LABELS[$this->type][$stage] ?? null;
    }

    /**
     * Walks this lead's pipeline stages from 'new', returning the furthest
     * one whose document checklist is fully checked — stopping at the first
     * incomplete stage, so a later stage being complete doesn't count if an
     * earlier one isn't. A stage with no documents defined (like buyer's
     * 'showing') counts as satisfied automatically. This type's listing-
     * narrowing stage (LISTING_NARROWING_STAGE_BY_TYPE) additionally
     * requires exactly one linked listing, and 'under_contract' additionally
     * requires no other lead already being under contract on that same
     * listing, unless $ignoreListingGate asks to look past both and report
     * where the documents alone would take it.
     */
    private function furthestCompleteStage(bool $ignoreListingGate = false): string
    {
        $stages = self::PIPELINE_STAGES_BY_TYPE[$this->type];
        $gateStage = self::LISTING_NARROWING_STAGE_BY_TYPE[$this->type] ?? null;
        $furthestComplete = $stages[0];

        foreach ($stages as $stage) {
            $checklist = $this->documentChecklistForStage($stage);

            if (collect($checklist)->contains(fn ($doc) => ! $doc['checked'])) {
                break;
            }

            if (! $ignoreListingGate && $stage === $gateStage && $this->listings()->count() !== 1) {
                break;
            }

            if (! $ignoreListingGate && $stage === 'under_contract' && $this->listings()->count() === 1 && $this->conflictingUnderContractLead()) {
                break;
            }

            $furthestComplete = $stage;
        }

        return $furthestComplete;
    }

    /**
     * Recompute this lead's status from its document checklist and save it.
     * Moves the status up or down to match, so unchecking a document can
     * send it back a stage. No-ops when the lead is Lost, its type has no
     * pipeline defined, or it has no checklist defined for its type ×
     * property type. See furthestCompleteStage() for how the stage itself
     * is determined.
     */
    public function advanceStatusFromDocuments(): void
    {
        $stages = self::PIPELINE_STAGES_BY_TYPE[$this->type] ?? null;

        if ($this->status === 'lost' || ! $stages || ! $this->hasDocumentChecklist()) {
            return;
        }

        $this->status = $this->furthestCompleteStage();

        // Snapshot whether this lead is fully ready for 'under_contract' but
        // blocked by another lead already holding the listing — read before
        // the checklist reset below, and kept in its own column (rather than
        // re-derived from the checkboxes) so blockingUnderContractLead() can
        // keep reporting this on every later visit even after those
        // checkboxes get cleared.
        $blocked = $this->hasDocumentChecklist()
            && $this->listings()->count() === 1
            && $this->documentsCompleteThroughStage('under_contract')
            && $this->conflictingUnderContractLead();

        $this->held_under_contract = (bool) $blocked;
        $this->save();

        if ($blocked) {
            $this->documents()
                ->whereIn('key', array_keys(self::DOCUMENTS_BY_TYPE[$this->type][$this->property_type]['under_contract'] ?? []))
                ->update(['checked' => false]);
        }

        $this->listings()->get()->each->syncStatusFromLeads();
    }
}

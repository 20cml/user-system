<?php

namespace App\Funnel\Pipelines;

/**
 * Renter's funnel. Mirrors the buyer's: 'showing' has no documents, and
 * the listing is narrowed to exactly one at 'under_contract'.
 */
class RenterPipeline extends AbstractPipeline
{
    protected const TYPE = 'renter';

    protected const STAGES = [
        '0' => 'new',
        '1' => 'contacted',
        '2' => 'qualified',
        '3' => 'property_search',
        '4' => 'showing',
        '5' => 'offer',
        '6' => 'under_contract',
        '7' => 'closed',
    ];

    /**
     * Document checklist per property type, then per stage. Only condo is
     * defined for now — every other property type gets an empty checklist.
     */
    protected const DOCUMENTS = [
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
            'showing' => [
            ],
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
    ];

    protected const NARROWING_STAGE = 'under_contract';
}

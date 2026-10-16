<?php

namespace App\Funnel\Pipelines;

/**
 * Landlord's funnel. A landlord owns their listing, so there is no
 * listing-narrowing gate.
 */
class LandlordPipeline extends AbstractPipeline
{
    protected const TYPE = 'landlord';

    protected const STAGES = [
        '0' => 'new',
        '1' => 'contacted',
        '2' => 'listed',
        '3' => 'application_received',
        '4' => 'lease',
        '5' => 'rented',
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
    ];

    protected const NARROWING_STAGE = null;
}

<?php

namespace App\Funnel\Pipelines;

/**
 * Buyer's funnel. 'showing' has no documents: it's just the agent taking
 * the buyer to view properties before an offer gets made. The listing must
 * be narrowed to exactly one at 'under_contract' — several offers can be
 * out on the same listing at once, so it's only once one is firmed up that
 * "Interested Leads" needs to be down to the one.
 */
class BuyerPipeline extends AbstractPipeline
{
    protected const TYPE = 'buyer';

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
            'showing' => [
            ],
            'offer' => [
                'agreement_of_purchase_and_sale' => 'Agreement of Purchase and Sale (APS)',
                'deposit_receipt' => 'Deposit Receipt',
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
    ];

    protected const NARROWING_STAGE = 'under_contract';
}

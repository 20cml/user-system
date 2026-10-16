<?php

namespace App\Funnel\Pipelines;

/**
 * Seller's funnel. A seller owns their listing, so there is no
 * listing-narrowing gate.
 */
class SellerPipeline extends AbstractPipeline
{
    protected const TYPE = 'seller';

    protected const STAGES = [
        '0' => 'new',
        '1' => 'contacted',
        '2' => 'listed',
        '3' => 'offer_received',
        '4' => 'under_contract',
        '5' => 'closed',
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
    ];

    protected const NARROWING_STAGE = null;
}

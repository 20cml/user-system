<?php

namespace Tests\Concerns;

use App\Funnel\Pipelines\PipelineResolver;
use App\Models\PipelineStage;
use App\Models\StageDocument;
use App\Models\User;

/**
 * The application has no columns or documents built in: agents set them up in
 * Settings. These tests exercise the funnel engine, so every user created
 * during a test gets a realistic Ontario real-estate funnel (the same columns
 * for every property type, documents for condo only), the way an agent might have set it up. Test data only.
 */
trait SeedsDefaultFunnel
{
    /** @var array<string, array{stages: array<string, string>, documents: array<string, array<string, string>>}> */
    private const DEFAULT_FUNNEL = [
        'buyer' => [
            'stages' => ['new' => 'New', 'contacted' => 'Contacted', 'qualified' => 'Qualified', 'property_search' => 'Property Search', 'showing' => 'Showing', 'offer' => 'Offer', 'under_contract' => 'Under Contract', 'closed' => 'Closed'],
            'documents' => [
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
        ],
        'seller' => [
            'stages' => ['new' => 'New', 'contacted' => 'Contacted', 'listed' => 'Listed', 'offer_received' => 'Offer Received', 'under_contract' => 'Under Contract', 'closed' => 'Closed'],
            'documents' => [
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
        ],
        'renter' => [
            'stages' => ['new' => 'New', 'contacted' => 'Contacted', 'qualified' => 'Qualified', 'property_search' => 'Property Search', 'showing' => 'Showing', 'offer' => 'Offer', 'under_contract' => 'Under Contract', 'closed' => 'Closed'],
            'documents' => [
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
            'stages' => ['new' => 'New', 'contacted' => 'Contacted', 'listed' => 'Listed', 'application_received' => 'Application Received', 'lease' => 'Lease', 'rented' => 'Rented'],
            'documents' => [
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

    protected function setUpSeedsDefaultFunnel(): void
    {
        User::created(fn (User $user) => $this->seedDefaultFunnel($user));
    }

    protected function seedDefaultFunnel(User $user): void
    {
        // The listener can be registered more than once per process.
        if (PipelineStage::where('user_id', $user->id)->exists()) {
            return;
        }

        foreach (self::DEFAULT_FUNNEL as $leadType => $funnel) {
            foreach (PipelineResolver::PROPERTY_TYPES as $propertyType) {
                $position = 0;

                foreach ($funnel['stages'] as $key => $label) {
                    $stage = PipelineStage::create([
                        'user_id' => $user->id,
                        'lead_type' => $leadType,
                        'property_type' => $propertyType,
                        'key' => $key,
                        'label' => $label,
                        'position' => $position++,
                    ]);

                    $order = 0;

                    // only condo has documents; the other property types get the columns alone
                    foreach ($propertyType === 'condo' ? ($funnel['documents'][$key] ?? []) : [] as $docKey => $docLabel) {
                        StageDocument::create([
                            'pipeline_stage_id' => $stage->id,
                            'key' => $docKey,
                            'label' => $docLabel,
                            'position' => $order++,
                        ]);
                    }
                }
            }
        }

        app(PipelineResolver::class)->flush();
    }
}

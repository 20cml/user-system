<?php

namespace App\Http\Requests;

use App\Models\Listing;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListingRequest extends FormRequest
{
    /**
     * The buyer is picked from the listing's interested leads, so one who
     * isn't (or who was just removed from that list) is dropped rather than
     * kept. "Interested" is the submitted lead_ids if present, otherwise
     * what's already saved on the listing.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('buyer_lead_id')) {
            return;
        }

        $interested = $this->has('lead_ids')
            ? (array) $this->input('lead_ids')
            : ($this->route('listing')?->leads()->pluck('leads.id')->all() ?? []);

        if (! in_array((int) $this->input('buyer_lead_id'), array_map('intval', $interested), true)) {
            $this->merge(['buyer_lead_id' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // On create, the listing's owner (an existing seller/landlord lead,
        // optional) is picked right alongside it, so listing_type must be
        // known up front to know which lead type may own it, and
        // property_type to know which document checklist applies. On update, all three stay
        // optional, filled in later from the listing's own root row.
        $creating = $this->isMethod('post');

        return [
            // The address is filled in after the listing exists, so none of it is required.
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state_province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', Rule::in(['CA', 'US'])],
            'price' => ['nullable', 'numeric', 'gt:0'],
            'listing_type' => [$creating ? 'required' : 'nullable', Rule::in(['sale', 'rent'])],
            'property_type' => [$creating ? 'required' : 'nullable', Rule::in(['house', 'condo', 'land', 'commercial'])],
            'area_sqm' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
            'photos' => ['array', 'max:'.Listing::MAX_PHOTOS],
            'photos.*' => ['image', 'max:5120'],
            'remove_photos' => ['array'],
            'remove_photos.*' => ['integer'],
            'lead_ids' => ['array'],
            'lead_ids.*' => [Rule::exists('leads', 'id')->where(function ($query) {
                $query->where('user_id', $this->user()->id);

                // Rental listings take only Renter leads; sale listings only Buyer leads.
                $listingType = $this->input('listing_type') ?? $this->route('listing')?->listing_type;
                $leadType = ['rent' => 'renter', 'sale' => 'buyer'][$listingType] ?? null;
                if ($leadType) {
                    $query->where('type', $leadType);
                }
            })],
            // The buyer (sale) or renter (rental) is optional, picked on the listing page.
            'buyer_lead_id' => ['nullable', Rule::exists('leads', 'id')->where(function ($query) {
                $query->where('user_id', $this->user()->id);

                $listingType = $this->input('listing_type') ?? $this->route('listing')?->listing_type;
                $buyerType = ['rent' => 'renter', 'sale' => 'buyer'][$listingType] ?? null;
                if ($buyerType) {
                    $query->where('type', $buyerType);
                }
            })],
            // Optional ("None yet"). A sale listing's owner is a Seller lead, a rental's a Landlord lead.
            'owner_lead_id' => ['nullable', Rule::exists('leads', 'id')->where(function ($query) {
                $query->where('user_id', $this->user()->id);

                $listingType = $this->input('listing_type') ?? $this->route('listing')?->listing_type;
                $ownerType = ['rent' => 'landlord', 'sale' => 'seller'][$listingType] ?? null;
                if ($ownerType) {
                    $query->where('type', $ownerType);
                }
            })],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photos.*.uploaded' => __('A photo could not be uploaded. It is probably larger than the server allows.'),
            'photos.*.max' => __('Each photo must be 5 MB or smaller.'),
            'photos.*.image' => __('Only image files can be added as photos.'),
            'photos.max' => __('A listing can have up to :max photos.', ['max' => Listing::MAX_PHOTOS]),
        ];
    }

    /**
     * A listing holds at most Listing::MAX_PHOTOS photos: what it has now,
     * minus the ones being removed, plus the new uploads.
     */
    public function after(): array
    {
        return [function ($validator) {
            $listing = $this->route('listing');
            $removed = $listing ? $listing->photos()->whereIn('id', (array) $this->input('remove_photos', []))->count() : 0;
            $total = ($listing ? $listing->photos()->count() : 0) - $removed + count((array) $this->file('photos', []));

            if ($total > Listing::MAX_PHOTOS) {
                $validator->errors()->add('photos', __('A listing can have up to :max photos.', ['max' => Listing::MAX_PHOTOS]));
            }
        }];
    }
}

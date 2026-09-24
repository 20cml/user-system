<?php

namespace App\Http\Requests;

use App\Models\Lead;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Seller and Landlord leads are only ever created from the Add
        // Listing flow (ListingController::store()), never through this
        // form — so a new lead must be Buyer or Renter. Updating an
        // existing lead still allows all four, since a Seller/Landlord
        // lead created that way needs to keep saving its own details —
        // except a lead that owns a listing (Listing::owner()) can't be
        // switched to a different type at all, since the listing depends
        // on it staying whichever of the two it already is.
        $typeRules = match (true) {
            (bool) $this->route('lead')?->ownedListing => ['required', Rule::in([$this->route('lead')->type])],
            $this->isMethod('post') => ['required', Rule::in(['buyer', 'renter'])],
            default => ['nullable', Rule::in(['buyer', 'seller', 'renter', 'landlord'])],
        };

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'type' => $typeRules,
            'property_type' => ['nullable', Rule::in(['condo', 'house', 'land', 'commercial'])],
            'status' => ['sometimes', Rule::in([
                ...collect(Lead::PIPELINE_STAGES_BY_TYPE)->flatten()->unique()->values()->all(),
                'lost',
            ])],
            'listing_ids' => ['array'],
            'listing_ids.*' => [Rule::exists('listings', 'id')->where('user_id', $this->user()->id)],
            'documents' => ['array'],
            'documents.*' => ['boolean'],
        ];
    }
}

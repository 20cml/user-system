<?php

namespace App\Http\Requests;

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
        // Any of the four lead types can be created here — a Seller or
        // Landlord is a lead like any other, picked as the owner when a
        // listing is added. A lead that owns a listing (Listing::owner())
        // or is its buyer (Listing::buyer()) can't be switched to a
        // different type at all, since the listing depends on it staying
        // whichever it already is. The
        // owner form on the listing page doesn't post a type, so it's
        // optional there: omitted leaves it untouched, anything else must
        // match.
        $typeRules = match (true) {
            $this->route('lead') && ($this->route('lead')->ownedListings()->exists() || $this->route('lead')->boughtListings()->exists()) => ['sometimes', Rule::in([$this->route('lead')->type])],
            $this->isMethod('post') => ['required', Rule::in(['buyer', 'seller', 'renter', 'landlord'])],
            default => ['nullable', Rule::in(['buyer', 'seller', 'renter', 'landlord'])],
        };

        return [
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'type' => $typeRules,
            'property_type' => ['nullable', Rule::in(['condo', 'house', 'land', 'commercial'])],
            'listing_ids' => ['array'],
            'listing_ids.*' => [Rule::exists('listings', 'id')->where('user_id', $this->user()->id)],
            'documents' => ['array'],
            'documents.*' => ['boolean'],
        ];
    }
}

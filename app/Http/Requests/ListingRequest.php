<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // On create, the listing's owner (seller/landlord) is entered right
        // alongside it — see ListingController::store() — so listing_type
        // and property_type must be known up front too, to know which lead
        // type to create and which document checklist applies. On update,
        // all three stay optional, filled in later from the listing's own
        // root row.
        $creating = $this->isMethod('post');

        return [
            'address_line' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state_province' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', Rule::in(['CA', 'US'])],
            'price' => ['nullable', 'numeric', 'gt:0'],
            'listing_type' => [$creating ? 'required' : 'nullable', Rule::in(['sale', 'rent'])],
            'property_type' => [$creating ? 'required' : 'nullable', Rule::in(['house', 'condo', 'land', 'commercial'])],
            'status' => ['sometimes', Rule::in(['available', 'pending', 'closed'])],
            'area_sqm' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'remove_photo' => ['nullable', 'boolean'],
            'lead_ids' => ['array'],
            'lead_ids.*' => [Rule::exists('leads', 'id')->where('user_id', $this->user()->id)],
            'owner_first_name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'owner_last_name' => ['nullable', 'string', 'max:255'],
            'owner_phone' => ['nullable', 'string', 'max:20'],
            'owner_email' => ['nullable', 'string', 'email', 'max:255'],
        ];
    }
}

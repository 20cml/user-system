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
        return [
            'address_line' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state_province' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', Rule::in(['CA', 'US'])],
            'price' => ['required', 'numeric', 'gt:0'],
            'listing_type' => ['required', Rule::in(['sale', 'rent'])],
            'property_type' => ['required', Rule::in(['house', 'apartment', 'land', 'commercial'])],
            'status' => ['sometimes', Rule::in(['available', 'pending', 'closed'])],
            'area_sqm' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'remove_photo' => ['nullable', 'boolean'],
            'lead_ids' => ['array'],
            'lead_ids.*' => [Rule::exists('leads', 'id')->where('user_id', $this->user()->id)],
        ];
    }
}

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
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'type' => ['nullable', Rule::in(['buyer', 'seller', 'investor', 'renter', 'landlord'])],
            'financing_preapproval' => ['boolean'],
            'financing_income_proof' => ['boolean'],
            'financing_id_document' => ['boolean'],
            'status' => ['sometimes', Rule::in(['new', 'contacted', 'qualified', 'active_search', 'visited', 'proposal', 'closed', 'lost'])],
            'listing_ids' => ['array'],
            'listing_ids.*' => [Rule::exists('listings', 'id')->where('user_id', $this->user()->id)],
        ];
    }
}

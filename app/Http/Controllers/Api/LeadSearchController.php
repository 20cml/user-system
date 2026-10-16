<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadSearchController extends Controller
{
    /**
     * Search the logged-in agent's own leads by name, for the lead↔listing
     * "tag picker" (see specs/005-lead-crm/research.md).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('query', ''));
        $leadType = $request->query('lead_type');

        $leads = $request->user()->leads()
            // Seller/Landlord leads belong to the listing they're already
            // tied to (Listing::owner()) — they can't also be an "Interested
            // Lead" on a listing, so they're excluded here just like they
            // are from "My Leads" (see LeadController::index()).
            ->where(fn ($builder) => $builder->whereNotIn('type', ['seller', 'landlord'])->orWhereNull('type'))
            // A rental listing only takes Renter leads and a sale listing only
            // Buyer leads — the picker passes the matching type.
            ->when(in_array($leadType, ['buyer', 'renter'], true), fn ($builder) => $builder->where('type', $leadType))
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($builder) use ($query) {
                    $builder->where('first_name', 'like', "%{$query}%")
                        ->orWhere('last_name', 'like', "%{$query}%");
                });
            })
            ->latest()
            ->limit(10)
            ->get(['id', 'first_name', 'last_name']);

        return response()->json($leads->map(fn ($lead) => [
            'id' => $lead->id,
            'label' => $lead->name,
        ]));
    }
}

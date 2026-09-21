<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListingSearchController extends Controller
{
    /**
     * Search the logged-in agent's own listings by address or by ID, for the
     * lead↔listing "tag picker" (see specs/005-lead-crm/research.md).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('query', ''));
        $propertyType = $request->query('property_type');

        $listings = $request->user()->listings()
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($builder) use ($query) {
                    $builder->where('address_line', 'like', "%{$query}%")
                        ->orWhere('city', 'like', "%{$query}%");

                    if (ctype_digit($query)) {
                        $builder->orWhere('id', (int) $query);
                    }
                });
            })
            ->when($propertyType, fn ($builder) => $builder->where('property_type', $propertyType))
            ->latest()
            ->limit(10)
            ->get(['id', 'address_line', 'city']);

        return response()->json($listings->map(fn ($listing) => [
            'id' => $listing->id,
            'label' => "#{$listing->id} — {$listing->address_line}, {$listing->city}",
        ]));
    }
}

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

        if ($query === '') {
            return response()->json([]);
        }

        $leads = $request->user()->leads()
            ->where(function ($builder) use ($query) {
                $builder->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'first_name', 'last_name']);

        return response()->json($leads->map(fn ($lead) => [
            'id' => $lead->id,
            'label' => $lead->name,
        ]));
    }
}

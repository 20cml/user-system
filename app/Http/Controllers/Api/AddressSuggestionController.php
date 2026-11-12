<?php

namespace App\Http\Controllers\Api;

use App\Address\AddressProvider;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AddressSuggestionController extends Controller
{
    /**
     * Look up address suggestions for the given partial query using whichever
     * provider is configured. Always returns a 200 with an array (empty on
     * any failure) so the profile form never gets blocked by the service
     * being slow or unavailable.
     */
    public function __invoke(Request $request, AddressProvider $provider): JsonResponse
    {
        $query = trim((string) $request->query('query', ''));

        if ($query === '') {
            return response()->json([]);
        }

        try {
            return response()->json($provider->suggest($query));
        } catch (\Throwable $e) {
            Log::warning('Address suggestion lookup failed', ['message' => $e->getMessage()]);

            return response()->json([]);
        }
    }
}

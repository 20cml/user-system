<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AddressSuggestionController extends Controller
{
    /**
     * Look up address suggestions for the given partial query, restricted to
     * Canada and the United States. Always returns a 200 with an array (empty
     * on any failure) so the profile form never gets blocked by this service
     * being slow or unavailable.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('query', ''));

        if ($query === '' || ! config('services.geoapify.key')) {
            return response()->json([]);
        }

        try {
            $response = Http::timeout(3)->get('https://api.geoapify.com/v1/geocode/autocomplete', [
                'text' => $query,
                'filter' => 'countrycode:ca,us',
                'format' => 'json',
                'apiKey' => config('services.geoapify.key'),
            ]);

            if (! $response->successful()) {
                return response()->json([]);
            }

            $suggestions = collect($response->json('results', []))
                ->map(fn (array $result) => [
                    'street' => $result['address_line1'] ?? $result['street'] ?? '',
                    'city' => $result['city'] ?? '',
                    'region' => $result['state_code'] ?? $result['state'] ?? '',
                    'postal_code' => $result['postcode'] ?? '',
                    'country' => $result['country_code'] ? strtoupper($result['country_code']) : '',
                ])
                ->values()
                ->all();

            return response()->json($suggestions);
        } catch (\Throwable $e) {
            Log::warning('Address suggestion lookup failed', ['message' => $e->getMessage()]);

            return response()->json([]);
        }
    }
}

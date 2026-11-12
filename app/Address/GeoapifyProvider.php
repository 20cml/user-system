<?php

namespace App\Address;

use Illuminate\Support\Facades\Http;

/**
 * Real provider: Geoapify's autocomplete API, restricted to Canada and the
 * United States. Everything specific to Geoapify (URL, parameters, response
 * fields) lives in this class.
 */
class GeoapifyProvider implements AddressProvider
{
    public function suggest(string $query): array
    {
        $key = config('services.geoapify.key');

        if (! $key) {
            return [];
        }

        $response = Http::timeout(3)->get('https://api.geoapify.com/v1/geocode/autocomplete', [
            'text' => $query,
            'filter' => 'countrycode:ca,us',
            'format' => 'json',
            'apiKey' => $key,
        ]);

        if (! $response->successful()) {
            return [];
        }

        return collect($response->json('results', []))
            ->map(fn (array $result) => [
                'street' => $result['address_line1'] ?? $result['street'] ?? '',
                'city' => $result['city'] ?? '',
                'state_province' => $result['state_code'] ?? $result['state'] ?? '',
                'postal_code' => $result['postcode'] ?? '',
                'country' => $result['country_code'] ? strtoupper($result['country_code']) : '',
            ])
            ->values()
            ->all();
    }
}

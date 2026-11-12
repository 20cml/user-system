<?php

namespace App\Address;

/**
 * What the app needs from an address-lookup service. The rest of the app
 * only talks to this contract, so which service sits behind it is a
 * configuration choice (services.address.provider), not a code change.
 */
interface AddressProvider
{
    /**
     * Address suggestions for a partial query, already in the app's own shape:
     * a list of ['street', 'city', 'state_province', 'postal_code', 'country'].
     * Returns an empty array when there is nothing to suggest.
     *
     * @return array<int, array{street: string, city: string, state_province: string, postal_code: string, country: string}>
     */
    public function suggest(string $query): array;
}

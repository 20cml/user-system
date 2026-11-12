<?php

namespace App\Address;

/**
 * Simulated provider: no network calls, no API key. Returns realistic
 * stand-in addresses from a fixed list, filtered by whatever the user typed.
 */
class FakeProvider implements AddressProvider
{
    private const ADDRESSES = [
        ['street' => '100 King St W', 'city' => 'Toronto', 'state_province' => 'ON', 'postal_code' => 'M5X 1A9', 'country' => 'CA'],
        ['street' => '123 Main St', 'city' => 'Toronto', 'state_province' => 'ON', 'postal_code' => 'M5V 2T6', 'country' => 'CA'],
        ['street' => '55 Queen St E', 'city' => 'Toronto', 'state_province' => 'ON', 'postal_code' => 'M5C 1R6', 'country' => 'CA'],
        ['street' => '200 Bay St', 'city' => 'Toronto', 'state_province' => 'ON', 'postal_code' => 'M5J 2J2', 'country' => 'CA'],
        ['street' => '123 Main St W', 'city' => 'Hamilton', 'state_province' => 'ON', 'postal_code' => 'L8P 4R3', 'country' => 'CA'],
        ['street' => '1280 Main St W', 'city' => 'Hamilton', 'state_province' => 'ON', 'postal_code' => 'L8S 4L8', 'country' => 'CA'],
        ['street' => '77 James St N', 'city' => 'Hamilton', 'state_province' => 'ON', 'postal_code' => 'L8R 2K3', 'country' => 'CA'],
        ['street' => '350 Albert St', 'city' => 'Ottawa', 'state_province' => 'ON', 'postal_code' => 'K1R 1A4', 'country' => 'CA'],
        ['street' => '90 Elgin St', 'city' => 'Ottawa', 'state_province' => 'ON', 'postal_code' => 'K1P 5K4', 'country' => 'CA'],
        ['street' => '25 Dundas St W', 'city' => 'Mississauga', 'state_province' => 'ON', 'postal_code' => 'L5B 1H4', 'country' => 'CA'],
        ['street' => '4 Yonge St', 'city' => 'Oakville', 'state_province' => 'ON', 'postal_code' => 'L6J 1Y3', 'country' => 'CA'],
        ['street' => '500 Wellington St', 'city' => 'London', 'state_province' => 'ON', 'postal_code' => 'N6A 3P9', 'country' => 'CA'],
    ];

    public function suggest(string $query): array
    {
        $needle = mb_strtolower($query);

        return collect(self::ADDRESSES)
            ->filter(fn (array $address) => str_contains(mb_strtolower(implode(' ', $address)), $needle))
            ->values()
            ->all();
    }
}

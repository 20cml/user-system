<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Listing>
 */
class ListingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $country = fake()->randomElement(['CA', 'US']);

        return [
            'user_id' => User::factory(),
            'source' => 'manual',
            'listing_type' => fake()->randomElement(['sale', 'rent']),
            'property_type' => fake()->randomElement(['house', 'apartment', 'land', 'commercial']),
            'status' => 'available',
            'address_line' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state_province' => fake()->stateAbbr(),
            'postal_code' => fake()->postcode(),
            'country' => $country,
            'price' => fake()->numberBetween(50_000, 2_000_000),
            'currency' => $country === 'CA' ? 'CAD' : 'USD',
            'area_sqm' => fake()->numberBetween(40, 400),
            'description' => fake()->paragraph(),
        ];
    }
}

<?php

namespace App\Providers;

use App\Address\AddressProvider;
use App\Address\FakeProvider;
use App\Address\GeoapifyProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The active address provider is chosen by configuration
        // (ADDRESS_PROVIDER in .env), never by the code that uses it.
        $this->app->bind(AddressProvider::class, fn () => match (config('services.address.provider')) {
            'geoapify' => new GeoapifyProvider,
            'fake' => new FakeProvider,
            default => throw new \InvalidArgumentException(
                'Unknown ADDRESS_PROVIDER "'.config('services.address.provider').'". Use "geoapify" or "fake".'
            ),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

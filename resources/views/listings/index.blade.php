<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Listings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow sm:rounded-lg p-4">
                <form method="get" action="{{ route('listings.index') }}" class="flex flex-wrap items-end gap-4">
                    <div>
                        <x-input-label for="filter_status" :value="__('Status')" />
                        <select id="filter_status" name="status" class="mt-1 block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Any') }}</option>
                            <option value="available" @selected(request('status') === 'available')>{{ __('Available') }}</option>
                            <option value="pending" @selected(request('status') === 'pending')>{{ __('Pending') }}</option>
                            <option value="closed" @selected(request('status') === 'closed')>{{ __('Closed') }}</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="filter_listing_type" :value="__('Listing type')" />
                        <select id="filter_listing_type" name="listing_type" class="mt-1 block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Any') }}</option>
                            <option value="sale" @selected(request('listing_type') === 'sale')>{{ __('For sale') }}</option>
                            <option value="rent" @selected(request('listing_type') === 'rent')>{{ __('For rent') }}</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="filter_property_type" :value="__('Property type')" />
                        <select id="filter_property_type" name="property_type" class="mt-1 block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Any') }}</option>
                            <option value="house" @selected(request('property_type') === 'house')>{{ __('House') }}</option>
                            <option value="apartment" @selected(request('property_type') === 'apartment')>{{ __('Apartment') }}</option>
                            <option value="land" @selected(request('property_type') === 'land')>{{ __('Land') }}</option>
                            <option value="commercial" @selected(request('property_type') === 'commercial')>{{ __('Commercial') }}</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="min_price" :value="__('Min price')" />
                        <x-text-input id="min_price" name="min_price" type="number" step="0.01" class="mt-1 block w-32 text-sm" :value="request('min_price')" />
                    </div>

                    <div>
                        <x-input-label for="max_price" :value="__('Max price')" />
                        <x-text-input id="max_price" name="max_price" type="number" step="0.01" class="mt-1 block w-32 text-sm" :value="request('max_price')" />
                    </div>

                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
                        <a href="{{ route('listings.index') }}" class="inline-flex items-center px-4 py-2 text-xs text-gray-600 underline">{{ __('Clear') }}</a>
                    </div>
                </form>
            </div>

            <div class="flex justify-end">
                <a href="{{ route('listings.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    {{ __('Add listing') }}
                </a>
            </div>

            @forelse ($listings as $listing)
                @if ($loop->first)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @endif

                <a href="{{ route('listings.edit', $listing) }}" class="block bg-white shadow sm:rounded-lg overflow-hidden hover:shadow-md transition">
                    <div class="h-48 w-full bg-gray-100">
                        @if ($listing->photo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($listing->photo_path) }}" alt="" class="h-full w-full object-cover">
                        @else
                            <div class="h-full w-full flex items-center justify-center text-gray-400 text-sm">{{ __('No photo') }}</div>
                        @endif
                    </div>

                    <div class="p-4 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-gray-900">{{ $listing->currency }} {{ number_format($listing->price, 2) }}</span>
                            <span class="text-xs uppercase tracking-wide text-gray-500">{{ ucfirst($listing->status) }}</span>
                        </div>
                        <p class="text-sm text-gray-700">{{ $listing->address_line }}, {{ $listing->city }}</p>
                        <p class="text-xs text-gray-500">{{ ucfirst($listing->listing_type) }} &middot; {{ ucfirst($listing->property_type) }}</p>
                        <p class="text-xs text-gray-400">{{ __('Added') }} {{ $listing->created_at->format('M j, Y') }}</p>
                    </div>
                </a>

                @if ($loop->last)
                    </div>
                @endif
            @empty
                <div class="bg-white shadow sm:rounded-lg p-6 text-center text-gray-500">
                    {{ __('No listings yet.') }}
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>

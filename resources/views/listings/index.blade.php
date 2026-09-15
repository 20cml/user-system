<x-app-layout>
    <x-slot name="header">
        <div class="h-10 flex items-center justify-between">
            <h2 class="font-semibold text-base text-gray-800 leading-tight">
                {{ __('My Listings') }}
            </h2>

            <div class="flex items-center gap-2">
                <x-dropdown align="right" width="w-64" rounded="rounded-2xl" content-classes="bg-white px-4 py-3" :close-on-click="false">
                    <x-slot name="trigger">
                        <button type="button"
                                class="h-9 w-9 flex items-center justify-center rounded-full bg-white border border-gray-300 text-gray-600 hover:bg-gray-50"
                                aria-label="{{ __('Filter listings') }}"
                                title="{{ __('Filter listings') }}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" d="M4 6h16M7 12h10M10 18h4" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <form method="get" action="{{ route('listings.index') }}" class="space-y-3">
                            <div>
                                <label for="filter_status" class="block pb-1 text-xs font-medium text-gray-400">{{ __('Status') }}</label>
                                <select id="filter_status" name="status" onchange="this.form.submit()" class="block w-full border-gray-300 focus:border-gray-400 focus:ring-0 rounded-md text-[13px]">
                                    <option value="">{{ __('Any') }}</option>
                                    <option value="available" @selected(request('status') === 'available')>{{ __('Available') }}</option>
                                    <option value="pending" @selected(request('status') === 'pending')>{{ __('Pending') }}</option>
                                    <option value="closed" @selected(request('status') === 'closed')>{{ __('Closed') }}</option>
                                </select>
                            </div>

                            <div>
                                <label for="filter_listing_type" class="block pb-1 text-xs font-medium text-gray-400">{{ __('Listing type') }}</label>
                                <select id="filter_listing_type" name="listing_type" onchange="this.form.submit()" class="block w-full border-gray-300 focus:border-gray-400 focus:ring-0 rounded-md text-[13px]">
                                    <option value="">{{ __('Any') }}</option>
                                    <option value="sale" @selected(request('listing_type') === 'sale')>{{ __('For sale') }}</option>
                                    <option value="rent" @selected(request('listing_type') === 'rent')>{{ __('For rent') }}</option>
                                </select>
                            </div>

                            <div>
                                <label for="filter_property_type" class="block pb-1 text-xs font-medium text-gray-400">{{ __('Property type') }}</label>
                                <select id="filter_property_type" name="property_type" onchange="this.form.submit()" class="block w-full border-gray-300 focus:border-gray-400 focus:ring-0 rounded-md text-[13px]">
                                    <option value="">{{ __('Any') }}</option>
                                    <option value="house" @selected(request('property_type') === 'house')>{{ __('House') }}</option>
                                    <option value="apartment" @selected(request('property_type') === 'apartment')>{{ __('Apartment') }}</option>
                                    <option value="land" @selected(request('property_type') === 'land')>{{ __('Land') }}</option>
                                    <option value="commercial" @selected(request('property_type') === 'commercial')>{{ __('Commercial') }}</option>
                                </select>
                            </div>

                            <div class="flex gap-2">
                                <div>
                                    <label for="min_price" class="block pb-1 text-xs font-medium text-gray-400">{{ __('Min price') }}</label>
                                    <div class="relative">
                                        <span class="pointer-events-none absolute inset-y-0 left-2 flex items-center text-[13px] text-gray-400">$</span>
                                        <input type="text" inputmode="numeric" id="min_price" name="min_price" value="{{ request('min_price') ? number_format((float) request('min_price')) : '' }}" class="price-filter-input block w-full rounded-md border-gray-300 pl-5 text-[13px] focus:!border-red-500 focus:!ring-red-500">
                                    </div>
                                </div>
                                <div>
                                    <label for="max_price" class="block pb-1 text-xs font-medium text-gray-400">{{ __('Max price') }}</label>
                                    <div class="relative">
                                        <span class="pointer-events-none absolute inset-y-0 left-2 flex items-center text-[13px] text-gray-400">$</span>
                                        <input type="text" inputmode="numeric" id="max_price" name="max_price" value="{{ request('max_price') ? number_format((float) request('max_price')) : '' }}" class="price-filter-input block w-full rounded-md border-gray-300 pl-5 text-[13px] focus:!border-red-500 focus:!ring-red-500">
                                    </div>
                                </div>
                            </div>

                            @if (request()->hasAny(['status', 'listing_type', 'property_type', 'min_price', 'max_price']))
                                <a href="{{ route('listings.index') }}" class="block text-center text-xs text-gray-500 underline hover:text-gray-700">{{ __('Clear filters') }}</a>
                            @endif
                        </form>

                        <script>
                            (function () {
                                document.querySelectorAll('.price-filter-input').forEach(function (input) {
                                    input.addEventListener('input', function () {
                                        var digits = input.value.replace(/\D/g, '');
                                        input.value = digits ? Number(digits).toLocaleString('en-US') : '';
                                    });

                                    input.closest('form').addEventListener('submit', function () {
                                        input.value = input.value.replace(/\D/g, '');
                                    });
                                });
                            })();
                        </script>
                    </x-slot>
                </x-dropdown>

                <a href="{{ route('listings.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-300 rounded-full font-medium text-sm text-gray-800 hover:bg-gray-50">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('Add listing') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="pt-6 pb-12">
        <div class="px-8 sm:px-10 space-y-0">
            @forelse ($listings as $listing)
                @if ($loop->first)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @endif

                <a href="{{ route('listings.edit', $listing) }}" class="block bg-white shadow rounded-2xl overflow-hidden hover:shadow-md transition">
                    <div class="h-48 w-full bg-gray-100">
                        @if ($listing->photo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($listing->photo_path) }}" alt="" class="h-full w-full object-cover">
                        @else
                            <div class="h-full w-full flex items-center justify-center text-gray-600 text-sm">{{ __('No photo') }}</div>
                        @endif
                    </div>

                    <div class="p-4 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-gray-900">{{ $listing->currency }} {{ number_format($listing->price, 2) }}</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[12.1px] font-normal bg-gray-100 text-gray-700">{{ ucfirst($listing->status) }}</span>
                        </div>
                        <p class="text-sm text-gray-700">{{ $listing->address_line }}, {{ $listing->city }}</p>
                        <p class="text-xs text-gray-500">{{ ucfirst($listing->listing_type) }} &middot; {{ ucfirst($listing->property_type) }}</p>
                        <p class="text-xs text-gray-600">{{ __('Added') }} {{ $listing->created_at->format('M j, Y') }}</p>
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

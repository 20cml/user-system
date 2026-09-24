<x-app-layout>
    <x-slot name="header">
        <div class="relative top-1 -left-8 h-10 flex items-center justify-between">
            <h2 class="font-semibold text-sm text-gray-200 leading-tight">
                {{ __('My Listings') }}
            </h2>

            <div class="relative -top-2.5 mr-2 flex items-center gap-2">
                <x-dropdown align="right" width="w-64" rounded="rounded-2xl" content-classes="bg-white px-4 py-3" :close-on-click="false" :initial-open="request()->hasAny(['status', 'listing_type', 'property_type', 'min_price', 'max_price'])">
                    <x-slot name="trigger">
                        <button type="button"
                                class="h-7 w-7 flex items-center justify-center rounded-full bg-gray-700 border border-gray-400 text-gray-400 hover:bg-gray-600"
                                aria-label="{{ __('Filter listings') }}"
                                title="{{ __('Filter listings') }}">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
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
                                    <option value="condo" @selected(request('property_type') === 'condo')>{{ __('Condo') }}</option>
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

                <button type="button" x-data="" x-on:click="$dispatch('open-modal', 'add-listing')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 rounded-full font-medium text-xs text-gray-800 hover:bg-gray-50">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('Add listing') }}
                </button>
            </div>
        </div>
    </x-slot>

    <div class="pt-6 pb-4 flex-1 flex flex-col min-h-0">
        <div class="px-4 sm:px-6 flex-1 flex flex-col min-h-0">
        <div class="flex-1 min-h-0 overflow-y-auto">
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
                            <span class="font-semibold text-gray-900">{{ $listing->price !== null ? "{$listing->currency} " . number_format($listing->price, 2) : __('Price not set') }}</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[12.1px] font-normal bg-gray-100 text-gray-700">{{ ucfirst($listing->status) }}</span>
                        </div>
                        <p class="text-sm text-gray-700">{{ $listing->address_line }}, {{ $listing->city }}</p>
                        <p class="text-xs text-gray-500">{{ $listing->listing_type ? ucfirst($listing->listing_type) : __('Not set') }} &middot; {{ $listing->property_type ? ucfirst($listing->property_type) : __('Not set') }}</p>
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

            <div class="pt-3 border-t border-gray-200">
                <span class="inline-flex items-center px-3 py-1.5 rounded-full bg-gray-200 text-xs font-medium text-gray-700">
                    {{ $listings->count() }} {{ __('listings') }}
                </span>
            </div>
        </div>
    </div>

    <x-modal name="add-listing" :show="$errors->any()" focusable>
        <form method="post" action="{{ route('listings.store') }}" class="p-6 space-y-6" x-data="{ listingType: '{{ old('listing_type') }}', propertyType: '{{ old('property_type') }}' }">
            @csrf

            <h2 class="text-sm font-medium text-gray-900">
                {{ __('Add a Listing') }}
            </h2>

            <div>
                <x-input-label :value="__('Selling or renting?')" class="!text-[12.1px]" />
                <input type="hidden" name="listing_type" :value="listingType">
                <div class="mt-1 flex items-center gap-2">
                    <button type="button" @click="listingType = 'sale'" class="px-3 py-1.5 rounded-full text-[12.1px] font-medium transition" :class="listingType === 'sale' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">{{ __('Selling') }}</button>
                    <button type="button" @click="listingType = 'rent'" class="px-3 py-1.5 rounded-full text-[12.1px] font-medium transition" :class="listingType === 'rent' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">{{ __('Renting') }}</button>
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('listing_type')" />
            </div>

            <div>
                <x-input-label :value="__('Property type')" class="!text-[12.1px]" />
                <input type="hidden" name="property_type" :value="propertyType">
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <button type="button" @click="propertyType = 'condo'" class="px-3 py-1.5 rounded-full text-[12.1px] font-medium transition" :class="propertyType === 'condo' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">{{ __('Condo') }}</button>
                    <button type="button" @click="propertyType = 'house'" class="px-3 py-1.5 rounded-full text-[12.1px] font-medium transition" :class="propertyType === 'house' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">{{ __('House') }}</button>
                    <button type="button" @click="propertyType = 'land'" class="px-3 py-1.5 rounded-full text-[12.1px] font-medium transition" :class="propertyType === 'land' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">{{ __('Land') }}</button>
                    <button type="button" @click="propertyType = 'commercial'" class="px-3 py-1.5 rounded-full text-[12.1px] font-medium transition" :class="propertyType === 'commercial' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">{{ __('Commercial') }}</button>
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('property_type')" />
            </div>

            <p class="text-[12.1px] font-medium text-gray-700 pt-2 border-t border-gray-100">
                {{ __("Who's selling or renting it out?") }}
            </p>

            <div>
                <x-input-label for="owner_first_name" :value="__('First name')" class="!text-[12.1px]" />
                <x-text-input id="owner_first_name" name="owner_first_name" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('owner_first_name')" required />
                <x-input-error class="mt-2" :messages="$errors->get('owner_first_name')" />
            </div>

            <div>
                <x-input-label for="owner_last_name" :value="__('Last name')" class="!text-[12.1px]" />
                <x-text-input id="owner_last_name" name="owner_last_name" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('owner_last_name')" />
                <x-input-error class="mt-2" :messages="$errors->get('owner_last_name')" />
            </div>

            <div>
                <x-input-label for="owner_phone" :value="__('Phone')" class="!text-[12.1px]" />
                <x-text-input id="owner_phone" name="owner_phone" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('owner_phone')" />
                <x-input-error class="mt-2" :messages="$errors->get('owner_phone')" />
            </div>

            <div>
                <x-input-label for="owner_email" :value="__('Email')" class="!text-[12.1px]" />
                <x-text-input id="owner_email" name="owner_email" type="email" class="mt-1 block w-full !text-[12.1px]" :value="old('owner_email')" />
                <x-input-error class="mt-2" :messages="$errors->get('owner_email')" />
            </div>

            <p class="text-[12.1px] font-medium text-gray-700 pt-2 border-t border-gray-100">
                {{ __('Property Details') }}
            </p>

            <div>
                <x-input-label for="price" :value="__('Price')" class="!text-[12.1px]" />
                <input type="text" inputmode="numeric" id="price" name="price" value="{{ old('price') !== null ? number_format((float) old('price')) : '' }}" class="price-input mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 !text-[12.1px]">
                <x-input-error class="mt-2" :messages="$errors->get('price')" />
            </div>

            @include('listings.partials.address-fields', ['listing' => null])

            <div>
                <x-input-label for="area_sqm" :value="__('Size')" class="!text-[12.1px]" />
                <div class="relative mt-1">
                    <input type="text" inputmode="decimal" id="area_sqm" name="area_sqm" value="{{ old('area_sqm') }}" class="block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 pr-10 !text-[12.1px]">
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-[12.1px] text-gray-400">m&sup2;</span>
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('area_sqm')" />
            </div>

            <div>
                <x-input-label for="description" :value="__('Description')" class="!text-[12.1px]" />
                <textarea id="description" name="description" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm !text-[12.1px]">{{ old('description') }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('description')" />
            </div>

            <div class="flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button>{{ __('Add listing') }}</x-primary-button>
            </div>
        </form>
    </x-modal>

    <script>
        (function () {
            var priceInput = document.getElementById('price');
            var form = priceInput.closest('form');

            priceInput.addEventListener('input', function () {
                var digits = priceInput.value.replace(/\D/g, '');
                priceInput.value = digits ? Number(digits).toLocaleString('en-US') : '';
            });

            form.addEventListener('submit', function () {
                priceInput.value = priceInput.value.replace(/\D/g, '');
            });
        })();
    </script>
</x-app-layout>

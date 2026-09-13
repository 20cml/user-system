<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add a Listing') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <form method="post" action="{{ route('listings.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        @include('listings.partials.address-fields')

                        <div>
                            <x-input-label for="price" :value="__('Price')" />
                            <x-text-input id="price" name="price" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('price')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('price')" />
                        </div>

                        <div>
                            <x-input-label for="listing_type" :value="__('Listing type')" />
                            <select id="listing_type" name="listing_type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">{{ __('Select one') }}</option>
                                <option value="sale" @selected(old('listing_type') === 'sale')>{{ __('For sale') }}</option>
                                <option value="rent" @selected(old('listing_type') === 'rent')>{{ __('For rent') }}</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('listing_type')" />
                        </div>

                        <div>
                            <x-input-label for="property_type" :value="__('Property type')" />
                            <select id="property_type" name="property_type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">{{ __('Select one') }}</option>
                                <option value="house" @selected(old('property_type') === 'house')>{{ __('House') }}</option>
                                <option value="apartment" @selected(old('property_type') === 'apartment')>{{ __('Apartment') }}</option>
                                <option value="land" @selected(old('property_type') === 'land')>{{ __('Land') }}</option>
                                <option value="commercial" @selected(old('property_type') === 'commercial')>{{ __('Commercial') }}</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('property_type')" />
                        </div>

                        <div>
                            <x-input-label for="area_sqm" :value="__('Size (square meters)')" />
                            <x-text-input id="area_sqm" name="area_sqm" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('area_sqm')" />
                            <x-input-error class="mt-2" :messages="$errors->get('area_sqm')" />
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('Description')" />
                            <textarea id="description" name="description" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>

                        <div>
                            <x-input-label for="photo" :value="__('Photo')" />
                            @include('listings.partials.photo-input')
                            <x-input-error class="mt-2" :messages="$errors->get('photo')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Add listing') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

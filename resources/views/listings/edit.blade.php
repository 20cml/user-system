<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Listing') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <form method="post" action="{{ route('listings.update', $listing) }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('patch')

                        @include('listings.partials.address-fields')

                        <div>
                            <x-input-label for="price" :value="__('Price')" />
                            <x-text-input id="price" name="price" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('price', $listing->price)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('price')" />
                        </div>

                        <div>
                            <x-input-label for="listing_type" :value="__('Listing type')" />
                            <select id="listing_type" name="listing_type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="sale" @selected(old('listing_type', $listing->listing_type) === 'sale')>{{ __('For sale') }}</option>
                                <option value="rent" @selected(old('listing_type', $listing->listing_type) === 'rent')>{{ __('For rent') }}</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('listing_type')" />
                        </div>

                        <div>
                            <x-input-label for="property_type" :value="__('Property type')" />
                            <select id="property_type" name="property_type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="house" @selected(old('property_type', $listing->property_type) === 'house')>{{ __('House') }}</option>
                                <option value="apartment" @selected(old('property_type', $listing->property_type) === 'apartment')>{{ __('Apartment') }}</option>
                                <option value="land" @selected(old('property_type', $listing->property_type) === 'land')>{{ __('Land') }}</option>
                                <option value="commercial" @selected(old('property_type', $listing->property_type) === 'commercial')>{{ __('Commercial') }}</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('property_type')" />
                        </div>

                        <div>
                            <x-input-label for="status" :value="__('Status')" />
                            <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="available" @selected(old('status', $listing->status) === 'available')>{{ __('Available') }}</option>
                                <option value="pending" @selected(old('status', $listing->status) === 'pending')>{{ __('Pending') }}</option>
                                <option value="closed" @selected(old('status', $listing->status) === 'closed')>{{ __('Closed') }}</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('status')" />
                        </div>

                        <div>
                            <x-input-label for="area_sqm" :value="__('Size (square meters)')" />
                            <x-text-input id="area_sqm" name="area_sqm" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('area_sqm', $listing->area_sqm)" />
                            <x-input-error class="mt-2" :messages="$errors->get('area_sqm')" />
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('Description')" />
                            <textarea id="description" name="description" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $listing->description) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>

                        <div>
                            <x-input-label :value="__('Photo')" />

                            @if ($listing->photo_path)
                                <div class="mt-2 relative inline-block" data-photo-row>
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($listing->photo_path) }}" alt="" class="h-24 w-24 object-cover rounded">
                                    <button
                                        type="button"
                                        class="photo-remove-button absolute -top-2 -right-2 h-5 w-5 flex items-center justify-center rounded-full bg-red-600 text-white text-xs leading-none hover:bg-red-700"
                                        title="{{ __('Remove this photo') }}"
                                    >&times;</button>
                                    <input type="checkbox" name="remove_photo" value="1" class="hidden">
                                </div>
                                <x-input-label for="photo" :value="__('Replace photo')" class="mt-3" />
                            @endif

                            @include('listings.partials.photo-input')
                            <x-input-error class="mt-2" :messages="$errors->get('photo')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Save') }}</x-primary-button>
                        </div>
                    </form>

                    <form
                        method="post"
                        action="{{ route('listings.destroy', $listing) }}"
                        class="mt-6 pt-6 border-t border-gray-200"
                        onsubmit="return confirm('{{ __('Delete this listing? This cannot be undone.') }}');"
                    >
                        @csrf
                        @method('delete')
                        <button type="submit" class="text-sm text-red-600 underline hover:text-red-800">
                            {{ __('Delete this listing') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var button = document.querySelector('.photo-remove-button');

            if (button) {
                button.addEventListener('click', function () {
                    var row = button.closest('[data-photo-row]');
                    row.querySelector('input[type="checkbox"]').checked = true;
                    row.style.display = 'none';
                });
            }
        })();
    </script>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="relative top-1 -left-8 h-10 flex items-center">
            <h2 class="font-semibold text-sm text-gray-200 leading-tight">
                {{ __('Edit Listing') }}
            </h2>
        </div>
    </x-slot>

    <div class="p-3 sm:p-4 min-h-full">
        <div class="relative p-3 sm:p-4 bg-white shadow sm:rounded-lg min-h-full flex flex-col">
                <div class="max-w-xl">
                    <form method="post" action="{{ route('listings.update', $listing) }}" enctype="multipart/form-data">
                        @php
                            $listingTypeOptions = ['sale' => __('For sale'), 'rent' => __('For rent')];
                            $propertyTypeOptions = ['house' => __('House'), 'condo' => __('Condo'), 'land' => __('Land'), 'commercial' => __('Commercial')];
                            $listingStatusOptions = ['available' => __('Available'), 'pending' => __('Pending'), 'closed' => __('Closed')];
                        @endphp
                        <div
                            class="flex items-center justify-start gap-4 pb-4 mb-4 border-b border-gray-200"
                            x-data='{
                                listingTypeVal: "{{ old('listing_type', $listing->listing_type) }}",
                                listingTypeOpen: false,
                                listingTypeLabels: @json($listingTypeOptions),
                                propertyTypeVal: "{{ old('property_type', $listing->property_type) }}",
                                propertyTypeOpen: false,
                                propertyTypeLabels: @json($propertyTypeOptions),
                                listingStatusVal: "{{ old('status', $listing->status) }}",
                                listingStatusOpen: false,
                                listingStatusLabels: @json($listingStatusOptions)
                            }'
                        >
                            <div class="flex items-center gap-0.5">
                                <span class="text-[12.1px] font-bold text-gray-900">$</span>
                                <input type="text" inputmode="numeric" id="price" name="price" value="{{ number_format((float) old('price', $listing->price)) }}" required class="price-input font-bold text-gray-900 text-[12.1px] leading-[1.5] border-0 border-b border-transparent hover:border-gray-300 focus:border-gray-400 focus:ring-0 p-0 w-20 bg-transparent">
                            </div>

                            <input type="hidden" name="listing_type" :value="listingTypeVal">
                            <div class="relative" @click.outside="listingTypeOpen = false">
                                <button type="button" @click="listingTypeOpen = !listingTypeOpen" class="flex items-center gap-1 text-[12.1px] text-gray-700 hover:text-gray-900 cursor-pointer">
                                    <span class="font-bold text-gray-900" x-text="listingTypeLabels[listingTypeVal]"></span>
                                    <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>

                                <div x-show="listingTypeOpen" x-transition class="absolute left-0 top-full mt-1 z-20 w-36 bg-white border border-gray-200 rounded-xl shadow-lg py-1" style="display: none;">
                                    @foreach ($listingTypeOptions as $value => $label)
                                        <button type="button" @click="listingTypeVal = '{{ $value }}'; listingTypeOpen = false; $nextTick(() => $el.closest('form').submit())" class="block w-full text-left px-3 py-1.5 text-[12.1px] text-gray-700 hover:bg-gray-50" :class="listingTypeVal === '{{ $value }}' ? 'font-semibold text-gray-900' : ''">{{ $label }}</button>
                                    @endforeach
                                </div>
                            </div>

                            <input type="hidden" name="property_type" :value="propertyTypeVal">
                            <div class="relative" @click.outside="propertyTypeOpen = false">
                                <button type="button" @click="propertyTypeOpen = !propertyTypeOpen" class="flex items-center gap-1 text-[12.1px] text-gray-700 hover:text-gray-900 cursor-pointer">
                                    <span class="font-bold text-gray-900" x-text="propertyTypeLabels[propertyTypeVal]"></span>
                                    <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>

                                <div x-show="propertyTypeOpen" x-transition class="absolute left-0 top-full mt-1 z-20 w-36 bg-white border border-gray-200 rounded-xl shadow-lg py-1" style="display: none;">
                                    @foreach ($propertyTypeOptions as $value => $label)
                                        <button type="button" @click="propertyTypeVal = '{{ $value }}'; propertyTypeOpen = false; $nextTick(() => $el.closest('form').submit())" class="block w-full text-left px-3 py-1.5 text-[12.1px] text-gray-700 hover:bg-gray-50" :class="propertyTypeVal === '{{ $value }}' ? 'font-semibold text-gray-900' : ''">{{ $label }}</button>
                                    @endforeach
                                </div>
                            </div>

                            <input type="hidden" name="status" :value="listingStatusVal">
                            <div class="relative" @click.outside="listingStatusOpen = false">
                                <button type="button" @click="listingStatusOpen = !listingStatusOpen" class="flex items-center gap-1 text-[12.1px] text-gray-700 hover:text-gray-900 cursor-pointer">
                                    <span class="font-bold text-gray-900" x-text="listingStatusLabels[listingStatusVal]"></span>
                                    <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>

                                <div x-show="listingStatusOpen" x-transition class="absolute left-0 top-full mt-1 z-20 w-36 bg-white border border-gray-200 rounded-xl shadow-lg py-1" style="display: none;">
                                    @foreach ($listingStatusOptions as $value => $label)
                                        <button type="button" @click="listingStatusVal = '{{ $value }}'; listingStatusOpen = false; $nextTick(() => $el.closest('form').submit())" class="block w-full text-left px-3 py-1.5 text-[12.1px] text-gray-700 hover:bg-gray-50" :class="listingStatusVal === '{{ $value }}' ? 'font-semibold text-gray-900' : ''">{{ $label }}</button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <x-input-error class="mb-2" :messages="$errors->get('price')" />
                        <x-input-error class="mb-2" :messages="$errors->get('listing_type')" />
                        <x-input-error class="mb-2" :messages="$errors->get('property_type')" />
                        <x-input-error class="mb-2" :messages="$errors->get('status')" />

                        @csrf
                        @method('patch')

                        <div x-data="{ interestedOpen: false, detailsOpen: false, photoOpen: false }">
                            {{-- tree trunk: every node hangs off this single vertical line --}}
                            <div class="border-l-2 border-gray-200 ml-2">
                                {{-- Photo node --}}
                                <div class="pl-6 -ml-px border-l-2 border-transparent py-3">
                                    <div class="flex items-start gap-6">
                                        <button type="button" @click="photoOpen = !photoOpen" class="shrink-0 w-36 flex items-center gap-2 text-[12.1px] font-medium" :class="photoOpen ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 16.5V6a1.5 1.5 0 011.5-1.5h15A1.5 1.5 0 0121 6v10.5a1.5 1.5 0 01-1.5 1.5h-15A1.5 1.5 0 013 16.5zm10.5-8.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                            </svg>
                                            {{ __('Photo') }}
                                            <svg class="h-3 w-3 transition-transform" :class="photoOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>

                                        <div x-show="photoOpen" class="flex-1 max-w-sm border-l border-gray-100 pl-6">
                                            <div class="border-l-2 border-gray-200 pl-6 space-y-4">
                                                @if ($listing->photo_path)
                                                    <div class="relative inline-block" data-photo-row>
                                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($listing->photo_path) }}" alt="" class="h-24 w-24 object-cover rounded">
                                                        <button
                                                            type="button"
                                                            class="photo-remove-button absolute -top-2 -right-2 h-7 w-7 flex items-center justify-center rounded-full bg-red-600 text-white text-xs leading-none hover:bg-red-700"
                                                            title="{{ __('Remove this photo') }}"
                                                            aria-label="{{ __('Remove this photo') }}"
                                                        >&times;</button>
                                                        <input type="checkbox" name="remove_photo" value="1" class="hidden">
                                                    </div>
                                                    <x-input-label for="photo" :value="__('Replace photo')" class="!text-[12.1px]" />
                                                @endif

                                                @include('listings.partials.photo-input')
                                                <x-input-error class="mt-2" :messages="$errors->get('photo')" />

                                                <x-primary-button>{{ __('Save') }}</x-primary-button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Interested Leads node --}}
                                <div class="pl-6 -ml-px border-l-2 border-transparent py-3">
                                    <div class="flex items-start gap-6">
                                        <button type="button" @click="interestedOpen = !interestedOpen" class="shrink-0 w-36 flex items-center gap-2 text-[12.1px] font-medium" :class="interestedOpen ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.169.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                                            </svg>
                                            {{ __('Interested Leads') }}
                                            <svg class="h-3 w-3 transition-transform" :class="interestedOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>

                                        <div x-show="interestedOpen" class="flex-1 max-w-sm border-l border-gray-100 pl-6">
                                            <div class="border-l-2 border-gray-200 pl-6 space-y-4">
                                                @include('partials.tag-picker', [
                                                    'name' => 'lead_ids',
                                                    'searchUrl' => route('leads.search'),
                                                    'placeholder' => __('Search by lead name...'),
                                                    'selected' => $listing->leads->map(fn ($lead) => [
                                                        'id' => $lead->id,
                                                        'label' => $lead->name,
                                                    ]),
                                                ])
                                                <x-input-error class="mt-2" :messages="$errors->get('lead_ids')" />

                                                <x-primary-button>{{ __('Save') }}</x-primary-button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Property Details node --}}
                                <div class="pl-6 -ml-px border-l-2 border-transparent py-3">
                                    <div class="flex items-start gap-6">
                                        <button type="button" @click="detailsOpen = !detailsOpen" class="shrink-0 w-36 flex items-center gap-2 text-[12.1px] font-medium" :class="detailsOpen ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                            </svg>
                                            {{ __('Property Details') }}
                                            <svg class="h-3 w-3 transition-transform" :class="detailsOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>

                                        <div x-show="detailsOpen" class="flex-1 max-w-sm border-l border-gray-100 pl-6">
                                            <div class="border-l-2 border-gray-200 pl-6 space-y-4">
                                                @include('listings.partials.address-fields')

                                                <div>
                                                    <x-input-label for="area_sqm" :value="__('Size')" class="!text-[12.1px]" />
                                                    <div class="relative mt-1">
                                                        <input type="text" inputmode="decimal" id="area_sqm" name="area_sqm" value="{{ old('area_sqm', $listing->area_sqm) }}" class="block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 pr-10 !text-[12.1px]">
                                                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-[12.1px] text-gray-400">m&sup2;</span>
                                                    </div>
                                                    <x-input-error class="mt-2" :messages="$errors->get('area_sqm')" />
                                                </div>

                                                <div>
                                                    <x-input-label for="description" :value="__('Description')" class="!text-[12.1px]" />
                                                    <textarea id="description" name="description" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm !text-[12.1px]">{{ old('description', $listing->description) }}</textarea>
                                                    <x-input-error class="mt-2" :messages="$errors->get('description')" />
                                                </div>

                                                <x-primary-button>{{ __('Save') }}</x-primary-button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <form
                    method="post"
                    action="{{ route('listings.destroy', $listing) }}"
                    class="mt-auto pt-6 border-t border-gray-200"
                    onsubmit="return confirm('{{ __('Delete this listing? This cannot be undone.') }}');"
                >
                    @csrf
                    @method('delete')
                    <button type="submit" class="text-[12.1px] text-red-600 underline hover:text-red-800">
                        {{ __('Delete this listing') }}
                    </button>
                </form>
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

            var priceInput = document.getElementById('price');
            var priceLastSaved = priceInput.value;

            priceInput.addEventListener('input', function () {
                var digits = priceInput.value.replace(/\D/g, '');
                priceInput.value = digits ? Number(digits).toLocaleString('en-US') : '';
            });

            priceInput.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter') {
                    return;
                }

                event.preventDefault();

                if (priceInput.value === priceLastSaved) {
                    return;
                }

                priceLastSaved = priceInput.value;
                // form.submit() called via JS doesn't fire the form's 'submit' event,
                // so the comma-stripping below has to happen here, not just there.
                priceInput.value = priceInput.value.replace(/\D/g, '');
                priceInput.closest('form').submit();
            });

            priceInput.closest('form').addEventListener('submit', function () {
                priceInput.value = priceInput.value.replace(/\D/g, '');
            });
        })();
    </script>
</x-app-layout>

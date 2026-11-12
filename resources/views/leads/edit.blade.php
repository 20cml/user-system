<x-app-layout>
    <x-slot name="header">
        <div class="relative top-1 -left-8 h-10 flex items-center">
            <h2 class="font-semibold text-sm text-gray-200 leading-tight">
                {{ $lead->name }} {{ __('Profile') }}
            </h2>
        </div>
    </x-slot>

    <div class="p-3 sm:p-4 min-h-full">
        <div class="relative p-3 sm:p-4 bg-white shadow sm:rounded-lg min-h-full flex flex-col">
                <div x-data="{ notesOpen: false }">
                    <x-side-tab open="notesOpen" :label="__('Notes')">
                        @include('partials.notes-panel', ['subject' => $lead, 'routePrefix' => 'leads'])
                    </x-side-tab>
                </div>

                <div :class="(personalOpen || listingsOpen || documentsOpen) ? 'max-w-3xl' : 'max-w-sm'" class="transition-[max-width] duration-300" x-data="{ personalOpen: {{ in_array('personal', (array) request('open', [])) ? 'true' : 'false' }}, listingsOpen: {{ (in_array('listings', (array) request('open', [])) || $lead->needsListingNarrowed() || $lead->blockingUnderContractLead()) ? 'true' : 'false' }}, documentsOpen: {{ in_array('documents', (array) request('open', [])) ? 'true' : 'false' }} }">

                    {{-- root node: type/status --}}
                    <div class="flex items-center justify-start gap-4 pb-4 mb-4 border-b border-gray-200">
                        @php
                            $typeLabels = ['' => __('Not set'), 'buyer' => __('Buyer'), 'renter' => __('Renter'), 'seller' => __('Seller'), 'landlord' => __('Landlord')];
                            // A Seller/Landlord owns listings; only Buyer/Renter have "interested" listings.
                            $isOwner = in_array($lead->type, ['seller', 'landlord'], true);
                            $statusOptions = $lead->stageLabels() + ['lost' => __('Lost')];
                        @endphp
                        <div class="flex items-center gap-4 shrink-0">
                            <div
                                x-data='{
                                    typeVal: "{{ old('type', $lead->type) }}",
                                    typeOpen: false,
                                    typeLabels: @json($typeLabels)
                                }'
                            >
                                <input type="hidden" form="lead-details-form" name="type" :value="typeVal">
                                <div class="relative" @click.outside="typeOpen = false">
                                    <button type="button" @click="typeOpen = !typeOpen" class="flex items-center gap-1 text-[12.1px] text-gray-700 hover:text-gray-900 cursor-pointer">
                                        <span :class="typeVal ? 'font-bold' : 'font-normal'" x-text="typeLabels[typeVal]"></span>
                                        <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>

                                    <div x-show="typeOpen" x-transition class="absolute left-0 top-full mt-1 z-20 w-36 bg-white border border-gray-200 rounded-xl shadow-lg py-1" style="display: none;">
                                        @foreach ($typeLabels as $value => $label)
                                            <button type="button" @click="typeVal = '{{ $value }}'; typeOpen = false; $nextTick(() => document.getElementById('lead-details-form').submit())" class="block w-full text-left px-3 py-1.5 text-[12.1px] text-gray-700 hover:bg-gray-50" :class="typeVal === '{{ $value }}' ? 'font-semibold text-gray-900' : ''">{{ $label }}</button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[12.1px] font-medium bg-gray-700 text-white">{{ $statusOptions[$lead->status] ?? ucfirst($lead->status) }}</span>
                        </div>
                    </div>
                    <x-input-error class="mb-2" :messages="$errors->get('type')" />
                    <x-input-error class="mb-2" :messages="$errors->get('delete')" />
                    <x-input-error class="mb-2" :messages="$errors->get('status')" />

                    {{-- tree trunk: every node hangs off this single vertical line --}}
                    <div class="border-l-2 border-gray-200 ml-2">
                        <form id="lead-details-form" method="post" action="{{ route('leads.update', $lead) }}">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="open_branches" :value="[personalOpen && 'personal', listingsOpen && 'listings', documentsOpen && 'documents'].filter(Boolean).join(',')">

                            @include('leads.partials.personal-info-branch', ['lead' => $lead])

                            {{-- Listings node --}}
                            <div class="tree-indent -ml-px border-l-2 border-transparent py-3">
                                <div class="flex items-start tree-gap">
                                    <button type="button" @click="listingsOpen = !listingsOpen" class="shrink-0 tree-title flex items-center gap-2 text-[12.1px] font-medium" :class="listingsOpen ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                        </svg>
                                        {{ __('Property Type') }}
                                        <svg class="h-3 w-3 transition-transform" :class="listingsOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>

                                    <div x-show="listingsOpen" class="flex-1 max-w-2xl border-l border-gray-100 tree-indent" x-data="{ propertyType: '{{ old('property_type', $lead->property_type) }}' || null }">
                                        <p class="text-[12.1px] text-gray-500 mb-2">{{ match ($lead->type) { 'renter' => __('What are they renting?'), 'seller' => __('What are they selling?'), 'landlord' => __('What are they renting out?'), default => __('What are they buying?') } }}</p>

                                        <div class="flex items-center tree-gap">
                                            {{-- Property type branch --}}
                                            <div class="border-l-2 border-gray-200 tree-indent">
                                                <input type="hidden" form="lead-details-form" name="property_type" :value="propertyType">
                                                <div class="flex flex-col items-start gap-2">
                                                    <button type="button" @click="propertyType = (propertyType === 'condo' ? null : 'condo')" class="px-3 py-1.5 rounded-full text-[12.1px] font-medium transition" :class="propertyType === 'condo' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">{{ __('Condo') }}</button>
                                                    <button type="button" @click="propertyType = (propertyType === 'house' ? null : 'house')" class="px-3 py-1.5 rounded-full text-[12.1px] font-medium transition" :class="propertyType === 'house' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">{{ __('House') }}</button>
                                                    <button type="button" @click="propertyType = (propertyType === 'land' ? null : 'land')" class="px-3 py-1.5 rounded-full text-[12.1px] font-medium transition" :class="propertyType === 'land' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">{{ __('Land') }}</button>
                                                    <button type="button" @click="propertyType = (propertyType === 'commercial' ? null : 'commercial')" class="px-3 py-1.5 rounded-full text-[12.1px] font-medium transition" :class="propertyType === 'commercial' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">{{ __('Commercial') }}</button>
                                                </div>
                                            </div>

                                            {{-- Property search branch, only appears once a type is picked, to the right of the type branch --}}
                                            <div x-show="propertyType" x-transition class="border-l border-gray-100 tree-indent">
                                                <div class="border-l-2 border-gray-200 tree-indent w-64">
                                                    @if ($isOwner)
                                                        <p class="text-[12.1px] font-medium text-gray-700 mb-1">{{ __('Their listings') }}</p>
                                                        @forelse ($lead->ownedListings as $ownedListing)
                                                            <a href="{{ route('listings.edit', $ownedListing) }}" class="block text-[12.1px] text-gray-700 underline hover:text-gray-900">#{{ $ownedListing->id }} — {{ $ownedListing->title() }}</a>
                                                        @empty
                                                            <p class="text-[12.1px] text-gray-500">{{ __('Not the owner of any listing yet.') }}</p>
                                                        @endforelse
                                                    @else
                                                    @php
                                                        $listingTypeFilter = match ($lead->type) {
                                                            'renter' => 'rent',
                                                            'buyer', 'seller' => 'sale',
                                                            default => '',
                                                        };
                                                    @endphp
                                                    @include('partials.tag-picker', [
                                                        'name' => 'listing_ids',
                                                        'searchUrl' => route('listings.search'),
                                                        'placeholder' => __('Search by address or listing ID...'),
                                                        'selected' => $lead->listings->map(fn ($listing) => [
                                                            'id' => $listing->id,
                                                            'label' => "#{$listing->id} — {$listing->title()}",
                                                        ]),
                                                        'extraParamsExpr' => "'property_type=' + (propertyType || '') + '&listing_type=" . $listingTypeFilter . "'",
                                                    ])
                                                    <x-input-error class="mt-2" :messages="$errors->get('listing_ids')" />
                                                    @endif

                                                    <div class="mt-4">
                                                        <x-save-button>{{ __('Save') }}</x-save-button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @include('leads.partials.documents-branch', ['lead' => $lead])
                        </form>


                    </div>
                </div>

                <form
                    method="post"
                    action="{{ route('leads.destroy', $lead) }}"
                    class="mt-auto pt-6 border-t border-gray-200"
                    onsubmit="return confirm('{{ __('Delete this lead? This cannot be undone.') }}');"
                >
                    @csrf
                    @method('delete')
                    <button type="submit" class="text-[12.1px] text-red-600 underline hover:text-red-800">
                        {{ __('Delete this lead') }}
                    </button>
                </form>

                {{-- floating chat tab, anchored to the white card --}}
                <div x-data="{ chatOpen: false }" class="absolute bottom-4 right-4 z-40 flex flex-col items-end">
                    <div x-show="chatOpen" x-transition class="mb-2 w-64 bg-white border border-gray-200 rounded-lg shadow-lg p-4 text-[12.1px] text-gray-400">
                        {{ __('Coming soon.') }}
                    </div>

                    <button type="button" @click="chatOpen = !chatOpen" class="h-8 w-8 flex items-center justify-center rounded-full bg-gray-700 text-white shadow-lg hover:bg-gray-600" aria-label="{{ __('Chat') }}" title="{{ __('Chat') }}">
                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <x-modal name="narrow-listing-warning" :show="$lead->needsListingNarrowed()" focusable>
            <div class="p-6">
                @php $targetStageLabel = $lead->targetStageLabelOnceListingNarrowed(); @endphp
                <h2 class="text-sm font-medium text-gray-900">
                    {{ __('This lead needs one listing') }}
                </h2>

                <p class="mt-2 text-[12.1px] text-gray-600">
                    @if ($lead->listings->isEmpty())
                        {{ __('This lead is ready to move to :stage — link the listing it\'s for.', ['stage' => $targetStageLabel]) }}
                    @else
                        {{ __('This lead has :count listings linked — remove all but the one it\'s for to move it to :stage.', ['count' => $lead->listings->count(), 'stage' => $targetStageLabel]) }}
                    @endif
                </p>

                <div class="mt-6 flex justify-end">
                    <x-save-button x-on:click="$dispatch('close')">
                        {{ __('Got it') }}
                    </x-save-button>
                </div>
            </div>
        </x-modal>

        <x-modal name="under-contract-conflict-warning" :show="(bool) $lead->blockingUnderContractLead()" focusable>
            <div class="p-6">
                @php $blockingLead = $lead->blockingUnderContractLead(); @endphp
                <h2 class="text-sm font-medium text-gray-900">
                    {{ __('This listing is already under contract') }}
                </h2>

                <p class="mt-2 text-[12.1px] text-gray-600">
                    {{ __(':blockingLead already has this listing under contract — resolve that before :lead can move there too.', ['blockingLead' => $blockingLead?->name, 'lead' => $lead->name]) }}
                </p>

                <div class="mt-6 flex justify-end">
                    <x-save-button x-on:click="$dispatch('close')">
                        {{ __('Got it') }}
                    </x-save-button>
                </div>
            </div>
        </x-modal>
</x-app-layout>

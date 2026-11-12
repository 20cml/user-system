<x-app-layout>
    <x-slot name="header">
        <div class="relative top-1 -left-8 h-10 flex items-center">
            <h2 class="font-semibold text-sm text-gray-200 leading-tight">
                #{{ $listing->id }} — {{ $listing->title() }}
            </h2>
        </div>
    </x-slot>

    <div class="p-3 sm:p-4 min-h-full">
        <div class="relative p-3 sm:p-4 bg-white shadow sm:rounded-lg min-h-full flex flex-col">
                <div>
                    @php
                        $listingTypeOptions = ['' => __('Not set'), 'sale' => __('For sale'), 'rent' => __('For rent')];
                        $propertyTypeOptions = ['' => __('Not set'), 'house' => __('House'), 'condo' => __('Condo'), 'land' => __('Land'), 'commercial' => __('Commercial')];
                        $listingStatusOptions = ['available' => __('Available'), 'pending' => __('Pending'), 'closed' => __('Closed')];
                        $owner = $listing->owner;
                        $ownerStatusOptions = $owner ? $owner->stageLabels() + ['lost' => __('Lost')] : [];
                        $buyer = $listing->buyer;
                        $buyerStatusOptions = $buyer ? $buyer->stageLabels() + ['lost' => __('Lost')] : [];
                    @endphp
                    <div
                        :class="(ownerProfileOpen || buyerProfileOpen) ? 'max-w-6xl' : (detailsOpen ? 'max-w-3xl' : 'max-w-xl')"
                        class="flex-1 min-w-0 transition-[max-width] duration-300"
                        x-data="{
                            listingTypeVal: '{{ old('listing_type', $listing->listing_type) }}',
                            listingTypeOpen: false,
                            listingTypeLabels: {{ Illuminate\Support\Js::from($listingTypeOptions) }},
                            propertyTypeVal: '{{ old('property_type', $listing->property_type) }}',
                            propertyTypeOpen: false,
                            propertyTypeLabels: {{ Illuminate\Support\Js::from($propertyTypeOptions) }},
                            ownerOpen: {{ in_array('owner', (array) request('open', [])) ? 'true' : 'false' }},
                            ownerProfileOpen: {{ in_array('owner-profile', (array) request('open', [])) ? 'true' : 'false' }},
                            buyerOpen: {{ in_array('buyer', (array) request('open', [])) ? 'true' : 'false' }},
                            buyerProfileOpen: {{ in_array('buyer-profile', (array) request('open', [])) ? 'true' : 'false' }},
                            buyerPersonalOpen: {{ in_array('buyer-personal', (array) request('open', [])) ? 'true' : 'false' }},
                            buyerDocumentsOpen: {{ in_array('buyer-documents', (array) request('open', [])) ? 'true' : 'false' }},
                            buyerStatusOpen: {{ in_array('buyer-status', (array) request('open', [])) ? 'true' : 'false' }},
                            personalOpen: {{ in_array('personal', (array) request('open', [])) ? 'true' : 'false' }},
                            documentsOpen: {{ in_array('documents', (array) request('open', [])) ? 'true' : 'false' }},
                            statusOpen: {{ in_array('status', (array) request('open', [])) ? 'true' : 'false' }},
                            interestedOpen: {{ in_array('interested', (array) request('open', [])) ? 'true' : 'false' }},
                            detailsOpen: {{ in_array('details', (array) request('open', [])) ? 'true' : 'false' }},
                            photosOpen: {{ (in_array('photos', (array) request('open', [])) || $errors->has('photos') || $errors->has('photos.*')) ? 'true' : 'false' }},
                            notesOpen: false,
                            openBranches() {
                                return [
                                    this.ownerOpen && 'owner', this.ownerProfileOpen && 'owner-profile',
                                    this.buyerOpen && 'buyer', this.buyerProfileOpen && 'buyer-profile', this.buyerPersonalOpen && 'buyer-personal',
                                    this.buyerDocumentsOpen && 'buyer-documents', this.buyerStatusOpen && 'buyer-status',
                                    this.personalOpen && 'personal', this.documentsOpen && 'documents', this.statusOpen && 'status',
                                    this.interestedOpen && 'interested', this.detailsOpen && 'details', this.photosOpen && 'photos',
                                ].filter(Boolean).join(',');
                            },
                        }"
                    >
                        {{-- Notes and Photos: red tabs on the card's right edge, panels slide out over the page --}}
                        <x-side-tab open="notesOpen" :label="__('Notes')">
                            @include('partials.notes-panel', ['subject' => $listing, 'routePrefix' => 'listings'])
                        </x-side-tab>

                        {{-- up to 6 photos, with an obvious spot to add more. Picking files saves right away;
                             the × on a photo removes it. --}}
                        <x-side-tab open="photosOpen" :label="__('Photos')" top="top-28" panel="w-[26rem] max-w-[calc(100%-3rem)] max-h-[calc(100%-2rem)] overflow-y-auto bg-white border-gray-200 p-3">
                            <p class="mb-2 text-[12.1px] text-gray-400">{{ $listing->photos->count() }}/{{ \App\Models\Listing::MAX_PHOTOS }}</p>

                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($listing->photos as $photo)
                                    <div class="relative group">
                                        <img src="{{ $photo->url() }}" alt="{{ __('Property photo') }}" class="h-36 w-full object-cover rounded-lg shadow">
                                        <button type="button" data-remove-photo="{{ $photo->id }}" class="absolute top-1 right-1 h-5 w-5 hidden group-hover:flex items-center justify-center rounded-full bg-gray-900/70 text-white text-xs leading-none hover:bg-red-600" title="{{ __('Remove this photo') }}" aria-label="{{ __('Remove this photo') }}">&times;</button>
                                    </div>
                                @endforeach

                                {{-- every free slot is an empty frame; clicking any of them opens the file picker --}}
                                @for ($slot = $listing->photos->count(); $slot < \App\Models\Listing::MAX_PHOTOS; $slot++)
                                    <label for="photos" class="h-36 flex flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-gray-300 text-center cursor-pointer text-[12.1px] text-gray-500 hover:border-gray-400 hover:bg-gray-50 transition">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A1.5 1.5 0 0021.75 19.5V4.5A1.5 1.5 0 0020.25 3H3.75A1.5 1.5 0 002.25 4.5v15A1.5 1.5 0 003.75 21zM9 8.625a1.125 1.125 0 11-2.25 0 1.125 1.125 0 012.25 0z" />
                                        </svg>
                                        @if ($slot === $listing->photos->count())
                                            {{ __('Add photos') }}
                                        @endif
                                    </label>
                                @endfor
                            </div>

                            {{-- one file input behind all the empty frames; picking files saves right away --}}
                            @if ($listing->photos->count() < \App\Models\Listing::MAX_PHOTOS)
                                <input type="file" id="photos" form="listing-form" name="photos[]" multiple accept="image/*" class="sr-only" onchange="document.getElementById('listing-form').submit()">
                            @endif

                            {{-- photos.* errors come back nested per file, so flatten them --}}
                            <x-input-error class="mt-2" :messages="collect([...$errors->get('photos'), ...$errors->get('photos.*')])->flatten()->unique()->all()" />
                        </x-side-tab>

                        <div class="flex items-center justify-start gap-4 pb-4 mb-4 border-b border-gray-200">
                            <div class="flex items-center gap-0.5">
                                <span class="text-[12.1px] font-bold text-gray-900">$</span>
                                <input type="text" inputmode="numeric" id="price" form="listing-form" name="price" value="{{ old('price', $listing->price) !== null ? number_format((float) old('price', $listing->price)) : '' }}" placeholder="{{ __('Not set') }}" class="price-input font-bold text-gray-900 text-[12.1px] leading-[1.5] border-0 border-b border-transparent hover:border-gray-300 focus:border-gray-400 focus:ring-0 p-0 w-20 bg-transparent">
                            </div>

                            <input type="hidden" form="listing-form" name="listing_type" :value="listingTypeVal">
                            <div class="relative" @click.outside="listingTypeOpen = false">
                                <button type="button" @click="listingTypeOpen = !listingTypeOpen" class="flex items-center gap-1 text-[12.1px] text-gray-700 hover:text-gray-900 cursor-pointer">
                                    <span class="font-bold text-gray-900" x-text="listingTypeLabels[listingTypeVal]"></span>
                                    <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>

                                <div x-show="listingTypeOpen" x-transition class="absolute left-0 top-full mt-1 z-20 w-36 bg-white border border-gray-200 rounded-xl shadow-lg py-1" style="display: none;">
                                    @foreach ($listingTypeOptions as $value => $label)
                                        <button type="button" @click="listingTypeVal = '{{ $value }}'; listingTypeOpen = false; $nextTick(() => document.getElementById('listing-form').submit())" class="block w-full text-left px-3 py-1.5 text-[12.1px] text-gray-700 hover:bg-gray-50" :class="listingTypeVal === '{{ $value }}' ? 'font-semibold text-gray-900' : ''">{{ $label }}</button>
                                    @endforeach
                                </div>
                            </div>

                            <input type="hidden" form="listing-form" name="property_type" :value="propertyTypeVal">
                            <div class="relative" @click.outside="propertyTypeOpen = false">
                                <button type="button" @click="propertyTypeOpen = !propertyTypeOpen" class="flex items-center gap-1 text-[12.1px] text-gray-700 hover:text-gray-900 cursor-pointer">
                                    <span class="font-bold text-gray-900" x-text="propertyTypeLabels[propertyTypeVal]"></span>
                                    <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>

                                <div x-show="propertyTypeOpen" x-transition class="absolute left-0 top-full mt-1 z-20 w-36 bg-white border border-gray-200 rounded-xl shadow-lg py-1" style="display: none;">
                                    @foreach ($propertyTypeOptions as $value => $label)
                                        <button type="button" @click="propertyTypeVal = '{{ $value }}'; propertyTypeOpen = false; $nextTick(() => document.getElementById('listing-form').submit())" class="block w-full text-left px-3 py-1.5 text-[12.1px] text-gray-700 hover:bg-gray-50" :class="propertyTypeVal === '{{ $value }}' ? 'font-semibold text-gray-900' : ''">{{ $label }}</button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Interested leads: everyone who's looking at this listing; the buyer is picked from this list --}}
                            <div class="relative" @click.outside="interestedOpen = false">
                                <button type="button" @click="interestedOpen = !interestedOpen" class="flex items-center gap-1 text-[12.1px] text-gray-700 hover:text-gray-900 cursor-pointer">
                                    <span class="font-bold text-gray-900">{{ __('Interested') }} ({{ $listing->leads->count() }})</span>
                                    <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>

                                <div x-show="interestedOpen" x-transition class="absolute left-0 top-full mt-1 z-30 w-72 bg-white border border-gray-200 rounded-xl shadow-lg p-4 space-y-3" style="display: none;">
                                    @include('partials.tag-picker', [
                                        'name' => 'lead_ids',
                                        'form' => 'listing-form',
                                        'searchUrl' => route('leads.search'),
                                        'placeholder' => __('Search by lead name...'),
                                        'extraParamsExpr' => "'lead_type=' + (listingTypeVal === 'rent' ? 'renter' : (listingTypeVal === 'sale' ? 'buyer' : ''))",
                                        'selected' => $listing->leads->map(fn ($lead) => [
                                            'id' => $lead->id,
                                            'label' => $lead->name,
                                            'variant' => $listing->leadDrivesCurrentStatus($lead) ? 'success' : null,
                                        ]),
                                    ])
                                    <x-input-error class="mt-2" :messages="$errors->get('lead_ids')" />

                                    <x-save-button form="listing-form">{{ __('Save') }}</x-save-button>
                                </div>
                            </div>

                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[12.1px] font-medium bg-gray-700 text-white">{{ $listingStatusOptions[$listing->status] ?? ucfirst($listing->status) }}</span>
                        </div>
                        <x-input-error class="mb-2" :messages="$errors->get('price')" />
                        <x-input-error class="mb-2" :messages="$errors->get('listing_type')" />
                        <x-input-error class="mb-2" :messages="$errors->get('property_type')" />

                        {{-- Two sibling forms cover this page's fields — a lead's own edit
                             form can't be nested inside the listing's, so both are declared
                             here (empty shells) and every field below binds to one of them
                             by id via the HTML `form` attribute, regardless of where it sits
                             in the tree below. --}}
                        <form id="listing-form" method="post" action="{{ route('listings.update', $listing) }}" enctype="multipart/form-data">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="open_branches" :value="openBranches()">
                        </form>
                        @if ($owner)
                            <form id="owner-form" method="post" action="{{ route('leads.update', $owner) }}">
                                @csrf
                                @method('patch')
                                <input type="hidden" name="open_branches" :value="openBranches()">
                                <input type="hidden" name="from_listing" value="{{ $listing->id }}">
                            </form>
                        @endif

                        @if ($buyer)
                            <form id="buyer-form" method="post" action="{{ route('leads.update', $buyer) }}">
                                @csrf
                                @method('patch')
                                <input type="hidden" name="open_branches" :value="openBranches()">
                                <input type="hidden" name="from_listing" value="{{ $listing->id }}">
                            </form>
                        @endif

                        {{-- tree trunk: every node hangs off this single vertical line --}}
                        <div class="border-l-2 border-gray-200 ml-2">
                            {{-- Owner node (seller/landlord): always there, so one can be picked even when none is set yet --}}
                                <div class="tree-indent -ml-px border-l-2 border-transparent py-3">
                                    <div class="flex items-start tree-gap">
                                        <button type="button" @click="ownerOpen = !ownerOpen" class="shrink-0 tree-title flex items-center gap-2 text-[12.1px] font-medium" :class="ownerOpen ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            {{ $listing->listing_type === 'rent' ? __('Landlord') : __('Seller') }}
                                            <svg class="h-3 w-3 transition-transform" :class="ownerOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>

                                        <div x-show="ownerOpen" class="flex-1 border-l border-gray-100 tree-indent">
                                            <div class="border-l-2 border-gray-200 tree-indent flex items-start tree-gap">
                                                <div class="shrink-0">
                                                <x-dropdown-select name="owner_lead_id" form="listing-form" submit="listing-form" class="tree-select shrink-0" :options="['' => __('None yet')] + $owners->pluck('name', 'id')->all()" :selected="$listing->owner_lead_id" />
                                                <x-input-error class="mt-2" :messages="$errors->get('owner_lead_id')" />
                                                </div>
                                                @if ($owner)
                                                <div class="border-l border-gray-100 tree-indent flex items-start tree-gap">
                                                {{-- level 2: the owner's profile, click to reveal their own tree to the right --}}
                                                <button type="button" @click="ownerProfileOpen = !ownerProfileOpen" class="shrink-0 tree-title field-h flex items-center gap-2 text-[12.1px] font-medium" :class="ownerProfileOpen ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                                    {{ __('Profile') }}
                                                    <svg class="h-3 w-3 transition-transform" :class="ownerProfileOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </button>

                                                {{-- level 3: the owner's own Personal Info / Documents / Status branches --}}
                                                <div x-show="ownerProfileOpen" class="flex-1 border-l border-gray-100 tree-indent">
                                                    <div class="border-l-2 border-gray-200 tree-indent">
                                                        {{-- Status node (read-only — it's computed from Documents) --}}
                                                        <div class="tree-indent -ml-px border-l-2 border-transparent py-3">
                                                            <div class="flex items-start tree-gap">
                                                                <button type="button" @click="statusOpen = !statusOpen" class="shrink-0 tree-title flex items-center gap-2 text-[12.1px] font-medium" :class="statusOpen ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    </svg>
                                                                    {{ __('Status') }}
                                                                    <svg class="h-3 w-3 transition-transform" :class="statusOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                                    </svg>
                                                                </button>

                                                                <div x-show="statusOpen" class="flex-1 border-l border-gray-100 tree-indent">
                                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[12.1px] font-medium bg-gray-700 text-white">{{ $ownerStatusOptions[$owner->status] ?? ucfirst($owner->status) }}</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        @include('leads.partials.personal-info-branch', ['lead' => $owner, 'form' => 'owner-form'])
                                                        @include('leads.partials.documents-branch', ['lead' => $owner, 'form' => 'owner-form'])
                                                    </div>
                                                </div>
                                            </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>


                            {{-- Buyer node (buyer/renter), picked from the agent's own leads --}}
                            <div class="tree-indent -ml-px border-l-2 border-transparent py-3">
                                <div class="flex items-start tree-gap">
                                    <button type="button" @click="buyerOpen = !buyerOpen" class="shrink-0 tree-title flex items-center gap-2 text-[12.1px] font-medium" :class="buyerOpen ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ $listing->listing_type === 'rent' ? __('Renter') : __('Buyer') }}
                                        <svg class="h-3 w-3 transition-transform" :class="buyerOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>

                                    <div x-show="buyerOpen" class="flex-1 border-l border-gray-100 tree-indent">
                                      <div class="border-l-2 border-gray-200 tree-indent">
                                        <div class="flex items-start tree-gap">
                                        <div class="shrink-0">
                                            <x-dropdown-select name="buyer_lead_id" form="listing-form" submit="listing-form" class="tree-select shrink-0" :options="['' => $listing->leads->isEmpty() ? __('Add interested leads first') : __('None yet')] + $listing->leads->pluck('name', 'id')->all() + ($buyer ? [$buyer->id => $buyer->name] : [])" :selected="$listing->buyer_lead_id" />
                                            <x-input-error class="mt-2" :messages="$errors->get('buyer_lead_id')" />
                                        </div>

                                        @if ($buyer)
                                            <div class="border-l border-gray-100 tree-indent flex items-start tree-gap">
                                                <button type="button" @click="buyerProfileOpen = !buyerProfileOpen" class="shrink-0 tree-title field-h flex items-center gap-2 text-[12.1px] font-medium" :class="buyerProfileOpen ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                                    {{ __('Profile') }}
                                                    <svg class="h-3 w-3 transition-transform" :class="buyerProfileOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </button>

                                                <div x-show="buyerProfileOpen" class="flex-1 border-l border-gray-100 tree-indent">
                                                    <div class="border-l-2 border-gray-200 tree-indent">
                                                        {{-- Status node (read-only — it's computed from Documents) --}}
                                                        <div class="tree-indent -ml-px border-l-2 border-transparent py-3">
                                                            <div class="flex items-start tree-gap">
                                                                <button type="button" @click="buyerStatusOpen = !buyerStatusOpen" class="shrink-0 tree-title flex items-center gap-2 text-[12.1px] font-medium" :class="buyerStatusOpen ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                                                    {{ __('Status') }}
                                                                    <svg class="h-3 w-3 transition-transform" :class="buyerStatusOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                                    </svg>
                                                                </button>

                                                                <div x-show="buyerStatusOpen" class="flex-1 border-l border-gray-100 tree-indent">
                                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[12.1px] font-medium bg-gray-700 text-white">{{ $buyerStatusOptions[$buyer->status] ?? ucfirst($buyer->status) }}</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        @include('leads.partials.personal-info-branch', ['lead' => $buyer, 'form' => 'buyer-form', 'openVar' => 'buyerPersonalOpen'])
                                                        @include('leads.partials.documents-branch', ['lead' => $buyer, 'form' => 'buyer-form', 'openVar' => 'buyerDocumentsOpen'])
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        </div>
                                      </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Property Details node --}}
                            <div class="tree-indent -ml-px border-l-2 border-transparent py-3">
                                <div class="flex items-start tree-gap">
                                    <button type="button" @click="detailsOpen = !detailsOpen" class="shrink-0 tree-title flex items-center gap-2 text-[12.1px] font-medium" :class="detailsOpen ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                        </svg>
                                        {{ __('Property Details') }}
                                        <svg class="h-3 w-3 transition-transform" :class="detailsOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>

                                    <div x-show="detailsOpen" class="flex-1 max-w-sm border-l border-gray-100 tree-indent">
                                        <div class="border-l-2 border-gray-200 tree-indent space-y-4">
                                            @include('listings.partials.address-fields', ['form' => 'listing-form'])

                                            <div>
                                                <x-input-label for="area_sqm" :value="__('Size')" class="!text-[12.1px]" />
                                                <div class="relative mt-1">
                                                    <input type="text" inputmode="decimal" id="area_sqm" form="listing-form" name="area_sqm" value="{{ old('area_sqm', $listing->area_sqm) }}" class="field-slim block rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 pr-10 !text-[12.1px]">
                                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-[12.1px] text-gray-400">m&sup2;</span>
                                                </div>
                                                <x-input-error class="mt-2" :messages="$errors->get('area_sqm')" />
                                            </div>

                                            <div>
                                                <x-input-label for="description" :value="__('Description')" class="!text-[12.1px]" />
                                                <textarea id="description" form="listing-form" name="description" rows="4" class="field-w mt-1 block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm !text-[12.1px]">{{ old('description', $listing->description) }}</textarea>
                                                <x-input-error class="mt-2" :messages="$errors->get('description')" />
                                            </div>

                                            <x-save-button form="listing-form">{{ __('Save') }}</x-save-button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

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
            document.querySelectorAll('[data-remove-photo]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'remove_photos[]';
                    input.value = button.dataset.removePhoto;
                    input.setAttribute('form', 'listing-form');
                    document.body.appendChild(input);
                    document.getElementById('listing-form').submit();
                });
            });

            var priceInput = document.getElementById('price');
            var priceLastSaved = priceInput.value;
            var form = document.getElementById('listing-form');

            function stripPriceCommas() {
                priceInput.value = priceInput.value.replace(/\D/g, '');
            }

            // form.submit() called via JS doesn't fire the form's native 'submit'
            // event, so a plain submit listener alone won't catch it. The root
            // dropdowns (listing type, property type, status) all auto-save by
            // calling form.submit() directly — overriding the method itself is
            // the only way to guarantee the price field is cleaned up no matter
            // which trigger fires the submit.
            var nativeSubmit = form.submit.bind(form);
            form.submit = function () {
                stripPriceCommas();
                nativeSubmit();
            };

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
                form.submit();
            });

            form.addEventListener('submit', stripPriceCommas);
        })();
    </script>
</x-app-layout>

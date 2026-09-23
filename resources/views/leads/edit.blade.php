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
                    {{-- Levi's-style tab, sticks out from the right edge of the white card --}}
                    <button
                        type="button"
                        @click="notesOpen = !notesOpen"
                        class="absolute right-0 top-8 z-30 [writing-mode:vertical-rl] px-1 py-1.5 bg-red-600 text-white text-[10px] font-bold tracking-wider uppercase rounded-l-md shadow-lg hover:bg-red-700 transition"
                    >
                        {{ __('Notes') }}
                    </button>

                    {{-- notes panel, slides out when the tab is clicked --}}
                    <div
                        x-show="notesOpen"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-x-4"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 translate-x-4"
                        @click.outside="notesOpen = false"
                        class="absolute right-10 top-4 z-30 w-64 bg-yellow-50 border border-yellow-200 rounded-lg shadow-lg pt-2 pl-3 pr-3 pb-4"
                        style="display: none;"
                    >
                        <h3 class="text-[12.1px] font-semibold text-yellow-900 mb-2">{{ __('Notes') }}</h3>

                        <form method="post" action="{{ route('leads.notes.store', $lead) }}" class="space-y-2 mb-3">
                            @csrf
                            <div class="relative">
                                <textarea name="body" rows="2" class="block w-full bg-white border-yellow-200 focus:border-yellow-400 focus:ring-yellow-400 rounded-md shadow-sm pr-9 !text-[12.1px]" placeholder="{{ __('Log a call, message, or visit...') }}"></textarea>
                                <button type="submit" class="absolute bottom-1.5 right-1.5 h-6 w-6 flex items-center justify-center rounded-full bg-yellow-400 text-white hover:bg-yellow-500" aria-label="{{ __('Add note') }}" title="{{ __('Add note') }}">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('body')" />
                        </form>

                        <div class="space-y-1 max-h-64 overflow-y-auto">
                            @forelse ($lead->notes as $note)
                                <div class="text-[12.1px] bg-white/70 rounded-md p-1.5 flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-gray-800">{{ $note->body }}</p>
                                        <p class="text-[11px] text-gray-400">{{ $note->created_at->format('M j, Y g:ia') }}</p>
                                    </div>
                                    <form
                                        method="post"
                                        action="{{ route('leads.notes.destroy', [$lead, $note]) }}"
                                        onsubmit="return confirm('{{ __('Delete this note?') }}');"
                                    >
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="text-[12.1px] text-red-600 hover:text-red-800" aria-label="{{ __('Delete') }}">&times;</button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-[12.1px] text-yellow-700/70">{{ __('No notes yet.') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div :class="(personalOpen || listingsOpen || documentsOpen) ? 'max-w-3xl' : 'max-w-sm'" class="transition-[max-width] duration-300" x-data="{ personalOpen: {{ in_array('personal', (array) request('open', [])) ? 'true' : 'false' }}, listingsOpen: {{ (in_array('listings', (array) request('open', [])) || $lead->needsListingNarrowedForOffer()) ? 'true' : 'false' }}, documentsOpen: {{ in_array('documents', (array) request('open', [])) ? 'true' : 'false' }} }">

                    {{-- root node: type/status --}}
                    <div class="flex items-center justify-start gap-4 pb-4 mb-4 border-b border-gray-200">
                        @php
                            $typeOptions = ['' => __('Not set'), 'buyer' => __('Buyer'), 'seller' => __('Seller'), 'investor' => __('Investor'), 'renter' => __('Renter'), 'landlord' => __('Landlord')];
                            $statusOptions = ['new' => __('New'), 'contacted' => __('Contacted'), 'qualified' => __('Qualified'), 'offer' => __('Offer'), 'under_contract' => __('Under Contract'), 'closed' => __('Closed'), 'lost' => __('Lost')];
                        @endphp
                        <div
                            class="flex items-center gap-4 shrink-0"
                            x-data='{
                                typeVal: "{{ old('type', $lead->type) }}",
                                typeOpen: false,
                                typeLabels: @json($typeOptions)
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
                                    @foreach ($typeOptions as $value => $label)
                                        <button type="button" @click="typeVal = '{{ $value }}'; typeOpen = false; $nextTick(() => document.getElementById('lead-details-form').submit())" class="block w-full text-left px-3 py-1.5 text-[12.1px] text-gray-700 hover:bg-gray-50" :class="typeVal === '{{ $value }}' ? 'font-semibold text-gray-900' : ''">{{ $label }}</button>
                                    @endforeach
                                </div>
                            </div>

                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[12.1px] font-medium bg-gray-700 text-white">{{ $statusOptions[$lead->status] ?? ucfirst($lead->status) }}</span>
                        </div>
                    </div>
                    <x-input-error class="mb-2" :messages="$errors->get('type')" />
                    <x-input-error class="mb-2" :messages="$errors->get('status')" />

                    {{-- tree trunk: every node hangs off this single vertical line --}}
                    <div class="border-l-2 border-gray-200 ml-2">
                        <form id="lead-details-form" method="post" action="{{ route('leads.update', $lead) }}">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="open_branches" :value="[personalOpen && 'personal', listingsOpen && 'listings', documentsOpen && 'documents'].filter(Boolean).join(',')">

                            {{-- Personal Info node --}}
                            <div class="pl-6 -ml-px border-l-2 border-transparent py-3">
                                <div class="flex items-start gap-6">
                                    <button type="button" @click="personalOpen = !personalOpen" class="shrink-0 w-36 flex items-center gap-2 text-[12.1px] font-medium" :class="personalOpen ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ __('Personal Info') }}
                                        <svg class="h-3 w-3 transition-transform" :class="personalOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>

                                    <div x-show="personalOpen" class="flex-1 max-w-sm border-l border-gray-100 pl-6">
                                        <div class="border-l-2 border-gray-200 pl-6 space-y-4">
                                            <div>
                                                <x-input-label for="first_name" :value="__('First name')" class="!text-[12.1px]" />
                                                <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('first_name', $lead->first_name)" required />
                                                <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
                                            </div>

                                            <div>
                                                <x-input-label for="last_name" :value="__('Last name')" class="!text-[12.1px]" />
                                                <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('last_name', $lead->last_name)" />
                                                <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
                                            </div>

                                            <div>
                                                <x-input-label for="phone" :value="__('Phone')" class="!text-[12.1px]" />
                                                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('phone', $lead->phone)" />
                                                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                                            </div>

                                            <div>
                                                <x-input-label for="email" :value="__('Email')" class="!text-[12.1px]" />
                                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full !text-[12.1px]" :value="old('email', $lead->email)" />
                                                <x-input-error class="mt-2" :messages="$errors->get('email')" />
                                            </div>

                                            <x-primary-button>{{ __('Save') }}</x-primary-button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Listings node --}}
                            <div class="pl-6 -ml-px border-l-2 border-transparent py-3">
                                <div class="flex items-start gap-6">
                                    <button type="button" @click="listingsOpen = !listingsOpen" class="shrink-0 w-36 flex items-center gap-2 text-[12.1px] font-medium" :class="listingsOpen ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                        </svg>
                                        {{ __('Property Type') }}
                                        <svg class="h-3 w-3 transition-transform" :class="listingsOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>

                                    <div x-show="listingsOpen" class="flex-1 max-w-2xl border-l border-gray-100 pl-6" x-data="{ propertyType: '{{ old('property_type', $lead->property_type) }}' || null }">
                                        <p class="text-[12.1px] text-gray-500 mb-2">{{ __('What are they buying?') }}</p>

                                        <div class="flex items-center gap-6">
                                            {{-- Property type branch --}}
                                            <div class="border-l-2 border-gray-200 pl-6">
                                                <input type="hidden" form="lead-details-form" name="property_type" :value="propertyType">
                                                <div class="flex flex-col items-start gap-2">
                                                    <button type="button" @click="propertyType = (propertyType === 'condo' ? null : 'condo')" class="px-3 py-1.5 rounded-full text-[12.1px] font-medium transition" :class="propertyType === 'condo' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">{{ __('Condo') }}</button>
                                                    <button type="button" @click="propertyType = (propertyType === 'house' ? null : 'house')" class="px-3 py-1.5 rounded-full text-[12.1px] font-medium transition" :class="propertyType === 'house' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">{{ __('House') }}</button>
                                                    <button type="button" @click="propertyType = (propertyType === 'land' ? null : 'land')" class="px-3 py-1.5 rounded-full text-[12.1px] font-medium transition" :class="propertyType === 'land' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">{{ __('Land') }}</button>
                                                    <button type="button" @click="propertyType = (propertyType === 'commercial' ? null : 'commercial')" class="px-3 py-1.5 rounded-full text-[12.1px] font-medium transition" :class="propertyType === 'commercial' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">{{ __('Commercial') }}</button>
                                                </div>
                                            </div>

                                            {{-- Property search branch, only appears once a type is picked, to the right of the type branch --}}
                                            <div x-show="propertyType" x-transition class="border-l border-gray-100 pl-6">
                                                <div class="border-l-2 border-gray-200 pl-6 w-64">
                                                    @include('partials.tag-picker', [
                                                        'name' => 'listing_ids',
                                                        'searchUrl' => route('listings.search'),
                                                        'placeholder' => __('Search by address or listing ID...'),
                                                        'selected' => $lead->listings->map(fn ($listing) => [
                                                            'id' => $listing->id,
                                                            'label' => "#{$listing->id} — {$listing->address_line}, {$listing->city}",
                                                        ]),
                                                        'extraParamsExpr' => "'property_type=' + (propertyType || '')",
                                                    ])
                                                    <x-input-error class="mt-2" :messages="$errors->get('listing_ids')" />

                                                    <div class="mt-4">
                                                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Documents node --}}
                            @if ($lead->hasDocumentChecklist())
                                <div class="pl-6 -ml-px border-l-2 border-transparent py-3">
                                    <div class="flex items-start gap-6">
                                        <button type="button" @click="documentsOpen = !documentsOpen" class="shrink-0 w-36 flex items-center gap-2 text-[12.1px] font-medium" :class="documentsOpen ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                                            </svg>
                                            {{ __('Documents') }}
                                            <svg class="h-3 w-3 transition-transform" :class="documentsOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>

                                        @php $docStageDefault = $lead->documents->contains('checked', true) ? 'true' : 'false'; @endphp
                                        <div x-show="documentsOpen" class="flex-1 max-w-sm border-l border-gray-100 pl-6" x-data="{ docStage: { contacted: {{ $docStageDefault }}, qualified: {{ $docStageDefault }}, offer: {{ $docStageDefault }}, under_contract: {{ $docStageDefault }}, closed: {{ $docStageDefault }} } }">
                                            <div class="border-l-2 border-gray-200 pl-6">
                                                @foreach ([
                                                    'contacted' => __('Contacted'),
                                                    'qualified' => __('Qualified'),
                                                    'offer' => __('Offer'),
                                                    'under_contract' => __('Under Contract'),
                                                    'closed' => __('Closed'),
                                                ] as $stageKey => $stageLabel)
                                                    <div class="py-2">
                                                        <button type="button" @click="docStage.{{ $stageKey }} = !docStage.{{ $stageKey }}" class="w-full flex items-center gap-2 text-[12.1px] font-medium" :class="docStage.{{ $stageKey }} ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                                            {{ $stageLabel }}
                                                            <svg class="h-3 w-3 transition-transform" :class="docStage.{{ $stageKey }} ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                            </svg>
                                                        </button>

                                                        <div x-show="docStage.{{ $stageKey }}" class="mt-2 border-l border-gray-100 pl-4 space-y-2">
                                                            @foreach ($lead->documentChecklistForStage($stageKey) as $key => $doc)
                                                                <label class="flex items-center gap-2 text-[12.1px] text-gray-700">
                                                                    <input type="checkbox" name="documents[{{ $key }}]" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked($doc['checked'])>
                                                                    {{ __($doc['label']) }}
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endforeach

                                                <div class="pt-2 pb-2">
                                                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
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

        <x-modal name="narrow-listing-warning" :show="$lead->needsListingNarrowedForOffer()" focusable>
            <div class="p-6">
                <h2 class="text-sm font-medium text-gray-900">
                    {{ __('This offer needs one listing') }}
                </h2>

                <p class="mt-2 text-[12.1px] text-gray-600">
                    @if ($lead->listings->isEmpty())
                        {{ __('This lead is ready to move to Offer — link the listing this offer is for.') }}
                    @else
                        {{ __('This lead has :count listings linked — remove all but the one this offer is for to move it to Offer.', ['count' => $lead->listings->count()]) }}
                    @endif
                </p>

                <div class="mt-6 flex justify-end">
                    <x-primary-button x-on:click="$dispatch('close')">
                        {{ __('Got it') }}
                    </x-primary-button>
                </div>
            </div>
        </x-modal>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="relative top-1 -left-8 h-10 flex items-center">
            <h2 class="font-semibold text-sm text-gray-200 leading-tight">
                {{ __('Edit Lead') }}
            </h2>
        </div>
    </x-slot>

    <div class="pt-6 pb-12">
        <div class="px-4 sm:px-6">
            <div class="pt-3 pb-4 px-4 sm:pt-4 sm:pb-8 sm:px-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <form method="post" action="{{ route('leads.update', $lead) }}" class="space-y-6">
                        @csrf
                        @method('patch')

                        <div>
                            <x-input-label for="first_name" :value="__('First name')" class="!text-[12.1px]" />
                            <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('first_name', $lead->first_name)" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
                        </div>

                        <div>
                            <x-input-label for="last_name" :value="__('Last name')" class="!text-[12.1px]" />
                            <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('last_name', $lead->last_name)" />
                            <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
                        </div>

                        <div>
                            <x-input-label for="type" :value="__('Lead Type')" class="!text-[12.1px]" />
                            <select id="type" name="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm !text-[12.1px]">
                                <option value="">{{ __('Not set') }}</option>
                                <option value="buyer" @selected(old('type', $lead->type) === 'buyer')>{{ __('Buyer') }}</option>
                                <option value="seller" @selected(old('type', $lead->type) === 'seller')>{{ __('Seller') }}</option>
                                <option value="investor" @selected(old('type', $lead->type) === 'investor')>{{ __('Investor') }}</option>
                                <option value="renter" @selected(old('type', $lead->type) === 'renter')>{{ __('Renter') }}</option>
                                <option value="landlord" @selected(old('type', $lead->type) === 'landlord')>{{ __('Landlord') }}</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('type')" />
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

                        @php $isBuyer = old('type', $lead->type) === 'buyer'; @endphp

                        <div>
                            <x-input-label for="status" :value="__('Status')" class="!text-[12.1px]" />
                            <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm !text-[12.1px]" required>
                                @if ($isBuyer)
                                    <option value="new" @selected(old('status', $lead->status) === 'new')>{{ __('New') }}</option>
                                    <option value="contacted" @selected(old('status', $lead->status) === 'contacted')>{{ __('Contacted') }}</option>
                                    <option value="qualified" @selected(old('status', $lead->status) === 'qualified')>{{ __('Qualified') }}</option>
                                    <option value="active_search" @selected(old('status', $lead->status) === 'active_search')>{{ __('Active Search') }}</option>
                                    <option value="lost" @selected(old('status', $lead->status) === 'lost')>{{ __('Lost') }}</option>
                                @else
                                    <option value="new" @selected(old('status', $lead->status) === 'new')>{{ __('New') }}</option>
                                    <option value="qualified" @selected(old('status', $lead->status) === 'qualified')>{{ __('Qualified') }}</option>
                                    <option value="visited" @selected(old('status', $lead->status) === 'visited')>{{ __('Visited') }}</option>
                                    <option value="proposal" @selected(old('status', $lead->status) === 'proposal')>{{ __('Proposal') }}</option>
                                    <option value="closed" @selected(old('status', $lead->status) === 'closed')>{{ __('Closed') }}</option>
                                    <option value="lost" @selected(old('status', $lead->status) === 'lost')>{{ __('Lost') }}</option>
                                @endif
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('status')" />
                        </div>

                        @if ($isBuyer)
                            <div>
                                <x-input-label :value="__('Financing checklist')" class="!text-[12.1px]" />
                                <p class="mt-1 text-[12.1px] text-gray-500">{{ __('Once all 3 are checked, this lead becomes Qualified automatically.') }}</p>
                                <div class="mt-2 space-y-2">
                                    <label class="flex items-center gap-2 text-[12.1px] text-gray-700">
                                        <input type="checkbox" name="financing_preapproval" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked(old('financing_preapproval', $lead->financing_preapproval))>
                                        {{ __('Pre-approval letter') }}
                                    </label>
                                    <label class="flex items-center gap-2 text-[12.1px] text-gray-700">
                                        <input type="checkbox" name="financing_income_proof" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked(old('financing_income_proof', $lead->financing_income_proof))>
                                        {{ __('Proof of income') }}
                                    </label>
                                    <label class="flex items-center gap-2 text-[12.1px] text-gray-700">
                                        <input type="checkbox" name="financing_id_document" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked(old('financing_id_document', $lead->financing_id_document))>
                                        {{ __('ID document') }}
                                    </label>
                                </div>
                            </div>
                        @endif

                        <div>
                            <x-input-label :value="__('Interested in')" class="!text-[12.1px]" />
                            @include('partials.tag-picker', [
                                'name' => 'listing_ids',
                                'searchUrl' => route('listings.search'),
                                'placeholder' => __('Search by address or listing ID...'),
                                'selected' => $lead->listings->map(fn ($listing) => [
                                    'id' => $listing->id,
                                    'label' => "#{$listing->id} — {$listing->address_line}, {$listing->city}",
                                ]),
                            ])
                            <x-input-error class="mt-2" :messages="$errors->get('listing_ids')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Save') }}</x-primary-button>
                        </div>
                    </form>

                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h3 class="text-[12.1px] font-medium text-gray-900 mb-3">{{ __('Notes') }}</h3>

                        <form method="post" action="{{ route('leads.notes.store', $lead) }}" class="space-y-2 mb-4">
                            @csrf
                            <textarea name="body" rows="2" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm !text-[12.1px]" placeholder="{{ __('Log a call, message, or visit...') }}"></textarea>
                            <x-input-error :messages="$errors->get('body')" />
                            <x-primary-button type="submit">{{ __('Add note') }}</x-primary-button>
                        </form>

                        <div class="space-y-3">
                            @forelse ($lead->notes as $note)
                                <div class="text-[12.1px] border-b border-gray-100 pb-2 flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-gray-700">{{ $note->body }}</p>
                                        <p class="text-[12.1px] text-gray-400">{{ $note->created_at->format('M j, Y g:ia') }}</p>
                                    </div>
                                    <form
                                        method="post"
                                        action="{{ route('leads.notes.destroy', [$lead, $note]) }}"
                                        onsubmit="return confirm('{{ __('Delete this note?') }}');"
                                    >
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="text-[12.1px] text-red-600 underline hover:text-red-800 whitespace-nowrap">
                                            {{ __('Delete') }}
                                        </button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-[12.1px] text-gray-500">{{ __('No notes yet.') }}</p>
                            @endforelse
                        </div>
                    </div>

                    <form
                        method="post"
                        action="{{ route('leads.destroy', $lead) }}"
                        class="mt-6 pt-6 border-t border-gray-200"
                        onsubmit="return confirm('{{ __('Delete this lead? This cannot be undone.') }}');"
                    >
                        @csrf
                        @method('delete')
                        <button type="submit" class="text-[12.1px] text-red-600 underline hover:text-red-800">
                            {{ __('Delete this lead') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

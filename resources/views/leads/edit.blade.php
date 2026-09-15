<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-base text-gray-800 leading-tight">
            {{ __('Edit Lead') }}
        </h2>
    </x-slot>

    <div class="pt-2 pb-12">
        <div class="px-8 sm:px-10">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <form method="post" action="{{ route('leads.update', $lead) }}" class="space-y-6">
                        @csrf
                        @method('patch')

                        <div>
                            <x-input-label for="first_name" :value="__('First name')" />
                            <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $lead->first_name)" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
                        </div>

                        <div>
                            <x-input-label for="last_name" :value="__('Last name')" />
                            <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $lead->last_name)" />
                            <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
                        </div>

                        <div>
                            <x-input-label for="phone" :value="__('Phone')" />
                            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $lead->phone)" />
                            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $lead->email)" />
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        </div>

                        <div>
                            <x-input-label for="status" :value="__('Status')" />
                            <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="new" @selected(old('status', $lead->status) === 'new')>{{ __('New') }}</option>
                                <option value="qualified" @selected(old('status', $lead->status) === 'qualified')>{{ __('Qualified') }}</option>
                                <option value="visited" @selected(old('status', $lead->status) === 'visited')>{{ __('Visited') }}</option>
                                <option value="proposal" @selected(old('status', $lead->status) === 'proposal')>{{ __('Proposal') }}</option>
                                <option value="closed" @selected(old('status', $lead->status) === 'closed')>{{ __('Closed') }}</option>
                                <option value="lost" @selected(old('status', $lead->status) === 'lost')>{{ __('Lost') }}</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('status')" />
                        </div>

                        <div>
                            <x-input-label :value="__('Interested in')" />
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
                        <h3 class="text-sm font-medium text-gray-900 mb-3">{{ __('Notes') }}</h3>

                        <form method="post" action="{{ route('leads.notes.store', $lead) }}" class="space-y-2 mb-4">
                            @csrf
                            <textarea name="body" rows="2" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" placeholder="{{ __('Log a call, message, or visit...') }}"></textarea>
                            <x-input-error :messages="$errors->get('body')" />
                            <x-primary-button type="submit">{{ __('Add note') }}</x-primary-button>
                        </form>

                        <div class="space-y-3">
                            @forelse ($lead->notes as $note)
                                <div class="text-sm border-b border-gray-100 pb-2 flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-gray-700">{{ $note->body }}</p>
                                        <p class="text-xs text-gray-400">{{ $note->created_at->format('M j, Y g:ia') }}</p>
                                    </div>
                                    <form
                                        method="post"
                                        action="{{ route('leads.notes.destroy', [$lead, $note]) }}"
                                        onsubmit="return confirm('{{ __('Delete this note?') }}');"
                                    >
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="text-xs text-red-600 underline hover:text-red-800 whitespace-nowrap">
                                            {{ __('Delete') }}
                                        </button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">{{ __('No notes yet.') }}</p>
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
                        <button type="submit" class="text-sm text-red-600 underline hover:text-red-800">
                            {{ __('Delete this lead') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

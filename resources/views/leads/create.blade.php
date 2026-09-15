<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-base text-gray-800 leading-tight">
            {{ __('Add a Lead') }}
        </h2>
    </x-slot>

    <div class="pt-2 pb-12">
        <div class="px-8 sm:px-10">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <form method="post" action="{{ route('leads.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="first_name" :value="__('First name')" />
                            <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name')" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
                        </div>

                        <div>
                            <x-input-label for="last_name" :value="__('Last name')" />
                            <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name')" />
                            <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
                        </div>

                        <div>
                            <x-input-label for="phone" :value="__('Phone')" />
                            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone')" />
                            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" />
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        </div>

                        <div>
                            <x-input-label :value="__('Interested in')" />
                            @include('partials.tag-picker', [
                                'name' => 'listing_ids',
                                'searchUrl' => route('listings.search'),
                                'placeholder' => __('Search by address or listing ID...'),
                                'selected' => [],
                            ])
                            <x-input-error class="mt-2" :messages="$errors->get('listing_ids')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Add lead') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

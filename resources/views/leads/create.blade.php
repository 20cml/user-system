<x-app-layout>
    <x-slot name="header">
        <div class="relative top-1 -left-8 h-10 flex items-center">
            <h2 class="font-semibold text-sm text-gray-200 leading-tight">
                {{ __('Add a Lead') }}
            </h2>
        </div>
    </x-slot>

    <div class="pt-6 pb-12">
        <div class="px-4 sm:px-6">
            <div class="pt-3 pb-4 px-4 sm:pt-4 sm:pb-8 sm:px-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <form method="post" action="{{ route('leads.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="first_name" :value="__('First name')" class="!text-[12.1px]" />
                            <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('first_name')" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
                        </div>

                        <div>
                            <x-input-label for="last_name" :value="__('Last name')" class="!text-[12.1px]" />
                            <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('last_name')" />
                            <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
                        </div>

                        <div>
                            <x-input-label for="type" :value="__('Lead Type')" class="!text-[12.1px]" />
                            <select id="type" name="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm !text-[12.1px]">
                                <option value="">{{ __('Not set') }}</option>
                                <option value="buyer" @selected(old('type') === 'buyer')>{{ __('Buyer') }}</option>
                                <option value="seller" @selected(old('type') === 'seller')>{{ __('Seller') }}</option>
                                <option value="investor" @selected(old('type') === 'investor')>{{ __('Investor') }}</option>
                                <option value="renter" @selected(old('type') === 'renter')>{{ __('Renter') }}</option>
                                <option value="landlord" @selected(old('type') === 'landlord')>{{ __('Landlord') }}</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('type')" />
                        </div>

                        <div>
                            <x-input-label for="phone" :value="__('Phone')" class="!text-[12.1px]" />
                            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('phone')" />
                            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('Email')" class="!text-[12.1px]" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full !text-[12.1px]" :value="old('email')" />
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        </div>

                        <div>
                            <x-input-label :value="__('Interested in')" class="!text-[12.1px]" />
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

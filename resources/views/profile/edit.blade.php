<x-app-layout>
    <x-slot name="header">
        <div class="relative top-1 -left-8 h-10 flex items-center">
            <h2 class="font-semibold text-sm text-gray-200 leading-tight">
                {{ $user->first_name }} {{ $user->last_name }} {{ __('Profile') }}
            </h2>
        </div>
    </x-slot>

    <div class="p-3 sm:p-4 min-h-full">
        <div class="relative p-3 sm:p-4 bg-white shadow sm:rounded-lg min-h-full flex flex-col">
                <div class="max-w-xl">
                    <div
                        x-data="{
                            firstName: {{ \Illuminate\Support\Js::from(old('first_name', $user->first_name)) }},
                            lastName: {{ \Illuminate\Support\Js::from(old('last_name', $user->last_name)) }},
                            personalOpen: {{ ($errors->hasAny(['first_name', 'last_name', 'email', 'phone']) || in_array('personal', (array) request('open', []))) ? 'true' : 'false' }},
                            addressOpen: {{ ($errors->hasAny(['postal_code', 'address_line', 'address_complement', 'city', 'state_province', 'country']) || in_array('address', (array) request('open', []))) ? 'true' : 'false' }},
                            passwordOpen: {{ ($errors->updatePassword->any() || in_array('password', (array) request('open', []))) ? 'true' : 'false' }}
                        }"
                    >
                        <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                            @csrf
                        </form>

                        {{-- tree trunk: every node hangs off this single vertical line --}}
                        <div class="border-l-2 border-gray-200 ml-2">
                            <form id="profile-details-form" method="post" action="{{ route('profile.update') }}">
                                @csrf
                                @method('patch')
                                <input type="hidden" name="open_branches" :value="[personalOpen && 'personal', addressOpen && 'address', passwordOpen && 'password'].filter(Boolean).join(',')">

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
                                                    <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full !text-[12.1px]" x-model="firstName" required autocomplete="given-name" />
                                                    <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
                                                </div>

                                                <div>
                                                    <x-input-label for="last_name" :value="__('Last name')" class="!text-[12.1px]" />
                                                    <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full !text-[12.1px]" x-model="lastName" required autocomplete="family-name" />
                                                    <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
                                                </div>

                                                <div>
                                                    <x-input-label for="email" :value="__('Email')" class="!text-[12.1px]" />
                                                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full !text-[12.1px]" :value="old('email', $user->email)" required autocomplete="username" />
                                                    <x-input-error class="mt-2" :messages="$errors->get('email')" />

                                                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                                        <div>
                                                            <p class="text-[12.1px] mt-2 text-gray-800">
                                                                {{ __('Your email address is unverified.') }}

                                                                <button form="send-verification" class="underline text-[12.1px] text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                                    {{ __('Click here to re-send the verification email.') }}
                                                                </button>
                                                            </p>

                                                            @if (session('status') === 'verification-link-sent')
                                                                <p class="mt-2 font-medium text-[12.1px] text-green-600">
                                                                    {{ __('A new verification link has been sent to your email address.') }}
                                                                </p>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>

                                                <div>
                                                    <x-input-label for="phone" :value="__('Phone number')" class="!text-[12.1px]" />
                                                    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('phone', $user->phone)" required autocomplete="tel" />
                                                    <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                                                </div>

                                                <div class="flex items-center gap-4">
                                                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Address node --}}
                                <div class="pl-6 -ml-px border-l-2 border-transparent py-3">
                                    <div class="flex items-start gap-6">
                                        <button type="button" @click="addressOpen = !addressOpen" class="shrink-0 w-36 flex items-center gap-2 text-[12.1px] font-medium" :class="addressOpen ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                            </svg>
                                            {{ __('Address') }}
                                            <svg class="h-3 w-3 transition-transform" :class="addressOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>

                                        <div x-show="addressOpen" class="flex-1 max-w-sm border-l border-gray-100 pl-6">
                                            <div class="border-l-2 border-gray-200 pl-6 space-y-4">
                                                <div>
                                                    <x-input-label for="postal_code" :value="__('Postal / ZIP code')" class="!text-[12.1px]" />
                                                    <x-text-input id="postal_code" name="postal_code" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('postal_code', $user->postal_code)" required autocomplete="postal-code" />
                                                    <x-input-error class="mt-2" :messages="$errors->get('postal_code')" />
                                                    <p id="address-suggestions" class="mt-1 text-[12.1px] text-gray-600"></p>
                                                </div>

                                                <div>
                                                    <x-input-label for="address_line" :value="__('Street address')" class="!text-[12.1px]" />
                                                    <x-text-input id="address_line" name="address_line" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('address_line', $user->address_line)" required autocomplete="address-line1" />
                                                    <x-input-error class="mt-2" :messages="$errors->get('address_line')" />
                                                </div>

                                                <div>
                                                    <x-input-label for="address_complement" :value="__('Apartment / suite / unit (optional)')" class="!text-[12.1px]" />
                                                    <x-text-input id="address_complement" name="address_complement" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('address_complement', $user->address_complement)" autocomplete="address-line2" />
                                                    <x-input-error class="mt-2" :messages="$errors->get('address_complement')" />
                                                </div>

                                                <div>
                                                    <x-input-label for="city" :value="__('City')" class="!text-[12.1px]" />
                                                    <x-text-input id="city" name="city" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('city', $user->city)" required autocomplete="address-level2" />
                                                    <x-input-error class="mt-2" :messages="$errors->get('city')" />
                                                </div>

                                                <div>
                                                    <x-input-label for="state_province" :value="__('State / Province')" class="!text-[12.1px]" />
                                                    <x-text-input id="state_province" name="state_province" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('state_province', $user->state_province)" required autocomplete="address-level1" />
                                                    <x-input-error class="mt-2" :messages="$errors->get('state_province')" />
                                                </div>

                                                <div>
                                                    <x-input-label for="country" :value="__('Country')" class="!text-[12.1px]" />
                                                    <select id="country" name="country" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm !text-[12.1px]" required>
                                                        <option value="">{{ __('Select a country') }}</option>
                                                        <option value="CA" @selected(old('country', $user->country) === 'CA')>{{ __('Canada') }}</option>
                                                        <option value="US" @selected(old('country', $user->country) === 'US')>{{ __('United States') }}</option>
                                                    </select>
                                                    <x-input-error class="mt-2" :messages="$errors->get('country')" />
                                                </div>

                                                <div class="flex items-center gap-4">
                                                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            {{-- Password node --}}
                            <div class="pl-6 -ml-px border-l-2 border-transparent py-3">
                                <div class="flex items-start gap-6">
                                    <button type="button" @click="passwordOpen = !passwordOpen" class="shrink-0 w-36 flex items-center gap-2 text-[12.1px] font-medium" :class="passwordOpen ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                        </svg>
                                        {{ __('Password') }}
                                        <svg class="h-3 w-3 transition-transform" :class="passwordOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>

                                    <div x-show="passwordOpen" class="flex-1 max-w-sm border-l border-gray-100 pl-6">
                                        <div class="border-l-2 border-gray-200 pl-6 space-y-4">
                                            <form method="post" action="{{ route('password.update') }}">
                                                @csrf
                                                @method('put')
                                                <input type="hidden" name="open_branches" :value="[personalOpen && 'personal', addressOpen && 'address', passwordOpen && 'password'].filter(Boolean).join(',')">

                                                <div class="space-y-4">
                                                    <div>
                                                        <x-input-label for="update_password_current_password" :value="__('Current Password')" class="!text-[12.1px]" />
                                                        <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full !text-[12.1px]" autocomplete="current-password" />
                                                        <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
                                                    </div>

                                                    <div>
                                                        <x-input-label for="update_password_password" :value="__('New Password')" class="!text-[12.1px]" />
                                                        <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full !text-[12.1px]" autocomplete="new-password" />
                                                        <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
                                                    </div>

                                                    <div>
                                                        <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" class="!text-[12.1px]" />
                                                        <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full !text-[12.1px]" autocomplete="new-password" />
                                                        <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
                                                    </div>

                                                    <div class="flex items-center gap-4">
                                                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-auto pt-6 border-t border-gray-200">
                    <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')" class="text-[12.1px] text-red-600 underline hover:text-red-800">
                        {{ __('Delete Account') }}
                    </button>

                    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
                        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
                            @csrf
                            @method('delete')

                            <h2 class="text-sm font-medium text-gray-900">
                                {{ __('Are you sure you want to delete your account?') }}
                            </h2>

                            <p class="mt-1 text-[12.1px] text-gray-600">
                                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                            </p>

                            <div class="mt-6">
                                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                                <x-text-input
                                    id="password"
                                    name="password"
                                    type="password"
                                    class="mt-1 block w-3/4 !text-[12.1px]"
                                    placeholder="{{ __('Password') }}"
                                />

                                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                            </div>

                            <div class="mt-6 flex justify-end">
                                <x-secondary-button x-on:click="$dispatch('close')">
                                    {{ __('Cancel') }}
                                </x-secondary-button>

                                <x-danger-button class="ms-3">
                                    {{ __('Delete Account') }}
                                </x-danger-button>
                            </div>
                        </form>
                    </x-modal>
                </div>
        </div>
    </div>

    <script>
        (function () {
            var postalInput = document.getElementById('postal_code');
            var suggestionsBox = document.getElementById('address-suggestions');
            var cityInput = document.getElementById('city');
            var stateProvinceInput = document.getElementById('state_province');
            var countrySelect = document.getElementById('country');
            var debounceTimer = null;

            function clearSuggestions() {
                suggestionsBox.innerHTML = '';
            }

            function renderSuggestions(suggestions) {
                clearSuggestions();

                if (!suggestions.length) {
                    return;
                }

                suggestions.forEach(function (suggestion) {
                    var link = document.createElement('button');
                    link.type = 'button';
                    link.className = 'underline text-indigo-600 hover:text-indigo-900 block';
                    link.textContent = [suggestion.street, suggestion.city, suggestion.state_province, suggestion.postal_code].filter(Boolean).join(', ');
                    link.addEventListener('click', function () {
                        if (suggestion.city) cityInput.value = suggestion.city;
                        if (suggestion.state_province) stateProvinceInput.value = suggestion.state_province;
                        if (suggestion.country) countrySelect.value = suggestion.country;
                        clearSuggestions();
                    });
                    suggestionsBox.appendChild(link);
                });
            }

            function fetchSuggestions(query) {
                fetch('{{ route('address-suggestions') }}?query=' + encodeURIComponent(query), {
                    headers: { 'Accept': 'application/json' },
                })
                    .then(function (response) { return response.ok ? response.json() : []; })
                    .then(renderSuggestions)
                    .catch(clearSuggestions);
            }

            if (postalInput) {
                postalInput.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    var query = postalInput.value.trim();

                    if (query.length < 3) {
                        clearSuggestions();
                        return;
                    }

                    debounceTimer = setTimeout(function () { fetchSuggestions(query); }, 400);
                });
            }
        })();
    </script>
</x-app-layout>

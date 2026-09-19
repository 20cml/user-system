<section>
    <header>
        <h2 class="text-sm font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-[12.1px] text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="first_name" :value="__('First name')" class="!text-[12.1px]" />
            <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('first_name', $user->first_name)" required autofocus autocomplete="given-name" />
            <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
        </div>

        <div>
            <x-input-label for="last_name" :value="__('Last name')" class="!text-[12.1px]" />
            <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('last_name', $user->last_name)" required autocomplete="family-name" />
            <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" class="!text-[12.1px]" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full !text-[12.1px]" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
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

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>

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
</section>

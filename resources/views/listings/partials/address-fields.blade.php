@php $listing = $listing ?? null; @endphp

<div>
    <x-input-label for="postal_code" :value="__('Postal / ZIP code')" />
    <x-text-input id="postal_code" name="postal_code" type="text" class="mt-1 block w-full" :value="old('postal_code', $listing->postal_code ?? '')" required autocomplete="postal-code" />
    <x-input-error class="mt-2" :messages="$errors->get('postal_code')" />
    <p id="address-suggestions" class="mt-1 text-sm text-gray-600"></p>
</div>

<div>
    <x-input-label for="address_line" :value="__('Street address')" />
    <x-text-input id="address_line" name="address_line" type="text" class="mt-1 block w-full" :value="old('address_line', $listing->address_line ?? '')" required autocomplete="address-line1" />
    <x-input-error class="mt-2" :messages="$errors->get('address_line')" />
</div>

<div>
    <x-input-label for="city" :value="__('City')" />
    <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" :value="old('city', $listing->city ?? '')" required autocomplete="address-level2" />
    <x-input-error class="mt-2" :messages="$errors->get('city')" />
</div>

<div>
    <x-input-label for="state_province" :value="__('State / Province')" />
    <x-text-input id="state_province" name="state_province" type="text" class="mt-1 block w-full" :value="old('state_province', $listing->state_province ?? '')" required autocomplete="address-level1" />
    <x-input-error class="mt-2" :messages="$errors->get('state_province')" />
</div>

<div>
    <x-input-label for="country" :value="__('Country')" />
    <select id="country" name="country" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
        <option value="">{{ __('Select a country') }}</option>
        <option value="CA" @selected(old('country', $listing->country ?? '') === 'CA')>{{ __('Canada') }}</option>
        <option value="US" @selected(old('country', $listing->country ?? '') === 'US')>{{ __('United States') }}</option>
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('country')" />
</div>

<script>
    (function () {
        var postalInput = document.getElementById('postal_code');
        var suggestionsBox = document.getElementById('address-suggestions');
        var addressLine = document.getElementById('address_line');
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
                    if (suggestion.street) addressLine.value = suggestion.street;
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

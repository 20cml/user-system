<x-app-layout>
    <x-slot name="header">
        <div class="relative top-1 -left-8 h-10 flex items-center justify-between">
            <h2 class="font-semibold text-sm text-gray-200 leading-tight">
                {{ __('My Leads') }}
            </h2>

            <div class="relative -top-2.5 mr-2 flex items-center gap-2">
                <x-dropdown align="right" width="w-48" rounded="rounded-2xl" content-classes="bg-white px-4 py-3" :close-on-click="false" :initial-open="request()->hasAny(['name', 'status', 'type'])">
                    <x-slot name="trigger">
                        <button type="button"
                                class="h-7 w-7 flex items-center justify-center rounded-full bg-gray-700 border border-gray-400 text-gray-400 hover:bg-gray-600"
                                aria-label="{{ __('Filter leads') }}"
                                title="{{ __('Filter leads') }}">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" d="M4 6h16M7 12h10M10 18h4" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <form method="get" action="{{ route('leads.index') }}" id="lead-filter-form">
                            <input type="text" name="name" id="lead-filter-name" value="{{ request('name') }}" placeholder="{{ __("Type lead's name...") }}" autocomplete="off" class="block w-full border-0 border-b border-gray-200 focus:border-gray-300 focus:ring-0 px-0 pb-2 text-[13px] placeholder-gray-400">

                            <p class="pt-3 pb-1 text-xs font-medium text-gray-400">{{ __('Lead Type') }}</p>
                            @foreach (['buyer' => 'Buyer', 'seller' => 'Seller', 'investor' => 'Investor', 'renter' => 'Renter', 'landlord' => 'Landlord'] as $value => $label)
                                <label class="flex items-center justify-between py-1.5 text-[13px] text-gray-900">
                                    {{ __($label) }}
                                    <input type="checkbox" name="type[]" value="{{ $value }}" class="lead-filter-field h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-0" @checked(in_array($value, (array) request('type', [])))>
                                </label>
                            @endforeach
                        </form>
                    </x-slot>
                </x-dropdown>

                <button type="button" x-data="" x-on:click="$dispatch('open-modal', 'add-lead')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 rounded-full font-medium text-xs text-gray-800 hover:bg-gray-50">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('Add lead') }}
                </button>
            </div>
        </div>
    </x-slot>

    <div class="pt-6 pb-4 flex-1 flex flex-col min-h-0">
        <div class="px-4 sm:px-6 flex-1 flex flex-col min-h-0">
            <div class="overflow-auto flex-1 min-h-0">
                <table class="min-w-full table-fixed divide-y divide-gray-100">
                    <thead>
                        <tr class="bg-gray-50 text-left font-normal text-gray-500 font-sans">
                            <th class="w-1/4 px-6 py-3 text-[12.1px] font-normal">{{ __('Name') }}</th>
                            <th class="w-1/4 px-6 py-3 text-[12.1px] font-normal">{{ __('Phone') }}</th>
                            <th class="w-1/4 px-6 py-3 text-[12.1px] font-normal">{{ __('Email') }}</th>
                            <th class="w-1/4 px-6 py-3 text-[12.1px] font-normal">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody id="lead-rows" class="divide-y divide-gray-100">
                        @include('leads.partials.rows')
                    </tbody>
                </table>
            </div>

            <div class="pt-3 border-t border-gray-200">
                <span id="lead-count" class="inline-flex items-center px-3 py-1.5 rounded-full bg-gray-200 text-xs font-medium text-gray-700">
                    {{ $leads->count() }} {{ __('leads') }}
                </span>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var form = document.getElementById('lead-filter-form');
            var nameInput = document.getElementById('lead-filter-name');
            var rowsBody = document.getElementById('lead-rows');
            var countLabel = document.getElementById('lead-count');
            var debounceTimer = null;

            function applyFilters() {
                var params = new URLSearchParams(new FormData(form));
                var url = form.action + '?' + params.toString();

                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        rowsBody.innerHTML = data.rows;
                        countLabel.textContent = data.count + ' {{ __('leads') }}';
                        window.history.replaceState(null, '', url);
                    });
            }

            nameInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(applyFilters, 400);
            });

            form.querySelectorAll('.lead-filter-field').forEach(function (field) {
                field.addEventListener('change', applyFilters);
            });
        })();
    </script>

    <x-modal name="add-lead" :show="$errors->any()" focusable>
        <form method="post" action="{{ route('leads.store') }}" class="p-6 space-y-6">
            @csrf

            <h2 class="text-sm font-medium text-gray-900">
                {{ __('Add a Lead') }}
            </h2>

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
                <x-input-label for="first_name" :value="__('First name')" class="!text-[12.1px]" />
                <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('first_name')" required />
                <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
            </div>

            <div>
                <x-input-label for="last_name" :value="__('Last name')" class="!text-[12.1px]" />
                <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('last_name')" />
                <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
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

            <div class="flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button>{{ __('Add lead') }}</x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>

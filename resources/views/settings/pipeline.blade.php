<x-app-layout>
    <x-slot name="header">
        <div class="relative top-1 -left-8 h-10 flex items-center">
            <h2 class="font-semibold text-sm text-gray-200 leading-tight">{{ __('Settings') }}</h2>
        </div>
    </x-slot>

    <div class="p-3 sm:p-4 min-h-full">
        <div class="p-3 sm:p-4 bg-white shadow sm:rounded-lg min-h-full">
            @include('settings.partials.tabs', ['page' => 'pipeline'])

            <p class="mb-4 text-[12.1px] text-gray-500 max-w-xl">
                {{ __('These are the columns a lead of this type and property type moves through, in order, from the first to the last. Add the ones you use; there are none until you do.') }}
            </p>

            <form
                method="post"
                action="{{ route('settings.pipeline.update') }}"
                x-data="{
                    rows: {{ Illuminate\Support\Js::from($rows) }},
                    max: {{ $maxStages }},
                    add() { if (this.rows.length >= this.max) return; this.rows.push({ key: null, label: '' }); this.$nextTick(() => this.$refs.list.querySelector('[data-row]:last-child input[type=text]')?.focus()); },
                    remove(i) { this.rows.splice(i, 1); },
                    move(i, by) { const j = i + by; if (j < 0 || j >= this.rows.length) return; [this.rows[i], this.rows[j]] = [this.rows[j], this.rows[i]]; },
                }"
                class="space-y-3"
            >
                @csrf
                @method('put')
                <input type="hidden" name="type" value="{{ $leadType }}">
                <input type="hidden" name="property_type" value="{{ $propertyType }}">

                <div x-ref="list" class="space-y-2">
                    <template x-for="(row, i) in rows" :key="i + '-' + (row.key ?? 'new')">
                        <div data-row class="flex items-center gap-2">
                            <span class="w-5 text-[12.1px] text-gray-400 text-right" x-text="i + 1"></span>
                            <input type="hidden" :name="`stages[${i}][key]`" :value="row.key ?? ''">
                            <input type="text" :name="`stages[${i}][label]`" x-model="row.label" maxlength="60" placeholder="{{ __('Column name') }}" class="field-slim block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md !text-[12.1px]">
                            <button type="button" @click="move(i, -1)" class="field-h px-1 text-gray-400 hover:text-gray-700" aria-label="{{ __('Move up') }}" title="{{ __('Move up') }}">&uarr;</button>
                            <button type="button" @click="move(i, 1)" class="field-h px-1 text-gray-400 hover:text-gray-700" aria-label="{{ __('Move down') }}" title="{{ __('Move down') }}">&darr;</button>
                            <button type="button" @click="remove(i)" class="field-h px-1 text-red-600 hover:text-red-800" aria-label="{{ __('Remove') }}" title="{{ __('Remove') }}">&times;</button>
                        </div>
                    </template>

                    <p x-show="rows.length === 0" class="text-[12.1px] text-gray-400">{{ __('No columns yet.') }}</p>
                </div>

                <p x-show="rows.length >= max" style="display: none;" class="text-[12.1px] text-gray-500">{{ __('A funnel can have at most :max columns.', ['max' => $maxStages]) }}</p>
                <x-input-error :messages="$errors->get('stages')" />

                <div class="flex items-center gap-3 pt-2">
                    <x-cancel-button type="button" x-show="rows.length < max" @click="add()">{{ __('Add column') }}</x-cancel-button>
                    <x-save-button>{{ __('Save') }}</x-save-button>
                </div>

                <p class="text-[12.1px] text-gray-400 max-w-xl">
                    {{ __('Removing a column moves its leads back to the column they have earned from their documents.') }}
                </p>
            </form>
        </div>
    </div>
</x-app-layout>

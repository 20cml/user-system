<x-app-layout>
    <x-slot name="header">
        <div class="relative top-1 -left-8 h-10 flex items-center">
            <h2 class="font-semibold text-sm text-gray-200 leading-tight">{{ __('Settings') }}</h2>
        </div>
    </x-slot>

    <div class="p-3 sm:p-4 min-h-full">
        <div class="p-3 sm:p-4 bg-white shadow sm:rounded-lg min-h-full">
            @include('settings.partials.tabs', ['page' => 'documents'])

            @if ($stages->isEmpty())
                <p class="text-[12.1px] text-gray-500 max-w-xl">
                    {{ __('There are no columns for this lead type and property type yet.') }}
                    <a href="{{ route('settings.pipeline', ['type' => $leadType, 'property_type' => $propertyType]) }}" class="underline hover:text-gray-800">{{ __('Add columns first') }}</a>,
                    {{ __('then choose the documents each one asks for.') }}
                </p>
            @else
                <p class="mb-4 text-[12.1px] text-gray-500 max-w-xl">
                    {{ __('The documents each column asks for. A lead moves to the next column once all the documents of the columns before it are ticked; a column with no documents is passed automatically.') }}
                </p>

                <form
                    method="post"
                    action="{{ route('settings.documents.update') }}"
                    x-data="{
                        stages: {{ Illuminate\Support\Js::from($stages) }},
                        max: {{ $maxDocuments }},
                        add(stage) { if (stage.rows.length < this.max) stage.rows.push({ key: null, label: '' }); },
                        remove(stage, i) { stage.rows.splice(i, 1); },
                    }"
                    class="space-y-5"
                >
                    @csrf
                    @method('put')
                    <input type="hidden" name="type" value="{{ $leadType }}">
                    <input type="hidden" name="property_type" value="{{ $propertyType }}">

                    <template x-for="stage in stages" :key="stage.key">
                        <div class="border-l-2 border-gray-200 pl-4">
                            <h3 class="text-[12.1px] font-semibold text-gray-900 mb-2" x-text="stage.label"></h3>

                            <div class="space-y-2">
                                <template x-for="(row, i) in stage.rows" :key="i + '-' + (row.key ?? 'new')">
                                    <div class="flex items-center gap-2">
                                        <input type="hidden" :name="`documents[${stage.key}][${i}][key]`" :value="row.key ?? ''">
                                        <input type="text" :name="`documents[${stage.key}][${i}][label]`" x-model="row.label" maxlength="120" placeholder="{{ __('Document name') }}" class="field-slim block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md !text-[12.1px]">
                                        <button type="button" @click="remove(stage, i)" class="field-h px-1 text-red-600 hover:text-red-800" aria-label="{{ __('Remove') }}" title="{{ __('Remove') }}">&times;</button>
                                    </div>
                                </template>

                                <p x-show="stage.rows.length === 0" class="text-[12.1px] text-gray-400">{{ __('No documents.') }}</p>
                            </div>

                            <button type="button" x-show="stage.rows.length < max" @click="add(stage)" class="mt-2 text-[12.1px] text-gray-600 underline hover:text-gray-900">{{ __('Add document') }}</button>
                            <p x-show="stage.rows.length >= max" style="display: none;" class="mt-2 text-[12.1px] text-gray-500">{{ __('A column can ask for at most :max documents.', ['max' => $maxDocuments]) }}</p>
                        </div>
                    </template>

                    <x-input-error :messages="collect($errors->get('documents.*'))->flatten()->unique()->all()" />

                    <div class="flex items-center gap-3">
                        <x-save-button>{{ __('Save') }}</x-save-button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>

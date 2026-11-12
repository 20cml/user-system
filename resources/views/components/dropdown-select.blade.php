{{-- The one dropdown look used across the app: bold text with a chevron, opening a small
     rounded list (the same as the listing's "For sale" / "Condo" menus). Picking an option
     sets a hidden field and, if `submit` names a form, saves it right away.

     @props: name     — the hidden field's name
             options  — [value => label]
             selected — the current value
             form     — id of the form the hidden field belongs to
             submit   — id of a form to submit when an option is picked
             width    — Tailwind width of the opened list --}}
@props(['name', 'options', 'selected' => null, 'form' => null, 'submit' => null, 'width' => 'w-44'])

<div
    {{ $attributes->class(['relative']) }}
    x-data="{ open: false, value: {{ \Illuminate\Support\Js::from((string) ($selected ?? '')) }}, labels: {{ \Illuminate\Support\Js::from($options) }} }"
    @click.outside="open = false"
>
    <input type="hidden" name="{{ $name }}" :value="value" @if ($form) form="{{ $form }}" @endif>

    <button type="button" @click="open = !open" class="field-h flex items-center gap-1 text-[12.1px] text-gray-700 hover:text-gray-900 cursor-pointer">
        <span class="font-bold text-gray-900" x-text="labels[value] ?? ''"></span>
        <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <div x-show="open" x-transition class="absolute left-0 top-full mt-1 z-20 {{ $width }} max-h-60 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-lg py-1" style="display: none;">
        @foreach ($options as $value => $label)
            <button
                type="button"
                @click="value = {{ \Illuminate\Support\Js::from((string) $value) }}; open = false; @if ($submit) $nextTick(() => document.getElementById('{{ $submit }}').submit()) @endif"
                class="block w-full text-left px-3 py-1.5 text-[12.1px] text-gray-700 hover:bg-gray-50"
                :class="value === {{ \Illuminate\Support\Js::from((string) $value) }} ? 'font-semibold text-gray-900' : ''"
            >{{ $label }}</button>
        @endforeach
    </div>
</div>

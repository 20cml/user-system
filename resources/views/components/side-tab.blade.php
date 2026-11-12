{{-- Red tab on the right edge of the white card; clicking it slides out a panel over the page,
     and clicking anywhere else closes it. `open` is the name of the Alpine boolean it toggles
     (declared by the caller); `top` stacks several tabs; `panel` sets the panel's width/colours. --}}
@props(['open', 'label', 'top' => 'top-8', 'panel' => 'w-64 bg-yellow-50 border-yellow-200 pt-2 px-3 pb-4'])

<button
    type="button"
    @click="{{ $open }} = !{{ $open }}"
    class="absolute right-0 {{ $top }} z-30 [writing-mode:vertical-rl] px-1 py-1.5 bg-red-600 text-white text-[10px] font-bold tracking-wider uppercase rounded-l-md shadow-lg hover:bg-red-700 transition"
>
    {{ $label }}
</button>

<div
    x-show="{{ $open }}"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-x-4"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-4"
    @click.outside="{{ $open }} = false"
    class="absolute right-10 top-4 z-30 border rounded-lg shadow-lg {{ $panel }}"
    style="display: none;"
>
    {{ $slot }}
</div>

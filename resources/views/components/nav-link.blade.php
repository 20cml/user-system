@props(['active' => false, 'label' => null])

<a {{ $attributes->merge([
        'class' => 'flex items-center justify-center h-11 w-11 rounded-full transition '
            . (($active ?? false) ? '' : 'text-gray-300 hover:bg-gray-600 hover:text-white'),
        'aria-label' => $label,
        'title' => $label,
    ]) }}>
    @if ($active ?? false)
        <span class="flex items-center justify-center h-9 w-9 rounded-full bg-gray-500 text-white">
            {{ $slot }}
        </span>
    @else
        {{ $slot }}
    @endif
</a>

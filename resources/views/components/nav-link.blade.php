@props(['active' => false, 'label' => null])

@php
$classes = ($active ?? false)
            ? 'flex items-center justify-center h-11 w-11 rounded-xl bg-red-50 text-red-600 transition'
            : 'flex items-center justify-center h-11 w-11 rounded-xl text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition';
@endphp

<a {{ $attributes->merge(['class' => $classes, 'aria-label' => $label, 'title' => $label]) }}>
    {{ $slot }}
</a>

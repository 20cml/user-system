{{-- The compact destructive button, the same height as <x-save-button>. --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'field-h px-3 rounded-md bg-red-600 text-[12.1px] font-medium text-white hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition']) }}>
    {{ $slot }}
</button>

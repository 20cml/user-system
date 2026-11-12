{{-- The compact secondary button (Cancel and the like), the same height as <x-save-button>. --}}
<button {{ $attributes->merge(['type' => 'button', 'class' => 'field-h px-3 rounded-md bg-white border border-gray-300 text-[12.1px] font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition']) }}>
    {{ $slot }}
</button>

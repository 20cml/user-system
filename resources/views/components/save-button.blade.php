{{-- The compact primary button (Save, Add lead, Add listing...) used across the app. Change its
     look here and every Save button follows. Pass `form="..."` when the button
     sits outside the <form> it submits. --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'field-h px-3 rounded-md bg-gray-800 text-[12.1px] font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition']) }}>
    {{ $slot }}
</button>

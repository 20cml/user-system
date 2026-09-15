<x-app-layout>
    @php
        $hour = now()->hour;
        $greeting = $hour < 12 ? __('Good morning') : ($hour < 18 ? __('Good afternoon') : __('Good evening'));
    @endphp

    <x-slot name="header">
        <div class="h-10 flex items-center justify-between">
            <h2 class="font-semibold text-base text-gray-800 leading-tight">
                {{ $greeting }}, {{ auth()->user()->first_name }}
            </h2>
        </div>
    </x-slot>

    <div class="pt-6 pb-12">
        <div class="px-8 sm:px-10">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

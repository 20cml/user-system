<x-app-layout>
    <x-slot name="header">
        <div class="h-10 flex items-center justify-between">
            <h2 class="font-semibold text-base text-gray-800 leading-tight">
                {{ __('Profile') }}
            </h2>
        </div>
    </x-slot>

    <div class="pt-2 pb-12">
        <div class="px-8 sm:px-10 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

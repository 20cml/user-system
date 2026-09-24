{{-- Personal Info node. Expects `$lead` and an enclosing `personalOpen` Alpine var.
     Pass `$form` (a form id) when these fields sit outside their `<form>` tag. --}}
@php $form = $form ?? null; @endphp
<div class="pl-6 -ml-px border-l-2 border-transparent py-3">
    <div class="flex items-start gap-6">
        <button type="button" @click="personalOpen = !personalOpen" class="shrink-0 w-36 flex items-center gap-2 text-[12.1px] font-medium" :class="personalOpen ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900'">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            {{ __('Personal Info') }}
            <svg class="h-3 w-3 transition-transform" :class="personalOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </button>

        <div x-show="personalOpen" class="flex-1 border-l border-gray-100 pl-6">
            <div class="border-l-2 border-gray-200 pl-6 space-y-4">
                <div>
                    <x-input-label for="first_name" :value="__('First name')" class="!text-[12.1px]" />
                    <x-text-input id="first_name" :form="$form" name="first_name" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('first_name', $lead->first_name)" required />
                    <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
                </div>

                <div>
                    <x-input-label for="last_name" :value="__('Last name')" class="!text-[12.1px]" />
                    <x-text-input id="last_name" :form="$form" name="last_name" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('last_name', $lead->last_name)" />
                    <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
                </div>

                <div>
                    <x-input-label for="phone" :value="__('Phone')" class="!text-[12.1px]" />
                    <x-text-input id="phone" :form="$form" name="phone" type="text" class="mt-1 block w-full !text-[12.1px]" :value="old('phone', $lead->phone)" />
                    <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                </div>

                <div>
                    <x-input-label for="email" :value="__('Email')" class="!text-[12.1px]" />
                    <x-text-input id="email" :form="$form" name="email" type="email" class="mt-1 block w-full !text-[12.1px]" :value="old('email', $lead->email)" />
                    <x-input-error class="mt-2" :messages="$errors->get('email')" />
                </div>

                <x-primary-button :form="$form">{{ __('Save') }}</x-primary-button>
            </div>
        </div>
    </div>
</div>

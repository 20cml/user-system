<x-app-layout>
    <x-slot name="header">
        <div class="relative top-1 -left-8 h-10 flex items-center justify-between"
             x-data="{
                 greeting: (() => {
                     const hour = new Date().getHours();
                     return hour < 12 ? '{{ __('Good morning') }}' : (hour < 18 ? '{{ __('Good afternoon') }}' : '{{ __('Good evening') }}');
                 })()
             }">
            <h2 class="font-semibold text-sm text-gray-200 leading-tight" x-text="greeting + ', {{ auth()->user()->first_name }}'">
                {{ auth()->user()->first_name }}
            </h2>

            <div class="relative -top-2.5 mr-2 flex items-center gap-2">
                <x-dropdown align="right" width="w-40" rounded="rounded-2xl" content-classes="bg-white px-2 py-2">
                    <x-slot name="trigger">
                        <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 rounded-full font-medium text-[12.1px] leading-none text-gray-800 hover:bg-gray-50">
                            {{ match ($pipeline) {
                                'seller' => __('Seller Pipeline'),
                                'renter' => __('Renter Pipeline'),
                                'landlord' => __('Landlord Pipeline'),
                                default => __('Buyer Pipeline'),
                            } }}
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <a href="{{ route('dashboard', ['pipeline' => 'buyer']) }}" class="flex items-center justify-between px-3 py-2 text-[12.1px] text-gray-900 rounded-lg hover:bg-gray-50">
                            {{ __('Buyer') }}
                            @if ($pipeline === 'buyer')
                                <svg class="h-4 w-4 text-gray-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            @endif
                        </a>
                        <a href="{{ route('dashboard', ['pipeline' => 'renter']) }}" class="flex items-center justify-between px-3 py-2 text-[12.1px] text-gray-900 rounded-lg hover:bg-gray-50">
                            {{ __('Renter') }}
                            @if ($pipeline === 'renter')
                                <svg class="h-4 w-4 text-gray-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            @endif
                        </a>
                        <a href="{{ route('dashboard', ['pipeline' => 'seller']) }}" class="flex items-center justify-between px-3 py-2 text-[12.1px] text-gray-900 rounded-lg hover:bg-gray-50">
                            {{ __('Seller') }}
                            @if ($pipeline === 'seller')
                                <svg class="h-4 w-4 text-gray-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            @endif
                        </a>
                        <a href="{{ route('dashboard', ['pipeline' => 'landlord']) }}" class="flex items-center justify-between px-3 py-2 text-[12.1px] text-gray-900 rounded-lg hover:bg-gray-50">
                            {{ __('Landlord') }}
                            @if ($pipeline === 'landlord')
                                <svg class="h-4 w-4 text-gray-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            @endif
                        </a>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </x-slot>

    <div class="pt-6 pb-4 flex-1 flex flex-col min-h-0">
        <div class="px-4 sm:px-6 flex-1 flex flex-col min-h-0">
            <div class="flex gap-4 overflow-x-auto pb-2 flex-1 min-h-0">
                @foreach ($stageLabels as $status => $label)
                    <div class="shrink-0 w-72 flex flex-col">
                        <div class="flex items-center gap-2 px-1 mb-2">
                            <h3 class="text-sm font-semibold text-gray-800">{{ __($label) }}</h3>
                            <span class="text-xs text-gray-500">{{ $leadsByStatus[$status]->count() }}</span>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-2 space-y-2 flex-1 min-h-0 overflow-y-auto">
                            @forelse ($leadsByStatus[$status] as $lead)
                                <a href="{{ route('leads.edit', $lead) }}" class="block bg-white border border-gray-200 rounded-lg p-3 hover:shadow-md transition">
                                    <p class="text-sm font-medium text-gray-900">{{ $lead->name }}</p>
                                    @if ($lead->phone)
                                        <p class="text-xs text-gray-500 mt-1">{{ $lead->phone }}</p>
                                    @endif
                                    @if ($lead->email)
                                        <p class="text-xs text-gray-500">{{ $lead->email }}</p>
                                    @endif
                                    <p class="text-xs text-gray-400 mt-1">{{ __('Added') }} {{ $lead->created_at->format('M j, Y') }}</p>
                                </a>
                            @empty
                                <p class="text-xs text-gray-400 text-center py-4">{{ __('No leads') }}</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="pt-3">
                <span class="inline-flex items-center px-3 py-1.5 rounded-full bg-gray-200 text-xs font-medium text-gray-700">
                    {{ $leadsByStatus->sum(fn ($leads) => $leads->count()) }} {{ __('leads') }}
                </span>
            </div>
        </div>
    </div>
</x-app-layout>

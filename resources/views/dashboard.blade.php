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
        </div>
    </x-slot>

    <div class="pt-6 pb-4 flex-1 flex flex-col min-h-0">
        <div class="px-4 sm:px-6 flex-1 flex flex-col min-h-0">
            <div class="flex gap-4 overflow-x-auto pb-2 flex-1 min-h-0">
                @foreach ([
                    'new' => __('New'),
                    'contacted' => __('Contacted'),
                    'qualified' => __('Qualified'),
                    'active_search' => __('Active Search'),
                    'visited' => __('Visited'),
                    'proposal' => __('Proposal'),
                    'closed' => __('Closed'),
                    'lost' => __('Lost'),
                ] as $status => $label)
                    <div class="shrink-0 w-72 flex flex-col">
                        <div class="flex items-center gap-2 px-1 mb-2">
                            <h3 class="text-sm font-semibold text-gray-800">{{ $label }}</h3>
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

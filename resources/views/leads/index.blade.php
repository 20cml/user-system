<x-app-layout>
    <x-slot name="header">
        <div class="relative top-1 -left-8 h-10 flex items-center justify-between">
            <h2 class="font-semibold text-sm text-gray-200 leading-tight">
                {{ __('My Leads') }}
            </h2>

            <div class="relative -top-2.5 mr-2 flex items-center gap-2">
                <x-dropdown align="right" width="w-48" rounded="rounded-2xl" content-classes="bg-white px-4 py-3" :close-on-click="false">
                    <x-slot name="trigger">
                        <button type="button"
                                class="h-7 w-7 flex items-center justify-center rounded-full bg-gray-700 border border-gray-400 text-gray-400 hover:bg-gray-600"
                                aria-label="{{ __('Filter leads') }}"
                                title="{{ __('Filter leads') }}">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" d="M4 6h16M7 12h10M10 18h4" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <form method="get" action="{{ route('leads.index') }}">
                            <input type="text" name="name" value="{{ request('name') }}" placeholder="{{ __('Filter...') }}" autocomplete="off" class="block w-full border-0 border-b border-gray-200 focus:border-gray-300 focus:ring-0 px-0 pb-2 text-[13px] placeholder-gray-400">

                            <p class="pt-3 pb-1 text-xs font-medium text-gray-400">{{ __('Status') }}</p>
                            @foreach (['new' => 'New', 'qualified' => 'Qualified', 'visited' => 'Visited', 'proposal' => 'Proposal', 'closed' => 'Closed', 'lost' => 'Lost'] as $value => $label)
                                <label class="flex items-center justify-between py-1.5 text-[13px] text-gray-900">
                                    {{ __($label) }}
                                    <input type="checkbox" name="status[]" value="{{ $value }}" onchange="this.form.submit()" class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-0" @checked(in_array($value, (array) request('status', [])))>
                                </label>
                            @endforeach
                        </form>
                    </x-slot>
                </x-dropdown>

                <a href="{{ route('leads.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 rounded-full font-medium text-xs text-gray-800 hover:bg-gray-50">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('Add lead') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="pt-6 pb-4 flex-1 flex flex-col min-h-0">
        <div class="px-4 sm:px-6 flex-1 flex flex-col min-h-0">
            <div class="bg-white shadow rounded-xl overflow-auto flex-1 min-h-0">
                <table class="min-w-full table-fixed divide-y divide-gray-100">
                    <thead>
                        <tr class="bg-gray-50 text-left font-normal text-gray-500 font-sans">
                            <th class="w-1/4 px-6 py-3 text-[12.1px] font-normal">{{ __('Name') }}</th>
                            <th class="w-1/4 px-6 py-3 text-[12.1px] font-normal">{{ __('Phone') }}</th>
                            <th class="w-1/4 px-6 py-3 text-[12.1px] font-normal">{{ __('Email') }}</th>
                            <th class="w-1/4 px-6 py-3 text-[12.1px] font-normal">{{ __('Status') }}</th>
                            <th class="w-10 px-2 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($leads as $lead)
                            <tr class="group hover:bg-gray-100 font-sans">
                                <td class="px-6 py-2 text-[12.1px] font-normal text-gray-900">
                                    <span class="inline-block -mx-3 px-3 py-1 rounded-full transition-colors duration-200 group-hover:bg-gray-200">{{ $lead->name }}</span>
                                </td>
                                <td class="px-6 py-2 text-[12.1px] text-gray-700 truncate">{{ $lead->phone }}</td>
                                <td class="px-6 py-2 text-[12.1px] text-gray-700 truncate">{{ $lead->email }}</td>
                                <td class="px-6 py-2">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[12.1px] font-normal bg-gray-100 text-gray-700">
                                        {{ ucfirst($lead->status) }}
                                    </span>
                                </td>
                                <td class="px-2 py-2">
                                    <a href="{{ route('leads.edit', $lead) }}"
                                       class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 h-7 w-7 flex items-center justify-center rounded-full border border-gray-300 text-gray-600 hover:bg-gray-50"
                                       aria-label="{{ __('Edit lead') }}"
                                       title="{{ __('Edit lead') }}">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                            <circle cx="5" cy="12" r="1.5" />
                                            <circle cx="12" cy="12" r="1.5" />
                                            <circle cx="19" cy="12" r="1.5" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-500" colspan="5">{{ __('No leads yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pt-3">
                <span class="inline-flex items-center px-3 py-1.5 rounded-full bg-gray-200 text-xs font-medium text-gray-700">
                    {{ $leads->count() }} {{ __('leads') }}
                </span>
            </div>
        </div>
    </div>
</x-app-layout>

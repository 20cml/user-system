@forelse ($leads as $lead)
    <tr class="group hover:bg-gray-100 font-sans">
        <td class="px-6 py-2 text-[12.1px] font-normal text-gray-900">
            <a href="{{ route('leads.edit', $lead) }}" class="inline-flex items-center gap-2 -mx-3 px-3 py-1 rounded-full transition-colors duration-200 group-hover:bg-gray-200">
                @if ($lead->type)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 text-gray-700">
                        {{ ucfirst($lead->type) }}
                    </span>
                @endif
                {{ $lead->name }}
            </a>
        </td>
        <td class="px-6 py-2 text-[12.1px] text-gray-700 truncate">{{ $lead->phone }}</td>
        <td class="px-6 py-2 text-[12.1px] text-gray-700 truncate">{{ $lead->email }}</td>
        <td class="px-6 py-2">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[12.1px] font-normal bg-gray-100 text-gray-700">
                {{ ucfirst($lead->status) }}
            </span>
        </td>
    </tr>
@empty
    <tr>
        <td class="px-6 py-4 text-sm text-gray-500" colspan="4">{{ __('No leads yet.') }}</td>
    </tr>
@endforelse

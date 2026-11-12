{{-- Contents of the Notes side tab, shared by leads and listings.
     $subject: the Lead or Listing; $routePrefix: 'leads' or 'listings' --}}
<h3 class="text-[12.1px] font-semibold text-yellow-900 mb-2">{{ __('Notes') }}</h3>

<form method="post" action="{{ route($routePrefix.'.notes.store', $subject) }}" class="space-y-2 mb-3">
    @csrf
    <div class="relative">
        <textarea name="body" rows="2" class="block w-full bg-white border-yellow-200 focus:border-yellow-400 focus:ring-yellow-400 rounded-md shadow-sm pr-9 !text-[12.1px]" placeholder="{{ __('Log a call, message, or visit...') }}"></textarea>
        <button type="submit" class="absolute bottom-1.5 right-1.5 h-6 w-6 flex items-center justify-center rounded-full bg-yellow-400 text-white hover:bg-yellow-500" aria-label="{{ __('Add note') }}" title="{{ __('Add note') }}">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
        </button>
    </div>
    <x-input-error :messages="$errors->get('body')" />
</form>

<div class="space-y-1 max-h-64 overflow-y-auto">
    @forelse ($subject->notes as $note)
        <div class="text-[12.1px] bg-white/70 rounded-md p-1.5 flex items-start justify-between gap-2">
            <div>
                <p class="text-gray-800">{{ $note->body }}</p>
                <p class="text-[11px] text-gray-400">{{ $note->created_at->format('M j, Y g:ia') }}</p>
            </div>
            <form
                method="post"
                action="{{ route($routePrefix.'.notes.destroy', [$subject, $note]) }}"
                onsubmit="return confirm('{{ __('Delete this note?') }}');"
            >
                @csrf
                @method('delete')
                <button type="submit" class="text-[12.1px] text-red-600 hover:text-red-800" aria-label="{{ __('Delete') }}">&times;</button>
            </form>
        </div>
    @empty
        <p class="text-[12.1px] text-yellow-700/70">{{ __('No notes yet.') }}</p>
    @endforelse
</div>

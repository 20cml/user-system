{{-- The compact primary button (Save, Add lead, Add listing...) used across the app. Change its
     look here and every Save button follows. Pass `form="..."` when the button
     sits outside the <form> it submits.

     A button labelled "Save" also confirms itself: once the page comes back after that button
     submitted its form (and nothing failed validation), "Saved." appears beside it for a few
     seconds. The page reloads on save, so the button remembers it was the one pressed in
     sessionStorage. Other labels (Add lead, Got it...) just render the button. --}}
@php($isSave = trim((string) $slot) === __('Save'))

@if ($isSave)
    <span
        class="inline-flex items-center gap-2"
        x-data="{
            saved: false,
            index() { return [...document.querySelectorAll('[data-save-button]')].indexOf(this.$refs.button); },
            init() {
                try {
                    const form = this.$refs.button.form;
                    form?.addEventListener('submit', (event) => {
                        if (event.submitter === this.$refs.button) {
                            sessionStorage.setItem('savedBy', JSON.stringify({ index: this.index(), at: Date.now() }));
                        }
                    });

                    const mark = JSON.parse(sessionStorage.getItem('savedBy') || 'null');
                    if (mark && mark.index === this.index() && Date.now() - mark.at < 10000 && {{ $errors->isEmpty() ? 'true' : 'false' }}) {
                        sessionStorage.removeItem('savedBy');
                        this.saved = true;
                        setTimeout(() => this.saved = false, 4000);
                    }
                } catch (e) {}
            },
        }"
    >
        <button x-ref="button" data-save-button {{ $attributes->merge(['type' => 'submit', 'class' => 'field-h px-3 rounded-md bg-gray-800 text-[12.1px] font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition']) }}>
            {{ $slot }}
        </button>
        <span x-show="saved" x-transition.opacity style="display: none;" class="text-[12.1px] text-gray-500">{{ __('Saved.') }}</span>
    </span>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => 'field-h px-3 rounded-md bg-gray-800 text-[12.1px] font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition']) }}>
        {{ $slot }}
    </button>
@endif

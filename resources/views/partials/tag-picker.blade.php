@php
    $selected = $selected ?? [];
@endphp

<div class="tag-picker" data-search-url="{{ $searchUrl }}" data-field-name="{{ $name }}">
    <input type="text" class="tag-picker-input mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" placeholder="{{ $placeholder }}" autocomplete="off">
    <div class="tag-picker-suggestions mt-1 text-sm space-y-1"></div>
    <div class="tag-picker-chips mt-2 flex flex-wrap gap-2">
        @foreach ($selected as $item)
            <span class="tag-picker-chip inline-flex items-center gap-1 bg-gray-100 rounded-full pl-3 pr-2 py-1 text-sm">
                {{ $item['label'] }}
                <button type="button" class="tag-picker-remove h-5 w-5 flex items-center justify-center text-gray-500 hover:text-red-600" aria-label="{{ __('Remove :label', ['label' => $item['label']]) }}">&times;</button>
                <input type="hidden" name="{{ $name }}[]" value="{{ $item['id'] }}">
            </span>
        @endforeach
    </div>
</div>

<script>
    (function () {
        document.querySelectorAll('.tag-picker').forEach(function (picker) {
            if (picker.dataset.wired) {
                return;
            }
            picker.dataset.wired = 'true';

            var input = picker.querySelector('.tag-picker-input');
            var suggestionsBox = picker.querySelector('.tag-picker-suggestions');
            var chipsBox = picker.querySelector('.tag-picker-chips');
            var searchUrl = picker.dataset.searchUrl;
            var fieldName = picker.dataset.fieldName;
            var debounceTimer = null;

            function selectedIds() {
                return Array.from(chipsBox.querySelectorAll('input[type="hidden"]')).map(function (el) {
                    return String(el.value);
                });
            }

            function wireRemove(chip) {
                chip.querySelector('.tag-picker-remove').addEventListener('click', function () {
                    chip.remove();
                });
            }

            chipsBox.querySelectorAll('.tag-picker-chip').forEach(wireRemove);

            function addChip(item) {
                if (selectedIds().indexOf(String(item.id)) !== -1) {
                    return;
                }

                var chip = document.createElement('span');
                chip.className = 'tag-picker-chip inline-flex items-center gap-1 bg-gray-100 rounded-full pl-3 pr-2 py-1 text-sm';

                var label = document.createTextNode(item.label);
                chip.appendChild(label);

                var removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'tag-picker-remove h-5 w-5 flex items-center justify-center text-gray-500 hover:text-red-600';
                removeButton.innerHTML = '&times;';
                removeButton.setAttribute('aria-label', 'Remove ' + item.label);
                chip.appendChild(removeButton);

                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = fieldName + '[]';
                hidden.value = item.id;
                chip.appendChild(hidden);

                chipsBox.appendChild(chip);
                wireRemove(chip);
            }

            function clearSuggestions() {
                suggestionsBox.innerHTML = '';
            }

            function renderSuggestions(items) {
                clearSuggestions();

                items.forEach(function (item) {
                    if (selectedIds().indexOf(String(item.id)) !== -1) {
                        return;
                    }

                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'underline text-indigo-600 hover:text-indigo-900 block';
                    button.textContent = item.label;
                    button.addEventListener('click', function () {
                        addChip(item);
                        clearSuggestions();
                        input.value = '';
                    });
                    suggestionsBox.appendChild(button);
                });
            }

            function fetchSuggestions(query) {
                fetch(searchUrl + '?query=' + encodeURIComponent(query), {
                    headers: { Accept: 'application/json' },
                })
                    .then(function (response) { return response.ok ? response.json() : []; })
                    .then(renderSuggestions)
                    .catch(clearSuggestions);
            }

            input.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                var query = input.value.trim();

                if (query.length < 1) {
                    clearSuggestions();
                    return;
                }

                debounceTimer = setTimeout(function () { fetchSuggestions(query); }, 300);
            });
        });
    })();
</script>

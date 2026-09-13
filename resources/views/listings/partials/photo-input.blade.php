<input id="photo" name="photo" type="file" accept="image/*" class="mt-1 block w-full text-sm text-gray-600" />

<div id="photo-preview-wrapper" class="mt-2 relative inline-block hidden">
    <img id="photo-preview" alt="" class="h-24 w-24 object-cover rounded">
    <button
        type="button"
        id="photo-preview-clear"
        class="absolute -top-2 -right-2 h-5 w-5 flex items-center justify-center rounded-full bg-red-600 text-white text-xs leading-none hover:bg-red-700"
        title="{{ __('Remove selected photo') }}"
    >&times;</button>
</div>

<script>
    (function () {
        var input = document.getElementById('photo');
        var wrapper = document.getElementById('photo-preview-wrapper');
        var preview = document.getElementById('photo-preview');
        var clearButton = document.getElementById('photo-preview-clear');

        if (!input) {
            return;
        }

        input.addEventListener('change', function () {
            var file = input.files[0];

            if (!file) {
                wrapper.classList.add('hidden');
                preview.src = '';
                return;
            }

            preview.src = URL.createObjectURL(file);
            wrapper.classList.remove('hidden');
        });

        clearButton.addEventListener('click', function () {
            input.value = '';
            wrapper.classList.add('hidden');
            preview.src = '';
        });
    })();
</script>

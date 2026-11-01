<div data-image-uploader data-max-files="{{ $maxImages }}">
    <label for="images" class="{{ $labelClasses }}">Agregar fotografías</label>
    <label
        for="images"
        data-image-dropzone
        class="mt-2 flex min-h-36 cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-white px-5 py-7 text-center transition hover:border-teal-600 hover:bg-teal-50/50"
    >
        <span class="grid h-11 w-11 place-items-center rounded-full bg-teal-50 text-2xl font-bold text-teal-800" aria-hidden="true">＋</span>
        <span class="mt-3 text-sm font-bold text-slate-800">Arrastra tus fotos aquí o selecciona archivos</span>
        <span class="mt-1 text-xs text-slate-500">JPG, PNG o WebP; máximo 15 MB por foto.</span>
        <input
            type="file"
            name="images[]"
            id="images"
            multiple
            accept="image/jpeg,image/png,image/webp"
            @disabled($maxImages === 0)
            class="sr-only"
        >
    </label>
    <p class="mt-2 text-xs text-slate-500">
        Puedes añadir fotos en varias selecciones. Máximo 20 fotografías por inmueble.
    </p>
    @error('images')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
    @error('images.*')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
    <p class="mt-2 text-sm text-teal-800" data-image-selection-status role="status" aria-live="polite"></p>
    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" data-image-previews></div>
</div>

<script>
    (() => {
        const uploader = document.querySelector('[data-image-uploader]');
        if (!uploader) {
            return;
        }

        const input = uploader.querySelector('input[type="file"]');
        const dropzone = uploader.querySelector('[data-image-dropzone]');
        const previews = uploader.querySelector('[data-image-previews]');
        const status = uploader.querySelector('[data-image-selection-status]');
        const maximum = Number(uploader.dataset.maxFiles);
        let selectedFiles = [];
        let previewUrls = [];
        let selectionMessage = '';

        const renderPreviews = () => {
            previewUrls.forEach((url) => URL.revokeObjectURL(url));
            previewUrls = [];
            previews.replaceChildren();

            const transfer = new DataTransfer();
            selectedFiles.forEach((file, index) => {
                transfer.items.add(file);

                const card = document.createElement('article');
                card.className = 'overflow-hidden rounded-xl border border-slate-200 bg-white p-2';

                const image = document.createElement('img');
                const previewUrl = URL.createObjectURL(file);
                previewUrls.push(previewUrl);
                image.src = previewUrl;
                image.alt = `Vista previa de ${file.name}`;
                image.className = 'h-28 w-full rounded-lg object-cover';

                const name = document.createElement('p');
                name.className = 'mt-2 truncate text-xs font-semibold text-slate-700';
                name.textContent = file.name;

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'mt-2 w-full rounded-lg border border-rose-200 px-2 py-1.5 text-xs font-bold text-rose-700 transition hover:bg-rose-50';
                removeButton.textContent = 'Quitar';
                removeButton.addEventListener('click', () => {
                    selectedFiles.splice(index, 1);
                    renderPreviews();
                });

                card.append(image, name, removeButton);
                previews.append(card);
            });

            input.files = transfer.files;
            status.textContent = selectionMessage || (selectedFiles.length === 0
                ? (maximum === 0 ? 'El inmueble ya alcanzó el máximo de fotografías. Elimina una para agregar otra.' : '')
                : `${selectedFiles.length} ${selectedFiles.length === 1 ? 'foto seleccionada' : 'fotos seleccionadas'}.`);
        };

        const addFiles = (files) => {
            const incomingFiles = Array.from(files);
            const imageFiles = incomingFiles.filter((file) => file.type.startsWith('image/'));
            const remaining = Math.max(0, maximum - selectedFiles.length);
            selectionMessage = '';

            if (imageFiles.length !== incomingFiles.length) {
                selectionMessage = 'Selecciona únicamente archivos de imagen.';
            } else if (imageFiles.length > remaining) {
                selectionMessage = `Solo puedes agregar ${remaining} ${remaining === 1 ? 'foto más' : 'fotos más'}.`;
            }

            selectedFiles = selectedFiles.concat(imageFiles.slice(0, remaining));
            renderPreviews();
        };

        input.addEventListener('change', () => {
            const incomingFiles = Array.from(input.files);
            input.value = '';
            addFiles(incomingFiles);
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.add('border-teal-700', 'bg-teal-50');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.remove('border-teal-700', 'bg-teal-50');
            });
        });

        dropzone.addEventListener('drop', (event) => addFiles(event.dataTransfer.files));
        renderPreviews();
    })();
</script>

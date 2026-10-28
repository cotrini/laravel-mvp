@extends('layouts.app')

@section('title', isset($tour) ? 'Editar recorrido 360°' : 'Crear recorrido 360°')

@section('content')
    @php
        $tourScenes = old('scenes', isset($tour)
            ? $tour->scenes->map(fn ($scene) => [
                'id' => $scene->id,
                'title' => $scene->title,
                'caption' => $scene->caption,
                'image_url' => $scene->image_url,
                'sort_order' => $scene->sort_order,
            ])->all()
            : [['title' => '', 'caption' => '', 'image_url' => '', 'sort_order' => 0]]);
        $isEditing = isset($tour);
    @endphp

    <style>
        .tour-form-shell {
            width: min(980px, 100%);
            margin: 0 auto;
            padding: 34px 0 60px;
        }

        .tour-form-heading {
            margin-bottom: 23px;
        }

        .tour-form-eyebrow {
            margin: 0 0 7px;
            color: #12877b;
            font-size: 11px;
            font-weight: 850;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .tour-form-heading h1 {
            margin: 0;
            color: #242424;
            font-size: clamp(28px, 4vw, 38px);
            font-weight: 850;
            letter-spacing: -.045em;
        }

        .tour-form-heading p:last-child {
            margin: 9px 0 0;
            color: #69717d;
            font-size: 14px;
        }

        .tour-form-card {
            display: grid;
            gap: 22px;
            padding: clamp(20px, 4vw, 34px);
            border: 1px solid #e7e9ed;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 5px 18px rgb(31 41 55 / 5%);
        }

        .tour-form-section {
            display: grid;
            gap: 16px;
        }

        .tour-form-section + .tour-form-section {
            padding-top: 22px;
            border-top: 1px solid #edf0f2;
        }

        .tour-form-section h2 {
            margin: 0;
            color: #242424;
            font-size: 18px;
            font-weight: 820;
        }

        .tour-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .tour-form-field {
            display: grid;
            gap: 7px;
            color: #485262;
            font-size: 12px;
            font-weight: 750;
        }

        .tour-form-field--wide {
            grid-column: 1 / -1;
        }

        .tour-form-field input,
        .tour-form-field textarea {
            width: 100%;
            min-height: 44px;
            padding: 10px 12px;
            border: 1px solid #d9dee6;
            border-radius: 10px;
            outline: none;
            color: #2d3440;
            font: inherit;
            font-size: 13px;
            font-weight: 450;
        }

        .tour-form-field textarea {
            min-height: 92px;
            resize: vertical;
        }

        .tour-form-field input:focus,
        .tour-form-field textarea:focus {
            border-color: #12877b;
            box-shadow: 0 0 0 3px rgb(18 135 123 / 11%);
        }

        .tour-scene-card {
            display: grid;
            gap: 13px;
            padding: 17px;
            border: 1px solid #e7e9ed;
            border-radius: 14px;
            background: #f8faf9;
        }

        .tour-scene-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .tour-scene-heading strong {
            color: #12877b;
            font-size: 13px;
        }

        .tour-scene-heading span {
            color: #69717d;
            font-size: 11px;
        }

        .tour-form-error {
            margin: 0;
            color: #b42318;
            font-size: 12px;
            font-weight: 650;
        }

        .tour-form-status {
            color: #12877b;
            font-size: 12px;
            font-weight: 650;
        }

        .tour-form-status.tour-form-error {
            color: #b42318;
        }

        .tour-published-toggle {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #485262;
            font-size: 13px;
            font-weight: 650;
        }

        .tour-form-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 11px;
            padding-top: 6px;
        }

        .tour-form-button,
        .tour-add-scene,
        .tour-remove-scene {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 16px;
            border: 0;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            transition: transform 160ms ease, background 160ms ease;
        }

        .tour-form-button:hover,
        .tour-add-scene:hover,
        .tour-remove-scene:hover {
            transform: translateY(-1px);
        }

        .tour-form-button {
            background: #ffe22d;
            color: #242424;
        }

        .tour-form-button:hover,
        .tour-add-scene:hover {
            background: #f4d116;
        }

        .tour-add-scene {
            justify-self: start;
            background: #e8f5f2;
            color: #12877b;
        }

        .tour-add-scene:disabled {
            cursor: not-allowed;
            opacity: .55;
            transform: none;
        }

        .tour-remove-scene {
            min-height: 30px;
            padding: 0 10px;
            background: #fff0ed;
            color: #b42318;
            font-size: 11px;
        }

        .tour-cancel-link {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            padding: 0 15px;
            border: 1px solid #d9dee6;
            border-radius: 10px;
            color: #485262;
            font-size: 13px;
            font-weight: 750;
        }

        @media (max-width: 620px) {
            .tour-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="page-shell">
        <div class="tour-form-shell">
            <header class="tour-form-heading">
                <p class="tour-form-eyebrow">{{ $isEditing ? 'Administrar recorrido' : 'Contenido interactivo' }}</p>
                <h1>{{ $isEditing ? 'Editar recorrido 360°' : 'Crear recorrido 360°' }}</h1>
                <p>Inmueble: <strong>{{ $property->title }}</strong> · {{ $property->city }}</p>
            </header>

            @if($errors->any())
                <div class="dashboard-notice" role="alert">
                    Revisa los campos marcados y vuelve a guardar el recorrido: {{ $errors->first() }}
                </div>
            @endif

            @if(session('error'))
                <div class="dashboard-notice" role="alert">{{ session('error') }}</div>
            @endif

            <form
                action="{{ $isEditing ? route('tours360.update', $tour->id) : route('tours360.store') }}"
                method="POST"
                enctype="multipart/form-data"
                class="tour-form-card"
            >
                @csrf
                <input type="hidden" name="property_id" value="{{ $property->id }}">
                @if($isEditing)
                    @method('PUT')
                @endif

                <section class="tour-form-section" aria-labelledby="tour-info-heading">
                    <h2 id="tour-info-heading">Información del recorrido</h2>
                    <div class="tour-form-grid">
                        <label class="tour-form-field tour-form-field--wide">
                            Nombre del recorrido
                            <input type="text" name="title" value="{{ old('title', $tour->title ?? 'Recorrido 360° - ' . $property->title) }}" maxlength="255" required>
                            @error('title')<span class="tour-form-error">{{ $message }}</span>@enderror
                        </label>
                        <label class="tour-form-field tour-form-field--wide">
                            Descripción
                            <textarea name="description" maxlength="5000">{{ old('description', $tour->description ?? '') }}</textarea>
                            @error('description')<span class="tour-form-error">{{ $message }}</span>@enderror
                        </label>
                        <label class="tour-form-field">
                            Ubicación
                            <input type="text" name="location" value="{{ old('location', $tour->location ?? trim(implode(', ', array_filter([$property->neighborhood, $property->city, $property->department])))) }}" maxlength="255">
                            @error('location')<span class="tour-form-error">{{ $message }}</span>@enderror
                        </label>
                        <label class="tour-form-field">
                            Imagen de portada (URL)
                            <input type="url" name="cover_url" value="{{ old('cover_url', $tour->cover_url ?? '') }}" maxlength="2048" placeholder="https://...">
                            @error('cover_url')<span class="tour-form-error">{{ $message }}</span>@enderror
                        </label>
                    </div>
                    <label class="tour-published-toggle">
                        <input type="hidden" name="is_published" value="0">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $tour->is_published ?? true))>
                        Publicar el recorrido para que sea visible a los visitantes
                    </label>
                </section>

                <section class="tour-form-section" aria-labelledby="tour-scenes-heading">
                    <h2 id="tour-scenes-heading">Escenas 360°</h2>
                    <p class="dashboard-muted">Agrega hasta 30 escenas. Selecciona una foto para subirla de inmediato; solo podrás agregar la siguiente escena cuando la foto se haya cargado correctamente o tenga un enlace HTTPS válido.</p>
                    <div id="tour-scenes-list" class="tour-form-section">
                        @foreach($tourScenes as $index => $scene)
                            <article class="tour-scene-card" data-scene-card>
                                <div class="tour-scene-heading">
                                    <strong data-scene-title>Escena {{ $index + 1 }}</strong>
                                    @if(! isset($scene['id']))
                                        <button type="button" class="tour-remove-scene" data-remove-scene>Quitar escena</button>
                                    @else
                                        <span>Escena existente</span>
                                    @endif
                                </div>
                                @if(isset($scene['id']))
                                    <input type="hidden" name="scenes[{{ $index }}][id]" value="{{ $scene['id'] }}">
                                @endif
                                <input type="hidden" name="scenes[{{ $index }}][sort_order]" value="{{ $scene['sort_order'] ?? $index }}">
                                <input type="hidden" name="scenes[{{ $index }}][staged_image]" value="{{ $scene['staged_image'] ?? '' }}" data-field="staged_image">
                                <div class="tour-form-grid">
                                    <label class="tour-form-field">
                                        Título de la escena
                                        <input type="text" name="scenes[{{ $index }}][title]" value="{{ $scene['title'] }}" maxlength="255" required>
                                        @error('scenes.' . $index . '.title')<span class="tour-form-error">{{ $message }}</span>@enderror
                                    </label>
                                    <label class="tour-form-field">
                                        Enlace HTTPS de imagen panorámica 360° (opcional si subes un archivo)
                                        <input type="url" name="scenes[{{ $index }}][image_url]" value="{{ $scene['image_url'] }}" maxlength="2048" placeholder="https://...">
                                        @error('scenes.' . $index . '.image_url')<span class="tour-form-error">{{ $message }}</span>@enderror
                                    </label>
                                    <label class="tour-form-field">
                                        Subir imagen panorámica 360° (JPEG o PNG, máximo 15 MB)
                                        <input type="file" accept="image/jpeg,image/png,.jpg,.jpeg,.png" data-upload-image>
                                        @error('scenes.' . $index . '.image')<span class="tour-form-error">{{ $message }}</span>@enderror
                                        <span class="tour-form-status" data-upload-status role="status"></span>
                                    </label>
                                    <label class="tour-form-field tour-form-field--wide">
                                        Descripción breve (opcional)
                                        <input type="text" name="scenes[{{ $index }}][caption]" value="{{ $scene['caption'] ?? '' }}" maxlength="1000">
                                    </label>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    @error('scenes')<span class="tour-form-error">{{ $message }}</span>@enderror
                    <button type="button" class="tour-add-scene" id="tour-add-scene" disabled>+ Agregar escena</button>
                    <p class="tour-form-error" id="tour-scene-limit-error" role="alert" hidden>Un recorrido puede tener como máximo 30 escenas.</p>
                </section>

                <div class="tour-form-actions">
                    <a href="{{ route('dashboard.index') }}" class="tour-cancel-link">Cancelar</a>
                    <button type="submit" class="tour-form-button">{{ $isEditing ? 'Guardar cambios' : 'Crear recorrido' }}</button>
                </div>
            </form>
        </div>
    </div>

    <template id="tour-scene-template">
        <article class="tour-scene-card" data-scene-card>
            <div class="tour-scene-heading">
                <strong data-scene-title></strong>
                <button type="button" class="tour-remove-scene" data-remove-scene>Quitar escena</button>
            </div>
            <input type="hidden" data-field="sort_order">
            <div class="tour-form-grid">
                <label class="tour-form-field">
                    Título de la escena
                    <input type="text" data-field="title" maxlength="255" required>
                </label>
                <label class="tour-form-field">
                    Enlace HTTPS de imagen panorámica 360° (opcional si subes un archivo)
                    <input type="url" data-field="image_url" maxlength="2048" placeholder="https://...">
                </label>
                <label class="tour-form-field">
                    Subir imagen panorámica 360° (JPEG o PNG, máximo 15 MB)
                    <input type="file" accept="image/jpeg,image/png,.jpg,.jpeg,.png" data-upload-image>
                    <span class="tour-form-status" data-upload-status role="status"></span>
                </label>
                <input type="hidden" data-field="staged_image">
                <label class="tour-form-field tour-form-field--wide">
                    Descripción breve (opcional)
                    <input type="text" data-field="caption" maxlength="1000">
                </label>
            </div>
        </article>
    </template>

    <script>
        (() => {
            const list = document.getElementById('tour-scenes-list');
            const template = document.getElementById('tour-scene-template');
            const form = document.querySelector('.tour-form-card');
            const addSceneButton = document.getElementById('tour-add-scene');
            const sceneLimitError = document.getElementById('tour-scene-limit-error');
            const maxScenes = 30;
            const maxImageBytes = @json(15 * 1024 * 1024);
            const sceneImageUploadUrl = @json(route('tours360.scene-images.store', [], false));
            let nextSceneIndex = {{ count($tourScenes) }};

            const updateSceneLabels = () => {
                const cards = list.querySelectorAll('[data-scene-card]');
                cards.forEach((card, index) => {
                    const label = card.querySelector('[data-scene-title]');
                    if (label) {
                        label.textContent = `Escena ${index + 1}`;
                    }
                });
                const everySceneHasImage = Array.from(cards).every((card) => {
                    const stagedImage = card.querySelector('[data-field="staged_image"]')?.value;
                    const imageUrl = card.querySelector('input[name$="[image_url]"], [data-field="image_url"]')?.value.trim();
                    return card.dataset.uploading !== 'true'
                        && (Boolean(stagedImage) || /^https:\/\//i.test(imageUrl ?? ''));
                });
                addSceneButton.disabled = cards.length >= maxScenes || !everySceneHasImage;
                sceneLimitError.hidden = cards.length < maxScenes;
            };

            addSceneButton.addEventListener('click', () => {
                if (list.querySelectorAll('[data-scene-card]').length >= maxScenes) {
                    updateSceneLabels();
                    return;
                }

                const card = template.content.firstElementChild.cloneNode(true);
                card.querySelectorAll('[data-field]').forEach((field) => {
                    field.name = `scenes[${nextSceneIndex}][${field.dataset.field}]`;
                });
                list.append(card);
                nextSceneIndex += 1;
                updateSceneLabels();
            });

            list.addEventListener('click', (event) => {
                const removeButton = event.target.closest('[data-remove-scene]');
                if (removeButton) {
                    removeButton.closest('[data-scene-card]').remove();
                    updateSceneLabels();
                }
            });

            list.addEventListener('change', (event) => {
                const imageInput = event.target.closest('[data-upload-image]');
                const imageFile = imageInput?.files[0];

                if (!imageInput || !imageFile) {
                    return;
                }

                const status = imageInput.closest('[data-scene-card]').querySelector('[data-upload-status]');
                status.classList.remove('tour-form-error');

                if (imageFile.size > maxImageBytes) {
                    imageInput.value = '';
                    status.classList.add('tour-form-error');
                    status.textContent = 'La foto supera el máximo de 15 MB. Elige una imagen más liviana.';
                    return;
                }

                const card = imageInput.closest('[data-scene-card]');
                const stagedImageInput = card.querySelector('[data-field="staged_image"]');
                card.dataset.uploading = 'true';
                imageInput.disabled = true;
                status.classList.remove('tour-form-error');
                status.textContent = `Subiendo ${imageFile.name} (${(imageFile.size / 1024 / 1024).toFixed(2)} MB)...`;
                updateSceneLabels();

                const uploadData = new FormData();
                uploadData.append('property_id', form.elements.namedItem('property_id').value);
                uploadData.append('image', imageFile);

                fetch(sceneImageUploadUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': form.elements.namedItem('_token').value,
                    },
                    body: uploadData,
                    credentials: 'same-origin',
                }).then(async (response) => {
                    const contentType = response.headers.get('content-type') ?? '';
                    const result = contentType.includes('application/json')
                        ? await response.json()
                        : {};

                    if (!response.ok || typeof result.path !== 'string') {
                        const message = result.errors?.image?.[0]
                            ?? result.errors?.property_id?.[0]
                            ?? (response.status === 413
                                ? 'La foto supera el límite de carga del servidor. Reduce el tamaño e inténtalo de nuevo.'
                                : null)
                            ?? (response.status === 419
                                ? 'La sesión expiró. Recarga la página e inténtalo de nuevo.'
                                : null)
                            ?? (response.status >= 500
                                ? `El servidor no pudo guardar la foto (error ${response.status}). Inténtalo de nuevo.`
                                : result.message)
                            ?? `No se pudo guardar la foto (respuesta HTTP ${response.status}).`;

                        throw new Error(message);
                    }

                    stagedImageInput.value = result.path;
                    imageInput.value = '';
                    status.textContent = 'Foto cargada correctamente. Ya puedes agregar otra escena.';
                }).catch((error) => {
                    status.classList.add('tour-form-error');
                    status.textContent = error instanceof TypeError
                        ? 'No hubo conexión con el servidor. Verifica tu conexión e inténtalo de nuevo.'
                        : error.message;
                    imageInput.value = '';
                }).finally(() => {
                    card.dataset.uploading = 'false';
                    imageInput.disabled = false;
                    updateSceneLabels();
                });
            });

            list.addEventListener('input', (event) => {
                if (event.target.matches('input[name$="[image_url]"], [data-field="image_url"]')) {
                    updateSceneLabels();
                }
            });

            form.addEventListener('submit', (event) => {
                const incompleteCard = Array.from(list.querySelectorAll('[data-scene-card]')).find((card) => {
                    const stagedImage = card.querySelector('[data-field="staged_image"]')?.value;
                    const imageUrl = card.querySelector('input[name$="[image_url]"], [data-field="image_url"]')?.value.trim();
                    return card.dataset.uploading === 'true'
                        || (!stagedImage && !/^https:\/\//i.test(imageUrl ?? ''));
                });

                if (incompleteCard) {
                    event.preventDefault();
                    const status = incompleteCard.querySelector('[data-upload-status]');
                    status.classList.add('tour-form-error');
                    status.textContent = incompleteCard.dataset.uploading === 'true'
                        ? 'Espera a que termine de subir esta foto antes de guardar el recorrido.'
                        : 'Sube una foto o agrega un enlace HTTPS antes de guardar el recorrido.';
                    incompleteCard.querySelector('[data-upload-image]').focus();
                    return;
                }

                const submitButton = form.querySelector('button[type="submit"]');
                submitButton.disabled = true;
            });

            updateSceneLabels();
        })();
    </script>
@endsection

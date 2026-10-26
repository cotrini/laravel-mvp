@extends('layouts.app')

@section('title', 'Mi panel - Colombia Sin Comisión')

@section('content')
    <div class="dashboard-shell">
        <section class="dashboard-heading">
            <div>
                <p class="dashboard-eyebrow">Panel de control</p>
                <h1>Mis inmuebles</h1>
                <p>Hola, {{ $user->name }}. Administra tus publicaciones, medios y recorridos.</p>
            </div>
            <a href="{{ route('properties.create') }}" class="dashboard-add-button">
                <span aria-hidden="true">+</span> Publicar inmueble
            </a>
            @can('manage-location-catalog')
                <a href="{{ route('location-catalog.index') }}" class="dashboard-add-button">
                    Administrar ubicaciones
                </a>
            @endcan
        </section>

        @if(session('success'))
            <div class="dashboard-notice" role="status">{{ session('success') }}</div>
        @endif

        <section class="dashboard-stats" aria-label="Resumen de publicaciones">
            <article class="dashboard-stat">
                <span>Total de inmuebles</span>
                <strong>{{ $stats['total_properties'] }}</strong>
            </article>
            <article class="dashboard-stat">
                <span>Publicados</span>
                <strong>{{ $stats['published_properties'] }}</strong>
            </article>
            <article class="dashboard-stat">
                <span>Pendientes</span>
                <strong>{{ $stats['pending_properties'] }}</strong>
            </article>
        </section>

        <section class="dashboard-table-card" aria-labelledby="dashboard-properties-title">
            <div class="dashboard-table-heading">
                <div>
                    <h2 id="dashboard-properties-title">Tus publicaciones</h2>
                    <p>Administra los medios y recorridos de cada inmueble.</p>
                </div>
            </div>

            @if($properties->isNotEmpty())
                <div class="dashboard-table-scroll">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th scope="col">Inmueble</th>
                                <th scope="col">Precio</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Recorrido 360°</th>
                                <th scope="col">Video</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($properties as $property)
                                @php
                                    $previewImage = $property->media->where('type', 'image')->firstWhere('is_primary', true)
                                        ?? $property->media->firstWhere('type', 'image');
                                    $propertyVideo = $property->media->firstWhere('type', 'video');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="dashboard-property">
                                            <a href="{{ route('properties.show', $property->id) }}" class="dashboard-property-image" aria-label="Ver {{ $property->title }}">
                                                @if($previewImage)
                                                    <img src="{{ asset($previewImage->url_or_path) }}" alt="{{ $property->title }}" loading="lazy">
                                                @else
                                                    <span aria-hidden="true">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="m3 10 9-7 9 7"></path><path d="M5 9v11h14V9"></path><path d="M9 20v-6h6v6"></path>
                                                        </svg>
                                                    </span>
                                                @endif
                                            </a>
                                            <div class="dashboard-property-copy">
                                                <a href="{{ route('properties.show', $property->id) }}">{{ $property->title }}</a>
                                                <span>{{ ucfirst($property->property_type) }} · {{ $property->city }}</span>
                                                <span class="dashboard-operation-badge {{ $property->listing_type === 'alquiler' ? 'dashboard-operation-badge--rental' : ($property->listing_type === 'venta' ? 'dashboard-operation-badge--sale' : '') }}">
                                                    {{ $property->listing_type === 'alquiler' ? 'En alquiler' : ($property->listing_type === 'venta' ? 'En venta' : 'Operación sin especificar') }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="dashboard-price">
                                        ${{ number_format($property->price, 0, ',', '.') }}
                                        @if($property->listing_type === 'alquiler')
                                            <span class="dashboard-muted">/ mes</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="dashboard-status">{{ ucfirst($property->status) }}</span>
                                    </td>
                                    <td>
                                        @if($property->tour)
                                            <div class="dashboard-media-actions">
                                                <a href="{{ route('tours360.show', $property->tour->slug) }}" class="dashboard-action-button dashboard-action-button--view">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                    Ver
                                                </a>
                                                <a href="{{ route('tours360.edit', $property->tour->id) }}" class="dashboard-action-button dashboard-action-button--edit">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="m15 5 4 4M4 20l4-.8L19 8a2.8 2.8 0 0 0-4-4L4 15l0 5Z"></path>
                                                    </svg>
                                                    Editar
                                                </a>
                                                <form action="{{ route('tours360.destroy', $property->tour->id) }}" method="POST" onsubmit="return confirm('¿Eliminar el recorrido 360° de este inmueble?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dashboard-action-button dashboard-action-button--delete">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m4 4v6m6-6v6"></path>
                                                        </svg>
                                                        Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <a href="{{ route('tours360.create', ['property_id' => $property->id]) }}" class="dashboard-action-button dashboard-action-button--video">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <circle cx="12" cy="12" r="9"></circle><path d="M12 8v8m-4-4h8"></path>
                                                </svg>
                                                Agregar recorrido 360°
                                            </a>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="dashboard-video-actions">
                                            @if($propertyVideo)
                                                <a href="{{ asset($propertyVideo->url_or_path) }}" target="_blank" rel="noopener noreferrer" class="dashboard-video-link">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="m9 6 10 6-10 6V6Z"></path><rect x="3" y="3" width="18" height="18" rx="3"></rect>
                                                    </svg>
                                                    Ver video
                                                </a>
                                            @else
                                                <span class="dashboard-muted">Sin video</span>
                                            @endif
                                            <button
                                                type="button"
                                                class="dashboard-action-button dashboard-action-button--video"
                                                data-video-upload-url="{{ route('properties.video.store', $property->id) }}"
                                                data-property-id="{{ $property->id }}"
                                                data-property-title="{{ $property->title }}"
                                            >
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M12 15V3m0 0L7 8m5-5 5 5"></path><path d="M5 13v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6"></path>
                                                </svg>
                                                {{ $propertyVideo ? 'Cambiar video' : 'Agregar video' }}
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="dashboard-property-actions">
                                            <a href="{{ route('properties.show', $property->id) }}" class="dashboard-action-button dashboard-action-button--view">Ver</a>
                                            <a href="{{ route('properties.edit', $property->id) }}" class="dashboard-action-button dashboard-action-button--edit">Editar</a>
                                            <form action="{{ route('properties.destroy', $property->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta propiedad?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dashboard-action-button dashboard-action-button--delete">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="dashboard-pagination">
                    {{ $properties->links() }}
                </div>
            @else
                <div class="dashboard-empty">
                    <span class="dashboard-empty-icon" aria-hidden="true">⌂</span>
                    <h3>Aún no has publicado inmuebles</h3>
                    <p>Publica tu primer inmueble para comenzar a administrarlo desde aquí.</p>
                    <a href="{{ route('properties.create') }}" class="dashboard-add-button">Publicar inmueble</a>
                </div>
            @endif
        </section>
    </div>

    <dialog class="video-upload-dialog" id="video-upload-dialog" aria-labelledby="video-dialog-title">
        <form action="" method="POST" enctype="multipart/form-data" id="video-upload-form">
            @csrf
            <input type="hidden" name="property_id" id="video-property-id" value="{{ old('property_id') }}">
            <div class="video-dialog-heading">
                <div>
                    <p class="dashboard-eyebrow">Contenido del inmueble</p>
                    <h2 id="video-dialog-title">Agregar video</h2>
                    <p id="video-property-name"></p>
                </div>
                <button type="button" class="video-dialog-close" data-close-video-dialog aria-label="Cerrar ventana">×</button>
            </div>

            <label for="property-video" class="video-file-label">Selecciona un archivo MP4</label>
            <input
                type="file"
                name="video"
                id="property-video"
                accept="video/mp4,.mp4"
                required
                class="video-file-input"
                aria-describedby="video-file-help video-file-error"
            >
            <p class="video-file-help" id="video-file-help">Máximo 1 minuto y 100 MB. Recomendado: Full HD (1080p).</p>
            <p class="video-file-error" id="video-file-error" role="alert">@error('video'){{ $message }}@enderror</p>

            <div class="video-dialog-actions">
                <button type="button" class="video-cancel-button" data-close-video-dialog>Cancelar</button>
                <button type="submit" class="dashboard-add-button" id="video-submit-button">Guardar video</button>
            </div>
        </form>
    </dialog>

    <script>
        (() => {
            const dialog = document.getElementById('video-upload-dialog');
            const form = document.getElementById('video-upload-form');
            const fileInput = document.getElementById('property-video');
            const errorMessage = document.getElementById('video-file-error');
            const submitButton = document.getElementById('video-submit-button');
            const propertyTitle = document.getElementById('video-property-name');
            const propertyId = document.getElementById('video-property-id');
            const maxSize = 100 * 1024 * 1024;

            document.querySelectorAll('[data-video-upload-url]').forEach((button) => {
                button.addEventListener('click', () => {
                    form.action = button.dataset.videoUploadUrl;
                    propertyId.value = button.dataset.propertyId;
                    propertyTitle.textContent = button.dataset.propertyTitle;
                    fileInput.value = '';
                    errorMessage.textContent = '';
                    dialog.showModal();
                    fileInput.focus();
                });
            });

            document.querySelectorAll('[data-close-video-dialog]').forEach((button) => {
                button.addEventListener('click', () => dialog.close());
            });

            dialog.addEventListener('click', (event) => {
                if (event.target === dialog) {
                    dialog.close();
                }
            });

            fileInput.addEventListener('change', () => {
                errorMessage.textContent = '';
                submitButton.disabled = false;
                const file = fileInput.files[0];

                if (!file) {
                    return;
                }

                if (!file.name.toLowerCase().endsWith('.mp4') || (file.type && file.type !== 'video/mp4')) {
                    errorMessage.textContent = 'Selecciona un archivo de video MP4.';
                    fileInput.value = '';
                    return;
                }

                if (file.size > maxSize) {
                    errorMessage.textContent = 'El video no puede superar los 100 MB.';
                    fileInput.value = '';
                    return;
                }

                const video = document.createElement('video');
                const videoUrl = URL.createObjectURL(file);
                video.preload = 'metadata';
                video.onloadedmetadata = () => {
                    URL.revokeObjectURL(videoUrl);

                    if (video.duration > 60) {
                        errorMessage.textContent = 'El video no puede durar más de 1 minuto.';
                        fileInput.value = '';
                    }
                };
                video.onerror = () => {
                    URL.revokeObjectURL(videoUrl);
                    errorMessage.textContent = 'No se pudo leer el video. Verifica que sea un MP4 válido.';
                    fileInput.value = '';
                };
                video.src = videoUrl;
            });

            form.addEventListener('submit', (event) => {
                if (!fileInput.files.length) {
                    event.preventDefault();
                    errorMessage.textContent = 'Selecciona el video que deseas subir.';
                }
            });

            @if($errors->has('video') && old('property_id'))
                const failedUploadButton = document.querySelector(`[data-property-id="${CSS.escape(@js(old('property_id')))}"]`);
                if (failedUploadButton) {
                    failedUploadButton.click();
                    errorMessage.textContent = @js($errors->first('video'));
                }
            @endif
        })();
    </script>
@endsection

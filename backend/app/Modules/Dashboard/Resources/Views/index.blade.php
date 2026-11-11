@extends('layouts.app')

@section('title', 'Mi panel - Colombia Sin Comisión')

@section('content')
    <div class="dashboard-shell">
        <section class="dashboard-heading">
            <div>
                <p class="dashboard-eyebrow">Panel de control</p>
                <h1>{{ $user->isAdmin() ? 'Todos los inmuebles' : ($user->isAppraiser() || $user->isFilmmaker() ? 'Inmuebles asignados' : 'Mis inmuebles') }}</h1>
                <p>Hola, {{ $user->name }}. {{ $user->isAppraiser() ? 'Sube avalúos comerciales en PDF para los inmuebles asignados.' : ($user->isFilmmaker() ? 'Carga videos y recorridos 360° en los inmuebles asignados.' : 'Administra tus publicaciones, medios y recorridos.') }}</p>
            </div>
            @unless($user->isFilmmaker() || $user->isAppraiser())
                <a href="{{ route('properties.create') }}" class="dashboard-add-button">
                    <span aria-hidden="true">+</span> Publicar inmueble
                </a>
            @endunless
            @can('manage-users')
                <a href="{{ route('admin.users.index') }}" class="dashboard-add-button">
                    Administrar cuentas
                </a>
            @endcan
            @can('manage-location-catalog')
                <a href="{{ route('location-catalog.index') }}" class="dashboard-add-button">
                    Administrar ubicaciones
                </a>
            @endcan
            @can('manage-users')
                <a href="{{ route('admin.property-requests.index') }}" class="dashboard-add-button">
                    Solicitudes al administrador ({{ $pendingPropertyRequests }})
                </a>
                <a href="{{ route('admin.bank-interest-rates.index') }}" class="dashboard-add-button">
                    Intereses bancos
                </a>
            @endcan
        </section>

        @foreach(['success', 'warning'] as $noticeType)
            @if(session($noticeType))
                <div class="dashboard-notice" role="{{ $noticeType === 'warning' ? 'alert' : 'status' }}">{{ session($noticeType) }}</div>
            @endif
        @endforeach

        @if($errors->any())
            <div class="dashboard-notice" role="alert">
                {{ $errors->first() }}
            </div>
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
                    <h2 id="dashboard-properties-title">{{ $user->isAppraiser() ? 'Avalúos asignados' : 'Tus publicaciones' }}</h2>
                    <p>{{ $user->isAppraiser() ? 'Sube o reemplaza el PDF del avalúo comercial de cada inmueble.' : 'Administra los medios y recorridos de cada inmueble.' }}</p>
                </div>
            </div>

            @if($user->isAdmin())
                <form action="{{ route('dashboard.index') }}" method="GET" class="dashboard-property-filter">
                    <label for="property-search">Buscar por código, inmueble, ubicación o propietario</label>
                    <div class="dashboard-property-filter-controls">
                        <input
                            id="property-search"
                            name="search"
                            type="search"
                            value="{{ $search }}"
                            maxlength="100"
                            placeholder="Ej. CSC-00000001, ciudad o correo"
                        >
                        <button type="submit" class="dashboard-add-button">Buscar</button>
                        @if($search !== '')
                            <a class="dashboard-property-filter-clear" href="{{ route('dashboard.index') }}">Limpiar</a>
                        @endif
                    </div>
                </form>
            @endif

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
                                    $propertyAppraisal = $property->media->firstWhere('type', 'appraisal');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="dashboard-property">
                                            <a href="{{ route('properties.show', $property->id) }}" class="dashboard-property-image" aria-label="Ver {{ $property->title }}">
                                                @if($previewImage)
                                                    <img src="{{ $previewImage->public_url }}" alt="{{ $property->title }}" loading="lazy">
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
                                                <span>Código: {{ $property->identification_code }}</span>
                                                <span class="dashboard-operation-badge {{ $property->listing_type === 'alquiler' ? 'dashboard-operation-badge--rental' : ($property->listing_type === 'venta' ? 'dashboard-operation-badge--sale' : '') }}">
                                                    {{ $property->listing_type === 'alquiler' ? 'En alquiler' : ($property->listing_type === 'venta' ? 'En venta' : 'Operación sin especificar') }}
                                                </span>
                                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold {{ $property->is_verified ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600' }}">
                                                    {{ $property->is_verified ? '✓ Propiedad verificada' : 'Propiedad no verificada aún' }}
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
                                        @php
                                            $propertyStatusLabel = ! $property->is_active
                                                ? 'Inactiva'
                                                : match (strtolower($property->status ?? 'active')) {
                                                    'active' => 'Disponible',
                                                    'publicado', 'published' => 'Publicado',
                                                    'pendiente' => 'En revisión',
                                                    'pausado' => 'Pausado',
                                                    default => ucfirst($property->status ?? 'Disponible'),
                                                };
                                        @endphp
                                        <span class="dashboard-status">{{ $propertyStatusLabel }}</span>
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
                                                @if($user->isAdmin() || $user->isFilmmaker())
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
                                                @endif
                                            </div>
                                        @elseif($user->isAdmin() || $user->isFilmmaker())
                                            <a href="{{ route('tours360.create', ['property_id' => $property->id]) }}" class="dashboard-action-button dashboard-action-button--video">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <circle cx="12" cy="12" r="9"></circle><path d="M12 8v8m-4-4h8"></path>
                                                </svg>
                                                Agregar recorrido 360°
                                            </a>
                                        @else
                                            <span class="dashboard-muted">Sin recorrido 360°</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="dashboard-video-actions">
                                            @if($propertyVideo)
                                                <a href="{{ $propertyVideo->public_url }}" target="_blank" rel="noopener noreferrer" class="dashboard-video-link">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="m9 6 10 6-10 6V6Z"></path><rect x="3" y="3" width="18" height="18" rx="3"></rect>
                                                    </svg>
                                                    Ver video
                                                </a>
                                            @else
                                                <span class="dashboard-muted">Sin video</span>
                                            @endif
                                            @if($user->isAdmin() || $user->isFilmmaker())
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
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $isPropertyOwner = (int) $property->user_id === (int) $user->id;
                                            $canToggleActive = $user->isAdmin() || (! $user->isFilmmaker() && $isPropertyOwner);
                                            $isOwnerRequestingServices = ! $user->isAdmin() && ! $user->isFilmmaker() && $isPropertyOwner;
                                            $pendingRequestTypes = $property->requests
                                                ->where('status', \App\Modules\Property\Models\PropertyRequest::STATUS_PENDING)
                                                ->pluck('type');
                                            $propertyMediaRequestPending = $pendingRequestTypes->contains(\App\Modules\Property\Models\PropertyRequest::TYPE_VIDEO_AND_TOUR_360)
                                                || (! $propertyVideo && $pendingRequestTypes->contains(\App\Modules\Property\Models\PropertyRequest::TYPE_VIDEO))
                                                || (! $property->tour && $pendingRequestTypes->contains(\App\Modules\Property\Models\PropertyRequest::TYPE_TOUR_360));
                                            $missingPropertyMediaLabel = match (true) {
                                                ! $propertyVideo && ! $property->tour => 'Solicitar video y recorrido 360°',
                                                ! $propertyVideo => 'Solicitar video',
                                                default => 'Solicitar recorrido 360°',
                                            };
                                            $showVerificationToggle = $user->isAdmin();
                                            $showVerificationRequest = $isOwnerRequestingServices && ! $property->is_verified;
                                            $showMediaRequest = $isOwnerRequestingServices && (! $propertyVideo || ! $property->tour);
                                            $showAppraisalRequest = $isPropertyOwner && ! $propertyAppraisal;
                                            $showDeleteAction = ! $user->isFilmmaker();
                                            $hasMoreActions = $showVerificationToggle || $showVerificationRequest || $showMediaRequest || $showAppraisalRequest || $showDeleteAction;
                                            $menuItemClasses = 'flex w-full items-center gap-2 px-3 py-2 text-left text-xs font-semibold text-slate-700 hover:bg-slate-50';
                                            $quickButtonClasses = 'inline-flex h-8 items-center justify-center gap-1.5 rounded-lg px-2.5 text-xs font-bold whitespace-nowrap transition-colors';
                                        @endphp
                                        <div class="flex min-w-[190px] flex-col items-start gap-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <a href="{{ route('properties.show', $property->id) }}" class="{{ $quickButtonClasses }} bg-sky-50 text-sky-600 hover:bg-sky-100">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                    Ver
                                                </a>
                                                @unless($user->isAppraiser())
                                                    <a href="{{ route('properties.edit', $property->id) }}" class="{{ $quickButtonClasses }} bg-amber-50 text-amber-700 hover:bg-amber-100">
                                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <path d="m15 5 4 4M4 20l4-.8L19 8a2.8 2.8 0 0 0-4-4L4 15l0 5Z"></path>
                                                        </svg>
                                                        Editar
                                                    </a>
                                                @endunless
                                                @if($propertyAppraisal && ! $user->isAppraiser())
                                                    <a href="{{ route('properties.appraisals.show', $property) }}" class="{{ $quickButtonClasses }} bg-slate-100 text-slate-600 hover:bg-slate-200">
                                                        Ver avalúo
                                                    </a>
                                                @endif
                                            </div>
                                            @if($user->isAppraiser())
                                                @if($propertyAppraisal)
                                                    <span class="dashboard-muted">Avalúo comercial disponible</span>
                                                @endif
                                                <form class="dashboard-appraisal-upload grid w-full max-w-[230px] gap-2" action="{{ route('properties.appraisals.upload', $property) }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    <label class="dashboard-muted" for="appraisal_{{ $property->id }}">
                                                        {{ $propertyAppraisal ? 'Reemplazar avalúo PDF' : 'Subir avalúo PDF' }}
                                                    </label>
                                                    <input id="appraisal_{{ $property->id }}" type="file" name="appraisal" accept="application/pdf,.pdf" required>
                                                    <span class="dashboard-muted">PDF de hasta 20 MB</span>
                                                    <button type="submit" class="dashboard-action-button dashboard-action-button--video">
                                                        {{ $propertyAppraisal ? 'Actualizar avalúo' : 'Guardar avalúo' }}
                                                    </button>
                                                </form>
                                            @else
                                                <div class="flex flex-wrap items-center gap-2">
                                                    @if($isPropertyOwner)
                                                        <a href="{{ route('propiedades.cartel', $property->id) }}" target="_blank" rel="noopener noreferrer" class="{{ $quickButtonClasses }} bg-emerald-50 text-emerald-700 hover:bg-emerald-100" title="Abrir aviso imprimible con código QR">
                                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path d="M6 9V3h12v6"></path><rect x="3" y="9" width="18" height="9" rx="2"></rect><path d="M7 14h10v7H7z"></path>
                                                            </svg>
                                                            Aviso imprimible
                                                        </a>
                                                    @endif
                                                    @if($canToggleActive)
                                                        <form action="{{ route('properties.active.toggle', $property) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="{{ $quickButtonClasses }} bg-slate-100 text-slate-700 hover:bg-slate-200" title="{{ $property->is_active ? 'Desactivar propiedad' : 'Activar propiedad' }}">
                                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                    <path d="M12 3v9"></path><path d="M18.4 6.6a9 9 0 1 1-12.8 0"></path>
                                                                </svg>
                                                                {{ $property->is_active ? 'Desactivar' : 'Activar' }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if($hasMoreActions)
                                                        <details class="relative" data-actions-menu>
                                                            <summary class="flex h-8 w-8 cursor-pointer list-none items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 [&::-webkit-details-marker]:hidden" aria-label="Más acciones" title="Más acciones">
                                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                                    <circle cx="5" cy="12" r="1.8"></circle><circle cx="12" cy="12" r="1.8"></circle><circle cx="19" cy="12" r="1.8"></circle>
                                                                </svg>
                                                            </summary>
                                                            <div class="fixed z-50 w-60 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg" role="menu" data-actions-menu-panel>
                                                                @if($showVerificationToggle)
                                                                    <form action="{{ route('properties.verification.toggle', $property) }}" method="POST">
                                                                        @csrf
                                                                        <button type="submit" role="menuitem" class="{{ $menuItemClasses }}">
                                                                            {{ $property->is_verified ? 'Retirar verificación' : 'Verificar propiedad' }}
                                                                        </button>
                                                                    </form>
                                                                @endif
                                                                @if($showVerificationRequest)
                                                                    @if($pendingRequestTypes->contains(\App\Modules\Property\Models\PropertyRequest::TYPE_VERIFICATION))
                                                                        <span class="block px-3 py-2 text-xs text-slate-400">Solicitar verificación: pendiente</span>
                                                                    @else
                                                                        <form action="{{ route('properties.requests.store', $property) }}" method="POST">
                                                                            @csrf
                                                                            <input type="hidden" name="type" value="verification">
                                                                            <button type="submit" role="menuitem" class="{{ $menuItemClasses }}">
                                                                                Solicitar verificación
                                                                            </button>
                                                                        </form>
                                                                    @endif
                                                                @endif
                                                                @if($showMediaRequest)
                                                                    @if($propertyMediaRequestPending)
                                                                        <span class="block px-3 py-2 text-xs text-slate-400">{{ $missingPropertyMediaLabel }}: pendiente</span>
                                                                    @else
                                                                        <form action="{{ route('properties.requests.store', $property) }}" method="POST">
                                                                            @csrf
                                                                            <input type="hidden" name="type" value="{{ \App\Modules\Property\Models\PropertyRequest::TYPE_VIDEO_AND_TOUR_360 }}">
                                                                            <button type="submit" role="menuitem" class="{{ $menuItemClasses }}">
                                                                                {{ $missingPropertyMediaLabel }}
                                                                            </button>
                                                                        </form>
                                                                    @endif
                                                                @endif
                                                                @if($showAppraisalRequest)
                                                                    <form action="{{ route('properties.appraisals.request', $property) }}" method="POST">
                                                                        @csrf
                                                                        <button type="submit" role="menuitem" class="{{ $menuItemClasses }}">
                                                                            Solicitar avalúo comercial
                                                                        </button>
                                                                    </form>
                                                                @endif
                                                                @if($showDeleteAction)
                                                                    <form class="mt-1 border-t border-slate-100 pt-1" action="{{ route('properties.destroy', $property->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta propiedad?');">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" role="menuitem" class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs font-semibold text-red-600 hover:bg-red-50">
                                                                            Eliminar propiedad
                                                                        </button>
                                                                    </form>
                                                                @endif
                                                            </div>
                                                        </details>
                                                    @endif
                                                </div>
                                            @endif
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
                    <h3>{{ $user->isFilmmaker() || $user->isAppraiser() ? 'Aún no tienes inmuebles asignados' : 'Aún no has publicado inmuebles' }}</h3>
                    @unless($user->isFilmmaker() || $user->isAppraiser())
                        <p>Publica tu primer inmueble para comenzar a administrarlo desde aquí.</p>
                        <a href="{{ route('properties.create') }}" class="dashboard-add-button">Publicar inmueble</a>
                    @endunless
                </div>
            @endif
        </section>
    </div>

    @if($user->isAdmin() || $user->isFilmmaker())
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
    @endif

    <script>
        (() => {
            const menus = () => document.querySelectorAll('details[data-actions-menu]');
            const closeAll = (except = null) => menus().forEach((menu) => {
                if (menu !== except) {
                    menu.open = false;
                }
            });

            document.addEventListener('toggle', (event) => {
                const menu = event.target;

                if (!(menu instanceof HTMLDetailsElement) || !menu.hasAttribute('data-actions-menu') || !menu.open) {
                    return;
                }

                closeAll(menu);

                const panel = menu.querySelector('[data-actions-menu-panel]');
                const trigger = menu.querySelector('summary').getBoundingClientRect();
                const panelRect = panel.getBoundingClientRect();
                const opensUpwards = trigger.bottom + panelRect.height + 8 > window.innerHeight && trigger.top > panelRect.height + 8;

                panel.style.left = `${Math.max(8, trigger.right - panelRect.width)}px`;
                panel.style.top = `${opensUpwards ? trigger.top - panelRect.height - 4 : trigger.bottom + 4}px`;
            }, true);

            document.addEventListener('click', (event) => {
                if (!event.target.closest('details[data-actions-menu]')) {
                    closeAll();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeAll();
                }
            });

            window.addEventListener('resize', () => closeAll());
            window.addEventListener('scroll', () => closeAll(), true);
        })();
    </script>
@endsection

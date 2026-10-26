@extends('layouts.app')

@section('title', 'Editar inmueble - Colombia Sin Comisión')

@section('content')
    @php
        $inputClasses = 'mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-teal-700 focus:ring-4 focus:ring-teal-700/10';
        $labelClasses = 'block text-sm font-semibold text-slate-700';
    @endphp

    <main class="min-h-screen bg-slate-50 px-4 py-10 text-slate-800 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <a href="{{ route('dashboard.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-teal-800 transition hover:text-teal-950">
                <span aria-hidden="true">←</span> Volver al panel
            </a>

            <header class="mb-7 mt-5 flex items-start gap-4">
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-amber-100 text-2xl text-amber-700" aria-hidden="true">⌂</span>
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-teal-700">Administrar publicación</p>
                    <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Editar inmueble</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{{ $property->title }}</p>
                </div>
            </header>

            @if(session('success'))
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800" role="status">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
                    <p class="font-bold">Revisa la información ingresada:</p>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="mb-7 rounded-3xl border border-slate-200 bg-white p-5 shadow-[0_18px_55px_rgba(31,41,55,0.05)] sm:p-7" aria-labelledby="property-media-heading">
                <div class="mb-5">
                    <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-teal-700">Fotos y archivos</p>
                    <h2 id="property-media-heading" class="mt-1 text-xl font-extrabold text-slate-900">Contenido multimedia actual</h2>
                </div>

                @if($property->media->isNotEmpty())
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach($property->media as $mediaItem)
                            <article class="flex min-w-0 flex-col rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                @if($mediaItem->type === 'image')
                                    <img src="{{ asset($mediaItem->url_or_path) }}" alt="Foto del inmueble {{ $property->title }}" class="h-32 w-full rounded-xl object-cover">
                                @elseif($mediaItem->type === 'blueprint')
                                    <a href="{{ route('properties.blueprint.show', $property->id) }}" target="_blank" rel="noopener noreferrer" class="grid h-32 place-items-center rounded-xl bg-teal-50 px-3 text-center text-sm font-bold text-teal-800 transition hover:bg-teal-100">
                                        Ver planos PDF
                                    </a>
                                @elseif($mediaItem->type === 'video')
                                    <div class="grid h-32 place-items-center rounded-xl bg-slate-900 text-sm font-bold text-white">
                                        <span aria-hidden="true">▶</span> Video cargado
                                    </div>
                                @else
                                    <div class="grid h-32 place-items-center rounded-xl bg-amber-50 px-3 text-center text-sm font-bold uppercase text-amber-800">
                                        {{ str_replace('_', ' ', $mediaItem->type) }}
                                    </div>
                                @endif

                                <span class="my-3 truncate text-center text-xs font-semibold capitalize text-slate-600">{{ str_replace('_', ' ', $mediaItem->type) }}</span>

                                <form action="{{ route('media.destroy', $mediaItem->id) }}" method="POST" onsubmit="return confirm('¿Deseas eliminar este archivo multimedia?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-50">
                                        Eliminar archivo
                                    </button>
                                </form>
                            </article>
                        @endforeach
                    </div>
                @else
                    <p class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-600">
                        Este inmueble todavía no tiene archivos multimedia.
                    </p>
                @endif
            </section>

            @php($blueprint = $property->media->firstWhere('type', 'blueprint'))
            <section class="mb-7 rounded-3xl border border-slate-200 bg-white p-5 shadow-[0_18px_55px_rgba(31,41,55,0.05)] sm:p-7" aria-labelledby="property-blueprint-heading">
                <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-teal-700">Planos arquitectónicos</p>
                <h2 id="property-blueprint-heading" class="mt-1 text-xl font-extrabold text-slate-900">Administrar planos en PDF</h2>
                <p class="mt-2 text-sm text-slate-600">Admite archivos PDF de hasta 10 páginas y 10 MB.</p>
                @if($blueprint)
                    <a href="{{ route('properties.blueprint.show', $property) }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex rounded-lg bg-teal-50 px-4 py-2 text-sm font-bold text-teal-800 transition hover:bg-teal-100">
                        Ver planos actuales
                    </a>
                @endif
                <form action="{{ route('properties.blueprint.store', $property) }}" method="POST" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                    @csrf
                    <div class="flex-1">
                        <label for="blueprint" class="{{ $labelClasses }}">{{ $blueprint ? 'Reemplazar planos' : 'Seleccionar planos' }}</label>
                        <input type="file" name="blueprint" id="blueprint" accept="application/pdf,.pdf" required class="mt-2 block w-full rounded-xl border border-slate-200 bg-white text-sm text-slate-600 file:mr-4 file:border-0 file:bg-teal-50 file:px-4 file:py-3 file:font-bold file:text-teal-800 hover:file:bg-teal-100">
                        @error('blueprint')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-teal-800 px-6 text-sm font-extrabold text-white transition hover:bg-teal-900">
                        {{ $blueprint ? 'Actualizar planos' : 'Subir planos' }}
                    </button>
                </form>
            </section>

            <form action="{{ route('properties.update', $property->id) }}" method="POST" enctype="multipart/form-data" class="space-y-8 rounded-3xl border border-slate-200 bg-white p-5 shadow-[0_18px_55px_rgba(31,41,55,0.07)] sm:p-8 lg:p-10">
                @csrf
                @method('PUT')

                <section class="space-y-5" aria-labelledby="property-basics-heading">
                    <div class="border-b border-slate-100 pb-4">
                        <h2 id="property-basics-heading" class="text-lg font-extrabold text-slate-900">Información básica</h2>
                        <p class="mt-1 text-sm text-slate-500">Mantén actualizados el título, precio y tipo de inmueble.</p>
                    </div>

                    <div>
                        <label for="title" class="{{ $labelClasses }}">Título del inmueble</label>
                        <input type="text" name="title" id="title" value="{{ old('title', $property->title) }}" required maxlength="255" class="{{ $inputClasses }}">
                        @error('title')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label for="listing_type" class="{{ $labelClasses }}">Tipo de publicación</label>
                            <select name="listing_type" id="listing_type" required class="{{ $inputClasses }}">
                                <option value="">Selecciona una operación</option>
                                <option value="venta" @selected(old('listing_type', $property->listing_type) === 'venta')>Venta</option>
                                <option value="alquiler" @selected(old('listing_type', $property->listing_type) === 'alquiler')>Alquiler</option>
                            </select>
                            @error('listing_type')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="price" class="{{ $labelClasses }}">Precio / canon mensual (COP)</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center font-semibold text-slate-400">$</span>
                                <input type="number" name="price" id="price" value="{{ old('price', $property->price) }}" required min="1000000" max="{{ old('listing_type', $property->listing_type) === 'alquiler' ? 100000000 : 50000000000 }}" step="1" data-sale-max="50000000000" data-rental-max="100000000" class="{{ $inputClasses }} pl-9">
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Mínimo $1.000.000. Máximo: $50.000 millones en venta o $100 millones mensuales en alquiler.</p>
                            @error('price')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="property_type" class="{{ $labelClasses }}">Tipo de inmueble</label>
                            @php($selectedPropertyType = strtolower(old('property_type', $property->property_type)))
                            <select name="property_type" id="property_type" required class="{{ $inputClasses }}">
                                @foreach([
                                    'casa' => 'Casa',
                                    'apartamento' => 'Apartamento',
                                    'lote' => 'Lote',
                                    'finca' => 'Finca / casa campestre',
                                    'oficina' => 'Oficina',
                                    'comercial' => 'Propiedad comercial',
                                    'bodega' => 'Bodega',
                                    'local' => 'Local',
                                ] as $value => $label)
                                    <option value="{{ $value }}" @selected($selectedPropertyType === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('property_type')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="space-y-5" aria-labelledby="property-location-heading">
                    <div class="border-b border-slate-100 pb-4">
                        <h2 id="property-location-heading" class="text-lg font-extrabold text-slate-900">Ubicación</h2>
                        <p class="mt-1 text-sm text-slate-500">Verifica la ubicación que se mostrará a quienes visiten la publicación.</p>
                    </div>

                    @include('properties::partials.location-fields', [
                        'departments' => $departments,
                        'selectedDepartmentId' => $selectedDepartmentId,
                        'selectedCityId' => $selectedCityId,
                        'selectedNeighborhood' => old('neighborhood', $property->neighborhood),
                        'inputClasses' => $inputClasses,
                        'labelClasses' => $labelClasses,
                    ])
                </section>

                <section class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4 sm:p-6">
                    @include('properties::partials.details-fields', [
                        'property' => $property,
                        'inputClasses' => $inputClasses,
                        'labelClasses' => $labelClasses,
                        'isDark' => false,
                    ])
                </section>

                <section class="space-y-5">
                    <div>
                        <label for="description" class="{{ $labelClasses }}">Descripción del inmueble</label>
                        <textarea name="description" id="description" rows="6" required placeholder="Describe la distribución, el estado y los detalles que hacen especial esta propiedad." class="{{ $inputClasses }} resize-y">{{ old('description', $property->description) }}</textarea>
                        @error('description')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="status" class="{{ $labelClasses }}">Estado de publicación</label>
                        <select name="status" id="status" required class="{{ $inputClasses }}">
                            @foreach([
                                'publicado' => 'Publicado',
                                'pendiente' => 'En revisión / pendiente',
                                'pausado' => 'Pausado',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $property->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                </section>

                <section class="space-y-5 rounded-2xl border border-slate-100 bg-slate-50/70 p-4 sm:p-6" aria-labelledby="property-upload-heading">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-teal-700">Completa la publicación</p>
                        <h2 id="property-upload-heading" class="mt-1 text-lg font-extrabold text-slate-900">Agregar fotos y contenido</h2>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label for="images" class="{{ $labelClasses }}">Agregar fotos a la galería</label>
                            <input type="file" name="images[]" id="images" multiple accept="image/*" class="mt-2 block w-full rounded-xl border border-slate-200 bg-white text-sm text-slate-600 file:mr-4 file:border-0 file:bg-teal-50 file:px-4 file:py-3 file:font-bold file:text-teal-800 hover:file:bg-teal-100">
                            @error('images.*')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="video" class="{{ $labelClasses }}">Agregar video</label>
                            <input type="file" name="video" id="video" accept="video/*" class="mt-2 block w-full rounded-xl border border-slate-200 bg-white text-sm text-slate-600 file:mr-4 file:border-0 file:bg-teal-50 file:px-4 file:py-3 file:font-bold file:text-teal-800 hover:file:bg-teal-100">
                            @error('video')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="map_url" class="{{ $labelClasses }}">Enlace de Google Maps</label>
                            <input type="text" name="map_url" id="map_url" value="{{ old('map_url', $property->map_url ?? '') }}" placeholder="https://www.google.com/maps/embed?..." class="{{ $inputClasses }}">
                            @error('map_url')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="tour_360_url" class="{{ $labelClasses }}">Enlace del recorrido virtual 360°</label>
                            <input type="text" name="tour_360_url" id="tour_360_url" value="{{ old('tour_360_url', $property->tour_360_url ?? '') }}" placeholder="URL del recorrido 360°" class="{{ $inputClasses }}">
                            @error('tour_360_url')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="tour_3d_url" class="{{ $labelClasses }}">Enlace del modelo 3D</label>
                            <input type="text" name="tour_3d_url" id="tour_3d_url" value="{{ old('tour_3d_url', $property->tour_3d_url ?? '') }}" placeholder="URL del modelo 3D (Matterport, etc.)" class="{{ $inputClasses }}">
                            @error('tour_3d_url')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                    </div>

                </section>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
                    <a href="{{ route('dashboard.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-200 px-6 text-sm font-bold text-slate-700 transition hover:border-teal-700 hover:bg-teal-50 hover:text-teal-800">
                        Cancelar
                    </a>
                    <button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-teal-800 px-7 text-sm font-extrabold text-white shadow-lg shadow-teal-900/15 transition hover:-translate-y-0.5 hover:bg-teal-900 focus:outline-none focus:ring-4 focus:ring-teal-700/20">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </main>
    <script>
        (() => {
            const operation = document.querySelector('#listing_type');
            const price = document.querySelector('#price');
            const updateMaximum = () => {
                price.max = operation.value === 'alquiler' ? price.dataset.rentalMax : price.dataset.saleMax;
            };
            operation.addEventListener('change', updateMaximum);
            updateMaximum();
        })();
    </script>
@endsection

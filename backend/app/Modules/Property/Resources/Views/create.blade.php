@extends('layouts.app')

@section('title', 'Publicar inmueble - Colombia Sin Comisión')

@section('content')
    @php
        $inputClasses = 'mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-teal-700 focus:ring-4 focus:ring-teal-700/10';
        $labelClasses = 'block text-sm font-semibold text-slate-700';
    @endphp

    <main class="min-h-screen bg-slate-50 px-4 py-10 text-slate-800 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <a href="{{ url()->previous() }}" class="inline-flex items-center gap-2 text-sm font-semibold text-teal-800 transition hover:text-teal-950">
                <span aria-hidden="true">←</span> Volver
            </a>

            <header class="mb-7 mt-5 flex items-start gap-4">
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-amber-100 text-2xl text-amber-700" aria-hidden="true">⌂</span>
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-teal-700">Colombia Sin Comisión</p>
                    <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Publica tu inmueble</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                        Cuéntanos lo esencial para presentar tu propiedad con claridad a las personas interesadas.
                    </p>
                </div>
            </header>

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

            <form action="{{ route('properties.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8 rounded-3xl border border-slate-200 bg-white p-5 shadow-[0_18px_55px_rgba(31,41,55,0.07)] sm:p-8 lg:p-10">
                @csrf

                <section class="space-y-5" aria-labelledby="property-basics-heading">
                    <div class="border-b border-slate-100 pb-4">
                        <h2 id="property-basics-heading" class="text-lg font-extrabold text-slate-900">Información básica</h2>
                        <p class="mt-1 text-sm text-slate-500">Identifica el inmueble y define su precio de publicación.</p>
                    </div>

                    <div>
                        <label for="title" class="{{ $labelClasses }}">Título del inmueble</label>
                        <input type="text" name="title" id="title" value="{{ old('title') }}" required maxlength="255" placeholder="Ej. Apartamento iluminado cerca al centro" class="{{ $inputClasses }}">
                        @error('title')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label for="listing_type" class="{{ $labelClasses }}">Tipo de publicación</label>
                            <select name="listing_type" id="listing_type" required class="{{ $inputClasses }}">
                                <option value="">Selecciona una operación</option>
                                <option value="venta" @selected(old('listing_type') === 'venta')>Venta</option>
                                <option value="alquiler" @selected(old('listing_type') === 'alquiler')>Alquiler</option>
                            </select>
                            @error('listing_type')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="price" class="{{ $labelClasses }}">Precio / canon mensual (COP)</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center font-semibold text-slate-400">$</span>
                                <input type="number" name="price" id="price" value="{{ old('price') }}" required min="1000000" max="{{ old('listing_type') === 'alquiler' ? 100000000 : 50000000000 }}" step="1" placeholder="250000000" data-sale-max="50000000000" data-rental-max="100000000" class="{{ $inputClasses }} pl-9">
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Mínimo $1.000.000. Máximo: $50.000 millones en venta o $100 millones mensuales en alquiler.</p>
                            @error('price')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="property_type" class="{{ $labelClasses }}">Tipo de inmueble</label>
                            <select name="property_type" id="property_type" required class="{{ $inputClasses }}">
                                <option value="">Selecciona un tipo</option>
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
                                    <option value="{{ $value }}" @selected(old('property_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('property_type')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="space-y-5" aria-labelledby="property-location-heading">
                    <div class="border-b border-slate-100 pb-4">
                        <h2 id="property-location-heading" class="text-lg font-extrabold text-slate-900">Ubicación</h2>
                        <p class="mt-1 text-sm text-slate-500">Esta información ayuda a encontrar tu inmueble en las búsquedas.</p>
                    </div>

                    @include('properties::partials.location-fields', [
                        'departments' => $departments,
                        'selectedDepartmentId' => old('department_id'),
                        'selectedCityId' => old('city_id'),
                        'selectedNeighborhood' => old('neighborhood'),
                        'inputClasses' => $inputClasses,
                        'labelClasses' => $labelClasses,
                    ])
                </section>

                <section class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4 sm:p-6">
                    @include('properties::partials.details-fields', [
                        'inputClasses' => $inputClasses,
                        'labelClasses' => $labelClasses,
                        'isDark' => false,
                    ])
                </section>

                <section class="space-y-4 rounded-2xl border border-slate-100 bg-slate-50/70 p-4 sm:p-6" aria-labelledby="property-blueprint-heading">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-teal-700">Documentación opcional</p>
                        <h2 id="property-blueprint-heading" class="mt-1 text-lg font-extrabold text-slate-900">Planos arquitectónicos</h2>
                        <p class="mt-1 text-sm text-slate-500">Puedes agregar un PDF de hasta 10 páginas y 10 MB. También podrás cargarlo o reemplazarlo al editar el inmueble.</p>
                    </div>
                    <div>
                        <label for="blueprint" class="{{ $labelClasses }}">Seleccionar planos en PDF</label>
                        <input type="file" name="blueprint" id="blueprint" accept="application/pdf,.pdf" class="mt-2 block w-full rounded-xl border border-slate-200 bg-white text-sm text-slate-600 file:mr-4 file:border-0 file:bg-teal-50 file:px-4 file:py-3 file:font-bold file:text-teal-800 hover:file:bg-teal-100">
                        @error('blueprint')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                </section>

                <section class="space-y-4 rounded-2xl border border-slate-100 bg-slate-50/70 p-4 sm:p-6" aria-labelledby="property-images-heading">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-teal-700">Contenido multimedia</p>
                        <h2 id="property-images-heading" class="mt-1 text-lg font-extrabold text-slate-900">Fotos del inmueble</h2>
                        <p class="mt-1 text-sm text-slate-500">Puedes cargar hasta 20 imágenes JPG, PNG o WebP de máximo 10 MB cada una.</p>
                    </div>
                    <div>
                        <label for="images" class="{{ $labelClasses }}">Seleccionar fotos</label>
                        <input type="file" name="images[]" id="images" multiple accept="image/jpeg,image/png" class="mt-2 block w-full rounded-xl border border-slate-200 bg-white text-sm text-slate-600 file:mr-4 file:border-0 file:bg-teal-50 file:px-4 file:py-3 file:font-bold file:text-teal-800 hover:file:bg-teal-100">
                        @error('images')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        @error('images.*')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                </section>

                <section>
                    <label for="description" class="{{ $labelClasses }}">Descripción del inmueble</label>
                    <textarea name="description" id="description" rows="6" required placeholder="Describe la distribución, el estado y los detalles que hacen especial esta propiedad." class="{{ $inputClasses }} resize-y">{{ old('description') }}</textarea>
                    @error('description')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </section>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
                    <a href="{{ url()->previous() }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-200 px-6 text-sm font-bold text-slate-700 transition hover:border-teal-700 hover:bg-teal-50 hover:text-teal-800">
                        Cancelar
                    </a>
                    <button type="submit" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-teal-800 px-7 text-sm font-extrabold text-white shadow-lg shadow-teal-900/15 transition hover:-translate-y-0.5 hover:bg-teal-900 focus:outline-none focus:ring-4 focus:ring-teal-700/20">
                        <span class="text-amber-300" aria-hidden="true">＋</span> Publicar inmueble
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

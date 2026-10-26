@extends('layouts.app')

@section('title', $listingTitle . ' - Colombia Sin Comisión')

@section('content')
    @php
        $hasAdvancedFilters = collect($catalogFilters)->contains(fn ($value) => $value !== null && $value !== '');
        $preservedFilters = array_filter($catalogFilters, fn ($value) => $value !== null && $value !== '');
        $filtersForOperation = static function (array $filters, string $operation): array {
            if ($operation === 'alquiler') {
                foreach (['min_price', 'max_price'] as $field) {
                    if (isset($filters[$field]) && (float) $filters[$field] > 100000000) {
                        unset($filters[$field]);
                    }
                }
            }

            return $filters;
        };
    @endphp

    <div class="page-shell">
        <section class="catalog-intro" aria-labelledby="catalog-title">
            <p class="catalog-eyebrow">Colombia Sin Comisión</p>
            <h1 id="catalog-title">{{ $listingTitle }}</h1>
            <p>Explora publicaciones directas y encuentra el espacio ideal para ti.</p>
        </section>

        <section class="catalog-search" aria-label="Buscar inmuebles">
            <form class="catalog-search-form" action="{{ route('properties.index') }}" method="GET">
                <label class="catalog-search-field" for="catalog-destination">
                    <span>Ciudad o barrio</span>
                    <input
                        id="catalog-destination"
                        name="destination"
                        type="search"
                        value="{{ $destination }}"
                        placeholder="Ej. Manizales, Chipre"
                        maxlength="120"
                    >
                </label>

                <input type="hidden" name="property_type" value="{{ $propertyType }}">

                <label class="catalog-search-field" for="catalog-listing-type">
                    <span>Tipo de operación</span>
                    <select id="catalog-listing-type" name="listing_type">
                        <option value="">Todas las operaciones</option>
                        <option value="venta" @selected($listingType === 'venta')>En venta</option>
                        <option value="alquiler" @selected($listingType === 'alquiler')>En alquiler</option>
                    </select>
                </label>

                <label class="catalog-search-field" for="catalog-bedrooms">
                    <span>Habitaciones</span>
                    <input id="catalog-bedrooms" name="bedrooms" type="number" min="0" max="100" value="{{ $catalogFilters['bedrooms'] ?? '' }}" placeholder="Cualquiera">
                </label>

                <label class="catalog-search-field" for="catalog-bathrooms">
                    <span>Baños</span>
                    <input id="catalog-bathrooms" name="bathrooms" type="number" min="0" max="100" value="{{ $catalogFilters['bathrooms'] ?? '' }}" placeholder="Cualquiera">
                </label>

                <label class="catalog-search-field" for="catalog-stratum">
                    <span>Estrato</span>
                    <select id="catalog-stratum" name="stratum">
                        <option value="">Cualquiera</option>
                        @foreach(range(1, 6) as $stratum)
                            <option value="{{ $stratum }}" @selected((string) ($catalogFilters['stratum'] ?? '') === (string) $stratum)>{{ $stratum }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="catalog-search-field" for="catalog-min-price">
                    <span>Precio mínimo (COP)</span>
                    <input id="catalog-min-price" name="min_price" type="number" min="1000000" max="{{ $listingType === 'alquiler' ? 100000000 : 50000000000 }}" step="1" value="{{ $catalogFilters['min_price'] ?? '' }}" placeholder="Desde">
                </label>

                <label class="catalog-search-field" for="catalog-max-price">
                    <span>Precio máximo (COP)</span>
                    <input id="catalog-max-price" name="max_price" type="number" min="1000000" max="{{ $listingType === 'alquiler' ? 100000000 : 50000000000 }}" step="1" value="{{ $catalogFilters['max_price'] ?? '' }}" placeholder="Hasta">
                </label>

                <button class="catalog-search-button" type="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="10.8" cy="10.8" r="6.8"></circle>
                        <path d="m16 16 4.5 4.5"></path>
                    </svg>
                    Buscar
                </button>
            </form>
            @if($errors->any())
                <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        <nav class="catalog-type-nav" aria-label="Filtrar por tipo de inmueble">
            @foreach($propertyTypes as $type)
                @php
                    $isActive = $type['value'] === ''
                        ? $propertyType === ''
                        : ($type['value'] === 'comercial'
                            ? in_array($propertyType, ['comercial', 'local'], true)
                            : $propertyType === $type['value']);
                @endphp

                <a
                    href="{{ route('properties.index', array_merge($preservedFilters, array_filter([
                        'destination' => $destination ?: null,
                        'property_type' => $type['value'] ?: null,
                        'listing_type' => $listingType ?: null,
                    ], fn ($value) => $value !== null && $value !== ''))) }}"
                    class="catalog-type-link {{ $isActive ? 'is-active' : '' }}"
                    @if($isActive) aria-current="page" @endif
                >
                    {{ $type['label'] }}
                </a>
            @endforeach
        </nav>

        <nav class="catalog-type-nav" aria-label="Filtrar por tipo de operación">
            @foreach([
                ['value' => '', 'label' => 'Todas las operaciones'],
                ['value' => 'venta', 'label' => 'En venta'],
                ['value' => 'alquiler', 'label' => 'En alquiler'],
            ] as $operation)
                @php
                    $operationFilters = $filtersForOperation($preservedFilters, $operation['value']);
                @endphp
                <a
                    href="{{ route('properties.index', array_merge($operationFilters, array_filter([
                        'destination' => $destination ?: null,
                        'property_type' => $propertyType ?: null,
                        'listing_type' => $operation['value'] ?: null,
                    ], fn ($value) => $value !== null && $value !== ''))) }}"
                    class="catalog-type-link {{ $listingType === $operation['value'] ? 'is-active' : '' }}"
                    @if($listingType === $operation['value']) aria-current="page" @endif
                >
                    {{ $operation['label'] }}
                </a>
            @endforeach
        </nav>

        <section class="catalog-results" aria-labelledby="catalog-results-title">
            <div class="catalog-results-heading">
                <div>
                    <h2 id="catalog-results-title">{{ $listingTitle }}</h2>
                    <p>{{ $properties->total() }} {{ $properties->total() === 1 ? 'inmueble publicado' : 'inmuebles publicados' }}</p>
                </div>
                @if($destination !== '' || $propertyType !== '' || $listingType !== '' || $hasAdvancedFilters)
                    <a class="catalog-clear-link" href="{{ route('properties.index') }}">Limpiar filtros</a>
                @endif
            </div>

            @if($properties->isNotEmpty())
                <div class="property-grid catalog-property-grid">
                    @foreach($properties as $property)
                        @php
                            $primaryMedia = $property->media->firstWhere('is_primary', true) ?? $property->media->first();
                        @endphp

                        <article class="property-card catalog-property-card">
                            <a class="property-image" href="{{ route('properties.show', $property->id) }}" aria-label="Ver {{ $property->title }}">
                                @if($primaryMedia)
                                    <img src="{{ asset($primaryMedia->url_or_path) }}" alt="{{ $property->title }}" loading="lazy">
                                @else
                                    <span class="property-image-placeholder" aria-hidden="true">
                                        <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="m8 29 24-19 24 19"></path>
                                            <path d="M14 27v27h36V27"></path>
                                            <path d="M26 54V37h12v17"></path>
                                        </svg>
                                    </span>
                                @endif
                            </a>

                            <div class="property-details">
                                <p class="property-type">{{ $property->property_type }}</p>
                                <span class="property-operation {{ $property->listing_type === 'alquiler' ? 'property-operation--rental' : ($property->listing_type === 'venta' ? 'property-operation--sale' : '') }}">
                                    {{ $property->listing_type === 'alquiler' ? 'En alquiler' : ($property->listing_type === 'venta' ? 'En venta' : 'Operación sin especificar') }}
                                </span>
                                <h3 class="property-title">{{ $property->title }}</h3>
                                <p class="property-location">
                                    {{ $property->neighborhood ? $property->neighborhood . ', ' : '' }}{{ $property->city }}, {{ $property->department }}
                                </p>
                                @if($property->bedrooms !== null || $property->bathrooms !== null || $property->parking_spaces !== null || $property->built_area_m2 !== null)
                                    <div class="property-card-facts">
                                        @if($property->bedrooms !== null)<span>{{ $property->bedrooms }} hab.</span>@endif
                                        @if($property->bathrooms !== null)<span>{{ $property->bathrooms }} baños</span>@endif
                                        @if($property->parking_spaces !== null)<span>{{ $property->parking_spaces }} garajes</span>@endif
                                        @if($property->built_area_m2 !== null)<span>{{ number_format((float) $property->built_area_m2, 0, ',', '.') }} m²</span>@endif
                                    </div>
                                @endif
                                <div class="property-card-bottom">
                                    <span class="property-price">${{ number_format($property->price, 0, ',', '.') }}{{ $property->listing_type === 'alquiler' ? ' / mes' : '' }}</span>
                                    <a href="{{ route('properties.show', $property->id) }}" class="property-details-link">Ver inmueble →</a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="catalog-pagination">
                    {{ $properties->links() }}
                </div>
            @else
                <div class="catalog-empty-state">
                    <h3>No encontramos inmuebles con esos filtros.</h3>
                    <p>Prueba con otra operación, tipo de inmueble o ciudad.</p>
                    <a class="catalog-search-button" href="{{ route('properties.index') }}">Ver todos los inmuebles</a>
                </div>
            @endif
        </section>
    </div>
    <script>
        (() => {
            const operation = document.querySelector('#catalog-listing-type');
            const minPrice = document.querySelector('#catalog-min-price');
            const maxPrice = document.querySelector('#catalog-max-price');
            const updatePriceLimits = () => {
                const maximum = operation.value === 'alquiler' ? '100000000' : '50000000000';
                minPrice.max = maximum;
                maxPrice.max = maximum;
                if (Number(minPrice.value) > Number(maximum)) {
                    minPrice.value = '';
                }
                if (Number(maxPrice.value) > Number(maximum)) {
                    maxPrice.value = '';
                }
            };
            operation.addEventListener('change', updatePriceLimits);
            updatePriceLimits();
        })();
    </script>
@endsection

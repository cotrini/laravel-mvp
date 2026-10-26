@extends('layouts.app')

@section('content')
    @php
        $heroProperty = $latestProperties->first();
        $heroMedia = $heroProperty?->media->firstWhere('is_primary', true) ?? $heroProperty?->media->first();
    @endphp

    <div class="page-shell">
        <section class="campaign" aria-labelledby="campaign-title">
            <div class="campaign-copy">
                <p class="campaign-kicker">Colombia Sin Comisión</p>
                <h1 id="campaign-title">Un lugar para llamar hogar.</h1>
                <p class="campaign-description">Encuentra tu próximo inmueble y conecta directamente con quien lo publica.</p>
                <p class="campaign-note">Compra o arrienda sin intermediarios.</p>
            </div>

            <div class="campaign-photo">
                @if($heroMedia)
                    <img src="{{ asset($heroMedia->url_or_path) }}" alt="{{ $heroProperty->title }}">
                    <div class="campaign-stamp">
                        {{ $heroProperty->title }}
                        <span>{{ $heroProperty->city }}{{ $heroProperty->department ? ', ' . $heroProperty->department : '' }}</span>
                    </div>
                @else
                    <div class="property-image-placeholder" aria-hidden="true">
                        <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m8 29 24-19 24 19"></path>
                            <path d="M14 27v27h36V27"></path>
                            <path d="M26 54V37h12v17"></path>
                            <path d="M19 34h6m14 0h6"></path>
                        </svg>
                    </div>
                    <div class="campaign-stamp">
                        Tu próximo hogar te espera
                        <span>Explora propiedades en toda Colombia</span>
                    </div>
                @endif
            </div>
        </section>

        <section class="search-panel" aria-labelledby="search-title">
            <h2 id="search-title">Encuentra tu inmueble ideal</h2>
            <form class="search-fields" action="{{ route('properties.index') }}" method="GET">
                <label class="search-field" for="destination">
                    Ciudad o barrio
                    <span class="search-input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"></path>
                            <circle cx="12" cy="10" r="2.5"></circle>
                        </svg>
                        <input
                            id="destination"
                            name="destination"
                            type="search"
                            value="{{ $destination }}"
                            placeholder="Ej. Manizales, Chipre"
                            maxlength="120"
                        >
                    </span>
                </label>

                <label class="search-field" for="property_type">
                    Tipo de inmueble
                    <span class="search-input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m3 10 9-7 9 7"></path>
                            <path d="M5 9v11h14V9"></path>
                            <path d="M9 20v-6h6v6"></path>
                        </svg>
                        <select id="property_type" name="property_type">
                            <option value="">Todos los tipos</option>
                            <option value="apartamento" @selected($propertyType === 'apartamento')>Apartamento</option>
                            <option value="casa" @selected($propertyType === 'casa')>Casa</option>
                            <option value="finca" @selected($propertyType === 'finca')>Finca</option>
                            <option value="lote" @selected($propertyType === 'lote')>Lote</option>
                            <option value="oficina" @selected($propertyType === 'oficina')>Oficina</option>
                            <option value="comercial" @selected($propertyType === 'comercial' || $propertyType === 'local')>Propiedad comercial</option>
                            <option value="bodega" @selected($propertyType === 'bodega')>Bodega</option>
                        </select>
                    </span>
                </label>

                <label class="search-field" for="listing_type">
                    Tipo de operación
                    <span class="search-input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 7h16M4 12h16M4 17h16"></path><circle cx="8" cy="7" r="2"></circle><circle cx="16" cy="12" r="2"></circle><circle cx="10" cy="17" r="2"></circle>
                        </svg>
                        <select id="listing_type" name="listing_type">
                            <option value="">Todas las operaciones</option>
                            <option value="venta" @selected($listingType === 'venta')>En venta</option>
                            <option value="alquiler" @selected($listingType === 'alquiler')>En alquiler</option>
                        </select>
                    </span>
                </label>

                <button class="search-button" type="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="10.8" cy="10.8" r="6.8"></circle>
                        <path d="m16 16 4.5 4.5"></path>
                    </svg>
                    Buscar inmuebles
                </button>
            </form>
        </section>

        @forelse($propertySections as $sectionIndex => $propertySection)
            <section class="listings-section property-type-section property-type-section--{{ $propertySection['accent'] }}" aria-labelledby="listings-title-{{ $sectionIndex }}">
                <div class="section-heading">
                    <div>
                        <h2 id="listings-title-{{ $sectionIndex }}">{{ $propertySection['title'] }}</h2>
                        <p>{{ $propertySection['description'] }}</p>
                    </div>
                    @if($hasSearch)
                        <a class="all-listings-link" href="{{ route('home') }}">Limpiar búsqueda</a>
                    @else
                        <a class="all-listings-link" href="{{ route('properties.index', ['property_type' => $propertySection['type']]) }}">Ver más</a>
                    @endif
                </div>

                <div class="property-grid">
                    @foreach($propertySection['properties'] as $property)
                        @php
                            $primaryMedia = $property->media->firstWhere('is_primary', true) ?? $property->media->first();
                        @endphp

                        <article class="property-card">
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
            </section>
        @empty
            <section class="listings-section" aria-labelledby="empty-listings-title">
                <div class="empty-results">
                    <h2 id="empty-listings-title">{{ $hasSearch ? 'No encontramos inmuebles con esos filtros.' : 'Pronto publicaremos nuevos inmuebles.' }}</h2>
                    <p>{{ $hasSearch ? 'Prueba con otra operación, ciudad o tipo de inmueble.' : 'Vuelve pronto para descubrir nuevas publicaciones.' }}</p>
                    @if($hasSearch)
                        <a href="{{ route('home') }}" class="all-listings-link">Ver todas las publicaciones</a>
                    @endif
                </div>
            </section>
        @endforelse

        <section class="advisory-section" aria-labelledby="advisory-title">
            <div class="section-heading advisory-heading">
                <div>
                    <h2 id="advisory-title">Da el siguiente paso hacia tu nuevo hogar</h2>
                    <p>Te acompañamos con asesoría y servicios para tu inmueble.</p>
                </div>
            </div>

            <div class="advisory-grid">
                <article class="advisory-card advisory-card--finance">
                    <div class="advisory-copy">
                        <p class="advisory-eyebrow">Asesoría financiera</p>
                        <h3>Haz realidad tu próximo hogar</h3>
                        <p>Conoce opciones de financiación y recibe orientación para solicitar un préstamo bancario para tu inmueble.</p>
                        <a
                            class="advisory-button"
                            href="https://wa.me/573113611426?text={{ rawurlencode('Hola, quiero recibir asesoría sobre opciones de financiación para comprar un inmueble.') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <span aria-hidden="true">→</span> Pedir asesoría
                        </a>
                    </div>
                    <div class="advisory-photo advisory-photo--finance" role="img" aria-label="Asesoría para financiar la compra de un inmueble"></div>
                </article>

                <article class="advisory-card advisory-card--publish">
                    <div class="advisory-copy">
                        <p class="advisory-eyebrow">Publica con nosotros</p>
                        <h3>Tu inmueble merece brillar</h3>
                        <p>Te asesoramos para publicar y crear fotos, videos y recorridos 360° que destaquen tu inmueble.</p>
                        <a
                            class="advisory-button"
                            href="https://wa.me/573113611426?text={{ rawurlencode('Hola, quiero asesoría para publicar mi inmueble y conocer el servicio de fotos, videos y recorridos 360°.') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <span aria-hidden="true">→</span> Publica con nosotros
                        </a>
                    </div>
                    <div class="advisory-photo advisory-photo--publish" role="img" aria-label="Servicio de fotos, videos y recorridos 360 para inmuebles"></div>
                </article>
            </div>
        </section>
    </div>
@endsection

@extends('layouts.app')

@section('title', $property->title . ' - Colombia Sin Comisión')

@section('content')
    @php
        $images = $property->media->where('type', 'image');
        $video = $property->media->firstWhere('type', 'video');
        $tour3d = $property->media->firstWhere('type', 'tour_3d');
        $blueprint = $property->media->firstWhere('type', 'blueprint');
        $blueprintUrl = route('properties.blueprint.show', $property);
        $propertyUrl = route('properties.show', $property->id);
        $mediaRequestUrl = route('properties.media-request', $property);
        $mapLocation = implode(', ', array_filter([
            $property->neighborhood,
            $property->city,
            $property->department,
            'Colombia',
        ]));
        $mapEmbedUrl = 'https://www.google.com/maps?q=' . rawurlencode($mapLocation) . '&output=embed';
        $advisorPhone = '573113611426';
        $primaryImage = $images->firstWhere('is_primary', true) ?? $images->first();
        $statusLabel = match (strtolower($property->status ?? 'active')) {
            'active' => 'Disponible',
            'publicado', 'published' => 'Publicado',
            'pendiente' => 'En revisión',
            'pausado' => 'Pausado',
            default => ucfirst($property->status ?? 'Disponible'),
        };
        $listingTypeLabel = match ($property->listing_type) {
            'venta' => 'En venta',
            'alquiler' => 'En alquiler',
            default => 'Operación sin especificar',
        };
        $propertyHighlights = collect([
            ['label' => 'Habitaciones', 'value' => $property->bedrooms, 'icon' => 'bed'],
            ['label' => 'Baños', 'value' => $property->bathrooms, 'icon' => 'bath'],
            ['label' => 'Garajes', 'value' => $property->parking_spaces, 'icon' => 'car'],
            [
                'label' => 'Área construida',
                'value' => $property->built_area_m2 !== null
                    ? number_format((float) $property->built_area_m2, 2, ',', '.') . ' m²'
                    : null,
                'icon' => 'area',
            ],
        ])->filter(fn (array $highlight): bool => $highlight['value'] !== null)->values();
        $propertyFacts = collect([
            ['label' => 'Tipo de inmueble', 'value' => ucfirst($property->property_type ?? 'Inmueble'), 'icon' => 'home'],
            ['label' => 'Tipo de operación', 'value' => $listingTypeLabel, 'icon' => 'tag'],
            ['label' => 'Estado', 'value' => $statusLabel, 'icon' => 'status'],
            ['label' => 'Barrio o sector', 'value' => $property->neighborhood ?: 'No especificado', 'icon' => 'pin'],
            [
                'label' => 'Ciudad y departamento',
                'value' => $property->city . ($property->department ? ', ' . $property->department : ''),
                'icon' => 'pin',
            ],
            ['label' => 'Habitaciones', 'value' => $property->bedrooms, 'icon' => 'bed'],
            ['label' => 'Baños completos', 'value' => $property->bathrooms, 'icon' => 'bath'],
            ['label' => 'Medios baños', 'value' => $property->half_bathrooms, 'icon' => 'bath'],
            ['label' => 'Garajes', 'value' => $property->parking_spaces, 'icon' => 'car'],
            [
                'label' => 'Área construida',
                'value' => $property->built_area_m2 !== null
                    ? number_format((float) $property->built_area_m2, 2, ',', '.') . ' m²'
                    : null,
                'icon' => 'area',
            ],
            [
                'label' => 'Área del lote',
                'value' => $property->lot_area_m2 !== null
                    ? number_format((float) $property->lot_area_m2, 2, ',', '.') . ' m²'
                    : null,
                'icon' => 'area',
            ],
            ['label' => 'Piso', 'value' => $property->floor_number, 'icon' => 'stairs'],
            ['label' => 'Niveles del inmueble', 'value' => $property->total_floors, 'icon' => 'layers'],
            ['label' => 'Estrato', 'value' => $property->stratum, 'icon' => 'building'],
            ['label' => 'Año de construcción', 'value' => $property->year_built, 'icon' => 'calendar'],
            [
                'label' => 'Administración',
                'value' => $property->administration_fee !== null
                    ? '$' . number_format((float) $property->administration_fee, 0, ',', '.') . ' COP'
                    : null,
                'icon' => 'coins',
            ],
            [
                'label' => 'Amoblado',
                'value' => $property->is_furnished !== null ? ($property->is_furnished ? 'Sí' : 'No') : null,
                'icon' => 'sofa',
            ],
            [
                'label' => 'Ascensor',
                'value' => $property->has_elevator !== null ? ($property->has_elevator ? 'Sí' : 'No') : null,
                'icon' => 'elevator',
            ],
        ])->filter(fn (array $fact): bool => $fact['value'] !== null && $fact['value'] !== '')->values();
    @endphp

    <style>
        .property-detail-page {
            --detail-ink: #242424;
            --detail-muted: #69717d;
            --detail-teal: #12877b;
            --detail-yellow: #ffe22d;
            --detail-border: #e7e9ed;
            padding-top: 28px;
            padding-bottom: 64px;
        }

        .property-breadcrumb {
            margin: 0 0 18px;
            color: var(--detail-muted);
            font-size: 13px;
        }

        .property-breadcrumb a {
            color: var(--detail-teal);
            font-weight: 700;
        }

        .property-detail-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 22px;
        }

        .property-detail-heading h1 {
            margin: 0;
            color: var(--detail-ink);
            font-size: clamp(27px, 4vw, 40px);
            font-weight: 800;
            letter-spacing: -0.045em;
            line-height: 1.1;
        }

        .property-detail-location {
            display: flex;
            align-items: center;
            gap: 7px;
            margin: 10px 0 0;
            color: var(--detail-muted);
            font-size: 14px;
        }

        .property-detail-location svg {
            width: 17px;
            height: 17px;
            color: var(--detail-teal);
        }

        .property-detail-price {
            flex: none;
            color: var(--detail-teal);
            font-size: clamp(22px, 3vw, 30px);
            font-weight: 850;
            white-space: nowrap;
        }

        .property-detail-price-unit {
            display: block;
            margin-top: 3px;
            color: var(--detail-muted);
            font-size: 12px;
            font-weight: 700;
            text-align: right;
        }

        .property-price-block {
            text-align: right;
        }

        .property-operation-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 7px;
            padding: 6px 10px;
            border: 1px solid #d5ebe6;
            border-radius: 999px;
            background: #eef8f6;
            color: var(--detail-teal);
            font-size: 11px;
            font-weight: 850;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .property-operation-badge--sale {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .property-operation-badge--rental {
            border-color: #a7f3d0;
            background: #ecfdf5;
            color: #047857;
        }

        .property-highlight-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin: 0 0 20px;
        }

        .property-highlight {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 15px 17px;
            border: 1px solid var(--detail-border);
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 4px 14px rgb(31 41 55 / 4%);
        }

        .property-highlight .property-highlight-icon,
        .property-fact .property-fact-icon {
            display: grid;
            flex: none;
            place-items: center;
            border-radius: 12px;
            background: #eaf5f3;
            color: var(--detail-teal);
        }

        .property-highlight .property-highlight-icon {
            width: 44px;
            height: 44px;
            margin: 0;
        }

        .property-fact .property-fact-icon {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            margin: 0;
        }

        .property-highlight-icon svg {
            width: 23px;
            height: 23px;
        }

        .property-fact-icon svg {
            width: 20px;
            height: 20px;
        }

        .property-highlight-copy,
        .property-fact-copy {
            min-width: 0;
        }

        .property-highlight-copy > span {
            display: block;
            margin-bottom: 5px;
            color: var(--detail-muted);
            font-size: 12px;
        }

        .property-highlight strong {
            color: var(--detail-teal);
            font-size: 18px;
            font-weight: 850;
        }

        .property-highlights-empty {
            margin: 0 0 20px;
            padding: 15px 18px;
            border: 1px solid #f0df9c;
            border-radius: 14px;
            background: #fff9e5;
            color: #66551a;
            font-size: 13px;
            line-height: 1.55;
        }

        .property-gallery-layout {
            display: grid;
            grid-template-columns: 74px minmax(0, 1fr) minmax(270px, 320px);
            align-items: start;
            gap: 18px;
        }

        .property-media-rail {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .property-media-tile {
            position: relative;
            width: 74px;
            height: 74px;
            display: grid;
            place-items: center;
            overflow: hidden;
            padding: 0;
            border: 1px solid var(--detail-border);
            border-radius: 14px;
            background: #fff;
            color: var(--detail-teal);
            cursor: pointer;
            transition: border-color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
        }

        .property-media-tile:hover,
        .property-media-tile:focus-visible {
            transform: translateY(-1px);
            border-color: var(--detail-teal);
            box-shadow: 0 5px 14px rgb(18 135 123 / 13%);
        }

        .property-media-tile img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .property-media-tile--video,
        .property-media-tile--tour {
            background: #253a46;
            color: #fff;
        }

        .property-media-tile--map {
            background: #e7f4f1;
        }

        .property-media-tile--count {
            background: #313d46;
            color: #fff;
        }

        .property-media-tile--count strong {
            font-size: 21px;
            font-weight: 850;
        }

        .property-media-tile--blueprint {
            background: #f0faf8;
        }

        .property-media-tile svg {
            width: 25px;
            height: 25px;
        }

        .property-media-tile--video .tile-play-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 25px;
            height: 25px;
            display: grid;
            place-items: center;
            transform: translate(-50%, -50%);
            border-radius: 50%;
            background: var(--detail-yellow);
            color: var(--detail-ink);
        }

        .property-media-tile--video .tile-play-icon svg {
            width: 13px;
            height: 13px;
        }

        .property-media-tile-label {
            position: absolute;
            right: 0;
            bottom: 0;
            left: 0;
            padding: 4px 2px;
            background: rgb(36 36 36 / 72%);
            color: #fff;
            font-size: 9px;
            font-weight: 750;
            text-align: center;
        }

        .property-gallery-main {
            min-width: 0;
            height: clamp(330px, 43vw, 520px);
            display: grid;
            place-items: center;
            overflow: hidden;
            border: 1px solid var(--detail-border);
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 5px 18px rgb(31 41 55 / 5%);
        }

        .property-gallery-main button {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            padding: 0;
            border: 0;
            background: transparent;
            cursor: zoom-in;
        }

        .property-gallery-main img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .property-gallery-placeholder {
            display: grid;
            place-items: center;
            color: #79aaa3;
        }

        .property-gallery-placeholder svg {
            width: 76px;
            height: 76px;
        }

        .property-contact-card,
        .property-info-card {
            border: 1px solid var(--detail-border);
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 5px 18px rgb(31 41 55 / 5%);
        }

        .property-contact-card {
            position: sticky;
            top: 22px;
            padding: 22px;
        }

        .property-owner {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 17px;
            border-bottom: 1px solid #eef0f2;
        }

        .property-owner-avatar {
            width: 44px;
            height: 44px;
            display: grid;
            flex: none;
            place-items: center;
            border-radius: 15px;
            background: #fff3c4;
            color: #725900;
            font-size: 14px;
            font-weight: 850;
        }

        .property-owner h2 {
            margin: 0;
            color: var(--detail-ink);
            font-size: 15px;
            font-weight: 800;
        }

        .property-owner p {
            margin: 4px 0 0;
            color: var(--detail-muted);
            font-size: 12px;
        }

        .property-contact-card > p {
            margin: 16px 0;
            color: var(--detail-muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .property-contact-form,
        .advisor-form {
            display: grid;
            gap: 11px;
        }

        .property-contact-form input,
        .advisor-form input {
            width: 100%;
            min-height: 45px;
            padding: 0 12px;
            border: 1px solid #d9dee6;
            border-radius: 10px;
            outline: none;
            color: #2d3440;
            font-size: 13px;
        }

        .property-contact-form input:focus,
        .advisor-form input:focus {
            border-color: var(--detail-teal);
            box-shadow: 0 0 0 3px rgb(18 135 123 / 11%);
        }

        .property-whatsapp-button {
            min-height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 0 15px;
            border: 0;
            border-radius: 10px;
            background: var(--detail-teal);
            color: #fff;
            cursor: pointer;
            font-size: 13px;
            font-weight: 800;
            transition: background 160ms ease, transform 160ms ease;
        }

        .property-whatsapp-button:hover {
            transform: translateY(-1px);
            background: #0d756b;
        }

        .property-whatsapp-button svg {
            width: 19px;
            height: 19px;
        }

        .property-info-card {
            margin-top: 22px;
            padding: clamp(20px, 3vw, 32px);
        }

        .property-info-card h2,
        .property-recommendations h2 {
            margin: 0;
            color: var(--detail-ink);
            font-size: 22px;
            font-weight: 820;
            letter-spacing: -0.035em;
        }

        .property-facts {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin: 20px 0 25px;
        }

        .property-fact {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 14px;
            border-radius: 12px;
            background: #f6f8f8;
        }

        .property-fact--location {
            background: #fff8dd;
        }

        .property-fact-copy > span {
            display: block;
            margin-bottom: 6px;
            color: var(--detail-muted);
            font-size: 11px;
        }

        .property-fact strong {
            color: var(--detail-ink);
            font-size: 13px;
            overflow-wrap: anywhere;
        }

        .property-description {
            margin: 10px 0 0;
            color: #5e6671;
            font-size: 14px;
            line-height: 1.75;
            white-space: pre-line;
        }

        .property-info-subheading {
            margin: 26px 0 0 !important;
            padding-top: 22px;
            border-top: 1px solid var(--detail-border);
        }

        .property-advisor-card {
            display: grid;
            grid-template-columns: 1fr minmax(240px, 0.72fr);
            gap: 24px;
            align-items: center;
            margin-top: 22px;
            padding: clamp(22px, 3vw, 34px);
            overflow: hidden;
            border-radius: 20px;
            background: #ffe77d;
        }

        .property-contact-card .property-advisor-card {
            grid-template-columns: minmax(0, 1fr);
            gap: 15px;
            margin-top: 21px;
            padding: 19px;
            border-radius: 15px;
        }

        .property-contact-card .property-advisor-copy h2 {
            font-size: 21px;
        }

        .property-contact-card .advisor-form {
            padding: 13px;
            background: rgb(255 255 255 / 78%);
        }

        .property-advisor-copy .advisor-eyebrow {
            margin: 0 0 8px;
            color: #66551a;
            font-size: 11px;
            font-weight: 850;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .property-advisor-copy h2 {
            margin: 0;
            color: #292929;
            font-size: clamp(22px, 3vw, 30px);
            font-weight: 850;
            letter-spacing: -0.045em;
            line-height: 1.1;
        }

        .property-advisor-copy p:last-child {
            max-width: 550px;
            margin: 12px 0 0;
            color: #514c46;
            font-size: 14px;
            line-height: 1.55;
        }

        .advisor-form {
            padding: 17px;
            border: 1px solid rgb(41 41 41 / 8%);
            border-radius: 15px;
            background: rgb(255 255 255 / 72%);
        }

        .property-recommendations {
            margin-top: 44px;
        }

        .property-recommendation-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-top: 18px;
        }

        .property-recommendation-card {
            overflow: hidden;
            border: 1px solid var(--detail-border);
            border-radius: 16px;
            background: #fff;
            transition: transform 160ms ease, box-shadow 160ms ease;
        }

        .property-recommendation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgb(31 41 55 / 10%);
        }

        .property-recommendation-image {
            height: 190px;
            display: grid;
            place-items: center;
            overflow: hidden;
            background: #eef4f2;
            color: #79aaa3;
        }

        .property-recommendation-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .property-recommendation-image svg {
            width: 50px;
            height: 50px;
        }

        .property-recommendation-copy {
            padding: 16px;
        }

        .property-recommendation-copy > span {
            color: var(--detail-teal);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .property-recommendation-copy h3 {
            margin: 6px 0;
            overflow: hidden;
            color: var(--detail-ink);
            font-size: 15px;
            font-weight: 800;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .property-recommendation-copy p {
            margin: 0;
            color: var(--detail-muted);
            font-size: 13px;
        }

        .property-recommendation-link {
            display: block;
            margin-top: 14px;
            padding: 10px;
            border-radius: 9px;
            background: #f0f5f4;
            color: var(--detail-teal);
            font-size: 12px;
            font-weight: 800;
            text-align: center;
        }

        .property-media-dialog {
            width: min(1000px, calc(100% - 28px));
            max-width: none;
            max-height: min(90vh, 900px);
            padding: 0;
            overflow: hidden;
            border: 1px solid rgb(255 255 255 / 15%);
            border-radius: 20px;
            background: #17232b;
            color: #fff;
        }

        .property-media-dialog::backdrop {
            background: rgb(15 23 32 / 82%);
            backdrop-filter: blur(4px);
        }

        .property-dialog-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 16px 20px;
            border-bottom: 1px solid rgb(255 255 255 / 10%);
        }

        .property-dialog-heading h2 {
            margin: 0;
            font-size: 17px;
            font-weight: 800;
        }

        .property-dialog-close {
            width: 38px;
            height: 38px;
            display: grid;
            flex: none;
            place-items: center;
            border: 0;
            border-radius: 50%;
            background: rgb(255 255 255 / 12%);
            color: #fff;
            cursor: pointer;
            font-size: 24px;
        }

        .property-dialog-content {
            max-height: calc(90vh - 71px);
            overflow: auto;
            padding: 20px;
            text-align: center;
        }

        .property-dialog-content[hidden] {
            display: none;
        }

        .property-dialog-gallery {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .property-dialog-gallery img {
            width: 100%;
            height: 320px;
            border-radius: 12px;
            background: #26343c;
            object-fit: contain;
        }

        .property-dialog-video {
            width: 100%;
            max-height: calc(90vh - 150px);
            border-radius: 12px;
            background: #000;
        }

        .property-dialog-map {
            width: 100%;
            height: min(65vh, 560px);
            border: 0;
            border-radius: 12px;
            background: #fff;
        }

        .property-media-empty {
            min-height: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 25px;
            border: 1px solid rgb(255 255 255 / 9%);
            border-radius: 14px;
            background: #202e37;
        }

        .property-media-empty > svg {
            width: 42px;
            height: 42px;
            color: #ffe22d;
        }

        .property-media-empty h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
        }

        .property-media-empty p {
            max-width: 440px;
            margin: 0;
            color: #c4cbd0;
            font-size: 13px;
            line-height: 1.55;
        }

        .property-media-empty form {
            width: min(100%, 340px);
        }

        .media-request-status {
            min-height: 17px;
            margin: 9px 0 0 !important;
            color: #b7f0dd !important;
            font-size: 12px !important;
        }

        @media (max-width: 1000px) {
            .property-gallery-layout {
                grid-template-columns: 64px minmax(0, 1fr);
            }

            .property-media-tile {
                width: 64px;
                height: 64px;
            }

            .property-contact-card {
                position: static;
                grid-column: 2;
            }

            .property-detail-heading {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 640px) {
            .property-detail-page {
                padding-top: 18px;
            }

            .property-highlight-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .property-gallery-layout {
                grid-template-columns: minmax(0, 1fr);
                gap: 12px;
            }

            .property-gallery-main {
                grid-row: 1;
                height: 330px;
            }

            .property-media-rail {
                grid-row: 2;
                flex-direction: row;
                overflow-x: auto;
                padding: 2px 1px 6px;
            }

            .property-contact-card {
                grid-column: auto;
            }

            .property-facts {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .property-advisor-card {
                grid-template-columns: minmax(0, 1fr);
            }

            .property-recommendation-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .property-dialog-gallery {
                grid-template-columns: minmax(0, 1fr);
            }

            .property-dialog-gallery img {
                height: 260px;
            }
        }
    </style>

    <div class="page-shell property-detail-page">
        <p class="property-breadcrumb">
            <a href="{{ route('properties.index') }}">Inmuebles</a>
            <span aria-hidden="true"> / </span>{{ $property->city }}
        </p>

        <header class="property-detail-heading">
            <div>
                <h1>{{ $property->title }}</h1>
                <p class="property-detail-location">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle>
                    </svg>
                    {{ $property->neighborhood ? $property->neighborhood . ', ' : '' }}{{ $property->city }}{{ $property->department ? ', ' . $property->department : '' }}
                </p>
            </div>
            <div class="property-price-block">
                <span class="property-operation-badge {{ $property->listing_type === 'alquiler' ? 'property-operation-badge--rental' : ($property->listing_type === 'venta' ? 'property-operation-badge--sale' : '') }}">{{ $listingTypeLabel }}</span>
                <p class="property-detail-price">
                    ${{ number_format($property->price, 0, ',', '.') }} COP{{ $property->listing_type === 'alquiler' ? ' / mes' : '' }}
                </p>
                @if($property->listing_type === 'alquiler')
                    <span class="property-detail-price-unit">Canon mensual</span>
                @endif
            </div>
        </header>

        @if($propertyHighlights->isNotEmpty())
            <section class="property-highlight-strip" aria-label="Características principales">
                @foreach($propertyHighlights as $highlight)
                    <div class="property-highlight">
                        <span class="property-highlight-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                @switch($highlight['icon'])
                                    @case('bed')
                                        <path d="M3 19v-8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8M3 15h18M7 9V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v3"></path>
                                        @break
                                    @case('bath')
                                        <path d="M4 12h16v4a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4v-4ZM6 12V6a2 2 0 0 1 4 0v1"></path><path d="M4 16h16M8 20v2m8-2v2"></path>
                                        @break
                                    @case('car')
                                        <path d="m5 11 1.5-4.5A2 2 0 0 1 8.4 5h7.2a2 2 0 0 1 1.9 1.5L19 11M3 11h18v8H3zM5 19v2m14-2v2M6.5 15h.01M17.5 15h.01"></path><path d="M3 14h3m12 0h3"></path>
                                        @break
                                    @case('area')
                                        <path d="M4 4h16v16H4zM9 4v4H4m16 3h-5v5m-3 4v-5H4m16-3V4h-4"></path>
                                        @break
                                @endswitch
                            </svg>
                        </span>
                        <span class="property-highlight-copy">
                            <span>{{ $highlight['label'] }}</span>
                            <strong>{{ $highlight['value'] }}</strong>
                        </span>
                    </div>
                @endforeach
            </section>
        @else
            <p class="property-highlights-empty" role="status">
                Este inmueble aún no tiene características adicionales registradas, como habitaciones, baños, garajes o área construida.
            </p>
        @endif

        <section class="property-gallery-layout" aria-label="Fotos y contacto del inmueble">
            <nav class="property-media-rail" aria-label="Contenido multimedia">
                @foreach($images->take(2) as $image)
                    <button type="button" class="property-media-tile" data-gallery-open aria-label="Abrir galería de fotos">
                        <img src="{{ $image->public_url }}" alt="" loading="lazy">
                    </button>
                @endforeach

                @if($images->count() > 2)
                    <button type="button" class="property-media-tile property-media-tile--count" data-dialog-open="gallery" aria-label="Ver las {{ $images->count() }} fotos">
                        <strong>+{{ $images->count() - 2 }}</strong>
                        <span class="property-media-tile-label">Fotos</span>
                    </button>
                @endif

                <button type="button" class="property-media-tile property-media-tile--video" data-dialog-open="video" aria-label="{{ $video ? 'Ver video del inmueble' : 'Solicitar un video al propietario' }}">
                    @if($video && $primaryImage)
                        <img src="{{ $primaryImage->public_url }}" alt="" loading="lazy">
                    @endif
                    <span class="tile-play-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"></path></svg>
                    </span>
                    <span class="property-media-tile-label">Video</span>
                </button>

                <button type="button" class="property-media-tile property-media-tile--map" data-dialog-open="map" aria-label="Ver ubicación en el mapa">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3V6Z"></path><path d="M9 3v15m6-12v15"></path><path d="M12 8a3 3 0 1 0 6 0c0-1.7-1.3-3-3-3s-3 1.3-3 3Zm3 0v.01"></path>
                    </svg>
                    <span class="property-media-tile-label">Mapa</span>
                </button>

                @if($property->tour)
                    <button type="button" class="property-media-tile property-media-tile--tour" data-dialog-open="tour" aria-label="Ver recorrido virtual 360 grados">
                        <span aria-hidden="true">360°</span>
                        <span class="property-media-tile-label">Recorrido</span>
                    </button>
                @else
                    <button type="button" class="property-media-tile property-media-tile--tour" data-dialog-open="tour" aria-label="Solicitar recorrido virtual 360 grados">
                        <span aria-hidden="true">360°</span>
                        <span class="property-media-tile-label">Recorrido</span>
                    </button>
                @endif

                @if($tour3d)
                    <button type="button" class="property-media-tile property-media-tile--tour" data-dialog-open="tour-3d" aria-label="Ver modelo tridimensional">3D</button>
                @endif

                <button type="button" class="property-media-tile property-media-tile--blueprint" data-dialog-open="blueprint" aria-label="{{ $blueprint ? 'Ver planos arquitectónicos' : 'Solicitar planos arquitectónicos al propietario' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 3h8l5 5v13H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"></path><path d="M14 3v5h5M8 13h8M8 17h8"></path>
                    </svg>
                    <span class="property-media-tile-label">Planos PDF</span>
                </button>
            </nav>

            <div class="property-gallery-main">
                @if($primaryImage)
                    <button type="button" data-gallery-open aria-label="Ampliar fotos del inmueble">
                        <img src="{{ $primaryImage->public_url }}" alt="{{ $property->title }}">
                    </button>
                @else
                    <div class="property-gallery-placeholder" aria-label="Sin imagen disponible">
                        <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m8 29 24-19 24 19"></path><path d="M14 27v27h36V27"></path><path d="M26 54V37h12v17"></path>
                        </svg>
                    </div>
                @endif
            </div>

            <aside class="property-contact-card" aria-labelledby="property-contact-title">
                <div class="property-owner">
                    <span class="property-owner-avatar" aria-hidden="true">{{ strtoupper(substr($property->user?->name ?? 'CS', 0, 2)) }}</span>
                    <div>
                        <h2 id="property-contact-title">{{ $property->user?->name ?? 'Propietario del inmueble' }}</h2>
                        <p>Contacto directo por WhatsApp</p>
                    </div>
                </div>

                <p>Déjanos tus datos para enviar tu interés por esta propiedad a través de WhatsApp.</p>
                <form class="property-contact-form" data-whatsapp-form data-phone="{{ $advisorPhone }}" data-message="Hola, estoy interesado(a) en esta propiedad: {{ $property->title }}.">
                    <input type="hidden" name="property_url" value="{{ $propertyUrl }}">
                    <input type="text" name="name" placeholder="Nombre y apellido" aria-label="Nombre y apellido" autocomplete="name" required>
                    <input type="tel" name="phone" placeholder="Teléfono" aria-label="Teléfono" autocomplete="tel" required>
                    <button type="submit" class="property-whatsapp-button">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M20.52 3.48A11.87 11.87 0 0 0 12.07 0C5.5 0 .15 5.34.15 11.92c0 2.1.55 4.14 1.6 5.94L.05 24l6.3-1.65a11.9 11.9 0 0 0 5.72 1.46h.01c6.57 0 11.92-5.35 11.92-11.92 0-3.18-1.24-6.17-3.48-8.41ZM12.08 21.8h-.01a9.9 9.9 0 0 1-5.04-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.85 9.85 0 0 1-1.52-5.25c0-5.46 4.45-9.9 9.92-9.9a9.84 9.84 0 0 1 7.01 2.9 9.84 9.84 0 0 1 2.9 7.01c0 5.47-4.45 9.92-9.92 9.92Zm5.44-7.42c-.3-.15-1.77-.87-2.05-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.95 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.47-.89-.79-1.49-1.77-1.66-2.07-.18-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.38-.03-.53-.07-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.49s1.06 2.89 1.21 3.09c.15.2 2.1 3.2 5.1 4.49.71.3 1.27.49 1.7.63.72.23 1.37.2 1.88.12.57-.09 1.77-.72 2.02-1.42.25-.7.25-1.3.17-1.42-.07-.13-.27-.2-.57-.35Z"></path>
                        </svg>
                        Continuar por WhatsApp
                    </button>
                </form>

                <section class="property-advisor-card" aria-labelledby="property-advisor-title">
                    <div class="property-advisor-copy">
                        <p class="advisor-eyebrow">Asesoría financiera</p>
                        <h2 id="property-advisor-title">Haz realidad tu próximo hogar</h2>
                        <p>Recibe orientación para conocer opciones de financiación y solicitar un préstamo bancario para este inmueble.</p>
                    </div>
                    <form class="advisor-form" data-whatsapp-form data-phone="{{ $advisorPhone }}" data-message="Hola, quiero recibir asesoría bancaria para financiar esta propiedad: {{ $property->title }}.">
                        <input type="hidden" name="property_url" value="{{ $propertyUrl }}">
                        <input type="text" name="name" placeholder="Tu nombre" aria-label="Tu nombre" autocomplete="name" required>
                        <input type="tel" name="phone" placeholder="Tu teléfono" aria-label="Tu teléfono" autocomplete="tel" required>
                        <button type="submit" class="property-whatsapp-button">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M20.52 3.48A11.87 11.87 0 0 0 12.07 0C5.5 0 .15 5.34.15 11.92c0 2.1.55 4.14 1.6 5.94L.05 24l6.3-1.65a11.9 11.9 0 0 0 5.72 1.46h.01c6.57 0 11.92-5.35 11.92-11.92 0-3.18-1.24-6.17-3.48-8.41ZM12.08 21.8h-.01a9.9 9.9 0 0 1-5.04-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.85 9.85 0 0 1-1.52-5.25c0-5.46 4.45-9.9 9.92-9.9a9.84 9.84 0 0 1 7.01 2.9 9.84 9.84 0 0 1 2.9 7.01c0 5.47-4.45 9.92-9.92 9.92Z"></path>
                            </svg>
                            Pedir asesoría por WhatsApp
                        </button>
                    </form>
                </section>
            </aside>
        </section>

        <section class="property-info-card" aria-labelledby="property-details-title">
            <h2 id="property-details-title">Detalles del inmueble</h2>
            <div class="property-facts">
                @foreach($propertyFacts as $fact)
                    <div class="property-fact">
                        <span class="property-fact-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                @switch($fact['icon'])
                                    @case('home')
                                        <path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-6v-6h-4v6H4a1 1 0 0 1-1-1V10Z"></path>
                                        @break
                                    @case('status')
                                        <path d="M12 3 19 6v5c0 4.5-3 7.5-7 10-4-2.5-7-5.5-7-10V6l7-3Z"></path><path d="m9 12 2 2 4-4"></path>
                                        @break
                                    @case('pin')
                                        <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle>
                                        @break
                                    @case('bed')
                                        <path d="M3 19v-8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8M3 15h18M7 9V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v3"></path>
                                        @break
                                    @case('bath')
                                        <path d="M4 12h16v4a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4v-4ZM6 12V6a2 2 0 0 1 4 0v1"></path><path d="M4 16h16M8 20v2m8-2v2"></path>
                                        @break
                                    @case('car')
                                        <path d="m5 11 1.5-4.5A2 2 0 0 1 8.4 5h7.2a2 2 0 0 1 1.9 1.5L19 11M3 11h18v8H3zM5 19v2m14-2v2M6.5 15h.01M17.5 15h.01"></path><path d="M3 14h3m12 0h3"></path>
                                        @break
                                    @case('area')
                                        <path d="M4 4h16v16H4zM9 4v4H4m16 3h-5v5m-3 4v-5H4m16-3V4h-4"></path>
                                        @break
                                    @case('stairs')
                                    @case('layers')
                                        <path d="m3 17 6-6 4 4 8-8"></path><path d="M15 7h6v6"></path><path d="M4 21h16"></path>
                                        @break
                                    @case('building')
                                        <path d="M4 21V5l8-3 8 3v16M4 9h16M9 9v3m6-3v3m-6 3v3m6-3v3M2 21h20"></path>
                                        @break
                                    @case('calendar')
                                        <rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path>
                                        @break
                                    @case('coins')
                                        <circle cx="8" cy="8" r="5"></circle><path d="M8 5v6m-2-4h3m-3 2h3"></path><path d="M13 6h3a5 5 0 0 1 0 10h-3m-2 5h5a5 5 0 0 0 0-10"></path>
                                        @break
                                    @case('tag')
                                        <path d="M20 13 13 20 3 10V4h6l11 9Z"></path><circle cx="7.5" cy="7.5" r="1"></circle>
                                        @break
                                    @case('sofa')
                                        <path d="M5 11V8a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v3"></path><path d="M4 11a2 2 0 0 0-2 2v5h20v-5a2 2 0 0 0-2-2h-1v4H5v-4H4ZM5 18v3m14-3v3"></path>
                                        @break
                                    @case('elevator')
                                        <rect x="5" y="3" width="14" height="18" rx="2"></rect><path d="m9 8 2-2 2 2m-2-2v5m4 5-2 2-2-2m2 2v-5"></path>
                                        @break
                                @endswitch
                            </svg>
                        </span>
                        <span class="property-fact-copy">
                            <span>{{ $fact['label'] }}</span>
                            <strong>{{ $fact['value'] }}</strong>
                        </span>
                    </div>
                @endforeach
            </div>
            <h2 class="property-info-subheading">Descripción</h2>
            <p class="property-description">{{ $property->description }}</p>
        </section>

        <section class="property-recommendations" aria-labelledby="property-recommendations-title">
            <h2 id="property-recommendations-title">Otras propiedades interesantes</h2>
            @if($otherProperties->isNotEmpty())
                <div class="property-recommendation-grid">
                    @foreach($otherProperties as $otherProperty)
                        @php($otherImage = $otherProperty->media->firstWhere('type', 'image'))
                        <article class="property-recommendation-card">
                            <a href="{{ route('properties.show', $otherProperty->id) }}" class="property-recommendation-image" aria-label="Ver {{ $otherProperty->title }}">
                                @if($otherImage)
                                    <img src="{{ $otherImage->public_url }}" alt="{{ $otherProperty->title }}" loading="lazy">
                                @else
                                    <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="m8 29 24-19 24 19"></path><path d="M14 27v27h36V27"></path><path d="M26 54V37h12v17"></path>
                                    </svg>
                                @endif
                            </a>
                            <div class="property-recommendation-copy">
                                <span>{{ $otherProperty->property_type }}</span>
                                <span class="property-operation {{ $otherProperty->listing_type === 'alquiler' ? 'property-operation--rental' : ($otherProperty->listing_type === 'venta' ? 'property-operation--sale' : '') }}">
                                    {{ $otherProperty->listing_type === 'alquiler' ? 'En alquiler' : ($otherProperty->listing_type === 'venta' ? 'En venta' : 'Operación sin especificar') }}
                                </span>
                                <h3>{{ $otherProperty->title }}</h3>
                                <p>${{ number_format($otherProperty->price, 0, ',', '.') }} COP{{ $otherProperty->listing_type === 'alquiler' ? ' / mes' : '' }} · {{ $otherProperty->city }}</p>
                                <a class="property-recommendation-link" href="{{ route('properties.show', $otherProperty->id) }}">Ver inmueble</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="property-description">No hay otras propiedades disponibles por el momento.</p>
            @endif
        </section>
    </div>

    <dialog class="property-media-dialog" id="property-media-dialog" aria-labelledby="property-dialog-title">
        <header class="property-dialog-heading">
            <h2 id="property-dialog-title">{{ $property->title }}</h2>
            <button type="button" class="property-dialog-close" data-dialog-close aria-label="Cerrar">&times;</button>
        </header>

        <div class="property-dialog-content" data-dialog-content="gallery" hidden>
            <div class="property-dialog-gallery">
                @foreach($images as $image)
                    <img src="{{ $image->public_url }}" alt="{{ $property->title }}" loading="lazy">
                @endforeach
            </div>
        </div>

        <div class="property-dialog-content" data-dialog-content="video" hidden>
            @if($video)
                <video class="property-dialog-video" controls preload="metadata">
                    <source src="{{ $video->public_url }}" type="video/mp4">
                    Tu navegador no soporta la reproducción de video.
                </video>
            @else
                <div class="property-media-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m10 9 5 3-5 3V9Z"></path>
                    </svg>
                    <h3>Video aún no disponible</h3>
                    <p>Esta propiedad todavía no tiene un video. Puedes solicitarle al propietario que publique uno.</p>
                    <form method="POST" action="{{ $mediaRequestUrl }}" data-media-request-form>
                        @csrf
                        <input type="hidden" name="media_type" value="video">
                        <button class="property-whatsapp-button" type="submit" data-media-request-button>
                            Solicitar video y recorrido 360°
                        </button>
                        <p class="media-request-status" role="status" aria-live="polite"></p>
                    </form>
                </div>
            @endif
        </div>

        <div class="property-dialog-content" data-dialog-content="map" hidden>
            <iframe
                class="property-dialog-map"
                src="{{ $mapEmbedUrl }}"
                title="Mapa de {{ $property->city }}{{ $property->department ? ', ' . $property->department : '' }}"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen
            ></iframe>
        </div>

        <div class="property-dialog-content" data-dialog-content="tour" hidden>
            @if($property->tour)
                <iframe class="property-dialog-map" src="{{ route('tours360.embed', $property->tour->id) }}" title="Recorrido virtual de {{ $property->title }}" allowfullscreen></iframe>
            @else
                <div class="property-media-empty">
                    <h3>Recorrido 360° aún no disponible</h3>
                    <p>Esta propiedad todavía no tiene un recorrido virtual.</p>
                    <form method="POST" action="{{ $mediaRequestUrl }}" data-media-request-form>
                        @csrf
                        <input type="hidden" name="media_type" value="tour_360">
                        <button class="property-whatsapp-button" type="submit" data-media-request-button>
                            Solicitar recorrido 360°
                        </button>
                        <p class="media-request-status" role="status" aria-live="polite"></p>
                    </form>
                </div>
            @endif
        </div>

        <div class="property-dialog-content" data-dialog-content="tour-3d" hidden>
            @if($tour3d)
                <iframe class="property-dialog-map" src="{{ $tour3d->url_or_path }}" title="Modelo 3D de {{ $property->title }}" allowfullscreen></iframe>
            @endif
        </div>

        <div class="property-dialog-content" data-dialog-content="blueprint" hidden>
            @if($blueprint)
                <iframe
                    class="property-dialog-map"
                    src="{{ $blueprintUrl }}"
                    title="Planos arquitectónicos de {{ $property->title }}"
                ></iframe>
                <a class="property-whatsapp-button" href="{{ $blueprintUrl }}" target="_blank" rel="noopener noreferrer">
                    Abrir o descargar planos PDF
                </a>
            @else
                <div class="property-media-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 3h8l5 5v13H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"></path><path d="M14 3v5h5M8 13h8M8 17h8"></path>
                    </svg>
                    <h3>Planos arquitectónicos aún no disponibles</h3>
                    <p>Solicítale al propietario que agregue los planos de este inmueble para conocer mejor su distribución.</p>
                    <form method="POST" action="{{ $mediaRequestUrl }}" data-media-request-form>
                        @csrf
                        <input type="hidden" name="media_type" value="blueprint">
                        <button class="property-whatsapp-button" type="submit" data-media-request-button>
                            Solicitar planos al propietario
                        </button>
                        <p class="media-request-status" role="status" aria-live="polite"></p>
                    </form>
                </div>
            @endif
        </div>
    </dialog>

    <script>
        (() => {
            const dialog = document.getElementById('property-media-dialog');

            document.querySelectorAll('[data-dialog-open]').forEach((button) => {
                button.addEventListener('click', () => {
                    document.querySelectorAll('[data-dialog-content]').forEach((content) => {
                        content.hidden = content.dataset.dialogContent !== button.dataset.dialogOpen;
                    });
                    dialog.showModal();
                });
            });

            document.querySelectorAll('[data-gallery-open]').forEach((button) => {
                button.addEventListener('click', () => {
                    document.querySelectorAll('[data-dialog-content]').forEach((content) => {
                        content.hidden = content.dataset.dialogContent !== 'gallery';
                    });
                    dialog.showModal();
                });
            });

            document.querySelectorAll('[data-dialog-close]').forEach((button) => {
                button.addEventListener('click', () => dialog.close());
            });

            dialog.addEventListener('click', (event) => {
                if (event.target === dialog) {
                    dialog.close();
                }
            });

            dialog.addEventListener('close', () => {
                dialog.querySelectorAll('video').forEach((videoElement) => videoElement.pause());
            });

            document.querySelectorAll('[data-media-request-form]').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const button = form.querySelector('[data-media-request-button]');
                    const status = form.querySelector('.media-request-status');
                    const originalLabel = button.textContent.trim();
                    button.disabled = true;
                    button.textContent = 'Enviando solicitud...';
                    status.textContent = '';

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            credentials: 'same-origin',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        const result = await response.json();

                        if (!response.ok) {
                            throw new Error(result.message || 'No fue posible enviar la solicitud. Inténtalo nuevamente.');
                        }

                        button.textContent = 'Solicitud enviada';
                        status.textContent = 'Avisamos al propietario por correo. Gracias por tu interés.';
                    } catch (error) {
                        button.disabled = false;
                        button.textContent = originalLabel;
                        status.textContent = error.message || 'Ocurrió un error al enviar la solicitud. Inténtalo nuevamente.';
                    }
                });
            });

            document.querySelectorAll('[data-whatsapp-form]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();

                    const formData = new FormData(form);
                    const message = [
                        form.dataset.message,
                        `Nombre: ${formData.get('name')}`,
                        `Teléfono: ${formData.get('phone')}`,
                        `Enlace de la propiedad: ${formData.get('property_url')}`,
                    ].join('\n');
                    const whatsappUrl = `https://wa.me/${form.dataset.phone}?text=${encodeURIComponent(message)}`;

                    window.open(whatsappUrl, '_blank', 'noopener,noreferrer');
                });
            });
        })();
    </script>
@endsection

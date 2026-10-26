@php
    $property = $property ?? null;
    $inputClasses = $inputClasses ?? 'mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition focus:border-teal-700 focus:ring-4 focus:ring-teal-700/10';
    $labelClasses = $labelClasses ?? 'block text-sm font-medium text-gray-700';
    $isDark = $isDark ?? false;
@endphp

<section class="space-y-4" aria-labelledby="property-details-form-heading">
    <div>
        <h2 id="property-details-form-heading" class="{{ $labelClasses }}">Características del inmueble</h2>
        <p class="mt-1 text-sm {{ $isDark ? 'text-slate-400' : 'text-slate-500' }}">
            Completa los datos que conozcas. Los campos pueden dejarse vacíos si no aplican.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach([
            'bedrooms' => ['Habitaciones', 100],
            'bathrooms' => ['Baños completos', 100],
            'half_bathrooms' => ['Medios baños', 100],
            'parking_spaces' => ['Garajes o parqueaderos', 100],
            'floor_number' => ['Piso (apartamento)', 1000],
            'total_floors' => ['Niveles del inmueble', 1000],
            'stratum' => ['Estrato', 6],
            'year_built' => ['Año de construcción', now()->year + 1],
        ] as $field => [$label, $maximum])
            <label for="{{ $field }}" class="{{ $labelClasses }}">
                {{ $label }}
                <input
                    type="number"
                    name="{{ $field }}"
                    id="{{ $field }}"
                    value="{{ old($field, $property?->{$field}) }}"
                    min="{{ $field === 'stratum' ? 1 : ($field === 'year_built' ? 1950 : 0) }}"
                    max="{{ $maximum }}"
                    class="{{ $inputClasses }}"
                >
                @error($field)<span class="mt-1 block text-sm text-rose-700">{{ $message }}</span>@enderror
            </label>
        @endforeach

        @foreach([
            'built_area_m2' => ['Área construida (m²)', '99999999.99'],
            'lot_area_m2' => ['Área del lote (m²)', '99999999.99'],
            'administration_fee' => ['Administración mensual (COP)', '100000000'],
        ] as $field => $label)
            <label for="{{ $field }}" class="{{ $labelClasses }}">
                {{ $label[0] }}
                <input
                    type="number"
                    name="{{ $field }}"
                    id="{{ $field }}"
                    value="{{ old($field, $property?->{$field}) }}"
                    min="0"
                    max="{{ $label[1] }}"
                    step="0.01"
                    class="{{ $inputClasses }}"
                >
                @error($field)<span class="mt-1 block text-sm text-rose-700">{{ $message }}</span>@enderror
            </label>
        @endforeach
    </div>

    <div class="flex flex-wrap gap-6">
        @foreach([
            'is_furnished' => 'Inmueble amoblado',
            'has_elevator' => 'Cuenta con ascensor',
        ] as $field => $label)
            <label class="flex items-center gap-2 {{ $labelClasses }}">
                <input type="hidden" name="{{ $field }}" value="0">
                <input
                    type="checkbox"
                    name="{{ $field }}"
                    id="{{ $field }}"
                    value="1"
                    class="h-4 w-4 rounded border-slate-300 accent-teal-800 focus:ring-teal-700"
                    @checked((bool) old($field, $property?->{$field} ?? false))
                >
                {{ $label }}
            </label>
        @endforeach
    </div>
</section>

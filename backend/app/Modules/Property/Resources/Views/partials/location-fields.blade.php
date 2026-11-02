<div
    class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3"
    data-location-fields
    data-selected-city="{{ $selectedCityId ?? '' }}"
    data-geocode-url="{{ route('location-catalog.geocode', [], false) }}"
    data-selected-latitude="{{ $selectedLatitude ?? '' }}"
    data-selected-longitude="{{ $selectedLongitude ?? '' }}"
>
    <div>
        <label for="department_id" class="{{ $labelClasses }}">Departamento</label>
        <select name="department_id" id="department_id" required class="{{ $inputClasses }}">
            <option value="">Selecciona un departamento</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected((string) ($selectedDepartmentId ?? '') === (string) $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>
        @error('department_id')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="city_id" class="{{ $labelClasses }}">Ciudad / municipio</label>
        <select name="city_id" id="city_id" required disabled class="{{ $inputClasses }}">
            <option value="">Selecciona primero un departamento</option>
        </select>
        <template data-city-options>
            @foreach($departments as $department)
                @foreach($department->cities as $city)
                    <option value="{{ $city->id }}" data-department-id="{{ $department->id }}">{{ $city->name }}</option>
                @endforeach
            @endforeach
        </template>
        @error('city_id')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="neighborhood" class="{{ $labelClasses }}">Barrio / sector</label>
        <input
            type="text"
            name="neighborhood"
            id="neighborhood"
            value="{{ $selectedNeighborhood ?? '' }}"
            maxlength="255"
            required
            placeholder="Escribe el barrio o sector"
            class="{{ $inputClasses }}"
        >
        @error('neighborhood')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2 lg:col-span-3">
        <label for="property-address" class="{{ $labelClasses }}">Dirección (opcional)</label>
        <div class="mt-2 flex flex-col gap-2 sm:flex-row">
            <input
                type="text"
                name="address"
                id="property-address"
                value="{{ $selectedAddress ?? '' }}"
                maxlength="255"
                placeholder="Ej. Calle 10 # 20-30"
                class="{{ $inputClasses }} mt-0"
                data-location-address
            >
            <button type="button" class="min-h-12 shrink-0 rounded-xl bg-blue-700 px-5 text-sm font-bold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50" data-search-address disabled>
                Buscar dirección
            </button>
        </div>
        @error('address')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
    </div>

    <input type="hidden" name="latitude" value="{{ $selectedLatitude ?? '' }}" data-location-latitude>
    <input type="hidden" name="longitude" value="{{ $selectedLongitude ?? '' }}" data-location-longitude>
    @error('latitude')<p class="sm:col-span-2 lg:col-span-3 text-sm text-rose-700">{{ $message }}</p>@enderror
    @error('longitude')<p class="sm:col-span-2 lg:col-span-3 text-sm text-rose-700">{{ $message }}</p>@enderror
    <div class="sm:col-span-2 lg:col-span-3">
        <p class="mb-2 text-sm text-slate-600">Selecciona primero el departamento y la ciudad, luego ubica el pin o busca una dirección.</p>
        <div
            id="property-location-map"
            class="property-location-map"
            tabindex="0"
            aria-label="Mapa para marcar la ubicación del inmueble"
            aria-describedby="property-location-status"
        ></div>
        <p id="property-location-status" class="mt-2 text-sm text-slate-500" data-location-status role="status" aria-live="polite"></p>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<style>
    .property-location-map {
        z-index: 0;
        min-height: 360px;
        overflow: hidden;
        border: 1px solid #cbd5e1;
        border-radius: 16px;
        background: #e2e8f0;
    }

    @media (max-width: 640px) {
        .property-location-map {
            min-height: 300px;
        }
    }
</style>

<script>
    (() => {
        const container = document.querySelector('[data-location-fields]');
        if (!container) {
            return;
        }

        const department = container.querySelector('#department_id');
        const city = container.querySelector('#city_id');
        const cityOptions = container.querySelector('[data-city-options]');
        const status = container.querySelector('[data-location-status]');
        const address = container.querySelector('[data-location-address]');
        const searchAddress = container.querySelector('[data-search-address]');
        const latitudeInput = container.querySelector('[data-location-latitude]');
        const longitudeInput = container.querySelector('[data-location-longitude]');
        const mapElement = document.getElementById('property-location-map');

        container.closest('form')?.addEventListener('submit', (event) => {
            if (latitudeInput.value.trim() && longitudeInput.value.trim()) {
                return;
            }

            event.preventDefault();
            status.textContent = 'Marca un punto en el mapa para guardar el inmueble.';
            mapElement.focus();
            mapElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });

        if (!window.L) {
            status.textContent = 'No se pudo cargar el mapa. Verifica tu conexión y vuelve a intentarlo.';
            return;
        }

        const map = window.L.map(mapElement).setView([4.5709, -74.2973], 6);
        window.L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
            subdomains: 'abcd',
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        }).addTo(map);

        let marker = null;
        let searchInProgress = false;

        const setMarker = (latitude, longitude, zoom = 16) => {
            const point = [Number(latitude), Number(longitude)];
            if (marker) {
                marker.setLatLng(point);
            } else {
                marker = window.L.marker(point, { draggable: true }).addTo(map);
                marker.on('dragend', () => {
                    const position = marker.getLatLng();
                    latitudeInput.value = position.lat.toFixed(7);
                    longitudeInput.value = position.lng.toFixed(7);
                });
            }

            latitudeInput.value = point[0].toFixed(7);
            longitudeInput.value = point[1].toFixed(7);
            map.setView(point, zoom);
            status.textContent = 'Ubicación seleccionada. Puedes arrastrar el pin para ajustarla.';
        };

        const geocode = async (includeAddress) => {
            const cityName = city.selectedOptions[0]?.textContent.trim();
            const departmentName = department.selectedOptions[0]?.textContent.trim();
            if (!department.value || !city.value || !cityName || !departmentName) {
                status.textContent = 'Selecciona primero el departamento y la ciudad.';
                return;
            }

            if (searchInProgress) {
                return;
            }

            const query = new URLSearchParams({
                city: cityName,
                department: departmentName,
            });
            if (includeAddress && address.value.trim()) {
                query.set('address', address.value.trim());
            }

            searchInProgress = true;
            searchAddress.disabled = true;
            status.textContent = 'Buscando ubicación…';

            try {
                const response = await fetch(`${container.dataset.geocodeUrl}?${query.toString()}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const responseBody = await response.text();
                let result = null;
                try {
                    result = JSON.parse(responseBody);
                } catch {}

                if (!response.ok) {
                    status.textContent = result?.message ?? 'No se pudo encontrar la ubicación. Puedes marcarla directamente en el mapa.';
                    return;
                }

                if (!Number.isFinite(result?.latitude) || !Number.isFinite(result?.longitude)) {
                    status.textContent = 'No se pudo encontrar la ubicación. Puedes marcarla directamente en el mapa.';
                    return;
                }

                setMarker(result.latitude, result.longitude, includeAddress ? 17 : 13);
            } catch {
                status.textContent = 'No se pudo encontrar la ubicación. Puedes marcarla directamente en el mapa.';
            } finally {
                searchInProgress = false;
                searchAddress.disabled = !city.value;
            }
        };

        map.on('click', (event) => {
            setMarker(event.latlng.lat, event.latlng.lng);
        });

        const loadCities = (selectedId = '') => {
            const departmentId = department.value;
            city.replaceChildren(new Option(
                departmentId ? 'Selecciona una ciudad' : 'Selecciona primero un departamento',
                '',
            ));

            let cityCount = 0;
            for (const option of cityOptions.content.querySelectorAll('option[data-department-id]')) {
                if (option.dataset.departmentId !== departmentId) {
                    continue;
                }

                city.add(option.cloneNode(true));
                cityCount++;
            }

            city.disabled = !departmentId || cityCount === 0;
            searchAddress.disabled = city.disabled;
            const selectedCity = Array.from(city.options).find((option) => option.value === String(selectedId));
            if (selectedCity) {
                city.value = String(selectedId);
            }

            if (departmentId && cityCount === 0) {
                status.textContent = 'No hay ciudades registradas para este departamento. Solicita al administrador que las agregue al catálogo.';
            }
        };

        department.addEventListener('change', () => {
            latitudeInput.value = '';
            longitudeInput.value = '';
            marker?.remove();
            marker = null;
            loadCities();
            status.textContent = '';
        });
        city.addEventListener('change', () => {
            latitudeInput.value = '';
            longitudeInput.value = '';
            marker?.remove();
            marker = null;
            geocode(false);
        });
        searchAddress.addEventListener('click', () => geocode(true));

        if (department.value) {
            loadCities(container.dataset.selectedCity);
        }

        const existingLatitude = container.dataset.selectedLatitude;
        const existingLongitude = container.dataset.selectedLongitude;
        if (existingLatitude && existingLongitude) {
            setMarker(existingLatitude, existingLongitude);
        } else if (city.value) {
            geocode(false);
        }
    })();
</script>

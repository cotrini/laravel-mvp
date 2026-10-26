<div
    class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3"
    data-location-fields
    data-selected-city="{{ $selectedCityId ?? '' }}"
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

    <p class="sm:col-span-2 lg:col-span-3 text-sm text-slate-500" data-location-status role="status" aria-live="polite"></p>
</div>

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
            const selectedCity = Array.from(city.options).find((option) => option.value === String(selectedId));
            if (selectedCity) {
                city.value = String(selectedId);
            }

            status.textContent = departmentId && cityCount === 0
                ? 'No hay ciudades registradas para este departamento. Solicita al administrador que las agregue al catálogo.'
                : '';
        };

        department.addEventListener('change', () => loadCities());

        if (department.value) {
            loadCities(container.dataset.selectedCity);
        }
    })();
</script>

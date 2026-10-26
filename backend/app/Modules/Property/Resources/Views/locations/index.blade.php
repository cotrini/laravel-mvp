@extends('layouts.app')

@section('title', 'Catálogo de ubicaciones - Colombia Sin Comisión')

@section('content')
    @php
        $inputClasses = 'w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition focus:border-teal-700 focus:ring-4 focus:ring-teal-700/10';
        $buttonClasses = 'rounded-xl bg-teal-800 px-4 py-3 text-sm font-bold text-white transition hover:bg-teal-900';
    @endphp

    <main class="min-h-screen bg-slate-50 px-4 py-10 text-slate-800 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl space-y-7">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-teal-700">Administración</p>
                    <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-900">Catálogo de ubicaciones</h1>
                    <p class="mt-2 text-sm text-slate-600">Mantén las listas de departamentos, municipios y barrios que aparecen al publicar un inmueble.</p>
                </div>
                <a href="{{ route('dashboard.index') }}" class="text-sm font-bold text-teal-800 hover:text-teal-950">Volver al panel</a>
            </header>

            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800" role="status">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="space-y-5 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-extrabold text-slate-900">Departamentos</h2>
                        <p class="mt-1 text-sm text-slate-500">Los departamentos del país y Bogotá D.C. ya están precargados; puedes agregar o ajustar opciones.</p>
                    </div>
                    <form action="{{ route('location-catalog.departments.store') }}" method="POST" class="flex w-full gap-2 sm:w-auto">
                        @csrf
                        <label class="sr-only" for="new-department">Nuevo departamento</label>
                        <input class="{{ $inputClasses }} min-w-0 sm:w-64" name="name" id="new-department" required maxlength="255" placeholder="Nuevo departamento">
                        <button class="{{ $buttonClasses }}" type="submit">Agregar</button>
                    </form>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    @foreach($departments as $department)
                        <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-100 bg-slate-50 p-3">
                            <form action="{{ route('location-catalog.departments.update', $department) }}" method="POST" class="flex min-w-0 flex-1 gap-2">
                                @csrf
                                @method('PUT')
                                <label class="sr-only" for="department-{{ $department->id }}">Nombre del departamento</label>
                                <input class="{{ $inputClasses }} min-w-0" name="name" id="department-{{ $department->id }}" value="{{ $department->name }}" required maxlength="255">
                                <button class="rounded-lg border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 hover:bg-teal-50">Guardar</button>
                            </form>
                            <form action="{{ route('location-catalog.departments.destroy', $department) }}" method="POST" onsubmit="return confirm('¿Eliminar este departamento del catálogo?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" @disabled($department->cities_count > 0) class="rounded-lg border border-rose-200 bg-white px-3 py-3 text-xs font-bold text-rose-700 enabled:hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-40" title="{{ $department->cities_count > 0 ? 'Primero elimina sus ciudades' : 'Eliminar departamento' }}">Eliminar</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="space-y-5 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900">Ciudades y municipios</h2>
                    <p class="mt-1 text-sm text-slate-500">Selecciona un departamento para administrar sus municipios.</p>
                </div>
                <form action="{{ route('location-catalog.index') }}" method="GET" class="flex flex-col gap-3 sm:flex-row">
                    <label class="sr-only" for="catalog-department">Departamento</label>
                    <select class="{{ $inputClasses }} sm:max-w-md" name="department_id" id="catalog-department" required>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected($selectedDepartment?->id === $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                    <button class="{{ $buttonClasses }}" type="submit">Ver municipios</button>
                </form>

                @if($selectedDepartment)
                    <form action="{{ route('location-catalog.cities.store') }}" method="POST" class="flex flex-col gap-2 sm:flex-row">
                        @csrf
                        <input type="hidden" name="department_id" value="{{ $selectedDepartment->id }}">
                        <label class="sr-only" for="new-city">Nuevo municipio</label>
                        <input class="{{ $inputClasses }} min-w-0" name="name" id="new-city" required maxlength="255" placeholder="Nuevo municipio en {{ $selectedDepartment->name }}">
                        <button class="{{ $buttonClasses }}" type="submit">Agregar municipio</button>
                    </form>

                    <div class="space-y-3">
                        @forelse($cities as $city)
                            <div class="grid gap-3 rounded-2xl border border-slate-100 bg-slate-50 p-3 md:grid-cols-[minmax(0,1fr)_auto_auto]">
                                <form action="{{ route('location-catalog.cities.update', $city) }}" method="POST" class="flex min-w-0 gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="department_id" value="{{ $selectedDepartment->id }}">
                                    <label class="sr-only" for="city-{{ $city->id }}">Nombre del municipio</label>
                                    <input class="{{ $inputClasses }} min-w-0" name="name" id="city-{{ $city->id }}" value="{{ $city->name }}" required maxlength="255">
                                    <button class="rounded-lg border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 hover:bg-teal-50">Guardar</button>
                                </form>
                                <a href="{{ route('location-catalog.index', ['department_id' => $selectedDepartment->id, 'city_id' => $city->id]) }}" class="inline-flex items-center justify-center rounded-xl border border-teal-200 bg-white px-4 py-3 text-sm font-bold text-teal-800 hover:bg-teal-50">
                                    Barrios ({{ $city->neighborhoods_count }})
                                </a>
                                <form action="{{ route('location-catalog.cities.destroy', $city) }}" method="POST" onsubmit="return confirm('¿Eliminar este municipio del catálogo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" @disabled($city->neighborhoods_count > 0) class="h-full rounded-xl border border-rose-200 bg-white px-4 py-3 text-xs font-bold text-rose-700 enabled:hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-40" title="{{ $city->neighborhoods_count > 0 ? 'Primero elimina sus barrios' : 'Eliminar municipio' }}">Eliminar</button>
                                </form>
                            </div>
                        @empty
                            <p class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">Todavía no hay municipios en este departamento.</p>
                        @endforelse
                    </div>
                @endif
            </section>

            @if($selectedCity)
                <section class="space-y-5 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <div>
                        <h2 class="text-xl font-extrabold text-slate-900">Barrios de {{ $selectedCity->name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $selectedDepartment->name }} · Administra sectores y barrios disponibles.</p>
                    </div>
                    <form action="{{ route('location-catalog.neighborhoods.store') }}" method="POST" class="flex flex-col gap-2 sm:flex-row">
                        @csrf
                        <input type="hidden" name="city_id" value="{{ $selectedCity->id }}">
                        <label class="sr-only" for="new-neighborhood">Nuevo barrio</label>
                        <input class="{{ $inputClasses }} min-w-0" name="name" id="new-neighborhood" required maxlength="255" placeholder="Nuevo barrio o sector">
                        <button class="{{ $buttonClasses }}" type="submit">Agregar barrio</button>
                    </form>

                    <div class="grid gap-3 md:grid-cols-2">
                        @forelse($neighborhoods as $neighborhood)
                            <div class="flex min-w-0 gap-2 rounded-2xl border border-slate-100 bg-slate-50 p-3">
                                <form action="{{ route('location-catalog.neighborhoods.update', $neighborhood) }}" method="POST" class="flex min-w-0 flex-1 gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="city_id" value="{{ $selectedCity->id }}">
                                    <label class="sr-only" for="neighborhood-{{ $neighborhood->id }}">Nombre del barrio</label>
                                    <input class="{{ $inputClasses }} min-w-0" name="name" id="neighborhood-{{ $neighborhood->id }}" value="{{ $neighborhood->name }}" required maxlength="255">
                                    <button class="rounded-lg border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 hover:bg-teal-50">Guardar</button>
                                </form>
                                <form action="{{ route('location-catalog.neighborhoods.destroy', $neighborhood) }}" method="POST" onsubmit="return confirm('¿Eliminar este barrio del catálogo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-xl border border-rose-200 bg-white px-3 py-3 text-xs font-bold text-rose-700 hover:bg-rose-50">Eliminar</button>
                                </form>
                            </div>
                        @empty
                            <p class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">Todavía no hay barrios registrados para esta ciudad.</p>
                        @endforelse
                    </div>
                </section>
            @endif
        </div>
    </main>
@endsection

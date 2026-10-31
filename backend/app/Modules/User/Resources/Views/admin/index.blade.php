@extends('layouts.app')

@section('title', 'Administrar cuentas - Colombia Sin Comisión')

@section('content')
<div class="min-h-[80vh] py-10 px-4">
    <div class="mx-auto max-w-6xl space-y-8">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-600">Administración</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-800">Cuentas y asignaciones</h1>
                <p class="mt-2 text-gray-600">Crea cuentas con roles y asigna inmuebles a los cineastas.</p>
            </div>
            <a href="{{ route('dashboard.index') }}" class="font-medium text-blue-600 hover:underline">Volver al panel</a>
        </header>

        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-green-700" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-100 bg-red-50 p-4 text-sm text-red-600" role="alert">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-xl" aria-labelledby="create-user-title">
            <h2 id="create-user-title" class="text-xl font-semibold text-slate-800">Crear cuenta</h2>
            <form action="{{ route('admin.users.store') }}" method="POST" class="mt-5 grid gap-4 md:grid-cols-2">
                @csrf
                <div>
                    <label for="name" class="mb-2 block text-sm font-semibold text-gray-600">Nombre</label>
                    <input id="name" name="name" value="{{ old('name') }}" required class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>
                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-gray-600">Correo electrónico</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>
                <div>
                    <label for="role" class="mb-2 block text-sm font-semibold text-gray-600">Tipo de cuenta</label>
                    <select id="role" name="role" required class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="user" @selected(old('role', 'user') === 'user')>Usuario</option>
                        <option value="filmmaker" @selected(old('role') === 'filmmaker')>Cineasta</option>
                        <option value="admin" @selected(old('role') === 'admin')>Administrador</option>
                    </select>
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-gray-600">Contraseña</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>
                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-gray-600">Confirmar contraseña</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full rounded-xl bg-blue-600 px-4 py-3 font-semibold text-white transition hover:bg-blue-700">
                        Crear cuenta
                    </button>
                </div>
            </form>
        </section>

        <section class="space-y-4" aria-labelledby="user-list-title">
            <h2 id="user-list-title" class="text-xl font-semibold text-slate-800">Cuentas registradas</h2>
            @forelse ($users as $managedUser)
                <article class="rounded-2xl border border-gray-100 bg-white p-6 shadow-md">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-slate-800">{{ $managedUser->name }}</h3>
                            <p class="text-sm text-gray-600">{{ $managedUser->email }}</p>
                        </div>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700">
                            {{ match ($managedUser->role) { 'admin' => 'Administrador', 'filmmaker' => 'Cineasta', default => 'Usuario' } }}
                        </span>
                    </div>

                    @if ($managedUser->isFilmmaker())
                        <form action="{{ route('admin.users.properties.update', $managedUser) }}" method="POST" class="mt-5 border-t border-gray-100 pt-5">
                            @csrf
                            @method('PUT')
                            <label for="property_ids_{{ $managedUser->id }}" class="mb-2 block text-sm font-semibold text-gray-600">
                                Inmuebles asignados
                            </label>
                            <select
                                id="property_ids_{{ $managedUser->id }}"
                                name="property_ids[]"
                                multiple
                                size="5"
                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            >
                                @foreach ($properties as $property)
                                    <option value="{{ $property->id }}" @selected($managedUser->assignedProperties->contains('id', $property->id))>
                                        {{ $property->title }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-sm text-gray-500">Usa Ctrl o Cmd para seleccionar varias propiedades.</p>
                            <button type="submit" class="mt-4 rounded-xl border border-blue-200 px-4 py-2 font-semibold text-blue-700 transition hover:bg-blue-50">
                                Guardar asignaciones
                            </button>
                        </form>
                    @endif
                </article>
            @empty
                <p class="rounded-xl bg-white p-6 text-gray-600">No hay cuentas registradas.</p>
            @endforelse

            <div>{{ $users->links() }}</div>
        </section>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Administrar cuentas - Colombia Sin Comisión')

@section('content')
<div class="min-h-[80vh] py-10 px-4">
    <div class="mx-auto max-w-6xl space-y-8">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-600">Administración</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-800">Cuentas y asignaciones</h1>
                <p class="mt-2 text-gray-600">Crea y administra cuentas; asigna inmuebles a Filmmaker y avaluadores.</p>
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
                        <option value="filmmaker" @selected(old('role') === 'filmmaker')>Filmmaker</option>
                        <option value="appraiser" @selected(old('role') === 'appraiser')>Avaluador</option>
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
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2 id="user-list-title" class="text-xl font-semibold text-slate-800">Cuentas registradas</h2>
                <form action="{{ route('admin.users.index') }}" method="GET" role="search" class="flex w-full flex-wrap gap-3 sm:w-auto">
                    <div class="min-w-0 flex-1 sm:w-80">
                        <label for="user-search" class="mb-2 block text-sm font-semibold text-gray-600">Buscar por nombre, correo o teléfono</label>
                        <input
                            id="user-search"
                            name="search"
                            type="search"
                            value="{{ $search }}"
                            maxlength="100"
                            placeholder="Ej. María, correo o +57..."
                            class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="self-end rounded-xl bg-blue-600 px-4 py-3 font-semibold text-white transition hover:bg-blue-700">
                            Buscar
                        </button>
                        @if ($search !== '')
                            <a href="{{ route('admin.users.index') }}" class="self-end rounded-xl border border-gray-200 px-4 py-3 font-semibold text-gray-700 transition hover:bg-gray-50">
                                Limpiar
                            </a>
                        @endif
                    </div>
                </form>
            </div>
            @forelse ($users as $managedUser)
                <article class="rounded-2xl border border-gray-100 bg-white p-6 shadow-md">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-slate-800">{{ $managedUser->name }}</h3>
                            <p class="text-sm text-gray-600">{{ $managedUser->email }}</p>
                        </div>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700">
                            {{ match ($managedUser->role) { 'admin' => 'Administrador', 'filmmaker' => 'Filmmaker', 'appraiser' => 'Avaluador', default => 'Usuario' } }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ $managedUser->is_banned ? 'Cuenta baneada' : 'Cuenta activa' }}
                        <span class="mx-1">·</span>
                        {{ ! $managedUser->requiresEmailVerification() ? 'Correo sin requisito de verificación' : ($managedUser->hasVerifiedEmail() ? 'Correo verificado' : 'Correo sin verificar') }}
                    </p>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <details class="w-full rounded-xl border border-gray-200 p-4">
                            <summary class="cursor-pointer font-semibold text-blue-700">Editar información de la cuenta</summary>
                            <form action="{{ route('admin.users.update', $managedUser) }}" method="POST" class="mt-4 grid gap-4 md:grid-cols-2">
                                @csrf
                                @method('PUT')
                                <div>
                                    <label for="name_{{ $managedUser->id }}" class="mb-2 block text-sm font-semibold text-gray-600">Nombre</label>
                                    <input id="name_{{ $managedUser->id }}" name="name" value="{{ $managedUser->name }}" required maxlength="255" class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                </div>
                                <div>
                                    <label for="email_{{ $managedUser->id }}" class="mb-2 block text-sm font-semibold text-gray-600">Correo electrónico</label>
                                    <input id="email_{{ $managedUser->id }}" name="email" type="email" value="{{ $managedUser->email }}" required maxlength="255" class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                </div>
                                <div>
                                    <label for="phone_number_{{ $managedUser->id }}" class="mb-2 block text-sm font-semibold text-gray-600">Teléfono</label>
                                    <input id="phone_number_{{ $managedUser->id }}" name="phone_number" type="tel" value="{{ $managedUser->phone_number }}" maxlength="30" class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                </div>
                                <div>
                                    <label for="role_{{ $managedUser->id }}" class="mb-2 block text-sm font-semibold text-gray-600">Rol</label>
                                    <select id="role_{{ $managedUser->id }}" name="role" required class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                        <option value="user" @selected($managedUser->role === 'user')>Usuario</option>
                                        <option value="filmmaker" @selected($managedUser->role === 'filmmaker')>Filmmaker</option>
                                        <option value="appraiser" @selected($managedUser->role === 'appraiser')>Avaluador</option>
                                        <option value="admin" @selected($managedUser->role === 'admin')>Administrador</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="password_{{ $managedUser->id }}" class="mb-2 block text-sm font-semibold text-gray-600">Nueva contraseña (opcional)</label>
                                    <input id="password_{{ $managedUser->id }}" name="password" type="password" autocomplete="new-password" minlength="8" class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                </div>
                                <div>
                                    <label for="password_confirmation_{{ $managedUser->id }}" class="mb-2 block text-sm font-semibold text-gray-600">Confirmar nueva contraseña</label>
                                    <input id="password_confirmation_{{ $managedUser->id }}" name="password_confirmation" type="password" autocomplete="new-password" class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                </div>
                                <div class="md:col-span-2">
                                    <button type="submit" class="rounded-xl bg-blue-600 px-4 py-3 font-semibold text-white transition hover:bg-blue-700">
                                        Guardar cambios
                                    </button>
                                </div>
                            </form>
                        </details>

                        @unless (auth()->user()->is($managedUser))
                            <form action="{{ route('admin.users.ban', $managedUser) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-xl border px-4 py-2 font-semibold {{ $managedUser->is_banned ? 'border-green-200 text-green-700 hover:bg-green-50' : 'border-red-200 text-red-700 hover:bg-red-50' }}">
                                    {{ $managedUser->is_banned ? 'Desbanear cuenta' : 'Banear cuenta' }}
                                </button>
                            </form>
                        @endunless
                    </div>

                    @if ($managedUser->isFilmmaker() || $managedUser->isAppraiser())
                        @php
                            $managedUserAssignments = $managedUser->isAppraiser()
                                ? $managedUser->assignedAppraisalProperties
                                : $managedUser->assignedProperties;
                            $assignmentSearchMatchesUser = $assignmentLookupPerformed
                                && $assignmentUserId === $managedUser->id;
                        @endphp
                        <div class="mt-5 border-t border-gray-100 pt-5">
                            <h4 class="mb-3 text-sm font-semibold text-gray-700">
                                {{ $managedUser->isAppraiser() ? 'Inmuebles asignados al avaluador' : 'Inmuebles asignados al Filmmaker' }}
                            </h4>
                            <form action="{{ route('admin.users.index') }}" method="GET" role="search" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                                <input type="hidden" name="assignment_user_id" value="{{ $managedUser->id }}">
                                @if ($search !== '')
                                    <input type="hidden" name="search" value="{{ $search }}">
                                @endif
                                <input type="hidden" name="page" value="{{ $users->currentPage() }}">
                                <div>
                                    <label for="property_code_{{ $managedUser->id }}" class="mb-2 block text-sm font-semibold text-gray-600">
                                        Buscar inmueble por código
                                    </label>
                                    <input
                                        id="property_code_{{ $managedUser->id }}"
                                        name="property_code"
                                        type="search"
                                        value="{{ $assignmentSearchMatchesUser ? $assignmentCode : '' }}"
                                        required
                                        maxlength="32"
                                        placeholder="Ej. CSC-00000001"
                                        class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                    >
                                </div>
                                <button type="submit" class="self-end rounded-xl border border-blue-200 px-4 py-3 font-semibold text-blue-700 transition hover:bg-blue-50">
                                    Buscar inmueble
                                </button>
                            </form>

                            @if ($assignmentSearchMatchesUser)
                                @if ($assignmentProperty)
                                    @php
                                        $propertyAlreadyAssigned = $managedUserAssignments->contains('id', $assignmentProperty->id);
                                    @endphp
                                    <div class="mt-4 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
                                        <div>
                                            <p class="font-semibold text-slate-800">{{ $assignmentProperty->title }}</p>
                                            <p class="mt-1 text-sm text-gray-600">
                                                {{ $assignmentProperty->identification_code }} ·
                                                {{ $assignmentProperty->city }}, {{ $assignmentProperty->department }}
                                            </p>
                                            @if ($assignmentProperty->user)
                                                <p class="mt-1 text-sm text-gray-500">Propietario: {{ $assignmentProperty->user->name }}</p>
                                            @endif
                                        </div>
                                        @if ($propertyAlreadyAssigned)
                                            <span class="rounded-full bg-green-50 px-3 py-1 text-sm font-semibold text-green-700">
                                                Ya asignado
                                            </span>
                                        @else
                                            <form action="{{ route('admin.users.properties.store', $managedUser) }}" method="POST">
                                                @csrf
                                                @if ($search !== '')
                                                    <input type="hidden" name="search" value="{{ $search }}">
                                                @endif
                                                <input type="hidden" name="page" value="{{ $users->currentPage() }}">
                                                <input type="hidden" name="property_code" value="{{ $assignmentProperty->identification_code }}">
                                                <button type="submit" class="rounded-xl bg-blue-600 px-4 py-3 font-semibold text-white transition hover:bg-blue-700">
                                                    Asignar propiedad
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @else
                                    <p class="mt-3 rounded-xl bg-amber-50 p-3 text-sm text-amber-800" role="status">
                                        No encontramos un inmueble con el código {{ $assignmentCode }}.
                                    </p>
                                @endif
                            @endif

                            <ul class="mt-4 space-y-2">
                                @forelse ($managedUserAssignments as $assignedProperty)
                                    <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-100 p-3">
                                        <div>
                                            <p class="font-medium text-slate-800">{{ $assignedProperty->title }}</p>
                                            <p class="text-sm text-gray-500">
                                                {{ $assignedProperty->identification_code }} · {{ $assignedProperty->city }}
                                            </p>
                                        </div>
                                        <form action="{{ route('admin.users.properties.destroy', [$managedUser, $assignedProperty]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                                Retirar asignación
                                            </button>
                                        </form>
                                    </li>
                                @empty
                                    <li class="text-sm text-gray-500">Aún no tiene inmuebles asignados.</li>
                                @endforelse
                            </ul>
                        </div>
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

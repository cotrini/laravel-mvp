@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-10 px-4">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-gray-100 p-8 relative">
        <a href="{{ url('/') }}" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600 transition-colors" aria-label="Volver al inicio">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </a>

        <h2 class="text-2xl font-bold text-slate-800 text-center mb-8">
            Crear una cuenta
        </h2>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-100 text-red-600 text-sm" role="alert">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-xs font-semibold text-gray-500 mb-2">
                    Nombre
                </label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name') }}"
                    placeholder="Ingresa tu nombre"
                    autocomplete="name"
                    required
                    autofocus
                    class="w-full px-4 py-3.5 rounded-xl border border-blue-400/80 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-gray-700 placeholder-gray-300 transition"
                >
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold text-gray-500 mb-2">
                    Correo Electrónico
                </label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    value="{{ old('email') }}"
                    placeholder="Ingresa tu correo electrónico"
                    autocomplete="email"
                    required
                    class="w-full px-4 py-3.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-gray-700 placeholder-gray-300 transition"
                >
            </div>

            <div>
                <label for="phone_number" class="block text-xs font-semibold text-gray-500 mb-2">
                    Número de teléfono
                </label>
                <input
                    type="tel"
                    name="phone_number"
                    id="phone_number"
                    value="{{ old('phone_number') }}"
                    placeholder="+57 300 123 4567"
                    autocomplete="tel"
                    inputmode="tel"
                    required
                    class="w-full px-4 py-3.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-gray-700 placeholder-gray-300 transition"
                >
                <p class="mt-2 text-xs text-gray-400">
                    Celular colombiano de 10 dígitos que empieza por 3. Puedes incluir el código +57.
                </p>
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-gray-500 mb-2">
                    Contraseña
                </label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    placeholder="Mínimo 8 caracteres"
                    autocomplete="new-password"
                    required
                    class="w-full px-4 py-3.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-gray-700 placeholder-gray-300 transition"
                >
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold text-gray-500 mb-2">
                    Confirmar Contraseña
                </label>
                <input
                    type="password"
                    name="password_confirmation"
                    id="password_confirmation"
                    placeholder="Repite tu contraseña"
                    autocomplete="new-password"
                    required
                    class="w-full px-4 py-3.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-gray-700 placeholder-gray-300 transition"
                >
            </div>

            <button
                type="submit"
                class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-md shadow-blue-500/20 transition-all duration-200"
            >
                Crear cuenta
            </button>
        </form>

        <div class="relative my-6 text-center">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <span class="relative bg-white px-3 text-sm text-gray-400">o</span>
        </div>

        <div class="mt-8 text-center text-sm text-gray-600">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:underline">
                Inicia sesión
            </a>
        </div>

    </div>
</div>
@endsection
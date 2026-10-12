@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-10 px-4">
    <!-- Contenedor Modal/Tarjeta -->
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-gray-100 p-8 relative">
        
        <!-- Botón de Cerrar (X) -->
        <a href="{{ url('/') }}" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </a>

        <!-- Título -->
        <h2 class="text-2xl font-bold text-slate-800 text-center mb-8">
            Bienvenido de vuelta
        </h2>

        <!-- Errores de Validación -->
        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-100 text-red-600 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Formulario de Login -->
        <form action="{{ route('login') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Correo Electrónico -->
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
                    required 
                    autofocus
                    class="w-full px-4 py-3.5 rounded-xl border border-blue-400/80 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-gray-700 placeholder-gray-300 transition"
                >
            </div>

            <!-- Contraseña -->
            <div>
                <label for="password" class="block text-xs font-semibold text-gray-500 mb-2">
                    Contraseña
                </label>
                <div class="relative">
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        placeholder="••••••••" 
                        required
                        class="w-full px-4 py-3.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-gray-700 placeholder-gray-300 transition pr-10"
                    >
                    <!-- Icono para Mostrar/Ocultar Contraseña -->
                    <button type="button" onclick="togglePasswordVisibility()" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M1 1l22 22"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Botón Ingresar -->
            <button 
                type="submit" 
                class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-md shadow-blue-500/20 transition-all duration-200"
            >
                Ingresar
            </button>
        </form>

        <!-- ¿Olvidaste tu contraseña? -->
        <div class="text-right mt-4 mb-6">
            <a href="#" class="text-sm font-medium text-blue-600 hover:underline">
                ¿Olvidaste tu contraseña?
            </a>
        </div>

        <!-- Separador con 'o' -->
        <div class="relative my-6 text-center">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <span class="relative bg-white px-3 text-sm text-gray-400">o</span>
        </div>

        <!-- Botones Social Auth -->
        <div class="space-y-3">
            <!-- Facebook -->
            <a href="#" class="w-full flex items-center justify-center gap-3 py-3 px-4 border border-gray-200 rounded-xl font-semibold text-slate-700 hover:bg-gray-50 transition">
                <svg class="w-5 h-5 text-blue-600 fill-current" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                Continuar con Facebook
            </a>

            <!-- Google -->
            <a href="#" class="w-full flex items-center justify-center gap-3 py-3 px-4 border border-gray-200 rounded-xl font-semibold text-slate-700 hover:bg-gray-50 transition">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                Continuar con Google
            </a>
        </div>

        <!-- Registro Footer -->
        <div class="mt-8 text-center text-sm text-gray-600">
            ¿No tienes cuenta? 
            <a href="{{ route('register') }}" class="font-medium text-blue-600 hover:underline inline-flex items-center gap-1">
                Regístrate &rarr;
            </a>
        </div>

    </div>
</div>

<script>
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
        } else {
            passwordInput.type = 'password';
        }
    }
</script>
@endsection
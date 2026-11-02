@extends('layouts.app')

@section('title', 'Recuperar contraseña - Colombia Sin Comisión')

@section('content')
    <main class="mx-auto flex min-h-[70vh] max-w-xl items-center px-4 py-12">
        <section class="w-full rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">Recupera tu contraseña</h1>
            <p class="mt-3 text-slate-600">
                Ingresa el correo asociado a tu cuenta y te enviaremos un enlace para crear una nueva contraseña.
            </p>

            @if (session('status'))
                <p class="mt-5 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800" role="status">
                    {{ session('status') }}
                </p>
            @endif

            @if ($errors->any())
                <div class="mt-5 rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Correo electrónico</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                        autofocus
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >
                </div>

                <button type="submit" class="w-full rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white transition hover:bg-blue-700">
                    Enviar enlace de recuperación
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-600">
                <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:underline">Volver a iniciar sesión</a>
            </p>
        </section>
    </main>
@endsection

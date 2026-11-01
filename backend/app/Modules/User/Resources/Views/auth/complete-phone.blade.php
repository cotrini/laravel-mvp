@extends('layouts.app')

@section('title', 'Completa tu teléfono - Colombia Sin Comisión')

@section('content')
    <main class="min-h-[70vh]">
        <dialog open class="m-auto w-[min(94vw,560px)] rounded-3xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-950/70">
            <section class="space-y-5 p-7 sm:p-9" aria-labelledby="complete-phone-title">
                <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-blue-700">Un último paso</p>
                <h1 id="complete-phone-title" class="text-2xl font-extrabold text-slate-900">Completa tu información</h1>
                <p class="text-sm leading-6 text-slate-600">
                    Para continuar, registra tu celular colombiano. Este paso es obligatorio y solo se solicita cuando tu cuenta aún no tiene teléfono.
                </p>

                @if($errors->any())
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
                        {{ $errors->first('phone_number') }}
                    </div>
                @endif

                <form action="{{ route('profile.phone.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <label for="phone_number" class="block text-sm font-bold text-slate-700">
                        Teléfono celular
                        <input
                            id="phone_number"
                            name="phone_number"
                            type="tel"
                            value="{{ old('phone_number') }}"
                            placeholder="+57 300 123 4567"
                            autocomplete="tel"
                            required
                            autofocus
                            class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-600/10"
                        >
                    </label>
                    <button type="submit" class="w-full rounded-xl bg-blue-700 px-5 py-3 font-bold text-white transition hover:bg-blue-800">
                        Guardar y continuar
                    </button>
                </form>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full text-center text-sm font-semibold text-slate-500 underline">
                        Cerrar sesión
                    </button>
                </form>
            </section>
        </dialog>
    </main>
@endsection

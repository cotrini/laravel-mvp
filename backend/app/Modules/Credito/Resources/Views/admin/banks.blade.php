@extends('layouts.app')

@section('title', 'Intereses bancos - Colombia Sin Comisión')

@section('content')
    <main class="min-h-screen">
        <section class="mx-auto max-w-4xl space-y-6 py-10">
            <header>
                <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-blue-700">Administración</p>
                <h1 class="mt-1 text-3xl font-extrabold text-slate-900">Intereses bancos</h1>
                <p class="mt-2 text-sm text-slate-600">Configura manualmente la tasa efectiva anual que se usará en el simulador.</p>
            </header>

            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800" role="status">{{ session('success') }}</div>
            @endif

            <div class="grid gap-4">
                @foreach($banks as $bank)
                    <form action="{{ route('admin.bank-interest-rates.update', $bank) }}" method="POST" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-[1fr_auto] sm:items-end">
                        @csrf
                        @method('PUT')
                        <div>
                            <label for="rate-{{ $bank->id }}" class="block text-sm font-bold text-slate-700">{{ $bank->name }} — tasa E.A. (%)</label>
                            <input
                                id="rate-{{ $bank->id }}"
                                name="annual_effective_rate"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                value="{{ old('annual_effective_rate', $bank->annual_effective_rate) }}"
                                placeholder="Sin configurar"
                                class="mt-2 min-h-12 w-full rounded-xl border border-slate-300 px-4 text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-600/10"
                            >
                            @error('annual_effective_rate')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="min-h-12 rounded-xl bg-blue-700 px-5 font-bold text-white transition hover:bg-blue-800">Guardar tasa</button>
                    </form>
                @endforeach
            </div>

            <a href="{{ route('dashboard.index') }}" class="inline-flex font-semibold text-blue-700 underline">Volver al panel</a>
        </section>
    </main>
@endsection

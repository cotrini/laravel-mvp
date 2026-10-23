@extends('tours::layout')

@section('title', 'Recorridos 360° — Explora el mundo')

@section('content')
<header class="relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-sky-900 via-slate-950 to-slate-900"></div>
    <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 20%, rgba(56,189,248,0.35), transparent 40%), radial-gradient(circle at 80% 0%, rgba(14,165,233,0.25), transparent 45%);"></div>
    <div class="relative max-w-6xl mx-auto px-6 pt-20 pb-24 sm:pt-28 sm:pb-32">
        <div class="text-sky-300/90 text-xs uppercase tracking-[0.25em] mb-5">Recorridos inmersivos</div>
        <h1 class="text-4xl sm:text-6xl font-semibold leading-[1.05] tracking-tight max-w-3xl">
            Explora el mundo en
            <span class="block bg-gradient-to-r from-sky-300 to-emerald-300 bg-clip-text text-transparent">360 grados</span>
        </h1>
        <p class="mt-6 text-lg text-white/70 max-w-xl leading-relaxed">
            Sumérgete en paisajes, ciudades y rincones naturales. Arrastra para mirar alrededor,
            acércate y salta entre escenas como si estuvieras allí.
        </p>
    </div>
</header>

<main class="relative max-w-6xl mx-auto px-6 -mt-12 pb-24">
    <h2 class="text-sm uppercase tracking-[0.2em] text-white/50 mb-6">Recorridos disponibles</h2>

    @if ($tours->isEmpty())
        <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-10 text-center text-white/60">
            Aún no hay recorridos publicados. Ejecuta el seeder o crea uno desde el panel.
        </div>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($tours as $tour)
                <a href="{{ route('tours.show', $tour->slug) }}"
                   class="group rounded-2xl overflow-hidden border border-white/10 bg-slate-900/60 hover:border-sky-400/40 transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-sky-900/30">
                    <div class="relative aspect-[4/3] overflow-hidden">
                        @if ($tour->cover_url)
                            <img src="{{ $tour->cover_url }}" alt="{{ $tour->title }}"
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                        @else
                            <div class="w-full h-full bg-slate-800 flex items-center justify-center text-white/30">Sin imagen</div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/10 to-transparent"></div>
                        @if ($tour->location)
                            <span class="absolute bottom-3 left-3 rounded-full bg-black/50 backdrop-blur px-3 py-1 text-[11px] text-white/90 border border-white/10">
                                {{ $tour->location }}
                            </span>
                        @endif
                        <span class="absolute bottom-3 right-3 rounded-full bg-black/50 backdrop-blur px-3 py-1 text-[11px] text-white/90 border border-white/10">
                            {{ $tour->scenes->count() }} escenas
                        </span>
                    </div>
                    <div class="p-5">
                        <h3 class="text-lg font-semibold tracking-tight">{{ $tour->title }}</h3>
                        <p class="mt-2 text-sm text-white/60 leading-relaxed line-clamp-2">{{ $tour->description }}</p>
                        <div class="mt-4 flex items-center gap-2 text-sm text-sky-300 group-hover:text-sky-200 transition-colors">
                            Iniciar recorrido →
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</main>

<footer class="border-t border-white/10 py-8 text-center text-xs text-white/40">
    Recorridos 360° · módulo Laravel
</footer>
@endsection

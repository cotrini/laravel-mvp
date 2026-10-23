@extends('tours360::Tours.layout')

@section('title', $tour->title . ' — Recorridos 360°')

@section('content')
<header class="relative overflow-hidden">
    <div class="absolute inset-0">
        @if ($tour->cover_url)
            <img src="{{ $tour->cover_url }}" alt="{{ $tour->title }}" class="w-full h-full object-cover">
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/60 to-slate-950/30"></div>
    </div>
    <div class="relative max-w-6xl mx-auto px-6 pt-16 pb-20">
        <a href="{{ route('tours360.index') }}" class="text-sm text-sky-300/90 hover:text-sky-200 mb-6 inline-block">← Volver a la galería</a>
        <h1 class="text-3xl sm:text-5xl font-semibold tracking-tight">{{ $tour->title }}</h1>
        @if ($tour->location)
            <p class="mt-3 text-white/60">{{ $tour->location }}</p>
        @endif
        <p class="mt-4 text-white/70 max-w-2xl leading-relaxed">{{ $tour->description }}</p>
    </div>
</header>

<main class="max-w-6xl mx-auto px-6 py-12">
    <h2 class="text-sm uppercase tracking-[0.2em] text-white/50 mb-6">Escenas del recorrido</h2>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($tour->scenes as $i => $scene)
            <a href="{{ route('tours360.viewer', [$tour->slug, $scene->id]) }}"
               class="group relative aspect-video rounded-2xl overflow-hidden border border-white/10 hover:border-sky-400/50 transition-all duration-300 hover:-translate-y-1">
                <img src="{{ $scene->image_url }}" alt="{{ $scene->title }}"
                     class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent"></div>
                <div class="absolute bottom-4 left-4 right-4">
                    <span class="text-[11px] uppercase tracking-widest text-sky-300/90">Escena {{ $i + 1 }}</span>
                    <h3 class="text-lg font-semibold mt-0.5">{{ $scene->title }}</h3>
                    @if ($scene->caption)
                        <p class="text-sm text-white/60 mt-1 line-clamp-1">{{ $scene->caption }}</p>
                    @endif
                </div>
            </a>
        @endforeach
    </div>
</main>
@endsection

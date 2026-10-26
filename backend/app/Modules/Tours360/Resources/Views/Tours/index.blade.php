@extends('tours360::Tours.layout')

@section('title', 'Recorridos 360° — Explora el mundo')

@section('content')
<header class="relative overflow-hidden bg-[#d9efeb]">
    <div class="absolute inset-0 bg-gradient-to-br from-[#d9efeb] via-[#f3f7ee] to-[#ffe77d]"></div>
    <div class="relative max-w-6xl mx-auto px-6 pt-16 pb-20 sm:pt-24 sm:pb-28">
        <div class="text-[#12877b] text-xs uppercase tracking-[0.25em] mb-5 font-bold">Recorridos inmersivos</div>
        <h1 class="text-4xl sm:text-6xl font-semibold leading-[1.05] tracking-tight max-w-3xl">
            Explora el mundo en
            <span class="block text-[#12877b]">360 grados</span>
        </h1>
        <p class="mt-6 text-lg text-[#59616d] max-w-xl leading-relaxed">
            Sumérgete en paisajes, ciudades y rincones naturales. Arrastra para mirar alrededor,
            acércate y salta entre escenas como si estuvieras allí.
        </p>
    </div>
</header>

<main class="relative max-w-6xl mx-auto px-6 py-10 sm:py-14">
    <h2 class="text-sm uppercase tracking-[0.2em] text-[#69717d] mb-6">Recorridos disponibles</h2>

    @if ($tours->isEmpty())
        <div class="rounded-2xl border border-[#e7e9ed] bg-white p-10 text-center text-[#69717d] shadow-sm">
            Aún no hay recorridos publicados. Vuelve pronto para explorar nuevas propiedades.
        </div>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($tours as $tour)
                <a href="{{ route('tours360.show', $tour->slug) }}"
                   class="group rounded-2xl overflow-hidden border border-[#e7e9ed] bg-white text-[#242424] shadow-sm hover:border-[#12877b] transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                    <div class="relative aspect-[4/3] overflow-hidden">
                        @if ($tour->cover_url)
                            <img src="{{ $tour->cover_url }}" alt="{{ $tour->title }}"
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                        @else
                            <div class="w-full h-full bg-[#f0f5f4] flex items-center justify-center text-[#69717d]">Sin imagen</div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/10 to-transparent"></div>
                        @if ($tour->location)
                            <span class="absolute bottom-3 left-3 rounded-full bg-black/55 backdrop-blur px-3 py-1 text-[11px] text-white border border-white/20">
                                {{ $tour->location }}
                            </span>
                        @endif
                        <span class="absolute bottom-3 right-3 rounded-full bg-black/55 backdrop-blur px-3 py-1 text-[11px] text-white border border-white/20">
                            {{ $tour->scenes->count() }} escenas
                        </span>
                    </div>
                    <div class="p-5">
                        <h3 class="text-lg font-semibold tracking-tight">{{ $tour->title }}</h3>
                        <p class="mt-2 text-sm text-[#69717d] leading-relaxed line-clamp-2">{{ $tour->description }}</p>
                        <div class="mt-4 flex items-center gap-2 text-sm text-[#12877b] group-hover:text-[#0d756b] transition-colors font-bold">
                            Iniciar recorrido →
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</main>

<footer class="border-t border-[#e7e9ed] bg-white py-8 text-center text-xs text-[#69717d]">
    Recorridos 360° · Colombia Sin Comisión
</footer>
@endsection

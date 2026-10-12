@extends('layouts.app')

@section('content')
    <!-- Hero / Buscador principal -->
    <div class="bg-blue-900 text-white py-20 text-center">
        <h1 class="text-4xl font-bold mb-4">Encuentra tu próximo inmueble en Colombia</h1>
        <p class="text-lg">Sin comisiones infladas ni intermediarios innecesarios</p>
    </div>

    <!-- Sección de Propiedades Destacadas -->
    <div class="container mx-auto px-4 py-12">
        <h2 class="text-2xl font-bold mb-6">Últimas publicaciones</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($latestProperties as $property)
                <div class="bg-white rounded shadow overflow-hidden flex flex-col">
                    
                    @php
                        // Busca la foto marcada como principal (is_primary), si no existe toma la primera disponible
                        $primaryMedia = $property->media->where('is_primary', true)->first() ?? $property->media->first();
                    @endphp

                    @if($primaryMedia)
                        <img src="{{ asset($primaryMedia->url_or_path) }}" alt="{{ $property->title }}" class="w-full h-48 object-cover">
                    @else
                        <!-- Imagen por defecto si la propiedad no tiene fotos -->
                        <div class="w-full h-48 bg-gray-200 flex items-center justify-center text-gray-400">
                            <span>Sin imagen disponible</span>
                        </div>
                    @endif

                    <div class="p-4 flex flex-col flex-grow">
                        <h3 class="font-semibold text-lg mb-2">{{ $property->title }}</h3>
                        <p class="text-gray-600 font-bold mb-4">${{ number_format($property->price, 0) }}</p>
                        <a href="{{ route('properties.show', $property->id) }}" class="mt-auto inline-block text-blue-600 font-medium hover:underline">Ver detalles →</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
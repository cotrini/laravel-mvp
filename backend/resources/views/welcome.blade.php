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
                <div class="bg-white rounded shadow p-4">
                    <h3 class="font-semibold text-lg">{{ $property->title }}</h3>
                    <p class="text-gray-600">${{ number_format($property->price, 0) }}</p>
                    <a href="{{ route('properties.show', $property->id) }}" class="mt-4 inline-block text-blue-600 font-medium">Ver detalles →</a>
                </div>
            @endforeach
        </div>
    </div>
@endsection
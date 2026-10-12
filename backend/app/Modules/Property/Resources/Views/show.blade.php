@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-12">
        <h1 class="text-3xl font-bold mb-6">{{ $property->title }}</h1>
        <p class="text-gray-600 font-bold mb-4">${{ number_format($property->price, 0) }}</p>

        <!-- Galería de imágenes -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            @foreach($property->media as $media)
                <img src="{{ asset($media->url_or_path) }}" alt="{{ $property->title }}" class="w-full h-auto object-cover rounded shadow">
            @endforeach
        </div>

        <!-- Descripción de la propiedad -->
        <div class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Descripción</h2>
            <p>{{ $property->description }}</p>
        </div>

        <!-- Detalles adicionales -->
        <div>
            <h2 class="text-xl font-semibold mb-2">Detalles</h2>
            <ul class="list-disc list-inside">
                <li>Ubicación: {{ $property->location }}</li>
                <li>Tipo: {{ $property->type }}</li>
                <li>Estado: {{ ucfirst($property->status) }}</li>
                <!-- Agrega más detalles según sea necesario -->
            </ul>
        </div>
    </div>

@endsection
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-12 max-w-4xl">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Editar Propiedad: {{ $property->title }}</h1>
        <a href="{{ route('dashboard.index') }}" class="text-sm text-gray-600 hover:underline">← Volver al Panel</a>
    </div>

    <!-- 1. SECCIÓN DE ARCHIVOS MULTIMEDIA EXISTENTES (FUERA DEL FORMULARIO PRINCIPAL) -->
    <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm mb-8">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Archivos Multimedia Actuales en la propoiedad</h3>
        
        @if($property->media && $property->media->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                @foreach($property->media as $mediaItem)
                    <div class="relative group bg-gray-50 border border-gray-200 rounded-lg p-2 shadow-sm flex flex-col items-center">
                        
                        <!-- Previsualización -->
                        @if($mediaItem->type === 'image' || $mediaItem->type === 'blueprint')
                            <img src="{{ asset($mediaItem->url_or_path) }}" class="w-full h-28 object-cover rounded-md mb-2">
                        @elseif($mediaItem->type === 'video')
                            <div class="w-full h-28 bg-black rounded-md mb-2 flex items-center justify-center text-white text-xs font-semibold">
                                🎥 Video Cargado
                            </div>
                        @else
                            <div class="w-full h-28 bg-blue-50 rounded-md mb-2 flex items-center justify-center text-blue-700 text-xs font-semibold uppercase">
                                {{ $mediaItem->type }}
                            </div>
                        @endif

                        <span class="text-xs text-gray-500 truncate w-full text-center capitalize mb-2">{{ str_replace('_', ' ', $mediaItem->type) }}</span>

                        <!-- Formulario de eliminación individual independiente -->
                        <form action="{{ route('media.destroy', $mediaItem->id) }}" method="POST" class="w-full" onsubmit="return confirm('¿Deseas eliminar este archivo multimedia?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 text-xs py-1 rounded transition font-medium">
                                Eliminar
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-sm bg-gray-50 p-4 rounded-xl border border-gray-200">Esta propiedad aún no tiene archivos multimedia asociados.</p>
        @endif
    </div>

    <!-- 2. FORMULARIO PRINCIPAL DE ACTUALIZACIÓN (properties.update) -->
    <form action="{{ route('properties.update', $property->id) }}" method="POST" enctype="multipart/form-data" class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm space-y-6">
        @csrf
        @method('PUT')

        <!-- Título -->
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700">Título de la Propiedad</label>
            <input type="text" name="title" id="title" value="{{ old('title', $property->title) }}" required 
                   class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5 border">
        </div>

        <!-- Descripción -->
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700">Descripción General</label>
            <textarea name="description" id="description" rows="4" required 
                      class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5 border">{{ old('description', $property->description) }}</textarea>
        </div>

        <!-- Fila de Precio y Tipo -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="price" class="block text-sm font-medium text-gray-700">Precio (COP)</label>
                <input type="number" name="price" id="price" value="{{ old('price', $property->price) }}" required 
                       class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5 border">
            </div>

            <div>
                <label for="property_type" class="block text-sm font-medium text-gray-700">Tipo de Inmueble</label>
                <select name="property_type" id="property_type" required 
                        class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5 border">
                    <option value="casa" {{ old('property_type', $property->property_type) == 'casa' ? 'selected' : '' }}>Casa</option>
                    <option value="apartamento" {{ old('property_type', $property->property_type) == 'apartamento' ? 'selected' : '' }}>Apartamento</option>
                    <option value="lote" {{ old('property_type', $property->property_type) == 'lote' ? 'selected' : '' }}>Lote</option>
                    <option value="finca" {{ old('property_type', $property->property_type) == 'finca' ? 'selected' : '' }}>Finca</option>
                    <option value="local" {{ old('property_type', $property->property_type) == 'local' ? 'selected' : '' }}>Local / Oficina</option>
                </select>
            </div>
        </div>

        <!-- Fila de Ubicación Geográfica -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="department" class="block text-sm font-medium text-gray-700">Departamento</label>
                <input type="text" name="department" id="department" value="{{ old('department', $property->department) }}" 
                       class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5 border">
            </div>

            <div>
                <label for="city" class="block text-sm font-medium text-gray-700">Ciudad / Municipio</label>
                <input type="text" name="city" id="city" value="{{ old('city', $property->city) }}" 
                       class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5 border">
            </div>

            <div>
                <label for="neighborhood" class="block text-sm font-medium text-gray-700">Barrio / Sector</label>
                <input type="text" name="neighborhood" id="neighborhood" value="{{ old('neighborhood', $property->neighborhood) }}" 
                       class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5 border">
            </div>
        </div>

        <!-- Estado -->
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700">Estado de Publicación</label>
            <select name="status" id="status" required 
                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5 border">
                <option value="publicado" {{ old('status', $property->status) == 'publicado' ? 'selected' : '' }}>Publicado</option>
                <option value="pendiente" {{ old('status', $property->status) == 'pendiente' ? 'selected' : '' }}>En Revisión / Pendiente</option>
                <option value="pausado" {{ old('status', $property->status) == 'pausado' ? 'selected' : '' }}>Pausado</option>
            </select>
        </div>

        <hr class="border-gray-200 my-6">

        <!-- SUBIDA DE NUEVA MULTIMEDIA -->
        <h3 class="text-lg font-bold text-gray-800">Agregar Nueva Multimedia y Enlaces Interactivos</h3>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Agregar más fotos a la galería</label>
                <input type="file" name="images[]" multiple accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Archivo de Video (o enlace)</label>
                <input type="file" name="video" accept="video/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>

            <div>
                <label for="map_url" class="block text-sm font-medium text-gray-700">Enlace o Código Iframe de Google Maps</label>
                <input type="text" name="map_url" id="map_url" value="{{ old('map_url', $property->map_url ?? '') }}" placeholder="Ej: https://www.google.com/maps/embed?..." 
                       class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5 border">
            </div>

            <div>
                <label for="tour_360_url" class="block text-sm font-medium text-gray-700">Enlace de Recorrido Virtual 360°</label>
                <input type="text" name="tour_360_url" id="tour_360_url" value="{{ old('tour_360_url', $property->tour_360_url ?? '') }}" placeholder="URL del proveedor 360°" 
                       class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5 border">
            </div>

            <div>
                <label for="tour_3d_url" class="block text-sm font-medium text-gray-700">Enlace de Modelo Tridimensional 3D</label>
                <input type="text" name="tour_3d_url" id="tour_3d_url" value="{{ old('tour_3d_url', $property->tour_3d_url ?? '') }}" placeholder="URL del modelo 3D (Matterport, etc.)" 
                       class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5 border">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Imagen o PDF del Plano Arquitectónico</label>
                <input type="file" name="blueprint" accept="image/*,.pdf" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>
        </div>

        <!-- Botón de Envío Principal -->
        <div class="pt-4">
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition shadow cursor-pointer">
                Guardar Cambios de la Propiedad
            </button>
        </div>
    </form>
</div>
@endsection
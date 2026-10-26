@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-10 px-4">
    
    {{-- Formulario Principal del Tour --}}
    <form action="{{ route('tours360.update', $tour->slug) }}" method="POST" class="bg-white shadow-md rounded-lg p-6 space-y-6">
        @csrf
        @method('PUT')

        <h2 class="text-xl font-bold text-gray-800">Editar Tour Principal</h2>

        <div>
            <label class="block text-sm font-medium text-gray-700">Título</label>
            <input type="text" name="title" value="{{ old('title', $tour->title) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Descripción</label>
            <textarea name="description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2">{{ old('description', $tour->description) }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Ubicación</label>
                <input type="text" name="location" value="{{ old('location', $tour->location) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">URL de la portada</label>
                <input type="url" name="cover_url" value="{{ old('cover_url', $tour->cover_url) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2">
            </div>
        </div>

        <div class="flex items-center">
            <input type="checkbox" name="is_published" value="1" {{ old('is_published', $tour->is_published) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm">
            <span class="ml-2 text-sm text-gray-700">Publicado</span>
        </div>

        <hr class="my-6">

        {{-- SECCIÓN: Escenas / Imágenes 360 del Tour --}}
        <div>
            <h3 class="text-lg font-bold text-gray-800 mb-4">Escenas 360 del Tour</h3>
            
            <div class="space-y-4">
                @forelse($tour->scenes as $index => $scene)
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-sm text-indigo-600">Escena #{{ $index + 1 }} (ID: {{ $scene->id }})</span>
                            {{-- Enlace directo si tienes una ruta para editar la escena de forma independiente --}}
                            <a href="{{ route('tours360.editScene', [$tour->slug, $scene->id]) }}" class="text-xs bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded transition">
                                Configurar Hotspots / Detalle
                            </a>
                        </div>

                        {{-- Campos ocultos para identificar qué escena estamos actualizando si envías esto masivamente, 
                             o puedes usar rutas independientes por cada escena --}}
                        <input type="hidden" name="scenes[{{ $index }}][id]" value="{{ $scene->id }}">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Título de la escena</label>
                                <input type="text" name="scenes[{{ $index }}][title]" value="{{ old('scenes.'.$index.'.title', $scene->title) }}" class="mt-1 w-full rounded border-gray-300 text-sm border p-1.5">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Orden (sort_order)</label>
                                <input type="number" name="scenes[{{ $index }}][sort_order]" value="{{ old('scenes.'.$index.'.sort_order', $scene->sort_order) }}" class="mt-1 w-full rounded border-gray-300 text-sm border p-1.5">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">URL de la imagen 360</label>
                                <input type="url" name="scenes[{{ $index }}][image_url]" value="{{ old('scenes.'.$index.'.image_url', $scene->image_url) }}" class="mt-1 w-full rounded border-gray-300 text-sm border p-1.5">
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 italic">Este tour aún no tiene escenas 360 registradas.</p>
                @endforelse
            </div>
        </div>

        <div class="flex justify-end space-x-4 pt-4">
            <a href="{{ route('tours360.show', $tour->slug) }}" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-100 text-sm">Cancelar</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm">Guardar cambios</button>
        </div>
    </form>
</div>
@endsection
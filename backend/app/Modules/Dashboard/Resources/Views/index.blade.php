@extends('layouts.app')

@section('content')
@if($properties->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase">
                    <th class="py-3 px-4">Propiedad</th>
                    <th class="py-3 px-4">Precio</th>
                    <th class="py-3 px-4">Estado</th>
                    <th class="py-3 px-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @foreach($properties as $property)
                    <tr>
                        <!-- Título de la propiedad -->
                        <td class="py-3 px-4 font-semibold text-gray-800">{{ $property->title }}</td>
                        
                        <!-- Precio formateado -->
                        <td class="py-3 px-4 text-gray-600">${{ number_format($property->price, 0, ',', '.') }}</td>
                        
                        <!-- Estado -->
                        <td class="py-3 px-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                {{ ucfirst($property->status) }}
                            </span>
                        </td>
                        
                        <!-- Acciones (Ver, Editar, Eliminar) -->
                        <td class="py-3 px-4 text-right space-x-3">
                            <a href="{{ route('properties.show', $property->id) }}" class="text-blue-600 hover:underline font-medium">
                                Ver
                            </a>
                            <a href="{{ route('properties.edit', $property->id) }}" class="text-amber-600 hover:underline font-medium">
                                Editar
                            </a>
                            <form action="{{ route('properties.destroy', $property->id) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta propiedad?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline font-medium">
                                    Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <!-- Paginación -->
    <div class="mt-4">
        {{ $properties->links() }}
    </div>
@else
    <p class="text-gray-500 text-sm text-center py-8">Aún no has publicado ninguna propiedad.</p>
@endif
@endsection
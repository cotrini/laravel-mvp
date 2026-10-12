@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    
    <!-- GRID PRINCIPAL DE TRES COLUMNAS (Estilo Amazon + Finca Raíz) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- COLUMNA 1: MINIATURAS VERTICALES (Izquierda) -->
        <div class="lg:col-span-1 hidden lg:flex flex-col space-y-3">
            @php
                $imagenes = $property->media->where('type', 'image');
                $primerasTres = $imagenes->take(3);
                $restantesCount = $imagenes->count() - 3;
                $videoItem = $property->media->where('type', 'video')->first();
                $tour360Item = $property->media->where('type', 'tour_360')->first();
                $tour3dItem = $property->media->where('type', 'tour_3d')->first();
                $planoItem = $property->media->where('type', 'blueprint')->first();
            @endphp

            {{-- 3 Primeras Fotos --}}
            @foreach($primerasTres as $img)
                <div class="w-16 h-16 border-2 border-transparent hover:border-orange-500 rounded-lg overflow-hidden cursor-pointer shadow-sm transition"
                     onclick="cambiarVistaPrincipal('{{ asset($img->url_or_path) }}')">
                    <img src="{{ asset($img->url_or_path) }}" class="w-full h-full object-cover" alt="Miniatura">
                </div>
            @endforeach

            {{-- Recuadro de más fotos (si hay más de 3) --}}
            @if($restantesCount > 0)
                <div class="w-16 h-16 bg-gray-900 bg-opacity-80 rounded-lg flex flex-col items-center justify-center text-white text-xs font-bold cursor-pointer hover:bg-black transition"
                     onclick="abrirModalMedia('galeria')">
                    <span>+{{ $restantesCount }}</span>
                    <span class="text-[10px] font-normal">fotos</span>
                </div>
            @endif

            {{-- Botón / Recuadro de Video --}}
            @if($videoItem)
                <div class="w-16 h-16 relative border-2 border-gray-300 hover:border-orange-500 rounded-lg overflow-hidden cursor-pointer shadow-sm bg-black"
                     onclick="abrirModalMedia('video')">
                    <img src="{{ asset($videoItem->url_or_path ?? ($primerasTres->first()->url_or_path ?? '')) }}" class="w-full h-full object-cover opacity-60">
                    <div class="absolute inset-0 flex items-center justify-center text-white">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                </div>
            @endif

            {{-- Recuadro Recorrido 360 --}}
            @if($tour360Item)
                <div class="w-16 h-16 relative border-2 border-gray-300 hover:border-orange-500 rounded-lg overflow-hidden cursor-pointer shadow-sm bg-blue-900"
                     onclick="abrirModalMedia('tour_360')">
                    <img src="{{ asset($tour360Item->url_or_path ?? ($primerasTres->first()->url_or_path ?? '')) }}" class="w-full h-full object-cover opacity-60">
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-white text-[10px] font-bold bg-black bg-opacity-40">
                        <span>360°</span>
                    </div>
                </div>
            @endif

            {{-- Recuadro Recorrido 3D --}}
            @if($tour3dItem)
                <div class="w-16 h-16 relative border-2 border-gray-300 hover:border-orange-500 rounded-lg overflow-hidden cursor-pointer shadow-sm bg-purple-900"
                     onclick="abrirModalMedia('tour_3d')">
                    <img src="{{ asset($tour3dItem->url_or_path ?? ($primerasTres->first()->url_or_path ?? '')) }}" class="w-full h-full object-cover opacity-60">
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-white text-[10px] font-bold bg-black bg-opacity-40">
                        <span>3D</span>
                    </div>
                </div>
            @endif

            {{-- Recuadro Planos --}}
            @if($planoItem)
                <div class="w-16 h-16 relative border-2 border-gray-300 hover:border-orange-500 rounded-lg overflow-hidden cursor-pointer shadow-sm bg-gray-800"
                     onclick="abrirModalMedia('planos')">
                    <img src="{{ asset($planoItem->url_or_path ?? ($primerasTres->first()->url_or_path ?? '')) }}" class="w-full h-full object-cover opacity-60">
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-white text-[10px] font-bold bg-black bg-opacity-40">
                        <span>Plano</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- COLUMNA 2: VISOR PRINCIPAL Y PREVISUALIZACIÓN TIPO AMAZON (Centro) -->
        <div class="lg:col-span-6 flex flex-col items-center">
            <div class="w-full bg-white border border-gray-200 rounded-xl p-4 shadow-sm relative flex justify-center items-center h-[450px] overflow-hidden">
                <img id="imagenPrincipal" 
                     src="{{ asset($imagenes->first()->url_or_path ?? 'https://via.placeholder.com/600x400') }}" 
                     alt="{{ $property->title }}" 
                     class="max-h-full max-w-full object-contain cursor-zoom-in transition duration-300"
                     onclick="abrirModalMedia('galeria')">
                <span class="absolute bottom-3 left-3 text-xs text-gray-500 bg-gray-100 px-3 py-1 rounded-full shadow">Haz clic para una vista completa</span>
            </div>
        </div>

        <!-- COLUMNA 3: SECCIÓN DE CONTACTO ESTILO FINCA RAÍZ (Derecha) -->
        <div class="lg:col-span-3 bg-white border border-gray-200 rounded-xl p-6 shadow-md sticky top-6">
            <div class="flex items-center space-x-3 mb-4 border-b pb-3">
                <div class="w-10 h-10 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center font-bold">
                    {{ strtoupper(substr($property->user->name ?? 'Inmobiliaria', 0, 2)) }}
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 text-sm">{{ $property->user->name ?? 'Propietario / Agente' }}</h3>
                    <span class="text-xs text-gray-500">Inmobiliaria Verificada</span>
                </div>
            </div>

            <p class="text-xs text-gray-600 mb-4">Completa tus datos para habilitar el medio de contacto</p>

            <form action="#" method="POST" class="space-y-3">
                @csrf
                <div>
                    <input type="text" placeholder="Nombre y Apellido*" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div class="flex space-x-2">
                    <select class="border border-gray-300 rounded-lg px-2 py-2 text-sm bg-gray-50">
                        <option>+57</option>
                    </select>
                    <input type="tel" placeholder="Teléfono*" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <input type="email" placeholder="Email*" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <textarea rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">Hola, vi esta propiedad y me interesa recibir más información. ¡Gracias!</textarea>
                </div>
                <div class="flex items-center space-x-2 text-xs text-gray-500">
                    <input type="checkbox" required class="rounded border-gray-300">
                    <span>Aceptas nuestros <a href="#" class="text-blue-600 underline">Términos y condiciones</a></span>
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg text-sm transition shadow">
                    ✉ Contactar
                </button>
            </form>

            <div class="mt-4 pt-4 border-t flex flex-col space-y-2">
                <a href="tel:3000000000" class="flex items-center justify-center space-x-2 text-blue-600 border border-blue-600 py-2 rounded-lg text-sm font-semibold hover:bg-blue-50 transition">
                    <span>📞 Llamar</span>
                </a>
                <a href="https://wa.me/" target="_blank" class="flex items-center justify-center space-x-2 text-green-600 border border-green-600 py-2 rounded-lg text-sm font-semibold hover:bg-green-50 transition">
                    <span>💬 Whatsapp</span>
                </a>
            </div>
        </div>

    </div>

    <!-- INFORMACIÓN DETALLADA DE LA PROPIEDAD -->
    <div class="mt-10 bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ $property->title }}</h1>
        <p class="text-xl font-bold text-blue-600 mb-4">${{ number_format($property->price, 0, ',', '.') }}</p>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 py-4 border-t border-b border-gray-100 my-4 text-sm text-gray-700">
            <div>📍 <strong>Ubicación:</strong> {{ $property->neighborhood ?? 'N/A' }}, {{ $property->city ?? '' }}</div>
            <div>🏠 <strong>Tipo:</strong> {{ ucfirst($property->property_type ?? 'Inmueble') }}</div>
            <div>📋 <strong>Estado:</strong> {{ ucfirst($property->status ?? 'Disponible') }}</div>
            <div>🏢 <strong>Depto:</strong> {{ $property->department ?? 'N/A' }}</div>
        </div>

        <h3 class="text-lg font-semibold text-gray-800 mb-2">Descripción</h3>
        <p class="text-gray-600 text-sm leading-relaxed mb-6">{{ $property->description }}</p>
    </div>

    <!-- SECCIÓN INFERIOR: OTRAS PROPIEDADES INTERESANTES -->
    <div class="mt-16">
        <h3 class="text-xl font-bold text-gray-900 mb-6">Otras propiedades interesantes</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @php
                $otrasPropiedades = \App\Modules\Property\Models\Property::where('id', '!=', $property->id)->take(3)->get();
            @endphp

            @forelse($otrasPropiedades as $otra)
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition">
                    <div class="h-48 bg-gray-100 overflow-hidden">
                        @php $imgOtra = $otra->media->where('type', 'image')->first(); @endphp
                        <img src="{{ asset($imgOtra->url_or_path ?? 'https://via.placeholder.com/400x300') }}" class="w-full h-full object-cover">
                    </div>
                    <div class="p-4">
                        <span class="text-xs text-blue-600 font-semibold uppercase">{{ $otra->property_type }}</span>
                        <h4 class="font-bold text-gray-800 text-base truncate mt-1">{{ $otra->title }}</h4>
                        <p class="text-gray-900 font-bold mt-2">${{ number_format($otra->price, 0, ',', '.') }}</p>
                        <a href="{{ route('properties.show', $otra->id) }}" class="mt-4 block text-center bg-gray-100 hover:bg-blue-600 hover:text-white text-gray-800 py-2 rounded-lg text-sm font-medium transition">
                            Ver propiedad
                        </a>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-sm">No hay otras propiedades disponibles por el momento.</p>
            @endforelse
        </div>
    </div>

</div>

<!-- MODAL TIPO SPAM / AMAZON PARA VISUALIZAR MEDIA COMPLETA -->
<div id="mediaModal" class="fixed inset-0 z-50 bg-black bg-opacity-90 hidden flex items-center justify-center p-4">
    <div class="relative w-full max-w-5xl bg-black rounded-xl overflow-hidden flex flex-col items-center max-h-[90vh]">
        <!-- Botón cerrar -->
        <button onclick="cerrarModalMedia()" class="absolute top-4 right-4 text-white text-3xl font-bold bg-gray-800 bg-opacity-60 w-10 h-10 rounded-full flex items-center justify-center hover:bg-red-600 transition z-10">
            &times;
        </button>
        
        <!-- Contenedores de contenido renderizados de forma segura con Blade -->
        <div id="content-galeria" class="modal-content-section hidden w-full p-6 overflow-auto max-h-[80vh]">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                @foreach($imagenes as $m)
                    <img src="{{ asset($m->url_or_path) }}" class="w-full h-auto rounded-lg shadow">
                @endforeach
            </div>
        </div>

        <div id="content-video" class="modal-content-section hidden w-full flex flex-col items-center p-6">
            <h2 class="text-white text-lg font-bold mb-4">Video del Inmueble</h2>
            @if($videoItem)
                <video controls class="w-full max-h-[70vh] rounded-lg">
                    <source src="{{ asset($videoItem->url_or_path) }}" type="video/mp4">
                    Tu navegador no soporta videos.
                </video>
            @endif
        </div>

        <div id="content-tour_360" class="modal-content-section hidden w-full text-center text-white p-6">
            <h2 class="text-xl font-bold mb-2">Recorrido Virtual 360°</h2>
            <div class="w-full h-[65vh] bg-gray-800 rounded-lg flex items-center justify-center border border-gray-700">
                <iframe src="{{ $tour360Item->url_or_path ?? '#' }}" class="w-full h-full rounded-lg"></iframe>
            </div>
        </div>

        <div id="content-tour_3d" class="modal-content-section hidden w-full text-center text-white p-6">
            <h2 class="text-xl font-bold mb-2">Modelo Tridimensional 3D</h2>
            <div class="w-full h-[65vh] bg-gray-800 rounded-lg flex items-center justify-center border border-gray-700">
                <iframe src="{{ $tour3dItem->url_or_path ?? '#' }}" class="w-full h-full rounded-lg"></iframe>
            </div>
        </div>

        <div id="content-planos" class="modal-content-section hidden w-full text-center text-white p-6">
            <h2 class="text-xl font-bold mb-2">Plano Arquitectónico</h2>
            @if($planoItem)
                <img src="{{ asset($planoItem->url_or_path) }}" class="max-h-[70vh] mx-auto rounded-lg shadow">
            @endif
        </div>
    </div>
</div>

<script>
    function cambiarVistaPrincipal(url) {
        document.getElementById('imagenPrincipal').src = url;
    }

    function abrirModalMedia(tipo) {
        const modal = document.getElementById('mediaModal');
        modal.classList.remove('hidden');

        // Ocultar todas las secciones del modal
        document.querySelectorAll('.modal-content-section').forEach(el => {
            el.classList.add('hidden');
        });

        // Mostrar solo la sección seleccionada
        const targetSection = document.getElementById('content-' + tipo);
        if (targetSection) {
            targetSection.classList.remove('hidden');
        }
    }

    function cerrarModalMedia() {
        document.getElementById('mediaModal').classList.add('hidden');
        document.querySelectorAll('.modal-content-section').forEach(el => {
            el.classList.add('hidden');
        });
    }
</script>
@endsection
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Colombia Sin Comisión - Inmuebles sin intermediarios</title>
    <!-- Tailwind CSS CDN para estilos rápidos y modernos -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans antialiased flex flex-col min-h-screen">

    <!-- Header Estilo Amazon -->
    <header class="bg-[#131921] text-white">
        <!-- Barra superior principal -->
            <div class="flex items-center space-x-6">
            
            <!-- 1. SALUDO Y ENLACE AL DASHBOARD (O LOGIN SI ES INVITADO) -->
            @auth
                <a href="{{ route('dashboard') }}" class="text-sm text-gray-200 hover:text-white transition">
                    Hola, <span class="font-bold text-white">{{ auth()->user()->name }}</span>
                </a>
            @endauth

            @guest
                <a href="{{ route('login') }}" class="text-sm text-gray-200 hover:text-white transition">
                    Hola, <span class="font-bold text-white">Inversionista</span>
                </a>
            @endguest

            <!-- 2. BOTÓN PUBLICAR INMUEBLE DINÁMICO -->
            @auth
                <a href="{{ route('properties.create') }}" class="bg-amber-500 hover:bg-amber-400 text-gray-900 font-bold px-4 py-2 rounded-lg transition shadow">
                    Publicar Inmueble
                </a>
            @endauth

            @guest
                <a href="{{ route('login') }}" class="bg-amber-500 hover:bg-amber-400 text-gray-900 font-bold px-4 py-2 rounded-lg transition shadow">
                    Publicar Inmueble
                </a>
            @endguest

        </div>
        <div class="max-w-7xl mx-auto px-4 py-2 flex items-center justify-between gap-4">
            <!-- Logo / Nombre -->
            <a href="{{ url('/') }}" class="text-xl font-bold tracking-tight text-white flex items-center gap-1">
                Colombia<span class="text-amber-400">SinComisión</span>
            </a>

            <!-- Barra de búsqueda central -->
            <div class="flex-1 max-w-2xl flex">
                <select class="bg-gray-200 text-gray-800 text-sm px-3 rounded-l-md border-r border-gray-300 focus:outline-none hidden sm:block">
                    <option>Todo</option>
                    <option>Apartamentos</option>
                    <option>Casas</option>
                    <option>Lotes</option>
                </select>
                <input type="text" placeholder="Busca por ciudad, barrio o tipo de inmueble..." class="w-full px-4 py-2 text-gray-900 focus:outline-none">
                <button class="bg-amber-400 hover:bg-amber-500 text-gray-900 px-5 rounded-r-md font-semibold transition flex items-center justify-center">
                    🔍
                </button>
            </div>

            <!-- Enlaces de usuario / Publicar -->
            <div class="flex items-center gap-4 text-sm">
                <a href="#" class="hover:text-amber-400 transition hidden md:block">Hola, Inversionista</a>
                <a href="#" class="bg-amber-500 hover:bg-amber-600 text-gray-900 px-4 py-2 rounded font-bold shadow transition">
                    Publicar Inmueble
                </a>
            </div>
        </div>

        <!-- Sub-barra de navegación de categorías secundarias -->
        <nav class="bg-[#232f3e] text-sm px-4 py-2 flex items-center gap-6 overflow-x-auto whitespace-nowrap">
            <a href="#" class="hover:text-amber-400 font-semibold flex items-center gap-1">☰ Todo</a>
            <a href="#" class="hover:text-amber-400">Apartamentos en Venta</a>
            <a href="#" class="hover:text-amber-400">Casas Campestres</a>
            <a href="#" class="hover:text-amber-400">Oficinas y Locales</a>
            <a href="#" class="hover:text-amber-400">Lotes</a>
            <a href="#" class="hover:text-amber-400">Manizales</a>
            <a href="#" class="hover:text-amber-400">Bogotá</a>
            <a href="#" class="hover:text-amber-400">Medellín</a>
        </nav>
    </header>

    <!-- Contenido Principal Dinámico -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 py-6">
        @yield('content')
    </main>

    <!-- Footer estilo E-commerce -->
    <footer class="bg-[#232f3e] text-white mt-12">
        <div class="bg-[#374151] py-4 text-center text-sm">
            <a href="#" class="hover:underline">Volver arriba ↑</a>
        </div>
        <div class="max-w-7xl mx-auto px-4 py-10 grid grid-cols-2 md:grid-cols-4 gap-8 text-sm text-gray-300">
            <div>
                <h4 class="font-bold text-white mb-3">Conócenos</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="hover:underline">Acerca de Colombia Sin Comisión</a></li>
                    <li><a href="#" class="hover:underline">Trabaja con nosotros</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-white mb-3">Gana Dinero con Nosotros</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="hover:underline">Publica tu inmueble gratis</a></li>
                    <li><a href="#" class="hover:underline">Alianzas inmobiliarias</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-white mb-3">Podemos Ayudarte</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="hover:underline">Preguntas frecuentes</a></li>
                    <li><a href="#" class="hover:underline">Centro de seguridad</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-white mb-3">Países Disponibles</h4>
                <ul class="space-y-2">
                    <li><span class="text-amber-400 font-semibold">Colombia</span> (Manizales, Bogotá, Medellín)</li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-700 py-6 text-center text-xs text-gray-400">
            &copy; {{ date('Y') }} Colombia Sin Comisión S.A.S. Todos los derechos reservados.
        </div>
    </footer>

</body>
</html>
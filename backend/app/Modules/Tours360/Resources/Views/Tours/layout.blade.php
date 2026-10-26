<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Recorridos 360°')</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.1/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .tour-site-header { min-height: 68px; display:flex; align-items:center; justify-content:space-between; gap:18px; padding:0 clamp(18px, 5vw, 64px); background:#fff; box-shadow:0 2px 8px rgb(25 25 25 / 8%); }
        .tour-site-brand { display:inline-flex; align-items:center; gap:10px; color:#242424; font-size:18px; font-weight:800; letter-spacing:-.04em; text-decoration:none; }
        .tour-site-brand-mark { width:34px; height:34px; display:grid; place-items:center; border-radius:11px; background:#ffe22d; color:#242424; }
        .tour-site-links { display:flex; align-items:center; gap:18px; color:#59616d; font-size:13px; font-weight:650; }
        .tour-site-links a:hover { color:#12877b; }
        .tour-site-content { min-height:calc(100vh - 68px); }
        @media (max-width: 560px) { .tour-site-header { min-height:60px; } .tour-site-brand { font-size:15px; } .tour-site-links { gap:11px; font-size:11px; } }
        @yield('styles')
    </style>
</head>
<body class="bg-[#f8f8f8] text-[#242424] antialiased">
    <header class="tour-site-header">
        <a href="{{ route('home') }}" class="tour-site-brand">
            <span class="tour-site-brand-mark" aria-hidden="true">⌂</span>
            Colombia Sin Comisión
        </a>
        <nav class="tour-site-links" aria-label="Navegación de recorridos">
            <a href="{{ route('properties.index') }}">Inmuebles</a>
            <a href="{{ route('tours360.index') }}">Recorridos 360°</a>
        </nav>
    </header>
    <div class="tour-site-content">
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>

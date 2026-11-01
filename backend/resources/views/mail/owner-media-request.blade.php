<!DOCTYPE html>
<html lang="es">
<body style="margin:0;padding:32px;background:#f6f8f8;color:#242424;font-family:Arial,sans-serif">
    <main style="max-width:640px;margin:0 auto;padding:28px;border:1px solid #e7e9ed;border-radius:18px;background:#fff">
        <p style="margin:0 0 8px;color:#12877b;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase">Colombia Sin Comisión</p>
        <h1 style="margin:0 0 16px;font-size:24px">
            {{ $mediaType === 'media_package' ? 'Hay personas interesadas en tu propiedad' : 'Solicitud de '.($mediaType === 'video' ? 'video' : 'recorrido 360°') }}
        </h1>
        <p style="font-size:15px;line-height:1.6">
            @if($mediaType === 'media_package')
                Recibimos interés en {{ $property->title }}. Agregar videos profesionales y un recorrido virtual 360° permite que las personas conozcan mejor el inmueble y puede incrementar las posibilidades de concretar la venta. Aún puedes agregar: {{ $requestedMedia }}.
            @else
                {{ $requesterName }} está interesado en {{ $property->title }} y solicita que agregues {{ $mediaType === 'video' ? 'un video' : 'un recorrido virtual 360°' }}.
            @endif
        </p>
        <p style="font-size:15px;line-height:1.6">
            Las propiedades con recorridos 360 y videos tienen un 70% más de probabilidades de ser vendidas en plazos menores.
        </p>
        <ul style="padding-left:20px;font-size:14px;line-height:1.8">
            <li>Valor: ${{ number_format((float) $property->price, 0, ',', '.') }} COP</li>
            <li>Ubicación: {{ $property->city }}{{ $property->department ? ', '.$property->department : '' }}</li>
            @unless($mediaType === 'media_package')
                <li>Correo del interesado: {{ $requesterEmail ?: 'No proporcionado' }}</li>
                <li>Teléfono del interesado: {{ $requesterPhone ?: 'No proporcionado' }}</li>
            @endunless
        </ul>
        <p style="margin:24px 0">
            <a href="{{ route('properties.show', $property) }}" style="display:inline-block;padding:12px 18px;border-radius:10px;background:#2563eb;color:#fff;font-weight:bold;text-decoration:none">
                Ver inmueble
            </a>
        </p>
    </main>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<body style="margin:0;padding:32px;background:#f6f8f8;color:#242424;font-family:Arial,sans-serif">
    <main style="max-width:640px;margin:0 auto;padding:28px;border:1px solid #e7e9ed;border-radius:18px;background:#fff">
        <p style="margin:0 0 8px;color:#12877b;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase">Colombia Sin Comisión</p>
        <h1 style="margin:0 0 16px;font-size:24px">Alguien vio tu propiedad</h1>
        <p style="font-size:15px;line-height:1.6">
            Hola {{ $property->user?->name ?? 'propietario' }}, una persona interesada visitó la publicación
            <strong>{{ $property->title }}</strong> y solicitó {{ $mediaLabel }}.
        </p>
        <p style="font-size:15px;line-height:1.6">
            Agregar fotos, video, un recorrido 360° y planos arquitectónicos puede ayudar a que más personas conozcan el inmueble y aumentar sus oportunidades de venta.
        </p>
        <p style="margin:24px 0">
            <a href="{{ $propertyUrl }}" style="display:inline-block;padding:12px 18px;border-radius:10px;background:#ffe22d;color:#242424;font-weight:bold;text-decoration:none">
                Ver mi publicación
            </a>
        </p>
        <p style="margin:0;color:#69717d;font-size:12px">Este mensaje se envió porque un visitante pidió más contenido para tu inmueble.</p>
    </main>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<body style="margin:0;padding:32px;background:#f6f8f8;color:#242424;font-family:Arial,sans-serif">
    @php
        $requestLabel = match ($propertyRequest->type) {
            'verification' => 'verificación de la propiedad',
            'video' => 'video',
            'tour_360' => 'recorrido virtual 360°',
            'video_and_tour_360' => 'video y recorrido virtual 360°',
            'blueprint' => 'planos arquitectónicos',
            default => 'contenido',
        };
    @endphp
    <main style="max-width:640px;margin:0 auto;padding:28px;border:1px solid #e7e9ed;border-radius:18px;background:#fff">
        <p style="margin:0 0 8px;color:#12877b;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase">Colombia Sin Comisión</p>
        <h1 style="margin:0 0 16px;font-size:24px">Nueva solicitud de {{ $requestLabel }}</h1>
        <p style="font-size:15px;line-height:1.6">
            {{ $propertyRequest->user?->name ?? 'Un visitante' }} solicitó {{ $requestLabel }} para
            <strong>{{ $propertyRequest->property->title }}</strong>.
        </p>
        <p style="margin:24px 0">
            <a href="{{ route('admin.property-requests.index') }}" style="display:inline-block;padding:12px 18px;border-radius:10px;background:#2563eb;color:#fff;font-weight:bold;text-decoration:none">
                Revisar solicitud en el panel
            </a>
        </p>
    </main>
</body>
</html>

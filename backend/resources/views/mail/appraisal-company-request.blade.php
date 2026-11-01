<!DOCTYPE html>
<html lang="es">
<body style="margin:0;padding:32px;background:#f6f8f8;color:#242424;font-family:Arial,sans-serif">
    <main style="max-width:640px;margin:0 auto;padding:28px;border:1px solid #e7e9ed;border-radius:18px;background:#fff">
        <p style="margin:0 0 8px;color:#12877b;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase">Colombia Sin Comisión</p>
        <h1 style="margin:0 0 16px;font-size:24px">Solicitud de avalúo comercial</h1>
        <p style="font-size:15px;line-height:1.6">
            El propietario solicitó contactar a la casa de avalúos para coordinar un avalúo comercial. Por favor, comuníquense directamente con el propietario.
        </p>
        <ul style="padding-left:20px;font-size:14px;line-height:1.8">
            <li>Inmueble: {{ $property->title }}</li>
            <li>Valor publicado: ${{ number_format((float) $property->price, 0, ',', '.') }} COP</li>
            <li>Ubicación: {{ $property->city }}{{ $property->department ? ', '.$property->department : '' }}</li>
            <li>Propietario: {{ $owner->name }}</li>
            <li>Correo: {{ $owner->email }}</li>
            <li>Teléfono: {{ $owner->phone_number ?: 'No proporcionado' }}</li>
        </ul>
        <p style="margin:24px 0">
            <a href="{{ route('properties.show', $property) }}" style="display:inline-block;padding:12px 18px;border-radius:10px;background:#2563eb;color:#fff;font-weight:bold;text-decoration:none">
                Ver inmueble
            </a>
        </p>
    </main>
</body>
</html>

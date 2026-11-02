<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>Restablece tu contraseña</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f7f8;color:#242424;font-family:Arial,Helvetica,sans-serif">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f5f7f8">
        <tr>
            <td align="center" style="padding:36px 16px">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px">
                    <tr>
                        <td align="center" style="padding:0 12px 24px">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="46" height="46" align="center" valign="middle" style="width:46px;height:46px;border-radius:14px;background-color:#ffe22d;color:#252525;font-family:Arial,Helvetica,sans-serif;font-size:31px;line-height:46px">
                                        ⌂
                                    </td>
                                    <td style="padding-left:12px;color:#202124;font-size:23px;font-weight:800;letter-spacing:-0.8px;white-space:nowrap">
                                        Colombia <span style="color:#159a8c">Sin Comisión</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 38px;border:1px solid #e5e9ec;border-radius:16px;background-color:#ffffff">
                            <p style="margin:0 0 10px;color:#12877b;font-size:12px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase">
                                Seguridad de la cuenta
                            </p>
                            <h1 style="margin:0 0 20px;color:#202124;font-size:26px;line-height:1.3">
                                Solicitud para restablecer tu contraseña
                            </h1>
                            <p style="margin:0 0 16px;color:#3f4752;font-size:16px;line-height:1.7">
                                Estimado/a {{ $userName }},
                            </p>
                            <p style="margin:0 0 26px;color:#3f4752;font-size:16px;line-height:1.7">
                                Recibimos una solicitud para cambiar la contraseña de tu cuenta. Usa el siguiente botón para elegir una nueva:
                            </p>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto 26px">
                                <tr>
                                    <td align="center" style="border-radius:10px;background-color:#12877b">
                                        <a href="{{ $resetUrl }}" style="display:inline-block;padding:15px 25px;border:1px solid #12877b;border-radius:10px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none">
                                            Restablecer contraseña
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 16px;color:#59616b;font-size:14px;line-height:1.7">
                                Por seguridad, este enlace estará disponible durante {{ $expirationMinutes }} minutos y solo puede utilizarse una vez.
                            </p>
                            <p style="margin:0 0 8px;color:#59616b;font-size:14px;line-height:1.7">
                                Si el botón no funciona, copia y pega este enlace en tu navegador:
                            </p>
                            <p style="margin:0 0 24px;overflow-wrap:anywhere;font-size:13px;line-height:1.7">
                                <a href="{{ $resetUrl }}" style="color:#08776c">{{ $resetUrl }}</a>
                            </p>
                            <p style="margin:0 0 20px;color:#59616b;font-size:14px;line-height:1.7">
                                Si no solicitaste este cambio, ignora este mensaje. Tu contraseña actual seguirá funcionando.
                            </p>
                            <p style="margin:0;color:#3f4752;font-size:14px;line-height:1.7">
                                Cordialmente,<br>
                                <strong>El equipo de Colombia Sin Comisión</strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:20px 16px 0;color:#7a828c;font-size:12px;line-height:1.6">
                            Este es un mensaje automático. Por favor, no respondas a este correo.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

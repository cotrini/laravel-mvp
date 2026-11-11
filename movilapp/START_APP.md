# Iniciar la app móvil

## 1. Backend Laravel

Laravel debe escuchar en todas las interfaces para que el celular lo alcance:

```bash
cd ..\backend
php artisan serve --host=0.0.0.0 --port=8001
```

## 2. Configuración de la app

`movilapp/.env.local` debe usar la IP de tu PC en la red local, no `localhost`
(en el celular, `localhost` apunta al propio teléfono):

```
EXPO_PUBLIC_API_URL=http://192.168.1.5:8001/api/v1
```

Si cambia la IP (`ipconfig`, buscar IPv4 de la red Wi-Fi), actualiza esta línea.

Comprueba que el backend responde en esa IP antes de abrir la app (debe devolver `200`):

```powershell
curl.exe -s -o NUL -w "%{http_code}" "http://192.168.1.5:8001/api/v1/properties?per_page=1"
```

Si devuelve `000`, el servidor sigue escuchando solo en `127.0.0.1`: vuelve a iniciarlo con el comando del paso 1.

## 3. Inicio de sesión

- **Correo y contraseña (cuentas de la base de datos):** la app usa `POST /api/v1/auth/login` y
  `POST /api/v1/auth/register` (Laravel Sanctum). El token se guarda en el dispositivo. El
  registro pide nombre, correo, celular colombiano y contraseña (mínimo 8), igual que la web.
  La cuenta debe verificar su correo (enlace que llega por SMTP) antes de iniciar sesión.
- **Google:** sigue usando Firebase Auth; el backend recibe el ID token en `/auth/firebase`.

Después de actualizar el backend, ejecuta la migración de tokens: `php artisan migrate`.

## 4. Expo

```bash
cd movilapp
npx expo start --clear
```

- **Expo Go (celular):** escanea el QR. El teléfono debe estar en la misma red Wi-Fi.
- **Web:** presiona `w`.

## Verificación

- `npx tsc --noEmit` sin errores.
- `npx expo export --platform android --output-dir .export-check` compila el bundle.
  Borra `.export-check` después.

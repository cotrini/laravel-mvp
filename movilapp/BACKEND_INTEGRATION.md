# 🔗 Backend Integration Guide - Colombia Sin Comisión Mobile

Este documento explica cómo vincular **exactamente** tu app React Native con el backend Laravel ubicado en `../backend/`.

---

## 📋 Tabla de Contenidos

1. [Verificar Backend](#1-verificar-backend)
2. [Configurar Variables de Entorno](#2-configurar-variables-de-entorno)
3. [Endpoints Esperados](#3-endpoints-esperados)
4. [Testing de Conexión](#4-testing-de-conexión)
5. [Troubleshooting](#5-troubleshooting)

---

## 1. Verificar Backend

### Paso 1: Inspecciona la estructura backend

```bash
cd ../backend

# Verifica que existan estas carpetas:
# - app/Http/Controllers/
# - routes/api.php
# - database/migrations/
# - config/

# Verifica que haya un archivo .env con DB_CONNECTION
cat .env | grep DB_CONNECTION
# Salida esperada: DB_CONNECTION=sqlite (o mysql, pgsql, etc)
```

### Paso 2: Levanta el backend

```bash
cd ../backend

# Instalar dependencias (si no estás en el mismo entorno)
composer install

# Ejecutar migraciones
php artisan migrate

# Iniciar servidor
php artisan serve --port=8000

# Verás: Laravel development server started: http://127.0.0.1:8000
```

### Paso 3: Verifica que está corriendo

```bash
# En otra terminal:
curl http://127.0.0.1:8000/api/properties

# Salida esperada:
# {"data":[...],"meta":{"current_page":1,...}}
# O: {"message":"Unauthenticated"}  (esperado sin token)
```

---

## 2. Configurar Variables de Entorno

### En `movilapp/.env.local`:

```bash
# API Configuration
EXPO_PUBLIC_API_URL=http://127.0.0.1:8000

# Firebase (opcional, para autenticación real)
EXPO_PUBLIC_FIREBASE_PROJECT_ID=your-project-id
EXPO_PUBLIC_FIREBASE_API_KEY=your-api-key
EXPO_PUBLIC_FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com

# Backend Storage (Cloudflare R2 o similar)
EXPO_PUBLIC_R2_PUBLIC_URL=https://your-bucket.r2.cloudflairstatic.com
```

### En `../backend/.env`:

```bash
APP_URL=http://127.0.0.1:8000

# CORS - Permitir requests desde Expo Go/Emulator
SANCTUM_STATEFUL_DOMAINS=localhost:8000,127.0.0.1:8000,192.168.x.x:8000
SESSION_DOMAIN=.127.0.0.1

# Base de datos
DB_CONNECTION=sqlite
DB_DATABASE=database.sqlite

# Storage (si usas S3/R2)
FILESYSTEM_DISK=r2
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_URL=https://your-bucket.r2.cloudflairstatic.com
```

### En `../backend/config/cors.php`:

```php
'allowed_origins' => [
    'http://localhost:8000',
    'http://127.0.0.1:8000',
    'http://127.0.0.1:*',  // Para emuladores
    'http://192.168.*.* ',  // Para dispositivos reales
],

'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'exposed_headers' => ['Authorization'],
'max_age' => 0,
'supports_credentials' => true,
```

---

## 3. Endpoints Esperados

### 3.1 Autenticación

#### POST `/api/auth/login`

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "phone": "+573001234567",
    "verified_at": "2024-01-15T10:30:00Z"
  }
}
```

**Implementación (Backend Laravel):**
```php
// routes/api.php
Route::post('/auth/login', [AuthController::class, 'login']);

// app/Http/Controllers/AuthController.php
public function login(Request $request)
{
    $validated = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (!Auth::attempt($validated)) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $user = Auth::user();
    $token = $user->createToken('mobile-app')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user,
    ]);
}
```

---

#### POST `/api/auth/verify-phone`

**Request:**
```json
{
  "phone": "+573001234567",
  "otp": "123456"
}
```

**Response (200):**
```json
{
  "message": "Phone verified",
  "verified_at": "2024-01-15T10:35:00Z"
}
```

---

#### POST `/api/auth/logout`

**Request:**
```json
{}
```

**Response (200):**
```json
{
  "message": "Logged out successfully"
}
```

---

### 3.2 Propiedades

#### GET `/api/properties`

**Query Params:**
```
?page=1&per_page=10&search=apartamento&city=Bogotá
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Hermoso apartamento en Chapinero",
      "description": "3 habitaciones, 2 baños...",
      "price": 450000000,
      "location": {
        "address": "Cra 7 #72-10, Bogotá",
        "city": "Bogotá",
        "latitude": 4.6866,
        "longitude": -74.0447
      },
      "images": [
        {
          "id": 1,
          "url": "https://r2-bucket.cloudflairstatic.com/property-1-main.jpg",
          "type": "main"
        }
      ],
      "tour360": {
        "id": 1,
        "public_cover_url": "https://r2-bucket.cloudflairstatic.com/tour-1.jpg"
      },
      "created_at": "2024-01-10T15:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total_pages": 5,
    "total_count": 47
  }
}
```

**Implementación (Backend):**
```php
// routes/api.php
Route::get('/properties', [PropertyController::class, 'index']);

// app/Http/Controllers/PropertyController.php
public function index(Request $request)
{
    $query = Property::query();

    if ($request->has('search')) {
        $query->where('title', 'like', "%{$request->search}%");
    }

    if ($request->has('city')) {
        $query->whereHas('location', fn($q) => $q->where('city', $request->city));
    }

    return response()->json([
        'data' => $query->paginate($request->per_page ?? 10),
        'meta' => [
            'current_page' => $query->paginate(10)->currentPage(),
            // ...
        ],
    ]);
}
```

---

#### GET `/api/properties/{id}`

**Response (200):**
```json
{
  "id": 1,
  "title": "Hermoso apartamento en Chapinero",
  "price": 450000000,
  "bedrooms": 3,
  "bathrooms": 2,
  "area": 120,
  "images": [
    {
      "id": 1,
      "url": "https://r2-bucket.cloudflairstatic.com/prop-1.jpg",
      "position": 1
    }
  ],
  "tour360": {
    "id": 1,
    "scenes": [
      {
        "id": 1,
        "name": "Sala Principal",
        "image_url": "https://r2-bucket.cloudflairstatic.com/scene-1.jpg"
      }
    ]
  },
  "commercial_video": {
    "id": 1,
    "url": "https://r2-bucket.cloudflairstatic.com/video-1.mp4",
    "thumbnail": "https://r2-bucket.cloudflairstatic.com/video-1-thumb.jpg"
  }
}
```

---

#### POST `/api/properties`

**Request (FormData):**
```
Title: "Nuevo apartamento"
Price: "450000000"
City: "Bogotá"
Images: [file1.jpg, file2.jpg]  (multipart)
```

**Response (201):**
```json
{
  "id": 2,
  "title": "Nuevo apartamento",
  "price": 450000000,
  "status": "draft"
}
```

**Implementación (Backend):**
```php
public function store(Request $request)
{
    $property = Property::create($request->validate([
        'title' => 'required|string',
        'price' => 'required|numeric',
        'city' => 'required|string',
    ]));

    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            $path = $image->storePublicly('properties', 's3');
            $property->images()->create([
                'url' => Storage::disk('s3')->url($path),
            ]);
        }
    }

    return response()->json($property, 201);
}
```

---

#### PUT `/api/properties/{id}`

**Request (FormData):**
```
Title: "Apartamento actualizado"
Price: "480000000"
```

**Response (200):**
```json
{
  "id": 1,
  "title": "Apartamento actualizado",
  "price": 480000000
}
```

---

#### DELETE `/api/properties/{id}`

**Response (204):**
```
(sin contenido)
```

---

### 3.3 Tours 360

#### GET `/api/properties/{id}/tour360`

**Response (200):**
```json
{
  "id": 1,
  "property_id": 1,
  "public_cover_url": "https://r2-bucket.cloudflairstatic.com/tour-1-cover.jpg",
  "scenes": [
    {
      "id": 1,
      "name": "Sala Principal",
      "image_url": "https://r2-bucket.cloudflairstatic.com/scene-1.jpg",
      "hotspots": [
        {
          "id": 1,
          "x": 0.5,
          "y": 0.5,
          "label": "Comedor",
          "target_scene_id": 2
        }
      ]
    }
  ]
}
```

---

#### POST `/api/properties/{id}/tour360`

**Request (FormData):**
```
image: equirectangular_image.jpg  (multipart)
name: "Sala Principal"
```

**Response (201):**
```json
{
  "id": 1,
  "property_id": 1,
  "public_image_url": "https://r2-bucket.cloudflairstatic.com/tour-1.jpg"
}
```

---

#### PUT `/api/tour360/{id}/scenes`

**Request:**
```json
{
  "scenes": [
    {
      "id": 1,
      "name": "Sala Principal",
      "position": 1,
      "hotspots": [
        {
          "x": 0.5,
          "y": 0.5,
          "label": "Comedor",
          "target_scene_id": 2
        }
      ]
    }
  ]
}
```

---

### 3.4 Videos Comerciales

#### POST `/api/properties/{id}/commercial-video`

**Request (FormData):**
```
video: video.mp4  (multipart, máx 100MB)
format: "horizontal"  (o "vertical")
thumbnail: thumbnail.jpg
```

**Response (201):**
```json
{
  "id": 1,
  "property_id": 1,
  "public_url": "https://r2-bucket.cloudflairstatic.com/video-1.mp4",
  "format": "horizontal",
  "duration": 40
}
```

---

## 4. Testing de Conexión

### 4.1 Test Manual con curl

```bash
# Test 1: Login
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password"
  }'

# Salida esperada:
# {"token":"eyJ0eXAi...","user":{...}}


# Test 2: Guardar token
TOKEN="eyJ0eXAi..."

# Test 3: Listar propiedades con token
curl -X GET http://127.0.0.1:8000/api/properties \
  -H "Authorization: Bearer $TOKEN"

# Salida esperada:
# {"data":[...],"meta":{...}}
```

### 4.2 Test desde la App

#### En `movilapp/src/services/api.config.ts`:

```typescript
// Verificar que la URL está configurada
console.log('API URL:', process.env.EXPO_PUBLIC_API_URL);

// Debería imprimir: API URL: http://127.0.0.1:8000
```

#### En `movilapp/app/(auth)/login.tsx`:

```typescript
// Agregar logs para debugging:
const handleLogin = async () => {
  try {
    console.log('🔐 Attempting login...');
    const response = await authService.login(email, password);
    console.log('✅ Login successful:', response);
    // ...
  } catch (error) {
    console.error('❌ Login failed:', error);
    // ...
  }
};
```

#### Ejecuta la app y verifica:

```bash
npm start

# En la consola de Expo, verás los logs:
# 🔐 Attempting login...
# ✅ Login successful: { token: "...", user: {...} }
```

---

## 5. Troubleshooting

### Error: "ECONNREFUSED - 127.0.0.1:8000"

**Causa:** Backend no está corriendo.

**Solución:**
```bash
cd ../backend
php artisan serve --port=8000

# Verifica que está corriendo:
curl http://127.0.0.1:8000/api/health
# Debería retornar algo (o un error, pero conexión establecida)
```

---

### Error: "CORS policy: blocked"

**Causa:** Backend no permite requests desde la app.

**Solución:**

1. Edita `../backend/config/cors.php`:
```php
'allowed_origins' => ['*'],  // Desarrollo
```

2. O en `../backend/.env`:
```bash
SANCTUM_STATEFUL_DOMAINS=127.0.0.1:8000,localhost:8000
```

3. Reinicia el backend:
```bash
php artisan serve --port=8000
```

---

### Error: "Unauthenticated" en endpoints protegidos

**Causa:** El token no se está enviando o es inválido.

**Verificación:**

1. Verifica que el token se guardó en `SecureStore`:
```typescript
// En src/hooks/useAuth.ts
useEffect(() => {
  const token = await SecureStore.getItemAsync('sanctum_token');
  console.log('Token stored:', token);
}, []);
```

2. Verifica que el interceptor está inyectando el token:
```typescript
// En src/services/api.config.ts
// Línea 14-28: El interceptor debe hacer:
// Authorization: Bearer <token>
```

3. Verifica que el backend reconoce el token:
```bash
TOKEN="tu-token-aqui"
curl -X GET http://127.0.0.1:8000/api/properties \
  -H "Authorization: Bearer $TOKEN"
```

---

### Error: "File upload failed"

**Causa:** El FormData no se está construyendo correctamente.

**Verificación:**

```typescript
// En src/services/property.service.ts
const uploadProperty = async (formData: FormData) => {
  console.log('FormData keys:', Array.from(formData.keys()));
  // Debería imprimir: ["title", "price", "images"]
  
  const response = await api.post('/properties', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });
  
  return response.data;
};
```

---

### Error: "Database connection error"

**Causa:** Backend no puede conectar a la base de datos.

**Solución:**

```bash
cd ../backend

# Verifica .env
cat .env | grep DB_

# Para SQLite:
touch database/database.sqlite
php artisan migrate

# Para MySQL:
# Asegúrate de que MySQL está corriendo
# mysql -u root -p
# CREATE DATABASE colombia_sin_comision;

php artisan migrate
```

---

### Error: "Storage disk not found"

**Causa:** Cloudflare R2 no está configurado.

**Solución temporal (desarrollo):**

Usa `public` disk en lugar de `r2`:

```php
// En ../backend/.env
FILESYSTEM_DISK=public

// Crea la carpeta:
php artisan storage:link

// Las imágenes se guardarán en storage/app/public/
// Y serán accesibles en http://127.0.0.1:8000/storage/
```

---

## 📋 Checklist: Backend Integrado

- [ ] ✅ Backend está corriendo en http://127.0.0.1:8000
- [ ] ✅ `.env.local` tiene `EXPO_PUBLIC_API_URL=http://127.0.0.1:8000`
- [ ] ✅ Endpoint POST `/api/auth/login` funciona (curl test)
- [ ] ✅ Endpoint GET `/api/properties` funciona
- [ ] ✅ CORS está configurado en backend
- [ ] ✅ Puedes hacer login desde la app
- [ ] ✅ Token se guarda en SecureStore
- [ ] ✅ Puedes listar propiedades desde la app
- [ ] ✅ Puedes subir una propiedad con imágenes
- [ ] ✅ Puedes capturar 360 y subir la imagen
- [ ] ✅ Puedes grabar video y subirlo

---

## 🔗 Recursos Útiles

- [Laravel Sanctum Docs](https://laravel.com/docs/sanctum)
- [Expo Axios Docs](https://axios-http.com/)
- [FormData MDN](https://developer.mozilla.org/en-US/docs/Web/API/FormData)
- [React Native Debugging](https://reactnative.dev/docs/debugging)

---

**Si todo está configurado correctamente, la app debería conectar automáticamente con tu backend. ¡Éxito! 🚀**

# 📑 Índice de Archivos - Colombia Sin Comisión Mobile

**Guía rápida para encontrar y entender cada archivo del proyecto.**

---

## 🚀 EMPEZAR AQUÍ

| Documento | Descripción | Duración |
|-----------|-------------|----------|
| **[GETTING_STARTED.md](#documentacion)** | Guía ejecutiva para comenzar en 5 pasos | 5 min |
| **[BACKEND_INTEGRATION.md](#documentacion)** | Cómo conectar con tu backend Laravel | 10 min |
| **[QUICK_START.md](#documentacion)** | Los 5 comandos esenciales | 2 min |
| **[README.md](#documentacion)** | Documentación técnica completa | 20 min |

---

## 📁 ESTRUCTURA DE ARCHIVOS

### 🔧 CONFIGURACIÓN BASE

```
package.json               Dependencias npm (Expo, React, Zustand, Axios, FFmpeg)
app.json                   Configuración Expo (plugins, permisos, apps ID)
tsconfig.json             TypeScript compiler options con path aliases
babel.config.js           Babel presets para React Native
.env.example              Template de variables de entorno
.gitignore               Archivos ignorados por git
```

**Qué modificar:**
- `package.json` - Agregar nuevas dependencias
- `app.json` - Configurar apps ID para App Store/Play Store
- `.env.local` - Valores reales (copiar desde `.env.example`)

---

### 🔌 SERVICIOS API (src/services/)

#### **api.config.ts** - Cliente HTTP Central
```
Responsabilidades:
  • Crear instancia Axios con baseURL
  • Configurar interceptor de request (inyecta Bearer token)
  • Configurar interceptor de response (maneja 401, errores)
  • Exportar instancia para usar en otros servicios

Usado por:
  • auth.service.ts
  • property.service.ts
  • tour360.service.ts

Modificar si:
  • Cambias URL del backend
  • Agregas autenticación diferente
  • Necesitas headers personalizados
```

#### **auth.service.ts** - Autenticación
```
Responsabilidades:
  • login(email, password) - Autenticar usuario
  • logout() - Cerrar sesión
  • verifyPhone(phone, otp) - Verificar número
  • checkAuth() - Validar token activo
  • getUser() - Obtener datos del usuario
  • updateProfile() - Actualizar perfil

Integración:
  • Firebase Auth (login) → Backend (Sanctum) → Token guardado

Usado por:
  • useAuth hook
  • authStore (Zustand)
  • screens de autenticación

Modificar si:
  • Cambias proveedor de autenticación
  • Agregas multi-factor
  • Cambias estructura de response
```

#### **property.service.ts** - Propiedades Inmobiliarias
```
Responsabilidades:
  • getProperties() - Listar todas (paginado)
  • getProperty(id) - Detalle de una
  • createProperty(data) - Crear nueva con imágenes
  • updateProperty(id, data) - Actualizar
  • deleteProperty(id) - Eliminar
  • uploadImages(propertyId, files) - Subir fotos

Usado por:
  • useProperties hook
  • propertyStore (Zustand)
  • pantallas de propiedades

Modificar si:
  • Cambias estructura de propiedad
  • Agregas filtros diferentes
  • Modificas endpoint de upload
```

#### **tour360.service.ts** - Tours 360°
```
Responsabilidades:
  • getTour360(propertyId) - Obtener tour de propiedad
  • createScene(propertyId, sceneData) - Crear escena
  • uploadSceneImage(propertyId, image) - Subir imagen equirectangular
  • updateHotspots(sceneId, hotspots) - Actualizar puntos interactivos
  • deleteTour360(tourId) - Eliminar tour

Usado por:
  • tour360Store (Zustand)
  • useSensors hook (para captura)
  • pantallas de captura y visualización

Modificar si:
  • Cambias estructura de escenas
  • Agregas nuevos datos a hotspots
  • Cambias formato de imagen
```

---

### 💾 STATE MANAGEMENT (src/stores/)

#### **authStore.ts** - Estado de Autenticación
```
Estado:
  • user - Datos del usuario logueado
  • token - Token Sanctum activo
  • isLoading - Cargando
  • isAuthenticated - Booleano de login
  • error - Último error

Acciones:
  • loginWithFirebase(email, password)
  • logout()
  • updatePhone(phone, otp)
  • checkAuth()
  • setError(error)

Uso en componentes:
  import { useAuthStore } from '@/stores/authStore'
  const { user, isAuthenticated } = useAuthStore()
  const logout = useAuthStore(state => state.logout)
```

#### **propertyStore.ts** - Estado de Propiedades
```
Estado:
  • properties - Array de propiedades
  • currentProperty - Propiedad seleccionada
  • isLoading - Cargando
  • pagination - Info de paginación
  • error - Último error

Acciones:
  • fetchProperties(page)
  • fetchProperty(id)
  • createProperty(data)
  • updateProperty(id, data)
  • deleteProperty(id)
  • uploadImages(propertyId, files)

Uso:
  const properties = usePropertyStore(state => state.properties)
  const fetchProperties = usePropertyStore(state => state.fetchProperties)
```

#### **tour360Store.ts** - Estado de Tours 360
```
Estado:
  • tours - Array de tours creados
  • currentTour - Tour en edición
  • scenes - Escenas del tour actual
  • isCapturing - En captura de 360
  • progress - % de captura completado

Acciones:
  • startCapture(propertyId)
  • addFrame(frameData) - Agregar frame capturado
  • completeCapture() - Finalizar captura
  • addScene(sceneData)
  • updateScene(sceneId, data)
  • uploadTour()

Uso:
  const progress = useTour360Store(state => state.progress)
  const addFrame = useTour360Store(state => state.addFrame)
```

#### **videoStore.ts** - Estado del Editor Video
```
Estado:
  • storyboard - Array de escenas con duraciones
  • currentScene - Escena siendo grabada
  • recordedClips - Videos capturados por escena
  • format - "horizontal" o "vertical"
  • totalDuration - Duración acumulada
  • isComposing - Componiendo video final

Acciones:
  • createStoryboard(format) - Inicializar storyboard
  • updateScene(sceneId, clipFile)
  • nextScene() / previousScene()
  • composeVideo(watermarkPath)
  • uploadVideo()

Uso:
  const storyboard = useVideoStore(state => state.storyboard)
  const format = useVideoStore(state => state.format)
  const composeVideo = useVideoStore(state => state.composeVideo)
```

---

### 🪝 CUSTOM HOOKS (src/hooks/)

#### **useAuth.ts** - Hook de Autenticación
```
Returns:
  • user - Usuario actual (null si no autenticado)
  • isLoading - Cargando
  • error - Error actual
  • login(email, password) - Hacer login
  • logout() - Cerrar sesión
  • checkAuth() - Validar sesión

Ejemplo:
  const { user, login, logout } = useAuth()
  if (!user) return <LoginScreen />
```

#### **useProperties.ts** - Hook de Propiedades
```
Returns:
  • properties - Array de propiedades
  • currentProperty - Propiedad seleccionada
  • isLoading - Cargando
  • error - Error
  • fetchProperties() - Obtener listado
  • fetchProperty(id) - Obtener detalle
  • createProperty(data) - Crear
  • deleteProperty(id) - Eliminar

Ejemplo:
  useEffect(() => {
    const { properties } = useProperties()
    console.log(properties)
  }, [])
```

#### **useSensors.ts** - Hook de Giroscopio (SENSOR DEVICE MOTION)
```
Returns:
  • orientation - { x, y, z } ángulos actuales
  • isListening - Escuchando sensores
  • calibrationOffset - Punto cero calibrado
  • calibrate() - Establecer punto cero
  • startListening() - Activar sensores
  • stopListening() - Desactivar sensores
  • getGridPoint() - Obtener punto en esfera según orientación

Ejemplo (360 Guided Capture):
  const { orientation, calibrate } = useSensors()
  // Cada rotación actualiza automáticamente la esfera visual
```

#### **useCamera.ts** - Hook de Cámara
```
Returns:
  • hasPermission - Permiso de cámara otorgado
  • isReady - Cámara lista
  • requestPermission() - Solicitar permiso
  • takePicture() - Capturar foto
  • recordVideo() - Grabar video
  • stopRecording() - Detener grabación

Ejemplo:
  const { hasPermission, takePicture } = useCamera()
  if (!hasPermission) return <PermissionScreen />
```

---

### 📋 TIPOS TYPESCRIPT (src/types/)

#### **auth.ts**
```typescript
interface User {
  id: number
  name: string
  email: string
  phone?: string
  verified_at?: string
}

interface AuthResponse {
  token: string
  user: User
}

interface PhoneVerificationPayload {
  phone: string
  otp: string
}
```

#### **property.ts**
```typescript
interface Property {
  id: number
  title: string
  price: number
  bedrooms: number
  bathrooms: number
  area: number
  images: Array<{ id: number; url: string }>
  tour360?: Tour360
}

interface PropertyList {
  data: Property[]
  meta: PaginationMeta
}
```

#### **tour360.ts**
```typescript
interface Tour360 {
  id: number
  property_id: number
  scenes: Scene[]
  public_cover_url: string
}

interface Scene {
  id: number
  name: string
  image_url: string
  hotspots: Hotspot[]
}

interface CapturedFrame {
  image: string  // base64
  latitude: number
  longitude: number
  timestamp: number
}
```

#### **video.ts**
```typescript
type VideoFormat = 'horizontal' | 'vertical'

interface VideoScene {
  id: string
  name: string
  recommendedDuration: number
  description: string
  recordedClip?: string  // file path
}

interface VideoStoryboard {
  format: VideoFormat
  scenes: VideoScene[]
  totalDuration: number
}

// Storyboards predefinidos
const DEFAULT_STORYBOARD_HORIZONTAL = [
  { id: '1', name: 'Hook', recommendedDuration: 5, ... },
  { id: '2', name: 'Social Areas', recommendedDuration: 10, ... },
  // ...
]
```

#### **sensors.ts**
```typescript
interface DeviceOrientation {
  x: number  // roll (rotación en X)
  y: number  // pitch (rotación en Y)
  z: number  // yaw (rotación en Z)
}

interface SphereGridPoint {
  id: string
  latitude: number   // -90 a 90
  longitude: number  // -180 a 180
  captured: boolean
}
```

---

### ⚙️ CONSTANTES (src/constants/)

#### **api.config.ts**
```typescript
// Rutas de API
export const API_ROUTES = {
  AUTH: {
    LOGIN: '/api/auth/login',
    LOGOUT: '/api/auth/logout',
    VERIFY_PHONE: '/api/auth/verify-phone',
  },
  PROPERTIES: {
    LIST: '/api/properties',
    DETAIL: (id) => `/api/properties/${id}`,
    CREATE: '/api/properties',
    UPDATE: (id) => `/api/properties/${id}`,
    DELETE: (id) => `/api/properties/${id}`,
    UPLOAD_IMAGES: (id) => `/api/properties/${id}/images`,
  },
  TOUR360: {
    GET: (propertyId) => `/api/properties/${propertyId}/tour360`,
    CREATE_SCENE: (propertyId) => `/api/properties/${propertyId}/tour360/scenes`,
    UPDATE_HOTSPOTS: (sceneId) => `/api/tour360/${sceneId}/hotspots`,
  },
  VIDEO: {
    UPLOAD: (propertyId) => `/api/properties/${propertyId}/commercial-video`,
  },
}

// Configuración de cámara, sensores, video
export const CAMERA_CONFIG = { ... }
export const SPHERE_GUIDE_CONFIG = { ... }
export const VIDEO_CONFIG = { ... }
```

#### **routes.ts**
```typescript
export const ROUTES = {
  AUTH: {
    LOGIN: '/(auth)/login',
    PHONE_VERIFY: '/(auth)/phone-verify',
  },
  APP: {
    HOME: '/(app)/home',
    PROPERTIES: '/(app)/properties',
    PROPERTY_DETAIL: (id) => `/(app)/properties/${id}`,
    CREATE_PROPERTY: '/(app)/create-property',
    MY_PROPERTIES: '/(app)/my-properties',
    SETTINGS: '/(app)/settings',
  },
}
```

#### **themes.ts**
```typescript
// Paleta de colores
export const colors = {
  primary: '#1976D2',
  secondary: '#1E88E5',
  success: '#43A047',
  error: '#E53935',
  // ... más colores
}

// Tamaños de fuente
export const fontSizes = {
  xs: 12,
  sm: 14,
  base: 16,
  lg: 18,
  xl: 20,
}

// Espaciado
export const spacing = {
  xs: 4,
  sm: 8,
  md: 16,
  lg: 24,
}

// Bordes
export const borderRadius = {
  sm: 4,
  md: 8,
  lg: 12,
}

// Sombras
export const shadows = { ... }
```

---

### 🛠️ UTILIDADES (src/utils/)

#### **equirectangular.ts** - Composición de Imágenes 360
```typescript
class EquirectangularComposer {
  addFrame(frame: CapturedFrame): void
  getProgress(): number
  composeImage(): Promise<string>  // Retorna base64
  getMetadata(): FrameMetadata[]
}

// Uso:
const composer = new EquirectangularComposer()
frames.forEach(frame => composer.addFrame(frame))
const panorama = await composer.composeImage()
await api.post('/tour360/create', { image: panorama })
```

#### **videoComposer.ts** - Composición de Videos con FFmpeg
```typescript
class VideoComposer {
  static async composeVideo(
    clips: string[],      // Array de file paths
    watermark: string,    // Path de watermark PNG
    format: VideoFormat   // 'horizontal' o 'vertical'
  ): Promise<string>      // Path del video final
  
  static async getVideoMetadata(videoPath: string): Promise<{
    duration: number
    width: number
    height: number
  }>
}

// Uso:
const videoPath = await VideoComposer.composeVideo(
  [clip1, clip2, clip3],
  watermarkPath,
  'horizontal'
)
await property.uploadVideo(videoPath)
```

#### **errorHandler.ts** - Manejo de Errores
```typescript
export function getErrorMessage(error: unknown): string
export function isNetworkError(error: unknown): boolean
export function isAuthError(error: unknown): boolean
export function isValidationError(error: unknown): boolean

// Uso:
try {
  await api.post('/properties', data)
} catch (error) {
  const message = getErrorMessage(error)
  showAlert(message)
}
```

---

### 🎨 COMPONENTES (src/components/)

#### **common/Button.tsx**
```typescript
<Button
  title="Capturar 360"
  variant="primary"      // 'primary' | 'secondary' | 'danger'
  onPress={() => { }}
  isLoading={false}
  disabled={false}
/>
```

#### **common/Input.tsx**
```typescript
<Input
  label="Email"
  placeholder="user@example.com"
  value={email}
  onChangeText={setEmail}
  error={emailError}
  type="email"
/>
```

#### **common/Card.tsx**
```typescript
<Card>
  <Text>Contenido dentro de tarjeta</Text>
</Card>
```

#### **common/Loading.tsx**
```typescript
<Loading />
// Muestra spinner con "Cargando..."
```

#### **tour360/SphereGuide.tsx** - Visualización Interactiva 360
```typescript
<SphereGuide
  progress={45}              // % completado
  onCapture={() => { }}
  onComplete={() => { }}
  isCapturing={true}
/>
// Muestra esfera 3D con grid de puntos
// Se rellena según orientación del dispositivo
// Feedback visual en tiempo real
```

---

### 📱 NAVEGACIÓN (app/)

#### **_layout.tsx** - Root Layout
```
• Configura Stack de navegación
• Carga fuentes personalizadas
• Valida autenticación al iniciar
• Redirige a login si no hay token
```

#### **(auth)/_layout.tsx** - Auth Group
```
• Stack para Login y Phone Verify
• Sin header
• Transiciones personalizadas
```

#### **(app)/_layout.tsx** - Protected Routes
```
• Stack para todas las pantallas de la app
• Requiere autenticación válida
• Headers personalizados
• Definen todas las rutas protegidas
```

---

### 🖥️ PANTALLAS IMPLEMENTADAS (app/)

#### **login.tsx**
```
Flujo:
  1. Mostrar formulario Email + Password
  2. Botón "Iniciar Sesión"
  3. En caso de error, mostrar alert
  4. En caso de éxito, guardar token
  5. Navegar a home

Estados:
  • email - String
  • password - String
  • isLoading - Boolean
  • error - String

Cambios a hacer:
  • Integrar Firebase si quieres autenticación social
  • Agregar "¿Olvidaste contraseña?"
  • Agregar "No tienes cuenta? Regístrate"
```

#### **phone-verify.tsx**
```
Flujo:
  1. Solicitar número de teléfono
  2. Enviar OTP al backend
  3. Solicitar código OTP
  4. Verificar en backend
  5. Completar autenticación

Estados:
  • phone - String
  • otp - String
  • isLoading - Boolean
  • error - String

Integración:
  • Llamar a authService.verifyPhone(phone, otp)
```

#### **home.tsx**
```
Pantalla Principal (Dashboard)
  • Bienvenida personalizada
  • Botones de acción rápida:
    - Crear Propiedad
    - Capturar 360
    - Grabar Video
  • Propiedades destacadas
  • Mis propiedades (link)
  • Perfil (link)

Lógica:
  • useEffect para cargar propiedades
  • useAuth para obtener usuario
  • Navegar según botón presionado
```

#### **create-property/360-guided/index.tsx**
```
Captura 360 Guiada
  • Mostrar SphereGuide
  • Botón CALIBRAR
  • Botón CAPTURAR
  • Estado de progreso
  • Preview del resultado
  • Botón SUBIR

Flujo:
  1. Calibrar giroscopio
  2. Rotar dispositivo para capturar
  3. Puntos se iluminan en tiempo real
  4. Al 100%, permitir completar
  5. Subir al backend
```

#### **create-property/commercial-video/format-select.tsx**
```
Selección de Formato Video
  • Botón: Horizontal (YouTube)
  • Botón: Vertical (Reels/TikTok)
  • Descripción de cada formato

Flujo:
  1. Usuario selecciona formato
  2. Guardar formato en videoStore
  3. Navegar a storyboard
```

---

### 📚 DOCUMENTACIÓN

#### **README.md** (14KB+)
```
Índice:
  • Setup e instalación
  • Estructura del proyecto
  • Descripción de módulos
  • Arquitectura detallada
  • Decisiones técnicas
  • Endpoints del backend
  • Troubleshooting
  • Deployment
```

#### **QUICK_START.md**
```
5 pasos rápidos:
  1. npm install
  2. cp .env.example .env.local
  3. npm start
  4. Probar login
  5. Explorar features
```

#### **GETTING_STARTED.md** [NUEVO]
```
Guía ejecutiva:
  • Resumen de lo creado
  • Estructura resumida
  • Checklist de instalación
  • Personalización inmediata
  • Testing rápido
```

#### **BACKEND_INTEGRATION.md** [NUEVO]
```
Cómo integrar con Laravel:
  • Verificar backend corriendo
  • Configurar variables de entorno
  • Endpoints esperados
  • Testing de conexión
  • Troubleshooting
  • CORS setup
```

#### **IMPLEMENTATION_GUIDE.md**
```
Plantillas para 7 pantallas restantes:
  • Properties List
  • Property Detail
  • Tour360 Viewer
  • My Properties
  • Create Property Form
  • Video Storyboard
  • Video Recording
  • Video Editor
```

#### **IMPLEMENTATION_SUMMARY.md**
```
Resumen ejecutivo:
  • Estadísticas de archivos
  • Dependencias instaladas
  • Modelos de datos
  • Roadmap
  • Blockers conocidos
```

---

## 🎯 CÓMO USAR ESTE ÍNDICE

### Si quieres modificar...

**La autenticación:**
→ [auth.service.ts](#servicios-api-srcservices), [authStore.ts](#state-management-srcstores), [useAuth.ts](#custom-hooks-srchooks)

**Las propiedades:**
→ [property.service.ts](#servicios-api-srcservices), [propertyStore.ts](#state-management-srcstores), [Property type](#tipos-typescript-srctypes)

**Los tours 360:**
→ [tour360.service.ts](#servicios-api-srcservices), [tour360Store.ts](#state-management-srcstores), [useSensors.ts](#custom-hooks-srchooks), [SphereGuide.tsx](#componentes-srccomponents)

**Los videos:**
→ [videoStore.ts](#state-management-srcstores), [videoComposer.ts](#utilidades-srcutils), [Video type](#tipos-typescript-srctypes)

**Las rutas de navegación:**
→ [routes.ts](#constantes-srcconstants), [app/_layout.tsx](#navegación-app), [(app)/_layout.tsx](#navegación-app)

**El tema visual:**
→ [themes.ts](#constantes-srcconstants), [Button.tsx](#componentes-srccomponents), [Card.tsx](#componentes-srccomponents)

**La conexión API:**
→ [api.config.ts](#servicios-api-srcservices), [api.config.ts (constants)](#constantes-srcconstants), [BACKEND_INTEGRATION.md](#documentación)

---

## ✅ CHECKLIST DE NAVEGACIÓN

- [ ] Leí GETTING_STARTED.md (5 min)
- [ ] Leí BACKEND_INTEGRATION.md (10 min)
- [ ] Entiendo la estructura de servicios
- [ ] Entiendo cómo funcionan los Zustand stores
- [ ] Entiendo los custom hooks
- [ ] Entiendo los tipos TypeScript
- [ ] Puedo encontrar cualquier archivo en este índice
- [ ] Puedo modificar cualquier componente sin perder coherencia

---

**📞 Cualquier pregunta, vuelve a este índice.**

_Creado para facilitar navegación del proyecto completo._

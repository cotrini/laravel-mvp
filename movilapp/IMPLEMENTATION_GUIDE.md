# 📋 Guía de Implementación - Pantallas Faltantes

## Resumen del Trabajo Completado

✅ **Estructura base** - Carpetas y archivos configurados
✅ **Configuración de Expo** - app.json, tsconfig, babel.config
✅ **Servicios de API** - Axios con interceptores, auth, propiedades, tours 360
✅ **Zustand Stores** - authStore, propertyStore, tour360Store, videoStore
✅ **Hooks Personalizados** - useAuth, useProperties, useSensors, useCamera
✅ **Utilidades** - equirectangular.ts, videoComposer.ts, errorHandler.ts
✅ **Tipos TypeScript** - Completos para auth, propiedades, tours 360, video, sensores
✅ **Componentes Base** - Button, Input, Card, Loading, SphereGuide
✅ **Pantallas de Autenticación** - login.tsx, phone-verify.tsx
✅ **Layout Raíz** - _layout.tsx con stack navigation
✅ **Pantalla Home** - home.tsx con acciones rápidas
✅ **Módulo 360 Inicio** - 360-guided/index.tsx
✅ **Módulo Video Inicio** - format-select.tsx

---

## Pantallas a Completar (Copiar de Arquitectura)

### 1. Propiedades - Listado y Detalle

#### `app/(app)/properties/index.tsx`
**Propósito**: Mostrar catálogo de propiedades con búsqueda y filtros

**Implementar basado en**:
- Hook `useProperties()` para fetching
- Componente `PropertyCard` para cada item
- Barra de búsqueda con filtros (ciudad, precio, habitaciones)
- Paginación

```typescript
import { useProperties } from '@/hooks/useProperties';
import { PropertyCard } from '@/components/property/PropertyCard';
import { PropertyFilter } from '@/components/property/PropertyFilter';

export default function PropertiesScreen() {
  const { properties, fetchProperties } = useProperties();
  const [filters, setFilters] = useState({});
  
  useEffect(() => {
    fetchProperties(1, filters);
  }, [filters]);
  
  return (
    <FlatList
      data={properties}
      renderItem={({ item }) => <PropertyCard property={item} />}
      ListHeaderComponent={<PropertyFilter onFilterChange={setFilters} />}
    />
  );
}
```

#### `app/(app)/properties/[id]/index.tsx`
**Propósito**: Mostrar detalles completos de una propiedad

**Implementar**:
- Obtener propiedad por ID de ruta
- Mostrar galería de imágenes
- Características (precio, dormitorios, baños, etc.)
- Botón para ver tour 360
- Botón de contacto

#### `app/(app)/properties/[id]/tour360/index.tsx`
**Propósito**: Visor interactivo de tour 360

**Librerías recomendadas**:
- `react-native-three` para renderizado 3D
- O usar componente custom con Reanimated

**Implementar**:
- Cargar imagen equirectangular del tour
- Permitir gesto de rotación y zoom
- Mostrar hotspots interactivos
- Navegación entre escenas

---

### 2. Mis Propiedades

#### `app/(app)/my-properties.tsx`
**Propósito**: Listar propiedades del usuario autenticado

**Implementar**:
- `fetchMyProperties()` al montar
- Listar con opciones (editar, eliminar, ver tour)
- Botón flotante para crear nueva

---

### 3. Crear Propiedad

#### `app/(app)/create-property/index.tsx`
**Propósito**: Pantalla principal para crear propiedad

**Estructura**:
```
1. Información básica
   - Título, descripción, dirección
2. Detalles
   - Precio, tipo, habitaciones, etc.
3. Ubicación (mapa)
   - Latitud, longitud
4. Multimedia
   - Opción: Captura 360 guiada
   - Opción: Video comercial
   - Galería de fotos
```

---

### 4. Módulo de Video Comercial (3 pantallas)

#### `app/(app)/create-property/commercial-video/storyboard.tsx`
**Propósito**: Mostrar escenas predefinidas y permitir grabación

**Implementar basado en**:
```typescript
import { useVideoStore } from '@/stores/videoStore';

export default function StoryboardScreen() {
  const storyboard = useVideoStore((state) => state.currentStoryboard);
  const { nextScene, previousScene, updateScene } = useVideoStore();
  
  return (
    <ScrollView>
      {/* Escena actual en grande */}
      <CurrentSceneCard />
      
      {/* Grid de todas las escenas */}
      <SceneGrid scenes={storyboard.scenes} />
      
      {/* Barra de progreso */}
      <ProgressBar />
      
      {/* Botones de navegación */}
      <NavigationButtons />
    </ScrollView>
  );
}
```

**Componentes necesarios**:
- `CurrentSceneCard`: Muestra escena activa con consejos
- `SceneGrid`: Grid de todas las escenas con estados
- `ProgressBar`: Progreso visual

#### `app/(app)/create-property/commercial-video/recording.tsx`
**Propósito**: Grabar o importar video para cada escena

**Implementar**:
```typescript
import { CameraView } from 'expo-camera';
import { useVideoStore } from '@/stores/videoStore';

export default function RecordingScreen() {
  const [isRecording, setIsRecording] = useState(false);
  const [recordedUri, setRecordedUri] = useState<string | null>(null);
  const cameraRef = useRef(null);
  const updateScene = useVideoStore((state) => state.updateScene);
  
  const startRecording = async () => {
    const video = await cameraRef.current.recordAsync();
    setRecordedUri(video.uri);
  };
  
  const saveRecording = () => {
    updateScene(sceneId, recordedUri, recordingTime);
    router.back();
  };
  
  if (recordedUri) {
    return <VideoPreview uri={recordedUri} onSave={saveRecording} />;
  }
  
  return <CameraView ref={cameraRef} />; // Con overlay de timer
}
```

#### `app/(app)/create-property/commercial-video/editor.tsx`
**Propósito**: Componer video final con marca de agua y subir

**Implementar**:
```typescript
import { VideoComposer } from '@/utils/videoComposer';
import { usePropertyStore } from '@/stores/propertyStore';
import { useVideoStore } from '@/stores/videoStore';

export default function EditorScreen() {
  const storyboard = useVideoStore((state) => state.currentStoryboard);
  const uploadVideo = usePropertyStore((state) => state.uploadVideo);
  const [isComposing, setIsComposing] = useState(false);
  const [progress, setProgress] = useState(0);
  
  const handleCompose = async () => {
    setIsComposing(true);
    try {
      const watermarkUri = require('@/assets/images/watermark.png');
      const videoUri = await VideoComposer.composeVideo(storyboard, watermarkUri);
      await uploadVideo(storyboard.propertyId, videoUri);
      Alert.alert('¡Éxito!', 'Video subido correctamente');
    } catch (error) {
      Alert.alert('Error', 'No se pudo componer el video');
    }
  };
  
  return (
    <>
      <ConfigurationSummary />
      {isComposing && <ProgressIndicator progress={progress} />}
      <Button onPress={handleCompose} title="Componer y Subir" />
    </>
  );
}
```

---

### 5. Perfil y Configuración

#### `app/(app)/profile.tsx`
**Propósito**: Mostrar y editar perfil de usuario

**Implementar**:
```typescript
import { useAuth } from '@/hooks/useAuth';

export default function ProfileScreen() {
  const { user, updatePhone } = useAuth();
  
  return (
    <>
      <ProfileHeader user={user} />
      <EditableField label="Nombre" value={user.name} />
      <EditableField label="Email" value={user.email} editable={false} />
      <EditableField label="Teléfono" value={user.phone_number} onSave={updatePhone} />
    </>
  );
}
```

#### `app/(app)/settings.tsx`
**Propósito**: Configuración general de la app

**Implementar**:
- Cambio de idioma
- Preferencias de notificaciones
- Información de versión
- Política de privacidad
- Cerrar sesión

---

## Componentes a Crear

### Propiedades
**`src/components/property/PropertyCard.tsx`**
- Mostrar: imagen, título, precio, ubicación, características

**`src/components/property/PropertyDetails.tsx`**
- Vista expandida de propiedad con todas las características

**`src/components/property/PropertyFilter.tsx`**
- Filtros: ciudad, precio rango, habitaciones, baños

### Video
**`src/components/video/VideoPreview.tsx`**
- Mostrar video grabado con controles de reproducción

**`src/components/video/StoryboardScenes.tsx`**
- Grid de escenas con progreso visual

**`src/components/video/WatermarkOverlay.tsx`**
- Componente para posicionar marca de agua

---

## Checklist de Implementación

- [ ] Crear `properties/index.tsx` con FlatList
- [ ] Crear `PropertyCard.tsx`
- [ ] Crear `PropertyFilter.tsx`
- [ ] Crear `properties/[id]/index.tsx`
- [ ] Crear `properties/[id]/tour360/index.tsx`
- [ ] Crear `my-properties.tsx`
- [ ] Crear `create-property/index.tsx`
- [ ] Crear `commercial-video/storyboard.tsx`
- [ ] Crear `commercial-video/recording.tsx`
- [ ] Crear `commercial-video/editor.tsx`
- [ ] Crear `profile.tsx`
- [ ] Crear `settings.tsx`
- [ ] Crear todos los componentes secundarios
- [ ] Copiar logo a assets/images/
- [ ] Implementar Firebase Auth real
- [ ] Pruebas en dispositivo real
- [ ] Optimización de performance
- [ ] Build para producción

---

## Tips Importantes

1. **Reutiliza componentes**: Button, Input, Card ya están listos
2. **Usa el hook correcto**: `useAuth()`, `useProperties()`, `useSensors()`
3. **Manejo de estado**: Zustand está centralizado en stores/
4. **TypeScript**: Todos los tipos ya están definidos en types/
5. **Estilos**: Usa constantes de COLORS y SPACING de constants/themes.ts
6. **Errores**: Usa `errorHandler.getErrorMessage()` para mostrar errores
7. **Navegación**: Usa `router.push()` de expo-router
8. **Permisos**: Solicita en tiempo de uso, no en inicio

---

## Próximos Pasos

1. Completar las pantallas con el template proporcionado
2. Probar cada pantalla en simulador y dispositivo real
3. Integración con backend real (cambiar URLs en `.env.local`)
4. Implementar Firebase Auth completo
5. Optimizar imágenes y performance
6. Preparar para App Store y Google Play

¡Éxito con la implementación! 🚀

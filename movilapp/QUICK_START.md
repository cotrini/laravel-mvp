# 🚀 Quick Start Guide - Colombia Sin Comisión Mobile

## ⚡ 5 Pasos para Empezar

### 1️⃣ Instalar Dependencias
```bash
cd movilapp
npm install
```

### 2️⃣ Configurar Variables de Entorno
```bash
cp .env.example .env.local
# Edita .env.local con tu configuración
```

**Variables necesarias**:
```env
EXPO_PUBLIC_API_URL=http://localhost:8000/api
# O tu URL de producción: https://api.colombiasincomision.com/api
```

### 3️⃣ Ejecutar en Desarrollo
```bash
# En terminal 1: Expo server
npm start

# En terminal 2 (después que veas el código QR):
# Para iOS
npm run ios

# Para Android
npm run android

# Para Web
npm run web
```

### 4️⃣ Probar en Dispositivo Real
```bash
# Instalando Expo Go en tu celular:
# 1. Descarga Expo Go de Play Store o App Store
# 2. Escanea el código QR que aparece en tu terminal
# 3. La app se cargará en tu dispositivo
```

### 5️⃣ Próximos Pasos
- [ ] Completar pantallas faltantes (ver IMPLEMENTATION_GUIDE.md)
- [ ] Implementar Firebase Auth con credenciales reales
- [ ] Conectar con backend real (cambiar URL en .env.local)
- [ ] Probar módulo 360 en dispositivo (necesita giroscopio)
- [ ] Probar grabación de video
- [ ] Build para producción

---

## 🔑 Características Principales

### ✅ Ya Implementadas
- **Autenticación**: Login y verificación de teléfono
- **API Client**: Axios con interceptores y manejo de errores
- **Gestión de Estado**: Zustand stores para auth, propiedades, tours, video
- **Módulo 360 Guiado**: SphereGuide con seguimiento de sensores
- **Módulo Video Comercial**: Storyboard predefinido, grabación por escenas
- **Componentes Base**: Button, Input, Card, Loading

### ⏳ Pendientes (Fácil de completar)
Ver `IMPLEMENTATION_GUIDE.md` para copiar templates

---

## 📁 Archivos Clave

| Archivo | Propósito | Ubicación |
|---------|-----------|-----------|
| Autenticación | Zustand store de auth | `src/stores/authStore.ts` |
| Propiedades | API y store de propiedades | `src/services/property.service.ts` |
| 360 Guiado | Componente esfera + sensores | `src/components/tour360/SphereGuide.tsx` |
| Video Comercial | Configuración + composición | `src/types/video.ts`, `src/utils/videoComposer.ts` |
| Tipos | Definiciones TypeScript | `src/types/*.ts` |
| Constantes | Rutas, colores, configuración | `src/constants/*.ts` |

---

## 🧪 Testing Rápido

### Probar Autenticación
```bash
# 1. Ejecutar app
npm start

# 2. En la pantalla de login, usar cualquier email/password
# (Usa "dev-token" como placeholder)

# 3. Verificar que se redirija a home
```

### Probar Módulo 360
```bash
# 1. En pantalla Home, ir a "Nueva Propiedad" → "360 Guiado"
# 2. Permitir acceso a cámara
# 3. Presionar "Calibrar"
# 4. Rotar el dispositivo y presionar "Capturar Punto"
# 5. Cuando se complete el 100%, verás mensaje de éxito
```

### Probar Módulo Video
```bash
# 1. En pantalla Home, ir a "Nueva Propiedad" → "Video Comercial"
# 2. Seleccionar formato (Horizontal o Vertical)
# 3. Ver storyboard con escenas predefinidas
# 4. Seleccionar una escena y presionar "Grabar Escena"
```

---

## 🔧 Troubleshooting Común

### Error: "Cannot find module 'axios'"
```bash
npm install axios
```

### Error: "Permiso de cámara denegado"
- iOS: Ir a Settings → App → Permisos → Cámara
- Android: Ir a Configuración → Aplicaciones → Colombia Sin Comisión → Permisos

### Error: "API connection failed"
- Verificar que `EXPO_PUBLIC_API_URL` está correcta en `.env.local`
- Verificar que el backend está corriendo
- En desarrollo, usar `http://localhost:8000` (no `127.0.0.1`)

### Error: "Token not found"
- Es normal la primera vez que abres la app
- El login guardará el token en `expo-secure-store`
- Si persiste, limpiar datos de la app

### App se cierra al abrir cámara
- Necesita permisos
- En Expo Go, solicita permisos en tiempo de ejecución
- En build nativo, verifica `app.json` plugins

---

## 📱 Requisitos Mínimos

- **iOS**: 13.0+
- **Android**: API 21 (Android 5.0)+
- **Node**: 16.0+
- **npm**: 7.0+

---

## 🎯 Roadmap Rápido

```
Semana 1: ✅ Arquitectura completada
          ⏳ Completar pantallas base
          ⏳ Integración backend

Semana 2: ⏳ Testing en dispositivo
          ⏳ Pulir módulos 360 y video
          ⏳ Optimización

Semana 3: ⏳ Build iOS y Android
          ⏳ Publicar en App Store/Play Store
```

---

## 💡 Tips Importantes

1. **Guarda los cambios**: La recarga en vivo está habilitada
2. **Console logs**: Aparecen en Expo CLI (terminal)
3. **Errores de tipo**: Ejecuta `npm run type-check` regularmente
4. **Prueba con dispositivo real**: Los sensores solo funcionan en hardware real

---

## 📞 Necesitas Ayuda?

1. **Revisar documentación**: `README.md` - Documentación completa
2. **Guía de implementación**: `IMPLEMENTATION_GUIDE.md` - Cómo completar pantallas
3. **Resumen de implementación**: `IMPLEMENTATION_SUMMARY.md` - Qué se creó
4. **Comunidad Expo**: https://forums.expo.dev
5. **Docs React Native**: https://reactnative.dev

---

## ✨ ¡Ya está listo!

Tu aplicación móvil Colombia Sin Comisión tiene:
- ✅ Estructura profesional
- ✅ Servicios de API configurados
- ✅ Gestión de estado
- ✅ Módulos avanzados (360° y Video)
- ✅ TypeScript completo
- ✅ Componentes reutilizables

**¡Solo necesitas completar las pantallas y probar!**

¡Adelante! 🚀

---

**Última actualización**: 4 de Noviembre, 2026
**Versión**: 1.0.0
**Estado**: ✅ LISTO PARA DESARROLLO

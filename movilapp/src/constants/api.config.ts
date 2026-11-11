// URL base del backend (incluye /api/v1). En dispositivo físico debe ser la IP LAN del PC, no localhost.
export const API_BASE_URL = process.env.EXPO_PUBLIC_API_URL || 'http://localhost:8001/api/v1';

// Origen del backend (esquema + host + puerto), para rutas web como /simulador-credito.
export const API_ORIGIN = API_BASE_URL.match(/^https?:\/\/[^/]+/)?.[0] ?? '';

// Fotos de la sección de asesoría; mismas URLs que backend/resources/views/layouts/app.blade.php.
export const ADVISORY_PHOTOS = {
  finance: 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=900&q=85',
  publish: 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=900&q=85',
};

// Configuración de rutas de API
export const API_ROUTES = {
  AUTH: {
    FIREBASE: '/auth/firebase',
    ME: '/me',
    PHONE: '/me/phone',
  },
  PROPERTIES: {
    LIST: '/properties',
    DETAIL: (id: number) => `/properties/${id}`,
    MY: '/my/properties',
    VIDEO: (id: number) => `/properties/${id}/video`,
  },
  TOURS360: {
    LIST: '/tours',
    DETAIL: (slug: string) => `/tours/${slug}`,
    MY: '/my/tours',
    SCENE_IMAGES: '/tours/scene-images',
    CREATE: '/tours',
    UPDATE: (id: number) => `/tours/${id}`,
    DELETE: (id: number) => `/tours/${id}`,
  },
};

// Configuración de la aplicación
export const APP_CONFIG = {
  TIMEOUT: 30000, // ms
  MAX_RETRIES: 3,
  CACHE_DURATION: 5 * 60 * 1000, // 5 minutos
};

// Configuración de cámara
export const CAMERA_CONFIG = {
  QUALITY: 0.8,
  PHOTO_TYPE: 'jpg',
  VIDEO_BITRATE: 5000000,
};

// Configuración de 360 Guide
export const SPHERE_GUIDE_CONFIG = {
  GRID_SIZE: 4, // 4x4 grid por cara de la esfera
  UPDATE_INTERVAL: 100, // ms para sensor updates
  MIN_DISTANCE_THRESHOLD: 10, // grados
};

// Configuración de video
export const VIDEO_CONFIG = {
  MAX_DURATION: 40, // segundos
  MIN_SCENE_DURATION: 2,
  WATERMARK_OPACITY: 0.8,
};

// Estados de la aplicación
export const APP_STATES = {
  LOADING: 'loading',
  READY: 'ready',
  PROCESSING: 'processing',
  ERROR: 'error',
};

// Mensajes de error comunes
export const ERROR_MESSAGES = {
  NETWORK_ERROR: 'Error de conexión. Por favor verifica tu internet.',
  AUTH_ERROR: 'Error de autenticación. Por favor inicia sesión de nuevo.',
  CAMERA_PERMISSION: 'Se necesita permiso de cámara para continuar.',
  SENSOR_PERMISSION: 'Se necesita acceso a los sensores del dispositivo.',
  UPLOAD_ERROR: 'Error al subir el archivo. Intenta de nuevo.',
  PROCESSING_ERROR: 'Error al procesar el archivo.',
};

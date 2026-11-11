export type VideoFormat = 'horizontal' | 'vertical';

export interface VideoScene {
  id: string;
  title: string;
  description: string;
  recommendedDuration: number;
  hints: string[];
  videoUri?: string;
  duration?: number;
}

export interface VideoStoryboard {
  id: string;
  propertyId: number;
  format: VideoFormat;
  totalDuration: number;
  scenes: VideoScene[];
  watermarkUri?: string;
  musicUri?: string;
}

export interface ComercialVideoConfig {
  format: VideoFormat;
  resolution: {
    width: number;
    height: number;
  };
  bitrate: string;
  fps: number;
}

export const VIDEO_CONFIGS: Record<VideoFormat, ComercialVideoConfig> = {
  horizontal: {
    format: 'horizontal',
    resolution: { width: 1920, height: 1080 },
    bitrate: '5000k',
    fps: 30,
  },
  vertical: {
    format: 'vertical',
    resolution: { width: 1080, height: 1920 },
    bitrate: '4000k',
    fps: 30,
  },
};

export const DEFAULT_STORYBOARD_HORIZONTAL: VideoScene[] = [
  {
    id: 'hook',
    title: 'Gancho Inicial (0-5s)',
    description: 'Captura la atención: fachada o ángulo impactante',
    recommendedDuration: 5,
    hints: [
      'Muestra el exterior o entrada principal',
      'Usa movimiento dinámico de cámara',
      'Luz natural óptima',
    ],
  },
  {
    id: 'social-areas',
    title: 'Áreas Sociales (5-15s)',
    description: 'Sala, comedor, cocina principales',
    recommendedDuration: 10,
    hints: [
      'Planos amplios mostrando dimensiones',
      'Iluminación uniforme',
      'Detalla características especiales',
    ],
  },
  {
    id: 'bedrooms',
    title: 'Habitaciones (15-30s)',
    description: 'Recorrido rápido por dormitorios',
    recommendedDuration: 15,
    hints: [
      'Muestra almacenamiento y vistas',
      'Baños adjuntos si existen',
      'Iluminación natural',
    ],
  },
  {
    id: 'closing',
    title: 'Cierre / CTA (30-40s)',
    description: 'Call-to-action con info de contacto',
    recommendedDuration: 10,
    hints: [
      'Plano final impactante (terraza, piscina, etc)',
      'Incluye logo/datos de contacto',
      'Música que sube de volumen',
    ],
  },
];

export const DEFAULT_STORYBOARD_VERTICAL: VideoScene[] = [
  {
    id: 'hook-v',
    title: 'Gancho (0-3s)',
    description: 'Hook vertical impactante',
    recommendedDuration: 3,
    hints: ['Video vertical', 'Texto overlay con precio'],
  },
  {
    id: 'tour-v',
    title: 'Tour Rápido (3-25s)',
    description: 'Recorrido acelerado de la propiedad',
    recommendedDuration: 22,
    hints: ['Transiciones rápidas', 'Efecto fast-forward suave'],
  },
  {
    id: 'cta-v',
    title: 'CTA (25-40s)',
    description: 'Llamada a la acción',
    recommendedDuration: 15,
    hints: ['Botón de descarga de app', 'Link de WhatsApp'],
  },
];

export const MAX_VIDEO_SECONDS = 40;

// Sugerencia de música/ritmo por escena (se muestra en el storyboard).
export const MUSIC_HINTS: Record<string, string> = {
  hook: 'Arranque con energía: ritmo alto y bombo marcado',
  'social-areas': 'Pop instrumental o acústico, tempo medio',
  bedrooms: 'Lo-fi o piano suave, tempo tranquilo',
  closing: 'Ritmo que sube de volumen hasta el cierre',
  'hook-v': 'Beat corto y directo, sin pausas',
  'tour-v': 'Electrónica ligera a tempo alto para acelerar el recorrido',
  'cta-v': 'Remate con golpe final y silencio al terminar',
};


export interface Tour360 {
  id: number;
  title: string;
  slug: string;
  description: string;
  cover_url: string;
  public_cover_url: string;
  location: string;
  is_published: boolean;
  scenes: Scene[];
  created_at: string;
  updated_at: string;
}

export interface Scene {
  id: number;
  tour_id: number;
  image_url: string;
  public_image_url: string;
  sort_order: number;
  hotspots: Hotspot[];
  created_at: string;
  updated_at: string;
}

export interface Hotspot {
  id: number;
  scene_id: number;
  yaw: number;
  pitch: number;
  title: string;
  text: string;
  target_scene_id?: number;
}

// Respuesta de POST /tours/scene-images: la ruta se usa luego como staged_image al crear el tour.
export interface SceneImage {
  path: string;
}

export interface CapturedTour360 {
  frames: CapturedFrame[];
  metadata: {
    startedAt: number;
    completedAt: number;
    totalFrames: number;
    calibrationPoint?: { alpha: number; beta: number; gamma: number };
  };
}

export interface CapturedFrame {
  uri: string;
  latitude: number;
  longitude: number;
  timestamp: number;
}

export interface DeviceOrientation {
  alpha: number;
  beta: number;
  gamma: number;
}

export interface SphereGridPoint {
  id: string;
  latitude: number;
  longitude: number;
  captured: boolean;
  imageUri?: string;
}

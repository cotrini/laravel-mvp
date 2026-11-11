import { Skia, ImageFormat } from '@shopify/react-native-skia';
import * as FileSystem from 'expo-file-system/legacy';
import { CapturedFrame } from '@/types/tour360';

export const PANORAMA_WIDTH = 4096;
export const PANORAMA_HEIGHT = 2048;

// Une las fotos en un mapa equirectangular 2:1. Cada foto ocupa la celda de su punto
// de la grilla (lat/lon). Es una unión por celdas: no corrige paralaje ni costuras.
export async function composeEquirectangular(
  frames: CapturedFrame[],
  gridSize: number
): Promise<string> {
  const surface = Skia.Surface.MakeOffscreen(PANORAMA_WIDTH, PANORAMA_HEIGHT);
  if (!surface) throw new Error('No se pudo crear el lienzo del panorama');

  const canvas = surface.getCanvas();
  canvas.clear(Skia.Color('#000000'));
  const paint = Skia.Paint();

  const cellW = PANORAMA_WIDTH / gridSize;
  const cellH = PANORAMA_HEIGHT / (gridSize + 1);

  for (const frame of frames) {
    const data = await Skia.Data.fromURI(frame.uri);
    const image = Skia.Image.MakeImageFromEncoded(data);
    if (!image) continue;

    const src = Skia.XYWHRect(0, 0, image.width(), image.height());
    const cx = (frame.longitude / 360) * PANORAMA_WIDTH;
    const cy = ((frame.latitude + 90) / 180) * PANORAMA_HEIGHT;

    // Repite la celda en los bordes para que el mapa cierre sin costura horizontal.
    for (const offset of [-PANORAMA_WIDTH, 0, PANORAMA_WIDTH]) {
      const dest = Skia.XYWHRect(cx - cellW / 2 + offset, cy - cellH / 2, cellW, cellH);
      canvas.drawImageRect(image, src, dest, paint);
    }
    image.dispose();
  }

  const snapshot = surface.makeImageSnapshot();
  const base64 = snapshot.encodeToBase64(ImageFormat.JPEG, 90);
  snapshot.dispose();
  surface.dispose();

  const panoramaUri = `${FileSystem.cacheDirectory}panorama-${Date.now()}.jpg`;
  await FileSystem.writeAsStringAsync(panoramaUri, base64, {
    encoding: FileSystem.EncodingType.Base64,
  });
  return panoramaUri;
}

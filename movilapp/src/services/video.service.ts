import apiClient from './api.config';
import { VideoFormat } from '@/types/video';

export interface AssembledVideo {
  message: string;
  data: { id: number; type: string; title: string; url: string };
}

export const videoService = {
  // Envía las escenas en orden; el backend las une con ffmpeg, aplica el logo y guarda el video en el inmueble.
  async assemble(propertyId: number, format: VideoFormat, clipUris: string[]): Promise<AssembledVideo> {
    const formData = new FormData();
    formData.append('format', format);
    clipUris.forEach((uri, index) => {
      formData.append('clips[]', {
        uri,
        type: 'video/mp4',
        name: `scene-${index}.mp4`,
      } as any);
    });

    const response = await apiClient.post<AssembledVideo>(
      `/properties/${propertyId}/video-assembly`,
      formData,
      {
        headers: { 'Content-Type': 'multipart/form-data' },
        timeout: 300000,
      }
    );
    return response.data;
  },
};

import apiClient from './api.config';
import { Tour360, SceneImage } from '@/types/tour360';

export const tour360Service = {
  async getTours(page = 1): Promise<any> {
    try {
      const response = await apiClient.get('/tours', { params: { page } });
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  async getTour(slug: string): Promise<Tour360> {
    try {
      const response = await apiClient.get<Tour360>(`/tours/${slug}`);
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  async getMyTours(): Promise<Tour360[]> {
    try {
      const response = await apiClient.get<Tour360[]>('/my/tours');
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  async uploadSceneImage(propertyId: number, imageUri: string, sceneIndex: number): Promise<SceneImage> {
    try {
      const formData = new FormData();

      formData.append('image', {
        uri: imageUri,
        type: 'image/jpeg',
        name: `scene-${sceneIndex}-${Date.now()}.jpg`,
      } as any);
      formData.append('property_id', String(propertyId));

      const response = await apiClient.post<SceneImage>(
        '/tours/scene-images',
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        }
      );

      return response.data;
    } catch (error) {
      throw error;
    }
  },

  async createTour(tourData: {
    property_id: number;
    title: string;
    description?: string;
    location?: string;
    scenes: { title: string; staged_image: string }[];
  }): Promise<Tour360> {
    try {
      const response = await apiClient.post<Tour360>('/tours', tourData);
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  async updateTour(tourId: number, tourData: Partial<Tour360>): Promise<Tour360> {
    try {
      const response = await apiClient.put<Tour360>(`/tours/${tourId}`, tourData);
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  async deleteTour(tourId: number): Promise<void> {
    try {
      await apiClient.delete(`/tours/${tourId}`);
    } catch (error) {
      throw error;
    }
  },
};

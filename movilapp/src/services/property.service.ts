import apiClient from './api.config';
import { Property, PropertyFilters, PropertyList } from '@/types/property';
import { resolveMediaUrl } from '@/utils/format';

// El backend devuelve URLs de media absolutas con APP_URL (localhost); aquí se ajustan al host alcanzable.
function normalizeProperty(property: Property): Property {
  return {
    ...property,
    media: property.media.map((media) => ({ ...media, url: resolveMediaUrl(media.url) })),
    tour: property.tour
      ? {
          ...property.tour,
          cover_url: property.tour.cover_url ? resolveMediaUrl(property.tour.cover_url) : null,
        }
      : null,
  };
}

export const propertyService = {
  async getProperties(filters: PropertyFilters = {}): Promise<PropertyList> {
    const response = await apiClient.get<PropertyList>('/properties', {
      params: filters,
    });
    return { ...response.data, data: response.data.data.map(normalizeProperty) };
  },

  async getProperty(id: number): Promise<Property> {
    const response = await apiClient.get<{ data: Property }>(`/properties/${id}`);
    return normalizeProperty(response.data.data);
  },

  async getMyProperties(perPage = 12): Promise<PropertyList> {
    const response = await apiClient.get<PropertyList>('/my/properties', {
      params: { per_page: perPage },
    });
    return { ...response.data, data: response.data.data.map(normalizeProperty) };
  },

  async uploadVideo(propertyId: number, videoUri: string): Promise<any> {
    try {
      const formData = new FormData();

      formData.append('video', {
        uri: videoUri,
        type: 'video/mp4',
        name: `video-${Date.now()}.mp4`,
      } as any);

      const response = await apiClient.post(
        `/properties/${propertyId}/video`,
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
};

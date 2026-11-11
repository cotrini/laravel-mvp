import { create } from 'zustand';
import { Property } from '@/types/property';
import { propertyService } from '@/services/property.service';

interface PropertyStoreState {
  properties: Property[];
  myProperties: Property[];
  selectedProperty: Property | null;
  isLoading: boolean;
  error: string | null;

  fetchProperties: (page?: number) => Promise<void>;
  fetchMyProperties: () => Promise<void>;
  selectProperty: (property: Property) => void;
  uploadVideo: (propertyId: number, videoUri: string) => Promise<void>;
  clearError: () => void;
}

export const usePropertyStore = create<PropertyStoreState>((set) => ({
  properties: [],
  myProperties: [],
  selectedProperty: null,
  isLoading: false,
  error: null,

  fetchProperties: async (page = 1) => {
    set({ isLoading: true, error: null });
    try {
      const result = await propertyService.getProperties({ page });
      set({
        properties: result.data,
        isLoading: false,
      });
    } catch (error: any) {
      set({
        error: error.message,
        isLoading: false,
      });
    }
  },

  fetchMyProperties: async () => {
    set({ isLoading: true, error: null });
    try {
      const result = await propertyService.getMyProperties();
      set({
        myProperties: result.data,
        isLoading: false,
      });
    } catch (error: any) {
      set({
        error: error.message,
        isLoading: false,
      });
    }
  },

  selectProperty: (property) => {
    set({ selectedProperty: property });
  },

  uploadVideo: async (propertyId, videoUri) => {
    set({ isLoading: true, error: null });
    try {
      await propertyService.uploadVideo(propertyId, videoUri);
      set({ isLoading: false });
    } catch (error: any) {
      set({
        error: error.message,
        isLoading: false,
      });
      throw error;
    }
  },

  clearError: () => set({ error: null }),
}));

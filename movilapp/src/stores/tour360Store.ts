import { create } from 'zustand';
import { Tour360 } from '@/types/tour360';
import { tour360Service } from '@/services/tour360.service';

interface Tour360StoreState {
  tours: Tour360[];
  myTours: Tour360[];
  selectedTour: Tour360 | null;
  isLoading: boolean;
  error: string | null;

  fetchTours: (page?: number) => Promise<void>;
  fetchMyTours: () => Promise<void>;
  getTourBySlug: (slug: string) => Promise<Tour360>;
  selectTour: (tour: Tour360) => void;
  deleteTour: (tourId: number) => Promise<void>;
  clearError: () => void;
}

export const useTour360Store = create<Tour360StoreState>((set) => ({
  tours: [],
  myTours: [],
  selectedTour: null,
  isLoading: false,
  error: null,

  fetchTours: async (page = 1) => {
    set({ isLoading: true, error: null });
    try {
      const result = await tour360Service.getTours(page);
      set({
        tours: result.data || [],
        isLoading: false,
      });
    } catch (error: any) {
      set({
        error: error.message,
        isLoading: false,
      });
    }
  },

  fetchMyTours: async () => {
    set({ isLoading: true, error: null });
    try {
      const tours = await tour360Service.getMyTours();
      set({
        myTours: tours,
        isLoading: false,
      });
    } catch (error: any) {
      set({
        error: error.message,
        isLoading: false,
      });
    }
  },

  getTourBySlug: async (slug: string) => {
    set({ isLoading: true, error: null });
    try {
      const tour = await tour360Service.getTour(slug);
      set({ selectedTour: tour, isLoading: false });
      return tour;
    } catch (error: any) {
      set({
        error: error.message,
        isLoading: false,
      });
      throw error;
    }
  },

  selectTour: (tour) => {
    set({ selectedTour: tour });
  },

  deleteTour: async (tourId) => {
    set({ isLoading: true, error: null });
    try {
      await tour360Service.deleteTour(tourId);
      set((state) => ({
        myTours: state.myTours.filter((t) => t.id !== tourId),
        isLoading: false,
      }));
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

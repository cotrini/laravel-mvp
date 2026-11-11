import { create } from 'zustand';
import { VideoStoryboard, VideoScene, VideoFormat } from '@/types/video';
import { DEFAULT_STORYBOARD_HORIZONTAL, DEFAULT_STORYBOARD_VERTICAL } from '@/types/video';

interface VideoStoreState {
  currentStoryboard: VideoStoryboard | null;
  selectedFormat: VideoFormat;
  currentSceneIndex: number;
  isRecording: boolean;

  createStoryboard: (propertyId: number, format: VideoFormat) => void;
  updateScene: (sceneId: string, videoUri: string, duration: number) => void;
  nextScene: () => void;
  previousScene: () => void;
  setIsRecording: (recording: boolean) => void;
  reset: () => void;
}

export const useVideoStore = create<VideoStoreState>((set, get) => ({
  currentStoryboard: null,
  selectedFormat: 'horizontal',
  currentSceneIndex: 0,
  isRecording: false,

  createStoryboard: (propertyId, format) => {
    const scenes = format === 'horizontal'
      ? DEFAULT_STORYBOARD_HORIZONTAL
      : DEFAULT_STORYBOARD_VERTICAL;

    const totalDuration = scenes.reduce(
      (sum: number, scene: VideoScene) => sum + scene.recommendedDuration,
      0
    );

    set({
      currentStoryboard: {
        id: `storyboard-${Date.now()}`,
        propertyId,
        format,
        totalDuration,
        scenes,
      },
      selectedFormat: format,
      currentSceneIndex: 0,
    });
  },

  updateScene: (sceneId, videoUri, duration) => {
    set((state) => {
      if (!state.currentStoryboard) return state;

      const updatedScenes = state.currentStoryboard.scenes.map((scene) =>
        scene.id === sceneId
          ? { ...scene, videoUri, duration }
          : scene
      );

      return {
        currentStoryboard: {
          ...state.currentStoryboard,
          scenes: updatedScenes,
        },
      };
    });
  },

  nextScene: () => {
    set((state) => {
      if (!state.currentStoryboard) return state;
      return {
        currentSceneIndex: Math.min(
          state.currentSceneIndex + 1,
          state.currentStoryboard.scenes.length - 1
        ),
      };
    });
  },

  previousScene: () => {
    set((state) => ({
      currentSceneIndex: Math.max(state.currentSceneIndex - 1, 0),
    }));
  },

  setIsRecording: (recording) => set({ isRecording: recording }),

  reset: () => {
    set({
      currentStoryboard: null,
      currentSceneIndex: 0,
      isRecording: false,
    });
  },
}));

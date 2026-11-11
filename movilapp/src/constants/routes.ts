// Rutas de navegación constantes
export const ROUTES = {
  AUTH: {
    LOGIN: '/(auth)/login',
    PHONE_VERIFY: '/(auth)/phone-verify',
  },
  APP: {
    HOME: '/(app)/home',
    PROPERTIES: '/(app)/properties',
    PROPERTY_DETAIL: (id: number) => `/(app)/properties/${id}`,
    TOUR_360: (id: number) => `/(app)/properties/${id}/tour360`,
    MY_PROPERTIES: '/(app)/my-properties',
    CREATE_PROPERTY: '/(app)/create-property',
    TOUR_360_GUIDED: '/(app)/create-property/360-guided',
    VIDEO_FORMAT_SELECT: '/(app)/create-property/commercial-video/format-select',
    VIDEO_STORYBOARD: '/(app)/create-property/commercial-video/storyboard',
    VIDEO_RECORDING: '/(app)/create-property/commercial-video/recording',
    VIDEO_EDITOR: '/(app)/create-property/commercial-video/editor',
    PROFILE: '/(app)/profile',
    SETTINGS: '/(app)/settings',
  },
};

// Paleta extraída de backend/resources/views/layouts/app.blade.php y welcome.blade.php.
export const theme = {
  colors: {
    pageBg: '#f8f8f8',
    ink: '#242424',
    muted: '#717171',
    white: '#ffffff',
    yellow: '#ffe22d',
    yellowButton: '#ffdf24',
    yellowButtonHover: '#f4d116',
    teal: '#159a8c',
    tealHover: '#12877b',
    danger: '#dc2626',
    border: 'rgba(36, 36, 36, 0.08)',
  },
  radius: { card: 14, section: 20, pill: 999 },
};

// Tema de colores (legado; las pantallas nuevas usan `theme`)
export const COLORS = {
  PRIMARY: '#FF5722', // Orange
  PRIMARY_LIGHT: '#FFE0D0',
  SECONDARY: '#2196F3', // Blue
  SECONDARY_LIGHT: '#E3F2FD',
  SUCCESS: '#4CAF50', // Green
  WARNING: '#FFC107', // Amber
  DANGER: '#f44336', // Red
  DARK: '#333',
  LIGHT: '#f5f5f5',
  BORDER: '#ddd',
  PLACEHOLDER: '#999',
};

// Tamaños de fuente
export const FONT_SIZES = {
  XS: 10,
  SM: 12,
  BASE: 14,
  LG: 16,
  XL: 18,
  '2XL': 20,
  '3XL': 24,
  '4XL': 28,
};

// Espaciado
export const SPACING = {
  XS: 4,
  SM: 8,
  MD: 12,
  LG: 16,
  XL: 20,
  '2XL': 24,
  '3XL': 32,
};

// Radios de borde
export const BORDER_RADIUS = {
  SM: 4,
  MD: 8,
  LG: 12,
  XL: 16,
  FULL: 9999,
};

// Sombras
export const SHADOWS = {
  SM: {
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 1,
  },
  MD: {
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  LG: {
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.15,
    shadowRadius: 8,
    elevation: 5,
  },
};

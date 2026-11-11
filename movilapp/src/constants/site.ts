// Valores tomados de backend/app/Http/Controllers/HomeController.php y welcome.blade.php.

export const WHATSAPP_NUMBER = '573113611426';

export const WHATSAPP_MESSAGES = {
  financing:
    'Hola, quiero recibir asesoría sobre opciones de financiación para comprar un inmueble.',
  publish:
    'Hola, quiero asesoría para publicar mi inmueble y conocer el servicio de fotos, videos y recorridos 360°.',
};

export type Accent = 'aqua' | 'rose' | 'yellow' | 'lavender' | 'orange' | 'teal';

export const ACCENTS: Record<Accent, { background: string; border: string }> = {
  aqua: { background: '#edfafd', border: '#89d9e8' },
  rose: { background: '#fff2f7', border: '#f2b7ce' },
  yellow: { background: '#fffbea', border: '#f7d85c' },
  lavender: { background: '#f7f3fc', border: '#c7b1e6' },
  orange: { background: '#fff4ed', border: '#ff9a5f' },
  teal: { background: '#effaf8', border: '#8dd8cc' },
};

// `types` agrupa los valores de property_type que el web muestra juntos.
export interface PropertyCategory {
  key: string;
  label: string;
  title: string;
  accent: Accent;
  types: string[];
}

export const PROPERTY_CATEGORIES: PropertyCategory[] = [
  { key: 'apartamento', label: 'Apartamentos', title: 'Apartamentos', accent: 'aqua', types: ['apartamento'] },
  { key: 'casa', label: 'Casas', title: 'Casas', accent: 'rose', types: ['casa'] },
  { key: 'finca', label: 'Fincas', title: 'Fincas y casas campestres', accent: 'yellow', types: ['finca'] },
  { key: 'lote', label: 'Lotes', title: 'Lotes', accent: 'lavender', types: ['lote'] },
  { key: 'oficina', label: 'Oficinas', title: 'Oficinas', accent: 'orange', types: ['oficina'] },
  { key: 'comercial', label: 'Comerciales', title: 'Propiedades comerciales', accent: 'lavender', types: ['comercial', 'local'] },
  { key: 'bodega', label: 'Bodegas', title: 'Bodegas', accent: 'yellow', types: ['bodega'] },
];

export const PROPERTY_TYPE_OPTIONS = [
  { value: 'apartamento', label: 'Apartamento' },
  { value: 'casa', label: 'Casa' },
  { value: 'finca', label: 'Finca' },
  { value: 'lote', label: 'Lote' },
  { value: 'oficina', label: 'Oficina' },
  { value: 'local', label: 'Local' },
  { value: 'comercial', label: 'Comercial' },
  { value: 'bodega', label: 'Bodega' },
];

export const LISTING_TYPE_OPTIONS = [
  { value: 'venta', label: 'Venta' },
  { value: 'alquiler', label: 'Alquiler' },
];

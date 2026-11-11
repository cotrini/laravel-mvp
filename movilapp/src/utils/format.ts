import { Property } from '@/types/property';
import { API_ORIGIN } from '@/constants/api.config';

const LOCAL_HOST_PREFIX = /^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?/;

// APP_URL del backend apunta a localhost, que el teléfono no alcanza: se usa el host de la API.
export function resolveMediaUrl(url: string): string {
  return LOCAL_HOST_PREFIX.test(url) ? url.replace(LOCAL_HOST_PREFIX, API_ORIGIN) : url;
}

// Equivalente a number_format($value, 0, ',', '.') del Blade. La API envía precios decimales como texto.
export function formatCOP(value: number | string | null | undefined): string {
  if (value === null || value === undefined || value === '') return '';
  const amount = Number(value);
  if (Number.isNaN(amount)) return '';
  return Math.round(amount)
    .toString()
    .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// Imagen principal: la marcada como primaria, o la primera imagen disponible.
export function getCoverUrl(property: Property): string | null {
  const images = property.media.filter((m) => m.type === 'image');
  const cover = images.find((m) => m.is_primary) ?? images[0];
  return cover?.url ?? null;
}

export function getLocationLabel(property: Property): string {
  const place = [property.neighborhood, property.city].filter(Boolean).join(', ');
  return [place, property.department].filter(Boolean).join(', ');
}

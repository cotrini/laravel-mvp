import React from 'react';
import Svg, { Path } from 'react-native-svg';

export type CategoryIconName =
  | 'todos'
  | 'apartamento'
  | 'casa'
  | 'finca'
  | 'lote'
  | 'oficina'
  | 'comercial'
  | 'bodega';

// Mismos trazos (viewBox 24x24, trazo 1.7) que la barra de categorías de backend/resources/views/layouts/app.blade.php.
const ICON_PATHS: Record<CategoryIconName, string[]> = {
  todos: ['m3 10 9-7 9 7', 'M5 9v11h14V9', 'M9 20v-6h6v6'],
  apartamento: [
    'M4 21V5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v16',
    'M17 9h3v12',
    'M2 21h20',
    'M8 7h1m4 0h1M8 11h1m4 0h1M8 15h1m4 0h1',
  ],
  casa: ['m3 10 9-7 9 7', 'M5 9v11h14V9', 'M9 20v-6h6v6'],
  finca: ['M3 20h18', 'M5 20V9l7-5 7 5v11', 'M9 20v-5h6v5', 'M3 11h2m14 0h2'],
  lote: ['M4 19 7 6l10-2 3 14-9 3-7-2Z', 'm7 6 4 15m6-17-1 16'],
  oficina: [
    'M4 21V5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v16',
    'M17 9h3v12',
    'M2 21h20',
    'M8 7h1m4 0h1M8 11h1m4 0h1M8 15h1m4 0h1',
  ],
  comercial: [
    'M3 10h18l-1.5-6h-15L3 10Z',
    'M5 10v11h14V10',
    'M9 21v-7h6v7',
    'M3 10a2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0',
  ],
  bodega: ['M3 21V9l9-6 9 6v12', 'M3 10h18', 'M7 21v-7h10v7', 'M10 14v7m4-7v7'],
};

interface CategoryIconProps {
  name: CategoryIconName;
  size?: number;
  color?: string;
}

export function CategoryIcon({ name, size = 19, color = '#666' }: CategoryIconProps) {
  return (
    <Svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke={color}
      strokeWidth={1.7}
      strokeLinecap="round"
      strokeLinejoin="round"
    >
      {ICON_PATHS[name].map((d) => (
        <Path key={d} d={d} />
      ))}
    </Svg>
  );
}

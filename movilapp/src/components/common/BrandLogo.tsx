import React from 'react';
import Svg, { Path, Rect } from 'react-native-svg';

interface BrandLogoProps {
  size?: number;
}

// Mismo diseño que backend/public/favicon.svg (viewBox 48x48).
export function BrandLogo({ size = 38 }: BrandLogoProps) {
  return (
    <Svg width={size} height={size} viewBox="0 0 48 48">
      <Rect width={48} height={48} rx={14} fill="#ffe22d" />
      <Path
        d="m9 22 15-12 15 12M13 20v17h22V20M20 37V26h8v11"
        fill="none"
        stroke="#252525"
        strokeLinecap="round"
        strokeLinejoin="round"
        strokeWidth={3}
      />
    </Svg>
  );
}

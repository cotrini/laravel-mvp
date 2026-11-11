// Forma exacta que devuelve PropertyApiController::propertyData (backend).
export interface PropertyMedia {
  id: number;
  type: 'image' | 'video' | string;
  title: string | null;
  url: string;
  is_primary: boolean;
  order: number;
}

export interface PropertyTourSummary {
  id: number;
  slug: string;
  title: string;
  description: string | null;
  location: string | null;
  cover_url: string | null;
  scenes: unknown[];
}

export interface Property {
  id: number;
  identification_code: string | null;
  title: string;
  description: string | null;
  price: number | string;
  property_type: string;
  listing_type: 'venta' | 'alquiler' | string;
  status: string;
  is_active: boolean;
  is_verified: boolean;
  department: string;
  city: string;
  neighborhood: string | null;
  address: string | null;
  latitude: number | null;
  longitude: number | null;
  bedrooms: number | null;
  bathrooms: number | null;
  half_bathrooms: number | null;
  parking_spaces: number | null;
  built_area_m2: number | null;
  lot_area_m2: number | null;
  floor_number: number | null;
  total_floors: number | null;
  stratum: number | null;
  year_built: number | null;
  administration_fee: number | null;
  is_furnished: boolean;
  has_elevator: boolean;
  media: PropertyMedia[];
  tour: PropertyTourSummary | null;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface PropertyList {
  data: Property[];
  meta: PaginationMeta;
}

export interface PropertyFilters {
  destination?: string;
  property_type?: string;
  listing_type?: 'venta' | 'alquiler';
  min_price?: number;
  max_price?: number;
  bedrooms?: number;
  bathrooms?: number;
  stratum?: number;
  per_page?: number;
  page?: number;
}

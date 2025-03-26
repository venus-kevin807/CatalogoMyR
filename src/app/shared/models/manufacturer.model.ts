export interface Manufacturer {
  id: number;
  name: string;
  short_name?: string;
  description?: string;
  logo_path?: string;
  is_active?: boolean;
}

export interface ManufacturersResponse {
  manufacturers: Manufacturer[];
}

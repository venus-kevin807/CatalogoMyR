// Models for sidebar component
export interface Category {
  id: number;
  name: string;
  subcategories: Subcategory[];
  showSubcategories: boolean;
  description?: string;
  isManufacturerSpecific?: boolean;
}

export interface Subcategory {
  id: number;
  name: string;
  category_id: number;
  description?: string;
}

export interface Manufacturer {
  id: number;
  name: string;
}

// API response interfaces
export interface CategoriesResponse {
  categorias: {
    id: number;
    nombre: string;
    descripcion: string;
    activo: number;
  }[];
}

export interface SubcategoriesResponse {
  subcategorias: {
    id: number;
    categoria_id: number;
    nombre: string;
    descripcion: string;
    activo: number;
  }[];
}

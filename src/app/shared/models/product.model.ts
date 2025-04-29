export interface Product {
  id_repuesto: number;
  str_referencia: string;
  nombre: string;
  imagen_url?: string ;
  descripcion?: string;
  precio: number;
  stock: number;
  id_proveedor?: number | null;
  id_categoria: number;
  id_subcategoria?: number | null;
  id_fabricante: number;
  categoria_nombre?: string;
  fabricante_nombre?: string;
}

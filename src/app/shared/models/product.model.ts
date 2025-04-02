export interface Product {
  id_repuesto: number;
  str_referencia: string;
  nombre: string;
  imagen_url?: string ; // Base64 encoded image
  descripcion?: string;
  precio: number;
  stock: number;
  id_proveedor?: number | null; // Ahora es opcional
  id_categoria: number;
  id_subcategoria?: number | null; // Nuevo campo
  id_fabricante: number;
  categoria_nombre?: string;
  fabricante_nombre?: string;
}

import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { catchError, map } from 'rxjs/operators';
import { Product } from '../../shared/models/product.model';
import { DomSanitizer, SafeUrl } from '@angular/platform-browser';

@Injectable({
  providedIn: 'root'
})
export class ProductService {
  private apiUrl = 'http://localhost:8080/repuestos-api/repuestos.php';
  public defaultImage = 'assets/images/default-repuesto.png';

  constructor(private http: HttpClient, private sanitizer: DomSanitizer) {}

// product.service.ts (additions to your existing service)
getProducts(filters: {
  id_categoria?: number,
  id_subcategoria?: number,
  id_fabricante?: number,
  id_repuesto?: number,
  page?: number,
  perPage?: number
} = {}): Observable<{products: Product[], total: number}> {
  let params = new HttpParams();

  // Add existing filter parameters
  if (filters.id_categoria) {
    params = params.set('id_categoria', filters.id_categoria.toString());
  }
  if (filters.id_subcategoria) {
    params = params.set('id_subcategoria', filters.id_subcategoria.toString());
  }
  if (filters.id_fabricante) {
    params = params.set('id_fabricante', filters.id_fabricante.toString());
  }
  if (filters.id_repuesto) {
    params = params.set('id_repuesto', filters.id_repuesto.toString());
  }

  // Add pagination parameters
  if (filters.page) {
    params = params.set('page', filters.page.toString());
  }
  if (filters.perPage) {
    params = params.set('per_page', filters.perPage.toString());
  }

  return this.http.get<any>(this.apiUrl, { params }).pipe(
    map(response => {
      if (!response?.success || !Array.isArray(response.data)) {
        console.error('Invalid API response format', response);
        return {products: [], total: 0};
      }
      return {
        products: response.data.map((product: any) => this.processProduct(product)),
        total: response.total || response.data.length // Assuming your API returns total count
      };
    }),
    catchError(error => {
      console.error('Error fetching products', error);
      return of({products: [], total: 0});
    })
  );
}
  addProduct(productData: FormData): Observable<any> {
    return this.http.post(this.apiUrl, productData).pipe(
      catchError(error => {
        console.error('Error adding product', error);
        return of({ success: false, error: 'Error al agregar el repuesto' });
      })
    );
  }

private processProduct(product: any): Product {
    // URL directa al endpoint de imágenes
    if (product.id_repuesto) {
        product.imagen_url = `http://localhost:8080/repuestos-api/get_image.php?id=${product.id_repuesto}`;
    } else {
        product.imagen_url = this.defaultImage;
    }

    return product as Product;
}
}

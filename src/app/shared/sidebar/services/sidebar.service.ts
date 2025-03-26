import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, forkJoin, of, throwError } from 'rxjs';
import { catchError, map, switchMap } from 'rxjs/operators';
import { CatalogService } from '../../../catalog/services/catalog.service';
import { Category, Subcategory, CategoriesResponse, SubcategoriesResponse, Manufacturer } from '../models/sidebar.model';
import { ManufacturersResponse } from '../../models/manufacturer.model';

@Injectable({
  providedIn: 'root'
})
export class SidebarService {
  private apiUrl = 'http://localhost:8080/repuestos-api'; // Ajusta esta URL a la ubicación de tus archivos PHP

  constructor(
    private http: HttpClient,
    private catalogService: CatalogService
  ) { }

  getManufacturers(): Observable<Manufacturer[]> {
    return this.http.get<ManufacturersResponse>(`${this.apiUrl}/manufactures.php`).pipe(
      map(response => response.manufacturers),
      catchError(error => {
        console.error('Error fetching manufacturers:', error);
        return throwError(() => new Error('Error fetching manufacturers'));
      })
    );
  }
  /**
   * Obtiene todas las categorías con sus subcategorías
   */
  getCategories(): Observable<Category[]> {
    return this.http.get<CategoriesResponse>(`${this.apiUrl}/categorias.php`).pipe(
      switchMap(categoriesResponse => {
        // Obtener todas las subcategorías
        return this.http.get<SubcategoriesResponse>(`${this.apiUrl}/subcategorias.php`).pipe(
          map(subcategoriesResponse => {
            // Mapear las categorías y agregar sus subcategorías
            return categoriesResponse.categorias.map(cat => {
              // Encontrar subcategorías para esta categoría
              const subcategories = subcategoriesResponse.subcategorias
                .filter(subcat => subcat.categoria_id === cat.id)
                .map(subcat => ({
                  id: subcat.id,
                  name: subcat.nombre,
                  category_id: subcat.categoria_id,
                  description: subcat.descripcion
                }));

              // Crear objeto de categoría con sus subcategorías
              return {
                id: cat.id,
                name: cat.nombre,
                description: cat.descripcion,
                subcategories: subcategories,
                showSubcategories: false
              };
            });
          })
        );
      }),
      catchError(error => {
        console.error('Error fetching data:', error);
        return throwError(() => new Error('Error fetching data from API'));
      })
    );
  }

  /**
   * Obtiene subcategorías por ID de categoría
   */
  getSubcategoriesByCategoryId(categoryId: number): Observable<Subcategory[]> {
    return this.http.get<SubcategoriesResponse>(`${this.apiUrl}/subcategorias.php?categoria_id=${categoryId}`).pipe(
      map(response =>
        response.subcategorias.map(subcat => ({
          id: subcat.id,
          name: subcat.nombre,
          category_id: subcat.categoria_id,
          description: subcat.descripcion
        }))
      ),
      catchError(error => {
        console.error('Error fetching subcategories:', error);
        return throwError(() => new Error('Error fetching subcategories'));
      })
    );
  }

  /**
   * Selecciona una categoría para filtrar el catálogo
   */
  selectCategory(categoryId: number): void {
    this.catalogService.setSelectedCategory(categoryId);
  }

  /**
   * Selecciona una subcategoría para filtrar el catálogo
   */
  selectSubcategory(categoryId: number, subcategoryId: number, subcategoryName: string): void {
    this.catalogService.setSelectedCategory(categoryId);
    this.catalogService.setSelectedSubcategory(subcategoryName);

    // También puedes almacenar el ID de la subcategoría si necesitas usarlo
    // Por ejemplo, podrías crear un nuevo método en catalogService:
    // this.catalogService.setSelectedSubcategoryId(subcategoryId);
  }

  /**
   * Selecciona un fabricante para filtrar el catálogo
   */
  selectManufacturer(manufacturerId: number): void {
    this.catalogService.setSelectedManufacturer(manufacturerId);
  }

  /**
   * Limpia todos los filtros aplicados
   */
  clearFilters(): void {
    this.catalogService.clearFilters();
  }
}

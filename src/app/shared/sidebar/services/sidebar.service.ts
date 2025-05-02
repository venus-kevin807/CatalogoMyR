import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, throwError } from 'rxjs';
import { catchError, map, switchMap } from 'rxjs/operators';
import { CatalogService } from '../../../catalog/services/catalog.service';
import { Category, CategoriesResponse, SubcategoriesResponse, Subcategory } from '../models/sidebar.model';
import { Manufacturer, ManufacturersResponse } from '../../models/manufacturer.model';

@Injectable({
  providedIn: 'root'
})
export class SidebarService {
  private apiUrl = 'https://montacargasyrepuestossas.com/api';

  private sidebarOpenSource = new BehaviorSubject<boolean>(false);
  sidebarOpen$ = this.sidebarOpenSource.asObservable();

  constructor(
    private http: HttpClient,
    private catalogService: CatalogService
  ) {}

  toggleSidebar(open?: boolean): void {
    if (open !== undefined) {
      this.sidebarOpenSource.next(open);
    } else {
      this.sidebarOpenSource.next(!this.sidebarOpenSource.value);
    }
  }

  getManufacturers(): Observable<Manufacturer[]> {
    return this.http.get<ManufacturersResponse>(`${this.apiUrl}/manufactures.php`).pipe(
      map(response => response.manufacturers),
      catchError(error => {
        console.error('Error fetching manufacturers:', error);
        return throwError(() => new Error('Error fetching manufacturers'));
      })
    );
  }

  getCategories(): Observable<Category[]> {
    return this.http.get<CategoriesResponse>(`${this.apiUrl}/categorias.php`).pipe(
      switchMap(categoriesResponse => {
        return this.http.get<SubcategoriesResponse>(`${this.apiUrl}/subcategorias_c.php`).pipe(
          map(subcategoriesResponse => {
            return categoriesResponse.categorias.map(cat => {
              const subcategories = subcategoriesResponse.subcategorias
                .filter(subcat => subcat.categoria_id === cat.id)
                .map(subcat => ({
                  id: subcat.id,
                  name: subcat.nombre,
                  category_id: subcat.categoria_id,
                  description: subcat.descripcion
                }));
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

  selectCategory(categoryId: number): void {
    this.catalogService.setSelectedCategory(categoryId);
  }
  selectSubcategory(categoryId: number, subcategoryId: number, subcategoryName: string): void {
    this.catalogService.setSelectedCategory(categoryId);
    this.catalogService.setSelectedSubcategoryId(subcategoryId);
  }

  private filtersCleared = new BehaviorSubject<boolean>(false);
  filtersCleared$ = this.filtersCleared.asObservable();
  notifyFiltersClear(): void {
    this.filtersCleared.next(true);
    setTimeout(() => {
      this.filtersCleared.next(false);
    }, 100);
  }
  selectManufacturer(manufacturerId: number | null): void {
    this.catalogService.setSelectedManufacturer(manufacturerId);
  }
  clearFilters(): void {
    this.catalogService.clearFilters();
  }
}

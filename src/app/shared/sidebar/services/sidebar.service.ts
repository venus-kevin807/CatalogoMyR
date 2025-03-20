import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';
import { Category } from '../../models/category.model';
import { Location } from '../../models/location.model';

@Injectable({
  providedIn: 'root'
})
export class SidebarService {
  // Estado para la marca seleccionada
  private selectedBrandSubject = new BehaviorSubject<string | null>(null);
  selectedBrand$ = this.selectedBrandSubject.asObservable();

  // Categorías predefinidas
  private categoriesSubject = new BehaviorSubject<Category[]>([
    { id: 1, name: 'Dirección', icon: 'steering_wheel' },
    { id: 2, name: 'Frenos', icon: 'brake' },
    { id: 3, name: 'Filtros', icon: 'filter' },
    { id: 4, name: 'Motor', icon: 'engine' },
    { id: 5, name: 'Suspensión', icon: 'suspension' }
  ]);
  categories$ = this.categoriesSubject.asObservable();

  // Ubicaciones
  private locationsSubject = new BehaviorSubject<Location[]>([
    { id: 1, name: 'Almacén Central' },
    { id: 2, name: 'Sucursal Norte' },
    { id: 3, name: 'Sucursal Sur' }
  ]);
  locations$ = this.locationsSubject.asObservable();

  constructor() { }

  selectBrand(brand: string | null): void {
    this.selectedBrandSubject.next(brand);
  }

  getCategoriesByBrand(brandId: string): Observable<Category[]> {
    // Aquí iría la lógica para filtrar categorías por marca
    // Por ahora devolvemos las mismas categorías
    return this.categories$;
  }
}

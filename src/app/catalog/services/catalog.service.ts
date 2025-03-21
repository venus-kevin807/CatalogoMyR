import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class CatalogService {
  private selectedCategorySubject = new BehaviorSubject<number | null>(null);
  selectedCategory$: Observable<number | null> = this.selectedCategorySubject.asObservable();

  private selectedSubcategorySubject = new BehaviorSubject<string | null>(null);
  selectedSubcategory$: Observable<string | null> = this.selectedSubcategorySubject.asObservable();

  private selectedManufacturerSubject = new BehaviorSubject<number | null>(null);
  selectedManufacturer$: Observable<number | null> = this.selectedManufacturerSubject.asObservable();

  constructor() { }

  setSelectedCategory(categoryId: number | null): void {
    this.selectedCategorySubject.next(categoryId);
  }

  setSelectedSubcategory(subcategory: string | null): void {
    this.selectedSubcategorySubject.next(subcategory);
  }

  setSelectedManufacturer(manufacturerId: number | null): void {
    this.selectedManufacturerSubject.next(manufacturerId);
  }

  clearFilters(): void {
    this.selectedCategorySubject.next(null);
    this.selectedSubcategorySubject.next(null);
    this.selectedManufacturerSubject.next(null);
  }
}

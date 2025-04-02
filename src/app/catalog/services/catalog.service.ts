import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class CatalogService {
  private selectedCategorySubject = new BehaviorSubject<number | null>(null);
  selectedCategory$: Observable<number | null> = this.selectedCategorySubject.asObservable();

  private selectedSubcategoryIdSubject = new BehaviorSubject<number | null>(null);
  selectedSubcategoryId$: Observable<number | null> = this.selectedSubcategoryIdSubject.asObservable();

  setSelectedSubcategoryId(subcategoryId: number | null): void {
    this.selectedSubcategoryIdSubject.next(subcategoryId);
  }
  private selectedManufacturerSubject = new BehaviorSubject<number | null>(null);
  selectedManufacturer$: Observable<number | null> = this.selectedManufacturerSubject.asObservable();

  constructor() { }

  setSelectedCategory(categoryId: number | null): void {
    this.selectedCategorySubject.next(categoryId);
  }


  setSelectedManufacturer(manufacturerId: number | null): void {
    this.selectedManufacturerSubject.next(manufacturerId);
  }

  clearFilters(): void {
    this.selectedCategorySubject.next(null);
    this.selectedSubcategoryIdSubject.next(null);
    this.selectedManufacturerSubject.next(null);
  }
}

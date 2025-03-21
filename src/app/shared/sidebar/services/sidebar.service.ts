import { Injectable } from '@angular/core';
import { CatalogService } from '../../../catalog/services/catalog.service';

@Injectable({
  providedIn: 'root'
})
export class SidebarService {
  constructor(private catalogService: CatalogService) { }

  selectCategory(categoryId: number): void {
    this.catalogService.setSelectedCategory(categoryId);
  }

  selectSubcategory(categoryId: number, subcategory: string): void {
    this.catalogService.setSelectedCategory(categoryId);
    this.catalogService.setSelectedSubcategory(subcategory);
  }

  selectManufacturer(manufacturerId: number): void {
    this.catalogService.setSelectedManufacturer(manufacturerId);
  }

  clearFilters(): void {
    this.catalogService.clearFilters();
  }
}

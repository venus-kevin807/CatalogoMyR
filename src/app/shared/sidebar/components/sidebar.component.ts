import { Component, OnInit } from '@angular/core';
import { SidebarService } from './../services/sidebar.service';
import { Category, Manufacturer } from './../models/sidebar.model';

@Component({
  selector: 'app-sidebar',
  templateUrl: './sidebar.component.html',
  styleUrls: ['./sidebar.component.css']
})
export class SidebarComponent implements OnInit {
  categories: Category[] = [];
  manufacturers: Manufacturer[] = [];
  loading = true;
  error: string | null = null;

  constructor(private sidebarService: SidebarService) { }

  ngOnInit(): void {
    this.loadCategories();
    this.loadManufacturers();
  }

  isSidebarOpen = false;

  toggleSidebar(): void {
    this.isSidebarOpen = !this.isSidebarOpen;
  }

  loadCategories(): void {
    this.loading = true;
    this.error = null;

    this.sidebarService.getCategories().subscribe({
      next: (categories) => {
        this.categories = categories;
        this.loading = false;
      },
      error: (err) => {
        console.error('Error cargando categorías:', err);
        this.error = 'No se pudieron cargar las categorías. Intente nuevamente más tarde.';
        this.loading = false;
      }
    });
  }

  loadManufacturers(): void {
    this.sidebarService.getManufacturers().subscribe({
      next: (manufacturers) => {
        this.manufacturers = manufacturers;
      },
      error: (err) => {
        console.error('Error cargando fabricantes:', err);
        // Error handling is done in the service with fallback data
      }
    });
  }

  // Toggle visibility of subcategories
  toggleSubcategories(category: Category): void {
    category.showSubcategories = !category.showSubcategories;
  }

  // Select a category and filter catalog
  selectCategory(categoryId: number): void {
    this.sidebarService.selectCategory(categoryId);
  }

  // Select a subcategory and filter catalog
  selectSubcategory(categoryId: number, subcategoryId: number, subcategoryName: string): void {
    this.sidebarService.selectSubcategory(categoryId, subcategoryId, subcategoryName);
  }

  // Select a manufacturer and filter catalog
  selectManufacturer(manufacturerId: number): void {
    this.sidebarService.selectManufacturer(manufacturerId);
  }

  // Clear all filters
  clearFilters(): void {
    this.sidebarService.clearFilters();
  }

  // Retry loading data from API
  retryLoading(): void {
    this.loadCategories();
  }
}

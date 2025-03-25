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
  manufacturers: Manufacturer[] = [
    { id: 1, name: 'Toyota' },
    { id: 2, name: 'Mitsubishi' },
    { id: 3, name: 'Nissan' },
    { id: 4, name: 'Heli' },
    { id: 5, name: 'Hangcha' },
    { id: 6, name: 'Tailift' }
  ];
  loading = true;
  error: string | null = null;

  constructor(private sidebarService: SidebarService) { }

  ngOnInit(): void {
    this.loadCategories();
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

        // Fallback a datos dummy en caso de error
        this.useDummyData();
      }
    });
  }

  // Cargar datos dummy en caso de que falle la API
  useDummyData(): void {
    this.categories = [
      {
        id: 1,
        name: 'Dirección',
        subcategories: [
          { id: 1, name: 'Cremalleras', category_id: 1 },
          { id: 2, name: 'Bombas', category_id: 1 },
          { id: 3, name: 'Terminales', category_id: 1 },
          { id: 4, name: 'Barras', category_id: 1 }
        ],
        showSubcategories: false
      },
      {
        id: 2,
        name: 'Filtros',
        subcategories: [
          { id: 5, name: 'Aceite', category_id: 2 },
          { id: 6, name: 'Aire', category_id: 2 },
          { id: 7, name: 'Combustible', category_id: 2 },
          { id: 8, name: 'Hidráulico', category_id: 2 }
        ],
        showSubcategories: false
      },
      {
        id: 3,
        name: 'Frenos',
        subcategories: [
          { id: 9, name: 'Pastillas', category_id: 3 },
          { id: 10, name: 'Discos', category_id: 3 },
          { id: 11, name: 'Bombas', category_id: 3 },
          { id: 12, name: 'Zapatas', category_id: 3 }
        ],
        showSubcategories: false
      },
      {
        id: 4,
        name: 'Suspensión',
        subcategories: [
          { id: 13, name: 'Amortiguadores', category_id: 4 },
          { id: 14, name: 'Espirales', category_id: 4 },
          { id: 15, name: 'Bujes', category_id: 4 },
          { id: 16, name: 'Bandejas', category_id: 4 }
        ],
        showSubcategories: false
      },
      {
        id: 5,
        name: 'Eléctricos',
        subcategories: [
          { id: 17, name: 'Alternadores', category_id: 5 },
          { id: 18, name: 'Arranques', category_id: 5 },
          { id: 19, name: 'Fusibles', category_id: 5 },
          { id: 20, name: 'Baterías', category_id: 5 }
        ],
        showSubcategories: false
      }
    ];
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

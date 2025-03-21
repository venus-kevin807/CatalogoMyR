import { Component, OnInit } from '@angular/core';
import { SidebarService } from './../services/sidebar.service';

@Component({
  selector: 'app-sidebar',
  templateUrl: './sidebar.component.html',
  styleUrls: ['./sidebar.component.css']
})
export class SidebarComponent implements OnInit {
  // Categories with their subcategories
  categories = [
    {
      id: 1,
      name: 'Dirección',
      subcategories: ['Cremalleras', 'Bombas', 'Terminales', 'Barras'],
      showSubcategories: false
    },
    {
      id: 2,
      name: 'Filtros',
      subcategories: ['Aceite', 'Aire', 'Combustible', 'Hidráulico'],
      showSubcategories: false
    },
    {
      id: 3,
      name: 'Frenos',
      subcategories: ['Pastillas', 'Discos', 'Bombas', 'Zapatas'],
      showSubcategories: false
    },
    {
      id: 4,
      name: 'Suspensión',
      subcategories: ['Amortiguadores', 'Espirales', 'Bujes', 'Bandejas'],
      showSubcategories: false
    },
    {
      id: 5,
      name: 'Eléctricos',
      subcategories: ['Alternadores', 'Arranques', 'Fusibles', 'Baterías'],
      showSubcategories: false
    },
  ];

  // Manufacturers with IDs
  manufacturers = [
    { id: 1, name: 'Toyota' },
    { id: 2, name: 'Mitsubishi' },
    { id: 3, name: 'Nissan' },
    { id: 4, name: 'Heli' },
    { id: 5, name: 'Hangcha' },
    { id: 6, name: 'Tailift' }
  ];

  constructor(private sidebarService: SidebarService) { }

  ngOnInit(): void {
  }

  // Toggle visibility of subcategories
  toggleSubcategories(category: any): void {
    category.showSubcategories = !category.showSubcategories;
  }

  // Select a category and filter catalog
  selectCategory(categoryId: number): void {
    this.sidebarService.selectCategory(categoryId);
  }

  // Select a subcategory and filter catalog
  selectSubcategory(categoryId: number, subcategory: string): void {
    this.sidebarService.selectSubcategory(categoryId, subcategory);
  }

  // Select a manufacturer and filter catalog
  selectManufacturer(manufacturerId: number): void {
    this.sidebarService.selectManufacturer(manufacturerId);
  }

  // Clear all filters
  clearFilters(): void {
    this.sidebarService.clearFilters();
  }
}

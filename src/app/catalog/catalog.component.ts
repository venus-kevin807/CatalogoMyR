import { Component, OnInit, OnDestroy } from '@angular/core';
import { CatalogService } from './services/catalog.service';
import { SidebarService } from '../shared/sidebar/services/sidebar.service';
import { Subscription, combineLatest } from 'rxjs';
import { Manufacturer } from '../shared/models/manufacturer.model';

@Component({
  selector: 'app-catalog',
  templateUrl: './catalog.component.html',
  styleUrls: ['./catalog.component.css']
})
export class CatalogComponent implements OnInit, OnDestroy {
  selectedCategoryId: number | null = null;
  selectedSubcategory: string | null = null;
  selectedManufacturerId: number | null = null;

  categoryNames: { [key: number]: string } = {
    1: 'Dirección',
    2: 'Filtros',
    3: 'Frenos',
    4: 'Suspensión',
    5: 'Eléctricos'
  };

  manufacturerNames: { [key: number]: string } = {};
  manufacturers: Manufacturer[] = [];

  allProducts: any[] = [];
  filteredProducts: any[] = [];

  private subscriptions: Subscription[] = [];

  constructor(
    private catalogService: CatalogService,
    private sidebarService: SidebarService
  ) {}

  ngOnInit(): void {
    // Load manufacturers first
    this.loadManufacturers();

    // Load products
    this.loadProducts();

    // Subscribe to filter changes
    this.subscriptions.push(
      combineLatest([
        this.catalogService.selectedCategory$,
        this.catalogService.selectedSubcategory$,
        this.catalogService.selectedManufacturer$
      ]).subscribe(([categoryId, subcategory, manufacturerId]) => {
        this.selectedCategoryId = categoryId;
        this.selectedSubcategory = subcategory;
        this.selectedManufacturerId = manufacturerId;
        this.applyFilters();
      })
    );
  }

  ngOnDestroy(): void {
    // Unsubscribe to prevent memory leaks
    this.subscriptions.forEach(sub => sub.unsubscribe());
  }

  private loadManufacturers(): void {
    this.sidebarService.getManufacturers().subscribe(manufacturers => {
      this.manufacturers = manufacturers;

      // Create a mapping of manufacturer IDs to names
      this.manufacturerNames = manufacturers.reduce((acc: { [key: number]: string }, manufacturer) => {
        acc[manufacturer.id] = manufacturer.name;
        return acc;
      }, {});
    });
  }

  private loadProducts(): void {
    // Simulate product loading (in a real app, this would come from a service)
    this.allProducts = [
      { id: 1, name: 'Cremallera de dirección', price: 450000, categoryId: 1, subcategory: 'Cremalleras', manufacturerId: 1 },
      { id: 2, name: 'Bomba de dirección', price: 380000, categoryId: 1, subcategory: 'Bombas', manufacturerId: 2 },
      { id: 3, name: 'Filtro de aceite', price: 25000, categoryId: 2, subcategory: 'Aceite', manufacturerId: 1 },
      { id: 4, name: 'Filtro de aire', price: 35000, categoryId: 2, subcategory: 'Aire', manufacturerId: 3 },
      { id: 5, name: 'Pastillas de freno', price: 65000, categoryId: 3, subcategory: 'Pastillas', manufacturerId: 1 },
      { id: 6, name: 'Amortiguador delantero', price: 180000, categoryId: 4, subcategory: 'Amortiguadores', manufacturerId: 2 },
      { id: 7, name: 'Alternador', price: 320000, categoryId: 5, subcategory: 'Alternadores', manufacturerId: 1 },
      { id: 8, name: 'Batería de montacargas', price: 550000, categoryId: 5, subcategory: 'Baterías', manufacturerId: 4 }
    ];

    // Initially show all products
    this.filteredProducts = [...this.allProducts];
  }

  private applyFilters(): void {
    // Filter products based on selected criteria
    this.filteredProducts = this.allProducts.filter(product => {
      let matchesCategory = true;
      let matchesSubcategory = true;
      let matchesManufacturer = true;

      if (this.selectedCategoryId !== null) {
        matchesCategory = product.categoryId === this.selectedCategoryId;
      }

      if (this.selectedSubcategory !== null) {
        matchesSubcategory = product.subcategory === this.selectedSubcategory;
      }

      if (this.selectedManufacturerId !== null) {
        matchesManufacturer = product.manufacturerId === this.selectedManufacturerId;
      }

      return matchesCategory && matchesSubcategory && matchesManufacturer;
    });
  }

  // Method to get manufacturer name safely
  getManufacturerName(manufacturerId: number): string {
    return this.manufacturerNames[manufacturerId] || 'Fabricante desconocido';
  }

  addToFavorites(productId: number): void {
    // Implement logic to add to favorites
    console.log(`Product ${productId} added to favorites`);
  }
}

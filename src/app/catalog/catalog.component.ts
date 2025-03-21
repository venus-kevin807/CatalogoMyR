import { Component, OnInit, OnDestroy } from '@angular/core';
import { CatalogService } from './services/catalog.service';
import { Subscription, combineLatest } from 'rxjs';

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

  manufacturerNames: { [key: number]: string } = {
    1: 'Toyota',
    2: 'Mitsubishi',
    3: 'Nissan',
    4: 'Heli',
    5: 'Hangcha',
    6: 'Tailift'
  };

  allProducts: any[] = [];
  filteredProducts: any[] = [];

  private subscriptions: Subscription[] = [];

  constructor(private catalogService: CatalogService) {}

  ngOnInit(): void {
    // Cargar productos iniciales (esto podría ser de una API)
    this.loadProducts();

    // Suscribirse a cambios en los filtros
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
    // Desuscribirse para evitar memory leaks
    this.subscriptions.forEach(sub => sub.unsubscribe());
  }

  private loadProducts(): void {
    // Simular carga de productos (en una app real, esto vendría de un servicio)
    this.allProducts = [
      { id: 1, name: 'Cremallera de dirección', price: 450000, categoryId: 1, subcategory: 'Cremalleras', manufacturerId: 1, image: 'assets/images/products/cremallera.jpg' },
      { id: 2, name: 'Bomba de dirección', price: 380000, categoryId: 1, subcategory: 'Bombas', manufacturerId: 2, image: 'assets/images/products/bomba-direccion.jpg' },
      { id: 3, name: 'Filtro de aceite', price: 25000, categoryId: 2, subcategory: 'Aceite', manufacturerId: 1, image: 'assets/images/products/filtro-aceite.jpg' },
      { id: 4, name: 'Filtro de aire', price: 35000, categoryId: 2, subcategory: 'Aire', manufacturerId: 3, image: 'assets/images/products/filtro-aire.jpg' },
      { id: 5, name: 'Pastillas de freno', price: 65000, categoryId: 3, subcategory: 'Pastillas', manufacturerId: 1, image: 'assets/images/products/pastillas.jpg' },
      { id: 6, name: 'Amortiguador delantero', price: 180000, categoryId: 4, subcategory: 'Amortiguadores', manufacturerId: 2, image: 'assets/images/products/amortiguador.jpg' },
      { id: 7, name: 'Alternador', price: 320000, categoryId: 5, subcategory: 'Alternadores', manufacturerId: 1, image: 'assets/images/products/alternador.jpg' },
      { id: 8, name: 'Batería de montacargas', price: 550000, categoryId: 5, subcategory: 'Baterías', manufacturerId: 4, image: 'assets/images/products/bateria.jpg' }
    ];

    // Inicialmente, mostrar todos los productos
    this.filteredProducts = [...this.allProducts];
  }

  private applyFilters(): void {
    // Filtrar productos según los criterios seleccionados
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

  addToFavorites(productId: number): void {
    // Implementar lógica para agregar a favoritos
    console.log(`Producto ${productId} agregado a favoritos`);
  }
}

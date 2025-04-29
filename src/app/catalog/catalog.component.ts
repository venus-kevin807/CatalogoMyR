import { Component, OnInit, OnDestroy } from '@angular/core';
import { CatalogService } from './services/catalog.service';
import { SidebarService } from '../shared/sidebar/services/sidebar.service';
import { Subscription } from 'rxjs';
import { Manufacturer } from '../shared/models/manufacturer.model';
import { Category } from '../shared/sidebar/models/sidebar.model';
import { Product } from '../shared/models/product.model';
import { ProductService } from './services/product.service';
import { CartService } from './services/cart.service';

@Component({
  selector: 'app-catalog',
  templateUrl: './catalog.component.html',
  styleUrls: ['./catalog.component.css']
})
export class CatalogComponent implements OnInit, OnDestroy {
  selectedCategoryId: number | null = null;
  selectedSubcategory: number | null = null;
  selectedSubcategoryName: string | null = null;
  selectedManufacturerId: number | null = null;
  currentPage: number = 1;
  itemsPerPage: number = 8;
  totalItems: number = 0;
  categories: Category[] = [];
  categoryNames: { [key: number]: string } = {};
  subcategoryNames: { [key: number]: string } = {};
  initialLoadComplete = false;
  manufacturerNames: { [key: number]: string } = {};
  manufacturers: Manufacturer[] = [];

  products: Product[] = [];
  private subscriptions: Subscription[] = [];


  constructor(
    private productService: ProductService,
    private catalogService: CatalogService,
    private sidebarService: SidebarService,
    private cartService: CartService
  ) {}

  ngOnInit(): void {
    this.loadCategories();
    this.loadManufacturers();
    this.loadInitialProducts();

    this.subscriptions.push(
      this.catalogService.selectedCategory$.subscribe(categoryId => {
        this.selectedCategoryId = categoryId;
        this.loadProducts();
      }),
      this.catalogService.selectedSubcategoryId$.subscribe(subcategoryId => {
        this.selectedSubcategory = subcategoryId;
        this.loadProducts();
      }),
      this.catalogService.selectedManufacturer$.subscribe(manufacturerId => {
        this.selectedManufacturerId = manufacturerId;
        this.loadProducts();
      }),
      this.catalogService.selectedSubcategoryId$.subscribe(subcategoryId => {
        this.selectedSubcategory = subcategoryId;

        if (subcategoryId !== null) {
          this.selectedSubcategoryName = this.getSubcategoryName(subcategoryId);
        } else {
          this.selectedSubcategoryName = null;
        }

        this.loadProducts();
      })
    );
}

  ngOnDestroy(): void {
    this.subscriptions.forEach(sub => sub.unsubscribe());
  }

  private loadCategories(): void {
    this.sidebarService.getCategories().subscribe(categories => {
      this.categories = categories;
      this.categoryNames = categories.reduce((acc: { [key: number]: string }, category) => {
        acc[category.id] = category.name;
        return acc;
      }, {});
      this.loadSubcategoryNames();
    });

  }

  addToCart(product: Product): void {
    if (product.stock > 0) {
      this.cartService.addToCart(product);
    }
  }



  private loadSubcategoryNames(): void {
    this.categories.forEach(category => {
      if (category.subcategories) {
        category.subcategories.forEach(subcategory => {
          this.subcategoryNames[subcategory.id] = subcategory.name;
        });
      }
    });
  }

  getSubcategoryName(subcategoryId: number): string {
    return this.subcategoryNames[subcategoryId] || 'Subcategoría desconocida';
  }

  private loadManufacturers(): void {
    this.sidebarService.getManufacturers().subscribe(manufacturers => {
      this.manufacturers = manufacturers;
      this.manufacturerNames = manufacturers.reduce((acc: { [key: number]: string }, manufacturer) => {
        acc[manufacturer.id] = manufacturer.name;
        return acc;
      }, {});
    });
  }

  private loadInitialProducts(): void {
    this.initialLoadComplete = false;
    this.currentPage = 1;
    this.selectedCategoryId = null;
    this.selectedSubcategory = null;
    this.selectedManufacturerId = null;

    const filters = {
      page: this.currentPage,
      perPage: this.itemsPerPage
    };

    this.productService.getProducts(filters).subscribe({
      next: (response) => {
        this.products = response.products;
        this.totalItems = response.total;
        this.initialLoadComplete = true;
      },
      error: (error) => {
        console.error('Error loading products', error);
        this.products = [];
        this.totalItems = 0;
        this.initialLoadComplete = true;
      }
    });
  }

  private loadProducts(): void {
    if (!this.initialLoadComplete &&
        !this.selectedCategoryId &&
        !this.selectedSubcategory &&
        !this.selectedManufacturerId) {
        return;
    }

    const filters = {
        id_categoria: this.selectedCategoryId || undefined,
        id_subcategoria: this.selectedSubcategory || undefined,
        id_fabricante: this.selectedManufacturerId || undefined,
        page: this.currentPage,
        perPage: this.itemsPerPage
    };

    console.log('Loading products with filters:', filters);

    this.productService.getProducts(filters).subscribe({
        next: (response) => {
            console.log('Products loaded:', response);
            this.products = response.products;
            this.totalItems = response.total;
            if (!this.initialLoadComplete) {
                this.initialLoadComplete = true;
            }
        },
        error: (error) => {
            console.error('Error loading products', error);
            this.products = [];
            this.totalItems = 0;
        }
    });
}
  getManufacturerName(manufacturerId: number): string {
    return this.manufacturerNames[manufacturerId] || 'Fabricante desconocido';
  }


  onPageChange(page: number): void {
    this.currentPage = page;
    this.loadProducts();
    window.scrollTo(0, 0);
  }

  clearFilters(): void {
    this.currentPage = 1;
    this.selectedCategoryId = null;
    this.selectedSubcategory = null;
    this.selectedSubcategoryName = null;
    this.selectedManufacturerId = null;
    this.catalogService.clearFilters();
    this.sidebarService.notifyFiltersClear();
    this.loadInitialProducts();
}
}
